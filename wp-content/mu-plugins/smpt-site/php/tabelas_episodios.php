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
 * Return the download page URL for a given episode number.
 *
 * @param int $episode_number Episode number (1-200).
 * @return string Permalink to the corresponding download page.
 */
function smpt_get_download_page_url( $episode_number ) {
	$map = array(
		array( 1,   46,  1060 ),
		array( 47,  89,  3899 ),
		array( 90,  127, 4430 ),
		array( 128, 166, 5672 ),
		array( 167, 200, 5918 ),
	);

	foreach ( $map as $range ) {
		if ( $episode_number >= $range[0] && $episode_number <= $range[1] ) {
			return get_permalink( $range[2] );
		}
	}

	return '#';
}

/**
 * Return the season key for a given episode number.
 *
 * @param int $num Episode number (1-200).
 * @return string Season key like "temporada1".
 */
function smpt_get_episode_season( $num ) {
	$seasons = array(
		array( 1,   46,  'temporada1' ),
		array( 47,  89,  'temporada2' ),
		array( 90,  127, 'temporada3' ),
		array( 128, 166, 'temporada4' ),
		array( 167, 200, 'temporada5' ),
	);

	foreach ( $seasons as $range ) {
		if ( $num >= $range[0] && $num <= $range[1] ) {
			return $range[2];
		}
	}

	return '';
}

/**
 * Return nostalgia emissions for a given episode number.
 *
 * @param int   $num            Episode number (1-200).
 * @param array $nostalgia_data Decoded nostalgia JSON data.
 * @return array Array of emissions, each with 'source', 'url', and 'type' keys.
 */
function smpt_get_nostalgia_emissions( $num, $nostalgia_data ) {
	$season = smpt_get_episode_season( $num );
	if ( '' === $season || ! isset( $nostalgia_data['episodios'][ $season ][ $num ] ) ) {
		return array();
	}

	$entries = $nostalgia_data['episodios'][ $season ][ $num ];
	if ( ! is_array( $entries ) || empty( $entries ) ) {
		return array();
	}

	$sources_map = isset( $nostalgia_data['sources'] ) ? $nostalgia_data['sources'] : array();
	$emissions   = array();

	foreach ( $entries as $entry ) {
		if ( is_string( $entry ) ) {
			// Plain string: Dailymotion video ID, default source is tvi
			if ( '' === trim( $entry ) ) {
				continue;
			}
			$emissions[] = array(
				'source' => 'tvi',
				'url'    => 'https://geo.dailymotion.com/player.html?video=' . $entry,
				'type'   => 'dailymotion',
			);
		} elseif ( is_array( $entry ) ) {
			// Object with externalurl
			if ( isset( $entry['externalurl'] ) && '' !== trim( $entry['externalurl'] ) ) {
				$src = isset( $entry['source'] ) ? $entry['source'] : 'tvi';
				$emissions[] = array(
					'source' => $src,
					'url'    => $entry['externalurl'],
					'type'   => 'external',
				);
			} else {
				// Object with url1/source1
				if ( isset( $entry['url1'] ) && '' !== trim( $entry['url1'] ) ) {
					$src = isset( $entry['source1'] ) ? $entry['source1'] : 'tvi';
					$emissions[] = array(
						'source' => $src,
						'url'    => 'https://geo.dailymotion.com/player.html?video=' . $entry['url1'],
						'type'   => 'dailymotion',
					);
				}
				// Object with url2/source2
				if ( isset( $entry['url2'] ) && '' !== trim( $entry['url2'] ) ) {
					$src = isset( $entry['source2'] ) ? $entry['source2'] : 'tvi';
					$emissions[] = array(
						'source' => $src,
						'url'    => 'https://geo.dailymotion.com/player.html?video=' . $entry['url2'],
						'type'   => 'dailymotion',
					);
				}
			}
		}
	}

	return $emissions;
}

/**
 * Render the episode media area for allowed visitors.
 *
 * @param int    $episode_number      Episode number.
 * @param string $episode_padded      Padded episode number.
 * @param string $video_src           Streaming URL (AV1).
 * @param array  $nostalgia_emissions Nostalgia emissions array.
 * @return string
 */
