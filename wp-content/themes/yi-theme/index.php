<?php
declare(strict_types=1);

get_header();
?>
<section class="home-hero">
	<p class="eyebrow">吾爱易学工具站</p>
	<h1>五行穿衣、周易八字与传统文化工具</h1>
	<p>先从每日五行穿衣查询开始，把民俗知识做成可查询、可复访、边界清楚的实用工具。</p>
	<div class="home-actions">
		<a class="yi-button" href="<?php echo esc_url( home_url( '/wuxing-chuanyi/' ) ); ?>">查询今日穿衣颜色</a>
		<a class="yi-button yi-button--ghost" href="<?php echo esc_url( home_url( '/learn/' ) ); ?>">进入学习路线</a>
	</div>
</section>

<?php
$daily_query = new WP_Query(
	array(
		'category_name'  => 'wuxing-chuanyi',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	)
);
?>
<section class="home-daily" aria-label="每日五行穿衣">
	<div>
		<p class="eyebrow">每日五行穿衣</p>
		<h2>先查今天，再读每日说明</h2>
	</div>
	<div class="home-daily__actions">
		<a class="yi-button" href="<?php echo esc_url( home_url( '/wuxing-chuanyi/' ) ); ?>">打开五行穿衣工具</a>
		<?php if ( $daily_query->have_posts() ) : ?>
			<?php
			$daily_query->the_post();
			?>
			<a class="yi-button yi-button--ghost" href="<?php the_permalink(); ?>">阅读最新日更</a>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	</div>
</section>

<?php if ( have_posts() ) : ?>
	<section class="post-list" aria-label="最新内容">
		<h2>最新内容</h2>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'post-card' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="post-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
						<?php the_post_thumbnail( 'medium' ); ?>
					</a>
				<?php endif; ?>
				<div class="post-card__body">
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php
					$post_lucky_colors = (string) get_post_meta( get_the_ID(), '_yi_wuxing_lucky_colors', true );
					if ( $post_lucky_colors ) :
						?>
						<p class="post-card__meta">大吉色：<?php echo esc_html( $post_lucky_colors ); ?></p>
					<?php endif; ?>
					<div class="post-card__excerpt"><?php the_excerpt(); ?></div>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</section>
<?php endif; ?>
<?php
get_footer();
