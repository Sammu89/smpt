<?php
/**
 * Featured video support for page headers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SMPT_FEATURED_VIDEO_AV1_META      = '_smpt_featured_video_av1';
const SMPT_FEATURED_VIDEO_MP4_META      = '_smpt_featured_video_mp4';
const SMPT_FEATURED_VIDEO_OVERLAY_META  = '_smpt_featured_video_overlay';
const SMPT_FEATURED_VIDEO_AUTOPLAY_META = '_smpt_featured_video_autoplay';

/**
 * Replace the default GeneratePress page image output with page header media.
 */
function smpt_register_page_header_media() {
	remove_action( 'generate_after_header', 'generate_featured_page_header', 10 );
	add_action( 'generate_after_header', 'smpt_render_page_header_media', 10 );
}
add_action( 'after_setup_theme', 'smpt_register_page_header_media', 20 );

/**
 * Add the featured video meta box to pages.
 */
function smpt_add_featured_video_meta_box() {
	add_meta_box(
		'smpt-featured-video',
		__( 'SMPT Featured Video', 'generatepress' ),
		'smpt_render_featured_video_meta_box',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_page', 'smpt_add_featured_video_meta_box' );

/**
 * Load media picker assets for the featured video page meta box.
 *
 * @param string $hook_suffix Current admin screen hook suffix.
 * @return void
 */
function smpt_enqueue_featured_video_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'smpt-featured-video-admin',
		get_stylesheet_directory_uri() . '/javascript/featured-video-admin.js',
		array( 'jquery' ),
		smpt_child_asset_version( 'javascript/featured-video-admin.js', wp_get_theme()->get( 'Version' ) ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'smpt_enqueue_featured_video_admin_assets' );

/**
 * Render the featured video meta box.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function smpt_render_featured_video_meta_box( $post ) {
	wp_nonce_field( 'smpt_save_featured_video_meta', 'smpt_featured_video_nonce' );

	$av1_url     = get_post_meta( $post->ID, SMPT_FEATURED_VIDEO_AV1_META, true );
	$mp4_url     = get_post_meta( $post->ID, SMPT_FEATURED_VIDEO_MP4_META, true );
	$overlay_url = get_post_meta( $post->ID, SMPT_FEATURED_VIDEO_OVERLAY_META, true );
	$autoplay    = smpt_featured_video_should_autoplay( $post->ID );
	?>
	<p>
		<label for="smpt-featured-video-av1"><strong><?php esc_html_e( 'AV1 video URL', 'generatepress' ); ?></strong></label>
		<input type="url" class="widefat" id="smpt-featured-video-av1" name="smpt_featured_video_av1" value="<?php echo esc_attr( $av1_url ); ?>" placeholder="https://example.com/banner-av1.mp4">
		<button type="button" class="button button-secondary smpt-featured-video-picker" data-target="#smpt-featured-video-av1" data-media-type="video"><?php esc_html_e( 'Choose video', 'generatepress' ); ?></button>
		<button type="button" class="button-link-delete smpt-featured-video-clear" data-target="#smpt-featured-video-av1"><?php esc_html_e( 'Clear', 'generatepress' ); ?></button>
	</p>
	<p>
		<label for="smpt-featured-video-mp4"><strong><?php esc_html_e( 'MP4 fallback URL', 'generatepress' ); ?></strong></label>
		<input type="url" class="widefat" id="smpt-featured-video-mp4" name="smpt_featured_video_mp4" value="<?php echo esc_attr( $mp4_url ); ?>" placeholder="https://example.com/banner.mp4">
		<button type="button" class="button button-secondary smpt-featured-video-picker" data-target="#smpt-featured-video-mp4" data-media-type="video"><?php esc_html_e( 'Choose video', 'generatepress' ); ?></button>
		<button type="button" class="button-link-delete smpt-featured-video-clear" data-target="#smpt-featured-video-mp4"><?php esc_html_e( 'Clear', 'generatepress' ); ?></button>
	</p>
	<p>
		<label for="smpt-featured-video-overlay"><strong><?php esc_html_e( 'Overlay image URL', 'generatepress' ); ?></strong></label>
		<input type="url" class="widefat" id="smpt-featured-video-overlay" name="smpt_featured_video_overlay" value="<?php echo esc_attr( $overlay_url ); ?>" placeholder="https://example.com/logo.png">
		<button type="button" class="button button-secondary smpt-featured-video-picker" data-target="#smpt-featured-video-overlay" data-media-type="image"><?php esc_html_e( 'Choose image', 'generatepress' ); ?></button>
		<button type="button" class="button-link-delete smpt-featured-video-clear" data-target="#smpt-featured-video-overlay"><?php esc_html_e( 'Clear', 'generatepress' ); ?></button>
	</p>
	<p>
		<label for="smpt-featured-video-autoplay">
			<input type="checkbox" id="smpt-featured-video-autoplay" name="smpt_featured_video_autoplay" value="1" <?php checked( $autoplay ); ?>>
			<?php esc_html_e( 'Autoplay continuously', 'generatepress' ); ?>
		</label>
	</p>
	<p class="howto">
		<?php esc_html_e( 'If either video field is filled, the page header uses video instead of the featured image. AV1 is listed first and the browser falls back to MP4 when needed.', 'generatepress' ); ?>
	</p>
	<p class="howto">
		<?php esc_html_e( 'The page featured image is used automatically as the poster. Overlay image is optional.', 'generatepress' ); ?>
	</p>
	<p class="howto">
		<?php esc_html_e( 'When autoplay continuously is disabled, the header video still starts automatically but only plays once and stops.', 'generatepress' ); ?>
	</p>
	<?php
}

/**
 * Save the featured video fields.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function smpt_save_featured_video_meta( $post_id ) {
	if ( ! isset( $_POST['smpt_featured_video_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_featured_video_nonce'] ) ), 'smpt_save_featured_video_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		SMPT_FEATURED_VIDEO_AV1_META     => isset( $_POST['smpt_featured_video_av1'] ) ? wp_unslash( $_POST['smpt_featured_video_av1'] ) : '',
		SMPT_FEATURED_VIDEO_MP4_META     => isset( $_POST['smpt_featured_video_mp4'] ) ? wp_unslash( $_POST['smpt_featured_video_mp4'] ) : '',
		SMPT_FEATURED_VIDEO_OVERLAY_META => isset( $_POST['smpt_featured_video_overlay'] ) ? wp_unslash( $_POST['smpt_featured_video_overlay'] ) : '',
	);

	foreach ( $fields as $meta_key => $value ) {
		$sanitized_value = esc_url_raw( trim( $value ) );

		if ( $sanitized_value ) {
			update_post_meta( $post_id, $meta_key, $sanitized_value );
			continue;
		}

		delete_post_meta( $post_id, $meta_key );
	}

	if ( isset( $_POST['smpt_featured_video_autoplay'] ) ) {
		update_post_meta( $post_id, SMPT_FEATURED_VIDEO_AUTOPLAY_META, '1' );
	} else {
		update_post_meta( $post_id, SMPT_FEATURED_VIDEO_AUTOPLAY_META, '0' );
	}

	delete_post_meta( $post_id, '_smpt_featured_video_poster' );
}
add_action( 'save_post_page', 'smpt_save_featured_video_meta' );

/**
 * Determine if a page has featured video configured.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function smpt_page_has_featured_video( $post_id ) {
	return (bool) ( get_post_meta( $post_id, SMPT_FEATURED_VIDEO_AV1_META, true ) || get_post_meta( $post_id, SMPT_FEATURED_VIDEO_MP4_META, true ) );
}

/**
 * Determine whether a page header video should autoplay and loop.
 *
 * Existing pages default to autoplay until explicitly saved with the checkbox.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function smpt_featured_video_should_autoplay( $post_id ) {
	$stored_value = get_post_meta( $post_id, SMPT_FEATURED_VIDEO_AUTOPLAY_META, true );

	if ( '' === $stored_value ) {
		return true;
	}

	return '1' === $stored_value;
}

/**
 * Output the page header media in the GeneratePress featured media slot.
 *
 * @return void
 */
function smpt_render_page_header_media() {
	if ( function_exists( 'generate_page_header' ) ) {
		return;
	}

	if ( ! is_page() ) {
		return;
	}

	$post_id = get_queried_object_id();

	if ( ! $post_id ) {
		return;
	}

	if ( smpt_page_has_featured_video( $post_id ) ) {
		smpt_render_page_featured_video( $post_id );
		return;
	}

	generate_featured_page_header_area( 'page-header-image' );
}

/**
 * Render the featured video header markup.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function smpt_render_page_featured_video( $post_id ) {
	$av1_url     = get_post_meta( $post_id, SMPT_FEATURED_VIDEO_AV1_META, true );
	$mp4_url     = get_post_meta( $post_id, SMPT_FEATURED_VIDEO_MP4_META, true );
	$overlay_url = get_post_meta( $post_id, SMPT_FEATURED_VIDEO_OVERLAY_META, true );
	$autoplay    = smpt_featured_video_should_autoplay( $post_id );
	$poster_url  = '';

	if ( has_post_thumbnail( $post_id ) ) {
		$poster_url = get_the_post_thumbnail_url( $post_id, 'smpt-page-header-banner' );
	}

	?>
	<div class="featured-image page-header-image grid-container grid-parent smpt-featured-media smpt-featured-media--video">
		<?php if ( $poster_url ) : ?>
			<img
				class="smpt-featured-media__poster"
				src="<?php echo esc_url( $poster_url ); ?>"
				alt=""
				loading="eager"
				decoding="async"
				aria-hidden="true"
			>
		<?php endif; ?>
		<video
			class="smpt-featured-media__video"
			autoplay
			muted
			playsinline
			preload="metadata"
			<?php if ( $av1_url ) : ?>
				data-src-av1="<?php echo esc_url( $av1_url ); ?>"
			<?php endif; ?>
			<?php if ( $mp4_url ) : ?>
				data-src-mp4="<?php echo esc_url( $mp4_url ); ?>"
			<?php endif; ?>
			data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			<?php if ( $autoplay ) : ?>
				loop
			<?php endif; ?>
			<?php if ( $poster_url ) : ?>
				poster="<?php echo esc_url( $poster_url ); ?>"
			<?php endif; ?>
			aria-hidden="true"
		>
		</video>
		<?php if ( $overlay_url ) : ?>
			<div class="smpt-featured-media__overlay" aria-hidden="true">
				<img src="<?php echo esc_url( $overlay_url ); ?>" alt="" loading="lazy" decoding="async">
			</div>
		<?php endif; ?>
	</div>
	<?php
}
