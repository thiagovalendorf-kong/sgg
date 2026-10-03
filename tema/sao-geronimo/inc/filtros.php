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
	if ( ! isset( $_GET[ $chave ] ) ) {
		return in_array( $chave, array( 'sg_cat', 'sg_sub' ), true ) ? array() : '';
	}
	$v = wp_unslash( $_GET[ $chave ] );
	// phpcs:enable
	if ( in_array( $chave, array( 'sg_cat', 'sg_sub' ), true ) ) {
		return array_filter( array_map( 'sanitize_title', (array) $v ) );
	}
	if ( in_array( $chave, array( 'sg_min', 'sg_max' ), true ) ) {
		return '' === $v ? '' : max( 0, (float) str_replace( ',', '.', $v ) );
	}
	return '1' === (string) $v ? '1' : '';
}

/**
 * Aplica os filtros na consulta de produtos.
 *
 * @param WP_Query $q Consulta.
 */
function sg_filtros_consulta( $q ) {
	$cats = sg_filtro( 'sg_cat' );
	$subs = sg_filtro( 'sg_sub' );

	$tax = (array) $q->get( 'tax_query' );
	if ( $cats ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $cats );
	}
	if ( $subs ) {
		$tax[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $subs );
	}
	if ( $tax ) {
		$q->set( 'tax_query', $tax );
	}

	$meta = (array) $q->get( 'meta_query' );
	$min  = sg_filtro( 'sg_min' );
	$max  = sg_filtro( 'sg_max' );
	if ( '' !== $min || '' !== $max ) {
		$meta[] = array(
			'key'     => '_price',
			'value'   => array( '' === $min ? 0 : $min, '' === $max ? 99999999 : $max ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	}
	if ( sg_filtro( 'sg_estoque' ) ) {
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
	}
	if ( $meta ) {
		$q->set( 'meta_query', $meta );
	}

	if ( sg_filtro( 'sg_oferta' ) ) {
		$ids = wc_get_product_ids_on_sale();
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
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
add_action( 'woocommerce_update_product', function () {
	delete_transient( 'sg_faixa_precos' );
} );

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
 * Quantidade de filtros ativos.
 *
 * @return int
 */
function sg_filtros_ativos() {
	return count( sg_filtro( 'sg_cat' ) ) + count( sg_filtro( 'sg_sub' ) )
		+ ( '' !== sg_filtro( 'sg_min' ) ? 1 : 0 ) + ( '' !== sg_filtro( 'sg_max' ) ? 1 : 0 )
		+ ( sg_filtro( 'sg_oferta' ) ? 1 : 0 ) + ( sg_filtro( 'sg_estoque' ) ? 1 : 0 );
}

/**
 * Desenha a barra lateral de filtros.
 */
function sg_wc_filtros() {
	$base   = sg_url_base_loja();
	$ativos = sg_filtros_ativos();
	$cats   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'name' ) );
	$subs   = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 30 ) );
	$faixa  = sg_faixa_precos();
	$sel_c  = sg_filtro( 'sg_cat' );
	$sel_s  = sg_filtro( 'sg_sub' );
	$min    = sg_filtro( 'sg_min' );
	$max    = sg_filtro( 'sg_max' );
	$na_cat = is_product_category();
	?>
	<div class="filtros-fundo" data-filtros-fechar></div>
	<aside class="filtros" id="filtros" aria-label="<?php esc_attr_e( 'Filtrar produtos', 'sao-geronimo' ); ?>">
		<form method="get" action="<?php echo esc_url( $base ); ?>" data-filtros-form>
			<div class="filtros__topo">
				<h2><?php esc_html_e( 'Filtrar', 'sao-geronimo' ); ?></h2>
				<button type="button" class="filtros__x" data-filtros-fechar aria-label="<?php esc_attr_e( 'Fechar filtros', 'sao-geronimo' ); ?>">&times;</button>
			</div>

			<?php if ( ! $na_cat && $cats && ! is_wp_error( $cats ) ) : ?>
				<details class="filtro" open>
					<summary><?php esc_html_e( 'Categorias', 'sao-geronimo' ); ?></summary>
					<div class="filtro__lista">
						<?php foreach ( $cats as $c ) : ?>
							<label class="check">
								<input type="checkbox" name="sg_cat[]" value="<?php echo esc_attr( $c->slug ); ?>" <?php checked( in_array( $c->slug, $sel_c, true ) ); ?>>
								<span><?php echo esc_html( $c->name ); ?></span>
								<em><?php echo (int) $c->count; ?></em>
							</label>
						<?php endforeach; ?>
					</div>
				</details>
			<?php endif; ?>

			<details class="filtro" open>
				<summary><?php esc_html_e( 'Preço', 'sao-geronimo' ); ?></summary>
				<div class="filtro__preco">
					<label><span>R$</span><input type="number" inputmode="decimal" min="0" step="1" name="sg_min" placeholder="<?php echo esc_attr( (int) $faixa[0] ); ?>" value="<?php echo '' === $min ? '' : esc_attr( $min ); ?>" aria-label="<?php esc_attr_e( 'Preço mínimo', 'sao-geronimo' ); ?>"></label>
					<i>&ndash;</i>
					<label><span>R$</span><input type="number" inputmode="decimal" min="0" step="1" name="sg_max" placeholder="<?php echo esc_attr( (int) $faixa[1] ); ?>" value="<?php echo '' === $max ? '' : esc_attr( $max ); ?>" aria-label="<?php esc_attr_e( 'Preço máximo', 'sao-geronimo' ); ?>"></label>
				</div>
			</details>

			<?php if ( $subs && ! is_wp_error( $subs ) ) : ?>
				<details class="filtro" <?php echo $sel_s ? 'open' : ''; ?>>
					<summary><?php esc_html_e( 'Linha / sublinha', 'sao-geronimo' ); ?></summary>
					<div class="filtro__lista">
						<?php foreach ( $subs as $t ) : ?>
							<label class="check">
								<input type="checkbox" name="sg_sub[]" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( in_array( $t->slug, $sel_s, true ) ); ?>>
								<span><?php echo esc_html( $t->name ); ?></span>
								<em><?php echo (int) $t->count; ?></em>
							</label>
						<?php endforeach; ?>
					</div>
				</details>
			<?php endif; ?>

			<details class="filtro" open>
				<summary><?php esc_html_e( 'Disponibilidade', 'sao-geronimo' ); ?></summary>
				<div class="filtro__lista">
					<label class="check"><input type="checkbox" name="sg_oferta" value="1" <?php checked( (bool) sg_filtro( 'sg_oferta' ) ); ?>><span><?php esc_html_e( 'Só ofertas', 'sao-geronimo' ); ?></span></label>
					<label class="check"><input type="checkbox" name="sg_estoque" value="1" <?php checked( (bool) sg_filtro( 'sg_estoque' ) ); ?>><span><?php esc_html_e( 'Só em estoque', 'sao-geronimo' ); ?></span></label>
				</div>
			</details>

			<?php
			// Mantém a ordenação escolhida.
			if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_key( wp_unslash( $_GET['orderby'] ) ) ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification
			}
			?>

			<div class="filtros__acoes">
				<button type="submit" class="btn btn--azul"><?php esc_html_e( 'Aplicar filtros', 'sao-geronimo' ); ?></button>
				<?php if ( $ativos ) : ?>
					<a class="filtros__limpar" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Limpar tudo', 'sao-geronimo' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</aside>
	<?php
}

