<?php
namespace Ultimate_Fields\Post_Types\Controller;

use Ultimate_Fields\Container;
use Ultimate_Fields\Field;
use Ultimate_Fields\Datastore\Post_Meta as Post_Meta_Datastore;
use Ultimate_Fields\Location as Core_Location;
use Ultimate_Fields\UI\Field_Helper;
use Ultimate_Fields\Post_Types\Controller;

/**
 *
 *
 * @since 3.0
 */
class Taxonomy extends Controller {
	/**
	 * Returns the arguments, which will be used for the registration of the post type.
	 *
	 * @since 3.0
	 *
	 * @return mixed[]
	 */
	public function get_args() {
		# Prepare data
		$args = array();

		# Retrieve all vars and register the post type
		$plural   = $this->data[ 'name' ];
		$singular = $this->data[ 'singular_name' ];

		# Generate the default labels
		$labels = array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'popular_items'              => sprintf( __( 'Popular %s',                   'ultimate-post-types' ), $plural   ),
			'all_items'                  => sprintf( __( 'All %s',                       'ultimate-post-types' ), $plural   ),
			'update_item'                => sprintf( __( 'Update %s',                    'ultimate-post-types' ), $singular ),
			'search_items'               => sprintf( __( 'Search %s',                    'ultimate-post-types' ), $plural   ),
			'edit_item'                  => sprintf( __( 'Edit %s',                      'ultimate-post-types' ), $singular ),
			'add_new_item'               => sprintf( __( 'Add New %s',                   'ultimate-post-types' ), $singular ),
			'new_item_name'              => sprintf( __( 'New %s Name',                  'ultimate-post-types' ), $singular ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas',      'ultimate-post-types' ), $plural   ),
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s',             'ultimate-post-types' ), $plural   ),
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'upt' ), $plural   ),
			'not_found'                  => sprintf( __( 'No %s found.',                 'ultimate-post-types' ), $plural   )
		);

		# Add the main label
		$labels[ 'menu_name' ] = apply_filters( 'the_title', $this->post->post_title );
		# Add fine tuned labels eventually
		if ( $this->data[ 'fine_tune' ] ) {
			foreach ( $labels as $key => $label ) {
				if ( $this->data[ $key ] ) {
					$labels[ $key ] = $this->data[ $key ];
				}
			}
		}
		$args[ 'labels' ] = $labels;

		# Add params
		$args[ 'hierarchical' ]      = (bool) $this->data[ 'hierarchical' ];
		$args[ 'public' ]            = (bool) $this->data[ 'public' ];
		$args[ 'show_ui' ]           = (bool) $this->data[ 'show_ui' ];
		$args[ 'show_in_nav_menus' ] = (bool) $this->data[ 'show_in_nav_menus' ];
		$args[ 'show_admin_column' ] = (bool) $this->data[ 'show_admin_column' ];
		# Enable rewrite
		if ( $this->data[ 'rewrite_enable' ] ) {
			$args[ 'rewrite' ] = array(
				'with_front' => $this->data[ 'rewrite_with_front' ],
			);
			if ( $this->data[ 'rewrite_slug' ] ) {
				$args[ 'rewrite' ][ 'slug' ] = $this->data[ 'rewrite_slug' ];
			}
		}

		/**
		 * Allows the arguments for a taxonomy to be changed before it gets registered.
		 *
		 * @since 3.0
		 *
		 * @param mixed[] $args The arguments for register_taxonomy.
		 * @param mixed[] $this->data The data about the container.
		 */
		$args = apply_filters( 'upt.post_type.args', $args, $this->data );

		return $args;
	}

	/**
	 * Returns the slug of the taxonomy.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->data[ 'slug' ];
	}

	/**
	 * Returns the post types for the taxonomy.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_post_types() {
		return $this->data[ 'post_types' ];
	}

	/**
	 * Registers the taxonomy.
	 *
	 * @since 3.0
	 */
	public function register() {
		if ( in_array( $this->data[ 'slug'], get_taxonomies() ) ) {
			// Fail silently
			return;
		}

		register_taxonomy( $this->data[ 'slug' ], $this->data[ 'post_types'], $this->get_args() );

		$this->register_fields(
			isset( $this->data[ 'fields' ] ) ? $this->data[ 'fields' ] : array(),
			Core_Location::create( 'taxonomy', $this->data[ 'slug' ] )
		);
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
			'type'  => 'taxonomy',
			'hash'  => $this->get_hash(),
			'title' => $this->post->post_title
		);

		# Get a container and a datastore, associate them
		$container = Container::get_registered()[ 'upt_taxonomy' ];
		$datastore = new Post_Meta_Datastore();
		$datastore->set_id( $this->post->ID );
		$container->set_datastore( $datastore );

		# Go through each field and check for non-default, related values.
		foreach ( $container->get_fields() as $field ) {
			$name = $field->get_name();
			if ( 0 !== strpos( $name, 'upt_tax_' ) || 'upt_tax_fields' == $name )
				continue;

			$field_data = $field->export_data();
			if ( empty( $field_data ) )
				continue;

			$value = array_shift( $field_data );
			if ( $value == $field->get_default_value() )
				continue;

			$data[ str_replace( 'upt_tax_', '', $name ) ] = $value;
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
		return md5( 'taxonomy:' . $this->data[ 'slug' ] );
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
		$container = Container::get_registered()[ 'upt_taxonomy' ];

		# Create a new (still blank) datastore
		$datastore = new Post_Meta_Datastore();
		$container->set_datastore( $datastore );
		$save_data = array();
		foreach ( $container->get_fields() as $field ) {
			$full  = $field->get_name();
			$short = str_replace( 'upt_tax_', '', $full );

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
			'post_type'	   => 'uf-taxonomy',
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
		update_post_meta( $post_id, 'upt_tax_fields', $fields );

		# Success!
		return true;
	}
}
