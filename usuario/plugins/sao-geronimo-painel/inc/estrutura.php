<?php
/**
 * Estrutura do painel: abas, seções e campos.
 *
 * É AQUI que se mexe para acrescentar ou tirar campos do painel. Cada campo
 * vira automaticamente um controle no admin e uma opção legível no tema por
 * sg_opt( 'chave' ). Não é preciso tocar em mais nada.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devolve a estrutura completa do painel.
 *
 * @return array
 */
function sgp_estrutura() {
	$fontes = array(
		'Inter'             => 'Inter (atual)',
		'Lora'              => 'Lora (serifada, clássica)',
		'Cormorant Garamond' => 'Cormorant Garamond (serifada fina)',
		'Playfair Display'  => 'Playfair Display (serifada marcante)',
		'Poppins'           => 'Poppins (arredondada)',
		'Montserrat'        => 'Montserrat (geométrica)',
		'Raleway'           => 'Raleway (leve)',
		'Nunito Sans'       => 'Nunito Sans (suave)',
	);

	$fonte_vitrine = array(
		'recentes'  => __( 'Produtos mais recentes', 'sao-geronimo-painel' ),
		'vendidos'  => __( 'Mais vendidos', 'sao-geronimo-painel' ),
		'destaque'  => __( 'Marcados como destaque', 'sao-geronimo-painel' ),
		'promocao'  => __( 'Em promoção', 'sao-geronimo-painel' ),
		'avaliados' => __( 'Melhor avaliados', 'sao-geronimo-painel' ),
		'categoria' => __( 'De uma categoria', 'sao-geronimo-painel' ),
		'etiqueta'  => __( 'De uma etiqueta', 'sao-geronimo-painel' ),
		'manual'    => __( 'Escolhidos por mim (lista de referências)', 'sao-geronimo-painel' ),
		'aleatorio' => __( 'Sorteados a cada visita', 'sao-geronimo-painel' ),
	);

	/**
	 * Monta os campos de uma vitrine da home.
	 *
	 * @param int    $n       Número do slot.
	 * @param string $titulo  Título padrão.
	 * @param string $fonte   Fonte padrão.
	 * @return array
	 */
	$vitrine = function ( $n, $titulo, $fonte, $sub = '' ) use ( $fonte_vitrine ) {
		$p = 'home_vitrine' . $n . '_';
		return array(
			$p . 'ativo'     => array( 'rotulo' => __( 'Mostrar esta vitrine', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
			$p . 'titulo'    => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => $titulo, 'dica' => __( 'Deixe vazio para esconder a vitrine.', 'sao-geronimo-painel' ) ),
			$p . 'sub'       => array( 'rotulo' => __( 'Linha de apoio', 'sao-geronimo-painel' ), 'padrao' => $sub ),
			$p . 'fonte'     => array( 'rotulo' => __( 'Quais produtos mostrar', 'sao-geronimo-painel' ), 'tipo' => 'select', 'opcoes' => $fonte_vitrine, 'padrao' => $fonte ),
			$p . 'categoria' => array( 'rotulo' => __( 'Categoria (se escolheu "de uma categoria")', 'sao-geronimo-painel' ), 'ph' => 'incensos', 'dica' => __( 'Use o atalho da categoria (o pedaço que aparece no endereço). Separe por vírgula para mais de uma.', 'sao-geronimo-painel' ) ),
			$p . 'etiqueta'  => array( 'rotulo' => __( 'Etiqueta (se escolheu "de uma etiqueta")', 'sao-geronimo-painel' ), 'ph' => 'presentes' ),
			$p . 'skus'      => array( 'rotulo' => __( 'Referências (se escolheu "escolhidos por mim")', 'sao-geronimo-painel' ), 'ph' => 'NOA-OXUM, SIN-01, IR-11743', 'larga' => true ),
			$p . 'qtd'       => array( 'rotulo' => __( 'Quantos produtos', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 2, 'max' => 24, 'padrao' => 8 ),
			$p . 'link'      => array( 'rotulo' => __( 'Link do "Ver tudo"', 'sao-geronimo-painel' ), 'html' => 'url', 'dica' => __( 'Vazio = vai para a loja.', 'sao-geronimo-painel' ) ),
		);
	};

	return array(

		/* ================================================== IDENTIDADE === */
		'identidade' => array(
			'titulo' => __( 'Identidade', 'sao-geronimo-painel' ),
			'icone'  => 'admin-appearance',
			'resumo' => __( 'A cara da loja: logo, cores, letras e cantos. Mudou aqui, mudou no site inteiro.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'logo' => array(
					'titulo' => __( 'Logotipo', 'sao-geronimo-painel' ),
					'campos' => array(
						'logo_img'     => array( 'rotulo' => __( 'Imagem do logo', 'sao-geronimo-painel' ), 'tipo' => 'imagem', 'dica' => __( 'PNG com fundo transparente fica melhor. Se não enviar nada, usamos o nome escrito abaixo.', 'sao-geronimo-painel' ) ),
						'logo_texto'   => array( 'rotulo' => __( 'Nome escrito (quando não há imagem)', 'sao-geronimo-painel' ), 'padrao' => 'São Gerônimo' ),
						'logo_altura'  => array( 'rotulo' => __( 'Altura do logo', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 20, 'max' => 90, 'padrao' => 34, 'sufixo' => 'px' ),
						'favicon'      => array( 'rotulo' => __( 'Ícone da aba do navegador', 'sao-geronimo-painel' ), 'tipo' => 'imagem', 'dica' => __( 'Quadrado, 512×512 px.', 'sao-geronimo-painel' ) ),
					),
				),
				'cores' => array(
					'titulo' => __( 'Cores', 'sao-geronimo-painel' ),
					'campos' => array(
						'cor_primaria' => array( 'rotulo' => __( 'Cor principal', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#1E40AF', 'dica' => __( 'Botões, preços e links.', 'sao-geronimo-painel' ) ),
						'cor_logo'     => array( 'rotulo' => __( 'Cor do logotipo escrito', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#1E40AF' ),
						'cor_destaque' => array( 'rotulo' => __( 'Cor de destaque', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#D6A32F', 'dica' => __( 'Estrelas, detalhes dourados e o traço sob o menu ativo.', 'sao-geronimo-painel' ) ),
						'cor_fundo'    => array( 'rotulo' => __( 'Cor de fundo', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#FCFAF5' ),
						'cor_texto'    => array( 'rotulo' => __( 'Cor do texto', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#1B2A4A' ),
						'cor_whatsapp' => array( 'rotulo' => __( 'Cor do WhatsApp', 'sao-geronimo-painel' ), 'tipo' => 'cor', 'padrao' => '#25D366' ),
					),
				),
				'letras' => array(
					'titulo' => __( 'Letras e formato', 'sao-geronimo-painel' ),
					'campos' => array(
						'fonte_familia' => array( 'rotulo' => __( 'Letra do site', 'sao-geronimo-painel' ), 'tipo' => 'select', 'opcoes' => $fontes, 'padrao' => 'Inter' ),
						'fonte_titulo'  => array( 'rotulo' => __( 'Letra dos títulos', 'sao-geronimo-painel' ), 'tipo' => 'select', 'opcoes' => $fontes, 'padrao' => 'Inter' ),
						'fonte_google'  => array( 'rotulo' => __( 'Pesos a carregar', 'sao-geronimo-painel' ), 'padrao' => 'Inter:wght@300;400;500;600;700', 'dica' => __( 'Técnico — só mexa se trocou a letra e os títulos ficaram grossos demais.', 'sao-geronimo-painel' ), 'larga' => true ),
						'raio_cantos'   => array( 'rotulo' => __( 'Arredondamento dos cantos', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 0, 'max' => 40, 'padrao' => 10, 'sufixo' => 'px' ),
						'largura_site'  => array( 'rotulo' => __( 'Largura máxima do conteúdo', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 960, 'max' => 1920, 'padrao' => 1400, 'sufixo' => 'px' ),
					),
				),
			),
		),

		/* ======================================================== TOPO === */
		'topo' => array(
			'titulo' => __( 'Topo e rodapé', 'sao-geronimo-painel' ),
			'icone'  => 'align-center',
			'resumo' => __( 'A faixa de aviso, a busca e os selos de confiança do rodapé.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'faixa' => array(
					'titulo' => __( 'Faixa de aviso (acima do menu)', 'sao-geronimo-painel' ),
					'campos' => array(
						'faixa_topo_ativa' => array( 'rotulo' => __( 'Mostrar a faixa', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'faixa_topo'       => array( 'rotulo' => __( 'Texto', 'sao-geronimo-painel' ), 'padrao' => 'Há mais de 15 anos trazendo espiritualidade em cada detalhe · Entrega para todo o Brasil', 'larga' => true ),
						'faixa_topo_link'  => array( 'rotulo' => __( 'Link (opcional)', 'sao-geronimo-painel' ), 'html' => 'url' ),
					),
				),
				'busca' => array(
					'titulo' => __( 'Busca', 'sao-geronimo-painel' ),
					'campos' => array(
						'busca_placeholder'     => array( 'rotulo' => __( 'Texto dentro do campo', 'sao-geronimo-painel' ), 'padrao' => 'Buscar por nome, orixá, aroma ou referência…', 'larga' => true ),
						'busca_home_texto'      => array( 'rotulo' => __( 'Texto da barra na página inicial', 'sao-geronimo-painel' ), 'padrao' => 'O que você procura? Nome, orixá, aroma ou referência…', 'larga' => true ),
						'busca_sugestoes'       => array( 'rotulo' => __( 'Quantas sugestões aparecem', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 3, 'max' => 12, 'padrao' => 7 ),
						'busca_sugestoes_lista' => array( 'rotulo' => __( 'Buscas sugeridas', 'sao-geronimo-painel' ), 'padrao' => 'Incenso, São Jerônimo, Oxum, Vela, Sineta, Tarô, Palo Santo, Difusor', 'dica' => __( 'Aparecem antes de o cliente digitar. Separe por vírgula.', 'sao-geronimo-painel' ), 'larga' => true ),
					),
				),
				'selos' => array(
					'titulo' => __( 'Selos de confiança (rodapé)', 'sao-geronimo-painel' ),
					'campos' => array(
						'selos_ativos' => array( 'rotulo' => __( 'Mostrar os selos', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'selo1_tit' => array( 'rotulo' => __( 'Selo 1 — título', 'sao-geronimo-painel' ), 'padrao' => 'Entrega para todo o Brasil' ),
						'selo1_sub' => array( 'rotulo' => __( 'Selo 1 — descrição', 'sao-geronimo-painel' ), 'padrao' => 'Enviamos para qualquer cidade.' ),
						'selo2_tit' => array( 'rotulo' => __( 'Selo 2 — título', 'sao-geronimo-painel' ), 'padrao' => 'Compra segura' ),
						'selo2_sub' => array( 'rotulo' => __( 'Selo 2 — descrição', 'sao-geronimo-painel' ), 'padrao' => 'Pagamento protegido.' ),
						'selo3_tit' => array( 'rotulo' => __( 'Selo 3 — título', 'sao-geronimo-painel' ), 'padrao' => 'Embalagem com carinho' ),
						'selo3_sub' => array( 'rotulo' => __( 'Selo 3 — descrição', 'sao-geronimo-painel' ), 'padrao' => 'Cada peça vai protegida.' ),
						'selo4_tit' => array( 'rotulo' => __( 'Selo 4 — título', 'sao-geronimo-painel' ), 'padrao' => '+15 anos de tradição' ),
						'selo4_sub' => array( 'rotulo' => __( 'Selo 4 — descrição', 'sao-geronimo-painel' ), 'padrao' => 'Desde o começo com você.' ),
					),
				),
				'faixa_social' => array(
					'titulo' => __( 'Faixa de WhatsApp e Instagram (acima do rodapé)', 'sao-geronimo-painel' ),
					'campos' => array(
						'faixa_social_ativa'   => array( 'rotulo' => __( 'Mostrar a faixa', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'faixa_social_titulo'  => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => 'Fale com a gente ou acompanhe as novidades', 'larga' => true ),
						'faixa_social_sub'     => array( 'rotulo' => __( 'Linha de apoio', 'sao-geronimo-painel' ), 'padrao' => 'Atendimento rápido no WhatsApp e lançamentos em primeira mão no Instagram.', 'larga' => true ),
					),
				),
				'rodape' => array(
					'titulo' => __( 'Rodapé', 'sao-geronimo-painel' ),
					'campos' => array(
						'rodape_col1'       => array( 'rotulo' => __( 'Título da coluna 1', 'sao-geronimo-painel' ), 'padrao' => 'Navegar' ),
						'rodape_col2'       => array( 'rotulo' => __( 'Título da coluna 2', 'sao-geronimo-painel' ), 'padrao' => 'Conta' ),
						'rodape_col3'       => array( 'rotulo' => __( 'Título da coluna 3', 'sao-geronimo-painel' ), 'padrao' => 'Atendimento' ),
						'rodape_frase'      => array( 'rotulo' => __( 'Frase ao lado do copyright', 'sao-geronimo-painel' ), 'padrao' => 'Sua fé, sua energia, seu caminho.', 'larga' => true ),
						'rodape_assinatura' => array( 'rotulo' => __( 'Assinatura', 'sao-geronimo-painel' ), 'padrao' => 'Desenvolvido por Kong Media' ),
						'rodape_sobre' => array( 'rotulo' => __( 'Texto ao lado do logo', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'padrao' => 'Há mais de 15 anos trazendo para a sua vida a espiritualidade em cada detalhe. Sua fé, sua energia, seu caminho.', 'larga' => true ),
						'razao_social' => array( 'rotulo' => __( 'Razão social', 'sao-geronimo-painel' ), 'padrao' => 'São Gerônimo Religiosos' ),
						'cnpj'         => array( 'rotulo' => __( 'CNPJ', 'sao-geronimo-painel' ) ),
					),
				),
			),
		),

		/* ======================================================== HOME === */
		'home' => array(
			'titulo' => __( 'Página inicial', 'sao-geronimo-painel' ),
			'icone'  => 'admin-home',
			'resumo' => __( 'Monte a home: arraste os blocos para reordenar e preencha cada um.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'ordem' => array(
					'titulo' => __( 'Ordem dos blocos', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_ordem' => array(
							'rotulo' => __( 'Como a página fica, de cima para baixo', 'sao-geronimo-painel' ),
							'tipo'   => 'ordem',
							'larga'  => true,
							'itens'  => array(
								'hero'        => __( 'Banner rotativo', 'sao-geronimo-painel' ),
								'busca'       => __( 'Barra de busca', 'sao-geronimo-painel' ),
								'vitrine1'    => __( 'Vitrine 1', 'sao-geronimo-painel' ),
								'categorias'  => __( 'Carrossel de categorias', 'sao-geronimo-painel' ),
								'vitrine2'    => __( 'Vitrine 2', 'sao-geronimo-painel' ),
								'vitrine3'    => __( 'Vitrine 3', 'sao-geronimo-painel' ),
								'sobre'       => __( 'Sobre nós', 'sao-geronimo-painel' ),
								'depoimentos' => __( 'Depoimentos', 'sao-geronimo-painel' ),
								'vitrine4'    => __( 'Vitrine 4', 'sao-geronimo-painel' ),
								'vitrine5'    => __( 'Vitrine 5', 'sao-geronimo-painel' ),
								'blog'        => __( 'Últimos posts do blog', 'sao-geronimo-painel' ),
							),
							'padrao' => array( 'hero', 'busca', 'vitrine1', 'categorias', 'vitrine2', 'vitrine3', 'vitrine4', 'vitrine5', 'sobre', 'depoimentos', 'blog' ),
						),
					),
				),
				'banners' => array(
					'titulo' => __( 'Banner rotativo', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_hero_ativo' => array( 'rotulo' => __( 'Mostrar o banner', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'banners' => array(
							'rotulo' => __( 'Banners', 'sao-geronimo-painel' ),
							'tipo'   => 'repetidor',
							'larga'  => true,
							'add'    => __( 'Adicionar banner', 'sao-geronimo-painel' ),
							'dica'   => __( 'Tamanho recomendado: 1600×620 px no computador e 900×1100 px no celular.', 'sao-geronimo-painel' ),
							'campos' => array(
								'img'     => array( 'rotulo' => __( 'Imagem (computador)', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
								'img_mob' => array( 'rotulo' => __( 'Imagem (celular)', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
								'link'    => array( 'rotulo' => __( 'Para onde leva', 'sao-geronimo-painel' ), 'ph' => 'https://…' ),
								'alt'     => array( 'rotulo' => __( 'Descrição da imagem', 'sao-geronimo-painel' ), 'ph' => __( 'para quem não enxerga e para o Google', 'sao-geronimo-painel' ) ),
							),
						),
						'banner_tempo' => array( 'rotulo' => __( 'Troca de banner a cada', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 2000, 'max' => 20000, 'passo' => 500, 'padrao' => 7000, 'sufixo' => 'ms' ),
					),
				),
				'frase' => array(
					'titulo' => __( 'Frase abaixo do banner', 'sao-geronimo-painel' ),
					'campos' => array(
						'hero_frase'    => array( 'rotulo' => __( 'Frase grande', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'padrao' => 'Há mais de 15 anos trazendo para a sua vida a espiritualidade em cada detalhe.', 'larga' => true ),
						'hero_sub'      => array( 'rotulo' => __( 'Frase menor', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'padrao' => 'Sua fé, sua energia, seu caminho. Artigos místicos e religiosos escolhidos com carinho, com entrega para todo o Brasil.', 'larga' => true ),
						'hero_btn1_txt' => array( 'rotulo' => __( 'Botão 1 — texto', 'sao-geronimo-painel' ), 'padrao' => 'Explorar produtos' ),
						'hero_btn1_url' => array( 'rotulo' => __( 'Botão 1 — link', 'sao-geronimo-painel' ), 'html' => 'url' ),
						'hero_btn2_txt' => array( 'rotulo' => __( 'Botão 2 — texto', 'sao-geronimo-painel' ), 'padrao' => 'Categorias' ),
						'hero_btn2_url' => array( 'rotulo' => __( 'Botão 2 — link', 'sao-geronimo-painel' ), 'html' => 'url' ),
						'estat1_n' => array( 'rotulo' => __( 'Número 1', 'sao-geronimo-painel' ), 'padrao' => '+15' ),
						'estat1_t' => array( 'rotulo' => __( 'Número 1 — legenda', 'sao-geronimo-painel' ), 'padrao' => 'anos de tradição' ),
						'estat2_n' => array( 'rotulo' => __( 'Número 2', 'sao-geronimo-painel' ), 'padrao' => '4.9' ),
						'estat2_t' => array( 'rotulo' => __( 'Número 2 — legenda', 'sao-geronimo-painel' ), 'padrao' => 'estrelas em avaliações' ),
						'estat3_n' => array( 'rotulo' => __( 'Número 3 (opcional)', 'sao-geronimo-painel' ), 'padrao' => '' ),
						'estat3_t' => array( 'rotulo' => __( 'Número 3 — legenda', 'sao-geronimo-painel' ), 'padrao' => '' ),
					),
				),
				'vitrine1' => array( 'titulo' => __( 'Vitrine 1', 'sao-geronimo-painel' ), 'campos' => $vitrine( 1, 'Novidades', 'recentes', 'O que acabou de chegar à loja.' ) ),
				'categorias' => array(
					'titulo' => __( 'Carrossel de categorias', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_categorias_ativo'  => array( 'rotulo' => __( 'Mostrar o carrossel', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'home_categorias_titulo' => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => 'Categorias em destaque' ),
						'home_categorias_sub'    => array( 'rotulo' => __( 'Linha de apoio', 'sao-geronimo-painel' ), 'padrao' => 'Encontre o que você procura por tema.' ),
						'home_categorias_lista'  => array( 'rotulo' => __( 'Quais categorias', 'sao-geronimo-painel' ), 'tipo' => 'termos', 'taxonomia' => 'product_cat', 'larga' => true, 'dica' => __( 'Nenhuma marcada = mostramos as que mais têm produtos. O ícone de cada uma se escolhe em Produtos → Categorias.', 'sao-geronimo-painel' ) ),
						'home_categorias_qtd'    => array( 'rotulo' => __( 'Quantas (quando não escolhe nenhuma)', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 4, 'max' => 24, 'padrao' => 14 ),
					),
				),
				'vitrine2' => array( 'titulo' => __( 'Vitrine 2', 'sao-geronimo-painel' ), 'campos' => $vitrine( 2, 'Mais vendidos', 'vendidos', 'Os preferidos de quem já compra com a gente.' ) ),
				'vitrine3' => array( 'titulo' => __( 'Vitrine 3', 'sao-geronimo-painel' ), 'campos' => $vitrine( 3, 'Coleção Premium', 'destaque', 'Peças de destaque para altares e ambientes especiais.' ) ),
				'sobre' => array(
					'titulo' => __( 'Sobre nós', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_sobre_ativo'      => array( 'rotulo' => __( 'Mostrar o bloco', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'sobre_eyebrow'         => array( 'rotulo' => __( 'Palavrinha de cima', 'sao-geronimo-painel' ), 'padrao' => 'Quem somos' ),
						'sobre_titulo'          => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => 'Sobre nós' ),
						'sobre_img'             => array( 'rotulo' => __( 'Imagem', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
						'sobre_img_alt'         => array( 'rotulo' => __( 'Descrição da imagem', 'sao-geronimo-painel' ), 'padrao' => 'Espiritualidade em cada detalhe' ),
						'sobre_texto'           => array( 'rotulo' => __( 'Texto que aparece na home', 'sao-geronimo-painel' ), 'tipo' => 'editor', 'linhas' => 10, 'larga' => true, 'padrao' => "Há mais de 15 anos, a São Gerônimo faz parte da história de pessoas que encontram na espiritualidade um caminho de fé, força, equilíbrio e conexão.\n\nNascemos com o propósito de oferecer mais do que produtos. Queremos proporcionar uma experiência: a possibilidade de encontrar, em cada detalhe, algo que tenha significado para a sua caminhada espiritual.\n\nSomos movidos pelo respeito às diferentes formas de fé, pela beleza dos símbolos, pela força das tradições e pela crença de que cada pessoa possui uma maneira única de vivenciar sua espiritualidade.\n\nA São Gerônimo une a experiência de uma loja física construída ao longo de anos com a praticidade de uma experiência de compra online. Cada produto é escolhido pensando não apenas em sua beleza, mas também na história, na simbologia e no significado que ele pode carregar." ),
						'sobre_cta'             => array( 'rotulo' => __( 'Texto do botão "leia mais"', 'sao-geronimo-painel' ), 'padrao' => 'LEIA MAIS' ),
						'sobre_completo_titulo' => array( 'rotulo' => __( 'Título da janela', 'sao-geronimo-painel' ), 'padrao' => 'Nossa história' ),
						'sobre_completo'        => array( 'rotulo' => __( 'História completa (abre na janela)', 'sao-geronimo-painel' ), 'tipo' => 'editor', 'linhas' => 16, 'midia' => true, 'larga' => true, 'padrao' => '<p><em>Mais do que uma loja. Um espaço de conexão, significado e espiritualidade.</em></p><p>Há mais de 15 anos, a São Gerônimo faz parte da história de pessoas que encontram na espiritualidade um caminho de fé, força, equilíbrio e conexão.</p><p>Nascemos com o propósito de oferecer mais do que produtos. Queremos proporcionar uma experiência: a possibilidade de encontrar, em cada detalhe, algo que tenha significado para a sua caminhada espiritual.</p><p>Somos movidos pelo respeito às diferentes formas de fé, pela beleza dos símbolos, pela força das tradições e pela crença de que cada pessoa possui uma maneira única de vivenciar sua espiritualidade.</p><h3>O que nos move</h3><p>Nos move a vontade de acolher. Nos move a fé, a espiritualidade e o respeito por diferentes caminhos. Nos move a busca por produtos escolhidos com carinho, qualidade e significado.</p><p>E, principalmente, nos move a confiança de cada pessoa que entra em nossa loja, seja para encontrar um presente, montar seu altar, preparar um ritual, escolher uma guia, descobrir um cristal ou simplesmente buscar algo que faça sentido para o seu momento.</p><h3>Nosso diferencial</h3><p>A São Gerônimo une a experiência de uma loja física construída ao longo de anos com a praticidade de uma experiência de compra online. Cada produto é escolhido pensando não apenas em sua beleza, mas também na história, na simbologia e no significado que ele pode carregar.</p><p>Nossa curadoria reúne artigos místicos e religiosos, elementos de diferentes tradições espirituais, cristais, incensos, velas, guias, miçangas, imagens, oráculos, acessórios e muitos outros itens que fazem parte desse universo.</p><p>Mas acreditamos que nosso maior diferencial está nas pessoas. Está no atendimento próximo. Na escuta. No cuidado com cada escolha. Na vontade de ajudar você a encontrar aquilo que realmente procura.</p><h3>Uma história construída com você</h3><p>Ao longo desses anos, tivemos o privilégio de fazer parte de momentos especiais de muitas pessoas e famílias. Cada cliente que passou pela São Gerônimo deixou um pouco de sua história — e levou consigo um pouco da nossa.</p><p>É essa relação de confiança que queremos preservar e levar para o mundo digital. Porque, para nós, espiritualidade não é apenas aquilo que está em um produto. Está na intenção. Está no significado. Está no cuidado. Está naquilo que sentimos.</p><p><strong>São Gerônimo — espiritualidade em cada detalhe.</strong></p>' ),
					),
				),
				'depoimentos' => array(
					'titulo' => __( 'Depoimentos', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_depoimentos_ativo' => array( 'rotulo' => __( 'Mostrar o bloco', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'depoimentos_titulo'     => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => 'O que dizem de nós' ),
						'depoimentos_sub'        => array( 'rotulo' => __( 'Linha de apoio', 'sao-geronimo-painel' ), 'padrao' => 'Quem comprou, conta.' ),
						'depoimentos' => array(
							'rotulo' => __( 'Depoimentos', 'sao-geronimo-painel' ),
							'tipo'   => 'repetidor',
							'larga'  => true,
							'add'    => __( 'Adicionar depoimento', 'sao-geronimo-painel' ),
							'campos' => array(
								'texto' => array( 'rotulo' => __( 'O que a pessoa disse', 'sao-geronimo-painel' ), 'tipo' => 'textarea' ),
								'nome'  => array( 'rotulo' => __( 'Nome', 'sao-geronimo-painel' ) ),
								'local' => array( 'rotulo' => __( 'Cidade/estado', 'sao-geronimo-painel' ) ),
								'nota'  => array( 'rotulo' => __( 'Estrelas (1 a 5)', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 1, 'max' => 5, 'padrao' => 5 ),
							),
						),
					),
				),
				'vitrine4' => array( 'titulo' => __( 'Vitrine 4', 'sao-geronimo-painel' ), 'campos' => $vitrine( 4, 'Presentes', 'categoria', 'Para presentear com carinho e significado.' ) ),
				'vitrine5' => array( 'titulo' => __( 'Vitrine 5', 'sao-geronimo-painel' ), 'campos' => $vitrine( 5, 'Exclusivos São Gerônimo', 'aleatorio', 'Sua fé, sua energia, seu caminho.' ) ),
				'blog' => array(
					'titulo' => __( 'Blog na home', 'sao-geronimo-painel' ),
					'campos' => array(
						'home_blog_ativo'  => array( 'rotulo' => __( 'Mostrar o bloco', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'home_blog_titulo' => array( 'rotulo' => __( 'Título', 'sao-geronimo-painel' ), 'padrao' => 'Do nosso diário' ),
						'home_blog_sub'    => array( 'rotulo' => __( 'Linha de apoio', 'sao-geronimo-painel' ) ),
						'home_blog_qtd'    => array( 'rotulo' => __( 'Quantos posts', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 1, 'max' => 6, 'padrao' => 3 ),
					),
				),
			),
		),

		/* ===================================================== CONTATO === */
		'contato' => array(
			'titulo' => __( 'Contato e redes', 'sao-geronimo-painel' ),
			'icone'  => 'phone',
			'resumo' => __( 'Onde a loja fica, como falam com você e os links das redes.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'canais' => array(
					'titulo' => __( 'Canais de atendimento', 'sao-geronimo-painel' ),
					'campos' => array(
						'whatsapp'           => array( 'rotulo' => __( 'WhatsApp (só números)', 'sao-geronimo-painel' ), 'padrao' => '5548996397562', 'dica' => __( 'Com 55 e o DDD na frente, sem espaços.', 'sao-geronimo-painel' ) ),
						'whatsapp_exibido'   => array( 'rotulo' => __( 'WhatsApp como aparece', 'sao-geronimo-painel' ), 'padrao' => '(48) 99639-7562' ),
						'whatsapp_flutuante' => array( 'rotulo' => __( 'Botão flutuante de WhatsApp', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'whatsapp_msg'       => array( 'rotulo' => __( 'Mensagem que já vem escrita', 'sao-geronimo-painel' ), 'padrao' => 'Olá! Vim pelo site e gostaria de uma ajuda.', 'larga' => true ),
						'telefone'           => array( 'rotulo' => __( 'Telefone', 'sao-geronimo-painel' ), 'padrao' => '(48) 3286-7876' ),
						'email_contato'      => array( 'rotulo' => __( 'E-mail', 'sao-geronimo-painel' ), 'html' => 'email' ),
						'horario'            => array( 'rotulo' => __( 'Horário de atendimento', 'sao-geronimo-painel' ), 'padrao' => 'Seg a sex, 9h às 18h' ),
						'endereco'           => array( 'rotulo' => __( 'Endereço', 'sao-geronimo-painel' ), 'larga' => true, 'padrao' => 'Rua Edeling Schutz, 73 — Salas 01 e 02 — Centro, Palhoça/SC' ),
					),
				),
				'redes' => array(
					'titulo' => __( 'Redes sociais', 'sao-geronimo-painel' ),
					'campos' => array(
						'url_instagram'    => array( 'rotulo' => 'Instagram', 'html' => 'url', 'padrao' => 'https://www.instagram.com/loja_sao_geronimo/' ),
						'instagram_arroba' => array( 'rotulo' => __( 'Instagram — @', 'sao-geronimo-painel' ), 'padrao' => '@loja_sao_geronimo' ),
						'url_facebook'  => array( 'rotulo' => 'Facebook', 'html' => 'url' ),
						'url_youtube'   => array( 'rotulo' => 'YouTube', 'html' => 'url' ),
						'url_tiktok'    => array( 'rotulo' => 'TikTok', 'html' => 'url' ),
					),
				),
			),
		),

		/* ======================================================== LOJA === */
		'loja' => array(
			'titulo' => __( 'Loja', 'sao-geronimo-painel' ),
			'icone'  => 'cart',
			'resumo' => __( 'Como os produtos aparecem, parcelamento e desconto no Pix.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'grade' => array(
					'titulo' => __( 'Vitrine', 'sao-geronimo-painel' ),
					'campos' => array(
						'loja_colunas'        => array( 'rotulo' => __( 'Produtos por linha', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 2, 'max' => 5, 'padrao' => 4 ),
						'loja_por_pagina'     => array( 'rotulo' => __( 'Produtos por página', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 4, 'max' => 96, 'padrao' => 24 ),
						'texto_botao_comprar' => array( 'rotulo' => __( 'Texto do botão do cartão', 'sao-geronimo-painel' ), 'padrao' => 'Comprar agora' ),
						'selo_novo_dias'      => array( 'rotulo' => __( 'Mostrar selo "Novo" por', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 0, 'max' => 180, 'padrao' => 0, 'sufixo' => __( 'dias', 'sao-geronimo-painel' ), 'dica' => __( 'Zero desliga o selo.', 'sao-geronimo-painel' ) ),
						'mostrar_favoritos'   => array( 'rotulo' => __( 'Ícone de favoritos no topo', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'nao' ),
					),
				),
				'pagamento' => array(
					'titulo' => __( 'Condições de pagamento (texto exibido)', 'sao-geronimo-painel' ),
					'campos' => array(
						'pix_desconto' => array( 'rotulo' => __( 'Desconto no Pix', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 0, 'max' => 30, 'padrao' => 5, 'sufixo' => '%' ),
						'parcelas_max' => array( 'rotulo' => __( 'Parcelas sem juros', 'sao-geronimo-painel' ), 'tipo' => 'numero', 'min' => 1, 'max' => 12, 'padrao' => 10 ),
						'aviso_pagamento' => array( 'rotulo' => '', 'tipo' => 'aviso', 'texto' => '<strong>' . __( 'Atenção:', 'sao-geronimo-painel' ) . '</strong> ' . __( 'estes valores são só o que o cliente lê na vitrine. O desconto que realmente sai na conta é configurado no Mercado Pago, em WooCommerce → Configurações → Pagamentos.', 'sao-geronimo-painel' ) ),
					),
				),
			),
		),

		/* ==================================================== AVANÇADO === */
		'avancado' => array(
			'titulo' => __( 'Avançado', 'sao-geronimo-painel' ),
			'icone'  => 'admin-tools',
			'resumo' => __( 'Mexa aqui só se souber o que está fazendo — ou se alguém da Kong Media pedir.', 'sao-geronimo-painel' ),
			'secoes' => array(
				'conteudo' => array(
					'titulo' => __( 'Blog e páginas', 'sao-geronimo-painel' ),
					'campos' => array(
						'pagina_mostra_capa' => array( 'rotulo' => __( 'Mostrar a imagem destacada nas páginas', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
						'post_mostra_autor'  => array( 'rotulo' => __( 'Mostrar o autor nos posts', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
					),
				),
				'codigo' => array(
					'titulo' => __( 'Código extra', 'sao-geronimo-painel' ),
					'campos' => array(
						'css_extra'      => array( 'rotulo' => __( 'CSS extra', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'linhas' => 10, 'larga' => true, 'dica' => __( 'Entra depois de tudo, então sobrescreve o visual padrão.', 'sao-geronimo-painel' ) ),
						'head_extra'     => array( 'rotulo' => __( 'Código no <head>', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'linhas' => 6, 'larga' => true, 'dica' => __( 'Pixel do Meta, Google Analytics, verificação de domínio.', 'sao-geronimo-painel' ) ),
						'body_extra'     => array( 'rotulo' => __( 'Código no fim da página', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'linhas' => 6, 'larga' => true ),
						'texto_404_titulo' => array( 'rotulo' => __( 'Página não encontrada — título', 'sao-geronimo-painel' ), 'padrao' => 'Esta página não existe mais.' ),
						'texto_404'      => array( 'rotulo' => __( 'Página não encontrada — texto', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'larga' => true, 'padrao' => 'Pode ser que o endereço tenha mudado. Use a busca ou volte para a loja — o que você procura provavelmente está lá.' ),
					),
				),
			),
		),
	);
}

/**
 * Lista plana de todos os campos: chave => definição.
 *
 * @return array
 */
function sgp_campos_planos() {
	static $plano = null;
	if ( null !== $plano ) {
		return $plano;
	}
	$plano = array();
	foreach ( sgp_estrutura() as $aba ) {
		foreach ( $aba['secoes'] as $sec ) {
			foreach ( $sec['campos'] as $chave => $def ) {
				$plano[ $chave ] = $def;
			}
		}
	}
	return $plano;
}

/**
 * Valores padrão de todos os campos.
 *
 * @return array
 */
function sgp_padroes() {
	$out = array();
	foreach ( sgp_campos_planos() as $chave => $def ) {
		if ( isset( $def['padrao'] ) ) {
			$out[ $chave ] = $def['padrao'];
		}
	}
	return $out;
}
