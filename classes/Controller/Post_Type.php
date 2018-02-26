<?php
namespace Ultimate_Fields\PT\Controller;

use Ultimate_Fields\PT\Controller;
use Ultimate_Fields\Location\Post_Type as Core_Location;
use Ultimate_Fields\Container;
use Ultimate_Fields\Datastore\Post_Meta as Post_Meta_Datastore;
use Ultimate_Fields\Field;
use Ultimate_Fields\UI\Field_Helper;

/**
 *
 *
 * @since 3.0
 */
class Post_Type extends Controller {
	/**
	 * Returns the arguments, which will be used for the registration of the post type.
	 *
	 * @since 3.0
	 *
	 * @return mixed[]
	 */
	public function get_args() {
		# Prepare data
		$args = array(
			'capability_type' => 'post',
			'query_var'       => true,
			'supports'        => array()
		);

		# Prepare basic flags
		$args[ 'hierarchical' ]        = (bool) $this->data[ 'hierarchical' ];
		$args[ 'public' ]              = (bool) $this->data[ 'public' ];
		$args[ 'show_in_menu' ]        = (bool) $this->data[ 'show_in_menu' ];
		$args[ 'show_ui' ]             = (bool) $this->data[ 'show_ui' ];
		$args[ 'show_in_admin_bar' ]   = (bool) $this->data[ 'show_in_admin_bar' ];
		$args[ 'show_in_nav_menus' ]   = (bool) $this->data[ 'show_in_nav_menus' ];
		$args[ 'publicly_queryable' ]  = (bool) $this->data[ 'publicly_queryable' ];
		$args[ 'exclude_from_search' ] = (bool) $this->data[ 'exclude_from_search' ];
		$args[ 'has_archive' ]         = (bool) $this->data[ 'has_archive' ];
		$args[ 'can_export' ]          = (bool) $this->data[ 'can_export' ];

		# Check for a specific menu position
		if ( isset( $this->data[ 'menu_placement' ] ) && ( 'index' == $this->data[ 'menu_placement' ] ) && $this->data[ 'menu_position' ] ) {
			$args[ 'menu_position' ] = intval( $this->data[ 'menu_position' ] );
		}

		# Add supports
		$possible_supports = array(
			'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks',
			'custom_fields', 'comments', 'revisions', 'page_attributes', 'post_formats'
		);

		foreach ( $possible_supports as $s ) {
			if ( $this->data[ 'supports_' . $s ] ) {
				$args[ 'supports' ][] = str_replace( '_', '-', $s );
			}
		}

		# Prepare rewrites
		if ( $this->data[ 'rewrite_enable' ] ) {
			$args[ 'rewrite' ] = array(
				'with_front' => $this->data[ 'rewrite_with_front' ],
				'feeds'      => $this->data[ 'rewrite_feeds' ],
				'pages'      => $this->data[ 'rewrite_pages' ]
			);

			if ( $this->data[ 'rewrite_slug' ] ) {
				$args[ 'rewrite' ][ 'slug' ] = $this->data[ 'rewrite_slug' ];
			}
		}

		# Add labels
		$plural   = $this->data[ 'name' ];
		$singular = $this->data[ 'singular_name' ];

		# Generate the default labels
		$labels = array(
			'name'                => sprintf( __( '%s', 'ultimate-post-types' ),                   $plural ),
			'singular_name'       => sprintf( __( '%s', 'ultimate-post-types' ),                   $singular ),
			'add_new'             => sprintf( __( 'Add %s', 'ultimate-post-types' ),               $singular ),
			'add_new_item'        => sprintf( __( 'Add %s', 'ultimate-post-types' ),               $singular ),
			'edit_item'           => sprintf( __( 'Edit %s', 'ultimate-post-types' ),              $singular ),
			'new_item'            => sprintf( __( 'New %s', 'ultimate-post-types' ),               $singular ),
			'view_item'           => sprintf( __( 'View %s', 'ultimate-post-types' ),              $singular ),
			'search_items'        => sprintf( __( 'Search %s', 'ultimate-post-types' ),            $plural ),
			'not_found'           => sprintf( __( 'No %s found', 'ultimate-post-types' ),          $plural ),
			'not_found_in_trash'  => sprintf( __( 'No %s found in Trash', 'ultimate-post-types' ), $plural ),
			'parent_item_colon'   => sprintf( __( 'Parent %s:', 'ultimate-post-types' ),           $singular ),
		);

		# Add the main label
		$labels[ 'menu_name' ] = apply_filters( 'the_title', $this->post->post_title );

		# Add fine tuned labels eventually
		if ( $this->data[ 'fine_tune' ] ) {
			foreach ( $labels as $key => $label ) {
				if ( isset( $this->data[ $key ] ) && $this->data[ $key ] ) {
					$labels[ $key ] = $this->data[ $key ];
				}
			}
		}

		$args[ 'labels' ] = array_merge( $labels );

		# Check for an icon
		if ( isset( $this->data[ 'icon' ] ) && $this->data[ 'icon' ] ) {
			$args[ 'menu_icon' ] = str_replace( 'dashicons ', '', $this->data[ 'icon' ] );
		}

		/**
		 * Allows the arguments for a post type to be changed before it gets registered.
		 *
		 * @since 3.0
		 *
		 * @param mixed[] $args The arguments for register_post_type.
		 * @param mixed[] $data The data about the container.
		 */
		$args = apply_filters( 'upt.post_type.args', $args, $this->data );

		return $args;
	}

