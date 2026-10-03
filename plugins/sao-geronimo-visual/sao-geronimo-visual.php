<?php
/**
 * Plugin Name: São Gerônimo — Visual do Painel
 * Description: Deixa o painel do WordPress limpo, com modo claro e escuro. Cada pessoa escolhe o seu pelo botão no topo.
 * Version: 1.0.0
 * Text Domain: sao-geronimo-visual
 *
 * @package sao-geronimo-visual
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGV_VERSAO', '1.0.0' );
define( 'SGV_URL', plugin_dir_url( __FILE__ ) );

/**
 * Modo escolhido por quem está logado: 'claro' (padrão) ou 'escuro'.
 *
 * @return string
 */
function sgv_modo() {
	$modo = get_user_meta( get_current_user_id(), 'sgv_modo', true );
	if ( ! in_array( $modo, array( 'claro', 'escuro' ), true ) ) {
		$modo = get_option( 'sgv_modo_padrao', 'claro' );
	}
	return 'escuro' === $modo ? 'escuro' : 'claro';
}

/**
 * CSS e JS só dentro do painel.
 */
function sgv_assets() {
	wp_enqueue_style( 'sgv-painel', SGV_URL . 'assets/painel.css', array(), SGV_VERSAO );
	wp_enqueue_script( 'sgv-painel', SGV_URL . 'assets/painel.js', array(), SGV_VERSAO, true );
	wp_localize_script( 'sgv-painel', 'SGV', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'sgv_modo' ),
		'modo'  => sgv_modo(),
	) );
}
add_action( 'admin_enqueue_scripts', 'sgv_assets' );

/**
 * Marca o <body> do painel com o modo atual.
 *
 * @param string $classes Classes atuais.
 * @return string
 */
function sgv_classe_body( $classes ) {
	return $classes . ' sgv sgv-' . sgv_modo() . ' ';
}
add_filter( 'admin_body_class', 'sgv_classe_body' );

/**
 * Botão claro/escuro na barra de cima.
 *
 * @param WP_Admin_Bar $barra Barra de admin.
 */
function sgv_botao( $barra ) {
	$barra->add_node( array(
		'id'    => 'sgv-modo',
		'title' => '<span class="sgv-sol">☀</span><span class="sgv-lua">☾</span> <span class="sgv-rotulo">' . esc_html__( 'Claro / Escuro', 'sao-geronimo-visual' ) . '</span>',
		'href'  => '#',
		'meta'  => array( 'title' => __( 'Trocar entre modo claro e escuro', 'sao-geronimo-visual' ) ),
	) );
}
add_action( 'admin_bar_menu', 'sgv_botao', 5 );

/**
 * Grava a escolha da pessoa.
 */
function sgv_salvar_modo() {
	check_ajax_referer( 'sgv_modo', 'nonce' );
	$modo = isset( $_POST['modo'] ) && 'escuro' === $_POST['modo'] ? 'escuro' : 'claro';
	update_user_meta( get_current_user_id(), 'sgv_modo', $modo );
	wp_send_json_success( $modo );
}
add_action( 'wp_ajax_sgv_modo', 'sgv_salvar_modo' );

/**
 * Tira o que polui: avisos de terceiros, logo do WP, rodapé, widgets de blog.
 */
function sgv_limpar() {
	// Rodapé "Obrigado por criar com WordPress" e versão.
	add_filter( 'admin_footer_text', '__return_empty_string' );
	add_filter( 'update_footer', '__return_empty_string', 99 );

	// Logo do WordPress na barra.
	add_action( 'admin_bar_menu', function ( $barra ) {
		$barra->remove_node( 'wp-logo' );
		$barra->remove_node( 'comments' );
		$barra->remove_node( 'new-content' );
	}, 999 );
}
add_action( 'admin_init', 'sgv_limpar' );

/**
 * Caixas da página inicial do painel que ninguém usa.
 */
function sgv_limpar_inicio() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
add_action( 'wp_dashboard_setup', 'sgv_limpar_inicio', 999 );

/**
 * Esconde avisos de plugins (propaganda) para quem não é administrador.
 */
function sgv_avisos() {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}
}
add_action( 'in_admin_header', 'sgv_avisos', 1000 );

/**
 * Mesmas cores na tela de login.
 */
function sgv_login() {
	wp_enqueue_style( 'sgv-login', SGV_URL . 'assets/login.css', array(), SGV_VERSAO );
}
add_action( 'login_enqueue_scripts', 'sgv_login' );
