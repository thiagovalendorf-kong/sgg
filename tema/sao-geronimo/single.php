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
	<section class="sec sec--curto" style="padding-top:8px"><div class="wrap">
		<article <?php post_class( 'pagina pagina--post' ); ?>>
			<header class="pagina__cab">
				<?php
				$sg_cats = get_the_category();
				if ( $sg_cats ) :
					?>
					<a class="eyebrow" href="<?php echo esc_url( get_category_link( $sg_cats[0] ) ); ?>"><?php echo esc_html( $sg_cats[0]->name ); ?></a>
				<?php endif; ?>
				<h1 class="h-sec"><?php the_title(); ?></h1>
				<p class="pagina__meta texto-mudo">
					<?php echo esc_html( get_the_date() ); ?>
					<?php if ( 'sim' === sg_opt( 'post_mostra_autor', 'sim' ) ) : ?>
						&middot; <?php echo esc_html( get_the_author() ); ?>
					<?php endif; ?>
				</p>
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
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</div></section>
	<?php
endwhile;

get_footer();
