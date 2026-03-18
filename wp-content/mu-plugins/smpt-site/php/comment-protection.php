<?php
/**
 * Comment Protection: reCAPTCHA v3, Akismet integration, and Portuguese profanity filter.
 *
 * Settings stored in wp_options:
 *   smpt_recaptcha_site_key   — reCAPTCHA v3 site key
 *   smpt_recaptcha_secret_key — reCAPTCHA v3 secret key
 *   smpt_recaptcha_threshold  — minimum score (default 0.5)
 *   smpt_profanity_action     — 'censor' (default) or 'block'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   1. reCAPTCHA v3 (invisible)
   ========================================================================= */

/**
 * Check if reCAPTCHA is configured.
 */
function smpt_recaptcha_enabled() {
	return '' !== get_option( 'smpt_recaptcha_site_key', '' )
		&& '' !== get_option( 'smpt_recaptcha_secret_key', '' );
}

/**
 * Enqueue reCAPTCHA v3 script on pages that have comments.
 */
function smpt_recaptcha_enqueue() {
	if ( ! smpt_recaptcha_enabled() ) {
		return;
	}

	$site_key = get_option( 'smpt_recaptcha_site_key' );

	wp_enqueue_script(
		'google-recaptcha-v3',
		'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key ),
		array(),
		null,
		true
	);

	// Make site key available to JS.
	wp_localize_script( 'google-recaptcha-v3', 'smptRecaptcha', array(
		'siteKey' => $site_key,
	) );
}
add_action( 'wp_enqueue_scripts', 'smpt_recaptcha_enqueue', 25 );

/**
 * Verify a reCAPTCHA v3 token server-side.
 *
 * @param string $token   The token from the frontend.
 * @param string $action  Expected action name.
 * @return bool True if valid.
 */
function smpt_recaptcha_verify( $token, $action = 'comment' ) {
	if ( ! smpt_recaptcha_enabled() ) {
		return true; // Not configured — pass through.
	}

	if ( empty( $token ) ) {
		return false;
	}

	$secret    = get_option( 'smpt_recaptcha_secret_key' );
	$threshold = (float) get_option( 'smpt_recaptcha_threshold', 0.5 );

	$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
		'body' => array(
			'secret'   => $secret,
			'response' => $token,
			'remoteip' => smpt_ep_get_client_ip(),
		),
		'timeout' => 5,
	) );

	if ( is_wp_error( $response ) ) {
		return true; // Network error — don't block the user.
	}

	$result = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $result['success'] ) ) {
		return false;
	}

	// Check action matches.
	if ( isset( $result['action'] ) && $result['action'] !== $action ) {
		return false;
	}

	// Check score meets threshold.
	if ( isset( $result['score'] ) && (float) $result['score'] < $threshold ) {
		return false;
	}

	return true;
}

/**
 * Add reCAPTCHA hidden field to WordPress comment form.
 */
function smpt_recaptcha_wp_comment_field( $fields ) {
	if ( ! smpt_recaptcha_enabled() ) {
		return $fields;
	}

	$fields['smpt_recaptcha'] = '<input type="hidden" name="smpt_recaptcha_token" id="smpt-recaptcha-token" value="">';

	return $fields;
}
add_filter( 'comment_form_fields', 'smpt_recaptcha_wp_comment_field' );

/**
 * Validate reCAPTCHA on WordPress comment submission.
 */
