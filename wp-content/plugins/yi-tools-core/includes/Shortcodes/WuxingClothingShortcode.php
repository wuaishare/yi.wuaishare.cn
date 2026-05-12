<?php
declare(strict_types=1);

namespace YiToolsCore\Shortcodes;

use YiToolsCore\Wuxing\ClothingColors;

final class WuxingClothingShortcode {
	public static function register(): void {
		add_shortcode( 'yi_wuxing_clothing', array( self::class, 'render' ) );
	}

	public static function render( array $atts = array() ): string {
		$raw_date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : wp_date( 'Y-m-d' );
		$result   = ClothingColors::build_for_date_string( $raw_date );
		$notice   = '';

		if ( is_wp_error( $result ) ) {
			$notice = $result->get_error_message() . ' 已显示今日结果。';
			$result = ClothingColors::build_for_date_string( wp_date( 'Y-m-d' ) );
		}

		if ( is_wp_error( $result ) ) {
			return '<p>五行穿衣工具暂不可用。</p>';
		}

		$previous = gmdate( 'Y-m-d', strtotime( $result['date'] . ' -1 day' ) );
		$next     = gmdate( 'Y-m-d', strtotime( $result['date'] . ' +1 day' ) );
		$today    = wp_date( 'Y-m-d' );

		ob_start();
		?>
		<section class="yi-wuxing-tool" data-yi-tool="wuxing-clothing">
			<div class="yi-wuxing-hero yi-season-<?php echo esc_attr( $result['season'] ); ?>">
				<div class="yi-wuxing-hero__body">
					<p class="yi-wuxing-kicker">五行穿衣指南</p>
					<h1>
						<span class="yi-wuxing-date-title"><?php echo esc_html( $result['display_date'] ); ?></span>
						<span>穿衣颜色参考</span>
					</h1>
					<p class="yi-wuxing-summary">
						<?php echo esc_html( $result['weekday'] ); ?>，<?php echo esc_html( $result['ganzhi_day'] ); ?>，日五行为<?php echo esc_html( $result['day_element'] ); ?>。
					</p>
					<div class="yi-wuxing-actions">
						<a class="yi-button yi-button--ghost yi-button--prev" href="<?php echo esc_url( add_query_arg( 'date', $previous ) ); ?>">前一天</a>
						<form class="yi-date-form" method="get">
							<label for="yi-wuxing-date">选择日期</label>
							<input id="yi-wuxing-date" type="date" name="date" value="<?php echo esc_attr( $result['date'] ); ?>">
							<button class="yi-button" type="submit">查询</button>
						</form>
						<a class="yi-button yi-button--ghost yi-button--next" href="<?php echo esc_url( add_query_arg( 'date', $next ) ); ?>">后一天</a>
						<a class="yi-button yi-button--plain yi-button--today" href="<?php echo esc_url( add_query_arg( 'date', $today ) ); ?>">回到今天</a>
					</div>
				</div>
				<div class="yi-wuxing-hero__panel" aria-label="今日大吉色">
					<span>大吉色</span>
					<strong><?php echo esc_html( implode( '、', array_column( $result['colors']['lucky'], 'name' ) ) ); ?></strong>
					<?php self::render_swatches( $result['colors']['lucky'] ); ?>
				</div>
			</div>

			<?php if ( '' !== $notice ) : ?>
				<p class="yi-tool-alert" role="status"><?php echo esc_html( $notice ); ?></p>
			<?php endif; ?>

			<div class="yi-wuxing-facts" aria-label="日期信息">
				<?php self::render_fact( '公历', $result['display_date'] ); ?>
				<?php self::render_fact( '农历', $result['lunar_date'] ); ?>
				<?php self::render_fact( '星期', $result['weekday'] ); ?>
				<?php self::render_fact( '年干支', $result['ganzhi_year'] ); ?>
				<?php self::render_fact( '日干支', $result['ganzhi_day'] ); ?>
				<?php self::render_fact( '日五行', $result['day_element'] ); ?>
			</div>

			<div class="yi-color-grid" aria-label="穿衣颜色推荐">
				<?php self::render_color_card( '大吉色', '当日五行所生，适合优先使用。', $result['elements']['lucky'], $result['colors']['lucky'] ); ?>
				<?php self::render_color_card( '次吉色', '与当日五行相同，稳妥顺手。', $result['elements']['secondary'], $result['colors']['secondary'] ); ?>
				<?php self::render_color_card( '平平色', '颜色五行克当日五行，普通场景可少量使用。', $result['elements']['neutral'], $result['colors']['neutral'] ); ?>
				<?php self::render_color_card( '慎用色', '颜色五行生当日五行，容易被动消耗。', $result['elements']['caution'], $result['colors']['caution'] ); ?>
				<?php self::render_color_card( '不宜色', '当日五行克颜色五行，今日不建议作为主色。', $result['elements']['avoid'], $result['colors']['avoid'] ); ?>
			</div>

			<div class="yi-wuxing-advice">
				<h2>今日穿搭建议</h2>
				<p><?php echo esc_html( $result['advice'] ); ?></p>
			</div>

			<div class="yi-wuxing-notes">
				<p><?php echo esc_html( $result['method_note'] ); ?></p>
				<p><?php echo esc_html( $result['disclaimer'] ); ?></p>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_fact( string $label, string $value ): void {
		?>
		<div class="yi-fact">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	private static function render_color_card( string $title, string $description, string $element, array $colors ): void {
		?>
		<article class="yi-color-card">
			<div>
				<span class="yi-color-card__element"><?php echo esc_html( $element ); ?>系</span>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<strong><?php echo esc_html( implode( '、', array_column( $colors, 'name' ) ) ); ?></strong>
			<?php self::render_swatches( $colors ); ?>
		</article>
		<?php
	}

	private static function render_swatches( array $colors ): void {
		?>
		<div class="yi-swatches" aria-hidden="true">
			<?php foreach ( $colors as $color ) : ?>
				<span style="--yi-swatch: <?php echo esc_attr( $color['hex'] ); ?>"></span>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
