<?php
/**
 * Plugin Name: São Gerônimo — Central de Controle
 * Description: Painel limpo e simples para controlar todo o site: uma página para cada assunto, prévia em computador, tablet e celular, e menu enxuto. Substitui o plugin "São Gerônimo — Visual do Painel".
 * Version: 2.1.0
 * Text Domain: sao-geronimo-central
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGC_VERSAO', '2.1.0' );
define( 'SGC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGC_URL', plugin_dir_url( __FILE__ ) );

require_once SGC_DIR . 'inc/esquema.php';
require_once SGC_DIR . 'inc/dados.php';
require_once SGC_DIR . 'inc/campos.php';
require_once SGC_DIR . 'inc/central.php';
require_once SGC_DIR . 'inc/integracoes.php';
require_once SGC_DIR . 'inc/produto-ux.php';
require_once SGC_DIR . 'inc/catalogo.php';
require_once SGC_DIR . 'inc/menu.php';
require_once SGC_DIR . 'inc/visual.php';
