<?php

declare(strict_types=1);

namespace Imageshop\WordPress\Media;

use Imageshop\WordPress\API\REST_Controller;
use Imageshop\WordPress\Imageshop;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Search
 */
class Insert {
	private $attachment;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( Imageshop::onboarding_completed() ) {
			$this->attachment = Attachment::get_instance();

			\add_filter( 'rest_prepare_attachment', array( $this, 'rest_image_override' ), 10, 2 );
			\add_filter( 'image_size_names_choose', array( $this, 'add_original_image_size_name' ) );
		}
	}

	/**
	 * Modify the attachment post object response.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     The original attachment post.
	 *
	 * @return mixed
	 */
	public function rest_image_override( $response, $post ) {
		if ( 'attachment' !== $post->post_type ) {
			return $response;
		}
		if ( ! $post->_imageshop_document_id ) {
			return $response;
		}

		$media_details = $post->_imageshop_media_sizes;

		if ( empty( $media_details ) ) {
			$media_details = $this->attachment->generate_imageshop_metadata( $post );
		} elseif ( isset( $media_details['sizes']['original'] ) ) {
			/*
			 * Always re-derive 'full' from 'original' using the big-image threshold so that
			 * stale cache entries (where 'full' was stored as original dimensions before the
			 * threshold logic was introduced) are corrected on the next editorial load.
			 */
			$orig_w = (int) $media_details['sizes']['original']['width'];
			$orig_h = (int) $media_details['sizes']['original']['height'];
			$full   = $this->attachment->get_threshold_scaled_full_size( $post->ID, $orig_w, $orig_h );

			if ( ! empty( $full ) ) {
				$media_details['sizes']['full'] = $full;
				\update_post_meta( $post->ID, '_imageshop_media_sizes', $media_details );
			}
		}

		$response->data['media_details'] = $media_details;

		return $response;
	}

	public function add_original_image_size_name( $sizes ) {
		return array_merge( $sizes, array( 'original' => \__( 'Original', 'imageshop-dam-connector' ) ) );
	}
}
