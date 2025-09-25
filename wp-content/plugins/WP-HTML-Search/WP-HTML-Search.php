<?php
/**
 * Plugin Name:     WP_HTML_Search
 * Plugin URI:      https://gist.github.com/dmsnell/c390f73a4668ddc4b8fe178c752af333
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Dennis Snell
 * Author URI:      YOUR SITE HERE
 * Text Domain:     WP-HTML-Search
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         WP_HTML_Search
 */

// Your code starts here.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Searches for an exact match of a given string within the provided HTML content.
 *
 * This function utilizes the WP_HTML_Search class to create a fragment of the HTML,
 * prepares an index for searching, and attempts to find an exact match for the
 * specified search string. If a match is found, it outputs the surrounding HTML
 * content with the matched text highlighted. If no match is found, it outputs
 * a "Not found!" message and exits the script with a status code of 1.
 *
 * @param string $html The HTML content in which to search for the string.
 * @param string $search The string to search for within the HTML content.
 *
 * @return void This function does not return a value. It outputs the result directly.
 */
function html_match_exact( string $html, string $search ) {
	$si = WP_HTML_Search::create_fragment( $html );
	$si->prepare_index();

	$match = $si->match_exact( $search, 0 );
	if ( null === $match ) {
		echo "\e[31mNot found!\e[m\n";
		exit(1);
	}

	echo "\e[90m";
	echo substr( $html, 0, $match->containing_html_at );
	echo "\e[m";
	echo substr( $html, $match->containing_html_at, $match->plaintext_at - $match->containing_html_at );
	echo "\e[33m";
	echo substr( $html, $match->plaintext_at, $match->plaintext_end - $match->plaintext_at );
	echo "\e[m";
	echo substr( $html, $match->plaintext_end, $match->containing_html_at + $match->containing_html_length - $match->plaintext_end );
	echo "\e[90m";
	echo substr( $html, $match->containing_html_at + $match->containing_html_length );
	echo "\e[m\n";
}

/**
 * Matches a given pattern against HTML content and outputs the results.
 *
 * This function utilizes the WP_HTML_Search class to create a fragment from the provided HTML,
 * prepares an index for searching, and then matches the specified pattern using PCRE.
 * If no matches are found, it outputs a "Not found!" message and exits with a status code of 1.
 * For each match found, it outputs the surrounding HTML context along with the matched plaintext,
 * highlighting the matched portions in different colors for better visibility.
 *
 * @param string $html The HTML content to search within.
 * @param string $pattern The PCRE pattern to match against the HTML content.
 *
 * @return void
 */
function html_match_pcre( string $html, string $pattern ) {
	$si = WP_HTML_Search::create_fragment( $html );
	$si->prepare_index();

	$matches = $si->match_pcre( $pattern, 0 );
	if ( null === $matches ) {
		echo "\e[31mNot found!\e[m\n";
		exit(1);
	}

	foreach ( $matches as $key => $match ) {
		echo "\e[90mMatch \e[32m{$key}\e[m\n";
		echo "\e[90m";
		echo substr( $html, 0, $match->containing_html_at );
		echo "\e[m";
		echo substr( $html, $match->containing_html_at, $match->plaintext_at - $match->containing_html_at );
		echo "\e[33m";
		echo substr( $html, $match->plaintext_at, $match->plaintext_end - $match->plaintext_at );
		echo "\e[m";
		echo substr( $html, $match->plaintext_end, $match->containing_html_at + $match->containing_html_length - $match->plaintext_end );
		echo "\e[90m";
		echo substr( $html, $match->containing_html_at + $match->containing_html_length );
		echo "\e[m\n";
	}
}


if ( ! class_exists( 'WP_HTML_Plaintext_Mapping' ) ) {
	/**
	 * Class WP_HTML_Plaintext_Mapping
	 *
	 * This class represents a mapping between HTML content and its corresponding plaintext.
	 *
	 * @property int $containing_html_at The starting position of the HTML content.
	 * @property int $containing_html_length The length of the HTML content.
	 * @property int $plaintext_at The starting position of the plaintext content.
	 * @property int $plaintext_end The ending position of the plaintext content.
	 */
	class WP_HTML_Plaintext_Mapping {
		public function __construct(
			public int $containing_html_at,
			public int $containing_html_length,
			public int $plaintext_at,
			public int $plaintext_end,
		) {

		}
	}
}

