<?php
/**
 * Import GA4 aggregate history into the local analytics backfill table.
 *
 * Usage:
 *   php wp-content/mu-plugins/smpt-site/tools/import-ga4-history.php
 *   php wp-content/mu-plugins/smpt-site/tools/import-ga4-history.php --input-dir=/path/to/GA4_Analytics_...
 *   php wp-content/mu-plugins/smpt-site/tools/import-ga4-history.php --reset
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "This script must be run from the command line.\n" );
	exit( 1 );
}

$args = smpt_ga4_import_parse_args( $argv );

$input_dir = isset( $args['input-dir'] ) && '' !== $args['input-dir']
	? $args['input-dir']
	: smpt_ga4_import_latest_dir();

if ( ! $input_dir || ! is_dir( $input_dir ) ) {
	fwrite( STDERR, "Could not find a GA4 export directory.\n" );
	exit( 1 );
}

$csv_path = rtrim( $input_dir, "/\\" ) . DIRECTORY_SEPARATOR . 'event_items_by_date.csv';
if ( ! is_file( $csv_path ) ) {
	fwrite( STDERR, "Missing required file: {$csv_path}\n" );
	exit( 1 );
}

$connection = smpt_ga4_import_connect();
smpt_ga4_import_ensure_table( $connection );

if ( isset( $args['reset'] ) ) {
	$connection->query( 'TRUNCATE TABLE wp_smpt_ga4_history' );
}

$handle = fopen( $csv_path, 'r' );
if ( false === $handle ) {
	fwrite( STDERR, "Unable to open {$csv_path}\n" );
	exit( 1 );
}

$headers = fgetcsv( $handle );
if ( ! is_array( $headers ) ) {
	fwrite( STDERR, "CSV file is empty: {$csv_path}\n" );
	exit( 1 );
}

$header_map = smpt_ga4_import_header_map( $headers );
$required   = array( 'date', 'event_name', 'event_category', 'event_label', 'event_count' );

foreach ( $required as $key ) {
	if ( ! isset( $header_map[ $key ] ) ) {
		fwrite( STDERR, "Missing required column for {$key} in {$csv_path}\n" );
		exit( 1 );
	}
}

$sql = "INSERT INTO wp_smpt_ga4_history
	(event_date, event_type, item_id, event_count)
	VALUES (?, ?, ?, ?)
	ON DUPLICATE KEY UPDATE
		event_count = VALUES(event_count)";

$statement = $connection->prepare( $sql );
if ( ! $statement ) {
	fwrite( STDERR, "Failed to prepare import statement: {$connection->error}\n" );
	exit( 1 );
}

$imported    = 0;
$skipped     = 0;
$totals      = array();

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
	$event_name = trim( smpt_ga4_import_cell( $row, $header_map['event_name'] ) );
	$category   = trim( smpt_ga4_import_cell( $row, $header_map['event_category'] ) );
	$item_id    = trim( smpt_ga4_import_cell( $row, $header_map['event_label'] ) );
	$event_type = smpt_ga4_import_map_event_type( $event_name, $category );
	$event_date = smpt_ga4_import_normalize_date( smpt_ga4_import_cell( $row, $header_map['date'] ) );

	if ( '' === $event_type || '' === $event_date ) {
		$skipped++;
		continue;
	}

	$event_count = (int) smpt_ga4_import_cell( $row, $header_map['event_count'] );
	$statement->bind_param(
		'sssi',
		$event_date,
		$event_type,
		$item_id,
		$event_count
	);

	if ( ! $statement->execute() ) {
		fwrite( STDERR, "Import failed for {$event_name}/{$category}/{$item_id}: {$statement->error}\n" );
		exit( 1 );
	}

	$imported++;
	$totals[ $event_type ] = ( $totals[ $event_type ] ?? 0 ) + $event_count;
}

fclose( $handle );
$statement->close();
$connection->close();

ksort( $totals );

fwrite( STDOUT, "Imported {$imported} rows from {$csv_path}\n" );
if ( $skipped > 0 ) {
	fwrite( STDOUT, "Skipped {$skipped} rows that did not map to local analytics event types.\n" );
}
foreach ( $totals as $event_type => $count ) {
	fwrite( STDOUT, "  {$event_type}: {$count}\n" );
}

exit( 0 );

function smpt_ga4_import_parse_args( array $argv ) {
	$args = array();

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( 0 === strpos( $arg, '--' ) ) {
			$parts         = explode( '=', substr( $arg, 2 ), 2 );
			$key           = $parts[0];
			$args[ $key ]  = $parts[1] ?? true;
		}
	}

	return $args;
}

function smpt_ga4_import_latest_dir() {
	$matches = glob( __DIR__ . '/../GA4_Analytics_*', GLOB_ONLYDIR );

	if ( ! is_array( $matches ) || empty( $matches ) ) {
		return '';
	}

	usort(
		$matches,
		static function ( $left, $right ) {
			return filemtime( $right ) <=> filemtime( $left );
		}
	);

	return $matches[0];
}

function smpt_ga4_import_connect() {
	$host     = getenv( 'SMPT_DB_HOST' ) ?: '127.0.0.1';
	$port     = (int) ( getenv( 'SMPT_DB_PORT' ) ?: 10011 );
	$database = getenv( 'SMPT_DB_NAME' ) ?: 'local';
	$user     = getenv( 'SMPT_DB_USER' ) ?: 'root';
	$password = getenv( 'SMPT_DB_PASSWORD' ) ?: 'root';

	$connection = mysqli_init();
	if ( ! $connection ) {
		fwrite( STDERR, "Failed to initialize mysqli.\n" );
		exit( 1 );
	}

	if ( ! $connection->real_connect( $host, $user, $password, $database, $port ) ) {
		fwrite( STDERR, "Failed to connect to MySQL: {$connection->connect_error}\n" );
		exit( 1 );
	}

	$connection->set_charset( 'utf8mb4' );

	return $connection;
}

function smpt_ga4_import_ensure_table( mysqli $connection ) {
	$sql = "CREATE TABLE IF NOT EXISTS wp_smpt_ga4_history (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		event_date DATE NOT NULL,
		event_type VARCHAR(20) NOT NULL,
		item_id VARCHAR(191) NOT NULL DEFAULT '',
		event_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY ga4_event_item (event_date, event_type, item_id),
		KEY event_type (event_type),
		KEY event_date (event_date)
	) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";

	if ( ! $connection->query( $sql ) ) {
		fwrite( STDERR, "Failed to ensure wp_smpt_ga4_history exists: {$connection->error}\n" );
		exit( 1 );
	}

	smpt_ga4_import_optimize_table( $connection );
}

function smpt_ga4_import_header_map( array $headers ) {
	$map = array();

	foreach ( $headers as $index => $header ) {
		$name = trim( (string) $header );
		if ( 'date' === $name ) {
			$map['date'] = $index;
		} elseif ( 'eventName' === $name ) {
			$map['event_name'] = $index;
		} elseif ( 'eventCount' === $name ) {
			$map['event_count'] = $index;
		} elseif ( str_ends_with( $name, 'event_category' ) ) {
			$map['event_category'] = $index;
		} elseif ( str_ends_with( $name, 'event_label' ) ) {
			$map['event_label'] = $index;
		}
	}

	return $map;
}

function smpt_ga4_import_cell( array $row, $index ) {
	return isset( $row[ $index ] ) ? (string) $row[ $index ] : '';
}

function smpt_ga4_import_map_event_type( $event_name, $category ) {
	$event_name = strtolower( trim( (string) $event_name ) );
	$category   = strtolower( trim( (string) $category ) );

	if ( 'stream' === $event_name && 'anime' === $category ) {
		return 'stream';
	}

	if ( 'stream' === $event_name && 'musica' === $category ) {
		return 'music_stream';
	}

	if ( in_array( $event_name, array( 'download', 'episodios' ), true ) && in_array( $category, array( 'anime', 'downloads' ), true ) ) {
		return 'download';
	}

	if ( 'download' === $event_name && 'manga' === $category ) {
		return 'manga_download';
	}

	if ( in_array( $event_name, array( 'visualizacao', 'visualização' ), true ) && 'manga' === $category ) {
		return 'manga_view';
	}

	return '';
}

function smpt_ga4_import_optimize_table( mysqli $connection ) {
	$result = $connection->query( 'SHOW COLUMNS FROM wp_smpt_ga4_history' );

	if ( ! $result ) {
		return;
	}

	$existing = array();
	while ( $row = $result->fetch_assoc() ) {
		if ( isset( $row['Field'] ) ) {
			$existing[] = (string) $row['Field'];
		}
	}

	$legacy_columns = array(
		'total_users',
		'original_event_name',
		'original_category',
		'source_file',
		'imported_at',
	);
	$to_drop = array_values( array_intersect( $legacy_columns, $existing ) );

	if ( empty( $to_drop ) ) {
		return;
	}

	$drop_sql = array();
	foreach ( $to_drop as $column ) {
		$drop_sql[] = 'DROP COLUMN `' . $connection->real_escape_string( $column ) . '`';
	}

	if ( ! $connection->query( 'ALTER TABLE wp_smpt_ga4_history ' . implode( ', ', $drop_sql ) ) ) {
		fwrite( STDERR, "Failed to slim wp_smpt_ga4_history: {$connection->error}\n" );
		exit( 1 );
	}
}

function smpt_ga4_import_normalize_date( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/^\d{8}$/', $value ) ) {
		return substr( $value, 0, 4 ) . '-' . substr( $value, 4, 2 ) . '-' . substr( $value, 6, 2 );
	}

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return $value;
	}

	return '';
}
