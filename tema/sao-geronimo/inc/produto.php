<?php
/**
 * Página do produto — igual à do site original.
 *
 * Ordem na coluna da direita (cada número é a "prioridade" do gancho):
 *   4 linha pequena · 5 nome · 6 nota e referência · 10 preço · 30 botão de compra
 *   36 selos (se ligados) · 40 "Sobre o produto" · 42 blocos (notas, composição, modo de usar)
 *   50 ficha técnica · 60 calcular frete
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* O que o WooCommerce põe por conta própria sai de cena; o tema desenha tudo. */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/**
 * Ícone de estrela usado nas notas.
 *
 * @return string
 */
function sg_estrela() {
	return '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/></svg>';
}

/**
 * Texto do peso como o cliente lê ("165 g", "1,2 kg").
 *
 * @param WC_Product $produto Produto.
 * @return string
 */
function sg_peso_texto( $produto ) {
	$txt = trim( (string) get_post_meta( $produto->get_id(), '_sg_peso_txt', true ) );
	if ( $txt ) {
		return trim( preg_replace( '/^[^:]*:\s*/u', '', $txt ) ); // "Peso líquido: 45 g" → "45 g"
	}
	if ( ! $produto->get_weight() ) {
		return '';
	}
	$kg = wc_get_weight( (float) $produto->get_weight(), 'kg' );
	if ( $kg < 1 ) {
		return number_format_i18n( $kg * 1000, 0 ) . ' g';
	}
	return number_format_i18n( $kg, 2 ) . ' kg';
}

/**
 * Texto das dimensões ("3,5 × 11 × 3,5 cm …"); vazio se não houver.
 *
 * @param WC_Product $produto Produto.
 * @return string
 */
function sg_dimensoes_texto( $produto ) {
	$txt = trim( (string) get_post_meta( $produto->get_id(), '_sg_dimensoes', true ) );
	if ( ! $txt && $produto->has_dimensions() ) {
		$txt = wc_format_dimensions( $produto->get_dimensions( false ) );
	}
	return $txt;
}

/* -------------------------------------------------------------------------
 * Preço, com Pix e parcelas
 * ---------------------------------------------------------------------- */

/**
 * Bloco de preço.
 */
