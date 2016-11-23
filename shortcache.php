<?php
/*
Plugin Name: ShortCache
Plugin URI: http://arunas.co
Description: A shortcode fragment cache plugin
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: shortcache
*/

// TO DO invalidate on save
// TO DO catch hooks
// TO DO catch assets

add_action( 'plugins_loaded', array( 'ShortCache', 'init' ) );
class ShortCache {
	public static $options = array(
		'cache_interval' => 1,
	);
	public static $settings = false;
	public static $plugin_path = '';
	public static function init() {
		self::$plugin_path = plugin_dir_path( __FILE__ );

		add_shortcode( 'timestamp', array( 'ShortCache', 'timestamp') );
		add_shortcode( 'hook', 			array( 'ShortCache', 'hook') );

		add_filter( 'pre_do_shortcode_tag', array( 'ShortCache', 'filter' ), 99, 4 );

		// tinyOptions v 0.3.0
		self::$options = wp_parse_args( get_option( 'shortcache_options' ), self::$options );
		add_action( 'plugins_loaded', array( 'ShortCache', 'init_options' ), 9999 - 0030 );

	}
	public static function filter( $return, $tag, $attr=array(), $m ) {
		if ( !is_array( $attr ) ) {
			return $return;
		}
		if ( ( !isset( $attr['cache'] ) || 'false' == $attr['cache'] ) && !in_array( 'cache', $attr ) ) {
			return $return;
		}
		$current_url = self::_get_current_url();
		//Serialize atts and content to prepare for use as cache key
		$content_serialized = md5( $m[5] );
		$atts_serialized = md5( serialize( $attr ) );
		//Cache per-user as well as per-URL and per-shortcode
		$current_user = (string) get_current_user_id();
		//Build unique cache key
		$cache_key = "shortcache-{$current_url}-{$current_user}-shortcode_{$tag}-content_{$content_serialized}-atts_{$atts_serialized}";
		$cache_interval = self::$options['cache_interval'];

		$return = get_transient( $cache_key );
		if ( !$return ) {
			$return = do_shortcode( self::cache_false( $tag, $attr, $m ) );
			set_transient( $cache_key, $return, $cache_interval * HOUR_IN_SECONDS );
			return $return;
		}
		return '<!--cached by ShortCache-->'.$return.'<!--/cached by ShortCache-->';
	}

	private static function _get_current_url() {
		return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	private static function cache_false( $tag, $attr, $m ) {
		$attr = array_diff( $attr, array( 'cache' ) );
		$attr['cache'] = 'false';
		foreach( $attr as $key=>$value ) {
			if ( !is_numeric( $key ) ) {
				$attr[ $key ] = "$key=\"{$value}\"";
			} else {
				$attr[ $key ] = $value;
			}
		}
		$attr = implode( ' ', $attr );
		$closer = $m[4];
		$shortcode="[{$tag} {$attr}{$closer}]";
		if ( '' !== $m[5] ) {
			$shortcode .= "{$m[5]}[/{$tag}]";
		}
		return $shortcode;
	}

	public static function timestamp() {
		return date('Y-m-d H:i:s');
	}
	public static function hook(){
		wp_enqueue_script( 'jquery-ui' );
		echo 'hooks some styles';
	}

	public static function init_options() {
		self::$settings = array(
			'page' => array(
				'title' 			=> __( 'ShortCache Settings', 'shortcache' ),
				'menu_title'	=> __( 'ShortCache', 'shortcache' ),
				'slug' 				=> 'shortcache-settings',
				'option'			=> 'shortcache_options',
				// optional
				'description'	=> __( 'ShortCache allows you to cache the output of any shortcode by adding a <code>cache</code> attribute to it.', 'shortcache' ),
			),
			'sections' => array(
				'inputs' => array(
					'title'				=> '',//__( 'Section #1 - Inputs', 'shortcache' ),
					'description'	=> '',//__( 'Showcases various <code>&lt;input&gt;</code> based fields', 'shortcache' ),
					'fields'	=> array(
						'cache_interval' => array(
							'title'	=> __( 'Cache Interval', 'shortcache' ),
							'description' => __( 'How long should ShortCache keep the cached copy of the shortcode (in hours)?', 'shortcache'),
							'attributes' => array(
								'type'	=> 'number',
								'step'	=> 0.1,
								'min'		=> 0.1,
							),
						),
					),
				),
			),
			'l10n' => array(
				'no_access'			=> __( 'You do not have sufficient permissions to access this page.', 'shortcache' ),
				'save_changes'	=> esc_attr( 'Save Changes', 'shortcache' ),
			),
		);
		require_once( self::$plugin_path . 'tiny/tiny.options.php' );
		self::$settings = new tinyOptions( self::$settings, __CLASS__ );
	}
}
