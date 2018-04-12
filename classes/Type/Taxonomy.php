<?php
namespace Ultimate_Fields\Post_Types\Type;

use Ultimate_Fields\Container;
use Ultimate_Fields\Field;
use Ultimate_Fields\Post_Types\Type;
use Ultimate_Fields\Post_Types\Controller\Taxonomy as Controller;
use Ultimate_Fields\UI\Dump_Beautifier;
use Ultimate_Fields\Template;

/**
 * Manages taxonomies in UPT.
 *
 * @since 3.0
 */
class Taxonomy extends Type {
	/**
	 * Holds all existing taxonomies from UPT.
	 *
	 * @since 3.0
	 * @var Ultimate_Fields\Post_Types\Controller\Taxonomy[]
	 */
	protected $existing = array();

	/**
	 * Returns the slug for the type.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'uf-taxonomy';
	}

	/**
	 * Registers the post type for managing taxonomies in the admin.
	 *
	 * @since 3.0
	 */
	public function register() {
		$args = array(
			'hierarchical'        => true,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=ultimate-fields',
			'menu_position'       => 91,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),

			'labels' => array(
				'name'                => __( 'Taxonomies', 'upt' ),
				'singular_name'       => __( 'Taxonomy', 'upt' ),
				'add_new'             => __( 'Add Taxonomy', 'upt' ),
				'add_new_item'        => __( 'Add Taxonomy', 'upt' ),
				'edit_item'           => __( 'Edit Taxonomy', 'upt' ),
				'new_item'            => __( 'New Taxonomy', 'upt' ),
				'view_item'           => __( 'View Taxonomy', 'upt' ),
				'search_items'        => __( 'Search Taxonomies', 'upt' ),
				'not_found'           => __( 'No Taxonomies found', 'upt' ),
				'not_found_in_trash'  => __( 'No Taxonomies found in Trash', 'upt' ),
				'parent_item_colon'   => __( 'Parent Taxonomy:', 'upt' ),
				'menu_name'           => __( 'Taxonomies', 'upt' ),
			)
		);

		register_post_type( self::get_slug(), $args );

		# Fetch existing post types and register them
		$args = array(
			'post_type'      => self::get_slug(),
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order'
		);

