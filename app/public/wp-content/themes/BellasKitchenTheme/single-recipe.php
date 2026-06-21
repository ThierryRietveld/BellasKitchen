<?php
/**
 * Single Recipe template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$repository  = bellas_kitchen_get_recept_repository();
$recipe      = $repository ? $repository->findBySlug( bellas_kitchen_get_current_recept_slug() ) : null;
$archive_url = bellas_kitchen_get_recepten_archive_url();

if ( ! $recipe ) :
	?>
	<div class="bg-slate-50 dark:bg-night-page">
		<div class="container mx-auto max-w-3xl px-5 py-16 md:px-8">
			<div class="rounded-3xl border border-rose-100 bg-white/90 p-8 text-center shadow-card dark:border-night-border dark:bg-night-surface/90">
				<h1 class="font-display text-4xl font-semibold text-stone-900 dark:text-night-text">Recept niet gevonden</h1>
				<p class="mt-4 text-base leading-7 text-stone-600 dark:text-night-muted">Dit recept bestaat niet meer of heeft nog geen geldige permalink in de nieuwe receptendatabase.</p>
				<div class="mt-8">
					<a href="<?php echo esc_url( $archive_url ); ?>" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-rose-50 dark:border-night-border dark:bg-night-surface dark:text-night-textSoft dark:hover:bg-night-surfaceElevated">
						<span>&larr;</span> Terug naar alle recepten
					</a>
				</div>
			</div>
		</div>
	</div>
	<?php
	get_footer();
	return;
endif;

$description  = (string) ( $recipe['beschrijving'] ?? '' );
$servings     = (int) ( $recipe['aantal_personen'] ?? 0 );
$duration     = (int) ( $recipe['bereidingstijd'] ?? 0 );
$oven_temp    = (int) ( $recipe['oven_temperatuur'] ?? 0 );
$difficulty   = (string) ( $recipe['moeilijkheid'] ?? '' );
$meal_type    = (string) ( $recipe['soort_gerecht'] ?? '' );
$ingredients  = is_array( $recipe['ingredienten'] ?? null ) ? $recipe['ingredienten'] : array();
$instructions = is_array( $recipe['instructies'] ?? null ) ? $recipe['instructies'] : array();
$main_url     = bellas_kitchen_get_recept_image_url( $recipe, 'large' );
$main_alt     = bellas_kitchen_get_recept_image_alt( $recipe );
?>

	<article id="recept-<?php echo esc_attr( $recipe['id'] ); ?>">
		<!-- Hero Image -->
		<div class="relative w-full overflow-hidden bg-rose-50 dark:bg-night-surface">
			<?php if ( $main_url ) : ?>
				<img src="<?php echo esc_url( $main_url ); ?>"
				     alt="<?php echo esc_attr( $main_alt ); ?>"
				     class="h-auto w-full object-cover md:max-h-[500px]">
			<?php else : ?>
				<div class="flex h-96 items-center justify-center bg-slate-100 text-6xl dark:bg-night-surfaceElevated">&#127869;</div>
			<?php endif; ?>
		</div>

		<!-- Content -->
		<div class="bg-slate-50 dark:bg-night-page">
			<div class="container mx-auto max-w-5xl px-5 py-12 md:px-8">
				<!-- Header -->
				<header class="space-y-6">
					<h1 class="font-display text-5xl font-semibold leading-tight text-stone-900 dark:text-night-text md:text-6xl">
						<?php echo esc_html( $recipe['naam'] ); ?>
					</h1>

					<?php if ( $description ) : ?>
						<p class="text-xl leading-8 text-stone-600 dark:text-night-muted">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

					<!-- Meta Info -->
					<div class="flex flex-wrap gap-3">
						<?php if ( $meal_type ) : ?>
							<span class="inline-flex items-center rounded-full bg-peach-100 px-4 py-2 text-sm font-semibold text-orange-700">
								<?php echo esc_html( bellas_kitchen_format_recept_label( $meal_type ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $difficulty ) : ?>
							<span class="inline-flex items-center rounded-full bg-berry-100 px-4 py-2 text-sm font-semibold text-rose-700">
								<?php echo esc_html( bellas_kitchen_format_recept_label( $difficulty ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $duration ) : ?>
							<span class="inline-flex items-center rounded-full border border-rose-100 bg-white px-4 py-2 text-sm text-stone-700 dark:border-night-border dark:bg-night-surface dark:text-night-muted">
								Bereidingstijd: <span class="ml-2 font-semibold"><?php echo esc_html( bellas_kitchen_format_recept_duration( $duration ) ); ?></span>
							</span>
						<?php endif; ?>

						<?php if ( $oven_temp ) : ?>
							<span class="inline-flex items-center rounded-full border border-rose-100 bg-white px-4 py-2 text-sm text-stone-700 dark:border-night-border dark:bg-night-surface dark:text-night-muted">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/oven_heat.jpeg' ); ?>" alt="Oven" class="h-6 w-6"> <span class="ml-2 font-semibold"><?php echo esc_html( $oven_temp ); ?> °C</span>
							</span>
						<?php endif; ?>

					</div>

					<?php if ( $servings ) : ?>
						<div class="rounded-[1.5rem] border border-rose-100 bg-white/90 p-5 shadow-card dark:border-night-border dark:bg-night-surface/90" data-recipe-servings data-base-servings="<?php echo esc_attr( $servings ); ?>">
							<div class="flex gap-4 flex-row sm:items-center justify-between">
								<div>
									<p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-500">Porties aanpassen</p>
									<p class="mt-2 flex items-baseline gap-2 text-stone-900 dark:text-night-text">
										<span class="font-display text-4xl leading-none" data-servings-count><?php echo esc_html( $servings ); ?></span>
										<span class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-night-muted" data-servings-unit-label><?php echo esc_html( 1 === $servings ? 'persoon' : 'personen' ); ?></span>
									</p>
								</div>

								<div class="inline-flex items-center rounded-full border max-w-[11.5rem] border-rose-200 bg-white shadow-sm dark:border-night-border dark:bg-night-page">
									<button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-l-full text-xl font-semibold text-stone-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-night-textSoft dark:hover:bg-night-surfaceElevated" data-servings-decrease aria-label="Verlaag aantal personen">
										<span aria-hidden="true">&minus;</span>
									</button>
									<div class="min-w-[5rem] px-4 text-center text-base font-semibold text-stone-900 dark:text-night-text" data-servings-display>
										<?php echo esc_html( $servings ); ?>
									</div>
									<button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-r-full text-xl font-semibold text-stone-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-night-textSoft dark:hover:bg-night-surfaceElevated" data-servings-increase aria-label="Verhoog aantal personen">
										<span aria-hidden="true">+</span>
									</button>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</header>

				<!-- Main Content Grid -->
				<div class="mt-12 grid gap-8 lg:grid-cols-3">
					<!-- Ingredients -->
					<?php if ( is_array( $ingredients ) && ! empty( $ingredients ) ) : ?>
						<section class="lg:col-span-1">
							<div class="flex flex-col gap-2">
								<h2 class="font-display text-2xl font-semibold text-stone-900 dark:text-night-text">Ingrediënten</h2>
								<?php if ( $servings ) : ?>
									<p class="text-sm text-stone-600 dark:text-night-muted">Voor <span class="font-semibold text-stone-900 dark:text-night-text" data-servings-summary><?php echo esc_html( bellas_kitchen_format_recept_servings( $servings ) ); ?></span></p>
								<?php endif; ?>
							</div>
							<div class="mt-4 space-y-3 rounded-2xl border border-rose-100 bg-white/80 p-6 dark:border-night-border dark:bg-night-surface/90">
								<ul class="space-y-3">
									<?php foreach ( $ingredients as $ingredient ) : ?>
										<?php
										$quantity = trim( (string) ( $ingredient['quantity'] ?? '' ) );
										$unit_key = (string) ( $ingredient['unit'] ?? '' );
										$unit     = bellas_kitchen_format_recept_unit( $unit_key );
										$amount   = trim( implode( ' ', array_filter( array( $quantity, $unit ) ) ) );
										?>
										<li class="flex items-start gap-3 text-stone-700 dark:text-night-muted" data-ingredient data-base-quantity="<?php echo esc_attr( $quantity ); ?>" data-base-unit-key="<?php echo esc_attr( $unit_key ); ?>" data-base-unit="<?php echo esc_attr( $unit ); ?>" data-base-amount="<?php echo esc_attr( $amount ); ?>">
											<span class="mt-1 inline-flex h-2 w-2 flex-shrink-0 rounded-full bg-rose-400"></span>
											<div>
												<?php if ( '' !== $amount ) : ?>
													<span class="font-semibold" data-ingredient-amount>
														<?php echo esc_html( $amount ); ?>
													</span>
												<?php else : ?>
													<span class="hidden font-semibold" data-ingredient-amount></span>
												<?php endif; ?>
												<span class="text-stone-600 dark:text-night-muted">
													<?php echo esc_html( (string) ( $ingredient['item'] ?? '' ) ); ?>
												</span>
											</div>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</section>
					<?php endif; ?>

					<!-- Instructions -->
					<?php if ( is_array( $instructions ) && ! empty( $instructions ) ) : ?>
						<section class="lg:col-span-2">
							<h2 class="font-display text-2xl font-semibold text-stone-900 dark:text-night-text">Bereidingswijze</h2>
							<ol class="mt-4 space-y-6">
								<?php foreach ( $instructions as $index => $step ) : ?>
									<li class="flex gap-4">
										<span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200 font-semibold text-stone-900 dark:bg-night-surfaceHover dark:text-night-text">
											<?php echo esc_html( $index + 1 ); ?>
										</span>
										<div class="pt-1 text-base leading-7 text-stone-700 dark:text-night-muted">
											<?php echo wp_kses_post( nl2br( (string) ( $step['text'] ?? '' ) ) ); ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ol>
						</section>
					<?php endif; ?>
				</div>

				<?php get_template_part( 'template-parts/divider-heart' ); ?>

				<!-- Back Link -->
				<div class="mt-12">
					<a href="<?php echo esc_url( $archive_url ); ?>" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-rose-50 dark:border-night-border dark:bg-night-surface dark:text-night-textSoft dark:hover:bg-night-surfaceElevated">
						<span>&larr;</span> Terug naar alle recepten
					</a>
				</div>
			</div>
		</div>
	</article>

<?php get_footer();
