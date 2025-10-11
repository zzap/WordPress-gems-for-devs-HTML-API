<?php
/**
 * Plugin Name:     WPGems
 * Plugin URI:      https://gist.github.com/dmsnell/ff758c13e8d41bf9f0b75f3fd42ad1e5
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          WPSuomi
 * Text Domain:     HTML-Serialization-Builder
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         WPGems
 */

// Your code starts here.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'wpgems_remove_internal_nofollow' );

function wpgems_remove_internal_nofollow( string $the_content ): string {
    $tags          = new WP_HTML_Tag_Processor( $the_content );
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