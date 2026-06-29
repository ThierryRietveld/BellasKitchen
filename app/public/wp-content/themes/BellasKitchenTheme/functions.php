<?php
/**
 * Bellas Kitchen Theme Functions
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup theme
 */
function bellas_kitchen_theme_setup() {
	// Add theme support
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	// Register navigation menu
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'bellas-kitchen-theme' ),
	) );
}
add_action( 'after_setup_theme', 'bellas_kitchen_theme_setup' );

/**
 * Register Customizer settings for mode-specific logos.
 */
function bellas_kitchen_customize_register( \WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_setting(
		'bellas_kitchen_light_mode_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new \WP_Customize_Media_Control(
			$wp_customize,
			'bellas_kitchen_light_mode_logo',
			array(
				'label'       => esc_html__( 'Logo voor lichte modus', 'bellas-kitchen-theme' ),
				'description' => esc_html__( 'Gebruik hier de donkere/horizontale variant voor op een lichte navigatiebalk.', 'bellas-kitchen-theme' ),
				'section'     => 'title_tagline',
				'mime_type'   => 'image',
			)
		)
	);

	$wp_customize->add_setting(
		'bellas_kitchen_dark_mode_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new \WP_Customize_Media_Control(
			$wp_customize,
			'bellas_kitchen_dark_mode_logo',
			array(
				'label'       => esc_html__( 'Logo voor donkere modus', 'bellas-kitchen-theme' ),
				'description' => esc_html__( 'Gebruik hier de witte/horizontale variant voor op een donkere navigatiebalk.', 'bellas-kitchen-theme' ),
				'section'     => 'title_tagline',
				'mime_type'   => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'bellas_kitchen_customize_register' );

/**
 * Render the site logo in the header, falling back to text branding.
 */
function bellas_kitchen_render_site_branding(): void {
	$light_logo_id = absint( get_theme_mod( 'bellas_kitchen_light_mode_logo', 0 ) );
	$dark_logo_id  = absint( get_theme_mod( 'bellas_kitchen_dark_mode_logo', 0 ) );
	$site_name     = get_bloginfo( 'name' );

	if ( $light_logo_id || $dark_logo_id ) {
		$light_logo_classes = $dark_logo_id ? 'block h-25 w-auto max-w-64 object-contain dark:hidden' : 'block h-12 w-auto max-w-56 object-contain';
		$dark_logo_classes  = $light_logo_id ? 'hidden h-25 w-auto max-w-64 object-contain dark:block' : 'block h-12 w-auto max-w-56 object-contain';

		echo '<a class="inline-flex items-center" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( $site_name ) . '">';

		if ( $light_logo_id ) {
			echo wp_get_attachment_image(
				$light_logo_id,
				'full',
				false,
				array(
					'class' => $light_logo_classes,
					'alt'   => $site_name,
				)
			);
		}

		if ( $dark_logo_id ) {
			echo wp_get_attachment_image(
				$dark_logo_id,
				'full',
				false,
				array(
					'class' => $dark_logo_classes,
					'alt'   => $site_name,
				)
			);
		}

		echo '</a>';

		return;
	}

	?>
	<div>
		<h1 class="site-title text-2xl font-display font-bold text-slate-900 dark:text-night-text"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
		<p class="site-description text-sm text-slate-600 dark:text-night-subtle"><?php bloginfo( 'description' ); ?></p>
	</div>
	<?php
}

/**
 * Fallback menu output for primary navigation.
 */
function bellas_kitchen_primary_menu_fallback(): void {
	$pages = get_pages(
		array(
			'parent'      => 0,
			'sort_column' => 'menu_order,post_title',
		)
	);

	echo '<ul id="primary-menu" class="menu m-0 flex list-none flex-wrap items-center gap-2 p-0">';
	echo '<li class="menu-item"><a class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-ember-100 hover:text-ember-700 dark:text-night-textSoft dark:hover:bg-night-surfaceElevated dark:hover:text-amber-300" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'bellas-kitchen-theme' ) . '</a></li>';

	foreach ( $pages as $page ) {
		echo '<li class="menu-item"><a class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-ember-100 hover:text-ember-700 dark:text-night-textSoft dark:hover:bg-night-surfaceElevated dark:hover:text-amber-300" href="' . esc_url( get_permalink( $page->ID ) ) . '">' . esc_html( $page->post_title ) . '</a></li>';
	}

	echo '</ul>';
}

/**
 * Render primary navigation menu.
 */
function bellas_kitchen_render_primary_menu(): void {
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'menu_id'        => 'primary-menu',
			'menu_class'     => 'menu m-0 flex list-none flex-wrap items-center gap-2 p-0',
			'container'      => false,
			'fallback_cb'    => 'bellas_kitchen_primary_menu_fallback',
		)
	);
}

