<?php
/**
 * A tela "Meu Site": uma página por assunto, com prévia ao lado.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endereço que a prévia de uma seção deve abrir.
 *
 * @param string $ver Valor 'ver' do esquema.
 * @return string
 */
function sgc_url_previa( $ver ) {
	if ( 'loja' === $ver && function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
	} else {
		$url = home_url( 'loja' === $ver ? '/' : $ver );
	}
	return add_query_arg( 'sgc_previa', '1', $url );
}

/**
 * Menu "Meu Site".
 */
function sgc_menu() {
	add_menu_page( 'Meu Site', 'Meu Site', 'manage_options', 'sgc-central', 'sgc_pagina', 'dashicons-art', 2 );
}
add_action( 'admin_menu', 'sgc_menu' );

/**
 * A tela atual é a Central?
 *
 * @return bool
 */
function sgc_na_central() {
	return is_admin() && isset( $_GET['page'] ) && 'sgc-central' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Menu do WordPress já fechadinho nesta tela, para dar espaço à prévia.
 *
 * @param string $classes Classes.
 * @return string
 */
function sgc_classe_body( $classes ) {
	return sgc_na_central() ? $classes . ' folded sgc-tela ' : $classes;
}
add_filter( 'admin_body_class', 'sgc_classe_body', 20 );

/**
 * CSS e JS da Central.
 *
 * @param string $gancho Tela atual.
 */
function sgc_assets( $gancho ) {
	if ( 'toplevel_page_sgc-central' !== $gancho ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'sgc-central', SGC_URL . 'assets/central.css', array(), SGC_VERSAO );
	wp_enqueue_script( 'sgc-central', SGC_URL . 'assets/central.js', array(), SGC_VERSAO, true );

	$previas = array();
	foreach ( sgc_secoes() as $s ) {
		$previas[ $s['id'] ] = sgc_url_previa( $s['ver'] );
	}
	wp_localize_script( 'sgc-central', 'SGC', array(
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'sgc' ),
		'previas' => $previas,
		'site'    => home_url( '/' ),
		'marca'   => array(
			'cor_primaria' => '#1E40AF', 'cor_logo' => '#1E40AF', 'cor_destaque' => '#D6A32F',
			'cor_fundo' => '#FCFAF5', 'cor_texto' => '#1B2A4A', 'cor_whatsapp' => '#25D366',
		),
	) );
}
add_action( 'admin_enqueue_scripts', 'sgc_assets' );

/**
 * Desenha a Central.
 */
