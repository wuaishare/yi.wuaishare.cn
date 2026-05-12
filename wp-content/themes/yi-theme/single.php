<?php
declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-content article-content' ); ?>>
		<header class="page-header">
			<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
			<h1><?php the_title(); ?></h1>
		</header>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
		<?php
		$wuxing_date = (string) get_post_meta( get_the_ID(), '_yi_wuxing_date', true );
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
