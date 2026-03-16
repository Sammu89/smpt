# GA4 Backfill

This project imports only the historical GA4 data needed for rankings and period totals:

- streamed items
- downloaded items
- music streams

The import stores one daily aggregate row per:

- `event_date`
- `event_type`
- `item_id`
- `event_count`

It does not import fake visitors, fake timestamps, browser data, or other UI-only aggregates.

## Files

- Exporter: `wp-content/mu-plugins/smpt-site/tools/export-ga4.js`
- Importer: `wp-content/mu-plugins/smpt-site/tools/import-ga4-history.php`

## Requirements

- Google service-account JSON with GA4 property access
- `GOOGLE_APPLICATION_CREDENTIALS` pointing to that JSON file
- `GA4_PROPERTY_ID` set to the GA4 property ID

Example values for this project:

```powershell
$env:GOOGLE_APPLICATION_CREDENTIALS="C:\Users\Sammu\Downloads\sammu-great-site-3ad4462b890d.json"
$env:GA4_PROPERTY_ID="416744088"
```

Default export behavior:

- if `GA4_START_DATE` is not set, the exporter requests full history using `2015-08-14`
- GA4 will return data only from the earliest date that actually exists in the property

## Commands

Export only:

```powershell
npm run ga4:export
```

Import the latest export folder without clearing old rows first:

```powershell
npm run ga4:import
```

Import the latest export folder and reset the GA4 history table first:

```powershell
npm run ga4:import:reset
```

Recommended full refresh:

```powershell
npm run ga4:backfill
```

`ga4:backfill` does this:

1. exports the latest GA4 history into a new `GA4_Analytics_<timestamp>` folder
2. clears `wp_smpt_ga4_history`
3. imports the newest export automatically

## Output

The exporter writes a new folder under:

```text
wp-content/mu-plugins/smpt-site/GA4_Analytics_<timestamp>/
```

The minimal export contains:

- `metadata.json`
- `event_items_by_date.csv`
- `event_items_by_date.json`
- `manifest.json`

## Database table

Historical GA4 aggregates are stored in:

```text
wp_smpt_ga4_history
```

Columns:

- `id`
- `event_date`
- `event_type`
- `item_id`
- `event_count`

## Notes

- The importer automatically uses the newest `GA4_Analytics_<timestamp>` folder when `--input-dir` is not provided.
- Historical GA4 data is daily only. It does not include event-level time-of-day.
- For exact raw GA4 events, BigQuery export would be required.
