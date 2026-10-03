<?php
/**
 * Resultados da busca — mostra produtos na grade da loja e conteúdos abaixo.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
sg_migalhas();

$sg_termo = get_search_query();

$sg_produtos = array();
if ( function_exists( 'wc_get_products' ) && $sg_termo ) {
	$sg_produtos = wc_get_products( array(
		'status' => 'publish',
		'limit'  => 48,
		's'      => $sg_termo,
		'return' => 'ids',
	) );
}
?>
<section class="sec sec--curto" style="padding-top:8px"><div class="wrap">
	<div class="res-busca__cab">
		<span class="eyebrow"><?php esc_html_e( 'Loja', 'sao-geronimo' ); ?></span>
		<h1 class="h-sec">
			<?php
			if ( $sg_produtos ) {
				printf(
					esc_html( _n( '%1$d resultado para “%2$s”', '%1$d resultados para “%2$s”', count( $sg_produtos ), 'sao-geronimo' ) ),
					count( $sg_produtos ),
					esc_html( $sg_termo )
				);
			} else {
				printf( esc_html__( 'Nada encontrado para “%s”', 'sao-geronimo' ), esc_html( $sg_termo ) );
			}
			?>
		</h1>
		<?php get_search_form(); ?>
	</div>

	<?php if ( $sg_produtos ) : ?>
		<div class="grade-prod">
			<?php
			foreach ( $sg_produtos as $sg_pid ) {
				$GLOBALS['post']    = get_post( $sg_pid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				$GLOBALS['product'] = wc_get_product( $sg_pid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $GLOBALS['post'] );
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			$GLOBALS['product'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			?>
		</div>
	<?php else : ?>
		<div class="busca__nada">
			<b><?php esc_html_e( 'Não achamos nenhum produto com esse termo.', 'sao-geronimo' ); ?></b>
			<span>
				<?php esc_html_e( 'Tente uma palavra mais curta', 'sao-geronimo' ); ?>
				<?php if ( sg_whatsapp_link() ) : ?>
					&middot; <a href="<?php echo esc_url( sg_whatsapp_link( sprintf( 'Olá! Procurei por "%s" no site e não encontrei. Vocês têm?', $sg_termo ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'ou pergunte pelo WhatsApp', 'sao-geronimo' ); ?></a>
				<?php endif; ?>
			</span>
		</div>
	<?php endif; ?>

	<?php
	// Páginas e posts que também batem com o termo.
	$sg_conteudo = new WP_Query( array(
		'post_type'      => array( 'post', 'page' ),
		's'              => $sg_termo,
		'posts_per_page' => 6,
		'post_status'    => 'publish',
	) );
	if ( $sg_conteudo->have_posts() ) :
		?>
		<div class="sec__cab" style="margin-top:56px">
			<div><h2 class="h-sec" style="font-size:1.6rem"><?php esc_html_e( 'Também encontramos nestes conteúdos', 'sao-geronimo' ); ?></h2></div>
		</div>
		<ul class="lista-links">
			<?php
			while ( $sg_conteudo->have_posts() ) :
				$sg_conteudo->the_post();
				?>
				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
			<?php endwhile; ?>
		</ul>
		<?php
		wp_reset_postdata();
	endif;
	?>
</div></section>

<?php
get_footer();
