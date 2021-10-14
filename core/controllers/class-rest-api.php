<?php
/**
 * Rest endpoint.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\Core\Controllers
 */

namespace shush_toolkit\Core\Controllers;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use shush_toolkit\Core\Utils\Abstracts\Base;

/**
 * Class Capability
 *
 * @package shush_toolkit\Core\Controllers
 */
class Rest_Api extends Base {

	/**
	 * The rest url prefix. WP Default is wp-json.
	 *
	 * @var string $rest_url_prefix The rest url prefix.
	 *
	 * @since 1.0.0
	 */
	private $rest_url_prefix = 'rest-api';

	/**
	 * List of all Rest Endpoints of plugin.
	 *
	 * @var array $rest_routes The rest endpoints.
	 *
	 * @since 1.0.0
	 */
	protected static $rest_routes = array();



	/**
	 * Init Endpoints condroller
	 *
	 * @since 1.0.0
	 *
	 * @return void Initialize the Endpoint's methods.
	 */
	public function init() {
		// We need to add conditions as it is messing with all custom endpoints url prefix.
		// add_filter( 'rest_url_prefix', array( $this, 'rest_url_prefix' ) );
		add_action( 'rest_api_init', array( $this, 'set_endpoints' ) );
	}

	/**
	 * Filter the url prefix (default is wp-json) of the endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return string The url prefix.
	 */
	public function rest_url_prefix() {
		return $this->get_rest_url_prefix();
	}

	/**
	 * Set the route params of the endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return void Set Endpoints.
	 */
	public function set_endpoints() {

		$rest_routes = $this->get_rest_routes();

		if ( ! empty( $rest_routes ) ) {
			foreach ( $rest_routes as $rest_route ) {
				if ( ! is_array( $rest_route ) ||
					empty( $rest_route ) ||
					! isset( $rest_route['namespace'] ) ||
					! isset( $rest_route['route'] )
				) {
					continue;
				}

				$args = wp_parse_args(
					isset( $rest_route['args'] ) ? $rest_route['args'] : array(),
					array(
						// WP_REST_Server::READABLE || WP_REST_Server::EDITABLE || WP_REST_Server::CREATABLE || WP_REST_Server::DELETABLE || WP_REST_Server::ALLMETHODS
						// https://developer.wordpress.org/reference/classes/wp_rest_server/
						'methods'             => array( \WP_REST_Server::READABLE ),
						'callback'            => array( __CLASS__, 'callback' ),
						'permission_callback' => array( __CLASS__, 'permission_callback' ),
					)
				);

				$route_params = apply_filters(
					'pluginbase/rest_api_params',
					array(
						'namespace' => $rest_route['namespace'],
						'route'     => $rest_route['route'],
						'version'   => $rest_route['version'],
						'args'      => $args,
						'override'  => false,
					)
				);

				register_rest_route(
					\trailingslashit( $route_params['namespace'] ) . $route_params['version'],
					$route_params['route'],
					$route_params['args'],
					$route_params['override']
				);
			}
		}
	}

	/**
	 * Gets all the rest roures set for plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of Endpoints.
	 */
	private function get_rest_routes() {
		return self::$rest_routes;
	}

	/**
	 * Returns the url prefix set in class property.
	 *
	 * @since 1.0.0
	 *
	 * @return string The url prefix.
	 */
	protected function get_rest_url_prefix() {
		return apply_filters(
			'pluginbase/rest_url_prefix',
			$this->rest_url_prefix,
			$this
		);
	}

	/**
	 * Returns an array with all properties required for register_rest_route.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array with the register_rest_route properties.
	 */
	protected function add_endpoint( $endpoint ) {
		self::$rest_routes[] = $endpoint;
	}

	/**
	 * Rest request permissions. Returns a boolean or _doing_it_wrong notice. True for public access.
	 * https://make.wordpress.org/core/2020/07/22/rest-api-changes-in-wordpress-5-5/
	 *
	 * @since 1.0.0
	 *
	 * @return mixed|boolean|string Boolean or _doing_it_wrong notice.True for public access.
	 */
	public static function permission_callback( \WP_REST_Request $request ) {
		return true;
	}

	/*
	FROM : https://carlalexander.ca/designing-system-wordpress-rest-api-endpoints/
	To add Endpoints
	private function add_endpoint(MyPlugin_EndpointInterface $endpoint)
	{
		$this->endpoints[] = $endpoint;
	}
	*/

}
