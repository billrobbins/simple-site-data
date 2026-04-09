<?php
/**
 * Plugin Name: Simple Site Data
 * Description: Surface hidden WordPress data inside wp-admin for troubleshooting.
 * Version: 1.0.0
 * Author: Bill Robbins
 * License: GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSD_VERSION', '1.0.0' );
define( 'SSD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SSD_PLUGIN_DIR . 'class-module-loader.php';

/**
 * Boot the plugin after all plugins have loaded.
 */
function ssd_init() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$loader = new SSD_Module_Loader( SSD_PLUGIN_DIR . 'modules/' );
	$loader->load_modules();
}
add_action( 'admin_init', 'ssd_init' );
