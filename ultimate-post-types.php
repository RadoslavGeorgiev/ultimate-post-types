<?php
/**
 * Plugin name: Ultimate Post Types
 * Version:     3.0
 * Plugin URI:  https://www.ultimate-fields.com/docs/ultimate-post-types/
 * Author:      Radoslav Georgiev
 * Author URI:  http://rageorgiev.com/
 * Copyright:   Radoslav Georgiev
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain path: /languages
 * Text Domain: upt
 * Description: An add-on for Ultimate Fields, which allows creating post types and assign fields to them.
 * Requires at least: 4.9
 */


/**
 * Loads the files of Ultimate Post Types after Ultimate Fields/Ultimate Fields Pro.
 *
 * @since 3.0
 */
add_action( 'plugins_loaded', 'load_ultimate_post_types', 11 );
function load_ultimate_post_types() {
   define( 'UPT_LANGUAGES_DIR', basename( __DIR__ ) . '/languages/' );

   require_once __DIR__ . '/classes/Post_Types.php';
   Ultimate_Fields\Post_Types\Post_Types::instance( __FILE__ );
}
