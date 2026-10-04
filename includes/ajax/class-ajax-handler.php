<?php
// Copyright (C) 2026 klp-soft. All rights reserved.

namespace Klpsoft\Feeds;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class KLPSFEFO_Feed_Ajax_Handler {

    private $is_premium = false;
    
    public function __construct() {
        
        $this->is_premium = apply_filters('klpsfefo_feeds_is_premium_active', false);
        
        add_action('wp_ajax_klpsfefo_save_klp_feed_settings', array($this, 'klpsfefo_process_save'));
        add_action('wp_ajax_klpsfefo_feed_generate_manually', array($this, 'klpsfefo_process_manual_generation'));
        add_action('wp_ajax_klpsfefo_create_new_feed', array($this, 'klpsfefo_create_new_feed_profile'));
        add_action('wp_ajax_klpsfefo_delete_feed', array($this, 'klpsfefo_delete_feed_profile'));
        add_action('wp_ajax_klpsfefo_save_feed_filters', array($this, 'klpsfefo_save_feed_filters'));
        add_action('wp_ajax_klpsfefo_save_feed_table_state', array($this, 'klpsfefo_save_feed_table_state'));
    }

    private function klpsfefo_check_security($nonce_action = 'klpsfefo_feeds_settings_nonce') {
        
        if ( !check_ajax_referer($nonce_action, 'nonce') ) {
            wp_send_json_error(array('message' => 'invalid nonce!'), 403);
        }

        if ( !current_user_can('manage_woocommerce') && !current_user_can('manage_options') ) {
            wp_send_json_error(array('message' => 'Not allowed!'), 403);
        }
    }

    public function klpsfefo_process_save() {
        
        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        if ( !isset($_POST['form_data']) && !isset($_POST['form_data_filters']) ) {
            wp_send_json_error(array('message' => 'missing data!'), 400);
        }
        
        parse_str($_POST['form_data'], $form_data);
        parse_str($_POST['form_data_filters'], $form_data_filters);
        
        $form_data = json_decode(sanitize_text_field(json_encode($form_data)), true);
        $form_data_filters = json_decode(sanitize_text_field(json_encode($form_data_filters)), true);
        
        if ( isset($form_data['klpsfefo-general-settings']) ) {
            $current_settings = get_option('klpsfefo_feeds_general_settings', array());
            if ( !is_array($current_settings) ) {
                $current_settings = array();
            }
            $logging_status = isset($form_data['klpsfefo_feeds_general_settings']['logging']) ? 'on' : 'off';
            $current_settings['logging'] = $logging_status;
            // google category mapping
            if ( $this->is_premium == true ) {
                $current_settings = apply_filters('klpsfefo_feeds_sanitize_premium_settings', $current_settings, $form_data);
            }

            update_option('klpsfefo_feeds_general_settings', $current_settings);
        }

        if ( isset($form_data['klpsfefo_current_feed_id']) ) {
            $feed_channel   = sanitize_text_field($form_data['klpsfefo_current_feed_channel']);
            $feed_id        =  absint($form_data['klpsfefo_current_feed_id']);
            
            if ( empty($feed_channel) ) {
                wp_send_json_error(array('message' => 'Invalid feed channel!'));
            }
            if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
                wp_send_json_error( array('message' => 'Invalid feed channel!'));
            }
            if ( $feed_id == 0 ) {
                wp_send_json_error( array('message' => 'Invalid feed id!'));
            }

            $all_feeds = get_option('klpsfefo_feeds_'. $feed_channel, array());

            if ( !isset($all_feeds[$feed_id]) ) {
                $all_feeds[$feed_id] = array();
            }

            $meta = $form_data['klpsfefo_feeds_meta'] ?? array();
            
            if ( isset($meta['new_feedname']) && !empty($meta['new_feedname']) ) {
                $all_feeds[$feed_id]['name']       = sanitize_text_field($meta['new_feedname'] ?? '');
                $all_feeds[$feed_id]['file_hash'] = KLPSFEFO_Helper::get_secure_feed_hash($feed_id);
            }
            $all_feeds[$feed_id]['feed_type']       = sanitize_text_field($meta['feed_type'] ?? 'file');
            $all_feeds[$feed_id]['feed_status']       = sanitize_text_field($meta['feed_status'] ?? 'active');
            
            if ( $this->is_premium == true && $feed_channel == 'google' ) {
                $all_feeds[$feed_id]['google_api_enabled'] = sanitize_text_field($meta['google_api_enabled'] ?? '0');
            }
            
            $all_feeds[$feed_id]['cron_interval']   = sanitize_text_field($meta['cron_interval'] ?? '24');
            $all_feeds[$feed_id]['export_variants'] = sanitize_text_field($meta['export_variants'] ?? 'no');
            $all_feeds[$feed_id]['export_variants_default'] = sanitize_text_field($meta['export_variants_default'] ?? 'no');
            // 
            $all_feeds[$feed_id]['feedlabel']            = strtoupper(sanitize_text_field($meta['feedlabel'] ?? 'DE'));

            $all_feeds[$feed_id]['export_shipping']     = sanitize_text_field($meta['export_shipping'] ?? '1');
            $all_feeds[$feed_id]['shipping_country']            = strtoupper(sanitize_text_field($meta['shipping_country'] ?? 'DE'));
            $all_feeds[$feed_id]['delivery_time']            = sanitize_text_field($meta['delivery_time'] ?? '');
            $all_feeds[$feed_id]['shipping_price']              = number_format((float)($meta['shipping_price'] ?? 4.90 ), 2, '.', '');
            
            // utm & datasource_id parameter      
            $all_feeds[$feed_id] = apply_filters('klpsfefo_feeds_sanitize_premium_feed_settings', $all_feeds[$feed_id], $meta);

            $submitted_rules = isset($form_data_filters['klpsfefo_filter_rules']) ? $form_data_filters['klpsfefo_filter_rules'] : array();
            $clean_rules     = array();

            if ( is_array($submitted_rules) ) {
                foreach ( $submitted_rules as $rule ) {
                    if ( !empty($rule['field']) ) {
                        $clean_rules[] = array(
                            'field'    => sanitize_text_field($rule['field']),
                            'operator' => sanitize_text_field($rule['operator']),
                            'value'    => sanitize_text_field($rule['value']),
                            'action'   => sanitize_text_field($rule['action']),
                        );
                    }
                }
            }
            $all_feeds[$feed_id]['filters'] = $clean_rules;
        
            $mappings = $form_data['klpsfefo_feed_mappings'] ?? array();
            $sanitized_mappings = array();
            foreach ( $mappings as $key => $values ) {
                $sanitized_mappings[$key] = array(
                    'source' => sanitize_text_field($values['source'] ?? 'none'),
                    'custom' => sanitize_text_field($values['custom'] ?? '')
                );
            }
            $all_feeds[$feed_id]['mappings'] = $sanitized_mappings;

            update_option('klpsfefo_feeds_'. $feed_channel, $all_feeds);

            if ( $all_feeds[$feed_id]['feed_type'] === 'file' ) {
                $generator = new KLPSFEFO_Feed_Generator($feed_channel);
                
                if ( $all_feeds[$feed_id]['feed_status'] == 'active' ) {
                    
                    $generator->klpsfefo_generate_feed_file($feed_id, $feed_channel, $clean_rules, $all_feeds[$feed_id]['file_hash']);
                    $generator->klpsfefo_setup_cronjob($feed_channel, $feed_id, true);
                } else {
                    $generator->klpsfefo_delete_cronjob($feed_channel, $feed_id);
                    
                }
            }
        }
        
