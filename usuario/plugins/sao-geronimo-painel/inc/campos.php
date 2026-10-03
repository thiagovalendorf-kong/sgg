<?php
/**
 * Motor de campos do painel: desenha e salva.
 *
 * Toda tela do painel é descrita por um array (ver estrutura.php). Este arquivo
 * é quem transforma esse array em formulário e quem limpa os dados na hora de
 * salvar. Para criar um campo novo, basta acrescentá-lo lá — nada aqui muda.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Desenha um campo.
 *
 * @param string $chave Nome da opção.
 * @param array  $campo Definição do campo.
 * @param mixed  $valor Valor atual.
 */
function sgp_campo( $chave, $campo, $valor ) {
	$tipo  = isset( $campo['tipo'] ) ? $campo['tipo'] : 'texto';
	$nome  = 'sg[' . $chave . ']';
	$id    = 'sg-' . $chave;
	$dica  = isset( $campo['dica'] ) ? $campo['dica'] : '';
	$ph    = isset( $campo['ph'] ) ? $campo['ph'] : '';
	$larga = ! empty( $campo['larga'] ) ? ' sgp-linha--larga' : '';

	if ( 'aviso' === $tipo ) {
		echo '<div class="sgp-aviso">' . wp_kses_post( $campo['texto'] ) . '</div>';
		return;
	}

	echo '<div class="sgp-linha' . esc_attr( $larga ) . '" data-campo="' . esc_attr( $chave ) . '">';
	echo '<div class="sgp-rotulo"><label for="' . esc_attr( $id ) . '">' . esc_html( $campo['rotulo'] ) . '</label>';
	if ( $dica ) {
		echo '<p class="sgp-dica">' . wp_kses_post( $dica ) . '</p>';
	}
	echo '</div><div class="sgp-controle">';

	switch ( $tipo ) {

		case 'textarea':
			printf(
				'<textarea id="%s" name="%s" rows="%d" placeholder="%s">%s</textarea>',
				esc_attr( $id ), esc_attr( $nome ),
				isset( $campo['linhas'] ) ? (int) $campo['linhas'] : 4,
				esc_attr( $ph ), esc_textarea( (string) $valor )
			);
			break;

		case 'editor':
			wp_editor( (string) $valor, str_replace( array( '[', ']' ), '_', $id ), array(
				'textarea_name' => $nome,
				'textarea_rows' => isset( $campo['linhas'] ) ? (int) $campo['linhas'] : 10,
				'media_buttons' => ! empty( $campo['midia'] ),
				'teeny'         => true,
				'quicktags'     => true,
			) );
			break;

		case 'numero':
			printf(
				'<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" step="%s" class="sgp-curto">',
				esc_attr( $id ), esc_attr( $nome ), esc_attr( $valor ),
				esc_attr( isset( $campo['min'] ) ? $campo['min'] : 0 ),
				esc_attr( isset( $campo['max'] ) ? $campo['max'] : 9999 ),
				esc_attr( isset( $campo['passo'] ) ? $campo['passo'] : 1 )
			);
			if ( ! empty( $campo['sufixo'] ) ) {
				echo '<span class="sgp-sufixo">' . esc_html( $campo['sufixo'] ) . '</span>';
			}
			break;

		case 'cor':
			printf(
				'<div class="sgp-cor"><input type="color" value="%1$s" data-espelho="%2$s"><input type="text" id="%2$s" name="%3$s" value="%1$s" class="sgp-cor-txt" spellcheck="false"></div>',
				esc_attr( $valor ? $valor : '#000000' ), esc_attr( $id ), esc_attr( $nome )
			);
			break;

		case 'imagem':
			$tem = $valor ? ' tem' : '';
			echo '<div class="sgp-img' . esc_attr( $tem ) . '" data-img>';
			echo '<div class="sgp-img__visor">';
			if ( $valor ) {
				echo '<img src="' . esc_url( $valor ) . '" alt="">';
			} else {
				echo '<span>' . esc_html__( 'Nenhuma imagem', 'sao-geronimo-painel' ) . '</span>';
			}
			echo '</div><div class="sgp-img__acoes">';
			echo '<button type="button" class="button" data-img-escolher>' . esc_html__( 'Escolher imagem', 'sao-geronimo-painel' ) . '</button> ';
			echo '<button type="button" class="button-link sgp-remover" data-img-tirar>' . esc_html__( 'remover', 'sao-geronimo-painel' ) . '</button>';
			echo '</div>';
			printf( '<input type="hidden" id="%s" name="%s" value="%s" data-img-valor>', esc_attr( $id ), esc_attr( $nome ), esc_url( $valor ) );
			echo '</div>';
			break;

		case 'select':
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $nome ) );
			foreach ( $campo['opcoes'] as $k => $r ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $valor, $k, false ), esc_html( $r ) );
			}
			echo '</select>';
			break;

		case 'liga':
			printf(
				'<label class="sgp-liga"><input type="checkbox" id="%s" name="%s" value="sim" %s><span class="sgp-liga__pino"></span><span class="sgp-liga__txt">%s</span></label>',
				esc_attr( $id ), esc_attr( $nome ), checked( $valor, 'sim', false ),
				esc_html( isset( $campo['ligado'] ) ? $campo['ligado'] : __( 'Ativado', 'sao-geronimo-painel' ) )
			);
			break;

		case 'termos':
			$sel = is_array( $valor ) ? array_map( 'absint', $valor ) : array();
			$tax = isset( $campo['taxonomia'] ) ? $campo['taxonomia'] : 'product_cat';
			$ts  = taxonomy_exists( $tax ) ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) : array();
			echo '<div class="sgp-termos">';
			if ( is_wp_error( $ts ) || ! $ts ) {
				echo '<p class="sgp-dica">' . esc_html__( 'Nenhuma categoria cadastrada ainda.', 'sao-geronimo-painel' ) . '</p>';
			} else {
				foreach ( $ts as $t ) {
					printf(
						'<label><input type="checkbox" name="%s[]" value="%d" %s> %s <em>(%d)</em></label>',
						esc_attr( 'sg[' . $chave . ']' ), (int) $t->term_id,
						checked( in_array( (int) $t->term_id, $sel, true ), true, false ),
						esc_html( $t->name ), (int) $t->count
					);
				}
			}
			echo '</div>';
			break;

		case 'ordem':
			$itens = is_array( $valor ) ? $valor : array_keys( $campo['itens'] );
			// garante que itens novos apareçam no fim
			foreach ( array_keys( $campo['itens'] ) as $k ) {
				if ( ! in_array( $k, $itens, true ) ) {
					$itens[] = $k;
				}
			}
			echo '<ul class="sgp-ordem" data-ordem>';
			foreach ( $itens as $k ) {
				if ( ! isset( $campo['itens'][ $k ] ) ) {
					continue;
				}
				printf(
					'<li draggable="true"><span class="sgp-pega">⠿</span><span>%s</span><input type="hidden" name="%s[]" value="%s"></li>',
					esc_html( $campo['itens'][ $k ] ), esc_attr( 'sg[' . $chave . ']' ), esc_attr( $k )
				);
			}
			echo '</ul><p class="sgp-dica">' . esc_html__( 'Arraste para mudar a ordem na página.', 'sao-geronimo-painel' ) . '</p>';
			break;

		case 'repetidor':
			$linhas = is_array( $valor ) ? array_values( $valor ) : array();
			echo '<div class="sgp-rep" data-rep data-chave="' . esc_attr( $chave ) . '">';
			echo '<div class="sgp-rep__lista" data-rep-lista>';
			foreach ( $linhas as $i => $linha ) {
				echo sgp_repetidor_linha( $chave, $campo['campos'], $i, $linha ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
			printf(
				'<button type="button" class="button button-secondary" data-rep-add>+ %s</button>',
				esc_html( isset( $campo['add'] ) ? $campo['add'] : __( 'Adicionar item', 'sao-geronimo-painel' ) )
			);
			echo '<script type="text/template" data-rep-modelo>' . sgp_repetidor_linha( $chave, $campo['campos'], '__i__', array() ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</div>';
			break;

		case 'texto':
		default:
			printf(
				'<input type="%s" id="%s" name="%s" value="%s" placeholder="%s">',
				esc_attr( isset( $campo['html'] ) ? $campo['html'] : 'text' ),
				esc_attr( $id ), esc_attr( $nome ), esc_attr( (string) $valor ), esc_attr( $ph )
			);
			break;
	}

	echo '</div></div>';
}

/**
 * Uma linha do repetidor.
 *
 * @param string     $chave  Chave da opção.
 * @param array      $campos Subcampos.
 * @param int|string $i      Índice (ou __i__ no modelo).
 * @param array      $linha  Valores atuais.
 * @return string
 */
function sgp_repetidor_linha( $chave, $campos, $i, $linha ) {
	ob_start();
	echo '<div class="sgp-rep__item" data-rep-item><span class="sgp-pega" draggable="true">⠿</span><div class="sgp-rep__campos">';

	foreach ( $campos as $sub => $def ) {
		$n   = sprintf( 'sg[%s][%s][%s]', $chave, $i, $sub );
		$v   = isset( $linha[ $sub ] ) ? $linha[ $sub ] : ( isset( $def['padrao'] ) ? $def['padrao'] : '' );
		$tip = isset( $def['tipo'] ) ? $def['tipo'] : 'texto';

		echo '<label class="sgp-rep__campo"><span>' . esc_html( $def['rotulo'] ) . '</span>';
		if ( 'imagem' === $tip ) {
			echo '<span class="sgp-img sgp-img--mini' . ( $v ? ' tem' : '' ) . '" data-img>';
			echo '<span class="sgp-img__visor">' . ( $v ? '<img src="' . esc_url( $v ) . '" alt="">' : '<span>—</span>' ) . '</span>';
			echo '<button type="button" class="button button-small" data-img-escolher>' . esc_html__( 'Escolher', 'sao-geronimo-painel' ) . '</button>';
			echo '<button type="button" class="button-link sgp-remover" data-img-tirar>×</button>';
			echo '<input type="hidden" name="' . esc_attr( $n ) . '" value="' . esc_url( $v ) . '" data-img-valor>';
			echo '</span>';
		} elseif ( 'textarea' === $tip ) {
			echo '<textarea name="' . esc_attr( $n ) . '" rows="3">' . esc_textarea( (string) $v ) . '</textarea>';
		} elseif ( 'numero' === $tip ) {
			printf(
				'<input type="number" name="%s" value="%s" min="%s" max="%s">',
				esc_attr( $n ), esc_attr( $v ),
				esc_attr( isset( $def['min'] ) ? $def['min'] : 0 ),
				esc_attr( isset( $def['max'] ) ? $def['max'] : 999 )
			);
		} else {
			printf(
				'<input type="text" name="%s" value="%s" placeholder="%s">',
				esc_attr( $n ), esc_attr( (string) $v ), esc_attr( isset( $def['ph'] ) ? $def['ph'] : '' )
			);
		}
		echo '</label>';
	}

	echo '</div><button type="button" class="sgp-rep__x" data-rep-tirar aria-label="' . esc_attr__( 'Remover', 'sao-geronimo-painel' ) . '">×</button></div>';
	return ob_get_clean();
}

/**
 * Limpa o valor de um campo antes de gravar.
 *
 * @param array $campo Definição.
 * @param mixed $bruto Valor cru vindo do formulário.
 * @return mixed
 */
function sgp_limpa( $campo, $bruto ) {
	$tipo = isset( $campo['tipo'] ) ? $campo['tipo'] : 'texto';

	switch ( $tipo ) {
		case 'editor':
			return wp_kses_post( $bruto );

		case 'textarea':
			return sanitize_textarea_field( $bruto );

		case 'numero':
			return is_numeric( $bruto ) ? ( 0 + $bruto ) : 0;

		case 'cor':
			$c = sanitize_hex_color( $bruto );
			return $c ? $c : '';

		case 'imagem':
			return esc_url_raw( $bruto );

		case 'liga':
			return 'sim' === $bruto ? 'sim' : 'nao';

		case 'select':
			return isset( $campo['opcoes'][ $bruto ] ) ? $bruto : key( $campo['opcoes'] );

		case 'termos':
			return is_array( $bruto ) ? array_values( array_map( 'absint', $bruto ) ) : array();

		case 'ordem':
			$ok = array_keys( $campo['itens'] );
			return is_array( $bruto ) ? array_values( array_intersect( array_map( 'sanitize_key', $bruto ), $ok ) ) : $ok;

		case 'repetidor':
			if ( ! is_array( $bruto ) ) {
				return array();
			}
			$saida = array();
			foreach ( $bruto as $linha ) {
				if ( ! is_array( $linha ) ) {
					continue;
				}
				$lim  = array();
				$vaz  = true;
				foreach ( $campo['campos'] as $sub => $def ) {
					$v   = isset( $linha[ $sub ] ) ? $linha[ $sub ] : '';
					$tip = isset( $def['tipo'] ) ? $def['tipo'] : 'texto';
					if ( 'imagem' === $tip ) {
						$lim[ $sub ] = esc_url_raw( $v );
					} elseif ( 'textarea' === $tip ) {
						$lim[ $sub ] = sanitize_textarea_field( $v );
					} elseif ( 'numero' === $tip ) {
						$lim[ $sub ] = is_numeric( $v ) ? ( 0 + $v ) : 0;
					} else {
						$lim[ $sub ] = sanitize_text_field( $v );
					}
					if ( '' !== $lim[ $sub ] && 0 !== $lim[ $sub ] ) {
						$vaz = false;
					}
				}
				if ( ! $vaz ) {
					$saida[] = $lim;
				}
			}
			return $saida;

		case 'texto':
		default:
			if ( isset( $campo['html'] ) && 'url' === $campo['html'] ) {
				return esc_url_raw( $bruto );
			}
			if ( isset( $campo['html'] ) && 'email' === $campo['html'] ) {
				return sanitize_email( $bruto );
			}
			return sanitize_text_field( $bruto );
	}
}
