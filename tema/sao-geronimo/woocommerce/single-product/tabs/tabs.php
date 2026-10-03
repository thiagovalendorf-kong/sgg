<?php
/**
 * Abas do produto: no computador são abas, no celular viram acordeão.
 * Sem JavaScript, todas as seções aparecem uma embaixo da outra.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( empty( $product_tabs ) ) {
	return;
}
?>
<section class="sg-abas" data-sg-abas aria-label="<?php esc_attr_e( 'Detalhes do produto', 'sao-geronimo' ); ?>">
	<div class="sg-abas__lista" role="tablist" aria-label="<?php esc_attr_e( 'Seções do produto', 'sao-geronimo' ); ?>">
		<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
			<button type="button" class="sg-abas__aba" role="tab" id="aba-<?php echo esc_attr( $key ); ?>" data-aba="<?php echo esc_attr( $key ); ?>" aria-controls="painel-<?php echo esc_attr( $key ); ?>" aria-selected="false" tabindex="-1">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
		<div class="sg-aba" id="painel-<?php echo esc_attr( $key ); ?>" data-aba="<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="aba-<?php echo esc_attr( $key ); ?>" tabindex="0">
			<h2 class="sg-aba__tit">
				<button type="button" class="sg-aba__cab" id="cab-<?php echo esc_attr( $key ); ?>" aria-expanded="false" aria-controls="corpo-<?php echo esc_attr( $key ); ?>">
					<span><?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?></span>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>
				</button>
			</h2>
			<div class="sg-aba__corpo" id="corpo-<?php echo esc_attr( $key ); ?>">
				<?php
				if ( isset( $product_tab['callback'] ) ) {
					call_user_func( $product_tab['callback'], $key, $product_tab );
				}
				?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php do_action( 'woocommerce_product_after_tabs' ); ?>
</section>
