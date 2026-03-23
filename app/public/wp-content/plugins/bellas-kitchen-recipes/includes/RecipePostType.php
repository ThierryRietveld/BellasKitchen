<?php
/**
 * Recipe Custom Post Type registration.
 *
 * @package BellasKitchenRecipes
 */

namespace BellasKitchenRecipes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RecipePostType {

	public function register(): void {
		add_action( 'init', [ $this, 'registerPostType' ] );
	}

	public function registerPostType(): void {
		$labels = [
			'name'                  => _x( 'Recipes', 'Post type general name', 'bellas-kitchen-recipes' ),
			'singular_name'         => _x( 'Recipe', 'Post type singular name', 'bellas-kitchen-recipes' ),
			'menu_name'             => _x( 'Recipes', 'Admin Menu text', 'bellas-kitchen-recipes' ),
			'add_new'               => __( 'Add New', 'bellas-kitchen-recipes' ),
			'add_new_item'          => __( 'Add New Recipe', 'bellas-kitchen-recipes' ),
			'new_item'              => __( 'New Recipe', 'bellas-kitchen-recipes' ),
			'edit_item'             => __( 'Edit Recipe', 'bellas-kitchen-recipes' ),
			'view_item'             => __( 'View Recipe', 'bellas-kitchen-recipes' ),
			'all_items'             => __( 'All Recipes', 'bellas-kitchen-recipes' ),
			'search_items'          => __( 'Search Recipes', 'bellas-kitchen-recipes' ),
			'not_found'             => __( 'No recipes found.', 'bellas-kitchen-recipes' ),
			'not_found_in_trash'    => __( 'No recipes found in Trash.', 'bellas-kitchen-recipes' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'recipe' ],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-food',
			'show_in_rest'       => false,
			'supports'           => [ 'title', 'editor', 'thumbnail', 'revisions' ],
		];

		register_post_type( 'recipe', $args );
	}
}
