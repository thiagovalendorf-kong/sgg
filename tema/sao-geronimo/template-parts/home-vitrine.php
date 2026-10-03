<?php
/**
 * Vitrine de produtos da home (carrossel).
 *
 * Cada vitrine é um "slot" configurado no painel: título, subtítulo, de onde
 * puxar os produtos e quantos mostrar. São cinco slots por padrão, mas o painel
 * aceita quantos você criar.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

$sg_slot = isset( $args['slot'] ) ? sanitize_key( $args['slot'] ) : 'vitrine1';
$sg_p    = 'home_' . $sg_slot . '_';

$sg_titulo = sg_opt( $sg_p . 'titulo', '' );
if ( ! $sg_titulo ) {
	return;
}

$sg_fonte = sg_opt( $sg_p . 'fonte', 'recentes' );
$sg_qtd   = max( 2, min( 24, (int) sg_opt( $sg_p . 'qtd', 8 ) ) );

$sg_q = array(
	'status'   => 'publish',
	'limit'    => $sg_qtd,
	'orderby'  => 'date',
	'order'    => 'DESC',
	'return'   => 'ids',
	'stock_status' => 'instock',
);

switch ( $sg_fonte ) {
	case 'destaque':
		$sg_q['featured'] = true;
		break;
	case 'vendidos':
		$sg_q['orderby'] = 'popularity';
		break;
	case 'avaliados':
		$sg_q['orderby'] = 'rating';
		break;
	case 'promocao':
		$sg_q['on_sale'] = true;
		break;
	case 'aleatorio':
		$sg_q['orderby'] = 'rand';
		break;
	case 'categoria':
		$sg_cat = sg_opt( $sg_p . 'categoria', '' );
		if ( $sg_cat ) {
			$sg_q['category'] = array_map( 'trim', explode( ',', $sg_cat ) );
		}
		break;
	case 'etiqueta':
		$sg_tag = sg_opt( $sg_p . 'etiqueta', '' );
		if ( $sg_tag ) {
			$sg_q['tag'] = array_map( 'trim', explode( ',', $sg_tag ) );
		}
		break;
	case 'manual':
		$sg_skus = array_filter( array_map( 'trim', explode( ',', (string) sg_opt( $sg_p . 'skus', '' ) ) ) );
		if ( $sg_skus ) {
			$sg_ids = array();
			foreach ( $sg_skus as $sg_sku ) {
				$sg_id = wc_get_product_id_by_sku( $sg_sku );
				if ( $sg_id ) {
					$sg_ids[] = $sg_id;
				}
			}
			$sg_q['include'] = $sg_ids ? $sg_ids : array( 0 );
			$sg_q['orderby'] = 'include';
		}
		break;
}

$sg_ids = wc_get_products( $sg_q );
if ( ! $sg_ids ) {
	return;
}

$sg_link = sg_opt( $sg_p . 'link', '' );
if ( ! $sg_link && function_exists( 'wc_get_page_permalink' ) ) {
	$sg_link = wc_get_page_permalink( 'shop' );
}
$sg_id_sec = 'sg-' . $sg_slot;
?>
<section class="sec sec--curto vitrine" id="<?php echo esc_attr( $sg_id_sec ); ?>" aria-labelledby="t-<?php echo esc_attr( $sg_slot ); ?>"><div class="wrap">
	<div class="sec__cab">
		<div>
			<h2 class="h-sec" id="t-<?php echo esc_attr( $sg_slot ); ?>"><?php echo esc_html( $sg_titulo ); ?></h2>
			<?php if ( sg_opt( $sg_p . 'sub' ) ) : ?>
				<p class="sec__sub texto-mudo"><?php echo esc_html( sg_opt( $sg_p . 'sub' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $sg_link ) : ?>
			<a href="<?php echo esc_url( $sg_link ); ?>" class="link-sub"><?php esc_html_e( 'Ver tudo', 'sao-geronimo' ); ?> <?php echo sg_icone( 'seta', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
	</div>

	<div class="carrossel">
		<button class="carrossel__nav carrossel__nav--ant" data-pista-ant aria-label="<?php printf( esc_attr__( 'Anterior em %s', 'sao-geronimo' ), esc_attr( $sg_titulo ) ); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
		<button class="carrossel__nav carrossel__nav--prox" data-pista-prox aria-label="<?php printf( esc_attr__( 'Próximo em %s', 'sao-geronimo' ), esc_attr( $sg_titulo ) ); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></button>
		<div class="vitrine__pista" data-pista>
			<?php
			foreach ( $sg_ids as $sg_pid ) {
				// o cartão lê $product e $post: os dois precisam ser montados à mão,
				// porque aqui não há o laço do WordPress para fazer isso.
				$GLOBALS['post']    = get_post( $sg_pid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				$GLOBALS['product'] = wc_get_product( $sg_pid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $GLOBALS['post'] );
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			$GLOBALS['product'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			?>
		</div>
	</div>
</div></section>
