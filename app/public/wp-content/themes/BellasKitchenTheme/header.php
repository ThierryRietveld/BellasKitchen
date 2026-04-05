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
	<script>
		(function() {
			var savedTheme = localStorage.getItem('bellas-theme');
			var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			var shouldUseDark = savedTheme ? savedTheme === 'dark' : prefersDark;
			document.documentElement.classList.toggle('dark', shouldUseDark);
		})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-white font-sans text-slate-800 transition-colors dark:bg-slate-950 dark:text-slate-100' ); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="masthead" class="site-header border-b border-slate-200 bg-white transition-colors dark:border-slate-800 dark:bg-slate-900">
			<div class="container flex flex-col items-start justify-between gap-4 py-4 md:flex-row md:items-center">
				<div class="site-branding">
					<h1 class="site-title text-2xl font-display font-bold text-slate-900 dark:text-slate-100"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
					<p class="site-description text-sm text-slate-600 dark:text-slate-400"><?php bloginfo( 'description' ); ?></p>
				</div>
				<div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center md:gap-4">
					<nav id="site-navigation" class="main-navigation w-full md:w-auto">
						<?php
						bellas_kitchen_render_primary_menu();
						?>
					</nav>
					<button type="button" id="theme-toggle" class="inline-flex w-auto items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700" aria-label="Wissel tussen licht en donker thema">
						<span id="theme-toggle-icon" aria-hidden="true">🌙</span>
						<span id="theme-toggle-text">Donker</span>
					</button>
				</div>
			</div>
		</header>
		<main id="main" class="site-main">
