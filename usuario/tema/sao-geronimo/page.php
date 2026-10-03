<?php
/**
 * Página comum.
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
	<section class="sec sec--curto" style="padding-top:8px"><div class="wrap">
		<article <?php post_class( 'pagina' ); ?>>
			<header class="sec__cab">
				<div><h1 class="h-sec"><?php the_title(); ?></h1></div>
			</header>

			<?php if ( has_post_thumbnail() && 'sim' === sg_opt( 'pagina_mostra_capa', 'sim' ) ) : ?>
				<figure class="pagina__capa"><?php the_post_thumbnail( 'full', array( 'loading' => 'lazy' ) ); ?></figure>
			<?php endif; ?>

			<div class="prosa">
				<?php
				the_content();
				wp_link_pages( array(
					'before' => '<div class="paginacao">',
					'after'  => '</div>',
				) );
				?>
			</div>
		</article>
	</div></section>
	<?php
endwhile;

get_footer();
