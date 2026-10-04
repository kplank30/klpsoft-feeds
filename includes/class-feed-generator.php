<?php
/**
 * Feed-Generator klpsoft Feeds
 * Copyright (C) 2026 klp-soft. All rights reserved.
 */
namespace Klpsoft\Feeds;

if ( !defined('ABSPATH') ) {
    exit;
}


class KLPSFEFO_Feed_Generator {

    public $channel = 'google';
    
    /**
     * Konstruktor 
     */
    public function __construct($channel='google') {
        
        $this->channel = $channel;
        add_action('init', array($this, 'klpsfefo_register_route'));
        add_action('parse_request', array($this, 'klpsfefo_render_feed'));

        add_action('klpsfefo_feeds_event', array($this, 'klpsfefo_run_multi_cron_generation'), 10, 2);

    }

    public function klpsfefo_setup_cronjob($feed_channel, $feed_id, $force_rebuild = false) {
        if ( !function_exists('as_next_scheduled_action') ) {
            return;
        }

        $all_feeds = get_option('klpsfefo_feeds_' . $feed_channel, array());
        $feed_type = $all_feeds[$feed_id]['feed_type'] ?? 'stream';

        $clean_feed_id = is_numeric($feed_id) ? intval($feed_id) : $feed_id;
        $cron_args = array($feed_channel, $clean_feed_id);

        if ( $feed_type !== 'file' ) {
            as_unschedule_all_actions('klpsfefo_feeds_event', $cron_args);
            return;
        }

        if ( $force_rebuild ) {
            as_unschedule_all_actions('klpsfefo_feeds_event', $cron_args);
        }

        if ( !as_next_scheduled_action('klpsfefo_feeds_event', $cron_args) ) {
            $hours = floatval($all_feeds[$feed_id]['cron_interval'] ?? 24);
            if ( $hours <= 0 ) { $hours = 24; }

            $interval_seconds = round($hours * HOUR_IN_SECONDS);

            as_schedule_recurring_action(time() + 10, $interval_seconds, 'klpsfefo_feeds_event', $cron_args);
        }
    }

    public function klpsfefo_delete_cronjob($feed_channel, $feed_id) {
        if ( !function_exists('as_unschedule_all_actions') ) {
            return;
        }

        $clean_feed_id = is_numeric($feed_id) ? intval($feed_id) : $feed_id;
        $cron_args = array($feed_channel, $clean_feed_id);

        as_unschedule_all_actions('klpsfefo_feeds_event', $cron_args);
    }
    
    /**
     * all channels & profiles feeds 
     */
    public function klpsfefo_run_multi_cron_generation($feed_channel, $feed_id) {

        if ( !in_array($feed_channel, array('google', 'bing', 'idealo')) ) {
            return;
        }

        $feed_id = is_numeric($feed_id) ? intval($feed_id) : $feed_id;

        $all_feeds = get_option('klpsfefo_feeds_'. $feed_channel, array());

        if ( !isset($all_feeds[$feed_id]) ) {
            return; 
        }

        $feed_data = $all_feeds[$feed_id];

        if ( ( $feed_data['feed_type'] ?? 'stream' ) === 'file' && ($feed_data['feed_status'] ?? '') === 'active' ) {

            $this->klpsfefo_generate_feed_file($feed_id, $feed_channel, $feed_data['filters'], $feed_data['file_hash']);
            $this->klpsfefo_log_feed_generation($feed_channel, $feed_id);
        }
    }
    
    /**
     * Register URL parameter for feed and channel
     */
    public function klpsfefo_register_route() {
        add_filter('query_vars', array($this, 'klpsfefo_add_shopping_feed_vars'));
    }

    /**
     * url params
     */
    public function klpsfefo_add_shopping_feed_vars($vars) {
        $vars[] = 'klpsfefo_shopping_feed';
        $vars[] = 'feed_channel';
        return $vars;
    }

