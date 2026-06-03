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

$repository    = bellas_kitchen_get_recept_repository();
$recepten      = $repository ? $repository->getAll() : array();
$total_recipes = count( $recepten );
?>

<div class="container px-5 py-10 md:px-8" data-recipe-archive>

	<header class="archive-header mb-6 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
		<div>
			<h1 class="archive-title font-display text-4xl font-semibold text-slate-900 dark:text-night-text">Recepten</h1>
			<p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-night-muted">Alle recepten van Bella.</p>
		</div>

		<?php if ( ! empty( $recepten ) ) : ?>
			<div class="w-full md:max-w-sm">
				<label for="recipe-archive-search" class="sr-only">Zoek recepten</label>
				<input id="recipe-archive-search" type="search" class="w-full rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-ember-500 focus:ring-4 focus:ring-ember-100 dark:border-night-border dark:bg-night-surface dark:text-night-text dark:placeholder:text-night-placeholder dark:focus:border-amber-300 dark:focus:ring-night-ring" placeholder="Zoek recepten" autocomplete="off" data-recipe-search-input>
				<p class="mt-2 text-sm text-slate-500 dark:text-night-subtle" data-recipe-search-count aria-live="polite">
					<?php
					echo esc_html(
						sprintf(
							_n( '%d recept', '%d recepten', $total_recipes, 'bellas-kitchen-theme' ),
							$total_recipes
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
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
				$search_text = implode(
					' ',
					array_filter(
						array(
							(string) ( $recept['naam'] ?? '' ),
							$description,
							$difficulty,
							bellas_kitchen_format_recept_label( $difficulty ),
							$meal_type,
							bellas_kitchen_format_recept_label( $meal_type ),
							$duration > 0 ? bellas_kitchen_format_recept_duration( $duration ) : '',
						)
					)
				);
				?>

				<article id="recept-<?php echo esc_attr( $recept['id'] ); ?>" class="recipe-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 dark:border-night-borderMuted dark:bg-night-surface" data-recipe-card data-recipe-search="<?php echo esc_attr( $search_text ); ?>">
					<a href="<?php echo esc_url( bellas_kitchen_get_recept_url( $recept ) ); ?>" class="recipe-card-link block">

						<div class="recipe-card-image aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-night-surfaceElevated">
							<?php if ( $main_url ) : ?>
								<img src="<?php echo esc_url( $main_url ); ?>"
								     alt="<?php echo esc_attr( $main_alt ); ?>"
								     class="h-full w-full object-cover">
							<?php else : ?>
								<div class="recipe-card-placeholder flex h-full items-center justify-center text-5xl">&#127869;</div>
							<?php endif; ?>
						</div>

						<div class="recipe-card-body space-y-4 p-5">
							<h2 class="recipe-card-title font-display text-2xl font-semibold text-slate-900 dark:text-night-text"><?php echo esc_html( $recept['naam'] ); ?></h2>

							<?php if ( $description ) : ?>
								<p class="recipe-card-description text-slate-700 dark:text-night-muted"><?php echo esc_html( wp_trim_words( $description, 15 ) ); ?></p>
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

		<p class="mt-8 hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-slate-700 dark:border-night-border dark:bg-night-surface dark:text-night-muted" data-recipe-search-empty>Geen recepten gevonden voor je zoekopdracht.</p>

	<?php else : ?>

		<p class="no-recipes text-slate-700 dark:text-night-muted">Nog geen recepten gevonden. Zodra je recepten toevoegt in de nieuwe plugin verschijnen ze hier automatisch.</p>

	<?php endif; ?>

</div>

<?php get_footer();
