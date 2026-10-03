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

// O tema já desenha o título na capa; evita o título duplicado do WooCommerce.
add_filter( 'woocommerce_show_page_title', '__return_false' );
add_action( 'woocommerce_before_main_content', 'sg_wc_abre', 10 );
add_action( 'woocommerce_after_main_content', 'sg_wc_fecha', 10 );

/**
 * A loja usa os filtros do site original (lista de categorias) ou os filtros
 * completos (preço, linha, ofertas)? Escolha em Meu Site → Loja.
 *
 * @return bool
 */
function sg_filtros_classicos() {
	return 'completo' !== sg_opt( 'loja_filtros_modo', 'original' );
}

/**
 * Abertura das páginas do Woo.
 *
 * Loja: capa, migalhas, lista de categorias à esquerda e a grade à direita.
 * Categoria: capa, migalhas, pílulas de subcategoria e a grade (como no site original).
 */
function sg_wc_abre() {
	$e_loja = function_exists( 'is_shop' ) && is_shop();
	$e_lista = $e_loja || is_product_category() || is_product_tag();

	if ( $e_lista ) {
		$titulo = $e_loja ? sg_opt( 'loja_titulo', get_the_title( wc_get_page_id( 'shop' ) ) ) : single_term_title( '', false );
		$desc   = $e_loja ? (string) sg_opt( 'loja_desc', __( 'Todo o catálogo da São Gerônimo em um só lugar.', 'sao-geronimo' ) ) : '';
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

	// A página do produto desenha as próprias seções (woocommerce/content-single-product.php).
	if ( function_exists( 'is_product' ) && is_product() ) {
		return;
	}

	echo '<section class="sec sec--curto" style="padding-top:' . ( $e_lista ? '0' : '8px' ) . '"><div class="wrap">';

	if ( ! $e_lista ) {
		return;
	}

	if ( sg_filtros_classicos() ) {
		if ( $e_loja ) {
			echo '<div class="loja">';
			sg_wc_filtros_classicos();
			echo '<div>';
		} else {
			sg_wc_pilulas();
		}
		sg_wc_barra( $e_loja );
		return;
	}

	echo '<div class="loja-layout">';
	sg_wc_filtros();
	echo '<div class="loja-main">';
	sg_wc_barra( true );
}

/**
 * Barra lateral do site original: "Categorias" com a contagem de cada uma.
 */
function sg_wc_filtros_classicos() {
	$cats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		'orderby'    => 'meta_value_num',
		'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( is_wp_error( $cats ) || ! $cats ) {
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name' ) );
	}
	$total = (int) wp_count_posts( 'product' )->publish;

	echo '<aside class="filtros filtros--classico"><h4>' . esc_html__( 'Categorias', 'sao-geronimo' ) . '</h4><ul>';
	echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="on">' . esc_html__( 'Todas', 'sao-geronimo' ) . ' <span>' . (int) $total . '</span></a></li>';
	if ( $cats && ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			if ( 'uncategorized' === $c->slug || 'sem-categoria' === $c->slug ) {
				continue;
			}
			echo '<li><a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . ' <span>' . (int) $c->count . '</span></a></li>';
		}
	}
	echo '</ul></aside>';
}

/**
 * Pílulas que filtram a grade por subcategoria (as etiquetas dos produtos da categoria).
 *
 * Cada pílula é um link (?sg_sub=slug), então o filtro vale para a categoria
 * inteira, não só para a página que está na tela.
 */
