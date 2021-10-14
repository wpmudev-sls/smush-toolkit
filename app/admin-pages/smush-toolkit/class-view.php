<?php
/**
 * The Admin page for managing images.
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

use shush_toolkit\Core\Utils\Abstracts\Base;


/**
 * Class View
 *
 * @package shush_toolkit\App\Admin_Pages\Smush_Toolkit
 */
class View extends Base {

	/**
	 * Render the output.
	 *
	 * @since 1.0.0
	 *
	 * @return void Render the output.
	 */
	public function render( $params = array() ) {
		$unique_id = isset( $params['unique_id'] ) ? $params['unique_id'] : null;

		?>
		<div class="smush-toolkit-page-main">
			<div id="<?php echo $unique_id; ?>"></div>
		</div>
		<?php
	}

}
