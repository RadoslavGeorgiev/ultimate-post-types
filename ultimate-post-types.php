<?php
/**
 * Plugin Name: Ultimate Post Types
 * Plugin URI: https://www.ultimate-fields.com/docs/ultimate-post-types/
 * Description: An add-on for Ultimate Fields, which allows creating post types and assign fields to them.
 * Version: 3.0
 * Author: Radoslav Georgiev
 * Author URI: https://rageorgiev.com/
 * Copyright: Radoslav Georgiev
 */

/**
 * Loads the files of Ultimate Post Types after Ultimate Fields/Ultimate Fields Pro.
 *
 * @since 3.0
 */
add_action( 'plugins_loaded', 'load_ultimate_post_types', 11 );
function load_ultimate_post_types() {
   define( 'ULTIMATE_POST_TYPES_PLUGIN_FILE', __FILE__ );

   require_once __DIR__ . '/classes/Post_Types.php';
   Ultimate_Fields\Post_Types\Post_Types::instance( __FILE__ );
}
