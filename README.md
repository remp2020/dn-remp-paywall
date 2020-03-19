# REMP Paywall plugin

The REMP Paywall plugin allows you to control access to posts according to REMP user access tags.

## How to install

### From this repository

Go to the [releases](https://github.com/remp2020/dn-remp-paywall/releases) section of the repository and download the most recent release.

Then, from your WordPress administration panel, go to `Plugins > Add New` and click the `Upload Plugin` button at the top of the page.

## How to Use

From your WordPress administration panel go to `Plugins > Installed Plugins` and scroll down until you find `DN REMP CRM Auth` plugin. You will need to activate it first.

You will also need to install *REMP CRM Auth plugin* (we need this one to access REMP data).

During publishing process, indicate the place where the post content should be cut-off for visitors without correct subscription either by inserting `REMP LOCK` block (if you are using block editor) or `[lock]` shortcode (if you are using classic editor) in your *article content*. Without this shortcode, the whole article will be visible for all your visitors.

Then set the subscription level needed for this article in *sidebar*.

### Configuration

To configure the plugin, and add `DN_REMP_CRM_HOST` and `DN_REMP_CRM_TOKEN` constant definitions into your `wp-config.php` file with the correct host/token of REMP installation. The token can be obtained in your CRM installation under `/api/api-tokens-admin/`.

```php
define( 'DN_REMP_CRM_HOST', 'https://word.press/remp' );
define( 'DN_REMP_CRM_TOKEN', 'foobar' );
```

By default this plugin *does not* show any paywall or CTA box after the cut-off. If you wish to do this, use `remp_content_locked` filter. You can customize the paywall using provided information about user access tags and tag needed for this article.

#### *Params:*

| Name | Value | Description |
| --- |--- | --- |
| content | *String* | Post content without locked part |
| types | *String* | REMP access tags from current visitor |
| type | *String* | REMP access tag needed to unlock this article |

#### *Return value:*

Returns *String* Filtered post content.

#### Example:

```php
add_filter( 'remp_content_locked', function( $content, $types, $type ) {
    $content .= printf( "\n\nYour current subscription types are: %s and you are missing %s to see the whole article.", join( ', ', $types ), $type );
}, 10, 3 );
```

If you need to site-wide alter the behavior of locking articles, use the `dn_remp_paywall_access` filter.

#### *Params:*

| Name | Value |  Description |
| --- |--- | --- |
| type | *String* | REMP access tag needed to unlock this article |
| post | *String* | Post object |

#### *Return value:*

Returns *String* REMP access tag or empty string to unlock article.

If you wish to scroll to the locked part (for instance after succesful payment), use `#remp_lock_anchor` anchor.

#### Authorization token

CRM requires every call to be authorized by a token. For each request presence of `Authorization: Bearer token` HTTP header is validated. The token found in the header needs to match the token assigned to this plugin.