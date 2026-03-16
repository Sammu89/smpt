<?php
/**
 * Infobox visual settings and editor help.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the default visual settings for infobox styles.
 *
 * @return array<string, string>
 */
function smpt_get_infobox_style_defaults() {
	return array(
		'default_background_color'         => '',
		'default_border_color'             => '',
		'default_border_width'             => '2',
		'default_padding_top'              => '40',
		'default_padding_right'            => '32',
		'default_padding_bottom'           => '32',
		'default_padding_left'             => '32',
		'default_max_width'                => '720',
		'default_min_height'               => '0',
		'default_body_text_color'          => '',
		'default_header_text_color'        => '',
		'default_footer_text_color'        => '',
		'default_divider_color'            => '',
		'default_close_text_color'         => '',
		'default_close_background_color'   => '',
		'default_close_border_color'       => '',
		'default_close_size'               => '36',
		'default_corner_size'              => '56',
		'default_corner_offset_x'          => '14',
		'default_corner_offset_y'          => '14',
		'default_corner_tl_id'             => '',
		'default_corner_tr_id'             => '',
		'default_corner_bl_id'             => '',
		'default_corner_br_id'             => '',
		'temporada_background_color'       => '',
		'temporada_border_color'           => '',
		'temporada_border_width'           => '',
		'temporada_padding_top'            => '',
		'temporada_padding_right'          => '',
		'temporada_padding_bottom'         => '',
		'temporada_padding_left'           => '',
		'temporada_max_width'              => '',
		'temporada_min_height'             => '',
		'temporada_body_text_color'        => '',
		'temporada_header_text_color'      => '',
		'temporada_footer_text_color'      => '',
		'temporada_divider_color'          => '',
		'temporada_close_text_color'       => '',
		'temporada_close_background_color' => '',
		'temporada_close_border_color'     => '',
		'temporada_close_size'             => '',
		'temporada_corner_size'            => '',
		'temporada_corner_offset_x'        => '',
		'temporada_corner_offset_y'        => '',
		'temporada_corner_tl_id'           => '',
		'temporada_corner_tr_id'           => '',
		'temporada_corner_bl_id'           => '',
		'temporada_corner_br_id'           => '',
	);
}

/**
 * Sanitize an optional color value.
 *
 * @param string $value Raw value.
 * @return string
 */
function smpt_sanitize_infobox_optional_color( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	if ( function_exists( 'generate_sanitize_rgba_color' ) ) {
		return generate_sanitize_rgba_color( $value );
	}

	$color = sanitize_hex_color( $value );

	return $color ? $color : '';
}

/**
 * Sanitize an optional integer value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function smpt_sanitize_infobox_optional_int( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	return (string) max( 0, absint( $value ) );
}

/**
 * Sanitize an optional media attachment id.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function smpt_sanitize_infobox_optional_attachment( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	return (string) absint( $value );
}

/**
 * Return all saved infobox style settings.
 *
 * @return array<string, string>
 */
function smpt_get_infobox_style_settings() {
	return wp_parse_args(
		(array) get_option( 'smpt_infobox_styles', array() ),
		smpt_get_infobox_style_defaults()
	);
}

/**
 * Return one infobox style setting.
 *
 * @param string $key Option key.
 * @return string
 */
function smpt_get_infobox_style_setting( $key ) {
	$settings = smpt_get_infobox_style_settings();

	return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
}

/**
 * Add an infobox color control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key          Setting key.
 * @param string               $label        Label text.
 * @param string               $section      Section id.
 * @param int                  $priority     Priority.
 * @return void
 */
