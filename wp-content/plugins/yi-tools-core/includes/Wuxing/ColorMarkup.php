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
				'<span class="yi-color-token" style="--yi-color: %s; --yi-color-text: %s;" aria-label="%s"><span class="yi-color-token__swatch" aria-hidden="true"></span><span class="yi-color-token__name">%s</span></span>',
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
}
