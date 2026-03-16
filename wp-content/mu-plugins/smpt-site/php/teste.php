<?php
function gerar_episodios_old($inicio, $fim) {
    ob_start();
    
    echo '<div id="prismalunar">';
    
    for ($i = $inicio; $i <= $fim; $i++) {
        $num = str_pad($i, 2, '0', STR_PAD_LEFT); // Ensure at least two digits
        
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
        $video_src = "https://sm-portugal.com/streaming/" . $i . ".mp4";

        // Output HTML structure
        echo '<div id="episodio_' . $num . '" class="contentor_episodio smpt-table smpt-table--episode">
           <div class="cabecalho_video">
              <h2>Episódio ' . $i . '</h2>
           </div>
           <div class="nome_pt">' . do_shortcode('[epi num=' . $i . ' campo=nome_pt]') . '</div>
           <div class="contentor_video_inner invisivel">
              <video class="videoPlaceholder" controls preload="metadata" poster="' . $selected_image_src . '">
                 <source data-video-src="https://sm-portugal.com/streaming/' . $i . '.mp4" type="video/mp4">
              </video>
           </div>
           <div class="detalhes-episodio">
              <div class="rotulo">Título original:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_traduzido]') . '</div>
              <div class="rotulo">Título japonês:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_jp]') . '</div>
              <div class="rotulo">Título romanji:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_romanji]') . '</div>
           </div>
           <div class="episodio-media">
              <div class="episodio-opcoes">
                 <div class="episodio-coluna1">
                    <img decoding="async" loading="lazy" src="https://sm-portugal.com/anexos/imagens/episodios/' . $i . '.jpg" alt="Imagem do episódio ' . $num . '">
                 </div>
                 <div class="episodio-coluna2">
                    <div class="primeira-linha">
                       <button class="stream" onclick="VerOnline(\'' . $num . '\')">Ver online</button>
                       <button class="voltar invisivel" onclick="voltar(\'' . $num . '\')">« voltar</button>
                       <a class="download" onclick="trackDownload(\'download_ep' . $num . '_mp4\');" href="' . $video_src . '" download="Sailor Moon - ' . $num . ' (Dual Audio - Legendado 640X480)[smpt][h264 opus].mp4">Download 480p</a>
                    </div>
                    <div class="segunda-linha">
                       <div class="compatibilidade">
                          <label>
                             <input type="checkbox" id="episodio_' . $i . '" value="h264">
                             Modo compatibilidade
                          </label>
                       </div>
                    </div>
                 </div>
              </div>
           </div>
           <div class="resumo-episodio">' . do_shortcode('[epi num=' . $i . ' campo=resumo]') . '</div>
        </div>
        <div class="topo">
           <a href="#escolher-episodio" class="topo-link">↑ topo</a>
        </div>';
    }
    
    echo '</div>'; // Close the container prismalunar
    return ob_get_clean();
}

function gerar_episodios_shortcode_old($atts) {
    $atts = shortcode_atts(array(
        'inicio' => 1,
        'fim' => 10,
    ), $atts, 'html_episodios');

    return gerar_episodios($atts['inicio'], $atts['fim']);
}

// Legacy shortcode removed to avoid duplicate registration.
?>
