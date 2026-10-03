<?php
/**
 * Desenha os campos do painel.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Atributos de dependência (campo que só aparece se outro tiver certo valor).
 *
 * @param array $c Campo.
 * @return string
 */
function sgc_attr_se( $c ) {
	if ( empty( $c['se'] ) ) {
		return '';
	}
	return ' data-se="' . esc_attr( wp_json_encode( $c['se'] ) ) . '"';
}

/**
 * Desenha só o controle (sem rótulo), usado também dentro das listas.
 *
 * @param array  $c     Campo.
 * @param mixed  $valor Valor atual.
 * @param string $nome  Nome do input (para listas, é o nome do subcampo).
 * @param bool   $dentro Está dentro de uma lista?
 */
function sgc_controle( $c, $valor, $dentro = false ) {
	$k   = esc_attr( $c['k'] );
	$id  = $dentro ? '' : ' id="sgc-' . $k . '"';
	$dk  = $dentro ? ' data-sub="' . $k . '"' : ' data-k="' . $k . '"';
	$ph  = isset( $c['p'] ) ? ' placeholder="' . esc_attr( $c['p'] ) . '"' : '';

	switch ( $c['t'] ) {
		case 'textarea':
		case 'css':
			printf( '<textarea%s%s rows="%d"%s class="sgc-in%s">%s</textarea>', $id, $dk, 'css' === $c['t'] ? 8 : 3, $ph, 'css' === $c['t'] ? ' sgc-mono' : '', esc_textarea( (string) $valor ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'html':
			printf( '<textarea%s%s rows="5"%s class="sgc-in" data-html="1">%s</textarea>', $id, $dk, $ph, esc_textarea( (string) $valor ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'select':
			printf( '<select%s%s class="sgc-in">', $id, $dk ); // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( $c['o'] as $v => $rot ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( (string) $valor, (string) $v, false ), esc_html( $rot ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</select>';
			break;

		case 'toggle':
			printf(
				'<label class="sgc-chave"><input type="checkbox"%s%s %s><span class="sgc-chave__trilho"></span><span class="sgc-chave__txt" data-sim="Ligado" data-nao="Desligado"></span></label>',
				$id, $dk, checked( 'nao' !== $valor, true, false ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			break;

		case 'color':
			printf(
				'<div class="sgc-cor"><input type="color"%s%s value="%s" class="sgc-cor__pick"><input type="text" class="sgc-in sgc-cor__hex" value="%s" maxlength="7" aria-label="Código da cor"></div>',
				$id, $dk, esc_attr( $valor ? $valor : ( $c['d'] ?? '#000000' ) ), esc_attr( $valor ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			break;

		case 'number':
			$esc = isset( $c['esc'] ) ? (float) $c['esc'] : 1;
			$ver = '' === $valor ? '' : $valor / $esc;
			printf(
				'<div class="sgc-num"><input type="number"%s%s value="%s" min="%s" max="%s" step="1" data-esc="%s" class="sgc-in"><span>%s</span></div>',
				$id, $dk, esc_attr( $ver ), esc_attr( $c['min'] ?? '' ), esc_attr( $c['max'] ?? '' ), esc_attr( $esc ), esc_html( $c['un'] ?? '' ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			break;

		case 'image':
			printf(
				'<div class="sgc-img" data-img><input type="hidden"%s%s value="%s"><div class="sgc-img__prev">%s</div><div class="sgc-img__bt"><button type="button" class="sgc-bt" data-img-escolher>Escolher imagem</button> <button type="button" class="sgc-bt sgc-bt--fantasma" data-img-remover>Remover</button></div></div>',
				$id, $dk, esc_attr( $valor ), $valor ? '<img src="' . esc_url( $valor ) . '" alt="">' : '<span>Nenhuma imagem</span>' // phpcs:ignore WordPress.Security.EscapeOutput
			);
			break;

		case 'cats':
			sgc_campo_cats( $c, (array) $valor );
			break;

		case 'lista':
			sgc_campo_lista( $c, (array) $valor );
			break;

		case 'ordem':
			sgc_campo_ordem( $c );
			break;

		case 'url':
		case 'text':
		default:
			printf( '<input type="%s"%s%s value="%s"%s class="sgc-in">', 'url' === $c['t'] ? 'url' : 'text', $id, $dk, esc_attr( (string) $valor ), $ph ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Uma linha completa: rótulo, ajuda e controle.
 *
 * @param array $c     Campo.
 * @param array $base  Opções atuais.
 */
function sgc_linha( $c, $base ) {
	$valor = 'ordem' === $c['t'] ? '' : sgc_valor( $c, $base );
	$larga = in_array( $c['t'], array( 'lista', 'ordem', 'cats', 'css', 'html' ), true ) ? ' sgc-campo--largo' : '';
	?>
	<div class="sgc-campo<?php echo esc_attr( $larga ); ?>"<?php echo sgc_attr_se( $c ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<label class="sgc-rotulo" for="sgc-<?php echo esc_attr( $c['k'] ); ?>"><?php echo esc_html( $c['l'] ); ?></label>
		<?php if ( ! empty( $c['a'] ) ) : ?>
			<p class="sgc-ajuda"><?php echo esc_html( $c['a'] ); ?></p>
		<?php endif; ?>
		<?php sgc_controle( $c, $valor ); ?>
	</div>
	<?php
}

/**
 * Categorias do WooCommerce para marcar.
 *
 * @param array $c     Campo.
 * @param array $valor IDs marcados.
 */
function sgc_campo_cats( $c, $valor ) {
	$cats = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) ) : array();
	echo '<div class="sgc-cats" data-cats data-k="' . esc_attr( $c['k'] ) . '">';
	if ( is_wp_error( $cats ) || ! $cats ) {
		echo '<p class="sgc-ajuda">Cadastre categorias de produtos primeiro.</p>';
	} else {
		foreach ( $cats as $t ) {
			printf(
				'<label class="sgc-pilula"><input type="checkbox" value="%d"%s><span>%s</span></label>',
				(int) $t->term_id, checked( in_array( (int) $t->term_id, array_map( 'intval', $valor ), true ), true, false ), esc_html( $t->name ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
	}
	echo '</div>';
}

/**
 * Lista que cresce (banners, depoimentos).
 *
 * @param array $c     Campo.
 * @param array $itens Itens atuais.
 */
function sgc_campo_lista( $c, $itens ) {
	echo '<div class="sgc-lista" data-lista data-k="' . esc_attr( $c['k'] ) . '" data-nome="' . esc_attr( $c['item'] ) . '">';
	echo '<div class="sgc-lista__itens" data-itens>';
	foreach ( array_values( $itens ) as $i => $item ) {
		sgc_item_lista( $c, is_array( $item ) ? $item : array(), $i + 1 );
	}
	echo '</div>';
	echo '<button type="button" class="sgc-bt sgc-bt--add" data-add>+ Adicionar ' . esc_html( strtolower( $c['item'] ) ) . '</button>';
	echo '<template data-modelo>';
	sgc_item_lista( $c, array(), 0 );
	echo '</template>';
	echo '</div>';
}

/**
 * Um item da lista.
 *
 * @param array $c    Campo da lista.
 * @param array $item Valores.
 * @param int   $n    Posição.
 */
function sgc_item_lista( $c, $item, $n ) {
	echo '<div class="sgc-item" data-item draggable="false">';
	echo '<div class="sgc-item__topo"><span class="sgc-item__alca" data-alca title="Arraste para mudar a ordem">⋮⋮</span><b data-titulo>' . esc_html( $c['item'] ) . ' <i data-n>' . ( $n ? (int) $n : '' ) . '</i></b>';
	echo '<span class="sgc-item__acoes"><button type="button" class="sgc-mini" data-sobe title="Subir">↑</button><button type="button" class="sgc-mini" data-desce title="Descer">↓</button><button type="button" class="sgc-mini sgc-mini--x" data-remove title="Remover">✕</button></span></div>';
	echo '<div class="sgc-item__corpo">';
	foreach ( $c['sub'] as $s ) {
		$v = isset( $item[ $s['k'] ] ) ? $item[ $s['k'] ] : ( $s['d'] ?? '' );
		echo '<div class="sgc-campo"><label class="sgc-rotulo">' . esc_html( $s['l'] ) . '</label>';
		if ( ! empty( $s['a'] ) ) {
			echo '<p class="sgc-ajuda">' . esc_html( $s['a'] ) . '</p>';
		}
		sgc_controle( $s, $v, true );
		echo '</div>';
	}
	echo '</div></div>';
}

/**
 * Ordem das seções da home, com olhinho para ligar e desligar.
 *
 * @param array $c Campo.
 */
function sgc_campo_ordem( $c ) {
	$base   = array_merge( sgc_publicado(), sgc_rascunho() );
	$blocos = sgc_blocos_home();
	$ordem  = isset( $base['home_ordem'] ) && is_array( $base['home_ordem'] ) ? $base['home_ordem'] : array_keys( $blocos );
	foreach ( array_keys( $blocos ) as $b ) {
		if ( ! in_array( $b, $ordem, true ) ) {
			$ordem[] = $b;
		}
	}
	echo '<ol class="sgc-ordem" data-ordem data-k="_ordem">';
	foreach ( $ordem as $b ) {
		if ( ! isset( $blocos[ $b ] ) ) {
			continue;
		}
		$on = 'nao' !== ( $base[ 'home_' . $b . '_ativo' ] ?? 'sim' );
		printf(
			'<li class="sgc-ordem__item%s" data-b="%s" draggable="true"><span class="sgc-item__alca">⋮⋮</span><span class="sgc-ordem__nome">%s</span><button type="button" class="sgc-mini" data-sobe title="Subir">↑</button><button type="button" class="sgc-mini" data-desce title="Descer">↓</button><button type="button" class="sgc-olho" data-olho title="Mostrar / esconder" aria-pressed="%s"><span class="on">👁</span><span class="off">🚫</span></button></li>',
			$on ? '' : ' is-off', esc_attr( $b ), esc_html( $blocos[ $b ] ), $on ? 'true' : 'false' // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</ol>';
}
