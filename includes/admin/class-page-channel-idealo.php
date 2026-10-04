<?php

// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Page_Channel_Idealo {

    private static $all_feeds_data = array();
    
    private static function getFeedData($feedId, $type='mappings') {
        if ( !isset(self::$all_feeds_data[$feedId][$type]) ) {
            return array();
        }
        return self::$all_feeds_data[$feedId][$type];
    }
    
    public static function render() {
        if ( !current_user_can('manage_woocommerce') && !current_user_can('manage_options') ) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'klpsoft-feeds'));
        }

        self::$all_feeds_data = get_option('klpsfefo_feeds_idealo', array());

        $current_feed_id = 0;
        if ( !isset($_GET['feed_id']) ) {
            foreach ( self::$all_feeds_data as $id => $feed ) {
                $current_feed_id = $id;
                break;
            }
            if ( $current_feed_id == 0 ) {
                $current_feed_id = 1;
            }
        } else {
            $current_feed_id = isset($_GET['feed_id']) ? absint($_GET['feed_id']) : 1;
            if ( !isset( self::$all_feeds_data[$current_feed_id] ) ) {
                $current_feed_id = 1;
            }
        }

        $feed_data = $mappings = array();
        $file_hash = $file_url = $stream_url = '';
        $feed_status = 'active';
        $feed_type = 'file';
        $cron_interval = '24';
        $variants_status = 'no';
        $variants_default_status = 'no';
        $is_table_open = '1';
        $open_attribute = 'open';
        $export_shipping = 'yes';
        
        if ( !empty(self::$all_feeds_data) ) {

            $feed_data       = self::$all_feeds_data[$current_feed_id];
            $file_hash       = $feed_data['file_hash'] ?? '';
            $mappings        = $feed_data['mappings'] ?? array();
            $feed_status     = $feed_data['feed_status'] ?? 'active';
            $feed_type       = $feed_data['feed_type'] ?? 'file';
            $cron_interval   = $feed_data['cron_interval'] ?? '24';
            $variants_status = $feed_data['export_variants'] ?? 'no';
            $variants_default_status = $feed_data['export_variants_default'] ?? 'no';
            $export_shipping = $feed_data['export_shipping'] ?? 'yes';

            $is_table_open = $feed_data['mapping_tbl_open'] ?? '1'; 
            $open_attribute = ($is_table_open == '1') ? 'open' : '';
            
            $upload_dir = wp_upload_dir();
            $file_url   = $upload_dir['baseurl'] . '/klp-feeds-csv/idealo-feed-' . $file_hash . '.csv';
            $stream_url = site_url('?klpsfefo_shopping_feed=1&channel=idealo&feed_id=' . $current_feed_id);
        }
        
        $is_premium = apply_filters('klpsfefo_feeds_is_premium_active', false);

        ?>
        <div class="wrap">
            <h2>Idealo</h2>

            <form id="klpsfefo-feed-settings-form">
                <div class="klpsfefo-feed-selector-wrapper" style="background:#fff; padding:15px; border:1px solid #ccd0d4; border-radius:4px;">

                    <?php if ( empty(self::$all_feeds_data) ) : ?>
                        <label style="font-weight:bold; font-size:14px;" for="klpsfefo_feedname"><?php esc_html_e('New Feed Profile:', 'klpsoft-feeds'); ?> </label>
                        <input type="text" name="klpsfefo_feeds_meta[new_feedname]" id="klpsfefo_feedname" value="<?php echo esc_attr($feed_data['name'] ?? ''); ?>" class="regular-text" style=" " maxlength="40" />
                    <?php else: ?>
                        <label style="font-weight:bold; font-size:14px;" for="klpsfefo-active-feed-select"><?php esc_html_e('Select Feed Profile:', 'klpsoft-feeds'); ?> </label>
                        <select id="klpsfefo-active-feed-select" style="min-width: 250px;">
                            <?php foreach ( self::$all_feeds_data as $id => $feed ) : ?>
                                <option value="<?php echo esc_attr($id); ?>" <?php selected($current_feed_id, $id); ?>>
                                    <?php echo esc_html($feed['name']); ?> (ID: <?php echo esc_html($id); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                </div>
                <?php wp_nonce_field('klpsfefo_feeds_settings_nonce', 'nonce'); ?>
                <input type="hidden" name="klpsfefo_current_feed_id" id="klpsfefo_current_feed_id" value="<?php echo esc_attr($current_feed_id); ?>" />
                <input type="hidden" name="klpsfefo_current_feed_channel" id="klpsfefo_current_feed_channel" value="idealo" />

                <table class="form-table klpsoft-mapping-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Feed Status', 'klpsoft-feeds'); ?></th>
                        <td>
                            <div class="klpsoft-switch-container">
                                <input type="hidden" name="klpsfefo_feeds_meta[feed_status]" value="disabled">

                                <label class="klpsoft-switch">
                                    <input type="checkbox" name="klpsfefo_feeds_meta[feed_status]" value="active" <?php checked($feed_status, 'active'); ?>>
                                    <span class="klpsoft-slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Generation Method', 'klpsoft-feeds'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="klpsfefo_feeds_meta[feed_type]" value="file" class="klpsfefo-feed-type-toggle" <?php checked($feed_type, 'file'); ?> /> 
                                <strong><?php esc_html_e('Write static CSV file', 'klpsoft-feeds'); ?></strong>
                            </label>
                            <?php if ( !empty($file_url) ): ?>
                                <button type="button" id="klpsfefo-feed-generate-now-btn" class="button button-secondary" data-feedid="<?php echo esc_attr($current_feed_id); ?>" style=" margin-left: 10px">
                                    🔄 <?php esc_html_e('Regenerate CSV file now', 'klpsoft-feeds'); ?>
                                </button>
                                <span id="klpsfefo-manual-status-msg" style="margin-left: 10px; font-weight: bold;"></span>   
                            <?php endif; ?>

                            <p class="description klpsfefo-file-path-wrapper" style="margin-top:10px; background:#fff; padding:10px; border-left:4px solid #00a0d2; <?php echo ($feed_type !== 'file') ? 'display:none;' : ''; ?>">
                                <?php esc_html_e('Your static CSV file path URL:', 'klpsoft-feeds'); ?><br/>
                                <code><a href="<?php echo esc_url(add_query_arg('ver', time(), $file_url)); ?>" target="_blank"><?php echo esc_url($file_url); ?></a></code>
                            </p>
                            <br>                  
                        </td>
                    </tr>

                    <tr class="klpsfefo-file-options-row" style="<?php echo ($feed_type !== 'file') ? 'display:none;' : ''; ?>">
                        <th scope="row"><label for="klpsfefo_cron_interval"><?php esc_html_e('Automatic Update Interval', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <select name="klpsfefo_feeds_meta[cron_interval]" id="klpsfefo_cron_interval">
                                <option value="0.5" <?php selected($cron_interval, '0.5'); ?>><?php esc_html_e('Every 0.5 hours', 'klpsoft-feeds'); ?></option>
                                <option value="1" <?php selected($cron_interval, '1'); ?>><?php esc_html_e('Every 1 hours', 'klpsoft-feeds'); ?></option>
                                <option value="2" <?php selected($cron_interval, '2'); ?>><?php esc_html_e('Every 2 hours', 'klpsoft-feeds'); ?></option>
                                <option value="4" <?php selected($cron_interval, '4'); ?>><?php esc_html_e('Every 4 hours', 'klpsoft-feeds'); ?></option>
                                <option value="8" <?php selected($cron_interval, '8'); ?>><?php esc_html_e('Every 8 hours', 'klpsoft-feeds'); ?></option>
                                <option value="12" <?php selected($cron_interval, '12'); ?>><?php esc_html_e('Every 12 hours', 'klpsoft-feeds'); ?></option>
                                <option value="24" <?php selected($cron_interval, '24'); ?>><?php esc_html_e('Every 24 Hours (daily)', 'klpsoft-feeds'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="klpsfefo_shipping_country"><?php esc_html_e('Target Shipping Country', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <input type="text" name="klpsfefo_feeds_meta[shipping_country]" id="klpsfefo_shipping_country" value="<?php echo esc_attr($feed_data['shipping_country'] ?? 'DE'); ?>" class="small-text" style="text-transform: uppercase; text-align: center;" maxlength="2" />
                            <p class="description"><?php esc_html_e('2-letter country code (e.g., DE, AT, CH).', 'klpsoft-feeds'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="klpsfefo_delivery_time"><?php esc_html_e('Default Delivery Time', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <input type="text" name="klpsfefo_feeds_meta[delivery_time]" id="klpsfefo_delivery_time" value="<?php echo esc_attr($feed_data['delivery_time'] ?? '1-3 Werktage'); ?>" placeholder="1-3 Werktage" class="regular-text" style="width: 220px;" />
                            <p class="description"><?php esc_html_e('Default shipping / delivery time', 'klpsoft-feeds'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="klpsfefo_shipping_price"><?php esc_html_e('Default Shipping Price', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <input type="number" name="klpsfefo_feeds_meta[shipping_price]" id="klpsfefo_shipping_price" value="<?php echo esc_attr($feed_data['shipping_price'] ?? '4.90'); ?>" class="regular-text" style="width: 100px;" step="0.1" min="0" /> <?php echo esc_html(get_woocommerce_currency()); ?>
                            <p class="description"><?php esc_html_e('Fallback price for physical items. (Virtual/download items always export as 0.00).', 'klpsoft-feeds'); ?></p>
                        </td>
                    </tr>

                </table>
                <h3 style="margin-top:30px;"><?php esc_html_e('Attribute Field Mapping', 'klpsoft-feeds'); ?></h3>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Idealo Feed Attribute', 'klpsoft-feeds'); ?></th>
                            <th><?php esc_html_e('Data Source', 'klpsoft-feeds'); ?></th>
                            <th><?php esc_html_e('Static Value / Custom meta field', 'klpsoft-feeds'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fields = array(
                            'id' => array('label' => 'ID (id)', 'required' => true),
                            'title' => array('label' => 'Title (title)', 'required' => true),
                            'description' => array('label' => 'Description (description)', 'required' => true),
                            'price' => array('label' => 'Price (price)', 'required' => true),
                            'link' => array('label' => 'Link (link)', 'required' => true),
                            'image_link' => array('label' => 'Image URL (image_url)', 'required' => true),
                            'gtin' => array('label' => 'GTIN / EAN (eans)', 'required' => false),
                            'mpn' => array('label' => 'MPN (hans)', 'required' => false),
                            'brand' => array('label' => 'Brand (brand)', 'required' => false),
                        );
// id|brand|title|description|price|link|image_url|delivery_time|delivery_costs|eans|hans
                        foreach ($fields as $key => $f) :
                            $src = $mappings[$key]['source'] ?? 'none';
                            $cust = $mappings[$key]['custom'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($f['label']); ?></strong>
                                    <?php if ($f['required']) {
                                        echo '<span style="color: red;">*</span>';
                                    } ?>
                                </td>
                                <td>
                                    <select name="klpsfefo_feed_mappings[<?php echo esc_attr($key); ?>][source]" class="mapping-source-select">
                                        <option value="none" <?php selected($src, 'none'); ?>>-- <?php esc_html_e('Not exported / (default)', 'klpsoft-feeds'); ?> --</option>

                                        <optgroup label="<?php esc_html_e('Standard Fields', 'klpsoft-feeds'); ?>">
                                            <option value="post_id" <?php selected($src, 'post_id'); ?>><?php esc_html_e('WP Post-ID', 'klpsoft-feeds'); ?></option>
                                            <option value="post_title" <?php selected($src, 'post_title'); ?>><?php esc_html_e('WP Title', 'klpsoft-feeds'); ?></option>
                                            <option value="post_content" <?php selected($src, 'post_content'); ?>><?php esc_html_e('WP Post content', 'klpsoft-feeds'); ?></option>
                                            <option value="post_permalink" <?php selected($src, 'post_permalink'); ?>><?php esc_html_e('WP Product Permalink', 'klpsoft-feeds'); ?></option>
                                            <option value="post_price" <?php selected($src, 'post_price'); ?>><?php esc_html_e('WP Sale price', 'klpsoft-feeds'); ?></option>
                                            <option value="post_image_link" <?php selected($src, 'post_image_link'); ?>><?php esc_html_e('WP Image Link', 'klpsoft-feeds'); ?></option>
                                            <option value="post_gtin" <?php selected($src, 'post_gtin'); ?>><?php esc_html_e('WP GTIN / EAN', 'klpsoft-feeds'); ?></option>
                                            <option value="post_mpn" <?php selected($src, 'post_mpn'); ?>><?php esc_html_e('WP SKU', 'klpsoft-feeds'); ?></option>
                                        </optgroup>

                                        <?php
                                        $global_attributes = wc_get_attribute_taxonomies();
                                        if (!empty($global_attributes)) :
                                            ?>
                                            <optgroup label="<?php esc_html_e('Global Attributes', 'klpsoft-feeds'); ?>">
                                                <?php
                                                foreach ($global_attributes as $tax) :
                                                    $value = 'attribute_pa_' . $tax->attribute_name;
                                                    $label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
                                                    ?>
                                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($src, $value); ?>>
                                                    <?php echo esc_html($label); ?>
                                                    </option>
                                            <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>

                                        <?php
                                        global $wpdb;
                                        $custom_attributes_slugs = $wpdb->get_col("
                                        SELECT DISTINCT meta_value 
                                        FROM {$wpdb->postmeta} 
                                        WHERE meta_key = '_product_attributes'
                                    ");

                                        $unique_custom_fields = array();
                                        if ( !empty($custom_attributes_slugs) ) {
                                            foreach ($custom_attributes_slugs as $serialized_attr) {
                                                $attrs = maybe_unserialize($serialized_attr);
                                                if (is_array($attrs)) {
                                                    foreach ($attrs as $attr_key => $attr_data) {
                                                        if (!$attr_data['is_taxonomy']) {
                                                            $unique_custom_fields[$attr_key] = $attr_data['name'];
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if ( !empty($unique_custom_fields) ) :
                                            ?>
                                            <optgroup label="<?php esc_html_e('Custom Product Attributes', 'klpsoft-feeds'); ?>">
                                                <?php
                                                foreach ($unique_custom_fields as $slug => $name) :
                                                    $value = 'custom_attr_' . sanitize_title($slug);
                                                    ?>
                                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($src, $value); ?>>
                                                <?php echo esc_html($name); ?>
                                                    </option>
                <?php endforeach; ?>
                                            </optgroup>
            <?php endif; ?>

                                        <optgroup label="<?php esc_html_e('Advanced', 'klpsoft-feeds'); ?>">
                                            <option value="custom_field" <?php selected($src, 'custom_field'); ?>><?php esc_html_e('Custom Field (Meta Key)', 'klpsoft-feeds'); ?></option>
                                            <option value="static_val" <?php selected($src, 'static_val'); ?>><?php esc_html_e('Static value', 'klpsoft-feeds'); ?></option>
                                        </optgroup>
                                    </select>

                                </td>
                                <td>
                                    <input type="text" name="klpsfefo_feed_mappings[<?php echo esc_attr($key); ?>][custom]" value="<?php echo esc_attr($cust); ?>" placeholder="" class="mapping-custom-input" style="<?php echo ( $src !== 'custom_field' && $src !== 'static_val' ) ? 'display:none;' : ''; ?>" />
                                </td>
                            </tr>
        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                
            <?php
            
            KLPSFEFO_Feed_Section_Filters::render('idealo', $current_feed_id, KLPSFEFO_Feed_Page_Channel_Idealo::getFeedData($current_feed_id, 'filters'));
            ?>                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="klpsfefo-feed-save-btn"><?php esc_html_e('Save Feed Settings', 'klpsoft-feeds'); ?></button>
                    <span id="klpsfefo-feed-status-msg" style="margin-left:10px;"></span>
                </p>
        </div>
        <?php
    }
}
