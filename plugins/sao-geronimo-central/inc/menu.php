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
 * Menus que ficam à vista (o resto é recolhido).
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
 * Menu "Minha Loja".
 */
function sgc_menu_loja() {
	add_menu_page( 'Minha Loja', 'Minha Loja', 'edit_posts', 'sgc-loja', 'sgc_pagina_loja', 'dashicons-store', 3 );
}
add_action( 'admin_menu', 'sgc_menu_loja' );

/**
 * Recolhe os menus. Roda por último para pegar também os dos outros plugins.
 */
function sgc_recolher_menus() {
	global $menu;

	$guardados = array();
	foreach ( (array) $menu as $pos => $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( ! $slug || false !== strpos( (string) $item[4], 'wp-menu-separator' ) || in_array( $slug, sgc_menus_fixos(), true ) ) {
			continue;
		}
		$titulo = trim( wp_strip_all_tags( preg_replace( '#<span.*?</span>#is', '', (string) $item[0] ) ) );
		if ( '' === $titulo ) {
			continue;
		}
		$guardados[ $slug ] = array( 'titulo' => $titulo, 'url' => sgc_url_menu( $slug ), 'cap' => $item[1] );
	}

	// Guarda para a página "Minha Loja" e some do menu.
	$GLOBALS['sgc_menus_recolhidos'] = $guardados;
	foreach ( array_keys( $guardados ) as $slug ) {
		remove_menu_page( $slug );
	}

	// Posts vira "Blog".
	foreach ( (array) $menu as $pos => $item ) {
		if ( isset( $item[2] ) && 'edit.php' === $item[2] ) {
			$menu[ $pos ][0] = 'Blog'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	// Submenus que ninguém precisa no dia a dia.
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	remove_submenu_page( 'options-general.php', 'options-media.php' );
	remove_submenu_page( 'options-general.php', 'options-privacy.php' );
	remove_submenu_page( 'themes.php', 'theme-editor.php' );
	remove_submenu_page( 'themes.php', 'customize.php' );
}
add_action( 'admin_menu', 'sgc_recolher_menus', 9999 );

/**
 * Página "Minha Loja": tudo o que foi recolhido, em cartões grandes.
 */
function sgc_pagina_loja() {
	$woo = class_exists( 'WooCommerce' );
	$fixos = array(
		array( '➕', 'Cadastrar produto', 'Adicione um produto novo com foto, preço e estoque.', admin_url( 'post-new.php?post_type=product' ), $woo ),
		array( '🛍️', 'Todos os produtos', 'Veja, edite, tire do ar ou mude o preço.', admin_url( 'edit.php?post_type=product' ), $woo ),
		array( '🏷️', 'Categorias', 'Organize os produtos em categorias.', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ), $woo ),
		array( '🧾', 'Pedidos', 'Quem comprou, o que e em que ponto está a entrega.', class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' ), $woo ),
		array( '👥', 'Clientes', 'A lista de quem já comprou ou se cadastrou.', admin_url( 'admin.php?page=wc-admin&path=/customers' ), $woo ),
		array( '🎟️', 'Cupons', 'Crie códigos de desconto.', admin_url( 'edit.php?post_type=shop_coupon' ), $woo ),
		array( '📊', 'Relatórios', 'Vendas, produtos mais vendidos e resultados.', admin_url( 'admin.php?page=wc-admin&path=/analytics/overview' ), $woo ),
		array( '⚙️', 'Pagamento, frete e impostos', 'Mercado Pago, Correios, transportadoras.', admin_url( 'admin.php?page=wc-settings' ), $woo ),
		array( '🖼️', 'Biblioteca de imagens', 'Todas as fotos enviadas ao site.', admin_url( 'upload.php' ), true ),
		array( '📄', 'Páginas do site', 'Contato, políticas, trocas e devoluções…', admin_url( 'edit.php?post_type=page' ), true ),
		array( '💬', 'Comentários', 'Respostas e avaliações.', admin_url( 'edit-comments.php' ), true ),
		array( '🙋', 'Usuários do painel', 'Quem pode entrar aqui e com qual permissão.', admin_url( 'users.php' ), true ),
	);
	$extras = isset( $GLOBALS['sgc_menus_recolhidos'] ) ? $GLOBALS['sgc_menus_recolhidos'] : array();
	?>
	<div class="wrap sgc-loja">
		<h1>Minha Loja</h1>
		<p class="sgc-loja__sub">Tudo o que você usa para vender, num lugar só.</p>

		<div class="sgc-cartoes">
			<?php foreach ( $fixos as $f ) : if ( ! $f[4] ) { continue; } ?>
				<a class="sgc-cartao" href="<?php echo esc_url( $f[3] ); ?>">
					<span class="sgc-cartao__ico"><?php echo esc_html( $f[0] ); ?></span>
					<b><?php echo esc_html( $f[1] ); ?></b>
					<span><?php echo esc_html( $f[2] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php
		$mostrados = array( 'woocommerce', 'edit.php?post_type=product', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'users.php', 'woocommerce-marketing', 'wc-admin&path=/customers' );
		$outros    = array_diff_key( $extras, array_flip( $mostrados ) );
		?>
		<?php if ( $outros ) : ?>
			<h2 class="sgc-loja__tit">Outras ferramentas</h2>
			<div class="sgc-cartoes sgc-cartoes--mini">
				<?php foreach ( $outros as $o ) : ?>
					<a class="sgc-cartao" href="<?php echo esc_url( $o['url'] ); ?>"><b><?php echo esc_html( $o['titulo'] ); ?></b></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
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
		array( '🧾', 'Ver pedidos', admin_url( 'admin.php?page=sgc-loja' ) ),
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
