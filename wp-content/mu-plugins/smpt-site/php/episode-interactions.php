<?php
/**
 * Episode interactions backend — likes, dislikes, ratings, watched/want/favorite status, and comments.
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
	$db_ver    = '1.2';
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
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_user_points (
		user_id BIGINT UNSIGNED PRIMARY KEY,
		total_points INT DEFAULT 0,
		current_tier INT DEFAULT 0,
		views_today INT DEFAULT 0,
		last_view_date DATE,
		created_at DATETIME,
		updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
		INDEX(current_tier)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_point_log (
		id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		action VARCHAR(50),
		points_awarded INT,
		context_id INT,
		created_at DATETIME,
		INDEX(user_id, created_at)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_interaction_throttle (
		id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		episode_id SMALLINT UNSIGNED,
		action_type VARCHAR(30),
		last_action_at DATETIME,
		UNIQUE KEY user_ep_action (user_id, episode_id, action_type),
		INDEX(last_action_at)
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

			// Increment counter only when a new row was inserted (rows_affected=1).
			// ON DUPLICATE KEY UPDATE returns 2 if the existing row was changed → skip.
			if ( 1 === $wpdb->rows_affected ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET likes = likes + 1 WHERE episode_id = %d",
					$ep_id
				) );
			}
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

			// Increment counter only when a new row was inserted (rows_affected=1).
			if ( 1 === $wpdb->rows_affected ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$counter} SET dislikes = dislikes + 1 WHERE episode_id = %d",
					$ep_id
				) );
			}
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
   Personal status flags (logged-in only)
   ========================================================================= */

/**
 * Set an exclusive watch-status flag for a logged-in user.
 *
 * Watched and want-to-watch are mutually exclusive. Setting one clears the other.
 *
 * @param int    $ep_id   Episode number (1-200).
 * @param int    $user_id WordPress user ID.
 * @param string $type    Either 'watched' or 'want_watch'.
 * @return bool True on success.
 */
function smpt_ep_set_exclusive_watch_state( $ep_id, $user_id, $type ) {
	global $wpdb;

	if ( $user_id <= 0 || ! in_array( $type, array( 'watched', 'want_watch' ), true ) ) {
		return false;
	}

	$table     = $wpdb->prefix . 'smpt_episode_interactions';
	$now       = current_time( 'mysql' );
	$opposite  = 'watched' === $type ? 'want_watch' : 'watched';

	$wpdb->query( 'START TRANSACTION' );

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$table} WHERE user_id = %d AND episode_id = %d AND interaction_type = %s",
		$user_id, $ep_id, $opposite
	) );

	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
		 VALUES (%d, NULL, %d, %s, %s, %s)
		 ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)",
		$user_id, $ep_id, $type, $now, $now
	) );

	$wpdb->query( 'COMMIT' );

	return true;
}

/**
 * Mark an episode as watched for a logged-in user.
 *
 * @param int $ep_id   Episode number (1-200).
 * @param int $user_id WordPress user ID.
 * @return bool True on success.
 */
function smpt_ep_set_watched( $ep_id, $user_id ) {
	return smpt_ep_set_exclusive_watch_state( $ep_id, $user_id, 'watched' );
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
 * Resolve watched/want states for a user, with the most recently updated flag winning.
 *
 * @param int $user_id WordPress user ID.
 * @return array<int,string> Array keyed by episode ID with values 'watched' or 'want_watch'.
 */
function smpt_ep_get_watch_preference_map( $user_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'smpt_episode_interactions';
	$rows  = $wpdb->get_results( $wpdb->prepare(
		"SELECT episode_id, interaction_type
		 FROM {$table}
		 WHERE user_id = %d AND interaction_type IN ('watched', 'want_watch')
		 ORDER BY updated_at ASC, id ASC",
		$user_id
	) );

	$map = array();
	foreach ( $rows as $row ) {
		$map[ (int) $row->episode_id ] = $row->interaction_type;
	}

	return $map;
}

/**
 * Get all watched episode IDs for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Array of episode numbers.
 */
function smpt_ep_get_watched_episodes( $user_id ) {
	$map      = smpt_ep_get_watch_preference_map( $user_id );
	$episodes = array();

	foreach ( $map as $episode_id => $type ) {
		if ( 'watched' === $type ) {
			$episodes[] = (int) $episode_id;
		}
	}

	sort( $episodes, SORT_NUMERIC );

	return $episodes;
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
function smpt_ep_get_comments( $ep_id, $page = 1, $per_page = 20, $actor = null ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'smpt_episode_comments';
	$offset = ( $page - 1 ) * $per_page;

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE episode_id = %d",
		$ep_id
	) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, author_name, comment_text, created_at, updated_at, user_id, anon_hash
		 FROM {$table}
		 WHERE episode_id = %d
		 ORDER BY created_at ASC
		 LIMIT %d OFFSET %d",
		$ep_id, $per_page, $offset
	) );

	$comments = array();
	if ( $rows ) {
		foreach ( $rows as $row ) {
			$is_own = false;
			if ( null !== $actor ) {
				if ( null !== $actor['user_id'] && (int) $row->user_id === (int) $actor['user_id'] ) {
					$is_own = true;
				} elseif ( null !== $actor['anon_hash'] && null !== $row->anon_hash ) {
					$is_own = hash_equals( $actor['anon_hash'], $row->anon_hash );
				}
			}
			$comments[] = array(
				'id'           => (int) $row->id,
				'author_name'  => $row->author_name,
				'comment_text' => $row->comment_text,
				'created_at'   => $row->created_at,
				'updated_at'   => $row->updated_at,
				'user_id'      => $row->user_id ? (int) $row->user_id : null,
				'is_own'       => $is_own,
			);
		}
	}

	return array(
		'comments' => $comments,
		'total'    => $total,
	);
}

