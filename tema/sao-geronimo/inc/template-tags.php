<?php
/**
 * Pedaços reutilizáveis de HTML (ícones, cartões, avisos).
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devolve um ícone SVG de linha pelo nome.
 *
 * @param string $nome Nome do ícone.
 * @param int    $tam  Tamanho em pixels.
 * @return string
 */
function sg_icone( $nome, $tam = 17 ) {
	$d = array(
		'busca'     => '<circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/>',
		'conta'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'coracao'   => '<path d="M12 20s-7-4.6-7-9.3A4 4 0 0 1 12 8a4 4 0 0 1 7 2.7C19 15.4 12 20 12 20Z"/>',
		'sacola'    => '<path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'x'         => '<path d="M6 6l12 12M18 6 6 18"/>',
		'seta'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'ant'       => '<path d="M15 6l-6 6 6 6"/>',
		'prox'      => '<path d="M9 6l6 6-6 6"/>',
		'regua'     => '<rect x="2.5" y="8" width="19" height="8" rx="1.5"/><path d="M6.5 8v3M10.5 8v4M14.5 8v3M18.5 8v4"/>',
		'caminhao'  => '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
		'escudo'    => '<path d="M12 3l8 3v6c0 5-3.4 8-8 9-4.6-1-8-4-8-9V6l8-3Z"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>',
		'facebook'  => '<path d="M14 9h3V6h-3a4 4 0 0 0-4 4v2H8v3h2v6h3v-6h2.5l.5-3H13v-2a1 1 0 0 1 1-1Z"/>',
		'email'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 6.6 8.4 6 8.4-6"/>',
		'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20Z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5"/>',
		'estrela'   => '<path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/>',
		'local'     => '<path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
		'relogio'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'presente'  => '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 12h18M12 8v13M12 8S9 3 6.5 5 9 8 12 8Zm0 0s3-5 5.5-3S15 8 12 8Z"/>',
	);

	if ( ! isset( $d[ $nome ] ) ) {
		return '';
	}

	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $tam,
		$d[ $nome ]
	);
}

/**
 * Logotipo: usa a imagem enviada no painel ou cai no nome escrito.
 */
function sg_marca() {
	$img  = sg_opt( 'logo_img', '' );
	$casa = esc_url( home_url( '/' ) );

	if ( $img ) {
		printf(
			'<a href="%s" class="marca marca--img" rel="home"><img src="%s" alt="%s" style="max-height:%dpx;width:auto"></a>',
			$casa,
			esc_url( $img ),
			esc_attr( get_bloginfo( 'name' ) ),
			(int) sg_opt( 'logo_altura', 34 )
		);
		return;
	}

	printf(
		'<a href="%s" class="marca" rel="home">%s</a>',
		$casa,
		esc_html( sg_opt( 'logo_texto', get_bloginfo( 'name' ) ) )
	);
}

/**
 * Ícones de redes sociais do rodapé.
 */
