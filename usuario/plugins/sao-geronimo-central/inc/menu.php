<?php
/**
 * Menu "Gestão" organizado em seções, página-hub e página inicial do painel.
 *
 * O WordPress tem dezenas de menus. Aqui ficam só os que se usa todo dia; todo
 * o resto vai para dentro de "Gestão", agrupado em seções (Vendas, Catálogo,
 * Conteúdo, Pagamento e frete, Sistema). Nada fica inacessível: o que não for
 * reconhecido cai em "Sistema".
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menus que ficam à vista no topo (o resto vai para dentro de "Gestão").
 *
 * @return array
 */
function sgc_menus_fixos() {
	return array( 'index.php', 'sgc-central', 'sgc-loja', 'edit.php', 'themes.php', 'options-general.php' );
}

/**
 * Endereço de uma entrada de menu.
 *
 * @param string $slug Slug do menu.
 * @return string
 */
function sgc_url_menu( $slug ) {
	if ( preg_match( '/\.php($|\?)/', $slug ) ) {
		return admin_url( $slug );
	}
	return admin_url( 'admin.php?page=' . $slug );
}

/**
 * Título limpo (sem contadores e tags).
 *
 * @param string $t Título do menu.
 * @return string
 */
function sgc_titulo_limpo( $t ) {
	return trim( wp_strip_all_tags( preg_replace( '#<span.*?</span>#is', '', (string) $t ) ) );
}

/**
 * Endereço da lista de pedidos (muda conforme a versão do WooCommerce).
 *
 * @return string
 */
function sgc_url_pedidos() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	return admin_url( 'edit.php?post_type=shop_order' );
}

/* -------------------------------------------------------------------------
 * Seções do menu "Gestão"
 * ---------------------------------------------------------------------- */

/**
 * Seções, na ordem em que aparecem.
 *
 * @return array slug => título
 */
function sgc_secoes_titulos() {
	return array(
		'vendas'    => 'Vendas',
		'catalogo'  => 'Catálogo',
		'conteudo'  => 'Conteúdo',
		'pagamento' => 'Pagamento e frete',
		'sistema'   => 'Sistema',
	);
}

/**
 * Dados de cada item conhecido: título, ícone, ajuda e ordem.
 *
 * @return array chave => dados
 */
function sgc_itens_conhecidos() {
	return array(
		'pedidos'     => array( 'Pedidos', 'dashicons-cart', 'Veja, separe e envie os pedidos dos clientes.' ),
		'clientes'    => array( 'Clientes', 'dashicons-groups', 'Quem já comprou na loja.' ),
		'cupons'      => array( 'Cupons', 'dashicons-tickets-alt', 'Códigos de desconto para campanhas.' ),
		'relatorios'  => array( 'Relatórios', 'dashicons-chart-bar', 'Quanto você vendeu e o que mais sai.' ),
		'produtos'    => array( 'Todos os produtos', 'dashicons-products', 'Lista, edição rápida, preço e estoque.' ),
		'novo'        => array( 'Adicionar produto', 'dashicons-plus-alt', 'Formulário completo do WooCommerce: variações, estoque e frete.' ),
		'categorias'  => array( 'Categorias', 'dashicons-category', 'Os grupos em que a loja se divide.' ),
		'etiquetas'   => array( 'Linhas e etiquetas', 'dashicons-tag', 'Agrupe produtos por linha ou tema.' ),
		'atributos'   => array( 'Atributos', 'dashicons-editor-ul', 'Cor, tamanho e outras opções de variação.' ),
		'catalogo'    => array( 'Atualizar pelo catálogo', 'dashicons-update', 'Atualize vários produtos de uma vez.' ),
		'produto-facil' => array( 'Cadastro rápido de produto', 'dashicons-edit', 'Formulário curto, só com o essencial, para colocar um produto à venda.' ),
		'importar-produtos' => array( 'Importar produtos', 'dashicons-upload', 'Traga os produtos do catálogo, com fotos, preços e descrições.' ),
		'frete-entrega' => array( 'Frete e entrega', 'dashicons-car', 'Correios, Melhor Envio e suas transportadoras.' ),
		'pagamentos-sg' => array( 'Pagamentos (Mercado Pago)', 'dashicons-money-alt', 'Pix, cartão e boleto.' ),
		'seo'         => array( 'SEO, GEO e AEO', 'dashicons-search', 'Como o Google e as IAs enxergam cada página.' ),
		'controladoria' => array( 'Controladoria', 'dashicons-chart-area', 'Clientes, compras e resultados num só lugar.' ),
		'assistente'  => array( 'Primeiros passos', 'dashicons-flag', 'Passo a passo para deixar a loja pronta.' ),
		'painel-sg'   => array( 'Painel São Gerônimo', 'dashicons-store', 'Visão geral do painel da loja.' ),
		'paginas'     => array( 'Páginas', 'dashicons-admin-page', 'Contato, trocas e devoluções, políticas…' ),
		'midia'       => array( 'Mídia', 'dashicons-admin-media', 'Todas as fotos e arquivos enviados ao site.' ),
		'comentarios' => array( 'Comentários', 'dashicons-admin-comments', 'Respostas e avaliações dos visitantes.' ),
		'integracoes' => array( 'Pagamento e frete', 'dashicons-money-alt', 'Mercado Pago, Correios e transportadoras.' ),
		'config-loja' => array( 'Configurações da loja', 'dashicons-admin-generic', 'Moeda, entrega, impostos e e-mails da loja.' ),
		'pagamentos'  => array( 'Pagamentos (WooCommerce)', 'dashicons-money-alt', 'Formas de pagamento da loja.' ),
		'usuarios'    => array( 'Usuários', 'dashicons-admin-users', 'Quem pode entrar no painel.' ),
		'plugins'     => array( 'Plugins', 'dashicons-admin-plugins', 'Recursos extras instalados no site.' ),
		'ferramentas' => array( 'Ferramentas', 'dashicons-admin-tools', 'Importar, exportar e saúde do site.' ),
		'status'      => array( 'Status da loja', 'dashicons-heart', 'Verificações técnicas do WooCommerce.' ),
	);
}