function sg_pdp_preco() {
	global $product;
	if ( ! $product ) {
		return;
	}

	if ( 'yes' === get_post_meta( $product->get_id(), '_sg_sob_consulta', true ) || ! (float) $product->get_price() ) {
		echo '<div class="pdp__preco"><b style="font-size:1.4rem">' . esc_html__( 'Preço sob consulta', 'sao-geronimo' ) . '</b>';
		echo '<small>' . esc_html__( 'Este produto acabou de chegar. Fale com a gente pelo WhatsApp para saber o valor e a disponibilidade.', 'sao-geronimo' ) . '</small></div>';
		return;
	}

	$preco = (float) $product->get_price();
	$desc  = (float) sg_opt( 'pix_desconto', 5 );
	$n     = sg_parcelas();

	echo '<div class="pdp__preco"><b>' . esc_html( sg_preco_texto( $preco ) ) . '</b><small>';
	$partes = array();
	if ( $desc > 0 ) {
		$partes[] = sprintf(
			/* translators: 1: preço no Pix, 2: percentual */
			esc_html__( '%1$s no Pix, com %2$s%% de desconto', 'sao-geronimo' ),
			esc_html( sg_preco_texto( sg_preco_pix( $preco ) ) ),
			esc_html( number_format_i18n( $desc, 0 ) )
		);
	}
	if ( $n > 1 ) {
		$partes[] = sprintf(
			/* translators: 1: parcelas, 2: valor da parcela */
			esc_html__( 'ou %1$dx de %2$s sem juros', 'sao-geronimo' ),
			(int) $n,
			esc_html( sg_preco_texto( $preco / $n ) )
		);
	}
	echo implode( ' · ', $partes ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</small></div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_pdp_preco', 10 );

/* -------------------------------------------------------------------------
 * "Sobre o produto" (medidas + descrição) e os blocos seguintes
 * ---------------------------------------------------------------------- */

/**
 * Bloco "Sobre o produto": medidas e o texto de descrição.
 */
function sg_pdp_sobre() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$id     = $product->get_id();
	$titulo = trim( (string) get_post_meta( $id, '_sg_sobre_titulo', true ) );
	$titulo = $titulo ? $titulo : __( 'Sobre o produto', 'sao-geronimo' );
	$dim    = sg_dimensoes_texto( $product );
	$peso   = sg_peso_texto( $product );
	$desc   = trim( (string) $product->get_description() );
	$m_tit  = trim( (string) get_post_meta( $id, '_sg_med_titulo', true ) );
	$m_tit  = $m_tit ? $m_tit : __( 'Dimensões', 'sao-geronimo' );
	$aviso  = trim( (string) get_post_meta( $id, '_sg_med_aviso', true ) );

	echo '<div class="ficha"><h4>' . esc_html( $titulo ) . '</h4>';

	echo '<div class="medidas">' . sg_icone( 'regua', 20 ) . '<div><b>' . esc_html( $m_tit ) . '</b>'; // phpcs:ignore WordPress.Security.EscapeOutput
	if ( $dim ) {
		echo '<span>' . esc_html( $dim ) . '</span>';
	} else {
		$msg = sprintf(
			/* translators: 1: nome, 2: referência */
			__( 'Olá! Gostaria de saber as medidas do produto %1$s (Ref. %2$s).', 'sao-geronimo' ),
			$product->get_name(),
			$product->get_sku()
		);
		$url = sg_whatsapp_link( $msg );
		echo '<span>' . esc_html__( 'Consulte as medidas exatas', 'sao-geronimo' ) . ' ';
		echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'pelo WhatsApp', 'sao-geronimo' ) . '</a>.' : '.';
		echo '</span>';
	}
	if ( $dim && $aviso ) {
		$zap = sg_whatsapp_link( sprintf(
			/* translators: 1: nome, 2: referência */
			__( 'Olá! Gostaria de saber as medidas do produto %1$s (Ref. %2$s).', 'sao-geronimo' ),
			$product->get_name(),
			$product->get_sku()
		) );
		echo '<small>' . esc_html( $aviso ) . ' ';
		echo $zap ? '<a href="' . esc_url( $zap ) . '" target="_blank" rel="noopener">' . esc_html__( 'Pergunte no WhatsApp', 'sao-geronimo' ) . '</a>.' : '';
		echo '</small>';
	} elseif ( $peso ) {
		// Se o texto já traz o rótulo ("Peso líquido: 45 g"), usa como está.
		$linha = trim( (string) get_post_meta( $id, '_sg_peso_txt', true ) );
		$linha = ( $linha && false !== strpos( $linha, ':' ) ) ? $linha : __( 'Peso:', 'sao-geronimo' ) . ' ' . $peso;
		echo '<small>' . esc_html( $linha ) . '</small>';
	}
	echo '</div></div>';

	if ( $desc ) {
		echo wp_kses_post( wpautop( $desc ) );
	}
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_pdp_sobre', 40 );

/**
 * Notas olfativas, composição e modo de usar — cada um num bloco.
 */
function sg_pdp_blocos() {
	global $product;
	if ( ! $product ) {
		return;
	}
	$blocos = array(
		__( 'Notas olfativas', 'sao-geronimo' ) => get_post_meta( $product->get_id(), '_sg_notas', true ),
		__( 'Composição', 'sao-geronimo' )      => get_post_meta( $product->get_id(), '_sg_composicao', true ),
		__( 'Modo de usar', 'sao-geronimo' )    => get_post_meta( $product->get_id(), '_sg_modo_usar', true ),
	);
	foreach ( $blocos as $tit => $txt ) {
		if ( ! trim( (string) $txt ) ) {
			continue;
		}
		echo '<div class="ficha"><h4>' . esc_html( $tit ) . '</h4>' . wp_kses_post( wpautop( $txt ) ) . '</div>';
	}
}
add_action( 'woocommerce_single_product_summary', 'sg_pdp_blocos', 42 );

/**
 * Linhas da ficha técnica: [rótulo, valor].
 *
 * @param WC_Product $produto Produto.
 * @return array
 */
function sg_ficha_linhas( $produto ) {
	$id     = $produto->get_id();
	$linhas = array();

	if ( $produto->get_sku() ) {
		$linhas[] = array( __( 'Referência', 'sao-geronimo' ), $produto->get_sku() );
	}
	$marca = trim( (string) get_post_meta( $id, '_sg_marca', true ) );
	if ( $marca ) {
		$linhas[] = array( __( 'Marca', 'sao-geronimo' ), $marca );
	}

	$cat  = get_the_terms( $id, 'product_cat' );
	$tags = get_the_terms( $id, 'product_tag' );
	if ( $cat && ! is_wp_error( $cat ) ) {
		$txt = $cat[0]->name;
		if ( $tags && ! is_wp_error( $tags ) ) {
			$txt .= ' › ' . $tags[0]->name;
		}
		$linhas[] = array( __( 'Categoria', 'sao-geronimo' ), $txt );
	}

	$dim      = sg_dimensoes_texto( $produto );
	$conteudo = trim( (string) get_post_meta( $id, '_sg_conteudo', true ) );
	$extra    = trim( (string) get_post_meta( $id, '_sg_ficha', true ) );

	if ( $extra ) {
		foreach ( preg_split( '/\r\n|\r|\n/', $extra ) as $l ) {
			$partes = explode( ':', $l, 2 );
			if ( 2 !== count( $partes ) || ! trim( $partes[1] ) ) {
				continue;
			}
			$rot = trim( $partes[0] );
			$val = trim( $partes[1] );
			if ( 'Dimensões' === $rot ) {
				$val = $dim ? $dim : $val;
			} elseif ( 'Conteúdo' === $rot && $conteudo ) {
				$val = $conteudo;
			}
			$linhas[] = array( $rot, $val );
		}
	} else {
		// Produto cadastrado à mão: monta com o que houver.
		if ( $conteudo ) {
			$linhas[] = array( __( 'Conteúdo', 'sao-geronimo' ), $conteudo );
		}
		$peso = sg_peso_texto( $produto );
		if ( $peso ) {
			$linhas[] = array( __( 'Peso', 'sao-geronimo' ), $peso );
		}
		$linhas[] = array( __( 'Dimensões', 'sao-geronimo' ), $dim ? $dim : __( 'sob consulta', 'sao-geronimo' ) );
	}

	return $linhas;
}

/**
 * Ficha técnica.
 */
function sg_pdp_ficha() {
	global $product;
	if ( ! $product ) {
		return;
	}
	echo '<div class="ficha"><h4>' . esc_html__( 'Ficha técnica', 'sao-geronimo' ) . '</h4><dl>';
	foreach ( sg_ficha_linhas( $product ) as $l ) {
		echo '<dt>' . esc_html( $l[0] ) . '</dt><dd>' . esc_html( $l[1] ) . '</dd>';
	}
	echo '</dl></div>';
}
add_action( 'woocommerce_single_product_summary', 'sg_pdp_ficha', 50 );

/**
 * Caixa de cálculo de frete por CEP.
 */
function sg_pdp_frete() {
	global $product;
	if ( ! $product || 'nao' === sg_opt( 'produto_frete', 'sim' ) || $product->is_virtual() ) {
		return;
	}
	?>
	<div class="frete-box">
		<b style="font-size:14px"><?php esc_html_e( 'Calcular frete e prazo', 'sao-geronimo' ); ?></b>
		<form data-frete data-produto="<?php echo (int) $product->get_id(); ?>">
			<input class="inp" name="cep" placeholder="00000-000" maxlength="9" inputmode="numeric" autocomplete="postal-code" required>
			<button class="btn btn--vazado" type="submit"><?php esc_html_e( 'Calcular', 'sao-geronimo' ); ?></button>
		</form>
		<div class="frete-res" data-frete-res aria-live="polite"></div>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'sg_pdp_frete', 60 );

/* -------------------------------------------------------------------------
 * Galeria
 * ---------------------------------------------------------------------- */

/**
 * IDs das imagens do produto: principal primeiro, depois a galeria.
 *
 * @param WC_Product $produto Produto.
 * @return array
 */
function sg_pdp_imagens( $produto ) {
	$ids = array();
	if ( $produto->get_image_id() ) {
		$ids[] = (int) $produto->get_image_id();
	}
	foreach ( $produto->get_gallery_image_ids() as $g ) {
		$ids[] = (int) $g;
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Produtos do "Quem viu, levou também": mesma categoria, sem o atual.
 *
 * @param WC_Product $produto Produto.
 * @param int        $quantos Quantidade.
 * @return array IDs.
 */
function sg_pdp_relacionados( $produto, $quantos = 4 ) {
	$cats = get_the_terms( $produto->get_id(), 'product_cat' );
	$ids  = array();
	if ( $cats && ! is_wp_error( $cats ) ) {
		$ids = wc_get_products( array(
			'status'   => 'publish',
			'limit'    => $quantos,
			'exclude'  => array( $produto->get_id() ),
			'category' => array( $cats[0]->slug ),
			'orderby'  => 'rand',
			'return'   => 'ids',
		) );
	}
	if ( count( $ids ) < $quantos ) {
		$mais = wc_get_related_products( $produto->get_id(), $quantos );
		$ids  = array_slice( array_unique( array_merge( $ids, $mais ) ), 0, $quantos );
	}
	return $ids;
}
