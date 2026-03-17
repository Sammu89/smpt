<?php
/**
 * Episode interactions backend — likes, dislikes, ratings, watched status, and comments.
 *
 * Episodes are HTML cards (not WP posts) with IDs like episodio_001.
 * All interaction data lives in custom DB tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   Database tables
   ========================================================================= */

/**
 * Create or update episode interaction database tables.
 */
function smpt_ep_maybe_create_tables() {
	$db_ver    = '1.1';
	$installed = get_option( 'smpt_ep_interactions_db_ver', '' );

	if ( $db_ver === $installed ) {
		return;
	}

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$wpdb->prefix}smpt_episode_interactions (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NULL,
		anon_hash VARBINARY(32) NULL,
		episode_id SMALLINT UNSIGNED NOT NULL,
		interaction_type VARCHAR(20) NOT NULL,
		value DECIMAL(3,1) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY user_ep_type (user_id, episode_id, interaction_type),
		UNIQUE KEY anon_ep_type (anon_hash, episode_id, interaction_type),
		KEY episode_id (episode_id)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_episode_comments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NULL,
		anon_hash VARBINARY(32) NULL,
		author_name VARCHAR(100) NOT NULL DEFAULT '',
		author_email VARCHAR(200) NOT NULL DEFAULT '',
		episode_id SMALLINT UNSIGNED NOT NULL,
		comment_text TEXT NOT NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY ep_created (episode_id, created_at)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_episode_counters (
		episode_id SMALLINT UNSIGNED NOT NULL,
		likes INT UNSIGNED NOT NULL DEFAULT 0,
		dislikes INT UNSIGNED NOT NULL DEFAULT 0,
		rating_sum DECIMAL(10,1) NOT NULL DEFAULT 0,
		rating_count INT UNSIGNED NOT NULL DEFAULT 0,
		comment_count INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (episode_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'smpt_ep_interactions_db_ver', $db_ver );
}
add_action( 'init', 'smpt_ep_maybe_create_tables' );

/* =========================================================================
   Core helpers
   ========================================================================= */

/**
 * Hash an anonymous visitor UUID into a 32-byte binary value.
 *
 * @param string $uuid Raw visitor UUID from the frontend.
 * @return string Raw binary hash (32 bytes).
 */
function smpt_ep_hash_anon_uuid( $uuid ) {
	return hash_hmac( 'sha256', $uuid, wp_salt( 'auth' ), true );
}

/**
 * Resolve actor identity from a user ID and/or anonymous UUID.
 *
 * @param int    $user_id   WordPress user ID (0 if not logged in).
 * @param string $anon_uuid Raw visitor UUID from the frontend.
 * @return array Associative array with 'user_id' and 'anon_hash' keys.
 */
function smpt_ep_resolve_actor( $user_id, $anon_uuid ) {
	if ( $user_id > 0 ) {
		return array(
			'user_id'   => $user_id,
			'anon_hash' => null,
		);
	}

	return array(
		'user_id'   => null,
		'anon_hash' => smpt_ep_hash_anon_uuid( $anon_uuid ),
	);
}

/**
 * Ensure a counter row exists for a given episode.
 *
 * @param int $ep_id Episode number (1-200).
 */
function smpt_ep_ensure_counter( $ep_id ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO {$wpdb->prefix}smpt_episode_counters (episode_id) VALUES (%d)",
		$ep_id
	) );
}

/**
 * Build a WHERE clause fragment and params for actor-based lookups.
 *
 * @param array $actor Result from smpt_ep_resolve_actor().
 * @return array [ 'clause' => string, 'params' => array ]
 */
function smpt_ep_actor_where( $actor ) {
	if ( null !== $actor['user_id'] ) {
		return array(
			'clause' => 'user_id = %d',
			'params' => array( $actor['user_id'] ),
		);
	}

	return array(
		'clause' => 'anon_hash = %s',
		'params' => array( $actor['anon_hash'] ),
	);
}

/* =========================================================================
   Reaction state machine (like / dislike)
   ========================================================================= */

