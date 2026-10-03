<?php
/**
 * Limpeza de textos e páginas da loja.
 *
 * 1. Quando uma marcação como <br> ou <span> aparece escrita na tela (porque
 *    veio escapada de um cadastro, importação ou plugin), troca por quebra de
 *    linha ou remove. Só mexe em texto visível, nunca em atributos ou scripts.
 * 2. Garante que Carrinho e Finalizar compra usam o formato clássico
 *    ([woocommerce_cart] / [woocommerce_checkout]), que é o que o Mercado Pago,
 *    o Melhor Envio e o Brazilian Market esperam.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajusta o texto visível de uma página inteira.
 *
 * @param string $html Página pronta.
 * @return string
 */
function sg_limpar_saida( $html ) {
	if ( false === strpos( $html, '&lt;' ) && false === strpos( $html, '&amp;lt;' ) ) {
		return $html;
	}

	$lt = '&(?:amp;)?lt;';
	$gt = '&(?:amp;)?gt;';

	return preg_replace_callback(
		'/>([^<>]*' . $lt . '[^<>]*)</u',
		function ( $m ) use ( $lt, $gt ) {
			$t = $m[1];
			$t = preg_replace( '#' . $lt . '\s*br\s*/?\s*' . $gt . '#i', '<br>', $t );
			$t = preg_replace( '#' . $lt . '\s*/?\s*(?:span|bdi|strong|b|em|i|small|p|div|font)\b(?:(?!' . $gt . ')[^<>])*' . $gt . '#i', '', $t );
			return '>' . $t . '<';
		},
		$html
	);
}

/**
 * Liga a limpeza só no site (não no painel, nem em AJAX, feed ou API).
 */
function sg_limpeza_ligar() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'sg_limpar_saida' );
}
add_action( 'template_redirect', 'sg_limpeza_ligar', 1 );

/**
 * Também limpa os textos cadastrados no painel antes de aparecerem.
 *
 * @param string $texto Texto.
 * @return string
 */
function sg_texto_sem_tags( $texto ) {
	if ( is_admin() || ! is_string( $texto ) ) {
		return $texto;
	}
	$texto = preg_replace( '#<br\s*/?>#i', ' ', $texto );
	return trim( wp_strip_all_tags( $texto ) );
}
add_filter( 'the_title', 'sg_texto_sem_tags', 5 );
add_filter( 'woocommerce_product_get_name', 'sg_texto_sem_tags', 5 );

/**
 * Carrinho e Finalizar compra no formato clássico (uma vez só).
 */
function sg_paginas_classicas() {
	if ( ! function_exists( 'wc_get_page_id' ) || get_option( 'sg_paginas_classicas' ) === SG_VERSAO ) {
		return;
	}
	$mapa = array(
		'cart'     => '[woocommerce_cart]',
		'checkout' => '[woocommerce_checkout]',
	);
	foreach ( $mapa as $pagina => $codigo ) {
		$id = (int) wc_get_page_id( $pagina );
		if ( $id < 1 ) {
			continue;
		}
		$p = get_post( $id );
		if ( $p && false === strpos( $p->post_content, $codigo ) ) {
			wp_update_post( array( 'ID' => $id, 'post_content' => $codigo ) );
		}
	}
	update_option( 'sg_paginas_classicas', SG_VERSAO );
}
add_action( 'init', 'sg_paginas_classicas', 40 );

/**
 * CSS e JS da loja (filtros, carrinho, checkout, conta).
 */
function sg_assets_loja() {
	wp_enqueue_style( 'sg-wcbase', SG_URL . '/assets/css/wcbase.css', array( 'sg-tema' ), SG_VERSAO );
	wp_enqueue_style( 'sg-loja', SG_URL . '/assets/css/loja.css', array( 'sg-wcbase' ), SG_VERSAO );
	wp_enqueue_style( 'sg-filtros', SG_URL . '/assets/css/filtros.css', array( 'sg-loja' ), SG_VERSAO );
	wp_enqueue_style( 'sg-produto', SG_URL . '/assets/css/produto.css', array( 'sg-loja' ), SG_VERSAO );
	wp_enqueue_script( 'sg-loja', SG_URL . '/assets/js/loja.js', array(), SG_VERSAO, true );
}
add_action( 'wp_enqueue_scripts', 'sg_assets_loja', 20 );
