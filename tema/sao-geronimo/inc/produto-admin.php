<?php
/**
 * Tela de edição do produto (painel): aba "Informações do produto" e campos extras.
 *
 * Só roda no painel. A parte que aparece no site fica em inc/woocommerce.php.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra a aba extra na edição do produto.
 *
 * @param array $abas Abas atuais.
 * @return array
 */
function sg_wc_aba_produto( $abas ) {
	$abas['sg'] = array(
		'label'    => __( 'Informações do produto', 'sao-geronimo' ),
		'target'   => 'sg_dados_produto',
		'class'    => array(),
		'priority' => 60,
	);
	return $abas;
}
add_filter( 'woocommerce_product_data_tabs', 'sg_wc_aba_produto' );

/**
 * Título de um grupo de campos.
 *
 * @param string $titulo Título.
 * @param string $ajuda  Frase de ajuda.
 */
function sg_wc_titulo_grupo( $titulo, $ajuda = '' ) {
	echo '<div class="sgp-grupo-titulo"><strong>' . esc_html( $titulo ) . '</strong>';
	if ( $ajuda ) {
		echo '<span>' . esc_html( $ajuda ) . '</span>';
	}
	echo '</div>';
}

/**
 * Campos da aba extra.
 */
function sg_wc_campos_produto() {
	echo '<div id="sg_dados_produto" class="panel woocommerce_options_panel sgp-info">';

	echo '<div class="options_group">';
	sg_wc_titulo_grupo( __( 'Identificação', 'sao-geronimo' ), __( 'Aparecem na ficha técnica do produto, na página da loja.', 'sao-geronimo' ) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_marca',
		'label'       => __( 'Marca / fabricante', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: Ervas do Brasil', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Aparece embaixo do nome do produto e nos dados estruturados do Google.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_material',
		'label'       => __( 'Material', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: cera vegetal, resina, metal', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Do que o produto é feito.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_conteudo',
		'label'       => __( 'Conteúdo da embalagem', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: 100 ml, 10 varetas, 1 unidade', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Quantidade ou volume que vem na embalagem.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_cod_fabricante',
		'label'       => __( 'Código do fabricante', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: REF-1020', 'sao-geronimo' ),
		'desc_tip'    => true,
		'description' => __( 'Código ou referência que o fabricante usa. Só para sua organização e para a ficha técnica.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_dimensoes',
		'label'       => __( 'Dimensões em texto', 'sao-geronimo' ),
		'placeholder' => __( 'ex.: 20 cm de altura × 8 cm de base', 'sao-geronimo' ),
		'wrapper_class' => 'sgp-largo',
		'desc_tip'    => true,
		'description' => __( 'Como a medida aparece para o cliente. Se deixar vazio, usamos as medidas numéricas da aba "Frete".', 'sao-geronimo' ),
	) );
	echo '</div>';

	echo '<div class="options_group">';
	sg_wc_titulo_grupo( __( 'Textos da página do produto', 'sao-geronimo' ), __( 'Cada um vira um bloco próprio na página. Deixe em branco o que não se aplica.', 'sao-geronimo' ) );

	woocommerce_wp_textarea_input( array(
		'id'            => '_sg_composicao',
		'label'         => __( 'Composição', 'sao-geronimo' ),
		'placeholder'   => __( 'ex.: óleos essenciais de lavanda e alecrim, cera de soja, pavio de algodão', 'sao-geronimo' ),
		'rows'          => 4,
		'wrapper_class' => 'sgp-largo',
		'description'   => __( 'Do que o produto é feito, em frases curtas ou uma lista.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'            => '_sg_modo_usar',
		'label'         => __( 'Modo de usar', 'sao-geronimo' ),
		'placeholder'   => __( 'ex.: acenda em local arejado, longe de crianças; aparar o pavio a cada uso', 'sao-geronimo' ),
		'rows'          => 4,
		'wrapper_class' => 'sgp-largo',
		'description'   => __( 'Passo a passo ou cuidados de uso.', 'sao-geronimo' ),
	) );
	echo '</div>';

	echo '<div class="options_group">';
	sg_wc_titulo_grupo( __( 'Blocos da página do produto', 'sao-geronimo' ), __( 'Textos extras que aparecem na coluna da direita, como no site original.', 'sao-geronimo' ) );

	woocommerce_wp_text_input( array(
		'id'            => '_sg_sobre_titulo',
		'label'         => __( 'Título do bloco de descrição', 'sao-geronimo' ),
		'placeholder'   => 'Sobre o produto',
		'wrapper_class' => 'sgp-largo',
		'desc_tip'      => true,
		'description'   => __( 'Ex.: "Sobre o produto" ou "Sobre a peça". O texto em si é a "Descrição completa" lá em cima.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'            => '_sg_notas',
		'label'         => __( 'Notas olfativas', 'sao-geronimo' ),
		'rows'          => 3,
		'wrapper_class' => 'sgp-largo',
		'description'   => __( 'Para incensos e essências. Vira um bloco próprio na página do produto.', 'sao-geronimo' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'            => '_sg_ficha',
		'label'         => __( 'Ficha técnica (linhas extras)', 'sao-geronimo' ),
		'rows'          => 5,
		'wrapper_class' => 'sgp-largo',
		'description'   => __( 'Uma linha para cada item, assim: Material: Resina e tecido. Referência, marca e categoria já entram sozinhas.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_peso_txt',
		'label'       => __( 'Peso por extenso', 'sao-geronimo' ),
		'placeholder' => '165 g',
		'desc_tip'    => true,
		'description' => __( 'Como o peso aparece para o cliente. Se vazio, usamos o peso da aba "Peso e medidas".', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_med_titulo',
		'label'       => __( 'Título das medidas', 'sao-geronimo' ),
		'placeholder' => 'Dimensões',
		'desc_tip'    => true,
		'description' => __( 'Ex.: "Dimensões com embalagem". Vazio = "Dimensões".', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'            => '_sg_med_aviso',
		'label'         => __( 'Aviso das medidas', 'sao-geronimo' ),
		'placeholder'   => 'Quer a medida exata da peça?',
		'wrapper_class' => 'sgp-largo',
		'desc_tip'      => true,
		'description'   => __( 'Se preenchido, aparece com o link "Pergunte no WhatsApp" no lugar do peso.', 'sao-geronimo' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_sg_nota',
		'label'       => __( 'Nota de vitrine (estrelas)', 'sao-geronimo' ),
		'placeholder' => '4.8',
		'desc_tip'    => true,
		'description' => __( 'Aparece nas estrelas enquanto o produto não tem avaliações de clientes. Vazio = não mostra.', 'sao-geronimo' ),
	) );
	echo '</div>';

	echo '<div class="options_group">';
	sg_wc_titulo_grupo( __( 'Venda', 'sao-geronimo' ) );
	woocommerce_wp_checkbox( array(
		'id'            => '_sg_sob_consulta',
		'label'         => __( 'Preço sob consulta', 'sao-geronimo' ),
		'description'   => __( 'Esconde o preço e troca o botão por "Consultar pelo WhatsApp".', 'sao-geronimo' ),
		'wrapper_class' => 'sgp-largo',
	) );
	echo '</div>';

	echo '</div>';
}
add_action( 'woocommerce_product_data_panels', 'sg_wc_campos_produto' );

/**
 * Salva os campos extras.
 *
 * O nonce do WooCommerce já foi conferido antes deste gancho (woocommerce_process_product_meta).
 *
 * @param int $id ID do produto.
 */
function sg_wc_salva_produto( $id ) {
	$textos = array( '_sg_dimensoes', '_sg_marca', '_sg_material', '_sg_conteudo', '_sg_cod_fabricante', '_sg_sobre_titulo', '_sg_peso_txt', '_sg_nota', '_sg_med_titulo', '_sg_med_aviso' );
	foreach ( $textos as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_text_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	$areas = array( '_sg_composicao', '_sg_modo_usar', '_sg_notas', '_sg_ficha' );
	foreach ( $areas as $c ) {
		if ( isset( $_POST[ $c ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $id, $c, sanitize_textarea_field( wp_unslash( $_POST[ $c ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
	update_post_meta( $id, '_sg_sob_consulta', isset( $_POST['_sg_sob_consulta'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification
}
add_action( 'woocommerce_process_product_meta', 'sg_wc_salva_produto' );
