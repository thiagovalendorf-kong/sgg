<?php
/**
 * São Gerônimo — funções do tema.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SG_VERSAO', '1.2.0' );
define( 'SG_DIR', get_template_directory() );
define( 'SG_URL', get_template_directory_uri() );

/**
 * Lê uma opção do painel São Gerônimo.
 *
 * O plugin "São Gerônimo — Painel" é quem grava essas opções. Se ele estiver
 * desativado, o tema continua funcionando com os valores padrão definidos aqui.
 *
 * @param string $chave  Chave da opção.
 * @param mixed  $padrao Valor devolvido quando a opção não existe.
 * @return mixed
 */
if ( ! function_exists( 'sg_opt' ) ) {
	function sg_opt( $chave, $padrao = '' ) {
		static $opcoes = null;
		if ( null === $opcoes ) {
			$opcoes = get_option( 'sg_opcoes', array() );
			if ( ! is_array( $opcoes ) ) {
				$opcoes = array();
			}
		}
		if ( ! isset( $opcoes[ $chave ] ) || '' === $opcoes[ $chave ] ) {
			return $padrao;
		}
		return $opcoes[ $chave ];
	}
}

require_once SG_DIR . '/inc/setup.php';
require_once SG_DIR . '/inc/template-tags.php';
require_once SG_DIR . '/inc/busca.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once SG_DIR . '/inc/woocommerce.php';
	require_once SG_DIR . '/inc/filtros.php';
	require_once SG_DIR . '/inc/extras.php';
	if ( is_admin() ) {
		require_once SG_DIR . '/inc/produto-admin.php';
	}
}

require_once SG_DIR . '/inc/limpeza.php';
