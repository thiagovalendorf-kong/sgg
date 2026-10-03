<?php
/**
 * Marca branca: deixa o WordPress com a cara da agência/loja e esconde
 * o que o lojista não precisa ver.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opções da marca branca.
 *
 * @param string $chave  Chave.
 * @param string $padrao Padrão.
 * @return string
 */
function sgp_marca( $chave, $padrao = '' ) {
	$o = get_option( 'sgp_marca', array() );
	return ( is_array( $o ) && ! empty( $o[ $chave ] ) ) ? $o[ $chave ] : $padrao;
}

/* -------------------------------------------------------------------------
 * Tela de login
 * ---------------------------------------------------------------------- */

/**
 * Visual da tela de login.
 */
function sgp_login_visual() {
	$logo  = sgp_marca( 'logo_login', sg_opt( 'logo_img', '' ) );
	$cor   = sgp_marca( 'cor', sg_opt( 'cor_primaria', '#1E40AF' ) );
	$fundo = sgp_marca( 'fundo_login', '' );
	?>
	<style>
		body.login { background: <?php echo esc_attr( $fundo ? 'url(' . esc_url( $fundo ) . ') center/cover no-repeat' : '#f6f4ef' ); ?>; }
		body.login::before { content: ""; position: fixed; inset: 0; background: rgba(12,20,40,<?php echo $fundo ? '.55' : '0'; ?>); }
		#login { position: relative; z-index: 1; padding-top: 7vh; width: 340px; }
		.login form { border: 1px solid rgba(0,0,0,.08); border-radius: 14px; box-shadow: 0 24px 60px -24px rgba(12,20,40,.35); padding: 28px 26px; }
		.login label { font-size: 13px; color: #384a6b; }
		.login input[type=text], .login input[type=password] { border-radius: 9px !important; padding: 10px 12px !important; font-size: 15px !important; }
		.login input[type=text]:focus, .login input[type=password]:focus { border-color: <?php echo esc_attr( $cor ); ?> !important; box-shadow: 0 0 0 1px <?php echo esc_attr( $cor ); ?> !important; }
		.wp-core-ui .button-primary { background: <?php echo esc_attr( $cor ); ?> !important; border-color: <?php echo esc_attr( $cor ); ?> !important;
			border-radius: 999px !important; height: 42px !important; font-size: 13px !important; letter-spacing: .08em; text-transform: uppercase; }
		<?php if ( $logo ) : ?>
		#login h1 a { background-image: url("<?php echo esc_url( $logo ); ?>") !important; background-size: contain !important;
			width: 100% !important; height: 62px !important; margin-bottom: 18px; }
		<?php endif; ?>
		.login #backtoblog a, .login #nav a, .login .privacy-policy-link { color: #4a5c7a !important; }
		.login #backtoblog a:hover, .login #nav a:hover { color: <?php echo esc_attr( $cor ); ?> !important; }
		.login .language-switcher { display: none; }
	</style>
	<?php
}
add_action( 'login_enqueue_scripts', 'sgp_login_visual' );

/**
 * O logo do login leva para o site, não para o wordpress.org.
 *
 * @return string
 */
function sgp_login_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'sgp_login_url' );

/**
 * Texto alternativo do logo do login.
 *
 * @return string
 */
function sgp_login_titulo() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'sgp_login_titulo' );

/**
 * Mensagem em cima do formulário de login.
 *
 * @param string $msg Mensagem.
 * @return string
 */
function sgp_login_recado( $msg ) {
	$txt = sgp_marca( 'recado_login', '' );
	if ( $txt ) {
		$msg .= '<p class="message">' . esc_html( $txt ) . '</p>';
	}
	return $msg;
}
add_filter( 'login_message', 'sgp_login_recado' );

/* -------------------------------------------------------------------------
 * Dentro do admin
 * ---------------------------------------------------------------------- */

/**
 * Rodapé do admin.
 *
 * @return string
 */
