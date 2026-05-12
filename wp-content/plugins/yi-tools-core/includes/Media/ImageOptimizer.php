<?php
declare(strict_types=1);

namespace YiToolsCore\Media;

use WP_Image_Editor;

final class ImageOptimizer {
	private const MAX_WIDTH  = 1600;
	private const MAX_HEIGHT = 1200;
	private const QUALITY    = 82;

	public static function register(): void {
		add_filter( 'wp_handle_upload', array( self::class, 'optimize_upload' ) );
		add_filter( 'wp_handle_sideload', array( self::class, 'optimize_upload' ) );
	}

	/**
	 * @param array{file?: string, url?: string, type?: string} $upload
	 * @return array{file?: string, url?: string, type?: string}
	 */
	public static function optimize_upload( array $upload ): array {
		$file = isset( $upload['file'] ) ? (string) $upload['file'] : '';
		$type = isset( $upload['type'] ) ? (string) $upload['type'] : '';

		if ( ! $file || ! is_file( $file ) || ! self::is_optimizable_type( $type ) ) {
			return $upload;
		}

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			return $upload;
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) || ! $editor instanceof WP_Image_Editor ) {
			return $upload;
		}

		$editor->set_quality( self::QUALITY );

		$size = $editor->get_size();
		if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
			$editor->resize( self::MAX_WIDTH, self::MAX_HEIGHT, false );
		}

		$webp_file = preg_replace( '/\.[^.]+$/', '.webp', $file );
		if ( ! $webp_file ) {
			return $upload;
		}

		$saved = $editor->save( $webp_file, 'image/webp' );
		if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_file( (string) $saved['path'] ) ) {
			return $upload;
		}

		if ( realpath( $file ) !== realpath( (string) $saved['path'] ) && is_file( $file ) ) {
			wp_delete_file( $file );
		}

		$upload['file'] = (string) $saved['path'];
		$upload['type'] = 'image/webp';

		if ( isset( $upload['url'] ) ) {
			$upload['url'] = preg_replace( '/\.[^.?#]+(\?.*)?$/', '.webp$1', (string) $upload['url'] ) ?: (string) $upload['url'];
		}

		return $upload;
	}

	private static function is_optimizable_type( string $type ): bool {
		return in_array(
			$type,
			array(
				'image/png',
				'image/jpeg',
				'image/jpg',
				'image/webp',
			),
			true
		);
	}
}
