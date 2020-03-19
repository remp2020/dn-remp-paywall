<?php


/**
 * Plugin Name: DN REMP Paywall
 * Plugin URI:  https://remp2020.com
 * Description: REMP Paywall plugin. You need to install <strong>DN REMP CRM Auth</strong> plugin and define <code>DN_REMP_CRM_HOST</code> and <code>DN_REMP_CRM_TOKEN</code> in your <code>wp-config.php</code> file for this plugin to work correctly and then use included functions in your theme.
 * Version:     1.0.0
 * Author:      Michal Rusina
 * Author URI:  http://michalrusina.sk/
 * License:     MIT
 */

if ( !defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'remp_paywall_activate' );

add_shortcode( 'lock', 'remp_lock_shortcode' );

add_action( 'init', 'remp_paywall_init' );
add_action( 'the_content', 'remp_paywall_the_content' );
add_action( 'post_submitbox_misc_actions', 'remp_paywall_post_submitbox_misc_actions' );
add_action( 'save_post', 'remp_paywall_save_post', 10, 3 );
add_action( 'enqueue_block_editor_assets', 'remp_paywall_enqueue_assets' );


/**
 * Register paywall access code post meta
 *
 * @since 1.0.0
 */

register_post_meta( 'post', '_dn_remp_paywall_access', [
	'show_in_rest' => true,
	'single' => true,
	'type' => 'string',
	'auth_callback' => function() {
		return current_user_can( 'edit_posts' );
	}
] );


/**
 * Enqueue scripts for block editor
 *
 * @since 1.0.0
 */

function remp_paywall_enqueue_assets() {
	$types = remp_paywall_get_types();
	$options = [ [
		'label' => __( 'Odomknutý', 'dn-remp-paywall' ),
		'value' => ''
	] ];

	foreach ( $types as $type ) {
		$options[] = [
			'label' => $type['description'],
			'value' => $type['code']
		];
	}	

	wp_enqueue_script( 'dn-remp-paywall-js', plugins_url( 'dn-remp-paywall.js', __FILE__ ), [ 'wp-blocks', 'wp-element', 'wp-components' ] );
	wp_localize_script( 'dn-remp-paywall-js', 'dn_remp_paywall_access', $options );
	wp_enqueue_style( 'dn-remp-paywall-css', plugins_url( 'dn-remp-paywall.css', __FILE__ ), false );
}


/**
 * Get access tags from transient or load them and save to transient
 *
 * @since 1.0.0
 */

function remp_paywall_get_types() {
	$types = get_transient( 'dn_remp_paywall_types' );

	if ( $types === false ) {
		$headers = [
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . DN_REMP_CRM_TOKEN
		];

		$response = wp_remote_get( DN_REMP_CRM_HOST . '/api/v1/content-access/list', [ 'headers' => $headers ] );

		if ( is_wp_error( $response ) ) {
			error_log( 'REMP get_user_subscriptions: ' . $response->get_error_message() );

			return []; // fail silently
		}

		$types = json_decode( $response['body'], true );

		set_transient( 'dn_remp_paywall_types', $types, 60*60 );
	}

	return $types;
}


/**
 * Add access controls for article
 *
 * @since 1.0.0
 */

function remp_paywall_post_submitbox_misc_actions() {
	global $post;

	$current = get_post_meta( $post->ID, '_dn_remp_paywall_access', true );
	$types = remp_paywall_get_types();
	$html = sprintf( '<option value="">%s</option>', __( 'Odomknutý', 'dn-remp-paywall' ) );

	foreach ( $types as $type ) {
		$html .= sprintf( '<option value="%s"%s>%s</option>',
			$type['code'],
			$type['code'] == $current ?  ' selected': '',
			$type['description']
		);
	}

	printf( '<div class="misc-pub-section"><label class="selectit">%1$s<select id="%2$s" style="display:block;width:100%%;margin-top:4px;" name="%2$s">%3$s</select></label></div>',
		__( 'Prístup k článku', 'dn-remp-paywall' ),
		'dn_remp_paywall_access',
		$html
	);
}


/**
 * Save access controls for article
 *
 * @since 1.0.0
 */

function remp_paywall_save_post( $post_id, $post, $update ) {
	if ( !current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$key = '_dn_remp_paywall_access';

	if ( isset( $_POST[ $key ] ) ) {
		update_post_meta( $post_id, $key, $_POST[ $key ] );
	} else {
		delete_post_meta( $post_id, $key );
	}
}


/**
 * Strips the post content according to current subscription
 *
 * @since 1.0.0
 *
 * @param string $content Post content
 *
 * @return string Returns stripped or full post content according to current subscription
 */

function remp_paywall_the_content( $content ) {
	global $post;

	if ( !function_exists( 'remp_get_user' ) ) { // fail silently, return full post_content
		return $content;
	}

	$position = mb_strpos( $content, '<div id="remp_lock_anchor"' );

	if ( $position === false ) {
		$position = mb_strpos( $content, '[lock]' );
	}

	/**
	 * Filters the REMP access tag needed.
	 *
	 * @since 3.1.0
	 *
	 * @param string $type REMP access tag needed for this post.
	 * @param string $post Post object.
	 */

	$type = get_post_meta( $post->ID, '_dn_remp_paywall_access', true );
	$type = apply_filters( 'dn_remp_paywall_access', $type, $post );

	if ( $position !== false && !empty( $type ) ) {
		$now = new DateTime();
		$types = [];
		$subscriptions = remp_get_user( 'subscriptions' );

		if ( is_array( $subscriptions ) ) {
			$subscriptions = $subscriptions['subscriptions'];

			foreach ( $subscriptions as $subscription ) {
				$start = new DateTime( $subscription['start_at'] );
				$end = new DateTime( $subscription['end_at'] );

				if ( ( $start < $now && $now < $end ) ) {
					$types = array_merge( $types, $subscription['access'] );
				}
			}
		}

		if ( !in_array( $type, $types ) ) {
			$content = force_balance_tags( mb_substr( $content, 0, $position ) );

			/**
			 * Filters the article content.
			 *
			 * @since 3.1.0
			 *
			 * @param string $content Post content after stripping of the the paywall part.
			 * @param string $types REMP user access tags from all active subscriptions.
			 * @param string $type REMP access tag needed for this post.
			 */
			
			$content = apply_filters( 'remp_content_locked', $content, $types, $type );
		}
	}

	return $content;
}


/**
 * Lock shortcode anchor
 *
 * @since 1.0.0
 */

function remp_lock_shortcode() {
	return sprintf('<span id="remp_lock_anchor"></span>' );
}


/**
 * Localisations loaded
 *
 * @since 1.0.0
 */

function remp_paywall_init() {
	load_plugin_textdomain( 'dn-remp-paywall' );
}


/**
 * Dependencies check
 *
 * @since 1.0.0
 */

function remp_paywall_activate() {
	if ( !function_exists( 'is_plugin_active_for_network' ) ) {
		include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	if ( current_user_can( 'activate_plugins' ) && ( !function_exists( 'remp_get_user' ) || !defined( 'DN_REMP_CRM_HOST' ) || !defined( 'DN_REMP_CRM_TOKEN' ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		die( __( 'This plugin requires <strong>DN REMP CRM Auth</strong> plugin to be active, and <code>DN_REMP_CRM_HOST</code> and <code>DN_REMP_CRM_TOKEN</code> defined in your <code>wp-config.php</code> .', 'dn-remp-paywall' ) );
	}
}

