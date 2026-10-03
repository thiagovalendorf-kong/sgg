<?php
/**
 * SEO, GEO e AEO — lado do administrador.
 *
 * SEO: como a página aparece no Google.
 * GEO: onde o negócio fica e até onde atende (busca local e mapas).
 * AEO: perguntas e respostas que assistentes e a IA do buscador leem.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lê uma configuração global de SEO.
 *
 * @param string $chave  Chave.
 * @param mixed  $padrao Padrão.
 * @return mixed
 */
function sgp_seo( $chave, $padrao = '' ) {
	static $o = null;
	if ( null === $o ) {
		$o = get_option( 'sgp_seo', array() );
		if ( ! is_array( $o ) ) {
			$o = array();
		}
	}
	return ( isset( $o[ $chave ] ) && '' !== $o[ $chave ] ) ? $o[ $chave ] : $padrao;
}

/**
 * Campos da tela global de SEO.
 *
 * @return array
 */
function sgp_seo_campos() {
	return array(

		'geral' => array(
			'titulo' => __( 'Como a loja aparece no Google', 'sao-geronimo-painel' ),
			'campos' => array(
				'titulo_home'    => array( 'rotulo' => __( 'Título da página inicial', 'sao-geronimo-painel' ), 'larga' => true, 'dica' => __( 'Até 60 caracteres. É a linha azul clicável no Google.', 'sao-geronimo-painel' ) ),
				'descricao_home' => array( 'rotulo' => __( 'Descrição da página inicial', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'linhas' => 3, 'larga' => true, 'dica' => __( 'Até 155 caracteres. É o textinho cinza embaixo do título.', 'sao-geronimo-painel' ) ),
				'separador'      => array( 'rotulo' => __( 'Separador do título', 'sao-geronimo-painel' ), 'tipo' => 'select', 'padrao' => '—', 'opcoes' => array( '—' => '— (travessão)', '–' => '– (meia-risca)', '-' => '- (hífen)', '|' => '| (barra)', '·' => '· (ponto)' ) ),
				'imagem_padrao'  => array( 'rotulo' => __( 'Imagem de compartilhamento padrão', 'sao-geronimo-painel' ), 'tipo' => 'imagem', 'dica' => __( 'Aparece quando alguém manda o link no WhatsApp ou posta no Facebook. 1200×630 px.', 'sao-geronimo-painel' ) ),
				'twitter'        => array( 'rotulo' => __( 'Perfil no X/Twitter', 'sao-geronimo-painel' ), 'ph' => '@saogeronimo' ),
			),
		),

		'negocio' => array(
			'titulo' => __( 'Dados do negócio (GEO / busca local)', 'sao-geronimo-painel' ),
			'resumo' => __( 'Isto vira os dados estruturados que o Google usa no mapa, no painel da direita e nas buscas "perto de mim".', 'sao-geronimo-painel' ),
			'campos' => array(
				'negocio_tipo'    => array(
					'rotulo' => __( 'Tipo de negócio', 'sao-geronimo-painel' ),
					'tipo'   => 'select',
					'padrao' => 'Store',
					'opcoes' => array(
						'Store'            => __( 'Loja (geral)', 'sao-geronimo-painel' ),
						'HomeGoodsStore'   => __( 'Loja de artigos para casa', 'sao-geronimo-painel' ),
						'GiftShop'         => __( 'Loja de presentes', 'sao-geronimo-painel' ),
						'HealthAndBeautyBusiness' => __( 'Saúde e bem-estar', 'sao-geronimo-painel' ),
						'LocalBusiness'    => __( 'Negócio local (genérico)', 'sao-geronimo-painel' ),
						'OnlineStore'      => __( 'Loja só online', 'sao-geronimo-painel' ),
					),
				),
				'negocio_nome'    => array( 'rotulo' => __( 'Nome do negócio', 'sao-geronimo-painel' ) ),
				'negocio_desc'    => array( 'rotulo' => __( 'Descrição curta', 'sao-geronimo-painel' ), 'tipo' => 'textarea', 'linhas' => 3, 'larga' => true ),
				'negocio_logo'    => array( 'rotulo' => __( 'Logo (quadrado)', 'sao-geronimo-painel' ), 'tipo' => 'imagem' ),
				'negocio_rua'     => array( 'rotulo' => __( 'Rua e número', 'sao-geronimo-painel' ) ),
				'negocio_bairro'  => array( 'rotulo' => __( 'Bairro', 'sao-geronimo-painel' ) ),
				'negocio_cidade'  => array( 'rotulo' => __( 'Cidade', 'sao-geronimo-painel' ) ),
				'negocio_uf'      => array( 'rotulo' => __( 'Estado (sigla)', 'sao-geronimo-painel' ), 'ph' => 'SC' ),
				'negocio_cep'     => array( 'rotulo' => __( 'CEP', 'sao-geronimo-painel' ) ),
				'negocio_pais'    => array( 'rotulo' => __( 'País', 'sao-geronimo-painel' ), 'padrao' => 'BR' ),
				'negocio_lat'     => array( 'rotulo' => __( 'Latitude', 'sao-geronimo-painel' ), 'ph' => '-27.6386', 'dica' => __( 'Abra o Google Maps, clique com o botão direito no endereço da loja e copie os dois números.', 'sao-geronimo-painel' ) ),
				'negocio_lng'     => array( 'rotulo' => __( 'Longitude', 'sao-geronimo-painel' ), 'ph' => '-48.6703' ),
				'negocio_tel'     => array( 'rotulo' => __( 'Telefone', 'sao-geronimo-painel' ) ),
				'negocio_preco'   => array( 'rotulo' => __( 'Faixa de preço', 'sao-geronimo-painel' ), 'tipo' => 'select', 'padrao' => '$$', 'opcoes' => array( '$' => '$ — econômico', '$$' => '$$ — médio', '$$$' => '$$$ — alto' ) ),
				'negocio_atende'  => array( 'rotulo' => __( 'Onde atende', 'sao-geronimo-painel' ), 'larga' => true, 'padrao' => 'Brasil', 'dica' => __( 'Cidades, estados ou "Brasil". Separe por vírgula.', 'sao-geronimo-painel' ) ),
				'negocio_mapa'    => array( 'rotulo' => __( 'Link do perfil no Google Maps', 'sao-geronimo-painel' ), 'html' => 'url', 'larga' => true ),
				'horarios' => array(
					'rotulo' => __( 'Horários de funcionamento', 'sao-geronimo-painel' ),
					'tipo'   => 'repetidor',
					'larga'  => true,
					'add'    => __( 'Adicionar faixa de horário', 'sao-geronimo-painel' ),
					'campos' => array(
						'dias'  => array( 'rotulo' => __( 'Dias', 'sao-geronimo-painel' ), 'ph' => 'Mo,Tu,We,Th,Fr' ),
						'abre'  => array( 'rotulo' => __( 'Abre', 'sao-geronimo-painel' ), 'ph' => '09:00' ),
						'fecha' => array( 'rotulo' => __( 'Fecha', 'sao-geronimo-painel' ), 'ph' => '18:00' ),
					),
				),
			),
		),

		'aeo' => array(
			'titulo' => __( 'AEO — perguntas que a IA responde', 'sao-geronimo-painel' ),
			'resumo' => __( 'Perguntas e respostas que valem para o site todo. Viram dados estruturados do tipo FAQ, que o Google e os assistentes de IA leem para responder sobre a sua loja.', 'sao-geronimo-painel' ),
			'campos' => array(
				'faq' => array(
					'rotulo' => __( 'Perguntas frequentes da loja', 'sao-geronimo-painel' ),
					'tipo'   => 'repetidor',
					'larga'  => true,
					'add'    => __( 'Adicionar pergunta', 'sao-geronimo-painel' ),
					'campos' => array(
						'p' => array( 'rotulo' => __( 'Pergunta', 'sao-geronimo-painel' ), 'ph' => __( 'Vocês entregam para todo o Brasil?', 'sao-geronimo-painel' ) ),
						'r' => array( 'rotulo' => __( 'Resposta', 'sao-geronimo-painel' ), 'tipo' => 'textarea' ),
					),
				),
				'aeo_resumo' => array(
					'rotulo' => __( 'Resumo do negócio em uma frase', 'sao-geronimo-painel' ),
					'tipo'   => 'textarea',
					'linhas' => 3,
					'larga'  => true,
					'dica'   => __( 'Escreva como você explicaria a loja a alguém em dez segundos. É o que a IA tende a repetir.', 'sao-geronimo-painel' ),
				),
			),
		),

		'tecnico' => array(
			'titulo' => __( 'Técnico', 'sao-geronimo-painel' ),
			'campos' => array(
				'sitemap'       => array( 'rotulo' => __( 'Mapa do site', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim', 'ligado' => __( 'Ativado (em /wp-sitemap.xml)', 'sao-geronimo-painel' ) ),
				'noindex_busca' => array( 'rotulo' => __( 'Esconder páginas de busca do Google', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
				'noindex_tags'  => array( 'rotulo' => __( 'Esconder páginas de etiqueta do Google', 'sao-geronimo-painel' ), 'tipo' => 'liga', 'padrao' => 'sim' ),
				'ver_google'    => array( 'rotulo' => __( 'Verificação do Google Search Console', 'sao-geronimo-painel' ), 'larga' => true, 'dica' => __( 'Só o código, sem a tag inteira.', 'sao-geronimo-painel' ) ),
				'ver_bing'      => array( 'rotulo' => __( 'Verificação do Bing', 'sao-geronimo-painel' ), 'larga' => true ),
				'ver_pinterest' => array( 'rotulo' => __( 'Verificação do Pinterest', 'sao-geronimo-painel' ), 'larga' => true ),
			),
		),
	);
}

/**
 * Tela global de SEO.
 */
function sgp_tela_seo() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	$grupos = sgp_seo_campos();

	if ( isset( $_POST['sgp_seo_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_seo_nonce'] ) ), 'sgp_seo' ) ) {
		$novo    = get_option( 'sgp_seo', array() );
		$enviado = isset( $_POST['sg'] ) ? wp_unslash( $_POST['sg'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $grupos as $g ) {
			foreach ( $g['campos'] as $k => $def ) {
				$bruto        = isset( $enviado[ $k ] ) ? $enviado[ $k ] : ( 'liga' === ( isset( $def['tipo'] ) ? $def['tipo'] : '' ) ? 'nao' : '' );
				$novo[ $k ] = sgp_limpa( $def, $bruto );
			}
		}
		update_option( 'sgp_seo', $novo );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'SEO atualizado.', 'sao-geronimo-painel' ) . '</p></div>';
	}

	$atual = get_option( 'sgp_seo', array() );

	echo '<div class="wrap sgp">';
	sgp_cabecalho(
		__( 'SEO, GEO e AEO', 'sao-geronimo-painel' ),
		__( 'Como a loja aparece no Google, onde ela fica no mapa e o que a IA responde sobre ela.', 'sao-geronimo-painel' )
	);

	echo '<div class="sgp-card sgp-card--info"><p>'
		. esc_html__( 'Estas configurações valem para o site inteiro. Cada página e cada produto também tem a própria caixa de SEO, logo abaixo do editor — o que estiver lá manda sobre o que está aqui.', 'sao-geronimo-painel' )
		. '</p></div>';

	echo '<form method="post" class="sgp-form">';
	wp_nonce_field( 'sgp_seo', 'sgp_seo_nonce' );

	foreach ( $grupos as $gid => $g ) {
		echo '<section class="sgp-card" id="seo-' . esc_attr( $gid ) . '"><h2>' . esc_html( $g['titulo'] ) . '</h2>';
		if ( ! empty( $g['resumo'] ) ) {
			echo '<p class="sgp-resumo">' . esc_html( $g['resumo'] ) . '</p>';
		}
		foreach ( $g['campos'] as $k => $def ) {
			$v = isset( $atual[ $k ] ) ? $atual[ $k ] : ( isset( $def['padrao'] ) ? $def['padrao'] : '' );
			sgp_campo( $k, $def, $v );
		}
		echo '</section>';
	}

	echo '<div class="sgp-salvar"><button class="button button-primary button-hero">' . esc_html__( 'Salvar', 'sao-geronimo-painel' ) . '</button></div>';
	echo '</form></div>';
}

/* -------------------------------------------------------------------------
 * Caixa de SEO em cada página, post e produto
 * ---------------------------------------------------------------------- */

/**
 * Onde a caixa aparece.
 *
 * @return array
 */
function sgp_seo_tipos() {
	return apply_filters( 'sgp_seo_tipos', array( 'post', 'page', 'product' ) );
}

/**
 * Registra a caixa.
 */
function sgp_seo_caixa() {
	foreach ( sgp_seo_tipos() as $tipo ) {
		add_meta_box(
			'sgp_seo',
			__( 'SEO, GEO e AEO — como esta página aparece nas buscas', 'sao-geronimo-painel' ),
			'sgp_seo_caixa_html',
			$tipo,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'sgp_seo_caixa' );

/**
 * Conteúdo da caixa.
 *
 * @param WP_Post $post Post.
 */
function sgp_seo_caixa_html( $post ) {
	wp_nonce_field( 'sgp_seo_post', 'sgp_seo_post_nonce' );

	$m = function ( $k, $p = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, '_sgp_' . $k, true );
		return '' !== $v ? $v : $p;
	};

	$faq = get_post_meta( $post->ID, '_sgp_faq', true );
	$faq = is_array( $faq ) ? $faq : array();
	?>
	<div class="sgp sgp-seo-caixa">
		<nav class="sgp-seo-abas">
			<button type="button" class="on" data-seo-aba="busca"><?php esc_html_e( 'Busca', 'sao-geronimo-painel' ); ?></button>
			<button type="button" data-seo-aba="social"><?php esc_html_e( 'Redes sociais', 'sao-geronimo-painel' ); ?></button>
			<button type="button" data-seo-aba="geo"><?php esc_html_e( 'GEO (local)', 'sao-geronimo-painel' ); ?></button>
			<button type="button" data-seo-aba="aeo"><?php esc_html_e( 'AEO (perguntas)', 'sao-geronimo-painel' ); ?></button>
			<button type="button" data-seo-aba="tecnico"><?php esc_html_e( 'Técnico', 'sao-geronimo-painel' ); ?></button>
		</nav>

		<!-- ------------------------------------------------------- BUSCA -->
		<div class="sgp-seo-painel on" data-seo-painel="busca">
			<div class="sgp-previa" data-previa>
				<span class="sgp-previa__url" data-previa-url><?php echo esc_html( str_replace( array( 'https://', 'http://' ), '', home_url( '/' ) ) ); ?></span>
				<span class="sgp-previa__tit" data-previa-tit></span>
				<span class="sgp-previa__desc" data-previa-desc></span>
			</div>

			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Título no Google', 'sao-geronimo-painel' ); ?>
					<em data-conta="titulo" data-ideal="60">0/60</em></span>
				<input type="text" name="sgp[titulo]" value="<?php echo esc_attr( $m( 'titulo' ) ); ?>"
					data-previa-fonte="tit" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
				<small><?php esc_html_e( 'Vazio = usamos o título da página. Ponha a palavra mais importante no começo.', 'sao-geronimo-painel' ); ?></small>
			</label>

			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Descrição no Google', 'sao-geronimo-painel' ); ?>
					<em data-conta="descricao" data-ideal="155">0/155</em></span>
				<textarea name="sgp[descricao]" rows="3" data-previa-fonte="desc"><?php echo esc_textarea( $m( 'descricao' ) ); ?></textarea>
				<small><?php esc_html_e( 'Escreva como um convite, não como um resumo. É ela que faz a pessoa clicar.', 'sao-geronimo-painel' ); ?></small>
			</label>

			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Palavra-chave principal', 'sao-geronimo-painel' ); ?></span>
				<input type="text" name="sgp[chave]" value="<?php echo esc_attr( $m( 'chave' ) ); ?>" data-chave
					placeholder="<?php esc_attr_e( 'ex.: incenso de palo santo', 'sao-geronimo-painel' ); ?>">
			</label>

			<div class="sgp-analise" data-analise>
				<p class="sgp-dica"><?php esc_html_e( 'Preencha a palavra-chave para ver a análise.', 'sao-geronimo-painel' ); ?></p>
			</div>
		</div>

		<!-- ------------------------------------------------------ SOCIAL -->
		<div class="sgp-seo-painel" data-seo-painel="social">
			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Título ao compartilhar', 'sao-geronimo-painel' ); ?></span>
				<input type="text" name="sgp[og_titulo]" value="<?php echo esc_attr( $m( 'og_titulo' ) ); ?>">
			</label>
			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Descrição ao compartilhar', 'sao-geronimo-painel' ); ?></span>
				<textarea name="sgp[og_descricao]" rows="2"><?php echo esc_textarea( $m( 'og_descricao' ) ); ?></textarea>
			</label>
			<div class="sgp-seo-campo">
				<span><?php esc_html_e( 'Imagem ao compartilhar', 'sao-geronimo-painel' ); ?></span>
				<div class="sgp-img<?php echo $m( 'og_imagem' ) ? ' tem' : ''; ?>" data-img>
					<div class="sgp-img__visor">
						<?php if ( $m( 'og_imagem' ) ) : ?>
							<img src="<?php echo esc_url( $m( 'og_imagem' ) ); ?>" alt="">
						<?php else : ?>
							<span><?php esc_html_e( 'Usa a imagem destacada', 'sao-geronimo-painel' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="sgp-img__acoes">
						<button type="button" class="button" data-img-escolher><?php esc_html_e( 'Escolher', 'sao-geronimo-painel' ); ?></button>
						<button type="button" class="button-link sgp-remover" data-img-tirar><?php esc_html_e( 'remover', 'sao-geronimo-painel' ); ?></button>
					</div>
					<input type="hidden" name="sgp[og_imagem]" value="<?php echo esc_url( $m( 'og_imagem' ) ); ?>" data-img-valor>
				</div>
			</div>
		</div>

		<!-- --------------------------------------------------------- GEO -->
		<div class="sgp-seo-painel" data-seo-painel="geo">
			<p class="sgp-dica"><?php esc_html_e( 'Preencha só quando esta página falar de um lugar específico — uma loja física, uma cidade atendida, um evento. Sem isso, vale o endereço geral da loja.', 'sao-geronimo-painel' ); ?></p>
			<div class="sgp-seo-grade">
				<label class="sgp-seo-campo"><span><?php esc_html_e( 'Cidade', 'sao-geronimo-painel' ); ?></span>
					<input type="text" name="sgp[geo_cidade]" value="<?php echo esc_attr( $m( 'geo_cidade' ) ); ?>"></label>
				<label class="sgp-seo-campo"><span><?php esc_html_e( 'Estado (sigla)', 'sao-geronimo-painel' ); ?></span>
					<input type="text" name="sgp[geo_uf]" value="<?php echo esc_attr( $m( 'geo_uf' ) ); ?>"></label>
				<label class="sgp-seo-campo"><span><?php esc_html_e( 'Latitude', 'sao-geronimo-painel' ); ?></span>
					<input type="text" name="sgp[geo_lat]" value="<?php echo esc_attr( $m( 'geo_lat' ) ); ?>"></label>
				<label class="sgp-seo-campo"><span><?php esc_html_e( 'Longitude', 'sao-geronimo-painel' ); ?></span>
					<input type="text" name="sgp[geo_lng]" value="<?php echo esc_attr( $m( 'geo_lng' ) ); ?>"></label>
			</div>
			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Região atendida por esta página', 'sao-geronimo-painel' ); ?></span>
				<input type="text" name="sgp[geo_area]" value="<?php echo esc_attr( $m( 'geo_area' ) ); ?>"
					placeholder="<?php esc_attr_e( 'ex.: Grande Florianópolis, Santa Catarina', 'sao-geronimo-painel' ); ?>">
			</label>
		</div>

		<!-- --------------------------------------------------------- AEO -->
		<div class="sgp-seo-painel" data-seo-painel="aeo">
			<p class="sgp-dica"><?php esc_html_e( 'Perguntas e respostas desta página. Escreva a resposta completa na primeira frase — é o pedaço que a IA costuma citar.', 'sao-geronimo-painel' ); ?></p>

			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Resposta curta sobre esta página', 'sao-geronimo-painel' ); ?></span>
				<textarea name="sgp[aeo_resumo]" rows="2" placeholder="<?php esc_attr_e( 'Uma ou duas frases que respondem sozinhas ao que a página promete.', 'sao-geronimo-painel' ); ?>"><?php echo esc_textarea( $m( 'aeo_resumo' ) ); ?></textarea>
			</label>

			<div class="sgp-rep" data-rep data-chave="sgp_faq">
				<div class="sgp-rep__lista" data-rep-lista>
					<?php foreach ( array_values( $faq ) as $i => $linha ) : ?>
						<?php echo sgp_faq_linha( $i, $linha ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary" data-rep-add>+ <?php esc_html_e( 'Adicionar pergunta', 'sao-geronimo-painel' ); ?></button>
				<script type="text/template" data-rep-modelo><?php echo sgp_faq_linha( '__i__', array() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></script>
			</div>
		</div>

		<!-- ----------------------------------------------------- TÉCNICO -->
		<div class="sgp-seo-painel" data-seo-painel="tecnico">
			<div class="sgp-seo-grade">
				<label class="sgp-seo-campo">
					<span><?php esc_html_e( 'Aparecer no Google?', 'sao-geronimo-painel' ); ?></span>
					<select name="sgp[robots_index]">
						<option value="" <?php selected( $m( 'robots_index' ), '' ); ?>><?php esc_html_e( 'Sim (padrão)', 'sao-geronimo-painel' ); ?></option>
						<option value="noindex" <?php selected( $m( 'robots_index' ), 'noindex' ); ?>><?php esc_html_e( 'Não — esconder esta página', 'sao-geronimo-painel' ); ?></option>
					</select>
				</label>
				<label class="sgp-seo-campo">
					<span><?php esc_html_e( 'Seguir os links desta página?', 'sao-geronimo-painel' ); ?></span>
					<select name="sgp[robots_follow]">
						<option value="" <?php selected( $m( 'robots_follow' ), '' ); ?>><?php esc_html_e( 'Sim (padrão)', 'sao-geronimo-painel' ); ?></option>
						<option value="nofollow" <?php selected( $m( 'robots_follow' ), 'nofollow' ); ?>><?php esc_html_e( 'Não', 'sao-geronimo-painel' ); ?></option>
					</select>
				</label>
			</div>
			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Endereço canônico', 'sao-geronimo-painel' ); ?></span>
				<input type="url" name="sgp[canonical]" value="<?php echo esc_attr( $m( 'canonical' ) ); ?>" placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>">
				<small><?php esc_html_e( 'Use quando este conteúdo for cópia de outra página. Em dúvida, deixe vazio.', 'sao-geronimo-painel' ); ?></small>
			</label>
			<label class="sgp-seo-campo">
				<span><?php esc_html_e( 'Nome nas migalhas de pão', 'sao-geronimo-painel' ); ?></span>
				<input type="text" name="sgp[migalha]" value="<?php echo esc_attr( $m( 'migalha' ) ); ?>">
			</label>
		</div>
	</div>
	<?php
}

/**
 * Uma linha de pergunta/resposta.
 *
 * @param int|string $i     Índice.
 * @param array      $linha Valores.
 * @return string
 */
function sgp_faq_linha( $i, $linha ) {
	$p = isset( $linha['p'] ) ? $linha['p'] : '';
	$r = isset( $linha['r'] ) ? $linha['r'] : '';
	ob_start();
	?>
	<div class="sgp-rep__item" data-rep-item>
		<span class="sgp-pega">⠿</span>
		<div class="sgp-rep__campos">
			<label class="sgp-rep__campo"><span><?php esc_html_e( 'Pergunta', 'sao-geronimo-painel' ); ?></span>
				<input type="text" name="sgp_faq[<?php echo esc_attr( $i ); ?>][p]" value="<?php echo esc_attr( $p ); ?>"></label>
			<label class="sgp-rep__campo"><span><?php esc_html_e( 'Resposta', 'sao-geronimo-painel' ); ?></span>
				<textarea name="sgp_faq[<?php echo esc_attr( $i ); ?>][r]" rows="3"><?php echo esc_textarea( $r ); ?></textarea></label>
		</div>
		<button type="button" class="sgp-rep__x" data-rep-tirar aria-label="<?php esc_attr_e( 'Remover', 'sao-geronimo-painel' ); ?>">×</button>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Salva a caixa de SEO.
 *
 * @param int $id ID do post.
 */
function sgp_seo_salva( $id ) {
	if ( ! isset( $_POST['sgp_seo_post_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_seo_post_nonce'] ) ), 'sgp_seo_post' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $id ) ) {
		return;
	}

	$campos = array(
		'titulo'        => 'texto',
		'descricao'     => 'area',
		'chave'         => 'texto',
		'og_titulo'     => 'texto',
		'og_descricao'  => 'area',
		'og_imagem'     => 'url',
		'geo_cidade'    => 'texto',
		'geo_uf'        => 'texto',
		'geo_lat'       => 'texto',
		'geo_lng'       => 'texto',
		'geo_area'      => 'texto',
		'aeo_resumo'    => 'area',
		'robots_index'  => 'texto',
		'robots_follow' => 'texto',
		'canonical'     => 'url',
		'migalha'       => 'texto',
	);

	$enviado = isset( $_POST['sgp'] ) ? wp_unslash( $_POST['sgp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	foreach ( $campos as $k => $tipo ) {
		$v = isset( $enviado[ $k ] ) ? $enviado[ $k ] : '';
		if ( 'area' === $tipo ) {
			$v = sanitize_textarea_field( $v );
		} elseif ( 'url' === $tipo ) {
			$v = esc_url_raw( $v );
		} else {
			$v = sanitize_text_field( $v );
		}
		if ( '' === $v ) {
			delete_post_meta( $id, '_sgp_' . $k );
		} else {
			update_post_meta( $id, '_sgp_' . $k, $v );
		}
	}

	$faq   = isset( $_POST['sgp_faq'] ) ? wp_unslash( $_POST['sgp_faq'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$limpo = array();
	if ( is_array( $faq ) ) {
		foreach ( $faq as $linha ) {
			$p = isset( $linha['p'] ) ? sanitize_text_field( $linha['p'] ) : '';
			$r = isset( $linha['r'] ) ? sanitize_textarea_field( $linha['r'] ) : '';
			if ( $p && $r ) {
				$limpo[] = array( 'p' => $p, 'r' => $r );
			}
		}
	}
	if ( $limpo ) {
		update_post_meta( $id, '_sgp_faq', $limpo );
	} else {
		delete_post_meta( $id, '_sgp_faq' );
	}
}
add_action( 'save_post', 'sgp_seo_salva' );

/**
 * Coluna "SEO" nas listagens, para bater o olho no que falta.
 *
 * @param array $cols Colunas.
 * @return array
 */
function sgp_seo_coluna( $cols ) {
	$cols['sgp_seo'] = __( 'SEO', 'sao-geronimo-painel' );
	return $cols;
}

/**
 * Conteúdo da coluna.
 *
 * @param string $col Coluna.
 * @param int    $id  ID.
 */
function sgp_seo_coluna_html( $col, $id ) {
	if ( 'sgp_seo' !== $col ) {
		return;
	}
	$t = get_post_meta( $id, '_sgp_titulo', true );
	$d = get_post_meta( $id, '_sgp_descricao', true );

	if ( $t && $d ) {
		echo '<span class="sgp-bolinha sgp-bolinha--ok" title="' . esc_attr__( 'Título e descrição preenchidos', 'sao-geronimo-painel' ) . '"></span>';
	} elseif ( $t || $d ) {
		echo '<span class="sgp-bolinha sgp-bolinha--meio" title="' . esc_attr__( 'Falta título ou descrição', 'sao-geronimo-painel' ) . '"></span>';
	} else {
		echo '<span class="sgp-bolinha sgp-bolinha--vazio" title="' . esc_attr__( 'SEO não preenchido', 'sao-geronimo-painel' ) . '"></span>';
	}
}

foreach ( array( 'post', 'page', 'product' ) as $sgp_t ) {
	add_filter( "manage_{$sgp_t}_posts_columns", 'sgp_seo_coluna' );
	add_action( "manage_{$sgp_t}_posts_custom_column", 'sgp_seo_coluna_html', 10, 2 );
}
