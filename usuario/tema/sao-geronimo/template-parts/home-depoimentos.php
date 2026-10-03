<?php
/**
 * Depoimentos de clientes (cadastrados no painel).
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_deps = sg_opt( 'depoimentos', array() );
if ( ! is_array( $sg_deps ) ) {
	return;
}
$sg_deps = array_values( array_filter( $sg_deps, function ( $d ) {
	return ! empty( $d['texto'] );
} ) );
if ( ! $sg_deps ) {
	return;
}
?>
<section class="sec sec--curto depo" aria-labelledby="t-depo"><div class="wrap">
	<div class="sec__cab">
		<div>
			<h2 class="h-sec" id="t-depo"><?php echo esc_html( sg_opt( 'depoimentos_titulo', 'O que dizem de nós' ) ); ?></h2>
			<?php if ( sg_opt( 'depoimentos_sub' ) ) : ?>
				<p class="sec__sub texto-mudo"><?php echo esc_html( sg_opt( 'depoimentos_sub' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="depo__grade">
		<?php foreach ( $sg_deps as $sg_d ) :
			$sg_nota = isset( $sg_d['nota'] ) ? max( 1, min( 5, (int) $sg_d['nota'] ) ) : 5;
			?>
			<figure class="depo__card">
				<div class="depo__estrelas" aria-label="<?php printf( esc_attr__( 'Nota %d de 5', 'sao-geronimo' ), $sg_nota ); ?>">
					<?php for ( $sg_e = 0; $sg_e < $sg_nota; $sg_e++ ) : ?>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3 6.5 7 .9-5 4.8 1.3 6.8L12 17.8 5.7 21l1.3-6.8-5-4.8 7-.9L12 2Z"/></svg>
					<?php endfor; ?>
				</div>
				<blockquote><?php echo esc_html( $sg_d['texto'] ); ?></blockquote>
				<figcaption>
					<b><?php echo esc_html( isset( $sg_d['nome'] ) ? $sg_d['nome'] : '' ); ?></b>
					<?php if ( ! empty( $sg_d['local'] ) ) : ?>
						<span><?php echo esc_html( $sg_d['local'] ); ?></span>
					<?php endif; ?>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</div></section>