/**
 * Regras que reconhecem uma entrada de menu pelo slug (nunca pelo título,
 * que muda com o idioma).
 *
 * Cada regra: regex, seção, chave, chave do item-pai (se for sub-link) e título
 * fixo do sub-link. A seção 'ignorar' descarta a entrada.
 *
 * @return array
 */
function sgc_regras_menu() {
	return array(
		array( '#^sg-produto-facil$#', 'catalogo', 'produto-facil', '', '' ),
		array( '#^sg-importar$#', 'catalogo', 'importar-produtos', '', '' ),
		array( '#^sg-frete$#', 'pagamento', 'frete-entrega', '', '' ),
		array( '#^sg-pagamentos$#', 'pagamento', 'pagamentos-sg', '', '' ),
		array( '#^sg-seo$#', 'conteudo', 'seo', '', '' ),
		array( '#^sg-controladoria$#', 'vendas', 'controladoria', '', '' ),
		array( '#^sg-assistente$#', 'sistema', 'assistente', '', '' ),
		array( '#^sg-painel$#', 'sistema', 'painel-sg', '', '' ),
		array( '#^sg-painel-#', 'sistema', 'painel-sg-aba', 'painel-sg', '' ),
		array( '#^wc-admin$#', 'ignorar', '', '', '' ),
		array( '#^wc-admin&path=/(extensions|marketing|my-subscriptions)#', 'ignorar', '', '', '' ),
		array( '#^(woocommerce-marketing|woocommerce_extensions)$#', 'ignorar', '', '', '' ),
		array( '#^(wc-orders|edit\.php\?post_type=shop_order)#', 'vendas', 'pedidos', '', '' ),
		array( '#^wc-admin&path=/customers#', 'vendas', 'clientes', '', '' ),
		array( '#^edit\.php\?post_type=shop_coupon#', 'vendas', 'cupons', '', '' ),
		array( '#^wc-admin&path=/analytics/overview#', 'vendas', 'relatorios', '', '' ),
		array( '#^(wc-admin&path=/analytics|wc-reports)#', 'vendas', 'relatorios-extra', 'relatorios', '' ),
		array( '#^edit\.php\?post_type=product$#', 'catalogo', 'produtos', '', '' ),
		array( '#^post-new\.php\?post_type=product#', 'catalogo', 'novo', '', '' ),
		array( '#taxonomy=product_cat#', 'catalogo', 'categorias', '', '' ),
		array( '#taxonomy=product_tag#', 'catalogo', 'etiquetas', '', '' ),
		array( '#product_attributes#', 'catalogo', 'atributos', '', '' ),
		array( '#^edit-tags\.php\?taxonomy=[^&]+&post_type=product#', 'catalogo', 'taxonomia-extra', '', '' ),
		array( '#^edit\.php\?post_type=page$#', 'conteudo', 'paginas', '', '' ),
		array( '#^post-new\.php\?post_type=page#', 'conteudo', 'paginas-nova', 'paginas', 'Adicionar página' ),
		array( '#^upload\.php#', 'conteudo', 'midia', '', '' ),
		array( '#^media-new\.php#', 'conteudo', 'midia-nova', 'midia', 'Enviar arquivos' ),
		array( '#^edit-comments\.php#', 'conteudo', 'comentarios', '', '' ),
		array( '#^wc-settings#', 'pagamento', 'config-loja', '', '' ),
		array( '#^wc-admin&path=/payments#', 'pagamento', 'pagamentos', '', '' ),
		array( '#^users\.php#', 'sistema', 'usuarios', '', '' ),
		array( '#^user-new\.php#', 'sistema', 'usuarios-novo', 'usuarios', 'Adicionar usuário' ),
		array( '#^profile\.php#', 'sistema', 'perfil', 'usuarios', 'Meu perfil' ),
		array( '#^plugins\.php#', 'sistema', 'plugins', '', '' ),
		array( '#^plugin-install\.php#', 'sistema', 'plugins-novo', 'plugins', 'Adicionar plugin' ),
		array( '#^plugin-editor\.php#', 'sistema', 'plugins-editor', 'plugins', 'Editor de arquivos' ),
		array( '#^tools\.php#', 'sistema', 'ferramentas', '', '' ),
		array( '#^import\.php#', 'sistema', 'importar', 'ferramentas', 'Importar' ),
		array( '#^export\.php#', 'sistema', 'exportar', 'ferramentas', 'Exportar' ),
		array( '#^site-health\.php#', 'sistema', 'saude', 'ferramentas', 'Saúde do site' ),
		array( '#^export-personal-data\.php#', 'sistema', 'privacidade-exportar', 'ferramentas', 'Exportar dados pessoais' ),
		array( '#^erase-personal-data\.php#', 'sistema', 'privacidade-apagar', 'ferramentas', 'Apagar dados pessoais' ),
		array( '#^wc-status#', 'sistema', 'status', '', '' ),
	);
}