	/**
	 * Registers the post type.
	 *
	 * @since 3.0
	 */
	public function register() {
		if ( in_array( $this->data[ 'slug' ], get_post_types() ) ) {
			// Fail silently
			return;
		}

		register_post_type( $this->data[ 'slug' ], $this->get_args() );

		if ( $this->data[ 'supports_post_formats' ] ) {
			add_post_type_support( 'post-formats', $this->data[ 'formats' ] );
		}

		$this->container = $this->register_fields(
			isset( $this->data[ 'fields' ] ) ? $this->data[ 'fields' ] : array(),
			new Core_Location( $this->data[ 'slug' ] )
		);

		# Rewrite the template
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_filter( 'the_content', array( $this, 'add_fields_to_content' ), 3 );
	}

	/**
	 * Returns the slug of the post type.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->data[ 'slug' ];
	}

	/**
	 * Changes the template that is used for the post type, when requested.
	 *
	 * @since 3.0
	 *
	 * @param string $template The template that is about to be used.
	 * @return string
	 */
	public function template_include( $template ) {
		if( ! is_singular( $this->data[ 'slug' ] ) ) {
            return $template;
        }

        $type = $this->data[ 'template_type' ];
        if( 'single' == $type )
            $template = array( 'single-' . $this->data[ 'slug' ] . '.php', 'single.php' );
        elseif( 'page' == $type )
            $template = 'page.php';
        else
            $template = $type;

        return locate_template( $template, false, false );
	}

	/**
	 * Adds before/after to the content of the post type.
	 *
	 * @since 3.0
	 *
	 * @param string $content The content to process.
	 * @return string
	 */
	public function add_fields_to_content( $content ) {
		if( ! is_singular( $this->get_slug() ) ) {
			return $content;
		}

		if( $this->data[ 'before_content' ] )
			$content = trim( $this->data[ 'before_content' ] ) . "\n\n" .$content;

		if( $this->data[ 'after_content' ] )
			$content .= "\n\n" . trim( $this->data[ 'after_content' ] );

		return $content;
	}

	/**
	 * Prepares the export data for the content type.
	 *
	 * @since 3.0
	 *
	 * @return mixed[]
	 */
	public function export() {
		$data = array(
			'type' => 'post_type',
			'title' => $this->post->post_title,
			'hash'  => $this->get_hash()
		);

		# Get a container and a datastore, associate them
		$container = Container::get_registered()[ 'upt_post_type' ];
		$datastore = new Post_Meta_Datastore();
		$datastore->set_id( $this->post->ID );
		$container->set_datastore( $datastore );

		# Go through each field and check for non-default, related values.
		foreach ( $container->get_fields() as $field ) {
			$name = $field->get_name();
			if ( 0 !== strpos( $name, 'upt_pt_' ) || 'upt_pt_fields' == $name )
				continue;

			$field_data = $field->export_data();
			if ( empty( $field_data ) )
				continue;

			$value = array_shift( $field_data );
			if ( $value == $field->get_default_value() )
				continue;

			$data[ str_replace( 'upt_pt_', '', $name ) ] = $value;
		}

		# Export fields if any
		if ( $fields = $this->export_fields( $this->data[ 'fields' ] ) ) {
			$data[ 'fields' ] = $fields;
		}

		return $data;
	}

	/**
	 * Returns the hash (identifier) of the content type.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_hash() {
		return md5( 'post_type:' . $this->data[ 'slug' ] );
	}

	/**
	 * Imports data from an external source and saves data in the DB.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $data The data to import.
	 * @return bool   True on success, false on error.
	 */
	public function import( $data ) {
		# Get a container to load data into
		$container = Container::get_registered()[ 'upt_post_type' ];

		# Create a new (still blank) datastore
		$datastore = new Post_Meta_Datastore();
		$container->set_datastore( $datastore );
		$save_data = array();
		foreach ( $container->get_fields() as $field ) {
			$full  = $field->get_name();
			$short = str_replace( 'upt_pt_', '', $full );

			if ( 'fields' == $short ) {
				continue;
			}

			if ( isset( $data[ $short] ) ) {
				$save_data[ $full ] = $data[ $short ];
			} else {
				$save_data[ $full ] = $field->get_default_value();
			}
		}

		# Check if there are errors
		$errors = $container->save( $save_data );
		if ( ! empty( $errors ) ) {
			return false;
		}

		# Check for a title
		if ( ! isset( $data[ 'title' ] ) || ! $data[ 'title' ] ) {
			return false;
		}

		# Prepare fields
		$fields = array();

		foreach ( $data[ 'fields' ] as $f ) {
			$field = Field::create( $f[ 'type' ], $f[ 'name' ], $f[ 'label' ] );
			$field->import( $f );
			$fields[] = Field_Helper::get_field_data( $field );
		}

		# Insert a post
		$post_id = wp_insert_post(array(
			'post_type'	   => 'uf-post-type',
			'post_status'  => 'publish',
			'post_title'   => $data[ 'title' ],
			'post_content' => ''
		));

		if ( ! $post_id ) {
			return false;
		}

		# Associate the post ID with the datastore and save it's values
		$datastore->set_id( $post_id );
		$datastore->commit();

		# Save the fields
		update_post_meta( $post_id, 'upt_pt_fields', $fields );

		# Success!
		return true;
	}
}
