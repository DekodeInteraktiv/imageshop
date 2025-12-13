<?php
/**
 * Plugin uninstall handler.
 *
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- We use direct database queries for performance during the uninstall procedure.
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- No do not want ot cache these queries, as they are removing database entries and will never be re-used.
 */

declare( strict_types = 1 );

namespace Imageshop\WordPress\Uninstall;

if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;

// Remove dangling database entries after the plugin has been removed.
$attachments = $wpdb->get_results(
	"
			SELECT
		       DISTINCT( p.ID )
			FROM
				{$wpdb->posts} AS p
		    LEFT JOIN
			    {$wpdb->postmeta} AS pm
			        ON (p.ID = pm.post_id)
			WHERE
				(
					p.post_type = 'attachment'
					OR
					p.post_type = 'attachment-imageshop'
				)
			AND
			(
				pm.meta_key = '_imageshop_document_id'
				AND
				(
				    pm.meta_value IS NOT NULL
				        OR
				    pm.meta_value != ''
			    )
			)
			AND
		        NOT EXISTS (
		            SELECT
						1
					FROM
					     {$wpdb->postmeta} as spm
		            WHERE
						spm.post_id = p.ID
					AND
				        spm.meta_key = '_wp_attached_file'
				    AND
					(
					    spm.meta_value IS NOT NULL
					        AND
					    spm.meta_value != ''
					)
				)
		    "
);

if ( empty( $attachments ) ) {
	return;
}

$removable = array();

foreach ( $attachments as $attachment ) {
	$removable[] = \absint( $attachment->ID );
}

$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN (" . \implode( ',', $removable ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to implode a variable to use the `IN` SQL operator.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . \implode( ',', $removable ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to implode a variable to use the `IN` SQL operator.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%imageshop%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We are doing a non-variable query.

// Flush the caches, necessary to prevent errant database values being kept in memory after removing the plugin.
\wp_cache_flush();
