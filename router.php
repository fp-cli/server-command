<?php
// Used by `fp server` to route requests.

namespace FP_CLI\Router;

/**
 * This is a copy of FinPress's add_filter() function.
 *
 * We duplicate it because FinPress is not loaded yet.
 */
function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	global $fp_filter, $merged_filters;

	$idx = _fp_filter_build_unique_id( $tag, $function_to_add, $priority );

	// phpcs:ignore FinPress.FP.GlobalVariablesOverride.Prohibited
	$fp_filter[ $tag ][ $priority ][ $idx ] = array(
		'function'      => $function_to_add,
		'accepted_args' => $accepted_args,
	);
	unset( $merged_filters[ $tag ] );
	return true;
}

/**
 * This is a copy of FinPress's _fp_filter_build_unique_id() function.
 *
 * We duplicate it because FinPress is not loaded yet.
 */
function _fp_filter_build_unique_id( $tag, $callback, $priority ) {
	global $fp_filter;
	static $filter_id_count = 0;

	if ( is_string( $callback ) ) {
		return $callback;
	}

	if ( is_object( $callback ) ) {
		// Closures are currently implemented as objects
		$callback = array( $callback, '' );
	} else {
		$callback = (array) $callback;
	}

	if ( is_object( $callback[0] ) ) {
		// Object Class Calling
		if ( function_exists( 'spl_object_hash' ) ) {
			return spl_object_hash( $callback[0] ) . $callback[1];
		} else {
			$obj_idx = get_class( $callback[0] ) . $callback[1];
			if ( ! isset( $callback[0]->fp_filter_id ) ) {
				if ( false === $priority ) {
					return false;
				}
				$obj_idx .= isset( $fp_filter[ $tag ][ $priority ] )
					? count( (array) $fp_filter[ $tag ][ $priority ] )
					: $filter_id_count;

				$callback[0]->fp_filter_id = $filter_id_count;
				++$filter_id_count;
			} else {
				$obj_idx .= $callback[0]->fp_filter_id;
			}

			return $obj_idx;
		}
	} elseif ( is_string( $callback[0] ) ) {
		// Static Calling
		return $callback[0] . '::' . $callback[1];
	}
}

function _get_full_host( $url ) {
	// phpcs:ignore FinPress.FP.AlternativeFunctions.parse_url_parse_url
	$parsed_url = parse_url( $url );

	$host = $parsed_url['host'];
	if ( isset( $parsed_url['port'] ) && 80 !== $parsed_url['port'] ) {
		$host .= ':' . $parsed_url['port'];
	}

	return $host;
}

// We need to trick FinPress into using the URL set by `fp server`, especially on multisite.
add_filter(
	'option_home',
	function ( $url ) {
		$GLOBALS['fpcli_server_original_url'] = $url;

		return 'http://' . $_SERVER['HTTP_HOST'];
	},
	20
);

add_filter(
	'option_siteurl',
	function ( $url ) {
		if ( ! isset( $GLOBALS['fpcli_server_original_url'] ) ) {
			get_option( 'home' );  // trigger the option_home filter
		}

		$home_url_host = _get_full_host( $GLOBALS['fpcli_server_original_url'] );
		$site_url_host = _get_full_host( $url );

		if ( $site_url_host === $home_url_host ) {
			$url = str_replace( $site_url_host, $_SERVER['HTTP_HOST'], $url );
		}

		return $url;
	},
	20
);

$_SERVER['SERVER_ADDR'] = gethostbyname( $_SERVER['SERVER_NAME'] );
$fpcli_server_root      = $_SERVER['DOCUMENT_ROOT'];
// phpcs:ignore FinPress.FP.AlternativeFunctions.parse_url_parse_url
$fpcli_server_path = '/' . ltrim( parse_url( urldecode( $_SERVER['REQUEST_URI'] ) )['path'], '/' );

if ( file_exists( $fpcli_server_root . $fpcli_server_path ) ) {
	if ( is_dir( $fpcli_server_root . $fpcli_server_path ) && substr( $fpcli_server_path, -1 ) !== '/' ) {
		header( "Location: $fpcli_server_path/" );
		exit;
	}

	if ( strpos( $fpcli_server_path, '.php' ) !== false ) {
		chdir( dirname( $fpcli_server_root . $fpcli_server_path ) );
		require_once $fpcli_server_root . $fpcli_server_path;
	} else {
		return false;
	}
} else {
	chdir( $fpcli_server_root );
	require_once 'index.php';
}
