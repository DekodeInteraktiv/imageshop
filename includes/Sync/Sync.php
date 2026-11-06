<?php
/**
 * The Sync class.
 */

declare(strict_types=1);

namespace Imageshop\WordPress\Sync;

use Imageshop\WordPress\API\REST_Controller;
use Imageshop\WordPress\Media\Attachment;
use Imageshop\WordPress\Media\Permalinks;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Sync
 */
class Sync {

	const HOOK_IMPORT_WP_TO_IMAGESHOP = 'imageshop_import_wp_to_imageshop';
	const HOOK_IMPORT_IMAGESHOP_TO_WP = 'imageshop_import_imageshop_to_wp';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		\add_action( 'plugin_loaded', array( $this, 'register_init_actions' ) );

		\add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		\add_action( 'admin_notices', array( $this, 'check_import_progress' ) );
	}

	public function register_rest_routes() {
		\register_rest_route(
			'imageshop/v1',
			'sync/remote',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_remote' ),
				'permission_callback' => array( $this, 'user_can_sync' ),
			)
		);

		\register_rest_route(
			'imageshop/v1',
			'sync/local',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_local' ),
				'permission_callback' => array( $this, 'user_can_sync' ),
			)
		);

		\register_rest_route(
			'imageshop/v1',
			'tools/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tools_status' ),
				'permission_callback' => array( $this, 'user_can_sync' ),
			)
		);

		\register_rest_route(
			'imageshop/v1',
			'tools/stop',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'stop_task' ),
				'permission_callback' => array( $this, 'user_can_sync' ),
				'args'                => array(
					'task' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'export', 'import', 'permalinks' ),
					),
				),
			)
		);

		\register_rest_route(
			'imageshop/v1',
			'tools/check-links',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'check_links' ),
				'permission_callback' => array( $this, 'user_can_sync' ),
				'args'                => array(
					'recheck' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Capability-permission callback check to see if the user can perform sync operations.
	 *
	 * @return bool
	 */
	public function user_can_sync() {
		return \current_user_can( 'manage_options' );
	}

	/**
	 * Register actions for action scheduler.
	 */
	public function register_init_actions() {
		\add_action( self::HOOK_IMPORT_WP_TO_IMAGESHOP, array( $this, 'do_import_batch_to_imageshop' ) );
		\add_action( self::HOOK_IMPORT_IMAGESHOP_TO_WP, array( $this, 'do_import_batch_to_wp' ) );
	}

	/**
	 * Function to return the current status of pushign media from WordPress to Imageshop.
	 *
	 * @return array
	 */
	public function get_media_import_status() {
		global $wpdb;

		$total_attachments = $wpdb->get_var( "SELECT COUNT( DISTINCT( p.ID ) ) AS total FROM {$wpdb->posts} AS p WHERE p.post_type = 'attachment'" );
		$total_imported    = $wpdb->get_var( "SELECT COUNT( DISTINCT( p.ID ) ) AS total FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id) WHERE p.post_type = 'attachment' AND pm.meta_key = '_imageshop_document_id' AND ( pm.meta_value IS NOT NULL AND pm.meta_value != '' )" );

		return array(
			'total'    => \absint( $total_attachments ),
			'imported' => \absint( $total_imported ),
		);
	}

	/**
	 * Trigger an export from WordPress to Imageshop.
	 *
	 * @return \WP_REST_Response
	 */
	public function sync_remote() {
		global $wpdb;

		if ( \wp_next_scheduled( self::HOOK_IMPORT_WP_TO_IMAGESHOP ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'A previous import is still in progress, please wait for it to finish before scheduling another.', 'imageshop-dam-connector' ),
				),
				425
			);
		}

		// get id of all posts that are attachements and don't have the meta _imageshop_document_id using raw SQL
		$ret = $wpdb->get_results(
			"
			SELECT
				p.ID,
				p.post_title
			FROM
				{$wpdb->posts} p
			LEFT JOIN
				{$wpdb->postmeta} pm
					ON ( p.ID = pm.post_id AND pm.meta_key = '_imageshop_document_id' )
			WHERE
				p.post_type = 'attachment'
			AND
				pm.post_id IS NULL
			AND
				p.post_mime_type LIKE 'image/%' ;",
			ARRAY_A
		);

		if ( empty( $ret ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'No images were found in the local WordPress media library, that needs to be imported to Imageshop.', 'imageshop-dam-connector' ),
				),
				200
			);
		}

		Progress::start( 'export', \count( $ret ), 'web' );

		$batchs = \array_chunk( $ret, 20 );
		foreach ( $batchs as $posts ) {
			\wp_schedule_single_event( \time() - 1, self::HOOK_IMPORT_WP_TO_IMAGESHOP, array( $posts ) );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'success',
				'message' => \esc_html__( 'The import has been scheduled, and should start momentarily.', 'imageshop-dam-connector' ),
			),
			200
		);
	}

	/**
	 * Trigger an import from Imageshop to WordPress.
	 *
	 * @return \WP_REST_Response
	 */
	public function sync_local() {
		if ( \wp_next_scheduled( self::HOOK_IMPORT_IMAGESHOP_TO_WP ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'A previous import is still in progress, please wait for it to finish before scheduling another.', 'imageshop-dam-connector' ),
				),
				425
			);
		}

		$rest = REST_Controller::get_instance();

		// Pagesize set to 0 to get all documents.
		$attr = array(
			'Pagesize'      => 0,
			'SortDirection' => 'ASC',
		);

		$ret = $rest->search( $attr );

		if ( empty( $ret->DocumentList ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$ret->DocumentList` is defined by the SaaS API.
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'No documents were found in the active Imageshop interface, that needs to be imported to the local WordPress install.', 'imageshop-dam-connector' ),
				),
				200
			);
		}

		$response = $this->prepare_response( $ret->DocumentList ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$ret->DocumentList` is defined by the SaaS API.
		$batches  = \array_chunk( $response, 5 );

		Progress::start( 'import', \count( $response ), 'web' );
		foreach ( $batches as $documents ) {
			\wp_schedule_single_event( \time() - 1, self::HOOK_IMPORT_IMAGESHOP_TO_WP, array( $documents ) );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'success',
				'message' => \esc_html__( 'The import has been scheduled, and should start momentarily.', 'imageshop-dam-connector' ),
			),
			200
		);
	}

	/**
	 * @param $documents
	 */
	public function do_import_batch_to_wp( $documents ) {
		foreach ( $documents as $document ) {
			$rest = REST_Controller::get_instance();
			$ret  = $rest->download( $document['DocumentID'] );
			$this->execute_import_to_wp( $ret->Url, $document ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$ret->Url` is defined by the SaaS API.
		}

		Progress::advance( 'import', \count( $documents ) );
	}

	/**
	 * @param $document_id
	 *
	 * @return string|null
	 */
	public function get_post_id_by_document_id( $document_id ) {
		global $wpdb;
		$ret = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT
					pm.post_id
				FROM
					{$wpdb->postmeta} pm
				WHERE
					pm.meta_value = %d
					AND
					pm.meta_key = '_imageshop_document_id'
				",
				$document_id
			)
		);

		return $ret;
	}


	/**
	 * @param $url
	 * @param $document
	 */
	public function execute_import_to_wp( $url, $document ) {
		$file = \wp_remote_get( $url );

		if ( \is_wp_error( $file ) ) {
			return $file;
		}

		$file     = \wp_remote_retrieve_body( $file );
		$filename = $document['FileName'];

		$upload_file = \wp_upload_bits( $filename, null, $file );
		if ( ! $upload_file['error'] ) {
			$wp_filetype = \wp_check_filetype( $filename, null );
			$attachment  = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent'    => 0,
				'post_title'     => \preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'meta_input'     => array(
					'_imageshop_document_id' => $document['DocumentID'],
				),
			);

			$ret = $this->get_post_id_by_document_id( $document['DocumentID'] );
			if ( $ret ) {
				$attachment = \array_merge( array( 'ID' => $ret ), $attachment );
			}

			$attachment_id = \wp_insert_attachment( $attachment, $upload_file['file'] );
			if ( ! \is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin' . '/includes/image.php';
				$attachment_data = \wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
				\wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}
		}
	}

	/**
	 * Callback that is used by action scheduler in processing a batch.
	 *
	 * @param $posts
	 */
	public function do_import_batch_to_imageshop( $posts ) {
		$imageshop = Attachment::get_instance();
		foreach ( $posts as $post ) {
			$imageshop->export_to_imageshop( (int) $post['ID'] );

			// Add an arbitrary wait between requests.
			sleep( 2 );
		}

		Progress::advance( 'export', \count( $posts ) );
	}

	/**
	 * Get the progress of long-running tasks, and the state of stored permalinks.
	 *
	 * @return array
	 */
	public function get_tools_status() {
		return array(
			'tasks'      => array(
				'export'     => Progress::get( 'export' ),
				'import'     => Progress::get( 'import' ),
				'permalinks' => Progress::get( 'permalinks' ),
			),
			'permalinks' => Permalinks::get_instance()->get_status_counts(),
		);
	}

	/**
	 * Stop a long-running task.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function stop_task( \WP_REST_Request $request ) {
		$task = $request->get_param( 'task' );

		if ( 'export' === $task ) {
			\wp_unschedule_hook( self::HOOK_IMPORT_WP_TO_IMAGESHOP );
		} elseif ( 'import' === $task ) {
			\wp_unschedule_hook( self::HOOK_IMPORT_IMAGESHOP_TO_WP );
		} else {
			\update_option( Permalinks::SWEEP_OPTION, 'stopped', false );
		}

		Progress::stop( $task );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'success',
				'message' => \esc_html__( 'The task has been stopped. A batch that is already running will be completed first.', 'imageshop-dam-connector' ),
			),
			200
		);
	}

	/**
	 * Start checking, and repairing, permalinks stored by earlier versions.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function check_links( \WP_REST_Request $request ) {
		$progress = Progress::get( 'permalinks' );

		if ( $progress && 'running' === $progress['status'] ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'A previous check is still in progress, please wait for it to finish before starting another.', 'imageshop-dam-connector' ),
				),
				425
			);
		}

		if ( 0 === Permalinks::get_instance()->start_legacy_sweep( (bool) $request->get_param( 'recheck' ) ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => \esc_html__( 'All image links have already been checked.', 'imageshop-dam-connector' ),
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'success',
				'message' => \esc_html__( 'The check has been scheduled, and should start momentarily.', 'imageshop-dam-connector' ),
			),
			200
		);
	}

	/**
	 * Filter the doc_list of uncecessary parameters because action scheduler has a limited number of charachters
	 * that can receive as parameters.
	 *
	 * @param $doc_list
	 *
	 * @return array
	 */
	private function prepare_response( $doc_list ) {
		$ret            = array();
		$allowed_values = array(
			'DocumentID',
			'FileName',
		);
		foreach ( $doc_list as $key => $document ) {
			foreach ( $allowed_values as $value ) {
				$ret[ $key ][ $value ] = $document->$value;
			}
		}

		return $ret;
	}

	/**
	 * Output an admin notice showing the progress on exporting data form WordPress to Imageshop.
	 */
	public function check_import_progress() {
		if ( ! \current_user_can( 'manage_options' ) || ! \wp_next_scheduled( self::HOOK_IMPORT_WP_TO_IMAGESHOP ) ) {
			return;
		}

		$status = $this->get_media_import_status();

		?>
		<div class="notice notice-warning">
			<h2>
				<?php \esc_html_e( 'Imageshop import status', 'imageshop-dam-connector' ); ?>
			</h2>

			<p>
				<?php \esc_html_e( 'An import job has been initiated, the current status of it can be seen below. This notice will go away once the import is completed.', 'imageshop-dam-connector' ); ?>
			</p>

			<progress max="<?php echo \esc_attr( $status['total'] ); ?>" value="<?php echo \esc_attr( $status['imported'] ); ?>">
				<?php
					\printf(
						// translators: 1: Current progress. 2: Total items to import.
						\esc_html__(
							'%1$d of %2$d attachments imported to Imageshop',
							'imageshop-dam-connector'
						),
						\esc_html( $status['imported'] ),
						\esc_html( $status['total'] )
					)
				?>
			</progress>
		</div>

		<?php
	}
}
