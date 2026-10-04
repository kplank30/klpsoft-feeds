<?php

// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Page_Settings {

    public static function render() {
        if ( !current_user_can('manage_woocommerce') && !current_user_can('manage_options') ) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'klpsoft-feeds'));
        }

        $general = get_option('klpsfefo_feeds_general_settings', array());

        $is_premium_licensed = apply_filters('klpsfefo_feeds_is_premium_active', false);

        $logging = $general['logging'] ?? 'off';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('General Settings', 'klpsoft-feeds'); ?></h1>

            <form id="klpsfefo-feed-settings-form">
                <?php wp_nonce_field('klpsfefo_feeds_settings_nonce', 'nonce'); ?>
                <input type="hidden" name="klpsfefo-general-settings" id="klpsfefo-general-settings" value="1" />

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="klpsfefo_feeds_logging"><?php esc_html_e('Logging on/off', 'klpsoft-feeds'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="klpsfefo_feeds_general_settings[logging]" id="klpsfefo_feeds_logging" value="on" <?php checked($logging, 'on'); ?> />
                            <label for="klpsfefo_feeds_logging"><?php esc_html_e('logs feed creation events in WooCommerce -> Status -> Logs.', 'klpsoft-feeds'); ?></label>
                        </td>
                    </tr>
                </table>

                <?php
                if ( $is_premium_licensed ) {
                    do_action('klpsfefo_feeds_render_category_mapping');
                } else {
                    self::render_google_cat_map_notice();
                }
                ?>

                <p class="submit">
                    <button class="button button-primary" id="klpsfefo-feed-save-btn"><?php esc_html_e('Save Settings', 'klpsoft-feeds'); ?></button>
                    <span id="klpsfefo-feed-status-msg" style="margin-left: 10px; font-weight: bold;"></span>
                </p>
            </form>
        </div>
        <?php
    }
    
    private static function render_google_cat_map_notice() {
        ?>
        <div class="klpsfefo-category-mapping-section" style="margin-top: 20px;">
            <h3><?php esc_html_e('Google Product category mapping', 'klpsoft-feeds'); ?></h3>
            <div class="notice notice-warning inline" style="margin-top: 20px; padding: 15px;">
                <h4>🔒 <?php esc_html_e('Google category mapping - Feature available only in Premium version', 'klpsoft-feeds'); ?></h4>
            </div>
        </div>
        <?php
    }
}
