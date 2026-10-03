<?php
/**
 * SEO, GEO e AEO — o que sai no site.
 *
 * Imprime título, descrição, Open Graph, canônico, robots e os dados
 * estruturados (JSON-LD) que o Google e os assistentes de IA leem.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta do post atual.
 *
 * @param string $k      Chave, sem o prefixo.
 * @param mixed  $padrao Padrão.
 * @return mixed
 */
function sgp_meta( $k, $padrao = '' ) {
	if ( ! is_singular() ) {
		return $padrao;
	}
	$v = get_post_meta( get_queried_object_id(), '_sgp_' . $k, true );
	return ( '' !== $v && null !== $v ) ? $v : $padrao;
}

/**
 * Corta um texto no limite, sem partir palavra.
 *
 * @param string $txt   Texto.
 * @param int    $limite Limite.
 * @return string
 */
function sgp_corta( $txt, $limite ) {
	$txt = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $txt ) ) );
	if ( mb_strlen( $txt ) <= $limite ) {
		return $txt;
	}
	$txt = mb_substr( $txt, 0, $limite );
	$p   = mb_strrpos( $txt, ' ' );
	return rtrim( $p ? mb_substr( $txt, 0, $p ) : $txt, " ,.;:-" ) . '…';
}

/**
 * Título da página.
 *
 * @return string
 */
function sgp_titulo_pagina() {
	$sep  = sgp_seo( 'separador', '—' );
	$nome = get_bloginfo( 'name' );

	if ( is_front_page() ) {
		$t = sgp_seo( 'titulo_home', '' );
		return $t ? $t : $nome . ' ' . $sep . ' ' . get_bloginfo( 'description' );
	}

	$proprio = sgp_meta( 'titulo', '' );
	if ( $proprio ) {
		return $proprio;
	}

	if ( is_singular() ) {
		$base = get_the_title( get_queried_object_id() );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$base = get_the_title( wc_get_page_id( 'shop' ) );
	} elseif ( is_search() ) {
		/* translators: termo buscado */
		$base = sprintf( __( 'Busca por "%s"', 'sao-geronimo-painel' ), get_search_query() );
	} elseif ( is_archive() ) {
		$base = wp_strip_all_tags( get_the_archive_title() );
	} elseif ( is_404() ) {
		$base = __( 'Página não encontrada', 'sao-geronimo-painel' );
	} else {
		$base = $nome;
	}

	return $base . ' ' . $sep . ' ' . $nome;
}

/**
 * Troca o título do WordPress pelo nosso.
 *
 * @param array $partes Partes do título.
 * @return array
 */
function sgp_filtra_titulo( $partes ) {
	return array( 'title' => sgp_titulo_pagina() );
}
add_filter( 'document_title_parts', 'sgp_filtra_titulo', 99 );

/**
 * Remove o separador padrão (já está dentro do nosso título).
 *
 * @return string
 */
function sgp_sem_separador() {
	return '';
}
add_filter( 'document_title_separator', 'sgp_sem_separador', 99 );

/**
 * Descrição da página.
 *
 * @return string
 */
function sgp_descricao_pagina() {
	if ( is_front_page() ) {
		$d = sgp_seo( 'descricao_home', '' );
		return $d ? $d : get_bloginfo( 'description' );
	}

	$p = sgp_meta( 'descricao', '' );
	if ( $p ) {
		return $p;
	}

	if ( is_singular() ) {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		$txt = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		return sgp_corta( strip_shortcodes( $txt ), 155 );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$t = get_queried_object();
		if ( $t && ! empty( $t->description ) ) {
			return sgp_corta( $t->description, 155 );
		}
	}

	return '';
}

/**
 * Imagem de compartilhamento.
 *
 * @return string
 */
function sgp_imagem_social() {
	$i = sgp_meta( 'og_imagem', '' );
	if ( $i ) {
		return $i;
	}
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
		if ( $src ) {
			return $src[0];
		}
	}
	return sgp_seo( 'imagem_padrao', '' );
}

/**
 * Endereço canônico.
 *
 * @return string
 */
function sgp_canonical() {
	$c = sgp_meta( 'canonical', '' );
	if ( $c ) {
		return $c;
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$l = get_term_link( get_queried_object() );
		return is_wp_error( $l ) ? '' : $l;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return get_permalink( wc_get_page_id( 'shop' ) );
	}
	return '';
}

/**
 * Imprime as metatags no <head>.
 */
