<?php
/**
 * Atualizar produtos a partir do catálogo (catalogo.json).
 *
 * Página oculta (o link fica no menu Gestão): sgc-catalogo.
 * Só atualiza produtos que já existem (achados pelo SKU). Nunca apaga nada.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGC_CAT_MAX_BYTES', 5 * 1024 * 1024 );
define( 'SGC_CAT_LOTE', 25 );

/* ==========================================================================
   Página
   ========================================================================== */

/**
 * Quem pode usar o catálogo: administrador ou gerente da loja.
 *
 * @return bool
 */
function sgc_cat_pode() {
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

/**
 * Registra a página oculta.
 */
function sgc_cat_menu() {
	add_submenu_page( null, 'Atualizar produtos pelo catálogo', 'Atualizar produtos pelo catálogo', current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options', 'sgc-catalogo', 'sgc_cat_pagina' );
}
add_action( 'admin_menu', 'sgc_cat_menu', 30 );

/**
 * Carrega CSS e JS só nesta página.
 *
 * @param string $hook Tela atual.
 */
function sgc_cat_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'sgc-catalogo' ) ) {
		return;
	}
	wp_enqueue_style( 'sgc-catalogo', SGC_URL . 'assets/catalogo.css', array( 'sgcv' ), SGC_VERSAO );
	wp_enqueue_script( 'sgc-catalogo', SGC_URL . 'assets/catalogo.js', array(), SGC_VERSAO, true );
	$estado = sgc_cat_estado();
	wp_localize_script( 'sgc-catalogo', 'SGCCAT', array(
		'ajax'      => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'sgc_catalogo' ),
		'csv'       => wp_nonce_url( admin_url( 'admin-post.php?action=sgc_cat_csv' ), 'sgc_cat_csv' ),
		'maxBytes'  => SGC_CAT_MAX_BYTES,
		'carregado' => sgc_cat_resumo_carregado(),
		'andamento' => ( $estado && 'andamento' === $estado['status'] ) ? sgc_cat_progresso( $estado ) : null,
		'final'     => ( $estado && 'concluido' === $estado['status'] ) ? sgc_cat_relatorio( $estado ) : null,
	) );
}
add_action( 'admin_enqueue_scripts', 'sgc_cat_assets' );

/**
 * Desenha a página.
 */
