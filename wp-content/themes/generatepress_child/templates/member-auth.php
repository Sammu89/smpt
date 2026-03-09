<?php
/**
 * Front-end auth page.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_auth_context = smpt_member_get_auth_context();

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="smpt-member-shell smpt-member-shell--auth">
			<div class="smpt-member-card">
				<header class="smpt-member-card__header">
					<p class="smpt-member-kicker"><?php esc_html_e( 'Area Moonies', 'generatepress' ); ?></p>
					<h1><?php the_title(); ?></h1>
					<p><?php esc_html_e( 'Usa a tua conta Moonies para entrar ou cria uma nova conta diretamente no site.', 'generatepress' ); ?></p>
				</header>

				<div class="smpt-member-switch">
					<a class="<?php echo 'login' === $smpt_auth_context['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( smpt_member_get_url( 'login' ) ); ?>">
						<?php esc_html_e( 'Entrar', 'generatepress' ); ?>
					</a>
					<a class="<?php echo 'register' === $smpt_auth_context['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( smpt_member_get_url( 'register' ) ); ?>">
						<?php esc_html_e( 'Registar', 'generatepress' ); ?>
					</a>
				</div>

				<div class="smpt-member-grid">
					<section class="smpt-member-panel <?php echo 'login' === $smpt_auth_context['view'] ? 'is-active' : ''; ?>">
						<h2><?php esc_html_e( 'Entrar', 'generatepress' ); ?></h2>
						<p><?php esc_html_e( 'Usa o teu nome de utilizador ou email para aceder ao teu painel de membro.', 'generatepress' ); ?></p>

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
					</section>

					<section class="smpt-member-panel <?php echo 'register' === $smpt_auth_context['view'] ? 'is-active' : ''; ?>">
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
			</div>
		</div>

		<?php do_action( 'generate_after_main_content' ); ?>
	</main>
</div>

<?php
do_action( 'generate_after_primary_content_area' );
generate_construct_sidebars();
get_footer();
