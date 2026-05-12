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
				<p><?php echo esc_html( $result['display_date'] ); ?>是<?php echo esc_html( $result['weekday'] ); ?>，<?php echo esc_html( $result['ganzhi_day'] ); ?>，日五行为<?php echo esc_html( $result['day_element'] ); ?>。按照五行穿衣的传统参考，<?php echo esc_html( $label ); ?>可以优先选择<?php echo wp_kses_post( ColorMarkup::tokens( $result['colors']['lucky'], $result['elements']['lucky'] ) ); ?>这一组颜色，整体会比单纯照着衣柜随手拿更有方向感。</p>
				<p class="yi-daily-lead">如果你只是想快速出门，可以记住一句话：<?php echo esc_html( $label ); ?>主色优先选<?php echo esc_html( self::element_phrase( $result['elements']['lucky'] ) ); ?>，<?php echo esc_html( self::element_phrase( $result['elements']['secondary'] ) ); ?>适合做稳妥搭配，<?php echo esc_html( self::color_names( $result['colors']['avoid'] ) ); ?>不建议大面积使用。</p>
			</section>

			<section class="yi-daily-answer" aria-labelledby="yi-daily-answer-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-answer-<?php echo esc_attr( $result['date'] ); ?>"><?php echo esc_html( $label ); ?>先看这几个颜色</h2>
				<div class="yi-daily-color-list">
					<?php echo wp_kses_post( $this->render_color_group( 'lucky', '大吉色', '适合放在上装、外套、衬衫、针织衫或连衣裙的主视觉位置。', $result['elements']['lucky'], $result['colors']['lucky'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'secondary', '次吉色', '更稳妥，适合不想太张扬的通勤、会议和日常安排。', $result['elements']['secondary'], $result['colors']['secondary'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'neutral', '平平色', '可以作为裤装、鞋包或小面积中性色，不必完全避开。', $result['elements']['neutral'], $result['colors']['neutral'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'caution', '慎用色', '可以做衬底或配饰，不建议全身大面积铺开。', $result['elements']['caution'], $result['colors']['caution'] ) ); ?>
					<?php echo wp_kses_post( $this->render_color_group( 'avoid', '不宜色', '减少大面积使用，必要时放到小配饰上就好。', $result['elements']['avoid'], $result['colors']['avoid'] ) ); ?>
				</div>
			</section>

			<section aria-labelledby="yi-daily-reason-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-reason-<?php echo esc_attr( $result['date'] ); ?>">为什么<?php echo esc_html( $label ); ?>推荐<?php echo esc_html( self::element_phrase( $result['elements']['lucky'] ) ); ?></h2>
				<p><?php echo esc_html( $result['display_date'] ); ?>为<?php echo esc_html( $result['ganzhi_day'] ); ?>，<?php echo esc_html( $result['day_branch'] ); ?>属<?php echo esc_html( $result['day_element'] ); ?>，所以日五行为<?php echo esc_html( $result['day_element'] ); ?>。五行关系里，<?php echo esc_html( $result['day_element'] ); ?>生<?php echo esc_html( $result['elements']['lucky'] ); ?>，因此<?php echo esc_html( self::element_phrase( $result['elements']['lucky'] ) ); ?>被列为<?php echo esc_html( $label ); ?>的大吉色。</p>
				<p>这里说的适合，不是说穿了就一定发生什么，而是把传统五行关系转成一个更容易执行的颜色参考。真正穿衣时，还是要看天气、场合、肤色和自己的舒适度。</p>
			</section>

			<section aria-labelledby="yi-daily-scenes-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-scenes-<?php echo esc_attr( $result['date'] ); ?>">通勤、见面和休闲怎么穿</h2>
				<div class="yi-scene-grid">
					<?php echo wp_kses_post( $this->render_scene_card( '上班通勤', sprintf( '用%s做上衣、内搭或外套，下装配%s，会比全身同色更稳。', self::color_names( $result['colors']['lucky'] ), self::color_names( array_slice( $result['colors']['secondary'], 0, 2 ) ) ) ) ); ?>
					<?php echo wp_kses_post( $this->render_scene_card( '见面沟通', '把大吉色放在靠近脸部的位置，比如衬衫、围巾、领带、发饰或外套内搭，存在感更清楚。' ) ); ?>
					<?php echo wp_kses_post( $this->render_scene_card( '日常休闲', '颜色不用堆满全身，有一个明确主色就够了。包、鞋、袜子这些小面积单品也能呼应当天颜色。' ) ); ?>
				</div>
			</section>

			<section aria-labelledby="yi-daily-balance-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-balance-<?php echo esc_attr( $result['date'] ); ?>">如果必须穿不宜色怎么办</h2>
				<p><?php echo esc_html( self::color_names( $result['colors']['avoid'] ) ); ?>并不是绝对不能穿。更实际的做法是降低面积，比如只放在袜子、包挂、内搭边缘，别做整套主色。</p>
				<p>如果衣柜里刚好只有这类颜色，可以用<?php echo wp_kses_post( ColorMarkup::tokens( array_slice( $result['colors']['lucky'], 0, 2 ), $result['elements']['lucky'] ) ); ?>或<?php echo wp_kses_post( ColorMarkup::tokens( array_slice( $result['colors']['secondary'], 0, 2 ), $result['elements']['secondary'] ) ); ?>做配饰、外套或鞋包，视觉上会更平衡。</p>
			</section>

			<section aria-labelledby="yi-daily-table-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-table-<?php echo esc_attr( $result['date'] ); ?>">五行颜色怎么看</h2>
				<div class="yi-element-table" role="list">
					<?php foreach ( ClothingColors::COLORS as $element => $colors ) : ?>
						<div class="yi-element-table__row" role="listitem">
							<strong><?php echo esc_html( $element ); ?>系</strong>
							<?php echo wp_kses_post( ColorMarkup::tokens( $colors, (string) $element ) ); ?>
						</div>
					<?php endforeach; ?>
				</div>
				<p><?php echo esc_html( $label ); ?>为<?php echo esc_html( $result['day_element'] ); ?>日，<?php echo esc_html( $result['day_element'] ); ?>生<?php echo esc_html( $result['elements']['lucky'] ); ?>，所以<?php echo esc_html( self::element_phrase( $result['elements']['lucky'] ) ); ?>成为<?php echo esc_html( $label ); ?>大吉色；<?php echo esc_html( self::element_phrase( $result['elements']['secondary'] ) ); ?>与日五行同气，因此为次吉色。</p>
			</section>

			<div class="yi-daily-tool-cta">
				<p>想查明天、周末或某个指定日期，可以直接打开五行穿衣查询工具，选择日期后会自动显示大吉色、次吉色、慎用色和不宜色。</p>
				<div class="yi-daily-tool-actions"><a class="yi-button" href="<?php echo esc_url( $tool_url ); ?>">查询 <?php echo esc_html( $result['display_date'] ); ?>穿衣颜色</a><a class="yi-button yi-button--ghost" href="<?php echo esc_url( $today_url ); ?>">打开今日五行穿衣工具</a></div>
			</div>

			<section class="yi-daily-faq" aria-labelledby="yi-daily-faq-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-faq-<?php echo esc_attr( $result['date'] ); ?>">常见问题</h2>
				<details>
					<summary><?php echo esc_html( $label ); ?>穿什么颜色更好？</summary>
					<p><?php echo esc_html( $label ); ?>优先参考<?php echo esc_html( self::color_names( $result['colors']['lucky'] ) ); ?>，想稳妥一点可以搭配<?php echo esc_html( self::color_names( $result['colors']['secondary'] ) ); ?>。</p>
				</details>
				<details>
					<summary>五行穿衣可以当成确定结果吗？</summary>
					<p>不建议。本站把它作为传统文化、民俗资料和生活穿搭参考，不做确定性承诺。</p>
				</details>
				<details>
					<summary>不宜色一定不能穿吗？</summary>
					<p>不是。日常穿搭更看场合和舒适度。如果必须穿不宜色，减少面积，用包、鞋、围巾或内搭做小范围点缀即可。</p>
				</details>
			</section>

			<section class="yi-daily-notes" aria-labelledby="yi-daily-notes-<?php echo esc_attr( $result['date'] ); ?>">
				<h2 id="yi-daily-notes-<?php echo esc_attr( $result['date'] ); ?>">参考说明</h2>
				<p>本文按常见万年历干支口径，以日地支五行和五行相生相克关系整理穿衣颜色建议。不同流派可能存在细节差异，本站统一作为传统文化和生活穿搭参考。</p>
				<p><?php echo esc_html( $result['disclaimer'] ); ?>实际穿搭请结合天气、场合要求、职业规范、个人舒适度和审美偏好。</p>
			</section>
		</article>
		<?php

		return trim( (string) ob_get_clean() );
	}

	private function render_color_group( string $level, string $title, string $description, string $element, array $colors ): string {
		return sprintf(
			'<div class="yi-daily-color-row" data-level="%s" data-element="%s"><div><span class="yi-daily-color-row__label">%s</span><strong>%s</strong></div><div>%s<p>%s</p></div></div>',
			esc_attr( $level ),
			esc_attr( $element ),
			esc_html( $title ),
			esc_html( $element . '系' ),
			ColorMarkup::tokens( $colors, $element ),
			esc_html( $description )
		);
	}

	private function render_scene_card( string $title, string $description ): string {
		return sprintf(
			'<div class="yi-scene-card"><strong>%s</strong><p>%s</p></div>',
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
			'%s推荐以%s为主，可用低饱和颜色降低搭配难度。',
			self::relative_label( $result['date'] ),
			self::element_phrase( $result['elements']['lucky'] )
		);
	}

	private static function image_prompt( array $result ): string {
		return sprintf(
			'春夏清爽通勤穿搭平铺图，主色为%s，包含衬衫、外套、长裤、休闲鞋、色卡、布料样本，男女通用，中性实用，不出现文字，适合中文网站文章封面，16:9。',
			self::color_names( $result['colors']['lucky'] )
		);
	}
}
