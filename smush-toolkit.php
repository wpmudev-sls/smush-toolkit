<?php
/**
 * @package shush_toolkit
 *
 * Plugin name: Smush Toolkit
 * Plugin URI:  https://gist.github.com/panoslyrakis/
 * Description: A base plugin to use as a starter
 * Author:      Panos Lyrakis @ WPMUDEV
 * Version:     1.0.0
 * License:     GNU General Public License (Version 2 - GPLv2)
 * Text Domain: shush-toolkit
 * Domain Path: /languages
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

// Plugin version.
if ( ! defined( 'SMUSHTOOLKIT_VERSION' ) ) {
	define( 'SMUSHTOOLKIT_VERSION', '1.0.0' );
}

// Define SMUSHTOOLKIT_PLUGIN_FILE.
if ( ! defined( 'SMUSHTOOLKIT_PLUGIN_FILE' ) ) {
	define( 'SMUSHTOOLKIT_PLUGIN_FILE', __FILE__ );
}

// Plugin directory.
if ( ! defined( 'SMUSHTOOLKIT_DIR' ) ) {
	define( 'SMUSHTOOLKIT_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin url.
if ( ! defined( 'SMUSHTOOLKIT_URL' ) ) {
	define( 'SMUSHTOOLKIT_URL', plugin_dir_url( __FILE__ ) );
}
// Assets url.
if ( ! defined( 'SMUSHTOOLKIT_ASSETS_URL' ) ) {
	define( 'SMUSHTOOLKIT_ASSETS_URL', plugin_dir_url( __FILE__ ) . trailingslashit( 'assets' ) );
}

// Scripts version.
if ( ! defined( 'SMUSHTOOLKIT_SCIPTS_VERSION' ) ) {
	define( 'SMUSHTOOLKIT_SCIPTS_VERSION', '1.0.0' );
}

// Autoloader.
require_once plugin_dir_path( __FILE__ ) . '/core/utils/autoloader.php';

/**
 * Run plugin activation hook to setup plugin.
 *
 * @since 1.0.0
 */


// Make sure shush_toolkit is not already defined.
if ( ! function_exists( 'shush_toolkit' ) ) {
	/**
	 * Main instance of plugin.
	 *
	 * Returns the main instance of shush_toolkit to prevent the need to use globals
	 * and to maintain a single copy of the plugin object.
	 * You can simply call shush_toolkit() to access the object.
	 *
	 * @since  1.0.0
	 *
	 * @return shush_toolkit\Core\shush_toolkit
	 */
	function shush_toolkit() {
		return shush_toolkit\Core\Loader::instance();
	}

	// Init the plugin and load the plugin instance for the first time.
	add_action( 'plugins_loaded', 'shush_toolkit' );
}
