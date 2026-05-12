<?php
declare(strict_types=1);

namespace YiToolsCore\Cli;

use DateTimeImmutable;
use DateTimeZone;
use WP_CLI;
use YiToolsCore\Wuxing\ClothingColors;
use YiToolsCore\Wuxing\ColorMarkup;

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
		update_post_meta( $post_id, '_yi_wuxing_day_element', $result['day_element'] );
		update_post_meta( $post_id, '_yi_wuxing_ganzhi_day', $result['ganzhi_day'] );
		update_post_meta( $post_id, '_yi_wuxing_feature_caption', self::feature_caption( $result ) );
		update_post_meta( $post_id, '_yi_wuxing_image_prompt', self::image_prompt( $result ) );

		if ( isset( $assoc_args['image-attachment-id'] ) ) {
			$attachment_id = absint( $assoc_args['image-attachment-id'] );

			if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				WP_CLI::error( 'image-attachment-id 不是有效附件 ID。' );
			}

			set_post_thumbnail( $post_id, $attachment_id );
		}

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
			'%s五行穿衣指南：%s大吉色%s',
			$result['display_date'],
			self::relative_label( $result['date'] ),
			self::color_names( $result['colors']['lucky'] )
		);

		return array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => 'wuxing-chuanyi-' . $result['date'],
			'post_excerpt' => sprintf(
				'%s五行穿衣参考：%s，日五行为%s，%s大吉色%s，次吉色%s。',
				$result['display_date'],
				$result['ganzhi_day'],
				$result['day_element'],
				self::relative_label( $result['date'] ),
				self::color_names( $result['colors']['lucky'] ),
				self::color_names( $result['colors']['secondary'] )
			),
			'post_content' => $this->build_post_content( $result ),
		);
	}

	private function build_post_content( array $result ): string {
		$tool_url  = add_query_arg( 'date', $result['date'], home_url( '/wuxing-chuanyi/' ) );
		$today_url = home_url( '/wuxing-chuanyi/' );
		$label     = self::relative_label( $result['date'] );

		ob_start();
		?>
		<article class="yi-daily-post yi-daily-post--wuxing">
			<section class="yi-daily-intro" aria-label="<?php echo esc_attr( $label ); ?>五行穿衣概览">
				<p><?php echo esc_html( $result['display_date'] ); ?>是<?php echo esc_html( $result['weekday'] ); ?>，<?php echo esc_html( $result['ganzhi_day'] ); ?>，日五行为<?php echo esc_html( $result['day_element'] ); ?>。</p>
				<div class="yi-daily-lead" role="note" aria-label="今日穿衣重点">
					<?php echo wp_kses_post( $this->render_lead_row( 'lucky', $label . '大吉色', ColorMarkup::tokens( $result['colors']['lucky'], $result['elements']['lucky'] ) ) ); ?>
					<?php echo wp_kses_post( $this->render_lead_row( 'secondary', '次吉色可选', ColorMarkup::tokens( $result['colors']['secondary'], $result['elements']['secondary'] ) ) ); ?>
					<?php echo wp_kses_post( $this->render_lead_row( 'avoid', '少穿这些色', esc_html( self::color_names( $result['colors']['avoid'] ) ) . '不建议大面积使用。' ) ); ?>
				</div>
			</section>

			<section class="yi-daily-answer" aria-labelledby="yi-daily-answer-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-answer-<?php echo esc_attr( $result['date'] ); ?>"><span class="yi-section-icon" aria-hidden="true">✦</span><?php echo esc_html( $label ); ?>颜色速查</h2>
				<div class="yi-daily-color-list">
					<?php echo wp_kses_post( $this->render_color_group( 'lucky', '大吉色', '适合做上衣、外套或整体主色。', $result['elements']['lucky'], $result['colors']['lucky'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'secondary', '次吉色', '适合通勤、会议和日常搭配。', $result['elements']['secondary'], $result['colors']['secondary'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'neutral', '平平色', '可以少量使用，做裤装、鞋包也可以。', $result['elements']['neutral'], $result['colors']['neutral'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'caution', '慎用色', '少做全身主色，配饰点到即可。', $result['elements']['caution'], $result['colors']['caution'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'avoid', '不宜色', '尽量减少大面积使用。', $result['elements']['avoid'], $result['colors']['avoid'] ) ); ?>
				</div>
			</section>

			<section aria-labelledby="yi-daily-scenes-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-scenes-<?php echo esc_attr( $result['date'] ); ?>"><span class="yi-section-icon" aria-hidden="true">☘</span>简单搭配建议</h2>
				<div class="yi-scene-grid">
					<?php echo wp_kses_post( $this->render_scene_card( '💼', '通勤', sprintf( '%s上衣配%s下装，稳妥不突兀。', self::color_names( array_slice( $result['colors']['lucky'], 0, 2 ) ), self::color_names( array_slice( $result['colors']['secondary'], 0, 2 ) ) ) ) ); ?>
					<?php echo wp_kses_post( $this->render_scene_card( '🤝', '见面', '把大吉色放在靠近脸部的位置，比如衬衫、外套、围巾或领带。' ) ); ?>
					<?php echo wp_kses_post( $this->render_scene_card( '☕', '休闲', '不必全身同色，用包、鞋、袜子小面积呼应即可。' ) ); ?>
				</div>
			</section>

			<div class="yi-daily-tool-cta">
				<p>以上内容按常见万年历干支口径整理，仅作传统文化和日常穿搭参考。想查其它日期，可以直接使用五行穿衣工具。</p>
				<div class="yi-daily-tool-actions"><a class="yi-button" href="<?php echo esc_url( $tool_url ); ?>">查询 <?php echo esc_html( $result['display_date'] ); ?>穿衣颜色</a><a class="yi-button yi-button--ghost" href="<?php echo esc_url( $today_url ); ?>">打开今日五行穿衣工具</a></div>
			</div>
		</article>
		<?php

		return trim( (string) ob_get_clean() );
	}

	private function render_lead_row( string $tone, string $label, string $content_html ): string {
		return sprintf(
			'<p class="yi-daily-lead__row" data-tone="%s"><span class="yi-daily-lead__label">%s</span><span class="yi-daily-lead__content">%s</span></p>',
			esc_attr( $tone ),
			esc_html( $label ),
			$content_html
		);
	}

	private function render_color_group( string $level, string $title, string $description, string $element, array $colors ): string {
		return sprintf(
			'<div class="yi-daily-color-row" data-level="%s" data-element="%s"><div class="yi-daily-color-row__head"><span class="yi-daily-color-row__label"><span class="yi-daily-color-row__badge" aria-hidden="true">%s</span>%s</span><strong><span class="yi-daily-color-row__element-badge" aria-hidden="true">%s</span>%s</strong></div><div class="yi-daily-color-row__body">%s<p>%s</p></div></div>',
			esc_attr( $level ),
			esc_attr( $element ),
			esc_html( self::level_icon( $level ) ),
			esc_html( $title ),
			esc_html( ColorMarkup::element_icon( $element ) ),
			esc_html( $element . '系' ),
			ColorMarkup::tokens( $colors, $element ),
			esc_html( $description )
		);
	}

	private function render_scene_card( string $icon, string $title, string $description ): string {
		return sprintf(
			'<div class="yi-scene-card"><strong><span class="yi-scene-card__icon" aria-hidden="true">%s</span><span>%s</span></strong><p>%s</p></div>',
			esc_html( $icon ),
			esc_html( $title ),
			esc_html( $description )
		);
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
		return ColorMarkup::names( $colors );
	}

	private static function level_icon( string $level ): string {
		return match ( $level ) {
			'lucky' => '✦',
			'secondary' => '◐',
			'neutral' => '◌',
			'caution' => '△',
			'avoid' => '✕',
			default => '•',
		};
	}

	private static function relative_label( string $date ): string {
		$timezone = new DateTimeZone( 'Asia/Shanghai' );
		$today    = ( new DateTimeImmutable( 'now', $timezone ) )->format( 'Y-m-d' );
		$tomorrow = ( new DateTimeImmutable( 'tomorrow', $timezone ) )->format( 'Y-m-d' );

		return match ( $date ) {
			$today    => '今日',
			$tomorrow => '明日',
			default   => '当日',
		};
	}

	private static function element_phrase( string $element ): string {
		return match ( $element ) {
			'木' => '绿青系',
			'火' => '红粉紫系',
			'土' => '黄咖棕系',
			'金' => '白银灰系',
			'水' => '黑蓝系',
			default => $element . '系',
		};
	}

	private static function feature_caption( array $result ): string {
		return sprintf(
			'%s穿衣颜色参考，主色可优先选%s。',
			self::relative_label( $result['date'] ),
			self::element_phrase( $result['elements']['lucky'] )
		);
	}

	private static function image_prompt( array $result ): string {
		return sprintf(
			'高质量生活方式摄影，春夏通勤穿搭平铺静物，主色为%s，包含衬衫、薄外套、长裤、休闲鞋、帆布包、布料样本，干净自然光，真实织物质感，男女通用，中性实用，留出轻微留白，适合中文网站文章封面，16:9，无文字、无水印、无符号。',
			self::color_names( $result['colors']['lucky'] )
		);
	}
}
