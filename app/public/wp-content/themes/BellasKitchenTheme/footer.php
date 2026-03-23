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
		<footer id="colophon" class="site-footer">
			<div class="container">
				<div class="site-info">
					<p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
				</div>
			</div>
		</footer>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
