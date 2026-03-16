<?php
/**
 * WP-CLI utilities for SMPT site maintenance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Maintenance commands for legacy content cleanup.
	 */
	class SMPT_Site_CLI_Command {
		/**
		 * Remove legacy "↗" markers placed immediately before external links or inside link text.
		 *
		 * ## OPTIONS
		 *
		 * [--dry-run]
		 * : Show which posts would be updated without writing changes.
		 *
		 * [--include-revisions]
		 * : Process revisions as well as published content.
		 *
		 * ## EXAMPLES
		 *
		 *     wp smpt cleanup-link-arrows --dry-run
		 *     wp smpt cleanup-link-arrows
		 *     wp smpt cleanup-link-arrows --include-revisions
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public function cleanup_link_arrows( $args, $assoc_args ) {
			global $wpdb;

			$dry_run           = isset( $assoc_args['dry-run'] );
			$include_revisions = isset( $assoc_args['include-revisions'] );

			$sql = "SELECT ID, post_type, post_status, post_title, post_content
				FROM {$wpdb->posts}
				WHERE post_content LIKE '%↗%'
				AND post_content LIKE '%<a %'";

			if ( ! $include_revisions ) {
				$sql .= " AND post_type <> 'revision'";
			}

			$posts = $wpdb->get_results( $sql );

			$updated = 0;

			foreach ( $posts as $post ) {
				$content = (string) $post->post_content;

				$new_content = preg_replace(
					'/(<a\b[^>]*>\s*)(?:<img[^>]*\balt\s*=\s*(["\'])↗\2[^>]*>\s*|↗\s+)+/iu',
					'$1',
					$content
				);

				$new_content = preg_replace(
					'/(?:<img[^>]*\balt\s*=\s*(["\'])↗\1[^>]*>\s*|↗\s+)+(<a\b[^>]*>)/iu',
					'$2',
					$new_content
				);

				if ( $new_content === $content ) {
					continue;
				}

				WP_CLI::log(
					sprintf(
						'[%s] %s | %s | %s',
						$post->ID,
						$post->post_type,
						$post->post_status,
						$post->post_title
					)
				);

				if ( ! $dry_run ) {
					$wpdb->update(
						$wpdb->posts,
						array( 'post_content' => $new_content ),
						array( 'ID' => $post->ID ),
						array( '%s' ),
						array( '%d' )
					);
				}

				$updated++;
			}

			if ( $dry_run ) {
				WP_CLI::success( sprintf( 'Dry run complete. %d post(s) would be updated.', $updated ) );
				return;
			}

			WP_CLI::success( sprintf( 'Updated %d post(s).', $updated ) );
		}
	}

	WP_CLI::add_command( 'smpt', 'SMPT_Site_CLI_Command' );
}
