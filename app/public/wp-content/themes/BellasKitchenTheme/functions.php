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