function sgc_cat_pagina() {
	if ( ! sgc_cat_pode() ) {
		wp_die( 'Você não tem permissão para ver esta página.' );
	}
	?>
	<div class="wrap sgcat" id="sgcat">
		<h1>Atualizar produtos pelo catálogo</h1>
		<p class="sgcat__sub">Traga as descrições, o modo de usar, a ficha técnica, o peso e as medidas do catálogo para os produtos que já estão na loja.</p>

		<section class="sgcat__cartao">
			<h2><span class="sgcat__num">1</span> O que isto faz</h2>
			<div class="sgcat__duas">
				<div class="sgcat__caixa sgcat__caixa--ok">
					<b>O que faz</b>
					<ul>
						<li>Procura cada produto da loja pelo <b>SKU</b> (o código) e preenche a descrição curta, a descrição longa, o modo de usar e a ficha técnica.</li>
						<li>Coloca peso e medidas para o cálculo do frete.</li>
						<li>Mostra uma <b>simulação</b> antes de mexer em qualquer coisa.</li>
					</ul>
				</div>
				<div class="sgcat__caixa sgcat__caixa--no">
					<b>O que NÃO faz</b>
					<ul>
						<li>Não cria produtos novos: só atualiza os que já existem.</li>
						<li>Não apaga nada.</li>
						<li>Não mexe em fotos e, se você não pedir, não mexe nos preços.</li>
					</ul>
				</div>
			</div>
		</section>

		<section class="sgcat__cartao">
			<h2><span class="sgcat__num">2</span> Envie o arquivo do catálogo</h2>
			<p class="sgcat__nota">Arquivo <code>catalogo.json</code>, até 5 MB.</p>
			<div class="sgcat__envio">
				<label class="sgcat__arquivo" for="sgcat-arquivo">
					<span class="sgcat__arquivo-tit">Escolher o arquivo catalogo.json</span>
					<span class="sgcat__arquivo-nome" id="sgcat-nome">Nenhum arquivo escolhido</span>
				</label>
				<input type="file" id="sgcat-arquivo" accept=".json,application/json">
			</div>
			<details class="sgcat__colar">
				<summary>Ou cole o conteúdo do catálogo aqui</summary>
				<textarea id="sgcat-texto" rows="6" placeholder='{"categorias": [...], "produtos": [...]}' spellcheck="false"></textarea>
			</details>
			<div class="sgcat__linha">
				<button type="button" class="sgcat__bt sgcat__bt--principal" id="sgcat-enviar">Enviar e conferir o arquivo</button>
			</div>
			<div class="sgcat__linha" id="sgcat-linha-ultimo" hidden>
				<button type="button" class="sgcat__bt" id="sgcat-ver-ultimo">Ver o relatório da última atualização</button>
			</div>
			<div id="sgcat-carregado" class="sgcat__aviso sgcat__aviso--info" hidden></div>
			<div id="sgcat-erro" class="sgcat__aviso sgcat__aviso--erro" role="alert" hidden></div>
		</section>

		<section class="sgcat__cartao" id="sgcat-passo3" hidden>
			<h2><span class="sgcat__num">3</span> Escolha as opções</h2>
			<label class="sgcat__opcao">
				<input type="checkbox" id="sgcat-o-sobrescrever">
				<span><b>Sobrescrever textos que já existem</b><small>Se desligado, só preenche o que está vazio. Se ligado, troca também o que já foi escrito (inclui peso e medidas).</small></span>
			</label>
			<label class="sgcat__opcao">
				<input type="checkbox" id="sgcat-o-precos">
				<span><b>Atualizar preços</b><small>Se desligado, os preços da loja ficam como estão. Se ligado, usa o preço do catálogo.</small></span>
			</label>
			<label class="sgcat__opcao">
				<input type="checkbox" id="sgcat-o-medidas" checked>
				<span><b>Atualizar peso e medidas</b><small>Usado no cálculo do frete. Só preenche o que está vazio, a menos que "sobrescrever" esteja ligado.</small></span>
			</label>
			<div class="sgcat__linha">
				<button type="button" class="sgcat__bt sgcat__bt--principal" id="sgcat-simular">Simular (não altera nada)</button>
			</div>
		</section>

		<section class="sgcat__cartao" id="sgcat-passo4" hidden>
			<h2><span class="sgcat__num">4</span> Simulação</h2>
			<div id="sgcat-sim"></div>
			<p class="sgcat__nota sgcat__nota--aplicar" id="sgcat-aplicar-nota">Os produtos são atualizados de 25 em 25. Pode demorar um pouco; se a página fechar, é possível continuar de onde parou.</p>
			<div class="sgcat__linha" id="sgcat-linha-aplicar">
				<button type="button" class="sgcat__bt sgcat__bt--ouro" id="sgcat-aplicar">Aplicar agora</button>
			</div>
		</section>

		<section class="sgcat__cartao" id="sgcat-passo5" hidden>
			<h2><span class="sgcat__num">5</span> Andamento e relatório</h2>
			<div class="sgcat__barra" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="sgcat-barra"><i id="sgcat-barra-i"></i></div>
			<p class="sgcat__prog" id="sgcat-prog" aria-live="polite"></p>
			<div class="sgcat__linha" id="sgcat-retomar" hidden>
				<button type="button" class="sgcat__bt sgcat__bt--ouro" id="sgcat-continuar">Continuar de onde parou</button>
				<button type="button" class="sgcat__bt" id="sgcat-recomecar">Recomeçar do zero</button>
			</div>
			<div id="sgcat-rel"></div>
			<div class="sgcat__linha" id="sgcat-linha-csv" hidden>
				<a class="sgcat__bt sgcat__bt--principal" id="sgcat-csv" href="#">Baixar relatório (CSV)</a>
			</div>
		</section>
	</div>
	<?php
}

/* ==========================================================================
   Leitura e validação do catálogo
   ========================================================================== */

/**
 * Texto curto limpo.
 *
 * @param mixed $v Valor.
 * @return string
 */
function sgc_cat_curto( $v ) {
	return is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
}

/**
 * Texto longo limpo (mantém quebras de linha).
 *
 * @param mixed $v Valor.
 * @return string
 */
function sgc_cat_longo( $v ) {
	return is_scalar( $v ) ? trim( wp_kses_post( str_replace( "\r\n", "\n", (string) $v ) ) ) : '';
}

/**
 * Valida o JSON enviado e devolve só o que interessa, já limpo.
 *
 * @param string $texto Conteúdo do arquivo.
 * @return array|WP_Error Lista de produtos ou erro amigável.
 */