/**
 * Normaliza o slug de um submenu para comparar.
 *
 * @param string $slug Slug.
 * @return string
 */
function sgc_slug_norm( $slug ) {
	$s = html_entity_decode( (string) $slug, ENT_QUOTES );
	return preg_replace( '#^admin\.php\?page=#', '', $s );
}

/**
 * Reconhece uma entrada.
 *
 * @param string $slug Slug.
 * @return array|null regra encontrada [seção, chave, pai, título fixo]
 */
function sgc_reconhecer( $slug ) {
	$n = sgc_slug_norm( $slug );
	foreach ( sgc_regras_menu() as $r ) {
		if ( preg_match( $r[0], $n ) ) {
			return array( $r[1], $r[2], $r[3], $r[4] );
		}
	}
	return null;
}

/**
 * Slug como o menu lateral precisa (com .php, para o link funcionar).
 *
 * @param string $slug Slug original.
 * @return string
 */
function sgc_slug_link( $slug ) {
	$s = html_entity_decode( (string) $slug, ENT_QUOTES );
	return preg_match( '/\.php($|\?)/', $s ) ? $s : 'admin.php?page=' . $s;
}

/**
 * A tela de "Atualizar pelo catálogo" foi registrada por outro módulo?
 *
 * @return bool
 */
function sgc_tem_catalogo() {
	foreach ( array_keys( (array) ( $GLOBALS['_registered_pages'] ?? array() ) ) as $k ) {
		if ( '_sgc-catalogo' === substr( $k, -13 ) || 'sgc-catalogo' === $k ) {
			return true;
		}
	}
	return false;
}

