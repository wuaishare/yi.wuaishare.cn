<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		register_nav_menus(
			array(
				'primary' => '主导航',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'yi-theme',
			get_stylesheet_uri(),
			array(),
			'0.2.2'
		);
		wp_enqueue_style(
			'yi-theme-main',
			get_template_directory_uri() . '/assets/css/main.css',
			array( 'yi-theme' ),
			'0.2.2'
		);
	}
);
