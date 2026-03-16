#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { GoogleAuth } = require('google-auth-library');

const DEFAULT_GA4_URL =
	'https://analytics.google.com/analytics/web/#/a26168234p416744088/reports/intelligenthome';
const DEFAULT_PROPERTY_ID = parsePropertyId(DEFAULT_GA4_URL);
const PROPERTY_ID = process.env.GA4_PROPERTY_ID || parsePropertyId(process.env.GA4_PROPERTY_URL || '') || DEFAULT_PROPERTY_ID;
const START_DATE = process.env.GA4_START_DATE || '2015-08-14';
const END_DATE = process.env.GA4_END_DATE || 'today';
const OUTPUT_ROOT = path.resolve(__dirname, '..');
const TIMESTAMP = buildTimestamp();
const OUTPUT_DIR = path.join(OUTPUT_ROOT, `GA4_Analytics_${TIMESTAMP}`);
const ANALYTICS_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
const PAGE_SIZE = 100000;

main().catch((error) => {
	console.error('\nGA4 export failed.');
	console.error(error && error.message ? error.message : error);
	process.exitCode = 1;
});

async function main() {
	if (!PROPERTY_ID) {
		throw new Error('No GA4 property ID available. Set GA4_PROPERTY_ID or GA4_PROPERTY_URL.');
	}

	ensureDir(OUTPUT_DIR);

	const auth = buildGoogleAuth();
	const client = await auth.getClient();
	const manifest = {
		propertyId: PROPERTY_ID,
		propertyUrl: process.env.GA4_PROPERTY_URL || DEFAULT_GA4_URL,
		startDate: START_DATE,
		endDate: END_DATE,
		outputDir: OUTPUT_DIR,
		generatedAt: new Date().toISOString(),
		notes: [
			'This export uses the GA4 Data API, not the Analytics UI.',
			'It writes only the daily item counts needed for historical rankings and period-based totals.',
			'If you need raw event-level GA4 data, use GA4 BigQuery export instead.',
		],
		files: [],
		errors: [],
	};

	console.log(`Exporting GA4 property ${PROPERTY_ID} into ${OUTPUT_DIR}`);

	const metadata = await requestJson(
		client,
		'GET',
		`https://analyticsdata.googleapis.com/v1beta/properties/${PROPERTY_ID}/metadata`
	);
	writeJson(manifest, 'metadata.json', metadata);

	const eventCategoryDimension = findCustomDimension(metadata, 'event_category');
	const eventLabelDimension = findCustomDimension(metadata, 'event_label');

	if (eventCategoryDimension && eventLabelDimension) {
		const result = await runPagedReport(client, PROPERTY_ID, {
			dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
			dimensions: [
				{ name: 'date' },
				{ name: 'eventName' },
				{ name: eventCategoryDimension },
				{ name: eventLabelDimension },
			],
			metrics: [{ name: 'eventCount' }],
			orderBys: [
				{ dimension: { dimensionName: 'date' } },
				{ dimension: { dimensionName: 'eventName' } },
				{ dimension: { dimensionName: eventLabelDimension } },
			],
		});
		writeReport(manifest, 'event_items_by_date', result);
	} else {
		throw new Error(
			'Could not find custom dimensions for event_category and event_label in GA4 metadata.'
		);
	}

	fs.writeFileSync(
		path.join(OUTPUT_DIR, 'manifest.json'),
		JSON.stringify(manifest, null, 2),
		'utf8'
	);

	console.log('Export complete.');
	console.log(`Files written: ${manifest.files.length}`);
	if (manifest.errors.length > 0) {
		console.log(`Partial failures: ${manifest.errors.length} (see manifest.json)`);
	}
}

function buildGoogleAuth() {
	const inlineCredentials = process.env.GA4_SERVICE_ACCOUNT_JSON || '';

	if (inlineCredentials.trim().startsWith('{')) {
		return new GoogleAuth({
			credentials: JSON.parse(inlineCredentials),
			scopes: [ANALYTICS_SCOPE],
		});
	}

	if (inlineCredentials.trim()) {
		return new GoogleAuth({
			keyFile: path.resolve(inlineCredentials),
			scopes: [ANALYTICS_SCOPE],
		});
	}

	return new GoogleAuth({
		scopes: [ANALYTICS_SCOPE],
	});
}

async function runPagedReport(client, propertyId, baseBody) {
	let offset = 0;
	let totalRows = null;
	const merged = {
		dimensionHeaders: [],
		metricHeaders: [],
		rows: [],
		rowCount: 0,
		metadata: {},
		propertyQuota: null,
	};

	for (;;) {
		const response = await requestJson(
			client,
			'POST',
			`https://analyticsdata.googleapis.com/v1beta/properties/${propertyId}:runReport`,
			{
				...baseBody,
				limit: PAGE_SIZE,
				offset,
				keepEmptyRows: false,
				returnPropertyQuota: offset === 0,
			}
		);

		if (!merged.dimensionHeaders.length) {
			merged.dimensionHeaders = response.dimensionHeaders || [];
			merged.metricHeaders = response.metricHeaders || [];
			merged.metadata = response.metadata || {};
			merged.propertyQuota = response.propertyQuota || null;
		}

		const rows = response.rows || [];
		merged.rows.push(...rows);
		merged.rowCount = merged.rows.length;

		if (totalRows === null) {
			totalRows = Number(response.rowCount || rows.length || 0);
		}

		offset += rows.length;
		if (!rows.length || offset >= totalRows) {
			break;
		}
	}

	return merged;
}

async function requestJson(client, method, url, data) {
	const response = await client.request({
		method,
		url,
		data,
	});

	return response.data;
}

function writeReport(manifest, fileBase, report) {
	writeJson(manifest, `${fileBase}.json`, report);
	writeCsv(manifest, `${fileBase}.csv`, report);
}

function writeJson(manifest, fileName, data) {
	const filePath = path.join(OUTPUT_DIR, fileName);
	fs.writeFileSync(filePath, JSON.stringify(data, null, 2), 'utf8');
	manifest.files.push(fileName);
}

function writeCsv(manifest, fileName, report) {
	const headers = [
		...(report.dimensionHeaders || []).map((item) => item.name),
		...(report.metricHeaders || []).map((item) => item.name),
	];
	const rows = (report.rows || []).map((row) => [
		...((row.dimensionValues || []).map((item) => item.value)),
		...((row.metricValues || []).map((item) => item.value)),
	]);
	const csv = [headers, ...rows].map(csvLine).join('\n');
	fs.writeFileSync(path.join(OUTPUT_DIR, fileName), csv, 'utf8');
	manifest.files.push(fileName);
}

function recordError(manifest, context, error) {
	manifest.errors.push({
		context,
		message: error && error.message ? error.message : String(error),
	});
}

function findCustomDimension(metadata, suffix) {
	return (
		(metadata.dimensions || [])
			.map((item) => item.apiName)
			.find(
				(name) =>
					typeof name === 'string' &&
					name.startsWith('customEvent:') &&
					name.endsWith(suffix)
			) || ''
	);
}

function csvLine(values) {
	return values.map(csvEscape).join(',');
}

function csvEscape(value) {
	const normalized = value == null ? '' : String(value);
	if (/[",\n]/.test(normalized)) {
		return `"${normalized.replace(/"/g, '""')}"`;
	}
	return normalized;
}

function ensureDir(dirPath) {
	fs.mkdirSync(dirPath, { recursive: true });
}

function buildTimestamp() {
	return new Date().toISOString().replace(/[:.]/g, '-');
}

function parsePropertyId(url) {
	const match = String(url || '').match(/p(\d+)/);
	return match ? match[1] : '';
}
