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
	$woo = function_exists( 'wc_get_page_permalink' );
	switch ( $ver ) {
		case 'loja':
			$url = $woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			break;
		case 'produto':
			$p   = $woo ? get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) ) : array();
			$url = $p ? get_permalink( $p[0] ) : ( $woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
			break;
		case 'post':
			$p   = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
			$url = $p ? get_permalink( $p[0] ) : home_url( '/' );
			break;
		case 'pagina':
			// Primeira página comum publicada (exemplo para ver capa e rodapé).
			$p   = get_posts( array( 'post_type' => 'page', 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC', 'exclude' => array( (int) get_option( 'page_on_front' ) ) ) );
			$url = $p ? get_permalink( $p[0] ) : home_url( '/' );
			break;
		case 'carrinho':
			$url = $woo ? wc_get_cart_url() : home_url( '/' );
			break;
		case 'checkout':
			$url = $woo ? wc_get_checkout_url() : home_url( '/' );
			break;
		case 'conta':
			$url = $woo ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
			break;
		default:
			$url = home_url( $ver );
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
 * Trilha (breadcrumb) de uma seção: Páginas › Início › Banners.
 * Cada passo leva à primeira seção daquele nível.
 *
 * @param array $s Seção.
 * @return array Lista de array( rotulo, id ).
 */
function sgc_trilha( $s ) {
	$todas = sgc_secoes();
	$achar = function ( $campo, $valor, $grupo ) use ( $todas ) {
		foreach ( $todas as $o ) {
			if ( $o['grupo'] === $grupo && ( 'grupo' === $campo || $o[ $campo ] === $valor ) ) {
				return $o['id'];
			}
		}
		return '';
	};
	$t = array( array( 'rotulo' => $s['grupo'], 'id' => $achar( 'grupo', '', $s['grupo'] ) ) );
	if ( '' !== $s['pagina'] ) {
		$t[] = array( 'rotulo' => $s['pagina'], 'id' => $achar( 'pagina', $s['pagina'], $s['grupo'] ) );
	}
	if ( '' !== $s['sub'] && $s['sub'] !== $s['pagina'] ) {
		$t[] = array( 'rotulo' => $s['sub'], 'id' => $s['id'] );
	}
	return $t;
}

/**
 * Desenha a Central.
 */
function sgc_pagina() {
	$base    = array_merge( sgc_publicado(), sgc_rascunho() );
	$secoes  = sgc_secoes();
	$nav     = sgc_navegacao();
	$gestao  = array(
		array( admin_url( 'admin.php?page=sgc-loja' ), 'Produtos, pedidos e clientes', '📦' ),
		array( admin_url( 'admin.php?page=sgc-integracoes' ), 'Pagamento e frete', '🔌' ),
	);
	// Opções do seletor "Ir para…": todas as seções + atalhos de gestão.
	$opcoes = array();
	foreach ( $secoes as $s ) {
		$trilha   = sgc_trilha( $s );
		$rot      = implode( ' › ', wp_list_pluck( $trilha, 'rotulo' ) );
		$opcoes[] = array( 'id' => $s['id'], 'url' => '', 'rot' => $rot, 'txt' => $rot . ' ' . $s['titulo'] . ' ' . $s['desc'] );
	}
	foreach ( $gestao as $g ) {
		$opcoes[] = array( 'id' => '', 'url' => $g[0], 'rot' => 'Gestão › ' . $g[1], 'txt' => 'Gestão ' . $g[1] );
	}
	$mudancas = count( sgc_rascunho() );
	$primeira = $secoes[0]['id'];
	?>
	<div class="sgc" id="sgc" data-primeira="<?php echo esc_attr( $primeira ); ?>">

		<header class="sgc-topo">
			<div class="sgc-topo__marca"><span class="sgc-topo__ponto"></span><b>Meu Site</b></div>
			<div class="sgc-topo__status<?php echo $mudancas ? ' sujo' : ''; ?>" data-status data-mudancas="<?php echo (int) $mudancas; ?>" role="status" aria-live="polite">
				<span class="sgc-st-lg"><?php echo $mudancas ? 'Você tem alterações que ainda não foram publicadas.' : 'Tudo publicado.'; ?></span><span class="sgc-st-cr"><?php echo $mudancas ? 'Não publicado' : 'Publicado'; ?></span>
			</div>
			<div class="sgc-topo__acoes">
				<button type="button" class="sgc-bt sgc-bt--fantasma sgc-so-celular" data-previa-alterna>👁 Prévia</button>
				<a class="sgc-bt sgc-bt--fantasma" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Ver o site ↗</a>
				<button type="button" class="sgc-bt sgc-bt--fantasma" data-descartar>Descartar</button>
				<button type="button" class="sgc-bt sgc-bt--principal" data-publicar>Publicar</button>
			</div>
		</header>

		<div class="sgc-corpo">

			<nav class="sgc-nav" aria-label="Assuntos do site" data-nav>
				<?php foreach ( $nav as $g ) : ?>
					<div class="sgc-nav__grupo" data-no="g-<?php echo esc_attr( $g['slug'] ); ?>">
						<button type="button" class="sgc-nav__gt" aria-expanded="true" aria-controls="sgc-nav-g-<?php echo esc_attr( $g['slug'] ); ?>" data-alterna>
							<span><?php echo esc_html( $g['nome'] ); ?></span><i class="sgc-seta" aria-hidden="true"></i>
						</button>
						<div class="sgc-nav__lista" id="sgc-nav-g-<?php echo esc_attr( $g['slug'] ); ?>">
							<?php foreach ( $g['itens'] as $it ) : ?>
								<?php if ( isset( $it['filhos'] ) ) : ?>
									<div class="sgc-nav__no" data-no="i-<?php echo esc_attr( $it['chave'] ); ?>">
										<button type="button" class="sgc-nav__item sgc-nav__pai" aria-expanded="false" aria-controls="sgc-nav-i-<?php echo esc_attr( $it['chave'] ); ?>" data-alterna>
											<span class="sgc-nav__ico"><?php echo esc_html( $it['icone'] ); ?></span>
											<span class="sgc-nav__txt"><?php echo esc_html( $it['rotulo'] ); ?></span>
											<em class="sgc-nav__qtd"><?php echo (int) count( $it['filhos'] ); ?></em>
											<i class="sgc-seta" aria-hidden="true"></i>
										</button>
										<div class="sgc-nav__filhos" id="sgc-nav-i-<?php echo esc_attr( $it['chave'] ); ?>" hidden>
											<?php foreach ( $it['filhos'] as $f ) : ?>
												<button type="button" class="sgc-nav__item sgc-nav__sub" data-ir="<?php echo esc_attr( $f['id'] ); ?>"><span><?php echo esc_html( $f['rotulo'] ); ?></span></button>
											<?php endforeach; ?>
										</div>
									</div>
								<?php else : ?>
									<button type="button" class="sgc-nav__item" data-ir="<?php echo esc_attr( $it['id'] ); ?>">
										<span class="sgc-nav__ico"><?php echo esc_html( $it['icone'] ); ?></span>
										<span class="sgc-nav__txt"><?php echo esc_html( $it['rotulo'] ); ?></span>
									</button>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="sgc-nav__grupo" data-no="g-gestao">
					<button type="button" class="sgc-nav__gt" aria-expanded="true" aria-controls="sgc-nav-g-gestao" data-alterna>
						<span>Gestão</span><i class="sgc-seta" aria-hidden="true"></i>
					</button>
					<div class="sgc-nav__lista" id="sgc-nav-g-gestao">
						<?php foreach ( $gestao as $g ) : ?>
							<a class="sgc-nav__item" href="<?php echo esc_url( $g[0] ); ?>"><span class="sgc-nav__ico"><?php echo esc_html( $g[2] ); ?></span><span class="sgc-nav__txt"><?php echo esc_html( $g[1] ); ?></span></a>
						<?php endforeach; ?>
					</div>
				</div>
			</nav>

			<div class="sgc-centro">
			<div class="sgc-ir" data-ir-caixa>
				<label class="sgc-ir__rotulo" for="sgc-ir-campo">Ir para…</label>
				<div class="sgc-ir__campo">
					<input type="search" id="sgc-ir-campo" class="sgc-in" placeholder="Achar uma seção…" title="Digite para achar uma seção (ex.: banners, cores)" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="sgc-ir-lista" aria-autocomplete="list" data-ir-busca>
					<ul class="sgc-ir__lista" id="sgc-ir-lista" role="listbox" hidden data-ir-lista>
						<?php foreach ( $opcoes as $o ) : ?>
							<?php if ( $o['url'] ) : ?>
								<li role="option" class="sgc-ir__op" data-url="<?php echo esc_url( $o['url'] ); ?>" data-txt="<?php echo esc_attr( $o['txt'] ); ?>"><?php echo esc_html( $o['rot'] ); ?></li>
							<?php else : ?>
								<li role="option" class="sgc-ir__op" data-destino="<?php echo esc_attr( $o['id'] ); ?>" data-txt="<?php echo esc_attr( $o['txt'] ); ?>"><?php echo esc_html( $o['rot'] ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
						<li class="sgc-ir__vazio" hidden data-ir-vazio>Nada encontrado. Tente outra palavra.</li>
					</ul>
				</div>
			</div>

			<main class="sgc-form" id="sgc-form">
				<?php foreach ( $secoes as $s ) : ?>
					<section class="sgc-sec" data-sec="<?php echo esc_attr( $s['id'] ); ?>" data-ancora="<?php echo esc_attr( $s['ancora'] ); ?>" hidden>
						<header class="sgc-sec__cab">
							<nav class="sgc-trilha" aria-label="Você está em">
								<ol>
									<?php $trilha = sgc_trilha( $s ); $ult = count( $trilha ) - 1; foreach ( $trilha as $i => $t ) : ?>
										<li><?php if ( $i < $ult ) : ?><button type="button" data-ir="<?php echo esc_attr( $t['id'] ); ?>"><?php echo esc_html( $t['rotulo'] ); ?></button><?php else : ?><span aria-current="page"><?php echo esc_html( $t['rotulo'] ); ?></span><?php endif; ?></li>
									<?php endforeach; ?>
								</ol>
							</nav>
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
							<div class="sgc-abas" role="tablist" aria-label="Partes desta seção">
								<?php $i = 0; foreach ( $s['grupos'] as $nome => $campos ) : ?>
									<button type="button" role="tab" id="sgc-aba-<?php echo esc_attr( $s['id'] . '-' . $i ); ?>" aria-controls="sgc-painel-<?php echo esc_attr( $s['id'] . '-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" class="sgc-aba<?php echo 0 === $i ? ' on' : ''; ?>" data-aba="<?php echo (int) $i; ?>"><?php echo esc_html( $nome ); ?></button>
									<?php $i++; endforeach; ?>
							</div>
							<?php $i = 0; foreach ( $s['grupos'] as $nome => $campos ) : ?>
								<div class="sgc-painel" role="tabpanel" id="sgc-painel-<?php echo esc_attr( $s['id'] . '-' . $i ); ?>" aria-labelledby="sgc-aba-<?php echo esc_attr( $s['id'] . '-' . $i ); ?>" data-painel="<?php echo (int) $i; ?>" <?php echo 0 === $i ? '' : 'hidden'; ?>>
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
			</div>

			<aside class="sgc-previa" aria-label="Prévia do site">
				<div class="sgc-previa__barra">
					<button type="button" class="sgc-bt sgc-so-celular" data-previa-alterna>← Voltar ao editor</button>
					<div class="sgc-disp" role="group" aria-label="Tamanho da tela">
						<button type="button" class="sgc-disp__bt on" data-disp="desktop" title="Computador">🖥️ <span>Computador</span></button>
						<button type="button" class="sgc-disp__bt" data-disp="tablet" title="Tablet">📱 <span>Tablet</span></button>
						<button type="button" class="sgc-disp__bt" data-disp="mobile" title="Celular">📲 <span>Celular</span></button>
					</div>
					<span class="sgc-previa__ferr"><button type="button" class="sgc-mini sgc-mini--ampliar" data-ampliar aria-pressed="false" aria-label="Ampliar a prévia" title="Ampliar a prévia">⤢</button><button type="button" class="sgc-mini" data-recarrega aria-label="Recarregar a prévia" title="Recarregar a prévia">⟳</button></span>
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

/**
 * Na prévia do finalizar compra, o carrinho precisa ter algo; senão o WooCommerce
 * manda de volta ao carrinho vazio. Põe um produto só para a pessoa ver a tela.
 */
function sgc_previa_checkout() {
	if ( ! sgc_e_previa() || ! function_exists( 'is_checkout' ) || ! is_checkout() || ! WC()->cart || ! WC()->cart->is_empty() ) {
		return;
	}
	$p = get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
	if ( $p ) {
		WC()->cart->add_to_cart( $p[0] );
	}
}
add_action( 'wp', 'sgc_previa_checkout' );

/**
 * Quando a pessoa ainda não tem produtos, o finalizar compra redireciona;
 * na prévia seguramos esse redirecionamento.
 *
 * @param string $url Destino.
 * @return string|false
 */
function sgc_previa_sem_redirect( $url ) {
	return sgc_e_previa() && function_exists( 'is_checkout' ) && is_checkout() ? false : $url;
}
add_filter( 'wp_redirect', 'sgc_previa_sem_redirect' );
