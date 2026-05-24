<?php
/**
 * Footer template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		</main>
		<footer id="colophon" class="site-footer border-t border-slate-200 bg-slate-50 py-8 dark:border-night-borderMuted dark:bg-night-surface">
			<div class="container">
				<div class="site-info text-sm text-slate-600 dark:text-night-subtle">
					<p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
				</div>
			</div>
		</footer>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
