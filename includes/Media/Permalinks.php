<?php
/**
 * Permalink registration and confirmation for Imageshop media.
 */

declare(strict_types=1);

namespace Imageshop\WordPress\Media;

use Imageshop\WordPress\API\REST_Controller;
use Imageshop\WordPress\Sync\Progress;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Only uses Imageshop permalinks once confirmed, as unconfirmed URLs give a 404.
 *
 * - Media picked in the media modal is registered on Select/Insert.
 * - Other media is queued, and registered by WP-Cron with retries.
 * - Permalinks from earlier versions are checked from the settings page, or with WP-CLI.
 */
class Permalinks {

	public const CRON_HOOK = 'imageshop_process_permalinks';

	public const META_URL          = '_imageshop_original_permalink';
	public const META_STATUS       = '_imageshop_permalink_status';
	public const META_REQUEST      = '_imageshop_permalink_request';
	public const META_ATTEMPTS     = '_imageshop_permalink_attempts';
	public const META_NEXT_ATTEMPT = '_imageshop_permalink_next_attempt';
	public const META_ERROR        = '_imageshop_permalink_error';

	/**
	 * Option holding the state of the check of permalinks saved by earlier versions.
	 */
	public const SWEEP_OPTION = 'imageshop_permalink_legacy_sweep';

	/**
	 * Registered, and confirmed by Imageshop.
	 */
	public const STATUS_CONFIRMED = 'confirmed';

	/**
	 * Not registered yet, or the registration failed. No URL is used until it is confirmed.
	 */
	public const STATUS_PENDING = 'pending';

	/**
	 * Saved by an earlier version, and could not be checked yet. It is still used.
	 */
	public const STATUS_UNVERIFIED = 'unverified';

	/**
	 * Gave up after too many attempts, or Imageshop rejected the permalink.
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Number of automatic attempts before giving up on a permalink.
	 */
	public const MAX_AUTOMATIC_ATTEMPTS = 8;

	/**
	 * Maximum number of permalinks processed in a single background run.
	 */
	public const PER_RUN_LIMIT = 20;

	/**
	 * Seconds to wait before the next attempt, by number of failed attempts.
	 */
	private const BACKOFF = array( 60, 300, 900, 3600, 21600 );

	/**
	 * @var Permalinks|null
	 */
	private static $instance;

