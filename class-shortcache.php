<?php
/**
 * Main plugin classs
 *
 * @package ShortCache
 */

/**
 * ShortCache plugin main class.
 */
class ShortCache {
	/**
	 * Plugin options
	 *
	 * @var array
	 */
	public static $options = array(
		'cache_interval' 			=> 1,
		'cache_scope'					=> array(
			'last_save',
			'user_id',
		),
	);
	/**
	 * Setings object
	 *
	 * @var boolean
	 */
	public static $settings = false;
	/**
	 * Plugin path
	 *
	 * @var string
	 */
	public static $plugin_path = '';
	/**
	 * Initalization function
	 *
	 * @return void
	 */
	public static function init() {
		self::$plugin_path = plugin_dir_path( __FILE__ );
		self::$options = wp_parse_args( get_option( 'shortcache_options' ), self::$options );
		// Main hook to short-circuit shortcode output.
		add_filter( 'pre_do_shortcode_tag', 		array( 'ShortCache', 'filter' ), 99, 4 );
		// Reset last save timestamp on post save action.
		add_action( 'save_post', 								array( 'ShortCache', '_set_last_save' ) );
		// Sample hooks.
		add_shortcode( 'shortcache_timestamp', 	array( 'ShortCache', 'timestamp' ) );
		add_shortcode( 'shortcache_hook', 			array( 'ShortCache', 'hook' ) );
		// Initialize plugin settings.
		// tinyOptions v0.3.0.
		add_action( 'plugins_loaded', array( 'ShortCache', 'init_options' ), 9999 - 0030 );
	}
	/**
	 * Cache shortcode
	 *
	 * @param  string $return shortcode content.
	 * @param  string $tag  	shortcode names.
	 * @param  array  $attr   shordcode attributes.
	 * @param  array  $m      shortcode regexed parts.
	 * @return [type]         shortcode content
	 */
	public static function filter( $return, $tag, $attr = array(), $m ) {
		if ( ! is_array( $attr ) ) {
			return $return;
		}
		$cache = false;
		if ( in_array( 'cache', $attr, true ) ) {
			$cache = true;
		}
		if ( isset( $attr['cache'] ) && $attr['cache'] ) {
			$cache = true;
		}
		if ( isset( $attr['cache'] ) && 'false' === $attr['cache'] ) {
			$cache = false;
		}
		if ( ! $cache ) {
			return $return;
		}
		$args = array(
			'tag' 				=> $tag,
			'content'			=> $m[5],
			'attributes'	=> $attr,
		);
		$cache_key = self::_get_cache_key( $args );
		$return = self::_get_cache( $cache_key );
		if ( $return ) {
			self::_restore_scripts( $return );
			return '<!--cached by ShortCache-->' . $return['content'] . '<!--/cached by ShortCache-->';
		}
		$scripts_old = self::_get_scripts();
		$return = array(
			'content' => do_shortcode( self::_cache_false( $tag, $attr, $m ) ),
		);
		$return['scripts'] = self::_diff_scripts( $scripts_old, self::_get_scripts() );
		$cache_interval = false;
		if ( isset( $attr['cache'] ) && 0 < 1 * $attr['cache'] ) {
			$cache_interval = 1 * $attr['cache'];
		}
		self::_set_cache( $cache_key, $return, $cache_interval );
		return '<!--NOT cached by ShortCache, yet-->' . $return['content'] . '<!--/NOT cached by ShortCache, yet-->';
	}
	/**
	 * Restore scripts that were enqueued via shortcode
	 *
	 * @param  array $return stored shortcode data.
	 * @return void
	 */
	private static function _restore_scripts( $return ) {
		foreach ( $return['scripts']['register'] as $script ) {
			wp_register_script( $script->handle, $script->src, $script->deps, $script->ver );
			if ( isset( $script->extra['data'] ) ) {
				wp_add_inline_script( $script->handle, $script->extra['data'], 'before' );
			}
			if ( isset( $script->extra['after'] ) ) {
				foreach ( $script->extra['after'] as $position => $data ) {
					wp_add_inline_script( $script->handle, $data );
				}
			}
		}
		foreach ( $return['scripts']['queue'] as $script ) {
			wp_enqueue_script( $script );
		}
	}
	/**
	 * Detect changes in scripts array
	 *
	 * @param  array $old original array of scripts.
	 * @param  array $new current array of scripts.
	 * @return array      scripts added via shortcode
	 */
	private static function _diff_scripts( $old, $new ) {
		$diff = array_diff( array_keys( $new->registered ), array_keys( $old->registered ) );
		$cache = array(
			'register'	=> array(),
		);
		foreach ( $diff as $script ) {
			$cache['register'][ $script ] = clone $new->registered[ $script ];
		}
		$diff = array_diff( $new->queue, $old->queue );
		$cache['queue'] = $diff;
		return $cache;
	}
	/**
	 * Get a copy of global scripts array
	 *
	 * @return array scripts array
	 */
	private static function _get_scripts() {
		global $wp_scripts;
		return clone $wp_scripts;
	}
	/**
	 * Generate a cache key
	 *
	 * @param  array $args Cache key parts.
	 * @return string      Cache key
	 */
	private static function _get_cache_key( $args ) {
		$defaults = array(
			'tag'					=> false,
			'content'			=> false,
			'attributes'	=> array(),
			'url'					=> self::_get_current_url(),
			'user_id'			=> (string) get_current_user_id(),
			'timestamp'		=> self::_get_last_save(),
			'post_id'			=> get_the_id(),
			'scope'				=> self::$options['cache_scope'],
		);
		$args = wp_parse_args( $args, $defaults );
		if ( isset( $args['attributes']['cache-scope'] ) ) {
			$args['scope'] = $args['cache-scope'];
		}
		if ( is_array( $args['attributes'] ) ) {
			$args['attributes'] = wp_json_encode( $args['attributes'] );
		}
		$cache_key = array();
		if ( in_array( 'url', $args['scope'], true ) ) {
			$cache_key[] = $args['url'];
		}
		if ( in_array( 'post_id', $args['scope'], true ) ) {
			$cache_key[] = $args['post_id'];
		}
		$cache_key[] = $args['content'];
		$cache_key[] = $args['attributes'];
		$cache_key = implode( '-', $cache_key );
		$cache_key_hash = md5( $cache_key );
		$cache_key = array();
		$cache_key[] = 'shortcache';
		$cache_key[] = $args['tag'];
		if ( in_array( 'user_id', $args['scope'], true ) ) {
			$cache_key[] = $args['user_id'];
		}
		if ( in_array( 'last_save', $args['scope'], true ) ) {
			$cache_key[] = $args['timestamp'];
		}
		$cache_key[] = $cache_key_hash;
		$cache_key = implode( '-', $cache_key );
		return $cache_key;
	}
	/**
	 * Store shortcode data in transient
	 *
	 * @param string $cache_key      transient key.
	 * @param string $value          transient value.
	 * @param mixed  $cache_interval how long should it be cached.
	 */
	private static function _set_cache( $cache_key, $value, $cache_interval = false ) {
		if ( false === $cache_interval ) {
			$cache_interval = self::$options['cache_interval'];
		}
		return set_transient( $cache_key, $value, $cache_interval * HOUR_IN_SECONDS );
	}
	/**
	 * Retrieve shortcode data from transient
	 *
	 * @param  string $cache_key transient key.
	 * @return string            transient value.
	 */
	private static function _get_cache( $cache_key ) {
		return get_transient( $cache_key );
	}
	/**
	 * Set timestamp of last save
	 *
	 * @return void
	 */
	public static function _set_last_save() {
		$cache_interval = self::$options['cache_interval'];
		set_transient( 'shortcache', current_time( 'timestamp' ), $cache_interval * HOUR_IN_SECONDS * 2 );
	}
	/**
	 * Get timestamp of last save
	 *
	 * @return int timetsamp
	 */
	private static function _get_last_save() {
		$timestamp = get_transient( 'shortcache' );
		$timestamp = intval( $timestamp );
		return $timestamp;
	}
	/**
	 * Return URI of current page
	 *
	 * @return string current URI
	 */
	private static function _get_current_url() {
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		return $current_url;
	}
	/**
	 * Explicitly mark shortcode not to be cached
	 *
	 * @param  string $tag  shortcode name.
	 * @param  array  $attr shortcode attributes.
	 * @param  array  $m    shortcode regexed parts.
	 * @return string       full shortcode
	 */
	private static function _cache_false( $tag, $attr, $m ) {
		$attr = array_diff( $attr, array( 'cache' ) );
		$attr['cache'] = 'false';
		foreach ( $attr as $key => $value ) {
			if ( ! is_numeric( $key ) ) {
				$attr[ $key ] = "$key=\"{$value}\"";
			} else {
				$attr[ $key ] = $value;
			}
		}
		$attr = implode( ' ', $attr );
		$closer = $m[4];
		$shortcode = "[{$tag} {$attr}{$closer}]";
		if ( '' !== $m[5] ) {
			$shortcode .= "{$m[5]}[/{$tag}]";
		}
		return $shortcode;
	}
	/**
	 * [shortcache_timestamp] shortcode
	 *
	 * @return string current datetime
	 */
	public static function timestamp() {
		return date( 'Y-m-d H:i:s' );
	}
	/**
	 * [shprtcache_hook] shortcode to test script caching
	 *
	 * @return string dummy text
	 */
	public static function hook() {
		wp_register_script( 'shortcache', plugins_url( 'test.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_add_inline_script( 'shortcache', 'var test;' );
		wp_localize_script( 'shortcache', 'shortache_string', array( 'test' ) );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'shortcache' );
		return 'hooks some scripts';
	}
	/**
	 * Initialize plugin settings
	 *
	 * @return void
	 */
	public static function init_options() {
		self::$settings = array(
			'page' => array(
				'title' 			=> __( 'ShortCache Settings', 'shortcache' ),
				'menu_title'	=> __( 'ShortCache', 'shortcache' ),
				'slug' 				=> 'shortcache-settings',
				'option'			=> 'shortcache_options',
				'description'	=> __( 'ShortCache allows you to cache the output of any shortcode by adding a <code>cache</code> attribute to it.', 'shortcache' ),
			),
			'sections' => array(
				'inputs' => array(
					'title'				=> '',
					'description'	=> '',
					'fields'	=> array(
						'cache_interval' => array(
							'title'				=> __( 'Cache Interval', 'shortcache' ),
							'description' => __( 'How long should ShortCache keep the cached copy of the shortcode (in hours)?', 'shortcache' ),
							'attributes' 	=> array(
								'type'	=> 'number',
								'step'	=> 0.1,
								'min'		=> 0.1,
							),
						),
						'cache_scope' => array(
							'title'				=> __( 'Cache Scope', 'shortcache' ),
							'description' => __( 'Which fields should differentiate different caches?', 'shortcache' ),
							'callback'		=> 'listfield',
							'list'				=> array(
								'last_save'	=> __( 'Last Update Timestamp', 'shortcache' ),
								'user_id'		=> __( 'User ID', 'shortcache' ),
								'post_id'		=> __( 'Post ID', 'shortcache' ),
								'url'				=> __( 'URL', 'shortcache' ),
							),
							'attributes' 	=> array(
								'type'	=> 'checkbox',
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
