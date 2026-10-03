<?php
/**
 * Carrossel de categorias com ícones.
 *
 * O ícone de cada categoria é escolhido na própria categoria do WooCommerce
 * (Produtos → Categorias → editar → campo "Ícone no site"). Se nenhum for
 * escolhido, usa um ícone genérico.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$sg_escolhidas = sg_opt( 'home_categorias_lista', array() );
$sg_args       = array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'orderby'    => 'name',
);

if ( is_array( $sg_escolhidas ) && $sg_escolhidas ) {
	$sg_args['include'] = array_map( 'absint', $sg_escolhidas );
	$sg_args['orderby'] = 'include';
} else {
	$sg_args['number']  = (int) sg_opt( 'home_categorias_qtd', 14 );
	$sg_args['orderby'] = 'count';
	$sg_args['order']   = 'DESC';
}

$sg_cats = get_terms( $sg_args );
if ( is_wp_error( $sg_cats ) || ! $sg_cats ) {
	return;
}
?>
<section class="sec sec--curto vitrine cat-destaque" aria-labelledby="t-categorias"><div class="wrap">
	<div class="sec__cab">
		<div>
			<h2 class="h-sec" id="t-categorias"><?php echo esc_html( sg_opt( 'home_categorias_titulo', 'Categorias em destaque' ) ); ?></h2>
			<?php if ( sg_opt( 'home_categorias_sub' ) ) : ?>
				<p class="sec__sub texto-mudo"><?php echo esc_html( sg_opt( 'home_categorias_sub' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="link-sub"><?php esc_html_e( 'Ver tudo', 'sao-geronimo' ); ?> <?php echo sg_icone( 'seta', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
	</div>

	<div class="carrossel">
		<button class="carrossel__nav carrossel__nav--ant" data-pista-ant aria-label="<?php esc_attr_e( 'Categoria anterior', 'sao-geronimo' ); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
		<button class="carrossel__nav carrossel__nav--prox" data-pista-prox aria-label="<?php esc_attr_e( 'Próxima categoria', 'sao-geronimo' ); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></button>
		<div class="cat-ico__pista" data-pista>
			<?php foreach ( $sg_cats as $sg_c ) : ?>
				<a class="cat-ico" href="<?php echo esc_url( get_term_link( $sg_c ) ); ?>">
					<span class="cat-ico__disco"><?php echo sg_icone_categoria( $sg_c->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="cat-ico__nome"><?php echo esc_html( $sg_c->name ); ?></span>
					<span class="cat-ico__qtd"><?php printf( esc_html( _n( '%d produto', '%d produtos', $sg_c->count, 'sao-geronimo' ) ), (int) $sg_c->count ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div></section>