if ( ! class_exists( 'WP_HTML_Search' ) ) {

	/**
	 * Class WP_HTML_Search
	 *
	 * This class extends the WP_HTML_Processor to handle the processing of HTML content
	 * and extraction of plaintext. It manages the mapping between plaintext and its
	 * corresponding HTML chunks, allowing for efficient searching and indexing.
	 *
	 * Properties:
	 * - string $plaintext: Decoded plaintext content of HTML, aspiring to be `.textContent`.
	 * - array $plaintext_chunks_at: Byte offsets where plaintext chunks start.
	 * - array $plaintext_chunks_length: Byte lengths of plaintext chunks.
	 * - array $html_chunks_at: Byte offsets where HTML containing plaintext chunks start.
	 * - array $html_chunks_length: Byte lengths of HTML spans containing plaintext chunks.
	 *
	 * Methods:
	 * - here(): Returns the current position in the HTML being processed.
	 * - raw(): Returns the raw HTML content at the current position.
	 * - skip(): Skips over elements that should not be processed.
	 * - prepare_index(): Prepares the index of plaintext and HTML chunks for searching.
	 * - match_exact(string $pattern, int $offset): Matches an exact pattern in the plaintext.
	 * - match_pcre(string $pattern, int $offset): Matches a pattern using PCRE in the plaintext.
	 * - should_skip_element(): Determines if the current element should be skipped based on its type or attributes.
	 * - map(int $offset, int $length): Maps a plaintext offset and length to its corresponding HTML range.
	 * - map_text_node(int $index, int $offset, $side): Maps a text node index to its corresponding HTML position.
	 */
	class WP_HTML_Search extends WP_HTML_Processor {
		/**
		 * Decoded plaintext content of HTML, aspiring to be `.textContent`.
		 *
		 * @var string
		 */
		public $plaintext = '';

		/**
		 * Byte offsets where plaintext chunks start.
		 *
		 * @var array
		 */
		public $plaintext_chunks_at = array();

		/**
		 * Byte lengths of plaintext chunks.
		 *
		 * @var array
		 */
		public $plaintext_chunks_length = array();

		/**
		 * Byte offsets where HTML containing plaintext chunks start.
		 *
		 * @var array
		 */
		public $html_chunks_at = array();

		/**
		 * Byte lengths of HTML spans containing plaintext chunks.
		 * @var array
		 */
		public $html_chunks_length = array();

		/**
		 * Sets a bookmark at the current position in the search results.
		 *
		 * This method attempts to create a bookmark labeled 'here'.
		 * Note that virtual tokens cannot be bookmarked, but the indices
		 * may still be required for functionality.
		 *
		 * @return mixed The bookmark index for 'here', or null if the bookmark could not be set.
		 */
		public function here() {
			// @todo Cannot bookmark virtual tokens, but we need the indices probably.
			@$this->set_bookmark( 'here' );
			return $this->bookmarks['_here'];
		}

		/**
		 * Retrieves a raw substring of HTML based on the current position.
		 *
		 * This method uses the `here()` function to determine the starting position
		 * and length of the substring to extract from the HTML content.
		 *
		 * @return string The raw HTML substring.
		 */
		public function raw() {
			$here = $this->here();
			return substr( $this->html, $here->start, $here->length );
		}

		/**
		 * Skip tokens until the current depth is less than or equal to the previous depth.
		 *
		 * This method iterates through the tokens, continuing until it finds a token
		 * that indicates the current depth has changed. If a closing tag is encountered,
		 * it advances to the next token.
		 *
		 * @return void
		 */
		public function skip() {
			$depth = $this->get_current_depth();
			while ( $this->next_token() && $depth > $this->get_current_depth() ) {
				continue;
			}
			if ( $this->is_tag_closer() ) {
				$this->next_token();
			}
		}

		/**
		 * Prepares the index for the HTML search by processing tokens.
		 *
		 * This method iterates through the tokens and collects plaintext chunks
		 * while maintaining their positions in the original HTML. It handles
		 * specific token types such as text nodes and line breaks, while skipping
		 * elements that should not be indexed.
		 *
		 * The method utilizes a custom error handler to manage specific warnings
		 * that may indicate the need to retry processing. If an error occurs that
		 * suggests a retry, the method will attempt to process the tokens again.
		 *
		 * @return bool Returns true if the indexing was successful and no incomplete
		 *              tokens are present, false otherwise.
		 */
		public function prepare_index(): bool {
			$should_retry = false;
			$did_retry    = false;
			set_error_handler( function( $errno, $errstr ) use ( &$should_retry ) {
				if ( str_starts_with( $errstr, 'should_retry' ) ) {
					$should_retry = true;
					return true;
				}

				return false;
			}, E_WARNING );

			retry:
			while ( $this->next_token() ) {
				if ( $this->should_skip_element() ) {
					$this->skip();
					continue;
				}

				// @todo: This might product garbage if paused on a virtual token.
				$here = $this->here();

				$token_name = $this->get_token_name();
				switch ( $token_name ) {
					case '#text':
						$chunk = $this->get_modifiable_text();
						break;

					case 'BR':
						$chunk = "\n";
						break;

					default:
						continue 2;
				}

				$this->plaintext_chunks_at[]     = strlen( $this->plaintext );
				$this->plaintext_chunks_length[] = strlen( $chunk );
				$this->html_chunks_at[]          = $here->start;
				$this->html_chunks_length[]      = $here->length;
				$this->plaintext                .= $chunk;
			}

			restore_error_handler();

			return ! $this->paused_at_incomplete_token() && null === $this->get_token_name();
		}

		/**
		 * Searches for an exact match of a given pattern in the plaintext starting from a specified offset.
		 *
		 * @param string $pattern The pattern to search for in the plaintext.
		 * @param int $offset The position in the plaintext to start the search from.
		 *
		 * @return WP_HTML_Plaintext_Mapping|null Returns a mapping object if a match is found,
		 *                                         or null if no match is found.
		 */
		public function match_exact( string $pattern, int $offset ): ?WP_HTML_Plaintext_Mapping {
			$match_at = strpos( $this->plaintext, $pattern, $offset );
			if ( false === $match_at ) {
				return null;
			}

			return $this->map( $match_at, strlen( $pattern ) );
		}

		/**
		 * Matches a given PCRE pattern against the plaintext starting from a specified offset.
		 *
		 * This method uses the `preg_match` function to find matches of the provided pattern
		 * in the plaintext property of the class. If a match is found, it processes the matches
		 * and returns an array of mapped offsets and lengths of the matched chunks.
		 *
		 * @param string $pattern The PCRE pattern to match against the plaintext.
		 * @param int $offset The offset in the plaintext to start the search from.
		 *
		 * @return array|null Returns an array of matches with their mapped offsets and lengths,
		 *                   or null if no match is found.
		 */
		public function match_pcre( string $pattern, int $offset ): ?array {
			if ( 1 !== preg_match( $pattern, $this->plaintext, $preg_matches, PREG_OFFSET_CAPTURE, $offset ) ) {
				return null;
			}

			$matches = array();
			foreach ( $preg_matches as $key => $match ) {
				list( $chunk, $offset ) = $match;

				$matches[ $key ] = $this->map( $offset, strlen( $chunk ) );
			}

			return $matches;
		}

		/**
		 * Determines whether an HTML element should be skipped during processing.
		 *
		 * This method checks the token name of the element and evaluates
		 * specific attributes to decide if the element should be ignored.
		 *
		 * The following conditions will result in the element being skipped:
		 * - If the token name is 'TEMPLATE', 'SCRIPT', or 'STYLE'.
		 * - If the 'aria-hidden' attribute is set (not null).
		 * - If the 'style' attribute contains 'display: hidden;'.
		 *
		 * @return bool True if the element should be skipped, false otherwise.
		 */
		public function should_skip_element() {
			$token_name = $this->get_token_name();

			switch ( $token_name ) {
				case 'TEMPLATE':
				case 'SCRIPT':
				case 'STYLE':
					return true;
			}

			if ( null !== $this->get_attribute( 'aria-hidden' ) ) {
				return true;
			}

			// @todo: Parse the CSS, or at least PCRE match on boundaries.
			$style = $this->get_attribute( 'style' );
			if ( is_string( $style ) && str_contains( $style, 'display: hidden;' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Maps a segment of plaintext to its corresponding HTML representation.
		 *
		 * This method takes an offset and a length, and determines the start and end
		 * indices of the corresponding HTML chunks based on the provided plaintext
		 * chunk information. It then creates a mapping of the plaintext segment to
		 * the HTML segment, including leading and trailing text nodes.
		 *
		 * @param int $offset The starting position in the plaintext.
		 * @param int $length The length of the plaintext segment to map.
		 *
		 * @return WP_HTML_Plaintext_Mapping The mapping object containing the start
		 *                                    and end positions in the HTML, along
		 *                                    with leading and trailing text nodes.
		 */
		public function map( int $offset, int $length ): WP_HTML_Plaintext_Mapping {
			$chunk_count = count( $this->plaintext_chunks_at );
			$end_offset  = $offset + $length;

			for ( $index = 0; $index < $chunk_count; $index++ ) {
				$chunk_start  = $this->plaintext_chunks_at[ $index ];
				$chunk_length = $this->plaintext_chunks_length[ $index ];

				if ( $offset >= $chunk_start && $offset < $chunk_start + $chunk_length ) {
					break;
				}
			}

			$start_index = max( 0, min( $chunk_count - 1, $index ) );

			for ( ; $index < $chunk_count; $index++ ) {
				$chunk_start  = $this->plaintext_chunks_at[ $index ];
				$chunk_length = $this->plaintext_chunks_length[ $index ];

				if ( $end_offset >= $chunk_start && $end_offset < $chunk_start + $chunk_length ) {
					break;
				}
			}

			$end_index = max( 0, min( $chunk_count - 1, $index ) );

			$html_start = $this->html_chunks_at[ $start_index ];
			$html_end   = $this->html_chunks_at[ $end_index ] + $this->html_chunks_length[ $end_index ];

			echo "\e[90mMapping \e[34m" . substr( $this->plaintext, $offset, $length ) . "\e[90m into \e[36m" . substr( $this->html, $html_start, $html_end - $html_start ) . "\e[m\n";

			return new WP_HTML_Plaintext_Mapping(
				$html_start,
				$html_end - $html_start ,
				$this->map_text_node( $start_index, $offset, 'leading' ),
				$this->map_text_node( $end_index, $offset + $length, 'trailing' )
			);
		}

		/**
		 * Maps a text node index to its corresponding HTML position.
		 *
		 * This method calculates the position in the HTML string based on the
		 * provided index and offset within the plaintext chunks. It handles
		 * character references and ensures that the correct position is returned
		 * based on the specified side (leading or trailing).
		 *
		 * @param int $index The index of the plaintext chunk.
		 * @param int $offset The offset within the plaintext chunk.
		 * @param string $side Indicates whether to return the leading or trailing
		 *                     position of the character reference ('leading' or
		 *                     any other value for trailing).
		 *
		 * @return int The position in the HTML string corresponding to the given
		 *             index and offset.
		 *
		 * @throws Error If the calculated position is before the start of the chunk.
		 */
		public function map_text_node( int $index, int $offset, $side ): int {
			$get_to = $offset - $this->plaintext_chunks_at[ $index ];
			if ( $get_to < 0 ) {
				throw new Error( "Cannot find index before chunk!" );
			}

			if ( 0 === $get_to ) {
				return $this->html_chunks_at[ $index ];
			}

			$at     = $this->html_chunks_at[ $index ];
			$end    = $at + $this->html_chunks_length[ $index ];
			$was_at = $at;
			$got_to = 0;

			while ( $at < $end ) {
				$need = $get_to - $got_to;
				if ( 0 === $need ) {
					return $was_at;
				}

				$amp_after = strcspn( $this->html, '&', $at, $end - $at );
				$amp_at    = $at + $amp_after;

				// No more potential character references.
				if ( $amp_at >= $end ) {
					return $was_at + $get_to - $got_to;
				}

				// The ampersand is after the offset.
				if ( $amp_after > $need ) {
					return $was_at + $get_to - $got_to;
				}

				// The ampersand is before the offset.
				$got_to += $amp_after;
				$decoded = WP_HTML_Decoder::read_character_reference( 'data', $this->html, $amp_at, $match_byte_length );

				if ( isset( $decoded ) ) {
					$got_to += strlen( $decoded );

					/*
					 * Since character references can decode into two code points,
					 * any offset within the decoded range maps to its start, which
					 * will collapse some offsets, but this is proper because they
					 * do actually collapse to the same starting offset in the HTML.
					 */
					if ( $got_to >= $get_to ) {
						return 'leading' === $side ? $amp_at : $amp_at + $match_byte_length;
					}

					$was_at = $amp_at + $match_byte_length;
				} else {
					++$got_to;
					++$was_at;
				}
			}
		}
	}
}