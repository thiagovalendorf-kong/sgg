<?php
/**
 * Banner rotativo da home.
 *
 * A marcação segue exatamente a do site original: cada banner tem um fundo
 * desfocado atrás (hero__fundo), para a imagem aparecer inteira sem faixas
 * pretas nas laterais. Os banners vêm do painel.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_banners = sg_opt( 'banners', array() );
if ( ! is_array( $sg_banners ) ) {
	$sg_banners = array();
}
$sg_banners = array_values( array_filter( $sg_banners, function ( $b ) {
	return ! empty( $b['img'] );
} ) );

$sg_tempo = max( 2000, (int) sg_opt( 'banner_tempo', 7000 ) );

// Sem banner e sem frase não há o que mostrar: nada de faixa vazia colada no topo.
// A home sempre precisa de um <h1> (acessibilidade e SEO), então ele sai só para leitores de tela.
if ( ! $sg_banners && ! sg_opt( 'hero_frase' ) ) {
	echo '<h1 class="sr">' . esc_html( sg_opt( 'logo_texto', get_bloginfo( 'name' ) ) ) . '</h1>';
	return;
}
?>
<section class="hero"><div class="wrap">

	<?php if ( $sg_banners ) : ?>
		<div class="hero__janela" data-hero data-t="<?php echo esc_attr( $sg_tempo ); ?>">
			<div class="hero__trilho" data-trilho>
				<?php
				foreach ( $sg_banners as $sg_i => $sg_b ) :
					$sg_link = ! empty( $sg_b['link'] ) ? $sg_b['link'] : '';
					$sg_alt  = ! empty( $sg_b['alt'] ) ? $sg_b['alt'] : get_bloginfo( 'name' );
					$sg_mob  = ! empty( $sg_b['img_mob'] ) ? $sg_b['img_mob'] : '';
					$sg_tag  = $sg_link ? 'a' : 'div';
					?>
					<<?php echo esc_attr( $sg_tag ); ?> <?php echo $sg_link ? 'href="' . esc_url( $sg_link ) . '"' : ''; ?> class="hero__slide hero__slide--banner">
						<span class="hero__fundo" style="background-image:url('<?php echo esc_url( $sg_b['img'] ); ?>')"></span>
						<?php if ( $sg_mob ) : ?>
							<picture>
								<source media="(max-width: 760px)" srcset="<?php echo esc_url( $sg_mob ); ?>">
								<img src="<?php echo esc_url( $sg_b['img'] ); ?>" alt="<?php echo esc_attr( $sg_alt ); ?>"
									<?php echo 0 === $sg_i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
							</picture>
						<?php else : ?>
							<img src="<?php echo esc_url( $sg_b['img'] ); ?>" alt="<?php echo esc_attr( $sg_alt ); ?>"
								<?php echo 0 === $sg_i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
						<?php endif; ?>
					</<?php echo esc_attr( $sg_tag ); ?>>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $sg_banners ) > 1 ) : ?>
				<button class="hero__nav hero__nav--ant" data-ant aria-label="<?php esc_attr_e( 'Banner anterior', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'ant', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button class="hero__nav hero__nav--prox" data-prox aria-label="<?php esc_attr_e( 'Próximo banner', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'prox', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<div class="hero__pontos" data-pontos>
					<?php foreach ( $sg_banners as $sg_i => $sg_b ) : ?>
						<button class="hero__ponto<?php echo 0 === $sg_i ? ' on' : ''; ?>" data-ir="<?php echo esc_attr( $sg_i ); ?>" aria-label="<?php printf( esc_attr__( 'Banner %d', 'sao-geronimo' ), (int) $sg_i + 1 ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! sg_opt( 'hero_frase' ) ) : ?>
		<h1 class="sr"><?php echo esc_html( sg_opt( 'logo_texto', get_bloginfo( 'name' ) ) ); ?></h1>
	<?php else : ?>
		<div class="hero__abaixo">
			<div>
				<h1 class="h-xl hero__frase"><?php echo wp_kses_post( sg_opt( 'hero_frase' ) ); ?></h1>

				<?php if ( sg_opt( 'hero_sub' ) ) : ?>
					<p class="texto-mudo hero__sub-txt"><?php echo esc_html( sg_opt( 'hero_sub' ) ); ?></p>
				<?php endif; ?>

				<?php
				// Botão principal: por padrão leva para a página da loja (todos os produtos).
				$sg_loja_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
				$sg_b1_txt   = sg_opt( 'hero_btn1_txt', __( 'Explorar produtos', 'sao-geronimo' ) );
				$sg_b1_url   = sg_opt( 'hero_btn1_url', '' );
				if ( ! $sg_b1_url || '#' === $sg_b1_url ) {
					$sg_b1_url = $sg_loja_url;
				}
				$sg_b2_txt = sg_opt( 'hero_btn2_txt' );
				$sg_b2_url = sg_opt( 'hero_btn2_url', '' );
				if ( ! $sg_b2_url ) {
					$sg_b2_url = '#';
				}
				?>
				<div class="hero__acoes">
					<a href="<?php echo esc_url( $sg_b1_url ); ?>" class="btn btn--azul"><?php echo esc_html( $sg_b1_txt ); ?> <?php echo sg_icone( 'seta', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php if ( $sg_b2_txt ) : ?>
						<a href="<?php echo esc_url( $sg_b2_url ); ?>" class="btn btn--vazado"><?php echo esc_html( $sg_b2_txt ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php
			$sg_estats = array();
			for ( $sg_n = 1; $sg_n <= 3; $sg_n++ ) {
				$sg_v = sg_opt( 'estat' . $sg_n . '_n' );
				if ( $sg_v ) {
					$sg_estats[] = array( $sg_v, sg_opt( 'estat' . $sg_n . '_t' ) );
				}
			}
			if ( $sg_estats ) :
				?>
				<div class="estats">
					<?php foreach ( $sg_estats as $sg_e ) : ?>
						<div class="estat"><b><?php echo esc_html( $sg_e[0] ); ?></b><span><?php echo esc_html( $sg_e[1] ); ?></span></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</div></section>
