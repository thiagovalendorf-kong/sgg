<?php
/**
 * Textos e blocos editáveis das páginas da loja: produto, carrinho, checkout e conta.
 * Tudo vem do painel "Meu Site"; os padrões abaixo valem se nada foi escrito.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Página do produto
 * ---------------------------------------------------------------------- */

/**
 * Selos de confiança e aviso embaixo do botão de compra.
 */
function sg_produto_selos() {
	if ( 'nao' === sg_opt( 'produto_selos', 'sim' ) ) {
		return;
	}
	$padroes = array(
		1 => 'Compra 100% segura',
		2 => 'Enviamos para todo o Brasil',
		3 => 'Troca fácil em até 7 dias',
	);
	$itens = array();
	foreach ( $padroes as $n => $padrao ) {
		$t = trim( (string) sg_opt( 'produto_selo' . $n, $padrao ) );
		if ( $t ) {
			$itens[] = $t;
		}
	}
	if ( ! $itens ) {
		return;
	}
	echo '<ul class="selos">';
	foreach ( $itens as $t ) {
		echo '<li>' . sg_icone( 'estrela', 16 ) . '<span>' . esc_html( $t ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
	$aviso = trim( (string) sg_opt( 'produto_aviso', '' ) );
	if ( $aviso ) {
		echo '<p class="selos__aviso">' . esc_html( $aviso ) . '</p>';
	}
}
add_action( 'woocommerce_single_product_summary', 'sg_produto_selos', 36 );

/**
 * Esconde os "produtos relacionados" se o painel mandou.
 */
function sg_produto_relacionados() {
	if ( 'nao' === sg_opt( 'produto_relacionados', 'sim' ) ) {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}
}
add_action( 'wp', 'sg_produto_relacionados' );

/* -------------------------------------------------------------------------
 * Carrinho e checkout
 * ---------------------------------------------------------------------- */

/**
 * Barra "falta pouco para o frete grátis".
 */
function sg_barra_frete_gratis() {
	$meta = (float) sg_opt( 'frete_gratis_valor', 0 );
	if ( $meta <= 0 || ! WC()->cart ) {
		return;
	}
	$total = (float) WC()->cart->get_displayed_subtotal();
	$falta = $meta - $total;
	$pct   = max( 0, min( 100, $total / $meta * 100 ) );

	echo '<div class="frete-barra' . ( $falta <= 0 ? ' frete-barra--ok' : '' ) . '">';
	if ( $falta <= 0 ) {
		echo '<p>' . esc_html__( 'Você ganhou frete grátis! 🎉', 'sao-geronimo' ) . '</p>';
	} else {
		/* translators: %s: valor que falta */
		echo '<p>' . wp_kses( sprintf( __( 'Falta só <b>%s</b> para você ganhar frete grátis.', 'sao-geronimo' ), esc_html( sg_moeda( $falta ) ) ), array( 'b' => array() ) ) . '</p>';
	}
	echo '<div class="frete-barra__trilho"><span style="width:' . esc_attr( round( $pct ) ) . '%"></span></div></div>';
}
add_action( 'woocommerce_before_cart', 'sg_barra_frete_gratis', 5 );
add_action( 'woocommerce_before_checkout_form', 'sg_barra_frete_gratis', 4 );

/**
 * Aviso no topo do finalizar compra.
 */
function sg_checkout_aviso() {
	$t = trim( (string) sg_opt( 'checkout_aviso', '' ) );
	if ( $t ) {
		echo '<div class="aviso-loja">' . esc_html( $t ) . '</div>';
	}
}
add_action( 'woocommerce_before_checkout_form', 'sg_checkout_aviso', 5 );

/**
 * Linha de segurança embaixo do botão "Finalizar pedido".
 */
function sg_checkout_seguranca() {
	$t = trim( (string) sg_opt( 'checkout_seguranca', 'Pagamento seguro. Seus dados são protegidos.' ) );
	if ( $t ) {
		echo '<p class="checkout-seguro">' . sg_icone( 'estrela', 14 ) . ' ' . esc_html( $t ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'woocommerce_review_order_after_submit', 'sg_checkout_seguranca' );

/**
 * Texto do botão final.
 *
 * @param string $texto Texto original.
 * @return string
 */
function sg_checkout_botao( $texto ) {
	$t = trim( (string) sg_opt( 'checkout_botao', '' ) );
	return $t ? $t : $texto;
}
add_filter( 'woocommerce_order_button_text', 'sg_checkout_botao' );

/* -------------------------------------------------------------------------
 * Minha conta
 * ---------------------------------------------------------------------- */

/**
 * Recado de boas-vindas no começo da conta.
 */
function sg_conta_boas_vindas() {
	$t = trim( (string) sg_opt( 'conta_boas_vindas', '' ) );
	if ( $t ) {
		echo '<div class="aviso-loja">' . esc_html( $t ) . '</div>';
	}
}
add_action( 'woocommerce_account_dashboard', 'sg_conta_boas_vindas', 5 );

/**
 * Esconde "Downloads" da conta quando o cliente não tem arquivos para baixar.
 *
 * @param array $itens Itens do menu.
 * @return array
 */
function sg_conta_menu_sem_downloads( $itens ) {
	if ( isset( $itens['downloads'] ) && function_exists( 'WC' ) && is_user_logged_in() && function_exists( 'wc_get_customer_available_downloads' ) ) {
		if ( ! wc_get_customer_available_downloads( get_current_user_id() ) ) {
			unset( $itens['downloads'] );
		}
	}
	return $itens;
}
add_filter( 'woocommerce_account_menu_items', 'sg_conta_menu_sem_downloads', 20 );
