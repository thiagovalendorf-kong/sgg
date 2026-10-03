<?php
/**
 * Cadastro de produto em um formulário curto.
 *
 * A tela normal do WooCommerce tem dezenas de campos e assusta. Esta pede só o
 * essencial e cria o produto publicado, pronto para vender. Depois, se quiser
 * mexer em algo mais fino, o botão leva para a tela completa.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra a tela.
 */
function sgp_menu_produto_facil() {
	add_submenu_page(
		'sg-painel',
		__( 'Cadastrar produto', 'sao-geronimo-painel' ),
		__( '➕ Cadastrar produto', 'sao-geronimo-painel' ),
		'edit_products',
		'sg-produto-facil',
		'sgp_tela_produto_facil'
	);
}
add_action( 'admin_menu', 'sgp_menu_produto_facil', 15 );

/**
 * A tela.
 */
function sgp_tela_produto_facil() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}
	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<div class="wrap sgp"><div class="sgp-card sgp-card--alerta"><p>'
			. esc_html__( 'O WooCommerce precisa estar ativo.', 'sao-geronimo-painel' ) . '</p></div></div>';
		return;
	}

	$criado = 0;
	$erro   = '';

	if ( isset( $_POST['sgp_pf_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_pf_nonce'] ) ), 'sgp_pf' ) ) {
		$res = sgp_cria_produto( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_wp_error( $res ) ) {
			$erro = $res->get_error_message();
		} else {
			$criado = $res;
		}
	}

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Cadastrar produto', 'sao-geronimo-painel' ), __( 'Preencha o que souber e salve. O que faltar dá para completar depois.', 'sao-geronimo-painel' ) );

	if ( $erro ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $erro ) . '</p></div>';
	}

	if ( $criado ) {
		printf(
			'<div class="sgp-card sgp-card--ok"><h2>%s</h2><p>%s</p><p>
				<a class="button button-primary" href="%s" target="_blank" rel="noopener">%s</a>
				<a class="button" href="%s">%s</a>
				<a class="button" href="%s">%s</a></p></div>',
			esc_html__( 'Produto criado e publicado.', 'sao-geronimo-painel' ),
			esc_html( get_the_title( $criado ) ),
			esc_url( get_permalink( $criado ) ),
			esc_html__( 'Ver no site', 'sao-geronimo-painel' ),
			esc_url( get_edit_post_link( $criado ) ),
			esc_html__( 'Abrir a ficha completa', 'sao-geronimo-painel' ),
			esc_url( admin_url( 'admin.php?page=sg-produto-facil' ) ),
			esc_html__( 'Cadastrar outro', 'sao-geronimo-painel' )
		);
	}

	$cats = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) : array();
	?>
	<form method="post" class="sgp-form sgp-pf">
		<?php wp_nonce_field( 'sgp_pf', 'sgp_pf_nonce' ); ?>

		<section class="sgp-card">
			<h2><?php esc_html_e( '1. O básico', 'sao-geronimo-painel' ); ?></h2>

			<div class="sgp-linha">
				<div class="sgp-rotulo"><label for="pf-nome"><?php esc_html_e( 'Nome do produto', 'sao-geronimo-painel' ); ?> <b class="sgp-obrig">*</b></label>
					<p class="sgp-dica"><?php esc_html_e( 'Escreva como o cliente procuraria. Ex.: "Incenso Noa Oxum — 10 varetas".', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="text" id="pf-nome" name="nome" required></div>
			</div>

			<div class="sgp-linha">
				<div class="sgp-rotulo"><label for="pf-preco"><?php esc_html_e( 'Preço de venda', 'sao-geronimo-painel' ); ?> <b class="sgp-obrig">*</b></label>
					<p class="sgp-dica"><?php esc_html_e( 'Use vírgula: 24,90', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="text" id="pf-preco" name="preco" class="sgp-curto" inputmode="decimal" placeholder="0,00" required>
					<span class="sgp-sufixo">R$</span></div>
			</div>

			<div class="sgp-linha">
				<div class="sgp-rotulo"><label for="pf-promo"><?php esc_html_e( 'Preço promocional', 'sao-geronimo-painel' ); ?></label>
					<p class="sgp-dica"><?php esc_html_e( 'Opcional. Se preencher, o preço cheio aparece riscado.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="text" id="pf-promo" name="promo" class="sgp-curto" inputmode="decimal" placeholder="0,00">
					<span class="sgp-sufixo">R$</span></div>
			</div>

			<div class="sgp-linha">
				<div class="sgp-rotulo"><label><?php esc_html_e( 'Foto principal', 'sao-geronimo-painel' ); ?></label>
					<p class="sgp-dica"><?php esc_html_e( 'Quadrada, pelo menos 1000×1000 px, fundo limpo.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle">
					<div class="sgp-img" data-img>
						<div class="sgp-img__visor"><span><?php esc_html_e( 'Nenhuma imagem', 'sao-geronimo-painel' ); ?></span></div>
						<div class="sgp-img__acoes">
							<button type="button" class="button" data-img-escolher data-id="pf-foto-id"><?php esc_html_e( 'Escolher imagem', 'sao-geronimo-painel' ); ?></button>
							<button type="button" class="button-link sgp-remover" data-img-tirar><?php esc_html_e( 'remover', 'sao-geronimo-painel' ); ?></button>
						</div>
						<input type="hidden" name="foto" value="" data-img-valor>
						<input type="hidden" name="foto_id" value="" data-img-id id="pf-foto-id">
					</div>
				</div>
			</div>

			<div class="sgp-linha">
				<div class="sgp-rotulo"><label><?php esc_html_e( 'Categoria', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle"><div class="sgp-termos">
					<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
						<?php foreach ( $cats as $c ) : ?>
							<label><input type="checkbox" name="cats[]" value="<?php echo (int) $c->term_id; ?>"> <?php echo esc_html( $c->name ); ?></label>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="sgp-dica"><?php esc_html_e( 'Nenhuma categoria ainda — crie em Produtos → Categorias.', 'sao-geronimo-painel' ); ?></p>
					<?php endif; ?>
				</div></div>
			</div>
		</section>

		<section class="sgp-card">
			<h2><?php esc_html_e( '2. Descrição', 'sao-geronimo-painel' ); ?></h2>
			<div class="sgp-linha sgp-linha--larga">
				<div class="sgp-rotulo"><label for="pf-desc"><?php esc_html_e( 'Sobre o produto', 'sao-geronimo-painel' ); ?></label>
					<p class="sgp-dica"><?php esc_html_e( 'Diga para que serve e o que tem de especial. Três ou quatro linhas bastam.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><textarea id="pf-desc" name="descricao" rows="5"></textarea></div>
			</div>
			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-comp"><?php esc_html_e( 'Composição', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle"><textarea id="pf-comp" name="composicao" rows="3"></textarea></div></div>
			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-modo"><?php esc_html_e( 'Modo de usar', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle"><textarea id="pf-modo" name="modo" rows="3"></textarea></div></div>
		</section>

		<section class="sgp-card">
			<h2><?php esc_html_e( '3. Medidas e estoque', 'sao-geronimo-painel' ); ?></h2>
			<p class="sgp-resumo"><?php esc_html_e( 'Peso e medidas são o que o cálculo de frete usa. Sem eles, o frete sai errado.', 'sao-geronimo-painel' ); ?></p>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-sku"><?php esc_html_e( 'Referência (SKU)', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'Deixe vazio que a gente cria uma.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="text" id="pf-sku" name="sku"></div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-peso"><?php esc_html_e( 'Peso', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle"><input type="text" id="pf-peso" name="peso" class="sgp-curto" inputmode="decimal"><span class="sgp-sufixo"><?php echo esc_html( get_option( 'woocommerce_weight_unit', 'kg' ) ); ?></span></div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label><?php esc_html_e( 'Medidas da caixa', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle sgp-medidas">
					<input type="text" name="comp" placeholder="<?php esc_attr_e( 'comprimento', 'sao-geronimo-painel' ); ?>" inputmode="decimal">
					<input type="text" name="larg" placeholder="<?php esc_attr_e( 'largura', 'sao-geronimo-painel' ); ?>" inputmode="decimal">
					<input type="text" name="alt" placeholder="<?php esc_attr_e( 'altura', 'sao-geronimo-painel' ); ?>" inputmode="decimal">
					<span class="sgp-sufixo"><?php echo esc_html( get_option( 'woocommerce_dimension_unit', 'cm' ) ); ?></span>
				</div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-dim"><?php esc_html_e( 'Medidas por extenso', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'O que o cliente lê. Ex.: 20 cm de altura × 8 cm de base.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="text" id="pf-dim" name="dimensoes"></div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-estoque"><?php esc_html_e( 'Quantidade em estoque', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'Vazio = sempre disponível, sem controle de estoque.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><input type="number" id="pf-estoque" name="estoque" class="sgp-curto" min="0"></div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-marca"><?php esc_html_e( 'Marca / fabricante', 'sao-geronimo-painel' ); ?></label></div>
				<div class="sgp-controle"><input type="text" id="pf-marca" name="marca"></div></div>

			<div class="sgp-linha"><div class="sgp-rotulo"><label for="pf-destaque"><?php esc_html_e( 'Marcar como destaque', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'Produtos em destaque podem aparecer numa vitrine da página inicial.', 'sao-geronimo-painel' ); ?></p></div>
				<div class="sgp-controle"><label class="sgp-liga"><input type="checkbox" id="pf-destaque" name="destaque" value="sim"><span class="sgp-liga__pino"></span><span class="sgp-liga__txt"><?php esc_html_e( 'Sim', 'sao-geronimo-painel' ); ?></span></label></div></div>
		</section>

		<div class="sgp-salvar">
			<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Publicar produto', 'sao-geronimo-painel' ); ?></button>
			<span class="sgp-salvar__dica"><?php esc_html_e( 'Ele já entra no ar, visível para os clientes.', 'sao-geronimo-painel' ); ?></span>
		</div>
	</form>
	</div>
	<?php
}

/**
 * Cria o produto a partir do formulário.
 *
 * @param array $d Dados do POST.
 * @return int|WP_Error
 */
function sgp_cria_produto( $d ) {
	$pega = function ( $k, $padrao = '' ) use ( $d ) {
		return isset( $d[ $k ] ) ? sanitize_text_field( wp_unslash( $d[ $k ] ) ) : $padrao;
	};
	$area = function ( $k ) use ( $d ) {
		return isset( $d[ $k ] ) ? sanitize_textarea_field( wp_unslash( $d[ $k ] ) ) : '';
	};
	$num = function ( $v ) {
		$v = str_replace( array( '.', ' ' ), '', (string) $v );
		$v = str_replace( ',', '.', $v );
		return is_numeric( $v ) ? $v : '';
	};

	$nome = $pega( 'nome' );
	if ( ! $nome ) {
		return new WP_Error( 'sem_nome', __( 'O produto precisa de um nome.', 'sao-geronimo-painel' ) );
	}

	$preco = $num( $pega( 'preco' ) );
	if ( '' === $preco ) {
		return new WP_Error( 'sem_preco', __( 'Informe o preço de venda, usando vírgula (ex.: 24,90).', 'sao-geronimo-painel' ) );
	}

	$p = new WC_Product_Simple();
	$p->set_name( $nome );
	$p->set_status( 'publish' );
	$p->set_catalog_visibility( 'visible' );
	$p->set_regular_price( $preco );

	$promo = $num( $pega( 'promo' ) );
	if ( '' !== $promo && (float) $promo > 0 && (float) $promo < (float) $preco ) {
		$p->set_sale_price( $promo );
	}

	$p->set_description( $area( 'descricao' ) );
	$p->set_short_description( wp_trim_words( $area( 'descricao' ), 36 ) );

	$sku = $pega( 'sku' );
	if ( ! $sku ) {
		$sku = 'SG-' . strtoupper( wp_generate_password( 6, false, false ) );
	}
	if ( ! wc_get_product_id_by_sku( $sku ) ) {
		$p->set_sku( $sku );
	}

	$peso = $num( $pega( 'peso' ) );
	if ( '' !== $peso ) {
		$p->set_weight( $peso );
	}
	foreach ( array( 'comp' => 'set_length', 'larg' => 'set_width', 'alt' => 'set_height' ) as $campo => $metodo ) {
		$v = $num( $pega( $campo ) );
		if ( '' !== $v ) {
			$p->$metodo( $v );
		}
	}

	if ( isset( $d['estoque'] ) && '' !== $d['estoque'] ) {
		$p->set_manage_stock( true );
		$p->set_stock_quantity( (int) $d['estoque'] );
	}

	if ( 'sim' === $pega( 'destaque' ) ) {
		$p->set_featured( true );
	}

	$cats = isset( $d['cats'] ) ? array_map( 'absint', (array) $d['cats'] ) : array();
	if ( $cats ) {
		$p->set_category_ids( $cats );
	}

	$foto_id = isset( $d['foto_id'] ) ? absint( $d['foto_id'] ) : 0;
	if ( $foto_id ) {
		$p->set_image_id( $foto_id );
	}

	$id = $p->save();
	if ( ! $id ) {
		return new WP_Error( 'falhou', __( 'Não consegui salvar. Tente de novo.', 'sao-geronimo-painel' ) );
	}

	foreach ( array( 'dimensoes' => '_sg_dimensoes', 'marca' => '_sg_marca' ) as $campo => $meta ) {
		$v = $pega( $campo );
		if ( $v ) {
			update_post_meta( $id, $meta, $v );
		}
	}
	foreach ( array( 'composicao' => '_sg_composicao', 'modo' => '_sg_modo_usar' ) as $campo => $meta ) {
		$v = $area( $campo );
		if ( $v ) {
			update_post_meta( $id, $meta, $v );
		}
	}

	return $id;
}