function sgp_rodape_admin() {
	$t = sgp_marca( 'rodape', '' );
	if ( ! $t ) {
		/* translators: nome da agência */
		$t = sprintf( __( 'Site da %1$s · suporte técnico com a %2$s', 'sao-geronimo-painel' ), get_bloginfo( 'name' ), sgp_marca( 'agencia', 'Kong Media' ) );
	}
	$url = sgp_marca( 'agencia_url', '' );
	return $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $t ) . '</a>' : esc_html( $t );
}
add_filter( 'admin_footer_text', 'sgp_rodape_admin' );

/**
 * Esconde o número da versão do WordPress no rodapé.
 *
 * @return string
 */
function sgp_esconde_versao() {
	return '';
}
add_filter( 'update_footer', 'sgp_esconde_versao', 11 );

/**
 * Limpa a barra do topo.
 *
 * @param WP_Admin_Bar $barra Barra.
 */
function sgp_barra( $barra ) {
	$barra->remove_node( 'wp-logo' );
	$barra->remove_node( 'comments' );

	if ( ! current_user_can( 'manage_options' ) ) {
		$barra->remove_node( 'updates' );
		$barra->remove_node( 'new-content' );
	}

	$barra->add_node( array(
		'id'    => 'sgp-painel',
		'title' => '<span class="ab-icon dashicons dashicons-store" style="top:2px"></span> ' . sgp_marca_nome(),
		'href'  => admin_url( 'admin.php?page=sg-painel' ),
	) );
}
add_action( 'admin_bar_menu', 'sgp_barra', 80 );

/**
 * CSS que pinta o admin com a cor da marca.
 */
function sgp_admin_cor() {
	$cor = sgp_marca( 'cor', sg_opt( 'cor_primaria', '#1E40AF' ) );
	?>
	<style>
		#adminmenu .wp-menu-image.dashicons-store::before { color: <?php echo esc_attr( $cor ); ?>; }
		#adminmenu li.toplevel_page_sg-painel.current, #adminmenu li.toplevel_page_sg-painel.wp-has-current-submenu { background: <?php echo esc_attr( $cor ); ?>; }
	</style>
	<?php
}
add_action( 'admin_head', 'sgp_admin_cor' );

/**
 * Painel inicial do WordPress: tira os blocos inúteis e põe o nosso.
 */
function sgp_dashboard() {
	global $wp_meta_boxes;

	$tirar = array( 'dashboard_primary', 'dashboard_secondary', 'dashboard_quick_press',
		'dashboard_incoming_links', 'dashboard_plugins', 'dashboard_recent_drafts', 'dashboard_browser_nag' );
	foreach ( $tirar as $id ) {
		remove_meta_box( $id, 'dashboard', 'normal' );
		remove_meta_box( $id, 'dashboard', 'side' );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	}

	wp_add_dashboard_widget( 'sgp_boas_vindas', sgp_marca_nome(), 'sgp_widget_boas_vindas' );
}
add_action( 'wp_dashboard_setup', 'sgp_dashboard', 99 );

/**
 * Conteúdo do bloco de boas-vindas.
 */
function sgp_widget_boas_vindas() {
	$u = wp_get_current_user();
	echo '<p style="font-size:14px">' . sprintf(
		/* translators: nome da pessoa */
		esc_html__( 'Olá, %s. Tudo o que aparece no site se muda pelo painel abaixo.', 'sao-geronimo-painel' ),
		esc_html( $u->first_name ? $u->first_name : $u->display_name )
	) . '</p>';

	$links = array(
		array( __( 'Abrir o painel da loja', 'sao-geronimo-painel' ), 'admin.php?page=sg-painel' ),
		array( __( 'Cadastrar um produto', 'sao-geronimo-painel' ), 'admin.php?page=sg-produto-facil' ),
		array( __( 'Ver os pedidos', 'sao-geronimo-painel' ), 'admin.php?page=sg-controladoria' ),
	);
	echo '<p>';
	foreach ( $links as $i => $l ) {
		printf(
			'<a class="button %s" href="%s" style="margin-right:6px">%s</a>',
			0 === $i ? 'button-primary' : '',
			esc_url( admin_url( $l[1] ) ),
			esc_html( $l[0] )
		);
	}
	echo '</p>';
}

