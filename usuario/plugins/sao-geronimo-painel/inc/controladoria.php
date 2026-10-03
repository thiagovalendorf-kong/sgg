<?php
/**
 * Controladoria: vendas, pedidos, produtos e clientes em uma tela só.
 *
 * Tudo sai do próprio WooCommerce — não guardamos nada em paralelo, então
 * os números batem sempre com a página de pedidos.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Períodos que a tela oferece.
 *
 * @return array
 */
function sgp_periodos() {
	return array(
		'hoje'    => __( 'Hoje', 'sao-geronimo-painel' ),
		'7'       => __( 'Últimos 7 dias', 'sao-geronimo-painel' ),
		'30'      => __( 'Últimos 30 dias', 'sao-geronimo-painel' ),
		'mes'     => __( 'Este mês', 'sao-geronimo-painel' ),
		'mes_ant' => __( 'Mês passado', 'sao-geronimo-painel' ),
		'90'      => __( 'Últimos 90 dias', 'sao-geronimo-painel' ),
		'ano'     => __( 'Este ano', 'sao-geronimo-painel' ),
	);
}

/**
 * Converte o período escolhido em datas.
 *
 * @param string $p Período.
 * @return array [inicio, fim, rotulo]
 */
function sgp_periodo_datas( $p ) {
	$hoje = current_time( 'Y-m-d' );
	switch ( $p ) {
		case 'hoje':
			return array( $hoje, $hoje );
		case '7':
			return array( gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $hoje ) ) ), $hoje );
		case '90':
			return array( gmdate( 'Y-m-d', strtotime( '-89 days', strtotime( $hoje ) ) ), $hoje );
		case 'mes':
			return array( gmdate( 'Y-m-01', strtotime( $hoje ) ), $hoje );
		case 'mes_ant':
			$i = gmdate( 'Y-m-01', strtotime( 'first day of last month', strtotime( $hoje ) ) );
			return array( $i, gmdate( 'Y-m-t', strtotime( $i ) ) );
		case 'ano':
			return array( gmdate( 'Y-01-01', strtotime( $hoje ) ), $hoje );
		case '30':
		default:
			return array( gmdate( 'Y-m-d', strtotime( '-29 days', strtotime( $hoje ) ) ), $hoje );
	}
}

/**
 * Busca os pedidos do período.
 *
 * @param string $inicio Data inicial.
 * @param string $fim    Data final.
 * @return array
 */
function sgp_pedidos( $inicio, $fim ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	return wc_get_orders( array(
		'limit'        => -1,
		'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending', 'wc-cancelled', 'wc-refunded' ),
		'date_created' => $inicio . '...' . $fim,
		'orderby'      => 'date',
		'order'        => 'DESC',
	) );
}

/**
 * Tela da controladoria.
 */