function sgp_cabeca() {
	$desc = sgp_descricao_pagina();
	$img  = sgp_imagem_social();
	$url  = sgp_canonical();
	$tit  = sgp_titulo_pagina();

	echo "\n<!-- SEO · São Gerônimo -->\n";

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $url ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}

	// robots
	$robots = array();
	if ( 'noindex' === sgp_meta( 'robots_index', '' ) ) {
		$robots[] = 'noindex';
	} elseif ( is_search() && 'sim' === sgp_seo( 'noindex_busca', 'sim' ) ) {
		$robots[] = 'noindex';
	} elseif ( is_tag() && 'sim' === sgp_seo( 'noindex_tags', 'sim' ) ) {
		$robots[] = 'noindex';
	} elseif ( is_404() ) {
		$robots[] = 'noindex';
	} else {
		$robots[] = 'index';
	}
	$robots[] = 'nofollow' === sgp_meta( 'robots_follow', '' ) ? 'nofollow' : 'follow';
	$robots[] = 'max-image-preview:large';
	$robots[] = 'max-snippet:-1';
	echo '<meta name="robots" content="' . esc_attr( implode( ', ', $robots ) ) . '">' . "\n";

	// Open Graph
	$og_t = sgp_meta( 'og_titulo', $tit );
	$og_d = sgp_meta( 'og_descricao', $desc );

	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'product' ) ? 'product' : ( is_singular( 'post' ) ? 'article' : 'website' ) ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $og_t ) . '">' . "\n";
	if ( $og_d ) {
		echo '<meta property="og:description" content="' . esc_attr( $og_d ) . '">' . "\n";
	}
	if ( $url ) {
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	}
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		echo '<meta property="og:image:alt" content="' . esc_attr( $og_t ) . '">' . "\n";
	}

	// Twitter / X
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	if ( sgp_seo( 'twitter' ) ) {
		echo '<meta name="twitter:site" content="' . esc_attr( sgp_seo( 'twitter' ) ) . '">' . "\n";
	}

	// GEO
	$lat = sgp_meta( 'geo_lat', sgp_seo( 'negocio_lat' ) );
	$lng = sgp_meta( 'geo_lng', sgp_seo( 'negocio_lng' ) );
	$cid = sgp_meta( 'geo_cidade', sgp_seo( 'negocio_cidade' ) );
	$uf  = sgp_meta( 'geo_uf', sgp_seo( 'negocio_uf' ) );

	if ( $lat && $lng ) {
		echo '<meta name="geo.position" content="' . esc_attr( $lat . ';' . $lng ) . '">' . "\n";
		echo '<meta name="ICBM" content="' . esc_attr( $lat . ', ' . $lng ) . '">' . "\n";
	}
	if ( $cid ) {
		echo '<meta name="geo.placename" content="' . esc_attr( $cid ) . '">' . "\n";
	}
	if ( $uf ) {
		echo '<meta name="geo.region" content="' . esc_attr( 'BR-' . strtoupper( $uf ) ) . '">' . "\n";
	}

	// verificações
	foreach ( array( 'ver_google' => 'google-site-verification', 'ver_bing' => 'msvalidate.01', 'ver_pinterest' => 'p:domain_verify' ) as $k => $meta ) {
		$v = sgp_seo( $k );
		if ( $v ) {
			echo '<meta name="' . esc_attr( $meta ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}

	sgp_json_ld();
	echo "<!-- /SEO -->\n\n";
}
add_action( 'wp_head', 'sgp_cabeca', 2 );

/**
 * Monta e imprime os dados estruturados.
 */
function sgp_json_ld() {
	$casa  = trailingslashit( home_url( '/' ) );
	$id_org = $casa . '#organizacao';
	$grafo = array();

	/* ---------------------------------------------- organização / negócio */
	$tipo = sgp_seo( 'negocio_tipo', 'Store' );
	$org  = array(
		'@type' => 'OnlineStore' === $tipo ? array( 'Organization', 'OnlineStore' ) : array( 'Organization', $tipo ),
		'@id'   => $id_org,
		'name'  => sgp_seo( 'negocio_nome', get_bloginfo( 'name' ) ),
		'url'   => $casa,
	);

	if ( sgp_seo( 'negocio_desc' ) ) {
		$org['description'] = sgp_seo( 'negocio_desc' );
	}
	if ( sgp_seo( 'negocio_logo' ) ) {
		$org['logo'] = array( '@type' => 'ImageObject', 'url' => sgp_seo( 'negocio_logo' ) );
		$org['image'] = sgp_seo( 'negocio_logo' );
	}
	if ( sgp_seo( 'negocio_tel' ) ) {
		$org['telephone'] = sgp_seo( 'negocio_tel' );
	}
	if ( sgp_seo( 'negocio_preco' ) ) {
		$org['priceRange'] = sgp_seo( 'negocio_preco' );
	}

	$endereco = array_filter( array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => sgp_seo( 'negocio_rua' ),
		'addressLocality' => sgp_seo( 'negocio_cidade' ),
		'addressRegion'   => sgp_seo( 'negocio_uf' ),
		'postalCode'      => sgp_seo( 'negocio_cep' ),
		'addressCountry'  => sgp_seo( 'negocio_pais', 'BR' ),
	) );
	if ( count( $endereco ) > 2 ) {
		$org['address'] = $endereco;
	}

	if ( sgp_seo( 'negocio_lat' ) && sgp_seo( 'negocio_lng' ) ) {
		$org['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) sgp_seo( 'negocio_lat' ),
			'longitude' => (float) sgp_seo( 'negocio_lng' ),
		);
	}

	$areas = array_filter( array_map( 'trim', explode( ',', (string) sgp_seo( 'negocio_atende', 'Brasil' ) ) ) );
	if ( $areas ) {
		$org['areaServed'] = array_map(
			function ( $a ) {
				return array( '@type' => 'Place', 'name' => $a );
			},
			$areas
		);
	}

	$horarios = sgp_seo( 'horarios', array() );
	if ( is_array( $horarios ) && $horarios ) {
		$org['openingHoursSpecification'] = array();
		foreach ( $horarios as $h ) {
			if ( empty( $h['dias'] ) || empty( $h['abre'] ) ) {
				continue;
			}
			$org['openingHoursSpecification'][] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array_map( 'trim', explode( ',', $h['dias'] ) ),
				'opens'     => $h['abre'],
				'closes'    => isset( $h['fecha'] ) ? $h['fecha'] : '',
			);
		}
	}

	$redes = array_filter( array(
		sg_opt( 'url_instagram' ), sg_opt( 'url_facebook' ),
		sg_opt( 'url_youtube' ), sg_opt( 'url_tiktok' ), sgp_seo( 'negocio_mapa' ),
	) );
	if ( $redes ) {
		$org['sameAs'] = array_values( $redes );
	}

	$grafo[] = $org;

	/* --------------------------------------------------------- o site */
	$grafo[] = array(
		'@type'           => 'WebSite',
		'@id'             => $casa . '#site',
		'url'             => $casa,
		'name'            => get_bloginfo( 'name' ),
		'publisher'       => array( '@id' => $id_org ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $casa . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	/* ------------------------------------------------------- o produto */
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p ) {
			$prod = array(
				'@type'       => 'Product',
				'@id'         => get_permalink() . '#produto',
				'name'        => $p->get_name(),
				'description' => sgp_corta( $p->get_short_description() ? $p->get_short_description() : $p->get_description(), 400 ),
				'url'         => get_permalink(),
				'brand'       => array(
					'@type' => 'Brand',
					'name'  => get_post_meta( $p->get_id(), '_sg_marca', true ) ?: get_bloginfo( 'name' ),
				),
			);

			if ( $p->get_sku() ) {
				$prod['sku']  = $p->get_sku();
				$prod['mpn']  = $p->get_sku();
			}

			$imgs = array();
			if ( $p->get_image_id() ) {
				$s = wp_get_attachment_image_src( $p->get_image_id(), 'full' );
				if ( $s ) {
					$imgs[] = $s[0];
				}
			}
			foreach ( array_slice( $p->get_gallery_image_ids(), 0, 5 ) as $gid ) {
				$s = wp_get_attachment_image_src( $gid, 'full' );
				if ( $s ) {
					$imgs[] = $s[0];
				}
			}
			if ( $imgs ) {
				$prod['image'] = $imgs;
			}

			if ( 'yes' !== get_post_meta( $p->get_id(), '_sg_sob_consulta', true ) && $p->get_price() ) {
				$prod['offers'] = array(
					'@type'         => 'Offer',
					'url'           => get_permalink(),
					'price'         => wc_format_decimal( $p->get_price(), 2 ),
					'priceCurrency' => get_woocommerce_currency(),
					'availability'  => $p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
					'itemCondition' => 'https://schema.org/NewCondition',
					'seller'        => array( '@id' => $id_org ),
					'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
				);
			}

			if ( $p->get_rating_count() > 0 ) {
				$prod['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => (string) $p->get_average_rating(),
					'reviewCount' => (int) $p->get_review_count(),
				);
			}

			$grafo[] = $prod;
		}
	}

	/* --------------------------------------------------------- artigo */
	if ( is_singular( 'post' ) ) {
		$grafo[] = array(
			'@type'            => 'Article',
			'@id'              => get_permalink() . '#artigo',
			'headline'         => get_the_title(),
			'description'      => sgp_descricao_pagina(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => array( '@type' => 'Person', 'name' => get_the_author() ),
			'publisher'        => array( '@id' => $id_org ),
			'mainEntityOfPage' => get_permalink(),
			'image'            => sgp_imagem_social(),
		);
	}

	/* ---------------------------------------------------- migalhas */
	$migalhas = sgp_migalhas_schema();
	if ( $migalhas ) {
		$grafo[] = $migalhas;
	}

	/* -------------------------------------------------------- AEO/FAQ */
	$faq = sgp_meta( 'faq', array() );
	if ( is_front_page() || ! $faq ) {
		$global = sgp_seo( 'faq', array() );
		if ( is_front_page() && is_array( $global ) && $global ) {
			$faq = $global;
		}
	}
	if ( is_array( $faq ) && $faq ) {
		$perguntas = array();
		foreach ( $faq as $f ) {
			if ( empty( $f['p'] ) || empty( $f['r'] ) ) {
				continue;
			}
			$perguntas[] = array(
				'@type'          => 'Question',
				'name'           => $f['p'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['r'] ),
			);
		}
		if ( $perguntas ) {
			$grafo[] = array(
				'@type'      => 'FAQPage',
				'@id'        => sgp_canonical() . '#faq',
				'mainEntity' => $perguntas,
			);
		}
	}

	$json = wp_json_encode(
		array( '@context' => 'https://schema.org', '@graph' => $grafo ),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Migalhas de pão em formato de dados estruturados.
 *
 * @return array|null
 */
function sgp_migalhas_schema() {
	if ( is_front_page() ) {
		return null;
	}

	$itens = array( array( __( 'Início', 'sao-geronimo-painel' ), home_url( '/' ) ) );

	if ( function_exists( 'is_product' ) && is_product() ) {
		$loja = wc_get_page_id( 'shop' );
		if ( $loja > 0 ) {
			$itens[] = array( get_the_title( $loja ), get_permalink( $loja ) );
		}
		$t = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			$primeiro = array_shift( $t );
			$itens[]  = array( $primeiro->name, get_term_link( $primeiro ) );
		}
		$itens[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular() ) {
		$itens[] = array( sgp_meta( 'migalha', get_the_title() ), get_permalink() );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$o = get_queried_object();
		$l = get_term_link( $o );
		$itens[] = array( $o->name, is_wp_error( $l ) ? '' : $l );
	} else {
		return null;
	}

	$lista = array();
	foreach ( $itens as $i => $item ) {
		$lista[] = array_filter( array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item[0],
			'item'     => $item[1],
		) );
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => sgp_canonical() . '#migalhas',
		'itemListElement' => $lista,
	);
}

/**
 * Bloco visível de perguntas frequentes no fim da página do produto.
 *
 * Os dados estruturados sozinhos não bastam: o Google só confia no FAQ que
 * também aparece para quem lê a página.
 */
function sgp_faq_visivel() {
	$faq = sgp_meta( 'faq', array() );
	if ( ! is_array( $faq ) || ! $faq ) {
		return;
	}
	echo '<section class="sec sec--curto faq-bloco"><div class="wrap">';
	echo '<div class="sec__cab"><div><h2 class="h-sec" style="font-size:1.8rem">'
		. esc_html__( 'Perguntas frequentes', 'sao-geronimo-painel' ) . '</h2></div></div>';
	echo '<div class="faq-lista">';
	foreach ( $faq as $f ) {
		if ( empty( $f['p'] ) || empty( $f['r'] ) ) {
			continue;
		}
		echo '<details class="faq-item"><summary>' . esc_html( $f['p'] ) . '</summary>';
		echo '<div class="faq-item__r">' . wp_kses_post( wpautop( $f['r'] ) ) . '</div></details>';
	}
	echo '</div></div></section>';
}
add_action( 'woocommerce_after_single_product_summary', 'sgp_faq_visivel', 25 );

/**
 * Desliga o mapa do site do WordPress se o lojista pedir.
 *
 * @param bool $ativo Estado atual.
 * @return bool
 */
function sgp_sitemap( $ativo ) {
	return 'sim' === sgp_seo( 'sitemap', 'sim' ) ? $ativo : false;
}
add_filter( 'wp_sitemaps_enabled', 'sgp_sitemap' );

/**
 * Acrescenta o mapa do site ao robots.txt.
 *
 * @param string $txt    Conteúdo.
 * @param string $visivel Se o site é público.
 * @return string
 */
function sgp_robots_txt( $txt, $visivel ) {
	if ( ! $visivel ) {
		return $txt;
	}
	$txt .= "\n# São Gerônimo\n";
	$txt .= "Disallow: /carrinho/\nDisallow: /finalizar-compra/\nDisallow: /minha-conta/\nDisallow: /*?s=\n";
	if ( 'sim' === sgp_seo( 'sitemap', 'sim' ) ) {
		$txt .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	}
	return $txt;
}
add_filter( 'robots_txt', 'sgp_robots_txt', 10, 2 );
