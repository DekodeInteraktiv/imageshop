<?php
/**
 * WP-CLI commands for Imageshop permalinks.
 */

declare(strict_types=1);

namespace Imageshop\WordPress\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use Imageshop\WordPress\Media\Permalinks as Permalink_Registry;
use Imageshop\WordPress\Sync\Progress;

/**
 * Check and repair the Imageshop permalinks used by attachments.
 */
class Permalinks {

	/**
	 * Show how many attachments are in each permalink state.
	 *
	 * States:
	 *  - confirmed:  Registered, and confirmed by Imageshop.
	 *  - legacy:     Saved by an earlier version without confirmation. Not checked yet.
	 *  - pending:    Not registered yet, or waiting for a retry. No URL is used.
	 *  - unverified: Saved by an earlier version, and could not be checked yet. Still used.
	 *  - failed:     Gave up on automatic attempts. Use `repair` to try again.
	 *
	 * ## EXAMPLES
	 *
	 *      $ wp imageshop permalinks status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status( $args, $assoc_args ) {
		$registry = Permalink_Registry::get_instance();
		$rows     = array();

		foreach ( $registry->get_status_counts() as $state => $total ) {
			$rows[] = array(
				'status'      => $state,
				'attachments' => $total,
			);
		}

		\WP_CLI\Utils\format_items( 'table', $rows, array( 'status', 'attachments' ) );

		$next = \wp_next_scheduled( Permalink_Registry::CRON_HOOK );
		\WP_CLI::log( \sprintf( 'Legacy sweep: %s', \get_option( Permalink_Registry::SWEEP_OPTION, 'not started' ) ) );
		\WP_CLI::log( \sprintf( 'Next background run: %s', $next ? \gmdate( 'Y-m-d H:i:s', $next ) . ' UTC' : 'not scheduled' ) );
	}

	/**
	 * Check and repair permalinks.
	 *
	 * Registers permalinks again, which is safe for working ones, and keeps their URL.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more attachment IDs.
	 *
	 * [--all]
	 * : Process every attachment that is not confirmed.
	 *
	 * [--recheck]
	 * : Also check permalinks that Imageshop has already confirmed.
	 *
	 * [--dry-run]
	 * : List the attachments that would be processed, without contacting Imageshop.
	 *
	 * ## EXAMPLES
	 *
	 *      # Repair a single attachment.
	 *      $ wp imageshop permalinks repair 35818
	 *
	 *      # Check and repair everything that is not confirmed.
	 *      $ wp imageshop permalinks repair --all
	 *
	 *      # Check every permalink again, including confirmed ones.
	 *      $ wp imageshop permalinks repair --all --recheck
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function repair( $args, $assoc_args ) {
		$registry = Permalink_Registry::get_instance();
		$ids      = \array_map( 'absint', $args );
		$recheck  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'recheck', false );

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false ) ) {
			$ids = $this->get_unconfirmed_attachment_ids( $recheck );
		}

		if ( empty( $ids ) ) {
			\WP_CLI::error( 'Provide one or more attachment IDs, or use --all.' );
		}

		$legacy  = array();
		$pending = array();

		foreach ( $ids as $id ) {
			if ( ! \get_post_meta( $id, '_imageshop_document_id', true ) ) {
				\WP_CLI::warning( \sprintf( 'Attachment %d is not Imageshop media, skipping.', $id ) );
				continue;
			}

			$state = $registry->get_status( $id );

			if ( Permalink_Registry::STATUS_CONFIRMED === $state && ! $recheck ) {
				\WP_CLI::log( \sprintf( 'Attachment %d is already confirmed.', $id ) );
				continue;
			}

			if ( \get_post_meta( $id, Permalink_Registry::META_URL, true ) ) {
				$legacy[] = $id;
			} else {
				$pending[] = $id;
			}
		}

		\WP_CLI::log( \sprintf( 'Stored permalinks to verify: %d. Permalinks to register: %d.', \count( $legacy ), \count( $pending ) ) );

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ) ) {
			return;
		}

		$summary = array(
			'working'    => 0,
			'repaired'   => 0,
			'registered' => 0,
			'retrying'   => 0,
			'failed'     => 0,
		);

		Progress::start( 'permalinks', \count( $legacy ) + \count( $pending ), 'cli' );

		// Process groups of 20, reporting progress after each.
		foreach ( \array_chunk( $legacy, Permalink_Registry::PER_RUN_LIMIT ) as $chunk ) {
			$counts = \array_fill_keys( \array_keys( $summary ), 0 );

			foreach ( $registry->verify_legacy( $chunk ) as $id => $result ) {
				if ( \is_wp_error( $result ) ) {
					++$counts[ Permalink_Registry::STATUS_FAILED === $registry->get_status( $id ) ? 'failed' : 'retrying' ];
					\WP_CLI::warning( \sprintf( 'Attachment %d: %s', $id, $result->get_error_message() ) );
				} elseif ( 'registered' === $result ) {
					++$counts['repaired'];
					\WP_CLI::log( \sprintf( 'Attachment %d: was broken, and has been repaired.', $id ) );
				} else {
					++$counts['working'];
				}
			}

			foreach ( $counts as $outcome => $count ) {
				$summary[ $outcome ] += $count;
			}
			Progress::advance( 'permalinks', \count( $chunk ), $counts );

			if ( Progress::is_stopped( 'permalinks' ) ) {
				\WP_CLI::warning( 'The check was stopped from the settings page.' );
				break;
			}
		}

		foreach ( Progress::is_stopped( 'permalinks' ) ? array() : \array_chunk( $pending, Permalink_Registry::PER_RUN_LIMIT ) as $chunk ) {
			$counts = \array_fill_keys( \array_keys( $summary ), 0 );

			foreach ( $registry->ensure( $chunk, true ) as $id => $result ) {
				if ( \is_wp_error( $result ) ) {
					++$counts[ Permalink_Registry::STATUS_FAILED === $registry->get_status( $id ) ? 'failed' : 'retrying' ];
					\WP_CLI::warning( \sprintf( 'Attachment %d: %s', $id, $result->get_error_message() ) );
				} else {
					++$counts['registered'];
				}
			}

			foreach ( $counts as $outcome => $count ) {
				$summary[ $outcome ] += $count;
			}
			Progress::advance( 'permalinks', \count( $chunk ), $counts );

			if ( Progress::is_stopped( 'permalinks' ) ) {
				\WP_CLI::warning( 'The check was stopped from the settings page.' );
				break;
			}
		}

		foreach ( $summary as $label => $total ) {
			\WP_CLI::log( \sprintf( '%s: %d', \ucfirst( $label ), $total ) );
		}

		if ( $summary['failed'] > 0 ) {
			\WP_CLI::warning( 'Some permalinks failed; they will be retried in the background, or can be retried with this command.' );
		} else {
			\WP_CLI::success( 'All processed permalinks are confirmed.' );
		}
	}

	/**
	 * Run the background permalink queue now.
	 *
	 * ## EXAMPLES
	 *
	 *      $ wp imageshop permalinks process
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function process( $args, $assoc_args ) {
		$summary = Permalink_Registry::get_instance()->process_queue();

		\WP_CLI::success( \sprintf( 'Retried %d, verified %d (repaired %d), failed %d.', $summary['retried'], $summary['verified'], $summary['repaired'], $summary['failed'] ) );
	}

	/**
	 * Get every Imageshop attachment with a permalink that is not confirmed.
	 *
	 * @param bool $include_confirmed Include confirmed permalinks as well.
	 *
	 * @return int[]
	 */
	private function get_unconfirmed_attachment_ids( bool $include_confirmed = false ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off CLI query.
		return \array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT d.post_id
					FROM {$wpdb->postmeta} d
					LEFT JOIN {$wpdb->postmeta} u ON u.post_id = d.post_id AND u.meta_key = %s
					LEFT JOIN {$wpdb->postmeta} s ON s.post_id = d.post_id AND s.meta_key = %s
					WHERE d.meta_key = '_imageshop_document_id'
					AND ( u.meta_id IS NOT NULL OR s.meta_id IS NOT NULL )
					AND ( %d = 1 OR s.meta_value IS NULL OR s.meta_value != %s )
					ORDER BY d.post_id DESC",
					Permalink_Registry::META_URL,
					Permalink_Registry::META_STATUS,
					(int) $include_confirmed,
					Permalink_Registry::STATUS_CONFIRMED
				)
			)
		);
	}
}

\WP_CLI::add_command( 'imageshop permalinks', __NAMESPACE__ . '\\Permalinks' );