    /**
     * logging
     */
    public function klpsfefo_log_feed_generation($feed_channel, $feed_id) {
        $logging_enabled = get_option('klpsfefo_feeds_general_settings', 'off');

        if ( $logging_enabled['logging'] !== 'on' ) {
            return;
        }

        if ( function_exists('wc_get_logger') ) {
            $logger = wc_get_logger();

            $current_time = date_i18n('d.m.Y - H:i:s');

            $message = sprintf(
                'klpsoft-feeds has created feed, ID %s (Channel: %s) at %s.',
                $feed_id,
                $feed_channel,
                $current_time
            );

            $logger->info($message, array('source' => 'klpsoft-feeds'));
        }
    }

    /**
     * XML streaming
     */
    public function klpsfefo_render_feed($wp) {
        if ( !isset($wp->query_vars['klpsfefo_shopping_feed']) ) {
            return;
        }
        if ( !isset($_GET['channel']) ) {
            wp_die( __('Error: Feed channel not found.', 'klpsoft-feeds'));
        }
        $channel = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : 'google';
        
        if ( $channel == 'idealo' || !in_array($channel, array('google', 'bing', 'meta', 'tiktok'))  ) {
            return;
        }
        $this->channel = $channel;
        
        $general = get_option('klpsfefo_feeds_general_settings', array());
        if ( ( $general['feed_status'] ?? 'active' ) === 'disabled' ) {
            global $wp_query; $wp_query->set_404(); status_header(404); get_template_part('404'); exit;
        }

        $feed_id   = isset($_GET['feed_id']) ? absint($_GET['feed_id']) : 1;
        
        $all_feeds = get_option('klpsfefo_feeds_'. $channel, array());
        
        if ( !isset($all_feeds[$feed_id]) ) {
            wp_die( __('Error: Feed profile not found.', 'klpsoft-feeds'));
        }

        $feed_data       = $all_feeds[$feed_id];
        $mappings        = $feed_data['mappings'] ?? array();
        $filters         = $feed_data['filters'] ?? array();
        $feed_type       = $feed_data['feed_type'] ?? 'stream';
        $export_variants = $feed_data['export_variants'] ?? 'no';

        if ( $feed_type === 'file' ) {
            wp_die(__('Live stream option is disabled. Change settings or use xml file at /uploads/.', 'klpsoft-feeds'));
        }

        if ( $channel === 'google' || $channel === 'bing' ) {
            $this->klpsfefo_generate_google_xml_feed($feed_id, $filters);
        } else {
            do_action('klpsfefo_feed_render_premium_channels', $channel, $feed_id);
        }
        exit;
    }

    /**
     *  generate_google_xml_feed stream
     */
    public function klpsfefo_generate_google_xml_feed($feed_id, $filters=array()) {
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new \XMLWriter();
        $writer->openURI('php://output');
 
        $this->klpsfefo_writeXmlData($writer, $feed_id, $filters);

        exit;
    }
    
    /**
     * folder /uploads/klp-feeds-xml/ write XML files
     */
    public function klpsfefo_generate_feed_file($feed_id, $channel='', $filters=array(), $file_hash='') {
        
        if ( !empty($channel) ) {
            $this->channel = $channel;
        }
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/klp-feeds-xml';

        $file_path  = $base_dir . '/' . sanitize_key($this->channel) . '-feed-' . $file_hash . '.xml';

        if ( !file_exists($base_dir) ) {
            wp_mkdir_p($base_dir);
        }

        if ( $this->channel == 'google' || $this->channel == 'bing' ) {
            
            $writer = new \XMLWriter();
            if ( !$writer->openURI($file_path) ) {
                return false;
            }

            $this->klpsfefo_writeXmlData($writer, $feed_id, $filters);
            
        } else if ( $this->channel == 'idealo' ) {
            //
            $this->klpsfefo_writeIdealoCsvData($feed_id, $filters, $file_hash);
        } else {
            //
            do_action('klpsfefo_feeds_generate_custom_channel', $this->channel, $file_path, $feed_id, $filters, $file_hash);
        }
        
        return true;
    }
    
