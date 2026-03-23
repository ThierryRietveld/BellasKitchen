<?php
/**
 * Plugin Name: Bella's Kitchen Recipes
 * Plugin URI:  https://bellaskitchen.nl
 * Description: Custom post type and fields for managing recipes in Bella's Kitchen.
 * Version:     1.0.0
 * Author:      Thierry Rietveld
 * License:     GPL-2.0-or-later
 * Text Domain: bellas-kitchen-recipes
 *
 * @package BellasKitchenRecipes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BKR_PLUGIN_VERSION', '1.0.0' );

require_once plugin_dir_path( __FILE__ ) . 'includes/RecipePostType.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/RecipeFields.php';

use BellasKitchenRecipes\RecipePostType;
use BellasKitchenRecipes\RecipeFields;

/**
 * Main plugin class.
 */
class BellasKitchenRecipesPlugin {

	public function __construct() {
		( new RecipePostType() )->register();
		( new RecipeFields() )->register();
	}
}

new BellasKitchenRecipesPlugin();
