<?php
declare(strict_types=1);

namespace YiToolsCore\Wuxing;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use WP_Error;

final class ClothingColors {
	public const COLORS = array(
		'木' => array(
			array( 'name' => '绿色', 'hex' => '#2f855a' ),
			array( 'name' => '青色', 'hex' => '#0f766e' ),
			array( 'name' => '浅绿', 'hex' => '#86c98b', 'text_hex' => '#2f855a' ),
		),
		'火' => array(
			array( 'name' => '红色', 'hex' => '#c53030' ),
			array( 'name' => '粉色', 'hex' => '#d53f8c' ),
			array( 'name' => '紫色', 'hex' => '#805ad5' ),
			array( 'name' => '橙红', 'hex' => '#dd6b20' ),
		),
		'土' => array(
			array( 'name' => '黄色', 'hex' => '#d69e2e', 'text_hex' => '#8a5a08' ),
			array( 'name' => '咖色', 'hex' => '#8b5e34' ),
			array( 'name' => '棕色', 'hex' => '#7b341e' ),
			array( 'name' => '米黄', 'hex' => '#ead7a4', 'text_hex' => '#8a5a08' ),
			array( 'name' => '驼色', 'hex' => '#b8895b' ),
		),
		'金' => array(
			array( 'name' => '白色', 'hex' => '#f7fafc', 'text_hex' => '#4b5563' ),
			array( 'name' => '银色', 'hex' => '#cbd5e0', 'text_hex' => '#64748b' ),
			array( 'name' => '灰色', 'hex' => '#718096' ),
			array( 'name' => '金色', 'hex' => '#8f6b18', 'text_hex' => '#7a5a10' ),
			array( 'name' => '米白', 'hex' => '#f5f0df', 'text_hex' => '#7c6f4d' ),
		),
		'水' => array(
			array( 'name' => '黑色', 'hex' => '#111827' ),
			array( 'name' => '蓝色', 'hex' => '#2b6cb0' ),
			array( 'name' => '深蓝', 'hex' => '#1a365d' ),
		),
	);

	public static function build_for_date_string( string $date ): array|WP_Error {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new WP_Error( 'yi_tools_invalid_date', '日期格式必须为 YYYY-MM-DD。', array( 'status' => 400 ) );
		}

		try {
			$datetime = new DateTimeImmutable( $date, new DateTimeZone( 'Asia/Shanghai' ) );
		} catch ( Exception $exception ) {
			return new WP_Error( 'yi_tools_invalid_date', '日期无效。', array( 'status' => 400 ) );
		}

		if ( $datetime->format( 'Y-m-d' ) !== $date ) {
			return new WP_Error( 'yi_tools_invalid_date', '日期无效。', array( 'status' => 400 ) );
		}

		return self::build_for_date( $datetime );
	}

	public static function build_for_date( DateTimeImmutable $date ): array {
		$day      = ElementRules::day_ganzhi( $date );
		$element  = $day['element'];
		$elements = array(
			'lucky'    => ElementRules::GENERATES[ $element ],
			'secondary'=> $element,
			'neutral'  => ElementRules::controlled_by( $element ),
			'caution'  => ElementRules::generated_by( $element ),
			'avoid'    => ElementRules::CONTROLS[ $element ],
		);

		return array(
			'date'          => $date->format( 'Y-m-d' ),
			'display_date'  => $date->format( 'Y年n月j日' ),
			'weekday'       => self::weekday_label( $date ),
			'lunar_date'    => self::lunar_label( $date ),
			'ganzhi_year'   => self::year_ganzhi_label( $date ),
			'ganzhi_month'  => '按节气月令生成，首期暂不展示',
			'ganzhi_day'    => $day['ganzhi'],
			'day_branch'    => $day['branch'],
			'day_element'   => $element,
			'season'        => self::season( $date ),
			'elements'      => $elements,
			'colors'        => array(
				'lucky'     => self::COLORS[ $elements['lucky'] ],
				'secondary' => self::COLORS[ $elements['secondary'] ],
				'neutral'   => self::COLORS[ $elements['neutral'] ],
				'caution'   => self::COLORS[ $elements['caution'] ],
				'avoid'     => self::COLORS[ $elements['avoid'] ],
			),
			'advice'        => self::advice_for( $element, $elements['lucky'] ),
			'disclaimer'    => '本站内容基于传统文化、民俗资料与生活参考整理，仅供娱乐和文化参考，不构成现实决策依据。',
			'method_note'   => '首期按常见万年历干支口径，以日地支五行生成穿衣颜色建议。',
		);
	}

	private static function weekday_label( DateTimeImmutable $date ): string {
		$labels = array( '星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六' );

		return $labels[ (int) $date->format( 'w' ) ];
	}

	private static function season( DateTimeImmutable $date ): string {
		$month = (int) $date->format( 'n' );

		return match ( true ) {
			$month >= 3 && $month <= 5 => 'spring',
			$month >= 6 && $month <= 8 => 'summer',
			$month >= 9 && $month <= 11 => 'autumn',
			default => 'winter',
		};
	}

	private static function lunar_label( DateTimeImmutable $date ): string {
		if ( ! class_exists( IntlDateFormatter::class ) ) {
			return '农历展示需服务器 IntlDateFormatter 支持';
		}

		$formatter = new IntlDateFormatter(
			'zh_CN@calendar=chinese',
			IntlDateFormatter::LONG,
			IntlDateFormatter::NONE,
			'Asia/Shanghai',
			IntlDateFormatter::TRADITIONAL,
			'MMM d'
		);

		$label = $formatter->format( $date );

		return is_string( $label ) && '' !== $label ? $label : '农历暂不可用';
	}

	private static function year_ganzhi_label( DateTimeImmutable $date ): string {
		$year  = (int) $date->format( 'Y' );
		$index = ( $year - 4 ) % 60;

		if ( $index < 0 ) {
			$index += 60;
		}

		return ElementRules::STEMS[ $index % 10 ] . ElementRules::BRANCHES[ $index % 12 ] . '年';
	}

	private static function advice_for( string $day_element, string $lucky_element ): string {
		$color_names = implode( '、', array_column( self::COLORS[ $lucky_element ], 'name' ) );

		return sprintf(
			'今日日五行为%s，优先选择%s系颜色。通勤可用主色做上装或外套，日常可用配饰、小面积单品呼应，保持整体清爽即可。',
			$day_element,
			$color_names
		);
	}
}
