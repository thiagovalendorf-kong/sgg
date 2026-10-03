<?php
/**
 * Cabeçalho do site.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="pular" href="#conteudo"><?php esc_html_e( 'Pular para o conteúdo', 'sao-geronimo' ); ?></a>

<?php sg_faixa_topo(); ?>

<header class="cab" data-cab>
	<div class="wrap cab__barra">
		<button type="button" class="burger" data-abrir-menu aria-label="<?php esc_attr_e( 'Abrir menu', 'sao-geronimo' ); ?>" aria-expanded="false" aria-controls="menu-mob">
			<?php echo sg_icone( 'menu', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<?php sg_marca(); ?>

		<nav class="menu" aria-label="<?php esc_attr_e( 'Menu principal', 'sao-geronimo' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'principal',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'depth'          => 1,
				'fallback_cb'    => 'sg_menu_padrao',
			) );
			?>
		</nav>

		<div class="icones">
			<button type="button" class="icone" data-abrir-busca aria-label="<?php esc_attr_e( 'Buscar produtos', 'sao-geronimo' ); ?>">
				<?php echo sg_icone( 'busca' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>

			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="icone" aria-label="<?php esc_attr_e( 'Minha conta', 'sao-geronimo' ); ?>">
					<?php echo sg_icone( 'conta' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			<?php endif; ?>

			<?php if ( 'sim' === sg_opt( 'mostrar_favoritos', 'sim' ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/favoritos/' ) ); ?>" class="icone" aria-label="<?php esc_attr_e( 'Favoritos', 'sao-geronimo' ); ?>">
					<?php echo sg_icone( 'coracao' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			<?php endif; ?>

			<?php if ( function_exists( 'WC' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="icone" data-sacola aria-label="<?php esc_attr_e( 'Sacola', 'sao-geronimo' ); ?>">
					<?php echo sg_icone( 'sacola' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="icone__n" data-n <?php echo WC()->cart && WC()->cart->get_cart_contents_count() ? '' : 'style="display:none"'; ?>>
						<?php echo WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?>
					</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php
sg_painel_busca();
sg_menu_mobile();
?>

<main id="conteudo">
