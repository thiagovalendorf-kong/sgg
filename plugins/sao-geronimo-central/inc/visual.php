<?php
/**
 * Visual do painel: claro com as cores da marca, ou escuro.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modo escolhido por quem está logado.
 *
 * @return string claro|escuro
 */
function sgcv_modo() {
	$m = get_user_meta( get_current_user_id(), 'sgc_modo', true );
	return 'escuro' === $m ? 'escuro' : 'claro';
}

/**
 * Estilos do painel inteiro.
 */
function sgcv_assets() {
	wp_enqueue_style( 'sgcv', SGC_URL . 'assets/painel.css', array(), SGC_VERSAO );
	wp_enqueue_script( 'sgcv', SGC_URL . 'assets/painel.js', array(), SGC_VERSAO, true );
	wp_localize_script( 'sgcv', 'SGCV', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'sgc_modo' ),
		'modo'  => sgcv_modo(),
	) );
}
add_action( 'admin_enqueue_scripts', 'sgcv_assets' );

/**
 * Classes no <body> do painel.
 *
 * @param string $classes Classes.
 * @return string
 */
function sgcv_body( $classes ) {
	return $classes . ' sgcv sgcv-' . sgcv_modo() . ' ';
}
add_filter( 'admin_body_class', 'sgcv_body' );

/**
 * Botão claro/escuro na barra do topo.
 *
 * @param WP_Admin_Bar $barra Barra.
 */
function sgcv_botao( $barra ) {
	$barra->add_node( array(
		'id'    => 'sgcv-modo',
		'title' => '<span class="sgcv-sol">☀</span><span class="sgcv-lua">☾</span> <span class="sgcv-rotulo">Claro / Escuro</span>',
		'href'  => '#',
	) );
	$barra->remove_node( 'wp-logo' );
	$barra->remove_node( 'comments' );
	$barra->remove_node( 'new-content' );
	$barra->remove_node( 'updates' );
}
add_action( 'admin_bar_menu', 'sgcv_botao', 999 );

/**
 * Grava o modo escolhido.
 */
function sgcv_salvar() {
	check_ajax_referer( 'sgc_modo', 'nonce' );
	$m = isset( $_POST['modo'] ) && 'escuro' === $_POST['modo'] ? 'escuro' : 'claro';
	update_user_meta( get_current_user_id(), 'sgc_modo', $m );
	wp_send_json_success( $m );
}
add_action( 'wp_ajax_sgcv_modo', 'sgcv_salvar' );

/**
 * Limpeza geral (rodapé, avisos, caixas inúteis).
 */
function sgcv_limpar() {
	add_filter( 'admin_footer_text', '__return_empty_string' );
	add_filter( 'update_footer', '__return_empty_string', 99 );
}
add_action( 'admin_init', 'sgcv_limpar' );

/**
 * Esconde avisos de plugins (propaganda) de quem não é administrador.
 */
function sgcv_avisos() {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}
}
add_action( 'in_admin_header', 'sgcv_avisos', 1000 );

/**
 * Tela de login com a mesma cara.
 */
function sgcv_login() {
	wp_enqueue_style( 'sgcv-login', SGC_URL . 'assets/login.css', array(), SGC_VERSAO );
}
add_action( 'login_enqueue_scripts', 'sgcv_login' );
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return get_bloginfo( 'name' );
} );
