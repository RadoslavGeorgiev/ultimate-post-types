<?php
namespace Ultimate_Fields\Post_Types;

use Ultimate_Fields\Template;

/**
 * Displays a page in the admin for managing content types.
 *
 * @since 3.0
 */
class UI_Page {
	/**
	 * Returns an instance of the page.
	 *
	 * @since 3.0
	 *
	 * @return Ultimate_Fields\Post_Types\UI_page
	 */
	public static function instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Adds the necessary hooks to the admin.
	 *
	 * @since 3.0
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 9 );
	}

	/**
	 * Attaches the page to the admin.
	 *
	 * @since 3.0
	 */
	public function add_menu_page() {
		$page_title = $menu_title = __( 'Post Types', 'ultimate-fields' );
		$capability = 'manage_options';
		$menu_slug  = 'content-types';
		$function   = array( $this, 'display' );

		$id = add_submenu_page( 'edit.php?post_type=ultimate-fields', $page_title, $menu_title, $capability, $menu_slug, $function );

		# Make sure the page is loaded as soon as it's known that it exists
		add_action( "load-$id", array( $this, 'load' ) );
	}

	/**
	 * Gets called when the page is loading.
	 *
	 * @since 3.0
	 */
	public function load() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues scripts and styles for the page.
	 *
	 * @since 3.0
	 */
	public function enqueue() {
		wp_enqueue_style( 'ultimate-fields-css' );
	}

	/**
	 * Displays the page.
	 *
	 * @since 3.0
	 */
	public function display() {
		$engine = Template::instance();

		$engine->include_template( 'post-types/page' );
	}
}
