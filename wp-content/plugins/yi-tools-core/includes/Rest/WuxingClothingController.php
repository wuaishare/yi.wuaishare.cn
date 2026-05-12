<?php
declare(strict_types=1);

namespace YiToolsCore\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use YiToolsCore\Wuxing\ClothingColors;

final class WuxingClothingController {
	public function register_routes(): void {
		register_rest_route(
			'yi-tools/v1',
			'/wuxing-clothing',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'date' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function get_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$date = (string) ( $request->get_param( 'date' ) ?: wp_date( 'Y-m-d' ) );
		$data = ClothingColors::build_for_date_string( $date );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return rest_ensure_response( $data );
	}
}
