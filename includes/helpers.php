<?php
// Helper functions aimed at improving developer experiences.

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'dd' ) ) {
	function dd() {
		if ( ! function_exists( 'dump' ) ) {
			echo '<pre>';
			print_r( func_get_args() );
			echo '</pre>';
		} else {
			call_user_func_array( 'dump', func_get_args() );
		}

		die();
	}
}

function imageshop_incompatibile( $msg ) {
	require_once ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
	\deactivate_plugins( __FILE__ );
	\wp_die( \esc_html( $msg ) );
}
