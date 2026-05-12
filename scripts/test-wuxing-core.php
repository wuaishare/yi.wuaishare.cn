<?php
declare(strict_types=1);

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;
		private array $data;

		public function __construct( string $code, string $message, array $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): array {
			return $this->data;
		}
	}
}

require_once __DIR__ . '/../wp-content/plugins/yi-tools-core/includes/Wuxing/ElementRules.php';
require_once __DIR__ . '/../wp-content/plugins/yi-tools-core/includes/Wuxing/ClothingColors.php';

use YiToolsCore\Wuxing\ClothingColors;

function assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

$result = ClothingColors::build_for_date_string( '2026-05-13' );
assert_true( is_array( $result ), 'valid date returns data' );
assert_true( '2026-05-13' === $result['date'], 'date is preserved' );
assert_true( isset( $result['colors']['lucky'][0]['name'] ), 'lucky colors are present' );
assert_true( isset( $result['colors']['avoid'][0]['hex'] ), 'avoid colors include hex values' );

$bad_format = ClothingColors::build_for_date_string( 'bad-date' );
assert_true( $bad_format instanceof WP_Error, 'bad date returns WP_Error' );
assert_true( 'yi_tools_invalid_date' === $bad_format->get_error_code(), 'bad date error code is stable' );

$bad_calendar_date = ClothingColors::build_for_date_string( '2026-02-30' );
assert_true( $bad_calendar_date instanceof WP_Error, 'impossible calendar date returns WP_Error' );

echo "wuxing-core smoke tests passed\n";
