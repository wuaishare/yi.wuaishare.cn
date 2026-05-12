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

<?php if ( have_posts() ) : ?>
	<section class="post-list" aria-label="最新内容">
		<h2>最新内容</h2>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'post-card' ); ?>>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<div class="post-card__excerpt"><?php the_excerpt(); ?></div>
			</article>
			<?php
		endwhile;
		?>
	</section>
<?php endif; ?>
<?php
get_footer();
