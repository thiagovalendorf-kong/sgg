<?php
/**
 * Filtros laterais da loja.
 *
 * Funcionam por endereço (?sg_cat[]=velas&sg_min=10…), então o cliente pode
 * compartilhar o link já filtrado e o botão "voltar" do navegador funciona.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lê um filtro do endereço, já limpo.
 *
 * @param string $chave Nome do parâmetro.
 * @return array|string
 */
function sg_filtro( $chave ) {
	// phpcs:disable WordPress.Security.NonceVerification
	// A faixa rápida ("0-30") vale como mínimo e máximo, se nada foi digitado.
	if ( in_array( $chave, array( 'sg_min', 'sg_max' ), true ) && empty( $_GET[ $chave ] ) && ! empty( $_GET['sg_faixa'] ) ) {
		$par = is_string( $_GET['sg_faixa'] ) ? explode( '-', wp_unslash( $_GET['sg_faixa'] ) ) : array();
		if ( 2 === count( $par ) ) {
			$n = 'sg_min' === $chave ? $par[0] : $par[1];
			return '' === $n ? '' : max( 0, (float) $n );
		}
	}
	if ( ! isset( $_GET[ $chave ] ) ) {
		return in_array( $chave, array( 'sg_cat', 'sg_sub' ), true ) ? array() : '';
	}
	$v = wp_unslash( $_GET[ $chave ] );
	// phpcs:enable
	if ( in_array( $chave, array( 'sg_cat', 'sg_sub' ), true ) ) {
		// Só lista simples de textos; listas aninhadas (sg_cat[][]=) são ignoradas.
		return array_values( array_filter( array_map( 'sanitize_title', array_filter( (array) $v, 'is_string' ) ) ) );
	}
	if ( is_array( $v ) ) {
		return '';
	}
	if ( in_array( $chave, array( 'sg_min', 'sg_max' ), true ) ) {
		return '' === $v ? '' : max( 0, (float) str_replace( ',', '.', (string) $v ) );
	}
	if ( 'sg_faixa' === $chave ) {
		return preg_match( '/^\d*-\d*$/', (string) $v ) ? (string) $v : '';
	}
	return '1' === (string) $v ? '1' : '';
}

/**
 * Monta os trechos de consulta dos filtros ativos.
 *
 * @param array $excluir Filtros a ignorar (cat, sub, preco, estoque, oferta).
 * @return array tax, meta e ids.
 */
