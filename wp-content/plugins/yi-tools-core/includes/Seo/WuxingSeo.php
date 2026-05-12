<?php
declare(strict_types=1);

namespace YiToolsCore\Seo;

use YiToolsCore\Wuxing\ClothingColors;

final class WuxingSeo {
	private const TOOL_SLUG = 'wuxing-chuanyi';

	public static function register(): void {
		add_filter( 'pre_get_document_title', array( self::class, 'filter_document_title' ), 20 );
		add_filter( 'wp_robots', array( self::class, 'filter_robots' ), 20 );
		add_action( 'wp', array( self::class, 'remove_default_canonical' ), 20 );
		add_action( 'wp_head', array( self::class, 'print_tool_meta' ), 3 );
	}

	public static function filter_document_title( string $title ): string {
		if ( ! self::is_tool_page() ) {
			return $title;
		}

		$result = self::current_result();

		if ( null === $result ) {
			return $title;
		}

		return sprintf(
			'%s五行穿衣指南：大吉色%s - %s',
			$result['display_date'],
			self::color_names( $result['colors']['lucky'] ),
			get_bloginfo( 'name' )
		);
	}

	public static function filter_robots( array $robots ): array {
		if ( ! self::is_tool_page() || ! self::has_non_today_date_query() ) {
			return $robots;
		}

		unset( $robots['index'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;

		return $robots;
	}

	public static function remove_default_canonical(): void {
		if ( self::is_tool_page() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	public static function print_tool_meta(): void {
		if ( ! self::is_tool_page() ) {
			return;
		}

		$result = self::current_result();

		if ( null === $result ) {
			return;
		}

		$title       = self::filter_document_title( '' );
		$description = self::description( $result );
		$canonical   = get_permalink();
		$json_ld     = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'WebApplication',
			'name'                => '五行穿衣指南查询',
			'applicationCategory' => 'LifestyleApplication',
			'operatingSystem'     => 'Web',
			'url'                 => $canonical,
			'description'         => $description,
			'offers'              => array(
				'@type'         => 'Offer',
				'price'         => '0',
				'priceCurrency' => 'CNY',
			),
		);
		?>
		<meta name="description" content="<?php echo esc_attr( $description ); ?>">
		<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>">
		<meta property="og:type" content="website">
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
		<meta property="og:url" content="<?php echo esc_url( $canonical ); ?>">
		<script type="application/ld+json"><?php echo wp_json_encode( $json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		<?php
	}

	private static function is_tool_page(): bool {
		return is_page( self::TOOL_SLUG );
	}

	private static function current_result(): ?array {
		$result = ClothingColors::build_for_date_string( self::query_date() );

		if ( is_wp_error( $result ) ) {
			$result = ClothingColors::build_for_date_string( wp_date( 'Y-m-d' ) );
		}

		return is_wp_error( $result ) ? null : $result;
	}

	private static function query_date(): string {
		if ( ! isset( $_GET['date'] ) ) {
			return wp_date( 'Y-m-d' );
		}

		return sanitize_text_field( wp_unslash( $_GET['date'] ) );
	}

	private static function has_non_today_date_query(): bool {
		return isset( $_GET['date'] ) && self::query_date() !== wp_date( 'Y-m-d' );
	}

	private static function description( array $result ): string {
		return sprintf(
			'%s五行穿衣指南：%s，日五行为%s，大吉色%s，次吉色%s。内容仅作传统文化和日常穿搭参考。',
			$result['display_date'],
			$result['ganzhi_day'],
			$result['day_element'],
			self::color_names( $result['colors']['lucky'] ),
			self::color_names( $result['colors']['secondary'] )
		);
	}

	private static function color_names( array $colors ): string {
		return implode( '、', array_column( $colors, 'name' ) );
	}
}
