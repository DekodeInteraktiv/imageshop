<?php
/**
 * Plugin Name: Imageshop DAM Connector
 * Plugin URI:
 * Description: Use the Imageshop media library as your companys one source for all media.
 * Version: 1.6.0
 * Author: Imageshop
 * Author URI: https://imageshop.org
 * License: GPLv2
 * Text Domain: imageshop-dam-connector
 * Domain Path: /languages
 * Requires PHP: 7.2
 * Requires at least: 6.6
 */

declare( strict_types = 1 );

namespace Imageshop\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

\define( 'IMAGESHOP_ABSPATH', __DIR__ );
\define( 'IMAGESHOP_PLUGIN_BASE_NAME', __FILE__ );

/*
 * Autoloader to ensure our namespace can autoload files seamlessly.
 */
\spl_autoload_register(
	function ( $invoked_class ) {
		// Project-specific namespace
		$prefix   = __NAMESPACE__;
		$base_dir = __DIR__ . '/includes/';

		// Do not try to autoload anything outside our namespace.
		if ( substr( $invoked_class, 0, \strlen( $prefix ) ) !== $prefix ) {
			return;
		}

		// Remove the namespace prefix from the classname to get the relative path.
		$relative_class = \substr( $invoked_class, \strlen( $prefix ) );

		// Replace namespace separators with directory separators in the relative class name.
		$relative_class = \str_replace( '\\', '/', $relative_class );

		$file = $relative_class . '.php';

		// Validate the file using WordPress' `validate_file` function.
		if ( \validate_file( $file ) > 0 ) {
			return;
		}

		if ( \file_exists( $base_dir . $file ) ) {
			require $base_dir . $file;
		}
	}
);
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/includes/helpers.php';

// Validate that the plugin is compatible when being activated.
\register_activation_hook(
	__FILE__,
	[ 'Imageshop\WordPress\PluginStatus', 'register_activation_hook' ]
);

// When deactivating the plugin, hide any media that is only tied to Imageshop to give the end users a better experience if they are just testing something.
\register_deactivation_hook(
	__FILE__,
	[ 'Imageshop\WordPress\PluginStatus', 'register_deactivation_hook' ]
);

if ( \class_exists( 'WP_CLI' ) ) {
	require_once __DIR__ . '/includes/CLI/class-meta.php';
	require_once __DIR__ . '/includes/CLI/class-media.php';
	require_once __DIR__ . '/includes/CLI/class-duplicates.php';
}

new Imageshop();
