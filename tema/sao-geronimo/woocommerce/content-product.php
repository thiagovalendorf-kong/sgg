<?php
/**
 * Cartão de produto — o mesmo visual do site antigo.
 *
 * Este arquivo substitui o cartão padrão do WooCommerce. Para personalizar,
 * copie-o para um tema filho; não edite aqui, senão você perde na atualização.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$sg_id        = $product->get_id();
$sg_consulta  = 'yes' === get_post_meta( $sg_id, '_sg_sob_consulta', true );
$sg_cats      = get_the_terms( $sg_id, 'product_cat' );
$sg_cat_nome  = ( $sg_cats && ! is_wp_error( $sg_cats ) ) ? $sg_cats[0]->name : '';
$sg_tags      = get_the_terms( $sg_id, 'product_tag' );
$sg_sub       = ( $sg_tags && ! is_wp_error( $sg_tags ) ) ? $sg_tags[0]->name : $sg_cat_nome;
$sg_preco     = (float) $product->get_price();
$sg_parcelas  = sg_parcelas();
$sg_novo_dias = (int) sg_opt( 'selo_novo_dias', 30 );
$sg_e_novo    = $sg_novo_dias > 0 && ( time() - get_post_time( 'U', true, $sg_id ) ) < $sg_novo_dias * DAY_IN_SECONDS;
?>
<article <?php wc_product_class( 'prod', $product ); ?> data-sub="<?php echo esc_attr( $sg_sub ); ?>">
	<a href="<?php the_permalink(); ?>" class="prod__img">
		<?php
		if ( has_post_thumbnail( $sg_id ) ) {
			echo get_the_post_thumbnail( $sg_id, 'sg-card', array(
				'loading' => 'lazy',
				'alt'     => esc_attr( $product->get_name() ),
			) );
		} else {
			echo '<span class="prod__ph">' . sg_icone( 'estrela', 30 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		if ( $product->is_on_sale() && ! $sg_consulta ) {
			echo '<span class="prod__selo prod__selo--off">' . esc_html__( 'Oferta', 'sao-geronimo' ) . '</span>';
		} elseif ( $product->is_featured() ) {
			echo '<span class="prod__selo">' . esc_html__( 'Premium', 'sao-geronimo' ) . '</span>';
		} elseif ( $sg_e_novo ) {
			echo '<span class="prod__selo">' . esc_html__( 'Novo', 'sao-geronimo' ) . '</span>';
		}
		?>
	</a>

	<div class="prod__corpo">
		<?php if ( $sg_cat_nome ) : ?>
			<span class="prod__cat"><?php echo esc_html( $sg_cat_nome ); ?></span>
		<?php endif; ?>

		<a href="<?php the_permalink(); ?>"><h3 class="prod__nome"><?php echo esc_html( $product->get_name() ); ?></h3></a>

		<?php if ( $product->get_average_rating() > 0 ) : ?>
			<div class="prod__nota">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/></svg>
				<?php echo esc_html( number_format_i18n( $product->get_average_rating(), 1 ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $sg_consulta ) : ?>
			<div class="prod__preco">
				<b style="font-size:1rem"><?php esc_html_e( 'Preço sob consulta', 'sao-geronimo' ); ?></b>
				<span><?php esc_html_e( 'Fale com a gente pelo WhatsApp', 'sao-geronimo' ); ?></span>
			</div>
			<?php
			$sg_msg = sprintf(
				/* translators: 1: nome, 2: referência */
				__( 'Olá! Gostaria de saber o preço do produto %1$s (Ref. %2$s).', 'sao-geronimo' ),
				$product->get_name(),
				$product->get_sku()
			);
			?>
			<a class="prod__btn" href="<?php echo esc_url( sg_whatsapp_link( $sg_msg ) ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Consultar preço', 'sao-geronimo' ); ?>
			</a>

		<?php else : ?>
			<div class="prod__preco">
				<b><?php echo wp_kses_post( $product->get_price_html() ); ?></b>
				<?php if ( $sg_preco > 0 && $sg_parcelas > 1 ) : ?>
					<span><?php
						printf(
							/* translators: %s: "10x de R$ 14,59" */
							esc_html__( 'ou em até %s sem juros', 'sao-geronimo' ),
							'<em>' . sprintf(
								/* translators: 1: número de parcelas, 2: valor da parcela */
								esc_html__( '%1$dx de %2$s', 'sao-geronimo' ),
								(int) $sg_parcelas,
								esc_html( sg_preco_texto( $sg_preco / $sg_parcelas ) )
							) . '</em>'
						);
					?></span>
				<?php endif; ?>
			</div>

			<?php
			echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput
				'woocommerce_loop_add_to_cart_link',
				sprintf(
					'<a href="%s" data-quantity="1" class="prod__btn %s" %s>%s</a>',
					esc_url( $product->add_to_cart_url() ),
					$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart add_to_cart_button' : '',
					wc_implode_html_attributes( array(
						'data-product_id'  => $sg_id,
						'data-product_sku' => $product->get_sku(),
						'aria-label'       => $product->add_to_cart_description(),
						'rel'              => 'nofollow',
					) ),
					esc_html( sg_opt( 'texto_botao_comprar', __( 'Comprar agora', 'sao-geronimo' ) ) )
				),
				$product,
				array()
			);
			?>
		<?php endif; ?>
	</div>
</article>
