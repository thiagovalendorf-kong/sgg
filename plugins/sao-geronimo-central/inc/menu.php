<?php
/**
 * Menu enxuto, central "Minha Loja" e página inicial do painel.
 *
 * O WordPress tem dezenas de menus. Aqui ficam só os que se usa todo dia; todo
 * o resto vira um cartão na página "Minha Loja" (nada fica inacessível).
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menus que ficam à vista no topo (o resto vai para dentro de "Gestão").
 *
 * @return array
 */
function sgc_menus_fixos() {
	return array( 'index.php', 'sgc-central', 'sgc-loja', 'edit.php', 'themes.php', 'options-general.php' );
}

/**
 * Endereço de uma entrada de menu.
 *
 * @param string $slug Slug do menu.
 * @return string
 */
function sgc_url_menu( $slug ) {
	if ( preg_match( '/\.php($|\?)/', $slug ) ) {
		return admin_url( $slug );
	}
	return admin_url( 'admin.php?page=' . $slug );
}

/**
 * Título limpo (sem contadores e tags).
 *
 * @param string $t Título do menu.
 * @return string
 */
function sgc_titulo_limpo( $t ) {
	return trim( wp_strip_all_tags( preg_replace( '#<span.*?</span>#is', '', (string) $t ) ) );
}

/**
 * Endereço da lista de pedidos (muda conforme a versão do WooCommerce).
 *
 * @return string
 */
function sgc_url_pedidos() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	return admin_url( 'edit.php?post_type=shop_order' );
}

/**
 * Menu "Gestão": o único lugar onde ficam todas as abas nativas do WordPress.
 */
function sgc_menu_loja() {
	add_menu_page( 'Gestão', 'Gestão', 'edit_posts', 'sgc-loja', 'sgc_pagina_loja', 'dashicons-store', 3 );
	add_submenu_page( 'sgc-loja', 'Visão geral', 'Visão geral', 'edit_posts', 'sgc-loja', 'sgc_pagina_loja' );
}
add_action( 'admin_menu', 'sgc_menu_loja' );

/**
 * Leva as abas nativas para dentro de "Gestão". Roda por último, para pegar
 * também os menus criados por outros plugins.
 */