	/**
	 * Get the class instance.
	 *
	 * @return Permalinks
	 */
	public static function get_instance(): Permalinks {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		\add_action( self::CRON_HOOK, array( $this, 'process_queue' ) );
		\add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the REST routes used by the media modal.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		\register_rest_route(
			'imageshop/v1',
			'/attachments/prepare',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_prepare_attachments' ),
				'permission_callback' => function () {
					return \current_user_can( 'upload_files' );
				},
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array(
							'type' => 'integer',
						),
						'maxItems'          => 50,
						'sanitize_callback' => function ( $ids ) {
							return \array_values( \array_unique( \array_filter( \array_map( 'absint', (array) $ids ) ) ) );
						},
					),
				),
			)
		);

		\register_rest_route(
			'imageshop/v1',
			'/attachments/check',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_check_attachment' ),
				'permission_callback' => function ( \WP_REST_Request $request ) {
					return \current_user_can( 'edit_post', $request->get_param( 'id' ) );
				},
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Check the permalink of a single attachment, and repair or register it if needed.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return array|\WP_Error
	 */
	public function rest_check_attachment( \WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		if ( 'attachment' !== \get_post_type( $id ) || ! \get_post_meta( $id, '_imageshop_document_id', true ) ) {
			return new \WP_Error( 'imageshop_check_link', \__( 'This is not an Imageshop image.', 'imageshop-dam-connector' ), array( 'status' => 400 ) );
		}

		$result = \get_post_meta( $id, self::META_URL, true ) ? $this->verify_legacy( array( $id ) ) : $this->ensure( array( $id ), true );
		$result = $result[ $id ] ?? new \WP_Error( 'imageshop_check_link', \__( 'The image link could not be checked.', 'imageshop-dam-connector' ) );

		if ( \is_wp_error( $result ) ) {
			return new \WP_Error(
				'imageshop_check_link',
				/* translators: %s: The reason the image link could not be repaired. */
				\sprintf( \__( 'The image link could not be repaired: %s', 'imageshop-dam-connector' ), $result->get_error_message() ),
				array( 'status' => 502 )
			);
		}

		return array(
			'success' => true,
			'message' => 'registered' === $result
				? \__( 'The image link did not work, and has been repaired.', 'imageshop-dam-connector' )
				: \__( 'The image link works.', 'imageshop-dam-connector' ),
		);
	}

	/**
	 * Register permalinks for media picked in the media modal, and return their confirmed URLs.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_prepare_attachments( \WP_REST_Request $request ) {
		$ids   = \array_filter(
			$request->get_param( 'ids' ),
			function ( $id ) {
				return 'attachment' === \get_post_type( $id ) && \get_post_meta( $id, '_imageshop_document_id', true ) && \current_user_can( 'read_post', $id );
			}
		);
		$items = array();

		foreach ( $this->ensure( $ids, true ) as $id => $result ) {
			if ( \is_wp_error( $result ) ) {
				$items[ $id ] = array(
					'success' => false,
					'message' => $result->get_error_message(),
				);
				continue;
			}

			$attachment = $this->prepare_attachment_for_js( $id );

			$items[ $id ] = ( null === $attachment )
				? array(
					'success' => false,
					'message' => \__( 'The image sizes could not be generated.', 'imageshop-dam-connector' ),
				)
				: array(
					'success'    => true,
					'attachment' => $attachment,
				);
		}

		return \rest_ensure_response( array( 'items' => (object) $items ) );
	}

	/**
	 * Build the URL-related attachment fields the media modal and block editor rely on.
	 *
	 * Like `wp_prepare_attachment_for_js()`, which only works for local media.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array|null
	 */
	public function prepare_attachment_for_js( $attachment_id ) {
		$attachment = Attachment::get_instance();
		$attachment->clear_unresolved_state( $attachment_id );

		$media_sizes = \get_post_meta( $attachment_id, '_imageshop_media_sizes', true );
		if ( empty( $media_sizes['sizes']['original']['source_url'] ) ) {
			$media_sizes = $attachment->generate_imageshop_metadata( \get_post( $attachment_id ) );
		}

		if ( empty( $media_sizes['sizes']['original']['source_url'] ) ) {
			return null;
		}

		$sizes = array();
		foreach ( $media_sizes['sizes'] as $slug => $size ) {
			if ( empty( $size['source_url'] ) ) {
				continue;
			}

			$sizes[ $slug ] = array(
				'url'         => Attachment::apply_cdn_domain( $size['source_url'] ),
				'width'       => (int) $size['width'],
				'height'      => (int) $size['height'],
				'orientation' => ( (int) $size['height'] > (int) $size['width'] ? 'portrait' : 'landscape' ),
			);
		}

		$full = isset( $sizes['full'] ) ? $sizes['full'] : $sizes['original'];

		return array(
			'url'    => $full['url'],
			'sizes'  => $sizes,
			'width'  => $sizes['original']['width'],
			'height' => $sizes['original']['height'],
		);
	}

	/**
	 * Check if an attachment has a permalink that can be used.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	public function is_available( $attachment_id ): bool {
		if ( empty( \get_post_meta( $attachment_id, self::META_URL, true ) ) ) {
			return false;
		}

		// A stored URL is never pending; this guards against inconsistent data.
		return self::STATUS_PENDING !== $this->get_status( $attachment_id );
	}

	/**
	 * Get the permalink status of an attachment.
	 *
	 * An empty string means a permalink saved by an earlier version, that has not been checked yet.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	public function get_status( $attachment_id ): string {
		return (string) \get_post_meta( $attachment_id, self::META_STATUS, true );
	}

	/**
	 * Save what to register for an attachment, without registering it yet.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param int    $document_id   Imageshop document ID.
	 * @param int    $width         Original image width.
	 * @param int    $height        Original image height.
	 * @param string $token         Complete permalink token, including the `-{W}x{H}` suffix.
	 *
	 * @return array The stored request.
	 */
	public function remember_request( $attachment_id, $document_id, $width, $height, $token ): array {
		$request = array(
			'document_id' => (int) $document_id,
			'width'       => (int) $width,
			'height'      => (int) $height,
			'token'       => (string) $token,
		);

		if ( \get_post_meta( $attachment_id, self::META_REQUEST, true ) !== $request ) {
			\update_post_meta( $attachment_id, self::META_REQUEST, $request );
		}

		return $request;
	}

	/**
	 * Get the stored registration request for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array|null
	 */
	public function get_request( $attachment_id ) {
		$request = \get_post_meta( $attachment_id, self::META_REQUEST, true );

		if ( empty( $request['token'] ) || empty( $request['document_id'] ) || empty( $request['width'] ) || empty( $request['height'] ) ) {
			return null;
		}

		return $request;
	}

	/**
	 * Queue an attachment for background registration.
	 *
	 * Used outside the media modal, so visitors never wait for the API.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $request       The registration request, see remember_request().
	 *
	 * @return void
	 */
	public function queue( $attachment_id, array $request ) {
		$this->remember_request( $attachment_id, $request['document_id'], $request['width'], $request['height'], $request['token'] );

		$status = $this->get_status( $attachment_id );

		if ( self::STATUS_FAILED === $status ) {
			return;
		}

		if ( self::STATUS_PENDING !== $status ) {
			\update_post_meta( $attachment_id, self::META_STATUS, self::STATUS_PENDING );
			\update_post_meta( $attachment_id, self::META_NEXT_ATTEMPT, \time() );
		}

		$this->schedule( \max( 0, (int) \get_post_meta( $attachment_id, self::META_NEXT_ATTEMPT, true ) - \time() ) );
	}

	/**
	 * Register, and confirm, the permalinks for a set of attachments.
	 *
	 * Temporary failures, except timeouts, are retried once.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 * @param bool  $force          Whether to attempt attachments that have failed, or are waiting for a retry.
	 *
	 * @return array<int, true|\WP_Error> Keyed by attachment ID.
	 */
	public function ensure( array $attachment_ids, bool $force = false ): array {
		$results  = array();
		$requests = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;

			if ( $this->is_available( $attachment_id ) ) {
				$results[ $attachment_id ] = true;
				continue;
			}

			if ( ! $force && ! $this->is_due( $attachment_id ) ) {
				$results[ $attachment_id ] = new \WP_Error( 'imageshop_permalink_waiting', \__( 'Waiting to retry the permalink registration.', 'imageshop-dam-connector' ) );
				continue;
			}

			$request = $this->get_request( $attachment_id );
			if ( null === $request ) {
				$request = Attachment::get_instance()->get_permalink_request( $attachment_id );
			}

			if ( null === $request ) {
				$results[ $attachment_id ] = new \WP_Error( 'imageshop_permalink_unresolved', \__( 'The image could not be found in Imageshop.', 'imageshop-dam-connector' ) );
				continue;
			}

			$requests[ $attachment_id ] = $request;
		}

		return $results + $this->register( $requests, false );
	}

	/**
	 * Check permalinks saved by earlier versions, and register the broken ones again.
	 *
	 * Only broken permalinks, found with HEAD requests, are sent to the API. Their URL stays the same.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 *
	 * @return array<int, string|\WP_Error> Keyed by attachment ID: `working`, `registered` (was broken), `exists`, or a WP_Error.
	 */
	public function verify_legacy( array $attachment_ids ): array {
		$results  = array();
		$requests = array();
		$prefix   = \untrailingslashit( REST_Controller::IMAGESHOP_CDN_PREFIX ) . '/';

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			$url           = (string) \get_post_meta( $attachment_id, self::META_URL, true );
			$document_id   = (int) \get_post_meta( $attachment_id, '_imageshop_document_id', true );
			$token         = ( 0 === \strpos( $url, $prefix ) ) ? \substr( $url, \strlen( $prefix ) ) : '';

			if ( ! $document_id || ! \preg_match( '/-(\d+)x(\d+)$/', $token, $dimensions ) ) {
				$error                     = new \WP_Error( 'imageshop_permalink_unparseable', \__( 'The stored permalink could not be parsed.', 'imageshop-dam-connector' ), array( 'transient' => false ) );
				$results[ $attachment_id ] = $error;
				$this->record_failure( $attachment_id, $error, true );
				continue;
			}

			$working = $this->is_permalink_working( $url );

			if ( true === $working ) {
				$results[ $attachment_id ] = 'working';
				$this->record_success( $attachment_id, array( 'token' => $token ), true );
				continue;
			}

			if ( \is_wp_error( $working ) ) {
				$results[ $attachment_id ] = $working;
				$this->record_failure( $attachment_id, $working, true );
				continue;
			}

			$requests[ $attachment_id ] = $this->remember_request( $attachment_id, $document_id, (int) $dimensions[1], (int) $dimensions[2], $token );
		}

		return $results + $this->register( $requests, true );
	}

	/**
	 * Check if a permalink works, without contacting the Imageshop API.
	 *
	 * Working permalinks return 2xx or redirect to S3, broken ones end in a 404. Redirects are
	 * followed manually, as S3 only allows GET requests.
	 *
	 * @param string $url Permalink.
	 *
	 * @return bool|\WP_Error Whether the permalink works, or a WP_Error if that could not be determined.
	 */
	private function is_permalink_working( string $url ) {
		for ( $hop = 0; $hop < 3; $hop++ ) {
			$response = \wp_remote_head(
				$url,
				array(
					'redirection' => 0,
					'timeout'     => REST_Controller::get_request_timeout(),
				)
			);

			if ( \is_wp_error( $response ) ) {
				break;
			}

			$code     = (int) \wp_remote_retrieve_response_code( $response );
			$location = (string) \wp_remote_retrieve_header( $response, 'location' );
			$host     = (string) \wp_parse_url( $location, \PHP_URL_HOST );

			if ( $code >= 200 && $code < 300 ) {
				return true;
			}

			if ( 404 === $code || 410 === $code ) {
				return false;
			}

			if ( $code < 300 || $code >= 400 || '' === $host ) {
				break;
			}

			if ( '.amazonaws.com' === \substr( $host, -14 ) ) {
				return true;
			}

			$url = $location;
		}

		return new \WP_Error( 'imageshop_permalink_check_failed', \__( 'The permalink could not be checked.', 'imageshop-dam-connector' ), array( 'transient' => true ) );
	}

	/**
	 * Retry due permalinks, and continue checking those from earlier versions.
	 *
	 * Runs on WP-Cron.
	 *
	 * @return array Summary of the run.
	 */
	public function process_queue(): array {
		$summary = array(
			'retried'  => 0,
			'verified' => 0,
			'repaired' => 0,
			'failed'   => 0,
		);

		$due = $this->query_attachments(
			array(
				array(
					'key'     => self::META_STATUS,
					'value'   => array( self::STATUS_PENDING, self::STATUS_UNVERIFIED ),
					'compare' => 'IN',
				),
				array(
					'key'     => self::META_NEXT_ATTEMPT,
					'value'   => \time(),
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
			),
			self::PER_RUN_LIMIT
		);

		$pending    = array();
		$unverified = array();
		foreach ( $due as $attachment_id ) {
			if ( self::STATUS_UNVERIFIED === $this->get_status( $attachment_id ) ) {
				$unverified[] = $attachment_id;
			} else {
				$pending[] = $attachment_id;
			}
		}

		foreach ( $this->ensure( $pending ) as $result ) {
			++$summary['retried'];
			if ( \is_wp_error( $result ) ) {
				++$summary['failed'];
			}
		}

		$remaining = self::PER_RUN_LIMIT - \count( $due );
		$legacy    = array();

		if ( $remaining > 0 && 'pending' === \get_option( self::SWEEP_OPTION ) ) {
			$legacy = $this->query_attachments(
				array(
					array(
						'key'     => self::META_URL,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => self::META_STATUS,
						'compare' => 'NOT EXISTS',
					),
				),
				$remaining
			);

			if ( empty( $legacy ) ) {
				\update_option( self::SWEEP_OPTION, 'done', false );
				Progress::finish( 'permalinks' );
			}
		}

		$counts = array(
			'working'  => 0,
			'repaired' => 0,
			'retrying' => 0,
			'failed'   => 0,
		);

		foreach ( $this->verify_legacy( \array_merge( $unverified, $legacy ) ) as $attachment_id => $result ) {
			++$summary['verified'];
			if ( \is_wp_error( $result ) ) {
				++$summary['failed'];
				$outcome = self::STATUS_FAILED === $this->get_status( $attachment_id ) ? 'failed' : 'retrying';
			} elseif ( 'registered' === $result ) {
				++$summary['repaired'];
				$outcome = 'repaired';
			} else {
				$outcome = 'working';
			}

			if ( \in_array( $attachment_id, $legacy, true ) ) {
				++$counts[ $outcome ];
			}
		}

		if ( ! empty( $legacy ) ) {
			Progress::advance( 'permalinks', \count( $legacy ), $counts );
		}

		// Continue right away while there is a backlog, otherwise wait for the next retry.
		if ( \count( $due ) >= self::PER_RUN_LIMIT || ( ! empty( $legacy ) && 'pending' === \get_option( self::SWEEP_OPTION ) ) ) {
			$this->schedule( 60 );
		} else {
			$next = $this->get_next_attempt_time();
			if ( null !== $next ) {
				$this->schedule( \max( 60, $next - \time() ) );
			}
		}

		return $summary;
	}

	/**
	 * Start checking permalinks saved by earlier versions in the background.
	 *
	 * @param bool $recheck Also check confirmed permalinks.
	 *
	 * @return int The number of permalinks to check.
	 */
	public function start_legacy_sweep( bool $recheck = false ): int {
		if ( $recheck ) {
			\delete_metadata( 'post', 0, self::META_STATUS, self::STATUS_CONFIRMED, true );
		}

		$total = $this->get_status_counts()['legacy'] ?? 0;

		if ( $total > 0 ) {
			Progress::start( 'permalinks', $total, 'web' );
			\update_option( self::SWEEP_OPTION, 'pending', false );
			$this->schedule();
		}

		return $total;
	}

	/**
	 * Count attachments by permalink status.
	 *
	 * @return array<string, int>
	 */
	public function get_status_counts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reporting query, the result is not reused.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COALESCE( s.meta_value, %s ) AS status, COUNT(*) AS total
				FROM {$wpdb->postmeta} d
				LEFT JOIN {$wpdb->postmeta} u ON u.post_id = d.post_id AND u.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} s ON s.post_id = d.post_id AND s.meta_key = %s
				WHERE d.meta_key = '_imageshop_document_id' AND ( u.meta_id IS NOT NULL OR s.meta_id IS NOT NULL )
				GROUP BY status",
				'legacy',
				self::META_URL,
				self::META_STATUS
			)
		);

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ $row->status ] = (int) $row->total;
		}

		return $counts;
	}

	/**
	 * Schedule a background run, or move an existing one forward.
	 *
	 * @param int $delay Delay in seconds.
	 *
	 * @return void
	 */
	public function schedule( int $delay = 0 ) {
		$when      = \time() + \max( 0, $delay );
		$scheduled = \wp_next_scheduled( self::CRON_HOOK );

		if ( false !== $scheduled && $scheduled <= $when ) {
			return;
		}

		if ( false !== $scheduled ) {
			\wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}

		\wp_schedule_single_event( $when, self::CRON_HOOK );
	}

	/**
	 * Register permalinks, retrying temporary failures once, and record the outcome.
	 *
	 * @param array<int, array> $requests Registration requests, keyed by attachment ID.
	 * @param bool              $legacy   Whether these are verifications of permalinks stored by an earlier version.
	 *
	 * @return array<int, string|true|\WP_Error> Keyed by attachment ID.
	 */
	private function register( array $requests, bool $legacy ): array {
		if ( empty( $requests ) ) {
			return array();
		}

		$api      = REST_Controller::get_instance();
		$results  = array();
		$attempts = 0;

		while ( ! empty( $requests ) && $attempts < 2 ) {
			++$attempts;

			$payloads = array();
			foreach ( $requests as $request ) {
				$payloads[] = $api->build_permalink_payload( $request['document_id'], $request['width'], $request['height'], $request['token'] );
			}

			$outcomes = $api->register_permalinks( $payloads );
			$retry    = array();

			foreach ( $requests as $attachment_id => $request ) {
				$outcome = isset( $outcomes[ $request['token'] ] ) ? $outcomes[ $request['token'] ] : new \WP_Error( 'imageshop_permalink_missing_result', \__( 'No result was returned for this permalink.', 'imageshop-dam-connector' ), array( 'transient' => true ) );

				if ( \is_wp_error( $outcome ) && $attempts < 2 && $this->is_retryable_in_request( $outcome ) ) {
					$retry[ $attachment_id ] = $request;
					continue;
				}

				if ( \is_wp_error( $outcome ) ) {
					$this->record_failure( $attachment_id, $outcome, $legacy );
					$results[ $attachment_id ] = $outcome;
					continue;
				}

				$this->record_success( $attachment_id, $request, $legacy );
				$results[ $attachment_id ] = $legacy ? $outcome : true;
			}

			$requests = $retry;

			if ( ! empty( $requests ) ) {
				\sleep( 1 );
			}
		}

		return $results;
	}

	/**
	 * Check if a failure should be retried in the same request.
	 *
	 * @param \WP_Error $error The failure.
	 *
	 * @return bool
	 */
	private function is_retryable_in_request( \WP_Error $error ): bool {
		$data = $error->get_error_data();

		if ( empty( $data['transient'] ) ) {
			return false;
		}

		// A second timeout would make a long wait twice as long.
		return false === \stripos( $error->get_error_message(), 'timed out' );
	}

	/**
	 * Store a confirmed permalink.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $request       The registration request.
	 * @param bool  $legacy        Whether this verified a permalink stored by an earlier version.
	 *
	 * @return void
	 */
	private function record_success( $attachment_id, array $request, bool $legacy ) {
		$url = \untrailingslashit( REST_Controller::IMAGESHOP_CDN_PREFIX ) . '/' . $request['token'];

		\update_post_meta( $attachment_id, self::META_URL, $url );
		\update_post_meta( $attachment_id, self::META_STATUS, self::STATUS_CONFIRMED );
		\delete_post_meta( $attachment_id, self::META_ATTEMPTS );
		\delete_post_meta( $attachment_id, self::META_NEXT_ATTEMPT );
		\delete_post_meta( $attachment_id, self::META_ERROR );

		if ( ! $legacy ) {
			// Rebuild sizes cached before the permalink was confirmed.
			\delete_post_meta( $attachment_id, '_imageshop_media_sizes' );
		}

		Attachment::get_instance()->clear_unresolved_state( $attachment_id );
	}

	/**
	 * Record a failed registration, and schedule the next attempt.
	 *
	 * Permalinks from earlier versions are still used meanwhile, as most of them work.
	 *
	 * @param int       $attachment_id Attachment ID.
	 * @param \WP_Error $error         The failure.
	 * @param bool      $legacy        Whether this was a verification of a permalink stored by an earlier version.
	 *
	 * @return void
	 */
	private function record_failure( $attachment_id, \WP_Error $error, bool $legacy ) {
		$attempts  = (int) \get_post_meta( $attachment_id, self::META_ATTEMPTS, true ) + 1;
		$data      = $error->get_error_data();
		$transient = ! empty( $data['transient'] );

		if ( ! $transient || $attempts >= self::MAX_AUTOMATIC_ATTEMPTS ) {
			$status = self::STATUS_FAILED;
		} else {
			$status = $legacy ? self::STATUS_UNVERIFIED : self::STATUS_PENDING;
		}

		$delay = self::BACKOFF[ \min( $attempts, \count( self::BACKOFF ) ) - 1 ];

		\update_post_meta( $attachment_id, self::META_STATUS, $status );
		\update_post_meta( $attachment_id, self::META_ATTEMPTS, $attempts );
		\update_post_meta( $attachment_id, self::META_NEXT_ATTEMPT, \time() + $delay );
		\update_post_meta( $attachment_id, self::META_ERROR, $error->get_error_message() );

		if ( self::STATUS_FAILED !== $status ) {
			$this->schedule( $delay );
		}

		if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logged when debugging is enabled.
			\error_log(
				\sprintf(
					'Imageshop DAM Connector: Permalink registration for attachment %d failed (attempt %d, now %s): %s',
					$attachment_id,
					$attempts,
					$status,
					$error->get_error_message()
				)
			);
		}

		/**
		 * Fires when the registration of an Imageshop permalink fails.
		 *
		 * @param int       $attachment_id Attachment ID.
		 * @param \WP_Error $error         The failure.
		 * @param string    $status        The new permalink status.
		 * @param int       $attempts      Number of failed attempts so far.
		 */
		\do_action( 'imageshop_permalink_registration_failed', $attachment_id, $error, $status, $attempts );
	}

	/**
	 * Check if an attachment is due for an automatic attempt.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	private function is_due( $attachment_id ): bool {
		if ( self::STATUS_FAILED === $this->get_status( $attachment_id ) ) {
			return false;
		}

		return (int) \get_post_meta( $attachment_id, self::META_NEXT_ATTEMPT, true ) <= \time();
	}

	/**
	 * Get the time of the next scheduled retry.
	 *
	 * @return int|null
	 */
	private function get_next_attempt_time() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Scheduling lookup, must reflect the current state.
		$next = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN( CAST( n.meta_value AS UNSIGNED ) )
				FROM {$wpdb->postmeta} n
				INNER JOIN {$wpdb->postmeta} s ON s.post_id = n.post_id AND s.meta_key = %s AND s.meta_value IN ( %s, %s )
				WHERE n.meta_key = %s",
				self::META_STATUS,
				self::STATUS_PENDING,
				self::STATUS_UNVERIFIED,
				self::META_NEXT_ATTEMPT
			)
		);

		return null === $next ? null : (int) $next;
	}

	/**
	 * Find Imageshop attachment IDs matching a meta query.
	 *
	 * @param array $meta_query Meta query clauses.
	 * @param int   $limit      Maximum number of IDs.
	 *
	 * @return int[]
	 */
	private function query_attachments( array $meta_query, int $limit ): array {
		return \array_map(
			'intval',
			\get_posts(
				array(
					'post_type'              => 'attachment',
					'post_status'            => 'any',
					'posts_per_page'         => $limit,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Background processing in small batches.
					'meta_query'             => \array_merge(
						array(
							'relation' => 'AND',
							array(
								'key'     => '_imageshop_document_id',
								'compare' => 'EXISTS',
							),
						),
						$meta_query
					),
				)
			)
		);
	}
}
