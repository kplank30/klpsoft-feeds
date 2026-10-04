<?php

// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Section_Filters {

    /**
     * renders filter section
     */
    public static function render($current_feed_channel = 'google', $current_feed_id = 1, $saved_filters = array()) {
        if ( !current_user_can('manage_woocommerce') && !current_user_can('manage_options') ) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'klpsoft-feeds'));
        }
?>

<div class="klpsfefo-feed-section" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
    <h2><?php esc_html_e('Custom Filters', 'klpsoft-feeds'); ?></h2>
    <p class="description"><?php esc_html_e('Define the filter rules for this feed here.', 'klpsoft-feeds'); ?></p>

    <form id="klpsfefo-feed-filter-settings-form">
        <?php wp_nonce_field('klpsfefo_save_filters_nonce_action', 'klpsfefo_filters_nonce'); ?>
        
        <input type="hidden" name="klpsfefo_current_feed_id" value="<?php echo esc_attr($current_feed_id); ?>" />
        <input type="hidden" name="klpsfefo_current_feed_channel" value="<?php echo esc_attr($current_feed_channel); ?>" />
        
        <table class="wp-list-table widefat fixed striped" id="klpsfefo-filter-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="klpsfefo-select-all-filters"></th>
                    <th><?php esc_html_e('Product Field / Property', 'klpsoft-feeds'); ?></th>
                    <th><?php esc_html_e('Condition', 'klpsoft-feeds'); ?></th>
                    <th><?php esc_html_e('Value', 'klpsoft-feeds'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Action', 'klpsoft-feeds'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( !empty($saved_filters) ) : ?>
                    <?php foreach ( $saved_filters as $index => $filter ) : ?>
                        <tr class="filter-row">
                            <td><input type="checkbox" class="klpsfefo-delete-filter-checkbox"></td>
                            <td>
                                <select name="klpsfefo_filter_rules[<?php echo esc_attr($index); ?>][field]" style="width: 100%;">
                                    <?php echo self::klpsfefo_render_feed_fields_dropdown_options($filter['field']); ?>
                                </select>
                            </td>
                            <td>
                                <select name="klpsfefo_filter_rules[<?php echo esc_attr($index); ?>][operator]" style="width: 100%;">
                                    <option value="equal" <?php selected($filter['operator'], 'equal'); ?>><?php esc_html_e('is equal to (=)', 'klpsoft-feeds'); ?></option>
                                    <option value="not_equal" <?php selected($filter['operator'], 'not_equal'); ?>><?php esc_html_e('is not equal to (!=)', 'klpsoft-feeds'); ?></option>
                                    <option value="greater" <?php selected($filter['operator'], 'greater'); ?>><?php esc_html_e('greater than (&gt;)', 'klpsoft-feeds'); ?></option>
                                    <option value="less" <?php selected($filter['operator'], 'less'); ?>><?php esc_html_e('less than (&lt;)', 'klpsoft-feeds'); ?></option>
                                    <option value="contains" <?php selected($filter['operator'], 'contains'); ?>><?php esc_html_e('contains', 'klpsoft-feeds'); ?></option>
                                    <option value="not_empty" <?php selected($filter['operator'], 'not_empty'); ?>><?php esc_html_e('is not empty', 'klpsoft-feeds'); ?></option>
                                    <option value="is_empty" <?php selected($filter['operator'], 'is_empty'); ?>><?php esc_html_e('is empty', 'klpsoft-feeds'); ?></option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="klpsfefo_filter_rules[<?php echo esc_attr($index); ?>][value]" value="<?php echo esc_attr($filter['value']); ?>" class="regular-text" style="width: 100%;">
                            </td>
                            <td>
                                <select name="klpsfefo_filter_rules[<?php echo esc_attr($index); ?>][action]" style="width: 100%; font-weight: 500;">
                                    <option value="exclude" <?php selected($filter['action'], 'exclude'); ?> style="color: #b32d2e;"><?php esc_html_e('Exclude', 'klpsoft-feeds'); ?></option>
                                    <option value="include" <?php selected($filter['action'], 'include'); ?> style="color: #2bab55;"><?php esc_html_e('Include Only', 'klpsoft-feeds'); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr class="filter-row klpsfefo-no-filters-placeholder">
                        <td colspan="5" style="text-align: center; color: #666; padding: 20px;"><?php esc_html_e('No filters defined yet. Click on "Add Field".', 'klpsoft-feeds'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="klpsfefo-filter-actions" style="margin-top: 15px;">
            <button type="button" id="klpsfefo-btn-add-filter" class="button button-secondary"><?php esc_html_e('Add Field', 'klpsoft-feeds'); ?></button>
            <button type="button" id="klpsfefo-btn-delete-filter" class="button button-secondary" style="color: #b32d2e; border-color: #b32d2e;"><?php esc_html_e('Delete Filter', 'klpsoft-feeds'); ?></button>
            <button name="klpsfefo_save_filter_settings" id="klpsfefo_save_filter_settings" class="button button-primary" style="float:right;"><?php esc_html_e('Save Filters', 'klpsoft-feeds'); ?></button>
        </div>
        
    </form>
</div>

<script type="text/template" id="klpsfefo-filter-row-template">
    <tr class="filter-row">
        <td><input type="checkbox" class="klpsfefo-delete-filter-checkbox"></td>
        <td>
            <select name="klpsfefo_filter_rules[{{INDEX}}][field]" style="width: 100%;">
                <?php echo self::klpsfefo_render_feed_fields_dropdown_options(); ?>
            </select>
        </td>
        <td>
            <select name="klpsfefo_filter_rules[{{INDEX}}][operator]" style="width: 100%;">
                <option value="equal"><?php esc_html_e('is equal to (=)', 'klpsoft-feeds'); ?></option>
                <option value="not_equal"><?php esc_html_e('is not equal to (!=)', 'klpsoft-feeds'); ?></option>
                <option value="greater"><?php esc_html_e('greater than (&gt;)', 'klpsoft-feeds'); ?></option>
                <option value="less"><?php esc_html_e('less than (&lt;)', 'klpsoft-feeds'); ?></option>
                <option value="contains"><?php esc_html_e('contains', 'klpsoft-feeds'); ?></option>
                <option value="not_empty"><?php esc_html_e('is not empty', 'klpsoft-feeds'); ?></option>
                <option value="is_empty"><?php esc_html_e('is empty', 'klpsoft-feeds'); ?></option>
            </select>
        </td>
        <td>
            <input type="text" name="klpsfefo_filter_rules[{{INDEX}}][value]" value="" class="regular-text" style="width: 100%;">
        </td>
        <td>
            <select name="klpsfefo_filter_rules[{{INDEX}}][action]" style="width: 100%; font-weight: 500;">
                <option value="exclude" style="color: #b32d2e;"><?php esc_html_e('Exclude', 'klpsoft-feeds'); ?></option>
                <option value="include" style="color: #2bab55;"><?php esc_html_e('Include Only', 'klpsoft-feeds'); ?></option>
            </select>
        </td>
    </tr>
</script>
<?php
    }

    public static function klpsfefo_render_feed_fields_dropdown_options($src='') {
        ?>
        <option value=""><?php esc_html_e('-- Select Field --', 'klpsoft-feeds'); ?></option>
                
        <optgroup label="<?php esc_html_e('Standard fields', 'klpsoft-feeds'); ?>">
            <option value="post_title" <?php selected($src, 'post_title'); ?>><?php esc_html_e('Product Name', 'klpsoft-feeds'); ?></option>
            <option value="_sale_price" <?php selected($src, '_sale_price'); ?>><?php esc_html_e('Sale Price', 'klpsoft-feeds'); ?></option>
            <option value="_stock_status" <?php selected($src, '_stock_status'); ?>><?php esc_html_e('Stock Status (instock / outofstock)', 'klpsoft-feeds'); ?></option>
            <option value="product_cat" <?php selected($src, 'product_cat'); ?>><?php esc_html_e('Category (ID / Name)', 'klpsoft-feeds'); ?></option>
        </optgroup>
        <?php
        $global_attributes = wc_get_attribute_taxonomies();
        if ( !empty( $global_attributes ) ) : 
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
        $custom_attributes_slugs = $wpdb->get_col( "
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_product_attributes'
        " );

        $unique_custom_fields = array();
        if ( !empty($custom_attributes_slugs) ) {
            foreach ( $custom_attributes_slugs as $serialized_attr ) {
                $attrs = maybe_unserialize($serialized_attr);
                if ( is_array($attrs) ) {
                    foreach ( $attrs as $attr_key => $attr_data ) {
                        if ( !$attr_data['is_taxonomy']) {
                            $unique_custom_fields[$attr_key] = $attr_data['name'];
                        }
                    }
                }
            }
        }

        if ( !empty($unique_custom_fields) ) : 
        ?>
            <optgroup label="<?php esc_attr_e('Custom Product Attributes', 'klpsoft-feeds'); ?>">
                <?php foreach ( $unique_custom_fields as $slug => $name ) : 
                    $value = 'custom_attr_' . sanitize_title($slug);
                    ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($src, $value); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endif; ?>
    <?php }
    
}
