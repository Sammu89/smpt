<?php
/**
 * Front-end member dashboard.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_dashboard_user = wp_get_current_user();

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="smpt-member-shell smpt-member-shell--dashboard">
			<div class="smpt-member-card">
				<header class="smpt-member-card__header">
					<p class="smpt-member-kicker"><?php esc_html_e( 'Area Moonies', 'generatepress' ); ?></p>
					<h1><?php the_title(); ?></h1>
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
								<strong><?php esc_html_e( 'Papel', 'generatepress' ); ?></strong>
								<span><?php esc_html_e( 'Moonies', 'generatepress' ); ?></span>
							</li>
						</ul>
					</section>

					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Atalhos', 'generatepress' ); ?></h2>
						<div class="smpt-member-actions">
							<a class="smpt-member-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar ao site', 'generatepress' ); ?></a>
							<a class="smpt-member-button smpt-member-button--ghost" href="<?php echo esc_url( smpt_member_get_url( 'logout' ) ); ?>"><?php esc_html_e( 'Desconectar', 'generatepress' ); ?></a>
						</div>
						<p class="smpt-member-note"><?php esc_html_e( 'As contas Moonies usam apenas este painel frontal e nao acedem ao backend do WordPress.', 'generatepress' ); ?></p>
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