/* -------------------------------------------------------------------------
 * Perfil "Lojista"
 * ---------------------------------------------------------------------- */

/**
 * Cria o perfil Lojista e dá a capacidade do painel ao administrador.
 */
function sgp_cria_perfil() {
	$caps = array(
		'read'                      => true,
		'sgp_gerenciar'             => true,
		'upload_files'              => true,
		'edit_posts'                => true,
		'publish_posts'             => true,
		'delete_posts'              => true,
		'edit_published_posts'      => true,
		'delete_published_posts'    => true,
		'edit_pages'                => true,
		'publish_pages'             => true,
		'edit_published_pages'      => true,
		'manage_categories'         => true,
		'moderate_comments'         => true,
		'edit_others_posts'         => true,
		'edit_others_pages'         => true,
		// WooCommerce
		'manage_woocommerce'        => true,
		'view_woocommerce_reports'  => true,
		'edit_shop_orders'          => true,
		'read_shop_order'           => true,
		'edit_others_shop_orders'   => true,
		'publish_shop_orders'       => true,
		'edit_published_shop_orders' => true,
		'edit_product'              => true,
		'edit_products'             => true,
		'edit_others_products'      => true,
		'publish_products'          => true,
		'edit_published_products'   => true,
		'delete_products'           => true,
		'delete_published_products' => true,
		'read_product'              => true,
		'list_users'                => true,
	);

	remove_role( 'sg_lojista' );
	add_role( 'sg_lojista', __( 'Lojista', 'sao-geronimo-painel' ), $caps );

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'sgp_gerenciar' );
	}
	$gerente = get_role( 'shop_manager' );
	if ( $gerente ) {
		$gerente->add_cap( 'sgp_gerenciar' );
	}
}

/**
 * Esconde do lojista os menus que só confundem.
 */
function sgp_esconde_menus() {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	$fora = array( 'tools.php', 'edit-comments.php', 'themes.php', 'plugins.php', 'options-general.php' );
	foreach ( $fora as $m ) {
		remove_menu_page( $m );
	}

	remove_submenu_page( 'themes.php', 'theme-editor.php' );
	remove_submenu_page( 'themes.php', 'widgets.php' );
}
add_action( 'admin_menu', 'sgp_esconde_menus', 999 );

/**
 * Barra o lojista de abrir à força as telas escondidas.
 */
function sgp_barra_telas() {
	if ( current_user_can( 'manage_options' ) || ! is_admin() || wp_doing_ajax() ) {
		return;
	}

	global $pagenow;
	$proibidas = array( 'theme-editor.php', 'plugin-editor.php', 'plugin-install.php', 'theme-install.php', 'update-core.php' );

	if ( in_array( $pagenow, $proibidas, true ) ) {
		wp_die(
			esc_html__( 'Esta área é só do administrador. Se precisar de algo aqui, fale com o suporte.', 'sao-geronimo-painel' ),
			esc_html__( 'Sem acesso', 'sao-geronimo-painel' ),
			array( 'response' => 403, 'back_link' => true )
		);
	}
}
add_action( 'admin_init', 'sgp_barra_telas' );

/**
 * Manda o lojista direto para o painel da loja ao entrar.
 *
 * @param string          $destino Destino padrão.
 * @param string          $pedido  Destino pedido.
 * @param WP_User|WP_Error $user   Usuário.
 * @return string
 */
function sgp_destino_login( $destino, $pedido, $user ) {
	if ( is_wp_error( $user ) || ! isset( $user->roles ) ) {
		return $destino;
	}
	if ( in_array( 'sg_lojista', (array) $user->roles, true ) && ! $pedido ) {
		return admin_url( 'admin.php?page=sg-painel' );
	}
	return $destino;
}
add_filter( 'login_redirect', 'sgp_destino_login', 10, 3 );

/* -------------------------------------------------------------------------
 * Tela de configuração da marca branca (só administrador)
 * ---------------------------------------------------------------------- */