/**
 * Para itens de plugins que não têm regra: escolhe a seção pelo nome.
 *
 * @param string $titulo Título do item.
 * @return string Seção (vendas, catalogo, pagamento, sistema).
 */
function sgc_secao_por_nome( $titulo ) {
	$t = sgc_normaliza_titulo( $titulo );
	if ( preg_match( '/pedido|cliente|cupom|cupon|relatorio|venda|assinatura|reembolso/', $t ) ) {
		return 'vendas';
	}
	if ( preg_match( '/pagament|mercado|checkout|frete|entrega|envio|imposto|taxa|transport|correios/', $t ) ) {
		return 'pagamento';
	}
	if ( preg_match( '/produto|estoque|atributo|\bmarcas\b|categoria|etiqueta|avaliac|variac|download/', $t ) ) {
		return 'catalogo';
	}
	return 'sistema';
}

/**
 * Título sem acento, emoji e pontuação, para comparar.
 *
 * @param string $t Título.
 * @return string
 */
function sgc_normaliza_titulo( $t ) {
	$t = remove_accents( wp_strip_all_tags( (string) $t ) );
	$t = strtolower( preg_replace( '/[^A-Za-z0-9 ]+/', ' ', $t ) );
	return trim( preg_replace( '/\s+/', ' ', $t ) );
}

/**
 * Monta as seções a partir do que existe nos menus do WordPress.
 *
 * @return array seção => lista de itens
 */
