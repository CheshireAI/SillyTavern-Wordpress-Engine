<?php
if (!defined('ABSPATH')) exit;

// Include UI components
require_once plugin_dir_path(__FILE__) . 'admin-settings-ui.php';

/**
 * Settings backup and restore system
 */
class PMV_Settings_Manager {
    
    private static $instance = null;
    private $settings_group = 'png_metadata_viewer_settings';
    private $backup_option = 'pmv_settings_backup';
    private $version_option = 'pmv_settings_version';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'maybe_restore_settings'));
        add_action('admin_init', array($this, 'maybe_backup_settings'));
        add_action('admin_init', array($this, 'migrate_old_settings'));
        add_action('wp_ajax_pmv_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_pmv_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_pmv_backup_settings', array($this, 'ajax_backup_settings'));
        add_action('wp_ajax_pmv_restore_settings', array($this, 'ajax_restore_settings'));
        add_action('wp_ajax_pmv_validate_settings', array($this, 'ajax_validate_settings'));
    }
    
    /**
     * Get all plugin settings
     */
    public function get_all_settings() {
        $settings = array();
        
        // Get all options that start with our prefix
        global $wpdb;
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'png_metadata_%' 
             OR option_name LIKE 'pmv_%' 
             OR option_name LIKE 'openai_%'"
        );
        
        foreach ($options as $option) {
            $settings[$option->option_name] = maybe_unserialize($option->option_value);
        }
        
        return $settings;
    }
    
    /**
     * Backup all current settings
     */
    public function backup_settings() {
        $settings = $this->get_all_settings();
        $backup = array(
            'timestamp' => current_time('timestamp'),
            'version' => get_option('pmv_version', 'unknown'),
            'settings' => $settings
        );
        
        update_option($this->backup_option, $backup);
        update_option($this->version_option, get_option('pmv_version', 'unknown'));
        
        return true;
    }
    
    /**
     * Restore settings from backup
     */
    public function restore_settings($backup_data = null) {
        if ($backup_data === null) {
            $backup_data = get_option($this->backup_option);
        }
        
        if (!$backup_data || !isset($backup_data['settings'])) {
            return false;
        }
        
        foreach ($backup_data['settings'] as $option_name => $option_value) {
            update_option($option_name, $option_value);
        }
        
        return true;
    }
    
    /**
     * Check if settings need to be restored
     */
    public function maybe_restore_settings() {
        // Only check on admin pages
        if (!is_admin()) {
            return;
        }
        
        $current_version = get_option('pmv_version', 'unknown');
        $backup_version = get_option($this->version_option, 'unknown');
        
        // If we have a backup but no current settings, restore them
        $backup = get_option($this->backup_option);
        if ($backup && $backup_version !== $current_version) {
            $this->restore_settings($backup);
            $this->backup_settings(); // Create new backup with restored settings
        }
    }
    
    /**
     * Backup settings when they might be at risk
     */
    public function maybe_backup_settings() {
        // Only backup on admin pages
        if (!is_admin()) {
            return;
        }
        
        $last_backup = get_option($this->backup_option . '_last_backup', 0);
        $current_time = current_time('timestamp');
        
        // Backup every hour
        if ($current_time - $last_backup > 3600) {
            $this->backup_settings();
            update_option($this->backup_option . '_last_backup', $current_time);
        }
    }
    
    /**
     * Migrate old settings to new format
     */
    public function migrate_old_settings() {
        // Only migrate on admin pages
        if (!is_admin()) {
            return;
        }
        
        // Check if we need to migrate
        $migration_version = get_option('pmv_settings_migration_version', '0');
        $current_version = '1.0'; // Increment this when adding new migrations
        
        if ($migration_version === $current_version) {
            return;
        }
        
        // Migration 1.0: Ensure all required settings exist
        if ($migration_version === '0') {
            $this->migrate_to_1_0();
            update_option('pmv_settings_migration_version', '1.0');
        }
        
        // Add more migrations here as needed
    }
    
    /**
     * Migration to version 1.0
     */
    private function migrate_to_1_0() {
        $default_settings = array(
            'png_metadata_cards_per_page' => 12,
            'png_metadata_filter1_title' => 'Category',
            'png_metadata_filter2_title' => 'Style',
            'png_metadata_filter3_title' => 'Creator',
            'png_metadata_filter4_title' => 'Version',
            'pmv_allow_guest_conversations' => 1,
            'pmv_auto_save_conversations' => 1,
            'pmv_max_conversations_per_user' => 50,
            'pmv_guest_daily_token_limit' => 5000,
            'pmv_default_user_monthly_limit' => 100000,
            'pmv_guest_daily_limit' => 50,
            'pmv_guest_monthly_limit' => 10000,
            'pmv_image_credits_per_dollar' => 100,
            'pmv_text_credits_per_dollar' => 10000,
            'pmv_image_product_name' => 'Image Credits',
            'pmv_text_product_name' => 'Text Credits',
            'pmv_image_product_description' => 'Purchase image generation credits. Credits never expire and are cumulative.',
            'pmv_text_product_description' => 'Purchase text generation tokens. Tokens never expire and are cumulative.',
            'pmv_daily_reward_enabled' => 1,
            'pmv_daily_image_reward' => 10,
            'pmv_daily_text_reward' => 1000,
            'pmv_max_free_image_credits' => 1000,
            'pmv_max_free_token_credits' => 100000,
            'openai_api_base_url' => 'https://api.openai.com/v1/',
            'openai_model' => 'gpt-3.5-turbo',
            'openai_temperature' => 0.7,
            'openai_max_tokens' => 1000,
            'openai_presence_penalty' => 0.6,
            'openai_frequency_penalty' => 0.3,
            'png_metadata_chat_button_text' => 'Chat',
            'png_metadata_chat_button_text_color' => '#ffffff',
            'png_metadata_chat_button_bg_color' => '#28a745'
        );
        
        foreach ($default_settings as $option => $value) {
            if (!get_option($option)) {
                update_option($option, $value);
            }
        }
        
        // Create initial backup
        $this->backup_settings();
    }
    
    /**
     * Validate and repair settings
     */
    public function validate_settings() {
        $issues = array();
        $repairs = 0;
        
        try {
            // Get all current settings
            $settings = $this->get_all_settings();
            
            // Check for common issues
            foreach ($settings as $option_name => $option_value) {
            // Check for empty required settings
            if (in_array($option_name, array('openai_api_key', 'openai_model')) && empty($option_value)) {
                $issues[] = "Required setting '$option_name' is empty";
            }
            
            // Check for invalid color values
            if (strpos($option_name, '_color') !== false && !empty($option_value)) {
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $option_value)) {
                    $issues[] = "Invalid color format for '$option_name': $option_value";
                    // Try to repair
                    if (preg_match('/^#[0-9a-fA-F]{3}$/', $option_value)) {
                        // Convert 3-digit hex to 6-digit
                        $new_value = '#' . $option_value[1] . $option_value[1] . $option_value[2] . $option_value[2] . $option_value[3] . $option_value[3];
                        update_option($option_name, $new_value);
                        $repairs++;
                    }
                }
            }
            
            // Check for invalid numeric values
            if (in_array($option_name, array('png_metadata_cards_per_page', 'openai_max_tokens')) && !empty($option_value)) {
                if (!is_numeric($option_value) || $option_value < 1) {
                    $issues[] = "Invalid numeric value for '$option_name': $option_value";
                    // Repair with default value
                    $default = ($option_name === 'png_metadata_cards_per_page') ? 12 : 1000;
                    update_option($option_name, $default);
                    $repairs++;
                }
            }
            }
        } catch (Exception $e) {
            // If validation fails, log the error and return safe defaults
            error_log('PMV Settings Validation Error: ' . $e->getMessage());
            $issues[] = "Settings validation encountered an error: " . $e->getMessage();
        }
        
        return array(
            'issues' => $issues,
            'repairs' => $repairs,
            'total_settings' => count($settings)
        );
    }
    
    /**
     * Export settings as JSON
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        $export_data = array(
            'export_date' => current_time('mysql'),
            'plugin_version' => get_option('pmv_version', 'unknown'),
            'settings' => $settings
        );
        
        return json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings from JSON
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (!$data || !isset($data['settings'])) {
            return false;
        }
        
        // Backup current settings first
        $this->backup_settings();
        
        // Import new settings
        foreach ($data['settings'] as $option_name => $option_value) {
            update_option($option_name, $option_value);
        }
        
        return true;
    }
    
    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $export_data = $this->export_settings();
        
        wp_send_json_success(array(
            'data' => $export_data,
            'filename' => 'pmv-settings-' . date('Y-m-d-H-i-s') . '.json'
        ));
    }
    
    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!isset($_POST['settings_data'])) {
            wp_send_json_error('No settings data provided');
        }
        
        $result = $this->import_settings($_POST['settings_data']);
        
        if ($result) {
            wp_send_json_success('Settings imported successfully');
        } else {
            wp_send_json_error('Failed to import settings');
        }
    }
    
    /**
     * AJAX handler for backing up settings
     */
    public function ajax_backup_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $result = $this->backup_settings();
        
        if ($result) {
            wp_send_json_success('Settings backed up successfully');
        } else {
            wp_send_json_error('Failed to backup settings');
        }
    }
    
    /**
     * AJAX handler for restoring settings
     */
    public function ajax_restore_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $result = $this->restore_settings();
        
        if ($result) {
            wp_send_json_success('Settings restored successfully');
        } else {
            wp_send_json_error('Failed to restore settings');
        }
    }
    
    /**
     * AJAX handler for validating settings
     */
    public function ajax_validate_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $validation_result = $this->validate_settings();
        
        wp_send_json_success(array(
            'message' => 'Settings validation completed',
            'issues' => $validation_result['issues'],
            'repairs' => $validation_result['repairs'],
            'total_settings' => $validation_result['total_settings']
        ));
    }
}

// Initialize settings manager
PMV_Settings_Manager::getInstance();

/**
 * Register plugin settings - Complete version with keyword flagging
 */
