<?php
/**
 * Página "Categorias": um cartão para cada categoria, com a contagem, a frase
 * e as subcategorias — igual à do site original.
 *
 * Vale para a página com o endereço /categorias/. Tudo se edita em
 * Produtos → Categorias (nome, descrição, imagem).
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$sg_cats = taxonomy_exists( 'product_cat' ) ? get_terms( array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => false,
	'parent'     => 0,
	'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	'orderby'    => 'meta_value_num',
	'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery
) ) : array();
if ( is_wp_error( $sg_cats ) || ! $sg_cats ) {
	$sg_cats = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name' ) ) : array();
}
$sg_cats = is_wp_error( $sg_cats ) ? array() : $sg_cats;
?>
<section class="capa"><div class="wrap">
	<h1><?php the_title(); ?></h1>
	<p><?php echo esc_html( sprintf( /* translators: número de categorias */ __( 'As %d famílias de produtos da loja, com suas subcategorias.', 'sao-geronimo' ), count( $sg_cats ) ) ); ?></p>
</div></section>
<?php sg_migalhas(); ?>
<section class="sec sec--curto" style="padding-top:0"><div class="wrap">
	<div class="grade-prod" style="grid-template-columns:repeat(3,1fr)">
	<?php foreach ( $sg_cats as $sg_c ) : ?>
		<?php
		$sg_tid  = (int) get_term_meta( $sg_c->term_id, 'thumbnail_id', true );
		$sg_subs = (string) get_term_meta( $sg_c->term_id, '_sg_subs', true );
		$sg_img  = '';
		if ( $sg_tid ) {
			$sg_img = wp_get_attachment_image( $sg_tid, 'sg-card', false, array( 'alt' => $sg_c->name, 'loading' => 'lazy' ) );
		}
		if ( ! $sg_img && $sg_c->count ) {
			$sg_p = wc_get_products( array( 'status' => 'publish', 'limit' => 1, 'category' => array( $sg_c->slug ), 'return' => 'ids' ) );
			if ( $sg_p && has_post_thumbnail( $sg_p[0] ) ) {
				$sg_img = get_the_post_thumbnail( $sg_p[0], 'sg-card', array( 'alt' => $sg_c->name, 'loading' => 'lazy' ) );
			}
		}
		?>
		<a href="<?php echo esc_url( get_term_link( $sg_c ) ); ?>" class="prod">
			<div class="prod__img">
				<?php
				if ( $sg_img ) {
					echo $sg_img; // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<div class="ph"><div class="ph__ico">' . sg_icone( 'foto', 30 ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<div class="prod__corpo">
				<span class="prod__cat"><?php echo esc_html( sprintf( _n( '%d produto', '%d produtos', (int) $sg_c->count, 'sao-geronimo' ), (int) $sg_c->count ) ); ?></span>
				<h3 class="prod__nome" style="font-size:1.15rem"><?php echo esc_html( $sg_c->name ); ?></h3>
				<?php if ( $sg_c->description ) : ?>
					<p style="font-size:13px;color:var(--muted-fg)"><?php echo esc_html( wp_strip_all_tags( $sg_c->description ) ); ?></p>
				<?php endif; ?>
				<?php if ( $sg_subs ) : ?>
					<p style="font-size:12px;color:var(--muted-fg);margin-top:8px;opacity:.8"><?php echo esc_html( $sg_subs ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	<?php endforeach; ?>
	</div>
</div></section>
<?php
get_footer();
