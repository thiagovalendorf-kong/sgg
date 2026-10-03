<?php
/**
 * Plugin Name: São Gerônimo — Painel
 * Plugin URI:  https://saogeronimoreligiosos.com.br
 * Description: Painel administrativo da loja São Gerônimo: identidade visual, página inicial, SEO/GEO/AEO, controladoria de vendas, cadastro simplificado de produtos, importador do catálogo e marca branca do WordPress.
 * Version:     1.0.0
 * Author:      Kong Media
 * Author URI:  https://kongmedia.com.br
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sao-geronimo-painel
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGP_VERSAO', '1.0.0' );
define( 'SGP_ARQ', __FILE__ );
define( 'SGP_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Lê uma opção do painel.
 *
 * Definida aqui também (além do tema) para o plugin funcionar mesmo que o
 * tema São Gerônimo esteja desativado.
 *
 * @param string $chave  Chave.
 * @param mixed  $padrao Padrão.
 * @return mixed
 */
if ( ! function_exists( 'sg_opt' ) ) {
	function sg_opt( $chave, $padrao = '' ) {
		static $o = null;
		if ( null === $o ) {
			$o = get_option( 'sg_opcoes', array() );
			if ( ! is_array( $o ) ) {
				$o = array();
			}
		}
		if ( ! isset( $o[ $chave ] ) || '' === $o[ $chave ] ) {
			return $padrao;
		}
		return $o[ $chave ];
	}
}

require_once SGP_DIR . 'inc/campos.php';
require_once SGP_DIR . 'inc/estrutura.php';
require_once SGP_DIR . 'inc/painel.php';
require_once SGP_DIR . 'inc/whitelabel.php';
require_once SGP_DIR . 'inc/seo.php';
require_once SGP_DIR . 'inc/seo-frente.php';
require_once SGP_DIR . 'inc/controladoria.php';
require_once SGP_DIR . 'inc/produto-facil.php';
require_once SGP_DIR . 'inc/importador.php';
require_once SGP_DIR . 'inc/assistente.php';

/**
 * Traduções.
 */
function sgp_idioma() {
	load_plugin_textdomain( 'sao-geronimo-painel', false, dirname( plugin_basename( SGP_ARQ ) ) . '/languages' );
}
add_action( 'init', 'sgp_idioma' );

/**
 * Na ativação: cria o perfil Lojista, grava os valores padrão e as páginas.
 */
function sgp_ativacao() {
	sgp_cria_perfil();

	$atual = get_option( 'sg_opcoes', array() );
	if ( ! is_array( $atual ) ) {
		$atual = array();
	}
	// só preenche o que ainda não existe — nunca sobrescreve o que o lojista mudou
	update_option( 'sg_opcoes', array_merge( sgp_padroes(), $atual ) );

	if ( ! get_option( 'sgp_marca_nome' ) ) {
		update_option( 'sgp_marca_nome', 'São Gerônimo' );
	}

	wp_mkdir_p( sgp_pasta_import() );

	set_transient( 'sgp_recem_ativado', 1, 60 );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sgp_ativacao' );

/**
 * Na desativação: limpa as regras de URL.
 */
function sgp_desativacao() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sgp_desativacao' );

/**
 * Manda para os primeiros passos logo depois de ativar.
 */
function sgp_redireciona_ativacao() {
	if ( ! get_transient( 'sgp_recem_ativado' ) ) {
		return;
	}
	delete_transient( 'sgp_recem_ativado' );
	if ( isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=sg-assistente' ) );
	exit;
}
add_action( 'admin_init', 'sgp_redireciona_ativacao' );

/**
 * Avisa se o tema São Gerônimo não estiver ativo.
 */
function sgp_aviso_tema() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tema = wp_get_theme();
	if ( 'São Gerônimo' === $tema->get( 'Name' ) || 'São Gerônimo' === $tema->get( 'Template' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><b>São Gerônimo — Painel:</b> '
		. esc_html__( 'o tema São Gerônimo não está ativo. O painel continua funcionando, mas várias opções de visual só aparecem no site com esse tema.', 'sao-geronimo-painel' )
		. ' <a href="' . esc_url( admin_url( 'themes.php' ) ) . '">' . esc_html__( 'Ver temas', 'sao-geronimo-painel' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'sgp_aviso_tema' );

/**
 * Link rápido para o painel na lista de plugins.
 *
 * @param array $links Links.
 * @return array
 */
function sgp_link_plugin( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'admin.php?page=sg-painel' ) ) . '">' . esc_html__( 'Painel', 'sao-geronimo-painel' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sgp_link_plugin' );

/**
 * Declara compatibilidade com o armazenamento novo de pedidos do WooCommerce.
 */
function sgp_compat_hpos() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'sgp_compat_hpos' );