function pmv_register_plugin_settings() {
    // OpenAI Settings
    register_setting('png_metadata_viewer_settings', 'openai_api_key');
    register_setting('png_metadata_viewer_settings', 'openai_api_base_url');
    register_setting('png_metadata_viewer_settings', 'openai_model');
    register_setting('png_metadata_viewer_settings', 'openai_temperature');
    register_setting('png_metadata_viewer_settings', 'openai_max_tokens', array(
        'type' => 'integer',
        'default' => 1000,
        'sanitize_callback' => 'pmv_sanitize_max_tokens'
    ));
    register_setting('png_metadata_viewer_settings', 'openai_presence_penalty');
    register_setting('png_metadata_viewer_settings', 'openai_frequency_penalty');
    
    // Layout Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_cards_per_page');
    register_setting('png_metadata_viewer_settings', 'png_metadata_modal_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_modal_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_box_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_name_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_name_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_tags_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_tags_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_active_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_active_bg_color');
    
    // Filter Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter1_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter1_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter2_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter2_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter3_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter3_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter4_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter4_list');
    
    // Button Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_hover_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_text');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_link');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_hover_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_text');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_hover_bg_color');
    
    // Chat Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_name_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_name_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_input_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_input_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_user_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_user_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_bot_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_bot_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_history_font_size');
    
    // Gallery Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_margin');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_box_shadow');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_box_shadow');
    
    // Conversation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_allow_guest_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_auto_save_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_max_conversations_per_user');
    register_setting('png_metadata_viewer_settings', 'pmv_guest_daily_token_limit');
    register_setting('png_metadata_viewer_settings', 'pmv_default_user_monthly_limit');
    
    // Credit System Settings
    register_setting('png_metadata_viewer_settings', 'pmv_image_credits_per_dollar');
    register_setting('png_metadata_viewer_settings', 'pmv_text_credits_per_dollar');
    register_setting('png_metadata_viewer_settings', 'pmv_image_product_name');
    register_setting('png_metadata_viewer_settings', 'pmv_text_product_name');
    register_setting('png_metadata_viewer_settings', 'pmv_image_product_description');
    register_setting('png_metadata_viewer_settings', 'pmv_text_product_description');
    
    // Daily Reward Settings
    register_setting('png_metadata_viewer_settings', 'pmv_daily_image_reward');
    register_setting('png_metadata_viewer_settings', 'pmv_daily_text_reward');
    register_setting('png_metadata_viewer_settings', 'pmv_daily_reward_enabled');
    register_setting('png_metadata_viewer_settings', 'pmv_max_free_image_credits');
    register_setting('png_metadata_viewer_settings', 'pmv_max_free_token_credits');
    
    // Guest User Limits (for non-logged-in users)
    register_setting('png_metadata_viewer_settings', 'pmv_guest_daily_limit');
    register_setting('png_metadata_viewer_settings', 'pmv_guest_monthly_limit');
    
    // Keyword Flagging Settings
    register_setting('png_metadata_viewer_settings', 'pmv_unsafe_keywords', array(
        'sanitize_callback' => 'pmv_sanitize_unsafe_keywords'
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_enable_keyword_flagging');
    register_setting('png_metadata_viewer_settings', 'pmv_keyword_case_sensitive');
    
    // Image Generation Provider Settings
    register_setting('png_metadata_viewer_settings', 'pmv_image_provider');
    
    // SwarmUI API Settings
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_api_url');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_api_key');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_token');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_enabled');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_model');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_global_daily_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_global_monthly_limit', array(
        'type' => 'integer',
        'default' => 1000
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_daily_limit', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_monthly_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_guest_daily_limit', array(
        'type' => 'integer',
        'default' => 5
    ));
    
    // Nano-GPT API Settings
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_api_url');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_api_key');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_enabled');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_model');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_global_daily_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_global_monthly_limit', array(
        'type' => 'integer',
        'default' => 1000
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_user_daily_limit', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_user_monthly_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_guest_daily_limit', array(
        'type' => 'integer',
        'default' => 5
    ));
    
    // Image Generation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_steps', array(
        'type' => 'integer',
        'default' => 20
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_cfg_scale', array(
        'type' => 'number',
        'default' => 7.0
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_width', array(
        'type' => 'integer',
        'default' => 512
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_height', array(
        'type' => 'integer',
        'default' => 512
    ));
    
    // Nano-GPT Image Generation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_steps', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_scale', array(
        'type' => 'number',
        'default' => 7.5
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_width', array(
        'type' => 'integer',
        'default' => 1024
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_height', array(
        'type' => 'integer',
        'default' => 1024
    ));
}
add_action('admin_init', 'pmv_register_plugin_settings');

/**
 * Sanitize unsafe keywords
 */
function pmv_sanitize_unsafe_keywords($value) {
    if (empty($value)) return '';
    
    // Split by comma, trim whitespace, remove empty entries
    $keywords = array_filter(array_map('trim', explode(',', $value)));
    
    // Remove duplicates and sort
    $keywords = array_unique($keywords);
    sort($keywords);
    
    return implode(', ', $keywords);
}

/**
 * Sanitize max_tokens value to ensure it's within valid range
 */
function pmv_sanitize_max_tokens($value) {
    $value = intval($value);
    // Ensure value is within valid range for most AI APIs
    if ($value < 1) {
        $value = 1;
    }
    if ($value > 8192) {
        $value = 8192;  // Max supported by most APIs
    }
    return $value;
}

/**
 * Admin menu registration
 */
function pmv_add_admin_menu() {
    add_options_page(
        'PNG Metadata Settings',
        'PNG Metadata',
        'manage_options',
        'png-metadata-viewer',
        'pmv_admin_page_wrapper'
    );
    
    // Also add as a top-level menu for easier access
    add_menu_page(
        'PNG Metadata Viewer',
        'PNG Metadata',
        'manage_options',
        'png-metadata-viewer',
        'pmv_admin_page_wrapper',
        'dashicons-images-alt2',
        30
    );
}
add_action('admin_menu', 'pmv_add_admin_menu');

/**
 * Admin page wrapper with all tabs
 */
