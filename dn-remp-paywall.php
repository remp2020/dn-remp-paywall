<?php


/**
 * Plugin Name: DN REMP Paywall
 * Plugin URI:  https://remp2020.com
 * Description: REMP Paywall plugin. You need to define DN_REMP_HOST and DN_REMP_TOKEN in your wp-config.php file for this plugin to work correctly and then use included functions in your theme.
 * Version:     1.0.0
 * Author:      Michal Rusina
 * Author URI:  http://michalrusina.sk/
 * License:     MIT
 */

if ( !defined( 'WPINC' ) ) {
	die;
}


add_action( 'the_content', 'remp_the_content' );

/**
 * Strips the post content according to current subscription
 *
 * @since 1.0.0
 *
 * @param string $content Post content
 *
 * @return string Returns stripped or full post content according to current subscription
 */

function remp_the_content( $content ) {
	return $content;
}

/*
/api/v1/content-access/list
[
    {
        "code": "web",
        "description": "Web"
    },
    {
        "code": "mobile",
        "description": "Mobilné aplikácie"
    },
    {
        "code": "club",
        "description": "Klub"
    },
    // ...
]
*/