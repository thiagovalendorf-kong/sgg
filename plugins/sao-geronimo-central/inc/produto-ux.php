<?php
/**
 * Tela de edição de produto: visual e organização (claro/escuro).
 *
 * - nomes das abas em português simples;
 * - descrição curta logo abaixo do nome e descrição completa com títulos claros;
 * - "Checklist do cadastro" no topo (preenchido pelo produto-admin.js);
 * - CSS/JS só carregam na tela de editar/adicionar produto.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estamos na tela de editar/adicionar produto?
 *
 * @return bool
 */
function sgc_prod_tela() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return false;
	}
	$tela = get_current_screen();
	return $tela && 'post' === $tela->base && 'product' === $tela->post_type;
}

/**
 * Carrega CSS e JS.
 *
 * @param string $gancho Página atual.
 */
function sgc_prod_assets( $gancho ) {
	if ( ! in_array( $gancho, array( 'post.php', 'post-new.php' ), true ) || ! sgc_prod_tela() ) {
		return;
	}
	$ver = SGC_VERSAO;
	$dep = array();
	foreach ( array( 'sgcv', 'woocommerce_admin_styles', 'select2' ) as $h ) {
		if ( wp_style_is( $h, 'registered' ) ) {
			$dep[] = $h;
		}
	}
	$css = SGC_DIR . 'assets/produto-admin.css';
	$js  = SGC_DIR . 'assets/produto-admin.js';
	wp_enqueue_style( 'sgc-produto-admin', SGC_URL . 'assets/produto-admin.css', $dep, $ver . '.' . (int) @filemtime( $css ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	wp_enqueue_script( 'sgc-produto-admin', SGC_URL . 'assets/produto-admin.js', array(), $ver . '.' . (int) @filemtime( $js ), true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}
add_action( 'admin_enqueue_scripts', 'sgc_prod_assets', 99 );

/**
 * Classe no body para o CSS não depender de outras classes do WordPress.
 *
 * @param string $classes Classes.
 * @return string
 */
function sgc_prod_body( $classes ) {
	return sgc_prod_tela() ? $classes . ' sgp-tela ' : $classes;
}
add_filter( 'admin_body_class', 'sgc_prod_body', 20 );

/**
 * Nomes simples nas abas de "Dados do produto".
 *
 * @param array $abas Abas.
 * @return array
 */
function sgc_prod_abas( $abas ) {
	$nomes = array(
		'general'        => array( 'Preço', 10 ),
		'inventory'      => array( 'Estoque', 20 ),
		'shipping'       => array( 'Frete', 30 ),
		'attribute'      => array( 'Atributos', 40 ),
		'variations'     => array( 'Variações', 50 ),
		'sg'             => array( 'Informações do produto', 60 ),
		'linked_product' => array( 'Relacionados', 70 ),
		'advanced'       => array( 'Avançado', 80 ),
	);
	foreach ( $nomes as $chave => $d ) {
		if ( isset( $abas[ $chave ] ) ) {
			$abas[ $chave ]['label']    = $d[0];
			$abas[ $chave ]['priority'] = $d[1];
		}
	}
	return $abas;
}
add_filter( 'woocommerce_product_data_tabs', 'sgc_prod_abas', 99 );

/**
 * Textos do WooCommerce que ficam mais claros com palavras simples.
 *
 * @param string $traduzido Texto atual.
 * @param string $texto     Texto original.
 * @param string $dominio   Domínio.
 * @return string
 */
function sgc_prod_textos( $traduzido, $texto, $dominio ) {
	if ( 'woocommerce' !== $dominio || ! sgc_prod_tela() ) {
		return $traduzido;
	}
	static $mapa = array(
		'Product data'          => 'Dados do produto',
		'Product short description' => 'Descrição curta',
		'Product image'         => 'Foto principal do produto',
		'Product gallery'       => 'Galeria (outras fotos)',
		'Product categories'    => 'Categorias',
		'Product tags'          => 'Etiquetas',
	);
	return isset( $mapa[ $texto ] ) ? $mapa[ $texto ] : $traduzido;
}
add_filter( 'gettext', 'sgc_prod_textos', 20, 3 );

/**
 * Tira a caixa "Breve descrição" do rodapé: ela passa a aparecer logo abaixo do nome.
 */
function sgc_prod_tira_excerpt() {
	global $wp_meta_boxes;
	if ( ! isset( $wp_meta_boxes['product'] ) ) {
		return;
	}
	foreach ( $wp_meta_boxes['product'] as $contexto => $prioridades ) {
		foreach ( $prioridades as $prioridade => $caixas ) {
			if ( isset( $caixas['postexcerpt'] ) && is_array( $caixas['postexcerpt'] ) && ! empty( $caixas['postexcerpt']['callback'] ) ) {
				$GLOBALS['sgc_prod_excerpt_cb'] = $caixas['postexcerpt']['callback'];
				unset( $wp_meta_boxes['product'][ $contexto ][ $prioridade ]['postexcerpt'] );
			}
		}
	}
}
add_action( 'add_meta_boxes_product', 'sgc_prod_tira_excerpt', 999 );

/**
 * Checklist no topo da tela (o JS preenche e atualiza).
 *
 * @param WP_Post $post Produto.
 */
function sgc_prod_checklist( $post ) {
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	?>
	<section class="sgp-check" id="sgp-check" aria-labelledby="sgp-check-t" hidden>
		<div class="sgp-check__topo">
			<h2 id="sgp-check-t">Checklist do cadastro</h2>
			<p class="sgp-check__resumo" id="sgp-check-resumo" role="status" aria-live="polite"></p>
			<div class="sgp-check__barra" aria-hidden="true"><span id="sgp-check-barra"></span></div>
		</div>
		<ul class="sgp-check__lista" id="sgp-check-lista"></ul>
		<p class="sgp-check__nota">Só um guia: você pode salvar o produto mesmo com itens faltando. Clique em um item para ir até o campo.</p>
	</section>
	<?php
}
add_action( 'edit_form_top', 'sgc_prod_checklist' );

/**
 * Descrição curta (logo abaixo do nome) e título da descrição completa.
 *
 * @param WP_Post $post Produto.
 */
function sgc_prod_descricoes( $post ) {
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	if ( ! empty( $GLOBALS['sgc_prod_excerpt_cb'] ) && is_callable( $GLOBALS['sgc_prod_excerpt_cb'] ) ) {
		?>
		<section id="postexcerpt" class="postbox sgp-desc sgp-desc--curta">
			<div class="sgp-desc__topo">
				<h2>Descrição curta <small>(aparece ao lado da foto, perto do preço)</small></h2>
				<p>Duas ou três frases que fazem o cliente querer o produto. Dica: fale do benefício e do tamanho.</p>
			</div>
			<div class="inside">
				<?php call_user_func( $GLOBALS['sgc_prod_excerpt_cb'], $post ); ?>
			</div>
		</section>
		<?php
	}
	?>
	<section class="sgp-desc sgp-desc--longa postbox" id="sgp-desc-longa">
		<div class="sgp-desc__topo">
			<h2>Descrição completa <small>(aparece na aba Descrição, abaixo da foto)</small></h2>
			<p>Conte a história, o uso e os cuidados do produto. Pode ter títulos, listas e fotos.</p>
		</div>
	</section>
	<?php
}
add_action( 'edit_form_after_title', 'sgc_prod_descricoes' );
