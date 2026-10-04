<?php

// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', array($this, 'klpsfefo_add_menu_tabs'));
        add_action('admin_enqueue_scripts', array($this, 'klpsfefo_enqueue_admin_assets'));
        
        add_action('klpsfefo_feeds_render_filters', array($this, 'klp_render_filters'), 10, 3);
        // 'google', $current_feed_id, KLPSFEFO_Feed_Page_Channel_Google::getFeedData($current_feed_id, 'filters')
    }

    public function klpsfefo_add_menu_tabs() {
        if ( !current_user_can('manage_woocommerce') && !current_user_can('manage_options') ) {
            return;
        }

        $is_premium = apply_filters('klpsfefo_feeds_is_premium_active', false);

        if ( $is_premium ) {
            $title = __('klpsoft Feeds Premium', 'klpsoft-feeds');
        } else {
            $title = __('klpsoft Feeds', 'klpsoft-feeds');
        }

        add_menu_page(
                $title,
                $title,
                'manage_options',
                'klpsoft-feeds',
                array($this, 'klpsfefo_render_tabs_page'),
                'dashicons-share',
                56
        );
    }

    /**
     * tabs rendering
     */
    public function klpsfefo_render_tabs_page() {
        
        $is_premium = apply_filters('klpsfefo_feeds_is_premium_active', false);
        $is_premium_avail = apply_filters('klpsfefo_feeds_is_premium_available', false);
        
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'google';
        $current_feed_id = isset($_GET['feed_id']) ? absint($_GET['feed_id']) : 1;
        
        if ( !$is_premium && in_array($current_tab, array('meta', 'tiktok', 'googleapi')) ) {
            $current_tab = 'promo';
        }
        ?>
        <div class="wrap">
            <div class="klpsfefo-admin-header">
                <div class="klpsfefo-admin-title-container">
                    <h1 class="wp-heading-inline klp-admin-title">
                        klpsoft Feeds
                    </h1>

                    <?php if ( $is_premium ) : ?>
                        <span class="klpsfefo-badge klpsfefo-badge-premium">Premium</span>
                    <?php else : ?>
                        <span class="klpsfefo-badge klpsfefo-badge-free">Free</span>
                    <?php endif; ?>
                </div>

                <div class="klpsfefo-brand-logo">
                    <img src="<?php echo esc_url(plugins_url('../../assets/img/logo.jpg', __FILE__)); ?>" alt="klp-soft Logo">
                </div>
            </div>

            <hr class="wp-header-end klpsfefo-admin-divider">

            <nav class="nav-tab-wrapper" style="margin-bottom: 12px;">
                <a href="?page=klpsoft-feeds&tab=google" class="nav-tab <?php echo ($current_tab === 'google') ? 'nav-tab-active' : ''; ?>">
                    🔍 Google Shopping
                </a>
                
                <a href="?page=klpsoft-feeds&tab=bing" class="nav-tab <?php echo ($current_tab === 'bing') ? 'nav-tab-active' : ''; ?>">
                   🌐 Bing Shopping
                </a>
                
                <a href="?page=klpsoft-feeds&tab=idealo" class="nav-tab <?php echo ($current_tab === 'idealo') ? 'nav-tab-active' : ''; ?>">
                   🏷️ Idealo
                </a>
                
                <a href="?page=klpsoft-feeds&tab=meta" class="nav-tab <?php echo ($current_tab === 'meta') ? 'nav-tab-active' : ''; ?>" style="<?php echo ! $is_premium ? 'color: #999;' : ''; ?>">
                    👥 Facebook / Instagram <?php echo ! $is_premium ? '🔒' : ''; ?>
                </a>
                
                <a href="?page=klpsoft-feeds&tab=tiktok" class="nav-tab <?php echo ($current_tab === 'tiktok') ? 'nav-tab-active' : ''; ?>" style="<?php echo ! $is_premium ? 'color: #999;' : ''; ?>">
                    🎵 TikTok Feeds <?php echo ! $is_premium ? '🔒' : ''; ?>
                </a>
                
                <?php 
                do_action('klpsfefo_feeds_admin_nav_tabs', $current_tab, $is_premium); 
                ?>
                
                <a href="?page=klpsoft-feeds&tab=googleapi" class="nav-tab <?php echo ($current_tab === 'googleapi') ? 'nav-tab-active' : ''; ?>" style="<?php echo ! $is_premium ? 'color: #999;' : ''; ?>">
                    Google API Settings <?php echo ! $is_premium ? '🔒' : ''; ?>
                </a>
                
                <a href="?page=klpsoft-feeds&tab=settings" class="nav-tab <?php echo ($current_tab === 'settings') ? 'nav-tab-active' : ''; ?>">
                    ⚙️ <?php esc_html_e( 'Settings', 'klpsoft-feeds' ); ?>
                </a>

                <?php if ( !$is_premium ) : ?>
                    <a href="?page=klpsoft-feeds&tab=promo" class="nav-tab <?php echo ($current_tab === 'promo') ? 'nav-tab-active' : ''; ?>" style="background: #f39c12; color: #fff; border-color: #e67e22; font-weight: bold;">
                        🚀 Go Premium
                    </a>
                <?php else: ?>
                    <a href="?page=klpsoft-feeds&tab=promo" class="nav-tab <?php echo ($current_tab === 'promo') ? 'nav-tab-active' : ''; ?>" style="background: #f39c12; color: #fff; border-color: #e67e22; font-weight: bold;">
                         <?php esc_html_e('Your License', 'klpsoft-feeds'); ?>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="klpsfefo-tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'google':
                        KLPSFEFO_Feed_Page_Channel_Google::render();
                        break;
                    case 'googleapi':
                        do_action('klpsfefo_feeds_render_google_api');
                        break;
                    case 'bing':
                        KLPSFEFO_Feed_Page_Channel_Bing::render();
                        break;
                    case 'idealo':
                        KLPSFEFO_Feed_Page_Channel_Idealo::render();
                        break;
                    case 'meta':
                        do_action('klpsfefo_feeds_render_channel_meta');
                        break;
                    case 'tiktok':
                        do_action('klpsfefo_feeds_render_channel_tiktok');
                        break;
                    case 'settings':
                        KLPSFEFO_Feed_Page_Settings::render();
                        break;
                    case 'promo':
                    default:
                        if ( has_action('klpsfefo_feeds_render_channel_' . $current_tab) ) {
                            do_action('klpsfefo_feeds_render_channel_' . $current_tab, $current_tab);
                        } else {
                            $this->klpsfefo_renderPromoPage($is_premium, $is_premium_avail);
                        }
                        break;
                }
                ?>
            </div>
        </div>

        <div id="klpsfefo-ajax-wait-overlay" title="<?php esc_html_e('Please wait...', 'klpsoft-feeds'); ?>" style="display:none; text-align: center; padding: 20px;">
            <span class="spinner is-active" style="float: none; margin: 0 auto 15px auto; display: block;"></span>
            <p style="margin: 0; font-size: 14px; font-weight: 500;"><?php esc_html_e('Saving settings...', 'klpsoft-feeds'); ?></p>
        </div>

        <?php
            }

    public function klpsfefo_renderPromoPage($is_premium, $is_premium_avail) {
        
        if ( !$is_premium && !$is_premium_avail ) {
            ?>
            <div style="background: #fff; padding: 30px; border-radius: 6px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px;">
                <h2 style="margin-top: 0; color: #23282d;">🚀 <?php esc_html_e('Upgrade to klpsoft Feeds for WooCommerce - Premium', 'klpsoft-feeds'); ?></h2>
                <p style="font-size: 14px; line-height: 1.6; color: #555;">
                    <?php esc_html_e('Unlock the full power of your store. Premium features include Google realtime Merchant API, Analytics UTM tracking, more channels (Meta (Facebook/Insta) Catalogue, TikTok Feeds), and Google Product Category Mapping.', 'klpsoft-feeds'); ?>
                </p>
                <div style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #f39c12; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-weight: bold; color: #333;">
                        <?php esc_html_e('Don\'t have a license key yet?', 'klpsoft-feeds'); ?>
                    </p>
                    <p style="margin: 5px 0 0 0;">
                        <a href="https://www.klp-soft.com/klpsoft-feeds-premium/" target="_blank" class="button button-primary" style="background:#f39c12; border-color:#e67e22;">
                            <?php esc_html_e('Buy Premium Addon Now', 'klpsoft-feeds'); ?>
                        </a>
                    </p>
                </div>
            </div>
        <?php
            
        } else if ( $is_premium_avail == true ) {
            
            do_action('klpsfefo_feeds_render_promo_page');
        }
    }
    
    public function klp_render_filters($feedChannel, $feedId, $filters=array()) {
        
        KLPSFEFO_Feed_Section_Filters::render($feedChannel, $feedId, \Klpsoft\FeedsPremium\KLP_Woo_Feed_Premium_Channel::getFeedData($feedId, 'filters'));
        
    }
    
    public function klpsfefo_enqueue_admin_assets($hook) {
        if ( $hook !== 'toplevel_page_klpsoft-feeds' ) {
            return;
        }

        wp_enqueue_style('klpsfefo-admin-css', plugins_url('../../assets/css/admin.css', __FILE__), array(), '1.0.1');
        
        wp_enqueue_style('wp-jquery-ui-dialog');

        $custom_css = "
            .klpsfefo-no-close-dialog .ui-dialog-titlebar-close {
                display: none !important;
            }
            .ui-widget-overlay {
                background: rgba(0, 0, 0, 0.5) !important;
                opacity: 1 !important;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1001;
            }
            .ui-dialog {
                z-index: 1002;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                border: 1px solid #ccd0d4 !important;
            }
        ";
        
        wp_add_inline_style('wp-jquery-ui-dialog-in', $custom_css);
                
        wp_enqueue_script( 
            'klpsfefo-admin-js',
            plugins_url('../../assets/js/admin.js', __FILE__),
            array('jquery', 'jquery-ui-dialog'),
            '1.0.1',
            true
        );
        
        wp_localize_script('klpsfefo-admin-js', 'klpFeedSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('klpsfefo_feeds_settings_nonce'),
            'i18n'     => array(
                'dialog_title'    => __('New Shopping Feed', 'klpsoft-feeds'),
                'dialog_text'     => __('Enter a name for your new Shopping Feed:', 'klpsoft-feeds'),
                'btn_create'      => __('Create Feed', 'klpsoft-feeds'),
                'btn_cancel'      => __('Cancel', 'klpsoft-feeds'),
                'add_feed_btn'    => __('Add New Feed Profile', 'klpsoft-feeds'),
                'delete_feed_btn' => __('Delete Feed', 'klpsoft-feeds'),
                'confirm_delete_text'  => __('Are you sure you want to delete this feed profile? This action cannot be undone.', 'klpsoft-feeds'),
                'btn_delete_confirm'   => __('Yes, delete feed', 'klpsoft-feeds'),
                'invalid_feed_id_text' => __('Please select a valid feed profile to delete.', 'klpsoft-feeds'),
            ),
            'saving'   => __('Saving...', 'klpsoft-feeds'),
            'saved'    => __('Settings saved successfully!', 'klpsoft-feeds')
        ) );
    }
}