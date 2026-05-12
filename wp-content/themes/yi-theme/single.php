<?php
declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-content article-content' ); ?>>
		<header class="page-header yi-entry-header">
			<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php
			$wuxing_date        = (string) get_post_meta( get_the_ID(), '_yi_wuxing_date', true );
			$wuxing_day_element = (string) get_post_meta( get_the_ID(), '_yi_wuxing_day_element', true );
			$wuxing_ganzhi_day  = (string) get_post_meta( get_the_ID(), '_yi_wuxing_ganzhi_day', true );
			$wuxing_lucky       = (string) get_post_meta( get_the_ID(), '_yi_wuxing_lucky_colors', true );
			?>
			<?php if ( $wuxing_date || $wuxing_day_element || $wuxing_lucky ) : ?>
				<div class="yi-entry-meta" aria-label="五行穿衣文章信息">
					<?php if ( $wuxing_date ) : ?>
						<span><?php echo esc_html( $wuxing_date ); ?></span>
					<?php endif; ?>
					<?php if ( $wuxing_ganzhi_day ) : ?>
						<span><?php echo esc_html( $wuxing_ganzhi_day ); ?></span>
					<?php endif; ?>
					<?php if ( $wuxing_day_element ) : ?>
						<span>日五行：<?php echo esc_html( $wuxing_day_element ); ?></span>
					<?php endif; ?>
					<?php if ( $wuxing_lucky ) : ?>
						<span>大吉色：<?php echo esc_html( $wuxing_lucky ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="yi-entry-figure">
				<?php the_post_thumbnail( 'large' ); ?>
				<?php
				$thumbnail_id = get_post_thumbnail_id();
				$caption      = $thumbnail_id ? wp_get_attachment_caption( $thumbnail_id ) : '';
				$caption      = $caption ?: (string) get_post_meta( get_the_ID(), '_yi_wuxing_feature_caption', true );
				if ( $caption ) :
					?>
					<figcaption><?php echo esc_html( $caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>
		<div class="entry-content">
			<?php
			if ( $wuxing_date ) {
				remove_filter( 'the_content', 'wpautop' );
				the_content();
				add_filter( 'the_content', 'wpautop' );
			} else {
				the_content();
			}
			?>
		</div>
		<?php
		if ( $wuxing_date || has_category( 'wuxing-chuanyi' ) || has_tag( '今日五行穿衣' ) ) :
			$tool_url = $wuxing_date ? add_query_arg( 'date', $wuxing_date, home_url( '/wuxing-chuanyi/' ) ) : home_url( '/wuxing-chuanyi/' );
			?>
			<aside class="yi-article-cta" aria-label="五行穿衣工具">
				<p class="eyebrow">五行穿衣工具</p>
				<h2>查询其它日期的穿衣颜色</h2>
				<p>每日文章用于解释当日颜色，工具页可快速查询今天、明天或指定日期的五行穿衣参考。</p>
				<div class="home-actions">
					<a class="yi-button" href="<?php echo esc_url( $tool_url ); ?>">打开对应日期</a>
					<a class="yi-button yi-button--ghost" href="<?php echo esc_url( home_url( '/wuxing-chuanyi/' ) ); ?>">回到今日查询</a>
				</div>
			</aside>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