/**
 * Botão "Filtrar" (aparece só no celular).
 */
function sg_wc_filtros_topo_botao() {
	$ativos = sg_filtros_ativos();
	echo '<button type="button" class="filtros-abrir" data-filtros-abrir aria-controls="filtros">';
	echo sg_icone( 'filtro', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo ' ' . esc_html__( 'Filtrar', 'sao-geronimo' );
	if ( $ativos ) {
		echo ' <b>' . (int) $ativos . '</b>';
	}
	echo '</button>';
}

/**
 * Etiquetas dos filtros que estão valendo.
 */
function sg_wc_filtros_topo() {
	if ( ! sg_filtros_ativos() ) {
		return;
	}
	$base = sg_url_base_loja();

	$etiquetas = array();
	foreach ( sg_filtro( 'sg_cat' ) as $s ) {
		$t = get_term_by( 'slug', $s, 'product_cat' );
		if ( $t ) {
			$etiquetas[] = $t->name;
		}
	}
	foreach ( sg_filtro( 'sg_sub' ) as $s ) {
		$t = get_term_by( 'slug', $s, 'product_tag' );
		if ( $t ) {
			$etiquetas[] = $t->name;
		}
	}
	$min = sg_filtro( 'sg_min' );
	$max = sg_filtro( 'sg_max' );
	if ( '' !== $min || '' !== $max ) {
		$etiquetas[] = 'R$ ' . ( '' === $min ? '0' : (int) $min ) . ' – ' . ( '' === $max ? '∞' : (int) $max );
	}
	if ( sg_filtro( 'sg_oferta' ) ) {
		$etiquetas[] = __( 'Ofertas', 'sao-geronimo' );
	}
	if ( sg_filtro( 'sg_estoque' ) ) {
		$etiquetas[] = __( 'Em estoque', 'sao-geronimo' );
	}

	echo '<div class="chips">';
	foreach ( $etiquetas as $e ) {
		echo '<span class="chip">' . esc_html( $e ) . '</span>';
	}
	echo '<a class="chip chip--limpar" href="' . esc_url( $base ) . '">' . esc_html__( 'Limpar filtros', 'sao-geronimo' ) . '</a>';
	echo '</div>';
}
