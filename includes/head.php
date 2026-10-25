<?php
/**
 * Page head: take the tags and scripts most sites don't need out of the front end.
 *
 * Loaded only while the page head clean-up is switched on. Core adds all of
 * these before plugins load, so they can be removed straight away.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

// Emoji: the detection script and its styles, on pages and embeds.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'embed_head', 'print_emoji_detection_script' );
remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );

// Emoji in feeds and emails stay as text, rather than becoming images from WordPress.org.
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// The generator tag, which gives away the WordPress version, in pages and feeds.
add_filter( 'the_generator', '__return_empty_string' );

// The RSD link, used by old desktop publishing apps.
remove_action( 'wp_head', 'rsd_link' );

// The shortlink, in the head and the Link header.
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

// The oEmbed discovery links. Core hooks them twice for back-compat.
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
