<?php
/**
 * The Admin page for listing images data.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\App\Admin_Pages\Smush_Toolkit
 */

namespace shush_toolkit\App\Admin_Pages\Smush_Toolkit;

// Abort if called directly.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Controllers\Admin_Page;
use shush_toolkit\App\Admin_Pages\Smush_Toolkit\View;


/**
 * Class Controller
 *
 * @package shush_toolkit\App\Admin_Pages\Smush_Toolkit
 */
class Controller extends Admin_Page {

	/**
	 * A unique id.
	 *
	 * @since 1.0.0
	 *
	 * @var int A unique id to be used with React and JS in general.
	 */
	private $unique_id;

	/**
	 * The backups of the current site.
	 *
	 * @since 1.0.0
	 *
	 * @var array The backups of the current site.
	 */
	private $current_site_backups;

	/**
	 * Prepares the properties of the Admin Page.
	 *
	 * @since 1.0.0
	 *
	 * @return void Prepares properties of the Admin page.
	 */
	public function prepare_props() {
		$this->unique_id  = $this->get_unique_id();
		$this->page_title = __( 'Smush toolkit', 'shush-toolkit' );
		$this->menu_title = __( 'Smush toolkit', 'shush-toolkit' );
		$this->capability = 'manage_options';
		$this->menu_slug  = 'smush_toolkit';
	}

	/**
	 * Admin Menu Callback.
	 *
	 * @since 1.0.0
	 *
	 * @return void The callback function of the Admin Menu Page.
	 */
	public function callback() {
		View::instance()->render(
			array(
				'unique_id' => $this->unique_id,
			)
		);
	}

	/**
	 * Register scripts for the admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void Regiser scripts for the admin page.
	 */
	public function set_scripts() {

		return array(
			'shush_toolkit_admin_page' => array(
				'src'       => $this->scripts_dir . 'admin-pages/smush-toolkit/main.js',
				'deps'      => array( 'react', 'wp-element', 'wp-i18n', 'wp-is-shallow-equal', 'wp-polyfill' ),
				'ver'       => SMUSHTOOLKIT_SCIPTS_VERSION,
				'in_footer' => true,
				'localize'  => array(
					'smush_toolkit' => array(
						'data'   => array(
							'rest_url'       => esc_url_raw( rest_url() ),
							'rest_namespace' => '/smush_toolkit/v1/fetch_images',
							'unique_id'      => $this->unique_id,
							'nonce'          => wp_create_nonce( 'wp_rest' ),
						),
						'labels' => array(
							'page_title'     => $this->page_title,
							'fetch_images'   => __( 'Fetch Images', 'shush-toolkit' ),
							'error_messages' => array(
								'general' => __( 'Something went wrong here.', 'shush-toolkit' ),
							),
						),
					),
				),
			),
		);
	}

}