/**
 * Check whether an actor owns a comment row.
 *
 * @param object $comment  DB row with user_id and anon_hash fields.
 * @param int    $user_id  Current user ID (0 if anon).
 * @param string $anon_uuid Raw visitor UUID.
 * @return bool
 */
function smpt_ep_is_comment_owner( $comment, $user_id, $anon_uuid ) {
	if ( $user_id > 0 ) {
		return (int) $comment->user_id === $user_id;
	}
	if ( '' !== $anon_uuid && null !== $comment->anon_hash ) {
		return hash_equals( smpt_ep_hash_anon_uuid( $anon_uuid ), $comment->anon_hash );
	}
	return false;
}

/**
 * Edit an existing comment (within 24-hour window, owner or admin only).
 *
 * @param int    $comment_id ID of the comment to edit.
 * @param int    $user_id    Current user ID (0 if anon).
 * @param string $anon_uuid  Raw visitor UUID.
 * @param string $new_text   Replacement comment text.
 * @return true|string True on success; 'not_found', 'expired', or 'forbidden' on failure.
 */
function smpt_ep_edit_comment( $comment_id, $user_id, $anon_uuid, $new_text ) {
	global $wpdb;

	$table   = $wpdb->prefix . 'smpt_episode_comments';
	$comment = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, user_id, anon_hash, created_at FROM {$table} WHERE id = %d",
		$comment_id
	) );

	if ( ! $comment ) {
		return 'not_found';
	}

	$age_seconds = time() - strtotime( $comment->created_at );
	if ( $age_seconds > DAY_IN_SECONDS && ! current_user_can( 'edit_others_posts' ) ) {
		return 'expired';
	}

	if ( ! smpt_ep_is_comment_owner( $comment, $user_id, $anon_uuid ) && ! current_user_can( 'edit_others_posts' ) ) {
		return 'forbidden';
	}

	$wpdb->update(
		$table,
		array( 'comment_text' => $new_text, 'updated_at' => current_time( 'mysql' ) ),
		array( 'id' => $comment_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	return true;
}

/**
 * Delete a comment (owner or admin only, no time limit for admins).
 *
 * @param int    $comment_id ID of the comment to delete.
 * @param int    $user_id    Current user ID (0 if anon).
 * @param string $anon_uuid  Raw visitor UUID.
 * @return true|string True on success; 'not_found' or 'forbidden' on failure.
 */
function smpt_ep_delete_comment( $comment_id, $user_id, $anon_uuid ) {
	global $wpdb;

	$table   = $wpdb->prefix . 'smpt_episode_comments';
	$counter = $wpdb->prefix . 'smpt_episode_counters';

	$comment = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, user_id, anon_hash, episode_id FROM {$table} WHERE id = %d",
		$comment_id
	) );

	if ( ! $comment ) {
		return 'not_found';
	}

	if ( ! smpt_ep_is_comment_owner( $comment, $user_id, $anon_uuid ) && ! current_user_can( 'edit_others_posts' ) ) {
		return 'forbidden';
	}

	$wpdb->delete( $table, array( 'id' => $comment_id ), array( '%d' ) );

	$wpdb->query( $wpdb->prepare(
		"UPDATE {$counter} SET comment_count = GREATEST(comment_count - 1, 0) WHERE episode_id = %d",
		(int) $comment->episode_id
	) );

	return true;
}

/**
 * Add an episode to a logged-in user's "want to watch" list.
 *
 * @param int $ep_id   Episode number.
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function smpt_ep_set_want( $ep_id, $user_id ) {
	return smpt_ep_set_exclusive_watch_state( $ep_id, $user_id, 'want_watch' );
}

/**
 * Remove an episode from a user's "want to watch" list.
 *
 * @param int $ep_id   Episode number.
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function smpt_ep_remove_want( $ep_id, $user_id ) {
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
			'interaction_type' => 'want_watch',
		),
		array( '%d', '%d', '%s' )
	);

	return (bool) $deleted;
}

/**
 * Get all "want to watch" episode IDs for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return int[]
 */
function smpt_ep_get_want_episodes( $user_id ) {
	global $wpdb;

	$map      = smpt_ep_get_watch_preference_map( $user_id );
	$episodes = array();

	foreach ( $map as $episode_id => $type ) {
		if ( 'want_watch' === $type ) {
			$episodes[] = (int) $episode_id;
		}
	}

	sort( $episodes, SORT_NUMERIC );

	return $episodes;
}

/**
 * Mark an episode as favorite for a logged-in user.
 *
 * @param int $ep_id   Episode number.
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function smpt_ep_set_favorite( $ep_id, $user_id ) {
	global $wpdb;

	if ( $user_id <= 0 ) {
		return false;
	}

	$table = $wpdb->prefix . 'smpt_episode_interactions';
	$now   = current_time( 'mysql' );

	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$table} (user_id, anon_hash, episode_id, interaction_type, created_at, updated_at)
		 VALUES (%d, NULL, %d, 'favorite', %s, %s)
		 ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)",
		$user_id, $ep_id, $now, $now
	) );

	return true;
}

/**
 * Remove an episode from a user's favorites.
 *
 * @param int $ep_id   Episode number.
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function smpt_ep_remove_favorite( $ep_id, $user_id ) {
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
			'interaction_type' => 'favorite',
		),
		array( '%d', '%d', '%s' )
	);

	return (bool) $deleted;
}

/**
 * Get all favorite episode IDs for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return int[]
 */
