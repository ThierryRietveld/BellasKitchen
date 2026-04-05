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
?>

<?php while ( have_posts() ) : the_post(); ?>
	<?php
	$post_id      = get_the_ID();
	$description  = get_post_meta( $post_id, 'recipe_description', true );
	$servings     = get_post_meta( $post_id, 'recipe_servings', true );
	$prep_time    = (int) get_post_meta( $post_id, 'recipe_prep_time', true );
	$cooking_time = (int) get_post_meta( $post_id, 'recipe_cooking_time', true );
	$difficulty   = get_post_meta( $post_id, 'recipe_difficulty', true );
	$meal_type    = get_post_meta( $post_id, 'recipe_meal_type', true );
	$ingredients  = get_post_meta( $post_id, 'recipe_ingredients', true );
	$instructions = get_post_meta( $post_id, 'recipe_instructions', true );
	$main_id      = (int) get_post_meta( $post_id, 'recipe_main_image_id', true );
	$main_url     = $main_id ? wp_get_attachment_image_url( $main_id, 'large' ) : '';
	$main_alt     = $main_id ? get_post_meta( $main_id, '_wp_attachment_image_alt', true ) : '';
	?>

	<article id="recipe-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Hero Image -->
		<div class="relative w-full overflow-hidden bg-rose-50 dark:bg-slate-900">
			<?php if ( $main_url ) : ?>
				<img src="<?php echo esc_url( $main_url ); ?>"
				     alt="<?php echo esc_attr( $main_alt ?: get_the_title() ); ?>"
				     class="h-auto w-full object-cover md:max-h-[500px]">
			<?php elseif ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', [ 'class' => 'h-auto w-full object-cover md:max-h-[500px]' ] ); ?>
			<?php else : ?>
				<div class="flex h-96 items-center justify-center bg-slate-100 text-6xl dark:bg-slate-800">&#127869;</div>
			<?php endif; ?>
		</div>

		<!-- Content -->
		<div class="bg-slate-50 dark:bg-slate-950">
			<div class="container mx-auto max-w-3xl px-5 py-12 md:px-8">
				<!-- Header -->
				<header class="space-y-6">
					<h1 class="font-display text-5xl font-semibold leading-tight text-stone-900 dark:text-slate-100 md:text-6xl">
						<?php the_title(); ?>
					</h1>

					<?php if ( $description ) : ?>
						<p class="text-xl leading-8 text-stone-600 dark:text-slate-300">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

					<!-- Meta Info -->
					<div class="flex flex-wrap gap-3">
						<?php if ( $meal_type ) : ?>
							<span class="inline-flex items-center rounded-full bg-peach-100 px-4 py-2 text-sm font-semibold text-orange-700">
								<?php echo esc_html( ucfirst( $meal_type ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $difficulty ) : ?>
							<span class="inline-flex items-center rounded-full bg-berry-100 px-4 py-2 text-sm font-semibold text-rose-700">
								<?php echo esc_html( ucfirst( $difficulty ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $prep_time ) : ?>
							<span class="inline-flex items-center rounded-full border border-rose-100 bg-white px-4 py-2 text-sm text-stone-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
								Prep: <span class="ml-2 font-semibold"><?php echo esc_html( $prep_time ); ?> min</span>
							</span>
						<?php endif; ?>

						<?php if ( $cooking_time ) : ?>
							<span class="inline-flex items-center rounded-full border border-rose-100 bg-white px-4 py-2 text-sm text-stone-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
								Cook: <span class="ml-2 font-semibold"><?php echo esc_html( $cooking_time ); ?> min</span>
							</span>
						<?php endif; ?>
					</div>
				</header>

				<!-- Main Content Grid -->
				<div class="mt-12 grid gap-8 lg:grid-cols-3">
					<!-- Ingredients -->
					<?php if ( is_array( $ingredients ) && ! empty( $ingredients ) ) : ?>
						<section class="lg:col-span-1">
							<h2 class="font-display text-2xl font-semibold text-stone-900 dark:text-slate-100">Ingrediënten</h2>
							<div class="mt-4 space-y-3 rounded-2xl border border-rose-100 bg-white/80 p-6 dark:border-slate-700 dark:bg-slate-900/90">
								<ul class="space-y-3">
									<?php foreach ( $ingredients as $ingredient ) : ?>
										<li class="flex items-start gap-3 text-stone-700 dark:text-slate-300">
											<span class="mt-1 inline-flex h-2 w-2 flex-shrink-0 rounded-full bg-rose-400"></span>
											<div>
												<?php if ( ! empty( $ingredient['quantity'] ) || ! empty( $ingredient['unit'] ) ) : ?>
													<span class="font-semibold">
														<?php echo esc_html( $ingredient['quantity'] ); ?>
														<?php echo esc_html( $ingredient['unit'] ); ?>
													</span>
												<?php endif; ?>
												<span class="text-stone-600 dark:text-slate-300">
													<?php echo esc_html( $ingredient['item'] ); ?>
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
							<h2 class="font-display text-2xl font-semibold text-stone-900 dark:text-slate-100">Bereidingswijze</h2>
							<ol class="mt-4 space-y-6">
								<?php foreach ( $instructions as $index => $step ) : ?>
									<li class="flex gap-4">
										<span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200 font-semibold text-stone-900 dark:bg-slate-700 dark:text-slate-100">
											<?php echo esc_html( $index + 1 ); ?>
										</span>
										<div class="pt-1 text-base leading-7 text-stone-700 dark:text-slate-300">
											<?php echo wp_kses_post( nl2br( $step['text'] ) ); ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ol>
						</section>
					<?php endif; ?>
				</div>

				<!-- Extra Notes -->
				<?php
				$content = get_the_content();
				if ( $content ) :
				?>
					<section class="mt-12 border-t border-rose-100 pt-8 dark:border-slate-700">
						<h2 class="font-display text-2xl font-semibold text-stone-900 dark:text-slate-100">Tips & Opmerkingen</h2>
						<div class="mt-6 space-y-4 text-base leading-7 text-stone-700 dark:text-slate-300">
							<?php the_content(); ?>
						</div>
					</section>
				<?php endif; ?>

				<!-- Back Link -->
				<div class="mt-12 border-t border-rose-100 pt-8 dark:border-slate-700">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'recipe' ) ); ?>" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
						<span>&larr;</span> Terug naar alle recepten
					</a>
				</div>
			</div>
		</div>
	</article>

<?php endwhile; ?>

<?php get_footer();