function sgc_montar_secoes() {
	global $menu, $submenu;

	$conhecidos = sgc_itens_conhecidos();
	$itens      = array();   // chave => item
	$filhos     = array();   // sub-links soltos
	$extras     = array();   // itens sem regra (vão para Sistema)
	$grupos     = array();   // menus de topo recolhidos
	$manter     = array();   // menus de plugins desconhecidos, que não são recolhidos

	foreach ( (array) $menu as $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( ! $slug || false !== strpos( (string) ( $item[4] ?? '' ), 'wp-menu-separator' ) || in_array( $slug, sgc_menus_fixos(), true ) ) {
			continue;
		}
		$titulo_topo = sgc_titulo_limpo( $item[0] );
		if ( '' === $titulo_topo ) {
			continue;
		}
		$grupos[ $slug ] = true;

		$entradas = array();
		foreach ( (array) ( $submenu[ $slug ] ?? array() ) as $sub ) {
			$t = sgc_titulo_limpo( $sub[0] );
			if ( '' !== $t ) {
				$entradas[] = array( 'titulo' => $t, 'slug' => $sub[2], 'cap' => $sub[1] );
			}
		}
		if ( ! $entradas ) {
			$entradas[] = array( 'titulo' => $titulo_topo, 'slug' => $slug, 'cap' => $item[1] );
		}

		$reconhecidas = 0;
		$pendentes    = array();
		foreach ( $entradas as $e ) {
			$r = sgc_reconhecer( $e['slug'] );
			if ( ! $r ) {
				$pendentes[] = $e;
				continue;
			}
			++$reconhecidas;
			list( $secao, $chave, $pai, $fixo ) = $r;
			if ( 'ignorar' === $secao ) {
				continue;
			}
			$e['link'] = sgc_slug_link( $e['slug'] );
			if ( $pai ) {
				$e['titulo'] = $fixo ? $fixo : $e['titulo'];
				$filhos[]    = $e + array( 'pai' => $pai, 'secao' => $secao );
				continue;
			}
			if ( isset( $itens[ $chave ] ) ) {
				continue;
			}
			$e['secao']  = $secao;
			$e['chave']  = $chave;
			$e['titulo'] = isset( $conhecidos[ $chave ] ) ? $conhecidos[ $chave ][0] : $e['titulo'];
			$e['filhos'] = array();
			$itens[ $chave === 'taxonomia-extra' ? 'tx-' . sgc_slug_norm( $e['slug'] ) : $chave ] = $e;
		}

		if ( 0 === $reconhecidas ) {
			// Menu de plugin desconhecido: fica onde está (recolher quebra o link da página de topo).
			$manter[] = $slug;
			unset( $grupos[ $slug ] );
		} else {
			foreach ( $pendentes as $e ) {
				$e['link']   = sgc_slug_link( $e['slug'] );
				$e['secao']  = in_array( $slug, array( 'edit.php?post_type=product' ), true ) ? 'catalogo' : sgc_secao_por_nome( $e['titulo'] );
				$e['chave']  = 'extra-' . sanitize_title( $e['slug'] );
				$e['filhos'] = array();
				$extras[]    = $e;
			}
		}
	}

	// Sub-links ficam dentro do item-pai (ou viram item, se o pai não existe).
	foreach ( $filhos as $f ) {
		if ( isset( $itens[ $f['pai'] ] ) ) {
			$itens[ $f['pai'] ]['filhos'][] = $f;
		} else {
			$f['chave']  = $f['pai'] . '-' . sanitize_title( $f['slug'] );
			$f['filhos'] = array();
			$extras[]    = $f;
		}
	}

	// Entradas fixas que não vêm dos menus nativos.
	$itens['integracoes'] = array(
		'titulo' => $conhecidos['integracoes'][0], 'slug' => 'sgc-integracoes', 'cap' => 'manage_options', 'link' => 'admin.php?page=sgc-integracoes', 'menu' => 'sgc-integracoes',
		'secao' => 'pagamento', 'chave' => 'integracoes', 'filhos' => array(),
	);
	if ( sgc_tem_catalogo() ) {
		$itens['catalogo'] = array(
			'titulo' => $conhecidos['catalogo'][0], 'slug' => 'sgc-catalogo', 'cap' => current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options', 'link' => 'admin.php?page=sgc-catalogo',
			'secao' => 'catalogo', 'chave' => 'catalogo', 'filhos' => array(),
		);
	}

	foreach ( $extras as $e ) {
		$itens[ $e['chave'] ] = $e;
	}

	// Ordem dentro de cada seção.
	$ordem = array(
		'vendas'    => array( 'pedidos', 'clientes', 'cupons', 'relatorios' ),
		'catalogo'  => array( 'produtos', 'novo', 'produto-facil', 'categorias', 'etiquetas', 'atributos', 'importar-produtos', 'catalogo' ),
		'conteudo'  => array( 'paginas', 'midia', 'comentarios' ),
		'pagamento' => array( 'integracoes', 'config-loja', 'pagamentos' ),
		'sistema'   => array( 'usuarios', 'plugins', 'ferramentas' ),
	);
	$secoes = array();
	foreach ( array_keys( sgc_secoes_titulos() ) as $s ) {
		$secoes[ $s ] = array();
		foreach ( $ordem[ $s ] as $chave ) {
			if ( isset( $itens[ $chave ] ) && $itens[ $chave ]['secao'] === $s ) {
				$secoes[ $s ][] = $itens[ $chave ];
				unset( $itens[ $chave ] );
			}
		}
	}
	foreach ( $itens as $it ) {
		$secoes[ isset( $secoes[ $it['secao'] ] ) ? $it['secao'] : 'sistema' ][] = $it;
	}

	// Sem itens repetidos: se dois plugins oferecem o mesmo nome (ex.: "Cupons"), fica o primeiro.
	$vistos = array();
	foreach ( $secoes as $sec => $lista ) {
		$limpa = array();
		foreach ( $lista as $it ) {
			$k = sgc_normaliza_titulo( $it['titulo'] );
			if ( isset( $vistos[ $k ] ) ) {
				continue;
			}
			$vistos[ $k ] = true;
			$limpa[]      = $it;
		}
		$secoes[ $sec ] = $limpa;
	}

	$GLOBALS['sgc_grupos'] = $grupos;
	$GLOBALS['sgc_manter'] = $manter;
	return $secoes;
}

/**
 * Menu "Gestão": o único lugar onde ficam todas as abas nativas do WordPress.
 */
function sgc_menu_loja() {
	add_menu_page( 'Gestão', 'Gestão', 'edit_posts', 'sgc-loja', 'sgc_pagina_loja', 'dashicons-store', 3 );
	add_submenu_page( 'sgc-loja', 'Visão geral', 'Visão geral', 'edit_posts', 'sgc-loja', 'sgc_pagina_loja' );
}
add_action( 'admin_menu', 'sgc_menu_loja' );

/**
 * Leva as abas nativas para dentro de "Gestão", em seções. Roda por último,
 * para pegar também os menus criados por outros plugins.
 */
