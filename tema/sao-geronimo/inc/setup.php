<?php
/**
 * Registro de recursos, menus, scripts e variáveis de cor.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recursos que o tema suporta.
 */
function sg_setup() {
	load_theme_textdomain( 'sao-geronimo', SG_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 320,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	// WooCommerce — o tema desenha a loja inteira, por isso declara suporte completo.
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 600,
		'single_image_width'    => 1200,
		'product_grid'          => array(
			'default_rows'    => 3,
			'min_rows'        => 1,
			'default_columns' => 4,
			'min_columns'     => 2,
			'max_columns'     => 5,
		),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	add_image_size( 'sg-card', 700, 700, true );
	add_image_size( 'sg-mini', 140, 140, true );

	register_nav_menus( array(
		'principal' => __( 'Menu principal (cabeçalho)', 'sao-geronimo' ),
		'rodape'    => __( 'Menu do rodapé', 'sao-geronimo' ),
		'legal'     => __( 'Links legais (rodapé, linha de baixo)', 'sao-geronimo' ),
	) );
}
add_action( 'after_setup_theme', 'sg_setup' );

/**
 * Largura padrão do conteúdo.
 */
function sg_content_width() {
	$GLOBALS['content_width'] = 1400;
}
add_action( 'after_setup_theme', 'sg_content_width', 0 );

/**
 * Áreas de widget do rodapé.
 */
function sg_widgets() {
	for ( $i = 1; $i <= 3; $i++ ) {
		register_sidebar( array(
			'name'          => sprintf( __( 'Rodapé — coluna %d', 'sao-geronimo' ), $i ),
			'id'            => 'rodape-' . $i,
			'description'   => __( 'Blocos livres que aparecem no rodapé.', 'sao-geronimo' ),
			'before_widget' => '<div class="rodape__widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="rodape__tit">',
			'after_title'   => '</h4>',
		) );
	}
}
add_action( 'widgets_init', 'sg_widgets' );

/**
 * CSS e JS do site.
 */
function sg_assets() {
	// Única folha de fontes do site (o site.css não importa mais nada de fora).
	$fontes = sg_opt( 'fonte_google', 'Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400' );
	if ( $fontes ) {
		wp_enqueue_style(
			'sg-fontes',
			'https://fonts.googleapis.com/css2?family=' . str_replace( ' ', '+', $fontes ) . '&display=swap',
			array(),
			null
		);
	}

	wp_enqueue_style( 'sg-site', SG_URL . '/assets/css/site.css', array(), SG_VERSAO );
	wp_enqueue_style( 'sg-tema', SG_URL . '/assets/css/tema.css', array( 'sg-site' ), SG_VERSAO );
	wp_add_inline_style( 'sg-site', sg_css_variaveis() );

	$extra = trim( (string) sg_opt( 'css_extra', '' ) );
	if ( $extra ) {
		wp_add_inline_style( 'sg-site', $extra );
	}

	wp_enqueue_script( 'sg-site', SG_URL . '/assets/js/site.js', array(), SG_VERSAO, true );
	wp_localize_script( 'sg-site', 'SG_WP', array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'sg_front' ),
		'raiz'     => trailingslashit( home_url( '/' ) ),
		'busca'    => home_url( '/?s=' ),
		'carrinho' => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ),
		'whatsapp' => preg_replace( '/\D/', '', (string) sg_opt( 'whatsapp', '5548996397562' ) ),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sg_assets' );

/**
 * Converte as cores escolhidas no painel em variáveis CSS.
 *
 * Tudo no CSS do tema usa essas variáveis, então mudar uma cor aqui muda o site
 * inteiro — inclusive os botões, selos e o carrinho do WooCommerce.
 *
 * @return string
 */
function sg_css_variaveis() {
	$mapa = array(
		'--azul-royal' => sg_opt( 'cor_logo', '#1E40AF' ),
		'--primary'    => sg_opt( 'cor_primaria', '#1E40AF' ),
		'--gold'       => sg_opt( 'cor_destaque', '#D6A32F' ),
		'--background' => sg_opt( 'cor_fundo', '#FCFAF5' ),
		'--foreground' => sg_opt( 'cor_texto', '#1B2A4A' ),
		'--whatsapp'   => sg_opt( 'cor_whatsapp', '#25D366' ),
	);

	$css = ':root{';
	foreach ( $mapa as $var => $valor ) {
		$valor = trim( (string) $valor );
		if ( $valor ) {
			$css .= $var . ':' . $valor . ';';
		}
	}

	$raio = (int) sg_opt( 'raio_cantos', 10 );
	$css .= '--raio:' . max( 0, min( 40, $raio ) ) . 'px;';

	$largura = (int) sg_opt( 'largura_site', 1400 );
	$css .= '--largura:' . max( 960, min( 1920, $largura ) ) . 'px;';

	$fonte = sg_opt( 'fonte_familia', 'Inter' );
	$css .= '--fonte-base:"' . esc_attr( $fonte ) . '",ui-sans-serif,system-ui,sans-serif;';
	$css .= '--fonte-titulo:"' . esc_attr( sg_opt( 'fonte_titulo', $fonte ) ) . '",ui-sans-serif,system-ui,sans-serif;';
	$css .= '}';

	return $css;
}

/**
 * Classes extras no body, para o CSS saber em que contexto está.
 *
 * @param array $classes Classes atuais.
 * @return array
 */
function sg_body_class( $classes ) {
	$classes[] = 'sg';
	if ( is_front_page() ) {
		$classes[] = 'sg-home';
	}
	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		$classes[] = 'sg-loja';
	}
	// Classe própria: não dependemos da que o WooCommerce põe sozinho.
	if ( function_exists( 'is_product' ) && is_product() ) {
		$classes[] = 'sg-produto';
	}
	return $classes;
}
add_filter( 'body_class', 'sg_body_class' );

/**
 * Remove emojis e o generator — menos requisição, menos informação exposta.
 */
function sg_limpeza() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
}
add_action( 'init', 'sg_limpeza' );

/**
 * Pré-conecta nos domínios de fonte, para a primeira pintura ser mais rápida.
 *
 * @param array  $urls  URLs já registradas.
 * @param string $rel   Relação do link.
 * @return array
 */
function sg_preconnect( $urls, $rel ) {
	if ( 'preconnect' === $rel ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => '' );
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'sg_preconnect', 10, 2 );
