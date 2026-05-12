<?php
declare(strict_types=1);

namespace YiToolsCore\Wuxing;

final class ColorMarkup {
	public static function tokens( array $colors, string $element = '' ): string {
		$output = '<span class="yi-color-tokens">';

		foreach ( $colors as $color ) {
			$name      = (string) $color['name'];
			$hex       = (string) $color['hex'];
			$text_hex  = isset( $color['text_hex'] ) ? (string) $color['text_hex'] : $hex;
			$aria_text = $element ? sprintf( '%s，%s系颜色', $name, $element ) : $name;

			$output .= sprintf(
				'<span class="yi-color-token" data-color-name="%s" data-element="%s" style="--yi-color: %s; --yi-color-text: %s;" aria-label="%s"><span class="yi-color-token__swatch" aria-hidden="true"></span><span class="yi-color-token__name">%s</span></span>',
				esc_attr( $name ),
				esc_attr( $element ),
				esc_attr( $hex ),
				esc_attr( $text_hex ),
				esc_attr( $aria_text ),
				esc_html( $name )
			);
		}

		$output .= '</span>';

		return $output;
	}

	public static function names( array $colors ): string {
		return implode( '、', array_column( $colors, 'name' ) );
	}

	public static function element_icon( string $element ): string {
		return match ( $element ) {
			'木' => '🌿',
			'火' => '🔥',
			'土' => '⛰',
			'金' => '💠',
			'水' => '💧',
			default => '•',
		};
	}

	public static function element_slug( string $element ): string {
		return match ( $element ) {
			'木' => 'wood',
			'火' => 'fire',
			'土' => 'earth',
			'金' => 'metal',
			'水' => 'water',
			default => 'default',
		};
	}
}
