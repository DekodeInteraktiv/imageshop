<?php
/**
 * The Progress class.
 */

declare(strict_types=1);

namespace Imageshop\WordPress\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Progress of long-running tasks, in a non-autoloaded transient updated once per batch.
 */
class Progress {

	/**
	 * Start tracking a task.
	 *
	 * @param string $task   Task name.
	 * @param int    $total  Number of items to process.
	 * @param string $source Where the task was started, `cli` or `web`.
	 */
	public static function start( string $task, int $total, string $source ): void {
		self::save(
			$task,
			array(
				'status'    => 'running',
				'source'    => $source,
				'total'     => $total,
				'processed' => 0,
				'counts'    => array(),
				'started'   => \time(),
			)
		);
	}

	/**
	 * Add a processed batch to a task.
	 *
	 * @param string $task      Task name.
	 * @param int    $processed Number of items processed in the batch.
	 * @param array  $counts    Outcome counts for the batch, keyed by outcome.
	 */
	public static function advance( string $task, int $processed, array $counts = array() ): void {
		$progress = self::get( $task );

		if ( null === $progress ) {
			return;
		}

		$progress['processed'] = \min( $progress['total'], $progress['processed'] + $processed );

		foreach ( $counts as $outcome => $count ) {
			$progress['counts'][ $outcome ] = ( $progress['counts'][ $outcome ] ?? 0 ) + $count;
		}

		if ( 'running' === $progress['status'] && $progress['processed'] >= $progress['total'] ) {
			$progress['status'] = 'done';
		}

		self::save( $task, $progress );
	}

	/**
	 * Mark a running task as done, even if fewer items than expected were processed.
	 *
	 * @param string $task Task name.
	 */
	public static function finish( string $task ): void {
		self::set_status( $task, 'done' );
	}

	/**
	 * Mark a running task as stopped. WP-CLI commands check for this between batches.
	 *
	 * @param string $task Task name.
	 */
	public static function stop( string $task ): void {
		self::set_status( $task, 'stopped' );
	}

	/**
	 * Check if a task has been stopped.
	 *
	 * @param string $task Task name.
	 *
	 * @return bool
	 */
	public static function is_stopped( string $task ): bool {
		$progress = self::get( $task );

		return null !== $progress && 'stopped' === $progress['status'];
	}

	/**
	 * Get the progress of a task.
	 *
	 * @param string $task Task name.
	 *
	 * @return array|null
	 */
	public static function get( string $task ): ?array {
		// Skip the in-process cache, so long-running WP-CLI commands see a stop request.
		if ( ! \wp_using_ext_object_cache() ) {
			\wp_cache_delete( '_transient_imageshop_progress_' . $task, 'options' );
			\wp_cache_delete( '_transient_timeout_imageshop_progress_' . $task, 'options' );
		}

		$progress = \get_transient( 'imageshop_progress_' . $task );

		return \is_array( $progress ) ? $progress : null;
	}

	/**
	 * Change the status of a running task.
	 *
	 * @param string $task   Task name.
	 * @param string $status New status.
	 */
	private static function set_status( string $task, string $status ): void {
		$progress = self::get( $task );

		if ( null !== $progress && 'running' === $progress['status'] ) {
			$progress['status'] = $status;
			self::save( $task, $progress );
		}
	}

	/**
	 * Store the progress of a task.
	 *
	 * @param string $task     Task name.
	 * @param array  $progress Progress details.
	 */
	private static function save( string $task, array $progress ): void {
		$progress['updated'] = \time();

		\set_transient( 'imageshop_progress_' . $task, $progress, \DAY_IN_SECONDS );
	}
}
