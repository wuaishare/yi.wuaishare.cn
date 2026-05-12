<?php
declare(strict_types=1);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<span>吾爱易学</span>
		<small>传统文化与生活参考工具</small>
	</a>
	<nav class="site-nav" aria-label="主导航">
		<a href="<?php echo esc_url( home_url( '/wuxing-chuanyi/' ) ); ?>">五行穿衣</a>
		<a href="<?php echo esc_url( home_url( '/learn/' ) ); ?>">学习路线</a>
		<a href="<?php echo esc_url( home_url( '/knowledge/' ) ); ?>">知识库</a>
	</nav>
</header>
<main id="content" class="site-main">