        wp_send_json_success();
    }

    public function klpsfefo_process_manual_generation() {
        
        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        $feed_id = isset($_POST['feed_id']) ? absint($_POST['feed_id']) : 1;
        $feed_channel = isset($_POST['feed_channel']) ? sanitize_text_field($_POST['feed_channel']) : '';
        
        if ( empty($feed_channel) ) {
            wp_send_json_error(array('message' => 'Invalid feed channel!'));
        }
        if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
            wp_send_json_error( array('message' => 'Invalid feed channel!'));
        }
        
        $generator = new KLPSFEFO_Feed_Generator($feed_channel);
        $all_feeds = get_option('klpsfefo_feeds_'. $feed_channel, array());
        
        if ( $all_feeds[$feed_id]['feed_status'] != 'active' ) {
            wp_send_json_success(array('message' => __('Feed status inactive', 'klpsoft-feeds')));
        }
        
        $success = $generator->klpsfefo_generate_feed_file($feed_id, '', $all_feeds[$feed_id]['filters'], $all_feeds[$feed_id]['file_hash']);

        if ( $success ) {
            wp_send_json_success(array('message' => __('XML successfully generated!', 'klpsoft-feeds')));
        } else {
            wp_send_json_error(array('message' => __('Error writing file.', 'klpsoft-feeds')));
        }
    }
    
    public function klpsfefo_create_new_feed_profile() {

        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        $feed_name = isset($_POST['feed_name']) ? sanitize_text_field($_POST['feed_name']) : 'New Feed';
        $feed_channel = isset($_POST['feed_channel']) ? sanitize_text_field($_POST['feed_channel']) : '';

        if ( empty($feed_channel) ) {
            wp_send_json_error(array('message' => 'Invalid feed channel!'));
        }
        if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
            wp_send_json_error( array('message' => 'Invalid feed channel!'));
        }
        
        $all_feeds = get_option('klpsfefo_feeds_'. $feed_channel, array());

        $existing_ids = array_keys($all_feeds);
        $new_id = !empty($existing_ids) ? ( max($existing_ids) + 1 ) : 1;

        $all_feeds[$new_id] = array(
            'name'            => $feed_name,
            'feed_type'       => 'file',
            'file_hash'         => KLPSFEFO_Helper::get_secure_feed_hash($new_id),
            'feed_status'       => 'active',
            'cron_interval'   => '24',
            'export_variants' => 'no',
            'export_variants_default' => 'no',
            'mappings'        => array()       
        );
        if ( $this->is_premium == true && $feed_channel == 'google' ) {
            $all_feeds[$new_id]['google_api_enabled'] = '0';
        }

        update_option('klpsfefo_feeds_'. $feed_channel, $all_feeds);

        wp_send_json_success(array( 'new_id' => $new_id, 'feed_channel'=> $feed_channel));
    }

    public function klpsfefo_delete_feed_profile() {
        
        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        $feed_id      = isset($_POST['feed_id']) ? absint($_POST['feed_id']) : 0;
        $feed_channel = isset($_POST['feed_channel']) ? sanitize_text_field($_POST['feed_channel']) : '';

        if ( !$feed_id || empty($feed_channel) ) {
            wp_send_json_error(array('message' => 'Invalid Feed ID or Channel.'));
        }
        if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
            wp_send_json_error( array('message' => 'Invalid feed channel!'));
        }

        $option_key = 'klpsfefo_feeds_' . $feed_channel;
        $all_feeds  = get_option($option_key, array());

        if ( isset($all_feeds[$feed_id]) ) {
            unset($all_feeds[$feed_id]);

            update_option($option_key, $all_feeds);
            
            wp_clear_scheduled_hook('klpsfefo_feeds_event', array($feed_channel, $feed_id));

            wp_send_json_success(array('message' => 'Feed successfully deleted.'));
            
        } else {
            wp_send_json_error(array('message' => 'Feed profile not found.'));
        }
    }

    public function klpsfefo_save_feed_table_state() {
        
        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        $state      = isset($_POST['state']) ?  absint($_POST['state']) : 1;
        $feed_id      = isset($_POST['feed_id']) ?  absint($_POST['feed_id']) : 0;
        $feed_channel = isset($_POST['feed_channel']) ? sanitize_text_field($_POST['feed_channel']) : '';

        if ( !$feed_id || empty($feed_channel) ) {
            wp_send_json_error( array('message' => 'Invalid Feed-ID or channel.'));
        }
        if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
            wp_send_json_error( array('message' => 'Invalid feed channel!'));
        }
        
        $option_key = 'klpsfefo_feeds_' . $feed_channel;
        $all_feeds  = get_option($option_key, array());

        if ( !isset($all_feeds[$feed_id]) ) {
            wp_send_json_error(array('message' => 'Feed-profile not found!'));
        }

        $all_feeds[$feed_id]['mapping_tbl_open'] = $state;

        update_option($option_key, $all_feeds);

        wp_send_json_success(array('message' => 'Filter saved ok!'));
    }
    
    public function klpsfefo_save_feed_filters() {
        
        $this->klpsfefo_check_security('klpsfefo_feeds_settings_nonce');

        $feed_id      = isset($_POST['klpsfefo_current_feed_id']) ?  absint($_POST['klpsfefo_current_feed_id']) : 0;
        $feed_channel = isset($_POST['klpsfefo_current_feed_channel']) ? sanitize_text_field($_POST['klpsfefo_current_feed_channel']) : '';

        if ( !$feed_id || empty($feed_channel) ) {
            wp_send_json_error( array('message' => 'Feed-profile not found!'));
        }
        if ( !in_array($feed_channel, array('google', 'bing', 'idealo', 'meta', 'tiktok', 'amazon')) ) {
            wp_send_json_error( array('message' => 'Invalid feed channel!'));
        }

        $submitted_rules = isset($_POST['klpsfefo_filter_rules']) ? $_POST['klpsfefo_filter_rules'] : array();
        $clean_rules     = array();

        if ( is_array($submitted_rules) ) {
            foreach ( $submitted_rules as $rule ) {
                if ( !empty($rule['field']) ) {
                    $clean_rules[] = array(
                        'field'    => sanitize_text_field($rule['field']),
                        'operator' => sanitize_text_field($rule['operator']),
                        'value'    => sanitize_text_field($rule['value']),
                        'action'   => sanitize_text_field($rule['action']),
                    );
                }
            }
        }

        $option_key = 'klpsfefo_feeds_' . $feed_channel;
        $all_feeds  = get_option($option_key, array());

        if ( !isset($all_feeds[$feed_id]) ) {
            wp_send_json_error(array('message' => 'Feed-profile not found!'));
        }

        $all_feeds[$feed_id]['filters'] = $clean_rules;

        update_option($option_key, $all_feeds);

        wp_send_json_success(array('message' => 'Filter saved ok!'));
    }
}