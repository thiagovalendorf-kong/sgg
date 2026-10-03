<?php
/**
 * Telas do painel São Gerônimo.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capacidade exigida para mexer no painel.
 *
 * @return string
 */
function sgp_cap() {
	return apply_filters( 'sgp_capacidade', 'sgp_gerenciar' );
}

/**
 * Registra os menus do admin.
 */
function sgp_menu() {
	$cap = sgp_cap();

	add_menu_page(
		__( 'Painel São Gerônimo', 'sao-geronimo-painel' ),
		sgp_marca_nome(),
		$cap,
		'sg-painel',
		'sgp_tela_inicio',
		'dashicons-store',
		3
	);

	add_submenu_page( 'sg-painel', __( 'Visão geral', 'sao-geronimo-painel' ), __( 'Visão geral', 'sao-geronimo-painel' ), $cap, 'sg-painel', 'sgp_tela_inicio' );

	foreach ( sgp_estrutura() as $id => $aba ) {
		add_submenu_page(
			'sg-painel',
			$aba['titulo'],
			$aba['titulo'],
			$cap,
			'sg-painel-' . $id,
			'sgp_tela_aba'
		);
	}

	add_submenu_page( 'sg-painel', __( 'SEO, GEO e AEO', 'sao-geronimo-painel' ), __( 'SEO, GEO e AEO', 'sao-geronimo-painel' ), $cap, 'sg-seo', 'sgp_tela_seo' );
	add_submenu_page( 'sg-painel', __( 'Controladoria', 'sao-geronimo-painel' ), __( 'Controladoria', 'sao-geronimo-painel' ), $cap, 'sg-controladoria', 'sgp_tela_controladoria' );
	add_submenu_page( 'sg-painel', __( 'Frete e entrega', 'sao-geronimo-painel' ), __( 'Frete e entrega', 'sao-geronimo-painel' ), $cap, 'sg-frete', 'sgp_tela_frete' );
	add_submenu_page( 'sg-painel', __( 'Pagamentos', 'sao-geronimo-painel' ), __( 'Pagamentos', 'sao-geronimo-painel' ), $cap, 'sg-pagamentos', 'sgp_tela_pagamentos' );
	add_submenu_page( 'sg-painel', __( 'Importar produtos', 'sao-geronimo-painel' ), __( 'Importar produtos', 'sao-geronimo-painel' ), 'manage_options', 'sg-importar', 'sgp_tela_importar' );
	add_submenu_page( 'sg-painel', __( 'Primeiros passos', 'sao-geronimo-painel' ), __( 'Primeiros passos', 'sao-geronimo-painel' ), 'manage_options', 'sg-assistente', 'sgp_tela_assistente' );
}
add_action( 'admin_menu', 'sgp_menu' );

/**
 * Nome da marca que aparece no admin (white label).
 *
 * @return string
 */
function sgp_marca_nome() {
	$n = get_option( 'sgp_marca_nome', '' );
	return $n ? $n : __( 'São Gerônimo', 'sao-geronimo-painel' );
}

/**
 * Cabeçalho comum das telas.
 *
 * @param string $titulo Título.
 * @param string $resumo Linha de apoio.
 */
function sgp_cabecalho( $titulo, $resumo = '' ) {
	?>
	<div class="sgp-topo">
		<div>
			<h1><?php echo esc_html( $titulo ); ?></h1>
			<?php if ( $resumo ) : ?>
				<p class="sgp-resumo"><?php echo esc_html( $resumo ); ?></p>
			<?php endif; ?>
		</div>
		<a class="button button-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'Ver o site', 'sao-geronimo-painel' ); ?> ↗
		</a>
	</div>
	<?php
}

/**
 * Tela de abertura.
 */
