<?php
/**
 * Interface that every Simple Site Data module must implement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SSD_Module_Interface {

	/**
	 * Hook into WordPress. Called once during plugins_loaded.
	 */
	public function register(): void;
}
