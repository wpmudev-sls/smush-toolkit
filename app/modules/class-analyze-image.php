<?php
/**
 * Analyse an image.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\Core\Modules
 */

namespace shush_toolkit\App\Modules;

// Abort if called directly.
defined( 'WPINC' ) || die;

/**
 * Class Controller
 *
 * @package shush_toolkit\App\Madules
 */
class Analyze_Image {

	private $image_id      = null;
	private $meta          = array();
	private $report        = array();
	private $report_status = 'valid';

	protected function get_cases() {
		return array(
			'no_attachment_meta'  => array(
				'title'      => __( 'No Attachment meta found', 'shush-toolkit' ),
				'issue'      => __( 'It seems that attchment is missing the _wp_attachment_metadata in postmeta table for some reason.', 'shush-toolkit' ),
				'suggestion' => __( "If regenerating thumbnails doesn't help, you may try find a developer to run `wp_maybe_generate_attachment_metadata()` for this attachment.", 'shush-toolkit' ),
				'value'      => array( $this, 'check_attachment_meta' ),
			),

			'missing_image_sizes' => array(
				'title'      => __( 'Missing image sizes', 'shush-toolkit' ),
				'issue'      => __( "There are some images in site that this image doesn't have.", 'shush-toolkit' ),
				'suggestion' => __( 'You can try regenerating thumbnails', 'shush-toolkit' ),
				'value'      => array( $this, 'check_missing_image_sizes' ),
			),

			'unused_image_sizes'  => array(
				'title'      => __( 'Contains unused image sizes', 'shush-toolkit' ),
				'issue'      => __( 'Image contains a few image sizes that are not included in site image sizes. Smush will probably consider them to be Unsmushed', 'shush-toolkit' ),
				'suggestion' => __( 'The Regenerate Thumbnails plugin has an option to remove those unused image sizes', 'shush-toolkit' ),
				'value'      => array( $this, 'check_unused_image_sizes' ),
			),

			'greater_dimensions'  => array(
				'title'      => __( 'Has greater dimensions', 'shush-toolkit' ),
				'issue'      => __( 'The image width and/or height is greater than what set in Smush options.', 'shush-toolkit' ),
				'suggestion' => __( "It is possible that the filter `big_image_size_threshold` is used by some theme/plugin and WordPress can't re-size properly. A plugin conflict might help.", 'shush-toolkit' ),
				'value'      => array( $this, 'check_dimensions_limits' ),
			),

			'missing_files'       => array(
				'title'      => __( 'Missing some image files', 'shush-toolkit' ),
				'issue'      => __( 'There are some files missing for some image sizes.', 'shush-toolkit' ),
				'suggestion' => __( 'You can try regenerating thumbnails', 'shush-toolkit' ),
				'value'      => array( $this, 'check_missing_files' ),
			),
		);
	}

	public function __construct( int $image_id ) {
		$this->prepare_report( $image_id );
	}

	protected function prepare_report( int $image_id ) {
		$meta = \get_post_meta( $image_id, '_wp_attachment_metadata', true );

		$this->image_id = $image_id;
		$this->meta     = $meta;
		$this->report   = array(
			'image_id'    => $this->image_id,
			'image_link'  => $this->get_image_edit_link(),
			'suggestions' => array(),
		);
	}

	public function analyze() {
		if ( is_null( $this->image_id ) || empty( $this->meta ) ) {
			return false;
		}

		foreach ( $this->get_cases() as $case_key => $case ) {
			$this->analyze_case(
				array(
					'key'  => $case_key,
					'data' => $case,
				)
			);
		}

		return $this->get_report();
	}

	protected function analyze_case( $case = array() ) {
		if ( ! isset( $case['data']['title'] ) ||
			! isset( $case['data']['issue'] ) ||
			! isset( $case['data']['suggestion'] ) ||
			! isset( $case['data']['value'] )
		) {
			return false;
		}

		$result = is_callable( $case['data']['value'] ) ? call_user_func( $case['data']['value'] ) : null;

		if ( ! is_null( $result ) ) {
			$this->report_status                         = 'invalid';
			$this->report[ $case['key'] ]                = $result;
			$this->report['suggestions'][ $case['key'] ] = array(
				'title'      => $case['data']['title'],
				'issue'      => $case['data']['issue'],
				'suggestion' => $case['data']['suggestion'],
			);
		}
	}


	/**
	 * Check if image has the _wp_attachment_metadata meta.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed|boolean|null Returns .
	 */
	protected function check_attachment_meta() {
		return ( ! $this->meta || empty( $this->meta ) ) ? false : null;
	}