/**
 * Set or remove a like/dislike reaction for an episode.
 *
 * @param int    $ep_id     Episode number (1-200).
 * @param int    $user_id   WordPress user ID (0 if anon).
 * @param string $anon_uuid Visitor UUID from frontend.
 * @param string $intent    One of: like, remove_like, dislike, remove_dislike.
 * @return bool True on success.
 */
function smpt_ep_set_reaction( $ep_id, $user_id, $anon_uuid, $intent ) {
	global $wpdb;

	$actor   = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$aw      = smpt_ep_actor_where( $actor );
	$table   = $wpdb->prefix . 'smpt_episode_interactions';
	$counter = $wpdb->prefix . 'smpt_episode_counters';
	$now     = current_time( 'mysql' );

	// Advisory lock key.
	$lock_key = null !== $actor['user_id']
		? 'smpt_ep_react_' . $ep_id . '_u' . $actor['user_id']
		: 'smpt_ep_react_' . $ep_id . '_a' . bin2hex( $actor['anon_hash'] );

	$wpdb->query( $wpdb->prepare( "SELECT GET_LOCK(%s, 5)", $lock_key ) );
	$wpdb->query( 'START TRANSACTION' );

	smpt_ep_ensure_counter( $ep_id );

	$base_where  = $wpdb->prepare( "episode_id = %d AND {$aw['clause']}", array_merge( array( $ep_id ), $aw['params'] ) );

	switch ( $intent ) {
		case 'like':
			// Check for existing dislike and remove it.
			$has_dislike = $wpdb->get_var( "SELECT id FROM {$table} WHERE {$base_where} AND interaction_type = 'dislike'" );
			if ( $has_dislike ) {
				$wpdb->query( "DELETE FROM {$table} WHERE id = {$has_dislike}" );
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET dislikes = GREATEST(dislikes - 1, 0) WHERE episode_id = %d",
					$ep_id
				) );
			}

			// Upsert like.
			if ( null !== $actor['user_id'] ) {
				$wpdb->query( $wpdb->prepare(
					"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
					 VALUES (%d, NULL, %d, 'like', %s, %s)
					 ON DUPLICATE KEY UPDATE updated_at = %s",
					$actor['user_id'], $ep_id, $now, $now, $now
				) );
			} else {
				$wpdb->query( $wpdb->prepare(
					"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
					 VALUES (NULL, %s, %d, 'like', %s, %s)
					 ON DUPLICATE KEY UPDATE updated_at = %s",
					$actor['anon_hash'], $ep_id, $now, $now, $now
				) );
			}

			// Only increment if we actually inserted (not a dupe update).
			if ( $wpdb->rows_affected > 0 && false === $wpdb->get_var( "SELECT id FROM {$table} WHERE {$base_where} AND interaction_type = 'like' AND created_at != updated_at AND updated_at = '{$wpdb->_real_escape( $now )}'" ) ) {
				// Simpler: just use INSERT ... ON DUPLICATE KEY for counter too.
			}
			// Always recalc is safest for likes counter, but increment approach:
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$counter} SET likes = (
					SELECT COUNT(*) FROM {$table} WHERE episode_id = %d AND interaction_type = 'like'
				) WHERE episode_id = %d",
				$ep_id, $ep_id
			) );
			break;

		case 'dislike':
			// Check for existing like and remove it.
			$has_like = $wpdb->get_var( "SELECT id FROM {$table} WHERE {$base_where} AND interaction_type = 'like'" );
			if ( $has_like ) {
				$wpdb->query( "DELETE FROM {$table} WHERE id = {$has_like}" );
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET likes = GREATEST(likes - 1, 0) WHERE episode_id = %d",
					$ep_id
				) );
			}

			// Upsert dislike.
			if ( null !== $actor['user_id'] ) {
				$wpdb->query( $wpdb->prepare(
					"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
					 VALUES (%d, NULL, %d, 'dislike', %s, %s)
					 ON DUPLICATE KEY UPDATE updated_at = %s",
					$actor['user_id'], $ep_id, $now, $now, $now
				) );
			} else {
				$wpdb->query( $wpdb->prepare(
					"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
					 VALUES (NULL, %s, %d, 'dislike', %s, %s)
					 ON DUPLICATE KEY UPDATE updated_at = %s",
					$actor['anon_hash'], $ep_id, $now, $now, $now
				) );
			}

			$wpdb->query( $wpdb->prepare(
				"UPDATE {$counter} SET dislikes = (
					SELECT COUNT(*) FROM {$table} WHERE episode_id = %d AND interaction_type = 'dislike'
				) WHERE episode_id = %d",
				$ep_id, $ep_id
			) );
			break;

		case 'remove_like':
			$deleted = $wpdb->query( "DELETE FROM {$table} WHERE {$base_where} AND interaction_type = 'like'" );
			if ( $deleted ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET likes = GREATEST(likes - 1, 0) WHERE episode_id = %d",
					$ep_id
				) );
			}
			break;

		case 'remove_dislike':
			$deleted = $wpdb->query( "DELETE FROM {$table} WHERE {$base_where} AND interaction_type = 'dislike'" );
			if ( $deleted ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET dislikes = GREATEST(dislikes - 1, 0) WHERE episode_id = %d",
					$ep_id
				) );
			}
			break;
	}

	$wpdb->query( 'COMMIT' );
	$wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_key ) );

	return true;
}

