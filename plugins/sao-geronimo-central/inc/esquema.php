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
				array( 'k' => 'hero_btn2_txt', 't' => 'text', 'l' => 'Botão 2 — texto', 'a' => 'Vazio = o botão não aparece.' ),
				array( 'k' => 'hero_btn2_url', 't' => 'url', 'l' => 'Botão 2 — link' ),
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
			'id' => 'loja', 'grupo' => 'Loja e atendimento', 'icone' => '🏪', 'titulo' => 'Página da loja',
			'desc' => 'Como os produtos aparecem e como o preço é mostrado.', 'ver' => 'loja', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'loja_colunas', 't' => 'number', 'l' => 'Produtos por linha (computador)', 'd' => 4, 'min' => 2, 'max' => 5 ),
				array( 'k' => 'loja_por_pagina', 't' => 'number', 'l' => 'Produtos por página', 'd' => 24, 'min' => 4, 'max' => 96 ),
				array( 'k' => 'texto_botao_comprar', 't' => 'text', 'l' => 'Texto do botão de compra', 'd' => 'Comprar agora' ),
				array( 'k' => 'selo_novo_dias', 't' => 'number', 'l' => 'Selo "Novo" fica por quantos dias', 'd' => 30, 'min' => 0, 'max' => 365, 'a' => '0 desliga o selo.' ),
				array( 'k' => 'parcelas_max', 't' => 'number', 'l' => 'Parcelas sem juros (máximo)', 'd' => 10, 'min' => 1, 'max' => 12 ),
				array( 'k' => 'pix_desconto', 't' => 'number', 'l' => 'Desconto no Pix (%)', 'd' => 5, 'min' => 0, 'max' => 50 ),
				array( 'k' => 'mostrar_favoritos', 't' => 'toggle', 'l' => 'Mostrar o coração de favoritos', 'd' => 'sim' ),
			),
		),
		array(
			'id' => 'contato', 'grupo' => 'Loja e atendimento', 'icone' => '📞', 'titulo' => 'Contato e WhatsApp',
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
			'id' => 'cores', 'grupo' => 'Visual', 'icone' => '🎨', 'titulo' => 'Cores',
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
			'id' => 'logo', 'grupo' => 'Visual', 'icone' => '🔤', 'titulo' => 'Logo, letras e formato',
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
			'id' => 'topo', 'grupo' => 'Visual', 'icone' => '📢', 'titulo' => 'Aviso do topo',
			'desc' => 'A faixinha de aviso acima do menu.', 'ver' => '/', 'ancora' => '.faixa-topo',
			'campos' => array(
				array( 'k' => 'faixa_topo_ativa', 't' => 'toggle', 'l' => 'Mostrar o aviso', 'd' => 'sim' ),
				array( 'k' => 'faixa_topo', 't' => 'text', 'l' => 'Texto do aviso', 'p' => 'Frete grátis acima de R$ 299' ),
				array( 'k' => 'faixa_topo_link', 't' => 'url', 'l' => 'Link do aviso (opcional)' ),
			),
		),
		array(
			'id' => 'rodape', 'grupo' => 'Visual', 'icone' => '🦶', 'titulo' => 'Rodapé',
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
			'id' => 'erro404', 'grupo' => 'Visual', 'icone' => '🧩', 'titulo' => 'Página não encontrada',
			'desc' => 'O que aparece quando o endereço não existe.', 'ver' => '/pagina-que-nao-existe-sgc/', 'ancora' => '',
			'campos' => array(
				array( 'k' => 'texto_404_titulo', 't' => 'text', 'l' => 'Título', 'd' => 'Esta página não existe mais.' ),
				array( 'k' => 'texto_404', 't' => 'textarea', 'l' => 'Texto', 'd' => 'Pode ser que o endereço tenha mudado. Use a busca ou volte para a loja — o que você procura provavelmente está lá.' ),
			),
		),
		array(
			'id' => 'avancado', 'grupo' => 'Visual', 'icone' => '🛠️', 'titulo' => 'Avançado',
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
