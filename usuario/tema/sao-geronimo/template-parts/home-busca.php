<?php
/**
 * Barra de busca da home.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="busca-home"><div class="wrap">
	<button type="button" class="busca-home__caixa" data-abrir-busca>
		<?php echo sg_icone( 'busca', 19 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php echo esc_html( sg_opt( 'busca_home_texto', 'O que você procura? Nome, orixá, aroma ou referência…' ) ); ?></span>
		<span class="busca-home__btn"><?php esc_html_e( 'Buscar', 'sao-geronimo' ); ?></span>
	</button>
</div></section>
