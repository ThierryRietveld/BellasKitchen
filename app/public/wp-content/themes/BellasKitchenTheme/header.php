<?php
/**
 * Header template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="masthead" class="site-header border-b border-slate-200 bg-white">
			<div class="container flex flex-col items-start justify-between gap-4 py-4 md:flex-row md:items-center">
				<div class="site-branding">
					<h1 class="site-title text-2xl font-display font-bold text-slate-900"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
					<p class="site-description text-sm text-slate-600"><?php bloginfo( 'description' ); ?></p>
				</div>
				<nav id="site-navigation" class="main-navigation w-full md:w-auto">
					<?php
					bellas_kitchen_render_primary_menu();
					?>
				</nav>
			</div>
		</header>
		<main id="main" class="site-main">