function sgc_cat_ler( $texto ) {
	$texto = (string) $texto;
	if ( '' === trim( $texto ) ) {
		return new WP_Error( 'vazio', 'Nenhum conteúdo foi enviado. Escolha o arquivo catalogo.json ou cole o conteúdo.' );
	}
	if ( strlen( $texto ) > SGC_CAT_MAX_BYTES ) {
		return new WP_Error( 'grande', 'O arquivo tem mais de 5 MB. Envie o catalogo.json original, que é bem menor.' );
	}
	// Remove o marcador de ordem de bytes que alguns editores colocam no começo.
	if ( 0 === strpos( $texto, "\xEF\xBB\xBF" ) ) {
		$texto = substr( $texto, 3 );
	}
	$dados = json_decode( $texto, true, 32 );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $dados ) ) {
		return new WP_Error( 'json', 'Não consegui ler este arquivo: ele não é um JSON válido (' . json_last_error_msg() . '). Confira se é o catalogo.json inteiro, sem cortes.' );
	}
	$lista = isset( $dados['produtos'] ) && is_array( $dados['produtos'] ) ? $dados['produtos'] : ( isset( $dados[0] ) ? $dados : null );
	if ( null === $lista ) {
		return new WP_Error( 'estrutura', 'Este JSON não tem a lista "produtos". Confira se é o arquivo catalogo.json do catálogo.' );
	}

	$saida     = array();
	$invalidos = 0;
	$vistos    = array();
	foreach ( $lista as $p ) {
		if ( ! is_array( $p ) ) {
			++$invalidos;
			continue;
		}
		$sku = sgc_cat_curto( isset( $p['sku'] ) ? $p['sku'] : '' );
		if ( '' === $sku || isset( $vistos[ $sku ] ) ) {
			++$invalidos;
			continue;
		}
		$vistos[ $sku ] = true;
		$blocos         = array();
		if ( isset( $p['blocos'] ) && is_array( $p['blocos'] ) ) {
			foreach ( $p['blocos'] as $b ) {
				if ( ! is_array( $b ) ) {
					continue;
				}
				$tit = sgc_cat_curto( isset( $b['titulo'] ) ? $b['titulo'] : '' );
				$txt = sgc_cat_longo( isset( $b['texto'] ) ? $b['texto'] : '' );
				if ( '' !== $txt ) {
					$blocos[] = array( 'titulo' => $tit, 'texto' => $txt );
				}
			}
		}
		$preco = null;
		if ( isset( $p['preco'] ) && is_numeric( $p['preco'] ) && $p['preco'] >= 0 ) {
			$preco = (float) $p['preco'];
		}
		$saida[] = array(
			'sku'            => $sku,
			'nome'           => sgc_cat_curto( isset( $p['nome'] ) ? $p['nome'] : '' ),
			'preco'          => $preco,
			'marca'          => sgc_cat_curto( isset( $p['marca'] ) ? $p['marca'] : '' ),
			'conteudo'       => sgc_cat_curto( isset( $p['conteudo'] ) ? $p['conteudo'] : '' ),
			'material'       => sgc_cat_curto( isset( $p['material'] ) ? $p['material'] : '' ),
			'cod_fabricante' => sgc_cat_curto( isset( $p['cod_fabricante'] ) ? $p['cod_fabricante'] : '' ),
			'dimensoes'      => sgc_cat_curto( isset( $p['dimensoes'] ) ? $p['dimensoes'] : '' ),
			'peso'           => sgc_cat_curto( isset( $p['peso'] ) ? $p['peso'] : '' ),
			'descricao'      => sgc_cat_longo( isset( $p['descricao'] ) ? $p['descricao'] : '' ),
			'blocos'         => $blocos,
		);
	}
	if ( ! $saida ) {
		return new WP_Error( 'sem_produtos', 'O arquivo foi lido, mas não tem nenhum produto com SKU.' );
	}
	return array(
		'produtos'  => $saida,
		'invalidos' => $invalidos,
	);
}

/**
 * Catálogo guardado (lista limpa).
 *
 * @return array
 */
function sgc_cat_dados() {
	$d = get_option( 'sgc_catalogo_dados', array() );
	return is_array( $d ) ? $d : array();
}

/**
 * Resumo do que está carregado (para a tela).
 *
 * @return array|null
 */
function sgc_cat_resumo_carregado() {
	$d = sgc_cat_dados();
	if ( empty( $d['produtos'] ) ) {
		return null;
	}
	return array(
		'total'     => count( $d['produtos'] ),
		'invalidos' => (int) $d['invalidos'],
		'quando'    => isset( $d['quando'] ) ? wp_date( 'd/m/Y H:i', (int) $d['quando'] ) : '',
	);
}

/* ==========================================================================
   Texto -> campos do WooCommerce
   ========================================================================== */

/**
 * Converte "3,5" ou "1.200,5" em número.
 *
 * @param string $s Texto.
 * @return float
 */
function sgc_cat_numero( $s ) {
	$s = trim( $s );
	if ( false !== strpos( $s, ',' ) ) {
		$s = str_replace( '.', '', $s );
		$s = str_replace( ',', '.', $s );
	}
	return (float) $s;
}

/**
 * Número como texto simples (ponto decimal, sem zeros sobrando).
 *
 * @param float $n      Número.
 * @param int   $casas  Casas decimais.
 * @return string
 */
function sgc_cat_fmt( $n, $casas = 2 ) {
	$t = number_format( (float) $n, $casas, '.', '' );
	return false !== strpos( $t, '.' ) ? rtrim( rtrim( $t, '0' ), '.' ) : $t;
}

/**
 * "165 g" ou "1,2 kg" -> "0.165" (kg).
 *
 * @param string $txt Texto.
 * @return string Vazio se não entender.
 */
function sgc_cat_peso_kg( $txt ) {
	if ( preg_match( '/^\s*([\d.,]+)\s*(kg|g)\s*$/iu', $txt, $m ) ) {
		$n = sgc_cat_numero( $m[1] );
		if ( $n <= 0 ) {
			return '';
		}
		return sgc_cat_fmt( 'g' === strtolower( $m[2] ) ? $n / 1000 : $n, 3 );
	}
	return '';
}

