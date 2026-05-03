<?php
/**
 * Front page template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php
$front_page = null;

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$front_page = array(
			'title'      => get_the_title(),
			'content'    => get_the_content(),
			'permalink'  => get_permalink(),
			'thumbnail'  => get_the_post_thumbnail( get_the_ID(), 'large', array( 'class' => 'h-full w-full object-cover' ) ),
		);
	}
	wp_reset_postdata();
}

$repository         = bellas_kitchen_get_recept_repository();
$latest_recipes     = $repository ? $repository->getLatest( 3 ) : array();
$recipe_archive_url = bellas_kitchen_get_recepten_archive_url();
?>

<div class="relative overflow-hidden bg-slate-50 text-stone-800 dark:bg-slate-950 dark:text-slate-200">

	<div class="container relative px-5 py-8 md:px-8 md:py-10 lg:py-12">
		<section class="grid gap-6 lg:grid-cols-[minmax(0,1.08fr)_minmax(280px,0.72fr)] lg:items-center">
			<div class="space-y-4">
				<div class="space-y-3">
					<h1 class="max-w-3xl font-display text-4xl font-semibold leading-[0.95] text-balance text-stone-900 dark:text-slate-100 md:text-5xl lg:text-6xl">
						<?php echo esc_html( $front_page['title'] ?? get_bloginfo( 'name' ) ); ?>
					</h1>
					<div class="max-w-2xl text-sm leading-7 text-stone-600 dark:text-slate-300 md:text-base">
						<?php echo wp_kses_post( wpautop( $front_page['content'] ?? get_bloginfo( 'description' ) ) ); ?>
					</div>
				</div>

				<div class="flex flex-col gap-3 sm:flex-row sm:items-center">
					<?php if ( $recipe_archive_url ) : ?>
						<a href="<?php echo esc_url( $recipe_archive_url ); ?>" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-white px-5 py-3 text-sm font-bold text-stone-900 shadow-card transition hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">Bekijk alle recepten</a>
					<?php endif; ?>
						<a href="#latest-recipes" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-white/75 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:bg-slate-800">Nieuwste gerechten</a>
				</div>
			</div>

			<div class="relative">
				<div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/80 shadow-glow backdrop-blur dark:border-slate-700 dark:bg-slate-900/90">
					<?php if ( ! empty( $front_page['thumbnail'] ) ) : ?>
						<div class="aspect-[4/3] overflow-hidden lg:aspect-[5/4]">
							<?php echo wp_kses_post( $front_page['thumbnail'] ); ?>
						</div>
					<?php else : ?>
						<div class="flex aspect-[4/3] items-center justify-center bg-slate-100 p-8 text-center text-stone-900 dark:bg-slate-800 dark:text-slate-100 lg:aspect-[5/4]">
							<div>
								<p class="text-sm font-semibold uppercase tracking-[0.28em] text-rose-500">Welkom</p>
								<p class="mt-4 font-display text-4xl leading-none">Kook iets moois</p>
							</div>
						</div>
					<?php endif; ?>
					<div class="space-y-2 border-t border-rose-100 bg-white/90 p-4 md:p-5 dark:border-slate-700 dark:bg-slate-900/90">
						<p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-rose-500">Van de thuiskeuken</p>
						<p class="font-display text-xl text-stone-900 dark:text-slate-100">Zachte kleuren, verse recepten en meteen iets lekkers in beeld.</p>
					</div>
				</div>
			</div>
		</section>

		<section id="latest-recipes" class="mt-10 space-y-6 lg:mt-12">
			<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
				<div class="space-y-3">
					<p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-500">Nieuwste recepten</p>
					<h2 class="font-display text-3xl text-stone-900 dark:text-slate-100 md:text-4xl">Drie verse ideeën voor vanavond</h2>
					<p class="max-w-2xl text-sm leading-7 text-stone-600 dark:text-slate-300 md:text-base">De nieuwste recepten staan nu sneller in beeld, zodat bezoekers direct kunnen kiezen.</p>
				</div>
				<?php if ( $recipe_archive_url ) : ?>
					<a href="<?php echo esc_url( $recipe_archive_url ); ?>" class="inline-flex items-center gap-2 self-start rounded-full border border-rose-200 bg-white/80 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:bg-slate-800">Naar het receptenoverzicht <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $latest_recipes ) ) : ?>
				<div class="grid gap-6 lg:grid-cols-3">
					<?php
					$card_index = 0;
					foreach ( $latest_recipes as $recept ) :
						$image_url       = bellas_kitchen_get_recept_image_url( $recept, 'large' );
						$image_alt       = bellas_kitchen_get_recept_image_alt( $recept );
						$card_class      = 0 === $card_index ? 'lg:col-span-2' : '';
						$link_class      = 0 === $card_index ? 'flex flex-col lg:grid lg:grid-cols-[minmax(280px,1.05fr)_minmax(0,0.95fr)]' : 'flex flex-col';
						$image_class     = 0 === $card_index ? 'aspect-[4/3] lg:aspect-auto lg:min-h-[22rem]' : 'aspect-[4/3]';
						$card_body_class = 0 === $card_index ? 'flex flex-1 flex-col justify-between gap-5 p-6 md:p-7 lg:p-8' : 'flex flex-1 flex-col justify-between gap-4 p-6';
						$title_class     = 0 === $card_index ? 'font-display text-3xl leading-tight text-stone-900 dark:text-slate-100 md:text-4xl' : 'font-display text-2xl leading-tight text-stone-900 dark:text-slate-100';
						$description_text = wp_trim_words( (string) ( $recept['beschrijving'] ?? '' ), 24 );
						?>
						<article id="recept-<?php echo esc_attr( $recept['id'] ); ?>" class="group overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 shadow-card backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-rose-200 dark:border-slate-700 dark:bg-slate-900/90 dark:hover:border-slate-600 <?php echo esc_attr( $card_class ); ?>">
							<a href="<?php echo esc_url( bellas_kitchen_get_recept_url( $recept ) ); ?>" class="<?php echo esc_attr( $link_class ); ?> h-full">
								<div class="<?php echo esc_attr( $image_class ); ?> overflow-hidden bg-rose-50">
									<?php if ( $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
									<?php else : ?>
										<div class="flex h-full items-center justify-center bg-slate-100 text-5xl text-stone-900 dark:bg-slate-800 dark:text-slate-100">&#127869;</div>
									<?php endif; ?>
								</div>
								<div class="<?php echo esc_attr( $card_body_class ); ?>">
									<div class="space-y-4">
										<div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">
											<?php if ( ! empty( $recept['soort_gerecht'] ) ) : ?>
												<span class="rounded-full bg-peach-100 px-3 py-2 text-orange-700"><?php echo esc_html( bellas_kitchen_format_recept_label( (string) $recept['soort_gerecht'] ) ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $recept['moeilijkheid'] ) ) : ?>
												<span class="rounded-full bg-berry-100 px-3 py-2 text-rose-700"><?php echo esc_html( bellas_kitchen_format_recept_label( (string) $recept['moeilijkheid'] ) ); ?></span>
											<?php endif; ?>
										</div>
										<h3 class="<?php echo esc_attr( $title_class ); ?>"><?php echo esc_html( $recept['naam'] ); ?></h3>
										<p class="text-sm leading-7 text-stone-600 dark:text-slate-300 md:text-base">
											<?php echo esc_html( $description_text ); ?>
										</p>
									</div>

									<div class="space-y-4">
										<div class="flex flex-wrap items-center gap-3 text-sm text-stone-700 dark:text-slate-300">
											<?php if ( ! empty( $recept['bereidingstijd'] ) ) : ?>
												<span class="inline-flex items-center rounded-full border border-rose-100 bg-rose-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800"><?php echo esc_html( bellas_kitchen_format_recept_duration( (int) $recept['bereidingstijd'] ) ); ?></span>
											<?php endif; ?>
											<span class="inline-flex items-center rounded-full border border-transparent bg-slate-100 px-3 py-2 text-stone-800 dark:bg-slate-800 dark:text-slate-100">Bekijk recept</span>
										</div>
										<span class="inline-flex items-center text-sm font-semibold text-rose-500 transition group-hover:text-rose-600">Open recept <span class="ml-2" aria-hidden="true">&rarr;</span></span>
									</div>
								</div>
							</a>
						</article>
						<?php
						$card_index++;
					endforeach;
					?>
				</div>
			<?php else : ?>
				<div class="rounded-[2rem] border border-dashed border-rose-200 bg-white/80 p-8 text-center text-stone-600 shadow-card dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-300">
					<p class="font-display text-3xl text-stone-900 dark:text-slate-100">Nog geen recepten gepubliceerd</p>
					<p class="mt-3 text-base leading-7">Zodra je je eerste recepten toevoegt, verschijnen hier automatisch de nieuwste drie gerechten.</p>
				</div>
			<?php endif; ?>
		</section>
	</div>
</div>

<?php get_footer();
