<?php
/**
 * Module loader: discovers and initializes modules from the modules directory.
 *
 * Each module is a subdirectory containing a class-module.php file that returns
 * an instance implementing SSD_Module_Interface.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SSD_PLUGIN_DIR . 'interface-module.php';

class SSD_Module_Loader {

	/** @var string Absolute path to the modules directory. */
	private $modules_dir;

	public function __construct( string $modules_dir ) {
		$this->modules_dir = $modules_dir;
	}

	/**
	 * Scan the modules directory and initialize each module.
	 */
	public function load_modules(): void {
		if ( ! is_dir( $this->modules_dir ) ) {
			return;
		}

		$directories = glob( $this->modules_dir . '*/class-module.php' );

		if ( ! is_array( $directories ) ) {
			return;
		}

		foreach ( $directories as $module_file ) {
			$module = require $module_file;

			if ( $module instanceof SSD_Module_Interface ) {
				$module->register();
			}
		}
	}
}