/**
 * "3,5 × 11 × 3,5 cm (largura × altura × comprimento)" -> largura/altura/comprimento em cm.
 * Só converte quando a ordem do texto é a esperada.
 *
 * @param string $txt Texto.
 * @return array Vazio se não entender.
 */
function sgc_cat_medidas_cm( $txt ) {
	$re = '/([\d.,]+)\s*[×x]\s*([\d.,]+)\s*[×x]\s*([\d.,]+)\s*(mm|cm|m)\b\s*\(\s*largura\s*[×x]\s*altura\s*[×x]\s*(?:comprimento|profundidade)\s*\)/iu';
	if ( ! preg_match( $re, $txt, $m ) ) {
		return array();
	}
	$fator = array( 'mm' => 0.1, 'cm' => 1, 'm' => 100 );
	$f     = $fator[ strtolower( $m[4] ) ];
	$v     = array(
		'width'  => sgc_cat_numero( $m[1] ) * $f,
		'height' => sgc_cat_numero( $m[2] ) * $f,
		'length' => sgc_cat_numero( $m[3] ) * $f,
	);
	foreach ( $v as $k => $n ) {
		if ( $n <= 0 ) {
			return array();
		}
		$v[ $k ] = sgc_cat_fmt( $n, 2 );
	}
	return $v;
}

/**
 * Texto com linhas em branco -> parágrafos <p>, quebras simples -> <br>.
 *
 * @param string $txt Texto.
 * @return string
 */
function sgc_cat_paragrafos( $txt ) {
	$html = '';
	foreach ( preg_split( "/\n\s*\n/", trim( $txt ) ) as $par ) {
		$par = trim( $par );
		if ( '' === $par ) {
			continue;
		}
		$html .= '<p>' . nl2br( $par, false ) . "</p>\n";
	}
	return $html;
}

/**
 * Monta o que o catálogo quer em cada campo do produto.
 *
 * @param array $c Produto do catálogo.
 * @return array campo => valor desejado
 */
function sgc_cat_desejado( $c ) {
	$d      = array();
	$extras = '';
	$modo   = '';
	$comp   = '';
	foreach ( $c['blocos'] as $b ) {
		$tit = remove_accents( strtolower( $b['titulo'] ) );
		if ( 'modo de usar' === $tit ) {
			$modo = $b['texto'];
		} elseif ( 'composicao' === $tit ) {
			$comp = $b['texto'];
		} else {
			$extras .= ( '' !== $b['titulo'] ? '<h3>' . esc_html( $b['titulo'] ) . "</h3>\n" : '' ) . sgc_cat_paragrafos( $b['texto'] );
		}
	}
	$d['excerpt'] = '' !== $c['descricao'] ? sgc_cat_paragrafos( $c['descricao'] ) : '';
	$d['content'] = '' !== $extras ? $extras : ( '' !== $c['descricao'] ? sgc_cat_paragrafos( $c['descricao'] ) : '' );

	$d['_sg_modo_usar']      = $modo;
	$d['_sg_composicao']     = $comp;
	$d['_sg_marca']          = $c['marca'];
	$d['_sg_material']       = $c['material'];
	$d['_sg_conteudo']       = $c['conteudo'];
	$d['_sg_cod_fabricante'] = $c['cod_fabricante'];
	$d['_sg_dimensoes']      = $c['dimensoes'];

	$d['_weight'] = sgc_cat_peso_kg( $c['peso'] );
	foreach ( sgc_cat_medidas_cm( $c['dimensoes'] ) as $k => $v ) {
		$d[ '_' . $k ] = $v;
	}
	$d['_regular_price'] = null === $c['preco'] ? '' : sgc_cat_fmt( $c['preco'], 2 );
	return $d;
}

/**
 * Nomes bonitos dos campos (relatório e simulação).
 *
 * @return array
 */
function sgc_cat_rotulos() {
	return array(
		'excerpt'            => 'Descrição curta',
		'content'            => 'Descrição longa',
		'_sg_modo_usar'      => 'Modo de usar',
		'_sg_composicao'     => 'Composição',
		'_sg_marca'          => 'Marca',
		'_sg_material'       => 'Material',
		'_sg_conteudo'       => 'Conteúdo',
		'_sg_cod_fabricante' => 'Código do fabricante',
		'_sg_dimensoes'      => 'Medidas (texto)',
		'_weight'            => 'Peso (kg)',
		'_width'             => 'Largura (cm)',
		'_height'            => 'Altura (cm)',
		'_length'            => 'Comprimento (cm)',
		'_regular_price'     => 'Preço',
	);
}

/**
 * Valor atual de um campo do produto.
 *
 * @param WP_Post $post  Produto.
 * @param string  $campo Campo.
 * @return string
 */
function sgc_cat_atual( $post, $campo ) {
	if ( 'excerpt' === $campo ) {
		return (string) $post->post_excerpt;
	}
	if ( 'content' === $campo ) {
		return (string) $post->post_content;
	}
	$v = get_post_meta( $post->ID, $campo, true );
	return is_scalar( $v ) ? (string) $v : '';
}