function smpt_ep_get_favorite_episodes( $user_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'smpt_episode_interactions';
	$rows  = $wpdb->get_col( $wpdb->prepare(
		"SELECT episode_id FROM {$table} WHERE user_id = %d AND interaction_type = 'favorite' ORDER BY episode_id",
		$user_id
	) );

	return array_map( 'intval', $rows );
}

/* =========================================================================
   Batch queries
   ========================================================================= */

/**
 * Return the default counter payload for one episode card.
 *
 * @return array
 */
function smpt_ep_default_counter_row() {
	return array(
		'likes'         => 0,
		'dislikes'      => 0,
		'rating_sum'    => 0.0,
		'rating_count'  => 0,
		'rating_avg'    => 0,
		'comment_count' => 0,
		'views'         => 0,
		'downloads'     => 0,
	);
}

/**
 * Merge analytics counter rows into episode interaction counters.
 *
 * Views = remaster stream + nostalgia stream + imported GA4 stream.
 * Downloads = av1 + h264 + imported GA4 mp4/av1/h264.
 *
 * @param array $keyed Counters keyed by episode number.
 * @param array $rows  Analytics rows with event_type, item_id, and cnt.
 * @return array
 */
function smpt_ep_merge_analytics_totals_into_counters( array $keyed, array $rows ) {
	foreach ( $rows as $row ) {
		$event_type = isset( $row->event_type ) ? (string) $row->event_type : '';
		$item_id    = isset( $row->item_id ) ? (string) $row->item_id : '';
		$count      = isset( $row->cnt ) ? (int) $row->cnt : 0;

		if ( $count <= 0 || '' === $event_type || '' === $item_id ) {
			continue;
		}

		if ( 'stream' === $event_type && preg_match( '/^episodio_(\d+)$/', $item_id, $matches ) ) {
			$ep_num = (int) $matches[1];
			if ( isset( $keyed[ $ep_num ] ) ) {
				$keyed[ $ep_num ]['views'] += $count;
			}
			continue;
		}

		if ( 'nostalgia_play' === $event_type && preg_match( '/^nostalgia_ep_(\d+)$/', $item_id, $matches ) ) {
			$ep_num = (int) $matches[1];
			if ( isset( $keyed[ $ep_num ] ) ) {
				$keyed[ $ep_num ]['views'] += $count;
			}
			continue;
		}

		if ( 'download' === $event_type && preg_match( '/^download_ep(\d+)_(av1|h264|mp4)$/', $item_id, $matches ) ) {
			$ep_num = (int) $matches[1];
			if ( isset( $keyed[ $ep_num ] ) ) {
				$keyed[ $ep_num ]['downloads'] += $count;
			}
		}
	}

	return $keyed;
}

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

	$ep_ids = array_values( array_unique( array_map( 'intval', $ep_ids ) ) );
	$table        = $wpdb->prefix . 'smpt_episode_counters';
	$placeholders = implode( ',', array_fill( 0, count( $ep_ids ), '%d' ) );
	$keyed        = array();

	foreach ( $ep_ids as $ep_id ) {
		$keyed[ $ep_id ] = smpt_ep_default_counter_row();
	}

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE episode_id IN ({$placeholders})",
		$ep_ids
	) );

	if ( $rows ) {
		foreach ( $rows as $row ) {
			$eid = (int) $row->episode_id;
			if ( ! isset( $keyed[ $eid ] ) ) {
				$keyed[ $eid ] = smpt_ep_default_counter_row();
			}

			$keyed[ $eid ]['likes']         = (int) $row->likes;
			$keyed[ $eid ]['dislikes']      = (int) $row->dislikes;
			$keyed[ $eid ]['rating_sum']    = (float) $row->rating_sum;
			$keyed[ $eid ]['rating_count']  = (int) $row->rating_count;
			$keyed[ $eid ]['rating_avg']    = $row->rating_count > 0
				? round( (float) $row->rating_sum / (int) $row->rating_count, 1 )
				: 0;
			$keyed[ $eid ]['comment_count'] = (int) $row->comment_count;
		}
	}

	$counters_table = $wpdb->prefix . 'smpt_counters';
	$ga_table       = $wpdb->prefix . 'smpt_ga4_history';
	$stream_item_ids = array_map(
		static function ( $id ) {
			return sprintf( 'episodio_%03d', $id );
		},
		$ep_ids
	);
	$nostalgia_item_ids = array_map(
		static function ( $id ) {
			return sprintf( 'nostalgia_ep_%03d', $id );
		},
		$ep_ids
	);
	$download_item_ids = array();
	foreach ( $ep_ids as $ep_id ) {
		$download_item_ids[] = sprintf( 'download_ep%03d_av1', $ep_id );
		$download_item_ids[] = sprintf( 'download_ep%03d_h264', $ep_id );
		$download_item_ids[] = sprintf( 'download_ep%03d_mp4', $ep_id );
	}

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$counters_table}'" ) === $counters_table ) {
		$local_item_ids = array_merge( $stream_item_ids, $nostalgia_item_ids, $download_item_ids );
		if ( ! empty( $local_item_ids ) ) {
			$id_holders  = implode( ',', array_fill( 0, count( $local_item_ids ), '%s' ) );
			$local_rows  = $wpdb->get_results( $wpdb->prepare(
				"SELECT event_type, item_id, total_count AS cnt FROM {$counters_table}
				 WHERE event_type IN ('stream', 'nostalgia_play', 'download')
				 AND item_id IN ({$id_holders})",
				$local_item_ids
			) );

			if ( $local_rows ) {
				$keyed = smpt_ep_merge_analytics_totals_into_counters( $keyed, $local_rows );
			}
		}
	}

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$ga_table}'" ) === $ga_table ) {
		$ga_item_ids = array_merge( $stream_item_ids, $download_item_ids );
		if ( ! empty( $ga_item_ids ) ) {
			$id_holders = implode( ',', array_fill( 0, count( $ga_item_ids ), '%s' ) );
			$ga_rows    = $wpdb->get_results( $wpdb->prepare(
				"SELECT event_type, item_id, SUM(event_count) AS cnt FROM {$ga_table}
				 WHERE event_type IN ('stream', 'download')
				 AND item_id IN ({$id_holders})
				 GROUP BY event_type, item_id",
				$ga_item_ids
			) );

			if ( $ga_rows ) {
				$keyed = smpt_ep_merge_analytics_totals_into_counters( $keyed, $ga_rows );
			}
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
		"SELECT episode_id, interaction_type, value, updated_at, id FROM {$table}
		 WHERE {$aw['clause']} AND episode_id IN ({$placeholders})
		 ORDER BY updated_at ASC, id ASC",
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
			} elseif ( 'watched' === $type || 'want_watch' === $type ) {
				unset( $states[ $eid ]['watched'], $states[ $eid ]['want_watch'] );
				$states[ $eid ][ $type ] = true;
			} elseif ( in_array( $type, array( 'like', 'dislike', 'favorite' ), true ) ) {
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
   Engagement Tiers & Points System
   ========================================================================= */

/**
 * Point values for different interactions
 */
function smpt_ep_get_point_value( $action ) {
	$values = array(
		'comment'  => 7,
		'rate'     => 3,
		'like'     => 1,
		'dislike'  => 1,
	);
	return isset( $values[ $action ] ) ? $values[ $action ] : 0;
}

/**
 * Get user's current tier based on total points
 *
 * @param int $user_id User ID
 * @return int Tier (0-4)
 */
function smpt_ep_get_user_tier( $user_id ) {
	global $wpdb;

	$total_points = intval( $wpdb->get_var( $wpdb->prepare(
		"SELECT total_points FROM {$wpdb->prefix}smpt_user_points WHERE user_id = %d",
		$user_id
	) ) ?: 0 );

	// Tier thresholds: 0->40->80->120->160->200
	if ( $total_points < 40 ) {
		return 0; // Membro (3 eps/day)
	} elseif ( $total_points < 80 ) {
		return 1; // (5 eps/day)
	} elseif ( $total_points < 120 ) {
		return 2; // (7 eps/day)
	} elseif ( $total_points < 160 ) {
		return 3; // (10 eps/day)
	} else {
		return 4; // (15 eps/day)
	}
}

/**
 * Get daily view limit for user's tier
 *
 * @param int $user_id User ID
 * @return int Daily limit
 */
function smpt_ep_get_daily_limit( $user_id ) {
	// Admins bypass all limits
	if ( user_can( $user_id, 'manage_options' ) ) {
		return 999;
	}

	$tier = smpt_ep_get_user_tier( $user_id );
	$opt  = smpt_ep_get_tier_limits_option();
	return $opt['tiers'][ $tier ] ?? 3;
}

/**
 * Check if user reached 50 point daily cap
 *
 * @param int $user_id User ID
 * @return bool True if reached cap
 */
function smpt_ep_check_daily_point_cap( $user_id ) {
	// Admins bypass point cap
	if ( user_can( $user_id, 'manage_options' ) ) {
		return false;
	}

	global $wpdb;
	$now = current_time( 'mysql' );

	$points_today = intval( $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(points_awarded) FROM {$wpdb->prefix}smpt_point_log
		 WHERE user_id = %d AND created_at > DATE_SUB(%s, INTERVAL 24 HOUR)",
		$user_id, $now
	) ) ?: 0 );

	return $points_today >= 50;
}

