<?php
/**
 * Core setup, site hooks and filters.
 *
 * @package TenUpTheme
 */

namespace TenUpTheme\Core;

use \TenUpTheme\Utility;

/**
 * Set up theme defaults and register supported WordPress features.
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};
	add_action( 'after_setup_theme', $n( 'i18n' ) );
	add_action( 'after_setup_theme', $n( 'theme_setup' ) );
	add_action( 'wp_enqueue_scripts', $n( 'scripts' ) );
	add_action( 'wp_enqueue_scripts', $n( 'styles' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_styles' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );
	add_action( 'wp_head', $n( 'js_detection' ), 0 );
	add_action( 'wp_head', $n( 'module_detection' ), 0 );
	add_action( 'wp_head', Utility\preload_post_thumbnail, 2 );
	add_action( 'wp_head', Utility\link_preload_preconnect, 3 );
	add_action( 'wp_head', $n( 'add_manifest' ), 10 );
	add_action( 'get_header', $n( 'remove_admin_bar_layout_styles' ) );

	add_filter( 'script_loader_tag', $n( 'script_loader_tag' ), 10, 2 );

	if ( ! is_admin() ) {
		add_filter( 'style_loader_tag', $n( 'style_loader_tag' ), 99, 2 );
	}
}

/**
 * Makes Theme available for translation.
 *
 * Translations can be added to the /languages directory.
 * If you're building a theme based on "tenup-theme", change the
 * filename of '/languages/TenUpTheme.pot' to the name of your project.
 *
 * @return void
 */
function i18n() {
	load_theme_textdomain( 'tenup-theme', TENUP_THEME_PATH . '/languages' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function theme_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		[
			'search-form',
			'gallery',
			'script',
			'style',
		]
	);

	// This theme uses wp_nav_menu() in three locations.
	register_nav_menus(
		[
			'primary' => esc_html__( 'Primary Menu', 'tenup-theme' ),
		]
	);
}

/**
 * Enqueue scripts for front-end.
 *
 * @return void
 */
function scripts() {
	wp_enqueue_script(
		'frontend',
		TENUP_THEME_TEMPLATE_URL . '/dist/js/frontend.js',
		Utility\get_asset_info( 'frontend', 'dependencies' ),
		Utility\get_asset_info( 'frontend', 'version' ),
		true
	);

	wp_enqueue_script(
		'polyfill',
		TENUP_THEME_TEMPLATE_URL . '/dist/js/polyfill.js',
		[],
		Utility\get_asset_info( 'polyfill', 'version' ),
		true
	);

	wp_script_add_data(
		'polyfill',
		'attributes',
		[
			'nomodule' => true,
		]
	);
}

/**
 * Enqueue styles for front-end.
 *
 * @return void
 */
function styles() {
	wp_enqueue_style(
		'styles',
		TENUP_THEME_TEMPLATE_URL . '/dist/css/style.css',
		[],
		Utility\get_asset_info( 'style', 'version' )
	);
}

/**
 * Enqueue scripts for admin
 *
 * @return void
 */
function admin_scripts() {
	// wp_enqueue_script(
	// 	'admin',
	// 	TENUP_THEME_TEMPLATE_URL . '/dist/js/admin.js',
	// 	Utility\get_asset_info( 'admin', 'dependencies' ),
	// 	Utility\get_asset_info( 'admin', 'version' ),
	// 	true
	// );
}

/**
 * Enqueue styles for admin
 *
 * @return void
 */
function admin_styles() {
	// wp_enqueue_style(
	// 	'admin-style',
	// 	TENUP_THEME_TEMPLATE_URL . '/dist/css/admin-style.css',
	// 	[],
	// 	Utility\get_asset_info( 'admin-style', 'version' )
	// );
}

/**
 * Removes hardcoded styles for admin bar placemnet.
 */
function remove_admin_bar_layout_styles() {
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
}

/**
 * Asynchronous stylesheet definitions
 *
 * Determines which stylesheets should behave
 * asynchronously on the page by storing their
 * unique handle in an array.
 *
 * @return array
 */
function get_known_handles() {
	$async_styles = [
		'admin-bar',
		'dashicons',
		'single',
		'archive',
		'home',
		'front-page',
		'blocks',
	];

	return $async_styles;
}

/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 *
 * @return void
 */
function js_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}

/**
 * Safari 10.1 supports modules, but does not support the `nomodule` attribute - it will
 * load <script nomodule> anyway.
 *
 * @link https://gist.github.com/samthor/64b114e4a4f539915a95b91ffd340acc
 */
function module_detection() {
	echo "<script>
	(function(d) {
		var js = d.createElement('script');
		if (!('noModule' in js) && 'onbeforeload' in js) {
		  var support = false;
		  d.addEventListener('beforeload', function(e) {
			if (e.target === js) {
			  support = true;
			} else if (!e.target.hasAttribute('nomodule') || !support) {
			  return;
			}
			e.preventDefault();
		  }, true);

		  js.type = 'module';
		  js.src = '.';
		  d.head.appendChild(js);
		  js.remove();
		}
	  })(document);
	</script>";
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://developer.wordpress.org/reference/hooks/style_loader_tag/
 * @param string $html   The style html output.
 * @param string $handle The style handle.
 * @return string
 */
function style_loader_tag( $html, $handle ) {
	// Get previously defined stylesheets.
	$known_handles = get_known_handles();

	// Loop over stylesheets and replace media attribute
	foreach ( $known_handles as $known_style ) {
		if ( $known_style === $handle ) {
			$print_html = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
		}
	}

	if ( ! empty( $print_html ) ) {
		$html = $print_html . '<noscript>' . $html . '</noscript>';
	}

	return $html;
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function script_loader_tag( $tag, $handle ) {
	$new_tag = $tag;
	$attributes = wp_scripts()->get_data( $handle, 'attributes' );

	if ( empty( $attributes ) || ! is_array( $attributes ) ) {
		return $new_tag;
	}

	foreach ( $attributes as $attribute => $value ) {

		if ( ! $value ) {
			break;
		}

		// Abort adding async/defer for scripts that have this script as a dependency. _doing_it_wrong()?
		if ( 'async' === $attribute || 'defer' === $attribute ) {
			foreach ( wp_scripts()->registered as $script ) {
				if ( in_array( $handle, $script->deps, true ) ) {
					break;
				}
			}
		}

		// Add the attribute if it hasn't already been added.
		if ( ! preg_match( ":\s$attribute(=|>|\s):", $new_tag ) ) {

			if ( is_string( $value ) ) {
				$new_tag = preg_replace( ':(?=></script>):', " $attribute" . '="' . $value . '"', $new_tag, 1 );
			} else {
				$new_tag = preg_replace( ':(?=></script>):', " $attribute", $new_tag, 1 );
			}
		}
	}

	return $new_tag;
}

/**
 * Appends a link tag used to add a manifest.json to the head
 *
 * @return void
 */
function add_manifest() {
	echo "<link rel='manifest' href='" . esc_url( TENUP_THEME_TEMPLATE_URL . '/manifest.json' ) . "' />";
}
