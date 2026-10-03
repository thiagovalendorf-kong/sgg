<?php
/**
 * Página do produto — o mesmo desenho do site original.
 *
 * Galeria com miniaturas à esquerda, dados à direita (preço, compra, "sobre",
 * ficha técnica, frete) e "Quem viu, levou também" embaixo.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$sg_imgs  = sg_pdp_imagens( $product );
$sg_nome  = $product->get_name();
$sg_total = count( $sg_imgs );
?>
<section class="sec sec--curto" style="padding-top:0">
	<div class="wrap"><?php do_action( 'woocommerce_before_single_product' ); ?></div>

	<div id="product-<?php echo (int) $product->get_id(); ?>" class="wrap pdp">
		<div class="pdp__galeria">
			<?php if ( $sg_total ) : ?>
				<div class="pdp__minis">
					<?php foreach ( $sg_imgs as $i => $sg_img ) : ?>
						<button type="button" class="pdp__mini<?php echo 0 === $i ? ' on' : ''; ?>" data-troca="<?php echo esc_url( wp_get_attachment_image_url( $sg_img, 'large' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: número da foto */ __( 'Foto %d', 'sao-geronimo' ), $i + 1 ) ); ?>">
							<?php echo wp_get_attachment_image( $sg_img, 'thumbnail', false, array( 'alt' => $sg_nome, 'loading' => 'lazy' ) ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="pdp__principal">
				<?php
				if ( $sg_total ) {
					echo wp_get_attachment_image( $sg_imgs[0], 'large', false, array( 'alt' => $sg_nome, 'loading' => 'eager', 'fetchpriority' => 'high' ) );
				} else {
					echo '<span class="prod__ph">' . sg_icone( 'estrela', 40 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</div>

		<div class="pdp__dados">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>
	</div>
</section>

<?php
if ( 'nao' !== sg_opt( 'produto_relacionados', 'sim' ) ) :
	$sg_rel = sg_pdp_relacionados( $product, 4 );
	if ( $sg_rel ) :
		?>
		<section class="sec sec--curto"><div class="wrap">
			<div class="sec__cab"><h2 class="h-sec" style="font-size:1.8rem"><?php echo esc_html( sg_opt( 'produto_relacionados_titulo', __( 'Quem viu, levou também', 'sao-geronimo' ) ) ); ?></h2></div>
			<div class="grade-prod">
				<?php
				$sg_original = $product;
				foreach ( $sg_rel as $sg_rid ) {
					$GLOBALS['product'] = wc_get_product( $sg_rid );
					if ( $GLOBALS['product'] ) {
						wc_get_template_part( 'content', 'product' );
					}
				}
				$GLOBALS['product'] = $sg_original;
				?>
			</div>
		</div></section>
		<?php
	endif;
endif;
