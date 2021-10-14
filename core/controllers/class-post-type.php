<?php
/**
 * An Abstract calls for Post Type.
 *
 * @link    https://gist.github.com/panoslyrakis/
 * @since   1.0.0
 *
 * @author  Panos Lyrakis @ WPMUDEV
 * @package shush_toolkit\Core\Controllers
 */

namespace shush_toolkit\Core\Controllers;

// Abort if called directly.
defined( 'WPINC' ) || die;

// use shush_toolkit\Core\Endpoints;
// use shush_toolkit\Core\Controllers as Core_controllers;
use shush_toolkit\Core\Utils\Abstracts\Base;

/**
 * Class Core
 *
 * @package shush_toolkit\Core\Post_Type
 */
abstract class Post_Type extends Base {

	/**
	 * The Post Type slug.
	 *
	 * @var string $slug The name/slug of the post type.
	 *
	 * @since 1.0.0
	 */
	public $slug;

	/**
	 * The Post Type labels.
	 *
	 * @var array $labels The labels of the post type.
	 *
	 * @since 1.0.0
	 */
	public $labels;

	/**
	 * The Post Type args.
	 *
	 * @var array $args The args of the post type.
	 *
	 * @since 1.0.0
	 */
	public $args;

	/**
	 * Init Post Type. Register and add metaboxes
	 *
	 * @since 1.0.0
	 *
	 * @return void Initialize the post type.
	 */
	public function init() {
		$this->set_slug();
		$this->set_labels();
		$this->set_args();
		//$this->register_post_type();
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Post Type Slug/Name
	 *
	 * @since 1.0.0
	 *
	 * @return string Slug of post type.
	 */
	public function get_slug() : string {
		return $this->slug;
	}

	/**
	 * Set Post Type slug
	 *
	 * @since 1.0.0
	 *
	 * @return void Set the Podt Type Slug.
	 */
	abstract protected function set_slug();

	/**
	 * Get Post Type Labels
	 *
	 * @since 1.0.0
	 *
	 * @return array Labels of post type.
	 */
	public function get_labels() : string {
		return $this->labels;
	}

	/**
	 * Set Post Type labels
	 *
	 * @since 1.0.0
	 *
	 * @return void Set the Podt Type Labels.
	 */
	abstract protected function set_labels();

	/**
	 * Get Post Type Args
	 *
	 * @since 1.0.0
	 *
	 * @return array Args of post type.
	 */
	public function get_args() : array {
		return $this->args;
	}

	/**
	 * Set Post Type Args
	 *
	 * @since 1.0.0
	 *
	 * @return void Set Post Type Args.
	 */
	abstract protected function set_args();

	/**
	 * Get Post Type Metaboxes
	 *
	 * @since 1.0.0
	 *
	 * @return array Array with the mateboxes.
	 */
	public function get_metaboxes() : array{}

	/**
	 * Register the Post Type
	 *
	 * @since 1.0.0
	 *
	 * @return void Register Post Type.
	 */
	public function register_post_type() {
		register_post_type( $this->get_slug(), $this->get_args() );
	}

	/**
	 * Add the Post Type Metaboxes.
	 *
	 * @since 1.0.0
	 *
	 * @return void Add Metaboxes.
	 */
	public function add_metaboxes() {
		$metaboxes = $this->get_metaboxes();
	}

}
