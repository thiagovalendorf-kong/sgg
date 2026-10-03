<?php
/**
 * Busca com sugestões ao vivo (AJAX).
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tira acentos e deixa minúsculo, para comparar.
 *
 * @param string $txt Texto.
 * @return string
 */
function sg_sem_acento( $txt ) {
	$txt = remove_accents( (string) $txt );
	return strtolower( $txt );
}

/**
 * Destaca no nome os pedaços que o cliente digitou.
 *
 * @param string $nome   Nome do produto.
 * @param array  $termos Termos buscados.
 * @return string
 */
function sg_destaca( $nome, $termos ) {
	$saida = esc_html( $nome );
	$plano = sg_sem_acento( $saida );
	$faixas = array();

	foreach ( $termos as $t ) {
		if ( strlen( $t ) < 2 ) {
			continue;
		}
		$pos = 0;
		while ( ( $pos = strpos( $plano, $t, $pos ) ) !== false ) {
			$faixas[] = array( $pos, $pos + strlen( $t ) );
			$pos     += strlen( $t );
		}
	}

	if ( ! $faixas ) {
		return $saida;
	}

	sort( $faixas );
	$unidas = array( array_shift( $faixas ) );
	foreach ( $faixas as $f ) {
		$u = count( $unidas ) - 1;
		if ( $f[0] <= $unidas[ $u ][1] ) {
			$unidas[ $u ][1] = max( $unidas[ $u ][1], $f[1] );
		} else {
			$unidas[] = $f;
		}
	}

	$res = '';
	$ant = 0;
	foreach ( $unidas as $f ) {
		$res .= substr( $saida, $ant, $f[0] - $ant ) . '<mark>' . substr( $saida, $f[0], $f[1] - $f[0] ) . '</mark>';
		$ant  = $f[1];
	}
	return $res . substr( $saida, $ant );
}

/**
 * Versão do cache da busca: sobe quando um produto muda, e as respostas antigas
 * simplesmente deixam de ser usadas (sem varrer a tabela de opções).
 *
 * @return int
 */
function sg_busca_versao() {
	return (int) get_option( 'sg_busca_ver', 1 );
}

/**
 * Limite simples por visitante: no máximo 40 pedidos por minuto.
 * Conta só no cache de objeto quando existe; sem ele, usa um transient curto por IP.
 *
 * @return bool true se pode seguir.
 */
function sg_busca_pode() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
	$chave = 'sg_rl_' . md5( $ip );
	$n     = (int) get_transient( $chave );
	if ( $n >= 40 ) {
		return false;
	}
	set_transient( $chave, $n + 1, MINUTE_IN_SECONDS );
	return true;
}

/**
 * Responde à busca ao vivo.
 *
 * Os dados são públicos e a rota só lê, então ela não exige nonce: o nonce de um
 * visitante anônimo é igual para todos e, em página com cache, vence e devolve 403.
 * A proteção contra abuso é o limite de tamanho do termo e o limite por IP.
 */
