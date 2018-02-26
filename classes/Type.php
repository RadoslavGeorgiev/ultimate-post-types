<?php
namespace Ultimate_Fields\Post_Types;

use Ultimate_Fields\UI\Field_Editor;
use Ultimate_Fields\Template;

/**
 * Adds the basics for a custom thing (post type, taxonomy, etc.)
 *
 * @since 3.0
 */
abstract class Type {
	/**
	 * Initializes the type by creating an instance of it.
	 *
	 * @since 3.0
	 *
	 * @return Ultimate_Fields\Post_Types\Type The instance of the type.
	 */
	 public static function init() {
		 static $instances;

		 if ( is_null( $instances ) ) {
			 $instances = array();
		 }

		 $class_name = get_called_class();
		 if ( isset( $instances[ $class_name ] ) ) {
			 return $instances[ $class_name ];
		 } else {
			 return $instances[ $class_name ] = new $class_name;
		 }
	 }

	 /**
	  * Adds the primary hooks for the class.
	  *
	  * @since 3.0
	  */
	 protected function __construct() {
		$this->slug = call_user_func( array( get_class( $this ), 'get_slug' ) );

		add_action( 'init', array( $this, 'register' ) );

		# Add an actiont o register fields
		add_action( 'uf.init', array( $this, 'register_fields' ) );

		# Enqueues the needed scripts in the back-end
		add_action( 'uf.enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		# Add some JS/JSON to the footer
		add_action( 'admin_footer', array( $this, 'footer' ) );

		 # Change the message when a post type gets updated
		add_filter( 'post_updated_messages', array( $this, 'change_updated_message' ) );

		# Disable the normal submitdiv
		add_action( 'admin_menu', array( $this, 'hide_submitdiv' ) );

		# Changes the row actions to disable quick edit and etc.
		add_filter( 'page_row_actions', array( $this, 'change_quick_actions' ), 10, 2 );

		# Modify the columns for post types.
		add_filter( 'manage_' . $this->slug . '_posts_columns', array( $this, 'manage_columns' ) );

		# Output the values for custom fields
		add_action( 'manage_' . $this->slug . '_posts_custom_column', array( $this, 'output_columns' ), 10, 2 );

		# Add the custom submit box
		add_action( 'add_meta_boxes', array( $this, 'change_meta_boxes' ), 100 );

		# Add hooks for the export screen options.
		add_filter( 'uf.ui.export_groups', array( $this, 'change_export_groups' ), 10, 2 );

		# Add hooks that modify exports
		add_filter( 'uf.ui.export_data', array( $this, 'export' ), 10, 2 );

		# Indicates that post types and taxonomies can be imported back
		add_filter( 'uf.ui.importable_types', array( $this, 'add_import_type' ) );

		# Checks what items exist already, before trying to import them
		add_filter( 'uf.ui.existing_items', array( $this, 'add_existing_items_to_ui' ) );

		# Imports items when needed
		add_filter( 'uf.ui.import_item', array( $this, 'import' ), 10, 2 );

		# Add type-specific fields to PHP exports
		add_filter( 'uf.ui.export_code', array( $this, 'export_fields_to_php' ), 10, 2 );
		add_action( 'uf.ui.export_before', array( $this, 'export_type_to_php', ), 10, 2 );
	 }

	 /**
	  * Returns the slug for the type.
	  *
	  * @since 3.0
	  *
	  * @return tring
	  */
	 public static function get_slug() {
		 return false;
	 }

	 /**
	  * Registers a post tpye for management in the admin.
	  *
	  * @since 3.0
	  */
	 abstract protected function register();

	 /**
	  * Registers a the fields for the management post type.
	  *
	  * @since 3.0
	  */
	 abstract protected function register_fields();

	 /**
	  * Enqueues the scripts, needed for type editors.
	  *
	  * @since 3.0
	  */
	 public function enqueue_scripts() {
		 if ( ! function_exists( 'get_current_screen' ) || $this->get_slug() != get_current_screen()->id ) {
			 return;
		 }

		wp_enqueue_script( 'uf-ui' );
 		wp_enqueue_script( 'uf-ui-field' );
		wp_enqueue_script( 'uf-ui-field-helpers' );
 		wp_enqueue_script( 'uf-ui-editor' );
 		wp_enqueue_script( 'uf-pt', ULTIMATE_FIELDS_PT_URL . 'assets/ultimate-post-types.js', array( 'uf-ui-editor' ), ULTIMATE_FIELDS_PT_VER, true );
		wp_dequeue_script( 'autosave' );
		wp_enqueue_style( 'uf-ui' );

 		$GLOBALS[ 'wp_scripts' ]->query( 'uf-initialize', 'registered' )->deps[] = 'uf-ui-conditional-logic-field';

		$this->get_editor()->enqueue_scripts();
	 }

	 /**
	  * Outputs data in the admin footer.
	  *
	  * @since 3.0
	  */
	 public function footer() {
		if ( $this->get_slug() != get_current_screen()->id ) {
			return;
		}

		$editor = $this->get_editor();
		$editor->output();
	 }

	 /**
	  * Returns the editor for fields when needed.
	  *
	  * @since 3.0
	  *
	  * @return Field_Editor
	  */
	 protected function get_editor() {
		 return Field_Editor::instance();
	 }

	 /**
	 * Hide the default publish box.
	 *
	 * @since 3.0
	 */
	function hide_submitdiv() {
		# Remove the default submit div
		// remove_meta_box( 'submitdiv', $this->get_slug(), 'side' );
	}

	/**
	 * Changes the meta boxes for the edit screen.
	 *
	 * @since 3.0
	 */
	public function change_meta_boxes() {
		remove_meta_box( 'submitdiv', $this->get_slug(), 'side' );
		add_meta_box( 'uf-type-save', __( 'Publish' ), array( $this, 'publish_box' ), $this->get_slug(), 'side', 'high' );
	}

	/**
	 * Displays the "Publish" box on the post type.
	 *
	 * @since 3.0
	 *
	 * @param WP_Post $post The post that is currently being displayed.
	 */
	public function publish_box( $post ) {
		$engine = Template::instance();
		$engine->include_template( 'ui/save-box', compact( 'post' ) );
	}

	/**
	 * Removes the quick-edit link from the quick-action links.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $actions The current actions.
	 * @return mixed[]
	 */
	public function change_quick_actions( $actions ) {
		if ( isset( $actions[ 'inline hide-if-no-js' ] ) ) {
			unset( $actions[ 'inline hide-if-no-js' ] );
		}

		return $actions;
	}

	/**
	 * Exports data when needed.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $data The existing export data.
	 * @param int[]   $ids The IDs of the items, which are to be exported.
	 * @return mixed[]
	 */
	public function export( $data, $ids ) {
		foreach ( $ids as $id ) {
			if ( ! isset( $this->existing[ $id ] ) )
				continue;

			$controller = $this->existing[ $id ];
			$exported   = $controller->export();

			if ( $exported ) {
				$data[] = $exported;
			}
		}

		return $data;
	}

	/**
	 * Adds field data to the PHP export.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $data The already prepared data.
	 * @param int[]   $ids  The post IDs of things to export.
	 * @return mixed[]
	 */
	public function export_fields_to_php( $data, $ids ) {
		foreach( $ids as $id ) {
			if( ! isset( $this->existing[ $id ] ) )
				continue;

			$this->existing[ $id ]->register();

			if( isset( $this->existing[ $id ]->container ) && $this->existing[ $id ]->container ) {
				$data[] = $this->existing[ $id ]->container->export();
			}
		}

		return $data;
	}
}
