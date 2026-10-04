<?php

/**
 * Plugin Name:       klpsoft Feeds for WooCommerce
 * Plugin URI:        https://www.klp-soft.com/en/klpsoft-feeds-premium/
 * Description:       creates XML feeds for Google Shopping, Bing, idealo. with field mapping, filters, tabs, cron, and many options.
 * Version:           1.0.6
 * Author:            Klaus Plank
 * License:           GPL-2.0+
 * Requires Plugins:  woocommerce
 * Text Domain:       klpsoft-feeds
 */

// Copyright (C) 2026 klp-soft. All rights reserved.

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', function () {
    
    if ( !class_exists('WooCommerce') ) {
        return;
    }
    
    require_once plugin_dir_path(__FILE__) . 'includes/class-klpsoft-helper.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-feed-generator.php';
    require_once plugin_dir_path(__FILE__) . 'includes/ajax/class-ajax-handler.php';

    if ( is_admin() ) {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-menu.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-page-channel-google.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-page-channel-bing.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-page-channel-idealo.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-page-settings.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-section-filters.php';
        
        new Klpsoft\Feeds\KLPSFEFO_Feed_Admin_Menu();
        new Klpsoft\Feeds\KLPSFEFO_Feed_Ajax_Handler();
    }
    
    new Klpsoft\Feeds\KLPSFEFO_Feed_Generator();
});

/**
 * Activation hook
 */
function klpsfefo_feed_activate() {
    if ( !class_exists('WooCommerce') ) {
        wp_die( esc_html__( 'This plugin requires WooCommerce.', 'klpsoft-feeds' ), '', array( 'back_link' => true ) );
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-feed-generator.php';
    
    $klpsfefo_generator = new Klpsoft\Feeds\KLPSFEFO_Feed_Generator();
    $klpsfefo_generator->klpsfefo_register_route();

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'klpsfefo_feed_activate');

/**
 * Deactivation hook
 */
function klpsfefo_feed_deactivate() {
    wp_clear_scheduled_hook('klpsfefo_feed_cron_event');
    flush_rewrite_rules(false);
}

register_deactivation_hook(__FILE__, 'klpsfefo_feed_deactivate');
