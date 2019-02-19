<?php
/**
 * Plugin Name: WP REST API filter parameter
 * Description: This plugin adds a "filter" query parameter to API post collections to filter returned results based on public WP_Query parameters, adding back the "filter" parameter that was removed from the API when it was merged into WordPress core.
 * Author: Takayuki Miyauchi
 * Author URI: https://github.com/miya0001/_restfilter
 * Version: nightly
 * License: GPL2+
 **/

namespace _restfilter;
use Miya\WP\GH_Auto_Updater;

 // Autoload
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

add_action( 'init', '_restfilter\activate_autoupdate' );

function activate_autoupdate() {
	$plugin_slug = plugin_basename( __FILE__ ); // e.g. `hello/hello.php`.
	$gh_user = 'miya0001';                      // The user name of GitHub.
	$gh_repo = 'gh-auto-updater-example';       // The repository name of your plugin.

	// Activate automatic update.
	new GH_Auto_Updater( $plugin_slug, $gh_user, $gh_repo );
}

add_action( 'rest_api_init', '_restfilter\rest_api_filter_add_filters' );

 /**
  * Add the necessary filter to each post type
  **/
function rest_api_filter_add_filters() {
	foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
		add_filter( 'rest_' . $post_type->name . '_query', '_restfilter\rest_api_filter_add_filter_param', 10, 2 );
	}
}

/**
 * Add the filter parameter
 *
 * @param  array           $args    The query arguments.
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function rest_api_filter_add_filter_param( $args, $request ) {
	// Bail out if no filter parameter is set.
	if ( empty( $request['filter'] ) || ! is_array( $request['filter'] ) ) {
		return $args;
	}

	$filter = $request['filter'];

	if ( isset( $filter['posts_per_page'] ) && ( (int) $filter['posts_per_page'] >= 1 && (int) $filter['posts_per_page'] <= 100 ) ) {
		$args['posts_per_page'] = $filter['posts_per_page'];
	}

	global $wp;
	$vars = apply_filters( 'rest_query_vars', $wp->public_query_vars );

	function allow_meta_query( $valid_vars )
	{
	    $valid_vars = array_merge( $valid_vars, array( 'meta_query', 'meta_key', 'meta_value', 'meta_compare' ) );
	    return $valid_vars;
	}
	$vars = allow_meta_query( $vars );

	foreach ( $vars as $var ) {
		if ( isset( $filter[ $var ] ) ) {
			$args[ $var ] = $filter[ $var ];
		}
	}
	return $args;
}
