#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { GoogleAuth } = require('google-auth-library');

const DEFAULT_GA4_URL =
	'https://analytics.google.com/analytics/web/#/a26168234p416744088/reports/intelligenthome';
const DEFAULT_PROPERTY_ID = parsePropertyId(DEFAULT_GA4_URL);
const PROPERTY_ID = process.env.GA4_PROPERTY_ID || parsePropertyId(process.env.GA4_PROPERTY_URL || '') || DEFAULT_PROPERTY_ID;
const START_DATE = process.env.GA4_START_DATE || '365daysAgo';
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
			'This export uses the GA4 Data API and Admin API, not the Analytics UI.',
			'Custom event names are included in the event reports.',
			'Custom dimensions and metrics are exported individually when available.',
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

	const customDimensions = await listAllPages(
		client,
		`https://analyticsadmin.googleapis.com/v1beta/properties/${PROPERTY_ID}/customDimensions`
	);
	writeJson(manifest, 'custom-dimensions.json', customDimensions);

	const customMetrics = await listAllPages(
		client,
		`https://analyticsadmin.googleapis.com/v1beta/properties/${PROPERTY_ID}/customMetrics`
	);
	writeJson(manifest, 'custom-metrics.json', customMetrics);

	const standardReports = [
		{
			fileBase: 'events_by_date',
			body: {
				dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
				dimensions: [{ name: 'date' }, { name: 'eventName' }],
				metrics: [
					{ name: 'eventCount' },
					{ name: 'totalUsers' },
					{ name: 'activeUsers' },
					{ name: 'eventCountPerUser' },
				],
				orderBys: [{ dimension: { dimensionName: 'date' } }, { dimension: { dimensionName: 'eventName' } }],
			},
		},
		{
			fileBase: 'pages_by_date',
			body: {
				dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
				dimensions: [{ name: 'date' }, { name: 'pagePathPlusQueryString' }],
				metrics: [{ name: 'screenPageViews' }, { name: 'totalUsers' }],
				orderBys: [{ dimension: { dimensionName: 'date' } }],
			},
		},
		{
			fileBase: 'traffic_by_date',
			body: {
				dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
				dimensions: [{ name: 'date' }, { name: 'sessionSourceMedium' }],
				metrics: [{ name: 'sessions' }, { name: 'totalUsers' }],
				orderBys: [{ dimension: { dimensionName: 'date' } }],
			},
		},
	];

	for (const report of standardReports) {
		const result = await runPagedReport(client, PROPERTY_ID, report.body);
		writeReport(manifest, report.fileBase, result);
	}

	const customDimensionNames = extractCustomDimensionNames(metadata);
	for (const dimensionName of customDimensionNames) {
		try {
			const result = await runPagedReport(client, PROPERTY_ID, {
				dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
				dimensions: [{ name: 'date' }, { name: 'eventName' }, { name: dimensionName }],
				metrics: [{ name: 'eventCount' }],
				orderBys: [{ dimension: { dimensionName: 'date' } }],
			});
			writeReport(manifest, `custom_dimension_${safeFileName(dimensionName)}`, result);
		} catch (error) {
			recordError(manifest, `custom-dimension:${dimensionName}`, error);
		}
	}

	const customMetricNames = extractCustomMetricNames(metadata);
	for (const metricName of customMetricNames) {
		try {
			const result = await runPagedReport(client, PROPERTY_ID, {
				dateRanges: [{ startDate: START_DATE, endDate: END_DATE }],
				dimensions: [{ name: 'date' }, { name: 'eventName' }],
				metrics: [{ name: 'eventCount' }, { name: metricName }],
				orderBys: [{ dimension: { dimensionName: 'date' } }],
			});
			writeReport(manifest, `custom_metric_${safeFileName(metricName)}`, result);
		} catch (error) {
			recordError(manifest, `custom-metric:${metricName}`, error);
		}
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

async function listAllPages(client, url) {
	let nextPageToken = '';
	const all = [];

	do {
		const pageUrl = nextPageToken
			? `${url}?pageToken=${encodeURIComponent(nextPageToken)}`
			: url;
		const response = await requestJson(client, 'GET', pageUrl);
		const values = response.customDimensions || response.customMetrics || [];
		all.push(...values);
		nextPageToken = response.nextPageToken || '';
	} while (nextPageToken);

	return all;
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

function extractCustomDimensionNames(metadata) {
	return unique(
		(metadata.dimensions || [])
			.map((item) => item.apiName)
			.filter((name) => typeof name === 'string')
			.filter((name) => name.startsWith('customEvent:') || name.startsWith('customUser:'))
	);
}

function extractCustomMetricNames(metadata) {
	return unique(
		(metadata.metrics || [])
			.map((item) => item.apiName)
			.filter((name) => typeof name === 'string')
			.filter((name) => name.startsWith('customEvent:'))
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

function safeFileName(value) {
	return String(value)
		.replace(/[^a-zA-Z0-9._-]+/g, '_')
		.replace(/^_+|_+$/g, '')
		.slice(0, 120) || 'report';
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

function unique(values) {
	return [...new Set(values)];
}
