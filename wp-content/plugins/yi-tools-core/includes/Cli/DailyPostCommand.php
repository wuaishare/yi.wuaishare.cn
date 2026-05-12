<?php
declare(strict_types=1);

namespace YiToolsCore\Cli;

use DateTimeImmutable;
use DateTimeZone;
use WP_CLI;
use YiToolsCore\Wuxing\ClothingColors;

final class DailyPostCommand {
	public function __invoke( array $args, array $assoc_args ): void {
		$date   = $this->normalize_date( isset( $assoc_args['date'] ) ? (string) $assoc_args['date'] : wp_date( 'Y-m-d' ) );
		$result = ClothingColors::build_for_date_string( $date );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$post_data = $this->build_post_data( $result );
		$dry_run   = array_key_exists( 'dry-run', $assoc_args );

		if ( $dry_run ) {
			WP_CLI::log( 'dry-run: 不会写入数据库。' );
			WP_CLI::log( 'title=' . $post_data['post_title'] );
			WP_CLI::log( 'slug=' . $post_data['post_name'] );
			WP_CLI::log( 'excerpt=' . $post_data['post_excerpt'] );
			return;
		}

		$existing = get_page_by_path( $post_data['post_name'], OBJECT, 'post' );
		$action   = $existing ? 'updated' : 'created';

		if ( $existing ) {
			$post_data['ID'] = (int) $existing->ID;
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id->get_error_message() );
		}

		$category_id = $this->ensure_category( '五行穿衣', 'wuxing-chuanyi' );
		wp_set_post_terms( $post_id, array( $category_id ), 'category', false );
		wp_set_post_terms(
			$post_id,
			array( '今日五行穿衣', '五行穿衣指南', '每日穿衣颜色', '今日穿衣颜色', '今日大吉色' ),
			'post_tag',
			false
		);

		update_post_meta( $post_id, '_yi_wuxing_date', $result['date'] );
		update_post_meta( $post_id, '_yi_wuxing_lucky_colors', self::color_names( $result['colors']['lucky'] ) );

		WP_CLI::success(
			sprintf(
				'%s post #%d: %s',
				$action,
				$post_id,
				get_permalink( $post_id )
			)
		);
	}

	private function normalize_date( string $date ): string {
		$timezone = new DateTimeZone( 'Asia/Shanghai' );

		return match ( $date ) {
			'today'     => ( new DateTimeImmutable( 'now', $timezone ) )->format( 'Y-m-d' ),
			'tomorrow'  => ( new DateTimeImmutable( 'tomorrow', $timezone ) )->format( 'Y-m-d' ),
			'yesterday' => ( new DateTimeImmutable( 'yesterday', $timezone ) )->format( 'Y-m-d' ),
			default     => $date,
		};
	}

	private function build_post_data( array $result ): array {
		$title = sprintf(
			'%s五行穿衣指南：当日大吉色%s',
			$result['display_date'],
			self::color_names( $result['colors']['lucky'] )
		);

		return array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => 'wuxing-chuanyi-' . $result['date'],
			'post_excerpt' => sprintf(
				'%s五行穿衣参考：%s，日五行为%s，大吉色%s，次吉色%s。',
				$result['display_date'],
				$result['ganzhi_day'],
				$result['day_element'],
				self::color_names( $result['colors']['lucky'] ),
				self::color_names( $result['colors']['secondary'] )
			),
			'post_content' => $this->build_post_content( $result ),
		);
	}

	private function build_post_content( array $result ): string {
		$tool_url  = add_query_arg( 'date', $result['date'], home_url( '/wuxing-chuanyi/' ) );
		$today_url = home_url( '/wuxing-chuanyi/' );

		ob_start();
		?>
		<div class="yi-daily-post yi-daily-post--wuxing">
			<p><?php echo esc_html( $result['display_date'] ); ?>是<?php echo esc_html( $result['weekday'] ); ?>，<?php echo esc_html( $result['ganzhi_day'] ); ?>，日五行为<?php echo esc_html( $result['day_element'] ); ?>。今天的穿衣颜色可以优先参考大吉色，再按场景选择次吉色或少量中性色。</p>

			<h2>当日穿衣颜色</h2>
			<ul>
				<li><strong>大吉色：</strong><?php echo esc_html( self::color_names( $result['colors']['lucky'] ) ); ?>，适合做上装、外套或整体主色。</li>
				<li><strong>次吉色：</strong><?php echo esc_html( self::color_names( $result['colors']['secondary'] ) ); ?>，适合通勤、见面和日常搭配。</li>
				<li><strong>平平色：</strong><?php echo esc_html( self::color_names( $result['colors']['neutral'] ) ); ?>，普通场景可少量使用。</li>
				<li><strong>慎用色：</strong><?php echo esc_html( self::color_names( $result['colors']['caution'] ) ); ?>，不建议作为全身主色。</li>
				<li><strong>不宜色：</strong><?php echo esc_html( self::color_names( $result['colors']['avoid'] ) ); ?>，今日尽量减少大面积使用。</li>
			</ul>

			<h2>今日搭配建议</h2>
			<p><?php echo esc_html( $result['advice'] ); ?></p>

			<div class="yi-daily-tool-cta">
				<p>想查看其它日期的穿衣颜色，可以直接进入五行穿衣工具查询。</p>
				<div class="yi-daily-tool-actions"><a class="yi-button" href="<?php echo esc_url( $tool_url ); ?>">查询这一天</a><a class="yi-button yi-button--ghost" href="<?php echo esc_url( $today_url ); ?>">查询今天</a></div>
			</div>

			<h2>参考说明</h2>
			<p><?php echo esc_html( $result['method_note'] ); ?></p>
			<p><?php echo esc_html( $result['disclaimer'] ); ?></p>
		</div>
		<?php

		return trim( (string) ob_get_clean() );
	}

	private function ensure_category( string $name, string $slug ): int {
		$term = get_term_by( 'slug', $slug, 'category' );

		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		$result = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		return (int) $result['term_id'];
	}

	private static function color_names( array $colors ): string {
		return implode( '、', array_column( $colors, 'name' ) );
	}
}
