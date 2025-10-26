<?php
/**
 * PNG Metadata Viewer - Validation Utilities
 * 
 * Comprehensive input validation and sanitization functions
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validation utility class
 */
class PMV_Validation_Utils {
    
    /**
     * Validate and sanitize conversation data
     */
    public static function validate_conversation_data($data) {
        if (empty($data)) {
            return new WP_Error('empty_data', 'No conversation data provided');
        }
        
        // Parse JSON if needed
        if (is_string($data)) {
            $data = self::parse_json_safely($data);
            if (is_wp_error($data)) {
                return $data;
            }
        }
        
        // Validate required fields
        $required_fields = array('character_id', 'title', 'messages');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Required field missing: $field");
            }
        }
        
        // Validate character_id
        if (!is_string($data['character_id']) || strlen($data['character_id']) > 255) {
            return new WP_Error('invalid_character_id', 'Invalid character ID');
        }
        
        // Validate title
        if (!is_string($data['title']) || strlen($data['title']) > 500) {
            return new WP_Error('invalid_title', 'Invalid title (maximum 500 characters)');
        }
        
        // Validate messages array
        if (!is_array($data['messages'])) {
            return new WP_Error('invalid_messages', 'Messages must be an array');
        }
        
        // Limit number of messages
        if (count($data['messages']) > 1000) {
            return new WP_Error('too_many_messages', 'Too many messages (maximum 1000)');
        }
        
        // Validate each message
        foreach ($data['messages'] as $index => $message) {
            $message_validation = self::validate_message($message, $index);
            if (is_wp_error($message_validation)) {
                return $message_validation;
            }
        }
        
        return $data;
    }
    
    /**
     * Validate a single message
     */
    public static function validate_message($message, $index = 0) {
        if (!is_array($message)) {
            return new WP_Error('invalid_message', "Message at index $index is not an array");
        }
        
        if (!isset($message['role']) || !isset($message['content'])) {
            return new WP_Error('invalid_message', "Message at index $index is missing role or content");
        }
        
        // Validate role
        $valid_roles = array('user', 'assistant', 'system');
        if (!in_array($message['role'], $valid_roles)) {
            return new WP_Error('invalid_role', "Invalid message role at index $index: {$message['role']}");
        }
        
        // Validate content
        if (!is_string($message['content'])) {
            return new WP_Error('invalid_content', "Message content at index $index must be a string");
        }
        
        if (empty($message['content'])) {
            return new WP_Error('empty_content', "Message content at index $index cannot be empty");
        }
        
        if (strlen($message['content']) > 10000) {
            return new WP_Error('content_too_long', "Message content at index $index is too long (maximum 10000 characters)");
        }
        
        return true;
    }
    
    /**
     * Safely parse JSON with validation
     */
    public static function parse_json_safely($json_string) {
        if (!is_string($json_string)) {
            return new WP_Error('invalid_json', 'JSON data must be a string');
        }
        
        // Limit JSON string length
        if (strlen($json_string) > 1000000) { // 1MB limit
            return new WP_Error('json_too_large', 'JSON data is too large');
        }
        
        // Clean up WordPress slashes
        $clean_json = wp_unslash($json_string);
        
        $decoded = json_decode($clean_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSON decode error: ' . json_last_error_msg());
        }
        
        return $decoded;
    }
    
    /**
     * Validate and sanitize image generation parameters
     */
    public static function validate_image_params($params) {
        $validated = array();
        
        // Validate prompt
        $prompt = sanitize_textarea_field($params['prompt'] ?? '');
        if (empty($prompt)) {
            return new WP_Error('missing_prompt', 'Prompt is required');
        }
        
        if (strlen($prompt) > 2000) {
            return new WP_Error('prompt_too_long', 'Prompt is too long (maximum 2000 characters)');
        }
        
        $validated['prompt'] = $prompt;
        
        // Validate model
        $model = sanitize_text_field($params['model'] ?? '');
        if (empty($model) || strlen($model) > 255) {
            return new WP_Error('invalid_model', 'Invalid model');
        }
        
        $validated['model'] = $model;
        
        // Validate images_count
        $images_count = intval($params['images_count'] ?? 1);
        if ($images_count < 1 || $images_count > 4) {
            $images_count = 1;
        }
        
        $validated['images_count'] = $images_count;
        
        // Validate width
        $width = intval($params['width'] ?? 512);
        if ($width < 256 || $width > 2048 || $width % 64 !== 0) {
            $width = 512;
        }
        
        $validated['width'] = $width;
        
        // Validate height
        $height = intval($params['height'] ?? 512);
        if ($height < 256 || $height > 2048 || $height % 64 !== 0) {
            $height = 512;
        }
        
        $validated['height'] = $height;
        
        // Validate steps
        $steps = intval($params['steps'] ?? 20);
        if ($steps < 1 || $steps > 100) {
            $steps = 20;
        }
        
        $validated['steps'] = $steps;
        
        // Validate cfg_scale
        $cfg_scale = floatval($params['cfg_scale'] ?? 7.0);
        if ($cfg_scale < 0.1 || $cfg_scale > 20.0) {
            $cfg_scale = 7.0;
        }
        
        $validated['cfg_scale'] = $cfg_scale;
        
        // Validate negative prompt
        $negative = sanitize_textarea_field($params['negative_prompt'] ?? '');
        if (strlen($negative) > 1000) {
            $negative = substr($negative, 0, 1000);
        }
        
        $validated['negative_prompt'] = $negative;
        
        return $validated;
    }
    
    /**
     * Validate file upload
     */
    public static function validate_file_upload($file) {
        if (!isset($file) || !is_array($file)) {
            return new WP_Error('no_file', 'No file provided');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload failed');
        }
        
        if (!is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_upload', 'Invalid file upload');
        }
        
        // Validate file size
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            return new WP_Error('file_too_large', 'File size too large (maximum 10MB)');
        }
        
        if ($file['size'] < 100) {
            return new WP_Error('file_too_small', 'File appears to be empty or corrupted');
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type !== 'image/png') {
            return new WP_Error('invalid_type', 'Only PNG files are allowed');
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'png') {
            return new WP_Error('invalid_extension', 'Only PNG files are allowed');
        }
        
        return true;
    }
    
    /**
     * Validate user input for XSS prevention
     */
    public static function validate_user_input($input, $max_length = 5000) {
        if (!is_string($input)) {
            return new WP_Error('invalid_input', 'Input must be a string');
        }
        
        if (empty($input)) {
            return new WP_Error('empty_input', 'Input cannot be empty');
        }
        
        if (strlen($input) > $max_length) {
            return new WP_Error('input_too_long', "Input is too long (maximum $max_length characters)");
        }
        
        // Check for potentially harmful content
        $harmful_patterns = array(
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        );
        
        foreach ($harmful_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return new WP_Error('harmful_content', 'Input contains potentially harmful content');
            }
        }
        
        return sanitize_text_field($input);
    }
    
    /**
     * Validate conversation ID
     */
    public static function validate_conversation_id($id) {
        $id = intval($id);
        if ($id <= 0) {
            return new WP_Error('invalid_id', 'Invalid conversation ID');
        }
        
        return $id;
    }
    
    /**
     * Validate user ID
     */
    public static function validate_user_id($user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return new WP_Error('invalid_user', 'Invalid user ID');
        }
        
        if (!get_user_by('ID', $user_id)) {
            return new WP_Error('user_not_found', 'User not found');
        }
        
        return $user_id;
    }
    
    /**
     * Validate nonce
     */
    public static function validate_nonce($nonce, $action) {
        if (empty($nonce)) {
            return new WP_Error('missing_nonce', 'Security token is required');
        }
        
        if (!wp_verify_nonce($nonce, $action)) {
            return new WP_Error('invalid_nonce', 'Security verification failed');
        }
        
        return true;
    }
    
    /**
     * Validate permissions
     */
    public static function validate_permissions($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            return new WP_Error('insufficient_permissions', 'Insufficient permissions');
        }
        
        return true;
    }
} 