function pmv_admin_page_wrapper() {
    if (!current_user_can('manage_options')) return;
    
    // Debug: Check if subscription system is available
    $subscription_system_available = class_exists('PMV_Subscription_System');
    $woocommerce_available = class_exists('WooCommerce');
    
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'layout';
    $tabs = [
        'layout' => 'Layout & Filters',
        'buttons' => 'Buttons & Styling',
        'chat' => 'Chat & Modal',
        'gallery' => 'Gallery Styling',
        'openai' => 'OpenAI Settings',
        'conversations' => 'Conversations',
        'safety' => 'Content Safety',
        'content_moderation' => 'Content Moderation',
        'swarmui' => 'SwarmUI Settings',
        'subscriptions' => 'Credit System',
        'character_settings' => 'Character Settings',
        'upload' => 'Upload Files',
        'settings_backup' => 'Settings Backup'
    ];
    
            // Debug: Show system status at the top
        if ($current_tab === 'subscriptions') {
            echo '<div class="notice notice-info">';
            echo '<p><strong>Debug Info:</strong></p>';
            echo '<p>WooCommerce Available: ' . ($woocommerce_available ? 'Yes' : 'No') . '</p>';
            echo '<p>Subscription System Available: ' . ($subscription_system_available ? 'Yes' : 'No') . '</p>';
            echo '</div>';
        }
        
        // Show settings backup reminder
        if (class_exists('PMV_Settings_Manager') && $current_tab !== 'settings_backup') {
            try {
                $settings_manager = PMV_Settings_Manager::getInstance();
                $backup = get_option('pmv_settings_backup');
                $last_backup = get_option('pmv_settings_backup_last_backup', 0);
                $current_time = current_time('timestamp');
                
                // Show reminder if no backup or backup is old
                if (!$backup || ($current_time - $last_backup > 86400)) { // 24 hours
                    echo '<div class="notice notice-info is-dismissible">';
                    echo '<p><strong>Settings Protection:</strong> ';
                    if (!$backup) {
                        echo 'No settings backup found. ';
                    } else {
                        echo 'Settings backup is ' . human_time_diff($last_backup, $current_time) . ' old. ';
                    }
                    echo '<a href="?page=png-metadata-viewer&tab=settings_backup">Create a backup now</a> to protect your settings during updates.</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                // If reminder fails, show a safe message
                error_log('PMV Backup Reminder Error: ' . $e->getMessage());
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>Settings Protection:</strong> ';
                echo '<a href="?page=png-metadata-viewer&tab=settings_backup">Check your settings backup status</a> to ensure protection.</p>';
                echo '</div>';
            }
        }
    
    // Add tab for API testing
    if (current_user_can('administrator')) {
        $tabs['api_test'] = 'API Test';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Add shared JavaScript for settings preservation -->
        <script type="text/javascript">
        function pmvInitializeSettingsPreservation() {
            jQuery(document).ready(function($) {
                // Auto-backup settings when form is submitted
                $('#pmv-settings-form').on('submit', function() {
                    // Create a backup before saving
                    $.post(ajaxurl, {
                        action: 'pmv_backup_settings',
                        nonce: '<?= wp_create_nonce('pmv_settings_nonce') ?>'
                    }).done(function(response) {
                        if (response.success) {
                            console.log('Settings backed up before save');
                        }
                    });
                });
                
                // All possible settings that might not be on the current tab
                var allSettings = {
                    // OpenAI Settings
                    'openai_api_key': '<?= esc_js(get_option('openai_api_key', '')) ?>',
                    'openai_api_base_url': '<?= esc_js(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?>',
                    'openai_model': '<?= esc_js(get_option('openai_model', 'gpt-3.5-turbo')) ?>',
                    'openai_temperature': '<?= esc_js(get_option('openai_temperature', 0.7)) ?>',
                    'openai_max_tokens': '<?= esc_js(get_option('openai_max_tokens', 1000)) ?>',
                    'openai_presence_penalty': '<?= esc_js(get_option('openai_presence_penalty', 0.6)) ?>',
                    'openai_frequency_penalty': '<?= esc_js(get_option('openai_frequency_penalty', 0.3)) ?>',
                    
                    // Layout Settings
                    'png_metadata_cards_per_page': '<?= esc_js(get_option('png_metadata_cards_per_page', 12)) ?>',
                    'png_metadata_modal_bg_color': '<?= esc_js(get_option('png_metadata_modal_bg_color', '#1a1a1a')) ?>',
                    'png_metadata_modal_text_color': '<?= esc_js(get_option('png_metadata_modal_text_color', '#e0e0e0')) ?>',
                    'png_metadata_box_bg_color': '<?= esc_js(get_option('png_metadata_box_bg_color', '#f8f9fa')) ?>',
                    'png_metadata_card_name_text_color': '<?= esc_js(get_option('png_metadata_card_name_text_color', '#212529')) ?>',
                    'png_metadata_card_name_bg_color': '<?= esc_js(get_option('png_metadata_card_name_bg_color', '#e9ecef')) ?>',
                    'png_metadata_card_bg_color': '<?= esc_js(get_option('png_metadata_card_bg_color', '#ffffff')) ?>',
                    'png_metadata_card_tags_text_color': '<?= esc_js(get_option('png_metadata_card_tags_text_color', '#6c757d')) ?>',
                    'png_metadata_card_tags_bg_color': '<?= esc_js(get_option('png_metadata_card_tags_bg_color', '#f8f9fa')) ?>',
                    'png_metadata_pagination_button_text_color': '<?= esc_js(get_option('png_metadata_pagination_button_text_color', '#007bff')) ?>',
                    'png_metadata_pagination_button_bg_color': '<?= esc_js(get_option('png_metadata_pagination_button_bg_color', '#ffffff')) ?>',
                    'png_metadata_pagination_button_active_text_color': '<?= esc_js(get_option('png_metadata_pagination_button_active_text_color', '#ffffff')) ?>',
                    'png_metadata_pagination_button_active_bg_color': '<?= esc_js(get_option('png_metadata_pagination_button_active_bg_color', '#007bff')) ?>',
                    'png_metadata_filter1_title': '<?= esc_js(get_option('png_metadata_filter1_title', 'Category')) ?>',
                    'png_metadata_filter1_list': '<?= esc_js(get_option('png_metadata_filter1_list', 'General\nNSFW\nAnime\nRealistic')) ?>',
                    'png_metadata_filter2_title': '<?= esc_js(get_option('png_metadata_filter2_title', 'Style')) ?>',
                    'png_metadata_filter2_list': '<?= esc_js(get_option('png_metadata_filter2_list', 'Digital Art\nPainting\n3D Render\nPhotograph')) ?>',
                    'png_metadata_filter3_title': '<?= esc_js(get_option('png_metadata_filter3_title', 'Tags')) ?>',
                    'png_metadata_filter3_list': '<?= esc_js(get_option('png_metadata_filter3_list', 'Fantasy\nSci-Fi\nHorror\nRomance')) ?>',
                    'png_metadata_filter4_title': '<?= esc_js(get_option('png_metadata_filter4_title', 'Rating')) ?>',
                    'png_metadata_filter4_list': '<?= esc_js(get_option('png_metadata_filter4_list', 'Safe\nQuestionable\nExplicit')) ?>',
                    
                    // Button Settings
                    'png_metadata_button_text_color': '<?= esc_js(get_option('png_metadata_button_text_color', '#ffffff')) ?>',
                    'png_metadata_button_bg_color': '<?= esc_js(get_option('png_metadata_button_bg_color', '#007bff')) ?>',
                    'png_metadata_button_hover_text_color': '<?= esc_js(get_option('png_metadata_button_hover_text_color', '#ffffff')) ?>',
                    'png_metadata_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_button_hover_bg_color', '#0069d9')) ?>',
                    'png_metadata_secondary_button_text': '<?= esc_js(get_option('png_metadata_secondary_button_text', 'Learn More')) ?>',
                    'png_metadata_secondary_button_link': '<?= esc_js(get_option('png_metadata_secondary_button_link', '#')) ?>',
                    'png_metadata_secondary_button_text_color': '<?= esc_js(get_option('png_metadata_secondary_button_text_color', '#ffffff')) ?>',
                    'png_metadata_secondary_button_bg_color': '<?= esc_js(get_option('png_metadata_secondary_button_bg_color', '#6c757d')) ?>',
                    'png_metadata_secondary_button_hover_text_color': '<?= esc_js(get_option('png_metadata_secondary_button_hover_text_color', '#ffffff')) ?>',
                    'png_metadata_secondary_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_secondary_button_hover_bg_color', '#5a6268')) ?>',
                    'png_metadata_chat_button_text': '<?= esc_js(get_option('png_metadata_chat_button_text', 'Chat')) ?>',
                    'png_metadata_chat_button_text_color': '<?= esc_js(get_option('png_metadata_chat_button_text_color', '#ffffff')) ?>',
                    'png_metadata_chat_button_bg_color': '<?= esc_js(get_option('png_metadata_chat_button_bg_color', '#28a745')) ?>',
                    'png_metadata_chat_button_hover_text_color': '<?= esc_js(get_option('png_metadata_chat_button_hover_text_color', '#ffffff')) ?>',
                    'png_metadata_chat_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_chat_button_hover_bg_color', '#218838')) ?>',
                    
                    // Chat Settings
                    'png_metadata_chat_modal_bg_color': '<?= esc_js(get_option('png_metadata_chat_modal_bg_color', '#ffffff')) ?>',
                    'png_metadata_chat_modal_name_text_color': '<?= esc_js(get_option('png_metadata_chat_modal_name_text_color', '#ffffff')) ?>',
                    'png_metadata_chat_modal_name_bg_color': '<?= esc_js(get_option('png_metadata_chat_modal_name_bg_color', '#007bff')) ?>',
                    'png_metadata_chat_input_text_color': '<?= esc_js(get_option('png_metadata_chat_input_text_color', '#495057')) ?>',
                    'png_metadata_chat_input_bg_color': '<?= esc_js(get_option('png_metadata_chat_input_bg_color', '#ffffff')) ?>',
                    'png_metadata_chat_user_bg_color': '<?= esc_js(get_option('png_metadata_chat_user_bg_color', '#e2f1ff')) ?>',
                    'png_metadata_chat_user_text_color': '<?= esc_js(get_option('png_metadata_chat_user_text_color', '#333333')) ?>',
                    'png_metadata_chat_bot_bg_color': '<?= esc_js(get_option('png_metadata_chat_bot_bg_color', '#f0f0f0')) ?>',
                    'png_metadata_chat_bot_text_color': '<?= esc_js(get_option('png_metadata_chat_bot_text_color', '#212529')) ?>',
                    'png_metadata_chat_history_font_size': '<?= esc_js(get_option('png_metadata_chat_history_font_size', 14)) ?>',
                    
                    // Gallery Settings
                    'png_metadata_card_border_width': '<?= esc_js(get_option('png_metadata_card_border_width', '1')) ?>',
                    'png_metadata_card_border_color': '<?= esc_js(get_option('png_metadata_card_border_color', '#dee2e6')) ?>',
                    'png_metadata_card_border_radius': '<?= esc_js(get_option('png_metadata_card_border_radius', '0.25')) ?>',
                    'png_metadata_button_border_width': '<?= esc_js(get_option('png_metadata_button_border_width', '1')) ?>',
                    'png_metadata_button_border_color': '<?= esc_js(get_option('png_metadata_button_border_color', '#007bff')) ?>',
                    'png_metadata_button_border_radius': '<?= esc_js(get_option('png_metadata_button_border_radius', '0.25')) ?>',
                    'png_metadata_button_margin': '<?= esc_js(get_option('png_metadata_button_margin', '5')) ?>',
                    'png_metadata_button_padding': '<?= esc_js(get_option('png_metadata_button_padding', '8')) ?>',
                    'png_metadata_gallery_bg_color': '<?= esc_js(get_option('png_metadata_gallery_bg_color', '#ffffff')) ?>',
                    'png_metadata_gallery_padding': '<?= esc_js(get_option('png_metadata_gallery_padding', '20')) ?>',
                    'png_metadata_gallery_border_radius': '<?= esc_js(get_option('png_metadata_gallery_border_radius', '0')) ?>',
                    'png_metadata_gallery_border_width': '<?= esc_js(get_option('png_metadata_gallery_border_width', '0')) ?>',
                    'png_metadata_gallery_border_color': '<?= esc_js(get_option('png_metadata_gallery_border_color', '#dee2e6')) ?>',
                    'png_metadata_gallery_box_shadow': '<?= esc_js(get_option('png_metadata_gallery_box_shadow', '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)')) ?>',
                    'png_metadata_filters_bg_color': '<?= esc_js(get_option('png_metadata_filters_bg_color', '#f8f9fa')) ?>',
                    'png_metadata_filters_padding': '<?= esc_js(get_option('png_metadata_filters_padding', '15')) ?>',
                    'png_metadata_filters_border_radius': '<?= esc_js(get_option('png_metadata_filters_border_radius', '0.25')) ?>',
                    'png_metadata_filters_border_width': '<?= esc_js(get_option('png_metadata_filters_border_width', '1')) ?>',
                    'png_metadata_filters_border_color': '<?= esc_js(get_option('png_metadata_filters_border_color', '#dee2e6')) ?>',
                    'png_metadata_filters_box_shadow': '<?= esc_js(get_option('png_metadata_filters_box_shadow', '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)')) ?>',
                    
                    // SwarmUI API Settings
                    'pmv_swarmui_api_url': '<?= esc_js(get_option('pmv_swarmui_api_url', '')) ?>',
                    'pmv_swarmui_api_key': '<?= esc_js(get_option('pmv_swarmui_api_key', '')) ?>',
                    'pmv_swarmui_user_token': '<?= esc_js(get_option('pmv_swarmui_user_token', '')) ?>',
                    'pmv_swarmui_enabled': '<?= esc_js(get_option('pmv_swarmui_enabled', '0')) ?>',
                    'pmv_swarmui_default_model': '<?= esc_js(get_option('pmv_swarmui_default_model', 'OfficialStableDiffusion/sd_xl_base_1.0')) ?>',
                    'pmv_swarmui_global_daily_limit': '<?= esc_js(get_option('pmv_swarmui_global_daily_limit', '100')) ?>',
                    'pmv_swarmui_global_monthly_limit': '<?= esc_js(get_option('pmv_swarmui_global_monthly_limit', '1000')) ?>',
                    'pmv_swarmui_user_daily_limit': '<?= esc_js(get_option('pmv_swarmui_user_daily_limit', '10')) ?>',
                    'pmv_swarmui_user_monthly_limit': '<?= esc_js(get_option('pmv_swarmui_user_monthly_limit', '100')) ?>',
                    'pmv_swarmui_guest_daily_limit': '<?= esc_js(get_option('pmv_swarmui_guest_daily_limit', '5')) ?>',
                    'pmv_swarmui_default_steps': '<?= esc_js(get_option('pmv_swarmui_default_steps', '20')) ?>',
                    'pmv_swarmui_default_cfg_scale': '<?= esc_js(get_option('pmv_swarmui_default_cfg_scale', '7.0')) ?>',
                    'pmv_swarmui_default_width': '<?= esc_js(get_option('pmv_swarmui_default_width', '512')) ?>',
                    'pmv_swarmui_default_height': '<?= esc_js(get_option('pmv_swarmui_default_height', '512')) ?>',
                    
                    // Nano-GPT API Settings
                    'pmv_nanogpt_api_url': '<?= esc_js(get_option('pmv_nanogpt_api_url', '')) ?>',
                    'pmv_nanogpt_api_key': '<?= esc_js(get_option('pmv_nanogpt_api_key', '')) ?>',
                    'pmv_nanogpt_enabled': '<?= esc_js(get_option('pmv_nanogpt_enabled', '0')) ?>',
                    'pmv_nanogpt_default_model': '<?= esc_js(get_option('pmv_nanogpt_default_model', 'stabilityai/stable-diffusion-xl-base-1.0')) ?>',
                    'pmv_nanogpt_global_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_global_daily_limit', '100')) ?>',
                    'pmv_nanogpt_global_monthly_limit': '<?= esc_js(get_option('pmv_nanogpt_global_monthly_limit', '1000')) ?>',
                    'pmv_nanogpt_user_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_user_daily_limit', '10')) ?>',
                    'pmv_nanogpt_user_monthly_limit': '<?= esc_js(get_option('pmv_nanogpt_user_monthly_limit', '100')) ?>',
                    'pmv_nanogpt_guest_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_guest_daily_limit', '5')) ?>',
                    'pmv_nanogpt_default_steps': '<?= esc_js(get_option('pmv_nanogpt_default_steps', '10')) ?>',
                    'pmv_nanogpt_default_scale': '<?= esc_js(get_option('pmv_nanogpt_default_scale', '7.5')) ?>',
                    'pmv_nanogpt_default_width': '<?= esc_js(get_option('pmv_nanogpt_default_width', '1024')) ?>',
                    'pmv_nanogpt_default_height': '<?= esc_js(get_option('pmv_nanogpt_default_height', '1024')) ?>',
                    
                    // Image Generation Provider Settings
                    'pmv_image_provider': '<?= esc_js(get_option('pmv_image_provider', 'swarmui')) ?>',
                    
                    // Keyword Flagging Settings
                    'pmv_enable_keyword_flagging': '<?= esc_js(get_option('pmv_enable_keyword_flagging', '0')) ?>',
                    'pmv_keyword_case_sensitive': '<?= esc_js(get_option('pmv_keyword_case_sensitive', '0')) ?>',
                    
                    // Content Moderation Settings
                    'pmv_enable_content_moderation': '<?= esc_js(get_option('pmv_enable_content_moderation', '1')) ?>',
                    'pmv_strict_moderation_mode': '<?= esc_js(get_option('pmv_strict_moderation_mode', '0')) ?>',
                    'pmv_content_moderation_notification_email': '<?= esc_js(get_option('pmv_content_moderation_notification_email', get_option('admin_email', ''))) ?>',
                    'pmv_content_moderation_scan_interval': '<?= esc_js(get_option('pmv_content_moderation_scan_interval', '3600')) ?>',
                    
                    // Conversation Settings
                    'pmv_allow_guest_conversations': '<?= esc_js(get_option('pmv_allow_guest_conversations', '1')) ?>',
                    'pmv_auto_save_conversations': '<?= esc_js(get_option('pmv_auto_save_conversations', '1')) ?>',
                    'pmv_max_conversations_per_user': '<?= esc_js(get_option('pmv_max_conversations_per_user', '50')) ?>',
                    'pmv_guest_daily_token_limit': '<?= esc_js(get_option('pmv_guest_daily_token_limit', '1000')) ?>',
                    'pmv_default_user_monthly_limit': '<?= esc_js(get_option('pmv_default_user_monthly_limit', '10000')) ?>',
                    
                    // Subscription System Settings
                    'pmv_image_credits_per_dollar': '<?= esc_js(get_option('pmv_image_credits_per_dollar', '100')) ?>',
                    'pmv_text_credits_per_dollar': '<?= esc_js(get_option('pmv_text_credits_per_dollar', '10000')) ?>',
                    'pmv_image_product_name': '<?= esc_js(get_option('pmv_image_product_name', 'Image Credits')) ?>',
                    'pmv_text_product_name': '<?= esc_js(get_option('pmv_text_product_name', 'Text Credits')) ?>',
                    'pmv_image_product_description': '<?= esc_js(get_option('pmv_image_product_description', 'Purchase image generation credits. Credits never expire and are cumulative.')) ?>',
                    'pmv_text_product_description': '<?= esc_js(get_option('pmv_text_product_description', 'Purchase text generation tokens. Tokens never expire and are cumulative.')) ?>',
                    'pmv_daily_image_reward': '<?= esc_js(get_option('pmv_daily_image_reward', '5')) ?>',
                    'pmv_daily_text_reward': '<?= esc_js(get_option('pmv_daily_text_reward', '100')) ?>',
                    'pmv_daily_reward_enabled': '<?= esc_js(get_option('pmv_daily_reward_enabled', '1')) ?>',
                    'pmv_guest_daily_limit': '<?= esc_js(get_option('pmv_guest_daily_limit', '5')) ?>',
                    'pmv_guest_monthly_limit': '<?= esc_js(get_option('pmv_guest_monthly_limit', '50')) ?>',
                    'pmv_unsafe_keywords': '<?= esc_js(get_option('pmv_unsafe_keywords', 'nsfw, explicit, adult, porn, nude, sex, violence, gore, blood, death, suicide, self-harm, drugs, alcohol, tobacco, gambling, illegal, crime, terrorism, hate, racism, sexism, homophobia, transphobia, bullying, harassment, abuse, torture, mutilation, dismemberment, cannibalism, necrophilia, bestiality, incest, pedophilia, rape, murder, assault, battery, theft, fraud, corruption, bribery, extortion, blackmail, kidnapping, human trafficking, slavery, forced labor, child labor, animal cruelty, animal testing, vivisection, hunting, fishing, deforestation, pollution, climate change, global warming, nuclear war, biological warfare, chemical warfare, cyber warfare, espionage, treason, sedition, rebellion, revolution, civil war, genocide, ethnic cleansing, apartheid, segregation, discrimination, prejudice, bigotry, intolerance, extremism, radicalism, fundamentalism, cult, brainwashing, mind control, hypnosis, manipulation, coercion, blackmail, extortion, intimidation, threats, harassment, stalking, cyberbullying, trolling, flaming, spamming, phishing, hacking, cracking, piracy, copyright infringement, trademark violation, patent infringement, trade secret theft, industrial espionage, corporate espionage, government espionage, military espionage, diplomatic espionage, economic espionage, scientific espionage, technological espionage, cultural espionage, social espionage, psychological espionage, ideological espionage, religious espionage, political espionage, financial espionage, commercial espionage, industrial espionage, corporate espionage, government espionage, military espionage, diplomatic espionage, economic espionage, scientific espionage, technological espionage, cultural espionage, social espionage, psychological espionage, ideological espionage, religious espionage, political espionage, financial espionage, commercial espionage')) ?>'
                };
                
                // Before form submission, add hidden fields for all settings not in current tab
                $('#pmv-settings-form').on('submit', function() {
                    var currentFormFields = {};
                    
                    // Track which fields are already in the form
                    $(this).find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name && name !== 'submit' && name !== '_wpnonce' && name !== '_wp_http_referer' && name !== 'option_page' && name !== 'action') {
                            currentFormFields[name] = true;
                        }
                    });
                    
                    // Add hidden fields for missing settings
                    var hiddenContainer = $('#pmv-hidden-fields');
                    hiddenContainer.empty(); // Clear any previous hidden fields
                    
                    $.each(allSettings, function(settingName, settingValue) {
                        if (!currentFormFields[settingName]) {
                            var input = $('<input>').attr({
                                type: 'hidden',
                                name: settingName,
                                value: settingValue
                            });
                            hiddenContainer.append(input);
                        }
                    });
                });
            });
        }
        </script>
        
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                <a href="?page=png-metadata-viewer&tab=<?= esc_attr($tab_id) ?>" 
                   class="nav-tab <?= $current_tab === $tab_id ? 'nav-tab-active' : '' ?>">
                    <?= esc_html($tab_name) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="pmv-tab-content">
            <?php if ($current_tab === 'upload') : ?>
                <?php pmv_upload_files_tab_content(); ?>
            <?php elseif ($current_tab === 'api_test') : ?>
                <?php pmv_api_test_tab_content(); ?>
            <?php elseif ($current_tab === 'safety') : ?>
                <?php pmv_safety_tab_content(); ?>
            <?php elseif ($current_tab === 'swarmui') : ?>
                <form method="post" action="options.php" id="pmv-settings-form">
                    <?php
                    settings_fields('png_metadata_viewer_settings');
                    do_settings_sections('png-metadata-viewer-' . $current_tab);
                    pmv_swarmui_tab_content();
                    ?>
                    
                    <!-- Hidden fields container for preserving other tab settings -->
                    <div id="pmv-hidden-fields"></div>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <script type="text/javascript">
                pmvInitializeSettingsPreservation();
                </script>
            <?php elseif ($current_tab === 'subscriptions') : ?>
                <form method="post" action="options.php" id="pmv-settings-form">
                    <?php
                    settings_fields('png_metadata_viewer_settings');
                    do_settings_sections('png-metadata-viewer-' . $current_tab);
                    pmv_subscriptions_tab_content();
                    ?>
                    
                    <!-- Hidden fields container for preserving other tab settings -->
                    <div id="pmv-hidden-fields"></div>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <script type="text/javascript">
                pmvInitializeSettingsPreservation();
                </script>
            <?php elseif ($current_tab === 'conversations') : ?>
                <?php pmv_render_tab_content($current_tab); ?>
            <?php elseif ($current_tab === 'content_moderation') : ?>
                <?php pmv_content_moderation_tab_content(); ?>
            <?php elseif ($current_tab === 'settings_backup') : ?>
                <?php pmv_settings_backup_tab_content(); ?>
            <?php elseif ($current_tab === 'character_settings') : ?>
                <?php pmv_character_settings_tab_content(); ?>
            <?php else : ?>
                <form method="post" action="options.php" id="pmv-settings-form">
                    <?php
                    settings_fields('png_metadata_viewer_settings');
                    do_settings_sections('png-metadata-viewer-' . $current_tab);
                    pmv_render_tab_content($current_tab);
                    ?>
                    
                    <!-- Hidden fields container for preserving other tab settings -->
                    <div id="pmv-hidden-fields"></div>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <script type="text/javascript">
                pmvInitializeSettingsPreservation();
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render tab content
 */
