<?php
namespace Ultimate_Fields\PT;

use Ultimate_Fields\UI\Migration as UI_Migration;
use Ultimate_Fields\PT\Migration_Page as Migration_Page;
use Ultimate_Fields\Helper\V1_Migrator;
use Ultimate_Fields\Container;
use Ultimate_Fields\UI\Field_Helper;
use Ultimate_Fields\PT\Type\Post_Type;
use Ultimate_Fields\PT\Type\Taxonomy;

/**
 * Handles the migration of data for Ultimate Post Types, from v1 to v3.
 *
 * @since 3.0
 */
class Migration extends UI_Migration {
	/**
	 * Returns a new instance of the class.
	 *
	 * @since 3.0
	 * @return Migration
	 */
	public static function instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Holds the key, which indicates if the migration is needed.
	 *
	 * @since 3.0
	 * @var string
	 */
	protected $option_key = 'upt_v1_to_v2';

	/**
	 * Performs initial checks and adds listeners.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();

		if ( $this->state == self::STATE_PENDING ) {
			add_filter( 'uf.settings.tabs', array( $this, 'change_settings_tabs' ) );
		}
	}

	/**
	 * Checks if a migration is needed.
	 *
	 * @since 3.0
	 */
	public function check() {
		$existing = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => array( 'ultimate-post-type', 'upt-taxonomy' )
		));

		if ( empty( $existing ) ) {
			update_option( $this->option_key, self::STATE_DONE );
		} else {
			update_option( $this->option_key, self::STATE_PENDING );
			update_option( $this->option_key . '_pending', count( $existing ) );
		}
	}

	/**
	 * Displays a notification that a migration is needed.
	 *
	 * @since 3.0
	 */
	public function normal_notification() {
		# Check if the settings page is being displayed
		if (
			isset( $_GET[ 'post_type' ] ) && isset( $_GET[ 'page' ] )
			&& 'ultimate-fields' == $_GET[ 'post_type' ] && 'settings' == $_GET[ 'page' ]
		) {
			return;
		}

		$count = intval( get_option( $this->option_key . '_pending' ) );
		$text  = __( 'Thank you for updating to Ultimate Post Types 2!', 'ultimate-post-types' );

		$text .= ' ' . sprintf(
		    _n(
				'There is %s post type or taxonomy that needs to be migrated from Ultimate Post Types version 1 to version 2.',
				'There are %s post types or taxonomies that need to be migrated from Ultimate Post Types version 1 to version 2.',
				$count, 'ultimate-post-types'
			),
		    $count
		);

		$text .= "\n\n" . __( 'Those post types and taxonomies will not be active until you migrate them, as the structure of Ultimate Post Types 2 is completely different and they cannot be loaded.', 'ultimate-post-types' );

		$text .= "\n\n" . sprintf(
			'<a href="%s" class="button-secondary uf-button"><span class="dashicons dashicons-hammer uf-button-icon"></span> %s</a>',
			admin_url( 'edit.php?post_type=ultimate-fields&amp;page=settings&amp;screen=pt-migration' ),
			__( 'Go to the migration page', 'ultimate-post-types' )
		);

		echo '<div class="notice notice-info">';
			echo wpautop( $text );
		echo '</div>';
	}

	/**
	 * Adds a post types migration tab to the settings screen.
	 *
	 * @since 3.0
	 *
	 * @param Ultimate_Fields\UI\Settings\Screen[] $screens
	 * @return Ultimate_Fields\UI\Settings\Screen[]
	 */
	public function change_settings_tabs( $screens ) {
		$screens[] = new Migration_Page;

		return $screens;
	}

	/**
	 * Migrates a set of containers.
	 *
	 * @since 3.0
	 */
	public function migrate() {
		$items = get_posts(array(
			'posts_per_page' => -1,
			'post_type'      => array( 'ultimate-post-type', 'upt-taxonomy' )
		));

		foreach ( $items as $item ) {
			if ( 'ultimate-post-type' == $item->post_type ) {
				$this->migrate_post_type( $item );
			} elseif ( 'upt-taxonomy' == $item->post_type ) {
				$this->migrate_taxonomy( $item );
			}
		}

		update_option( $this->option_key, self::STATE_DONE );
		delete_option( $this->option_key . '_pending' );

		wp_redirect( admin_url( 'edit.php?post_type=uf-post-type' ) );
		exit;
	}

	/**
	 * Migrates an array of fields from version 1 to 2.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $source The source fields.
	 * @return mixed[] The transformed fields.
	 */
	protected function migrate_fields_array( $source ) {
		$container = Container::create( 'temporary-container' );
		V1_Migrator::import_fields( $source, $container );

		$fields = array();
		foreach ( $container->get_fields() as $field ) {
			$fields[] = Field_Helper::get_field_data( $field );
		}

		return $fields;
	}

	/**
	 * Migrates a post type to version 2.
	 *
	 * @since 3.0
	 *
	 * @param WP_Post $post The post that is managing the post type.
	 */
	protected function migrate_post_type( $post ) {
		$prefix   = 'upt_pt_';
		$all_meta = array();

		foreach ( get_post_meta( $post->ID ) as $key => $value ) {
			if ( 0 !== stripos( $key, $prefix ) || count( $value ) > 1 )
				continue;

			$value = maybe_unserialize( $value[ 0 ] );
			$all_meta[ $key ] = $value;
		}

		# Migrate the fields
		$all_meta[ 'upt_pt_fields' ] = $this->migrate_fields_array( get_post_meta( $post->ID, 'fields', true ) );

		# Create the new post
		$postdata = array(
			'post_title' => $post->post_title,
			'post_type'  => Post_Type::get_slug(),
			'post_status' => 'publish',
			'post_content' => '',
			'meta_input' => $all_meta,
		);

		wp_insert_post( $postdata );
	}

	/**
	 * Migrates a taxonomy to version 2.
	 *
	 * @since 3.0
	 *
	 * @param WP_Post $post The post that is managing the taxonomy.
	 */
	protected function migrate_taxonomy( $post ) {
		$prefix   = 'upt_tax_';
		$all_meta = array();

		foreach ( get_post_meta( $post->ID ) as $key => $value ) {
			if ( 0 !== stripos( $key, $prefix ) || count( $value ) > 1 )
				continue;

			$value = maybe_unserialize( $value[ 0 ] );
			$all_meta[ $key ] = $value;
		}

		# Migrate the fields
		$all_meta[ 'upt_tax_fields' ] = $this->migrate_fields_array( get_post_meta( $post->ID, 'fields', true ) );

		# Create the new post
		$postdata = array(
			'post_title'   => $post->post_title,
			'post_type'    => Taxonomy::get_slug(),
			'post_status'  => 'publish',
			'post_content' => '',
			'meta_input'   => $all_meta,
		);

		wp_insert_post( $postdata );
	}

	/**
	 * Migrates a particular container by its post.
	 *
	 * @since 3.0
	 *
	 * @param WP_Post $container The container post.
	 */
	protected function migrate_container( $post ) {
		$data = array();

		$keys = array( 'uf_title', 'uf_description', 'uf_type', 'uf_options_page_type', 'uf_options_parent_page', 'uf_options_page_parent_slug', 'uf_options_page_slug', 'uf_options_icon', 'uf_options_menu_position', 'uf_postmeta_posttype', 'uf_postmeta_templates', 'uf_postmeta_levels', 'fields' );
		foreach ( $keys as $key ) {
			$data[ $key ] = get_post_meta( $post->ID, $key, true );
		}

		$container = uf_setup_container( $data );
		$helper = new Container_Helper;
		$helper->import_container( $container );
		$helper->save( $container->export() );
	}
}
