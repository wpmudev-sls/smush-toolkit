<?php
/**
 * Class to boot up plugin.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\Core
 */

namespace shush_toolkit\Core;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Modules;
use shush_toolkit\Core\Controllers;
use shush_toolkit\Core\Utils\Abstracts\Base;
use shush_toolkit\Core\Controllers\Settings;
use shush_toolkit\App\Admin_Pages;
use shush_toolkit\App\Shortcodes;
use shush_toolkit\App\Rest_Endpoints;

/**
 * Class shush_toolkit
 *
 * @package shush_toolkit\Core
 */
final class Loader extends Base {

	/**
	 * Settings helper class instance.
	 *
	 * @var settings
	 *
	 * @since  1.0.0
	 */
	public $settings;

	/**
	 * The minimum PHP version.
	 *
	 * @var php_version
	 *
	 * @since  1.0.0
	 */
	public $php_version = '7.0.0beta';

	/**
	 * Minimum WordPress version.
	 *
	 * @var wp_version
	 *
	 * @since  1.0.0
	 */
	public $wp_version = '5.2';

	/**
	 * Scripts to be registered.
	 *
	 * @since 1.0.0
	 *
	 * @return void Scripts to be registered.
	 */
	public static $scripts = array();

	/**
	 * Initialize functionality of the plugin.
	 *
	 * This is where we kick-start the plugin by defining
	 * everything required and register all hooks.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function __construct() {
		if ( ! $this->can_boot() ) {
			return;
		}

		$this->init();
	}

	private function can_boot() {
		/**
		 * Checks
		 *  - PHP version
		 *  - WP Version
		 *  - Snapshot is installed and active
		 * If not then return.
		 */
		global $wp_version;

		//if ( ! function_exists( 'is_plugin_active' ) ) {
		//	include_once ABSPATH . 'wp-admin/includes/plugin.php';
		//}

		return (
			version_compare( PHP_VERSION, $this->php_version, '>' ) &&
			version_compare( $wp_version, $this->wp_version, '>' )
		);
	}

	/**
	 * Register all of the actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function init() {
		// Initialize the core files and the app files. 
		// Core files are the base files that the app classes can rely on.
		// Not all core files need to be initiated.
		$this->init_core();
		$this->init_app();

		/**
		 * Setup plugin scripts
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'handle_scripts' ) );

		/**
		 * Action hook to trigger after initializing all core actions.
		 *
		 * @since 1.0.0
		 */
		do_action( 'pluginbase/after_core_init' );
	}

	/**
	 * Load all Core modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_core() {
		Controllers\Rest_Api::instance()->init();
	}

	/**
	 * Load all App modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_app() {
		/**
		 * Load plugin utilities. Usually core files.
		 * Important parts that are required for some components to work.
		 */
		/*
		$this->load_utilities(
			apply_filters(
				'pluginbase/load_utilities',
				array(
					'Rest_Api',
				)
			)
		);
		*/

		/**
		 * Load plugin components. (Admin pages, Shortcodes, Rest Endpoints etc)
		 * Structures that build the plugins features and ui.
		 */
		$this->load_components(
			apply_filters(
				'pluginbase/load_components',
				array(
					'Admin_Pages',
					'Rest_Endpoints',
					//'Shortcodes',
					//'Post_Types',
				)
			)
		);
	}

	/**
	 * Loads components.
	 *
	 * @since 1.0.0
	 * 
	 * @var array $components An array of components root folder names.
	 * 
	 */
	private function load_components( $components = array() ) {
		if ( ! empty( $components ) ) {
			array_map(
				array( $this, 'load_component' ),
				$components
			);
		}
	}

	/**
	 * Loads component's controller.
	 *
	 * @since 1.0.0
	 * 
	 * @var string $component The component name which is the folder name that contains the component files (mvc etc).
	 * 
	 * @var string $namespace The namespace where the component belongs to. Default is App which derives from the `plugin_path/app` main folder.
	 * 
	 */
	private function load_component( $component = null, $namespace = 'App' ) {
		if ( ! \is_null( $component ) ) {
			$component_path_part = \str_replace( '_', '-', $component );
			$component_path      = \strtolower( \trailingslashit( SMUSHTOOLKIT_DIR ) . \trailingslashit( $namespace ) . \trailingslashit( $component_path_part ) );

			if ( \is_dir( $component_path ) ) {
				$component_dir = new \DirectoryIterator( $component_path );

				foreach ( $component_dir as $fileinfo ) {

					if ( $fileinfo->isDir() && ! $fileinfo->isDot() ) {
						$component_item_dir = $fileinfo->getFilename();
						$component_item     = \str_replace( '-', '_', $component_item_dir );

						if ( \file_exists( \trailingslashit( $component_path ) . \trailingslashit( $component_item_dir ) . 'class-controller.php' ) ) {
							$component_item = "shush_toolkit\\{$namespace}\\{$component}\\{$component_item}\\Controller";
							$component_item::instance()->init();
						}
					}
				}
			}
		}
	}

	/**
	 * Loads plugin utilities.
	 * Code parts that live inside the `utils` main dir.
	 * The `abstracts`, `autoloader.php` and current `class-loader.php` exist in the same folder.
	 * 
	 *
	 * @since 1.0.0
	 * 
	 * @var array $utilities An array of utilities root folder names.
	 * 
	 */
	//private function load_utilities( $utilities = array() ) {}

	/**
	 * Register and enqueue plugin scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function handle_scripts() {
		if ( ! empty( self::$scripts ) ) {
			// error_log( 'handle_scripts > self:scripts: ' . print_r( self::$scripts,true ) );
			foreach ( self::$scripts as $handle => $script ) {
				$src       = isset( $script['src'] ) ? $script['src'] : '';
				$deps      = isset( $script['deps'] ) ? $script['deps'] : array();
				$ver       = isset( $script['ver'] ) ? $script['ver'] : SMUSHTOOLKIT_SCIPTS_VERSION;
				$in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;

				wp_register_script( $handle, $src, $deps, $ver, $in_footer );

				if ( isset( $script['localize'] ) ) {
					foreach ( $script['localize'] as $object_name => $translation_array ) {
						wp_localize_script( $handle, $object_name, $translation_array );
					}
				}
				// error_log( 'handle: ' . $handle );
				wp_enqueue_script( $handle );
			}
		}
	}

}
