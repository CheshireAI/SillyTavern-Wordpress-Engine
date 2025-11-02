<?php
/**
 * Universal Presets Manager
 * 
 * Manages editing of universal (default) presets from the backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMV_Universal_Presets_Manager {
    
    private static $option_name = 'pmv_universal_presets';
    
    /**
     * Get all universal presets (from code or saved overrides)
     */
    public static function get_universal_presets() {
        // Get default presets from code
        $default_presets = PMV_Image_Presets::get_presets();
        
        // Get saved overrides from database
        $saved_overrides = get_option(self::$option_name, array());
        
        // Merge saved overrides with defaults
        foreach ($saved_overrides as $preset_id => $override) {
            if (isset($default_presets[$preset_id])) {
                // Merge config if override exists
                if (isset($override['config']) && is_array($override['config'])) {
                    $default_presets[$preset_id]['config'] = array_merge(
                        $default_presets[$preset_id]['config'],
                        $override['config']
                    );
                }
                // Override name, description, category if set
                if (isset($override['name'])) {
                    $default_presets[$preset_id]['name'] = $override['name'];
                }
                if (isset($override['description'])) {
                    $default_presets[$preset_id]['description'] = $override['description'];
                }
                if (isset($override['category'])) {
                    $default_presets[$preset_id]['category'] = $override['category'];
                }
            }
        }
        
        return $default_presets;
    }
    
    /**
     * Save override for a universal preset
     * 
     * @param string $preset_id Preset ID
     * @param array $override Override data (name, description, category, config)
     * @return bool Success status
     */
    public static function save_preset_override($preset_id, $override) {
        $saved_overrides = get_option(self::$option_name, array());
        
        $saved_overrides[$preset_id] = array(
            'name' => sanitize_text_field($override['name'] ?? ''),
            'description' => sanitize_textarea_field($override['description'] ?? ''),
            'category' => sanitize_key($override['category'] ?? ''),
            'config' => array(
                'steps' => intval($override['config']['steps'] ?? 20),
                'cfg_scale' => floatval($override['config']['cfg_scale'] ?? 7.0),
                'width' => intval($override['config']['width'] ?? 512),
                'height' => intval($override['config']['height'] ?? 512),
                'negative_prompt' => sanitize_textarea_field($override['config']['negative_prompt'] ?? ''),
                'prompt_enhancer' => sanitize_textarea_field($override['config']['prompt_enhancer'] ?? '')
            )
        );
        
        return update_option(self::$option_name, $saved_overrides);
    }
    
    /**
     * Delete override for a universal preset (revert to default)
     * 
     * @param string $preset_id Preset ID
     * @return bool Success status
     */
    public static function delete_preset_override($preset_id) {
        $saved_overrides = get_option(self::$option_name, array());
        
        if (isset($saved_overrides[$preset_id])) {
            unset($saved_overrides[$preset_id]);
            return update_option(self::$option_name, $saved_overrides);
        }
        
        return true;
    }
    
    /**
     * AJAX handler to get universal presets
     */
    public static function ajax_get_universal_presets() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $presets = self::get_universal_presets();
        
        wp_send_json_success(array('presets' => $presets));
    }
    
    /**
     * AJAX handler to save universal preset override
     */
    public static function ajax_save_universal_preset() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $preset_id = sanitize_key($_POST['preset_id'] ?? '');
        
        if (empty($preset_id)) {
            wp_send_json_error(array('message' => 'Preset ID is required'));
            return;
        }
        
        // Get original preset to validate it exists
        $original_preset = PMV_Image_Presets::get_preset($preset_id);
        if (!$original_preset) {
            wp_send_json_error(array('message' => 'Invalid preset ID'));
            return;
        }
        
        $override = array(
            'name' => sanitize_text_field($_POST['name'] ?? $original_preset['name']),
            'description' => sanitize_textarea_field($_POST['description'] ?? $original_preset['description']),
            'category' => sanitize_key($_POST['category'] ?? $original_preset['category']),
            'config' => array(
                'steps' => intval($_POST['steps'] ?? $original_preset['config']['steps']),
                'cfg_scale' => floatval($_POST['cfg_scale'] ?? $original_preset['config']['cfg_scale']),
                'width' => intval($_POST['width'] ?? $original_preset['config']['width']),
                'height' => intval($_POST['height'] ?? $original_preset['config']['height']),
                'negative_prompt' => sanitize_textarea_field($_POST['negative_prompt'] ?? $original_preset['config']['negative_prompt']),
                'prompt_enhancer' => sanitize_textarea_field($_POST['prompt_enhancer'] ?? $original_preset['config']['prompt_enhancer']),
                'model' => sanitize_text_field($_POST['model'] ?? ($original_preset['config']['model'] ?? ''))
            )
        );
        
        $result = self::save_preset_override($preset_id, $override);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Preset updated successfully',
                'preset' => self::get_universal_presets()[$preset_id]
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save preset'));
        }
    }
    
    /**
     * AJAX handler to delete universal preset override (revert to default)
     */
    public static function ajax_delete_universal_preset_override() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $preset_id = sanitize_key($_POST['preset_id'] ?? '');
        
        if (empty($preset_id)) {
            wp_send_json_error(array('message' => 'Preset ID is required'));
            return;
        }
        
        $result = self::delete_preset_override($preset_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Preset reverted to default'));
        } else {
            wp_send_json_error(array('message' => 'Failed to revert preset'));
        }
    }
}

// Register AJAX handlers
add_action('wp_ajax_pmv_get_universal_presets', array('PMV_Universal_Presets_Manager', 'ajax_get_universal_presets'));
add_action('wp_ajax_pmv_save_universal_preset', array('PMV_Universal_Presets_Manager', 'ajax_save_universal_preset'));
add_action('wp_ajax_pmv_delete_universal_preset_override', array('PMV_Universal_Presets_Manager', 'ajax_delete_universal_preset_override'));