/**
 * Award points to user for an action.
 *
 * Uses the throttle table to prevent duplicate awards for the same
 * user + context + action combination (idempotent per episode/post).
 *
 * @param int    $user_id    User ID.
 * @param string $action     Action type (comment, rate, like, dislike).
 * @param int    $context_id Episode ID or post ID.
 * @return bool|int Points awarded (false if capped or already awarded).
 */
function smpt_ep_award_points( $user_id, $action, $context_id = 0 ) {
	if ( $user_id <= 0 ) {
		return false; // Only logged-in users earn points.
	}

	// Check daily cap.
	if ( smpt_ep_check_daily_point_cap( $user_id ) ) {
		return false; // Cap reached.
	}

	$points = smpt_ep_get_point_value( $action );
	if ( $points <= 0 ) {
		return false;
	}

	global $wpdb;
	$now = current_time( 'mysql' );

	// Throttle: prevent duplicate points for same user + context + action.
	// INSERT IGNORE returns rows_affected=0 if the UNIQUE key already existed.
	if ( in_array( $action, array( 'like', 'dislike', 'rate' ), true ) ) {
		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->prefix}smpt_interaction_throttle
			 (user_id, episode_id, action_type, last_action_at)
			 VALUES (%d, %d, %s, %s)",
			$user_id, $context_id, $action, $now
		) );

		if ( 0 === $wpdb->rows_affected ) {
			return false; // Already awarded for this episode + action.
		}
	}

	// Add to point log.
	$wpdb->insert(
		$wpdb->prefix . 'smpt_point_log',
		array(
			'user_id'        => $user_id,
			'action'         => $action,
			'points_awarded' => $points,
			'context_id'     => $context_id,
			'created_at'     => $now,
		),
		array( '%d', '%s', '%d', '%d', '%s' )
	);

	// Update total points.
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$wpdb->prefix}smpt_user_points (user_id, total_points, current_tier, created_at, updated_at)
		 VALUES (%d, %d, 0, %s, %s)
		 ON DUPLICATE KEY UPDATE
		 total_points = total_points + %d,
		 current_tier = (
		 	CASE
		 		WHEN total_points + %d < 40 THEN 0
		 		WHEN total_points + %d < 80 THEN 1
		 		WHEN total_points + %d < 120 THEN 2
		 		WHEN total_points + %d < 160 THEN 3
		 		ELSE 4
		 	END
		 ),
		 updated_at = %s",
		$user_id, $points, $now, $now, $points, $points, $points, $points, $points, $now
	) );

	return $points;
}