function sgc_recolher_menus() {
	global $menu, $submenu;

	// Registra a página (a lista final do submenu é montada abaixo).
	add_submenu_page( 'sgc-loja', 'Pagamento e frete', 'Pagamento e frete', 'manage_options', 'sgc-integracoes', 'sgc_pagina_integracoes' );

	$secoes = sgc_montar_secoes();
	$GLOBALS['sgc_secoes'] = $secoes;

	$lista = array( array( 'Visão geral', 'edit_posts', 'sgc-loja', 'Visão geral' ) );
	foreach ( sgc_secoes_titulos() as $sid => $stitulo ) {
		if ( empty( $secoes[ $sid ] ) ) {
			continue;
		}
		// Cabeçalho da seção: texto sem link (o CSS tira o clique).
		$lista[] = array( '<span class="sgc-secao__t">' . esc_html( $stitulo ) . '</span>', 'edit_posts', '#sgc-secao-' . $sid, $stitulo, 'sgc-secao' );
		foreach ( $secoes[ $sid ] as $it ) {
			$lista[] = array( esc_html( $it['titulo'] ), $it['cap'], isset( $it['menu'] ) ? $it['menu'] : $it['link'], $it['titulo'] );
		}
	}
	$submenu['sgc-loja'] = $lista; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

	// Menus de plugin sem nenhuma tela reconhecida ficam no lugar: recolher quebraria o link.
	$manter = isset( $GLOBALS['sgc_manter'] ) ? (array) $GLOBALS['sgc_manter'] : array();
	foreach ( array_keys( (array) $GLOBALS['sgc_grupos'] ) as $slug ) {
		if ( in_array( $slug, $manter, true ) ) {
			unset( $GLOBALS['sgc_grupos'][ $slug ] );
			continue;
		}
		remove_menu_page( $slug );
	}

	// Posts vira "Blog".
	foreach ( (array) $menu as $pos => $item ) {
		if ( isset( $item[2] ) && 'edit.php' === $item[2] ) {
			$menu[ $pos ][0] = 'Blog'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}
add_action( 'admin_menu', 'sgc_recolher_menus', 9999 );

/**
 * Quando a pessoa está numa tela que foi para dentro de "Gestão", o menu
 * "Gestão" fica marcado como o atual.
 *
 * @param string $pai Menu pai atual.
 * @return string
 */
function sgc_menu_atual( $pai ) {
	$grupos = isset( $GLOBALS['sgc_grupos'] ) ? $GLOBALS['sgc_grupos'] : array();
	if ( $pai && isset( $grupos[ $pai ] ) ) {
		return 'sgc-loja';
	}
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_GET['page'] ) && 'sgc-catalogo' === $_GET['page'] ) {
		return 'sgc-loja';
	}
	return $pai;
}
add_filter( 'parent_file', 'sgc_menu_atual', 99 );

/**
 * Marca no submenu o item certo (os slugs dos itens mudaram de forma).
 *
 * @param string $atual Slug que o WordPress marcaria.
 * @return string
 */