/**
 * Compara o produto da loja com o catálogo e diz o que mudaria.
 *
 * @param WP_Post $post Produto.
 * @param array   $c    Produto do catálogo.
 * @param array   $op   Opções (sobrescrever, precos, medidas).
 * @return array campo => [antes, depois]
 */
function sgc_cat_plano( $post, $c, $op ) {
	$plano = array();
	foreach ( sgc_cat_desejado( $c ) as $campo => $novo ) {
		if ( null === $novo || '' === $novo ) {
			continue;
		}
		$medida = in_array( $campo, array( '_weight', '_width', '_height', '_length' ), true );
		if ( $medida && empty( $op['medidas'] ) ) {
			continue;
		}
		$preco = ( '_regular_price' === $campo );
		if ( $preco && empty( $op['precos'] ) ) {
			continue;
		}
		$atual = sgc_cat_atual( $post, $campo );
		if ( $preco ) {
			// Preço: troca sempre que for diferente (a pessoa pediu).
			if ( abs( (float) $atual - (float) $novo ) < 0.005 && '' !== $atual ) {
				continue;
			}
		} else {
			if ( '' !== trim( $atual ) && empty( $op['sobrescrever'] ) ) {
				continue;
			}
			if ( trim( $atual ) === trim( $novo ) || ( $medida && '' !== $atual && abs( (float) $atual - (float) $novo ) < 0.0005 ) ) {
				continue;
			}
		}
		$plano[ $campo ] = array( $atual, $novo );
	}
	return $plano;
}

/* ==========================================================================
   Banco: achar produtos e gravar
   ========================================================================== */

/**
 * Acha os produtos da loja pelo SKU (uma consulta só).
 *
 * @param array $skus Lista de SKUs.
 * @return array sku => ['id' => int, 'repetido' => bool]
 */
function sgc_cat_achar( $skus ) {
	global $wpdb;
	$achados = array();
	foreach ( array_chunk( array_values( array_unique( $skus ) ), 200 ) as $parte ) {
		$marcas = implode( ',', array_fill( 0, count( $parte ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql  = "SELECT m.meta_value AS sku, p.ID AS id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_sku' AND p.post_type = 'product' AND p.post_status NOT IN ('trash','auto-draft') AND m.meta_value IN ($marcas) ORDER BY p.ID ASC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $parte ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $rows as $r ) {
			if ( isset( $achados[ $r->sku ] ) ) {
				$achados[ $r->sku ]['repetido'] = true;
				continue;
			}
			$achados[ $r->sku ] = array( 'id' => (int) $r->id, 'repetido' => false );
		}
	}
	return $achados;
}

/**
 * Grava as mudanças de um produto.
 *
 * @param WP_Post $post  Produto.
 * @param array   $plano campo => [antes, depois].
 * @return true|WP_Error
 */
function sgc_cat_gravar( $post, $plano ) {
	$id   = $post->ID;
	$novo = array();
	foreach ( $plano as $campo => $par ) {
		$novo[ $campo ] = $par[1];
	}

	// Com o WooCommerce ativo, usa a API dele (limpa caches e índices de busca).
	if ( function_exists( 'wc_get_product' ) ) {
		try {
			$prod = wc_get_product( $id );
			if ( $prod && method_exists( $prod, 'save' ) && method_exists( $prod, 'set_description' ) && method_exists( $prod, 'set_short_description' ) ) {
				if ( isset( $novo['excerpt'] ) ) {
					$prod->set_short_description( $novo['excerpt'] );
				}
				if ( isset( $novo['content'] ) ) {
					$prod->set_description( $novo['content'] );
				}
				$set = array( '_weight' => 'set_weight', '_width' => 'set_width', '_height' => 'set_height', '_length' => 'set_length', '_regular_price' => 'set_regular_price' );
				foreach ( $set as $campo => $metodo ) {
					if ( isset( $novo[ $campo ] ) && method_exists( $prod, $metodo ) ) {
						$prod->$metodo( $novo[ $campo ] );
					}
				}
				$prod->save();
				foreach ( $novo as $campo => $valor ) {
					if ( 0 === strpos( $campo, '_sg_' ) ) {
						update_post_meta( $id, $campo, $valor );
					}
				}
				return true;
			}
		} catch ( Throwable $e ) {
			return new WP_Error( 'woo', 'Erro do WooCommerce: ' . $e->getMessage() );
		}
	}

	// Sem o WooCommerce (ou produto não carregou): grava direto.
	$post_dados = array( 'ID' => $id );
	if ( isset( $novo['excerpt'] ) ) {
		$post_dados['post_excerpt'] = $novo['excerpt'];
	}
	if ( isset( $novo['content'] ) ) {
		$post_dados['post_content'] = $novo['content'];
	}
	if ( count( $post_dados ) > 1 ) {
		$r = wp_update_post( wp_slash( $post_dados ), true );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}
	foreach ( $novo as $campo => $valor ) {
		if ( 'excerpt' === $campo || 'content' === $campo ) {
			continue;
		}
		update_post_meta( $id, $campo, $valor );
		if ( '_regular_price' === $campo && '' === (string) get_post_meta( $id, '_sale_price', true ) ) {
			update_post_meta( $id, '_price', $valor );
		}
	}
	return true;
}

/* ==========================================================================
   Estado (retomável) e relatório
   ========================================================================== */

/**
 * Estado da atualização em andamento ou da última concluída.
 *
 * @return array|null
 */
function sgc_cat_estado() {
	$e = get_option( 'sgc_catalogo_estado', null );
	return is_array( $e ) && isset( $e['status'] ) ? $e : null;
}

/**
 * Guarda o estado (fora do carregamento automático).
 *
 * @param array $e Estado.
 */
function sgc_cat_salvar_estado( $e ) {
	update_option( 'sgc_catalogo_estado', $e, false );
}

/**
 * Opções vindas da tela.
 *
 * @return array
 */
function sgc_cat_opcoes_post() {
	return array(
		'sobrescrever' => ! empty( $_POST['sobrescrever'] ) && '0' !== $_POST['sobrescrever'], // phpcs:ignore WordPress.Security.NonceVerification
		'precos'       => ! empty( $_POST['precos'] ) && '0' !== $_POST['precos'], // phpcs:ignore WordPress.Security.NonceVerification
		'medidas'      => ! empty( $_POST['medidas'] ) && '0' !== $_POST['medidas'], // phpcs:ignore WordPress.Security.NonceVerification
	);
}

/**
 * Progresso para a tela.
 *
 * @param array $e Estado.
 * @return array
 */
function sgc_cat_progresso( $e ) {
	return array(
		'pos'    => (int) $e['pos'],
		'total'  => (int) $e['total'],
		'opcoes' => $e['opcoes'],
		'status' => $e['status'],
	);
}

/**
 * Resume um texto longo para o relatório.
 *
 * @param string $t Texto.
 * @return string
 */
function sgc_cat_resumir( $t ) {
	$t = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $t ) ) );
	return mb_strlen( $t ) > 110 ? mb_substr( $t, 0, 110 ) . '…' : $t;
}