/**
 * Increment view count for today
 *
 * @param int $user_id User ID (0 for logged-out)
 * @return bool Success
 */
function smpt_ep_increment_view_count( $user_id ) {
	global $wpdb;

	if ( $user_id > 0 ) {
		// Logged-in user
		$today = gmdate( 'Y-m-d' );
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}smpt_user_points (user_id, views_today, last_view_date, created_at, updated_at)
			 VALUES (%d, 1, %s, NOW(), NOW())
			 ON DUPLICATE KEY UPDATE
			 views_today = IF(last_view_date = %s, views_today + 1, 1),
			 last_view_date = %s,
			 updated_at = NOW()",
			$user_id, $today, $today, $today
		) );
		return true;
	} else {
		// Logged-out user (would be tracked via transient/cookie in JS, not backend)
		return true;
	}
}

/**
 * Check if user can view/download an episode
 *
 * @param int $user_id User ID (0 for logged-out)
 * @return array [ 'allowed' => bool, 'views_today' => int, 'limit' => int, 'message' => string ]
 */
function smpt_ep_check_view_allowed( $user_id ) {
	// Admins always allowed
	if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) ) {
		return array(
			'allowed'      => true,
			'views_today'  => 0,
			'limit'        => 999,
			'message'      => '',
			'seconds_until_reset' => 0,
		);
	}

	global $wpdb;

	if ( $user_id > 0 ) {
		// Logged-in user — fetch views + points in one query to derive tier limit inline.
		$today = gmdate( 'Y-m-d' );
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT views_today, last_view_date, total_points FROM {$wpdb->prefix}smpt_user_points WHERE user_id = %d",
			$user_id
		) );

		$views_today  = 0;
		$total_points = $row ? intval( $row->total_points ) : 0;

		if ( $row && $row->last_view_date === $today ) {
			$views_today = intval( $row->views_today );
		}

		// Compute daily limit from points without a second DB query.
		$opt = smpt_ep_get_tier_limits_option();
		$tier = $total_points < 40 ? 0 : ( $total_points < 80 ? 1 : ( $total_points < 120 ? 2 : ( $total_points < 160 ? 3 : 4 ) ) );
		$limit = $opt['tiers'][ $tier ];
		$allowed = $views_today < $limit;

		if ( ! $allowed ) {
			// Calculate time until reset (next midnight UTC)
			$now = time();
			$tomorrow = strtotime( '+1 day', strtotime( gmdate( 'Y-m-d' ) ) );
			$seconds_until = $tomorrow - $now;

			return array(
				'allowed'            => false,
				'views_today'        => $views_today,
				'limit'              => $limit,
				'message'            => sprintf(
					__( 'Limite diário atingido (%d/%d). Volta em %d horas.', 'generatepress' ),
					$views_today,
					$limit,
					ceil( $seconds_until / 3600 )
				),
				'seconds_until_reset' => $seconds_until,
			);
		}

		return array(
			'allowed'             => true,
			'views_today'         => $views_today,
			'limit'               => $limit,
			'message'             => '',
			'seconds_until_reset' => 0,
		);
	} else {
		// Logged-out user — tracked on frontend via localStorage
		$opt = smpt_ep_get_tier_limits_option();
		return array(
			'allowed'             => true,
			'views_today'         => 0,
			'limit'               => $opt['logged_out'],
			'message'             => '',
			'seconds_until_reset' => 0,
		);
	}
}

/**
 * Get user's points dashboard data
 *
 * @param int $user_id User ID
 * @return array Points, tier, progress data
 */