function smpt_render_episode_media_allowed( $episode_number, $episode_padded, $video_src, $nostalgia_emissions = array(), $poster_src = '' ) {
	$h264_src     = str_replace(
		array( 'https://sm-portugal.com/streaming/', '[av1 opus]' ),
		array( 'https://sm-portugal.com/streamingh264/', '[h264 opus]' ),
		$video_src
	);
	$download_url = smpt_get_download_page_url( $episode_number );

	$source_labels = array(
		'sic'     => 'SIC',
		'tvi'     => 'TVI',
		'tvi_old' => 'TVI',
		'panda'   => 'Panda',
	);

	$nostalgia_buttons = '';
	if ( ! empty( $nostalgia_emissions ) ) {
		foreach ( $nostalgia_emissions as $emission ) {
			$source_key = $emission['source'];
			$label      = isset( $source_labels[ $source_key ] ) ? $source_labels[ $source_key ] : $source_key;
			$logo_url   = '';

			// Build logo URL from known sources
			$known_logos = array(
				'tvi'     => 'https://sm-portugal.com/wp-content/uploads/2025/01/tvi.webp',
				'tvi_old' => 'https://sm-portugal.com/wp-content/uploads/2025/01/tvi_antigo.webp',
				'sic'     => 'https://sm-portugal.com/wp-content/uploads/2025/01/sic.webp',
				'panda'   => 'https://sm-portugal.com/wp-content/uploads/2025/03/panda.webp',
			);
			if ( isset( $known_logos[ $source_key ] ) ) {
				$logo_url = $known_logos[ $source_key ];
			}

			$nostalgia_buttons .= '
			<button class="smpt-play smpt-play--nostalgia" data-ep="' . $episode_padded . '" data-nostalgia-url="' . esc_attr( $emission['url'] ) . '" data-nostalgia-type="' . esc_attr( $emission['type'] ) . '">
				<img class="smpt-channel-logo" src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $label ) . '"> ' . esc_html( $label ) . '
			</button>';
		}
	}

	return '
	<div class="episodio-media">
		<div class="episodio-opcoes">
			<div class="episodio-coluna1">
				<img decoding="async" src="https://sm-portugal.com/anexos/imagens/episodios/' . $episode_number . '.jpg" alt="Imagem do epis&oacute;dio ' . $episode_padded . '">
			</div>
			<div class="episodio-coluna2">
				<button class="smpt-toggle smpt-toggle--ver-online" data-target="ver-online" data-ep="' . $episode_padded . '"' . ( empty( $nostalgia_emissions ) ? ' data-video-src="' . esc_attr( $video_src ) . '" data-poster="' . esc_attr( $poster_src ) . '"' : '' ) . '>Ver online' . ( ! empty( $nostalgia_emissions ) ? ' &#x25BE;' : '' ) . '</button>
				<button class="smpt-toggle smpt-toggle--download" data-target="download" data-ep="' . $episode_padded . '">Download &#x25BE;</button>
			</div>
		</div>' . ( ! empty( $nostalgia_emissions ) ? '

		<div class="smpt-subsection smpt-subsection--ver-online" data-ep="' . $episode_padded . '" hidden>
			<button class="smpt-play smpt-play--stream" data-ep="' . $episode_padded . '" data-video-src="' . esc_attr( $video_src ) . '" data-poster="' . esc_attr( $poster_src ) . '">Ver remasterizado</button>' . $nostalgia_buttons . '
		</div>' : '' ) . '

		<div class="smpt-subsection smpt-subsection--download" data-ep="' . $episode_padded . '" hidden>
			<a class="smpt-dl smpt-dl--hd" href="' . esc_url( $download_url ) . '">Download em HD</a>
			<span class="smpt-dl-help">Ficheiro completo via p&aacute;gina de torrent</span>

			<a class="smpt-dl smpt-dl--av1" href="' . esc_url( $video_src ) . '" download="Sailor Moon - ' . $episode_padded . ' (Dual Audio - Legendado 640X480)[smpt][av1 opus].mp4" onclick="trackDownload(\'download_ep' . $episode_padded . '_av1\');">Download AV1</a>
			<span class="smpt-dl-help">~50MB, melhor qualidade, navegadores modernos</span>

			<a class="smpt-dl smpt-dl--h264" href="' . esc_url( $h264_src ) . '" download="Sailor Moon - ' . $episode_padded . ' (Dual Audio - Legendado 640X480)[smpt][h264 opus].mp4" onclick="trackDownload(\'download_ep' . $episode_padded . '_h264\');">Download H264</a>
			<span class="smpt-dl-help">~50MB, compatibilidade universal</span>
		</div>
	</div>';
}

