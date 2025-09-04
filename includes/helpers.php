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