function sg_redes() {
	$redes = array(
		'instagram' => array( sg_opt( 'url_instagram' ), 'Instagram' ),
		'facebook'  => array( sg_opt( 'url_facebook' ), 'Facebook' ),
		'email'     => array( sg_opt( 'email_contato' ) ? 'mailto:' . sg_opt( 'email_contato' ) : '', 'E-mail' ),
		'whatsapp'  => array( sg_whatsapp_link(), 'WhatsApp' ),
	);

	$saida = '';
	foreach ( $redes as $icone => $dados ) {
		list( $url, $rotulo ) = $dados;
		if ( ! $url ) {
			continue;
		}
		$saida .= sprintf(
			'<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $rotulo ),
			sg_icone( $icone, 16 )
		);
	}

	if ( $saida ) {
		echo '<div class="redes">' . $saida . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Monta um link de WhatsApp já com mensagem.
 *
 * @param string $msg Mensagem inicial.
 * @return string
 */
function sg_whatsapp_link( $msg = '' ) {
	$num = preg_replace( '/\D/', '', (string) sg_opt( 'whatsapp', '' ) );
	if ( ! $num ) {
		return '';
	}
	$url = 'https://wa.me/' . $num;
	if ( $msg ) {
		$url .= '?text=' . rawurlencode( $msg );
	}
	return $url;
}

/**
 * Painel de busca que desce do cabeçalho (o mesmo do site estático).
 */
function sg_painel_busca() {
	?>
	<div class="busca" data-busca role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Buscar produtos', 'sao-geronimo' ); ?>">
		<div class="busca__fundo" data-fechar-busca></div>
		<div class="busca__painel">
			<form class="wrap busca__topo" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="busca__lupa"><?php echo sg_icone( 'busca', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input class="busca__campo" data-busca-campo type="search" name="s" autocomplete="off" spellcheck="false"
					placeholder="<?php echo esc_attr( sg_opt( 'busca_placeholder', 'Buscar por nome, orixá, aroma ou referência…' ) ); ?>"
					aria-label="<?php esc_attr_e( 'Buscar produtos', 'sao-geronimo' ); ?>">
				<input type="hidden" name="post_type" value="product">
				<button type="button" class="busca__x" data-fechar-busca aria-label="<?php esc_attr_e( 'Fechar busca', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'x', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</form>
			<div class="busca__corpo" data-busca-corpo></div>
		</div>
	</div>
	<?php
}

/**
 * Faixa de avisos acima do cabeçalho.
 */
function sg_faixa_topo() {
	$txt = trim( (string) sg_opt( 'faixa_topo', '' ) );
	if ( ! $txt || 'nao' === sg_opt( 'faixa_topo_ativa', 'sim' ) ) {
		return;
	}
	$link = sg_opt( 'faixa_topo_link', '' );
	$int  = '<span>' . esc_html( $txt ) . '</span>';
	if ( $link ) {
		$int = '<a href="' . esc_url( $link ) . '">' . esc_html( $txt ) . '</a>';
	}
	echo '<div class="faixa-topo"><div class="wrap">' . $int . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Botão flutuante de WhatsApp.
 */
function sg_botao_whatsapp() {
	if ( 'sim' !== sg_opt( 'whatsapp_flutuante', 'sim' ) ) {
		return;
	}
	$url = sg_whatsapp_link( sg_opt( 'whatsapp_msg', 'Olá! Vim pelo site e gostaria de uma ajuda.' ) );
	if ( ! $url ) {
		return;
	}
	printf(
		'<a class="zap-flut" href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
		esc_url( $url ),
		esc_attr__( 'Falar no WhatsApp', 'sao-geronimo' ),
		sg_icone( 'whatsapp', 26 ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
}
add_action( 'wp_footer', 'sg_botao_whatsapp' );

/**
 * Migalhas de pão (breadcrumb) simples e com schema.
 */
function sg_migalhas() {
	if ( is_front_page() ) {
		return;
	}

	$itens = array( array( __( 'Início', 'sao-geronimo' ), home_url( '/' ) ) );

	if ( function_exists( 'is_product' ) && is_product() ) {
		$loja = wc_get_page_id( 'shop' );
		if ( $loja > 0 ) {
			$itens[] = array( get_the_title( $loja ), get_permalink( $loja ) );
		}
		$termos = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $termos && ! is_wp_error( $termos ) ) {
			$t       = array_shift( $termos );
			$itens[] = array( $t->name, get_term_link( $t ) );
		}
		$itens[] = array( get_the_title(), '' );
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$itens[] = array( single_term_title( '', false ), '' );
	} elseif ( is_search() ) {
		$itens[] = array( __( 'Busca', 'sao-geronimo' ), '' );
	} elseif ( is_singular() || is_page() ) {
		$itens[] = array( get_the_title(), '' );
	} elseif ( is_archive() ) {
		$itens[] = array( get_the_archive_title(), '' );
	}

	echo '<div class="wrap"><nav class="migalhas" aria-label="' . esc_attr__( 'Você está em', 'sao-geronimo' ) . '">';
	$ultimo = count( $itens ) - 1;
	foreach ( $itens as $i => $item ) {
		list( $rot, $url ) = $item;
		if ( $i === $ultimo || ! $url ) {
			echo '<span>' . esc_html( $rot ) . '</span>';
		} else {
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $rot ) . '</a><span>/</span>';
		}
	}
	echo '</nav></div>';
}

/**
 * Menu móvel (gaveta lateral).
 */
function sg_menu_mobile() {
	?>
	<div class="menu-mob" id="menu-mob" data-menu-mob>
		<div class="menu-mob__fundo" data-fechar-menu></div>
		<div class="menu-mob__painel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'sao-geronimo' ); ?>">
			<div class="menu-mob__topo">
				<?php sg_marca(); ?>
				<button type="button" class="menu-mob__x" data-fechar-menu aria-label="<?php esc_attr_e( 'Fechar menu', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'x', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<nav class="menu-mob__lista">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'principal',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 2,
					'fallback_cb'    => 'sg_menu_padrao',
				) );
				?>
			</nav>
		</div>
	</div>
	<?php
}

/**
 * Menu de emergência: se nenhum menu foi montado ainda, mostra as categorias.
 */
function sg_menu_padrao() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Loja', 'sao-geronimo' ) . '</a>';
	}
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Início', 'sao-geronimo' ) . '</a>';
		return;
	}
	$cats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => 7,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	if ( is_wp_error( $cats ) ) {
		return;
	}
	foreach ( $cats as $c ) {
		printf( '<a href="%s">%s</a>', esc_url( get_term_link( $c ) ), esc_html( $c->name ) );
	}
}

/**
 * Menu de rodapé de emergência, quando nenhum foi montado ainda.
 */
function sg_rodape_padrao() {
	$itens = array( array( __( 'Início', 'sao-geronimo' ), home_url( '/' ) ) );
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$itens[] = array( __( 'Loja', 'sao-geronimo' ), wc_get_page_permalink( 'shop' ) );
	}
	$blog = get_option( 'page_for_posts' );
	if ( $blog ) {
		$itens[] = array( get_the_title( $blog ), get_permalink( $blog ) );
	}
	echo '<ul>';
	foreach ( $itens as $i ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $i[1] ), esc_html( $i[0] ) );
	}
	echo '</ul>';
}