		foreach ( get_posts( $args ) as $registered ) {
			$controller = new Controller( $registered );
			$controller->register();
			$this->existing[ $registered->ID ] = $controller;
		}
	}

	/**
	 * Registers the fields for the editable post type.
	 *
	 * @since 3.0
	 */
	public function register_fields() {
		$fields = array();

		$fields[] = Field::create( 'tab', 'main_tab', __( 'Slug & Labels', 'upt' ) )
			->set_icon( 'dashicons dashicons-list-view' );
		$fields = array_merge( $fields, $this->main_tab_fields() );
		$fields[] = Field::create( 'tab', 'general_tab', __( 'General Settings', 'upt' ) )
			->set_icon( 'dashicons dashicons-admin-generic' );
		$fields = array_merge( $fields, $this->general_tab_fields() );
		$fields[] = Field::create( 'tab', 'urls_tab', __( 'URLs', 'upt' ) )
			->set_icon( 'dashicons dashicons-admin-site' );
		$fields = array_merge( $fields, $this->urls_tab_fields() );
		$fields[] = Field::create( 'tab', 'fields_tab', __( 'Fields', 'uf' ) )
			->set_icon( 'dashicons dashicons-list-view' );
		$fields = array_merge( $fields, $this->fields_tab_fields() );

		$container = Container::create( __( 'Taxonomy Settings', 'ultimate-fields' ) );
		$container->add_location( 'post_type', self::get_slug() );
		$container->add_fields( $fields );
		$container->set_id( 'upt_taxonomy' );

		/**
		 * Allow the properties of the container to be modified.
		 *
		 * @since 3.0
		 *
		 * @param Ultimate_Fields\Container        $container The container with the custom settings.
		 * @param Ultimate_Fields\Post_Types\Type\Taxonomy $object    The object that contains most of the necessary callbacks.
		 */
		do_action( 'upt.taxonomy.container', $container, $this );
	}

	/**
	 * Generates the fields for the main tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function main_tab_fields() {
		$fields = array(
			Field::create( 'text', 'upt_tax_slug', __( 'Slug', 'upt' ) )
				->set_description( __( 'This slug will be used when quierying posts from the post type or in URLs by default. Please use only lowercase letters, dashes and numbers!', 'upt' ) )
				->required( '/^[a-z0-9\-]+$/' ),
			Field::create( 'text', 'upt_tax_name', __( 'Plural Name', 'upt' ) )
				->required()
				->set_description( __( 'This is plural name of the taxonomy (e.g. Categories).', 'upt' ) ),
			Field::create( 'text', 'upt_tax_singular_name', __( 'Singular Name', 'upt' ) )
				->required()
				->set_description( __( 'This is the singular name of the taxonomy (e.g. Category).', 'upt' ) ),
			Field::create( 'multiselect', 'upt_tax_post_types', __( 'Post Types', 'upt' ) )
				->set_input_type( 'checkbox' )
				->set_options_callback( array( $this, 'get_post_type_options' ) )
				->required()
				->set_description( __( 'The taxonomy will be associated with those post types.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_fine_tune', __( 'Fine tune labels', 'upt' ) )
				->fancy()
				->set_default_value( false )
				->set_text( __( 'All other labels for the taxonomy are generated automatically by using the "Name" & "Singular Name" fields&apos; values. If you want to change a detail in those labels, check this.', 'upt' ) ),
			Field::create( 'text', 'upt_tax_add_new_item', __( 'Add New Item', 'upt' ) )
				->set_description( __( 'The adding label that will appear in other places of the admin/front end. (e.g. Add New Page).', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_edit_item', __( 'Edit Item', 'upt' ) )
				->set_description( __( 'The Edit Item label (e.g. Edit Page).', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_search_items', __( 'Search Items', 'upt' ) )
				->set_description( __( 'The Search Items label (e.g. Search Pages).', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_not_found', __( 'Not Found', 'upt' ) )
				->set_description( __( 'The Not Found label (e.g. No Pages found).', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_parent_item_colon', __( 'Parent Item Colon', 'upt' ) )
				->set_description( __( 'The Parent Item Colon label (e.g. Parent Page).', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_popular_items', __( 'Popular Items', 'upt' ) )
				->set_description( __( 'Popular Writers', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_all_items', __( 'All Items', 'upt' ) )
				->set_description( __( 'Ex. All Writers', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_new_item_name', __( 'New Item Name', 'upt' ) )
				->set_description( __( 'New Writer Name', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_separate_items_with_commas', __( 'Separate Items With Commas', 'upt' ) )
				->set_description( __( 'Separate writers with commas', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_add_or_remove_items', __( 'Add Or Remove Items', 'upt' ) )
				->set_description( __( 'Add or remove writers', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
			Field::create( 'text', 'upt_tax_choose_from_most_used', __( 'Choose From Most Used', 'upt' ) )
				->set_description( __( 'Choose from the most used writers', 'upt' ) )
				->add_dependency( 'upt_tax_fine_tune' ),
		);

		return $fields;
	}

	/**
	 * Generates the fields for the general tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function general_tab_fields() {
		$fields = array(
			Field::create( 'checkbox', 'upt_tax_hierarchical', __( 'Hierarchical', 'upt' ) )
				->fancy()
				->set_default_value( true )
				->set_text( __( 'Allows Parent to be specified. Also, non-hierarchical taxonomies work like tags, meaning that on post type screens the user has to manually enter terms manually.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_public', __( 'Public', 'upt' ) )
				->fancy()
				->set_default_value( true )
				->set_text( __( 'Controls how the type is visible to authors and readers.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_show_ui', __( 'Show UI', 'upt' ) )
				->fancy()
				->set_default_value( true )
				->set_text( __( 'Whether to generate a default UI for managing this taxonomy in the admin.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_show_in_nav_menus', __( 'Show In Nav Menus', 'upt' ) )
				->fancy()
				->set_default_value( true )
				->add_dependency( 'upt_tax_public' )
				->set_text( __( 'Whether the taxonomy is available for selection in navigation menus.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_show_admin_column', __( 'Show admin column', 'upt' ) )
				->fancy()
				->set_default_value( true )
				->add_dependency( 'upt_tax_public' )
				->set_text( __( 'Show the taxonomy terms in the post type listing.', 'upt' ) ),
		);

		return $fields;
	}

	/**
	 * Generates the fields for the urls tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function urls_tab_fields() {
		$fields = array(
			Field::create( 'checkbox', 'upt_tax_rewrite_enable', __( 'Enable URL rewrite', 'upt' ) )
				->fancy(),
			Field::create( 'text', 'upt_tax_rewrite_slug', __( 'Slug', 'upt' ) )
				->set_description( __( 'Customize the permalink structure slug, ex. <strong>genre</strong>/', 'upt' ) ),
			Field::create( 'checkbox', 'upt_tax_rewrite_with_front', __( 'With Front', 'upt' ) )
				->fancy()
				->set_text( __( 'Include the blog base for the URLs of this taxonomy.', 'upt' ) )
				->set_default_value( false )
		);

		return $fields;
	}

	/**
	 * Generates the fields for the fields tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function fields_tab_fields() {
		$fields = array(
			Field::create( 'fields', 'upt_tax_fields', __( 'Fields', 'upt' ) )
				->set_attr(array(
					'class' => 'upt-fields'
				))
		);

		if ( ! defined( 'ULTIMATE_FIELDS_PRO' ) ) {
			$html = <<<HTML
<p><strong>This functionality is only available for Ultimate Fields Pro users.</strong></p>
<p>To add fields to a taxonomy, you need to use the <strong>Terms Meta</strong> container, which is a premium feature. You can add fields below, but they wil not be active until you get the plugin.</p>
<p>Ultimate Fields Premium is available at <a href="http://ultimate-fields.com/premium/" target="_blank">http://ultimate-fields.com/premium/</a>.</p>
HTML;

			$fields[ 0 ]->set_description( $html );
		}

		return $fields;
	}

	/**
	 * Returns the options for the post type select.
	 *
	 * @since 3.0
	 *
	 * @return string[]
	 */
	public function get_post_type_options() {
		$post_types = array();
		$excluded = apply_filters( 'uf.excluded_post_types', array( 'attachment', 'ultimatefields' ) );
		$raw = get_post_types( array(
			'show_ui' => true
		), 'objects' );

		foreach ( $raw as $id => $post_type ) {
			if ( in_array( $id, $excluded ) ) {
				continue;
			}

			$post_types[ $id ] = $post_type->labels->name;
		}

		return $post_types;
	}

	/**
	 * Changes the columns for the post type.
	 *
	 * @since 3.0
	 *
	 * @param string[] $columns The default columns.
	 * @return string[] The modified columns.
	 */
	public function manage_columns( $columns ) {
		unset( $columns[ 'date' ] );

		$columns[ 'slug' ]     = __( 'Slug', 'upt' );
		$columns[ 'singular' ] = __( 'Singular name', 'upt' );
		$columns[ 'plural' ]   = __( 'Plural name', 'upt' );

		return $columns;
	}

	/**
	 * Output the values for admin columns.
	 *
	 * @since 3.0
	 *
	 * @param string $column_name The name of the column.
	 * @param int    $post_id     The ID of the displayed posts.
	 */
	public function output_columns( $column_name, $post_id ) {
		switch( $column_name ) {
			case 'slug':
				echo esc_html( get_post_meta( $post_id, 'upt_tax_slug', true ) );
				break;

			case 'singular':
				echo esc_html( get_post_meta( $post_id, 'upt_tax_singular_name', true ) );
				break;

			case 'plural':
				echo esc_html( get_post_meta( $post_id, 'upt_tax_name', true ) );
				break;
		}
	}

	/**
	 * When a container is updated, it's message should not be "Post Published".
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $messages The current group of messages.
	 * @return mixed[]
	 */
	public function change_updated_message( $messages ) {
		if ( ! isset( $_GET[ 'post' ] ) )
			return $messages;

		$p = get_post( $_GET[ 'post' ] );
		if ( $p->post_type != $this->slug ) {
			return $messages;
		}

		$message = __( 'Taxonomy saved.', 'upt' );
		$messages[ 'post' ][ 1 ] = $messages[ 'post' ][ 6 ] = $message;

		return $messages;
	}

	/**
	 * Adds options to the export groups on the UF settings screen.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $groups The existing options.
	 * @return mixed[]
	 */
	public function change_export_groups( $groups, $screen ) {
		if ( empty( $this->existing ) ) {
			return $groups;
		}

		$options = array();
		foreach ( $this->existing as $post_type ) {
			$options[ $post_type->ID ] = $post_type->post_title;
		}

		$groups[] = array(
			'label'   => __( 'Taxonomies', 'upt' ),
			'options' => $options
		);

		return $groups;
	}

	/**
	 * Indicates that the type can be imported through the UI.
	 *
	 * @since 3.0
	 *
	 * @param string[] $types The importable types.
	 * @return string[]
	 */
	public function add_import_type( $types ) {
		$types[] = 'taxonomy';
		return $types;
	}

	/**
	 * Adds all existing containers' hashes to the import procedure.
	 *
	 * @since 3.0
	 *
	 * @param string[] $existing The existing hashes.
	 * @return string[]
	 */
	public function add_existing_items_to_ui( $existing ) {
		foreach ( $this->existing as $type ) {
			$existing[] = $type->get_hash();
		}

		return $existing;
	}

	/**
	 * Imports a taxonomy to the database.
	 *
	 * @since 3.0
	 *
	 * @param bool    $imported Indicates whether the thing has already been imported.
	 * @param mixed[] $data     The data to import.
	 * @return mixed
	 */
	public function import( $imported, $item ) {
		if ( $imported || 'taxonomy' != $item[ 'type' ] ) {
			return $imported;
		}

		$controller = new Controller();
		return $controller->import( $item );
	}

	/**
	 * Adds taxonomy registration to a PHP export.
	 *
	 * @since 3.0
	 *
	 * @param int[]   $ids The IDs that should be exported.
	 * @param mixed[] $args The arguments for the export.
	 */
	public function export_type_to_php( $ids, $args ) {
		$pairs = array();

		foreach( $ids as $id ) {
			if( ! isset( $this->existing[ $id ] ) )
				continue;

			$dump = new Dump_Beautifier( $this->existing[ $id ]->get_args() );
			$dump->indent( 1 );
			if( $args[ 'textdomain' ] ) {
				$dump->add_textdomain( $args[ 'textdomain' ] );
			}

			$pairs[] = array(
				$this->existing[ $id ]->get_slug(),
				$this->existing[ $id ]->get_post_types(),
				$dump
			);
		}

		if( empty( $pairs ) ) {
			return;
		}

		Template::instance()->include_template( 'post-types/taxonomy-php-export.php', array(
			'pairs'         => $pairs,
			'function_name' => 'uf_taxonomies_' . round( microtime( true ) )
		));
	}
}
