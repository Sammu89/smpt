<?php
/**
 * Check whether streaming controls should be shown to the current visitor.
 *
 * @return bool
 */
function smpt_streaming_access_allowed() {
	if ( function_exists( 'smpt_current_visitor_is_allowed' ) ) {
		return smpt_current_visitor_is_allowed();
	}

	return true;
}

/**
 * Render the episode media area for allowed visitors.
 *
 * @param int    $episode_number Episode number.
 * @param string $episode_padded Padded episode number.
 * @param string $video_src      Streaming URL.
 * @return string
 */
function smpt_render_episode_media_allowed( $episode_number, $episode_padded, $video_src ) {
	return '
           <div class="episodio-media">
              <div class="episodio-opcoes">
                 <div class="episodio-coluna1">
                    <img decoding="async" src="https://sm-portugal.com/anexos/imagens/episodios/' . $episode_number . '.jpg" alt="Imagem do episódio ' . $episode_padded . '">
                 </div>
                 <div class="episodio-coluna2">
                    <div class="primeira-linha">
                       <button class="stream" onclick="VerOnline(\'' . $episode_padded . '\')">Ver online</button>
                       <button class="voltar invisivel" onclick="voltar(\'' . $episode_padded . '\')">« voltar</button>
                       <a class="download" onclick="trackDownload(\'download_ep' . $episode_padded . '_mp4\');" href="' . $video_src . '" download="Sailor Moon - ' . $episode_padded . ' (Dual Audio - Legendado 640X480)[smpt][av1 opus].mp4">Download 480p</a>
                    </div>
                    <div class="segunda-linha">
                       <div class="compatibilidade">
                          <label>
                             <input type="checkbox" id="episodio_' . $episode_number . '" value="h264">
                              <div class="tooltip"><span>Modo compatibilidade</span>
							 <span class="tooltiptext">Activar quando o vídeo não funcionar (especialmente em dispositivos Apple). Carrega um vídeo com menor qualidade.</span>
							 </div>
                          </label>
                       </div>
                    </div>
                 </div>
              </div>
           </div>';
}

function gerar_episodios($inicio, $fim) {
    ob_start();
    $streaming_allowed = smpt_streaming_access_allowed();
    
    echo '
	<div id="loading" style="display:flex; justify-content:center; align-items:center;">
    <img id="loading-image" src="https://sm-portugal.com/wp-content/uploads/2024/12/loading.gif" alt="A Carregar..." />
	</div>
	<div id="prismalunar" style="display:none">';
    
    for ($i = $inicio; $i <= $fim; $i++) {
        $num = str_pad($i, 3, '0', STR_PAD_LEFT); // Ensure at least three digits
        
        $image_sources = [
            [1, 26, "https://sm-portugal.com/wp-content/uploads/2023/12/classica.jpg"],
            [27, 46, "https://sm-portugal.com/wp-content/uploads/2023/12/classica2.jpg"],
            [47, 70, "https://sm-portugal.com/wp-content/uploads/2023/12/r1.jpg"],
            [71, 89, "https://sm-portugal.com/wp-content/uploads/2024/06/r2.jpg"],
            [90, 127, "https://sm-portugal.com/wp-content/uploads/2023/12/s.jpg"],
            [128, 166, "https://sm-portugal.com/wp-content/uploads/2023/12/ss.jpg"],
            [167, 200, "https://sm-portugal.com/wp-content/uploads/2023/12/stars.jpg"]
        ];

        // Default image source
        $selected_image_src = "";
        foreach ($image_sources as $range) {
            if ($i >= $range[0] && $i <= $range[1]) {
                $selected_image_src = $range[2];
                break;
            }
        }

        // Construct video source based on episode number
        $video_src = "https://sm-portugal.com/streaming/Sailor Moon - " . $num . " (Dual Audio - Legendado 640x480) [av1 opus][smpt].mp4";

        // Output HTML structure
        echo '	
		<div id="episodio_' . $num . '" class="contentor_episodio">
           <div class="cabecalho_video">
              <h2>Episódio ' . $i . '</h2>
           </div>
           <div class="nome_pt">' . do_shortcode('[epi num=' . $i . ' campo=nome_pt]') . '</div>
           ';
        if ( $streaming_allowed ) {
            echo '<div class="contentor_video_inner invisivel"><video class="videoPlaceholder" controls preload="metadata" poster="' . $selected_image_src . '">
                 <source data-video-src="' . $video_src . '" type="video/mp4">
              </video></div>';
        }
        echo '
           <div class="detalhes-episodio">
              <div class="rotulo">Título original:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_traduzido]') . '</div>
              <div class="rotulo">Título japonês:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_jp]') . '</div>
              <div class="rotulo">Título romanji:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_romanji]') . '</div>
           </div>
           ' . ( $streaming_allowed ? smpt_render_episode_media_allowed( $i, $num, $video_src ) : '' ) . '
           <div class="resumo-episodio">';
        if ( ! $streaming_allowed ) {
            echo '<img decoding="async" src="https://sm-portugal.com/anexos/imagens/episodios/' . $i . '.jpg" alt="Imagem do episódio ' . $num . '" style="float:left; margin:0 10px 5px 0;">';
        }
        echo do_shortcode('[epi num=' . $i . ' campo=resumo]') . '</div>
        </div>
        <div class="topo">
           <a href="#escolher-episodio" class="topo-link">↑ topo</a>
        </div>';
    }
    
    echo '</div>'; // Close the container prismalunar
    return ob_get_clean();
}

function gerar_episodios_shortcode($atts) {
    $atts = shortcode_atts(array(
        'inicio' => 1,
        'fim' => 10,
    ), $atts, 'html_episodios');

    return gerar_episodios($atts['inicio'], $atts['fim']);
}

add_shortcode('html_episodios', 'gerar_episodios_shortcode');
?>