function sg_ajax_busca() {
	$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$q = mb_substr( trim( $q ), 0, 60 );
	if ( mb_strlen( $q ) < 2 ) {
		wp_send_json_success( array( 'itens' => array(), 'total' => 0 ) );
	}

	if ( ! function_exists( 'wc_get_products' ) ) {
		wp_send_json_success( array( 'itens' => array(), 'total' => 0 ) );
	}

	if ( ! sg_busca_pode() ) {
		wp_send_json_error( array( 'msg' => 'muitas buscas' ), 429 );
	}

	$limite = max( 3, min( 12, (int) sg_opt( 'busca_sugestoes', 7 ) ) );
	$chave  = 'sg_busca_' . md5( $q . '|' . $limite . '|' . get_locale() . '|' . sg_busca_versao() );

	// Só guarda resposta quando existe cache de objeto de verdade (Redis, Memcached);
	// sem ele cada termo novo viraria uma linha na tabela de opções.
	$guarda = wp_using_ext_object_cache();
	if ( $guarda ) {
		$cache = get_transient( $chave );
		if ( false !== $cache ) {
			wp_send_json_success( $cache );
		}
	}

	// Busca por nome/descrição e, em paralelo, por SKU.
	$ids = wc_get_products( array(
		'status' => 'publish',
		'limit'  => 60,
		's'      => $q,
		'return' => 'ids',
	) );

	$por_sku = wc_get_products( array(
		'status' => 'publish',
		'limit'  => 10,
		'sku'    => $q,
		'return' => 'ids',
	) );

	$ids   = array_values( array_unique( array_merge( (array) $por_sku, (array) $ids ) ) );
	$total = count( $ids );

	$termos = array_filter( explode( ' ', sg_sem_acento( $q ) ) );
	$itens  = array();

	foreach ( array_slice( $ids, 0, $limite ) as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}

		$img = '';
		$tid = $p->get_image_id();
		if ( $tid ) {
			$src = wp_get_attachment_image_src( $tid, 'sg-mini' );
			$img = $src ? $src[0] : '';
		}

		if ( 'yes' === get_post_meta( $id, '_sg_sob_consulta', true ) ) {
			$preco = esc_html__( 'sob consulta', 'sao-geronimo' );
		} else {
			$preco = wp_strip_all_tags( $p->get_price_html() );
		}

		$itens[] = array(
			'nome'      => $p->get_name(),
			'nome_html' => sg_destaca( $p->get_name(), $termos ),
			'sku'       => $p->get_sku(),
			'preco'     => $preco,
			'img'       => $img,
			'url'       => get_permalink( $id ),
		);
	}

	$dados = array( 'itens' => $itens, 'total' => $total );
	if ( $guarda ) {
		set_transient( $chave, $dados, 10 * MINUTE_IN_SECONDS );
	}

	wp_send_json_success( $dados );
}
add_action( 'wp_ajax_sg_busca', 'sg_ajax_busca' );
add_action( 'wp_ajax_nopriv_sg_busca', 'sg_ajax_busca' );

/**
 * Limpa o cache da busca quando um produto muda.
 *
 * @param int $id ID do post.
 */
function sg_limpa_cache_busca( $id ) {
	if ( 'product' !== get_post_type( $id ) ) {
		return;
	}
	update_option( 'sg_busca_ver', sg_busca_versao() + 1, false );
}
add_action( 'save_post', 'sg_limpa_cache_busca' );
add_action( 'woocommerce_update_product', 'sg_limpa_cache_busca' );

/**
 * Passa as sugestões do painel para o JavaScript.
 *
 * @param array $dados Dados já enfileirados.
 * @return array
 */
function sg_busca_sugestoes_js() {
	$txt = (string) sg_opt( 'busca_sugestoes_lista', 'Incenso, São Jerônimo, Oxum, Vela, Sineta, Tarô, Palo Santo, Difusor' );
	$lista = array_values( array_filter( array_map( 'trim', explode( ',', $txt ) ) ) );
	wp_localize_script( 'sg-site', 'SG_SUG', array() ); // mantém compatibilidade
	wp_add_inline_script(
		'sg-site',
		'window.SG_WP = Object.assign(window.SG_WP || {}, { sugestoes: ' . wp_json_encode( $lista ) . ' });',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'sg_busca_sugestoes_js', 20 );

/**
 * Quem busca pela referência exata vai direto para o produto.
 *
 * Mexer na cláusula SQL da busca é frágil e quebra com acentos e aspas;
 * este atalho resolve o caso que importa sem tocar na consulta.
 */
function sg_busca_sku_atalho() {
	if ( is_admin() || ! is_search() || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}

	$termo = trim( get_search_query() );
	if ( ! $termo || preg_match( '/\s/', $termo ) ) {
		return;
	}

	$id = wc_get_product_id_by_sku( $termo );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		wp_safe_redirect( get_permalink( $id ) );
		exit;
	}
}
add_action( 'template_redirect', 'sg_busca_sku_atalho', 5 );
