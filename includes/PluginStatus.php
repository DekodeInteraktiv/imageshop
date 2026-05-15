<?php


declare(strict_types=1);

namespace Imageshop\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class PluginStatus {

	public static function register_activation_hook() {
		global $wpdb;

		if ( \is_admin() && ( ! \defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			if ( \version_compare( PHP_VERSION, '7.0', '<' ) ) {
				imageshop_incompatibile(
					\sprintf(
						'The Imageshop Media Library plugin requires PHP version 7.0 or higher. This site uses PHP version %s, which has caused the plugin to be automatically deactivated.',
						PHP_VERSION
					)
				);
			}
		}

		$wpdb->query(
			'UPDATE
				`' . $wpdb->posts . '` AS p
			SET
				p.post_type = "attachment"
			WHERE
				p.post_type = "attachment-imageshop"
			AND (
			    EXISTS (
			        SELECT 1
			        FROM `' . $wpdb->postmeta . '` AS pm
			        WHERE pm.post_id = p.ID
			        AND pm.meta_key IN ("_imageshop_permalinks", "_imageshop_media_sizes")
			    )
			    OR NOT EXISTS (
			        SELECT 1
			        FROM `' . $wpdb->postmeta . '` AS pm2
			        WHERE pm2.post_id = p.ID
			        AND pm2.meta_key = "_wp_attached_file"
			        AND pm2.meta_value IS NOT NULL
			        AND pm2.meta_value != ""
			    )
			)'
		);
	}

	public static function register_deactivation_hook() {
		global $wpdb;

		$wpdb->query(
			'UPDATE
				`' . $wpdb->posts . '` AS p
			SET
				p.post_type = "attachment-imageshop"
			WHERE
				p.post_type = "attachment"
			AND (
			    EXISTS (
			        SELECT 1
			        FROM `' . $wpdb->postmeta . '` AS pm
			        WHERE pm.post_id = p.ID
			        AND pm.meta_key IN ("_imageshop_permalinks", "_imageshop_media_sizes")
			    )
			    OR NOT EXISTS (
			        SELECT 1
			        FROM `' . $wpdb->postmeta . '` AS pm2
			        WHERE pm2.post_id = p.ID
			        AND pm2.meta_key = "_wp_attached_file"
			        AND pm2.meta_value IS NOT NULL
			        AND pm2.meta_value != ""
			    )
			)'
		);
	}
}