function pmv_render_tab_content($tab) {
    switch ($tab) {
        case 'layout':
            pmv_layout_tab_content();
            break;
        case 'buttons':
            pmv_buttons_tab_content();
            break;
        case 'chat':
            pmv_chat_tab_content();
            break;
        case 'gallery':
            pmv_gallery_tab_content();
            break;
        case 'openai':
            pmv_openai_tab_content();
            break;
        case 'conversations':
            pmv_conversations_tab_content();
            break;
    }
}

/**
 * API Test tab content
 */
function pmv_api_test_tab_content() {
    ?>
    <div id="pmv-api-test">
        <h3>API Configuration Test</h3>
        <p>Test your API connection and settings:</p>
        
        <div id="api-test-results" style="margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <div id="test-status">Ready to test...</div>
            <div id="test-details" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>
        </div>
        
        <button id="test-api-btn" class="button button-primary">Test API Connection</button>
        
        <h3>Token Counter Test</h3>
        <p>Test the token counter functionality:</p>
        
        <div id="token-test-results" style="margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <div id="token-test-status">Ready to test...</div>
        </div>
        
        <button id="test-token-counter-btn" class="button button-secondary">Test Token Counter</button>
        
        <h4>Current Settings</h4>
        <ul>
            <li><strong>API Base URL:</strong> <?= esc_html(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?></li>
            <li><strong>Model:</strong> <?= esc_html(get_option('openai_model', 'deepseek-chat')) ?></li>
            <li><strong>Temperature:</strong> <?= esc_html(get_option('openai_temperature', 0.7)) ?></li>
            <li><strong>Max Tokens:</strong> <?= esc_html(get_option('openai_max_tokens', 1000)) ?></li>
            <li><strong>API Key:</strong> <?= get_option('openai_api_key') ? 'Set (***hidden***)' : 'Not set' ?></li>
        </ul>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-api-btn').click(function() {
            const button = $(this);
            const results = $('#api-test-results');
            const status = $('#test-status');
            const details = $('#test-details');
            
            button.prop('disabled', true).text('Testing...');
            status.text('Testing API connection...');
            details.text('');
            results.css('background', '#fff3cd');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_api_directly',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.text('✅ API Test Successful!');
                        results.css('background', '#d4edda');
                        
                        if (response.data && response.data.response) {
                            const apiResponse = response.data.response;
                            details.html(
                                '<strong>Response Details:</strong><br>' +
                                'Model: ' + (apiResponse.model || 'N/A') + '<br>' +
                                'Usage: ' + JSON.stringify(apiResponse.usage || {}) + '<br>' +
                                'Response: ' + (apiResponse.choices && apiResponse.choices[0] 
                                    ? apiResponse.choices[0].message.content 
                                    : 'No response content')
                            );
                        }
                    } else {
                        status.text('❌ API Test Failed');
                        results.css('background', '#f8d7da');
                        details.text('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    status.text('❌ Connection Error');
                    results.css('background', '#f8d7da');
                    details.text('Network error: ' + error);
                },
                complete: function() {
                    button.prop('disabled', false).text('Test API Connection');
                }
            });
        });
        
        // Token counter test
        $('#test-token-counter-btn').click(function() {
            const button = $(this);
            const results = $('#token-test-results');
            const status = $('#token-test-status');
            
            button.prop('disabled', true).text('Testing...');
            status.text('Testing token counter...');
            results.css('background', '#fff3cd');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_token_counter',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.text('✅ Token Counter Test Successful!');
                        results.css('background', '#d4edda');
                    } else {
                        status.text('❌ Token Counter Test Failed');
                        results.css('background', '#f8d7da');
                    }
                },
                error: function(xhr, status, error) {
                    status.text('❌ Connection Error');
                    results.css('background', '#f8d7da');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Token Counter');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for admin conversation deletion
 */
function pmv_ajax_admin_delete_conversation() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_admin_delete_conversation')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Validate and sanitize conversation ID
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    if ($conversation_id <= 0) {
        wp_send_json_error(['message' => 'Invalid conversation ID']);
        return;
    }
    
    // Delete conversation using the function from conversations-database.php
    $result = pmv_delete_conversation($conversation_id);
    
    if ($result) {
        wp_send_json_success(['message' => 'Conversation deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete conversation']);
    }
}

