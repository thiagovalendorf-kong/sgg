<?php
/**
 * Nenhum produto encontrado.
 *
 * Com filtros marcados, diz isso e oferece o botão de limpar; sem filtros,
 * é uma categoria realmente vazia.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_filtrado = function_exists( 'sg_filtros_ativos' ) && sg_filtros_ativos() > 0;
?>
<div class="busca__nada<?php echo $sg_filtrado ? ' busca__nada--filtros' : ''; ?>">
	<?php if ( $sg_filtrado ) : ?>
		<b><?php esc_html_e( 'Nenhum produto com esses filtros.', 'sao-geronimo' ); ?></b>
		<span><?php esc_html_e( 'Remova algum filtro para ver mais opções.', 'sao-geronimo' ); ?></span>
		<?php
		$sg_ord = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$sg_url = $sg_ord ? add_query_arg( 'orderby', $sg_ord, sg_url_base_loja() ) : sg_url_base_loja();
		?>
		<a class="btn btn--azul" href="<?php echo esc_url( $sg_url ); ?>" data-chip><?php esc_html_e( 'Limpar filtros', 'sao-geronimo' ); ?></a>
	<?php else : ?>
		<b><?php esc_html_e( 'Nenhum produto por aqui ainda.', 'sao-geronimo' ); ?></b>
		<span><?php esc_html_e( 'Tente outra categoria ou use a busca no topo da página.', 'sao-geronimo' ); ?></span>
	<?php endif; ?>
</div>
