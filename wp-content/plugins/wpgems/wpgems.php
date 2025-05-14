<?php
/**
 * Plugin Name:     WP Gems
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          The Community
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

add_filter( 'the_content', 'wpgems_filter_the_content' );

function wpgems_filter_the_content( $the_content ): string {
	$tags = new WP_HTML_Tag_Processor( $the_content );
	$home_url_host = parse_url( get_home_url(), PHP_URL_HOST );
	while ( $tags->next_tag() ) {
		if ( 'A' === $tags->get_tag() ) {
			$href = $tags->get_attribute( 'href' );

			if ( str_contains( $href, $home_url_host ) ) {
				$tags->remove_attribute( 'rel' );
			}
		}
	}

	return $tags->get_updated_html();
}

add_filter( 'the_content', function( $the_content ) {
	$processor = HTML_Serialization_Builder::create_fragment( $the_content );

	while ( $processor->next_token() ) {
		if ( 'A' === $processor->get_tag() ) {
			// $processor->append( 'This is the link' );
			// $processor->append( '<span style="background: blue;">This is the link.</span>' );
			// $processor->prepend( '<span style="background: red;">This is the p.</span>' );
			// $processor->unwrap();
		}
		if ( 'P' === $processor->get_tag() ) {
			// $processor->prepend( '<span style="background: red;">This is the p.</span>' );
			// $processor->wrap( '<div style="background: green;" class="wrapped-text">' );
			$processor->unwrap();
		}
	}

	return $processor->build();
});
