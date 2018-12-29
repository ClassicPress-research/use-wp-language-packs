<?php
/*
 * Plugin Name:       Use WordPress Language Packs
 * Plugin URI:        https://github.com/ClassicPress-research/use-wp-language-packs
 * Description:       Use WordPress Language Packs for the locales not supported yet by ClassicPress.
 * Version:           0.1.0
 * Tested up to:      1.0.2
 * Author:            takanakui
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * Prevent direct access to plugin files.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Hook that will check if necessary to add more language packs (only in the General options page). */
add_action( 'load-options-general.php', 'add_extra_wordpress_language_packs' );

/**
 * Add the WordPress langugage packs for the locales not yet officialy supported by ClassicPress.
 *
 * @param object|WP_Error $res  Response object or WP_Error.
 * @param string          $type The type of translations being requested.
 * @param object          $args Translation API arguments.
 */
function add_wordpress_language_packs( $res, $type, $args ) {
	global $wp_version;

	$stats = array(
		'locale'  => get_locale(),
		'version' => $wp_version,
	);
	$options = array(
		'timeout' => 3,
		'method'  => 'POST',
	);

	$url                 = 'https://api.wordpress.org/translations/core/1.0/';
	$stats['wp_version'] = $wp_version;
	$options['body']     = $stats;
	$request             = wp_remote_request( $url, $options );
	$res_api             = json_decode( wp_remote_retrieve_body( $request ), true );

	if ( ! is_object( $res_api ) && ! is_array( $res_api ) ) {
		$res_api = new WP_Error( 'translations_api_failed',
			sprintf(
				/* translators: %s: support forums URL */
				__( 'An unexpected error occurred. Something may be wrong with the WordPress translation API or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">GitHub Issues</a>.' ),
				__( 'https://github.com/ClassicPress-research/use-wp-language-packs/issues' )
			),
			wp_remote_retrieve_body( $request )
		);
	} else {

		foreach ( $res_api['translations'] as $translation ) {

			// Leave the official ClassicPress Language packs untouched.
			if ( false === array_search( $translation['language'], array_column( $res['translations'], 'language' ), true ) ) {
				array_push( $res['translations'], $translation );
			}
		}
	}
	return $res;
}

/**
 * Add the WordPress langugage packs for the locales not yet officialy supported by ClassicPress.
 */
function add_extra_wordpress_language_packs() {

	require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

	// Update the translations language packs if less than 100 locales available (maybe it can be improved).
	if ( 100 > count( wp_get_available_translations() ) ) {
		delete_site_transient( 'available_translations' );
		add_filter( 'translations_api_result', 'add_wordpress_language_packs', 1, 3 );
	}
}