    public function klpsfefo_writeXmlData(&$writer, $feed_id, $saved_filters=array()) {
        // google & bing
        $writer->setIndent(true);
        $writer->setIndentString('  ');

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('rss');
        $writer->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $writer->writeAttribute('version', '2.0');
        $writer->startElement('channel');

        $writer->writeElement('title', wp_strip_all_tags(get_bloginfo('name')));
        $writer->writeElement('link', esc_url(site_url()));
        $chDesc = wp_strip_all_tags(get_bloginfo('description'));
        if ( !empty($chDesc) ) {
            $writer->writeElement('description', $chDesc);
        }

        $all_feeds = get_option('klpsfefo_feeds_' . $this->channel, array());
        $feed_data       = $all_feeds[$feed_id];
        $mappings        = $feed_data['mappings'] ?? array();
        $feed_type       = $feed_data['feed_type'] ?? 'stream';

        $export_variants             = $feed_data['export_variants'] ?? 'no';
        $export_only_default_variant = $feed_data['export_variants_default'] ?? 'no';
        
        $general_settings = get_option('klpsfefo_feeds_general_settings', array());

        $is_premium_licensed = apply_filters('klpsfefo_feeds_is_premium_active', false);

        $post_types = array('product');
        if ( $export_variants === 'yes' && $export_only_default_variant === 'no' ) {
            $post_types[] = 'product_variation';
            $post_types = apply_filters('klpsfefo_feed_allowed_post_types', $post_types);
        }

        $posts = get_posts(array('post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1));

        foreach ( $posts as $post ) {
            
            $is_variation = ( $post->post_type === 'product_variation' );
            $product      = wc_get_product($post->ID);
            
            if ( !$product ) {
                continue;
            }

            if ( !KLPSFEFO_Helper::product_passes_filters($product, $saved_filters) ) {
                continue;
            }
            
            if ( !$is_variation && $product->is_type('variable') ) {
                
                if ( $export_variants === 'no' && $export_only_default_variant === 'no' ) {
                    continue;
                }
                if ( $export_variants === 'yes' && $export_only_default_variant === 'no' ) {
                    continue;
                }
                if ( $export_only_default_variant === 'yes' ) {
                    $default_attributes = $product->get_default_attributes();
                    
                    if ( !empty($default_attributes) ) {
                        $formatted_attributes = array();
                        foreach ( $default_attributes as $key => $value ) {
                            $formatted_attributes['attribute_' . $key] = $value;
                        }
                        
                        $data_store = $product->get_data_store();
                        $default_variant_id = $data_store->find_matching_product_variation($product, $formatted_attributes);
                        
                        if ( $default_variant_id > 0 ) {
                            $variant_product = wc_get_product($default_variant_id);
                            if ( $variant_product ) {
                                $product = $variant_product;
                                $post    = get_post($default_variant_id);
                                
                                // fix
                                $is_variation = true;
                            } else {
                                continue;
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }

            $item_data = array('id' => $post->ID, 'parent_id' => $post->post_parent);
            $item_data = apply_filters('klpsfefo_feeds_item_data', $item_data, $post);

            $writer->startElement('item');
            
            $id_val = $this->klpsfefo_get_mapped_value($post, 'id', $mappings);
            if ( empty($id_val) ) {
                $id_val = $is_variation ? $post->post_parent . '_' . $post->ID : $item_data['id'];
            }
            $writer->writeElement('g:id', $id_val);

            $title_val = $this->klpsfefo_get_mapped_value($post, 'title', $mappings);
            if ( empty($title_val) ) {
                $title_val = $post->post_title;
            }

            $attributes_product_id = false;
            $attribute_values = array();

            if ( $is_variation ) {
                $parent_product = wc_get_product($post->post_parent);
                $title_val      = $parent_product ? $parent_product->get_name() : $post->post_title;

                $variation_attributes = wc_get_product_variation_attributes($post->ID);
                if ( !empty($variation_attributes) ) {
                    $attribute_values = array_values($variation_attributes);
                }

            } else if ( $export_variants === 'yes' && $export_only_default_variant === 'yes' && $product->is_type('variable') ) {
                $default_attributes = $product->get_default_attributes();

                if ( ! empty( $default_attributes ) ) {
                    $attribute_values = array_values($default_attributes);
                }
            }
            if ( !empty($attribute_values) ) {
                $title_val .= ' - ' . implode(', ', $attribute_values);
            }

            $title_val = $this->klpsfefo_prepare_feed_cdata_text($title_val);
            if ( $this->channel == 'google' ) {
                $writer->startElement('g:title');
            } else {
                $writer->startElement('title');
            }
            $writer->writeCData($title_val);
            $writer->endElement();

            $desc_val = $this->klpsfefo_get_mapped_value($post, 'description', $mappings);
            if ( empty($desc_val) ) {
                $desc_val = !empty($post->post_content) ? $post->post_content : get_post_field('post_content', $post->post_parent);
            }
            $desc_val = $this->klpsfefo_prepare_feed_cdata_text($desc_val);
            
            if ( $this->channel == 'google' ) {
                $writer->startElement('g:description');
            } else {
                $writer->startElement('description');
            }
            $writer->writeCData($desc_val);
            $writer->endElement();

            $gtin_val = $this->klpsfefo_get_mapped_value($post, 'gtin', $mappings);
            if ( $gtin_val ) {
                $writer->writeElement('g:gtin', $gtin_val);
            }
            
            $mpn_val = $this->klpsfefo_get_mapped_value($post, 'mpn', $mappings);
            if ( empty($mpn_val) && $is_variation ) {
                $mpn_val = $product->get_sku(); 
            }
            if ( $mpn_val ) {
                $writer->writeElement('g:mpn', $mpn_val);
            }
            
            $item_group_id = $product->is_type('variation') ? $product->get_parent_id() : '';
            if ( !empty($item_group_id) && $export_variants === 'yes' ) {
                $writer->writeElement('g:item_group_id', $item_group_id);
            }
            
            // g:shipping_weight  
            $weight = $product->get_weight();
            $formatted_weight = '';
            if ( ! empty($weight) ) {
                $weight_unit = get_option('woocommerce_weight_unit', 'kg');
                $formatted_weight = number_format( (float)$weight, 2, '.', '' ) . ' ' . $weight_unit;
                $writer->writeElement('g:shipping_weight', $formatted_weight);
            }
            
            // g:color 
            $color = $product->get_attribute('pa_farbe') ? $product->get_attribute('pa_farbe') : $product->get_attribute('color');
            if ( !empty($color) ) {
                $writer->writeElement('g:color', $color);
            }
            
            // g:gender  
            $gender = $product->get_attribute('gender');
            if ( empty($gender) ) {
                $gender = $product->get_meta('_gender');
                if ( !empty($gender) ) {
                    $writer->writeElement('g:gender', $gender);
                }
            }
            
            $base_link = get_permalink($is_variation ? $post->post_parent : $post->ID);
            if ( $is_premium_licensed ) {
                // utm params premium
                $final_link = apply_filters('klpsfefo_feeds_generate_product_link', $base_link, $feed_data, $post, $is_variation);
            } else {
                $final_link = $base_link;
            }
            $final_link = html_entity_decode($final_link);
            
            if ( $this->channel == 'google' ) {
                $writer->writeElement('g:link', esc_url($final_link));
            } else {
                $writer->writeElement('link', esc_url($final_link));
            }
            
            // images
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            
            if ( empty($image_url) && $is_variation ) {
                $image_url = get_the_post_thumbnail_url($post->post_parent, 'full');
            }
            if ( !empty($image_url) ) {
                $writer->writeElement('g:image_link', esc_url($image_url));
            }

            $gallery_image_ids = $product->get_gallery_image_ids();

            if ( empty($gallery_image_ids) && $is_variation ) {
                $parent_id = $product->get_parent_id();
                if ( $parent_id > 0 ) {
                    if ( $parent_product ) {
                        $gallery_image_ids = $parent_product->get_gallery_image_ids();
                    }
                }
            }

            if ( !empty($gallery_image_ids) ) {
                $limit = 4;
                $counter = 0;

                foreach ( $gallery_image_ids as $image_id ) {
                    if ( $counter >= $limit ) {
                        break; 
                    }
                    $additional_image_url = wp_get_attachment_url($image_id);

                    if ( !empty($additional_image_url) ) {
                        $writer->writeElement('g:additional_image_link', esc_url($additional_image_url));
                        $counter++;
                    }
                }
            }

            // g:price
            $regular_price_val = $this->klpsfefo_get_mapped_value($post, 'price', $mappings);
            if ( empty($regular_price_val) ) {
                $regular_price_val = number_format((float)$product->get_price(), 2, '.', '') . ' ' . get_woocommerce_currency();
            } else {
                $regular_price_val = number_format((float)$regular_price_val, 2, '.', '') . ' ' . get_woocommerce_currency();
            }
            $writer->writeElement('g:price', $regular_price_val);
            
            // g:sale_price
            $sale_price_val = $this->klpsfefo_get_mapped_value($post, 'sale_price', $mappings);
            if ( intval($sale_price_val) > 0 || intval($product->get_sale_price()) > 0) { 
                if ( empty($sale_price_val) ) {
                    $sale_price_val = number_format((float)$product->get_sale_price(), 2, '.', '') . ' ' . get_woocommerce_currency();
                } else {
                    $sale_price_val = number_format((float)$sale_price_val, 2, '.', '') . ' ' . get_woocommerce_currency();
                }
                if ( !empty($sale_price_val) && $regular_price_val != $sale_price_val ) {
                    $writer->writeElement('g:sale_price', $sale_price_val);
                }
            }

            // brand
            $brand_val = $this->klpsfefo_get_mapped_value($post, 'brand', $mappings);
            
            if ( !empty($brand_val) ) {
                $writer->writeElement('g:brand', !empty($brand_val) ? esc_xml($brand_val) : '');
            }

            // shipping
            if ( isset($feed_data['export_shipping']) && $feed_data['export_shipping'] == 'yes' ) {
                $writer->startElement('g:shipping');

                $shipping_country = $feed_data['shipping_country'] ?? 'DE';
                $writer->writeElement('g:country', $shipping_country);

                if ( $product->is_virtual() || $product->is_downloadable() ) {
                    $shipping_price = '0.00';
                } else {
                    $mapped_shipping = $this->klpsfefo_get_mapped_value($post, 'shipping_price', $mappings);
                    if ( !empty($mapped_shipping) ) {
                        $shipping_price = number_format((float)$mapped_shipping, 2, '.', '');
                    } else {
                        $fallback_price = $feed_data['shipping_price'] ?? '4.90';
                        $shipping_price = number_format((float)$fallback_price, 2, '.', '');
                    }
                }
                $writer->writeElement('g:price', $shipping_price . ' ' . get_woocommerce_currency());
                $writer->endElement();
            }
            
            $availability = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
            $writer->writeElement('g:availability', $availability);
            
            $condition_val = $this->klpsfefo_get_mapped_value($post, 'condition', $mappings);
            if ( !empty($condition_val) ) {
                $writer->writeElement('g:condition', !empty($condition_val) ? esc_xml($condition_val) : '');
            }
            
            // google category mapping
            if ( $is_premium_licensed ) {
                $mapped_google_category = apply_filters('klpsfefo_feeds_google_cat_mapping', $general_settings, $product);
                
                if ( !empty($mapped_google_category) ) {
                    $writer->writeElement('g:google_product_category', esc_xml($mapped_google_category));
                }
            }

            $writer->endElement(); // </item>
        }
        
        $writer->endElement(); // </channel>
        $writer->endElement(); // </rss>
        $writer->endDocument();

        $writer->flush();

    }
    
    public function klpsfefo_prepare_feed_cdata_text($text) {
        if ( empty($text) ) {
            return '';
        }

        $text = str_replace(array('“', '”', '„', '“'), '"', $text);
        $text = str_replace(array('&amp;', '&AMP;'), '&', $text);
        $text = str_replace(array('&nbsp;', '&AMP;NBSP;', ' '), ' ', $text);
        $text = str_replace('::', ':', $text);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return trim($text);
    }

    /**
     * admin mapping
     */
    public function klpsfefo_get_mapped_value($post, $field_key, $mappings) {
        $source = $mappings[$field_key]['source'] ?? 'none';
        $custom_key = $mappings[$field_key]['custom'] ?? '';

        $product = wc_get_product($post->ID);

        if ( $product ) {

            if ( $source === 'post_id' ) {
                return $post->ID;
            }
            
            if ( $source === 'post_title' ) {
                return $product->get_name();
            }
            
            if ( $source === 'post_content' ) {
                $clean_description = wp_strip_all_tags($product->get_description());
                $clean_description = str_replace(array( "\r", "\n", "\t"), ' ', $clean_description);
                $clean_description = mb_strimwidth($clean_description, 0, 5000, "...");
                
                return $clean_description;
            }
            
            if ( $source === 'post_permalink' ) {
                return $product->get_permalink();
            }
            
            if ( $source === 'post_price' ) {
                return $product->get_regular_price();
            }
            if ( $source === 'post_sale_price' ) {
                return $product->get_sale_price();
            }
            
            if ( $source === 'static_val' && !empty($custom_key) ) {
                return $custom_key;
            }
            
            if ( $source === 'custom_field' && !empty($custom_key) ) {

                $custom_key = trim($custom_key);

                if ( strpos($custom_key, '*') !== false || strpos($custom_key, '/') !== false ) {

                    preg_match('/^\s*([a-zA-Z0-9_\-]+)\s*([\*\/])\s*([0-9\.]+)/', $custom_key, $matches);

                    if ( !empty($matches) ) {
                        $real_meta_key = trim($matches[1]);
                        $operator      = $matches[2];
                        $factor        = floatval($matches[3]);

                        $base_price = floatval(get_post_meta($post->ID, $real_meta_key, true));

                        if ($base_price === 0.0) {
                            $meta_value = '';
                        } else {
                            if ($operator === '*') {
                                $meta_value = $base_price * $factor;
                            } else if ($operator === '/') {
                                $meta_value = ($factor != 0) ? ($base_price / $factor) : $base_price; 
                            }
                            $meta_value = round($meta_value, 2);
                        }
                    } else {
                        $meta_value = get_post_meta($post->ID, $custom_key, true);
                    }

                } else {
                    $meta_value = get_post_meta($post->ID, $custom_key, true);
                }

                return $meta_value;
            }

            
            if ( $source === 'post_image_link' && !empty($custom_key) ) {
                $image_id   = $product->get_image_id();
                $image_link = $image_id ? wp_get_attachment_url($image_id) : '';
                
                return $image_link;
            }
            
            if ( $source === 'post_mpn' ) {
                return $product->get_sku();
            }
            
            if ( $source === 'post_gtin' ) {
                $ean = '';
                if ( method_exists($product, 'get_gtin') ) {
                     $ean = $product->get_gtin();
                     if ( !empty($ean) ) {
                         return $ean;
                     }
                 }

                 $fallback_keys = [
                     '_alg_ean',
                     '_ts_gtin',
                     'hwp_product_gtin',
                     '_wpm_gtin_code',
                     'gtin',
                     '_barcode'
                 ];

                 foreach ( $fallback_keys as $key ) {
                     $ean = $product->get_meta($key);
                     if ( !empty($ean) ) {
                         break;
                     }
                 }

                 return $ean;
            }
            
            if ( strpos($source, 'attribute_pa_') === 0 ) {
                $taxonomy = str_replace('attribute_', '', $source);
                return $product->get_attribute( $taxonomy );
            }
            if ( strpos($source, 'custom_val_') === 0 ) {
                return str_replace('custom_val_', '', $source);
            }

            if ( strpos($source, 'custom_attr_') === 0 ) {
                $attr_slug = str_replace('custom_attr_', '', $source);
                
                $value = $product->get_attribute($attr_slug);

                if ( empty($value) && $product->is_type('variation') ) {
                    $parent_product = wc_get_product($product->get_parent_id());
                    if ( $parent_product ) {
                        $value = $parent_product->get_attribute($attr_slug);
                    }
                }

                return $value;
            }
            
        }
        
        return '';
    }

    private function klpsfefo_writeIdealoCsvData($feed_id, $filters, $file_hash='') {
        
        $all_feeds       = get_option('klpsfefo_feeds_idealo', array());
        $feed_data       = $all_feeds[$feed_id] ?? array();
        $mappings        = $feed_data['mappings'] ?? array();

        $upload_dir = wp_upload_dir();
        $file_path  = $upload_dir['basedir'] . '/klp-feeds-csv/idealo-feed-' . $file_hash . '.csv';

        if ( !file_exists(dirname($file_path)) ) {
            wp_mkdir_p(dirname($file_path));
        }

        $file = @fopen($file_path, 'w');
        if ( !$file ) {
            return false;
        }

        $header = array(
            'id',
            'brand',
            'title',
            'description',
            'price',
            'link',
            'image_url',
            'delivery_time',
            'delivery_costs',
            'eans',
            'hans'
        );

        // UTF-8 BOM
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($file, $header, '|');

        $post_types          = array('product');
        $posts = get_posts(array('post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1));

        foreach ( $posts as $post ) {
            
            $product = wc_get_product($post->ID);
            if ( !$product ) {
                continue;
            }
            
            if ( !KLPSFEFO_Helper::product_passes_filters($product, $filters) ) {
                continue;
            }
            
            $item_data = array('id' => $post->ID, 'parent_id' => $post->post_parent);
            $item_data = apply_filters('klpsfefo_feeds_item_data', $item_data, $post);

            $id_val    = $this->klpsfefo_get_mapped_value($post, 'id', $mappings);
            $brand_val = $this->klpsfefo_get_mapped_value($post, 'brand', $mappings);
            $title_val = $this->klpsfefo_get_mapped_value($post, 'title', $mappings);
            $desc_val  = $this->klpsfefo_get_mapped_value($post, 'description', $mappings);
            $price_val = $this->klpsfefo_get_mapped_value($post, 'sale_price', $mappings);
            $gtin_val  = $this->klpsfefo_get_mapped_value($post, 'gtin', $mappings);
            $mpn_val   = $this->klpsfefo_get_mapped_value($post, 'mpn', $mappings);
            if ( $product->is_virtual() || $product->is_downloadable() ) {
                $shipping_price = '0.00';
            } else {
                $shipping_price = $feed_data['shipping_price'] ?? '4.90';
                $shipping_price = number_format((float)$shipping_price, 2, '.', '');
            }
            $delivery_time = ( !empty($feed_data['delivery_time']) ) ? $feed_data['delivery_time'] : '1-3 Werktage';

            $row = array(
                !empty($id_val) ? $id_val : $item_data['id'],
                !empty($brand_val) ? $brand_val : '', 
                !empty($title_val) ? $this->klpsfefo_prepare_feed_cdata_text($title_val) : $this->klpsfefo_prepare_feed_cdata_text($post->post_title),
                !empty($desc_val) ? $this->klpsfefo_prepare_feed_cdata_text($desc_val) : $this->klpsfefo_prepare_feed_cdata_text($post->post_content),
                !empty($price_val) ? $price_val : number_format(get_post_meta($post->ID, '_price', true), 2, '.', '') . ' ' . get_woocommerce_currency(),
                get_permalink($post->ID),
                get_the_post_thumbnail_url($post->ID, 'full'),
                $delivery_time,
                $shipping_price,
                !empty($gtin_val) ? $gtin_val : '',
                !empty($mpn_val) ? $mpn_val : ''
            );

            fputcsv($file, $row, '|');
        }

        fclose($file);
        
        return true;
    }
}