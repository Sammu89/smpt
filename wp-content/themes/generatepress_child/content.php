<?php
/**
 * Template for displaying posts within the loop.
 *
 * On archive views, renders each post as a card with image, category badge,
 * title, date, excerpt, comment count, and read-more link.
 *
 * On singular views, falls back to the parent theme's content.php.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Singular posts/pages use the parent template unchanged.
if ( is_singular() ) {
	locate_template( 'content-single.php', true, false );
	return;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'smpt-archive-card' ); ?> <?php generate_do_microdata( 'article' ); ?>>
	<div class="inside-article">

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="smpt-archive-card__image">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'smpt-archive-card' ); ?>
				</a>
				<?php
				$smpt_categories = get_the_category();
				if ( ! empty( $smpt_categories ) ) :
					?>
					<div class="smpt-archive-card__badges">
						<?php foreach ( $smpt_categories as $smpt_i => $smpt_cat ) : ?>
							<a class="smpt-archive-card__badge smpt-archive-card__badge--<?php echo 0 === $smpt_i ? 'primary' : 'secondary'; ?>" href="<?php echo esc_url( get_category_link( $smpt_cat->term_id ) ); ?>">
								<?php echo esc_html( $smpt_cat->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="smpt-archive-card__body">
			<header class="entry-header">
				<h2 class="entry-title" <?php generate_do_microdata( 'heading' ); ?>>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
			</header>

			<div class="smpt-archive-card__meta">
				<span class="dashicons dashicons-clock"></span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
			</div>

			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div>

			<footer class="smpt-archive-card__footer">
				<?php
				$smpt_comment_count = get_comments_number();
				if ( $smpt_comment_count > 0 ) :
					?>
					<span class="smpt-archive-card__comments">
						<span class="dashicons dashicons-admin-comments"></span>
						<?php echo esc_html( $smpt_comment_count ); ?>
					</span>
				<?php endif; ?>

				<a class="smpt-archive-card__read-more" href="<?php the_permalink(); ?>">
					Ler notícia →
				</a>
			</footer>
		</div>

	</div>
</article>