function sg_wc_pilulas() {
	$termo = get_queried_object();
	if ( ! $termo || empty( $termo->term_id ) || ! is_product_category() ) {
		return;
	}

	$ids = wc_get_products( array(
		'status'   => 'publish',
		'limit'    => -1,
		'category' => array( $termo->slug ),
		'return'   => 'ids',
	) );
	if ( ! $ids ) {
		return;
	}
	$tags = wp_get_object_terms( $ids, 'product_tag' );
	if ( is_wp_error( $tags ) || count( $tags ) < 2 ) {
		return;
	}

	$ordem = array_filter( explode( '|', (string) get_term_meta( $termo->term_id, '_sg_subs_ordem', true ) ) );
	usort( $tags, function ( $a, $b ) use ( $ordem ) {
		$pa = array_search( $a->name, $ordem, true );
		$pb = array_search( $b->name, $ordem, true );
		$pa = false === $pa ? 999 : $pa;
		$pb = false === $pb ? 999 : $pb;
		return $pa === $pb ? strcmp( $a->name, $b->name ) : $pa <=> $pb;
	} );

	$base  = get_term_link( $termo );
	$ativo = sg_filtro( 'sg_sub' );

	echo '<div class="pilulas" style="margin-bottom:32px">';
	echo '<a class="pilula' . ( $ativo ? '' : ' on' ) . '" href="' . esc_url( $base ) . '" data-sub="">' . esc_html__( 'Todos', 'sao-geronimo' ) . '</a>';
	foreach ( $tags as $t ) {
		$on = in_array( $t->slug, (array) $ativo, true );
		echo '<a class="pilula' . ( $on ? ' on' : '' ) . '" href="' . esc_url( add_query_arg( 'sg_sub', $t->slug, $base ) ) . '" data-sub="' . esc_attr( $t->name ) . '">' . esc_html( $t->name ) . '</a>';
	}
	echo '</div>';
}

/**
 * Barra com a contagem de produtos e a ordenação.
 *
 * @param bool $com_ordem Mostra o seletor de ordem (a loja mostra; a categoria não).
 */
