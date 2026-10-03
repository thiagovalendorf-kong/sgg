<?php
/**
 * Página inicial.
 *
 * A ordem dos blocos e o conteúdo de cada um vêm do painel
 * (São Gerônimo → Página inicial). Arrastar lá reordena aqui.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$sg_ordem = sg_opt( 'home_ordem', array( 'hero', 'busca', 'vitrine1', 'categorias', 'vitrine2', 'vitrine3', 'vitrine4', 'vitrine5', 'sobre', 'depoimentos', 'blog' ) );
if ( ! is_array( $sg_ordem ) ) {
	$sg_ordem = explode( ',', (string) $sg_ordem );
}

foreach ( $sg_ordem as $sg_bloco ) {
	$sg_bloco = sanitize_key( trim( $sg_bloco ) );
	if ( ! $sg_bloco || 'nao' === sg_opt( 'home_' . $sg_bloco . '_ativo', 'sim' ) ) {
		continue;
	}

	if ( 0 === strpos( $sg_bloco, 'vitrine' ) ) {
		get_template_part( 'template-parts/home-vitrine', null, array( 'slot' => $sg_bloco ) );
		continue;
	}

	get_template_part( 'template-parts/home-' . $sg_bloco );
}

get_footer();
