<?php
/**
 * Mapa de tudo o que se pode editar no site.
 *
 * Cada "seção" vira uma página separada no painel. Cada campo tem:
 *  k = chave gravada (a mesma que o tema lê com sg_opt)   t = tipo
 *  l = rótulo   a = ajuda curta   d = valor padrão   o = opções   p = exemplo
 *  se = só aparece quando outro campo tem certo valor
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocos da página inicial, na ordem padrão.
 *
 * @return array chave => nome
 */
function sgc_blocos_home() {
	return array(
		'hero'        => 'Banners e frase principal',
		'busca'       => 'Barra de busca',
		'vitrine1'    => 'Vitrine 1',
		'categorias'  => 'Categorias em destaque',
		'vitrine2'    => 'Vitrine 2',
		'vitrine3'    => 'Vitrine 3',
		'vitrine4'    => 'Vitrine 4',
		'vitrine5'    => 'Vitrine 5',
		'sobre'       => 'Sobre nós',
		'depoimentos' => 'Depoimentos',
		'blog'        => 'Diário (blog)',
	);
}

/**
 * Campos de uma vitrine.
 *
 * @param int $n Número da vitrine.
 * @return array
 */
function sgc_campos_vitrine( $n ) {
	$p = 'home_vitrine' . $n . '_';
	return array(
		array( 'k' => $p . 'titulo', 't' => 'text', 'l' => 'Título da vitrine', 'a' => 'Deixe vazio para esconder esta vitrine.', 'p' => 'Ex.: Lançamentos' ),
		array( 'k' => $p . 'sub', 't' => 'text', 'l' => 'Frase embaixo do título' ),
		array( 'k' => $p . 'fonte', 't' => 'select', 'l' => 'Quais produtos mostrar', 'd' => 'recentes', 'o' => array(
			'recentes'  => 'Os mais novos',
			'destaque'  => 'Produtos marcados como destaque',
			'vendidos'  => 'Os mais vendidos',
			'avaliados' => 'Os mais bem avaliados',
			'promocao'  => 'Os que estão em oferta',
			'aleatorio' => 'Aleatórios',
			'categoria' => 'De uma categoria',
			'etiqueta'  => 'De uma linha (etiqueta)',
			'manual'    => 'Eu escolho pelo código (SKU)',
		) ),
		array( 'k' => $p . 'categoria', 't' => 'text', 'l' => 'Categoria', 'a' => 'Escreva o endereço da categoria (ex.: velas). Mais de uma: separe com vírgula.', 'se' => array( $p . 'fonte' => 'categoria' ) ),
		array( 'k' => $p . 'etiqueta', 't' => 'text', 'l' => 'Linha (etiqueta)', 'a' => 'Mais de uma: separe com vírgula.', 'se' => array( $p . 'fonte' => 'etiqueta' ) ),
		array( 'k' => $p . 'skus', 't' => 'text', 'l' => 'Códigos dos produtos (SKU)', 'a' => 'Separe com vírgula. Eles aparecem nessa ordem.', 'p' => '1256, 1301, 1488', 'se' => array( $p . 'fonte' => 'manual' ) ),
		array( 'k' => $p . 'qtd', 't' => 'number', 'l' => 'Quantos produtos', 'd' => 8, 'min' => 2, 'max' => 24 ),
		array( 'k' => $p . 'link', 't' => 'url', 'l' => 'Para onde leva o "Ver tudo"', 'a' => 'Vazio = página da loja.' ),
	);
}

/**
 * Todas as seções do painel.
 *
 * @return array
 */
