# REMP Paywall plugin

The REMP CRM Auth plugin allows you to read basic user and subscription information about CRM user visiting your WP website. Also adds simple login form with handling.

## How to install

### From this repository

Go to the [releases](https://github.com/remp2020/dn-remp-paywall/releases) section of the repository and download the most recent release.

Then, from your WordPress administration panel, go to `Plugins > Add New` and click the `Upload Plugin` button at the top of the page.

## How to Use

From your WordPress administration panel go to `Plugins > Installed Plugins` and scroll down until you find `DN REMP CRM Auth` plugin. You will need to activate it first.

### Configuration

To configure the plugin, install *REMP CRM Auth plugin* and add `DN_REMP_HOST` and `DN_REMP_PAYWALL_TOKEN` constant definitions into your `wp-config.php` file with the correct host/token of REMP installation. 

```php
define( 'DN_REMP_HOST', 'https://word.press/remp' );
define( 'DN_REMP_PAYWALL_TOKEN', 'foobar' );
```

By default this plugin *does not* show any paywall or CTA box after the cut-off. If you wish to do this, use `remp_content_locked` shordcode.

#### Example:

```php
add_filter( 'remp_content_locked', function( $content, $types, $type ) {
    $content .= printf( "\n\nYour current subscription types are: %s and you are missing %s to see the whole article.", join( ', ', $types ), $type );
}, 10, 3 );
```

If you wish to scroll to the locked part (after succesful payment), use `#remp_lock_anchor` anchor.

#### Authorization token

CRM requires every call to be authorized by a token. For each request presence of `Authorization: Bearer token` HTTP header is validated. The token found in the header needs to match the token assigned this plugin.

### Usage during publishing process

First, indicate the place where the post content should be cut-off for visitors without correct subscription by placing `[lock]` shortcode in your *article content*. Without this shortcode, the whole article will be visible for all your visitors.

Then set the subscription level needed for this article in *sidebar*.