function sgp_tela_controladoria() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Controladoria', 'sao-geronimo-painel' ), __( 'Quanto entrou, o que saiu e quem comprou.', 'sao-geronimo-painel' ) );

	if ( ! function_exists( 'wc_get_orders' ) ) {
		echo '<div class="sgp-card sgp-card--alerta"><p>' . esc_html__( 'O WooCommerce precisa estar ativo para esta tela funcionar.', 'sao-geronimo-painel' ) . '</p></div></div>';
		return;
	}

	$p = isset( $_GET['periodo'] ) ? sanitize_key( wp_unslash( $_GET['periodo'] ) ) : '30'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( sgp_periodos()[ $p ] ) ) {
		$p = '30';
	}
	list( $inicio, $fim ) = sgp_periodo_datas( $p );

	// filtro de período
	echo '<form method="get" class="sgp-filtro-periodo"><input type="hidden" name="page" value="sg-controladoria">';
	echo '<label>' . esc_html__( 'Período:', 'sao-geronimo-painel' ) . ' <select name="periodo" onchange="this.form.submit()">';
	foreach ( sgp_periodos() as $k => $r ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $p, $k, false ), esc_html( $r ) );
	}
	echo '</select></label>';
	printf(
		'<span class="sgp-dica">%s %s %s %s</span>',
		esc_html__( 'de', 'sao-geronimo-painel' ),
		esc_html( date_i18n( 'd/m/Y', strtotime( $inicio ) ) ),
		esc_html__( 'a', 'sao-geronimo-painel' ),
		esc_html( date_i18n( 'd/m/Y', strtotime( $fim ) ) )
	);
	echo '</form>';

	$pedidos = sgp_pedidos( $inicio, $fim );

	$pagos     = array();
	$faturado  = 0.0;
	$frete     = 0.0;
	$cancelado = 0.0;
	$itens     = 0;
	$por_dia   = array();
	$produtos  = array();
	$clientes  = array();
	$status    = array();

	foreach ( $pedidos as $ped ) {
		$st = $ped->get_status();
		$status[ $st ] = ( isset( $status[ $st ] ) ? $status[ $st ] : 0 ) + 1;

		if ( in_array( $st, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			$cancelado += (float) $ped->get_total();
			continue;
		}

		$pagos[]  = $ped;
		$total     = (float) $ped->get_total();
		$faturado += $total;
		$frete    += (float) $ped->get_shipping_total();

		$dia = $ped->get_date_created() ? $ped->get_date_created()->date( 'Y-m-d' ) : $fim;
		$por_dia[ $dia ] = ( isset( $por_dia[ $dia ] ) ? $por_dia[ $dia ] : 0 ) + $total;

		foreach ( $ped->get_items() as $item ) {
			$q      = (int) $item->get_quantity();
			$itens += $q;
			$nome   = $item->get_name();
			if ( ! isset( $produtos[ $nome ] ) ) {
				$produtos[ $nome ] = array( 'q' => 0, 'v' => 0.0, 'id' => $item->get_product_id() );
			}
			$produtos[ $nome ]['q'] += $q;
			$produtos[ $nome ]['v'] += (float) $item->get_total();
		}

		$email = $ped->get_billing_email();
		if ( $email ) {
			if ( ! isset( $clientes[ $email ] ) ) {
				$clientes[ $email ] = array(
					'nome'  => trim( $ped->get_billing_first_name() . ' ' . $ped->get_billing_last_name() ),
					'tel'   => $ped->get_billing_phone(),
					'cidade' => $ped->get_billing_city() . '/' . $ped->get_billing_state(),
					'n'     => 0,
					'v'     => 0.0,
					'id'    => $ped->get_customer_id(),
				);
			}
			$clientes[ $email ]['n']++;
			$clientes[ $email ]['v'] += $total;
		}
	}

	$n_pagos = count( $pagos );
	$ticket  = $n_pagos ? $faturado / $n_pagos : 0;

	// ------------------------------------------------------------ números
	$cartoes = array(
		array( __( 'Faturamento', 'sao-geronimo-painel' ), wc_price( $faturado ), __( 'pedidos válidos do período', 'sao-geronimo-painel' ) ),
		array( __( 'Pedidos', 'sao-geronimo-painel' ), number_format_i18n( $n_pagos ), __( 'sem contar cancelados', 'sao-geronimo-painel' ) ),
		array( __( 'Ticket médio', 'sao-geronimo-painel' ), wc_price( $ticket ), __( 'quanto cada cliente gastou', 'sao-geronimo-painel' ) ),
		array( __( 'Peças vendidas', 'sao-geronimo-painel' ), number_format_i18n( $itens ), __( 'unidades somadas', 'sao-geronimo-painel' ) ),
		array( __( 'Frete cobrado', 'sao-geronimo-painel' ), wc_price( $frete ), __( 'já incluso no faturamento', 'sao-geronimo-painel' ) ),
		array( __( 'Cancelado/estornado', 'sao-geronimo-painel' ), wc_price( $cancelado ), __( 'não entra na conta acima', 'sao-geronimo-painel' ) ),
	);

	echo '<div class="sgp-numeros sgp-numeros--6">';
	foreach ( $cartoes as $c ) {
		echo '<div class="sgp-numero"><b>' . wp_kses_post( $c[1] ) . '</b><span>' . esc_html( $c[0] ) . '</span><em>' . esc_html( $c[2] ) . '</em></div>';
	}
	echo '</div>';

	// ------------------------------------------------------- gráfico simples
	if ( $por_dia ) {
		ksort( $por_dia );
		$max = max( $por_dia );
		echo '<section class="sgp-card"><h2>' . esc_html__( 'Vendas por dia', 'sao-geronimo-painel' ) . '</h2>';
		echo '<div class="sgp-barras">';
		foreach ( $por_dia as $dia => $v ) {
			$alt = $max > 0 ? max( 3, round( $v / $max * 100 ) ) : 3;
			printf(
				'<div class="sgp-barra" title="%s — %s"><span style="height:%d%%"></span><em>%s</em></div>',
				esc_attr( date_i18n( 'd/m', strtotime( $dia ) ) ),
				esc_attr( wp_strip_all_tags( wc_price( $v ) ) ),
				(int) $alt,
				esc_html( date_i18n( 'd/m', strtotime( $dia ) ) )
			);
		}
		echo '</div></section>';
	}

	// ------------------------------------------------------ situação
	if ( $status ) {
		$rotulos = wc_get_order_statuses();
		echo '<section class="sgp-card"><h2>' . esc_html__( 'Pedidos por situação', 'sao-geronimo-painel' ) . '</h2><div class="sgp-pilulas">';
		foreach ( $status as $st => $n ) {
			printf(
				'<a class="sgp-pilula sgp-pilula--%s" href="%s">%s <b>%d</b></a>',
				esc_attr( $st ),
				esc_url( admin_url( 'admin.php?page=wc-orders&status=wc-' . $st ) ),
				esc_html( isset( $rotulos[ 'wc-' . $st ] ) ? $rotulos[ 'wc-' . $st ] : $st ),
				(int) $n
			);
		}
		echo '</div></section>';
	}

	// -------------------------------------------------- mais vendidos
	if ( $produtos ) {
		uasort( $produtos, function ( $a, $b ) {
			return $b['v'] <=> $a['v'];
		} );
		echo '<section class="sgp-card"><h2>' . esc_html__( 'Produtos que mais venderam', 'sao-geronimo-painel' ) . '</h2>';
		echo '<table class="sgp-tabela"><thead><tr><th>' . esc_html__( 'Produto', 'sao-geronimo-painel' ) . '</th><th>'
			. esc_html__( 'Unidades', 'sao-geronimo-painel' ) . '</th><th>' . esc_html__( 'Valor', 'sao-geronimo-painel' ) . '</th></tr></thead><tbody>';
		foreach ( array_slice( $produtos, 0, 15, true ) as $nome => $d ) {
			printf(
				'<tr><td><a href="%s">%s</a></td><td>%d</td><td>%s</td></tr>',
				esc_url( get_edit_post_link( $d['id'] ) ),
				esc_html( $nome ),
				(int) $d['q'],
				wp_kses_post( wc_price( $d['v'] ) )
			);
		}
		echo '</tbody></table></section>';
	}

	// --------------------------------------------------- clientes
	if ( $clientes ) {
		uasort( $clientes, function ( $a, $b ) {
			return $b['v'] <=> $a['v'];
		} );
		echo '<section class="sgp-card"><h2>' . esc_html__( 'Clientes do período', 'sao-geronimo-painel' ) . '</h2>';
		echo '<table class="sgp-tabela"><thead><tr><th>' . esc_html__( 'Cliente', 'sao-geronimo-painel' ) . '</th><th>'
			. esc_html__( 'Contato', 'sao-geronimo-painel' ) . '</th><th>' . esc_html__( 'Cidade', 'sao-geronimo-painel' ) . '</th><th>'
			. esc_html__( 'Pedidos', 'sao-geronimo-painel' ) . '</th><th>' . esc_html__( 'Total', 'sao-geronimo-painel' ) . '</th></tr></thead><tbody>';
		foreach ( array_slice( $clientes, 0, 30, true ) as $email => $c ) {
			$zap = preg_replace( '/\D/', '', $c['tel'] );
			printf(
				'<tr><td><b>%s</b><br><small>%s</small></td><td>%s</td><td>%s</td><td>%d</td><td>%s</td></tr>',
				esc_html( $c['nome'] ? $c['nome'] : __( '(sem nome)', 'sao-geronimo-painel' ) ),
				esc_html( $email ),
				$zap ? '<a href="https://wa.me/55' . esc_attr( $zap ) . '" target="_blank" rel="noopener">' . esc_html( $c['tel'] ) . '</a>' : '—',
				esc_html( trim( $c['cidade'], '/' ) ),
				(int) $c['n'],
				wp_kses_post( wc_price( $c['v'] ) )
			);
		}
		echo '</tbody></table>';
		echo '<p class="sgp-dica">' . esc_html__( 'Mostrando os 30 que mais gastaram no período.', 'sao-geronimo-painel' ) . '</p>';
		echo '</section>';
	}

	// --------------------------------------------- últimos pedidos
	echo '<section class="sgp-card"><h2>' . esc_html__( 'Últimos pedidos', 'sao-geronimo-painel' ) . '</h2>';
	if ( ! $pedidos ) {
		echo '<p class="sgp-dica">' . esc_html__( 'Nenhum pedido neste período.', 'sao-geronimo-painel' ) . '</p>';
	} else {
		echo '<table class="sgp-tabela"><thead><tr><th>#</th><th>' . esc_html__( 'Data', 'sao-geronimo-painel' ) . '</th><th>'
			. esc_html__( 'Cliente', 'sao-geronimo-painel' ) . '</th><th>' . esc_html__( 'Situação', 'sao-geronimo-painel' ) . '</th><th>'
			. esc_html__( 'Pagamento', 'sao-geronimo-painel' ) . '</th><th>' . esc_html__( 'Total', 'sao-geronimo-painel' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( array_slice( $pedidos, 0, 25 ) as $ped ) {
			printf(
				'<tr><td>%d</td><td>%s</td><td>%s</td><td><span class="sgp-pilula sgp-pilula--%s">%s</span></td><td>%s</td><td>%s</td><td><a class="button button-small" href="%s">%s</a></td></tr>',
				(int) $ped->get_order_number(),
				esc_html( $ped->get_date_created() ? $ped->get_date_created()->date_i18n( 'd/m/Y H:i' ) : '—' ),
				esc_html( trim( $ped->get_billing_first_name() . ' ' . $ped->get_billing_last_name() ) ),
				esc_attr( $ped->get_status() ),
				esc_html( wc_get_order_status_name( $ped->get_status() ) ),
				esc_html( $ped->get_payment_method_title() ),
				wp_kses_post( wc_price( $ped->get_total() ) ),
				esc_url( $ped->get_edit_order_url() ),
				esc_html__( 'Abrir', 'sao-geronimo-painel' )
			);
		}
		echo '</tbody></table>';
	}
	echo '</section>';

	// --------------------------------------------- estoque baixo
	sgp_estoque_baixo();

	echo '</div>';
}

/**
 * Avisa sobre produtos acabando.
 */
function sgp_estoque_baixo() {
	global $wpdb;
	$limite = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

	$ids = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT p.ID
		   FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} ms ON ms.post_id = p.ID AND ms.meta_key = '_manage_stock' AND ms.meta_value = 'yes'
		   JOIN {$wpdb->postmeta} st ON st.post_id = p.ID AND st.meta_key = '_stock'
		  WHERE p.post_type = 'product'
		    AND p.post_status = 'publish'
		    AND st.meta_value IS NOT NULL
		    AND CAST(st.meta_value AS SIGNED) <= %d
		  ORDER BY CAST(st.meta_value AS SIGNED) ASC
		  LIMIT 20",
		$limite
	) );

	$baixos = array();
	foreach ( (array) $ids as $id ) {
		$p = wc_get_product( $id );
		if ( $p ) {
			$baixos[] = $p;
		}
	}

	if ( ! $baixos ) {
		return;
	}

	echo '<section class="sgp-card sgp-card--alerta"><h2>' . esc_html__( 'Produtos acabando', 'sao-geronimo-painel' ) . '</h2>';
	echo '<table class="sgp-tabela"><tbody>';
	foreach ( $baixos as $p ) {
		printf(
			'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td></tr>',
			esc_url( get_edit_post_link( $p->get_id() ) ),
			esc_html( $p->get_name() ),
			esc_html( $p->get_sku() ),
			sprintf(
				/* translators: quantidade */
				esc_html( _n( '%d unidade', '%d unidades', (int) $p->get_stock_quantity(), 'sao-geronimo-painel' ) ),
				(int) $p->get_stock_quantity()
			)
		);
	}
	echo '</tbody></table></section>';
}
