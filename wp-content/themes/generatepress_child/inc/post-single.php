<?php
/**
 * Single post presentation helpers.
 *
 * Keeps GeneratePress' native single-post flow, while extending it through
 * hooks for the SMPT article layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is a standard single post view.
 *
 * @return bool
 */
function smpt_is_single_post_view() {
	return is_singular( 'post' ) && ! is_admin();
}

add_action( 'wp', 'smpt_configure_single_post_hooks', 20 );
/**
 * Adjust GeneratePress single-post hooks without overriding its template.
 */
function smpt_configure_single_post_hooks() {
	if ( ! smpt_is_single_post_view() ) {
		return;
	}

	remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
	remove_action( 'generate_after_entry_title', 'generate_post_meta' );

	add_action( 'generate_before_entry_title', 'smpt_render_single_post_header_navigation', 5 );
	add_action( 'generate_after_entry_title', 'smpt_render_single_post_meta', 10 );
	add_action( 'generate_after_entry_content', 'smpt_render_single_post_footer_navigation', 15 );
}

add_filter( 'generate_show_post_navigation', 'smpt_hide_default_single_post_navigation' );
/**
 * Disable GeneratePress' default single-post navigation when using the
 * header-level article arrows.
 *
 * @param bool $show Whether GP should show its default post navigation.
 * @return bool
 */
function smpt_hide_default_single_post_navigation( $show ) {
	if ( smpt_is_single_post_view() ) {
		return false;
	}

	return $show;
}

/**
 * Estimate reading time in whole minutes.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function smpt_get_post_read_time_minutes( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );

	if ( ! is_string( $content ) || '' === $content ) {
		return 1;
	}

	$text  = wp_strip_all_tags( strip_shortcodes( $content ) );
	$words = str_word_count( html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ) );

	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Render read time beside the native entry meta items.
 */
function smpt_render_single_post_meta() {
	if ( ! smpt_is_single_post_view() ) {
		return;
	}

	$minutes = smpt_get_post_read_time_minutes( get_the_ID() );

	?>
	<div class="entry-meta">
		<?php generate_posted_on(); ?>
		<span class="smpt-read-time">
			<?php
			printf(
				esc_html(
					_n(
						'%d min. de leitura',
						'%d min. de leitura',
						$minutes,
						'generatepress'
					)
				),
				(int) $minutes
			);
			?>
		</span>
	</div>
	<hr class="smpt-post-header-divider">
	<?php
}

/**
 * Render compact previous/next navigation inside the post header.
 */
function smpt_render_single_post_header_navigation() {
	if ( ! smpt_is_single_post_view() ) {
		return;
	}

	$posts = smpt_get_single_post_adjacent_posts();

	if ( ! $posts['previous'] && ! $posts['next'] ) {
		return;
	}

	?>
	<nav class="smpt-post-header-nav" aria-label="<?php esc_attr_e( 'Navegação entre artigos', 'generatepress' ); ?>">
		<?php if ( $posts['previous'] ) : ?>
			<a class="smpt-post-header-nav__link smpt-post-header-nav__link--prev" href="<?php echo esc_url( get_permalink( $posts['previous'] ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $posts['previous'] ) ); ?>">
				<span aria-hidden="true">&lt;</span>
			</a>
		<?php endif; ?>

		<?php if ( $posts['next'] ) : ?>
			<a class="smpt-post-header-nav__link smpt-post-header-nav__link--next" href="<?php echo esc_url( get_permalink( $posts['next'] ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $posts['next'] ) ); ?>">
				<span aria-hidden="true">&gt;</span>
			</a>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Fetch the adjacent posts for the current single-post view.
 *
 * @return array{previous:WP_Post|null,next:WP_Post|null}
 */
function smpt_get_single_post_adjacent_posts() {
	return array(
		'previous' => get_previous_post(),
		'next'     => get_next_post(),
	);
}

/**
 * Render title-based previous/next navigation below post meta and tags.
 */
function smpt_render_single_post_footer_navigation() {
	if ( ! smpt_is_single_post_view() ) {
		return;
	}

	$posts = smpt_get_single_post_adjacent_posts();

	if ( ! $posts['previous'] && ! $posts['next'] ) {
		return;
	}

	?>
	<nav class="smpt-page-nav smpt-page-nav--article smpt-page-nav--article-end" aria-label="<?php esc_attr_e( 'Navegação entre artigos', 'generatepress' ); ?>">
		<?php if ( $posts['previous'] ) : ?>
			<a class="smpt-page-nav__link smpt-page-nav__link--prev smpt-page-nav__link--article" href="<?php echo esc_url( get_permalink( $posts['previous'] ) ); ?>">
				<span class="smpt-page-nav__arrow smpt-page-nav__arrow--prev" aria-hidden="true"></span>
				<span class="smpt-page-nav__content">
					<span class="smpt-page-nav__meta"><?php esc_html_e( 'Artigo anterior', 'generatepress' ); ?></span>
					<span class="smpt-page-nav__title"><?php echo esc_html( get_the_title( $posts['previous'] ) ); ?></span>
				</span>
			</a>
		<?php endif; ?>

		<?php if ( $posts['next'] ) : ?>
			<a class="smpt-page-nav__link smpt-page-nav__link--next smpt-page-nav__link--article" href="<?php echo esc_url( get_permalink( $posts['next'] ) ); ?>">
				<span class="smpt-page-nav__content">
					<span class="smpt-page-nav__meta"><?php esc_html_e( 'Artigo seguinte', 'generatepress' ); ?></span>
					<span class="smpt-page-nav__title"><?php echo esc_html( get_the_title( $posts['next'] ) ); ?></span>
				</span>
				<span class="smpt-page-nav__arrow smpt-page-nav__arrow--next" aria-hidden="true"></span>
			</a>
		<?php endif; ?>
	</nav>
	<?php
}
