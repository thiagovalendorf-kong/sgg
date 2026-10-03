<?php
/**
 * Formulário de busca.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="form-busca" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'O que você procura?', 'sao-geronimo' ); ?>"
		aria-label="<?php esc_attr_e( 'Buscar', 'sao-geronimo' ); ?>">
	<input type="hidden" name="post_type" value="product">
	<button type="submit"><?php esc_html_e( 'Buscar', 'sao-geronimo' ); ?></button>
</form>