function smpt_ep_get_user_points_data( $user_id ) {
	global $wpdb;

	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT total_points, current_tier FROM {$wpdb->prefix}smpt_user_points WHERE user_id = %d",
		$user_id
	) );

	// Tier thresholds and names (defined once, used for both branches).
	$tier_thresholds = array( 0, 40, 80, 120, 160 );
	$tier_names = array(
		'Membro',
		'Temporada Clássica',
		'Temporada R',
		'Temporada S',
		'SuperS',
	);
	$opt = smpt_ep_get_tier_limits_option();
	$tier_limits = $opt['tiers'];

	if ( ! $row ) {
		return array(
			'total_points'      => 0,
			'current_tier'      => 0,
			'tier_name'         => $tier_names[0],
			'next_tier_name'    => $tier_names[1],
			'current_threshold' => 0,
			'next_threshold'    => 40,
			'progress'          => 0,
			'progress_needed'   => 40,
			'progress_percent'  => 0,
			'daily_limit'       => $tier_limits[0],
		);
	}

	$total = intval( $row->total_points );
	$tier = intval( $row->current_tier );

	$current_threshold = $tier_thresholds[ $tier ];
	$next_threshold = isset( $tier_thresholds[ $tier + 1 ] ) ? $tier_thresholds[ $tier + 1 ] : 200;

	$is_max = ( $tier >= 4 );
	$range  = $next_threshold - $current_threshold;
	$pct    = $range > 0 ? round( ( ( $total - $current_threshold ) / $range ) * 100 ) : 100;

	return array(
		'total_points'      => $total,
		'current_tier'      => $tier,
		'tier_name'         => isset( $tier_names[ $tier ] ) ? $tier_names[ $tier ] : 'SuperS',
		'next_tier_name'    => $is_max ? '' : ( isset( $tier_names[ $tier + 1 ] ) ? $tier_names[ $tier + 1 ] : '' ),
		'current_threshold' => $current_threshold,
		'next_threshold'    => $next_threshold,
		'progress'          => $total - $current_threshold,
		'progress_needed'   => max( 0, $next_threshold - $total ),
		'progress_percent'  => min( 100, $pct ),
		'daily_limit'       => isset( $tier_limits[ $tier ] ) ? $tier_limits[ $tier ] : 15,
	);
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

	// View status endpoint (check if user can stream/download).
	register_rest_route( 'smpt/v1', '/ep-view-status', array(
		'methods'             => 'GET',
		'callback'            => 'smpt_ep_rest_view_status',
		'permission_callback' => '__return_true',
	) );

	// Record view endpoint (increment view count when user streams/downloads).
	register_rest_route( 'smpt/v1', '/ep-record-view', array(
		'methods'             => 'POST',
		'callback'            => 'smpt_ep_rest_record_view',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'smpt_ep_register_routes' );

/* =========================================================================
   Comment Hooks for Point Awards (posts, pages, guestbook)
   ========================================================================= */

/**
 * Award points when a logged-in user posts an approved comment on a
 * post, page, or via the Gwolle Guestbook plugin.
 *
 * Uses comment_post (fires once at insert time) instead of
 * wp_insert_comment to avoid double-firing on status transitions.
 *
 * @param int        $comment_id       Comment ID.
 * @param int|string $comment_approved 1 if approved, 0 if held, 'spam' if spam.
 */
function smpt_ep_award_points_on_wp_comment( $comment_id, $comment_approved ) {
	// Only award for approved comments.
	if ( 1 !== (int) $comment_approved ) {
		return;
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment || ! $comment->user_id || $comment->user_id <= 0 ) {
		return; // Only logged-in users earn points.
	}

	// Check the post type — allow posts, pages, and gwolle guestbook.
	$post = get_post( $comment->comment_post_ID );
	if ( ! $post ) {
		return;
	}

	$allowed_types = array( 'post', 'page', 'gwolle_gb' );
	if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
		return;
	}

	smpt_ep_award_points( $comment->user_id, 'comment', $comment->comment_post_ID );
}
add_action( 'comment_post', 'smpt_ep_award_points_on_wp_comment', 10, 2 );

/**
 * Handle GET /smpt/v1/ep-view-status — check if user can view/download.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_view_status( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$status = smpt_ep_check_view_allowed( $user_id );
	return new WP_REST_Response( $status );
}

/**
 * Handle POST /smpt/v1/ep-record-view — increment view count.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_ep_rest_record_view( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$ep_id   = absint( $request->get_param( 'episode_id' ) );

	if ( $ep_id < 1 || $ep_id > 200 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_episode' ), 400 );
	}

	// Check if view is allowed
	$status = smpt_ep_check_view_allowed( $user_id );
	if ( ! $status['allowed'] ) {
		return new WP_REST_Response( array(
			'ok'       => false,
			'error'    => 'view_limit_exceeded',
			'status'   => $status,
		), 403 );
	}

	// Increment view count
	smpt_ep_increment_view_count( $user_id );

	return new WP_REST_Response( array( 'ok' => true ) );
}

/**
 * Get the real client IP, respecting common proxy headers.
 *
 * @return string Client IP address.
 */
function smpt_ep_get_client_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ) as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = $_SERVER[ $key ];
			if ( 'HTTP_X_FORWARDED_FOR' === $key ) {
				$ip = explode( ',', $ip )[0];
			}
			$ip = trim( $ip );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}
	return '0.0.0.0';
}

/**
 * Anti-abuse gate for anonymous interactions.
 * - Honeypot: rejects if the invisible `website` field is filled (bots).
 * - Rate limit: max 15 interactions per 60 s per IP.
 * - Min delay: at least 2 s between consecutive requests per IP.
 *
 * Logged-in users bypass all checks.
 *
 * @param array $body  Decoded request body.
 * @param int   $user_id Current user ID.
 * @return WP_REST_Response|true  True if allowed, WP_REST_Response error otherwise.
 */
