<?php
/**
 * Plugin Name:     HTML_Serialization_Builder
 * Plugin URI:      https://gist.github.com/dmsnell/ff758c13e8d41bf9f0b75f3fd42ad1e5
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Dennis Snell
 * Author URI:      https://github.com/dmsnell
 * Text Domain:     HTML-Serialization-Builder
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         HTML_Serialization_Builder
 */

// Your code starts here.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'HTML_Serialization_Builder' ) ) {

	/**
	 * HTML_Serialization_Builder class.
	 *
	 * This class can be used to perform structural changes to an
	 * HTML document while maintaining some but not all safety
	 * protections. Namely, proper nesting structure of HTML is
	 * maintained, but HTML updates could still leak out of the
	 * containing parent node. For example, this allows inserting
	 * an A element inside an open A element, which would close
	 * the containing A element.
	 *
	 * Modifications may be requested for a document _once_ after
	 * matching a token. Due to the way the modifications are
	 * applied, it's not possible to set the inner HTML for a
	 * node more than once, or append more than one HTML chunk.
	 */
	class HTML_Serialization_Builder extends WP_HTML_Processor {
		private $output;
		private $last_token = null;

		public function next_token(): bool {
			if ( isset( $this->last_token ) ) {
				$this->output    .= $this->last_token;
				$this->last_token = null;
			}

			$did_match = parent::next_token();
			$this->last_token = $this->serialize_token();

			return $did_match;
		}

		public function build() {
			if ( isset( $this->last_token ) ) {
				$this->output    .= $this->last_token;
				$this->last_token = null;
			}

			return $this->output;
		}

		public function append( $html ) {
			if ( ! isset( $this->last_token ) ) {
				return false;
			}

			$this->output    .= $this->last_token;
			$this->output    .= WP_HTML_Processor::normalize( $html );
			$this->last_token = null;
		}

		public function prepend( $html ) {
			if ( ! isset( $this->last_token ) ) {
				return false;
			}

			$this->output    .= WP_HTML_Processor::normalize( $html );
			$this->output    .= $this->last_token;
			$this->last_token = null;
		}

		public function set_inner_html( $html ) {
			if ( $this->is_tag_closer() || ! isset( $this->last_token ) ) {
				return false;
			}

			$prelude = implode( '', array_map( fn ( $name ) => "<{$name}>", $this->get_breadcrumbs() ) );
			$inner_p = WP_HTML_Processor::create_full_parser( "{$prelude}{$html}" );
			$inner_html = '';
			while ( $inner_p->next_token() && $inner_p->get_current_depth() < count( $this->get_breadcrumbs() ) ) {
				// Skip past the prelude
				continue;
			}
			while ( $inner_p->next_token() && $inner_p->get_current_depth() >= count( $this->get_breadcrumbs()) ) {
				$inner_html .= $inner_p->serialize_token();
			}
			if ( $inner_p->is_tag_closer() ) {
				$inner_p->next_token();
			}
			$has_more_tokens = $inner_p->next_token();

			$this->output    .= $this->last_token;
			$this->output    .= $has_more_tokens ? '' : $inner_html;
			$this->last_token = null;

			$depth = $this->get_current_depth();
			while ( $this->get_current_depth() >= $depth && parent::next_token() ) {
				$this->last_token = null;
				if ( $has_more_tokens ) {
					$this->output .= $this->serialize_token();
				}
				continue;
			}

			$this->output    .= $this->serialize_token();
			$this->last_token = null;
			return true;
		}

		public function set_outer_html( $html ) {
			if ( $this->is_tag_closer() || ! isset( $this->last_token ) ) {
				return false;
			}

			$depth = $this->get_current_depth() - 1;
			$prelude = implode( '', array_map( fn ( $name ) => "<{$name}>", array_slice( $this->get_breadcrumbs(), 0, -1 ) ) );
			$inner_p = WP_HTML_Processor::create_full_parser( "{$prelude}{$html}" );
			$inner_html = '';
			while ( $inner_p->next_token() && $inner_p->get_current_depth() < count( $this->get_breadcrumbs() ) - 1 ) {
				// Skip past the prelude
				continue;
			}
			while ( $inner_p->next_token() && $inner_p->get_current_depth() >= count( $this->get_breadcrumbs() ) - 1 ) {
				$inner_html .= $inner_p->serialize_token();
			}
			if ( $inner_p->is_tag_closer() ) {
				$inner_p->next_token();
			}
			$has_more_tokens = $inner_p->next_token();

			if ( $has_more_tokens ) {
				$this->output .= $this->last_token;
			} else {
				$this->output .= $inner_html;
			}
			$this->last_token = null;

			while ( $this->get_current_depth() >= $depth && parent::next_token() ) {
				$this->last_token = null;
				if ( $has_more_tokens ) {
					$this->output .= $this->serialize_token();
				}
				continue;
			}

			if ( ! $has_more_tokens ) {
				$this->last_token = $this->serialize_token();
			}
			return true;
		}

		public function wrap( $wrapping_tag ) {
			if ( $this->is_tag_closer() || ! isset( $this->last_token ) ) {
				return false;
			}

			$wrapper = WP_HTML_Processor::create_fragment( $wrapping_tag );
			if (
				false === $wrapper->next_token() ||
				'#tag' !== $wrapper->get_token_type() ||
				WP_HTML_Processor::is_void( $wrapper->get_token_name() )
			) {
				return false;
			}

			$this->output    .= $wrapper->serialize_token();
			$this->output    .= $this->serialize_token();
			$this->last_token = null;

			$depth = $this->get_current_depth();
			while ( $this->get_current_depth() > $depth && parent::next_token() ) {
				$this->output    .= $this->serialize_token();
				$this->last_token = null;
			}

			$this->output    .= '</' . strtolower( $wrapper->get_tag() ) . '>';
			$this->last_token = null;
			return true;
		}

		/**
		 * @todo This currently doesn't remove the end tag; why?
		 *
		 * @return bool
		 */
		public function unwrap() {
			if ( $this->is_tag_closer() || ! isset( $this->last_token ) ) {
				return false;
			}

			$this->last_token = null;
			$depth            = $this->get_current_depth();
			while ( $this->get_current_depth() >= $depth && parent::next_token() ) {
				$this->output    .= $this->serialize_token();
				$this->last_token = null;
			}

			$this->last_token = null;
			return true;
		}
	}
}