/* =========================================================================
   Ratings
   ========================================================================= */

/**
 * Set or update a rating for an episode.
 *
 * @param int    $ep_id     Episode number (1-200).
 * @param int    $user_id   WordPress user ID (0 if anon).
 * @param string $anon_uuid Visitor UUID from frontend.
 * @param float  $value     Rating value (0.5 to 5.0, step 0.5).
 * @return bool True on success, false on invalid value.
 */
function smpt_ep_set_rating( $ep_id, $user_id, $anon_uuid, $value ) {
	global $wpdb;

	$value = round( $value * 2 ) / 2; // snap to 0.5 step
	if ( $value < 0.5 || $value > 5.0 ) {
		return false;
	}

	$actor   = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$aw      = smpt_ep_actor_where( $actor );
	$table   = $wpdb->prefix . 'smpt_episode_interactions';
	$counter = $wpdb->prefix . 'smpt_episode_counters';
	$now     = current_time( 'mysql' );

	smpt_ep_ensure_counter( $ep_id );

	// Check for existing rating.
	$existing = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, value FROM {$table} WHERE episode_id = %d AND {$aw['clause']} AND interaction_type = 'rating'",
		array_merge( array( $ep_id ), $aw['params'] )
	) );

	if ( $existing ) {
		$old_val = (float) $existing->value;
		$diff    = $value - $old_val;

		$wpdb->update(
			$table,
			array( 'value' => $value, 'updated_at' => $now ),
			array( 'id' => $existing->id ),
			array( '%f', '%s' ),
			array( '%d' )
		);

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$counter} SET rating_sum = rating_sum + %f WHERE episode_id = %d",
			$diff, $ep_id
		) );
	} else {
		// Insert new rating.
		$insert_data = array(
			'episode_id'       => $ep_id,
			'interaction_type' => 'rating',
			'value'            => $value,
			'created_at'       => $now,
			'updated_at'       => $now,
		);
		$insert_fmt = array( '%d', '%s', '%f', '%s', '%s' );

		if ( null !== $actor['user_id'] ) {
			$insert_data['user_id']   = $actor['user_id'];
			$insert_data['anon_hash'] = null;
			$insert_fmt[]             = '%d';
			$insert_fmt[]             = null; // NULL handled by wpdb
		} else {
			$insert_data['user_id']   = null;
			$insert_data['anon_hash'] = $actor['anon_hash'];
		}

		$wpdb->insert( $table, $insert_data );

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$counter} SET rating_sum = rating_sum + %f, rating_count = rating_count + 1 WHERE episode_id = %d",
			$value, $ep_id
		) );
	}

	return true;
}

/**
 * Remove a rating for an episode.
 *
 * @param int    $ep_id     Episode number (1-200).
 * @param int    $user_id   WordPress user ID (0 if anon).
 * @param string $anon_uuid Visitor UUID from frontend.
 * @return bool True if a rating was deleted.
 */