function smpt_ep_anti_abuse_gate( $body, $user_id ) {
	if ( $user_id > 0 ) {
		return true;
	}

	// --- Honeypot: bots fill the invisible "website" field ---
	if ( ! empty( $body['website'] ) ) {
		// Silently accept so bots think it worked, but do nothing.
		return new WP_REST_Response( array( 'ok' => true, 'counters' => array() ), 200 );
	}

	$ip       = smpt_ep_get_client_ip();
	$ip_hash  = md5( 'smpt_rl_' . $ip );

	// --- Rate limit: max 15 interactions per 60 s ---
	$count_key = 'smpt_rl_cnt_' . $ip_hash;
	$count     = (int) get_transient( $count_key );
	if ( $count >= 15 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
	}
	set_transient( $count_key, $count + 1, 60 );

	// --- Min delay: 2 s between requests ---
	$ts_key  = 'smpt_rl_ts_' . $ip_hash;
	$last_ts = get_transient( $ts_key );
	$now     = microtime( true );
	if ( false !== $last_ts && ( $now - (float) $last_ts ) < 2.0 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'too_fast' ), 429 );
	}
	set_transient( $ts_key, $now, 60 );

	return true;
}

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

	$user_id = get_current_user_id();

	// Anti-abuse checks for anonymous users.
	$gate = smpt_ep_anti_abuse_gate( $body, $user_id );
	if ( true !== $gate ) {
		return $gate;
	}

	$ep_id      = isset( $body['episode_id'] ) ? absint( $body['episode_id'] ) : 0;
	$action     = isset( $body['action'] ) ? sanitize_text_field( $body['action'] ) : '';
	$anon_uuid  = isset( $body['anon_uuid'] ) ? sanitize_text_field( $body['anon_uuid'] ) : '';

	// Comment-level actions don't require a valid episode_id.
	$comment_only_actions = array( 'edit_comment', 'delete_comment' );
	if ( ! in_array( $action, $comment_only_actions, true ) && ( $ep_id < 1 || $ep_id > 200 ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_episode' ), 400 );
	}

	$valid_actions = array( 'like', 'remove_like', 'dislike', 'remove_dislike', 'rate', 'remove_rate', 'watched', 'remove_watched', 'want_watch', 'remove_want_watch', 'favorite', 'remove_favorite', 'comment', 'edit_comment', 'delete_comment' );
	if ( ! in_array( $action, $valid_actions, true ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_action' ), 400 );
	}

	// Login-only actions.
	if ( in_array( $action, array( 'watched', 'remove_watched', 'want_watch', 'remove_want_watch', 'favorite', 'remove_favorite' ), true ) && $user_id <= 0 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'login_required' ), 403 );
	}

	// Anon users must provide UUID (except for comment edit/delete which identifies by comment_id).
	if ( $user_id <= 0 && '' === $anon_uuid && ! in_array( $action, $comment_only_actions, true ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'anon_uuid_required' ), 400 );
	}

	$result = true;

	switch ( $action ) {
		case 'like':
		case 'remove_like':
		case 'dislike':
		case 'remove_dislike':
			$result = smpt_ep_set_reaction( $ep_id, $user_id, $anon_uuid, $action );
			// Award points for new like/dislike (not remove actions)
			if ( $result && in_array( $action, array( 'like', 'dislike' ), true ) ) {
				smpt_ep_award_points( $user_id, $action, $ep_id );
			}
			break;

		case 'rate':
			$value  = isset( $body['value'] ) ? (float) $body['value'] : 0;
			$result = smpt_ep_set_rating( $ep_id, $user_id, $anon_uuid, $value );
			if ( false === $result ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_rating' ), 400 );
			}
			// Award points for rating
			smpt_ep_award_points( $user_id, 'rate', $ep_id );
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

		case 'want_watch':
			$result = smpt_ep_set_want( $ep_id, $user_id );
			break;

		case 'remove_want_watch':
			$result = smpt_ep_remove_want( $ep_id, $user_id );
			break;

		case 'favorite':
			$result = smpt_ep_set_favorite( $ep_id, $user_id );
			break;

		case 'remove_favorite':
			$result = smpt_ep_remove_favorite( $ep_id, $user_id );
			break;

		case 'comment':
			$author_name  = isset( $body['author_name'] ) ? sanitize_text_field( $body['author_name'] ) : '';
			$author_email = isset( $body['author_email'] ) ? sanitize_email( $body['author_email'] ) : '';
			$comment_text = isset( $body['comment_text'] ) ? sanitize_textarea_field( $body['comment_text'] ) : '';
			if ( '' === $comment_text ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'empty_comment' ), 400 );
			}

			// reCAPTCHA v3 check (anon only).
			if ( $user_id <= 0 && function_exists( 'smpt_recaptcha_verify' ) ) {
				$recap_token = isset( $body['recaptcha_token'] ) ? sanitize_text_field( $body['recaptcha_token'] ) : '';
				if ( ! smpt_recaptcha_verify( $recap_token, 'ep_comment' ) ) {
					return new WP_REST_Response( array( 'ok' => false, 'error' => 'captcha_failed' ), 403 );
				}
			}

			// Akismet spam check.
			if ( function_exists( 'smpt_akismet_is_spam' ) && smpt_akismet_is_spam( $author_name, $author_email, $comment_text ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'spam_detected' ), 403 );
			}

			// Profanity filter.
			if ( function_exists( 'smpt_profanity_filter' ) ) {
				$filtered = smpt_profanity_filter( $comment_text );
				if ( false === $filtered ) {
					return new WP_REST_Response( array( 'ok' => false, 'error' => 'profanity_blocked' ), 403 );
				}
				$comment_text = $filtered;
			}

			$result = smpt_ep_add_comment( $ep_id, $user_id, $anon_uuid, $author_name, $comment_text, $author_email );
			if ( false === $result ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'comment_failed' ), 500 );
			}
			// Award points for comment
			smpt_ep_award_points( $user_id, 'comment', $ep_id );
			break;

		case 'edit_comment':
			$comment_id   = isset( $body['comment_id'] ) ? absint( $body['comment_id'] ) : 0;
			$comment_text = isset( $body['comment_text'] ) ? sanitize_textarea_field( $body['comment_text'] ) : '';
			if ( ! $comment_id || '' === $comment_text ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_params' ), 400 );
			}

			// Profanity filter on edits too.
			if ( function_exists( 'smpt_profanity_filter' ) ) {
				$filtered = smpt_profanity_filter( $comment_text );
				if ( false === $filtered ) {
					return new WP_REST_Response( array( 'ok' => false, 'error' => 'profanity_blocked' ), 403 );
				}
				$comment_text = $filtered;
			}

			$edit_result = smpt_ep_edit_comment( $comment_id, $user_id, $anon_uuid, $comment_text );
			if ( true !== $edit_result ) {
				$status = 'expired' === $edit_result ? 403 : ( 'forbidden' === $edit_result ? 403 : 404 );
				return new WP_REST_Response( array( 'ok' => false, 'error' => $edit_result ), $status );
			}
			return new WP_REST_Response( array( 'ok' => true ) );

		case 'delete_comment':
			$comment_id  = isset( $body['comment_id'] ) ? absint( $body['comment_id'] ) : 0;
			if ( ! $comment_id ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_params' ), 400 );
			}
			$del_result = smpt_ep_delete_comment( $comment_id, $user_id, $anon_uuid );
			if ( true !== $del_result ) {
				$status = 'forbidden' === $del_result ? 403 : 404;
				return new WP_REST_Response( array( 'ok' => false, 'error' => $del_result ), $status );
			}
			// Return updated comment counter for the episode.
			$counters = smpt_ep_get_batch_counters( array( $ep_id ) );
			$cap_reached = $user_id > 0 && smpt_ep_check_daily_point_cap( $user_id );
			return new WP_REST_Response( array(
				'ok'          => true,
				'counters'    => isset( $counters[ $ep_id ] ) ? $counters[ $ep_id ] : array(),
				'cap_reached' => $cap_reached,
			) );
	}

	// Return updated counters and user state.
	$counters = smpt_ep_get_batch_counters( array( $ep_id ) );
	$actor    = smpt_ep_resolve_actor( $user_id, $anon_uuid );
	$states   = smpt_ep_get_user_states( $actor, array( $ep_id ) );

	// Check if point cap is reached
	$cap_reached = $user_id > 0 && smpt_ep_check_daily_point_cap( $user_id );

	return new WP_REST_Response( array(
		'ok'          => true,
		'counters'    => isset( $counters[ $ep_id ] ) ? $counters[ $ep_id ] : array(),
		'state'       => isset( $states[ $ep_id ] ) ? $states[ $ep_id ] : array(),
		'cap_reached' => $cap_reached,
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

	$user_id   = get_current_user_id();
	$anon_uuid = sanitize_text_field( $request->get_param( 'anon_uuid' ) );
	$actor     = ( $user_id > 0 || '' !== $anon_uuid )
		? smpt_ep_resolve_actor( $user_id, $anon_uuid )
		: null;

	$data = smpt_ep_get_comments( $ep_id, $page, $per_page, $actor );

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

/* =========================================================================
 * SETTINGS PAGE — Episode View Limits
 * ========================================================================= */

/**
 * Get tier episode limits from options (with defaults).
 *
 * @return array { 'logged_out' => int, 'tiers' => int[5] }
 */
function smpt_ep_get_tier_limits_option() {
	$defaults = array(
		'logged_out' => 2,
		'tiers'      => array( 3, 5, 7, 10, 15 ),
	);
	$saved = get_option( 'smpt_ep_view_limits', array() );
	return wp_parse_args( $saved, $defaults );
}

/**
 * Register settings.
 */
function smpt_ep_register_settings() {
	register_setting( 'smpt_ep_settings', 'smpt_ep_view_limits', array(
		'type'              => 'array',
		'sanitize_callback' => 'smpt_ep_sanitize_view_limits',
	) );
}
add_action( 'admin_init', 'smpt_ep_register_settings' );

/**
 * Sanitize the view limits option.
 *
 * @param mixed $input Raw input.
 * @return array Sanitized.
 */
function smpt_ep_sanitize_view_limits( $input ) {
	$clean = array();
	$clean['logged_out'] = isset( $input['logged_out'] ) ? max( 0, intval( $input['logged_out'] ) ) : 2;
	$clean['tiers'] = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$clean['tiers'][ $i ] = isset( $input['tiers'][ $i ] ) ? max( 0, intval( $input['tiers'][ $i ] ) ) : 3;
	}
	return $clean;
}

/**
 * Add admin menu page.
 */
function smpt_ep_add_settings_page() {
	add_options_page(
		'Limites de Episódios',
		'Limites Episódios',
		'manage_options',
		'smpt-episode-limits',
		'smpt_ep_render_settings_page'
	);
}
add_action( 'admin_menu', 'smpt_ep_add_settings_page' );

/**
 * Render the settings page.
 */
function smpt_ep_render_settings_page() {
	$limits = smpt_ep_get_tier_limits_option();
	$tier_names = array( 'Membro', 'Temporada Clássica', 'Temporada R', 'Temporada S', 'SuperS' );
	$tier_points = array( '0–39', '40–79', '80–119', '120–159', '160+' );
	?>
	<div class="wrap">
		<h1>Limites de Episódios por Dia</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'smpt_ep_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Utilizadores não autenticados</th>
					<td>
						<input type="number" name="smpt_ep_view_limits[logged_out]" value="<?php echo intval( $limits['logged_out'] ); ?>" min="0" max="100" style="width:80px;" />
						<p class="description">Episódios por dia para visitantes sem login (controlado via localStorage no browser).</p>
					</td>
				</tr>
			</table>

			<h2>Limites por nível (utilizadores autenticados)</h2>
			<table class="form-table">
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $tier_names[ $i ] ); ?> <small style="color:#999;">(<?php echo esc_html( $tier_points[ $i ] ); ?> pts)</small></th>
					<td>
						<input type="number" name="smpt_ep_view_limits[tiers][<?php echo $i; ?>]" value="<?php echo intval( $limits['tiers'][ $i ] ); ?>" min="0" max="999" style="width:80px;" />
						<span>episódios/dia</span>
					</td>
				</tr>
				<?php endfor; ?>
			</table>

			<?php submit_button( 'Guardar alterações' ); ?>
		</form>
	</div>
	<?php
}
