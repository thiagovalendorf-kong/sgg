<?php
/**
 * Leitura, rascunho e publicação das opções do site.
 *
 * O site lê tudo de uma opção só (sg_opcoes). Enquanto a pessoa edita, as
 * mudanças ficam num RASCUNHO (só dela). A prévia mostra o rascunho; o site
 * de verdade só muda quando ela clica em "Publicar".
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rascunho da pessoa que está logada.
 *
 * @return array
 */
function sgc_rascunho() {
	$r = get_user_meta( get_current_user_id(), 'sgc_rascunho', true );
	return is_array( $r ) ? $r : array();
}

/**
 * Opções publicadas (sem rascunho).
 *
 * @return array
 */
function sgc_publicado() {
	remove_filter( 'option_sg_opcoes', 'sgc_aplicar_rascunho', 5 );
	$o = get_option( 'sg_opcoes', array() );
	add_filter( 'option_sg_opcoes', 'sgc_aplicar_rascunho', 5 );
	return is_array( $o ) ? $o : array();
}

/**
 * A requisição atual é uma prévia do painel?
 *
 * @return bool
 */
function sgc_e_previa() {
	// phpcs:ignore WordPress.Security.NonceVerification
	return isset( $_GET['sgc_previa'] ) && is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Na prévia, o site lê as opções já misturadas com o rascunho.
 *
 * @param mixed $valor Opções salvas.
 * @return mixed
 */
function sgc_aplicar_rascunho( $valor ) {
	if ( ! sgc_e_previa() ) {
		return $valor;
	}
	$valor = is_array( $valor ) ? $valor : array();
	return array_merge( $valor, sgc_rascunho() );
}
add_filter( 'option_sg_opcoes', 'sgc_aplicar_rascunho', 5 );

/**
 * Valor atual de um campo (rascunho > publicado > padrão do campo).
 *
 * @param array $campo Campo do esquema.
 * @param array $base  Opções já misturadas.
 * @return mixed
 */
function sgc_valor( $campo, $base ) {
	$k = $campo['k'];
	if ( isset( $base[ $k ] ) && '' !== $base[ $k ] ) {
		return $base[ $k ];
	}
	return isset( $campo['d'] ) ? $campo['d'] : ( in_array( $campo['t'], array( 'lista', 'cats' ), true ) ? array() : '' );
}

/**
 * Limpa um valor de acordo com o tipo do campo.
 *
 * @param array $campo Campo do esquema.
 * @param mixed $v     Valor enviado.
 * @return mixed
 */
function sgc_limpar( $campo, $v ) {
	switch ( $campo['t'] ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $v );
		case 'html':
			return wp_kses_post( (string) $v );
		case 'css':
			return trim( wp_strip_all_tags( (string) $v ) );
		case 'url':
		case 'image':
			return esc_url_raw( (string) $v );
		case 'color':
			$c = sanitize_hex_color( (string) $v );
			return $c ? $c : '';
		case 'number':
			$n   = (float) $v;
			$min = isset( $campo['min'] ) ? $campo['min'] : null;
			$max = isset( $campo['max'] ) ? $campo['max'] : null;
			if ( null !== $min ) {
				$n = max( $min * ( isset( $campo['esc'] ) ? $campo['esc'] : 1 ), $n );
			}
			if ( null !== $max ) {
				$n = min( $max * ( isset( $campo['esc'] ) ? $campo['esc'] : 1 ), $n );
			}
			return (int) round( $n );
		case 'toggle':
			return 'nao' === $v || false === $v || '0' === $v ? 'nao' : 'sim';
		case 'select':
			return isset( $campo['o'][ $v ] ) ? (string) $v : ( isset( $campo['d'] ) ? $campo['d'] : '' );
		case 'cats':
			return array_values( array_filter( array_map( 'absint', (array) $v ) ) );
		case 'lista':
			$out = array();
			foreach ( (array) $v as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$linha = array();
				$vazio = true;
				foreach ( $campo['sub'] as $sub ) {
					$val = sgc_limpar( $sub, isset( $item[ $sub['k'] ] ) ? $item[ $sub['k'] ] : '' );
					if ( '' !== $val && ( ! isset( $sub['d'] ) || $val !== $sub['d'] ) ) {
						$vazio = false;
					}
					$linha[ $sub['k'] ] = $val;
				}
				if ( ! $vazio ) {
					$out[] = $linha;
				}
			}
			return $out;
		case 'text':
		default:
			return sanitize_text_field( (string) $v );
	}
}

/**
 * Limpa tudo o que veio do formulário. Chaves desconhecidas são ignoradas.
 *
 * @param array $dados Dados enviados.
 * @return array
 */
function sgc_limpar_tudo( $dados ) {
	$campos = sgc_todos_campos();
	$out    = array();

	foreach ( $campos as $k => $c ) {
		if ( 'ordem' === $c['t'] || ! array_key_exists( $k, $dados ) ) {
			continue;
		}
		$out[ $k ] = sgc_limpar( $c, $dados[ $k ] );
	}

	// Ordem e visibilidade das seções da home.
	if ( isset( $dados['_ordem'] ) && is_array( $dados['_ordem'] ) ) {
		$validos = array_keys( sgc_blocos_home() );
		$ordem   = array();
		foreach ( (array) ( $dados['_ordem']['ordem'] ?? array() ) as $b ) {
			$b = sanitize_key( $b );
			if ( in_array( $b, $validos, true ) && ! in_array( $b, $ordem, true ) ) {
				$ordem[] = $b;
			}
		}
		foreach ( $validos as $b ) {
			if ( ! in_array( $b, $ordem, true ) ) {
				$ordem[] = $b;
			}
		}
		$out['home_ordem'] = $ordem;
		foreach ( $validos as $b ) {
			$ativo                       = $dados['_ordem']['ativo'][ $b ] ?? 'sim';
			$out[ 'home_' . $b . '_ativo' ] = 'nao' === $ativo ? 'nao' : 'sim';
		}
	}

	return $out;
}
