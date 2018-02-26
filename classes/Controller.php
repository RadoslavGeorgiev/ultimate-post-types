<?php
namespace Ultimate_Fields\Post_Types;

use Ultimate_Fields\Container;
use Ultimate_Fields\Field;
use Ultimate_Fields\UI\Field_Helper;

/**
 * Serves as a base for post-type controllers.
 *
 * @since 3.0
 */
abstract class Controller {
	/**
	 * Holds the post, which works with the type.
	 *
	 * @since 3.0
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * All meta-data about the content type.
	 *
	 * @since 3.0
	 * @var mixed[]
	 */
	protected $data;

	/**
	 * Initializes the controller.
	 *
	 * @since 3.0
	 *
	 * @param WP_Post $post The post
	 */
	public function __construct( $post = null ) {
		if ( ! $post ) {
			return;
		}

		$this->post = $post;

		$data = array();
		foreach ( get_post_meta( $post->ID, null, true ) as $key => $values ) {
			if ( strpos( $key, 'upt_pt_' ) === 0 ) {
				$data[ str_replace( 'upt_pt_', '', $key ) ] = maybe_unserialize( $values[ 0 ] );
			}

			if ( strpos( $key, 'upt_tax_' ) === 0 ) {
				$data[ str_replace( 'upt_tax_', '', $key ) ] = maybe_unserialize( $values[ 0 ] );
			}
		}

		$data[ 'slug' ] = strtolower( $data[ 'slug' ] );

		/**
		 * Allows the data about a post type to be modified before
		 * retistration or export of the post type is attempted.
		 *
		 * @since 2.0
		 *
		 * @param mixed[]           $data       The data for the post type.
		 * @param WP_Post           $post       The post that will be requested.
		 * @param Ultimate_Fields\Post_Types\Controller $controller The controller that is managing the content type.
		 * @return mixed[]
		 */
		$this->data = apply_filters( 'upt.content_type.data', $data, $post, $this );

		# Let the sub-class continue with the registration
		$this->register();
	}

	/**
	 * Registers the content type.
	 *
	 * @since 3.0
	 */
	abstract public function register();

	/**
	 * Creates a new fields container and associates it with a location.
	 *
	 * @since 3.0
	 *
	 * @param mixed[]      $fields   The raw data about the fields.
	 * @param Ultimate_Fields\Location $location The location to associate fields with.
	 */
	protected function register_fields( $data, $location ) {
		if ( ! $data || empty( $data ) ) {
			return false;
		}

		# Parse the fields
		$fields = array();
		foreach ( $data as $field ) {
			$helper = Field_Helper::import_from_meta( $field );
			$fields[] = $helper->setup_field();
		}

		$container = Container::create( $this->post->post_title )
			->set_layout( 'grid' )
			->add_location( $location )
			->add_fields( $fields );

		return $container;
	}

	/**
	 * Exports fields to JSON.
	 *
	 * @since 3.0
	 *
	 * @param mixed[] $fields The raw data about the fields.
	 * @return mixed[] A json-ready variant of the fields.
	 */
	protected function export_fields( $data ) {
		if ( ! $data || empty( $data ) ) {
			return false;
		}

		# Parse the fields
		$fields = array();
		foreach ( $data as $field ) {
			$helper = Field_Helper::import_from_meta( $field );
			$fields[] = $helper->setup_field()->export();
		}

		return $fields;
	}

	/**
	 * Allows values to be automatically fetched from the post type or data.
	 *
	 * @since 3.0
	 *
	 * @param string $key The needed key.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		if ( property_exists( $this->post, $key ) ) {
			return $this->post->$key;
		}
	}
}