/**
 * Valor de um campo para a simulação e o relatório (preço em Real).
 *
 * @param string $campo Campo.
 * @param mixed  $v     Valor.
 * @param int    $max   Limite de caracteres.
 * @return string
 */
function sgc_cat_ver( $campo, $v, $max = 110 ) {
	if ( '_regular_price' === $campo && is_numeric( $v ) ) {
		return 'R$ ' . number_format( (float) $v, 2, ',', '.' );
	}
	$t = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $v ) ) );
	return mb_strlen( $t ) > $max ? mb_substr( $t, 0, $max ) . '…' : $t;
}

/**
 * Relatório final com contagens e problemas.
 *
 * @param array $e Estado.
 * @return array
 */
function sgc_cat_relatorio( $e ) {
	$cont      = array( 'alterado' => 0, 'sem_mudanca' => 0, 'nao_achado' => 0, 'erro' => 0 );
	$problemas = array();
	if ( isset( $e['cont'] ) && is_array( $e['cont'] ) ) {
		$cont = array_merge( $cont, $e['cont'] );
	}
	foreach ( $e['log'] as $l ) {
		if ( ! isset( $e['cont'] ) && isset( $cont[ $l['status'] ] ) ) {
			++$cont[ $l['status'] ];
		}
		if ( 'nao_achado' === $l['status'] || 'erro' === $l['status'] || ! empty( $l['aviso'] ) ) {
			$problemas[] = array(
				'sku'   => $l['sku'],
				'nome'  => $l['nome'],
				'texto' => 'nao_achado' === $l['status'] ? 'Não existe produto com este SKU na loja.' : ( 'erro' === $l['status'] ? $l['detalhe'] : $l['aviso'] ),
			);
		}
	}
	return array(
		'total'     => (int) $e['total'],
		'contagens' => $cont,
		'problemas' => array_slice( $problemas, 0, 300 ),
		'problemas_total' => count( $problemas ),
		'status'    => $e['status'],
		'opcoes'    => $e['opcoes'],
	);
}

/* ==========================================================================
   AJAX
   ========================================================================== */

/**
 * Confere permissão e nonce.
 */
function sgc_cat_ajax_ok() {
	if ( ! sgc_cat_pode() ) {
		wp_send_json_error( array( 'mensagem' => 'Você não tem permissão para fazer isto.' ), 403 );
	}
	if ( ! check_ajax_referer( 'sgc_catalogo', 'nonce', false ) ) {
		wp_send_json_error( array( 'mensagem' => 'A página ficou aberta por muito tempo. Atualize a página (F5) e tente de novo.' ), 403 );
	}
}

/**
 * Passo 2: recebe e valida o catálogo.
 */
