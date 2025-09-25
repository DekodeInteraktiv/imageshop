<?php

namespace Imageshop\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Upgrade {

	public function __construct() {
		\add_action( 'init', array( $this, 'maybe_perform_upgrades' ) );
	}

	public function get_current_version() {
		return \get_plugin_data( IMAGESHOP_PLUGIN_BASE_NAME )['Version'] ?? '';
	}

	public function update_current_version() {
		\update_option( 'imageshop_version', $this->get_current_version() );
	}

	public function get_stored_version() {
		return \get_option( 'imageshop_version', '1.0.0' );
	}

	public function maybe_perform_upgrades() {
		if ( version_compare( $this->get_stored_version(), $this->get_current_version(), '>=' ) ) {
			return;
		}

		$methods = get_class_methods( $this );
		foreach ( $methods as $method ) {
			/*
			 * The Upgrade class has a fixed naming scheme for upgrade methods.
			 *
			 * They are always named `upgrade_X_Y_Z` where `X_Y_Z` is the version number
			 * of the upcoming release, with the periods replaced with underscores,
			 * that should trigger the upgrade routine.
			 */
			if ( preg_match( '/^upgrade_(\d+_\d+_\d+)$/', $method, $matches ) ) {
				$upgrade_version = str_replace( '_', '.', $matches[1] );
				if (
					version_compare( $upgrade_version, $this->get_stored_version(), '>' ) &&
					version_compare( $upgrade_version, $this->get_current_version(), '<=' )
				) {
					$this->$method();
				}
			}
		}

		$this->update_current_version();
	}

	public function upgrade_1_3_1() {
		global $wpdb;

		// Remove old and potentially invalid meta keys.
		$wpdb->query( "DELETE FROM `" . $wpdb->postmeta . "` WHERE `meta_key` IN ( '_imageshop_permalinks', '_imageshop_media_sizes' )" );

		// Flush cache values to ensure no stale data is hanging around.
		\wp_cache_flush();
	}

	public function upgrade_1_3_2() {
		// We need to perform the same metadata flush as in 1.3.1 to resolve the full size image URLs issue.
		$this->upgrade_1_3_1();
	}
}
