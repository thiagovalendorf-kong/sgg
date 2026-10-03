<?php
/**
 * Listagem padrão (blog, arquivos, categorias de post).
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
sg_migalhas();
?>

<section class="sec sec--curto" style="padding-top:8px"><div class="wrap">
	<div class="sec__cab">
		<div>
			<h1 class="h-sec">
				<?php
				if ( is_home() ) {
					echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'sao-geronimo' ) );
				} else {
					the_archive_title();
				}
				?>
			</h1>
			<?php the_archive_description( '<p class="sec__sub texto-mudo">', '</p>' ); ?>
		</div>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="blog-home__grade">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'post-card' ); ?>>
					<a href="<?php the_permalink(); ?>" class="post-card__img">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'sg-card', array( 'loading' => 'lazy' ) ); ?>
						<?php else : ?>
							<span class="post-card__ph"><?php echo sg_icone( 'estrela', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<?php endif; ?>
					</a>
					<div class="post-card__corpo">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
						<span class="post-card__data"><?php echo esc_html( get_the_date() ); ?></span>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => sg_icone( 'ant', 14 ),
			'next_text' => sg_icone( 'prox', 14 ),
			'class'     => 'paginacao',
		) );
		?>
	<?php else : ?>
		<p class="texto-mudo"><?php esc_html_e( 'Ainda não há publicações por aqui.', 'sao-geronimo' ); ?></p>
	<?php endif; ?>
</div></section>

<?php
get_footer();
