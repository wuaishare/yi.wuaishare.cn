<?php
declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-content' ); ?>>
		<?php if ( ! has_shortcode( (string) get_the_content(), 'yi_wuxing_clothing' ) ) : ?>
			<header class="page-header">
				<h1><?php the_title(); ?></h1>
			</header>
		<?php endif; ?>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
