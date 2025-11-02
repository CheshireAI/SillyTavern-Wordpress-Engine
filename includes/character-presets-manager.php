<?php
/**
 * Character Presets Manager
 * 
 * Manages character-specific image generation presets
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMV_Character_Presets_Manager {
    
    private static $table_name = 'pmv_character_presets';
    
    /**
     * Create database table for character presets
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            character_filename varchar(255) NOT NULL,
            preset_id varchar(255) NOT NULL,
            preset_name varchar(255) NOT NULL,
            preset_description text DEFAULT '',
            preset_category varchar(100) DEFAULT 'custom',
            preset_config longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY character_preset_unique (character_filename, preset_id),
            KEY character_filename (character_filename),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get all presets for a character
     * 
     * @param string $filename Character card filename
     * @return array List of presets
     */
    public static function get_character_presets($filename) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE character_filename = %s AND is_active = 1 ORDER BY sort_order ASC, preset_name ASC",
                $filename
            ),
            ARRAY_A
        );
        
        $presets = array();
        foreach ($results as $result) {
            $config = json_decode($result['preset_config'], true);
            if (!is_array($config)) {
                $config = array();
            }
            
            $presets[$result['preset_id']] = array(
                'id' => $result['preset_id'],
                'name' => $result['preset_name'],
                'description' => $result['preset_description'],
                'category' => $result['preset_category'],
                'config' => $config,
                'is_custom' => true,
                'character_filename' => $result['character_filename']
            );
        }
        
        return $presets;
    }
    
    /**
     * Get a single preset by character and preset ID
     * 
     * @param string $filename Character card filename
     * @param string $preset_id Preset ID
     * @return array|false Preset data or false if not found
     */
    public static function get_preset($filename, $preset_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE character_filename = %s AND preset_id = %s",
                $filename,
                $preset_id
            ),
            ARRAY_A
        );
        
        if (!$result) {
            return false;
        }
        
        $config = json_decode($result['preset_config'], true);
        if (!is_array($config)) {
            $config = array();
        }
        
        return array(
            'id' => $result['preset_id'],
            'name' => $result['preset_name'],
            'description' => $result['preset_description'],
            'category' => $result['preset_category'],
            'config' => $config,
            'is_custom' => true,
            'character_filename' => $result['character_filename'],
            'is_active' => $result['is_active'],
            'sort_order' => $result['sort_order']
        );
    }
    
    /**
     * Save a character preset
     * 
     * @param string $filename Character card filename
     * @param string $preset_id Preset ID (unique per character)
     * @param string $preset_name Preset name
     * @param string $preset_description Preset description
     * @param string $preset_category Preset category
     * @param array $preset_config Preset configuration
     * @param bool $is_active Whether preset is active
     * @param int $sort_order Sort order
     * @return int|false Insert/update ID or false on failure
     */
    public static function save_preset($filename, $preset_id, $preset_name, $preset_description = '', $preset_category = 'custom', $preset_config = array(), $is_active = true, $sort_order = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Validate and sanitize config
        $default_config = array(
            'steps' => 20,
            'cfg_scale' => 7.0,
            'width' => 512,
            'height' => 512,
            'negative_prompt' => 'blurry, distorted, low quality',
            'prompt_enhancer' => '',
            'model' => ''
        );
        
        $preset_config = wp_parse_args($preset_config, $default_config);
        
        $data = array(
            'character_filename' => sanitize_file_name($filename),
            'preset_id' => sanitize_key($preset_id),
            'preset_name' => sanitize_text_field($preset_name),
            'preset_description' => sanitize_textarea_field($preset_description),
            'preset_category' => sanitize_key($preset_category),
            'preset_config' => json_encode($preset_config),
            'is_active' => $is_active ? 1 : 0,
            'sort_order' => intval($sort_order)
        );
        
        // Check if preset exists
        $existing = self::get_preset($filename, $preset_id);
        
        if ($existing) {
            // Update existing preset
            $result = $wpdb->update(
                $table_name,
                $data,
                array(
                    'character_filename' => $filename,
                    'preset_id' => $preset_id
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                array('%s', '%s')
            );
            
            return $existing['id'];
        } else {
            // Insert new preset
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
            
            if ($result) {
                return $wpdb->insert_id;
            }
        }
        
        return false;
    }
    
    /**
     * Delete a character preset
     * 
     * @param string $filename Character card filename
     * @param string $preset_id Preset ID
     * @return bool Success status
     */
    public static function delete_preset($filename, $preset_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->delete(
            $table_name,
            array(
                'character_filename' => $filename,
                'preset_id' => $preset_id
            ),
            array('%s', '%s')
        ) !== false;
    }
    
    /**
     * AJAX handler to get character presets
     */
    public static function ajax_get_character_presets() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        
        if (empty($filename)) {
            wp_send_json_error(array('message' => 'Character filename is required'));
            return;
        }
        
        $presets = self::get_character_presets($filename);
        
        wp_send_json_success(array('presets' => $presets));
    }
    
    /**
     * AJAX handler to save character preset
     */
    public static function ajax_save_character_preset() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        $preset_id = sanitize_key($_POST['preset_id'] ?? '');
        $preset_name = sanitize_text_field($_POST['preset_name'] ?? '');
        $preset_description = sanitize_textarea_field($_POST['preset_description'] ?? '');
        $preset_category = sanitize_key($_POST['preset_category'] ?? 'custom');
        
        // Get preset config
        $preset_config = array(
            'steps' => intval($_POST['steps'] ?? 20),
            'cfg_scale' => floatval($_POST['cfg_scale'] ?? 7.0),
            'width' => intval($_POST['width'] ?? 512),
            'height' => intval($_POST['height'] ?? 512),
            'negative_prompt' => sanitize_textarea_field($_POST['negative_prompt'] ?? ''),
            'prompt_enhancer' => sanitize_textarea_field($_POST['prompt_enhancer'] ?? ''),
            'model' => sanitize_text_field($_POST['model'] ?? '')
        );
        
        $is_active = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (empty($filename) || empty($preset_id) || empty($preset_name)) {
            wp_send_json_error(array('message' => 'Filename, preset ID, and preset name are required'));
            return;
        }
        
        $result = self::save_preset($filename, $preset_id, $preset_name, $preset_description, $preset_category, $preset_config, $is_active, $sort_order);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Preset saved successfully',
                'id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save preset'));
        }
    }
    
    /**
     * AJAX handler to delete character preset
     */
    public static function ajax_delete_character_preset() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        $preset_id = sanitize_key($_POST['preset_id'] ?? '');
        
        if (empty($filename) || empty($preset_id)) {
            wp_send_json_error(array('message' => 'Filename and preset ID are required'));
            return;
        }
        
        $result = self::delete_preset($filename, $preset_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Preset deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete preset'));
        }
    }
}

// Initialize table on plugin activation
if (defined('PMV_PLUGIN_FILE')) {
    register_activation_hook(PMV_PLUGIN_FILE, array('PMV_Character_Presets_Manager', 'create_table'));
}

// Also create table on admin_init if it doesn't exist
add_action('admin_init', function() {
    if (class_exists('PMV_Character_Presets_Manager')) {
        PMV_Character_Presets_Manager::create_table();
    }
});

// Register AJAX handlers
add_action('wp_ajax_pmv_get_character_presets', array('PMV_Character_Presets_Manager', 'ajax_get_character_presets'));
add_action('wp_ajax_nopriv_pmv_get_character_presets', array('PMV_Character_Presets_Manager', 'ajax_get_character_presets'));
add_action('wp_ajax_pmv_save_character_preset', array('PMV_Character_Presets_Manager', 'ajax_save_character_preset'));
add_action('wp_ajax_pmv_delete_character_preset', array('PMV_Character_Presets_Manager', 'ajax_delete_character_preset'));

