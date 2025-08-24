<?php

namespace Imageshop\WordPress\Compatibility;

class Polylang {

	/**
	 * Meta fields to copy when featured image is synced
	 *
	 * @var array
	 */
	private $meta_fields_to_copy = array(
		'_imageshop_document_id',
		'_imageshop_permalink_token',
		'_imageshop_permalinks',
		'_imageshop_media_sizes',
	);

	/**
	 * Initialize the Polylang compatibility layer
	 */
	public function __construct() {
		add_action( 'pll_copy_post_metas', array( $this, 'copy_featured_image_meta' ) );
	}

	/**
	 * Set meta fields to copy during featured image sync
	 *
	 * @param array $meta_fields Array of meta field keys to copy
	 */
	public function set_meta_fields_to_copy( $meta_fields ) {
		$this->meta_fields_to_copy = $meta_fields;
	}

	/**
	 * Copy featured image meta when post is duplicated/synced by Polylang
	 *
	 * @param array $metas Array of meta keys to copy
	 * @param bool  $sync  Whether this is a sync operation
	 * @param int   $from  Source post ID
	 * @param int   $to    Target post ID
	 * @param string $lang Target language
	 * @return array Modified meta keys array
	 */
	public function copy_featured_image_meta( $metas ) {
		// Only proceed if we have meta fields configured
		if ( empty( $this->meta_fields_to_copy ) ) {
			return $metas;
		}

		return array_merge( $metas, $this->meta_fields_to_copy );
	}
}

new Polylang();
