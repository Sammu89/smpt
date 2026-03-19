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
						<h2>🌙 <?php esc_html_e( 'Seu Engajamento', 'generatepress' ); ?></h2>
						<?php
						$smpt_points_data = function_exists( 'smpt_ep_get_user_points_data' )
							? smpt_ep_get_user_points_data( $smpt_dashboard_user->ID )
							: array(
								'total_points' => 0, 'current_tier' => 0, 'tier_name' => 'Membro',
								'next_tier_name' => 'Temporada Clássica', 'progress_percent' => 0,
								'progress_needed' => 40, 'daily_limit' => 3,
							);
						$smpt_is_max_tier = intval( $smpt_points_data['current_tier'] ) >= 4;
						?>
						<div class="smpt-points-card">
							<div class="smpt-points-header">
								<p><strong><?php esc_html_e( 'Pontos:', 'generatepress' ); ?></strong> <span class="smpt-points-total"><?php echo intval( $smpt_points_data['total_points'] ); ?></span></p>
								<p><strong><?php esc_html_e( 'Nível:', 'generatepress' ); ?></strong> <span class="smpt-tier-badge"><?php echo esc_html( $smpt_points_data['tier_name'] ); ?></span> (<?php echo intval( $smpt_points_data['daily_limit'] ); ?> episódios/dia)</p>
							</div>
							<div class="smpt-progress-bar" style="background-color: #f0f0f0; height: 12px; border-radius: 6px; overflow: hidden; margin: 12px 0;">
								<div class="smpt-progress-fill" style="background-color: #8b5cf6; width: <?php echo intval( $smpt_points_data['progress_percent'] ); ?>%; height: 100%; transition: width 0.3s ease;"></div>
							</div>
							<p class="smpt-progress-text" style="font-size: 14px; color: #666;">
								<?php
								if ( $smpt_is_max_tier ) {
									esc_html_e( 'Máximo nível alcançado!', 'generatepress' );
								} else {
									printf(
										esc_html__( '%d pontos até %s', 'generatepress' ),
										intval( $smpt_points_data['progress_needed'] ),
										esc_html( $smpt_points_data['next_tier_name'] )
									);
								}
								?>
							</p>
						</div>
					</section>

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
						<h2><?php esc_html_e( 'Como Ganhar Pontos', 'generatepress' ); ?></h2>
						<table style="width: 100%; font-size: 14px; border-collapse: collapse;">
							<thead>
								<tr style="border-bottom: 1px solid #eee;">
									<th style="text-align: left; padding: 8px; font-weight: bold;">Nível</th>
									<th style="text-align: center; padding: 8px; font-weight: bold;">Pontos</th>
									<th style="text-align: center; padding: 8px; font-weight: bold;">Episódios/Dia</th>
								</tr>
							</thead>
							<tbody>
								<tr style="border-bottom: 1px solid #eee;">
									<td style="padding: 8px;">Membro</td>
									<td style="text-align: center; padding: 8px;">0-39</td>
									<td style="text-align: center; padding: 8px;">3</td>
								</tr>
								<tr style="border-bottom: 1px solid #eee;">
									<td style="padding: 8px;">Temporada Clássica</td>
									<td style="text-align: center; padding: 8px;">40-79</td>
									<td style="text-align: center; padding: 8px;">5</td>
								</tr>
								<tr style="border-bottom: 1px solid #eee;">
									<td style="padding: 8px;">Temporada R</td>
									<td style="text-align: center; padding: 8px;">80-119</td>
									<td style="text-align: center; padding: 8px;">7</td>
								</tr>
								<tr style="border-bottom: 1px solid #eee;">
									<td style="padding: 8px;">Temporada S</td>
									<td style="text-align: center; padding: 8px;">120-159</td>
									<td style="text-align: center; padding: 8px;">10</td>
								</tr>
								<tr>
									<td style="padding: 8px;">SuperS</td>
									<td style="text-align: center; padding: 8px;">160+</td>
									<td style="text-align: center; padding: 8px;">15</td>
								</tr>
							</tbody>
						</table>
						<div style="margin-top: 12px; padding: 12px; background-color: #f5f5f5; border-radius: 4px; font-size: 13px;">
							<p><strong>Como ganhar pontos:</strong></p>
							<ul style="margin: 8px 0; padding-left: 20px;">
								<li>💬 Comentar (episódio ou download): +7 pts</li>
								<li>⭐ Classificar (episódio): +3 pts</li>
								<li>❤️ Gostar / 👎 Desgostar (episódio): +1 pt</li>
							</ul>
							<p style="color: #999; margin: 8px 0;">⚠️ Limite diário: máximo 50 pontos por dia (24 horas)</p>
						</div>
					</section>

					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Atalhos', 'generatepress' ); ?></h2>
						<div class="smpt-member-actions">
							<a class="smpt-member-button" href="<?php echo esc_url( $smpt_dashboard_context['home_url'] ); ?>"><?php esc_html_e( 'Voltar ao site', 'generatepress' ); ?></a>
							<a class="smpt-member-button smpt-member-button--ghost" href="<?php echo esc_url( $smpt_dashboard_context['logout_url'] ); ?>"><?php esc_html_e( 'Desconectar', 'generatepress' ); ?></a>
						</div>
						<p class="smpt-member-note"><?php esc_html_e( 'Este painel frontal e o destino padrao da autenticacao. O fluxo normal de entrada nao expõe o backend do WordPress.', 'generatepress' ); ?></p>
					</section>

					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Episodios vistos', 'generatepress' ); ?></h2>
						<?php
						$smpt_watched_eps = function_exists( 'smpt_ep_get_watched_episodes' )
							? smpt_ep_get_watched_episodes( $smpt_dashboard_user->ID )
							: array();
						if ( ! empty( $smpt_watched_eps ) ) :
						?>
							<ul class="smpt-ep-watched-list">
								<?php foreach ( $smpt_watched_eps as $smpt_ep_num ) :
									$smpt_ep_padded = str_pad( $smpt_ep_num, 3, '0', STR_PAD_LEFT );
									$smpt_ep_url    = function_exists( 'smpt_ep_get_episode_page_url' )
										? smpt_ep_get_episode_page_url( $smpt_ep_num )
										: '#';
								?>
									<li>
										<a href="<?php echo esc_url( $smpt_ep_url ); ?>">
											<?php echo esc_html( sprintf( __( 'Episodio %d', 'generatepress' ), $smpt_ep_num ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="smpt-ep-empty"><?php esc_html_e( 'Ainda nao marcaste nenhum episodio como visto.', 'generatepress' ); ?></p>
						<?php endif; ?>
					</section>
					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Quero ver', 'generatepress' ); ?></h2>
						<?php
						$smpt_want_eps = function_exists( 'smpt_ep_get_want_episodes' )
							? smpt_ep_get_want_episodes( $smpt_dashboard_user->ID )
							: array();
						if ( ! empty( $smpt_want_eps ) ) :
						?>
							<ul class="smpt-ep-watched-list">
								<?php foreach ( $smpt_want_eps as $smpt_ep_num ) :
									$smpt_ep_url = function_exists( 'smpt_ep_get_episode_page_url' )
										? smpt_ep_get_episode_page_url( $smpt_ep_num )
										: '#';
								?>
									<li>
										<a href="<?php echo esc_url( $smpt_ep_url ); ?>">
											<?php echo esc_html( sprintf( __( 'Episodio %d', 'generatepress' ), $smpt_ep_num ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="smpt-ep-empty"><?php esc_html_e( 'Ainda nao adicionaste nenhum episodio a tua lista.', 'generatepress' ); ?></p>
						<?php endif; ?>
					</section>
					<section class="smpt-member-panel is-active">
						<h2><?php esc_html_e( 'Favoritos', 'generatepress' ); ?></h2>
						<?php
						$smpt_favorite_eps = function_exists( 'smpt_ep_get_favorite_episodes' )
							? smpt_ep_get_favorite_episodes( $smpt_dashboard_user->ID )
							: array();
						if ( ! empty( $smpt_favorite_eps ) ) :
						?>
							<ul class="smpt-ep-watched-list">
								<?php foreach ( $smpt_favorite_eps as $smpt_ep_num ) :
									$smpt_ep_url = function_exists( 'smpt_ep_get_episode_page_url' )
										? smpt_ep_get_episode_page_url( $smpt_ep_num )
										: '#';
								?>
									<li>
										<a href="<?php echo esc_url( $smpt_ep_url ); ?>">
											<?php echo esc_html( sprintf( __( 'Episodio %d', 'generatepress' ), $smpt_ep_num ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="smpt-ep-empty"><?php esc_html_e( 'Ainda nao marcaste nenhum episodio como favorito.', 'generatepress' ); ?></p>
						<?php endif; ?>
					</section>
					<section class="smpt-member-panel is-active" id="smpt-activity-panel" style="display:none;">
						<div style="display:flex;justify-content:space-between;align-items:center;">
							<h2><?php esc_html_e( 'Atividade recente', 'generatepress' ); ?></h2>
							<button type="button" id="smpt-activity-dismiss" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999;padding:4px 8px;" title="<?php esc_attr_e( 'Fechar', 'generatepress' ); ?>">&times;</button>
						</div>
						<ul id="smpt-activity-list" class="smpt-activity-list"></ul>
					</section>
					<script>
					(function(){
						if(typeof window.smptGetActivityLog!=='function')return;
						if(window.smptIsActivityDismissed())return;
						var log=window.smptGetActivityLog();
						if(!log.length)return;

						var icons={
							stream:'\uD83C\uDFAC',nostalgia:'\uD83D\uDCFA',download:'\u2B07\uFE0F',
							like:'\u2764\uFE0F',dislike:'\uD83D\uDC4E',rate:'\u2B50',
							comment:'\uD83D\uDCAC',watched:'\u2705',want:'\uD83D\uDCCC',
							favorite:'\uD83C\uDF1F',limit_reached:'\u26A0\uFE0F',cap_reached:'\u26A0\uFE0F'
						};

						function timeAgo(ts){
							var diff=Math.floor((Date.now()-ts)/1000);
							if(diff<60)return 'agora mesmo';
							if(diff<3600)return Math.floor(diff/60)+'m';
							if(diff<86400)return Math.floor(diff/3600)+'h';
							return Math.floor(diff/86400)+'d';
						}

						var panel=document.getElementById('smpt-activity-panel');
						var list=document.getElementById('smpt-activity-list');
						var html='';
						for(var i=0;i<log.length;i++){
							var e=log[i];
							var icon=icons[e.type]||'\uD83D\uDD35';
							html+='<li class="smpt-activity-item">'
								+'<span class="smpt-activity-icon">'+icon+'</span> '
								+'<span class="smpt-activity-msg">'+e.msg+'</span> '
								+'<span class="smpt-activity-time">'+timeAgo(e.ts)+'</span>'
								+'</li>';
						}
						list.innerHTML=html;
						panel.style.display='';

						document.getElementById('smpt-activity-dismiss').addEventListener('click',function(){
							window.smptDismissActivity();
							panel.style.display='none';
						});
					})();
					</script>
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
