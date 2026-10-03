<?php
/**
 * Cálculo de frete na página do produto.
 *
 * Usa as mesmas regras do checkout (zonas de entrega do WooCommerce, Melhor
 * Envio, transportadoras cadastradas à mão, frete grátis, retirada na loja…),
 * então o cliente vê aqui o mesmo valor que verá ao finalizar a compra.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carrega o JS da página do produto.
 */
function sg_produto_js() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	wp_enqueue_script( 'sg-produto', SG_URL . '/assets/js/produto.js', array(), SG_VERSAO, true );
	wp_localize_script( 'sg-produto', 'SG_PRODUTO', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'sg_frete' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'sg_produto_js', 30 );

/**
 * Descobre o estado (UF) pelo CEP, usando as faixas dos Correios.
 *
 * @param string $cep CEP só com números.
 * @return string Sigla da UF, ou vazio.
 */
function sg_cep_para_uf( $cep ) {
	$n      = (int) substr( $cep, 0, 5 );
	$faixas = array(
		'SP' => array( array( 1000, 19999 ) ),
		'RJ' => array( array( 20000, 28999 ) ),
		'ES' => array( array( 29000, 29999 ) ),
		'MG' => array( array( 30000, 39999 ) ),
		'BA' => array( array( 40000, 48999 ) ),
		'SE' => array( array( 49000, 49999 ) ),
		'PE' => array( array( 50000, 56999 ) ),
		'AL' => array( array( 57000, 57999 ) ),
		'PB' => array( array( 58000, 58999 ) ),
		'RN' => array( array( 59000, 59999 ) ),
		'CE' => array( array( 60000, 63999 ) ),
		'PI' => array( array( 64000, 64999 ) ),
		'MA' => array( array( 65000, 65999 ) ),
		'PA' => array( array( 66000, 68899 ) ),
		'AP' => array( array( 68900, 68999 ) ),
		'AM' => array( array( 69000, 69299 ), array( 69400, 69899 ) ),
		'RR' => array( array( 69300, 69399 ) ),
		'AC' => array( array( 69900, 69999 ) ),
		'DF' => array( array( 70000, 72799 ), array( 73000, 73699 ) ),
		'GO' => array( array( 72800, 72999 ), array( 73700, 76799 ) ),
		'RO' => array( array( 76800, 76999 ) ),
		'TO' => array( array( 77000, 77999 ) ),
		'MT' => array( array( 78000, 78899 ) ),
		'MS' => array( array( 79000, 79999 ) ),
		'PR' => array( array( 80000, 87999 ) ),
		'SC' => array( array( 88000, 89999 ) ),
		'RS' => array( array( 90000, 99999 ) ),
	);
	foreach ( $faixas as $uf => $partes ) {
		foreach ( $partes as $p ) {
			if ( $n >= $p[0] && $n <= $p[1] ) {
				return $uf;
			}
		}
	}
	return '';
}

/**
 * Responde ao "Calcular" da página do produto.
 */
function sg_ajax_frete() {
	check_ajax_referer( 'sg_frete', 'nonce' );

	$id  = isset( $_POST['produto'] ) ? absint( $_POST['produto'] ) : 0;
	$qtd = isset( $_POST['qtd'] ) ? max( 1, absint( $_POST['qtd'] ) ) : 1;
	$cep = isset( $_POST['cep'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['cep'] ) ) ) : '';

	if ( 8 !== strlen( $cep ) ) {
		wp_send_json_error( array( 'msg' => __( 'Informe um CEP com 8 dígitos.', 'sao-geronimo' ) ) );
	}

	$produto = $id ? wc_get_product( $id ) : false;
	if ( ! $produto ) {
		wp_send_json_error( array( 'msg' => __( 'Produto não encontrado.', 'sao-geronimo' ) ) );
	}

	$uf = sg_cep_para_uf( $cep );
	if ( ! $uf ) {
		wp_send_json_error( array( 'msg' => __( 'Não reconheci esse CEP. Confira os números.', 'sao-geronimo' ) ) );
	}

	if ( function_exists( 'wc_load_cart' ) && null === WC()->cart ) {
		wc_load_cart();
	}

	$total = (float) $produto->get_price() * $qtd;
	$chave = md5( 'sg-frete-' . $id );

	$pacote = array(
		'contents'        => array(
			$chave => array(
				'key'               => $chave,
				'product_id'        => $id,
				'variation_id'      => 0,
				'variation'         => array(),
				'quantity'          => $qtd,
				'data'              => $produto,
				'data_hash'         => wc_get_cart_item_data_hash( $produto ),
				'line_tax_data'     => array( 'subtotal' => array(), 'total' => array() ),
				'line_subtotal'     => $total,
				'line_subtotal_tax' => 0,
				'line_total'        => $total,
				'line_tax'          => 0,
			),
		),
		'contents_cost'   => $total,
		'applied_coupons' => array(),
		'user'            => array( 'ID' => get_current_user_id() ),
		'destination'     => array(
			'country'   => 'BR',
			'state'     => $uf,
			'postcode'  => $cep,
			'city'      => '',
			'address'   => '',
			'address_1' => '',
			'address_2' => '',
		),
		'cart_subtotal'   => $total,
	);

	$calculado = WC()->shipping()->calculate_shipping_for_package( $pacote );
	$opcoes    = array();

	if ( ! empty( $calculado['rates'] ) ) {
		foreach ( $calculado['rates'] as $taxa ) {
			$custo = (float) $taxa->get_cost() + array_sum( (array) $taxa->get_taxes() );
			$opcoes[] = array(
				'nome'  => wp_strip_all_tags( $taxa->get_label() ),
				'preco' => $custo > 0 ? sg_preco_texto( $custo ) : __( 'grátis', 'sao-geronimo' ),
				'valor' => $custo,
			);
		}
		usort( $opcoes, function ( $a, $b ) {
			return $a['valor'] <=> $b['valor'];
		} );
	}

	if ( ! $opcoes ) {
		$msg  = __( 'Ainda não temos uma opção de entrega automática para esse CEP.', 'sao-geronimo' );
		$zap  = sg_whatsapp_link( sprintf(
			/* translators: 1: produto, 2: CEP */
			__( 'Olá! Quero saber o frete do produto %1$s para o CEP %2$s.', 'sao-geronimo' ),
			$produto->get_name(),
			$cep
		) );
		wp_send_json_success( array(
			'opcoes' => array(),
			'aviso'  => $msg,
			'zap'    => $zap,
		) );
	}

	wp_send_json_success( array( 'opcoes' => $opcoes ) );
}
add_action( 'wp_ajax_sg_frete', 'sg_ajax_frete' );
add_action( 'wp_ajax_nopriv_sg_frete', 'sg_ajax_frete' );
