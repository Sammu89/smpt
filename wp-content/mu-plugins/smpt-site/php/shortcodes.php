<?php
// Shortcode to include CSS files from the "css" folder within the child theme
function include_css_file_shortcode($atts) {
    $type = strtolower($atts[0] ?? '');
    $css_file = get_stylesheet_directory_uri() . '/css/' . $type . '.css';

    if (in_array($type, ['audio', 'video', 'teste'])) {
        // Enqueue the main CSS file
        wp_enqueue_style('custom-' . $type . '-css', $css_file);
        
        // If the type is video, also enqueue episodios.css
        if ($type === 'video') {
            $episodios_file = get_stylesheet_directory_uri() . '/css/episodios.css';
            wp_enqueue_style('custom-episodios-css', $episodios_file);
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
                <button class="botao-voltar">
                    <a href="' . esc_url($atts['url']) . '">← Voltar</a>
                </button>
            </div>';
}
add_shortcode('voltar', 'voltar_shortcode');





/*ICONES INICIO*/
function icone_shortcode($atts) {
    $icons = array(
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
    
    $atts = shortcode_atts(
        array(
            'tipo' => '',
        ), 
        $atts,
        'icone'
    );

    $attribute = trim($atts['tipo']);
    
    if (isset($icons[$attribute])) {
        $icon = $icons[$attribute];
        return '<img decoding="async" class="ico" src="' . esc_url($icon['url']) . '" alt="' . esc_attr($icon['alt']) . '" height="12" width="12">';
    }

    return '';
}

add_shortcode('icone', 'icone_shortcode');

/*ICONES FIM*/

/*INFOBOX INICIO*/

function infobox_shortcode($atts, $content = null) {
    // Set up default attributes including the new 'estilo' and 'adicionar' attributes
    $atts = shortcode_atts(
        array(
            'temporada' => '',
            'estilo'    => '', 
            'adicionar' => '',
        ), 
        $atts,
        'infobox'
    );

    $temporada = esc_attr($atts['temporada']);
    $estilo    = esc_attr($atts['estilo']);
    $adicionar = esc_attr($atts['adicionar']);

    $temporada_content = '
        <p><strong>Stream: </strong>Ficheiros de qualidade média com cerca de 50MB, disponibilizados em 2024</p>
        <p><strong>Formato:</strong> MP4</p>
        <p><strong>Audio:</strong> Opus, 48000 Hz, TV-Rip, VHS-RIP</p>
        <p><strong>Vídeo:</strong> MP4 (codec AV1) 640×480, Bluray-Rip | <i>Modo compatibilidade:</i> MP4 (codec h264)</p>
        <a class="destaque" href="https://sm-portugal.com/download-s' . esc_attr($temporada) . '">[icone tipo="link"] Esta série encontra-se disponível em alta qualidade 1080p aqui</a>';

    $problema = '<div class="contentor" style="width: 85%; max-width: 470px; margin: 20px auto 10px auto;">
        <div style="flex: 0 0 auto;" class="nao-telemovel"><img src="https://sm-portugal.com/wp-content/uploads/2024/06/probleminha.png" width="200" alt="" /></div>
        <div style="flex: 1; margin-left: 10px; display: flex; flex-direction: column; justify-content: center;">
            <h3 style="margin: 0;">Algum problema?</h3>
            <p style="margin: 0;">Contacta-nos através do <a href="https://sm-portugal.com/livro-de-visitas/" data-type="page" data-id="2146"><strong>livro de visitas</strong></a></p>
        </div>
    </div>';

    $content_to_use = !empty($temporada) ? $temporada_content : $content;
    
    // Removed sanitization
    $sanitized_content = $content_to_use;

    $fechar_btn_style = '';
    $placeholder_style = '';
    $adicionar_final = '';

    $estilo_values = array_map('trim', explode(' ', $estilo));
    if (in_array('bloqueada', $estilo_values)) {
        $fechar_btn_style = 'style="display:none;"';
    }
    if (in_array('noplaceholder', $estilo_values)) {
        $placeholder_style = 'style="display:none;"';
    }

    $adicionar_values = array_map('trim', explode(' ', $adicionar));
    if (in_array('problema', $adicionar_values)) {
        $adicionar_final = $problema;
    }

    // Ensure $adicionar_final is empty if $adicionar is empty
    if (empty($adicionar)) {
        $adicionar_final = '';
    }

    return '
    <div class="infobox">
        <div class="placeholder" ' . $placeholder_style . '></div>
        <div class="fechar-btn" onclick="fecharInfoBox()" ' . $fechar_btn_style . '>X</div>
        ' . do_shortcode($sanitized_content) . '
        ' . $adicionar_final . '
    </div>
    <script>
        function fecharInfoBox() {
            var infoBox = document.querySelector(".infobox");
            if (infoBox) {
                infoBox.style.display = "none";
            }
        }
    </script>';
}

add_shortcode('infobox', 'infobox_shortcode');

/*INFOBOX FIM*/


/** Video Banner */
function custom_video_banner_shortcode() {
    // Get the user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Check if the user agent indicates a mobile device using the correct variable name
    $isMobile = preg_match("/(android|iphone|ipod|opera mini|iemobile|mobile)/i", $user_agent);

    ob_start();

    if ($isMobile) {
        // Output for mobile devices
        echo '<div class="wp-block-image">
                <picture class="aligncenter size-full">
                    <source srcset="https://sm-portugal.com/wp-content/uploads/2024/08/logosailormoonwebp.webp" type="image/webp">
                    <img decoding="async" width="600" height="216" src="https://sm-portugal.com/wp-content/uploads/2023/12/belaguardia.png" alt="" class="Logo Navegante da Lua">
                </picture>
              </div>';
    } else {
        // Output for non-mobile devices
        echo '<div id="intro" class="wp-block-post-featured-image">
                <video autoplay muted loop>
                    <source src="https://sm-portugal.com/wp-content/uploads/2024/08/IntroAV1.mp4" type="video/mp4; codecs=av01.0.04M.08">
                    <source src="https://sm-portugal.com/wp-content/uploads/2023/11/intro.mp4" type="video/mp4">
                </video>
                <div class="wp-block-image" style="z-index: 3; position: relative;">
                    <picture class="aligncenter size-full">
                        <source srcset="https://sm-portugal.com/wp-content/uploads/2024/08/logosailormoonwebp.webp" type="image/webp">
                        <img decoding="async" width="600" height="216" src="https://sm-portugal.com/wp-content/uploads/2023/12/belaguardia.png" alt="" class="Logo Navegante da Lua">
                    </picture>
                </div>
              </div>';
    }

    return ob_get_clean();

}

// Register the shortcode
add_shortcode('videobanner', 'custom_video_banner_shortcode');






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
