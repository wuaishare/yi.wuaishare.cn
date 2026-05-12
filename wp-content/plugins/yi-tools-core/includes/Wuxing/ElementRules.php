<?php
declare(strict_types=1);

namespace YiToolsCore\Wuxing;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class ElementRules {
	public const ELEMENTS = array( '木', '火', '土', '金', '水' );

	public const GENERATES = array(
		'木' => '火',
		'火' => '土',
		'土' => '金',
		'金' => '水',
		'水' => '木',
	);

	public const CONTROLS = array(
		'木' => '土',
		'土' => '水',
		'水' => '火',
		'火' => '金',
		'金' => '木',
	);

	public const STEMS = array( '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸' );
	public const BRANCHES = array( '子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥' );

	public const BRANCH_ELEMENTS = array(
		'子' => '水',
		'亥' => '水',
		'寅' => '木',
		'卯' => '木',
		'巳' => '火',
		'午' => '火',
		'申' => '金',
		'酉' => '金',
		'辰' => '土',
		'戌' => '土',
		'丑' => '土',
		'未' => '土',
	);

	private const DAY_CYCLE_REFERENCE = '1900-01-31';
	private const DAY_CYCLE_REFERENCE_INDEX = 40; // 1900-01-31 -> 甲辰日; keeps 2026-05-12/13 at 丙戌/丁亥.

	public static function validate_element( string $element ): string {
		if ( ! in_array( $element, self::ELEMENTS, true ) ) {
			throw new InvalidArgumentException( 'Unknown wuxing element.' );
		}

		return $element;
	}

	public static function generated_by( string $element ): string {
		self::validate_element( $element );

		foreach ( self::GENERATES as $source => $target ) {
			if ( $target === $element ) {
				return $source;
			}
		}

		throw new InvalidArgumentException( 'Unable to resolve generating element.' );
	}

	public static function controlling( string $element ): string {
		self::validate_element( $element );

		return self::CONTROLS[ $element ];
	}

	public static function controlled_by( string $element ): string {
		self::validate_element( $element );

		foreach ( self::CONTROLS as $source => $target ) {
			if ( $target === $element ) {
				return $source;
			}
		}

		throw new InvalidArgumentException( 'Unable to resolve controlling element.' );
	}

	public static function day_ganzhi( DateTimeImmutable $date ): array {
		$timezone  = new DateTimeZone( 'Asia/Shanghai' );
		$target    = $date->setTimezone( $timezone )->setTime( 0, 0, 0 );
		$reference = new DateTimeImmutable( self::DAY_CYCLE_REFERENCE, $timezone );
		$days      = (int) $reference->diff( $target )->format( '%r%a' );
		$index     = ( self::DAY_CYCLE_REFERENCE_INDEX + $days ) % 60;

		if ( $index < 0 ) {
			$index += 60;
		}

		$stem   = self::STEMS[ $index % 10 ];
		$branch = self::BRANCHES[ $index % 12 ];

		return array(
			'stem'    => $stem,
			'branch'  => $branch,
			'ganzhi'  => $stem . $branch . '日',
			'element' => self::BRANCH_ELEMENTS[ $branch ],
		);
	}
}