function smpt_recaptcha_check_wp_comment( $commentdata ) {
	if ( ! smpt_recaptcha_enabled() ) {
		return $commentdata;
	}

	// Skip for logged-in users.
	if ( get_current_user_id() > 0 ) {
		return $commentdata;
	}

	$token = isset( $_POST['smpt_recaptcha_token'] ) ? sanitize_text_field( $_POST['smpt_recaptcha_token'] ) : '';

	if ( ! smpt_recaptcha_verify( $token, 'wp_comment' ) ) {
		wp_die(
			'<p>A verificação anti-spam falhou. Por favor tenta novamente.</p>',
			'Erro de verificação',
			array( 'response' => 403, 'back_link' => true )
		);
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'smpt_recaptcha_check_wp_comment', 1 );

/**
 * Inline JS to populate reCAPTCHA token on WP comment form submit.
 */
function smpt_recaptcha_wp_comment_js() {
	if ( ! smpt_recaptcha_enabled() || ! is_singular() ) {
		return;
	}
	$site_key = esc_js( get_option( 'smpt_recaptcha_site_key' ) );
	?>
	<script>
	(function() {
		var form = document.getElementById('commentform');
		if (!form) return;
		form.addEventListener('submit', function(e) {
			var tokenField = document.getElementById('smpt-recaptcha-token');
			if (!tokenField || tokenField.value) return;
			e.preventDefault();
			grecaptcha.ready(function() {
				grecaptcha.execute('<?php echo $site_key; ?>', {action: 'wp_comment'}).then(function(token) {
					tokenField.value = token;
					form.submit();
				});
			});
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'smpt_recaptcha_wp_comment_js', 99 );


/* =========================================================================
   2. Akismet integration for episode comments
   ========================================================================= */

/**
 * Check if Akismet is available and has an API key.
 */
function smpt_akismet_available() {
	return class_exists( 'Akismet' ) && Akismet::get_api_key();
}

/**
 * Check a comment against Akismet.
 *
 * @param string $author_name  Commenter name.
 * @param string $author_email Commenter email.
 * @param string $text         Comment body.
 * @param string $user_ip      IP address.
 * @return bool True if spam.
 */
function smpt_akismet_is_spam( $author_name, $author_email, $text, $user_ip = '' ) {
	if ( ! smpt_akismet_available() ) {
		return false;
	}

	if ( '' === $user_ip ) {
		$user_ip = smpt_ep_get_client_ip();
	}

	$params = array(
		'blog'                 => home_url(),
		'user_ip'              => $user_ip,
		'user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
		'referrer'             => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '',
		'comment_type'         => 'comment',
		'comment_author'       => $author_name,
		'comment_author_email' => $author_email,
		'comment_content'      => $text,
		'blog_lang'            => 'pt',
		'blog_charset'         => 'UTF-8',
	);

	$query_string = Akismet::build_query( $params );
	$response     = Akismet::http_post( $query_string, 'comment-check' );

	// $response[1] is the body: 'true' = spam, 'false' = ham.
	return isset( $response[1] ) && 'true' === trim( $response[1] );
}


/* =========================================================================
   3. Portuguese profanity — seeded into WP Disallowed Comment Keys
   ========================================================================= */

/**
 * Plain Portuguese profanity words to seed into WP's disallowed_keys.
 * WP and wpDiscuz enforce this list natively on posts/pages.
 * Episode comments read from the same list via smpt_profanity_filter().
 */
function smpt_profanity_seed_words() {
	return array(
		'caralho', 'foder', 'foda', 'fodasse', 'fodido', 'fodida', 'fodilhão',
		'filho da puta', 'filha da puta', 'fdp',
		'puta', 'putaria', 'putão',
		'merda', 'merdas',
		'cocó', 'cagão', 'cagar',
		'cabrão', 'cabrões',
		'paneleiro', 'paneleira', 'paneca',
		'chupa', 'chupa-me', 'mama-me', 'lamba-me',
		'brocha', 'brochiste',
		'pissa', 'pija', 'pila', 'piça', 'piroca',
		'cona', 'coninha',
		'rabão', 'rabeta',
		'buceta',
		'cornudo', 'cornuda', 'corno',
		'escroto',
		'idiota', 'estúpido', 'estúpida', 'imbecil',
		'retardado', 'retardada',
		'mongoloide',
		'atrasada mental', 'atrasado mental',
		'abertola',
		'vai pro caralho', 'vai te foder', 'vai se foder', 'vai te lixar',
		'enrabar',
	);
}

/**
 * Seed disallowed_keys with Portuguese profanity words (once per version).
 * Non-destructive: only appends words not already present.
 */
function smpt_profanity_seed() {
	$version     = '1.0';
	$option_key  = 'smpt_profanity_seeded_v';

	if ( get_option( $option_key ) === $version ) {
		return;
	}

	$existing = get_option( 'disallowed_keys', '' );
	$existing_words = array_filter( array_map( 'trim', explode( "\n", $existing ) ) );

	$to_add = array();
	foreach ( smpt_profanity_seed_words() as $word ) {
		if ( ! in_array( $word, $existing_words, true ) ) {
			$to_add[] = $word;
		}
	}

	if ( ! empty( $to_add ) ) {
		$merged = array_merge( $existing_words, $to_add );
		update_option( 'disallowed_keys', implode( "\n", $merged ) );
	}

	update_option( $option_key, $version );
}
add_action( 'init', 'smpt_profanity_seed' );

/**
 * Build regex patterns from WP's disallowed_keys list.
 * Used for episode comments (posts/pages handled natively by WP/wpDiscuz).
 *
 * @return array Array of regex patterns.
 */
function smpt_profanity_patterns() {
	$keys = get_option( 'disallowed_keys', '' );
	$words = array_filter( array_map( 'trim', explode( "\n", $keys ) ) );

	$patterns = array();
	foreach ( $words as $word ) {
		$patterns[] = '/' . preg_quote( $word, '/' ) . '/iu';
	}
	return $patterns;
}

/**
 * Censor text using disallowed_keys word list (keep first+last letter).
 *
 * @param string $text Text to censor.
 * @return string Censored text.
 */
function smpt_censor_profanity( $text ) {
	foreach ( smpt_profanity_patterns() as $pattern ) {
		$text = preg_replace_callback( $pattern, function( $match ) {
			$len = mb_strlen( $match[0] );
			if ( $len <= 2 ) {
				return str_repeat( '*', $len );
			}
			return mb_substr( $match[0], 0, 1 ) . str_repeat( '*', $len - 2 ) . mb_substr( $match[0], -1 );
		}, $text );
	}
	return $text;
}

/**
 * Check text against disallowed_keys and censor or block.
 * Used only for episode comments — WP/wpDiscuz handle posts/pages natively.
 *
 * @param string $text Text to filter.
 * @return string|false Censored text, or false if WP would have blocked it.
 */
function smpt_profanity_filter( $text ) {
	// Mirror WP's own disallowed check: if wp_check_comment_disallowed_list
	// would block it, return false so the REST endpoint returns an error.
	if ( function_exists( 'wp_check_comment_disallowed_list' ) ) {
		// wp_check_comment_disallowed_list returns true if disallowed.
		if ( wp_check_comment_disallowed_list( '', '', '', $text, '', '' ) ) {
			return false;
		}
	}

	// Censor rather than block (episode comments show inline, not held for moderation).
	return smpt_censor_profanity( $text );
}


/* =========================================================================
   4. Admin settings page
   ========================================================================= */

function smpt_comment_protection_settings_page() {
	add_options_page(
		'SMPT Comment Protection',
		'SMPT Comment Protection',
		'manage_options',
		'smpt-comment-protection',
		'smpt_comment_protection_settings_html'
	);
}
add_action( 'admin_menu', 'smpt_comment_protection_settings_page' );

function smpt_comment_protection_register_settings() {
	register_setting( 'smpt_comment_protection', 'smpt_recaptcha_site_key' );
	register_setting( 'smpt_comment_protection', 'smpt_recaptcha_secret_key' );
	register_setting( 'smpt_comment_protection', 'smpt_recaptcha_threshold' );
}
add_action( 'admin_init', 'smpt_comment_protection_register_settings' );

function smpt_comment_protection_settings_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>SMPT Comment Protection</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'smpt_comment_protection' ); ?>

			<h2>reCAPTCHA v3</h2>
			<p>Get keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin</a>. Choose reCAPTCHA v3.</p>
			<table class="form-table">
				<tr>
					<th>Site Key</th>
					<td><input type="text" name="smpt_recaptcha_site_key" value="<?php echo esc_attr( get_option( 'smpt_recaptcha_site_key', '' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Secret Key</th>
					<td><input type="password" name="smpt_recaptcha_secret_key" value="<?php echo esc_attr( get_option( 'smpt_recaptcha_secret_key', '' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Score Threshold</th>
					<td>
						<input type="number" name="smpt_recaptcha_threshold" value="<?php echo esc_attr( get_option( 'smpt_recaptcha_threshold', '0.5' ) ); ?>" min="0" max="1" step="0.1" style="width:80px">
						<p class="description">0.0 = allow all, 1.0 = most strict. Default: 0.5</p>
					</td>
				</tr>
			</table>

			<h2>Akismet</h2>
			<p>
				<?php if ( smpt_akismet_available() ) : ?>
					<span style="color:green">&#x2713; Akismet is active and configured.</span>
				<?php else : ?>
					<span style="color:red">&#x2717; Akismet not detected.</span>
					Install and activate the <a href="<?php echo admin_url( 'plugin-install.php?s=akismet&tab=search&type=term' ); ?>">Akismet plugin</a> and set an API key.
				<?php endif; ?>
			</p>

			<h2>Profanity Filter (Portuguese)</h2>
			<p>
				Words are stored in <a href="<?php echo admin_url( 'options-discussion.php' ); ?>">Settings → Discussion → Disallowed Comment Keys</a>.
				WP, wpDiscuz and episode comments all read from that list.<br>
				Episode comments censor the word (first+last letter kept). Posts/pages are blocked by WP/wpDiscuz natively.
			</p>
			<p>
				<?php
				$count = count( array_filter( array_map( 'trim', explode( "\n", get_option( 'disallowed_keys', '' ) ) ) ) );
				echo '<strong>' . $count . '</strong> palavras na lista.';
				?>
				<a href="<?php echo admin_url( 'options-discussion.php#disallowed_keys' ); ?>">Editar lista &rarr;</a>
			</p>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}


/* =========================================================================
   5. Point WordPress comment form login link to the custom login page
   ========================================================================= */

/**
 * Replace the "You must be logged in to post a comment" link with the
 * custom front-end login page (with redirect back to the current post).
 */
function smpt_comment_must_login_text( $defaults ) {
	if ( ! function_exists( 'smpt_member_get_login_url' ) ) {
		return $defaults;
	}

	$redirect  = get_permalink();
	$login_url = smpt_member_get_login_url( $redirect );

	$defaults['must_log_in'] = '<p class="must-log-in">'
		. sprintf(
			/* translators: %s: login URL */
			__( 'Tens de <a href="%s">entrar</a> para publicar um comentário.' ),
			esc_url( $login_url )
		)
		. '</p>';

	return $defaults;
}
add_filter( 'comment_form_defaults', 'smpt_comment_must_login_text' );
