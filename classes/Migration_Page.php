<?php
namespace Ultimate_Fields\Post_Types;

use Ultimate_Fields\UI\Settings\Screen;
use Ultimate_Fields\Template;
use Ultimate_Fields\Post_Types\Migration;

/**
 * Displays a page for migration of post types.
 *
 * @since 3.0
 */
class Migration_Page extends Screen {
	/**
	 * Returns the ID of the screen.
	 *
	 * @since 3.0
	 * @return string
	 */
	public function get_id() {
		return 'pt-migration';
	}

	/**
	 * Returns the title of the screen.
	 *
	 * @since 3.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Types & Taxonomies Migration', 'ultimate-post-types' );
	}

	/**
	 * Displays the page.
	 *
	 * @since 3.0
	 */
	public function display() {
		$engine = Template::instance();

		$existing = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => array( 'ultimate-post-type', 'upt-taxonomy' )
		));

		$pending = array();
		foreach ( $existing as $thing ) {
			if ( '2' !== get_post_meta( $thing->ID, 'uf_version', true ) ) {
				$pending[] = $thing;
			}
		}

		$args = array(
			'url'   => $this->url,
			'items' => $pending
		);

		$engine->include_template( 'post-types/migration.php', $args );
	}

	/**
	 * Loads the screen and adds action hooks if needed.
	 *
	 * @since 3.0
	 */
	public function load() {
		if ( 'POST' == $_SERVER[ 'REQUEST_METHOD' ] ) {
			$this->migrate();
		}
	}

	/**
	 * Performs the migration.
	 *
	 * @since 3.0
	 */
	public function migrate() {
		if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'uf-migrate-pts' ) ) {
			return;
		}

		define( 'ULTIMATE_FIELDS_MIGRATING', true );
		Migration::instance()->migrate();
		exit;
	}
}