function gerar_episodios($inicio, $fim) {
    ob_start();
    $streaming_allowed = smpt_streaming_access_allowed();

    // Load nostalgia data once
    $nostalgia_json_path = get_stylesheet_directory() . '/php/nostalgia_ep.json';
    $nostalgia_data = null;
    if ( file_exists( $nostalgia_json_path ) ) {
        $nostalgia_data = json_decode( file_get_contents( $nostalgia_json_path ), true );
    }

    echo '
	<div id="loading" style="display:flex; justify-content:center; align-items:center;">
    <img id="loading-image" src="https://sm-portugal.com/wp-content/uploads/2024/12/loading.gif" alt="A Carregar..." />
	</div>';

    // Build JS-side emissions lookup
    if ( $nostalgia_data && $streaming_allowed ) {
        $js_sources  = isset( $nostalgia_data['sources'] ) ? $nostalgia_data['sources'] : array();
        $js_emissions = array();
        for ( $ep = $inicio; $ep <= $fim; $ep++ ) {
            $padded    = str_pad( $ep, 3, '0', STR_PAD_LEFT );
            $emissions = smpt_get_nostalgia_emissions( $ep, $nostalgia_data );
            if ( ! empty( $emissions ) ) {
                $js_emissions[ $padded ] = $emissions;
            }
        }
        echo '<script>window.smptNostalgiaData = ' . wp_json_encode( array(
            'sources'   => $js_sources,
            'emissions' => $js_emissions,
        ) ) . ';</script>';
    }

    echo '<div id="prismalunar" style="display:none">';

    for ($i = $inicio; $i <= $fim; $i++) {
        $num = str_pad($i, 3, '0', STR_PAD_LEFT);

        $image_sources = [
            [1, 26, "https://sm-portugal.com/wp-content/uploads/2023/12/classica.jpg"],
            [27, 46, "https://sm-portugal.com/wp-content/uploads/2023/12/classica2.jpg"],
            [47, 70, "https://sm-portugal.com/wp-content/uploads/2023/12/r1.jpg"],
            [71, 89, "https://sm-portugal.com/wp-content/uploads/2024/06/r2.jpg"],
            [90, 127, "https://sm-portugal.com/wp-content/uploads/2023/12/s.jpg"],
            [128, 166, "https://sm-portugal.com/wp-content/uploads/2023/12/ss.jpg"],
            [167, 200, "https://sm-portugal.com/wp-content/uploads/2023/12/stars.jpg"]
        ];

        $selected_image_src = "";
        foreach ($image_sources as $range) {
            if ($i >= $range[0] && $i <= $range[1]) {
                $selected_image_src = $range[2];
                break;
            }
        }

        $video_src = "https://sm-portugal.com/streaming/Sailor Moon - " . $num . " (Dual Audio - Legendado 640x480) [av1 opus][smpt].mp4";

        // Get nostalgia emissions for this episode
        $nostalgia_emissions = array();
        if ( $nostalgia_data ) {
            $nostalgia_emissions = smpt_get_nostalgia_emissions( $i, $nostalgia_data );
        }

        echo '
		<div id="episodio_' . $num . '" class="contentor_episodio smpt-table smpt-table--episode">
           <div class="cabecalho_video">
              <h2>Episódio ' . $i . '</h2>
           </div>
           <div class="nome_pt">' . do_shortcode('[epi num=' . $i . ' campo=nome_pt]') . '</div>
           <div class="episodio-card-body">
           <div class="detalhes-episodio">
              <div class="rotulo">Título original:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_traduzido]') . '</div>
              <div class="rotulo">Título japonês:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_jp]') . '</div>
              <div class="rotulo">Título romanji:</div>
              <div class="valor">' . do_shortcode('[epi num=' . $i . ' campo=nome_romanji]') . '</div>
           </div>
           ' . ( $streaming_allowed ? smpt_render_episode_media_allowed( $i, $num, $video_src, $nostalgia_emissions, $selected_image_src ) : '' ) . '
           <div class="resumo-episodio">';
        if ( ! $streaming_allowed ) {
            echo '<img decoding="async" src="https://sm-portugal.com/anexos/imagens/episodios/' . $i . '.jpg" alt="Imagem do episódio ' . $num . '" style="float:left; margin:0 10px 5px 0;">';
        }
        echo do_shortcode('[epi num=' . $i . ' campo=resumo]') . '</div>
           </div>
           <div class="smpt-ep-interactions" data-ep="' . $i . '"></div>
        </div>
        <div class="topo">
           <a href="#escolher-episodio" class="topo-link">↑ topo</a>
        </div>';
    }

    echo '</div>';

    // Shared player parking container
    echo '
    <div id="smpt-player-parking" style="display:none">
      <div id="smpt-shared-player" class="smpt-shared-player" hidden>
        <button type="button" class="smpt-player-close" aria-label="Fechar">&times;</button>
        <video class="smpt-video" controls preload="metadata">
          <source src="" type="video/mp4">
        </video>
      </div>
      <div id="smpt-shared-tv" class="smpt-shared-tv" hidden>
        <button type="button" class="smpt-player-close" aria-label="Fechar">&times;</button>
        <div class="smpt-tv-frame">
          <div class="tv">
            <iframe src="" frameborder="0" allowfullscreen allow="autoplay"></iframe>
          </div>
        </div>
      </div>
    </div>';

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
