<?php
/**
 * Page-to-page navigation via custom fields.
 *
 * Adds a meta box to pages allowing editors to pick prev/next pages and labels,
 * then renders the nav automatically via GeneratePress hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SMPT_PAGE_NAV_PREV_ID_META    = '_smpt_page_nav_prev_id';
const SMPT_PAGE_NAV_PREV_LABEL_META = '_smpt_page_nav_prev_label';
const SMPT_PAGE_NAV_NEXT_ID_META    = '_smpt_page_nav_next_id';
const SMPT_PAGE_NAV_NEXT_LABEL_META = '_smpt_page_nav_next_label';
const SMPT_PAGE_NAV_SHOW_BEGIN_META = '_smpt_page_nav_show_beginning';
const SMPT_PAGE_NAV_SHOW_END_META   = '_smpt_page_nav_show_end';

/* -------------------------------------------------------------------------
 * Meta box
 * ---------------------------------------------------------------------- */

function smpt_add_page_nav_meta_box() {
	add_meta_box(
		'smpt-page-nav',
		__( 'SMPT Page Navigation', 'generatepress' ),
		'smpt_render_page_nav_meta_box',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_page', 'smpt_add_page_nav_meta_box' );

/**
 * Render the page navigation meta box.
 *
 * @param WP_Post $post Current post object.
 */
function smpt_render_page_nav_meta_box( $post ) {
	wp_nonce_field( 'smpt_save_page_nav_meta', 'smpt_page_nav_nonce' );

	$prev_id    = (int) get_post_meta( $post->ID, SMPT_PAGE_NAV_PREV_ID_META, true );
	$prev_label = get_post_meta( $post->ID, SMPT_PAGE_NAV_PREV_LABEL_META, true );
	$next_id    = (int) get_post_meta( $post->ID, SMPT_PAGE_NAV_NEXT_ID_META, true );
	$next_label = get_post_meta( $post->ID, SMPT_PAGE_NAV_NEXT_LABEL_META, true );
	$show_begin = get_post_meta( $post->ID, SMPT_PAGE_NAV_SHOW_BEGIN_META, true );
	$show_end   = get_post_meta( $post->ID, SMPT_PAGE_NAV_SHOW_END_META, true );
	?>
	<p>
		<label for="smpt-page-nav-prev-id"><strong><?php esc_html_e( 'Previous page', 'generatepress' ); ?></strong></label>
		<?php
		wp_dropdown_pages( array(
			'name'              => 'smpt_page_nav_prev_id',
			'id'                => 'smpt-page-nav-prev-id',
			'selected'          => $prev_id,
			'show_option_none'  => __( '— None —', 'generatepress' ),
			'option_none_value' => 0,
			'sort_column'       => 'post_title',
			'post_status'       => 'publish',
			'class'             => 'widefat',
		) );
		?>
	</p>
	<p>
		<label for="smpt-page-nav-prev-label"><strong><?php esc_html_e( 'Previous label', 'generatepress' ); ?></strong></label>
		<input type="text" class="widefat" id="smpt-page-nav-prev-label" name="smpt_page_nav_prev_label" value="<?php echo esc_attr( $prev_label ); ?>" placeholder="<?php esc_attr_e( 'e.g. Eps. 1 a 46 (Série clássica)', 'generatepress' ); ?>">
	</p>
	<hr>
	<p>
		<label for="smpt-page-nav-next-id"><strong><?php esc_html_e( 'Next page', 'generatepress' ); ?></strong></label>
		<?php
		wp_dropdown_pages( array(
			'name'              => 'smpt_page_nav_next_id',
			'id'                => 'smpt-page-nav-next-id',
			'selected'          => $next_id,
			'show_option_none'  => __( '— None —', 'generatepress' ),
			'option_none_value' => 0,
			'sort_column'       => 'post_title',
			'post_status'       => 'publish',
			'class'             => 'widefat',
		) );
		?>
	</p>
	<p>
		<label for="smpt-page-nav-next-label"><strong><?php esc_html_e( 'Next label', 'generatepress' ); ?></strong></label>
		<input type="text" class="widefat" id="smpt-page-nav-next-label" name="smpt_page_nav_next_label" value="<?php echo esc_attr( $next_label ); ?>" placeholder="<?php esc_attr_e( 'e.g. Eps. 47 a 89 (Série R)', 'generatepress' ); ?>">
	</p>
	<hr>
	<p><strong><?php esc_html_e( 'Show navigation at', 'generatepress' ); ?></strong></p>
	<p>
		<label>
			<input type="checkbox" name="smpt_page_nav_show_beginning" value="1" <?php checked( $show_begin, '1' ); ?>>
			<?php esc_html_e( 'Beginning of content', 'generatepress' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input type="checkbox" name="smpt_page_nav_show_end" value="1" <?php checked( $show_end, '1' ); ?>>
			<?php esc_html_e( 'End of content', 'generatepress' ); ?>
		</label>
	</p>
	<?php
}

/* -------------------------------------------------------------------------
 * Save
 * ---------------------------------------------------------------------- */

/**
 * Save page navigation meta fields.
 *
 * @param int $post_id Post ID.
 */
function smpt_save_page_nav_meta( $post_id ) {
	if ( ! isset( $_POST['smpt_page_nav_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_page_nav_nonce'] ) ), 'smpt_save_page_nav_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$id_fields = array(
		SMPT_PAGE_NAV_PREV_ID_META => 'smpt_page_nav_prev_id',
		SMPT_PAGE_NAV_NEXT_ID_META => 'smpt_page_nav_next_id',
	);

	foreach ( $id_fields as $meta_key => $form_key ) {
		$value = isset( $_POST[ $form_key ] ) ? absint( $_POST[ $form_key ] ) : 0;

		if ( $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	$label_fields = array(
		SMPT_PAGE_NAV_PREV_LABEL_META => 'smpt_page_nav_prev_label',
		SMPT_PAGE_NAV_NEXT_LABEL_META => 'smpt_page_nav_next_label',
	);

	foreach ( $label_fields as $meta_key => $form_key ) {
		$value = isset( $_POST[ $form_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $form_key ] ) ) : '';

		if ( $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	$checkbox_fields = array(
		SMPT_PAGE_NAV_SHOW_BEGIN_META => 'smpt_page_nav_show_beginning',
		SMPT_PAGE_NAV_SHOW_END_META  => 'smpt_page_nav_show_end',
	);

	foreach ( $checkbox_fields as $meta_key => $form_key ) {
		if ( ! empty( $_POST[ $form_key ] ) ) {
			update_post_meta( $post_id, $meta_key, '1' );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}
add_action( 'save_post_page', 'smpt_save_page_nav_meta' );

/* -------------------------------------------------------------------------
 * Front-end rendering
 * ---------------------------------------------------------------------- */

/**
 * Build the page navigation HTML.
 *
 * @param string $modifier CSS modifier class (e.g. 'beginning' or 'end').
 * @return string HTML or empty string.
 */
function smpt_get_page_nav_html( $modifier = '' ) {
	if ( ! is_page() ) {
		return '';
	}

	$post_id = get_queried_object_id();

	if ( ! $post_id ) {
		return '';
	}

	$prev_id    = (int) get_post_meta( $post_id, SMPT_PAGE_NAV_PREV_ID_META, true );
	$prev_label = get_post_meta( $post_id, SMPT_PAGE_NAV_PREV_LABEL_META, true );
	$next_id    = (int) get_post_meta( $post_id, SMPT_PAGE_NAV_NEXT_ID_META, true );
	$next_label = get_post_meta( $post_id, SMPT_PAGE_NAV_NEXT_LABEL_META, true );

	if ( ! $prev_id && ! $next_id ) {
		return '';
	}

	$links = '';

	if ( $prev_id && 'publish' === get_post_status( $prev_id ) ) {
		$label  = $prev_label ? $prev_label : get_the_title( $prev_id );
		$links .= sprintf(
			'<a class="smpt-page-nav__link smpt-page-nav__link--prev" href="%s"><span class="smpt-page-nav__arrow smpt-page-nav__arrow--prev" aria-hidden="true"></span>%s</a>',
			esc_url( get_permalink( $prev_id ) ),
			esc_html( $label )
		);
	}

	if ( $next_id && 'publish' === get_post_status( $next_id ) ) {
		$label  = $next_label ? $next_label : get_the_title( $next_id );
		$links .= sprintf(
			'<a class="smpt-page-nav__link smpt-page-nav__link--next" href="%s">%s<span class="smpt-page-nav__arrow smpt-page-nav__arrow--next" aria-hidden="true"></span></a>',
			esc_url( get_permalink( $next_id ) ),
			esc_html( $label )
		);
	}

	if ( ! $links ) {
		return '';
	}

	$class = 'smpt-page-nav';

	if ( $modifier ) {
		$class .= ' smpt-page-nav--' . sanitize_html_class( $modifier );
	}

	return sprintf(
		'<nav class="%s" aria-label="%s">%s</nav>',
		esc_attr( $class ),
		esc_attr__( 'Navegação entre páginas', 'generatepress' ),
		$links
	);
}

/**
 * Render page nav at the beginning of the content area.
 */
function smpt_maybe_render_page_nav_beginning() {
	if ( ! is_page() ) {
		return;
	}

	if ( '1' !== get_post_meta( get_queried_object_id(), SMPT_PAGE_NAV_SHOW_BEGIN_META, true ) ) {
		return;
	}

	echo smpt_get_page_nav_html( 'beginning' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'generate_after_entry_header', 'smpt_maybe_render_page_nav_beginning', 5 );

/**
 * Render page nav at the end of the content area.
 */
function smpt_maybe_render_page_nav_end() {
	if ( ! is_page() ) {
		return;
	}

	if ( '1' !== get_post_meta( get_queried_object_id(), SMPT_PAGE_NAV_SHOW_END_META, true ) ) {
		return;
	}

	echo smpt_get_page_nav_html( 'end' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'generate_after_content', 'smpt_maybe_render_page_nav_end', 10 );