/**
 * Registra a tela.
 */
function sgp_menu_marca() {
	add_submenu_page(
		'sg-painel',
		__( 'Marca branca', 'sao-geronimo-painel' ),
		__( 'Marca branca', 'sao-geronimo-painel' ),
		'manage_options',
		'sg-marca',
		'sgp_tela_marca'
	);
}
add_action( 'admin_menu', 'sgp_menu_marca', 20 );

/**
 * Tela da marca branca.
 */
function sgp_tela_marca() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	$campos = array(
		'nome'        => array( 'rotulo' => __( 'Nome do painel', 'sao-geronimo-painel' ), 'dica' => __( 'Como o menu aparece no admin.', 'sao-geronimo-painel' ) ),
		'agencia'     => array( 'rotulo' => __( 'Nome da agência', 'sao-geronimo-painel' ), 'padrao' => 'Kong Media' ),
		'agencia_url' => array( 'rotulo' => __( 'Site da agência', 'sao-geronimo-painel' ), 'html' => 'url' ),
		'rodape'      => array( 'rotulo' => __( 'Texto do rodapé do admin', 'sao-geronimo-painel' ), 'larga' => true ),
		'cor'         => array( 'rotulo' => __( 'Cor do painel', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#1E40AF' ),
		'logo_login'  => array( 'rotulo' => __( 'Logo da tela de login', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
		'fundo_login' => array( 'rotulo' => __( 'Imagem de fundo do login', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
		'recado_login' => array( 'rotulo' => __( 'Recado na tela de login', 'sao-geronimo-painel' ), 'larga' => true ),
	);

	if ( isset( $_POST['sgp_marca_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_marca_nonce'] ) ), 'sgp_marca' ) ) {
		$novo    = array();
		$enviado = isset( $_POST['sg'] ) ? wp_unslash( $_POST['sg'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $campos as $k => $def ) {
			$novo[ $k ] = sgp_limpa( $def, isset( $enviado[ $k ] ) ? $enviado[ $k ] : '' );
		}
		update_option( 'sgp_marca', $novo );
		update_option( 'sgp_marca_nome', $novo['nome'] );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Marca branca atualizada.', 'sao-geronimo-painel' ) . '</p></div>';
	}

	$atual = get_option( 'sgp_marca', array() );

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Marca branca', 'sao-geronimo-painel' ), __( 'Deixe o painel com a sua cara — ou a do cliente. Só o administrador vê esta tela.', 'sao-geronimo-painel' ) );
	echo '<form method="post" class="sgp-form"><section class="sgp-card">';
	wp_nonce_field( 'sgp_marca', 'sgp_marca_nonce' );
	foreach ( $campos as $k => $def ) {
		$v = isset( $atual[ $k ] ) ? $atual[ $k ] : ( isset( $def['padrao'] ) ? $def['padrao'] : '' );
		sgp_campo( $k, $def, $v );
	}
	echo '</section><div class="sgp-salvar"><button class="button button-primary button-hero">' . esc_html__( 'Salvar', 'sao-geronimo-painel' ) . '</button></div></form></div>';
}

/* -------------------------------------------------------------------------
 * Código extra no site (pixel, analytics)
 * ---------------------------------------------------------------------- */

/**
 * Imprime o código do <head>.
 */
function sgp_head_extra() {
	$c = sg_opt( 'head_extra', '' );
	if ( $c ) {
		echo $c; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	$fav = sg_opt( 'favicon', '' );
	if ( $fav ) {
		echo '<link rel="icon" href="' . esc_url( $fav ) . '">';
		echo '<link rel="apple-touch-icon" href="' . esc_url( $fav ) . '">';
	}
}
add_action( 'wp_head', 'sgp_head_extra', 5 );

/**
 * Imprime o código do fim da página.
 */
function sgp_body_extra() {
	$c = sg_opt( 'body_extra', '' );
	if ( $c ) {
		echo $c; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_footer', 'sgp_body_extra', 99 );