function smpt_ep_remove_rating( $ep_id, $user_id, $anon_uuid ) {
	global $wpdb;

	$actor   = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$aw      = smpt_ep_actor_where( $actor );
	$table   = $wpdb->prefix . 'smpt_episode_interactions';
	$counter = $wpdb->prefix . 'smpt_episode_counters';

	$existing = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, value FROM {$table} WHERE episode_id = %d AND {$aw['clause']} AND interaction_type = 'rating'",
		array_merge( array( $ep_id ), $aw['params'] )
	) );

	if ( ! $existing ) {
		return false;
	}

	$wpdb->delete( $table, array( 'id' => $existing->id ), array( '%d' ) );

	$wpdb->query( $wpdb->prepare(
		"UPDATE {$counter} SET rating_sum = GREATEST(rating_sum - %f, 0), rating_count = GREATEST(rating_count - 1, 0) WHERE episode_id = %d",
		(float) $existing->value, $ep_id
	) );

	return true;
}

/* =========================================================================
   Watched status (logged-in only)
   ========================================================================= */

/**
 * Mark an episode as watched for a logged-in user.
 *
 * @param int $ep_id   Episode number (1-200).
 * @param int $user_id WordPress user ID.
 * @return bool True on success.
 */
function smpt_ep_set_watched( $ep_id, $user_id ) {
	global $wpdb;

	if ( $user_id <= 0 ) {
		return false;
	}

	$table = $wpdb->prefix . 'smpt_episode_interactions';
	$now   = current_time( 'mysql' );

	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
		 VALUES (%d, NULL, %d, 'watched', %s, %s)
		 ON DUPLICATE KEY UPDATE updated_at = %s",
		$user_id, $ep_id, $now, $now, $now
	) );

	return true;
}

/**
 * Remove watched status for an episode.
 *
 * @param int $ep_id   Episode number (1-200).
 * @param int $user_id WordPress user ID.
 * @return bool True if a row was deleted.
 */
function smpt_ep_remove_watched( $ep_id, $user_id ) {
	global $wpdb;

	if ( $user_id <= 0 ) {
		return false;
	}

	$table   = $wpdb->prefix . 'smpt_episode_interactions';
	$deleted = $wpdb->delete(
		$table,
		array(
			'user_id'          => $user_id,
			'episode_id'       => $ep_id,
			'interaction_type' => 'watched',
		),
		array( '%d', '%d', '%s' )
	);

	return (bool) $deleted;
}

/**
 * Get all watched episode IDs for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Array of episode numbers.
 */
function smpt_ep_get_watched_episodes( $user_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'smpt_episode_interactions';

	$rows = $wpdb->get_col( $wpdb->prepare(
		"SELECT episode_id FROM {$table} WHERE user_id = %d AND interaction_type = 'watched' ORDER BY episode_id",
		$user_id
	) );

	return array_map( 'intval', $rows );
}

/* =========================================================================
   Comments
   ========================================================================= */

/**
 * Add a comment to an episode.
 *
 * @param int    $ep_id        Episode number (1-200).
 * @param int    $user_id      WordPress user ID (0 if anon).
 * @param string $anon_uuid    Visitor UUID from frontend.
 * @param string $author_name  Display name for the commenter.
 * @param string $text         Comment text.
 * @param string $author_email Email for anonymous commenters.
 * @return int|false Inserted comment ID or false on failure.
 */
