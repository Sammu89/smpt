<?php
// Shortcode to include CSS files from the "css" folder within the child theme
function include_css_file_shortcode($atts) {
    $type = strtolower($atts[0] ?? '');
    $css_file = get_stylesheet_directory_uri() . '/css/' . $type . '.css';

    if (in_array($type, ['audio', 'video', 'teste'])) {
        if ($type === 'video') {
            // video.css is no longer used; episodios.css has all episode styles
            $episodios_file = get_stylesheet_directory_uri() . '/css/episodios.css';
            wp_enqueue_style('custom-episodios-css', $episodios_file);
        } else {
            wp_enqueue_style('custom-' . $type . '-css', $css_file);
        }

        return ''; // Prevent shortcode output
    }
    return ''; // Return empty string if shortcode attribute doesn't match expected values
}
add_shortcode('css', 'include_css_file_shortcode');

// Shortcode to include JS files from the "javascript" folder within the child theme
function include_js_file_shortcode($atts) {
    $type = strtolower($atts[0] ?? '');
    $js_file = get_stylesheet_directory_uri() . '/javascript/' . $type . '.js';

    if (in_array($type, ['audio', 'video', 'teste'])) {
        wp_enqueue_script('custom-' . $type . '-js', $js_file, array(), null, true);
        return ''; // Prevent shortcode output
    }
    return ''; // Return empty string if shortcode attribute doesn't match expected values
}
add_shortcode('js', 'include_js_file_shortcode');



function voltar_shortcode($atts) {
    $parent_id = wp_get_post_parent_id(get_the_ID());
    $default_url = $parent_id ? get_permalink($parent_id) : '#';

    $atts = shortcode_atts(array(
        'url' => $default_url
    ), $atts, 'voltar');

    return '<div class="voltar">
                <a class="button botao-voltar" href="' . esc_url($atts['url']) . '">← Voltar</a>
            </div>';
}
add_shortcode('voltar', 'voltar_shortcode');





/*ICONES INICIO*/
function smpt_get_link_icons() {
    return array(
        'magnet' => array(
            'url' => 'https://sm-portugal.com/wp-content/uploads/2023/11/icon-magnet.gif',
            'alt' => 'Magnet link',
        ),
        'torrent' => array(
            'url' => 'https://sm-portugal.com/wp-content/uploads/2023/11/torrent.png',
            'alt' => 'Torrent link',
        ),
        'link' => array(
            'url' => 'https://sm-portugal.com/wp-content/uploads/2023/11/external-link.png',
            'alt' => 'External link',
        ),
    );
}

function smpt_get_link_icon_markup( $type ) {
    $icons = smpt_get_link_icons();

    if ( ! isset( $icons[ $type ] ) ) {
        return '';
    }

    $icon = $icons[ $type ];

    return '<img decoding="async" class="ico" src="' . esc_url( $icon['url'] ) . '" alt="' . esc_attr( $icon['alt'] ) . '" height="12" width="12">';
}

function smpt_get_link_icon_type( $href ) {
    $href = trim( (string) $href );

    if ( '' === $href || str_starts_with( $href, '#' ) ) {
        return '';
    }

    if ( 0 === stripos( $href, 'magnet:' ) ) {
        return 'magnet';
    }

    $href_path = (string) wp_parse_url( $href, PHP_URL_PATH );

    if ( preg_match( '/\.torrent$/i', $href_path ) ) {
        return 'torrent';
    }

    if ( ! wp_http_validate_url( $href ) ) {
        return '';
    }

    $link_host = wp_parse_url( $href, PHP_URL_HOST );
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );

    if ( ! $link_host || ! $site_host ) {
        return '';
    }

    $internal_hosts = array_filter(
        array(
            strtolower( $site_host ),
            'sm-portugal.com',
            'www.sm-portugal.com',
        )
    );

    if ( ! in_array( strtolower( $link_host ), $internal_hosts, true ) ) {
        return 'link';
    }

    return '';
}

