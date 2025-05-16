<?php
/**
 * Plugin Name:     WP Gems
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          wp community
 * Author URI:      YOUR SITE HERE
 * Text Domain:     wpgems
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wpgems
 */

// Your code starts here.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'wpgems_remove_nofollow' );

function wpgems_remove_nofollow( $content ): string {

	$tags = new WP_HTML_Tag_Processor( $content );
	$home_url = parse_url( get_home_url(), PHP_URL_HOST );
	while ( $tags->next_tag() ) {
		// find all anchors
		if ( 'A' === $tags->get_tag() ) {
			// filter internal links
			$href = $tags->get_attribute( 'href' );
			if ( str_contains( $href, $home_url ) ) {
				// remove rel attribute
				$tags->remove_attribute( 'rel' );
			}

		}
	}
	// return filtered content
	return $tags->get_updated_html();
}

add_filter( 'the_content', function( $content ): string {
	$processor = HTML_Serialization_Builder::create_fragment( $content );
	while ( $processor->next_token() ) {

		if ( 'A' === $processor->get_tag() ) {
			// $processor->prepend( '<span style="background: blue">This is link</span>' );
			// $processor->append( '<span style="background: green">This is link</span>' );
		}
		if ( 'P' === $processor->get_tag() ) {
			// $processor->wrap( '<div style="background: blue" class="wrapped-text">' );
			$processor->unwrap();
		}

	}
	return $processor->build();
} );
