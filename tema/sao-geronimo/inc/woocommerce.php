<?php
/**
 * Integração com o WooCommerce.
 *
 * O tema desenha a loja com a mesma cara do site antigo. Para isso, remove
 * alguns ganchos padrão do Woo e põe os nossos no lugar.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Estrutura das páginas da loja
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

add_action( 'woocommerce_before_main_content', 'sg_wc_abre', 10 );
add_action( 'woocommerce_after_main_content', 'sg_wc_fecha', 10 );

/**
 * Abertura das páginas do Woo.
 *
 * Reproduz o começo das páginas de categoria do site original: capa com o
 * título, migalhas de pão, pílulas de filtro e a barra com a contagem.
 */
function sg_wc_abre() {
	$e_lista = ( function_exists( 'is_shop' ) && is_shop() ) || is_product_category() || is_product_tag();

	if ( $e_lista ) {
		$titulo = is_shop() ? get_the_title( wc_get_page_id( 'shop' ) ) : single_term_title( '', false );
		$desc   = '';
		if ( is_product_category() || is_product_tag() ) {
			$termo = get_queried_object();
			$desc  = ( $termo && ! empty( $termo->description ) ) ? $termo->description : '';
		}

		echo '<section class="capa"><div class="wrap"><h1>' . esc_html( $titulo ) . '</h1>';
		if ( $desc ) {
			echo '<p>' . esc_html( wp_strip_all_tags( $desc ) ) . '</p>';
		}
		echo '</div></section>';
	}

	sg_migalhas();

	echo '<section class="sec sec--curto" style="padding-top:' . ( $e_lista ? '0' : '8px' ) . '"><div class="wrap">';

	if ( $e_lista ) {
		sg_wc_pilulas();
		sg_wc_barra();
	}
}

/**
 * Pílulas que filtram a grade por sublinha (as etiquetas do produto).
 */
function sg_wc_pilulas() {
	global $wp_query;

	if ( empty( $wp_query->posts ) ) {
		return;
	}

	$subs = array();
	foreach ( $wp_query->posts as $sg_p ) {
		$id = is_object( $sg_p ) ? $sg_p->ID : (int) $sg_p;
		$ts = get_the_terms( $id, 'product_tag' );
		if ( $ts && ! is_wp_error( $ts ) ) {
			foreach ( $ts as $t ) {
				$subs[ $t->name ] = true;
			}
		}
	}

	if ( count( $subs ) < 2 ) {
		return;
	}

	$subs = array_keys( $subs );
	sort( $subs );

	echo '<div class="pilulas" style="margin-bottom:32px">';
	echo '<button class="pilula on" data-sub="">' . esc_html__( 'Todos', 'sao-geronimo' ) . '</button>';
	foreach ( $subs as $s ) {
		echo '<button class="pilula" data-sub="' . esc_attr( $s ) . '">' . esc_html( $s ) . '</button>';
	}
	echo '</div>';
}

/**
 * Barra com a contagem de produtos e a ordenação.
 */
function sg_wc_barra() {
	global $wp_query;
	$n = (int) $wp_query->found_posts;

	echo '<div class="barra-loja">';
	printf(
		'<span class="contagem" data-contagem>%s</span>',
		esc_html( sprintf( _n( '%d produto', '%d produtos', $n, 'sao-geronimo' ), $n ) )
	);
	woocommerce_catalog_ordering();
	echo '</div>';
}

/**
 * Fechamento das páginas do Woo.
 */
function sg_wc_fecha() {
	echo '</div></section>';
}

/* -------------------------------------------------------------------------
 * Grade de produtos
 * ---------------------------------------------------------------------- */

/**
 * Quantos produtos por página na loja.
 *
 * @return int
 */
function sg_wc_por_pagina() {
	return max( 4, (int) sg_opt( 'loja_por_pagina', 24 ) );
}
add_filter( 'loop_shop_per_page', 'sg_wc_por_pagina', 20 );

/**
 * Colunas da grade.
 *
 * @return int
 */