function sgc_secoes() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$vitrines = array();
	for ( $n = 1; $n <= 5; $n++ ) {
		$vitrines[ 'Vitrine ' . $n ] = sgc_campos_vitrine( $n );
	}

	$cache = array(

		/* ---------------------------------------------------------- INÍCIO */
		array(
			'id' => 'banners', 'grupo' => 'Página inicial', 'icone' => '🖼️', 'titulo' => 'Banners',
			'desc' => 'As imagens grandes que passam no topo do site.', 'ver' => '/', 'ancora' => '.hero',
			'campos' => array(
				array( 'k' => 'banners', 't' => 'lista', 'l' => 'Seus banners', 'a' => 'Arraste para trocar a ordem. Imagem larga (ex.: 1920×700).', 'item' => 'Banner', 'sub' => array(
					array( 'k' => 'img', 't' => 'image', 'l' => 'Imagem do computador' ),
					array( 'k' => 'img_mob', 't' => 'image', 'l' => 'Imagem do celular (opcional)', 'a' => 'Se não enviar, usa a do computador.' ),
					array( 'k' => 'link', 't' => 'url', 'l' => 'Para onde leva ao clicar (opcional)' ),
					array( 'k' => 'alt', 't' => 'text', 'l' => 'Descrição da imagem', 'a' => 'Ajuda quem usa leitor de tela e o Google.' ),
				) ),
				array( 'k' => 'banner_tempo', 't' => 'number', 'l' => 'Tempo de cada banner', 'd' => 7000, 'esc' => 1000, 'un' => 'segundos', 'min' => 2, 'max' => 30 ),
			),
		),
		array(
			'id' => 'frase', 'grupo' => 'Página inicial', 'icone' => '✍️', 'titulo' => 'Frase e botões',
			'desc' => 'O texto de boas-vindas, os botões e os números de destaque.', 'ver' => '/', 'ancora' => '.hero__abaixo',
			'campos' => array(
				array( 'k' => 'hero_frase', 't' => 'html', 'l' => 'Frase principal', 'a' => 'Pode usar negrito e itálico.' ),
				array( 'k' => 'hero_sub', 't' => 'textarea', 'l' => 'Texto de apoio' ),
				array( 'k' => 'hero_btn1_txt', 't' => 'text', 'l' => 'Botão 1 — texto', 'd' => 'Explorar produtos' ),
				array( 'k' => 'hero_btn1_url', 't' => 'url', 'l' => 'Botão 1 — link', 'a' => 'Vazio = página da loja.' ),
				array( 'k' => 'hero_btn2_txt', 't' => 'text', 'l' => 'Botão 2 — texto', 'd' => 'Categorias', 'a' => 'Vazio = o botão não aparece.' ),
				array( 'k' => 'hero_btn2_url', 't' => 'url', 'l' => 'Botão 2 — link', 'a' => 'Vazio = página Categorias.' ),
				array( 'k' => 'estat1_n', 't' => 'text', 'l' => 'Número de destaque 1', 'p' => '15+' ),
				array( 'k' => 'estat1_t', 't' => 'text', 'l' => 'Texto do número 1', 'p' => 'anos de história' ),
				array( 'k' => 'estat2_n', 't' => 'text', 'l' => 'Número de destaque 2' ),
				array( 'k' => 'estat2_t', 't' => 'text', 'l' => 'Texto do número 2' ),
				array( 'k' => 'estat3_n', 't' => 'text', 'l' => 'Número de destaque 3' ),
				array( 'k' => 'estat3_t', 't' => 'text', 'l' => 'Texto do número 3' ),
			),
		),
		array(
			'id' => 'busca', 'grupo' => 'Página inicial', 'icone' => '🔎', 'titulo' => 'Busca',
			'desc' => 'A barra de busca da home e a gaveta de busca do topo.', 'ver' => '/', 'ancora' => '.busca-home',
			'campos' => array(
				array( 'k' => 'busca_home_texto', 't' => 'text', 'l' => 'Texto da barra na home', 'd' => 'O que você procura? Nome, orixá, aroma ou referência…' ),
				array( 'k' => 'busca_placeholder', 't' => 'text', 'l' => 'Texto dentro do campo de busca', 'd' => 'Buscar por nome, orixá, aroma ou referência…' ),
				array( 'k' => 'busca_sugestoes_lista', 't' => 'text', 'l' => 'Sugestões de busca', 'a' => 'Palavras separadas por vírgula.', 'd' => 'Incenso, São Jerônimo, Oxum, Vela, Sineta, Tarô, Palo Santo, Difusor' ),
				array( 'k' => 'busca_sugestoes', 't' => 'number', 'l' => 'Quantas sugestões mostrar', 'd' => 7, 'min' => 0, 'max' => 12 ),
			),
		),
		array(
			'id' => 'vitrines', 'grupo' => 'Página inicial', 'icone' => '🛍️', 'titulo' => 'Vitrines de produtos',
			'desc' => 'As fileiras de produtos que passam na home. Cada vitrine tem a sua aba.', 'ver' => '/', 'ancora' => '#sg-vitrine1',
			'grupos' => $vitrines,
		),
		array(
			'id' => 'categorias', 'grupo' => 'Página inicial', 'icone' => '🧭', 'titulo' => 'Categorias em destaque',
			'desc' => 'O carrossel de ícones de categorias.', 'ver' => '/', 'ancora' => '.cat-destaque',
			'campos' => array(
				array( 'k' => 'home_categorias_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Categorias em destaque' ),
				array( 'k' => 'home_categorias_sub', 't' => 'text', 'l' => 'Frase embaixo do título' ),
				array( 'k' => 'home_categorias_lista', 't' => 'cats', 'l' => 'Quais categorias aparecem', 'a' => 'Não marcou nenhuma? O site mostra as que têm mais produtos.' ),
				array( 'k' => 'home_categorias_qtd', 't' => 'number', 'l' => 'Quantas mostrar (se não escolheu acima)', 'd' => 14, 'min' => 4, 'max' => 40 ),
			),
		),
		array(
			'id' => 'sobre', 'grupo' => 'Página inicial', 'icone' => '📖', 'titulo' => 'Sobre nós',
			'desc' => 'A seção da história da loja.', 'ver' => '/', 'ancora' => '#sobre',
			'campos' => array(
				array( 'k' => 'sobre_eyebrow', 't' => 'text', 'l' => 'Palavrinha acima do título', 'd' => 'Quem somos' ),
				array( 'k' => 'sobre_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Sobre nós' ),
				array( 'k' => 'sobre_texto', 't' => 'html', 'l' => 'Texto curto', 'a' => 'Linha em branco separa os parágrafos.' ),
				array( 'k' => 'sobre_img', 't' => 'image', 'l' => 'Foto' ),
				array( 'k' => 'sobre_img_alt', 't' => 'text', 'l' => 'Descrição da foto', 'd' => 'São Gerônimo Religiosos' ),
				array( 'k' => 'sobre_cta', 't' => 'text', 'l' => 'Texto do botão "ler mais"', 'd' => 'LEIA MAIS' ),
				array( 'k' => 'sobre_completo_titulo', 't' => 'text', 'l' => 'Título da história completa', 'd' => 'Nossa história' ),
				array( 'k' => 'sobre_completo', 't' => 'html', 'l' => 'História completa (abre numa janelinha)' ),
			),
		),
		array(
			'id' => 'depoimentos', 'grupo' => 'Página inicial', 'icone' => '💬', 'titulo' => 'Depoimentos',
			'desc' => 'O que os clientes dizem.', 'ver' => '/', 'ancora' => '.depo',
			'campos' => array(
				array( 'k' => 'depoimentos_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'O que dizem de nós' ),
				array( 'k' => 'depoimentos_sub', 't' => 'text', 'l' => 'Frase embaixo do título' ),
				array( 'k' => 'depoimentos', 't' => 'lista', 'l' => 'Depoimentos', 'item' => 'Depoimento', 'sub' => array(
					array( 'k' => 'nome', 't' => 'text', 'l' => 'Nome' ),
					array( 'k' => 'local', 't' => 'text', 'l' => 'Cidade ou profissão' ),
					array( 'k' => 'nota', 't' => 'select', 'l' => 'Estrelas', 'd' => '5', 'o' => array( '5' => '5 estrelas', '4' => '4 estrelas', '3' => '3 estrelas', '2' => '2 estrelas', '1' => '1 estrela' ) ),
					array( 'k' => 'texto', 't' => 'textarea', 'l' => 'O que a pessoa disse' ),
				) ),
			),
		),
		array(
			'id' => 'diario', 'grupo' => 'Página inicial', 'icone' => '📰', 'titulo' => 'Diário (blog na home)',
			'desc' => 'As últimas novidades do blog que aparecem na home.', 'ver' => '/', 'ancora' => '.blog-home',
			'campos' => array(
				array( 'k' => 'home_blog_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Do nosso diário' ),
				array( 'k' => 'home_blog_sub', 't' => 'text', 'l' => 'Frase embaixo do título' ),
				array( 'k' => 'home_blog_qtd', 't' => 'number', 'l' => 'Quantos textos mostrar', 'd' => 3, 'min' => 1, 'max' => 9 ),
			),
		),
		array(
			'id' => 'ordem', 'grupo' => 'Página inicial', 'icone' => '↕️', 'titulo' => 'Ordem das seções',
			'desc' => 'Arraste para mudar a ordem da página inicial. O olhinho liga e desliga cada seção.', 'ver' => '/', 'ancora' => '',
			'campos' => array(
				array( 'k' => '_ordem', 't' => 'ordem', 'l' => 'Seções da página inicial' ),
			),
		),

		/* ------------------------------------------------------------ LOJA */
		array(
			'id' => 'loja', 'grupo' => 'Página da loja', 'icone' => '🏪', 'titulo' => 'Loja e categorias',
			'desc' => 'A página com todos os produtos, os filtros e as páginas de categoria.', 'ver' => 'loja', 'ancora' => '',
			'grupos' => array(
				'Capa e filtros' => array(
					array( 'k' => 'loja_titulo', 't' => 'text', 'l' => 'Título da página', 'd' => 'Loja' ),
					array( 'k' => 'loja_desc', 't' => 'textarea', 'l' => 'Frase embaixo do título', 'd' => 'Todo o catálogo da São Gerônimo em um só lugar.' ),
					array( 'k' => 'loja_filtros_modo', 't' => 'select', 'l' => 'Estilo dos filtros', 'd' => 'original', 'o' => array( 'original' => 'Igual ao site original (lista de categorias)', 'completo' => 'Completo (categorias, preço, linha, ofertas)' ), 'a' => 'Os itens abaixo só valem no estilo Completo.' ),
					array( 'k' => 'loja_filtros', 't' => 'toggle', 'l' => 'Mostrar a barra de filtros (estilo Completo)', 'd' => 'sim' ),
					array( 'k' => 'loja_filtro_categorias', 't' => 'toggle', 'l' => 'Filtro de categorias', 'd' => 'sim' ),
					array( 'k' => 'loja_filtro_preco', 't' => 'toggle', 'l' => 'Filtro de preço', 'd' => 'sim' ),
					array( 'k' => 'loja_filtro_linhas', 't' => 'toggle', 'l' => 'Filtro de linhas', 'd' => 'sim' ),
					array( 'k' => 'loja_colunas', 't' => 'number', 'l' => 'Produtos por linha', 'd' => 4, 'min' => 2, 'max' => 5 ),
					array( 'k' => 'loja_por_pagina', 't' => 'number', 'l' => 'Produtos por página', 'd' => 24, 'min' => 4, 'max' => 96 ),
				),
				'Cartões dos produtos' => array(
					array( 'k' => 'texto_botao_comprar', 't' => 'text', 'l' => 'Texto do botão de compra', 'd' => 'Comprar agora' ),
					array( 'k' => 'selo_novo_dias', 't' => 'number', 'l' => 'Selo "Novo" fica por quantos dias', 'd' => 30, 'min' => 0, 'max' => 365, 'a' => '0 desliga o selo.' ),
					array( 'k' => 'mostrar_favoritos', 't' => 'toggle', 'l' => 'Mostrar o coração de favoritos', 'd' => 'sim' ),
				),
			),
		),
		array(
			'id' => 'produto', 'grupo' => 'Página do produto', 'icone' => '🏷️', 'titulo' => 'Página do produto',
			'desc' => 'Parcelas, Pix, selos de confiança e produtos relacionados.', 'ver' => 'produto', 'ancora' => '',
			'grupos' => array(
				'Preço e parcelas' => array(
					array( 'k' => 'parcelas_max', 't' => 'number', 'l' => 'Parcelas sem juros (máximo)', 'd' => 10, 'min' => 1, 'max' => 12, 'a' => 'Só informa o cliente. As parcelas reais vêm da sua conta do Mercado Pago.' ),
					array( 'k' => 'pix_desconto', 't' => 'number', 'l' => 'Desconto no Pix (%)', 'd' => 5, 'min' => 0, 'max' => 50 ),
				),
				'Selos de confiança' => array(
					array( 'k' => 'produto_selos', 't' => 'toggle', 'l' => 'Mostrar os selos embaixo do botão de compra', 'd' => 'nao', 'a' => 'O site original não tinha selos; ligue se quiser.' ),
					array( 'k' => 'produto_selo1', 't' => 'text', 'l' => 'Selo 1', 'd' => 'Compra 100% segura' ),
					array( 'k' => 'produto_selo2', 't' => 'text', 'l' => 'Selo 2', 'd' => 'Enviamos para todo o Brasil' ),
					array( 'k' => 'produto_selo3', 't' => 'text', 'l' => 'Selo 3', 'd' => 'Troca fácil em até 7 dias' ),
					array( 'k' => 'produto_aviso', 't' => 'text', 'l' => 'Aviso pequeno embaixo (opcional)', 'p' => 'Produto artesanal: cor e tamanho podem variar.' ),
				),
				'Frete e relacionados' => array(
					array( 'k' => 'produto_frete', 't' => 'toggle', 'l' => 'Mostrar a caixa "Calcular frete e prazo"', 'd' => 'sim' ),
					array( 'k' => 'produto_relacionados', 't' => 'toggle', 'l' => 'Mostrar "Quem viu, levou também"', 'd' => 'sim' ),
					array( 'k' => 'produto_relacionados_titulo', 't' => 'text', 'l' => 'Título dessa faixa', 'd' => 'Quem viu, levou também' ),
				),
			),
		),
		array(
			'id' => 'post', 'grupo' => 'Posts do blog', 'icone' => '📝', 'titulo' => 'Post individual',
			'desc' => 'Como cada texto do blog aparece.', 'ver' => 'post', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'post_mostra_data', 't' => 'toggle', 'l' => 'Mostrar a data', 'd' => 'sim' ),
				array( 'k' => 'post_mostra_autor', 't' => 'toggle', 'l' => 'Mostrar o autor', 'd' => 'sim' ),
				array( 'k' => 'post_relacionados', 't' => 'toggle', 'l' => 'Mostrar "Leia também" no final', 'd' => 'sim' ),
				array( 'k' => 'post_relacionados_titulo', 't' => 'text', 'l' => 'Título do "Leia também"', 'd' => 'Leia também' ),
			),
		),
		array(
			'id' => 'carrinho', 'grupo' => 'Carrinho e checkout', 'icone' => '🛒', 'titulo' => 'Carrinho',
			'desc' => 'A página da sacola de compras.', 'ver' => 'carrinho', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'frete_gratis_valor', 't' => 'number', 'l' => 'Frete grátis a partir de (R$)', 'd' => 0, 'min' => 0, 'max' => 99999, 'a' => 'Mostra uma barrinha "falta pouco para o frete grátis". 0 desliga. A regra de frete em si é configurada em Gestão → Pagamento e frete.' ),
			),
		),
		array(
			'id' => 'checkout', 'grupo' => 'Carrinho e checkout', 'icone' => '💳', 'titulo' => 'Finalizar compra',
			'desc' => 'A página de pagamento. Os campos e as formas de pagar vêm do WooCommerce e do Mercado Pago.', 'ver' => 'checkout', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'checkout_aviso', 't' => 'text', 'l' => 'Aviso no topo (opcional)', 'p' => 'Pedidos até 15h saem no mesmo dia.' ),
				array( 'k' => 'checkout_botao', 't' => 'text', 'l' => 'Texto do botão final', 'p' => 'Finalizar pedido' ),
				array( 'k' => 'checkout_seguranca', 't' => 'text', 'l' => 'Frase de segurança embaixo do botão', 'd' => 'Pagamento seguro. Seus dados são protegidos.' ),
			),
		),
		array(
			'id' => 'conta', 'grupo' => 'Minha conta', 'icone' => '🙋', 'titulo' => 'Área do cliente',
			'desc' => 'A página onde o cliente vê pedidos, endereços e dados.', 'ver' => 'conta', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'conta_boas_vindas', 't' => 'textarea', 'l' => 'Recado de boas-vindas (opcional)', 'p' => 'Aqui você acompanha seus pedidos e atualiza seus dados.' ),
			),
		),
		array(
			'id' => 'paginas', 'grupo' => 'Outras páginas', 'icone' => '📄', 'titulo' => 'Páginas comuns',
			'desc' => 'Contato, trocas e devoluções, políticas… Os textos de cada página você edita em Gestão → Páginas.', 'ver' => '/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'pagina_mostra_capa', 't' => 'toggle', 'l' => 'Mostrar a imagem de capa das páginas', 'd' => 'sim' ),
			),
		),
		array(
			'id' => 'erro404', 'grupo' => 'Outras páginas', 'icone' => '🧩', 'titulo' => 'Página não encontrada',
			'desc' => 'O que aparece quando o endereço não existe.', 'ver' => '/pagina-que-nao-existe-sgc/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'texto_404_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Esta página não existe mais.' ),
				array( 'k' => 'texto_404', 't' => 'textarea', 'l' => 'Texto', 'd' => 'Pode ser que o endereço tenha mudado. Use a busca ou volte para a loja — o que você procura provavelmente está lá.' ),
			),
		),
		array(
			'id' => 'contato', 'grupo' => 'Atendimento', 'icone' => '📞', 'titulo' => 'Contato e WhatsApp',
			'desc' => 'Telefone, e-mail, endereço e o botão flutuante do WhatsApp.', 'ver' => '/', 'ancora' => '.rodape',
			'campos' => array(
				array( 'k' => 'whatsapp', 't' => 'text', 'l' => 'WhatsApp (com DDD)', 'a' => 'Só números, com 55 na frente.', 'p' => '5548999999999', 'd' => '5548996397562' ),
				array( 'k' => 'whatsapp_exibido', 't' => 'text', 'l' => 'Como o número aparece escrito', 'p' => '(48) 99999-9999' ),
				array( 'k' => 'whatsapp_msg', 't' => 'text', 'l' => 'Mensagem pronta', 'd' => 'Olá! Vim pelo site e gostaria de uma ajuda.' ),
				array( 'k' => 'whatsapp_flutuante', 't' => 'toggle', 'l' => 'Botão flutuante do WhatsApp', 'd' => 'sim' ),
				array( 'k' => 'telefone', 't' => 'text', 'l' => 'Telefone' ),
				array( 'k' => 'email_contato', 't' => 'text', 'l' => 'E-mail' ),
				array( 'k' => 'endereco', 't' => 'textarea', 'l' => 'Endereço' ),
			),
		),

		/* ---------------------------------------------------------- VISUAL */
		array(
			'id' => 'cores', 'grupo' => 'Visual do site', 'icone' => '🎨', 'titulo' => 'Cores',
			'desc' => 'Mude uma cor e o site inteiro acompanha.', 'ver' => '/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'cor_primaria', 't' => 'color', 'l' => 'Cor principal (botões e links)', 'd' => '#1E40AF' ),
				array( 'k' => 'cor_logo', 't' => 'color', 'l' => 'Cor do logotipo', 'd' => '#1E40AF' ),
				array( 'k' => 'cor_destaque', 't' => 'color', 'l' => 'Cor de destaque (dourado)', 'd' => '#D6A32F' ),
				array( 'k' => 'cor_fundo', 't' => 'color', 'l' => 'Cor do fundo', 'd' => '#FCFAF5' ),
				array( 'k' => 'cor_texto', 't' => 'color', 'l' => 'Cor dos textos', 'd' => '#1B2A4A' ),
				array( 'k' => 'cor_whatsapp', 't' => 'color', 'l' => 'Verde do WhatsApp', 'd' => '#25D366' ),
			),
		),
		array(
			'id' => 'logo', 'grupo' => 'Visual do site', 'icone' => '🔤', 'titulo' => 'Logo, letras e formato',
			'desc' => 'Logotipo, fontes e jeito dos cantos.', 'ver' => '/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'logo_img', 't' => 'image', 'l' => 'Logotipo (imagem)', 'a' => 'Sem imagem, o site usa o nome em texto.' ),
				array( 'k' => 'logo_texto', 't' => 'text', 'l' => 'Nome em texto' ),
				array( 'k' => 'logo_altura', 't' => 'number', 'l' => 'Altura do logotipo (px)', 'd' => 34, 'min' => 18, 'max' => 90 ),
				array( 'k' => 'fonte_familia', 't' => 'text', 'l' => 'Letra do site', 'd' => 'Inter', 'a' => 'Nome de uma fonte do Google Fonts.' ),
				array( 'k' => 'fonte_google', 't' => 'text', 'l' => 'Código da fonte no Google', 'd' => 'Inter:wght@300;400;500;600;700', 'a' => 'Só mude se trocar a letra acima.' ),
				array( 'k' => 'fonte_titulo', 't' => 'text', 'l' => 'Letra dos títulos', 'a' => 'Vazio = a mesma do site.' ),
				array( 'k' => 'raio_cantos', 't' => 'number', 'l' => 'Arredondamento dos cantos (px)', 'd' => 10, 'min' => 0, 'max' => 40 ),
				array( 'k' => 'largura_site', 't' => 'number', 'l' => 'Largura máxima do site (px)', 'd' => 1400, 'min' => 960, 'max' => 1920 ),
			),
		),
		array(
			'id' => 'topo', 'grupo' => 'Visual do site', 'icone' => '📢', 'titulo' => 'Topo e menu',
			'desc' => 'O aviso acima do menu e o item "Home" do menu do topo.', 'ver' => '/', 'ancora' => '.cab',
			'campos' => array(
				array( 'k' => 'menu_home', 't' => 'toggle', 'l' => 'Mostrar "Home" no menu do topo', 'd' => 'sim', 'a' => 'Aparece na frente dos outros itens do menu, no computador e no celular.' ),
				array( 'k' => 'menu_home_txt', 't' => 'text', 'l' => 'Texto do item', 'd' => 'Home', 'a' => 'Ex.: Home, Início ou Página inicial.' ),
				array( 'k' => 'faixa_topo_ativa', 't' => 'toggle', 'l' => 'Mostrar o aviso', 'd' => 'sim' ),
				array( 'k' => 'faixa_topo', 't' => 'text', 'l' => 'Texto do aviso', 'p' => 'Frete grátis acima de R$ 299' ),
				array( 'k' => 'faixa_topo_link', 't' => 'url', 'l' => 'Link do aviso (opcional)' ),
			),
		),
		array(
			'id' => 'rodape', 'grupo' => 'Visual do site', 'icone' => '🦶', 'titulo' => 'Rodapé',
			'desc' => 'A faixa de redes sociais e o rodapé do site.', 'ver' => '/', 'ancora' => '.rodape',
			'grupos' => array(
				'Faixa de redes' => array(
					array( 'k' => 'faixa_social_ativa', 't' => 'toggle', 'l' => 'Mostrar a faixa de WhatsApp e Instagram', 'd' => 'sim' ),
					array( 'k' => 'faixa_social_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Fale com a gente ou acompanhe as novidades' ),
					array( 'k' => 'faixa_social_sub', 't' => 'text', 'l' => 'Frase', 'd' => 'Atendimento rápido no WhatsApp e lançamentos em primeira mão no Instagram.' ),
					array( 'k' => 'url_instagram', 't' => 'url', 'l' => 'Link do Instagram' ),
					array( 'k' => 'instagram_arroba', 't' => 'text', 'l' => '@ do Instagram' ),
					array( 'k' => 'url_facebook', 't' => 'url', 'l' => 'Link do Facebook' ),
				),
				'Textos' => array(
					array( 'k' => 'rodape_sobre', 't' => 'textarea', 'l' => 'Textinho sobre a loja', 'd' => 'Há mais de 15 anos trazendo para a sua vida a espiritualidade em cada detalhe.' ),
					array( 'k' => 'rodape_col1', 't' => 'text', 'l' => 'Título da coluna 1', 'd' => 'Navegar' ),
					array( 'k' => 'rodape_col2', 't' => 'text', 'l' => 'Título da coluna 2', 'd' => 'Conta' ),
					array( 'k' => 'rodape_col3', 't' => 'text', 'l' => 'Título da coluna 3', 'd' => 'Atendimento' ),
					array( 'k' => 'razao_social', 't' => 'text', 'l' => 'Nome da empresa (©)' ),
					array( 'k' => 'rodape_frase', 't' => 'text', 'l' => 'Frase final', 'd' => 'Sua fé, sua energia, seu caminho.' ),
					array( 'k' => 'rodape_assinatura', 't' => 'text', 'l' => 'Assinatura', 'd' => 'Desenvolvido por Kong Media' ),
				),
			),
		),
		array(
			'id' => 'avancado', 'grupo' => 'Visual do site', 'icone' => '🛠️', 'titulo' => 'Avançado',
			'desc' => 'Para quem sabe CSS. Se não sabe, pode ignorar.', 'ver' => '/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'css_extra', 't' => 'css', 'l' => 'CSS extra', 'a' => 'Entra no site inteiro.' ),
			),
		),
	);

	return $cache;
}

/**
 * Lista plana de todos os campos (incluindo os de dentro de grupos).
 *
 * @return array chave => campo
 */
function sgc_todos_campos() {
	$out = array();
	foreach ( sgc_secoes() as $s ) {
		$blocos = isset( $s['grupos'] ) ? $s['grupos'] : array( '' => $s['campos'] );
		foreach ( $blocos as $campos ) {
			foreach ( $campos as $c ) {
				$out[ $c['k'] ] = $c;
			}
		}
	}
	return $out;
}
