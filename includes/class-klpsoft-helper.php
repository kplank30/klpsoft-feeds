<?php

/**
 * klpsoft Feeds
 * Copyright (C) 2026 klp-soft. All rights reserved.
 */
namespace Klpsoft\Feeds;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('KLPSFEFO_Helper')) {

    class KLPSFEFO_Helper {

        /**
         * filter check
         */
        public static function product_passes_filters($product, $saved_filters) {
            if (!is_array($saved_filters) || empty($saved_filters)) {
                return true;
            }

            foreach ($saved_filters as $filter) {
                $field = isset($filter['field']) ? $filter['field'] : '';
                $operator = isset($filter['operator']) ? $filter['operator'] : '';
                $value = isset($filter['value']) ? trim($filter['value']) : '';
                $action = isset($filter['action']) ? $filter['action'] : 'exclude';

                if (empty($field) || empty($operator)) {
                    continue;
                }

                $condition_matches = false;

                $parent_product = null;
                if ($product->is_type('variation')) {
                    $parent_product = wc_get_product($product->get_parent_id());
                }

                if ($field === 'product_cat') {
                    $product_id_for_cat = $parent_product ? $parent_product->get_id() : $product->get_id();

                    $terms = wp_get_post_terms($product_id_for_cat, 'product_cat');
                    $product_cats = array();

                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $term) {
                            $product_cats[] = (string) $term->term_id;
                            $product_cats[] = $term->slug;
                            $product_cats[] = $term->name;
                        }
                    }

                    $filter_value = $value;

                    switch ($operator) {
                        case 'equal':
                            $condition_matches = in_array($filter_value, $product_cats, true);
                            break;
                        case 'not_equal':
                            $condition_matches = !in_array($filter_value, $product_cats, true);
                            break;
                        case 'greater':
                            $condition_matches = (count($terms) > floatval($value));
                            break;
                        case 'less':
                            $condition_matches = (count($terms) < floatval($value));
                            break;
                        case 'contains':
                            foreach ($product_cats as $cat) {
                                if (strpos($cat, $filter_value) !== false) {
                                    $condition_matches = true;
                                    break;
                                }
                            }
                            break;
                        case 'is_empty':
                            $condition_matches = empty($product_cats);
                            break;
                        case 'not_empty':
                            $condition_matches = !empty($product_cats);
                            break;
                    }
                } else {
                    $product_value = '';

                    if (strpos($field, 'custom_attr_') === 0) {
                        $clean_attribute_name = str_replace('custom_attr_', '', $field);
                        $product_value = $product->get_attribute($clean_attribute_name);
                        if (empty($product_value) && $parent_product) {
                            $product_value = $parent_product->get_attribute($clean_attribute_name);
                        }
                    } else if (strpos($field, 'pa_') === 0) {
                        $product_value = $product->get_attribute($field);
                        if (empty($product_value) && $parent_product) {
                            $product_value = $parent_product->get_attribute($field);
                        }
                    } else {
                        switch ($field) {
                            case 'post_title':
                                $product_value = $product->get_name();
                                if ($parent_product) {
                                    $product_value = $parent_product->get_name();
                                }
                                break;
                            case '_price':
                                $product_value = $product->get_price();
                                break;
                            case '_regular_price':
                                $product_value = $product->get_regular_price();
                                break;
                            case '_sale_price':
                                $product_value = $product->get_sale_price();
                                break;
                            case '_stock_status':
                                $product_value = $product->get_stock_status();
                                break;
                            case '_stock':
                                $product_value = $product->get_stock_quantity();
                                break;
                            case '_sku':
                                $product_value = $product->get_sku();
                                break;
                            default:
                                $attribute_value = $product->get_attribute($field);
                                if (empty($attribute_value) && $parent_product) {
                                    $attribute_value = $parent_product->get_attribute($field);
                                }

                                if (!empty($attribute_value)) {
                                    $product_value = $attribute_value;
                                } else {
                                    $product_value = $product->get_meta($field, true);
                                    if ($product_value === '' && $parent_product) {
                                        $product_value = $parent_product->get_meta($field, true);
                                    }
                                }
                                break;
                        }
                    }

                    $product_value = trim((string) $product_value);

                    switch ($operator) {
                        case 'equal':
                            $condition_matches = ($product_value === $value);
                            break;
                        case 'not_equal':
                            $condition_matches = ($product_value !== $value);
                            break;
                        case 'greater':
                            $condition_matches = (floatval($product_value) > floatval($value));
                            break;
                        case 'less':
                            $condition_matches = (floatval($product_value) < floatval($value));
                            break;
                        case 'contains':
                            $condition_matches = (strpos($product_value, $value) !== false);
                            break;
                        case 'is_empty':
                            $condition_matches = ($product_value === '');
                            break;
                        case 'not_empty':
                            $condition_matches = ($product_value !== '');
                            break;
                    }
                }

                if ($action === 'exclude' && $condition_matches) {
                    return false;
                }

                if ($action === 'include' && !$condition_matches) {
                    return false;
                }
            }

            return true;
        }

        public static function get_secure_feed_hash($feed_id) {
            $secret_token = get_option('klpsfefo_feed_secret_token');

            if (empty($secret_token)) {
                $secret_token = wp_generate_password(32, true, true);
                update_option('klpsfefo_feed_secret_token', $secret_token);
            }

            return md5($secret_token . $feed_id);
        }
    }

}