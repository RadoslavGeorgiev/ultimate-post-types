<?php
/**
 * Plugin Name: Ultimate Post Types 2
 * Plugin URI: http://post-types.ultimate-fields.com/
 * Description: An add-on for Ultimate Fields, which allows creating post types and assign fields to them.
 * Version: 3.0a
 * Author: Radoslav Georgiev
 * Author URI: http://rageorgiev.com/
 * Copyright: Radoslav Georgiev
 */

/**
 * Load the important files for Ultimate Post Types.
 *
 * @since 2.0
 */
require_once __DIR__ . '/Post_Types.php';
add_action( 'plugins_loaded', 'upt_load' );
function upt_load() {
	Ultimate_Fields\Post_Types::instance( __FILE__ );
}
