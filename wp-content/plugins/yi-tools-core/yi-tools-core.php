<?php
/**
 * Plugin Name: Yi Tools Core
 * Description: 吾爱易学工具核心，提供五行穿衣查询的算法、REST API 与短代码。
 * Version: 0.1.0
 * Author: Wuaishare
 * Text Domain: yi-tools-core
 * Requires at least: 6.9
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YI_TOOLS_CORE_VERSION', '0.1.0' );
define( 'YI_TOOLS_CORE_FILE', __FILE__ );
define( 'YI_TOOLS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'YI_TOOLS_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once YI_TOOLS_CORE_DIR . 'includes/Wuxing/ElementRules.php';
require_once YI_TOOLS_CORE_DIR . 'includes/Wuxing/ClothingColors.php';
require_once YI_TOOLS_CORE_DIR . 'includes/Rest/WuxingClothingController.php';
require_once YI_TOOLS_CORE_DIR . 'includes/Shortcodes/WuxingClothingShortcode.php';

add_action(
	'init',
	static function (): void {
		\YiToolsCore\Shortcodes\WuxingClothingShortcode::register();
	}
);

add_action(
	'rest_api_init',
	static function (): void {
		( new \YiToolsCore\Rest\WuxingClothingController() )->register_routes();
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		\YiToolsCore\Shortcodes\WuxingClothingShortcode::register();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules();
	}
);
