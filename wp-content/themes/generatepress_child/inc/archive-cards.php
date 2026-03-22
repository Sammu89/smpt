<?php
/**
 * Archive card grid: image size, grid wrapper hooks, and header size filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the current request is an archive-style view (not singular, not admin, not 404).
 *
 * @return bool
 */
function smpt_is_archive_view() {
	return ! is_admin() && ! is_singular() && ! is_404();
}

/**
 * Register the cropped image size for archive cards (3:2 ratio).
 */
function smpt_register_archive_card_image_size() {
	add_image_size( 'smpt-archive-card', 600, 400, true );
}
add_action( 'after_setup_theme', 'smpt_register_archive_card_image_size', 20 );

/**
 * Open the grid wrapper before the post loop on archive pages.
 *
 * @param string $template The template type (archive, index, search).
 */
function smpt_archive_grid_wrapper_open( $template ) {
	if ( ! smpt_is_archive_view() ) {
		return;
	}

	echo '<div class="smpt-archive-grid">';
}
add_action( 'generate_before_loop', 'smpt_archive_grid_wrapper_open' );

/**
 * Close the grid wrapper after the post loop on archive pages.
 *
 * @param string $template The template type (archive, index, search).
 */
function smpt_archive_grid_wrapper_close( $template ) {
	if ( ! smpt_is_archive_view() ) {
		return;
	}

	echo '</div><!-- .smpt-archive-grid -->';
}
add_action( 'generate_after_loop', 'smpt_archive_grid_wrapper_close' );

/**
 * Use the archive card image size for page header images on archive views.
 *
 * The existing filter in featured-images.php already guards with `if (!is_page()) return`.
 * This filter runs at a later priority to override only on archive views.
 *
 * @param string $size Requested image size.
 * @return string
 */
function smpt_archive_card_header_image_size( $size ) {
	if ( smpt_is_archive_view() ) {
		return 'smpt-archive-card';
	}

	return $size;
}
add_filter( 'generate_page_header_default_size', 'smpt_archive_card_header_image_size', 20 );

/**
 * Remove the GP "read more" link appended to excerpts on archive cards.
 * We render our own "Ler notícia" link in the card footer.
 */
add_filter( 'excerpt_more', function( $more ) {
	if ( smpt_is_archive_view() ) {
		return '&hellip;';
	}
	return $more;
}, 20 );
