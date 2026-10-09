<?php

namespace Imageshop\WordPress\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Compatibility layer for Advanced Custom Fields.
 *
 * ACF validates selected media with `wp_attachment_is_image()`, which (via `wp_attachment_is()`)
 * rejects any attachment where `get_attached_file()` is empty, before ever looking at its mime type.
 * Media picked directly from the Imageshop library has no local file, and deliberately no
 * `_wp_attached_file` meta, as its absence is how this plugin identifies virtual attachments.
 *
 * Instead of storing a value, a synthetic attached file is provided only while ACF is running
 * its own validation of a media field, leaving ACF's remaining checks (type, size) in place.
 */
class AdvancedCustomFields {

	/**
	 * ACF field types that select attachments.
	 *
	 * @var string[]
	 */
	private $media_field_types = array( 'image', 'gallery', 'file' );

	/**
	 * How many ACF media validations are currently running.
	 *
	 * A counter, as validation of nested fields (repeaters, flexible content) may overlap.
	 *
	 * @var int
	 */
	private $validation_depth = 0;

	/**
	 * Initialize the ACF compatibility layer
	 */
	public function __construct() {
		foreach ( $this->media_field_types as $field_type ) {
			// Field values on save.
			\add_filter( 'acf/validate_value/type=' . $field_type, array( $this, 'start_validation' ), 0 );
			\add_filter( 'acf/validate_value/type=' . $field_type, array( $this, 'end_validation' ), PHP_INT_MAX );

			// Attachments selected in the media modal, or uploaded with the basic uploader.
			\add_filter( 'acf/validate_attachment/type=' . $field_type, array( $this, 'start_validation' ), 0 );
			\add_filter( 'acf/validate_attachment/type=' . $field_type, array( $this, 'validate_allowed_file_types' ), 10, 4 );
			\add_filter( 'acf/validate_attachment/type=' . $field_type, array( $this, 'end_validation' ), PHP_INT_MAX );
		}

		\add_filter( 'get_attached_file', array( $this, 'virtual_attached_file' ), 10, 2 );
	}

	/**
	 * Flag that ACF is validating a media field.
	 *
	 * @param mixed $value The validity status, or list of errors, passed through unmodified.
	 *
	 * @return mixed
	 */
	public function start_validation( $value ) {
		++$this->validation_depth;

		return $value;
	}

	/**
	 * Flag that ACF has finished validating a media field.
	 *
	 * @param mixed $value The validity status, or list of errors, passed through unmodified.
	 *
	 * @return mixed
	 */
	public function end_validation( $value ) {
		$this->validation_depth = \max( 0, $this->validation_depth - 1 );

		return $value;
	}

	/**
	 * Enforce a field's "Allowed file types" setting for Imageshop attachments.
	 *
	 * ACF derives the file type from the extension of the attachment file name, which is empty
	 * for Imageshop attachments, so its own check is skipped. Compare mime types instead.
	 *
	 * @param array $errors     Validation errors.
	 * @param array $file       Normalized file data.
	 * @param array $attachment Attachment data for the current context.
	 * @param array $field      The field array.
	 *
	 * @return array
	 */
	public function validate_allowed_file_types( $errors, $file, $attachment, $field ) {
		$attachment_id = (int) \acf_maybe_get( $attachment, 'id', 0 );

		if ( ! empty( $file['type'] ) || ! $attachment_id || ! \get_post_meta( $attachment_id, '_imageshop_document_id', true ) ) {
			return $errors;
		}

		$extensions = \array_filter( \explode( ',', \str_replace( array( ' ', '.' ), '', \strtolower( (string) \acf_maybe_get( $field, 'mime_types', '' ) ) ) ) );

		if ( empty( $extensions ) ) {
			return $errors;
		}

		$allowed_mimes = array();
		foreach ( $extensions as $extension ) {
			$allowed_mimes[] = \wp_check_filetype( 'file.' . $extension )['type'];
		}

		if ( ! \in_array( \get_post_mime_type( $attachment_id ), $allowed_mimes, true ) ) {
			/* translators: %s: Comma-separated list of allowed file extensions. */
			$errors['mime_types'] = \sprintf( \__( 'File type must be %s.', 'imageshop-dam-connector' ), \implode( ', ', $extensions ) );
		}

		return $errors;
	}

	/**
	 * Provide a synthetic attached file for Imageshop attachments during ACF validation.
	 *
	 * The path never exists on disk, and is only used to satisfy `wp_attachment_is()`, which
	 * checks the attachment mime type before the file extension.
	 *
	 * @param string|false $file          The attached file path, or empty if none is stored.
	 * @param int          $attachment_id Attachment ID.
	 *
	 * @return string|false
	 */
	public function virtual_attached_file( $file, $attachment_id ) {
		if ( $this->validation_depth < 1 || ! empty( $file ) ) {
			return $file;
		}

		if ( ! \get_post_meta( $attachment_id, '_imageshop_document_id', true ) ) {
			return $file;
		}

		$extension = \wp_get_default_extension_for_mime_type( (string) \get_post_mime_type( $attachment_id ) );

		return \trailingslashit( \wp_upload_dir()['basedir'] ) . 'imageshop-virtual-' . $attachment_id . ( $extension ? '.' . $extension : '' );
	}
}
