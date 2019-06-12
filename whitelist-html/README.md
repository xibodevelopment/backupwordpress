# Whitelist HTML

Introduces an `esc_*()`-like function for when you need to allow *some* HTML.

## Rationale
### Background

Best practices when working with any sort of data is to escape your output, and
do it as late as possible. Values can't usually know about where they're going
to be used, so you need to escape based on whatever context you're
outputting into. These are things like `esc_attr()` for HTML attribute values,
`esc_html()` for text in HTML, and so on.

Even if values could know about their output context, it's still possible for
users to craft malicious output if you're not escaping properly. For this
reason, you need to do sanitization on input (to ensure your value is correct),
as well as escaping on output (to ensure the value is output into the context
correctly).

Right now across every WordPress site, there's a glaring hole in escaping, and
hence in security.

When translating strings in WordPress, the most common functions to use are
`__()` (translate and return) or `_e()` (translate and output). Where possible,
these need to be escaped too, to ensure that translations don't accidentally
break your output. For this reason, `esc_html_e()`, `esc_attr_e()`, etc are
offered as convenience functions.

However, this falls down when you need to have HTML in the translation.
Translation best practices say to include as much information as possible for
translators when you translate a string. This means including HTML tags in the
string so translators can understand how the sentence is formed.

It's possible to do "clever" hacks to get around this with placeholders,
for example:

```php
$text = sprintf(
	esc_html__( 'This is some text %1$swith a link%2$s'),
	'<a href="http://example.com/">',
	'</a>'
);
```

Note though that this is much harder for translators to understand, since they
can't intuitively tell what's going on without checking the code. Even with
translator comments, it's still harder to understand. There's also no guarantee
that this is secure. You could swap the placeholders, or leave out pieces. Best
practice states that we should instead have the following:

```php
$text = sprintf(
	esc_html__( 'This is some text <a href="%1$s">with a link</a>'),
	'http://example.com/'
);
```

Right now, the policy is essentially to treat translated strings with HTML as
trusted. Not only does this push the burden off to translation validators in
GlotPress, but it means you're no longer in control of your output. This is an
attack vector waiting to be exploited.


### How do we solve this?

WordPress contains functions specifically designed to help with this problem.
After all, people can submit comments or posts with HTML in them, but WP can
handle this fine. WordPress handles this through a library called kses, which
sanitizes HTML down to a small, whitelisted subset of HTML. Posts can have more
HTML tags than comments can, since they're usually semi-trusted users.

kses is great, but is not typically used outside of large HTML blocks like post
or comment content. The reason for this is often stated as performance. It's
well-known that kses is pretty slow, since it has to essentially disassemble the
HTML, then reconstruct it with the allowed tags.

However, Zack Tollman wrote a [fantastic post][tollmanz-kses] that calls into
question this accepted knowledge of kses performance. Zack's findings show that
while kses is worse with performance on longer pieces of content (like post
content), it's actually closer to being on-par with other escaping for short
strings. This is even more evident when reducing the whitelist of elements down
from the default to just the elements you need.

[tollmanz-kses]: https://www.tollmanz.com/wp-kses-performance/

### `whitelist_html`

This library provides a nice, easy, performant way to perform sanitization on
translated strings. Rather than requiring you to work with the internals of
kses, it's much closer to functions like `esc_html`.

Security is only useful if it's also usable. For the most part, `whitelist_html`
can be used in exactly the same way developers are used to using other escaping
functions.

A quick example to demonstrate how easy it is:
```html
<!-- Previously -->
<p><?php _e( 'This is a terrific use of <code>WP_Error</code>.' ) ?></p>

<!-- Secure version -->
<p><?php print_whitelist_html( __( 'This is a terrific use of <code>WP_Error</code>.' ), 'code' ) ?></p>
```

Even if a malicious translator changed this to include a link to a spam site (or
worse), this would be caught and stripped by `whitelist_html`.

Taking our original example from above, we can modify it to only allow `a` tags:

```php
$text = whitelist_html(
	sprintf(
		__( 'This is some text <a href="%1$s">with a link</a>'),
		'http://example.com/'
	),
	'a'
);
```

It's that easy. You can do this with multiple elements as well, using a
comma-separated string or list of elements:

```php
$text = whitelist_html(
	sprintf(
		__( 'This is <code>some</code> text <a href="%1$s">with a link</a>'),
		'http://example.com/'
	),
	'a, code' // or array( 'a', 'code' )
);
```

If you need custom attributes, you can use kses-style attribute specifiers.
These can be mixed too:

```php
$text = whitelist_html(
	sprintf(
		__( 'This is <span class="x">some</span> text <a href="%1$s">with a link</a>'),
		'http://example.com/'
	),
	array(
		'a',
		'span' => array(
			'class' => true,
		),
	)
);
```


### Performance Test

In a quick test, the string
`'hello with a <a href="wak://example.com">malicious extra link!<///q><o>b'` was
run through both `whitelist_html` (with only `a`) and `esc_html` with 10,000
iterations. While the two functions don't perform the same task, they're both
escaping functions, so it's useful to compare performance to understand whether
this approach can be used in production code.

In an unscientific trial, this gave figures of 0.96s for `whitelist_html` and
1.07s for `esc_html` for 10,000 trials each. This indicates that
`whitelist_html` is at least on the order of other escaping functions.


## Using this Library

Two steps to using this library:

1. Add this library in as a git submodule.
2. Load `whitelist-html.php` before you need to use it. We recommend in
   `mu-plugins`, but you can also load it in via `wp-config.php` if you want it
   earlier.

Done. Start using the function.
