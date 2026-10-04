<?php

// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Page_Channel_Google {

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

        self::$all_feeds_data = get_option('klpsfefo_feeds_google', array());
        
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
        $gapi_enabled = '0';
        
        if ( !empty(self::$all_feeds_data) ) {

            $feed_data       = self::$all_feeds_data[$current_feed_id];
            $file_hash       = $feed_data['file_hash'] ?? '';
            $mappings        = $feed_data['mappings'] ?? array();
            $feed_status     = $feed_data['feed_status'] ?? 'active';
            $feed_type       = $feed_data['feed_type'] ?? 'file';
            $cron_interval   = $feed_data['cron_interval'] ?? '24';
            $gapi_enabled    = $feed_data['google_api_enabled'] ?? '0';
            $variants_status = $feed_data['export_variants'] ?? 'no';
            $variants_default_status = $feed_data['export_variants_default'] ?? 'no';
            $export_shipping = $feed_data['export_shipping'] ?? 'yes';

            $is_table_open = $feed_data['mapping_tbl_open'] ?? '1'; 
            $open_attribute = ($is_table_open == '1') ? 'open' : '';
            
            $upload_dir = wp_upload_dir();
            $file_url   = $upload_dir['baseurl'] . '/klp-feeds-xml/google-feed-' . $file_hash . '.xml';
            $stream_url = site_url('?klpsfefo_shopping_feed=1&channel=google&feed_id=' . $current_feed_id);
        }
        
        $is_premium = apply_filters('klpsfefo_feeds_is_premium_active', false);
        
        ?>
        <div class="wrap">
            <h2>Google Shopping</h2>
            
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
                <input type="hidden" name="klpsfefo_current_feed_channel" id="klpsfefo_current_feed_channel" value="google" />

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
                                <strong><?php esc_html_e('Write static XML file', 'klpsoft-feeds'); ?></strong>
                            </label>
                            <?php if ( !empty($file_url) && $feed_type == 'file' ): ?>
                                <button type="button" id="klpsfefo-feed-generate-now-btn" class="button button-secondary" data-feedid="<?php echo esc_attr($current_feed_id); ?>" style=" margin-left: 10px">
                                    🔄 <?php esc_html_e('Regenerate XML file now', 'klpsoft-feeds'); ?>
                                </button>
                                <span id="klpsfefo-manual-status-msg" style="margin-left: 10px; font-weight: bold;"></span>   
                            <?php endif; ?>                           
                            <p class="description klpsfefo-file-path-wrapper" style="margin-top:10px; background:#fff; padding:10px; border-left:4px solid #00a0d2; <?php echo ( $feed_type !== 'file' ) ? 'display:none;' : ''; ?>">
                                <?php esc_html_e('Your static XML file path URL:', 'klpsoft-feeds'); ?><br/>
                                <code><a href="<?php echo esc_url(add_query_arg('ver', time(), $file_url)); ?>" target="_blank"><?php echo esc_url($file_url); ?></a></code>
                            </p>
                            <div style="margin-top: 15px;">
                                <label style="display: inline-block;">
                                    <input type="radio" name="klpsfefo_feeds_meta[feed_type]" value="stream" class="klpsfefo-feed-type-toggle" <?php checked($feed_type, 'stream'); ?> /> 
                                    <strong><?php esc_html_e('Live-Streaming', 'klpsoft-feeds'); ?></strong> – <?php esc_html_e('Feed is generated live on request.', 'klpsoft-feeds'); ?><br/>
                                    <code class="klpsfefo-stream-code-url" style="display:inline-block; margin-top:5px;"><a href="<?php echo esc_url($stream_url); ?>" target="_blank"><?php echo esc_url($stream_url); ?></a></code>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Send product changes instantly to Google', 'klpsoft-feeds'); ?></th>
                        <td>
                        <?php
                            if ($is_premium) { ?>
                        <label for="google_api_enabled">
                        <input 
                            type="checkbox" 
                            id="google_api_enabled" 
                            name="klpsfefo_feeds_meta[google_api_enabled]" 
                            value="1" 
                            <?php checked('1', $gapi_enabled); ?> 
                            <?php disabled(!$is_premium); ?>
                        />
                        <?php esc_html_e('Use Google Merchant real-time API', 'klpsoft-feeds'); ?>
                        <?php } ?>

                        <?php if (!$is_premium) : ?>
                        <input 
                            type="checkbox" 
                            id="google_api_enabled" 
                            disabled 
                                 />
                        <span class="premium-badge" style="background:#ffb900; color:#fff; padding:2px 6px; font-size:10px; border-radius:3px; font-weight:bold; margin-left:5px; vertical-align: middle;">
                            PREMIUM FEATURE!
                        </span>
                        <p class="description">
                            <?php esc_html_e('Use Google Merchant real-time API', 'klpsoft-feeds'); ?>
                        </p>
                        <?php endif; ?>
                        </label>
                        </td>
                    </tr>                    
                    <tr class="klpsfefo-file-options-row" style="<?php echo ( $feed_type !== 'file' ) ? 'display:none;' : ''; ?>">
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
                        <th scope="row"><label for="klpsfefo_feedlabel"><?php esc_html_e('Feed label', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <input type="text" name="klpsfefo_feeds_meta[feedlabel]" id="klpsfefo_feedlabel" value="<?php echo esc_attr($feed_data['feedlabel'] ?? 'DE'); ?>" class="regular-text" style="max-width:100px" maxlength="12" />
                            <p class="description"><?php esc_html_e('feed label (e.g., DE, AT, CH).', 'klpsoft-feeds'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Export shipping data', 'klpsoft-feeds'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="klpsfefo_feeds_meta[export_shipping]" value="yes" <?php checked($export_shipping, 'yes'); ?> /> 
                                <?php esc_html_e('Include shipping tags in this feed', 'klpsoft-feeds'); ?>
                            </label>
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
                        <th scope="row"><label for="klpsfefo_shipping_price"><?php esc_html_e('Default Shipping Price', 'klpsoft-feeds'); ?></label></th>
                        <td>
                            <input type="number" name="klpsfefo_feeds_meta[shipping_price]" id="klpsfefo_shipping_price" value="<?php echo esc_attr($feed_data['shipping_price'] ?? '4.90'); ?>" class="regular-text" style="width: 100px;" step="0.1" min="0" /> 
                                <?php echo esc_html(get_woocommerce_currency()); ?>
                            <p class="description"><?php esc_html_e('Fallback price for physical items. (Virtual/download items always export as 0.00).', 'klpsoft-feeds'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Product variants export', 'klpsoft-feeds'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="klpsfefo_feeds_meta[export_variants]" value="yes" <?php checked($variants_status, 'yes'); ?> /> 
                                <?php esc_html_e('Include variations in this feed', 'klpsoft-feeds'); ?>
                            </label>
                            <p><br /></p>
                            <label>
                                <input type="checkbox" name="klpsfefo_feeds_meta[export_variants_default]" value="yes" <?php checked($variants_default_status, 'yes'); ?> /> 
                                <?php esc_html_e('Include only default variation', 'klpsoft-feeds'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <?php
                            if ( $is_premium ) {
                                do_action('klpsfefo_feeds_render_utm_params', $feed_data);
                                do_action('klpsfefo_feeds_render_datasource', $feed_data);

                            } else {
                                self::render_google_utm_notice();
                            }
                        ?>
                    </tr>
                </table>
                
                <details id="klpsfefo-mapping-details" data-feed-id="<?php echo esc_attr($current_feed_id); ?>" <?php echo esc_attr($open_attribute); ?> style="margin-top:30px; border: 1px solid #ccd0d4; background: #fff; padding: 10px 15px; border-radius: 4px;">
                    <summary style="font-size: 1.3em; font-weight: 600; margin: 0; cursor: pointer; user-select: none; outline: none;">
                        <?php esc_html_e('Attribute Field Mapping', 'klpsoft-feeds'); ?> <span style="font-size: 0.8em; color: #646970; font-weight: normal; margin-left: 10px;"><?php  esc_html_e('(Click to expand/collapse)', 'klpsoft-feeds'); ?> </span>
                    </summary>
                    
                <table class="wp-list-table widefat fixed striped posts klpsoft-mapping-table" style="margin-top: 15px; border: none; box-shadow: none;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Google Feed Attribute', 'klpsoft-feeds'); ?></th>
                            <th><?php esc_html_e('Data Source', 'klpsoft-feeds'); ?></th>
                            <th><?php esc_html_e('Static Value / Custom meta field', 'klpsoft-feeds'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fields = array(
                            'id'          => array('label' => 'ID (g:id)', 'required' => true),
                            'title'       => array('label' => 'Title (g:title)', 'required' => true),
                            'description' => array('label' => 'Description (g:description)', 'required' => true),
                            'price'       => array('label' => 'Price (g:price)', 'required' => true),
                            'sale_price'       => array('label' => 'Sale price (g:sale_price)', 'required' => false),
                            'link'        => array('label' => 'Link (g:link)', 'required' => true),
                            'image_link'  => array('label' => 'Image URL (g:image_link)', 'required' => true),
                            'gtin'        => array('label' => 'GTIN / EAN (g:gtin)', 'required' => false),
                            'mpn'         => array('label' => 'MPN (g:mpn)', 'required' => false),
                            'brand'       => array('label' => 'Brand (g:brand)', 'required' => false),
                            'condition'    => array('label' => 'Condition (g:condition)', 'required' => false),
                            'shipping_price' => array('label' => 'Shipping price (g:shipping > g:price)', 'required' => false)
                        );

                        foreach ( $fields as $key => $f ) : 
                            $src = $mappings[$key]['source'] ?? 'none';
                            $cust = $mappings[$key]['custom'] ?? '';
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($f['label']); ?></strong>
                                    <?php if ( $f['required'] ) { echo '<span style="color: red;">*</span>'; } ?>
                                </td>
                                <td>
<select name="klpsfefo_feed_mappings[<?php echo esc_attr($key); ?>][source]" class="mapping-source-select" >
    <option value="none" <?php selected($src, 'none'); ?>>-- <?php esc_html_e('Not exported / (default)', 'klpsoft-feeds'); ?> --</option>
    
    <optgroup label="<?php esc_html_e('Standard Fields', 'klpsoft-feeds'); ?>">
        <option value="post_id" <?php selected($src, 'post_id'); ?>><?php esc_html_e('WP Post-ID', 'klpsoft-feeds'); ?></option>
        <option value="post_title" <?php selected($src, 'post_title'); ?>><?php esc_html_e('WP Title', 'klpsoft-feeds'); ?></option>
        <option value="post_content" <?php selected($src, 'post_content'); ?>><?php esc_html_e('WP Post content', 'klpsoft-feeds'); ?></option>
        <option value="post_permalink" <?php selected($src, 'post_permalink'); ?>><?php esc_html_e('WP Product Permalink', 'klpsoft-feeds'); ?></option>
        <option value="post_price" <?php selected($src, 'post_price'); ?>><?php esc_html_e('WP regular price', 'klpsoft-feeds'); ?></option>
        <option value="post_sale_price" <?php selected($src, 'post_sale_price'); ?>><?php esc_html_e('WP Sale price', 'klpsoft-feeds'); ?></option>
        <option value="post_image_link" <?php selected($src, 'post_image_link'); ?>><?php esc_html_e('WP Image Link', 'klpsoft-feeds'); ?></option>
        <option value="post_gtin" <?php selected($src, 'post_gtin'); ?>><?php esc_html_e('WP GTIN / EAN', 'klpsoft-feeds'); ?></option>
        <option value="post_mpn" <?php selected($src, 'post_mpn'); ?>><?php esc_html_e('WP SKU', 'klpsoft-feeds'); ?></option>
    </optgroup>

    <?php 
    $global_attributes = wc_get_attribute_taxonomies();
    if ( !empty( $global_attributes) ) : 
    ?>
        <optgroup label="<?php esc_html_e('Global Attributes', 'klpsoft-feeds'); ?>">
            <?php foreach ( $global_attributes as $tax ) : 
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
    if ( ! empty( $custom_attributes_slugs ) ) {
        foreach ( $custom_attributes_slugs as $serialized_attr ) {
            $attrs = maybe_unserialize($serialized_attr);
            if ( is_array($attrs) ) {
                foreach ( $attrs as $attr_key => $attr_data ) {
                    if ( !$attr_data['is_taxonomy'] ) {
                        $unique_custom_fields[$attr_key] = $attr_data['name'];
                    }
                }
            }
        }
    }

    if ( !empty( $unique_custom_fields ) ) : 
    ?>
        <optgroup label="<?php esc_html_e('Custom Product Attributes', 'klpsoft-feeds'); ?>">
            <?php foreach ( $unique_custom_fields as $slug => $name ) : 
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
    
    <optgroup label="<?php esc_html_e('Text', 'klpsoft-feeds'); ?>">
        <option value="custom_val_new" <?php selected($src, 'custom_val_new'); ?>>new</option>
        <option value="custom_val_refurbished" <?php selected($src, 'custom_val_refurbished'); ?>>refurbished</option>
        <option value="custom_val_used" <?php selected($src, 'custom_val_used'); ?>>used</option>
        <option value="custom_val_yes" <?php selected($src, 'yes'); ?>>yes</option>
        <option value="custom_val_no" <?php selected($src, 'no'); ?>>no</option>
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
              </details>
            </form>
            
            <?php
            
            KLPSFEFO_Feed_Section_Filters::render('google', $current_feed_id, KLPSFEFO_Feed_Page_Channel_Google::getFeedData($current_feed_id, 'filters'));
            ?>
            
            <p class="submit">
                <button class="button button-primary" id="klpsfefo-feed-save-btn"><?php esc_html_e('Save Feed Settings', 'klpsoft-feeds'); ?></button>
                <span id="klpsfefo-feed-status-msg" style="margin-left:10px;"></span>
            </p>
        </div>
        <?php
    }
    
    private static function render_google_utm_notice() {
        ?>
        <tr>
            <td colspan="2">
                <h4><span class="dashicons dashicons-lock" style="color: #999; vertical-align: text-bottom; margin-right: 5px;"></span>Google Analytics UTM parameter</h4>
                <p>Please upgrade to premium license to get "Google Analytics UTM params" feature.</p>
                <span class="premium-badge" style="background:#ffb900; color:#fff; padding:2px 6px; font-size:10px; border-radius:3px; font-weight:bold; margin-left:5px; vertical-align: middle;">
                    PREMIUM FEATURE!
                </span>
            </td>
        </tr>
        <?php
    }
}
