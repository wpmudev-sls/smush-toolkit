<?php
/**
 * The endpoint for Plugin Base.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\App\Rest_Endpoints\Replace_Originals
 */

namespace shush_toolkit\App\Rest_Endpoints\Replace_Originals;

// Abort if called directly.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Controllers\Rest_Api;
// use shush_toolkit\App\Modules\Analyze_Image;
// use Smush\App as Smush_App;

/**
 * Class Controller
 *
 * @package shush_toolkit\App\Rest_Endpoints\Replace_Originals
 */

class Controller extends Rest_Api {

	private $progress = array();

	private $params = array(
		'minWidth'  => null,
		'minHeight' => null,
		'limit'     => null,
	);

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
			'namespace' => 'regenerate_image',
			'version'   => 'v1',
			'route'     => 'replace_originals',
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
		// $response_data = $this->process_images();
		// Create the response object
		$params = $request->get_params();

		foreach ( $this->params as $key => $value ) {
			$this->params[ $key ] = ( isset( $params[ $key ] ) && is_numeric( $params[ $key ] ) && 0 < absint( $params[ $key ] ) ) ? absint( $params[ $key ] ) : $this->params[ $key ];
		}

		$response_data = $this->process_images();
		$response      = new \WP_REST_Response( $response_data );

		return $response;
	}

	public function process_images() {
		$request_response = null;
		$response_data    = array();
		$response_code    = 200;
		$response_body    = array();
		$progress         = $this->get_progress();
		$completed        = false;

		$response_data = array(
			'success'   => true,
			'code'      => $response_code,
			'format'    => 'json',
			'message'   => json_encode( $response_body ),
			'completed' => $completed,
		);

		$images = $this->get_images();

		if ( ! $images || empty( $images ) ) {
			$response_data['completed'] = true;
			$response_data['message']   = json_encode( 'All images have been replaced' );

			$this->reset_progress();

			return $response_data;
		}

		foreach ( $images as $image ) {
			if ( ! property_exists( $image, 'ID' ) ) {
				continue;
			}

			$image_meta = \get_post_meta( $image->ID, '_wp_attachment_metadata', true );

			if ( ! isset( $image_meta['file'] ) ) {
				$response_body[] = sprintf( __( "Image %d was skipped as it was missing the 'file' key in meta", 'shush-toolkit' ), $image->ID );
				continue;
			}

			$filename  = \pathinfo( $image_meta['file'], PATHINFO_FILENAME );
			$substring = '-scaled';

			if ( \substr( $filename, -\strlen( $substring ) ) !== $substring ) {
				$response_body[] = \sprintf( __( "Image %d was skipped because it seem's it's not scaled", 'shush-toolkit' ), $image->ID );
				continue;
			}

			$scaled_image     = \wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . $image_meta['file'];
			$scaled_image_url = \wp_upload_dir()['baseurl'] . DIRECTORY_SEPARATOR . $image_meta['file'];

			if ( ! \file_exists( $scaled_image ) ) {
				$response_body[] = sprintf( __( "Image %1\$d was skipped because file %2\$s doesn't exist", 'shush-toolkit' ), $image->ID, $scaled_image );
				continue;
			}

			$original_image        = \str_replace( $substring, '', $scaled_image );
			$original_image_url    = \str_replace( $substring, '', $scaled_image_url );
			$image_resize_messages = array();
			$should_replace        = false;

			list( $original_image_width, $original_image_height) = getimagesize( $original_image_url );

			if ( isset( $this->params['minWidth'] ) && absint( $this->params['minWidth'] ) >= $original_image_width ) {
				$image_resize_messages[] = sprintf( __( "Image %1\$d was skipped because it's original width (%2\$dpx) is smaller than %3\$dpx (or equal)", 'shush-toolkit' ), $image->ID, $original_image_width, $this->params['minWidth'] );
			} else {
				$should_replace = true;
			}

			if ( isset( $this->params['minHeight'] ) && absint( $this->params['minHeight'] ) >= $original_image_height ) {
				$image_resize_messages[] = sprintf( __( "Image %1\$d was skipped because it's original height (%2\$dpx) is smaller than %3\$dpx (or equal)", 'shush-toolkit' ), $image->ID, $original_image_height, $this->params['minHeight'] );
			} else {
				$should_replace = true;
			}

			if ( ! $should_replace ) {
				if ( ! empty( $image_resize_messages ) ) {
					foreach ( $image_resize_messages as $message ) {
						$response_body[] = $message;
					}
				}
				continue;
			}

			if ( ! \file_exists( $original_image ) ) {
				$response_body[] = sprintf( __( "Image %1\$s was skipped because the original image (%2\$s) doesn't exist", 'shush-toolkit' ), $image->ID, $original_image );
				continue;
			}

			if ( ! \unlink( $original_image ) ) {
				$response_body[] = sprintf( __( 'Failed to delete  %1$s for image id %2$s', 'shush-toolkit' ), $scaled_image, $image->ID );
				continue;
			}

			\error_log( date( 'd-m-Y H:i:s' ) . " Deleted orinal image {$original_image}. For image with id {$image->ID}\n", 3, WP_CONTENT_DIR . '/replace-originals.log' );

			if ( ! \copy( $scaled_image, $original_image ) ) {
				$response_body[] = sprintf( __( 'Failed to copy  %1$s to %2$s', 'shush-toolkit' ), $scaled_image, $original_image );
			} else {
				$response_body[] = sprintf( __( 'Image %1$d (%2$s) has been resized', 'shush-toolkit' ), $image->ID, $original_image );
			}

			\error_log( date( 'd-m-Y H:i:s' ) . " Replaced orinal file {$original_image}. For image with id {$image->ID}\n", 3, WP_CONTENT_DIR . '/replace-originals.log' );
		}

		$response_data['message'] = implode( '<br />', $response_body );
		$this->increment_process();

		return $response_data;
	}

	public function get_images() {
		global $wpdb;
		$progress = $this->get_progress();

		if ( ! isset( $progress['offset'] ) || ! is_numeric( $progress['offset'] ) ) {
			return false;
		}

		$image_id = null;
		$offset   = (int) $progress['offset'];
		$limit    = absint( $this->params['limit'] );
		$mimes    = implode( "', '", self::$mime_types );

		if ( $limit <= 0 ) {
			$limit = 1;
		}
		$images = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('$mimes') LIMIT %d OFFSET %d",
				$limit,
				absint( $progress['offset'] )
			)
		);

		return $images;
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
			if ( isset( $this->params['limit'] ) && is_numeric( $this->params['limit'] ) ) {
				$limit  = absint( $this->params['limit'] );
				$offset = absint( $progress['offset'] ) + $limit;
			} else {
				$offset = absint( $progress['offset'] ) + 1;
			}

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

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Not enough permissions.', 'text_domain' ),
				array( 'status' => 401 )
			);
		}

		$user = wp_get_current_user();

		if ( ! $user instanceof WP_User && ! in_array( 'administrator', (array) $user->roles ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Not enough permissions.', 'text_domain' ),
				array( 'status' => 403 )
			);
		}

		return true;
		// return \current_user_can( 'manage_options' ); // User ID is 0
		return true;
	}

}
