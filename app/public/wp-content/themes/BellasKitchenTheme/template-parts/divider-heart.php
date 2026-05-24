<?php
/**
 * Decorative heart divider.
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$divider_relative_path = '/assets/images/divider-heart.svg';
$divider_path          = get_template_directory() . $divider_relative_path;
$divider_url           = get_template_directory_uri() . $divider_relative_path;
?>

<div class="my-10 flex items-center justify-center" aria-hidden="true">
	<?php if ( file_exists( $divider_path ) ) : ?>
		<img
			src="<?php echo esc_url( $divider_url ); ?>"
			alt=""
			class="h-10 w-auto max-w-full opacity-80 dark:opacity-90"
			loading="lazy"
			decoding="async"
		>
	<?php else : ?>
		<div class="flex w-full max-w-3xl items-center gap-4 text-rose-400 dark:text-rose-300">
			<span class="h-px flex-1 bg-rose-200 dark:bg-night-surfaceHover"></span>
			<span class="text-xl leading-none">&hearts;</span>
			<span class="h-px flex-1 bg-rose-200 dark:bg-night-surfaceHover"></span>
		</div>
	<?php endif; ?>
</div>