function smpt_get_link_icon_type_from_anchor( $href, $attributes ) {
    $candidates = array( trim( (string) $href ) );

    if ( preg_match( '/\bdata-id\s*=\s*(["\'])(.*?)\1/is', $attributes, $matches ) ) {
        $candidates[] = html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' );
    }

    if ( preg_match( '/\bid\s*=\s*(["\'])(.*?)\1/is', $attributes, $matches ) ) {
        $candidates[] = html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' );
    }

    foreach ( $candidates as $candidate ) {
        $icon_type = smpt_get_link_icon_type( $candidate );

        if ( '' !== $icon_type ) {
            return $icon_type;
        }
    }

    return '';
}

function smpt_add_icons_to_content_links( $content ) {
    if ( '' === trim( (string) $content ) || false === stripos( $content, '<a ' ) ) {
        return $content;
    }

    $content = preg_replace_callback(
        '/<a\b([^>]*)\bhref\s*=\s*(["\'])(.*?)\2([^>]*)>(.*?)<\/a>/is',
        static function ( $matches ) {
            $full_match = $matches[0];
            $before_href = $matches[1];
            $quote = $matches[2];
            $href = html_entity_decode( $matches[3], ENT_QUOTES, 'UTF-8' );
            $after_href = $matches[4];
            $inner_html = $matches[5];
            $attributes = $before_href . 'href=' . $quote . $matches[3] . $quote . $after_href;

            if ( false !== stripos( $attributes, 'smpt-link-iconized' ) || false !== stripos( $inner_html, 'class="ico"' ) || false !== stripos( $inner_html, "class='ico'" ) ) {
                return $full_match;
            }

            $icon_type = smpt_get_link_icon_type_from_anchor( $href, $attributes );

            if ( '' === $icon_type ) {
                return $full_match;
            }

            if ( preg_match( '/\bclass\s*=\s*(["\'])(.*?)\1/is', $attributes, $class_match ) ) {
                $new_class = trim( $class_match[2] . ' smpt-link-iconized' );
                $attributes = preg_replace(
                    '/\bclass\s*=\s*(["\'])(.*?)\1/is',
                    'class=' . $class_match[1] . esc_attr( $new_class ) . $class_match[1],
                    $attributes,
                    1
                );
            } else {
                $attributes .= ' class="smpt-link-iconized"';
            }

            return '<a' . $attributes . '>' . smpt_get_link_icon_markup( $icon_type ) . ' ' . $inner_html . '</a>';
        },
        $content
    );

    // Preserve readable spacing when stored content placed a link directly after plain text.
    $content = preg_replace(
        '/(?<=[^\s>])(<a\b[^>]*\bclass\s*=\s*(["\'])[^"\']*smpt-link-iconized[^"\']*\2[^>]*>\s*<img\b)/i',
        ' $1',
        $content
    );

    return $content;
}
add_filter( 'the_content', 'smpt_add_icons_to_content_links', 20 );

/*ICONES FIM*/

/*INFOBOX INICIO*/

function smpt_get_infobox_reusable_block_content( $slug ) {
    static $cache = array();

    $slug = sanitize_title( $slug );

    if ( '' === $slug ) {
        return '';
    }

    if ( isset( $cache[ $slug ] ) ) {
        return $cache[ $slug ];
    }

    $block = get_page_by_path( $slug, OBJECT, 'wp_block' );

    if ( ! $block instanceof WP_Post || 'publish' !== $block->post_status ) {
        $cache[ $slug ] = '';
        return '';
    }

    $cache[ $slug ] = do_shortcode( do_blocks( $block->post_content ) );

    return $cache[ $slug ];
}

function smpt_get_infobox_slot_markup( $slot_class, $slug ) {
    $slot_content = smpt_get_infobox_reusable_block_content( $slug );

    if ( '' === trim( $slot_content ) ) {
        return '';
    }

    return '<div class="' . esc_attr( $slot_class ) . '">' . $slot_content . '</div>';
}

