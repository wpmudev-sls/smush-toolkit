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

namespace shush_toolkit\App\Admin_Pages\Regenerate_Image;

// Abort if called directly.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Controllers\Admin_Page;
use shush_toolkit\App\Admin_Pages\Regenerate_Image\View;


/**
 * Class Controller
 *
 * @package shush_toolkit\App\Admin_Pages\Regenerate_Image
 */
class Controller extends Admin_Page {

	/**
	 * The Admin Page's Menu Type.
	 *
	 * @var bool $is_submenu Set to true if page uses submenu.
	 *
	 * @since 1.0.0
	 */
	// protected $is_submenu = true;

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
		$this->unique_id = $this->get_unique_id();
		// this->parent_slug = 'smush_toolkit';
		$this->page_title = __( 'Regenerate Image', 'shush-toolkit' );
		$this->menu_title = __( 'Regenerate Image', 'shush-toolkit' );
		$this->capability = 'manage_options';
		$this->menu_slug  = 'regenerate_image';
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
			'regenerate_image_admin_page' => array(
				'src'       => $this->scripts_dir . 'admin-pages/regenerate-image/main.js',
				'deps'      => array( 'react', 'wp-element', 'wp-i18n', 'wp-is-shallow-equal', 'wp-polyfill' ),
				'ver'       => SMUSHTOOLKIT_SCIPTS_VERSION,
				// 'ver'       => time(),
				'in_footer' => true,
				'localize'  => array(
					'regenerate_image' => array(
						'data'   => array(
							'rest_url'                     => esc_url_raw( rest_url() ),
							'rest_namespace'               => '/regenerate_image/v1/fetch_images',
							'rest_replace_origs_namespace' => '/regenerate_image/v1/replace_originals',
							'unique_id'                    => $this->unique_id,
							'nonce'                        => wp_create_nonce( 'wp_rest' ),
						),
						'labels' => array(
							'page_title'        => $this->page_title,
							'regenerate_images' => __( 'Regenerate Images', 'shush-toolkit' ),
							'error_messages'    => array(
								'general' => __( 'Something went wrong here.', 'shush-toolkit' ),
							),
						),
					),
				),
			),
		);
	}

}
