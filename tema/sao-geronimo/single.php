<?php
/**
 * Post único do blog.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
sg_migalhas();

while ( have_posts() ) :
	the_post();
	?>
	<section class="sec sec--curto sec--topo-curto"><div class="wrap">
		<article <?php post_class( 'pagina pagina--post' ); ?>>
			<header class="pagina__cab">
				<?php
				$sg_cats = get_the_category();
				if ( $sg_cats ) :
					?>
					<a class="eyebrow" href="<?php echo esc_url( get_category_link( $sg_cats[0] ) ); ?>"><?php echo esc_html( $sg_cats[0]->name ); ?></a>
				<?php endif; ?>
				<h1 class="h-sec"><?php the_title(); ?></h1>
				<?php
				// Data e autor só aparecem quando existem: nada de "·" solto.
				$sg_data  = 'nao' !== sg_opt( 'post_mostra_data', 'sim' ) ? get_the_date() : '';
				$sg_autor = 'sim' === sg_opt( 'post_mostra_autor', 'sim' ) ? get_the_author() : '';
				$sg_meta  = array_filter( array( $sg_data, $sg_autor ) );
				if ( $sg_meta ) :
					?>
					<p class="pagina__meta texto-mudo"><?php echo esc_html( implode( ' · ', $sg_meta ) ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="pagina__capa"><?php the_post_thumbnail( 'full', array( 'loading' => 'lazy' ) ); ?></figure>
			<?php endif; ?>

			<div class="prosa"><?php the_content(); ?></div>

			<?php if ( has_tag() ) : ?>
				<div class="pagina__tags"><?php the_tags( '', '' ); ?></div>
			<?php endif; ?>
		</article>

		<?php
		if ( 'nao' !== sg_opt( 'post_relacionados', 'sim' ) ) {
			$sg_rel = get_posts( array(
				'numberposts'  => 3,
				'post__not_in' => array( get_the_ID() ),
				'category__in' => wp_get_post_categories( get_the_ID() ),
			) );
			if ( $sg_rel ) :
				?>
				<aside class="posts-rel">
					<h2><?php echo esc_html( sg_opt( 'post_relacionados_titulo', 'Leia também' ) ); ?></h2>
					<div class="posts-rel__grade">
						<?php foreach ( $sg_rel as $sg_r ) : ?>
							<?php // Mesmo cartão do blog da home, inclusive o placeholder quando não há foto. ?>
							<article class="post-card">
								<a href="<?php echo esc_url( get_permalink( $sg_r ) ); ?>" class="post-card__img" tabindex="-1" aria-hidden="true">
									<?php if ( has_post_thumbnail( $sg_r ) ) : ?>
										<?php echo get_the_post_thumbnail( $sg_r, 'sg-card', array( 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php else : ?>
										<span class="post-card__ph"><?php echo sg_icone( 'estrela', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<?php endif; ?>
								</a>
								<div class="post-card__corpo">
									<h3><a href="<?php echo esc_url( get_permalink( $sg_r ) ); ?>"><?php echo esc_html( get_the_title( $sg_r ) ); ?></a></h3>
									<span class="post-card__data"><?php echo esc_html( get_the_date( '', $sg_r ) ); ?></span>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</aside>
				<?php
			endif;
		}
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="comentarios">';
			comments_template();
			echo '</div>';
		}
		?>
	</div></section>
	<?php
endwhile;

get_footer();
