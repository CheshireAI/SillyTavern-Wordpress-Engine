<?php
/**
 * Character Settings Manager
 * 
 * Manages character-specific settings like prompt prefix/suffix for image generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMV_Character_Settings_Manager {
    
    private static $table_name = 'pmv_character_settings';
    
    /**
     * Create database table for character settings
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            character_filename varchar(255) NOT NULL,
            character_name varchar(255) DEFAULT '',
            prompt_prefix text DEFAULT '',
            prompt_suffix text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY character_filename (character_filename),
            KEY character_name (character_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get character settings by filename
     * 
     * @param string $filename Character card filename
     * @return array|false Settings array or false if not found
     */
    public static function get_settings($filename) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE character_filename = %s",
                $filename
            ),
            ARRAY_A
        );
        
        if (!$result) {
            return false;
        }
        
        return array(
            'id' => $result['id'],
            'character_filename' => $result['character_filename'],
            'character_name' => $result['character_name'],
            'prompt_prefix' => $result['prompt_prefix'],
            'prompt_suffix' => $result['prompt_suffix']
        );
    }
    
    /**
     * Get character settings by name
     * 
     * @param string $name Character name
     * @return array|false Settings array or false if not found
     */
    public static function get_settings_by_name($name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE character_name = %s LIMIT 1",
                $name
            ),
            ARRAY_A
        );
        
        if (!$result) {
            return false;
        }
        
        return array(
            'id' => $result['id'],
            'character_filename' => $result['character_filename'],
            'character_name' => $result['character_name'],
            'prompt_prefix' => $result['prompt_prefix'],
            'prompt_suffix' => $result['prompt_suffix']
        );
    }
    
    /**
     * Save character settings
     * 
     * @param string $filename Character card filename
     * @param string $name Character name
     * @param string $prompt_prefix Prompt prefix text
     * @param string $prompt_suffix Prompt suffix text
     * @return int|false Insert/update ID or false on failure
     */
    public static function save_settings($filename, $name = '', $prompt_prefix = '', $prompt_suffix = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Check if settings already exist
        $existing = self::get_settings($filename);
        
        if ($existing) {
            // Update existing settings
            $result = $wpdb->update(
                $table_name,
                array(
                    'character_name' => sanitize_text_field($name),
                    'prompt_prefix' => sanitize_textarea_field($prompt_prefix),
                    'prompt_suffix' => sanitize_textarea_field($prompt_suffix)
                ),
                array('character_filename' => $filename),
                array('%s', '%s', '%s'),
                array('%s')
            );
            
            return $existing['id'];
        } else {
            // Insert new settings
            $result = $wpdb->insert(
                $table_name,
                array(
                    'character_filename' => sanitize_file_name($filename),
                    'character_name' => sanitize_text_field($name),
                    'prompt_prefix' => sanitize_textarea_field($prompt_prefix),
                    'prompt_suffix' => sanitize_textarea_field($prompt_suffix)
                ),
                array('%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                return $wpdb->insert_id;
            }
        }
        
        return false;
    }
    
    /**
     * Delete character settings
     * 
     * @param string $filename Character card filename
     * @return bool Success status
     */
    public static function delete_settings($filename) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->delete(
            $table_name,
            array('character_filename' => $filename),
            array('%s')
        ) !== false;
    }
    
    /**
     * Get all character settings
     * 
     * @return array All settings
     */
    public static function get_all_settings() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY character_name ASC",
            ARRAY_A
        );
        
        $settings = array();
        foreach ($results as $result) {
            $settings[] = array(
                'id' => $result['id'],
                'character_filename' => $result['character_filename'],
                'character_name' => $result['character_name'],
                'prompt_prefix' => $result['prompt_prefix'],
                'prompt_suffix' => $result['prompt_suffix']
            );
        }
        
        return $settings;
    }
    
    /**
     * AJAX handler to get character settings
     */
    public static function ajax_get_character_settings() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        
        if (empty($filename)) {
            wp_send_json_error(array('message' => 'Character filename is required'));
            return;
        }
        
        $settings = self::get_settings($filename);
        
        if ($settings === false) {
            wp_send_json_success(array('settings' => null));
        } else {
            wp_send_json_success(array('settings' => $settings));
        }
    }
    
    /**
     * AJAX handler to save character settings
     */
    public static function ajax_save_character_settings() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $prompt_prefix = sanitize_textarea_field($_POST['prompt_prefix'] ?? '');
        $prompt_suffix = sanitize_textarea_field($_POST['prompt_suffix'] ?? '');
        
        if (empty($filename)) {
            wp_send_json_error(array('message' => 'Character filename is required'));
            return;
        }
        
        $result = self::save_settings($filename, $name, $prompt_prefix, $prompt_suffix);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Settings saved successfully',
                'id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save settings'));
        }
    }
    
    /**
     * AJAX handler to get all character settings
     */
    public static function ajax_get_all_character_settings() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $settings = self::get_all_settings();
        
        wp_send_json_success(array('settings' => $settings));
    }
}

// Initialize table on plugin activation
register_activation_hook(PMV_PLUGIN_FILE, array('PMV_Character_Settings_Manager', 'create_table'));

// Also create table on admin_init if it doesn't exist
add_action('admin_init', function() {
    PMV_Character_Settings_Manager::create_table();
});

// Register AJAX handlers
add_action('wp_ajax_pmv_get_character_settings', array('PMV_Character_Settings_Manager', 'ajax_get_character_settings'));
add_action('wp_ajax_pmv_save_character_settings', array('PMV_Character_Settings_Manager', 'ajax_save_character_settings'));
add_action('wp_ajax_pmv_get_all_character_settings', array('PMV_Character_Settings_Manager', 'ajax_get_all_character_settings'));

