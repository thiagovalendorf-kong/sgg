<?php
/**
 * Tela de edição do produto (painel): aba "São Gerônimo" e campos extras.
 *
 * Só roda no painel. A parte que aparece no site fica em inc/woocommerce.php.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra a aba extra na edição do produto.
 *
 * @param array $abas Abas atuais.
 * @return array
 */
function sg_wc_aba_produto( $abas ) {
	$abas['sg'] = array(
		'label'    => __( 'São Gerônimo', 'sao-geronimo' ),
		'target'   => 'sg_dados_produto',
		'class'    => array(),
		'priority' => 68,
	);
	return $abas;
}
add_filter( 'woocommerce_product_data_tabs', 'sg_wc_aba_produto' );

/**
 * Campos da aba extra.
 */
function sg_wc_campos_produto() {
	echo '<div id="sg_dados_produto" class="panel woocommerce_options_panel">';

	woocommerce_wp_text_input( array(
		'id'          => '_sg_dimensoes',
		'label'       => __( 'Dimensões por extenso', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: 20 cm de altura × 8 cm de base', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Como a medida aparece para o cliente. Se deixar vazio, usamos a medida numérica da aba Entrega.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_marca',
		'label'       => __( 'Marca / fabricante', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Aparece embaixo do nome do produto e nos dados estruturados do Google.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'       => '_sg_conteudo',
		'label'    => __( 'Conteúdo da embalagem', 'sao-geronimo' ),
		'desc_tip' => true,
		'description' => __( 'ex.: 10 varetas, 30 g, 1 unidade.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'          => '_sg_composicao',
		'label'       => __( 'Composição', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Vira um bloco próprio na página do produto.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'          => '_sg_modo_usar',
		'label'       => __( 'Modo de usar', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Vira um bloco próprio na página do produto.', 'sao-geronimo' ),
	) );

	woocommerce_wp_checkbox( array(
		'id'          => '_sg_sob_consulta',
		'label'       => __( 'Preço sob consulta', 'sao-geronimo' ),
		'description' => __( 'Esconde o preço e troca o botão por "Consultar pelo WhatsApp".', 'sao-geronimo' ),
	) );

	echo '</div>';
}
add_action( 'woocommerce_product_data_panels', 'sg_wc_campos_produto' );

/**
 * Salva os campos extras.
 *
 * @param int $id ID do produto.
 */
function sg_wc_salva_produto( $id ) {
	$textos = array( '_sg_dimensoes', '_sg_marca', '_sg_conteudo' );
	foreach ( $textos as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_text_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	$areas = array( '_sg_composicao', '_sg_modo_usar' );
	foreach ( $areas as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_textarea_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	update_post_meta( $id, '_sg_sob_consulta', isset( $_POST['_sg_sob_consulta'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification
}
add_action( 'woocommerce_process_product_meta', 'sg_wc_salva_produto' );
