<?php
/**
 * Recipe archive template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$repository   = bellas_kitchen_get_recept_repository();
$current_page = bellas_kitchen_get_recepten_archive_page();
$archive_url  = bellas_kitchen_get_recepten_archive_url();
$pagination   = $repository ? $repository->getPaginated( $current_page, 9 ) : array(
	'items'        => array(),
	'total_items'  => 0,
	'per_page'     => 9,
	'current_page' => 1,
	'total_pages'  => 1,
);
$recepten     = $pagination['items'];
$total_pages  = (int) $pagination['total_pages'];
$current_page = (int) $pagination['current_page'];
$pagination_ui = '';

if ( $total_pages > 1 ) {
	$pagination_ui = paginate_links(
		array(
			'base'      => trailingslashit( $archive_url ) . '%_%',
			'format'    => 'pagina/%#%/',
			'current'   => $current_page,
			'total'     => $total_pages,
			'type'      => 'list',
			'prev_text' => '&larr; Vorige',
			'next_text' => 'Volgende &rarr;',
		)
	);
}
?>

<div class="container px-5 py-10 md:px-8">

	<header class="archive-header mb-6">
		<h1 class="archive-title font-display text-4xl font-semibold text-slate-900 dark:text-slate-100">Recepten</h1>
		<p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">Alle recepten worden rechtstreeks uit de nieuwe receptentabellen geladen.</p>
	</header>

	<?php if ( ! empty( $recepten ) ) : ?>

		<div class="recipes-grid grid gap-6 md:grid-cols-2 lg:grid-cols-3">
			<?php foreach ( $recepten as $recept ) : ?>

				<?php
				$main_url    = bellas_kitchen_get_recept_image_url( $recept, 'medium' );
				$main_alt    = bellas_kitchen_get_recept_image_alt( $recept );
				$duration    = (int) ( $recept['bereidingstijd'] ?? 0 );
				$difficulty  = (string) ( $recept['moeilijkheid'] ?? '' );
				$meal_type   = (string) ( $recept['soort_gerecht'] ?? '' );
				$description = (string) ( $recept['beschrijving'] ?? '' );
				?>

				<article id="recept-<?php echo esc_attr( $recept['id'] ); ?>" class="recipe-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 dark:border-slate-800 dark:bg-slate-900">
					<a href="<?php echo esc_url( bellas_kitchen_get_recept_url( $recept ) ); ?>" class="recipe-card-link block">

						<div class="recipe-card-image aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
							<?php if ( $main_url ) : ?>
								<img src="<?php echo esc_url( $main_url ); ?>"
								     alt="<?php echo esc_attr( $main_alt ); ?>"
								     class="h-full w-full object-cover">
							<?php else : ?>
								<div class="recipe-card-placeholder flex h-full items-center justify-center text-5xl">&#127869;</div>
							<?php endif; ?>
						</div>

						<div class="recipe-card-body space-y-4 p-5">
							<h2 class="recipe-card-title font-display text-2xl font-semibold text-slate-900 dark:text-slate-100"><?php echo esc_html( $recept['naam'] ); ?></h2>

							<?php if ( $description ) : ?>
								<p class="recipe-card-description text-slate-700 dark:text-slate-300"><?php echo esc_html( wp_trim_words( $description, 15 ) ); ?></p>
							<?php endif; ?>

							<div class="recipe-card-meta flex flex-wrap gap-2 text-sm">
								<?php if ( $duration > 0 ) : ?>
									<span class="meta-item rounded-full bg-amber-100 px-3 py-1 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"><?php echo esc_html( bellas_kitchen_format_recept_duration( $duration ) ); ?></span>
								<?php endif; ?>
								<?php if ( $difficulty ) : ?>
									<span class="meta-item rounded-full bg-rose-100 px-3 py-1 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"><?php echo esc_html( bellas_kitchen_format_recept_label( $difficulty ) ); ?></span>
								<?php endif; ?>
								<?php if ( $meal_type ) : ?>
									<span class="meta-item rounded-full bg-sky-100 px-3 py-1 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"><?php echo esc_html( bellas_kitchen_format_recept_label( $meal_type ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>

					</a>
				</article>

			<?php endforeach; ?>
		</div>

		<?php if ( $pagination_ui ) : ?>
			<div class="pagination mt-8 text-slate-700 dark:text-slate-300">
				<?php echo wp_kses_post( $pagination_ui ); ?>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<p class="no-recipes text-slate-700 dark:text-slate-300">Nog geen recepten gevonden. Zodra je recepten toevoegt in de nieuwe plugin verschijnen ze hier automatisch.</p>

	<?php endif; ?>

</div>

<?php get_footer();