function sgc_cat_ajax_enviar() {
	sgc_cat_ajax_ok();
	$texto = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$r     = sgc_cat_ler( is_string( $texto ) ? $texto : '' );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( array( 'mensagem' => $r->get_error_message() ) );
	}
	$r['quando'] = time();
	update_option( 'sgc_catalogo_dados', $r, false );
	// Catálogo novo: zera uma atualização antiga.
	delete_option( 'sgc_catalogo_estado' );
	wp_send_json_success( array( 'carregado' => sgc_cat_resumo_carregado() ) );
}
add_action( 'wp_ajax_sgc_cat_enviar', 'sgc_cat_ajax_enviar' );

/**
 * Passo 4: simulação (não grava nada).
 */
function sgc_cat_ajax_simular() {
	sgc_cat_ajax_ok();
	$d = sgc_cat_dados();
	if ( empty( $d['produtos'] ) ) {
		wp_send_json_error( array( 'mensagem' => 'Envie o catálogo primeiro (passo 2).' ) );
	}
	$op      = sgc_cat_opcoes_post();
	$rot     = sgc_cat_rotulos();
	$skus    = wp_list_pluck( $d['produtos'], 'sku' );
	$achados = sgc_cat_achar( $skus );

	$sem_achar = array();
	$repetidos = array();
	$a_mudar   = 0;
	$sem_mud   = 0;
	$campos    = array();
	$amostra   = array();
	foreach ( $d['produtos'] as $c ) {
		if ( ! isset( $achados[ $c['sku'] ] ) ) {
			$sem_achar[] = $c['sku'];
			continue;
		}
		if ( $achados[ $c['sku'] ]['repetido'] ) {
			$repetidos[] = $c['sku'];
		}
		$post = get_post( $achados[ $c['sku'] ]['id'] );
		if ( ! $post ) {
			continue;
		}
		$plano = sgc_cat_plano( $post, $c, $op );
		if ( ! $plano ) {
			++$sem_mud;
			continue;
		}
		++$a_mudar;
		foreach ( array_keys( $plano ) as $campo ) {
			$campos[ $campo ] = isset( $campos[ $campo ] ) ? $campos[ $campo ] + 1 : 1;
		}
		if ( count( $amostra ) < 5 ) {
			$linhas = array();
			foreach ( $plano as $campo => $par ) {
				$linhas[] = array(
					'campo'  => $rot[ $campo ],
					'antes'  => sgc_cat_ver( $campo, $par[0] ),
					'depois' => sgc_cat_ver( $campo, $par[1] ),
				);
			}
			$amostra[] = array( 'sku' => $c['sku'], 'nome' => $post->post_title, 'linhas' => $linhas );
		}
	}
	$lista_campos = array();
	foreach ( $campos as $campo => $n ) {
		$lista_campos[] = array( 'campo' => $rot[ $campo ], 'qtd' => $n );
	}
	wp_send_json_success( array(
		'total'      => count( $d['produtos'] ),
		'achados'    => count( $d['produtos'] ) - count( $sem_achar ),
		'nao_achados'=> $sem_achar,
		'repetidos'  => $repetidos,
		'a_mudar'    => $a_mudar,
		'sem_mudanca'=> $sem_mud,
		'campos'     => $lista_campos,
		'amostra'    => $amostra,
	) );
}
add_action( 'wp_ajax_sgc_cat_simular', 'sgc_cat_ajax_simular' );

/**
 * Passo 5: aplica um lote (25 produtos) e devolve o andamento.
 */
