<?php
/**
 * Plugin Name: ShortCache
 * Plugin URI: http://arunas.co
 * Description: A shortcode fragment cache plugin
 * Version: 0.2.0
 * Author: Arūnas Liuiza
 * Author URI: http://arunas.co
 * Text Domain: shortcache
 *
 * @package ShortCache
 * @version 0.2.0
 */

// initialize plugin.
require( __DIR__ . '/class-shortcache.php' );
add_action( 'plugins_loaded', array( 'ShortCache', 'init' ) );