function sg_filtros_partes( $excluir = array() ) {
	$tax  = array();
	$meta = array();
	$ids  = null;

	$cats = in_array( 'cat', $excluir, true ) ? array() : sg_filtro( 'sg_cat' );
	$subs = in_array( 'sub', $excluir, true ) ? array() : sg_filtro( 'sg_sub' );
	if ( $cats ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $cats );
	}
	if ( $subs ) {
		$tax[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $subs );
	}

	$min = sg_filtro( 'sg_min' );
	$max = sg_filtro( 'sg_max' );
	if ( ! in_array( 'preco', $excluir, true ) && ( '' !== $min || '' !== $max ) ) {
		$meta[] = array(
			'key'     => '_price',
			'value'   => array( '' === $min ? 0 : $min, '' === $max ? 99999999 : $max ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	}
	if ( ! in_array( 'estoque', $excluir, true ) && sg_filtro( 'sg_estoque' ) ) {
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
	}
	if ( ! in_array( 'oferta', $excluir, true ) && sg_filtro( 'sg_oferta' ) ) {
		$ids = wc_get_product_ids_on_sale();
		$ids = $ids ? $ids : array( 0 );
	}
	return array( 'tax' => $tax, 'meta' => $meta, 'ids' => $ids );
}

/**
 * Aplica os filtros na consulta de produtos.
 *
 * @param WP_Query $q Consulta.
 */
function sg_filtros_consulta( $q ) {
	$p = sg_filtros_partes();
	if ( $p['tax'] ) {
		$q->set( 'tax_query', array_merge( (array) $q->get( 'tax_query' ), $p['tax'] ) );
	}
	if ( $p['meta'] ) {
		$q->set( 'meta_query', array_merge( (array) $q->get( 'meta_query' ), $p['meta'] ) );
	}
	if ( null !== $p['ids'] ) {
		$q->set( 'post__in', $p['ids'] );
	}
}
add_action( 'woocommerce_product_query', 'sg_filtros_consulta' );

/**
 * Menor e maior preço do catálogo (limites do campo de preço).
 *
 * @return array
 */
function sg_faixa_precos() {
	$cache = get_transient( 'sg_faixa_precos' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	global $wpdb;
	$r = $wpdb->get_row( "SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS min, MAX(CAST(meta_value AS DECIMAL(10,2))) AS max FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_price' AND pm.meta_value <> '' AND p.post_status = 'publish' AND p.post_type = 'product'" ); // phpcs:ignore WordPress.DB
	$faixa = array( floor( (float) ( $r->min ?? 0 ) ), ceil( (float) ( $r->max ?? 0 ) ) );
	set_transient( 'sg_faixa_precos', $faixa, HOUR_IN_SECONDS );
	return $faixa;
}
/**
 * Zera os caches dos filtros (faixa de preço e contagens). A versão entra na
 * chave das contagens, então basta mudar o número.
 */
function sg_limpar_cache_filtros() {
	static $feito = false;
	if ( $feito ) {
		return;
	}
	$feito = true;
	delete_transient( 'sg_faixa_precos' );
	update_option( 'sg_filtros_v', (string) microtime( true ), false );
}
add_action( 'woocommerce_update_product', 'sg_limpar_cache_filtros' );
add_action( 'woocommerce_new_product', 'sg_limpar_cache_filtros' );
add_action( 'woocommerce_update_product_variation', 'sg_limpar_cache_filtros' );
add_action( 'woocommerce_new_product_variation', 'sg_limpar_cache_filtros' );
add_action( 'trashed_post', 'sg_limpar_cache_filtros' );
add_action( 'untrashed_post', 'sg_limpar_cache_filtros' );
add_action( 'deleted_post', 'sg_limpar_cache_filtros' );
add_action( 'sg_filtros_limpar_cache', 'sg_limpar_cache_filtros' );
// Atualização direta de preço/estoque (importação, catálogo sem WooCommerce).
add_action(
	'updated_post_meta',
	function ( $meta_id, $post_id, $chave ) {
		if ( in_array( $chave, array( '_price', '_regular_price', '_sale_price', '_stock_status' ), true ) ) {
			sg_limpar_cache_filtros();
		}
	},
	10,
	3
);

/**
 * Tira da URL os parâmetros de filtro vazios (sem JS o formulário envia sg_min= e sg_max=).
 */
function sg_filtros_url_limpa() {
	if ( is_admin() || empty( $_GET ) || ( ! is_shop() && ! is_product_taxonomy() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$sujos = array();
	foreach ( array( 'sg_min', 'sg_max', 'sg_faixa' ) as $k ) {
		if ( isset( $_GET[ $k ] ) && '' === $_GET[ $k ] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$sujos[] = $k;
		}
	}
	if ( $sujos ) {
		wp_safe_redirect( remove_query_arg( $sujos ), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'sg_filtros_url_limpa', 5 );

/**
 * Endereço da página atual sem os filtros.
 *
 * @return string
 */
function sg_url_base_loja() {
	if ( is_product_taxonomy() ) {
		$o = get_queried_object();
		return $o ? get_term_link( $o ) : wc_get_page_permalink( 'shop' );
	}
	return wc_get_page_permalink( 'shop' );
}

/**
 * Quantidade de filtros ativos (preço conta como um só).
 *
 * @return int
 */
function sg_filtros_ativos() {
	return count( sg_filtro( 'sg_cat' ) ) + count( sg_filtro( 'sg_sub' ) )
		+ ( ( '' !== sg_filtro( 'sg_min' ) || '' !== sg_filtro( 'sg_max' ) ) ? 1 : 0 )
		+ ( sg_filtro( 'sg_oferta' ) ? 1 : 0 ) + ( sg_filtro( 'sg_estoque' ) ? 1 : 0 );
}

/**
 * Filtros e ordenação que estão valendo, como lista de parâmetros do endereço.
 *
 * @return array
 */
function sg_filtros_args() {
	$a = array();
	if ( sg_filtro( 'sg_cat' ) ) {
		$a['sg_cat'] = array_values( sg_filtro( 'sg_cat' ) );
	}
	if ( sg_filtro( 'sg_sub' ) ) {
		$a['sg_sub'] = array_values( sg_filtro( 'sg_sub' ) );
	}
	$min = sg_filtro( 'sg_min' );
	$max = sg_filtro( 'sg_max' );
	if ( '' !== $min ) {
		$a['sg_min'] = $min;
	}
	if ( '' !== $max ) {
		$a['sg_max'] = $max;
	}
	if ( sg_filtro( 'sg_oferta' ) ) {
		$a['sg_oferta'] = '1';
	}
	if ( sg_filtro( 'sg_estoque' ) ) {
		$a['sg_estoque'] = '1';
	}
	if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$o = sanitize_key( wp_unslash( $_GET['orderby'] ) );
		if ( $o ) {
			$a['orderby'] = $o;
		}
	}
	return $a;
}

/**
 * Endereço da loja com um conjunto de parâmetros.
 *
 * @param array $args Parâmetros (de sg_filtros_args).
 * @return string
 */
function sg_url_filtros( $args ) {
	$url = sg_url_base_loja();
	if ( ! $args ) {
		return $url;
	}
	// Listas saem como sg_cat[]=a&sg_cat[]=b (endereço limpo, igual ao do JS).
	$qs = preg_replace( '/%5B\d+%5D/', '%5B%5D', http_build_query( $args ) );
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $qs;
}

/**
 * Endereço atual sem um filtro (ou sem um valor dele). Usado no "×" das etiquetas.
 *
 * @param string|array $chaves Parâmetro(s) a tirar por inteiro.
 * @param string       $valor  Se informado, tira só este valor de uma lista.
 * @return string
 */
function sg_url_sem( $chaves, $valor = null ) {
	$a = sg_filtros_args();
	foreach ( (array) $chaves as $k ) {
		if ( null !== $valor && isset( $a[ $k ] ) && is_array( $a[ $k ] ) ) {
			$a[ $k ] = array_values( array_diff( $a[ $k ], array( $valor ) ) );
			if ( ! $a[ $k ] ) {
				unset( $a[ $k ] );
			}
		} else {
			unset( $a[ $k ] );
		}
	}
	return sg_url_filtros( $a );
}

/**
 * Cortes de preço para as faixas rápidas, em números redondos.
 *
 * @param array $faixa Menor e maior preço.
 * @return array
 */
function sg_cortes_preco( $faixa ) {
	$max   = max( 40, (float) $faixa[1] );
	$arred = function ( $v ) {
		$passo = $v >= 200 ? 50 : ( $v >= 60 ? 10 : 5 );
		return max( $passo, (int) ( round( $v / $passo ) * $passo ) );
	};
	$c = array_values( array_unique( array( $arred( $max * 0.12 ), $arred( $max * 0.3 ), $arred( $max * 0.6 ) ) ) );
	sort( $c );
	return $c;
}

/**
 * Preço em reais para os rótulos (sem centavos quando for redondo).
 *
 * @param float $v Valor.
 * @return string
 */
function sg_preco_curto( $v ) {
	$v = (float) $v;
	return 'R$ ' . ( floor( $v ) === $v ? number_format( $v, 0, ',', '.' ) : number_format( $v, 2, ',', '.' ) );
}

/**
 * Rótulo de faixa de preço: "Até R$ 45", "R$ 45 a R$ 120" ou "Acima de R$ 120".
 *
 * @param float|string $de  Mínimo (0 = sem mínimo).
 * @param float|string $ate Máximo ('' = sem máximo).
 * @return string
 */
function sg_rotulo_faixa( $de, $ate ) {
	if ( '' === $ate ) {
		/* translators: %s: preço */
		return sprintf( __( 'Acima de %s', 'sao-geronimo' ), sg_preco_curto( $de ) );
	}
	if ( ! (float) $de ) {
		/* translators: %s: preço */
		return sprintf( __( 'Até %s', 'sao-geronimo' ), sg_preco_curto( $ate ) );
	}
	/* translators: 1: preço mínimo, 2: preço máximo */
	return sprintf( __( '%1$s a %2$s', 'sao-geronimo' ), sg_preco_curto( $de ), sg_preco_curto( $ate ) );
}

/**
 * IDs dos produtos que passam por todos os filtros, menos os excluídos.
 *
 * @param array $excluir Filtros a ignorar.
 * @return int[]
 */
function sg_ids_filtrados( $excluir = array() ) {
	static $memo = array();
	sort( $excluir );
	$k = implode( ',', $excluir );
	if ( isset( $memo[ $k ] ) ) {
		return $memo[ $k ];
	}
	$p    = sg_filtros_partes( $excluir );
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'fields'                 => 'ids',
		'nopaging'               => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'              => $p['tax'], // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_query'             => $p['meta'], // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( null !== $p['ids'] ) {
		$args['post__in'] = $p['ids'];
	}
	if ( is_product_taxonomy() ) {
		$o = get_queried_object();
		if ( $o && ! empty( $o->taxonomy ) ) {
			$args['tax_query'][] = array( 'taxonomy' => $o->taxonomy, 'field' => 'term_id', 'terms' => (int) $o->term_id );
		}
	}
	$memo[ $k ] = array_map( 'intval', ( new WP_Query( $args ) )->posts );
	return $memo[ $k ];
}

/**
 * Há filtros ativos além do próprio? (Então a contagem precisa ser refeita.)
 *
 * @param string $proprio Filtro do bloco.
 * @return bool
 */
function sg_ha_outros_filtros( $proprio ) {
	$ativos = array(
		'cat'     => (bool) sg_filtro( 'sg_cat' ),
		'sub'     => (bool) sg_filtro( 'sg_sub' ),
		'preco'   => '' !== sg_filtro( 'sg_min' ) || '' !== sg_filtro( 'sg_max' ),
		'estoque' => (bool) sg_filtro( 'sg_estoque' ),
		'oferta'  => (bool) sg_filtro( 'sg_oferta' ),
	);
	unset( $ativos[ $proprio ] );
	return in_array( true, $ativos, true );
}

/**
 * Quantos produtos em estoque / em oferta, respeitando os outros filtros marcados.
 *
 * Sem outros filtros a conta fica guardada por 15 minutos (a chave leva a
 * versão do cache, que muda quando um produto é salvo).
 *
 * @param string $tipo 'estoque' ou 'oferta'.
 * @return int
 */
function sg_contagem_disp( $tipo ) {
	$outros = sg_ha_outros_filtros( $tipo );
	$cat    = is_product_category() ? (int) get_queried_object_id() : 0;
	$chave  = 'sg_disp_' . md5( get_option( 'sg_filtros_v', '0' ) ) . '_' . $tipo . '_' . $cat;
	if ( ! $outros ) {
		$n = get_transient( $chave );
		if ( false !== $n ) {
			return (int) $n;
		}
	}
	$ids = sg_ids_filtrados( array( $tipo ) );
	if ( 'oferta' === $tipo ) {
		$of = wc_get_product_ids_on_sale();
		$n  = count( array_intersect( $ids, $of ? $of : array() ) );
	} elseif ( ! $ids ) {
		$n = 0;
	} else {
		$q = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_query'     => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$n = (int) $q->found_posts;
	}
	if ( ! $outros ) {
		set_transient( $chave, $n, 15 * MINUTE_IN_SECONDS );
	}
	return $n;
}

/**
 * Contagem por termo (categorias contam as subcategorias) levando em conta os outros filtros.
 *
 * @param string $taxonomia product_cat ou product_tag.
 * @param string $proprio   Filtro do bloco (cat ou sub).
 * @return array|null term_id => quantidade; null = usar a contagem do próprio termo.
 */
function sg_contagem_termos( $taxonomia, $proprio ) {
	if ( ! sg_ha_outros_filtros( $proprio ) ) {
		return null;
	}
	$ids = sg_ids_filtrados( array( $proprio ) );
	if ( ! $ids ) {
		return array();
	}
	global $wpdb;
	$lista  = implode( ',', array_map( 'intval', $ids ) );
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT tr.object_id AS o, tt.term_id AS t FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy = %s AND tr.object_id IN ({$lista})", $taxonomia ) ); // phpcs:ignore WordPress.DB
	$obj    = array();
	foreach ( (array) $linhas as $l ) {
		$obj[ (int) $l->t ][ (int) $l->o ] = true;
		if ( is_taxonomy_hierarchical( $taxonomia ) ) {
			foreach ( get_ancestors( (int) $l->t, $taxonomia, 'taxonomy' ) as $pai ) {
				$obj[ (int) $pai ][ (int) $l->o ] = true;
			}
		}
	}
	return array_map( 'count', $obj );
}

/**
 * Contagem do termo com as filhas (usa a do WooCommerce quando existe).
 *
 * @param WP_Term $t Categoria.
 * @return int
 */
function sg_contagem_estatica( $t ) {
	$n = (int) get_term_meta( $t->term_id, 'product_count_product_cat', true );
	return $n ? $n : (int) $t->count;
}

/**
 * Desenha um bloco recolhível com lista de caixas de seleção.
 *
 * As opções marcadas sobem para o topo. Mostra 6 de início; o resto abre com
 * "Ver mais" (o JS cuida disso; sem JS a lista aparece inteira). Acima de 10
 * opções ganha campo de busca; acima de 12 rola por dentro quando expandida.
 *
 * @param array $o Dados: id, titulo, nome (campo), tipo, busca, aberto, itens.
 *                 Cada item: valor, rotulo, n (int|null), marcado (bool).
 */
function sg_filtro_bloco( $o ) {
	$itens = $o['itens'];
	usort(
		$itens,
		function ( $a, $b ) {
			return (int) $b['marcado'] <=> (int) $a['marcado'];
		}
	);
	$total  = count( $itens );
	$limite = 6;
	$marc   = count( array_filter( wp_list_pluck( $itens, 'marcado' ) ) );
	$abrir  = $o['aberto'] || $marc;
	$tipo   = isset( $o['tipo'] ) ? $o['tipo'] : 'checkbox';
	?>
	<details class="fb" <?php echo $abrir ? 'open' : ''; ?> data-bloco="<?php echo esc_attr( $o['id'] ); ?>">
		<summary id="fb-<?php echo esc_attr( $o['id'] ); ?>-t"><span><?php echo esc_html( $o['titulo'] ); ?></span><?php if ( $marc ) : ?><b class="fb__n"><?php echo (int) $marc; ?></b><?php endif; ?></summary>
		<fieldset class="fb__corpo" aria-labelledby="fb-<?php echo esc_attr( $o['id'] ); ?>-t">
			<legend class="sr"><?php echo esc_html( $o['titulo'] ); ?></legend>
			<?php if ( $total > 10 ) : ?>
				<div class="fb__busca" hidden data-busca>
					<input type="search" autocomplete="off" placeholder="<?php echo esc_attr( $o['busca'] ); ?>" aria-label="<?php echo esc_attr( $o['busca'] ); ?>">
				</div>
			<?php endif; ?>
			<div class="fl <?php echo $total > $limite ? 'fl--recolhida' : ''; ?> <?php echo $total > 12 ? 'fl--longa' : ''; ?>" data-lista data-limite="<?php echo (int) $limite; ?>">
				<div class="fl__rolagem" data-rolagem>
					<?php foreach ( $itens as $i => $it ) : ?>
						<label class="fo" <?php echo $i >= $limite && ! $it['marcado'] ? 'data-extra' : ''; ?> data-texto="<?php echo esc_attr( strtolower( remove_accents( $it['rotulo'] ) ) ); ?>">
							<input type="<?php echo esc_attr( $tipo ); ?>" name="<?php echo esc_attr( $o['nome'] ); ?>" value="<?php echo esc_attr( $it['valor'] ); ?>" <?php checked( $it['marcado'] ); ?>>
							<span class="fo__rotulo"><?php echo esc_html( $it['rotulo'] ); ?></span>
							<?php if ( null !== $it['n'] ) : ?>
								<span class="fo__n"><?php echo (int) $it['n']; ?></span>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
					<p class="fl__vazio" hidden data-vazio><?php esc_html_e( 'Nenhuma opção encontrada.', 'sao-geronimo' ); ?></p>
				</div>
			</div>
			<?php if ( $total > $limite ) : ?>
				<button type="button" class="fb__mais" hidden data-mais data-total="<?php echo (int) ( $total - $limite ); ?>" aria-expanded="false">
					<?php
					/* translators: %d: quantidade de opções escondidas */
					echo esc_html( sprintf( __( 'Ver mais (%d)', 'sao-geronimo' ), $total - $limite ) );
					?>
				</button>
			<?php endif; ?>
		</fieldset>
	</details>
	<?php
}

/**
 * Desenha a barra lateral de filtros.
 *
 * Listas verticais de caixas de seleção em blocos recolhíveis, como nas
 * grandes lojas. Sem JS funciona como formulário GET comum (botão "Ver
 * produtos"); com JS (loja.js) atualiza a grade por AJAX.
 */
function sg_wc_filtros() {
	if ( 'nao' === sg_opt( 'loja_filtros', 'sim' ) ) {
		return;
	}
	$base   = sg_url_base_loja();
	$ativos = sg_filtros_ativos();
	$faixa  = sg_faixa_precos();
	$sel_c  = sg_filtro( 'sg_cat' );
	$sel_s  = sg_filtro( 'sg_sub' );
	$min    = sg_filtro( 'sg_min' );
	$max    = sg_filtro( 'sg_max' );
	$na_cat = is_product_category();

	// Categorias: contagem do próprio termo.
	$itens_c = array();
	if ( 'nao' !== sg_opt( 'loja_filtro_categorias', 'sim' ) && ! $na_cat ) {
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'pad_counts' => true, 'parent' => 0, 'orderby' => 'name' ) );
		if ( $cats && ! is_wp_error( $cats ) ) {
			$din = sg_contagem_termos( 'product_cat', 'cat' );
			foreach ( $cats as $c ) {
				$n         = null === $din ? sg_contagem_estatica( $c ) : ( isset( $din[ $c->term_id ] ) ? (int) $din[ $c->term_id ] : 0 );
				$itens_c[] = array( 'valor' => $c->slug, 'rotulo' => $c->name, 'n' => $n, 'marcado' => in_array( $c->slug, $sel_c, true ) );
			}
		}
	}
	// Linhas (etiquetas): mais usadas primeiro.
	$itens_l = array();
	if ( 'nao' !== sg_opt( 'loja_filtro_linhas', 'sim' ) ) {
		$subs = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 60 ) );
		if ( $subs && ! is_wp_error( $subs ) ) {
			$din = sg_contagem_termos( 'product_tag', 'sub' );
			foreach ( $subs as $t ) {
				$n         = null === $din ? (int) $t->count : ( isset( $din[ $t->term_id ] ) ? (int) $din[ $t->term_id ] : 0 );
				$itens_l[] = array( 'valor' => $t->slug, 'rotulo' => $t->name, 'n' => $n, 'marcado' => in_array( $t->slug, $sel_s, true ) );
			}
		}
	}

	// Faixas rápidas: [mínimo, máximo ou ''].
	$faixas = array();
	$ant    = 0;
	foreach ( sg_cortes_preco( $faixa ) as $c ) {
		$faixas[] = array( $ant, $c, sg_rotulo_faixa( $ant, $c ) );
		$ant      = $c;
	}
	$faixas[] = array( $ant, '', sg_rotulo_faixa( $ant, '' ) );
	$lim_min  = (int) $faixa[0];
	$lim_max  = (int) $faixa[1];
	?>
	<div class="filtros-fundo" data-filtros-fechar></div>
	<aside class="filtros filtros--completo" id="filtros" aria-label="<?php esc_attr_e( 'Filtrar produtos', 'sao-geronimo' ); ?>" data-ativos="<?php echo (int) $ativos; ?>">
		<form method="get" action="<?php echo esc_url( $base ); ?>" data-filtros-form>
			<div class="filtros__topo">
				<h2 id="filtros-titulo"><?php esc_html_e( 'Filtrar', 'sao-geronimo' ); ?></h2>
				<a class="filtros__limpar" href="<?php echo esc_url( $base ); ?>" data-limpar <?php echo $ativos ? '' : 'hidden'; ?>><?php esc_html_e( 'Limpar', 'sao-geronimo' ); ?></a>
				<button type="button" class="filtros__x" data-filtros-fechar aria-label="<?php esc_attr_e( 'Fechar filtros', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'x', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>

			<div class="filtros__corpo" data-corpo>
				<?php
				if ( $itens_c ) {
					sg_filtro_bloco( array( 'id' => 'cat', 'titulo' => __( 'Categorias', 'sao-geronimo' ), 'nome' => 'sg_cat[]', 'busca' => __( 'Buscar categoria…', 'sao-geronimo' ), 'aberto' => true, 'itens' => $itens_c ) );
				}
				?>

				<?php if ( 'nao' !== sg_opt( 'loja_filtro_preco', 'sim' ) && $lim_max > 0 ) : ?>
					<details class="fb" open data-bloco="preco">
						<summary id="fb-preco-t"><span><?php esc_html_e( 'Preço', 'sao-geronimo' ); ?></span><?php if ( '' !== $min || '' !== $max ) : ?><b class="fb__n">1</b><?php endif; ?></summary>
						<fieldset class="fb__corpo" aria-labelledby="fb-preco-t">
							<legend class="sr"><?php esc_html_e( 'Preço', 'sao-geronimo' ); ?></legend>
							<div class="fp" data-preco data-min="<?php echo (int) $lim_min; ?>" data-max="<?php echo (int) $lim_max; ?>">
								<div class="fp__leitura" aria-hidden="true" hidden data-leitura></div>
								<div class="fp__trilho" hidden data-trilho>
									<div class="fp__barra" data-barra></div>
									<input type="range" class="fp__r" data-r="min" min="<?php echo (int) $lim_min; ?>" max="<?php echo (int) $lim_max; ?>" step="1" value="<?php echo (int) ( '' === $min ? $lim_min : $min ); ?>" aria-label="<?php esc_attr_e( 'Preço mínimo', 'sao-geronimo' ); ?>">
									<input type="range" class="fp__r" data-r="max" min="<?php echo (int) $lim_min; ?>" max="<?php echo (int) $lim_max; ?>" step="1" value="<?php echo (int) ( '' === $max ? $lim_max : $max ); ?>" aria-label="<?php esc_attr_e( 'Preço máximo', 'sao-geronimo' ); ?>">
								</div>
								<div class="fp__campos">
									<label><span class="fp__rot"><?php esc_html_e( 'Mínimo', 'sao-geronimo' ); ?></span><span class="fp__caixa"><i>R$</i><input type="number" inputmode="numeric" min="0" step="1" name="sg_min" placeholder="<?php echo (int) $lim_min; ?>" value="<?php echo '' === $min ? '' : esc_attr( (int) $min ); ?>"></span></label>
									<label><span class="fp__rot"><?php esc_html_e( 'Máximo', 'sao-geronimo' ); ?></span><span class="fp__caixa"><i>R$</i><input type="number" inputmode="numeric" min="0" step="1" name="sg_max" placeholder="<?php echo (int) $lim_max; ?>" value="<?php echo '' === $max ? '' : esc_attr( (int) $max ); ?>"></span></label>
								</div>
								<div class="fl fp__faixas">
									<?php foreach ( $faixas as $f ) : ?>
										<?php $marcada = ( '' !== $min || '' !== $max ) && (float) $min === (float) $f[0] && ( '' === $f[1] ? '' === $max : (float) $max === (float) $f[1] ); ?>
										<label class="fo">
											<input type="radio" name="sg_faixa" value="<?php echo esc_attr( $f[0] . '-' . $f[1] ); ?>" data-de="<?php echo (int) $f[0]; ?>" data-ate="<?php echo esc_attr( $f[1] ); ?>" <?php checked( $marcada ); ?>>
											<span class="fo__rotulo"><?php echo esc_html( $f[2] ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</fieldset>
					</details>
				<?php endif; ?>

				<?php
				if ( $itens_l ) {
					sg_filtro_bloco( array( 'id' => 'linha', 'titulo' => __( 'Linha', 'sao-geronimo' ), 'nome' => 'sg_sub[]', 'busca' => __( 'Buscar linha…', 'sao-geronimo' ), 'aberto' => false, 'itens' => $itens_l ) );
				}
				if ( 'nao' !== sg_opt( 'loja_filtro_disponibilidade', 'sim' ) ) {
					?>
					<details class="fb" open data-bloco="disp">
						<summary id="fb-disp-t"><span><?php esc_html_e( 'Disponibilidade', 'sao-geronimo' ); ?></span></summary>
						<fieldset class="fb__corpo" aria-labelledby="fb-disp-t">
							<legend class="sr"><?php esc_html_e( 'Disponibilidade', 'sao-geronimo' ); ?></legend>
							<div class="fl">
								<label class="fo"><input type="checkbox" name="sg_estoque" value="1" <?php checked( (bool) sg_filtro( 'sg_estoque' ) ); ?>><span class="fo__rotulo"><?php esc_html_e( 'Em estoque', 'sao-geronimo' ); ?></span><span class="fo__n"><?php echo (int) sg_contagem_disp( 'estoque' ); ?></span></label>
								<label class="fo"><input type="checkbox" name="sg_oferta" value="1" <?php checked( (bool) sg_filtro( 'sg_oferta' ) ); ?>><span class="fo__rotulo"><?php esc_html_e( 'Em oferta', 'sao-geronimo' ); ?></span><span class="fo__n"><?php echo (int) sg_contagem_disp( 'oferta' ); ?></span></label>
							</div>
						</fieldset>
					</details>
				<?php } ?>
			</div>

			<?php
			if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_key( wp_unslash( $_GET['orderby'] ) ) ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification
			}
			?>

			<div class="filtros__acoes">
				<button type="submit" class="btn btn--azul" data-ver><?php esc_html_e( 'Ver produtos', 'sao-geronimo' ); ?></button>
			</div>
		</form>
	</aside>
	<?php
}