function smpt_ep_add_comment( $ep_id, $user_id, $anon_uuid, $author_name, $text, $author_email = '' ) {
	global $wpdb;

	$actor   = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$table   = $wpdb->prefix . 'smpt_episode_comments';
	$counter = $wpdb->prefix . 'smpt_episode_counters';
	$now     = current_time( 'mysql' );

	smpt_ep_ensure_counter( $ep_id );

	$insert_data = array(
		'episode_id'   => $ep_id,
		'author_name'  => $author_name,
		'author_email' => $author_email,
		'comment_text' => $text,
		'created_at'   => $now,
		'updated_at'   => $now,
	);

	if ( null !== $actor['user_id'] ) {
		$insert_data['user_id']   = $actor['user_id'];
		$insert_data['anon_hash'] = null;
	} else {
		$insert_data['user_id']   = null;
		$insert_data['anon_hash'] = $actor['anon_hash'];
	}

	$result = $wpdb->insert( $table, $insert_data );
	if ( false === $result ) {
		return false;
	}

	$comment_id = $wpdb->insert_id;

	$wpdb->query( $wpdb->prepare(
		"UPDATE {$counter} SET comment_count = comment_count + 1 WHERE episode_id = %d",
		$ep_id
	) );

	return $comment_id;
}

/**
 * Get paginated comments for an episode (oldest first).
 *
 * @param int $ep_id    Episode number (1-200).
 * @param int $page     Page number (1-based).
 * @param int $per_page Comments per page.
 * @return array Associative array with 'comments' and 'total' keys.
 */
function smpt_ep_get_comments( $ep_id, $page = 1, $per_page = 20 ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'smpt_episode_comments';
	$offset = ( $page - 1 ) * $per_page;

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE episode_id = %d",
		$ep_id
	) );

	$comments = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, author_name, comment_text, created_at, user_id
		 FROM {$table}
		 WHERE episode_id = %d
		 ORDER BY created_at ASC
		 LIMIT %d OFFSET %d",
		$ep_id, $per_page, $offset
	) );

	return array(
		'comments' => $comments ? $comments : array(),
		'total'    => $total,
	);
}

/* =========================================================================
   Batch queries
   ========================================================================= */

/**
 * Get counters for a batch of episodes in a single query.
 *
 * @param int[] $ep_ids Array of episode numbers.
 * @return array Counters keyed by episode_id.
 */
function smpt_ep_get_batch_counters( $ep_ids ) {
	global $wpdb;

	if ( empty( $ep_ids ) ) {
		return array();
	}

	$table        = $wpdb->prefix . 'smpt_episode_counters';
	$placeholders = implode( ',', array_fill( 0, count( $ep_ids ), '%d' ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE episode_id IN ({$placeholders})",
		$ep_ids
	) );

	$keyed = array();
	if ( $rows ) {
		foreach ( $rows as $row ) {
			$eid = (int) $row->episode_id;
			$keyed[ $eid ] = array(
				'likes'        => (int) $row->likes,
				'dislikes'     => (int) $row->dislikes,
				'rating_sum'   => (float) $row->rating_sum,
				'rating_count' => (int) $row->rating_count,
				'rating_avg'   => $row->rating_count > 0
					? round( (float) $row->rating_sum / (int) $row->rating_count, 1 )
					: 0,
				'comment_count' => (int) $row->comment_count,
			);
		}
	}

	return $keyed;
}

/**
 * Batch load current user/anon interaction states for given episodes.
 *
 * @param array $actor  Result from smpt_ep_resolve_actor().
 * @param int[] $ep_ids Array of episode numbers.
 * @return array States keyed by episode_id.
 */
function smpt_ep_get_user_states( $actor, $ep_ids ) {
	global $wpdb;

	if ( empty( $ep_ids ) ) {
		return array();
	}

	$table        = $wpdb->prefix . 'smpt_episode_interactions';
	$aw           = smpt_ep_actor_where( $actor );
	$placeholders = implode( ',', array_fill( 0, count( $ep_ids ), '%d' ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT episode_id, interaction_type, value FROM {$table}
		 WHERE {$aw['clause']} AND episode_id IN ({$placeholders})",
		array_merge( $aw['params'], $ep_ids )
	) );

	$states = array();
	if ( $rows ) {
		foreach ( $rows as $row ) {
			$eid = (int) $row->episode_id;
			if ( ! isset( $states[ $eid ] ) ) {
				$states[ $eid ] = array();
			}

			$type = $row->interaction_type;
			if ( 'rating' === $type ) {
				$states[ $eid ]['rating'] = (float) $row->value;
			} else {
				$states[ $eid ][ $type ] = true;
			}
		}
	}

	return $states;
}