/**
 * Add Tailwind classes to primary menu <li> items.
 */
function bellas_kitchen_primary_menu_item_classes( array $classes, \WP_Post $item, \stdClass $args ): array {
	if ( ! bellas_kitchen_is_menu_item_current_request( $item ) ) {
		$classes = bellas_kitchen_without_current_menu_classes( $classes );
	}

	$classes[] = 'menu-item';

	return $classes;
}
add_filter( 'nav_menu_css_class', 'bellas_kitchen_primary_menu_item_classes', 10, 3 );

/**
 * Add Tailwind classes to primary menu links.
 */
function bellas_kitchen_primary_menu_link_classes( array $atts, \WP_Post $item, \stdClass $args ): array {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $atts;
	}

	$link_classes = 'inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-ember-100 hover:text-ember-700 dark:text-night-textSoft dark:hover:bg-night-surfaceElevated dark:hover:text-amber-300';
	$is_active    = bellas_kitchen_is_menu_item_current_request( $item );

	if ( $is_active ) {
		$link_classes .= ' bg-ember-100 text-ember-700 dark:bg-night-surfaceElevated dark:text-amber-300';
	}

	$atts['class'] = $link_classes;

	if ( ! $is_active ) {
		unset( $atts['aria-current'] );
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'bellas_kitchen_primary_menu_link_classes', 10, 3 );

/**
 * Check whether a menu item matches the current request path.
 */
function bellas_kitchen_is_menu_item_current_request( \WP_Post $item ): bool {
	if ( empty( $item->url ) ) {
		return false;
	}

	return bellas_kitchen_normalize_url_path( $item->url ) === bellas_kitchen_get_current_request_path();
}

/**
 * Get the normalized current browser request path.
 */
function bellas_kitchen_get_current_request_path(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

	return '/' . trim( (string) $path, '/' );
}

/**
 * Normalize a URL to a comparable path.
 */
function bellas_kitchen_normalize_url_path( string $url ): string {
	$path = wp_parse_url( $url, PHP_URL_PATH );

	return '/' . trim( (string) $path, '/' );
}

/**
 * Remove all current-state classes from a menu item.
 */
function bellas_kitchen_without_current_menu_classes( array $classes ): array {
	return array_values(
		array_filter(
			$classes,
			static function ( string $class ): bool {
				return 0 !== strpos( $class, 'current-' ) && 0 !== strpos( $class, 'current_' );
			}
		)
	);
}

/**
 * Enqueue styles and scripts
 */