function sgc_cat_ajax_aplicar() {
	sgc_cat_ajax_ok();
	$d = sgc_cat_dados();
	if ( empty( $d['produtos'] ) ) {
		wp_send_json_error( array( 'mensagem' => 'Envie o catálogo primeiro (passo 2).' ) );
	}
	// Trava: só um lote por vez (dois cliques, duas abas ou reenvio não rodam em paralelo).
	if ( get_transient( 'sgc_cat_trava' ) ) {
		wp_send_json_error( array( 'mensagem' => 'Já tem um lote em andamento, aguarde alguns segundos.' ), 409 );
	}
	set_transient( 'sgc_cat_trava', 1, 60 );
	register_shutdown_function( 'delete_transient', 'sgc_cat_trava' );

	$e = sgc_cat_estado();
	if ( ! empty( $_POST['inicio'] ) || ! $e || 'andamento' !== $e['status'] ) {
		if ( empty( $_POST['inicio'] ) ) {
			wp_send_json_error( array( 'mensagem' => 'Não há atualização em andamento. Clique em "Aplicar agora".' ) );
		}
		$e = array(
			'status' => 'andamento',
			'pos'    => 0,
			'total'  => count( $d['produtos'] ),
			'opcoes' => sgc_cat_opcoes_post(),
			'log'    => array(),
			'cont'   => array( 'alterado' => 0, 'sem_mudanca' => 0, 'nao_achado' => 0, 'erro' => 0 ),
			'inicio' => time(),
		);
	}

	$lote    = array_slice( $d['produtos'], (int) $e['pos'], SGC_CAT_LOTE );
	$achados = sgc_cat_achar( wp_list_pluck( $lote, 'sku' ) );
	$t0      = microtime( true );
	foreach ( $lote as $c ) {
		$linha = array( 'sku' => $c['sku'], 'nome' => $c['nome'], 'status' => 'sem_mudanca', 'campos' => array(), 'detalhe' => '', 'aviso' => '' );
		if ( ! isset( $achados[ $c['sku'] ] ) ) {
			$linha['status'] = 'nao_achado';
		} else {
			$post = get_post( $achados[ $c['sku'] ]['id'] );
			if ( $achados[ $c['sku'] ]['repetido'] ) {
				$linha['aviso'] = 'Há mais de um produto com este SKU; foi atualizado o mais antigo.';
			}
			if ( ! $post ) {
				$linha['status']  = 'erro';
				$linha['detalhe'] = 'Produto não pôde ser carregado.';
			} else {
				$plano = sgc_cat_plano( $post, $c, $e['opcoes'] );
				if ( $plano ) {
					$r = sgc_cat_gravar( $post, $plano );
					if ( is_wp_error( $r ) ) {
						$linha['status']  = 'erro';
						$linha['detalhe'] = $r->get_error_message();
					} else {
						$rot = sgc_cat_rotulos();
						$linha['status'] = 'alterado';
						foreach ( $plano as $campo => $par ) {
							$linha['campos'][] = array( $rot[ $campo ], sgc_cat_ver( $campo, $par[0], 60 ), sgc_cat_ver( $campo, $par[1], 60 ) );
						}
					}
				}
			}
		}
		// O log guarda só o que importa (alterados e problemas); o resto vira contagem.
		if ( isset( $e['cont'][ $linha['status'] ] ) ) {
			++$e['cont'][ $linha['status'] ];
		}
		if ( 'sem_mudanca' !== $linha['status'] || '' !== $linha['aviso'] ) {
			$e['log'][] = $linha;
		}
		++$e['pos'];
		if ( microtime( true ) - $t0 > 20 ) {
			break; // Não estoura o tempo: o resto vem no próximo lote.
		}
	}
	if ( $e['pos'] >= $e['total'] ) {
		$e['status'] = 'concluido';
		$e['fim']    = time();
	}
	sgc_cat_salvar_estado( $e );
	$saida = sgc_cat_progresso( $e );
	if ( 'concluido' === $e['status'] ) {
		$saida['relatorio'] = sgc_cat_relatorio( $e );
	}
	wp_send_json_success( $saida );
}
add_action( 'wp_ajax_sgc_cat_aplicar', 'sgc_cat_ajax_aplicar' );

/**
 * Recomeçar: descarta o andamento.
 */
function sgc_cat_ajax_zerar() {
	sgc_cat_ajax_ok();
	delete_option( 'sgc_catalogo_estado' );
	wp_send_json_success();
}
add_action( 'wp_ajax_sgc_cat_zerar', 'sgc_cat_ajax_zerar' );

/**
 * Baixa o relatório em CSV.
 */
function sgc_cat_csv() {
	if ( ! sgc_cat_pode() ) {
		wp_die( 'Você não tem permissão para baixar este relatório.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sgc_cat_csv' );
	$e = sgc_cat_estado();
	if ( ! $e ) {
		wp_die( 'Ainda não há relatório. Aplique a atualização primeiro.' );
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="relatorio-catalogo-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
	$f = fopen( 'php://output', 'w' );
	fwrite( $f, "\xEF\xBB\xBF" ); // Excel abre com acentos certos.
	fputcsv( $f, array( 'SKU', 'Produto', 'Situação', 'Campo', 'Antes', 'Depois', 'Observação' ), ';' );
	$sit = array( 'alterado' => 'Alterado', 'sem_mudanca' => 'Sem mudança', 'nao_achado' => 'SKU não encontrado', 'erro' => 'Erro' );
	foreach ( $e['log'] as $l ) {
		$obs = trim( $l['aviso'] . ' ' . $l['detalhe'] );
		if ( $l['campos'] ) {
			foreach ( $l['campos'] as $c ) {
				fputcsv( $f, array( sgc_cat_csv_seguro( $l['sku'] ), sgc_cat_csv_seguro( $l['nome'] ), $sit[ $l['status'] ], $c[0], sgc_cat_csv_seguro( $c[1] ), sgc_cat_csv_seguro( $c[2] ), $obs ), ';' );
			}
		} else {
			fputcsv( $f, array( sgc_cat_csv_seguro( $l['sku'] ), sgc_cat_csv_seguro( $l['nome'] ), $sit[ $l['status'] ], '', '', '', $obs ), ';' );
		}
	}
	fclose( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_sgc_cat_csv', 'sgc_cat_csv' );

/**
 * Evita fórmulas em planilhas (texto que começa com = + - @).
 *
 * @param string $t Texto.
 * @return string
 */
function sgc_cat_csv_seguro( $t ) {
	$t = (string) $t;
	return ( '' !== $t && false !== strpos( "=+-@\t\r", $t[0] ) ) ? "'" . $t : $t;
}