/**
 * AJAX handler for testing API directly
 */
function pmv_ajax_test_api_directly() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $api_key = get_option('openai_api_key', '');
    $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1/');
    $model = get_option('openai_model', 'gpt-3.5-turbo');
    $temperature = floatval(get_option('openai_temperature', 0.7));
    $max_tokens = intval(get_option('openai_max_tokens', 4000));
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key not configured']);
        return;
    }
    
    // Prepare test request
    $test_data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello! This is a test message to verify the API connection. Please respond with a brief confirmation.'
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => min($max_tokens, 1000) // Limit for test
    ];
    
    // Make API request
    $response = wp_remote_post(rtrim($api_base_url, '/') . '/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($test_data)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Request error: ' . $response->get_error_message()]);
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        $error_data = json_decode($response_body, true);
        $error_message = 'HTTP ' . $response_code;
        
        if (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
        }
        
        wp_send_json_error(['message' => $error_message]);
        return;
    }
    
    $api_response = json_decode($response_body, true);
    
    if (!$api_response) {
        wp_send_json_error(['message' => 'Invalid JSON response from API']);
        return;
    }
    
    wp_send_json_success([
        'message' => 'API test successful!',
        'response' => $api_response
    ]);
}

/**
 * AJAX handler for PNG file upload
 */
function pmv_ajax_upload_png() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_upload_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed');
        return;
    }
    
    $file = $_FILES['file'];
    
    // Additional file validation
    if (!is_uploaded_file($file['tmp_name'])) {
        wp_send_json_error('Invalid file upload');
        return;
    }
    
    // Validate file type using multiple methods
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($mime_type !== 'image/png') {
        wp_send_json_error('Only PNG files are allowed');
        return;
    }
    
    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'png') {
        wp_send_json_error('Only PNG files are allowed');
        return;
    }
    
    // Validate file size (e.g., max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        wp_send_json_error('File size too large (max 10MB)');
        return;
    }
    
    // Validate minimum file size (prevent empty files)
    if ($file['size'] < 100) {
        wp_send_json_error('File appears to be empty or corrupted');
        return;
    }
    
    // Create png-cards directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    
    if (!file_exists($png_cards_dir)) {
        if (!wp_mkdir_p($png_cards_dir)) {
            wp_send_json_error('Failed to create png-cards directory');
            return;
        }
    }
    
    // Generate unique filename to prevent conflicts
    $filename = sanitize_file_name($file['name']);
    $file_path = trailingslashit($png_cards_dir) . $filename;
    
    // If file already exists, add timestamp to make it unique
    if (file_exists($file_path)) {
        $path_parts = pathinfo($filename);
        $timestamp = time();
        $filename = $path_parts['filename'] . '_' . $timestamp . '.' . $path_parts['extension'];
        $file_path = trailingslashit($png_cards_dir) . $filename;
    }
    
    // Move uploaded file to png-cards directory
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_error('Failed to move uploaded file to png-cards directory');
        return;
    }
    
    // Set proper file permissions
    chmod($file_path, 0644);
    
    // Generate URL for the file
    $file_url = trailingslashit($upload_dir['baseurl']) . 'png-cards/' . $filename;
    
    // Try to extract metadata using the proper metadata reader
    $metadata = null;
    if (class_exists('PNG_Metadata_Reader')) {
        $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file_path);
    } else {
        // Fallback to basic metadata extraction
        $metadata = pmv_extract_png_metadata($file_path);
    }
    
    // Clear any caches to ensure the new card appears
    if (class_exists('PMV_Cache_Handler')) {
        PMV_Cache_Handler::clear_all_caches();
    }
    
    // Trigger action for other plugins/themes to hook into
    do_action('pmv_character_uploaded', $file_path, $file_url, $metadata);
    
    if ($metadata) {
        wp_send_json_success([
            'message' => 'Character card uploaded and metadata extracted successfully',
            'file_url' => $file_url,
            'file_path' => $file_path,
            'metadata' => $metadata
        ]);
    } else {
        wp_send_json_success([
            'message' => 'Character card uploaded but no metadata found - file may not be a valid character card',
            'file_url' => $file_url,
            'file_path' => $file_path
        ]);
    }
}

/**
 * Helper function to extract PNG metadata (basic implementation)
 */
function pmv_extract_png_metadata($file_path) {
    // This is a basic implementation - you would expand this based on your needs
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Try to read PNG chunks for metadata
    $handle = fopen($file_path, 'rb');
    if (!$handle) {
        return false;
    }
    
    // Skip PNG signature
    fseek($handle, 8);
    
    $metadata = [];
    
    while (!feof($handle)) {
        $chunk_length = unpack('N', fread($handle, 4))[1];
        $chunk_type = fread($handle, 4);
        
        if ($chunk_type === 'tEXt' || $chunk_type === 'iTXt') {
            $chunk_data = fread($handle, $chunk_length);
            
            if ($chunk_type === 'tEXt') {
                $null_pos = strpos($chunk_data, "\0");
                if ($null_pos !== false) {
                    $key = substr($chunk_data, 0, $null_pos);
                    $value = substr($chunk_data, $null_pos + 1);
                    $metadata[$key] = $value;
                }
            }
        } else {
            fseek($handle, $chunk_length, SEEK_CUR);
        }
        
        // Skip CRC
        fseek($handle, 4, SEEK_CUR);
        
        // Stop at IEND chunk
        if ($chunk_type === 'IEND') {
            break;
        }
    }
    
    fclose($handle);
    
    return !empty($metadata) ? $metadata : false;
}

