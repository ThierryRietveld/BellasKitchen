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
<body <?php body_class( 'bg-white font-sans text-slate-800 transition-colors dark:bg-night-page dark:text-night-text' ); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="masthead" class="site-header border-b border-slate-200 bg-white transition-colors dark:border-night-borderMuted dark:bg-night-surface">
			<div class="container flex flex-col items-start justify-between py-1 md:flex-row md:items-center">
				<div class="site-branding">
					<?php bellas_kitchen_render_site_branding(); ?>
				</div>
				<div class="flex w-full gap-3 md:w-auto flex-row md:items-center md:gap-4">
					<nav id="site-navigation" class="main-navigation md:w-auto flex-1">
						<?php
						bellas_kitchen_render_primary_menu();
						?>
					</nav>
					<button type="button" id="theme-toggle" class="inline-flex w-auto items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-night-border dark:bg-night-surfaceElevated dark:text-night-text dark:hover:bg-night-surfaceHover" aria-label="Wissel tussen licht en donker thema">
						<span id="theme-toggle-icon" aria-hidden="true">🌙</span>
						<span id="theme-toggle-text">Donker</span>
					</button>
				</div>
			</div>
		</header>
		<main id="main" class="site-main">