function sgc_pagina() {
	$base    = array_merge( sgc_publicado(), sgc_rascunho() );
	$secoes  = sgc_secoes();
	$grupos  = array();
	foreach ( $secoes as $s ) {
		$grupos[ $s['grupo'] ][] = $s;
	}
	$mudancas = count( sgc_rascunho() );
	$primeira = $secoes[0]['id'];
	?>
	<div class="sgc" id="sgc" data-primeira="<?php echo esc_attr( $primeira ); ?>">

		<header class="sgc-topo">
			<div class="sgc-topo__marca"><span class="sgc-topo__ponto"></span><b>Meu Site</b></div>
			<div class="sgc-topo__status" data-status data-mudancas="<?php echo (int) $mudancas; ?>">
				<?php echo $mudancas ? 'Você tem alterações que ainda não foram publicadas.' : 'Tudo publicado.'; ?>
			</div>
			<div class="sgc-topo__acoes">
				<button type="button" class="sgc-bt sgc-bt--fantasma sgc-so-celular" data-previa-alterna>👁 Prévia</button>
				<a class="sgc-bt sgc-bt--fantasma" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Ver o site ↗</a>
				<button type="button" class="sgc-bt sgc-bt--fantasma" data-descartar>Descartar</button>
				<button type="button" class="sgc-bt sgc-bt--principal" data-publicar>Publicar</button>
			</div>
		</header>

		<div class="sgc-corpo">

			<nav class="sgc-nav" aria-label="Assuntos do site">
				<?php foreach ( $grupos as $nome => $itens ) : ?>
					<div class="sgc-nav__grupo">
						<p><?php echo esc_html( $nome ); ?></p>
						<?php foreach ( $itens as $s ) : ?>
							<button type="button" class="sgc-nav__item" data-ir="<?php echo esc_attr( $s['id'] ); ?>">
								<span class="sgc-nav__ico"><?php echo esc_html( $s['icone'] ); ?></span>
								<span><?php echo esc_html( $s['titulo'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				<div class="sgc-nav__grupo">
					<p>Outras coisas</p>
					<a class="sgc-nav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=sgc-loja' ) ); ?>"><span class="sgc-nav__ico">📦</span><span>Produtos, pedidos e clientes</span></a>
				</div>
			</nav>

			<main class="sgc-form" id="sgc-form">
				<?php foreach ( $secoes as $s ) : ?>
					<section class="sgc-sec" data-sec="<?php echo esc_attr( $s['id'] ); ?>" data-ancora="<?php echo esc_attr( $s['ancora'] ); ?>" hidden>
						<header class="sgc-sec__cab">
							<h1><span><?php echo esc_html( $s['icone'] ); ?></span> <?php echo esc_html( $s['titulo'] ); ?></h1>
							<p><?php echo esc_html( $s['desc'] ); ?></p>
						</header>

						<?php if ( 'cores' === $s['id'] ) : ?>
							<div class="sgc-aviso">
								<b>Cores da marca São Gerônimo</b>
								<span>Azul royal, dourado e creme. Quer voltar a elas?</span>
								<button type="button" class="sgc-bt" data-cores-marca>Usar as cores da marca</button>
							</div>
						<?php endif; ?>

						<?php if ( isset( $s['grupos'] ) ) : ?>
							<div class="sgc-abas" role="tablist">
								<?php $i = 0; foreach ( $s['grupos'] as $nome => $campos ) : ?>
									<button type="button" class="sgc-aba<?php echo 0 === $i ? ' on' : ''; ?>" data-aba="<?php echo (int) $i; ?>"><?php echo esc_html( $nome ); ?></button>
									<?php $i++; endforeach; ?>
							</div>
							<?php $i = 0; foreach ( $s['grupos'] as $nome => $campos ) : ?>
								<div class="sgc-painel" data-painel="<?php echo (int) $i; ?>" <?php echo 0 === $i ? '' : 'hidden'; ?>>
									<?php foreach ( $campos as $c ) { sgc_linha( $c, $base ); } ?>
								</div>
								<?php $i++; endforeach; ?>
						<?php else : ?>
							<div class="sgc-painel">
								<?php foreach ( $s['campos'] as $c ) { sgc_linha( $c, $base ); } ?>
							</div>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</main>

			<aside class="sgc-previa" aria-label="Prévia do site">
				<div class="sgc-previa__barra">
					<div class="sgc-disp" role="group" aria-label="Tamanho da tela">
						<button type="button" class="sgc-disp__bt on" data-disp="desktop" title="Computador">🖥️ <span>Computador</span></button>
						<button type="button" class="sgc-disp__bt" data-disp="tablet" title="Tablet">📱 <span>Tablet</span></button>
						<button type="button" class="sgc-disp__bt" data-disp="mobile" title="Celular">📲 <span>Celular</span></button>
					</div>
					<button type="button" class="sgc-mini" data-recarrega title="Recarregar a prévia">⟳</button>
				</div>
				<div class="sgc-previa__palco" data-palco>
					<div class="sgc-previa__tela" data-tela>
						<iframe title="Prévia do site" data-iframe></iframe>
					</div>
					<div class="sgc-previa__carregando" data-carregando>Atualizando…</div>
				</div>
			</aside>

		</div>

		<div class="sgc-aviso-toast" data-toast role="status" aria-live="polite"></div>
	</div>
	<?php
}

/**
 * Guarda o rascunho (só mostra na prévia).
 */
function sgc_ajax_rascunho() {
	check_ajax_referer( 'sgc', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'sem permissão', 403 );
	}
	$dados = json_decode( wp_unslash( $_POST['dados'] ?? '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $dados ) ) {
		wp_send_json_error( 'dados inválidos', 400 );
	}
	$rasc = sgc_so_diferencas( sgc_limpar_tudo( $dados ) );
	update_user_meta( get_current_user_id(), 'sgc_rascunho', $rasc );
	wp_send_json_success( array( 'mudancas' => count( $rasc ) ) );
}
add_action( 'wp_ajax_sgc_rascunho', 'sgc_ajax_rascunho' );

/**
 * Publica: o que está no formulário passa a valer no site.
 */
function sgc_ajax_publicar() {
	check_ajax_referer( 'sgc', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'sem permissão', 403 );
	}
	$dados = json_decode( wp_unslash( $_POST['dados'] ?? '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $dados ) ) {
		wp_send_json_error( 'dados inválidos', 400 );
	}
	$novo = array_merge( sgc_publicado(), sgc_limpar_tudo( $dados ) );
	update_option( 'sg_opcoes', $novo );
	delete_user_meta( get_current_user_id(), 'sgc_rascunho' );
	wp_send_json_success( array( 'publicado' => true ) );
}
add_action( 'wp_ajax_sgc_publicar', 'sgc_ajax_publicar' );

/**
 * Joga fora o rascunho.
 */
function sgc_ajax_descartar() {
	check_ajax_referer( 'sgc', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'sem permissão', 403 );
	}
	delete_user_meta( get_current_user_id(), 'sgc_rascunho' );
	wp_send_json_success();
}
add_action( 'wp_ajax_sgc_descartar', 'sgc_ajax_descartar' );

/**
 * Só o que mudou em relação ao que está publicado.
 *
 * @param array $limpo Dados limpos.
 * @return array
 */
function sgc_so_diferencas( $limpo ) {
	$pub    = sgc_publicado();
	$campos = sgc_todos_campos();
	$dif    = array();

	foreach ( $limpo as $k => $v ) {
		if ( isset( $campos[ $k ] ) ) {
			$atual = isset( $pub[ $k ] ) && '' !== $pub[ $k ] ? $pub[ $k ] : ( $campos[ $k ]['d'] ?? '' );
		} elseif ( 'home_ordem' === $k ) {
			$atual = isset( $pub[ $k ] ) ? $pub[ $k ] : array_keys( sgc_blocos_home() );
		} else {
			$atual = isset( $pub[ $k ] ) ? $pub[ $k ] : 'sim';
		}
		$igual = ( is_array( $v ) || is_array( $atual ) )
			? wp_json_encode( $v ) === wp_json_encode( $atual )
			: (string) $v === (string) $atual;
		if ( ! $igual ) {
			$dif[ $k ] = $v;
		}
	}
	return $dif;
}

/* -------------------------------------------------------------------------
 * Dentro da prévia (o site aberto no quadro ao lado)
 * ---------------------------------------------------------------------- */

/**
 * Esconde a barra preta do WordPress na prévia.
 *
 * @param bool $mostra Mostrar?
 * @return bool
 */
function sgc_previa_sem_barra( $mostra ) {
	return sgc_e_previa() ? false : $mostra;
}
add_filter( 'show_admin_bar', 'sgc_previa_sem_barra' );

/**
 * Na prévia: não indexar, e manter o modo de prévia ao clicar nos links.
 */
function sgc_previa_rodape() {
	if ( ! sgc_e_previa() ) {
		return;
	}
	?>
	<script>
	( function () {
		var host = location.host;
		document.addEventListener( 'click', function ( e ) {
			var a = e.target.closest && e.target.closest( 'a[href]' );
			if ( ! a ) { return; }
			try {
				var u = new URL( a.href, location.href );
				if ( u.host !== host || u.pathname.indexOf( '/wp-admin' ) === 0 ) { return; }
				if ( ! u.searchParams.has( 'sgc_previa' ) ) { u.searchParams.set( 'sgc_previa', '1' ); }
				a.href = u.toString();
			} catch ( x ) {}
		}, true );
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'sgc_previa_rodape', 99 );

/**
 * A prévia não deve aparecer no Google.
 */
function sgc_previa_noindex() {
	if ( sgc_e_previa() ) {
		echo '<meta name="robots" content="noindex,nofollow">' . "\n";
	}
}
add_action( 'wp_head', 'sgc_previa_noindex', 1 );
