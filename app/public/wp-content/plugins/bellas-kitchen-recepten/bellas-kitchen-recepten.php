<?php
/**
 * Plugin Name: Bella's Kitchen Recepten
 * Plugin URI:  https://bellaskitchen.nl
 * Description: Beheer recepten in een eigen WordPress database tabel.
 * Version:     1.2.0
 * Author:      Thierry Rietveld
 * License:     GPL-2.0-or-later
 * Text Domain: bellas-kitchen-recepten
 *
 * @package BellasKitchenRecepten
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BKR_RECEPTEN_VERSION', '1.2.0' );
define( 'BKR_RECEPTEN_DB_VERSION', '1.2.0' );
define( 'BKR_RECEPTEN_PLUGIN_FILE', __FILE__ );
define( 'BKR_RECEPTEN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BKR_RECEPTEN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BKR_RECEPTEN_PLUGIN_DIR . 'includes/Database/Installer.php';
require_once BKR_RECEPTEN_PLUGIN_DIR . 'includes/Database/ReceptRepository.php';
require_once BKR_RECEPTEN_PLUGIN_DIR . 'includes/Admin/ReceptenAdminPage.php';

use BellasKitchenRecepten\Admin\ReceptenAdminPage;
use BellasKitchenRecepten\Database\Installer;
use BellasKitchenRecepten\Database\ReceptRepository;

register_activation_hook( __FILE__, [ Installer::class, 'activate' ] );
add_action( 'plugins_loaded', [ Installer::class, 'maybeUpgrade' ] );

/**
 * Main plugin class.
 */
class BellasKitchenReceptenPlugin {

	public function register(): void {
		$repository = new ReceptRepository();

		( new ReceptenAdminPage( $repository ) )->register();
	}
}

( new BellasKitchenReceptenPlugin() )->register();