/* =========================================================================
   Anon-to-user migration
   ========================================================================= */

/**
 * On login, migrate anonymous interaction rows to the authenticated user.
 *
 * @param int    $user_id   WordPress user ID.
 * @param string $anon_hash Raw binary anon hash (32 bytes).
 */
function smpt_ep_reattribute_anon( $user_id, $anon_hash ) {
	global $wpdb;

	$interactions = $wpdb->prefix . 'smpt_episode_interactions';
	$comments     = $wpdb->prefix . 'smpt_episode_comments';

	// --- Interactions: delete anon rows that would conflict with existing user rows ---
	$wpdb->query( $wpdb->prepare(
		"DELETE a FROM {$interactions} a
		 INNER JOIN {$interactions} u
		 ON u.user_id = %d
		    AND u.episode_id = a.episode_id
		    AND u.interaction_type = a.interaction_type
		 WHERE a.anon_hash = %s AND a.user_id IS NULL",
		$user_id, $anon_hash
	) );

	// --- Interactions: reassign remaining anon rows to the user ---
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$interactions} SET user_id = %d, anon_hash = NULL WHERE anon_hash = %s AND user_id IS NULL",
		$user_id, $anon_hash
	) );

	// --- Comments: reassign anon comments to the user ---
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$comments} SET user_id = %d, anon_hash = NULL WHERE anon_hash = %s AND user_id IS NULL",
		$user_id, $anon_hash
	) );
}

/* =========================================================================
   URL helper
   ========================================================================= */

/**
 * Map an episode number to the season page permalink with anchor.
 *
 * @param int $ep_num Episode number (1-200).
 * @return string Full URL with #episodio_NNN anchor.
 */
function smpt_ep_get_episode_page_url( $ep_num ) {
	$map = array(
		array( 1,   46,  1060 ),
		array( 47,  89,  3899 ),
		array( 90,  127, 4430 ),
		array( 128, 166, 5672 ),
		array( 167, 200, 5918 ),
	);

	foreach ( $map as $range ) {
		if ( $ep_num >= $range[0] && $ep_num <= $range[1] ) {
			$anchor = sprintf( '#episodio_%03d', $ep_num );
			return get_permalink( $range[2] ) . $anchor;
		}
	}

	return '#';
}

/* =========================================================================
   REST API endpoints
   ========================================================================= */

/**
 * Register episode interaction REST routes.
 */