// Register AJAX handlers
add_action('wp_ajax_pmv_admin_delete_conversation', 'pmv_ajax_admin_delete_conversation');
add_action('wp_ajax_pmv_test_api_directly', 'pmv_ajax_test_api_directly');
add_action('wp_ajax_pmv_upload_png', 'pmv_ajax_upload_png');
add_action('wp_ajax_pmv_register_existing_cards', 'pmv_ajax_register_existing_cards');

/**
 * AJAX handler for registering existing character card files
 * Useful for files uploaded via SCP or other direct methods
 */
function pmv_ajax_register_existing_cards() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_register_cards_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Get the png-cards directory
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    
    if (!file_exists($png_cards_dir)) {
        wp_send_json_error('png-cards directory not found. Please upload at least one character card first.');
        return;
    }
    
    // Scan for PNG files
    $png_files = glob($png_cards_dir . '*.png');
    
    if (empty($png_files)) {
        wp_send_json_error('No PNG files found in png-cards directory');
        return;
    }
    
    // If just counting, return count only
    if (isset($_POST['just_count']) && $_POST['just_count'] === 'true') {
        wp_send_json_success(array(
            'total_processed' => count($png_files),
            'message' => 'Directory scan complete'
        ));
        return;
    }
    
    $processed = 0;
    $success = 0;
    $errors = 0;
    $results = array();
    
    foreach ($png_files as $file_path) {
        $processed++;
        $filename = basename($file_path);
        
        try {
            // Try to extract metadata
            $metadata = null;
            if (class_exists('PNG_Metadata_Reader')) {
                $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file_path);
            } else {
                $metadata = pmv_extract_png_metadata($file_path);
            }
            
            if ($metadata) {
                $success++;
                $results[] = array(
                    'file' => $filename,
                    'status' => 'success',
                    'message' => 'Metadata extracted successfully'
                );
            } else {
                $errors++;
                $results[] = array(
                    'file' => $filename,
                    'status' => 'warning',
                    'message' => 'No metadata found - may not be a valid character card'
                );
            }
        } catch (Exception $e) {
            $errors++;
            $results[] = array(
                'file' => $filename,
                'status' => 'error',
                'message' => 'Error processing file: ' . $e->getMessage()
            );
        }
    }
    
    // Clear caches to ensure all cards are visible
    if (class_exists('PMV_Cache_Handler')) {
        PMV_Cache_Handler::clear_all_caches();
    }
    
    wp_send_json_success(array(
        'message' => "Processed $processed files: $success successful, $errors with issues",
        'total_processed' => $processed,
        'successful' => $success,
        'errors' => $errors,
        'results' => $results
    ));
}

/**
 * Content Moderation Tab Content
 */
