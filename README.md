# WordPress gems for devs: HTML API

Repository for code examples and resources used in the "WordPress gems for devs: HTML API" talk.

| Event  | Date | Branch | Links |
| --- | --- | --- | --- |
| WordPress Meetup Lisboa | 15 May 2025 | [meetup/lisboa-0525]() | [Event](https://www.meetup.com/wordpress-lisboa/events/307191616/) \| [Slides]() |

## HTML API

### What problems does it solve?

It makes manipulation of HTML faster, easier and more controlled.

It is built completely custom, starting with [HTML standards](https://html.spec.whatwg.org/), which means the parser is never surprised by the HTML it receives but rather it supports the HTML we will probably never see.

### Reference

- [WP_HTML_Tag_Processor](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/)
  - [next_tag()](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/next_tag/)
  - [get_tag()](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/get_tag/)
  - [get_attribute()](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/get_attribute/)
  - [remove_attribute()](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/remove_attribute/)
  - [get_updated_html()](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/get_updated_html/)
- [WP_HTML_Processor](https://developer.wordpress.org/reference/classes/wp_html_processor/)

### Examples

- [Add and remove classes](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/#modifying-css-classes-for-a-found-tag)
- [Set aspect ratio for an iframe](https://gist.github.com/zzap/827c34cf84c5dfef0230a3315805fe3b).
- [Test if images have size values set](https://gist.github.com/zzap/5cb8e0b798262c4d8f7ffe5a3a029933)
- [Add image size to image src](https://gist.github.com/zzap/8c673f6cc8bb10ca3bed82ac426dedd1)
- [Table of contents generator](https://github.com/WordPress/gutenberg/issues/61440#issuecomment-2107797038)
- Tests:
  - [HTML Tag Processor functionality](https://github.com/dmsnell/wordpress-develop/blob/aad531083a2eb33a051b1c8782a6c75a6d51c8b3/tests/phpunit/tests/html/wpHtmlTagProcessor.php)
  - [WP_HTML_Tag_Processor bookmark functionality](https://github.com/dmsnell/wordpress-develop/blob/aad531083a2eb33a051b1c8782a6c75a6d51c8b3/tests/phpunit/tests/html/wpHtmlTagProcessor-bookmark.php)

### Resources

- [HTML Tag Processor Roadmap](https://github.com/WordPress/gutenberg/issues/44410)
- [Introducing the HTML API in WordPress 6.2](https://make.wordpress.org/core/2023/03/07/introducing-the-html-api-in-wordpress-6-2/)
- [HTML standards](https://html.spec.whatwg.org/)

### Equivalents in other PHP frameworks

None.