function smpt_add_infobox_color_control( $wp_customize, $key, $label, $section, $priority ) {
	$setting_id = 'smpt_infobox_styles[' . $key . ']';
	$control_class = class_exists( 'GeneratePress_Customize_Color_Control' ) ? 'GeneratePress_Customize_Color_Control' : 'WP_Customize_Color_Control';

	$wp_customize->add_setting(
		$setting_id,
		array(
			'default'           => smpt_get_infobox_style_setting( $key ),
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'smpt_sanitize_infobox_optional_color',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new $control_class(
			$wp_customize,
			$setting_id,
			array(
				'label'    => $label,
				'section'  => $section,
				'priority' => $priority,
				'choices'  => array(
					'alpha' => true,
				),
			)
		)
	);
}

/**
 * Add an infobox numeric control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key          Setting key.
 * @param string               $label        Label text.
 * @param string               $section      Section id.
 * @param int                  $priority     Priority.
 * @param string               $description  Optional description.
 * @return void
 */
function smpt_add_infobox_number_control( $wp_customize, $key, $label, $section, $priority, $description = '' ) {
	$setting_id = 'smpt_infobox_styles[' . $key . ']';

	$wp_customize->add_setting(
		$setting_id,
		array(
			'default'           => smpt_get_infobox_style_setting( $key ),
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'smpt_sanitize_infobox_optional_int',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		$setting_id,
		array(
			'label'       => $label,
			'description' => $description,
			'section'     => $section,
			'priority'    => $priority,
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'step' => 1,
			),
		)
	);
}

/**
 * Add an infobox media picker control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key          Setting key.
 * @param string               $label        Label text.
 * @param string               $section      Section id.
 * @param int                  $priority     Priority.
 * @return void
 */
function smpt_add_infobox_media_control( $wp_customize, $key, $label, $section, $priority ) {
	$setting_id = 'smpt_infobox_styles[' . $key . ']';

	$wp_customize->add_setting(
		$setting_id,
		array(
			'default'           => smpt_get_infobox_style_setting( $key ),
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'smpt_sanitize_infobox_optional_attachment',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			$setting_id,
			array(
				'label'     => $label,
				'section'   => $section,
				'priority'  => $priority,
				'mime_type' => 'image',
			)
		)
	);
}

/**
 * Register infobox visual settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function smpt_register_infobox_customizer( $wp_customize ) {
	$wp_customize->add_panel(
		'smpt_infobox_panel',
		array(
			'title'       => __( 'Infoboxes', 'generatepress' ),
			'description' => __( 'Define o visual das infoboxes e do estilo de temporada.', 'generatepress' ),
			'priority'    => 160,
		)
	);

	$wp_customize->add_section(
		'smpt_infobox_default_section',
		array(
			'title'       => __( 'Infobox base', 'generatepress' ),
			'description' => __( 'Aplicado quando o shortcode não define nenhum estilo.', 'generatepress' ),
			'panel'       => 'smpt_infobox_panel',
			'priority'    => 10,
		)
	);

	$wp_customize->add_section(
		'smpt_infobox_temporada_section',
		array(
			'title'       => __( 'Estilo: temporada', 'generatepress' ),
			'description' => __( 'Sobrescreve o visual base quando o shortcode usa estilo="temporada".', 'generatepress' ),
			'panel'       => 'smpt_infobox_panel',
			'priority'    => 20,
		)
	);

	$base_section = 'smpt_infobox_default_section';
	$season_section = 'smpt_infobox_temporada_section';

	smpt_add_infobox_color_control( $wp_customize, 'default_background_color', __( 'Fundo', 'generatepress' ), $base_section, 10 );
	smpt_add_infobox_color_control( $wp_customize, 'default_border_color', __( 'Borda', 'generatepress' ), $base_section, 20 );
	smpt_add_infobox_color_control( $wp_customize, 'default_body_text_color', __( 'Texto do conteúdo', 'generatepress' ), $base_section, 30 );
	smpt_add_infobox_color_control( $wp_customize, 'default_header_text_color', __( 'Texto do cabeçalho', 'generatepress' ), $base_section, 40 );
	smpt_add_infobox_color_control( $wp_customize, 'default_footer_text_color', __( 'Texto do rodapé', 'generatepress' ), $base_section, 50 );
	smpt_add_infobox_color_control( $wp_customize, 'default_divider_color', __( 'Separador', 'generatepress' ), $base_section, 60 );
	smpt_add_infobox_color_control( $wp_customize, 'default_close_text_color', __( 'Texto do botão fechar', 'generatepress' ), $base_section, 70 );
	smpt_add_infobox_color_control( $wp_customize, 'default_close_background_color', __( 'Fundo do botão fechar', 'generatepress' ), $base_section, 80 );
	smpt_add_infobox_color_control( $wp_customize, 'default_close_border_color', __( 'Borda do botão fechar', 'generatepress' ), $base_section, 90 );
	smpt_add_infobox_number_control( $wp_customize, 'default_border_width', __( 'Espessura da borda (px)', 'generatepress' ), $base_section, 100 );
	smpt_add_infobox_number_control( $wp_customize, 'default_padding_top', __( 'Padding superior (px)', 'generatepress' ), $base_section, 110 );
	smpt_add_infobox_number_control( $wp_customize, 'default_padding_right', __( 'Padding direito (px)', 'generatepress' ), $base_section, 120 );
	smpt_add_infobox_number_control( $wp_customize, 'default_padding_bottom', __( 'Padding inferior (px)', 'generatepress' ), $base_section, 130 );
	smpt_add_infobox_number_control( $wp_customize, 'default_padding_left', __( 'Padding esquerdo (px)', 'generatepress' ), $base_section, 140 );
	smpt_add_infobox_number_control( $wp_customize, 'default_max_width', __( 'Largura máxima (px)', 'generatepress' ), $base_section, 150 );
	smpt_add_infobox_number_control( $wp_customize, 'default_min_height', __( 'Altura mínima (px)', 'generatepress' ), $base_section, 160 );
	smpt_add_infobox_number_control( $wp_customize, 'default_close_size', __( 'Tamanho do botão fechar (px)', 'generatepress' ), $base_section, 170 );
	smpt_add_infobox_number_control( $wp_customize, 'default_corner_size', __( 'Tamanho dos cantos (px)', 'generatepress' ), $base_section, 180 );
	smpt_add_infobox_number_control( $wp_customize, 'default_corner_offset_x', __( 'Offset horizontal dos cantos (px)', 'generatepress' ), $base_section, 190 );
	smpt_add_infobox_number_control( $wp_customize, 'default_corner_offset_y', __( 'Offset vertical dos cantos (px)', 'generatepress' ), $base_section, 200 );
	smpt_add_infobox_media_control( $wp_customize, 'default_corner_tl_id', __( 'Imagem canto superior esquerdo', 'generatepress' ), $base_section, 210 );
	smpt_add_infobox_media_control( $wp_customize, 'default_corner_tr_id', __( 'Imagem canto superior direito', 'generatepress' ), $base_section, 220 );
	smpt_add_infobox_media_control( $wp_customize, 'default_corner_bl_id', __( 'Imagem canto inferior esquerdo', 'generatepress' ), $base_section, 230 );
	smpt_add_infobox_media_control( $wp_customize, 'default_corner_br_id', __( 'Imagem canto inferior direito', 'generatepress' ), $base_section, 240 );

	smpt_add_infobox_color_control( $wp_customize, 'temporada_background_color', __( 'Fundo', 'generatepress' ), $season_section, 10 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_border_color', __( 'Borda', 'generatepress' ), $season_section, 20 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_body_text_color', __( 'Texto do conteúdo', 'generatepress' ), $season_section, 30 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_header_text_color', __( 'Texto do cabeçalho', 'generatepress' ), $season_section, 40 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_footer_text_color', __( 'Texto do rodapé', 'generatepress' ), $season_section, 50 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_divider_color', __( 'Separador', 'generatepress' ), $season_section, 60 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_close_text_color', __( 'Texto do botão fechar', 'generatepress' ), $season_section, 70 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_close_background_color', __( 'Fundo do botão fechar', 'generatepress' ), $season_section, 80 );
	smpt_add_infobox_color_control( $wp_customize, 'temporada_close_border_color', __( 'Borda do botão fechar', 'generatepress' ), $season_section, 90 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_border_width', __( 'Espessura da borda (px)', 'generatepress' ), $season_section, 100, __( 'Deixe vazio para herdar o valor base.', 'generatepress' ) );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_padding_top', __( 'Padding superior (px)', 'generatepress' ), $season_section, 110 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_padding_right', __( 'Padding direito (px)', 'generatepress' ), $season_section, 120 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_padding_bottom', __( 'Padding inferior (px)', 'generatepress' ), $season_section, 130 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_padding_left', __( 'Padding esquerdo (px)', 'generatepress' ), $season_section, 140 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_max_width', __( 'Largura máxima (px)', 'generatepress' ), $season_section, 150 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_min_height', __( 'Altura mínima (px)', 'generatepress' ), $season_section, 160 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_close_size', __( 'Tamanho do botão fechar (px)', 'generatepress' ), $season_section, 170 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_corner_size', __( 'Tamanho dos cantos (px)', 'generatepress' ), $season_section, 180 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_corner_offset_x', __( 'Offset horizontal dos cantos (px)', 'generatepress' ), $season_section, 190 );
	smpt_add_infobox_number_control( $wp_customize, 'temporada_corner_offset_y', __( 'Offset vertical dos cantos (px)', 'generatepress' ), $season_section, 200 );
	smpt_add_infobox_media_control( $wp_customize, 'temporada_corner_tl_id', __( 'Imagem canto superior esquerdo', 'generatepress' ), $season_section, 210 );
	smpt_add_infobox_media_control( $wp_customize, 'temporada_corner_tr_id', __( 'Imagem canto superior direito', 'generatepress' ), $season_section, 220 );
	smpt_add_infobox_media_control( $wp_customize, 'temporada_corner_bl_id', __( 'Imagem canto inferior esquerdo', 'generatepress' ), $season_section, 230 );
	smpt_add_infobox_media_control( $wp_customize, 'temporada_corner_br_id', __( 'Imagem canto inferior direito', 'generatepress' ), $season_section, 240 );
}
add_action( 'customize_register', 'smpt_register_infobox_customizer', 30 );

/**
 * Build a CSS variable value from an optional media attachment id.
 *
 * @param string $setting_key Option key.
 * @param string $fallback    Fallback CSS value.
 * @return string
 */
function smpt_get_infobox_corner_css_value( $setting_key, $fallback = 'none' ) {
	$attachment_id = absint( smpt_get_infobox_style_setting( $setting_key ) );

	if ( ! $attachment_id ) {
		return $fallback;
	}

	$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

	if ( ! $image_url ) {
		return $fallback;
	}

	return 'url("' . esc_url_raw( $image_url ) . '")';
}

/**
 * Return a CSS value with fallback when a setting is empty.
 *
 * @param string $setting_key Option key.
 * @param string $fallback    CSS fallback.
 * @param string $suffix      Optional suffix.
 * @return string
 */
function smpt_get_infobox_css_value( $setting_key, $fallback, $suffix = '' ) {
	$value = smpt_get_infobox_style_setting( $setting_key );

	if ( '' === $value ) {
		return $fallback;
	}

	return $value . $suffix;
}

/**
 * Output infobox CSS variables.
 *
 * @return void
 */
function smpt_enqueue_infobox_style_variables() {
	$css = ':root{' .
		'--smpt-infobox-default-background:' . smpt_get_infobox_css_value( 'default_background_color', 'var(--base-3)' ) . ';' .
		'--smpt-infobox-default-border-color:' . smpt_get_infobox_css_value( 'default_border_color', 'var(--base)' ) . ';' .
		'--smpt-infobox-default-border-width:' . smpt_get_infobox_css_value( 'default_border_width', '2px', 'px' ) . ';' .
		'--smpt-infobox-default-padding-top:' . smpt_get_infobox_css_value( 'default_padding_top', '40px', 'px' ) . ';' .
		'--smpt-infobox-default-padding-right:' . smpt_get_infobox_css_value( 'default_padding_right', '32px', 'px' ) . ';' .
		'--smpt-infobox-default-padding-bottom:' . smpt_get_infobox_css_value( 'default_padding_bottom', '32px', 'px' ) . ';' .
		'--smpt-infobox-default-padding-left:' . smpt_get_infobox_css_value( 'default_padding_left', '32px', 'px' ) . ';' .
		'--smpt-infobox-default-max-width:' . smpt_get_infobox_css_value( 'default_max_width', '720px', 'px' ) . ';' .
		'--smpt-infobox-default-min-height:' . smpt_get_infobox_css_value( 'default_min_height', '0px', 'px' ) . ';' .
		'--smpt-infobox-default-body-text:' . smpt_get_infobox_css_value( 'default_body_text_color', 'var(--contrast)' ) . ';' .
		'--smpt-infobox-default-header-text:' . smpt_get_infobox_css_value( 'default_header_text_color', 'var(--contrast)' ) . ';' .
		'--smpt-infobox-default-footer-text:' . smpt_get_infobox_css_value( 'default_footer_text_color', 'var(--contrast-2)' ) . ';' .
		'--smpt-infobox-default-divider:' . smpt_get_infobox_css_value( 'default_divider_color', 'var(--base)' ) . ';' .
		'--smpt-infobox-default-close-text:' . smpt_get_infobox_css_value( 'default_close_text_color', 'var(--contrast)' ) . ';' .
		'--smpt-infobox-default-close-background:' . smpt_get_infobox_css_value( 'default_close_background_color', 'var(--base-2)' ) . ';' .
		'--smpt-infobox-default-close-border:' . smpt_get_infobox_css_value( 'default_close_border_color', 'var(--base)' ) . ';' .
		'--smpt-infobox-default-close-size:' . smpt_get_infobox_css_value( 'default_close_size', '36px', 'px' ) . ';' .
		'--smpt-infobox-default-corner-size:' . smpt_get_infobox_css_value( 'default_corner_size', '56px', 'px' ) . ';' .
		'--smpt-infobox-default-corner-offset-x:' . smpt_get_infobox_css_value( 'default_corner_offset_x', '14px', 'px' ) . ';' .
		'--smpt-infobox-default-corner-offset-y:' . smpt_get_infobox_css_value( 'default_corner_offset_y', '14px', 'px' ) . ';' .
		'--smpt-infobox-default-corner-tl-image:' . smpt_get_infobox_corner_css_value( 'default_corner_tl_id' ) . ';' .
		'--smpt-infobox-default-corner-tr-image:' . smpt_get_infobox_corner_css_value( 'default_corner_tr_id' ) . ';' .
		'--smpt-infobox-default-corner-bl-image:' . smpt_get_infobox_corner_css_value( 'default_corner_bl_id' ) . ';' .
		'--smpt-infobox-default-corner-br-image:' . smpt_get_infobox_corner_css_value( 'default_corner_br_id' ) . ';' .
		'--smpt-infobox-temporada-background:' . smpt_get_infobox_css_value( 'temporada_background_color', 'var(--smpt-infobox-default-background)' ) . ';' .
		'--smpt-infobox-temporada-border-color:' . smpt_get_infobox_css_value( 'temporada_border_color', 'var(--smpt-infobox-default-border-color)' ) . ';' .
		'--smpt-infobox-temporada-border-width:' . smpt_get_infobox_css_value( 'temporada_border_width', 'var(--smpt-infobox-default-border-width)', 'px' ) . ';' .
		'--smpt-infobox-temporada-padding-top:' . smpt_get_infobox_css_value( 'temporada_padding_top', 'var(--smpt-infobox-default-padding-top)', 'px' ) . ';' .
		'--smpt-infobox-temporada-padding-right:' . smpt_get_infobox_css_value( 'temporada_padding_right', 'var(--smpt-infobox-default-padding-right)', 'px' ) . ';' .
		'--smpt-infobox-temporada-padding-bottom:' . smpt_get_infobox_css_value( 'temporada_padding_bottom', 'var(--smpt-infobox-default-padding-bottom)', 'px' ) . ';' .
		'--smpt-infobox-temporada-padding-left:' . smpt_get_infobox_css_value( 'temporada_padding_left', 'var(--smpt-infobox-default-padding-left)', 'px' ) . ';' .
		'--smpt-infobox-temporada-max-width:' . smpt_get_infobox_css_value( 'temporada_max_width', 'var(--smpt-infobox-default-max-width)', 'px' ) . ';' .
		'--smpt-infobox-temporada-min-height:' . smpt_get_infobox_css_value( 'temporada_min_height', 'var(--smpt-infobox-default-min-height)', 'px' ) . ';' .
		'--smpt-infobox-temporada-body-text:' . smpt_get_infobox_css_value( 'temporada_body_text_color', 'var(--smpt-infobox-default-body-text)' ) . ';' .
		'--smpt-infobox-temporada-header-text:' . smpt_get_infobox_css_value( 'temporada_header_text_color', 'var(--smpt-infobox-default-header-text)' ) . ';' .
		'--smpt-infobox-temporada-footer-text:' . smpt_get_infobox_css_value( 'temporada_footer_text_color', 'var(--smpt-infobox-default-footer-text)' ) . ';' .
		'--smpt-infobox-temporada-divider:' . smpt_get_infobox_css_value( 'temporada_divider_color', 'var(--smpt-infobox-default-divider)' ) . ';' .
		'--smpt-infobox-temporada-close-text:' . smpt_get_infobox_css_value( 'temporada_close_text_color', 'var(--smpt-infobox-default-close-text)' ) . ';' .
		'--smpt-infobox-temporada-close-background:' . smpt_get_infobox_css_value( 'temporada_close_background_color', 'var(--smpt-infobox-default-close-background)' ) . ';' .
		'--smpt-infobox-temporada-close-border:' . smpt_get_infobox_css_value( 'temporada_close_border_color', 'var(--smpt-infobox-default-close-border)' ) . ';' .
		'--smpt-infobox-temporada-close-size:' . smpt_get_infobox_css_value( 'temporada_close_size', 'var(--smpt-infobox-default-close-size)', 'px' ) . ';' .
		'--smpt-infobox-temporada-corner-size:' . smpt_get_infobox_css_value( 'temporada_corner_size', 'var(--smpt-infobox-default-corner-size)', 'px' ) . ';' .
		'--smpt-infobox-temporada-corner-offset-x:' . smpt_get_infobox_css_value( 'temporada_corner_offset_x', 'var(--smpt-infobox-default-corner-offset-x)', 'px' ) . ';' .
		'--smpt-infobox-temporada-corner-offset-y:' . smpt_get_infobox_css_value( 'temporada_corner_offset_y', 'var(--smpt-infobox-default-corner-offset-y)', 'px' ) . ';' .
		'--smpt-infobox-temporada-corner-tl-image:' . smpt_get_infobox_corner_css_value( 'temporada_corner_tl_id', 'var(--smpt-infobox-default-corner-tl-image)' ) . ';' .
		'--smpt-infobox-temporada-corner-tr-image:' . smpt_get_infobox_corner_css_value( 'temporada_corner_tr_id', 'var(--smpt-infobox-default-corner-tr-image)' ) . ';' .
		'--smpt-infobox-temporada-corner-bl-image:' . smpt_get_infobox_corner_css_value( 'temporada_corner_bl_id', 'var(--smpt-infobox-default-corner-bl-image)' ) . ';' .
		'--smpt-infobox-temporada-corner-br-image:' . smpt_get_infobox_corner_css_value( 'temporada_corner_br_id', 'var(--smpt-infobox-default-corner-br-image)' ) . ';' .
		'}';

	wp_add_inline_style( 'generate-style', $css );
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_infobox_style_variables', 25 );

/**
 * Add a helper page for editors.
 *
 * @return void
 */
function smpt_register_infobox_help_page() {
	add_theme_page(
		__( 'Infoboxes', 'generatepress' ),
		__( 'Infoboxes', 'generatepress' ),
		'edit_theme_options',
		'smpt-infoboxes',
		'smpt_render_infobox_help_page'
	);
}
add_action( 'admin_menu', 'smpt_register_infobox_help_page' );

/**
 * Render the infobox help page.
 *
 * @return void
 */
function smpt_render_infobox_help_page() {
	$blocks = get_posts(
		array(
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Infoboxes', 'generatepress' ); ?></h1>
		<p><?php esc_html_e( 'As infoboxes usam estilos do Customizer e blocos reutilizáveis para o cabeçalho, conteúdo e rodapé.', 'generatepress' ); ?></p>

		<h2><?php esc_html_e( 'Shortcode', 'generatepress' ); ?></h2>
		<pre>[infobox estilo="temporada" conteudo="temporada-05" rodape="ajuda" fechar="sim"]</pre>
		<pre>[infobox fechar="sim"]
[cabecalho]&lt;h2&gt;Título manual&lt;/h2&gt;[/cabecalho]
&lt;p&gt;Conteúdo manual da infobox.&lt;/p&gt;
[rodape]&lt;p&gt;Rodapé manual.&lt;/p&gt;[/rodape]
[/infobox]</pre>
		<ul>
			<li><code>estilo</code>: <?php esc_html_e( 'visual opcional. Se ficar vazio, usa o estilo base.', 'generatepress' ); ?></li>
			<li><code>cabecalho</code>, <code>conteudo</code>, <code>rodape</code>: <?php esc_html_e( 'slug de um bloco reutilizável publicado.', 'generatepress' ); ?></li>
			<li><code>fechar="sim"</code>: <?php esc_html_e( 'mostra o botão X. Se não existir, a infobox fica fixa.', 'generatepress' ); ?></li>
			<li><?php esc_html_e( 'Se "conteudo" não existir, a infobox usa o conteúdo escrito entre as tags do shortcode.', 'generatepress' ); ?></li>
			<li><?php esc_html_e( 'Também pode escrever [cabecalho]...[/cabecalho] e [rodape]...[/rodape] diretamente dentro do shortcode.', 'generatepress' ); ?></li>
		</ul>

		<h2><?php esc_html_e( 'Como criar blocos', 'generatepress' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Abra a lista de blocos reutilizáveis em', 'generatepress' ); ?> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wp_block' ) ); ?>">wp-admin/edit.php?post_type=wp_block</a>.</li>
			<li><?php esc_html_e( 'Crie um novo bloco sincronizado para o cabeçalho, conteúdo ou rodapé.', 'generatepress' ); ?></li>
			<li><?php esc_html_e( 'Use um título simples, por exemplo "Temporada 05" ou "Ajuda".', 'generatepress' ); ?></li>
			<li><?php esc_html_e( 'O shortcode deve usar o slug publicado do bloco, por exemplo conteudo="temporada-05".', 'generatepress' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Estilos disponíveis', 'generatepress' ); ?></h2>
		<ul>
			<li><code><?php esc_html_e( 'sem estilo', 'generatepress' ); ?></code>: <?php esc_html_e( 'usa o visual base.', 'generatepress' ); ?></li>
			<li><code>temporada</code>: <?php esc_html_e( 'usa o visual sazonal definido no Customizer.', 'generatepress' ); ?></li>
		</ul>

		<h2><?php esc_html_e( 'Blocos publicados', 'generatepress' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Título', 'generatepress' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'generatepress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $blocks as $block ) : ?>
					<tr>
						<td><?php echo esc_html( $block->post_title ); ?></td>
						<td><code><?php echo esc_html( $block->post_name ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
