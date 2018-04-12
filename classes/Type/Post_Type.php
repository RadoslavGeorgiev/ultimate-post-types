<?php
namespace Ultimate_Fields\Post_Types\Type;

use Ultimate_Fields\Container;
use Ultimate_Fields\Field;
use Ultimate_Fields\Post_Types\Type;
use Ultimate_Fields\UI\Post_Type as UI_Post_Type;
use Ultimate_Fields\Post_Types\Controller\Post_Type as Controller;
use Ultimate_Fields\UI\Dump_Beautifier;
use Ultimate_Fields\Template;

/**
 * Manages post types in UPT.
 *
 * @since 3.0
 */
class Post_Type extends Type {
	/**
	 * Holds all existing post types from UPT.
	 *
	 * @since 3.0
	 * @var Ultimate_Fields\Post_Types\Controller\Post_Type[]
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
		return 'uf-post-type';
	}

	/**
	 * Adds additional callbacks for the post type.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'uf.ui.export_after', array( $this, 'export_type_appearance_to_php' ), 10, 2 );
	}

	/**
	 * Registers the post type for managing post types in the admin.
	 *
	 * @since 3.0
	 */
	public function register() {
		$args = array(
			'hierarchical'        => true,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=' . UI_Post_Type::instance()->get_slug(),
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'menu_position'       => 91,
			'supports'            => array( 'title' ),

			'labels' => array(
				'name'                => __( 'Post Types', 'upt' ),
				'singular_name'       => __( 'Post Type', 'upt' ),
				'add_new'             => __( 'Add Post Type', 'upt' ),
				'add_new_item'        => __( 'Add Post Type', 'upt' ),
				'edit_item'           => __( 'Edit Post Type', 'upt' ),
				'new_item'            => __( 'New Post Type', 'upt' ),
				'view_item'           => __( 'View Post Type', 'upt' ),
				'search_items'        => __( 'Search Post Types', 'upt' ),
				'not_found'           => __( 'No Post Types found', 'upt' ),
				'not_found_in_trash'  => __( 'No Post Types found in Trash', 'upt' ),
				'parent_item_colon'   => __( 'Parent Post Type:', 'upt' ),
				'menu_name'           => __( 'Post Types', 'upt' ),
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

		$fields[] = Field::create( 'tab', 'main', __( 'Slug & Labels', 'ultimate-fields' ) )
			->set_icon( 'dashicons dashicons-list-view' );
		$fields = array_merge( $fields, $this->main_tab_fields() );

		$fields[] = Field::create( 'tab', 'general', __( 'General', 'ultimate-fields' ) )
			->set_icon( 'dashicons dashicons-admin-generic' );
		$fields = array_merge( $fields, $this->general_tab_fields() );

		$fields[] = Field::create( 'tab', 'urls', __( 'URLs', 'ultimate-fields' ) )
			->set_icon( 'dashicons dashicons-admin-site' );
		$fields = array_merge( $fields, $this->urls_tab_fields() );

		$fields[] = Field::create( 'tab', 'fields', __( 'Fields', 'ultimate-fields' ) )
			->set_icon( 'dashicons dashicons-list-view' )
			->hide_label();
		$fields = array_merge( $fields, $this->fields_tab_fields() );

		$fields[] = Field::create( 'tab', 'layout_tab', __( 'Appearance', 'ultimate-fields' ) )
			->set_icon( 'dashicons dashicons-align-center' );
		$fields = array_merge( $fields, $this->layout_tab_fields() );

		$container = Container::create( __( 'Post Type Settings', 'ultimate-fields' ) );
		$container->add_location( 'post_type', self::get_slug() );
		$container->add_fields( $fields );
		$container->set_id( 'upt_post_type' );

		/**
		 * Allow the properties of the container to be modified.
		 *
		 * @since 3.0
		 *
		 * @param Ultimate_Fields\Container         $container The container with the custom settings.
		 * @param Ultimate_Fields\Post_Types\Type\Post_Type $object    The object that contains most of the necessary callbacks.
		 */
		do_action( 'upt.post_type.container', $container, $this );
	}

	/**
	 * Returns the fields for the main tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function main_tab_fields() {
		if ( class_exists( 'Ultimate_Fields\\Pro\\Field\\Icon' ) ) {
			$icon_field = Field::create( 'icon', 'upt_pt_icon', __( 'Icon', 'upt' ) )
				->add_set( 'dashicons' );
		} else {
			$icon_field = Field::create( 'text', 'upt_pt_icon', __( 'Icon', 'upt' ) );
		}


		$fields = array(
			Field::create( 'text', 'upt_pt_slug', __( 'Slug', 'upt' ) )
				->set_description( __( 'This slug will be used when quierying posts from the post type or in URLs by default. It must be unique and not among the reserved <a href="http://codex.wordpress.org/Function_Reference/register_post_type#Reserved_Post_Types" target="_blank">post types</a>. Please use only lowercase letters, dashes and numbers!', 'upt' ) )
				->required( '/^[a-z0-9\-]+$/' ),
			$icon_field
				->set_description( __( 'This icon wil appear in the admin menu.', 'upt' ) ),
			Field::create( 'text', 'upt_pt_name', __( 'Plural Name', 'upt' ) )
				->set_description( __( 'This is plural name of the post type (e.g. Books).', 'upt' ) )
				->required(),
			Field::create( 'text', 'upt_pt_singular_name', __( 'Singular Name', 'upt' ) )
				->set_description( __( 'This is the singular name of the post type (e.g. Book).', 'upt' ) )
				->required(),
			Field::create( 'checkbox', 'upt_pt_fine_tune', __( 'Fine-tune labels', 'upt' ) )
				->fancy()
				->set_default_value( false )
				->set_text( __( 'All other labels for the post type are generated automatically by using the &quot;Name&quot; &amp; &quot;Singular Name&quot; fields&apos; values. If you want to change a detail in those labels, check this.', 'upt' ) ),

			Field::create( 'section', 'upt_labels_section', __( 'Labels', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_add_new', __( 'Add New', 'upt' ) )
				->set_description( __( 'The label for adding in the post type&apos;s section (e.g. Add Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_add_new_item', __( 'Add New Item', 'upt' ) )
				->set_description( __( 'The adding label that will appear in other places of the admin/front end. (e.g. Add New Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_edit_item', __( 'Edit Item', 'upt' ) )
				->set_description( __( 'The Edit Item label (e.g. Edit Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_new_item', __( 'New Item', 'upt' ) )
				->set_description( __( 'The New Item label (e.g. New Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_view_item', __( 'View Item', 'upt' ) )
				->set_description( __( 'The View Item label (e.g. View Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_search_items', __( 'Search Items', 'upt' ) )
				->set_description( __( 'The Search Items label (e.g. Search Books).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_not_found', __( 'Not Found', 'upt' ) )
				->set_description( __( 'The Not Found label (e.g. No Books found).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_not_found_in_trash', __( 'Not Found In Trash', 'upt' ) )
				->set_description( __( 'The Not Found In Trash label (e.g. No Books found in Trash).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' ),
			Field::create( 'text', 'upt_pt_parent_item_colon', __( 'Parent Item Colon', 'upt' ) )
				->set_description( __( 'The Parent Item Colon label (e.g. Parent Book).', 'upt' ) )
				->add_dependency( 'upt_pt_fine_tune' )
		);

		return $fields;
	}

	/**
	 * Returns the fields for the general tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function general_tab_fields() {
		$fields = array(
			Field::create( 'checkbox', 'upt_pt_hierarchical', __( 'Hierarchical', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Allows Parent to be specified. In the &apos;supports&apos; tab, please check &apos;page-attributes&apos; to show the parent select box on the editor page.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_public', __( 'Public', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Controls how the type is visible to authors and readers.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_show_in_menu', __( 'Show In Menu', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Whether to show the post type in the admin menu.', 'upt' ) ),
			Field::create( 'select', 'upt_pt_menu_placement', __( 'Menu order', 'upt' ) )
				->set_input_type( 'radio' )
				->set_default_value( 'default' )
				->add_options(array(
					'default' => __( 'Default<em>: Appear before the Appearance section</em>', 'upt' ),
					'index'   => __( 'Specific position', 'upt' )
				))
				->add_dependency( 'upt_pt_show_in_menu' ),
			Field::create( defined( 'ULTIMATE_FIELDS_PRO' ) ? 'number' : 'text', 'upt_pt_menu_position', __( 'Menu Position', 'upt' ) )
				->add_dependency( 'upt_pt_show_in_menu' )
				->add_dependency( 'upt_pt_menu_placement', 'index' )
				->set_description( __( 'Be careful with this setting, because you might silently overwrite another items as WordPress does not check if the particular position is free.', 'upt' ) ),
			Field::create( 'section', 'upt_pt_separator_advanced', __( 'Advanced Settings', 'upt' ) )
				->set_description( __( 'Please don\'t edit those settings unless you really know what you are doing!', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_show_ui', __( 'Show UI', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Whether to generate a default UI for managing this post type in the admin.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_show_in_admin_bar', __( 'Show In Admin Bar', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Whether to make this post type available in the WordPress admin bar.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_show_in_nav_menus', __( 'Show In Nav Menus', 'upt' ) )
				->set_default_value( true )
				->add_dependency( 'upt_pt_public' )
				->fancy()
				->set_text( __( 'Whether post_type is available for selection in navigation menus.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_publicly_queryable', __( 'Publicly Queryable', 'upt' ) )
				->set_default_value( true )
				->add_dependency( 'upt_pt_public' )
				->fancy()
				->set_text( __( 'Whether queries can be performed on the front end.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_exclude_from_search', __( 'Exclude from Search', 'upt' ) )
				->set_default_value( true )
				->add_dependency( 'upt_pt_public' )
				->fancy()
				->set_text( __( 'Whether to exclude posts with this post type from front end search results.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_has_archive', __( 'Has Archive', 'upt' ) )
				->set_default_value( false )
				->add_dependency( 'upt_pt_public' )
				->fancy()
				->set_text( __( 'Enables post type archives.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_can_export', __( 'Can Export', 'upt' ) )
				->set_default_value( true )
				->fancy()
				->set_text( __( 'Can this post type be exported.', 'upt' ) ),
			Field::create( 'section', 'supports_section', __( 'Supports', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_supports_title', __( 'Title', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow the post type posts to have a title.', 'upt' ) )
				->set_default_value( true ),
			Field::create( 'checkbox', 'upt_pt_supports_editor', __( 'Editor', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow the post type posts to have content, entered through the TinyMCE (WYSIWYG) editor.' ) )
				->set_default_value( true ),
			Field::create( 'checkbox', 'upt_pt_supports_author', __( 'Author', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow administrators to choose who is the author of the current post.', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_supports_thumbnail', __( 'Thumbnail', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow setting a featured image to the post. <strong>Please</strong> be sure that your theme supprts <strong>post-thumbnails</strong>', 'upt' ) )
				->set_default_value( false ),
			Field::create( 'checkbox', 'upt_pt_supports_excerpt', __( 'Excerpt', 'upt' ) )
				->fancy()
				->set_text( __( 'The WordPress Excerpt is an optional summary or description of a post; in short, a post summary', 'upt' ) )
				->set_default_value( false ),
			Field::create( 'checkbox', 'upt_pt_supports_trackbacks', __( 'Trackbacks', 'upt' ) )
				->fancy()
				->set_text( __( 'A trackback is a way of cross referencing two blog posts. ', 'upt' ) )
				->set_default_value( false ),
			Field::create( 'checkbox', 'upt_pt_supports_custom_fields', __( 'Custom Fields', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow managing custom fields by the default WordPress way. If you are planning to add fields generated by Ultimate Fields, you will prefer to leave this unchecked!', 'upt' ) )
				->set_default_value( false ),
			Field::create( 'checkbox', 'upt_pt_supports_comments', __( 'Comments', 'upt' ) )
				->fancy()
				->set_text( 'Enable (also will see comment count balloon on edit screen)' )
				->set_default_value( true ),
			Field::create( 'checkbox', 'upt_pt_supports_revisions', __( 'Revisions', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow the storing of revisions', 'upt' ) )
				->set_default_value( true ),
			Field::create( 'checkbox', 'upt_pt_supports_page_attributes', __( 'Page Attributes', 'upt' ) )
				->fancy()
				->set_text( __( 'Display a box that contains the menu order field, or parent option for hierarchical post types.', 'upt' ) )
				->set_default_value( true ),
			Field::create( 'checkbox', 'upt_pt_supports_post_formats', __( 'Post Formats', 'upt' ) )
				->fancy()
				->set_text( __( 'Allow the post type to have formats like video, photo, quote, etc. Select the formats in the next field.', 'upt' ) )
				->set_default_value( false ),
			Field::create( 'multiselect', 'upt_pt_formats', __( 'Supported Formats', 'upt' ) )
				->set_input_type( 'checkbox' )
				->set_default_value( array() )
				->add_options(array(
					'aside'   => __( 'Aside', 'upt' ),
					'audio'   => __( 'Audio', 'upt' ),
					'chat'    => __( 'Chat', 'upt' ),
					'gallery' => __( 'Gallery', 'upt' ),
					'image'   => __( 'Image', 'upt' ),
					'link'    => __( 'Link', 'upt' ),
					'quote'   => __( 'Quote', 'upt' ),
					'status'  => __( 'Status', 'upt' ),
					'video'   => __( 'Video', 'upt' )
				))
				->add_dependency( 'upt_pt_supports_post_formats' )
		);

		return $fields;
	}

	/**
	 * Returns the fields for the urls tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function urls_tab_fields() {
		$fields = array(
			Field::create( 'checkbox', 'upt_pt_rewrite_enable', __( 'Enable URL rewrite', 'upt' ) )
				->set_default_value( true )
				->set_text( __( 'Enable', 'upt' ) )
				->fancy(),

			Field::create( 'text', 'upt_pt_rewrite_slug', __( 'Slug', 'upt' ) )
				->set_description( __( 'Customize the permalink structure slug, ex. <strong>books</strong>/', 'upt' ) ),
			Field::create( 'checkbox', 'upt_pt_rewrite_with_front', __( 'With Front', 'upt' ) )
				->set_text( __( 'Include the blog base for the URLs of this post type.', 'upt' ) )
				->fancy(),
			Field::create( 'checkbox', 'upt_pt_rewrite_feeds', __( 'Feeds', 'upt' ) )
				->set_text( __( 'Should a feed permalink structure be built for this post type.', 'upt' ) )
				->set_default_value( true )
				->fancy(),
			Field::create( 'checkbox', 'upt_pt_rewrite_pages', __( 'Pages', 'upt' ) )
				->set_text( __( 'Should the permalink structure provide for pagination.', 'upt' ) )
				->set_default_value( true )
				->fancy(),
		);

		return $fields;
	}

	/**
	 * Returns the fields for the fields tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function fields_tab_fields() {
		$fields = array(
			Field::create( 'fields', 'upt_pt_fields', __( 'Fields', 'upt' ) )
				// ->hide_label()
				->set_attr( array(
					'class' => 'upt-fields'
				))
		);

		return $fields;
	}

	/**
	 * Returns the fields for the layout tab.
	 *
	 * @since 3.0
	 * @return Ultimate_Fields\Field[]
	 */
	public function layout_tab_fields() {
		$templates = array(
			'single' => __( 'Use the post template.', 'upt' ),
			'page' => __( 'Use the default page template.', 'upt' )
		);

		foreach ( wp_get_theme()->get_page_templates() as $template => $name ) {
			$templates[ $template ] = sprintf( __( 'Use the &quot;%s&quot; page template.', 'upt' ), $name );
		}

		$fields = array(
			Field::create( 'select', 'upt_pt_template_type', __( 'Template Type', 'upt' ) )
				->set_input_type( 'radio' )
				->add_options( $templates )
				->set_default_value( 'single' ),
			Field::create( 'wysiwyg', 'upt_pt_before_content', __( 'Before Content', 'upt' ) ),
			Field::create( 'wysiwyg', 'upt_pt_after_content', __( 'After Content', 'upt' ) )
				->set_description( __( 'This content will be displayed before/after the content of the post type, in the template that is selected above. You can use shorcodes like [value key="meta_key"] to display values that are associated with the current post type in order to create a template for it. <strong>meta_key</strong> is the key of the field as created int the <strong>Fields</strong> tab.' ) )
		);

		return $fields;
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
				echo esc_html( get_post_meta( $post_id, 'upt_pt_slug', true ) );
				break;

			case 'singular':
				echo esc_html( get_post_meta( $post_id, 'upt_pt_singular_name', true ) );
				break;

			case 'plural':
				echo esc_html( get_post_meta( $post_id, 'upt_pt_name', true ) );
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
			'label'   => __( 'Post Types', 'upt' ),
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
		$types[] = 'post_type';
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
	 * Imports a post type to the database.
	 *
	 * @since 3.0
	 *
	 * @param bool    $imported Indicates whether the thing has already been imported.
	 * @param mixed[] $data     The data to import.
	 * @return mixed
	 */
	public function import( $imported, $item ) {
		if ( $imported || 'post_type' != $item[ 'type' ] ) {
			return $imported;
		}

		$controller = new Controller();
		return $controller->import( $item );
	}

	/**
	 * Adds the post type registration to a PHP export.
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
				$dump
			);
		}

		if( empty( $pairs ) ) {
			return;
		}

		Template::instance()->include_template( 'post-types/post-type-php-export.php', array(
			'pairs'         => $pairs,
			'function_name' => 'uf_post_types_' . round( microtime( true ) )
		));
	}

	/**
	 * Exports additional (appearance) functions after the standard export.
	 *
	 * @since 3.0
	 *
	 *
	 * @param int[]   $ids The IDs that should be exported.
	 * @param mixed[] $args The arguments for the export.
	 */
	public function export_type_appearance_to_php( $ids, $args ) {
		$items = array();

		foreach( $ids as $id ) {
			if( ! isset( $this->existing[ $id ] ) )
				continue;

			$items[] = array(
				'prefix'         => 'uf_post_types_' . round( microtime( true ) ) . '_',
				'slug'           => $this->existing[ $id ]->get_slug(),
				'before_content' => $this->existing[ $id ]->before_content,
				'after_content'  => $this->existing[ $id ]->after_content,
				'template_type'  => $this->existing[ $id ]->template_type,
			);
		}

		if( empty( $items ) ) {
			return;
		}

		Template::instance()->include_template( 'post-types/post-type-php-export-features.php', array(
			'items'  => $items
		));
	}
}
