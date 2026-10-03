<?php
/**
 * Botão de compra do produto simples: quantidade + "Adicionar à sacola".
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

if ( ! $product->is_in_stock() ) {
	echo '<div class="pdp__acoes"><span class="preco-consulta">' . esc_html__( 'Produto esgotado no momento.', 'sao-geronimo' ) . '</span></div>';
	return;
}

$sg_max = $product->get_max_purchase_quantity();
?>
<form class="cart pdp__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<div class="pdp__acoes">
		<div class="qtd">
			<button type="button" data-qtd="-" aria-label="<?php esc_attr_e( 'Menos', 'sao-geronimo' ); ?>">−</button>
			<span data-qtd-v><?php echo (int) $product->get_min_purchase_quantity(); ?></span>
			<button type="button" data-qtd="+" aria-label="<?php esc_attr_e( 'Mais', 'sao-geronimo' ); ?>" <?php echo $sg_max > 0 ? 'data-max="' . (int) $sg_max . '"' : ''; ?>>+</button>
			<input type="hidden" name="quantity" value="<?php echo (int) $product->get_min_purchase_quantity(); ?>" data-qtd-campo>
		</div>
		<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="btn btn--azul single_add_to_cart_button" style="flex:1;min-width:200px"><?php esc_html_e( 'Adicionar à sacola', 'sao-geronimo' ); ?></button>
	</div>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
</form>
