<?php
/**
 * ASecure.me plugin
 *
 * @package ASecure
 */

/**
 * Plugin Name: ASecure.me
 * Description: Monitor the status of your WordPress website with ASecure.me WP plugin.
 * Plugin URI: https://asecure.me/
 * Author: aspexi
 * Author URI: https://aspexi.com
 * Text Domain: asecureme
 * Version: 1.0
 * Requires at least: 4.0
 * Requires PHP: 5.0
 */

require_once ABSPATH . 'wp-includes/version.php'; // Version information from WordPress.

/**
 * Define ASecure.me Debug Mode.
 *
 * @const (bool) Whether ASecure.me is in debug mode. Default: false.
 */
if ( ! defined( 'ASECURE_DEBUG' ) ) {
	define( 'ASECURE_DEBUG', false );
}

if ( ! defined( 'ASECURE_FILE' ) ) {

	/**
	 * Define the absolute full path and filename of this file.
	 *
	 * @const (string) ASecure.me main plugin file path.
	 */
	define( 'ASECURE_FILE', __FILE__ );
}

if ( ! defined( 'ASECURE_PLUGIN_DIR' ) ) {

	/**
	 * Define ASecure.me Plugin Directory.
	 *
	 * @const (string) ASecure.me Plugin Directory.
	 */
	define( 'ASECURE_PLUGIN_DIR', plugin_dir_path( ASECURE_FILE ) );
}

if ( ! defined( 'ASECURE_URL' ) ) {

	/**
	 * Define ASecure.me Plugin URL.
	 *
	 * @const (string) Defined ASecure.me Plugin URL.
	 */
	define( 'ASECURE_URL', plugin_dir_url( ASECURE_FILE ) );
}

/**
 * ASecure.me Plugin Autoloader to load all other class files.
 *
 * @param string $class_name Name of the class to load.
 *
 * @uses \ASecure\ASecure()
 */
function asecure_autoload( $class_name ) {

	if ( 0 === strpos( $class_name, 'ASecure' ) ) {
		// Strip the prefix: ASecure\
		$class_name = substr( $class_name, 8 );
	}

	$autoload_dir  = \trailingslashit( dirname( __FILE__ ) . '/class' );
	$autoload_path = sprintf( '%sclass-%s.php', $autoload_dir, strtolower( str_replace( '_', '-', $class_name ) ) );

	if ( file_exists( $autoload_path ) ) {
		require_once $autoload_path;
	}
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'asecure_autoload' );
}

$pluginASecure = new ASecure\ASecure( WP_PLUGIN_DIR . '/' . plugin_basename( __FILE__ ) );

register_activation_hook( __FILE__, array( $pluginASecure, 'activation' ) );
register_deactivation_hook( __FILE__, array( $pluginASecure, 'deactivation' ) );