function sg_wc_barra( $com_ordem = true ) {
	global $wp_query;
	$n = (int) $wp_query->found_posts;

	echo '<div class="barra-loja">';
	if ( ! sg_filtros_classicos() ) {
		sg_wc_filtros_topo_botao();
	}
	printf(
		'<span class="contagem" data-contagem>%s</span>',
		esc_html( sprintf( _n( '%d produto', '%d produtos', $n, 'sao-geronimo' ), $n ) )
	);
	if ( $com_ordem ) {
		// Sem JavaScript o select precisa de um botão para enviar.
		ob_start();
		woocommerce_catalog_ordering();
		$ord = ob_get_clean();
		echo str_replace( '</form>', '<noscript><button type="submit" class="btn btn--azul">' . esc_html__( 'Ordenar', 'sao-geronimo' ) . '</button></noscript></form>', $ord ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
	if ( ! sg_filtros_classicos() ) {
		sg_wc_filtros_topo();
	}
}

/**
 * Fechamento das páginas do Woo.
 */
function sg_wc_fecha() {
	if ( function_exists( 'is_product' ) && is_product() ) {
		return;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		echo '</div></div>'; // fecha a coluna da grade e .loja / .loja-layout
	} elseif ( ! sg_filtros_classicos() && ( is_product_category() || is_product_tag() ) ) {
		echo '</div></div>';
	}
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
	return round( $preco * ( 100 - $desc ) ) / 100; // meio centavo sobe (pode diferir 1 centavo do site antigo em alguns preços)
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
 * Nota (estrelas) mostrada no cartão e na página do produto.
 *
 * Vale a média real das avaliações; se ainda não há avaliações, vale a nota
 * de vitrine preenchida na aba "São Gerônimo" do produto (como no site antigo).
 *
 * @param WC_Product $product Produto.
 * @return float
 */
function sg_nota( $product ) {
	$real = (float) $product->get_average_rating();
	if ( $real > 0 ) {
		return $real;
	}
	return (float) str_replace( ',', '.', (string) get_post_meta( $product->get_id(), '_sg_nota', true ) );
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
	$nota  = sg_nota( $product );

	if ( ! $marca && ! $sku && ! $nota ) {
		return;
	}

	echo '<div class="prod__nota" style="margin-top:10px">';
	if ( $nota > 0 ) {
		for ( $i = 0; $i < 5; $i++ ) {
			echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/></svg>';
		}
		echo ' ' . esc_html( number_format( $nota, 1, '.', '' ) );
	}
	$txt = array_filter( array( $marca, $sku ? 'Ref. ' . $sku : '' ) );
	if ( $txt ) {
		echo ( $nota > 0 ? ' · ' : '' ) . esc_html( implode( ' · ', $txt ) );
	}
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_wc_marca_ref', 6 );

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

/**
 * Garante a página "Categorias" (a vitrine com todas as categorias).
 */
function sg_garantir_pagina_categorias() {
	$p = get_page_by_path( 'categorias' );
	if ( $p && 'publish' === $p->post_status ) {
		return;
	}
	wp_insert_post( array(
		'post_title'   => __( 'Categorias', 'sao-geronimo' ),
		'post_name'    => 'categorias',
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_content' => '',
	) );
}
add_action( 'after_switch_theme', 'sg_garantir_pagina_categorias', 25 );
add_action( 'init', function () {
	if ( get_option( 'sg_categorias_verificada' ) !== SG_VERSAO ) {
		sg_garantir_pagina_categorias();
		update_option( 'sg_categorias_verificada', SG_VERSAO );
	}
}, 31 );


/* -------------------------------------------------------------------------
 * Preço em HTML simples
 * ---------------------------------------------------------------------- */

/**
 * Preço sempre em uma linha só: "R$ 35,90".
 *
 * O HTML padrão do WooCommerce embrulha símbolo e valor em vários <span>/<bdi>,
 * e qualquer CSS que mexa em <span> acaba quebrando o preço em duas linhas ou
 * encolhendo o valor. Aqui o preço sai como texto puro, dentro de um único
 * elemento que nunca quebra.
 *
 * @param float $valor Valor.
 * @return string
 */
function sg_moeda( $valor ) {
	return str_replace( ' ', "\xC2\xA0", sg_preco_texto( $valor ) );
}

/**
 * Troca o HTML do preço por uma versão simples.
 *
 * @param string     $html    HTML original.
 * @param WC_Product $product Produto.
 * @return string
 */
function sg_wc_preco_simples( $html, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $html;
	}
	if ( 'yes' === get_post_meta( $product->get_id(), '_sg_sob_consulta', true ) || '' === $product->get_price() ) {
		return $html;
	}

	if ( $product->is_type( 'variable' ) ) {
		$min = (float) $product->get_variation_price( 'min', true );
		$max = (float) $product->get_variation_price( 'max', true );
		$txt = '<span class="sg-preco"><span class="sg-preco__por">' . esc_html( sg_moeda( $min ) ) . '</span>';
		if ( $max > $min ) {
			$txt .= '<span class="sg-preco__ate"> – ' . esc_html( sg_moeda( $max ) ) . '</span>';
		}
		return $txt . '</span>';
	}

	if ( $product->is_type( 'grouped' ) ) {
		return $html;
	}

	$atual = (float) wc_get_price_to_display( $product );
	$cheio = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );

	$txt = '<span class="sg-preco">';
	if ( $product->is_on_sale() && $cheio > $atual ) {
		$txt .= '<del class="sg-preco__de">' . esc_html( sg_moeda( $cheio ) ) . '</del> ';
	}
	return $txt . '<span class="sg-preco__por">' . esc_html( sg_moeda( $atual ) ) . '</span></span>';
}
add_filter( 'woocommerce_get_price_html', 'sg_wc_preco_simples', 99, 2 );

/**
 * Opções de ordenação com os mesmos nomes do site original.
 *
 * @param array $opcoes Opções padrão do WooCommerce.
 * @return array
 */
function sg_wc_opcoes_ordem( $opcoes ) {
	return array(
		'menu_order' => __( 'Relevância', 'sao-geronimo' ),
		'title'      => __( 'Nome A–Z', 'sao-geronimo' ),
		'price'      => __( 'Menor preço', 'sao-geronimo' ),
		'price-desc' => __( 'Maior preço', 'sao-geronimo' ),
	);
}
add_filter( 'woocommerce_catalog_orderby', 'sg_wc_opcoes_ordem' );

/**
 * "Nome A–Z": o WooCommerce chama de "title"; garante a ordem crescente.
 *
 * @param array $args Argumentos.
 * @return array
 */
function sg_wc_ordem_nome( $args ) {
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_GET['orderby'] ) && 'title' === $_GET['orderby'] ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'sg_wc_ordem_nome', 20 );
