<?php
/**
 * Front-end auth and password recovery page.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_auth_context = smpt_member_get_auth_context();
$smpt_view         = $smpt_auth_context['view'];
$smpt_page_title   = get_the_title();

if ( ! $smpt_page_title ) {
	$smpt_page_title = 'register' === $smpt_view ? __( 'Registar', 'generatepress' ) : ( ( 'lost_password' === $smpt_view || 'reset_password' === $smpt_view ) ? __( 'Recuperar password', 'generatepress' ) : __( 'Entrar', 'generatepress' ) );
}

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="smpt-member-shell smpt-member-shell--auth">
			<div class="smpt-member-card">
				<header class="smpt-member-card__header">
					<p class="smpt-member-kicker"><?php esc_html_e( 'Area Moonies', 'generatepress' ); ?></p>
					<h1><?php echo esc_html( $smpt_page_title ); ?></h1>
					<?php if ( 'lost_password' === $smpt_view || 'reset_password' === $smpt_view ) : ?>
						<p><?php esc_html_e( 'Recupera o acesso a tua conta sem sair do site.', 'generatepress' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Usa a tua conta Moonies para entrar ou cria uma nova conta diretamente no site.', 'generatepress' ); ?></p>
					<?php endif; ?>
				</header>

				<div class="smpt-member-switch">
					<a class="<?php echo 'login' === $smpt_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( smpt_member_get_url( 'login' ) ); ?>">
						<?php esc_html_e( 'Entrar', 'generatepress' ); ?>
					</a>
					<a class="<?php echo 'register' === $smpt_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( smpt_member_get_url( 'register' ) ); ?>">
						<?php esc_html_e( 'Registar', 'generatepress' ); ?>
					</a>
					<a class="<?php echo ( 'lost_password' === $smpt_view || 'reset_password' === $smpt_view ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( smpt_member_get_lost_password_url() ); ?>">
						<?php esc_html_e( 'Recuperar password', 'generatepress' ); ?>
					</a>
				</div>

				<?php if ( 'lost_password' === $smpt_view || 'reset_password' === $smpt_view ) : ?>
					<div class="smpt-member-grid smpt-member-grid--single">
						<section class="smpt-member-panel is-active">
							<?php if ( 'reset_password' === $smpt_view ) : ?>
								<h2><?php esc_html_e( 'Definir nova palavra-passe', 'generatepress' ); ?></h2>
								<p><?php esc_html_e( 'Escolhe uma nova palavra-passe para voltar a entrar na tua conta.', 'generatepress' ); ?></p>
							<?php else : ?>
								<h2><?php esc_html_e( 'Recuperar password', 'generatepress' ); ?></h2>
								<p><?php esc_html_e( 'Introduz o teu email ou nome de utilizador e enviamos um link para redefinir a password.', 'generatepress' ); ?></p>
							<?php endif; ?>

							<?php if ( $smpt_auth_context['password_notice'] ) : ?>
								<div class="smpt-member-notice smpt-member-notice--success"><?php echo wp_kses_post( $smpt_auth_context['password_notice'] ); ?></div>
							<?php endif; ?>

							<?php if ( $smpt_auth_context['password_error'] ) : ?>
								<div class="smpt-member-notice smpt-member-notice--error"><?php echo wp_kses_post( $smpt_auth_context['password_error'] ); ?></div>
							<?php endif; ?>

							<?php if ( 'reset_password' === $smpt_view && $smpt_auth_context['reset_user'] instanceof WP_User ) : ?>
								<form method="post" class="smpt-member-form">
									<label for="smpt-reset-pass1"><?php esc_html_e( 'Nova palavra-passe', 'generatepress' ); ?></label>
									<input id="smpt-reset-pass1" type="password" name="pass1" autocomplete="new-password" required>

									<label for="smpt-reset-pass2"><?php esc_html_e( 'Confirmar nova palavra-passe', 'generatepress' ); ?></label>
									<input id="smpt-reset-pass2" type="password" name="pass2" autocomplete="new-password" required>

									<?php wp_nonce_field( 'smpt_member_reset_password', 'smpt_member_reset_password_nonce' ); ?>
									<input type="hidden" name="smpt_member_action" value="reset_password">
									<input type="hidden" name="login" value="<?php echo esc_attr( $smpt_auth_context['reset_user']->user_login ); ?>">
									<input type="hidden" name="key" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) ) ); ?>">

									<button type="submit"><?php esc_html_e( 'Guardar nova palavra-passe', 'generatepress' ); ?></button>
								</form>
							<?php else : ?>
								<form method="post" class="smpt-member-form">
									<label for="smpt-lost-password"><?php esc_html_e( 'Email ou nome de utilizador', 'generatepress' ); ?></label>
									<input id="smpt-lost-password" type="text" name="user_login" value="<?php echo esc_attr( $smpt_auth_context['password_values']['identifier'] ); ?>" autocomplete="username" required>

									<?php wp_nonce_field( 'smpt_member_lost_password', 'smpt_member_lost_password_nonce' ); ?>
									<input type="hidden" name="smpt_member_action" value="lost_password">

									<button type="submit"><?php esc_html_e( 'Enviar link de recuperacao', 'generatepress' ); ?></button>
								</form>
							<?php endif; ?>
						</section>

						<section class="smpt-member-panel is-active">
							<h2><?php esc_html_e( 'Voltar a entrar', 'generatepress' ); ?></h2>
							<p><?php esc_html_e( 'Se te lembrares da password, podes voltar a pagina de entrada ou criar uma conta nova.', 'generatepress' ); ?></p>
							<div class="smpt-member-actions">
								<a class="smpt-member-button" href="<?php echo esc_url( smpt_member_get_url( 'login' ) ); ?>"><?php esc_html_e( 'Ir para Entrar', 'generatepress' ); ?></a>
								<a class="smpt-member-button smpt-member-button--ghost" href="<?php echo esc_url( smpt_member_get_url( 'register' ) ); ?>"><?php esc_html_e( 'Criar conta', 'generatepress' ); ?></a>
							</div>
						</section>
					</div>
				<?php else : ?>
					<div class="smpt-member-grid">
						<section class="smpt-member-panel <?php echo 'login' === $smpt_view ? 'is-active' : ''; ?>">
							<h2><?php esc_html_e( 'Entrar', 'generatepress' ); ?></h2>
							<p><?php esc_html_e( 'Usa o teu nome de utilizador ou email para aceder ao teu painel de membro.', 'generatepress' ); ?></p>

							<?php if ( $smpt_auth_context['login_notice'] ) : ?>
								<div class="smpt-member-notice smpt-member-notice--success"><?php echo wp_kses_post( $smpt_auth_context['login_notice'] ); ?></div>
							<?php endif; ?>

							<?php if ( $smpt_auth_context['login_error'] ) : ?>
								<div class="smpt-member-notice smpt-member-notice--error"><?php echo wp_kses_post( $smpt_auth_context['login_error'] ); ?></div>
							<?php endif; ?>

							<form method="post" class="smpt-member-form">
								<label for="smpt-log"><?php esc_html_e( 'Nome de utilizador ou email', 'generatepress' ); ?></label>
								<input id="smpt-log" type="text" name="log" value="<?php echo esc_attr( $smpt_auth_context['login_values']['identifier'] ); ?>" autocomplete="username" required>

								<label for="smpt-pwd"><?php esc_html_e( 'Palavra-passe', 'generatepress' ); ?></label>
								<input id="smpt-pwd" type="password" name="pwd" autocomplete="current-password" required>

								<label class="smpt-member-checkbox">
									<input type="checkbox" name="rememberme" value="forever" <?php checked( $smpt_auth_context['login_values']['remember'] ); ?>>
									<span><?php esc_html_e( 'Manter sessao iniciada', 'generatepress' ); ?></span>
								</label>

								<?php wp_nonce_field( 'smpt_member_login', 'smpt_member_login_nonce' ); ?>
								<input type="hidden" name="smpt_member_action" value="login">
								<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $smpt_auth_context['redirect_to'] ); ?>">

								<button type="submit"><?php esc_html_e( 'Entrar na Area Moonies', 'generatepress' ); ?></button>
							</form>

							<p class="smpt-member-inline-link">
								<a href="<?php echo esc_url( smpt_member_get_lost_password_url() ); ?>"><?php esc_html_e( 'Esqueci-me da password', 'generatepress' ); ?></a>
							</p>
						</section>

						<section class="smpt-member-panel <?php echo 'register' === $smpt_view ? 'is-active' : ''; ?>">
							<h2><?php esc_html_e( 'Registar', 'generatepress' ); ?></h2>
							<p><?php esc_html_e( 'Os novos registos recebem o papel Moonies e usam apenas o painel frontal do site.', 'generatepress' ); ?></p>

							<?php if ( $smpt_auth_context['register_error'] ) : ?>
								<div class="smpt-member-notice smpt-member-notice--error"><?php echo wp_kses_post( $smpt_auth_context['register_error'] ); ?></div>
							<?php endif; ?>

							<form method="post" class="smpt-member-form">
								<label for="smpt-display-name"><?php esc_html_e( 'Nome publico', 'generatepress' ); ?></label>
								<input id="smpt-display-name" type="text" name="display_name" value="<?php echo esc_attr( $smpt_auth_context['register_values']['display_name'] ); ?>" autocomplete="nickname">

								<label for="smpt-user-login"><?php esc_html_e( 'Nome de utilizador', 'generatepress' ); ?></label>
								<input id="smpt-user-login" type="text" name="user_login" value="<?php echo esc_attr( $smpt_auth_context['register_values']['user_login'] ); ?>" autocomplete="username" required>

								<label for="smpt-user-email"><?php esc_html_e( 'Email', 'generatepress' ); ?></label>
								<input id="smpt-user-email" type="email" name="user_email" value="<?php echo esc_attr( $smpt_auth_context['register_values']['user_email'] ); ?>" autocomplete="email" required>

								<label for="smpt-user-password"><?php esc_html_e( 'Palavra-passe', 'generatepress' ); ?></label>
								<input id="smpt-user-password" type="password" name="user_password" autocomplete="new-password" required>

								<label for="smpt-user-password-confirm"><?php esc_html_e( 'Confirmar palavra-passe', 'generatepress' ); ?></label>
								<input id="smpt-user-password-confirm" type="password" name="user_password_confirm" autocomplete="new-password" required>

								<?php wp_nonce_field( 'smpt_member_register', 'smpt_member_register_nonce' ); ?>
								<input type="hidden" name="smpt_member_action" value="register">

								<button type="submit"><?php esc_html_e( 'Criar conta Moonies', 'generatepress' ); ?></button>
							</form>
						</section>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php do_action( 'generate_after_main_content' ); ?>
	</main>
</div>

<?php
do_action( 'generate_after_primary_content_area' );
generate_construct_sidebars();
get_footer();