function sgc_submenu_atual( $atual ) {
	global $submenu;
	if ( empty( $submenu['sgc-loja'] ) || empty( $GLOBALS['sgc_secoes'] ) ) {
		return $atual;
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$candidatos = array( (string) $atual );
	$pagina     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( $pagina ) {
		$candidatos[] = $pagina;
		if ( isset( $_GET['path'] ) ) {
			$candidatos[] = $pagina . '&path=' . sanitize_text_field( wp_unslash( $_GET['path'] ) );
		}
	}
	// phpcs:enable
	foreach ( $GLOBALS['sgc_secoes'] as $itens ) {
		foreach ( $itens as $it ) {
			$todos = array_merge( array( $it ), $it['filhos'] );
			foreach ( $todos as $x ) {
				foreach ( $candidatos as $c ) {
					if ( '' !== $c && sgc_slug_norm( $c ) === sgc_slug_norm( $x['slug'] ) ) {
						return isset( $it['menu'] ) ? $it['menu'] : $it['link'];
					}
				}
			}
		}
	}
	return $atual;
}
add_filter( 'submenu_file', 'sgc_submenu_atual', 99 );

/* -------------------------------------------------------------------------
 * Página-hub "Gestão"
 * ---------------------------------------------------------------------- */

/**
 * Página "Gestão": faixa de mais usados e as seções em cartões, com busca.
 */
function sgc_pagina_loja() {
	$secoes     = isset( $GLOBALS['sgc_secoes'] ) ? $GLOBALS['sgc_secoes'] : array();
	$conhecidos = sgc_itens_conhecidos();
	$titulos    = sgc_secoes_titulos();

	// Procura um item pela chave, em qualquer seção.
	$achar = function ( $chave ) use ( $secoes ) {
		foreach ( $secoes as $lista ) {
			foreach ( $lista as $it ) {
				if ( $it['chave'] === $chave && current_user_can( $it['cap'] ) ) {
					return $it;
				}
			}
		}
		return null;
	};

	$rapidos = array();
	if ( $achar( 'novo' ) ) {
		$rapidos[] = array( 'Cadastrar produto', 'dashicons-plus-alt', admin_url( 'post-new.php?post_type=product' ) );
	}
	foreach ( array( 'pedidos' => 'Pedidos', 'clientes' => 'Clientes' ) as $k => $rot ) {
		if ( $it = $achar( $k ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$rapidos[] = array( $rot, $conhecidos[ $k ][1], admin_url( $it['link'] ) );
		}
	}
	if ( current_user_can( 'manage_options' ) ) {
		$rapidos[] = array( 'Editar o site', 'dashicons-art', admin_url( 'admin.php?page=sgc-central' ) );
	}
	if ( $achar( 'integracoes' ) ) {
		$rapidos[] = array( 'Pagamento e frete', 'dashicons-money-alt', admin_url( 'admin.php?page=sgc-integracoes' ) );
	}
	?>
	<div class="wrap sgc-loja sgc-hub">
		<h1>Gestão</h1>
		<p class="sgc-loja__sub">Tudo o que você usa para administrar a loja, organizado por assunto.</p>

		<div class="sgc-hub__busca">
			<label class="screen-reader-text" for="sgc-hub-busca">Buscar na Gestão</label>
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<input type="search" id="sgc-hub-busca" placeholder="Buscar: pedidos, produtos, páginas…" autocomplete="off">
		</div>

		<?php if ( $rapidos ) : ?>
			<section class="sgc-hub__secao" data-fixa="1">
				<h2 class="sgc-loja__tit">Mais usados</h2>
				<div class="sgc-rapidos">
					<?php foreach ( $rapidos as $r ) : ?>
						<a class="sgc-rapido" href="<?php echo esc_url( $r[2] ); ?>"><span class="dashicons <?php echo esc_attr( $r[1] ); ?>" aria-hidden="true"></span><b><?php echo esc_html( $r[0] ); ?></b></a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		foreach ( $secoes as $sid => $lista ) :
			$visiveis = array_filter( $lista, function ( $it ) {
				return current_user_can( $it['cap'] );
			} );
			if ( ! $visiveis ) {
				continue;
			}
			?>
			<section class="sgc-hub__secao" id="sgc-sec-<?php echo esc_attr( $sid ); ?>">
				<h2 class="sgc-loja__tit"><?php echo esc_html( $titulos[ $sid ] ); ?></h2>
				<div class="sgc-hubgrade">
					<?php
					foreach ( $visiveis as $it ) :
						$info  = $conhecidos[ $it['chave'] ] ?? array( $it['titulo'], 'dashicons-admin-generic', 'Aberto pelo plugin ' . $it['titulo'] . '.' );
						$subs  = array_filter( $it['filhos'], function ( $f ) {
							return current_user_can( $f['cap'] );
						} );
						$busca = $it['titulo'] . ' ' . $info[2] . ' ' . implode( ' ', wp_list_pluck( $subs, 'titulo' ) );
						?>
						<article class="sgc-hubc" data-busca="<?php echo esc_attr( remove_accents( strtolower( $busca ) ) ); ?>">
							<span class="sgc-hubc__ico dashicons <?php echo esc_attr( $info[1] ); ?>" aria-hidden="true"></span>
							<div class="sgc-hubc__corpo">
								<h3><a href="<?php echo esc_url( admin_url( $it['link'] ) ); ?>"><?php echo esc_html( $it['titulo'] ); ?></a></h3>
								<p><?php echo esc_html( $info[2] ); ?></p>
								<?php if ( $subs ) : ?>
									<div class="sgc-hubc__links">
										<?php if ( 'paginas' === $it['chave'] ) : ?>
											<a href="<?php echo esc_url( admin_url( $it['link'] ) ); ?>">Todas as páginas</a>
										<?php endif; ?>
										<?php foreach ( $subs as $f ) : ?>
											<a href="<?php echo esc_url( admin_url( $f['link'] ) ); ?>"><?php echo esc_html( $f['titulo'] ); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<p class="sgc-hub__vazio" hidden>Nada encontrado. Tente outra palavra, como "pedido" ou "foto".</p>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Página inicial do painel
 * ---------------------------------------------------------------------- */

/**
 * Troca as caixas do "Painel" por um resumo e atalhos (2 colunas, sem caixa vazia).
 */
function sgc_dashboard() {
	global $wp_meta_boxes;
	$wp_meta_boxes['dashboard'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	wp_add_dashboard_widget( 'sgc_atalhos', 'O que você quer fazer hoje?', 'sgc_widget_atalhos' );
	wp_add_dashboard_widget( 'sgc_resumo', 'Resumo da loja', 'sgc_widget_resumo' );

	// Uma caixa em cada coluna: nenhuma coluna fica vazia ("Drag boxes here").
	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['sgc_resumo'] ) ) {
		$wp_meta_boxes['dashboard']['side']['core']['sgc_resumo'] = $wp_meta_boxes['dashboard']['normal']['core']['sgc_resumo']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		unset( $wp_meta_boxes['dashboard']['normal']['core']['sgc_resumo'] );
	}
}
add_action( 'wp_dashboard_setup', 'sgc_dashboard', 9999 );

/**
 * O painel inicial usa sempre 2 colunas.
 *
 * @return int
 */
function sgc_dashboard_colunas() {
	return 2;
}
add_filter( 'get_user_option_screen_layout_dashboard', 'sgc_dashboard_colunas' );

/**
 * Botões grandes de atalho.
 */
function sgc_widget_atalhos() {
	$itens = array(
		array( 'dashicons-art', 'Editar o site', admin_url( 'admin.php?page=sgc-central' ) ),
		array( 'dashicons-plus-alt', 'Cadastrar produto', admin_url( 'post-new.php?post_type=product' ) ),
		array( 'dashicons-cart', 'Ver pedidos', sgc_url_pedidos() ),
		array( 'dashicons-edit', 'Escrever no blog', admin_url( 'post-new.php' ) ),
		array( 'dashicons-admin-site-alt3', 'Abrir o site', home_url( '/' ) ),
		array( 'dashicons-store', 'Gestão da loja', admin_url( 'admin.php?page=sgc-loja' ) ),
	);
	echo '<div class="sgc-atalhos">';
	foreach ( $itens as $i ) {
		printf( '<a href="%s"><span class="dashicons %s" aria-hidden="true"></span>%s</a>', esc_url( $i[2] ), esc_attr( $i[0] ), esc_html( $i[1] ) );
	}
	echo '</div>';
}

/**
 * Números da loja.
 */
function sgc_widget_resumo() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		echo '<p class="sgc-vazio">Os números da loja aparecem para quem administra a loja.</p>';
		return;
	}
	if ( ! post_type_exists( 'product' ) ) {
		echo '<p class="sgc-vazio">Ative o WooCommerce para ver os números da loja.</p>';
		return;
	}
	$produtos = (int) wp_count_posts( 'product' )->publish;
	$aguard   = function_exists( 'wc_orders_count' ) ? (int) wc_orders_count( 'processing' ) : 0;
	$clientes = get_transient( 'sgc_n_clientes' );
	if ( false === $clientes ) {
		$clientes = (int) ( count_users()['avail_roles']['customer'] ?? 0 );
		set_transient( 'sgc_n_clientes', $clientes, 15 * MINUTE_IN_SECONDS );
	}
	echo '<div class="sgc-numeros">';
	printf( '<div><b>%d</b><span>produtos à venda</span></div>', $produtos ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<div><b>%d</b><span>pedidos para enviar</span></div>', $aguard ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<div><b>%d</b><span>clientes</span></div>', $clientes ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