function sgp_tela_inicio() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	$atalhos = array(
		array( 'admin-appearance', __( 'Mudar cores e logo', 'sao-geronimo-painel' ), __( 'Troque a identidade visual da loja inteira.', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel-identidade' ),
		array( 'admin-home', __( 'Montar a página inicial', 'sao-geronimo-painel' ), __( 'Banners, vitrines, textos e a ordem dos blocos.', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel-home' ),
		array( 'products', __( 'Cadastrar um produto', 'sao-geronimo-painel' ), __( 'Formulário curto: foto, preço e pronto.', 'sao-geronimo-painel' ), 'admin.php?page=sg-produto-facil' ),
		array( 'cart', __( 'Ver os pedidos', 'sao-geronimo-painel' ), __( 'Quem comprou, quanto pagou e o que falta enviar.', 'sao-geronimo-painel' ), 'admin.php?page=sg-controladoria' ),
		array( 'search', __( 'Aparecer no Google', 'sao-geronimo-painel' ), __( 'SEO, dados do negócio e perguntas frequentes.', 'sao-geronimo-painel' ), 'admin.php?page=sg-seo' ),
		array( 'phone', __( 'Contato e redes', 'sao-geronimo-painel' ), __( 'WhatsApp, telefone, endereço e redes sociais.', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel-contato' ),
	);

	echo '<div class="wrap sgp">';
	sgp_cabecalho(
		/* translators: nome da loja */
		sprintf( __( 'Bem-vindo ao painel da %s', 'sao-geronimo-painel' ), sgp_marca_nome() ),
		__( 'Tudo o que aparece no site se muda por aqui. Escolha por onde começar.', 'sao-geronimo-painel' )
	);

	sgp_resumo_numeros();

	echo '<div class="sgp-atalhos">';
	foreach ( $atalhos as $a ) {
		printf(
			'<a class="sgp-atalho" href="%s"><span class="dashicons dashicons-%s"></span><b>%s</b><span>%s</span></a>',
			esc_url( admin_url( $a[3] ) ), esc_attr( $a[0] ), esc_html( $a[1] ), esc_html( $a[2] )
		);
	}
	echo '</div>';

	sgp_pendencias();
	echo '</div>';
}

/**
 * Quatro números-resumo no topo.
 */
function sgp_resumo_numeros() {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return;
	}

	$inicio = gmdate( 'Y-m-d', strtotime( 'first day of this month' ) );
	$pedidos = wc_get_orders( array(
		'limit'        => -1,
		'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
		'date_created' => '>=' . $inicio,
	) );

	$total = 0;
	foreach ( $pedidos as $p ) {
		$total += (float) $p->get_total();
	}

	$nums = array(
		array( __( 'Vendas no mês', 'sao-geronimo-painel' ), wc_price( $total ) ),
		array( __( 'Pedidos no mês', 'sao-geronimo-painel' ), count( $pedidos ) ),
		array( __( 'Produtos publicados', 'sao-geronimo-painel' ), wp_count_posts( 'product' )->publish ),
		array( __( 'Clientes cadastrados', 'sao-geronimo-painel' ), count_users()['avail_roles']['customer'] ?? 0 ),
	);

	echo '<div class="sgp-numeros">';
	foreach ( $nums as $n ) {
		echo '<div class="sgp-numero"><b>' . wp_kses_post( $n[1] ) . '</b><span>' . esc_html( $n[0] ) . '</span></div>';
	}
	echo '</div>';
}

/**
 * Lista do que ainda falta configurar.
 */
function sgp_pendencias() {
	$faltam = array();

	if ( ! class_exists( 'WooCommerce' ) ) {
		$faltam[] = array( __( 'O WooCommerce ainda não está ativo — sem ele não há loja.', 'sao-geronimo-painel' ), 'admin.php?page=sg-assistente' );
	}
	if ( ! sg_opt( 'whatsapp' ) ) {
		$faltam[] = array( __( 'Falta informar o WhatsApp da loja.', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel-contato' );
	}
	if ( ! sg_opt( 'banners' ) ) {
		$faltam[] = array( __( 'Nenhum banner cadastrado na página inicial.', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel-home' );
	}
	if ( ! get_option( 'sgp_seo', array() ) ) {
		$faltam[] = array( __( 'O SEO do site ainda não foi preenchido.', 'sao-geronimo-painel' ), 'admin.php?page=sg-seo' );
	}
	if ( 'publish' === get_option( 'blog_public' ) || '0' === get_option( 'blog_public' ) ) {
		$faltam[] = array( __( 'O site está pedindo aos buscadores para NÃO indexar. Corrija em Configurações → Leitura.', 'sao-geronimo-painel' ), 'options-reading.php' );
	}

	if ( ! $faltam ) {
		return;
	}

	echo '<div class="sgp-card sgp-card--alerta"><h2>' . esc_html__( 'Ainda falta', 'sao-geronimo-painel' ) . '</h2><ul class="sgp-pend">';
	foreach ( $faltam as $f ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( admin_url( $f[1] ) ), esc_html( $f[0] ) );
	}
	echo '</ul></div>';
}

/**
 * Tela de uma aba de configurações.
 */
function sgp_tela_aba() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	$pagina = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$id     = str_replace( 'sg-painel-', '', $pagina );
	$abas   = sgp_estrutura();

	if ( ! isset( $abas[ $id ] ) ) {
		wp_die( esc_html__( 'Seção não encontrada.', 'sao-geronimo-painel' ) );
	}

	$aba    = $abas[ $id ];
	$opcoes = get_option( 'sg_opcoes', array() );

	echo '<div class="wrap sgp">';
	sgp_cabecalho( $aba['titulo'], isset( $aba['resumo'] ) ? $aba['resumo'] : '' );
	sgp_abas( $id );

	echo '<form method="post" action="" class="sgp-form">';
	wp_nonce_field( 'sgp_salvar', 'sgp_nonce' );
	echo '<input type="hidden" name="sgp_aba" value="' . esc_attr( $id ) . '">';

	foreach ( $aba['secoes'] as $sid => $sec ) {
		echo '<section class="sgp-card" id="sec-' . esc_attr( $sid ) . '">';
		echo '<h2>' . esc_html( $sec['titulo'] ) . '</h2>';
		if ( ! empty( $sec['resumo'] ) ) {
			echo '<p class="sgp-resumo">' . esc_html( $sec['resumo'] ) . '</p>';
		}
		foreach ( $sec['campos'] as $chave => $def ) {
			$valor = isset( $opcoes[ $chave ] ) ? $opcoes[ $chave ] : ( isset( $def['padrao'] ) ? $def['padrao'] : '' );
			sgp_campo( $chave, $def, $valor );
		}
		echo '</section>';
	}

	echo '<div class="sgp-salvar"><button type="submit" class="button button-primary button-hero">' . esc_html__( 'Salvar alterações', 'sao-geronimo-painel' ) . '</button>';
	echo '<span class="sgp-salvar__dica">' . esc_html__( 'As mudanças aparecem no site na hora.', 'sao-geronimo-painel' ) . '</span></div>';
	echo '</form></div>';
}

/**
 * Navegação entre as abas.
 *
 * @param string $atual Aba aberta.
 */
function sgp_abas( $atual ) {
	echo '<nav class="sgp-abas">';
	foreach ( sgp_estrutura() as $id => $aba ) {
		printf(
			'<a class="sgp-aba%s" href="%s"><span class="dashicons dashicons-%s"></span>%s</a>',
			$id === $atual ? ' on' : '',
			esc_url( admin_url( 'admin.php?page=sg-painel-' . $id ) ),
			esc_attr( isset( $aba['icone'] ) ? $aba['icone'] : 'admin-generic' ),
			esc_html( $aba['titulo'] )
		);
	}
	echo '</nav>';
}

/**
 * Grava as opções.
 */
function sgp_salvar() {
	if ( ! isset( $_POST['sgp_nonce'], $_POST['sgp_aba'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_nonce'] ) ), 'sgp_salvar' ) ) {
		return;
	}
	if ( ! current_user_can( sgp_cap() ) ) {
		return;
	}

	$id   = sanitize_key( wp_unslash( $_POST['sgp_aba'] ) );
	$abas = sgp_estrutura();
	if ( ! isset( $abas[ $id ] ) ) {
		return;
	}

	$opcoes = get_option( 'sg_opcoes', array() );
	if ( ! is_array( $opcoes ) ) {
		$opcoes = array();
	}

	$enviado = isset( $_POST['sg'] ) ? wp_unslash( $_POST['sg'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	foreach ( $abas[ $id ]['secoes'] as $sec ) {
		foreach ( $sec['campos'] as $chave => $def ) {
			if ( 'aviso' === ( isset( $def['tipo'] ) ? $def['tipo'] : '' ) ) {
				continue;
			}
			// Caixas desmarcadas não são enviadas pelo navegador.
			$bruto = isset( $enviado[ $chave ] ) ? $enviado[ $chave ] : ( 'liga' === ( isset( $def['tipo'] ) ? $def['tipo'] : '' ) ? 'nao' : '' );
			$opcoes[ $chave ] = sgp_limpa( $def, $bruto );
		}
	}

	update_option( 'sg_opcoes', $opcoes );

	do_action( 'sgp_opcoes_salvas', $id, $opcoes );

	wp_safe_redirect( add_query_arg( array( 'page' => 'sg-painel-' . $id, 'sgp' => 'ok' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'sgp_salvar' );

/**
 * Aviso de "salvo".
 */
function sgp_aviso_salvo() {
	if ( ! isset( $_GET['sgp'] ) || 'ok' !== $_GET['sgp'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Pronto, salvamos. Dê um F5 no site para ver.', 'sao-geronimo-painel' ) . '</p></div>';
}
add_action( 'admin_notices', 'sgp_aviso_salvo' );

/**
 * CSS e JS do painel.
 *
 * @param string $hook Tela atual.
 */
function sgp_admin_assets( $hook ) {
	$nossa = ( false !== strpos( $hook, 'sg-painel' ) || false !== strpos( $hook, 'sg-seo' )
		|| false !== strpos( $hook, 'sg-controladoria' ) || false !== strpos( $hook, 'sg-importar' )
		|| false !== strpos( $hook, 'sg-assistente' ) || false !== strpos( $hook, 'sg-frete' )
		|| false !== strpos( $hook, 'sg-pagamentos' ) || false !== strpos( $hook, 'sg-produto-facil' ) );

	wp_enqueue_style( 'sgp-admin', SGP_URL . 'assets/painel.css', array(), SGP_VERSAO );

	if ( ! $nossa && ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'sgp-admin', SGP_URL . 'assets/painel.js', array(), SGP_VERSAO, true );
	wp_localize_script( 'sgp-admin', 'SGP', array(
		'escolher' => __( 'Escolher imagem', 'sao-geronimo-painel' ),
		'usar'     => __( 'Usar esta imagem', 'sao-geronimo-painel' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'sgp_admin_assets' );
