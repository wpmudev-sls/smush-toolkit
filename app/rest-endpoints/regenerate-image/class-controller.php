<?php
/**
 * The endpoint for Plugin Base.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\App\Rest_Endpoints\Regenerate_Image
 */

namespace shush_toolkit\App\Rest_Endpoints\Regenerate_Image;

// Abort if called directly.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Controllers\Rest_Api;
use shush_toolkit\App\Modules\Analyze_Image;
// use Smush\App as Smush_App;

/**
 * Class Controller
 *
 * @package shush_toolkit\App\Rest_Endpoints\Regenerate_Image
 */

class Controller extends Rest_Api {

	private $progress = array();

	private $site_image_sizes = array();

	public function init() {
		$endpoint = array(
			'namespace' => 'regenerate_image',
			'version'   => 'v1',
			'route'     => 'fetch_images',
			'args'      => array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'callback' ),
				'permission_callback' => array( __CLASS__, 'permission_callback' ),
			),
			'override'  => false,
		);

		$this->add_endpoint( $endpoint );
	}

	public function callback( $request ) {

		$response_data = $this->process_images();
		// Create the response object
		$response = new \WP_REST_Response( $response_data );

		return $response;
	}

	public function process_images() {
		$request_response = null;
		$response_data    = array();
		$response_code    = 200;
		$response_body    = null;
		$progress         = $this->get_progress();
		$completed        = false;

		$response_data = array(
			'success'   => true,
			'code'      => $response_code,
			'format'    => 'json',
			'message'   => json_encode( $response_body ),
			'completed' => $completed,
		);

		$image_data = $this->get_image_data();

		if ( ! $image_data || empty( $image_data ) || ! is_array( $image_data ) ) {
			$response_data['completed'] = true;
		}

		$response_data = \wp_parse_args( $image_data, $response_data );

		if ( ! $response_data['completed'] ) {
			$response_data['message'] = isset( $image_data['message'] ) ? $image_data['message'] : '';
			$this->increment_process();
		} else {
			$this->reset_progress();
		}

		return $response_data;
	}

	/**
	 * TODO: Create a new class for these
	 */


	 /**
	  * Get image data for current progress status.
	  *
	  * @since 1.0.0
	  *
	  * @return mixed|boolean|array Returns an array with the analysis data of the current image.
	  */
	public function get_image_data() {
		$response = array(
			'completed' => false,
			'images'    => '',
			'message'   => '',
		);

		$attachment_id = $this->get_attachment();

		if ( ! $attachment_id || empty( $attachment_id ) || 0 == $attachment_id ) {
			$response['completed'] = true;
			$response['message']   = __( 'Done!', 'shush-toolkit' );
			return $response;
		}

		$attached_file_path = \get_post_meta( $attachment_id, '_wp_attached_file', true );

		if ( ! $attached_file_path || empty( $attached_file_path ) ) {
			return array();
		}

		if ( strpos( $attached_file_path, '/uploads/' ) !== false ) {
			$attached_file_path = explode( '/uploads/', $attached_file_path )[1];
			\update_post_meta( $attachment_id, '_wp_attached_file', $attached_file_path );
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$file                = \wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . $attached_file_path;
		$attachment_metadata = \wp_generate_attachment_metadata( $attachment_id, $file );
		\wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

		$response['completed'] = false;
		$image_edit_link       = \admin_url( "upload.php?item={$attachment_id}" );
		$response['message']   = sprintf( __( 'Image <a href="%1$s" target="_blank">%2$d</a> has been repaired', 'shush-toolkit' ), $image_edit_link, $attachment_id );

		return $response;
	}

	/**
	 * Get current atachment id ad meta data.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with the id and _wp_attachment_metadata of the current image.
	 */
	protected function get_attachment() {
		$progress = $this->get_progress();

		if ( ! isset( $progress['offset'] ) || ! is_numeric( $progress['offset'] ) ) {
			return false;
		}

		$image_id = null;
		$offset   = (int) $progress['offset'];

		global $wpdb;

		$image = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value = 'b:0;' ORDER BY post_id LIMIT 1 OFFSET %d",
				absint( $progress['offset'] )
			)
		);

		if ( empty( $image ) ) {
			return false;
		}

		$image_id = (int) $image[0];

		return $image_id;
	}


	/**
	 * Gets the progress.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with the progress flag data.
	 */
	protected function get_progress() {
		$progress = ( ! empty( $this->progress ) ? $this->get_progress : \get_option( 'regenerate-image-progress' ) );

		if ( ! $progress || empty( $progress ) || ! isset( $progress['status'] ) || ! isset( $progress['offset'] ) ) {
			$progress = array(
				'status' => 'started',
				'offset' => 0,
			);
		}

		return $progress;
	}

	/**
	 * Sets given keys and values in the progress flag.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sets given keys and values in the progress flag.
	 */
	protected function set_progress( $args = array() ) {
		if ( ! is_array( $args ) || empty( $args ) ) {
			return;
		}

		$this->progress = $this->get_progress();

		foreach ( $args as $key => $value ) {
			if ( ! is_numeric( $value ) ) {
				$value = \sanitize_text_field( $value );
			}

			if ( 'offset' == $key ) {
				$value = (int) $value;
			}

			$this->progress[ $key ] = $value;
		}

		\update_option( 'regenerate-image-progress', $this->progress );
	}

	/**
	 * Resets the progress flag. It deletes it but doesn't re-create it.
	 *
	 * @since 1.0.0
	 *
	 * @return void Resets the progress flag.
	 */
	protected function reset_progress() {
		$this->progress = array();
		\delete_option( 'regenerate-image-progress' );
	}

	/**
	 * Increments the progress offset.
	 *
	 * @since 1.0.0
	 *
	 * @return void Incements the progress offset.
	 */
	protected function increment_process() {
		$progress = $this->get_progress();

		if ( ! isset( $progress['offset'] ) || ! is_numeric( $progress['offset'] ) ) {
			$this->set_progress( array( 'offset' => 0 ) );
		} else {
			$offset = intval( $progress['offset'] ) + 1;
			$this->set_progress( array( 'offset' => $offset ) );
		}
	}



	/**
	 * Rest request permissions. Returns a boolean or _doing_it_wrong notice.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed|boolean|string Boolean or _doing_it_wrong notice.
	 */
	public static function permission_callback( \WP_REST_Request $request ) {
		// return \current_user_can( 'manage_options' ); // User ID is 0
		return true;
	}

}