function smpt_extract_infobox_inline_slot( $content, $tag ) {
    $pattern = '/\[' . preg_quote( $tag, '/' ) . '\](.*?)\[\/' . preg_quote( $tag, '/' ) . '\]/is';
    $slot    = '';

    if ( preg_match( $pattern, (string) $content, $matches ) ) {
        $slot    = do_shortcode( trim( $matches[1] ) );
        $content = preg_replace( $pattern, '', (string) $content, 1 );
    }

    return array(
        'slot'    => $slot,
        'content' => (string) $content,
    );
}

function smpt_passthrough_shortcode( $atts, $content = null ) {
    return do_shortcode( (string) $content );
}

function infobox_shortcode($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'estilo'    => '',
            'cabecalho' => '',
            'conteudo'  => '',
            'rodape'    => '',
            'fechar'    => '',
        ),
        $atts,
        'infobox'
    );

    $style_slug = sanitize_key( $atts['estilo'] );
    $has_close  = 'sim' === strtolower( trim( (string) $atts['fechar'] ) );
    $classes    = array( 'infobox', 'smpt-infobox' );

    if ( '' !== $style_slug ) {
        $classes[] = 'smpt-infobox--' . $style_slug;
    }

    if ( $has_close ) {
        $classes[] = 'has-close';
    }

    $parsed_content = smpt_extract_infobox_inline_slot( (string) $content, 'cabecalho' );
    $inline_header  = trim( $parsed_content['slot'] );

    $parsed_content = smpt_extract_infobox_inline_slot( $parsed_content['content'], 'rodape' );
    $inline_footer  = trim( $parsed_content['slot'] );
    $inline_body    = $parsed_content['content'];

    $header_markup = '';
    if ( '' !== trim( (string) $atts['cabecalho'] ) ) {
        $header_markup = smpt_get_infobox_slot_markup( 'smpt-infobox__header', $atts['cabecalho'] );
    } elseif ( '' !== $inline_header ) {
        $header_markup = '<div class="smpt-infobox__header">' . $inline_header . '</div>';
    }

    $footer_markup = '';
    if ( '' !== trim( (string) $atts['rodape'] ) ) {
        $footer_markup = smpt_get_infobox_slot_markup( 'smpt-infobox__footer', $atts['rodape'] );
    } elseif ( '' !== $inline_footer ) {
        $footer_markup = '<div class="smpt-infobox__footer">' . $inline_footer . '</div>';
    }

    if ( '' !== trim( (string) $atts['conteudo'] ) ) {
        $body_content = smpt_get_infobox_reusable_block_content( $atts['conteudo'] );
    } else {
        $body_content = do_shortcode( (string) $inline_body );
    }

    if ( '' === trim( $body_content ) && '' === $header_markup && '' === $footer_markup ) {
        return '';
    }

    $body_markup = '';
    if ( '' !== trim( $body_content ) ) {
        $body_markup = '<div class="smpt-infobox__body">' . $body_content . '</div>';
    }

    $close_button = '';
    if ( $has_close ) {
        $close_button = '<button type="button" class="smpt-infobox__close" data-smpt-infobox-close aria-label="' . esc_attr__( 'Fechar infobox', 'generatepress' ) . '">X</button>';
    }

    return '
    <div class="' . esc_attr( implode( ' ', $classes ) ) . '">
        ' . $close_button . '
        <span class="smpt-infobox__corner smpt-infobox__corner--tl" aria-hidden="true"></span>
        <span class="smpt-infobox__corner smpt-infobox__corner--tr" aria-hidden="true"></span>
        <span class="smpt-infobox__corner smpt-infobox__corner--bl" aria-hidden="true"></span>
        <span class="smpt-infobox__corner smpt-infobox__corner--br" aria-hidden="true"></span>
        <div class="smpt-infobox__inner">
            ' . $header_markup . '
            ' . $body_markup . '
            ' . $footer_markup . '
        </div>
    </div>';
}

