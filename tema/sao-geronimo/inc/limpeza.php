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

	// Blocos de código, campos de texto, scripts e estilos ficam intocados:
	// um post que mostra "<div>" como exemplo precisa continuar mostrando.
	$partes = preg_split( '#(<(pre|code|textarea|script|style)\b.*?</\2\s*>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $partes ) {
		return $html;
	}

	$lt = '&(?:amp;)?lt;';
	$gt = '&(?:amp;)?gt;';

	$saida = '';
	// Com DELIM_CAPTURE vêm 3 itens por bloco protegido: bloco inteiro, nome da tag, texto seguinte.
	for ( $i = 0, $n = count( $partes ); $i < $n; $i++ ) {
		$pedaco = $partes[ $i ];
		if ( $i % 3 === 1 ) {
			$saida .= $pedaco; // bloco protegido
			continue;
		}
		if ( $i % 3 === 2 ) {
			continue; // nome da tag capturado, não é conteúdo
		}
		$saida .= preg_replace_callback(
			'/>([^<>]*' . $lt . '[^<>]*)</u',
			function ( $m ) use ( $lt, $gt ) {
				$t = $m[1];
				$t = preg_replace( '#' . $lt . '\s*br\s*/?\s*' . $gt . '#i', '<br>', $t );
				$t = preg_replace( '#' . $lt . '\s*/?\s*(?:span|bdi|strong|b|em|i|small|p|div|font)\b(?:(?!' . $gt . ')[^<>])*' . $gt . '#i', '', $t );
				return '>' . $t . '<';
			},
			$pedaco
		);
	}

	return $saida;
}

/**
 * Liga a limpeza só no site (não no painel, nem em AJAX, feed ou API).
 */
function sg_limpeza_ligar() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() || get_query_var( 'sitemap' ) || get_query_var( 'sitemap-stylesheet' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
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
	if ( ! function_exists( 'wc_get_page_id' ) || get_option( 'sg_paginas_classicas' ) ) {
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
		// Só converte página vazia ou feita com os blocos novos; nunca apaga texto da dona.
		if ( $p && false === strpos( $p->post_content, $codigo ) ) {
			$vazia = '' === trim( wp_strip_all_tags( $p->post_content ) );
			if ( $vazia || false !== strpos( $p->post_content, 'wp:woocommerce/' . $pagina ) ) {
				wp_update_post( array( 'ID' => $id, 'post_content' => $codigo ) );
			}
		}
	}
	update_option( 'sg_paginas_classicas', '1' );
}
add_action( 'init', 'sg_paginas_classicas', 40 );

/**
 * Desliga o CSS padrão do WooCommerce (layout, smallscreen e general).
 * O visual é todo do tema (wcbase.css + loja.css). Os scripts do WooCommerce
 * continuam ligados; só os estilos saem.
 *
 * @param array $estilos Estilos que o WooCommerce carregaria.
 * @return array
 */
function sg_wc_sem_css_padrao( $estilos ) {
	return array();
}
add_filter( 'woocommerce_enqueue_styles', 'sg_wc_sem_css_padrao', 99 );

/**
 * CSS e JS da loja (base do WooCommerce, filtros, carrinho, checkout, conta).
 * Ordem: tema.css -> wcbase.css -> loja.css -> filtros.css / produto.css.
 */
function sg_assets_loja() {
	// Só nas páginas da loja, carrinho, finalizar compra, conta e páginas com shortcode do WooCommerce.
	$loja = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
	if ( ! $loja && is_singular() ) {
		$loja = (bool) preg_match( '/\[(woocommerce_|products|product_|sale_products|recent_products|featured_products)/', (string) get_post_field( 'post_content', get_queried_object_id() ) );
	}
	if ( ! $loja ) {
		return;
	}
	wp_enqueue_style( 'sg-wcbase', SG_URL . '/assets/css/wcbase.css', array( 'sg-tema' ), SG_VERSAO );
	wp_enqueue_style( 'sg-loja', SG_URL . '/assets/css/loja.css', array( 'sg-wcbase' ), SG_VERSAO );
	wp_enqueue_style( 'sg-filtros', SG_URL . '/assets/css/filtros.css', array( 'sg-loja' ), SG_VERSAO );
	wp_enqueue_style( 'sg-produto', SG_URL . '/assets/css/produto.css', array( 'sg-loja' ), SG_VERSAO );
	wp_enqueue_script( 'sg-loja', SG_URL . '/assets/js/loja.js', array(), SG_VERSAO, true );
}
add_action( 'wp_enqueue_scripts', 'sg_assets_loja', 20 );
