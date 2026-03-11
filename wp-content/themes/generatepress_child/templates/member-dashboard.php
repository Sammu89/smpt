<?php
/**
 * Front-end member dashboard.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_dashboard_context = smpt_member_get_dashboard_context();
$smpt_dashboard_user    = $smpt_dashboard_context['user'];
$smpt_page_title        = get_the_title() ? get_the_title() : __( 'Painel', 'generatepress' );

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="smpt-member-shell smpt-member-shell--dashboard">
			<div class="smpt-member-card">
				<header class="smpt-member-card__header">
					<p class="smpt-member-kicker"><?php esc_html_e( 'Area Moonies', 'generatepress' ); ?></p>
					<h1><?php echo esc_html( $smpt_page_title ); ?></h1>
					<p><?php echo esc_html( sprintf( __( 'Bem-vinda de volta, %s.', 'generatepress' ), $smpt_dashboard_user->display_name ) ); ?></p>
				</header>

				<div class="smpt-member-dashboard">
					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'A tua conta', 'generatepress' ); ?></h2>
						<ul class="smpt-member-facts">
							<li>
								<strong><?php esc_html_e( 'Nome publico', 'generatepress' ); ?></strong>
								<span><?php echo esc_html( $smpt_dashboard_user->display_name ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Nome de utilizador', 'generatepress' ); ?></strong>
								<span><?php echo esc_html( $smpt_dashboard_user->user_login ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Email', 'generatepress' ); ?></strong>
								<span><?php echo esc_html( $smpt_dashboard_user->user_email ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Perfis', 'generatepress' ); ?></strong>
								<span><?php echo esc_html( $smpt_dashboard_context['roles'] ? implode( ', ', $smpt_dashboard_context['roles'] ) : __( 'Membro', 'generatepress' ) ); ?></span>
							</li>
						</ul>
					</section>

					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Atalhos', 'generatepress' ); ?></h2>
						<div class="smpt-member-actions">
							<a class="smpt-member-button" href="<?php echo esc_url( $smpt_dashboard_context['home_url'] ); ?>"><?php esc_html_e( 'Voltar ao site', 'generatepress' ); ?></a>
							<a class="smpt-member-button smpt-member-button--ghost" href="<?php echo esc_url( $smpt_dashboard_context['logout_url'] ); ?>"><?php esc_html_e( 'Desconectar', 'generatepress' ); ?></a>
						</div>
						<p class="smpt-member-note"><?php esc_html_e( 'Este painel frontal e o destino padrao da autenticacao. O fluxo normal de entrada nao expõe o backend do WordPress.', 'generatepress' ); ?></p>
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
