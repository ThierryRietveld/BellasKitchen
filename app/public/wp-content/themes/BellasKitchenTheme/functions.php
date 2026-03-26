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
 * Enqueue styles and scripts
 */
function bellas_kitchen_theme_enqueue() {
	wp_enqueue_style(
		'bellas-kitchen-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_script(
		'bellas-kitchen-tailwind',
		'https://cdn.tailwindcss.com',
		array(),
		null,
		false
	);

	wp_add_inline_script(
		'bellas-kitchen-tailwind',
		'tailwind.config = { theme: { extend: { colors: { ember: { 50: "#fff8f1", 100: "#ffedd5", 200: "#fed7aa", 300: "#fdba74", 500: "#f97316", 700: "#c2410c", 900: "#7c2d12" }, fig: { 100: "#f3e8ff", 300: "#d8b4fe", 700: "#7e22ce", 900: "#581c87" }, sage: { 100: "#e7f5ec", 300: "#9dd6af", 700: "#2f6a48" }, peach: { 100: "#ffe5d4", 200: "#ffd1ba", 300: "#ffbfa3" }, butter: { 100: "#fff4bf", 200: "#ffe78a", 300: "#ffd95c" }, mint: { 100: "#dcfce7", 200: "#bbf7d0", 300: "#86efac" }, berry: { 100: "#fce7f3", 200: "#fbcfe8", 300: "#f9a8d4" }, skycandy: { 100: "#e0f2fe", 200: "#bae6fd", 300: "#7dd3fc" } }, fontFamily: { display: ["Fraunces", "serif"], sans: ["Manrope", "sans-serif"] }, boxShadow: { glow: "0 20px 60px rgba(236, 72, 153, 0.14)", card: "0 18px 40px rgba(249, 168, 212, 0.18)" } } } };',
		'before'
	);

	wp_enqueue_style( 'bellas-kitchen-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'bellas-kitchen-script', get_template_directory_uri() . '/js/main.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'wp_enqueue_scripts', 'bellas_kitchen_theme_enqueue' );

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