function sgc_recolher_menus() {
	global $menu, $submenu;

	$grupos = array();   // slug do menu => dados
	foreach ( (array) $menu as $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( ! $slug || false !== strpos( (string) ( $item[4] ?? '' ), 'wp-menu-separator' ) || in_array( $slug, sgc_menus_fixos(), true ) ) {
			continue;
		}
		$titulo = sgc_titulo_limpo( $item[0] );
		if ( '' === $titulo ) {
			continue;
		}
		$filhos = array();
		foreach ( (array) ( $submenu[ $slug ] ?? array() ) as $sub ) {
			$t = sgc_titulo_limpo( $sub[0] );
			if ( '' !== $t ) {
				$filhos[] = array( 'titulo' => $t, 'slug' => $sub[2], 'cap' => $sub[1], 'url' => sgc_url_menu( $sub[2] ) );
			}
		}
		$grupos[ $slug ] = array( 'titulo' => $titulo, 'slug' => $slug, 'cap' => $item[1], 'url' => sgc_url_menu( $slug ), 'filhos' => $filhos );
	}
	$GLOBALS['sgc_grupos'] = $grupos;

	// Ordem: o que mais se usa vem primeiro.
	$ordem = array( 'edit.php?post_type=product', 'woocommerce', 'wc-admin&path=/customers', 'edit.php?post_type=shop_order', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'users.php', 'plugins.php', 'tools.php' );
	uksort( $grupos, function ( $a, $b ) use ( $ordem ) {
		$ia = array_search( $a, $ordem, true );
		$ib = array_search( $b, $ordem, true );
		$ia = false === $ia ? 99 : $ia;
		$ib = false === $ib ? 99 : $ib;
		return $ia <=> $ib;
	} );

	foreach ( $grupos as $slug => $g ) {
		// Produtos e WooCommerce mostram suas telas principais; os demais, só a entrada.
		$entradas = in_array( $slug, array( 'edit.php?post_type=product', 'woocommerce', 'plugins.php' ), true ) && $g['filhos'] ? $g['filhos'] : array( $g );
		foreach ( $entradas as $e ) {
			if ( in_array( $e['titulo'], array( 'Início', 'Home', 'Extensões', 'Extensions', 'Marketing' ), true ) ) {
				continue;
			}
			$rotulo = $e['titulo'];
			if ( 'woocommerce' === $slug && in_array( $rotulo, array( 'Configurações', 'Settings' ), true ) ) {
				$rotulo = 'Configurações da loja (WooCommerce)';
			}
			if ( 'edit.php?post_type=product' === $slug && 'Todos os produtos' !== $rotulo && 'All Products' !== $rotulo ) {
				$rotulo = '— ' . $rotulo;
			}
			add_submenu_page( 'sgc-loja', $rotulo, $rotulo, $e['cap'], preg_match( '/\.php/', $e['slug'] ) ? $e['slug'] : 'admin.php?page=' . $e['slug'] );
		}
		remove_menu_page( $slug );
	}

	add_submenu_page( 'sgc-loja', 'Pagamento e frete', 'Pagamento e frete', 'manage_options', 'sgc-integracoes', 'sgc_pagina_integracoes' );

	// Posts vira "Blog".
	foreach ( (array) $menu as $pos => $item ) {
		if ( isset( $item[2] ) && 'edit.php' === $item[2] ) {
			$menu[ $pos ][0] = 'Blog'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}
add_action( 'admin_menu', 'sgc_recolher_menus', 9999 );

/**
 * Quando a pessoa está numa tela que foi para dentro de "Gestão", o menu
 * "Gestão" fica marcado como o atual.
 *
 * @param string $pai Menu pai atual.
 * @return string
 */
function sgc_menu_atual( $pai ) {
	$grupos = isset( $GLOBALS['sgc_grupos'] ) ? $GLOBALS['sgc_grupos'] : array();
	if ( $pai && isset( $grupos[ $pai ] ) ) {
		return 'sgc-loja';
	}
	return $pai;
}
add_filter( 'parent_file', 'sgc_menu_atual', 99 );

/**
 * Página "Gestão": tudo o que foi guardado, em cartões, com os atalhos de cada área.
 */
function sgc_pagina_loja() {
	$grupos = isset( $GLOBALS['sgc_grupos'] ) ? $GLOBALS['sgc_grupos'] : array();
	$icones = array(
		'edit.php?post_type=product' => '🛍️', 'woocommerce' => '🧾', 'upload.php' => '🖼️', 'edit.php?post_type=page' => '📄',
		'edit-comments.php' => '💬', 'users.php' => '🙋', 'plugins.php' => '🔌', 'tools.php' => '🧰',
	);
	$descr = array(
		'edit.php?post_type=product' => 'Cadastre, edite, mude preço e estoque.',
		'woocommerce' => 'Pedidos, clientes, relatórios e configurações da loja.',
		'upload.php' => 'Todas as fotos enviadas ao site.',
		'edit.php?post_type=page' => 'Contato, trocas e devoluções, políticas…',
		'edit-comments.php' => 'Respostas e avaliações.',
		'users.php' => 'Quem pode entrar no painel.',
		'plugins.php' => 'Mercado Pago, Melhor Envio e outros.',
		'tools.php' => 'Importar, exportar e saúde do site.',
	);
	?>
	<div class="wrap sgc-loja">
		<h1>Gestão</h1>
		<p class="sgc-loja__sub">Tudo o que você usa para administrar a loja, num lugar só.</p>

		<div class="sgc-cartoes">
			<a class="sgc-cartao sgc-cartao--destaque" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>"><span class="sgc-cartao__ico">➕</span><b>Cadastrar produto</b><span>Foto, preço, estoque e categoria.</span></a>
			<a class="sgc-cartao sgc-cartao--destaque" href="<?php echo esc_url( admin_url( 'admin.php?page=sgc-integracoes' ) ); ?>"><span class="sgc-cartao__ico">💳</span><b>Pagamento e frete</b><span>Mercado Pago, Correios e transportadoras.</span></a>
			<a class="sgc-cartao sgc-cartao--destaque" href="<?php echo esc_url( admin_url( 'admin.php?page=sgc-central' ) ); ?>"><span class="sgc-cartao__ico">🎨</span><b>Editar o site</b><span>Textos, imagens, cores e prévia.</span></a>
		</div>

		<?php foreach ( $grupos as $slug => $g ) : ?>
			<h2 class="sgc-loja__tit"><?php echo esc_html( ( $icones[ $slug ] ?? '📁' ) . ' ' . $g['titulo'] ); ?></h2>
			<?php if ( isset( $descr[ $slug ] ) ) : ?><p class="sgc-loja__d"><?php echo esc_html( $descr[ $slug ] ); ?></p><?php endif; ?>
			<div class="sgc-lista-links">
				<?php
				$lista = $g['filhos'] ? $g['filhos'] : array( $g );
				foreach ( $lista as $f ) :
					?>
					<a href="<?php echo esc_url( $f['url'] ); ?>"><?php echo esc_html( $f['titulo'] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Página inicial do painel
 * ---------------------------------------------------------------------- */

/**
 * Troca as caixas do "Painel" por um resumo e atalhos.
 */
function sgc_dashboard() {
	global $wp_meta_boxes;
	$wp_meta_boxes['dashboard'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	wp_add_dashboard_widget( 'sgc_atalhos', 'O que você quer fazer hoje?', 'sgc_widget_atalhos' );
	wp_add_dashboard_widget( 'sgc_resumo', 'Resumo da loja', 'sgc_widget_resumo' );
}
add_action( 'wp_dashboard_setup', 'sgc_dashboard', 9999 );

/**
 * Botões grandes de atalho.
 */
function sgc_widget_atalhos() {
	$itens = array(
		array( '🎨', 'Editar o site', admin_url( 'admin.php?page=sgc-central' ) ),
		array( '➕', 'Cadastrar produto', admin_url( 'post-new.php?post_type=product' ) ),
		array( '🧾', 'Ver pedidos', sgc_url_pedidos() ),
		array( '✍️', 'Escrever no blog', admin_url( 'post-new.php' ) ),
		array( '🌐', 'Abrir o site', home_url( '/' ) ),
	);
	echo '<div class="sgc-atalhos">';
	foreach ( $itens as $i ) {
		printf( '<a href="%s"><span>%s</span>%s</a>', esc_url( $i[2] ), esc_html( $i[0] ), esc_html( $i[1] ) );
	}
	echo '</div>';
}

/**
 * Números da loja.
 */
function sgc_widget_resumo() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<p>Ative o WooCommerce para ver os números da loja.</p>';
		return;
	}
	$produtos = (int) wp_count_posts( 'product' )->publish;
	$aguard   = function_exists( 'wc_orders_count' ) ? (int) wc_orders_count( 'processing' ) : 0;
	$clientes = (int) ( count_users()['avail_roles']['customer'] ?? 0 );
	echo '<div class="sgc-numeros">';
	printf( '<div><b>%d</b><span>produtos à venda</span></div>', $produtos ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<div><b>%d</b><span>pedidos para enviar</span></div>', $aguard ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<div><b>%d</b><span>clientes</span></div>', $clientes ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