function sg_wc_colunas() {
	return max( 2, min( 5, (int) sg_opt( 'loja_colunas', 4 ) ) );
}
add_filter( 'loop_shop_columns', 'sg_wc_colunas', 20 );

// O cartão de produto é desenhado inteiro por woocommerce/content-product.php,
// então os ganchos padrão do laço saem de cena.
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/* -------------------------------------------------------------------------
 * Contador da sacola no cabeçalho
 * ---------------------------------------------------------------------- */

/**
 * Atualiza o número da sacola sem recarregar a página.
 *
 * @param array $fragmentos Fragmentos do Woo.
 * @return array
 */
function sg_wc_fragmento_sacola( $fragmentos ) {
	$n = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	ob_start();
	?>
	<span class="icone__n" data-n <?php echo $n ? '' : 'style="display:none"'; ?>><?php echo (int) $n; ?></span>
	<?php
	$fragmentos['span.icone__n'] = ob_get_clean();
	return $fragmentos;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'sg_wc_fragmento_sacola' );

/* -------------------------------------------------------------------------
 * Campos extras do produto (aba "São Gerônimo" na edição do produto)
 * ---------------------------------------------------------------------- */

/**
 * Preço como texto simples, sem as tags do WooCommerce.
 *
 * O wc_price() devolve spans e um &nbsp;; quando o valor vai dentro de uma
 * frase, isso atrapalha. Aqui fica só "R$ 14,59".
 *
 * @param float $valor Valor.
 * @return string
 */
