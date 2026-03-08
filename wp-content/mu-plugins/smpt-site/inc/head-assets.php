<?php
/**
 * Head assets: fonts, analytics, and global tracking helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue third-party assets loaded in <head>.
 */
function smpt_enqueue_external_head_assets() {
	wp_enqueue_style(
		'smpt-font-atma',
		'https://fonts.googleapis.com/css2?family=Atma:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'smpt-font-yanone',
		'https://fonts.googleapis.com/css?family=Yanone+Kaffeesatz',
		array(),
		null
	);

	wp_enqueue_script(
		'smpt-fontawesome-kit',
		'https://kit.fontawesome.com/ddf2ba72f8.js',
		array(),
		null,
		false
	);
	wp_script_add_data( 'smpt-fontawesome-kit', 'crossorigin', 'anonymous' );

	wp_enqueue_script(
		'smpt-gtag',
		'https://www.googletagmanager.com/gtag/js?id=G-GG2KC4SYW9',
		array(),
		null,
		false
	);

	$inline_tracking_js = <<<'JS'
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-GG2KC4SYW9');

function mangaview(fileName) {
	gtag('event', 'Visualizacao', {'event_category': 'Manga', 'event_label': fileName});
	console.log('Gtag VIZUALIZACAO do MANGA disparado com valor ' + fileName);
}
function manga(fileName) {
	gtag('event', 'Download', {'event_category': 'Manga', 'event_label': fileName});
	console.log('Gtag DOWNLOAD do MANGA disparado com valor ' + fileName);
}
function trackDownload(fileName) {
	gtag('event', 'Download', {'event_category': 'Anime', 'event_label': fileName});
	console.log('A lancar o download de ' + fileName);
}
function trackStream(fileName) {
	gtag('event', 'Stream', {'event_category': 'Anime', 'event_label': fileName});
	console.log('A lancar o stream do anime ' + fileName);
}
function trackMusica(fileName) {
	gtag('event', 'Stream', {'event_category': 'Musica', 'event_label': fileName});
	console.log('A lancar o stream da musica ' + fileName);
}
JS;

	wp_add_inline_script( 'smpt-gtag', $inline_tracking_js );
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_external_head_assets', 20 );