function bellas_kitchen_theme_enqueue() {
	wp_enqueue_style(
		'bellas-kitchen-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	$tailwind_css_relative_path = '/assets/css/tailwind.css';
	$tailwind_css_path          = get_template_directory() . $tailwind_css_relative_path;
	$tailwind_css_url           = get_template_directory_uri() . $tailwind_css_relative_path;

	if ( file_exists( $tailwind_css_path ) ) {
		wp_enqueue_style(
			'bellas-kitchen-tailwind',
			$tailwind_css_url,
			array(),
			(string) filemtime( $tailwind_css_path )
		);
	} else {
		wp_enqueue_script(
			'bellas-kitchen-tailwind-cdn',
			'https://cdn.tailwindcss.com',
			array(),
			null,
			false
		);
	}

	wp_enqueue_style( 'bellas-kitchen-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'bellas-kitchen-script', get_template_directory_uri() . '/js/main.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'wp_enqueue_scripts', 'bellas_kitchen_theme_enqueue' );

/**
 * Print Tailwind config before CDN script executes.
 */
function bellas_kitchen_print_tailwind_config(): void {
	$tailwind_css_path = get_template_directory() . '/assets/css/tailwind.css';
	if ( file_exists( $tailwind_css_path ) ) {
		return;
	}

	?>
	<script>
		window.tailwind = window.tailwind || {};
		window.tailwind.config = {
			darkMode: 'class',
			theme: {
				container: {
					center: true,
					padding: {
						DEFAULT: '1.25rem',
						md: '2rem'
					},
					screens: {
						'2xl': '1200px'
					}
				},
				extend: {
					colors: {
						ember: { 50: '#fff8f1', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 500: '#f97316', 700: '#c2410c', 900: '#7c2d12' },
						fig: { 100: '#f3e8ff', 300: '#d8b4fe', 700: '#7e22ce', 900: '#581c87' },
						sage: { 100: '#e7f5ec', 300: '#9dd6af', 700: '#2f6a48' },
						peach: { 100: '#ffe5d4', 200: '#ffd1ba', 300: '#ffbfa3' },
						butter: { 100: '#fff4bf', 200: '#ffe78a', 300: '#ffd95c' },
						mint: { 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac' },
						berry: { 100: '#fce7f3', 200: '#fbcfe8', 300: '#f9a8d4' },
						skycandy: { 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc' },
						night: {
							page: '#17080d',
							surface: '#241016',
							surfaceElevated: '#31161d',
							surfaceHover: '#421d27',
							border: '#5a2934',
							borderMuted: '#3a1a22',
							borderStrong: '#7a3846',
							text: '#fff1f4',
							textSoft: '#f8dbe2',
							muted: '#e6b7c2',
							subtle: '#c88a99',
							placeholder: '#9f6472',
							ring: '#421d27'
						}
					},
					fontFamily: {
						display: [ 'Fraunces', 'serif' ],
						sans: [ 'Manrope', 'sans-serif' ]
					},
					boxShadow: {
						glow: '0 20px 60px rgba(236, 72, 153, 0.14)',
						card: '0 18px 40px rgba(249, 168, 212, 0.18)'
					}
				}
			}
		};
	</script>
	<?php
}
add_action( 'wp_head', 'bellas_kitchen_print_tailwind_config', 1 );

/**
 * Register widget areas
 */
function bellas_kitchen_theme_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Primary Sidebar', 'bellas-kitchen-theme' ),
		'id'            => 'primary-sidebar',
		'description'   => esc_html__( 'Main sidebar', 'bellas-kitchen-theme' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'bellas_kitchen_theme_widgets_init' );

/**
 * Get the recepten repository from the custom plugin.
 */
function bellas_kitchen_get_recept_repository(): ?\BellasKitchenRecepten\Database\ReceptRepository {
	static $repository = null;
	static $loaded     = false;

	if ( $loaded ) {
		return $repository;
	}

	$loaded = true;

	if ( ! class_exists( '\BellasKitchenRecepten\Database\ReceptRepository' ) ) {
		return null;
	}

	$repository = new \BellasKitchenRecepten\Database\ReceptRepository();

	return $repository;
}

/**
 * Get the recepten archive URL.
 */
function bellas_kitchen_get_recepten_archive_url(): string {
	if ( class_exists( '\BellasKitchenRecepten\Frontend\ReceptenFrontend' ) ) {
		return \BellasKitchenRecepten\Frontend\ReceptenFrontend::getArchiveUrl();
	}

	return home_url( '/recepten/' );
}

/**
 * Get the permalink for a recept row.
 */
function bellas_kitchen_get_recept_url( array $recept ): string {
	if ( class_exists( '\BellasKitchenRecepten\Frontend\ReceptenFrontend' ) ) {
		return \BellasKitchenRecepten\Frontend\ReceptenFrontend::getRecipeUrl( $recept );
	}

	return bellas_kitchen_get_recepten_archive_url();
}

/**
 * Get the current recepten archive page.
 */
function bellas_kitchen_get_recepten_archive_page(): int {
	if ( class_exists( '\BellasKitchenRecepten\Frontend\ReceptenFrontend' ) ) {
		return \BellasKitchenRecepten\Frontend\ReceptenFrontend::getCurrentArchivePage();
	}

	return 1;
}

/**
 * Get the current recept slug.
 */
function bellas_kitchen_get_current_recept_slug(): string {
	if ( class_exists( '\BellasKitchenRecepten\Frontend\ReceptenFrontend' ) ) {
		return \BellasKitchenRecepten\Frontend\ReceptenFrontend::getCurrentRecipeSlug();
	}

	return '';
}

/**
 * Get the image URL for a recept.
 */
function bellas_kitchen_get_recept_image_url( array $recept, string $size = 'large' ): string {
	$foto_id = absint( $recept['foto_id'] ?? 0 );

	if ( $foto_id <= 0 ) {
		return '';
	}

	$image_url = wp_get_attachment_image_url( $foto_id, $size );

	return $image_url ?: '';
}

/**
 * Get the image alt text for a recept.
 */
function bellas_kitchen_get_recept_image_alt( array $recept ): string {
	$foto_id = absint( $recept['foto_id'] ?? 0 );

	if ( $foto_id <= 0 ) {
		return (string) ( $recept['naam'] ?? '' );
	}

	$alt = get_post_meta( $foto_id, '_wp_attachment_image_alt', true );

	return is_string( $alt ) && '' !== $alt ? $alt : (string) ( $recept['naam'] ?? '' );
}

/**
 * Format recipe labels for display.
 */
function bellas_kitchen_format_recept_label( string $value ): string {
	$value = str_replace( '_', ' ', $value );

	return ucfirst( $value );
}

/**
 * Format ingredient units for display.
 */
function bellas_kitchen_format_recept_unit( string $unit ): string {
	$units = array(
		''           => '',
		'ml'         => 'ml',
		'l'          => 'l',
		'g'          => 'g',
		'kg'         => 'kg',
		'tl'         => 'tl',
		'el'         => 'el',
		'snufje'     => 'snufje',
		'stuks'      => 'stuks',
		'naar_smaak' => 'naar smaak',
	);

	if ( isset( $units[ $unit ] ) ) {
		return $units[ $unit ];
	}

	return str_replace( '_', ' ', $unit );
}

/**
 * Format a duration in minutes.
 */
function bellas_kitchen_format_recept_duration( int $minutes ): string {
	if ( $minutes <= 0 ) {
		return '';
	}

	return sprintf(
		_n( '%d minuut', '%d minuten', $minutes, 'bellas-kitchen-theme' ),
		$minutes
	);
}

/**
 * Format servings for display.
 */
function bellas_kitchen_format_recept_servings( int $servings, string $label = '' ): string {
	if ( $servings <= 0 ) {
		return '';
	}

	$label = bellas_kitchen_format_recept_servings_label( $servings, $label );

	return sprintf( '%d %s', $servings, $label );
}

/**
 * Format the servings label for display.
 */
function bellas_kitchen_format_recept_servings_label( int $servings, string $label = '' ): string {
	$default_label = bellas_kitchen_get_default_recept_servings_label();
	$label         = '' !== trim( $label ) ? trim( $label ) : $default_label;

	if ( 1 === $servings && 0 === strcasecmp( $label, $default_label ) ) {
		return __( 'persoon', 'bellas-kitchen-theme' );
	}

	return $label;
}

/**
 * Get the servings label for a recept.
 */
function bellas_kitchen_get_recept_servings_label( array $recept ): string {
	$label = sanitize_text_field( (string) ( $recept['aantal_personen_label'] ?? '' ) );
	$label = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 100 ) : substr( $label, 0, 100 );
	$label = trim( $label );

	return '' !== $label ? $label : bellas_kitchen_get_default_recept_servings_label();
}

/**
 * Get the default servings label.
 */
function bellas_kitchen_get_default_recept_servings_label(): string {
	return __( 'personen', 'bellas-kitchen-theme' );
}
