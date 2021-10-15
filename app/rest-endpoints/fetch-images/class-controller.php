<?php
/**
 * The endpoint for Plugin Base.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\App\Rest_Endpoints\Fetch_Images
 */

namespace shush_toolkit\App\Rest_Endpoints\Fetch_Images;

// Abort if called directly.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Controllers\Rest_Api;
use shush_toolkit\App\Modules\Analyze_Image;
// use Smush\App as Smush_App;

/**
 * Class Controller
 *
 * @package shush_toolkit\App\Rest_Endpoints\shush_toolkit
 */

class Controller extends Rest_Api {

	private $progress = array();

	private $site_image_sizes = array();

	/**
	 * Allowed mime types of image.
	 *
	 * @var array $mime_types
	 */
	public static $mime_types = array(
		'image/jpg',
		'image/jpeg',
		'image/x-citrix-jpeg',
		'image/gif',
		'image/png',
		'image/x-png',
	);

	public function init() {
		$endpoint = array(
			'namespace' => 'smush_toolkit',
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

		// Add a custom status code.
		// $response->set_status( 200 );

		return $response;
	}

	public function process_images() {
		$request_response = null;
		$response_data    = array();
		$response_code    = 200;
		$response_body    = null;
		$progress         = $this->get_progress();
		$completed        = false; // ( isset( $progress[ 'status' ] ) && 'completed' === $progress[ 'status' ] );

		$response_data = array(
			'success'   => true,
			'code'      => $response_code,
			'format'    => 'json',
			'message'   => json_encode( $response_body ),
			'completed' => $completed,
		);

		$image_data = $this->get_image_data();

		if ( ! $image_data || empty( $image_data ) ) {
			$response_data['completed'] = true;
			$completed                  = true;
		}

		if ( ! $completed ) {
			$response_data['message'] = $image_data;// json_encode( $image_data );
			$this->increment_process();

			// We skip current image as we need to target only images that have known issues.
			// if (  ! isset( $image_data[ 'report_status' ] ) || 'invalid' !== $image_data[ 'report_status' ] ) {
				// $this->process_images();
			// }

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
		if ( ! \class_exists( 'WP_Smush' ) || ! \class_exists( 'Smush\Core\Stats' ) ) {
			return false;
		}

		$attachment_id = $this->get_attachment();

		if ( ! $attachment_id || empty( $attachment_id ) ) {
			return false;
		}

		$image_analyzer   = new Analyze_Image( (int) $attachment_id );
		$analysis_results = $image_analyzer->analyze();

		return ( ! isset( $analysis_results['report_status'] ) || ! $analysis_results['report_status'] ) ? false : $analysis_results;
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

		/**
		 * TODO : Add a UI option for this in front end, so that user can choose to chck un-smushed images or all images.
		 */
		$resmush_list = get_option( 'wp-smush-resmush-list' );

		if ( ! empty( $resmush_list ) ) {
			if ( ( count( $resmush_list ) - 1 ) < $offset ) {
				// Return false which marks the process done.
				return false;
			}

			$image_id = $resmush_list[ $offset ];
		}

		if ( is_null( $image_id ) ) {
			global $wpdb;

			$mimes = implode( "', '", self::$mime_types );
			$image = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('$mimes') LIMIT 1 OFFSET %d",
					$progress['offset']
				)
			);

			if ( empty( $image ) || in_array( 0, array_values( $image ) ) ) {
				return false;
			}

			$image_id = (int) $image[0];
		}
		
		return $image_id;
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
	 * Gets the progress.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with the progress flag data.
	 */
	protected function get_progress() {
		$progress = ( ! empty( $this->progress ) ? $this->get_progress : \get_option( 'smush-toolkit-progress' ) );

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

		\update_option( 'smush-toolkit-progress', $this->progress );
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
		\delete_option( 'smush-toolkit-progress' );
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