function pmv_content_moderation_tab_content() {
    // Get content moderation instance
    global $pmv_content_moderation;
    
    echo '<div class="notice notice-info"><p>Debug: Content moderation tab loaded. Global variable: ' . (isset($pmv_content_moderation) ? 'set' : 'not set') . ', Class exists: ' . (class_exists('PMV_Content_Moderation') ? 'yes' : 'no') . '</p></div>';
    
    if (!isset($pmv_content_moderation) || !class_exists('PMV_Content_Moderation')) {
        echo '<div class="notice notice-error"><p>Content moderation system not available.</p></div>';
        return;
    }
    
    // Get current status
    $last_scan = get_option('pmv_last_content_scan', 0);
    $next_scan = $last_scan + (8 * 3600); // 8 hours
    $enabled = get_option('pmv_enable_content_moderation', 1);
    
    ?>
    <div class="wrap content-moderation-page">
        <h2>Content Moderation System</h2>
        
        <div class="card">
            <h3>Status</h3>
            <div class="status-compact">
                <div class="status-item">
                    <strong>Moderation System</strong>
                    <span><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></span>
                </div>
                <div class="status-item">
                    <strong>Last Scan</strong>
                    <span><?php echo $last_scan ? date('Y-m-d H:i:s', $last_scan) : 'Never'; ?></span>
                </div>
                <div class="status-item">
                    <strong>Next Scan</strong>
                    <span><?php echo date('Y-m-d H:i:s', $next_scan); ?></span>
                </div>
                <div class="status-item">
                    <strong>Scan Interval</strong>
                    <span>Every 8 hours</span>
                </div>
            </div>
            
            <?php
            // Show database status
            global $wpdb;
            $conversations_table = $wpdb->prefix . 'pmv_conversations';
            $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
            $conversations_count = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
            $messages_count = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");
            ?>
            <div class="database-status">
                <strong>Conversations:</strong> <span><?php echo $conversations_count; ?></span>
            </div>
            <div class="database-status">
                <strong>Messages:</strong> <span><?php echo $messages_count; ?></span>
            </div>
            
            <div class="button-group">
                <button type="button" class="button button-primary" id="run-scan">Run Manual Scan</button>
                <button type="button" class="button button-secondary" id="test-system">Test System</button>
                <span id="scan-status"></span>
                <span id="test-status"></span>
            </div>
            
            <p><em>Note: Manual scans will scan ALL conversations, not just recent ones.</em></p>
        </div>
        
        <div class="card">
            <h3>Recent Moderation Log</h3>
            <div id="moderation-log">
                <p>Loading...</p>
            </div>
            <div class="button-group">
                <button type="button" class="button button-secondary" id="clear-log">Clear Log</button>
                <button type="button" class="button button-secondary" id="test-ajax">Test AJAX</button>
            </div>
        </div>
        
        <div class="card">
            <h3>Settings</h3>
            <div class="notice notice-info">
                <p><strong>Note:</strong> When saving content moderation settings, all your existing plugin settings (including OpenAI API key, layout settings, etc.) will be preserved automatically.</p>
            </div>
                    <form method="post" action="options.php" id="pmv-content-moderation-form" data-nonce="<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>">
            <?php settings_fields('png_metadata_viewer_settings'); ?>
                
                <!-- Hidden fields container for preserving other tab settings -->
                <div id="pmv-hidden-fields"></div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Content Moderation</th>
                        <td>
                            <input type="checkbox" name="pmv_enable_content_moderation" value="1" <?php checked(1, $enabled); ?> />
                            <p class="description">Enable automatic content scanning</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Notification Email</th>
                        <td>
                            <input type="email" name="pmv_content_moderation_notification_email" value="<?php echo esc_attr(get_option('pmv_content_moderation_notification_email', get_option('admin_email'))); ?>" class="regular-text" />
                            <p class="description">Email address for moderation alerts</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#run-scan').click(function() {
            var button = $(this);
            var status = $('#scan-status');
            
            button.prop('disabled', true);
            status.html('Running scan...');
            
            $.post(ajaxurl, {
                action: 'pmv_run_manual_scan',
                nonce: '<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    status.html('Scan completed: ' + response.data.message);
                    location.reload();
                } else {
                    status.html('Scan failed: ' + response.data.message);
                }
            })
            .fail(function() {
                status.html('Scan failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false);
            });
        });
        
        $('#test-system').click(function() {
            var button = $(this);
            var status = $('#test-status');
            
            button.prop('disabled', true);
            status.html('Testing system...');
            
            $.post(ajaxurl, {
                action: 'pmv_test_system',
                nonce: '<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    status.html('Test completed: ' + response.data.message);
                } else {
                    status.html('Test failed: ' + response.data.message);
                }
            })
            .fail(function() {
                status.html('Test failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false);
            });
        });
        
        // Load moderation log
        loadModerationLog();
        
        // Debug: Log the nonce being used
        console.log('Content moderation form nonce:', $('#pmv-content-moderation-form').data('nonce'));
        console.log('Form element found:', $('#pmv-content-moderation-form').length);
        
        // Generate nonce for AJAX calls
        var ajaxNonce = '<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>';
        console.log('Generated AJAX nonce:', ajaxNonce);
        
        // Ensure ajaxurl is defined
        if (typeof ajaxurl === 'undefined') {
            console.error('ajaxurl is not defined! Trying to set it manually...');
            if (typeof window.ajaxurl !== 'undefined') {
                ajaxurl = window.ajaxurl;
                console.log('Using window.ajaxurl:', ajaxurl);
            } else {
                // Try to construct the AJAX URL manually
                var currentUrl = window.location.href;
                var adminUrl = currentUrl.split('/wp-admin/')[0] + '/wp-admin/admin-ajax.php';
                ajaxurl = adminUrl;
                console.log('Constructed ajaxurl manually:', ajaxurl);
            }
        }
        
        console.log('Final ajaxurl value:', ajaxurl);
        
        // Test if we can reach the AJAX endpoint
        console.log('Testing AJAX endpoint accessibility...');
        $.get(ajaxurl + '?action=pmv_test_ajax&nonce=' + ajaxNonce)
            .done(function(response) {
                console.log('AJAX endpoint test successful:', response);
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX endpoint test failed:', {xhr: xhr, status: status, error: error});
            });
        
        // Handle form submission to preserve all settings
        $('#pmv-content-moderation-form').submit(function() {
            // Use the shared settings preservation function
            pmvInitializeSettingsPreservation();
        });
        
        $('#clear-log').click(function() {
            console.log('Clear log button clicked');
            console.log('ajaxNonce value:', ajaxNonce);
            console.log('ajaxurl value:', ajaxurl);
            
            // Create custom confirmation dialog instead of browser confirm
            var confirmDialog = $('<div id="clear-log-confirm" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999; display: flex; align-items: center; justify-content: center;">' +
                '<div style="background: #2d3748; border: 2px solid #e53e3e; border-radius: 8px; padding: 30px; max-width: 500px; text-align: center; color: #e2e8f0;">' +
                '<h3 style="color: #e53e3e; margin-top: 0;">⚠️ Clear Moderation Log</h3>' +
                '<p style="font-size: 16px; line-height: 1.5; margin-bottom: 25px;">' +
                'This action will <strong>permanently delete ALL moderation log entries</strong> and cannot be undone.<br><br>' +
                'Are you absolutely sure you want to proceed?' +
                '</p>' +
                '<div style="display: flex; gap: 15px; justify-content: center;">' +
                '<button id="confirm-clear-log" style="background: #e53e3e; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold;">Yes, Clear Log</button>' +
                '<button id="cancel-clear-log" style="background: #4a5568; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 16px; cursor: pointer;">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(confirmDialog);
            console.log('Custom confirmation dialog created');
            
            // Handle confirmation
            $('#confirm-clear-log').click(function() {
                console.log('User confirmed via custom dialog, sending AJAX request...');
                confirmDialog.remove();
                
                // Disable the button and show loading state
                var button = $('#clear-log');
                var originalText = button.text();
                button.prop('disabled', true).text('Clearing...');
                
                $.post(ajaxurl, {
                    action: 'pmv_clear_moderation_log',
                    nonce: ajaxNonce
                })
                .done(function(response) {
                    console.log('Clear log response received:', response);
                    console.log('Response type:', typeof response);
                    console.log('Response success:', response.success);
                    
                    if (response.success) {
                        console.log('Log cleared successfully, reloading...');
                        alert('✅ Log cleared successfully! The moderation log has been emptied.');
                        loadModerationLog();
                    } else {
                        console.error('Clear log failed:', response.data.message);
                        alert('❌ Failed to clear log: ' + response.data.message);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('AJAX request failed:', {xhr: xhr, status: status, error: error});
                    console.error('XHR status:', xhr.status);
                    console.error('XHR responseText:', xhr.responseText);
                    
                    // Try to get more details about the failure
                    if (xhr.status === 0) {
                        console.error('AJAX request was aborted or failed to connect');
                    } else if (xhr.status === 500) {
                        console.error('Server error occurred');
                    } else if (xhr.status === 403) {
                        console.error('Access forbidden - possible nonce issue');
                    }
                    
                    alert('❌ Failed to clear log: ' + error);
                })
                .always(function() {
                    // Re-enable the button
                    button.prop('disabled', false).text(originalText);
                });
            });
            
            // Handle cancellation
            $('#cancel-clear-log').click(function() {
                console.log('User cancelled via custom dialog');
                confirmDialog.remove();
            });
            
            // Close on overlay click
            confirmDialog.click(function(e) {
                if (e.target === this) {
                    console.log('User clicked overlay, closing dialog');
                    confirmDialog.remove();
                }
            });
        });
        
        // Add test AJAX button
        $('#test-ajax').click(function() {
            console.log('Test AJAX button clicked');
            console.log('Testing basic AJAX functionality...');
            
            $.post(ajaxurl, {
                action: 'pmv_test_ajax',
                nonce: ajaxNonce
            })
            .done(function(response) {
                console.log('Test AJAX response:', response);
                if (response.success) {
                    alert('AJAX system is working! Response: ' + response.data.message);
                } else {
                    alert('Test AJAX failed: ' + response.data.message);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Test AJAX failed:', {xhr: xhr, status: status, error: error});
                alert('Test AJAX failed: ' + error);
            });
        });
        
        function loadModerationLog() {
            console.log('loadModerationLog called');
            
            $.post(ajaxurl, {
                action: 'pmv_get_moderation_log',
                nonce: '<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>'
            })
            .done(function(response) {
                console.log('Moderation log response:', response);
                if (response.success) {
                    $('#moderation-log').html(response.data.html);
                    
                    // Add click handlers for view conversation buttons
                    $('.view-conversation').click(function() {
                        console.log('View conversation button clicked');
                        var logId = $(this).data('log-id');
                        console.log('Log ID:', logId);
                        viewConversationDetails(logId);
                    });
                } else {
                    $('#moderation-log').html('<p>Error loading log: ' + response.data.message + '</p>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load moderation log:', {xhr: xhr, status: status, error: error});
                $('#moderation-log').html('<p>Failed to load log: ' + error + '</p>');
            });
        }
        
        function viewConversationDetails(logId) {
            console.log('viewConversationDetails called with logId:', logId);
            
            // Use the nonce generated in the main scope
            var nonce = ajaxNonce;
            console.log('Using nonce for conversation details:', nonce);
            
            $.post(ajaxurl, {
                action: 'pmv_get_conversation_details',
                log_id: logId,
                nonce: nonce
            })
            .done(function(response) {
                console.log('AJAX response received:', response);
                console.log('Response type:', typeof response);
                console.log('Response keys:', Object.keys(response));
                console.log('Response data:', response.data);
                
                if (response.success) {
                    // Create modal with conversation details
                    showConversationModal(response.data.html);
                } else {
                    alert('Error loading conversation details: ' + response.data.message);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX request failed:', {xhr: xhr, status: status, error: error});
                alert('Failed to load conversation details: ' + error);
            });
        }
        
        function showConversationModal(content) {
            console.log('showConversationModal called with content length:', content.length);
            console.log('Content preview:', content.substring(0, 200) + '...');
            
            // Remove existing modal if any
            $('#conversation-modal').remove();
            console.log('Existing modal removed');
            
            // Create modal with proper structure and inline styles
            var modal = $('<div id="conversation-modal" class="conversation-modal-overlay">' +
                '<div class="conversation-modal" style="width: 90%; max-width: 800px; max-height: 90vh; height: auto; overflow: hidden; display: flex; flex-direction: column;">' +
                '<div class="conversation-modal-header" style="flex-shrink: 0; min-height: 60px;">' +
                '<h3>Conversation Details</h3>' +
                '<button type="button" class="close-modal">&times;</button>' +
                '</div>' +
                '<div class="conversation-modal-content" style="width: 100%; max-width: 100%; max-height: calc(90vh - 120px); height: auto; overflow-x: hidden; overflow-y: auto; word-wrap: break-word; overflow-wrap: break-word; flex: 1; min-height: 0;">' + content + '</div>' +
                '</div>' +
                '</div>');
            
            console.log('Modal HTML created, length:', modal.length);
            
            // Add to page
            $('body').append(modal);
            console.log('Modal appended to body');
            
            // Ensure modal is properly displayed with strict width constraints
            modal.css({
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center'
            });
            
            // Initialize image handling after modal is shown
            setTimeout(function() {
                initializeImageHandling();
            }, 100);
            
            // Force modal content to respect width constraints
            modal.find('.conversation-modal-content').css({
                'width': '100%',
                'max-width': '100%',
                'overflow-x': 'hidden',
                'word-wrap': 'break-word',
                'overflow-wrap': 'break-word',
                'word-break': 'break-word'
            });
            
            // Force all content within to respect width constraints
            modal.find('.conversation-modal-content *').css({
                'max-width': '100%',
                'word-wrap': 'break-word',
                'overflow-wrap': 'break-word',
                'word-break': 'break-word'
            });
            
            // Debug modal dimensions
            setTimeout(function() {
                var modalElement = $('#conversation-modal');
                var modalContent = modalElement.find('.conversation-modal');
                var contentArea = modalElement.find('.conversation-modal-content');
                
                console.log('Modal dimensions debug:');
                console.log('Modal overlay width:', modalElement.width());
                console.log('Modal content width:', modalContent.width());
                console.log('Content area width:', contentArea.width());
                console.log('Modal content scrollWidth:', contentArea[0].scrollWidth);
                console.log('Modal content clientWidth:', contentArea[0].clientWidth);
                console.log('Modal content offsetWidth:', contentArea[0].offsetWidth);
                
                // Force additional constraints if needed
                if (contentArea[0].scrollWidth > contentArea[0].clientWidth) {
                    console.log('Content overflow detected, applying additional constraints');
                    contentArea.css({
                        'overflow-x': 'hidden',
                        'word-wrap': 'break-word',
                        'overflow-wrap': 'break-word',
                        'word-break': 'break-word'
                    });
                }
            }, 100);
            
            // Test if modal is visible
            console.log('Modal element exists:', $('#conversation-modal').length);
            console.log('Modal CSS display:', $('#conversation-modal').css('display'));
            console.log('Modal CSS visibility:', $('#conversation-modal').css('visibility'));
            console.log('Modal CSS z-index:', $('#conversation-modal').css('z-index'));
            
            // Close modal handlers
            modal.find('.close-modal').click(function() {
                console.log('Close button clicked');
                modal.fadeOut(function() {
                    modal.remove();
                });
            });
            
            // Close on overlay click
            modal.click(function(e) {
                if (e.target === this) {
                    console.log('Overlay clicked, closing modal');
                    modal.fadeOut(function() {
                        modal.remove();
                    });
                }
            });
            
            // Add escape key handler
            $(document).on('keydown.modal', function(e) {
                if (e.key === 'Escape') {
                    console.log('Escape key pressed, closing modal');
                    modal.fadeOut(function() {
                        modal.remove();
                    });
                    $(document).off('keydown.modal');
                }
            });
            
            // Add debug rescan button handler
            modal.find('#debug-rescan').click(function() {
                var button = $(this);
                var conversationId = button.data('conversation-id');
                var originalText = button.text();
                
                console.log('Debug rescan clicked for conversation:', conversationId);
                
                button.prop('disabled', true).text('Re-scanning...');
                
                $.post(ajaxurl, {
                    action: 'pmv_debug_rescan_conversation',
                    conversation_id: conversationId,
                    nonce: ajaxNonce
                })
                .done(function(response) {
                    console.log('Debug rescan response:', response);
                    if (response.success) {
                        alert('Re-scan completed successfully!\n\nNew result:\n' + 
                              'Flagged: ' + response.data.flagged + '\n' +
                              'Risk Level: ' + response.data.risk_level + '\n' +
                              'Category: ' + response.data.risk_category + '\n' +
                              'Description: ' + response.data.risk_description + '\n' +
                              'Raw AI Response: ' + response.data.ai_response_raw);
                        
                        // Refresh the modal content
                        viewConversationDetails(button.closest('.conversation-details').data('log-id'));
                    } else {
                        alert('Re-scan failed: ' + response.data.message);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Debug rescan failed:', error);
                    alert('Re-scan failed: ' + error);
                })
                .always(function() {
                    button.prop('disabled', false).text(originalText);
                });
            });
        }
        
        // Initialize image handling for the conversation modal
        function initializeImageHandling() {
            $('.generated-image').each(function() {
                var $img = $(this);
                var $container = $img.closest('.generated-image-container');
                
                // Add loading state
                $container.addClass('loading');
                
                // Handle image load
                $img.on('load', function() {
                    $container.removeClass('loading');
                });
                
                // Handle image error
                $img.on('error', function() {
                    $container.removeClass('loading').addClass('error');
                    console.error('Image failed to load:', $img.attr('src'));
                });
                
                // Add click to enlarge functionality
                $img.on('click', function() {
                    showImageEnlarged($img.attr('src'));
                });
            });
        }
        
        // Show image in enlarged view
        function showImageEnlarged(imageSrc) {
            var enlargedModal = $('<div class="image-enlarged-modal">' +
                '<div class="image-enlarged-content">' +
                '<span class="image-enlarged-close">&times;</span>' +
                '<img src="' + imageSrc + '" alt="Enlarged Image" />' +
                '</div>' +
                '</div>');
            
            $('body').append(enlargedModal);
            
            // Close on click
            enlargedModal.on('click', function() {
                enlargedModal.remove();
            });
            
            // Prevent closing when clicking on the image
            enlargedModal.find('img').on('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    </script>
    <?php
}

/**
 * Settings Backup Tab Content
 */
function pmv_settings_backup_tab_content() {
    $settings_manager = PMV_Settings_Manager::getInstance();
    $backup = get_option('pmv_settings_backup');
    $last_backup = get_option('pmv_settings_backup_last_backup', 0);
    $current_settings = $settings_manager->get_all_settings();
    
    ?>
    <div class="wrap settings-backup-page">
        <h2>Settings Backup & Restore</h2>
        
        <div class="notice notice-info">
            <p><strong>Important:</strong> This system automatically backs up your settings to prevent data loss during plugin updates. 
            You can also manually export/import settings or restore from the last backup.</p>
        </div>
        
        <div class="card">
            <h3>Current Status</h3>
            <div class="status-info">
                <p><strong>Total Settings:</strong> <?php echo count($current_settings); ?> options</p>
                <p><strong>Last Backup:</strong> <?php echo $last_backup ? date('Y-m-d H:i:s', $last_backup) : 'Never'; ?></p>
                <p><strong>Backup Available:</strong> <?php echo $backup ? 'Yes' : 'No'; ?></p>
                <?php if ($backup): ?>
                    <p><strong>Backup Date:</strong> <?php echo date('Y-m-d H:i:s', $backup['timestamp']); ?></p>
                    <p><strong>Backup Version:</strong> <?php echo esc_html($backup['version']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="button-group">
                <button type="button" class="button button-primary" id="backup-now">Backup Settings Now</button>
                <button type="button" class="button button-secondary" id="restore-backup" <?php echo !$backup ? 'disabled' : ''; ?>>Restore from Backup</button>
                <button type="button" class="button button-secondary" id="validate-settings">Validate Settings</button>
                <span id="backup-status"></span>
            </div>
        </div>
        
        <div class="card">
            <h3>Export/Import Settings</h3>
            <p>Export your current settings to a JSON file for safekeeping, or import settings from a previously exported file.</p>
            
            <div class="export-section">
                <h4>Export Settings</h4>
                <p>Download a complete backup of all your current settings.</p>
                <button type="button" class="button button-primary" id="export-settings">Export Settings</button>
            </div>
            
            <div class="import-section">
                <h4>Import Settings</h4>
                <p>Restore settings from a previously exported JSON file.</p>
                <input type="file" id="import-file" accept=".json" style="margin: 10px 0;">
                <br>
                <button type="button" class="button button-secondary" id="import-settings">Import Settings</button>
                <span id="import-status"></span>
            </div>
        </div>
        
        <div class="card">
            <h3>Automatic Backup System</h3>
            <p>Your settings are automatically backed up:</p>
            <ul>
                <li><strong>Hourly:</strong> Automatic backup every hour</li>
                <li><strong>On Save:</strong> Backup when you save any settings</li>
                <li><strong>On Update:</strong> Backup before plugin updates</li>
                <li><strong>On Restore:</strong> Backup before restoring from backup</li>
            </ul>
            
            <div class="notice notice-warning">
                <p><strong>Note:</strong> The automatic backup system stores your settings in the WordPress database. 
                For additional safety, use the export feature to save a copy to your computer.</p>
            </div>
        </div>
        
        <div class="card">
            <h3>Settings Summary</h3>
            <div class="settings-summary">
                <?php
                $categories = array(
                    'OpenAI' => array_filter(array_keys($current_settings), function($key) { return strpos($key, 'openai_') === 0; }),
                    'Layout' => array_filter(array_keys($current_settings), function($key) { return strpos($key, 'png_metadata_') === 0; }),
                    'Plugin' => array_filter(array_keys($current_settings), function($key) { return strpos($key, 'pmv_') === 0; })
                );
                
                foreach ($categories as $category => $settings) {
                    if (!empty($settings)) {
                        echo '<div class="category">';
                        echo '<h4>' . esc_html($category) . ' (' . count($settings) . ')</h4>';
                        echo '<ul>';
                        foreach (array_slice($settings, 0, 5) as $setting) {
                            echo '<li>' . esc_html($setting) . '</li>';
                        }
                        if (count($settings) > 5) {
                            echo '<li><em>... and ' . (count($settings) - 5) . ' more</em></li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Backup settings now
        $('#backup-now').click(function() {
            var button = $(this);
            var status = $('#backup-status');
            
            button.prop('disabled', true);
            status.html('Creating backup...');
            
            $.post(ajaxurl, {
                action: 'pmv_backup_settings',
                nonce: '<?php echo wp_create_nonce('pmv_settings_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    status.html('✅ ' + response.data);
                    location.reload();
                } else {
                    status.html('❌ ' + response.data);
                }
            })
            .fail(function() {
                status.html('❌ Backup failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false);
            });
        });
        
        // Restore from backup
        $('#restore-backup').click(function() {
            if (!confirm('Are you sure you want to restore from the last backup? This will overwrite your current settings.')) {
                return;
            }
            
            var button = $(this);
            var status = $('#backup-status');
            
            button.prop('disabled', true);
            status.html('Restoring from backup...');
            
            $.post(ajaxurl, {
                action: 'pmv_restore_settings',
                nonce: '<?php echo wp_create_nonce('pmv_settings_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    status.html('✅ ' + response.data);
                    location.reload();
                } else {
                    status.html('❌ ' + response.data);
                }
            })
            .fail(function() {
                status.html('❌ Restore failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false);
            });
        });
        
        // Export settings
        $('#export-settings').click(function() {
            var button = $(this);
            
            button.prop('disabled', true).text('Exporting...');
            
            $.post(ajaxurl, {
                action: 'pmv_export_settings',
                nonce: '<?php echo wp_create_nonce('pmv_settings_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.data], {type: 'application/json'});
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    alert('Export failed: ' + response.data);
                }
            })
            .fail(function() {
                alert('Export failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false).text('Export Settings');
            });
        });
        
        // Validate settings
        $('#validate-settings').click(function() {
            var button = $(this);
            var status = $('#backup-status');
            
            button.prop('disabled', true);
            status.html('Validating settings...');
            
            $.post(ajaxurl, {
                action: 'pmv_validate_settings',
                nonce: '<?php echo wp_create_nonce('pmv_settings_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    var message = '✅ Settings validation completed!\n\n';
                    message += 'Total settings: ' + response.data.total_settings + '\n';
                    message += 'Issues found: ' + response.data.issues.length + '\n';
                    message += 'Auto-repairs: ' + response.data.repairs + '\n';
                    
                    if (response.data.issues.length > 0) {
                        message += '\nIssues:\n';
                        response.data.issues.forEach(function(issue) {
                            message += '• ' + issue + '\n';
                        });
                    }
                    
                    alert(message);
                    status.html('✅ Settings validated');
                } else {
                    status.html('❌ ' + response.data);
                }
            })
            .fail(function() {
                status.html('❌ Validation failed: Network error');
            })
            .always(function() {
                button.prop('disabled', false);
            });
        });
        
        // Import settings
        $('#import-settings').click(function() {
            var fileInput = $('#import-file')[0];
            var status = $('#import-status');
            
            if (!fileInput.files.length) {
                alert('Please select a file to import');
                return;
            }
            
            var file = fileInput.files[0];
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var button = $('#import-settings');
                button.prop('disabled', true).text('Importing...');
                status.html('Importing settings...');
                
                $.post(ajaxurl, {
                    action: 'pmv_import_settings',
                    nonce: '<?php echo wp_create_nonce('pmv_settings_nonce'); ?>',
                    settings_data: e.target.result
                })
                .done(function(response) {
                    if (response.success) {
                        status.html('✅ ' + response.data);
                        location.reload();
                    } else {
                        status.html('❌ ' + response.data);
                    }
                })
                .fail(function() {
                    status.html('❌ Import failed: Network error');
                })
                .always(function() {
                    button.prop('disabled', false).text('Import Settings');
                });
            };
            
            reader.readAsText(file);
        });
    });
    </script>
    
    <style>
    .settings-backup-page .card {
        margin-bottom: 20px;
        padding: 20px;
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .settings-backup-page .status-info p {
        margin: 5px 0;
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .settings-backup-page .button-group {
        margin: 20px 0;
    }
    
    .settings-backup-page .button-group button {
        margin-right: 10px;
    }
    
    .settings-backup-page .export-section,
    .settings-backup-page .import-section {
        margin: 20px 0;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    
    .settings-backup-page .settings-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .settings-backup-page .category {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
    }
    
    .settings-backup-page .category h4 {
        margin-top: 0;
        color: #23282d;
    }
    
    .settings-backup-page .category ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    
    .settings-backup-page .category li {
        margin: 5px 0;
        font-size: 13px;
        color: #666;
    }
    </style>
    <?php
}
