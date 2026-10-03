<?php
/**
 * Últimos posts do blog na home.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_posts = get_posts( array(
	'numberposts' => max( 1, (int) sg_opt( 'home_blog_qtd', 3 ) ),
	'post_status' => 'publish',
) );

if ( ! $sg_posts ) {
	return;
}
?>
<section class="sec sec--curto blog-home" aria-labelledby="t-blog"><div class="wrap">
	<div class="sec__cab">
		<div>
			<h2 class="h-sec" id="t-blog"><?php echo esc_html( sg_opt( 'home_blog_titulo', 'Do nosso diário' ) ); ?></h2>
			<?php if ( sg_opt( 'home_blog_sub' ) ) : ?>
				<p class="sec__sub texto-mudo"><?php echo esc_html( sg_opt( 'home_blog_sub' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php $sg_blog = get_permalink( get_option( 'page_for_posts' ) ); ?>
		<?php if ( $sg_blog ) : ?>
			<a href="<?php echo esc_url( $sg_blog ); ?>" class="link-sub"><?php esc_html_e( 'Ver todos', 'sao-geronimo' ); ?> <?php echo sg_icone( 'seta', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
	</div>

	<div class="blog-home__grade">
		<?php foreach ( $sg_posts as $sg_p ) : ?>
			<article class="post-card">
				<a href="<?php echo esc_url( get_permalink( $sg_p ) ); ?>" class="post-card__img">
					<?php if ( has_post_thumbnail( $sg_p ) ) : ?>
						<?php echo get_the_post_thumbnail( $sg_p, 'sg-card', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $sg_p ) ) ) ); ?>
					<?php else : ?>
						<span class="post-card__ph"><?php echo sg_icone( 'estrela', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
				</a>
				<div class="post-card__corpo">
					<?php
					$sg_cats = get_the_category( $sg_p->ID );
					if ( $sg_cats ) :
						?>
						<span class="post-card__cat"><?php echo esc_html( $sg_cats[0]->name ); ?></span>
					<?php endif; ?>
					<h3><a href="<?php echo esc_url( get_permalink( $sg_p ) ); ?>"><?php echo esc_html( get_the_title( $sg_p ) ); ?></a></h3>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $sg_p->post_excerpt ? $sg_p->post_excerpt : $sg_p->post_content ), 22 ) ); ?></p>
					<span class="post-card__data"><?php echo esc_html( get_the_date( '', $sg_p ) ); ?></span>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div></section>