	/**
	 * Checks if there is any image size missing form image's meta.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with image sizes missing from image.
	 */
	protected function check_missing_image_sizes() {
		$site_sizes  = $this->get_site_image_sizes();
		$image_sizes = isset( $this->meta['sizes'] ) ? $this->meta['sizes'] : array();

		// If there are no image sizes set for image then all site image sizes are missing.
		if ( empty( $image_sizes ) ) {
			return $site_sizes;
		}

		// Return image sizes that are in $site_sizes but not in $image_sizes.
		$missing_sizes   = array_diff_key( $site_sizes, $image_sizes );
		$original_width  = $this->meta['width'];
		$original_height = $this->meta['height'];

		if ( ! empty( $missing_sizes ) ) {
			/**
			 * We need to confirm if the missing sizes are bigger than the dimmensions of the image,
			 * in which case we can ignore those
			 */
			foreach ( $missing_sizes as $key => $size_data ) {
				if ( $size_data['width'] > $original_width && $size_data['height'] > $original_height ) {
					// Ignore this missing size.
					unset( $missing_sizes[ $key ] );
				}
			}
		}

		return empty( $missing_sizes ) ? null : array_keys( $missing_sizes );
	}

	/**
	 * Checks if there is an unused image size in image's meta.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with unused image sizes.
	 */
	protected function check_unused_image_sizes() {
		$site_sizes  = $this->get_site_image_sizes();
		$image_sizes = isset( $this->meta['sizes'] ) ? $this->meta['sizes'] : array();

		// If there are no image sizes set for image then all site image sizes are missing.
		if ( empty( $image_sizes ) ) {
			return array_keys( $site_sizes );
		}

		$unused_sizes = array_keys( array_diff_key( $image_sizes, $site_sizes ) );
		// Return image sizes that are in $image_sizes but not in $site_sizes.
		return empty( $unused_sizes ) ? null : $unused_sizes;
	}

	/**
	 * Check if image meta contains any imae size larger than the ones set in Smush options.
	 *
	 * @since 1.0.0
	 *
	 * @return array Returns an array with images sizes grater than what was set in Smush options.
	 */
	protected function check_dimensions_limits() {
		if ( ! (bool) $this->get_smush_settings( 'resize' ) ) {
			return null;
		}

		$resize_sizes       = get_option( WP_SMUSH_PREFIX . 'resize_sizes' );
		$max_width          = $resize_sizes['width'];
		$max_height         = $resize_sizes['height'];
		$image_width        = $this->meta['width'];
		$image_height       = $this->meta['height'];
		$greater_dimensions = array();

		if ( $image_width > $max_width || $image_height > $max_height ) {
			$greater_dimensions[] = "{$max_width}x{$max_height}";
		}

		return empty( $greater_dimensions ) ? null : $greater_dimensions;
	}

	protected function check_missing_files() {

		$missing_files = array();

		$image_sizes = isset( $this->meta['sizes'] ) ? ( array_keys( $this->meta['sizes'] ) ) : array();

		if ( ! empty( $image_sizes ) ) {
			foreach ( $image_sizes as $size ) {
				$image_size_url = wp_get_attachment_image_src( $this->image_id, $size )[0];

				if ( 200 !== wp_remote_retrieve_response_code( wp_remote_get( $image_size_url ) ) ) {
					$missing_files[] = $image_size_url;
				}
			}
		}
		return empty( $missing_files ) ? null : $missing_files;
	}


	public function get_report() {
		$this->report['report_status'] = $this->report_status;
		return $this->report;
	}

	protected function get_image_edit_link() {
		$edit_link   = get_edit_post_link( $this->image_id );
		$image_thumb = wp_get_attachment_image_url( $this->image_id );

		return "<a href=\"$edit_link\" target=\"_blank\" class=\"image-id-{$this->image_id}\"><img src=\"{$image_thumb}\" style=\"width: 60px;\" /></a>";
	}

	/**
	 * Get all image sizes of site.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array with site's image sizes.
	 */
	public function get_site_image_sizes() {
		if ( ! empty( $this->site_image_sizes ) ) {
			return $this->site_image_sizes;
		}

		$this->site_image_sizes = wp_get_registered_image_subsizes();

		return $this->site_image_sizes;
	}

	/**
	 * Get Smush settings.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Returns value from Smush settings for a given key.
	 */
	protected function get_smush_settings( $key = '' ) {
		return \Smush\Core\Settings::get_instance()->get( $key );
	}

}
