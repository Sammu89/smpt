<?php
/**
 * Table design settings and front-end styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the default table style settings.
 *
 * Empty values intentionally inherit from GeneratePress variables or
 * the general table tokens when overrides are not set.
 *
 * @return array<string, string>
 */
function smpt_get_table_style_defaults() {
	return array(
		'general_border_color'            => '',
		'general_background_color'        => '',
		'general_header_background_color' => '',
		'general_header_text_color'       => '',
		'general_label_color'             => '',
		'general_divider_color'           => '',
		'episode_border_color'            => '',
		'episode_background_color'        => '',
		'episode_header_background_color' => '',
		'episode_header_text_color'       => '',
		'episode_label_color'             => '',
		'episode_divider_color'           => '',
	);
}

/**
 * Sanitize an optional color value.
 *
 * Accepts the same formats as GeneratePress native color controls.
 *
 * @param string $value Raw color value.
 * @return string
 */
function smpt_sanitize_optional_color( $value ) {
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
 * Get a single table style setting with defaults applied.
 *
 * @param string $key Setting key.
 * @return string
 */
function smpt_get_table_style_setting( $key ) {
	$settings = wp_parse_args(
		(array) get_option( 'smpt_table_styles', array() ),
		smpt_get_table_style_defaults()
	);

	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/**
 * Add a color control to the table customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key          Setting key.
 * @param string               $label        Field label.
 * @param string               $section      Section ID.
 * @param int                  $priority     Control priority.
 * @param string               $toggle_id    GeneratePress toggle group ID.
 * @param string               $description  Optional description.
 * @return void
 */
function smpt_add_table_color_control( $wp_customize, $key, $label, $section, $priority, $toggle_id, $description = '' ) {
	$setting_id = 'smpt_table_styles[' . $key . ']';
	$control_class = class_exists( 'GeneratePress_Customize_Color_Control' ) ? 'GeneratePress_Customize_Color_Control' : 'WP_Customize_Color_Control';

	$wp_customize->add_setting(
		$setting_id,
		array(
			'default'           => smpt_get_table_style_setting( $key ),
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'smpt_sanitize_optional_color',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new $control_class(
			$wp_customize,
			$setting_id,
			array(
				'label'       => $label,
				'description' => $description,
				'section'     => $section,
				'priority'    => $priority,
				'choices'     => array(
					'alpha'    => true,
					'toggleId' => $toggle_id,
				),
			)
		)
	);
}

/**
 * Register customizer controls for reusable table styles.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function smpt_register_table_customizer( $wp_customize ) {
	$section = $wp_customize->get_section( 'generate_colors_section' ) ? 'generate_colors_section' : 'colors';

	if ( class_exists( 'GeneratePress_Customize_Field' ) ) {
		GeneratePress_Customize_Field::add_title(
			'smpt_tables_general_title',
			array(
				'section'  => $section,
				'title'    => __( 'Tabelas', 'generatepress' ),
				'priority' => 900,
				'choices' => array(
					'toggleId' => 'smpt-table-colors',
				),
			)
		);

		GeneratePress_Customize_Field::add_title(
			'smpt_tables_episode_title',
			array(
				'section'  => $section,
				'title'    => __( 'Tabelas de Episódios', 'generatepress' ),
				'priority' => 1000,
				'choices' => array(
					'toggleId' => 'smpt-table-episode-colors',
				),
			)
		);

	}

	smpt_add_table_color_control(
		$wp_customize,
		'general_border_color',
		__( 'Cor da borda', 'generatepress' ),
		$section,
		910,
		'smpt-table-colors',
		__( 'Usa a cor base do GeneratePress quando estiver vazio.', 'generatepress' )
	);
	smpt_add_table_color_control( $wp_customize, 'general_background_color', __( 'Cor de fundo', 'generatepress' ), $section, 920, 'smpt-table-colors' );
	smpt_add_table_color_control( $wp_customize, 'general_header_background_color', __( 'Fundo do cabeçalho', 'generatepress' ), $section, 930, 'smpt-table-colors' );
	smpt_add_table_color_control( $wp_customize, 'general_header_text_color', __( 'Texto do cabeçalho', 'generatepress' ), $section, 940, 'smpt-table-colors' );
	smpt_add_table_color_control( $wp_customize, 'general_label_color', __( 'Texto dos rótulos', 'generatepress' ), $section, 950, 'smpt-table-colors' );
	smpt_add_table_color_control( $wp_customize, 'general_divider_color', __( 'Cor do separador', 'generatepress' ), $section, 960, 'smpt-table-colors' );

	smpt_add_table_color_control(
		$wp_customize,
		'episode_border_color',
		__( 'Cor da borda', 'generatepress' ),
		$section,
		1010,
		'smpt-table-episode-colors',
		__( 'Deixe os campos vazios para herdar os valores gerais das tabelas.', 'generatepress' )
	);
	smpt_add_table_color_control( $wp_customize, 'episode_background_color', __( 'Cor de fundo', 'generatepress' ), $section, 1020, 'smpt-table-episode-colors' );
	smpt_add_table_color_control( $wp_customize, 'episode_header_background_color', __( 'Fundo do cabeçalho', 'generatepress' ), $section, 1030, 'smpt-table-episode-colors' );
	smpt_add_table_color_control( $wp_customize, 'episode_header_text_color', __( 'Texto do cabeçalho', 'generatepress' ), $section, 1040, 'smpt-table-episode-colors' );
	smpt_add_table_color_control( $wp_customize, 'episode_label_color', __( 'Texto dos rótulos', 'generatepress' ), $section, 1050, 'smpt-table-episode-colors' );
	smpt_add_table_color_control( $wp_customize, 'episode_divider_color', __( 'Cor do separador', 'generatepress' ), $section, 1060, 'smpt-table-episode-colors' );

}
add_action( 'generate_customize_after_controls', 'smpt_register_table_customizer', 20 );

/**
 * Build a CSS value with a fallback when the setting is empty.
 *
 * @param string $setting_key Setting key.
 * @param string $fallback    CSS fallback value.
 * @return string
 */
function smpt_get_table_css_value( $setting_key, $fallback ) {
	$value = smpt_get_table_style_setting( $setting_key );

	return $value ? $value : $fallback;
}

/**
 * Output the table CSS variables after GeneratePress styles load.
 *
 * @return void
 */
function smpt_enqueue_table_style_variables() {
	$css = ':root{' .
		'--smpt-table-border:' . smpt_get_table_css_value( 'general_border_color', 'var(--base)' ) . ';' .
		'--smpt-table-background:' . smpt_get_table_css_value( 'general_background_color', 'var(--base-3)' ) . ';' .
		'--smpt-table-header-background:' . smpt_get_table_css_value( 'general_header_background_color', 'var(--contrast)' ) . ';' .
		'--smpt-table-header-text:' . smpt_get_table_css_value( 'general_header_text_color', 'var(--base-3)' ) . ';' .
		'--smpt-table-label:' . smpt_get_table_css_value( 'general_label_color', 'var(--contrast-2)' ) . ';' .
		'--smpt-table-divider:' . smpt_get_table_css_value( 'general_divider_color', 'var(--base)' ) . ';' .
		'--smpt-table-episode-border:' . smpt_get_table_css_value( 'episode_border_color', 'var(--smpt-table-border)' ) . ';' .
		'--smpt-table-episode-background:' . smpt_get_table_css_value( 'episode_background_color', 'var(--smpt-table-background)' ) . ';' .
		'--smpt-table-episode-header-background:' . smpt_get_table_css_value( 'episode_header_background_color', 'var(--smpt-table-header-background)' ) . ';' .
		'--smpt-table-episode-header-text:' . smpt_get_table_css_value( 'episode_header_text_color', 'var(--smpt-table-header-text)' ) . ';' .
		'--smpt-table-episode-label:' . smpt_get_table_css_value( 'episode_label_color', 'var(--smpt-table-label)' ) . ';' .
		'--smpt-table-episode-divider:' . smpt_get_table_css_value( 'episode_divider_color', 'var(--smpt-table-divider)' ) . ';' .
		'}';

	wp_add_inline_style( 'generate-style', $css );
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_table_style_variables', 20 );