/**
 * Botão "Filtrar (n)" (aparece só no celular).
 */
function sg_wc_filtros_topo_botao() {
	$ativos = sg_filtros_ativos();
	echo '<button type="button" class="filtros-abrir" data-filtros-abrir aria-controls="filtros" aria-haspopup="dialog">';
	echo sg_icone( 'filtro', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo ' ' . esc_html__( 'Filtrar', 'sao-geronimo' );
	if ( $ativos ) {
		echo ' <b data-n-ativos>' . (int) $ativos . '</b>';
	}
	echo '</button>';
}

/**
 * Etiquetas dos filtros que estão valendo, cada uma com × que a remove.
 */
function sg_wc_filtros_topo() {
	if ( ! sg_filtros_ativos() ) {
		return;
	}
	$base = sg_url_base_loja();
	$et   = array(); // Cada item: rótulo, endereço sem ele.

	foreach ( sg_filtro( 'sg_cat' ) as $s ) {
		$t = get_term_by( 'slug', $s, 'product_cat' );
		if ( $t ) {
			$et[] = array( $t->name, sg_url_sem( 'sg_cat', $s ) );
		}
	}
	foreach ( sg_filtro( 'sg_sub' ) as $s ) {
		$t = get_term_by( 'slug', $s, 'product_tag' );
		if ( $t ) {
			$et[] = array( $t->name, sg_url_sem( 'sg_sub', $s ) );
		}
	}
	$min = sg_filtro( 'sg_min' );
	$max = sg_filtro( 'sg_max' );
	if ( '' !== $min || '' !== $max ) {
		// Limites além do catálogo valem como "sem limite" (igual ao marcador do preço).
		$fx = sg_faixa_precos();
		if ( '' !== $max && (float) $max >= (float) $fx[1] ) {
			$max = '';
		}
		if ( '' !== $min && (float) $min <= (float) $fx[0] ) {
			$min = '';
		}
		$rot = sg_rotulo_faixa( '' === $min ? 0 : $min, $max );
		$et[] = array( $rot, sg_url_sem( array( 'sg_min', 'sg_max' ) ) );
	}
	if ( sg_filtro( 'sg_estoque' ) ) {
		$et[] = array( __( 'Em estoque', 'sao-geronimo' ), sg_url_sem( 'sg_estoque' ) );
	}
	if ( sg_filtro( 'sg_oferta' ) ) {
		$et[] = array( __( 'Em oferta', 'sao-geronimo' ), sg_url_sem( 'sg_oferta' ) );
	}

	$ord = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$lim = $ord ? add_query_arg( 'orderby', $ord, $base ) : $base;

	echo '<div class="chips" data-chips role="group" aria-label="' . esc_attr__( 'Filtros ativos', 'sao-geronimo' ) . '">';
	foreach ( $et as $e ) {
		/* translators: %s: nome do filtro */
		$aria = sprintf( __( 'Remover filtro: %s', 'sao-geronimo' ), $e[0] );
		echo '<a class="chip" href="' . esc_url( $e[1] ) . '" data-chip aria-label="' . esc_attr( $aria ) . '"><span>' . esc_html( $e[0] ) . '</span>' . sg_icone( 'x', 12 ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<a class="chips__limpar" href="' . esc_url( $lim ) . '" data-chip>' . esc_html__( 'Limpar tudo', 'sao-geronimo' ) . '</a>';
	echo '</div>';
}