function sg_preco_texto( $valor ) {
	return trim( html_entity_decode( wp_strip_all_tags( wc_price( $valor ) ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Preço no Pix com desconto.
 *
 * @param float $preco Preço cheio.
 * @return float
 */
function sg_preco_pix( $preco ) {
	$desc = (float) sg_opt( 'pix_desconto', 5 );
	return round( $preco * ( 1 - $desc / 100 ), 2 );
}

/**
 * Número máximo de parcelas sem juros.
 *
 * @return int
 */
function sg_parcelas() {
	return max( 1, (int) sg_opt( 'parcelas_max', 10 ) );
}

/**
 * Linha pequena acima do título: categoria · sublinha.
 */
function sg_wc_eyebrow() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$partes = array();
	$cats   = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $cats && ! is_wp_error( $cats ) ) {
		$partes[] = $cats[0]->name;
	}
	$tags = get_the_terms( $product->get_id(), 'product_tag' );
	if ( $tags && ! is_wp_error( $tags ) ) {
		$partes[] = $tags[0]->name;
	}

	if ( $partes ) {
		echo '<span class="eyebrow">' . esc_html( implode( ' · ', $partes ) ) . '</span>';
	}
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_eyebrow', 4 );

/**
 * Marca e referência logo abaixo do título.
 */
function sg_wc_marca_ref() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$marca = get_post_meta( $product->get_id(), '_sg_marca', true );
	$sku   = $product->get_sku();
	$nota  = $product->get_average_rating();

	if ( ! $marca && ! $sku && ! $nota ) {
		return;
	}

	echo '<div class="prod__nota" style="margin-top:10px">';
	if ( $nota > 0 ) {
		for ( $i = 0; $i < 5; $i++ ) {
			echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/></svg>';
		}
		echo ' ' . esc_html( number_format_i18n( $nota, 1 ) );
	}
	$txt = array_filter( array( $marca, $sku ? 'Ref. ' . $sku : '' ) );
	if ( $txt ) {
		echo ( $nota > 0 ? ' · ' : '' ) . esc_html( implode( ' · ', $txt ) );
	}
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_marca_ref', 6 );

/**
 * Linha de "preço no Pix + parcelamento" abaixo do preço.
 */
function sg_wc_condicoes() {
	global $product;
	if ( ! $product || ! $product->get_price() ) {
		return;
	}
	$preco = (float) $product->get_price();
	$pix   = sg_preco_pix( $preco );
	$n     = sg_parcelas();
	$parc  = $preco / $n;

	echo '<small class="pdp__cond">';
	printf(
		/* translators: 1: preço no pix, 2: percentual, 3: parcelas, 4: valor da parcela */
		esc_html__( '%1$s no Pix, com %2$s%% de desconto · ou %3$dx de %4$s sem juros', 'sao-geronimo' ),
		esc_html( sg_preco_texto( $pix ) ),
		esc_html( (float) sg_opt( 'pix_desconto', 5 ) ),
		(int) $n,
		esc_html( sg_preco_texto( $parc ) )
	);
	echo '</small>';
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_condicoes', 11 );

/**
 * Bloco de dimensões logo no começo da descrição.
 */
function sg_wc_dimensoes() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$txt = get_post_meta( $product->get_id(), '_sg_dimensoes', true );
	if ( ! $txt && $product->has_dimensions() ) {
		$txt = wc_format_dimensions( $product->get_dimensions( false ) );
	}

	$peso = $product->get_weight() ? wc_format_weight( $product->get_weight() ) : '';

	if ( ! $txt && ! $peso ) {
		return;
	}

	echo '<div class="medidas">' . sg_icone( 'regua', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div><b>' . esc_html__( 'Dimensões', 'sao-geronimo' ) . '</b>';
	echo '<span>' . esc_html( $txt ? $txt : __( 'sob consulta', 'sao-geronimo' ) ) . '</span>';
	if ( $peso ) {
		echo '<small>' . esc_html__( 'Peso:', 'sao-geronimo' ) . ' ' . esc_html( $peso ) . '</small>';
	}
	echo '</div></div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_dimensoes', 19 );

/**
 * Registra a aba extra na edição do produto.
 *
 * @param array $abas Abas atuais.
 * @return array
 */
function sg_wc_aba_produto( $abas ) {
	$abas['sg'] = array(
		'label'    => __( 'São Gerônimo', 'sao-geronimo' ),
		'target'   => 'sg_dados_produto',
		'class'    => array(),
		'priority' => 68,
	);
	return $abas;
}
add_filter( 'woocommerce_product_data_tabs', 'sg_wc_aba_produto' );

/**
 * Campos da aba extra.
 */
function sg_wc_campos_produto() {
	echo '<div id="sg_dados_produto" class="panel woocommerce_options_panel">';

	woocommerce_wp_text_input( array(
		'id'          => '_sg_dimensoes',
		'label'       => __( 'Dimensões por extenso', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: 20 cm de altura × 8 cm de base', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Como a medida aparece para o cliente. Se deixar vazio, usamos a medida numérica da aba Entrega.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_marca',
		'label'       => __( 'Marca / fabricante', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Aparece embaixo do nome do produto e nos dados estruturados do Google.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'       => '_sg_conteudo',
		'label'    => __( 'Conteúdo da embalagem', 'sao-geronimo' ),
		'desc_tip' => true,
		'description' => __( 'ex.: 10 varetas, 30 g, 1 unidade.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'          => '_sg_composicao',
		'label'       => __( 'Composição', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Vira um bloco próprio na página do produto.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'          => '_sg_modo_usar',
		'label'       => __( 'Modo de usar', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Vira um bloco próprio na página do produto.', 'sao-geronimo' ),
	) );

	woocommerce_wp_checkbox( array(
		'id'          => '_sg_sob_consulta',
		'label'       => __( 'Preço sob consulta', 'sao-geronimo' ),
		'description' => __( 'Esconde o preço e troca o botão por "Consultar pelo WhatsApp".', 'sao-geronimo' ),
	) );

	echo '</div>';
}
add_action( 'woocommerce_product_data_panels', 'sg_wc_campos_produto' );

/**
 * Salva os campos extras.
 *
 * @param int $id ID do produto.
 */
function sg_wc_salva_produto( $id ) {
	$textos = array( '_sg_dimensoes', '_sg_marca', '_sg_conteudo' );
	foreach ( $textos as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_text_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	$areas = array( '_sg_composicao', '_sg_modo_usar' );
	foreach ( $areas as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_textarea_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	update_post_meta( $id, '_sg_sob_consulta', isset( $_POST['_sg_sob_consulta'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification
}
add_action( 'woocommerce_process_product_meta', 'sg_wc_salva_produto' );

/**
 * Blocos extras (composição, modo de usar) na página do produto.
 */
function sg_wc_blocos_extras() {
	global $product;
	if ( ! $product ) {
		return;
	}
	$blocos = array(
		__( 'Composição', 'sao-geronimo' ) => get_post_meta( $product->get_id(), '_sg_composicao', true ),
		__( 'Modo de usar', 'sao-geronimo' ) => get_post_meta( $product->get_id(), '_sg_modo_usar', true ),
	);
	foreach ( $blocos as $tit => $txt ) {
		if ( ! trim( (string) $txt ) ) {
			continue;
		}
		echo '<div class="ficha"><h4>' . esc_html( $tit ) . '</h4>';
		echo wp_kses_post( wpautop( $txt ) );
		echo '</div>';
	}
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_blocos_extras', 41 );

/**
 * Produto "sob consulta": esconde o preço e troca o botão.
 *
 * @param string     $html    HTML do preço.
 * @param WC_Product $product Produto.
 * @return string
 */
function sg_wc_sob_consulta_preco( $html, $product ) {
	if ( 'yes' === get_post_meta( $product->get_id(), '_sg_sob_consulta', true ) ) {
		return '<span class="preco-consulta">' . esc_html__( 'Preço sob consulta', 'sao-geronimo' ) . '</span>';
	}
	return $html;
}
add_filter( 'woocommerce_get_price_html', 'sg_wc_sob_consulta_preco', 10, 2 );

/**
 * Produto "sob consulta" não é comprável.
 *
 * @param bool       $pode    Se pode comprar.
 * @param WC_Product $product Produto.
 * @return bool
 */
function sg_wc_sob_consulta_compra( $pode, $product ) {
	if ( 'yes' === get_post_meta( $product->get_id(), '_sg_sob_consulta', true ) ) {
		return false;
	}
	return $pode;
}
add_filter( 'woocommerce_is_purchasable', 'sg_wc_sob_consulta_compra', 10, 2 );

/**
 * Botão de WhatsApp no lugar do "adicionar ao carrinho".
 */
function sg_wc_botao_consulta() {
	global $product;
	if ( ! $product || 'yes' !== get_post_meta( $product->get_id(), '_sg_sob_consulta', true ) ) {
		return;
	}
	$msg = sprintf(
		/* translators: 1: nome do produto, 2: SKU */
		__( 'Olá! Gostaria de saber o preço do produto %1$s (Ref. %2$s).', 'sao-geronimo' ),
		$product->get_name(),
		$product->get_sku()
	);
	$url = sg_whatsapp_link( $msg );
	if ( ! $url ) {
		return;
	}
	printf(
		'<div class="pdp__acoes"><a class="btn btn--azul" href="%s" target="_blank" rel="noopener" style="flex:1;min-width:240px">%s</a></div>',
		esc_url( $url ),
		esc_html__( 'Consultar pelo WhatsApp', 'sao-geronimo' )
	);
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_botao_consulta', 30 );

/* -------------------------------------------------------------------------
 * Ícone da categoria (usado no carrossel da home)
 * ---------------------------------------------------------------------- */

/**
 * Lista de ícones disponíveis para categoria.
 *
 * @return array
 */
function sg_icones_categoria() {
	return array(
		'imagens'           => __( 'Imagens e santos', 'sao-geronimo' ),
		'capelas-oratorios' => __( 'Capelas e oratórios', 'sao-geronimo' ),
		'luminarias'        => __( 'Luminárias', 'sao-geronimo' ),
		'difusores'         => __( 'Difusores', 'sao-geronimo' ),
		'baralhos'          => __( 'Baralhos e tarôs', 'sao-geronimo' ),
		'incensos'          => __( 'Incensos', 'sao-geronimo' ),
		'decoracao'         => __( 'Decoração', 'sao-geronimo' ),
		'fontes'            => __( 'Fontes de água', 'sao-geronimo' ),
		'sinos'             => __( 'Sinos e sinetas', 'sao-geronimo' ),
		'pedras'            => __( 'Pedras e cristais', 'sao-geronimo' ),
		'ervas'             => __( 'Ervas e banhos', 'sao-geronimo' ),
		'catolicos'         => __( 'Católicos', 'sao-geronimo' ),
		'velas'             => __( 'Velas', 'sao-geronimo' ),
		'casticais'         => __( 'Castiçais', 'sao-geronimo' ),
		'livros'            => __( 'Livros', 'sao-geronimo' ),
		'generico'          => __( 'Genérico (estrela)', 'sao-geronimo' ),
	);
}

/**
 * Devolve o SVG do ícone de uma categoria.
 *
 * @param int $term_id ID da categoria.
 * @return string
 */
function sg_icone_categoria( $term_id ) {
	$chave = get_term_meta( $term_id, '_sg_icone', true );
	if ( ! $chave ) {
		$termo = get_term( $term_id );
		$chave = ( $termo && ! is_wp_error( $termo ) ) ? $termo->slug : 'generico';
	}

	$svg = array(
		'imagens'           => '<path d="M12 3c2 0 3.2 1.4 3.2 3.2 0 1.4-.8 2.3-.8 3.3 0 .8.6 1.1 1.3 1.4 1.6.7 2.8 2 2.8 4.4V21H5.5v-5.7c0-2.4 1.2-3.7 2.8-4.4.7-.3 1.3-.6 1.3-1.4 0-1-.8-1.9-.8-3.3C8.8 4.4 10 3 12 3Z"/>',
		'capelas-oratorios' => '<path d="M4 21V9l8-6 8 6v12z"/><path d="M9.5 21v-6a2.5 2.5 0 0 1 5 0v6"/><path d="M12 6.5v3M10.6 8h2.8"/>',
		'luminarias'        => '<path d="M6 3h12l2.5 7h-17z"/><path d="M12 10v7"/><path d="M8.5 21h7"/><path d="M10 17h4v4h-4z"/>',
		'difusores'         => '<path d="M8 21h8a3 3 0 0 0 3-3v-3a7 7 0 0 0-14 0v3a3 3 0 0 0 3 3Z"/><path d="M12 8V3M10 5.5c1.5-.8 2.5.8 4-.5"/>',
		'baralhos'          => '<rect x="8" y="3" width="11" height="15" rx="2"/><path d="M5.5 6.5 4 8.2a2 2 0 0 0-.2 2.4l4.6 7.6"/><path d="m13.5 8-1.5 2.5 1.5 2.5"/>',
		'incensos'          => '<path d="M4 20h16"/><path d="M8 20v-3h8v3"/><path d="M12 17V5"/><path d="M12 5c-2-1.5-.5-3 0-4 .5 1 2 2.5 0 4Z"/>',
		'decoracao'         => '<path d="M12 3 9.6 9.6 3 12l6.6 2.4L12 21l2.4-6.6L21 12l-6.6-2.4z"/>',
		'fontes'            => '<path d="M4 20h16"/><path d="M6 20v-5h12v5"/><path d="M12 15V9"/><path d="M9 9h6"/><path d="M12 9V5a2 2 0 1 1 4 0"/>',
		'sinos'             => '<path d="M6.5 16.5h11L16 11a4 4 0 0 0-8 0z"/><path d="M12 7V4.5"/><path d="M10.5 19.5a1.5 1.5 0 0 0 3 0"/>',
		'pedras'            => '<path d="m12 3 6 4.5-2.5 11h-7L6 7.5z"/><path d="m6 7.5 6 3 6-3M12 10.5v8"/>',
		'ervas'             => '<path d="M12 21V9"/><path d="M12 13c-4 0-6-2.5-6-6 3.5 0 6 2 6 6Z"/><path d="M12 11c3 0 5-2 5-5-3 0-5 1.8-5 5Z"/>',
		'catolicos'         => '<path d="M12 3v18"/><path d="M6.5 8.5h11"/>',
		'velas'             => '<path d="M9 21h6v-11H9z"/><path d="M12 10V7"/><path d="M12 7c-1.5-1.2-.4-2.6 0-3.5.4.9 1.5 2.3 0 3.5Z"/>',
		'casticais'         => '<path d="M12 21V9"/><path d="M7 21h10"/><path d="M12 9c-1.4-1.2-.4-2.6 0-3.5.4.9 1.4 2.3 0 3.5Z"/><path d="M8 13h8"/>',
		'livros'            => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H12v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H12v16h5.5a2.5 2.5 0 0 1 2.5 2.5z"/>',
		'generico'          => '<path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/>',
	);

	$d = isset( $svg[ $chave ] ) ? $svg[ $chave ] : $svg['generico'];

	return '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/**
 * Campo do ícone no formulário de nova categoria.
 */
function sg_campo_icone_novo() {
	?>
	<div class="form-field">
		<label for="sg_icone"><?php esc_html_e( 'Ícone no site', 'sao-geronimo' ); ?></label>
		<select name="sg_icone" id="sg_icone">
			<?php foreach ( sg_icones_categoria() as $k => $r ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $r ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><?php esc_html_e( 'Usado no carrossel de categorias da página inicial.', 'sao-geronimo' ); ?></p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'sg_campo_icone_novo' );

/**
 * Campo do ícone na edição da categoria.
 *
 * @param WP_Term $termo Categoria.
 */
function sg_campo_icone_edita( $termo ) {
	$atual = get_term_meta( $termo->term_id, '_sg_icone', true );
	?>
	<tr class="form-field">
		<th><label for="sg_icone"><?php esc_html_e( 'Ícone no site', 'sao-geronimo' ); ?></label></th>
		<td>
			<select name="sg_icone" id="sg_icone">
				<?php foreach ( sg_icones_categoria() as $k => $r ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $atual, $k ); ?>><?php echo esc_html( $r ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Usado no carrossel de categorias da página inicial.', 'sao-geronimo' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'sg_campo_icone_edita' );

/**
 * Salva o ícone da categoria.
 *
 * @param int $term_id ID da categoria.
 */
function sg_salva_icone( $term_id ) {
	if ( isset( $_POST['sg_icone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		update_term_meta( $term_id, '_sg_icone', sanitize_key( wp_unslash( $_POST['sg_icone'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
}
add_action( 'created_product_cat', 'sg_salva_icone' );
add_action( 'edited_product_cat', 'sg_salva_icone' );


/* -------------------------------------------------------------------------
 * Página "Loja"
 * ---------------------------------------------------------------------- */

/**
 * Garante que existe uma página "Loja" ligada ao WooCommerce.
 *
 * Sem ela, o botão "Explorar produtos" e o item "Loja" do menu não levam a
 * lugar nenhum. Roda ao ativar o tema e, uma vez, quando o Woo já está ativo.
 */
function sg_garantir_pagina_loja() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}

	$id = (int) wc_get_page_id( 'shop' );
	if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
		return;
	}

	// Reaproveita uma página "loja" que já exista (ex.: criada pelo Woo e movida para a lixeira não conta).
	$existente = get_page_by_path( 'loja' );
	if ( $existente && 'publish' === $existente->post_status ) {
		$id = (int) $existente->ID;
	} else {
		$id = (int) wp_insert_post( array(
			'post_title'   => __( 'Loja', 'sao-geronimo' ),
			'post_name'    => 'loja',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '',
		) );
	}

	if ( $id > 0 ) {
		update_option( 'woocommerce_shop_page_id', $id );
		flush_rewrite_rules( false );
	}
}
add_action( 'after_switch_theme', 'sg_garantir_pagina_loja', 20 );
add_action( 'init', function () {
	if ( get_option( 'sg_loja_verificada' ) !== SG_VERSAO ) {
		sg_garantir_pagina_loja();
		update_option( 'sg_loja_verificada', SG_VERSAO );
	}
}, 30 );
