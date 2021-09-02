<?php
/**
 * Utility functions for the theme.
 *
 * This file is for custom helper functions.
 * These should not be confused with WordPress template
 * tags. Template tags typically use prefixing, as opposed
 * to Namespaces.
 *
 * @link https://developer.wordpress.org/themes/basics/template-tags/
 * @package TenUpTheme
 */

namespace TenUpTheme\Utility;

/**
 * Get asset info from extracted asset files
 *
 * @param string $slug Asset slug as defined in build/webpack configuration
 * @param string $attribute Optional attribute to get. Can be version or dependencies
 * @return string|array
 */
function get_asset_info( $slug, $attribute = null ) {
	if ( file_exists( TENUP_THEME_PATH . 'dist/js/' . $slug . '.asset.php' ) ) {
		$asset = require TENUP_THEME_PATH . 'dist/js/' . $slug . '.asset.php';
	} elseif ( file_exists( TENUP_THEME_PATH . 'dist/css/' . $slug . '.asset.php' ) ) {
		$asset = require TENUP_THEME_PATH . 'dist/css/' . $slug . '.asset.php';
	} else {
		return null;
	}

	if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
		return $asset[ $attribute ];
	}

	return $asset;
}

/**
 * Preload attachment image, defaults to post thumbnail
 *
 * @return void
 */
function preload_post_thumbnail() {
	global $post;

	/** Adjust image size based on post type or other factor. */
	$image_size = 'full';
	$image_size = apply_filters( 'preload_post_thumbnail_image_size', $image_size, $post );

	/** Get post thumbnail if an attachment ID isn't specified. */
	$thumbnail_id = apply_filters( 'preload_post_thumbnail_id', get_post_thumbnail_id(), $post );

	/** Get the image */
	$image = wp_get_attachment_image_src( $thumbnail_id, $image_size );
	$src = '';
	$attrs = [];
	$attr = '';

	/* @TODO: Preload the first featured blog post featured image on the posts page */
	if ( $image && ( is_single() || is_page() ) ) {
		list( $src, $width, $height ) = $image;

		/**
		 * The following code which generates the srcset is plucked straight
		 * out of wp_get_attachment_image() for consistency as it's important
		 * that the output matches otherwise the preloading could become ineffective.
		 *
		 * @see (https://core.trac.wordpress.org/browser/tags/5.7.1/src/wp-includes/media.php#L1066)
		 */
		$image_meta = wp_get_attachment_metadata( $thumbnail_id );

		if ( is_array( $image_meta ) ) {
			$size_array = array( absint( $width ), absint( $height ) );
			$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $thumbnail_id );

			if ( $srcset ) {
				$attrs['imagesrcset'] = $srcset;
				$attrs['imagesizes'] = '100vw';
			}
		}

		foreach ( $attrs as $name => $value ) {
			$attr .= "$name=" . '"' . $value . '" ';
		}
	} else {
		/** Early exit if no image is found. */
		return;
	}

	/** Output the link HTML tag */
	printf( '<link rel="preload" as="image" href="%s" %s/><link rel="preload" as="image" href="%s/dist/svg/brush-bottom.svg"/>', esc_url( $src ), $attr, HOLT_THEME_TEMPLATE_URL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Appends link tag for preloading and preconnecting. Booster for performance.
 *
 * @return void
 */
function link_preload_preconnect() {
	$preconnect_hrefs = [
		'css' => [],
	];
	$preload_hrefs    = [
		'font' => [],
	];

	$allowed_tags = [
		'link' => [
			'rel'         => true,
			'href'        => true,
			'as'          => true,
			'type'        => true,
			'crossorigin' => true,
		],
	];

	foreach ( $preconnect_hrefs as $href ) {
		echo "<link rel='preconnect' href='" . esc_url( $href ) . "' crossorigin>";
	}

	foreach ( $preload_hrefs as $type => $assets ) {
		foreach ( $assets as $asset ) {
			$attrs = 'font' === $type ? 'type=font/woff2 crossorigin' : '';
			$font_tag = "<link rel='preload' as='" . esc_attr( $type ) . "' href='" . esc_url( $asset ) . "'" . esc_attr( $attrs ) . ">\n";

			echo $font_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