function smpt_ep_register_routes() {
	// Unified interaction endpoint.
	register_rest_route( 'smpt/v1', '/ep-interact', array(
		'methods'             => 'POST',
		'callback'            => 'smpt_ep_rest_interact',
		'permission_callback' => '__return_true',
	) );

	// Batch stats endpoint.
	register_rest_route( 'smpt/v1', '/ep-stats', array(
		'methods'             => 'GET',
		'callback'            => 'smpt_ep_rest_stats',
		'permission_callback' => '__return_true',
	) );

	// Comments endpoint.
	register_rest_route( 'smpt/v1', '/ep-comments/(?P<episode_id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'smpt_ep_rest_comments',
		'permission_callback' => '__return_true',
	) );

	// Sync endpoint (login merge).
	register_rest_route( 'smpt/v1', '/ep-sync', array(
		'methods'             => 'POST',
		'callback'            => 'smpt_ep_rest_sync',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'smpt_ep_register_routes' );

/**
 * Handle POST /smpt/v1/ep-interact — unified interaction endpoint.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_interact( WP_REST_Request $request ) {
	$body = json_decode( $request->get_body(), true );
	if ( ! is_array( $body ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_body' ), 400 );
	}

	$ep_id      = isset( $body['episode_id'] ) ? absint( $body['episode_id'] ) : 0;
	$action     = isset( $body['action'] ) ? sanitize_text_field( $body['action'] ) : '';
	$anon_uuid  = isset( $body['anon_uuid'] ) ? sanitize_text_field( $body['anon_uuid'] ) : '';
	$user_id    = get_current_user_id();

	if ( $ep_id < 1 || $ep_id > 200 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_episode' ), 400 );
	}

	$valid_actions = array( 'like', 'remove_like', 'dislike', 'remove_dislike', 'rate', 'remove_rate', 'watched', 'remove_watched', 'comment' );
	if ( ! in_array( $action, $valid_actions, true ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_action' ), 400 );
	}

	// Watched requires login.
	if ( in_array( $action, array( 'watched', 'remove_watched' ), true ) && $user_id <= 0 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'login_required' ), 403 );
	}

	// Anon users must provide UUID.
	if ( $user_id <= 0 && '' === $anon_uuid ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'anon_uuid_required' ), 400 );
	}

	$result = true;

	switch ( $action ) {
		case 'like':
		case 'remove_like':
		case 'dislike':
		case 'remove_dislike':
			$result = smpt_ep_set_reaction( $ep_id, $user_id, $anon_uuid, $action );
			break;

		case 'rate':
			$value  = isset( $body['value'] ) ? (float) $body['value'] : 0;
			$result = smpt_ep_set_rating( $ep_id, $user_id, $anon_uuid, $value );
			if ( false === $result ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_rating' ), 400 );
			}
			break;

		case 'remove_rate':
			$result = smpt_ep_remove_rating( $ep_id, $user_id, $anon_uuid );
			break;

		case 'watched':
			$result = smpt_ep_set_watched( $ep_id, $user_id );
			break;

		case 'remove_watched':
			$result = smpt_ep_remove_watched( $ep_id, $user_id );
			break;

		case 'comment':
			$author_name  = isset( $body['author_name'] ) ? sanitize_text_field( $body['author_name'] ) : '';
			$comment_text = isset( $body['comment_text'] ) ? sanitize_textarea_field( $body['comment_text'] ) : '';
			if ( '' === $comment_text ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'empty_comment' ), 400 );
			}
			$result = smpt_ep_add_comment( $ep_id, $user_id, $anon_uuid, $author_name, $comment_text );
			if ( false === $result ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'comment_failed' ), 500 );
			}
			break;
	}

	// Return updated counters and user state.
	$counters = smpt_ep_get_batch_counters( array( $ep_id ) );
	$actor    = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$states   = smpt_ep_get_user_states( $actor, array( $ep_id ) );

	return new WP_REST_Response( array(
		'ok'       => true,
		'counters' => isset( $counters[ $ep_id ] ) ? $counters[ $ep_id ] : array(),
		'state'    => isset( $states[ $ep_id ] ) ? $states[ $ep_id ] : array(),
	) );
}

/**
 * Handle GET /smpt/v1/ep-stats — batch counters and user states.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_stats( WP_REST_Request $request ) {
	$raw = sanitize_text_field( $request->get_param( 'episodes' ) );
	if ( '' === $raw ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_episodes' ), 400 );
	}

	$parts  = explode( ',', $raw );
	$ep_ids = array();
	foreach ( $parts as $p ) {
		$id = absint( trim( $p ) );
		if ( $id >= 1 && $id <= 200 ) {
			$ep_ids[] = $id;
		}
	}

	if ( empty( $ep_ids ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'no_valid_episodes' ), 400 );
	}

	// Cap at 50 episodes per request.
	$ep_ids = array_slice( array_unique( $ep_ids ), 0, 50 );

	$counters = smpt_ep_get_batch_counters( $ep_ids );

	$states  = array();
	$user_id = get_current_user_id();
	$anon    = sanitize_text_field( $request->get_param( 'anon_uuid' ) );

	if ( $user_id > 0 || '' !== $anon ) {
		$actor  = smpt_ep_resolve_actor( $user_id, $anon );
		$states = smpt_ep_get_user_states( $actor, $ep_ids );
	}

	return new WP_REST_Response( array(
		'ok'       => true,
		'counters' => $counters,
		'states'   => $states,
	) );
}

/**
 * Handle GET /smpt/v1/ep-comments/<episode_id> — paginated comments.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_comments( WP_REST_Request $request ) {
	$ep_id = absint( $request->get_param( 'episode_id' ) );
	if ( $ep_id < 1 || $ep_id > 200 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_episode' ), 400 );
	}

	$page     = max( 1, absint( $request->get_param( 'page' ) ) );
	$per_page = absint( $request->get_param( 'per_page' ) );
	if ( $per_page < 1 || $per_page > 100 ) {
		$per_page = 20;
	}

	$data = smpt_ep_get_comments( $ep_id, $page, $per_page );

	return new WP_REST_Response( array(
		'ok'       => true,
		'comments' => $data['comments'],
		'total'    => $data['total'],
		'page'     => $page,
		'per_page' => $per_page,
	) );
}

/**
 * Handle POST /smpt/v1/ep-sync — merge anon data on login.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_sync( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( $user_id <= 0 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'login_required' ), 403 );
	}

	$body = json_decode( $request->get_body(), true );
	if ( ! is_array( $body ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_body' ), 400 );
	}

	$anon_uuid    = isset( $body['anon_uuid'] ) ? sanitize_text_field( $body['anon_uuid'] ) : '';
	$local_states = isset( $body['local_states'] ) && is_array( $body['local_states'] ) ? $body['local_states'] : array();

	// Reattribute anon rows to the logged-in user.
	if ( '' !== $anon_uuid ) {
		$anon_hash = smpt_ep_hash_anon_uuid( $anon_uuid );
		smpt_ep_reattribute_anon( $user_id, $anon_hash );
	}

	// Delete sync transient.
	delete_transient( 'smpt_ep_needs_sync_' . $user_id );

	// Process local states: apply if user has no server-side state.
	$affected_eps = array();
	if ( ! empty( $local_states ) ) {
		$actor = smpt_ep_resolve_actor( $user_id, '' );

		foreach ( $local_states as $ep_id_str => $state ) {
			$ep_id = absint( $ep_id_str );
			if ( $ep_id < 1 || $ep_id > 200 || ! is_array( $state ) ) {
				continue;
			}

			$affected_eps[] = $ep_id;

			// Get existing server state for this episode.
			$existing = smpt_ep_get_user_states( $actor, array( $ep_id ) );
			$has_state = isset( $existing[ $ep_id ] ) && ! empty( $existing[ $ep_id ] );

			if ( $has_state ) {
				continue; // Server state takes precedence.
			}

			// Apply local state.
			if ( ! empty( $state['like'] ) ) {
				smpt_ep_set_reaction( $ep_id, $user_id, '', 'like' );
			} elseif ( ! empty( $state['dislike'] ) ) {
				smpt_ep_set_reaction( $ep_id, $user_id, '', 'dislike' );
			}

			if ( ! empty( $state['rating'] ) ) {
				$rating = (float) $state['rating'];
				smpt_ep_set_rating( $ep_id, $user_id, '', $rating );
			}
		}
	}

	// Return merged states for all affected episodes.
	$actor  = smpt_ep_resolve_actor( $user_id, '' );
	$states = array();
	if ( ! empty( $affected_eps ) ) {
		$states = smpt_ep_get_user_states( $actor, $affected_eps );
	}

	return new WP_REST_Response( array(
		'ok'     => true,
		'states' => $states,
	) );
}

/* =========================================================================
   Hooks
   ========================================================================= */

/**
 * Set a transient when a user logs in, signaling the frontend to sync.
 *
 * @param string  $user_login Username.
 * @param WP_User $user       User object.
 */
function smpt_ep_on_login( $user_login, $user ) {
	set_transient( 'smpt_ep_needs_sync_' . $user->ID, 1, HOUR_IN_SECONDS );
}
add_action( 'wp_login', 'smpt_ep_on_login', 10, 2 );

/**
 * Allow comments on season episode pages (needed for the custom comment system).
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function smpt_ep_comments_open( $open, $post_id ) {
	$season_page_ids = array( 1060, 3899, 4430, 5672, 5918 );
	if ( in_array( (int) $post_id, $season_page_ids, true ) ) {
		return true;
	}
	return $open;
}
add_filter( 'comments_open', 'smpt_ep_comments_open', 10, 2 );
