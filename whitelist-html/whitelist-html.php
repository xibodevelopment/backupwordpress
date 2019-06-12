<?php

/**
 * Escapes text for HTML output, allowing certain tags
 *
 * Takes an arbitrary string and ensures it's safe for output into HTML. Unlike
 * `esc_html`, this allows a certain subset of tags, allowing it to be used for
 * strings which need to have some HTML in them (such as translated text).
 *
 * Allowed tags can be passed in one of two formats. The verbose form is the
 * traditional kses form of
 * `[ 'element' => array( 'attr' => true, 'otherattr' => true ) ]` which
 * specifies tags and their attributes.
 *
 * The concise form, useful for inline usage on output, is in the form of
 * `[ 'element', 'otherelement' ]` - This concise form takes the attribute list
 * from WP core's attribute whitelist for a good-enough list for most usages.
 * This can also be passed as a comma separated string.
 *
 * (You can also mix these forms, so something like
 * `[ 'a', 'code', 'x-panel' => array( 'src' => true ) ] )` is perfectly valid.)
 *
 * For example:
 *
 *     whitelist_html( __( 'Hello <a href="http://example.com">World!</a>' ), 'a' );
 *
 * This example would strip any tag except `a`, but would allow the default
 * attributes on it (`href` and `title`).
 *
 * The default attributes and tags are based on {@see wp_kses_allowed_html} with
 * the blank (default) "context". These are the tags in {@see $allowedtags}. To
 * get all allowed post tags, pass `'post'` as the `$context` parameter, or pass
 * the tags you need in the `$allowedtags` array. If a specified tag is not in
 * the list, no attributes will be allowed.
 *
 * @link https://www.tollmanz.com/wp-kses-performance/
 *
 * @param string $text Content to escape
 * @param array $allowedtags Allowed tags, see description.
 * @param string $context kses context to use, {@see wp_kses_allowed_html}.
 * @return string Escaped string for output into HTML context.
 */
function whitelist_html( $text, $allowedtags = array(), $context = '' ) {
	$actually_allowed = array();
	$default_list = wp_kses_allowed_html( $context );

	// Split comma-separated string
	if ( is_string( $allowedtags ) ) {
		$allowedtags = array_map( 'trim', explode( ',', $allowedtags ) );
	}

	foreach ( $allowedtags as $key => $tag ) {
		if ( is_array( $tag ) && is_string( $key ) ) {
			// kses-formatted of `'element' => [ 'attr' => true ]
			// `$tag` is actually the attrs, and `$key` is the tag name
			$actually_allowed[ $key ] = $tag;
			continue;
		}

		if ( ! is_string( $tag ) ) {
			// Not concise form, what even is this?
			_doing_it_wrong( 'whitelist_html', '$allowedtags must consist of strings or kses-style arrays' );
			continue;
		}

		// Grab default attributes for the tag
		$attrs = array();
		if ( isset( $default_list[ $tag ] ) ) {
			$attrs = $default_list[ $tag ];
		}

		// Add to allowed list
		$actually_allowed[ $tag ] = $attrs;
	}

	// Do the sanitization dance
	$sanitized = wp_kses( $text, $actually_allowed );

	/**
	 * Filter a string to be output into HTML, allowing some tags
	 *
	 * @param string $sanitized The text after it has been escaped.
	 * @param string $text The text before it has been escaped.
	 * @param string $allowedtags Tags requested to whitelist.
	 * @param string
	 */
	return apply_filters( 'whitelist_html', $sanitized, $text, $allowedtags, $context );
}

/**
 * Escapes text for HTML output, allowing certain tags, then outputs.
 *
 * @see whitelist_html
 *
 * @param string $text Content to escape
 * @param array $allowedtags Allowed tags, {@see whitelist_html}.
 * @param string $context kses context to use, {@see wp_kses_allowed_html}.
 * @return string Escaped string for output into HTML context.
 */
function print_whitelist_html( $text, $allowedtags = array(), $context = '' ) {
	echo whitelist_html( $text, $allowedtags, $context );
}