add_shortcode('infobox', 'infobox_shortcode');
add_shortcode('cabecalho', 'smpt_passthrough_shortcode');
add_shortcode('rodape', 'smpt_passthrough_shortcode');

/*INFOBOX FIM*/


//BACKUP//

function backup_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Precisas de estar logado para ver os backups.</p>';
    }
    
    ob_start();
    
    $backupDir = '/var/www/html/smpt/site/backup/ficheiros';
    
    if (!is_dir($backupDir) || !is_readable($backupDir)) {
        return '<p>Erro: O diretório de backups não existe ou não pode ser lido.</p>';
    }
    
    $files = array_diff(scandir($backupDir), array('..', '.'));
    $groupedFiles = [];
    
    foreach ($files as $file) {
        if (preg_match('/(\d{8})_?(?:\d{4})?_SMPt_(.*)\.(gzip|tar\.gz)$/', $file, $matches)) {
            $date = $matches[1];
            $fileName = $matches[2];
            
            $formattedDate = DateTime::createFromFormat('Ymd', $date)->format('j \d\e F \d\e Y');
            $formattedDate = str_replace([
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ], [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ], $formattedDate);
            
            if (!isset($groupedFiles[$formattedDate])) {
                $groupedFiles[$formattedDate] = [
                    'database' => [],
                    'site_structure' => [],
                    'plugins' => [],
                    'uploads' => [],
                    'antigos' => []
                ];
            }
            
            if (strpos($fileName, 'database_luanavegante') !== false) {
                $groupedFiles[$formattedDate]['database'][] = $file;
            } elseif (strpos($fileName, 'antigos_visuais') !== false) {
                $groupedFiles[$formattedDate]['antigos'][] = $file;
            } elseif (strpos($fileName, 'ficheiros_site_debian') !== false) {
                $groupedFiles[$formattedDate]['site_structure'][] = $file;
            } elseif (strpos($fileName, 'plugins_site') !== false) {
                $groupedFiles[$formattedDate]['plugins'][] = $file;
            } elseif (strpos($fileName, 'uploads') !== false) {
                $groupedFiles[$formattedDate]['uploads'][] = $file;
            }
        }
    }
    
    krsort($groupedFiles);
    
    foreach ($groupedFiles as $date => $categories) {
        echo '<div class="backup-day">';
        echo "<h2>$date</h2>";

        $order = [
            'database' => 'Base de dados do site',
            'site_structure' => 'Estrutura geral do site',
            'plugins' => 'Plugins do site',
            'uploads' => 'Ficheiros de imagem do site',
            'antigos' => 'Antigos visuais (menos importante)'
        ];

        foreach ($order as $key => $description) {
            if (!empty($categories[$key])) {
                echo "<h3>$description</h3>";
                echo '<ul>';
                foreach ($categories[$key] as $file) {
                    echo '<li><a href="' . esc_url(site_url('/backup//ficheiros/' . $file)) . '" download>' . esc_html($file) . '</a></li>';
                }
                echo '</ul>';
            }
        }
        
        echo '</div>';
    }
    
    return ob_get_clean();
}
add_shortcode('backup', 'backup_shortcode');


// Create a custom shortcode [redirect url]
function custom_redirect_template_redirect() {
    if ( ! is_singular() ) {
        return;
    }

    global $post;

    if ( ! $post instanceof WP_Post || empty( $post->post_content ) ) {
        return;
    }

    if ( has_shortcode( $post->post_content, 'redirect' ) ) {
        // Use a regular expression to extract the URL from the shortcode
        if ( preg_match( '/\[redirect\s+url="([^"]+)"\]/', $post->post_content, $matches ) && ! empty( $matches[1] ) ) {
            $redirect_url = esc_url_raw( $matches[1] );
            wp_redirect( $redirect_url, 301 );
            exit;
        }
    }
}
add_action( 'template_redirect', 'custom_redirect_template_redirect' );




?>
