<?php
/**
 * Image Generation Preset System
 * 
 * This file defines safe presets for image generation that prevent
 * jailbreaking and inappropriate content. Presets have hidden technical
 * parameters that users cannot directly modify.
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMV_Image_Presets {
    
    /**
     * Get all available presets
     * 
     * @return array List of preset objects with id, name, description, and hidden config
     */
    public static function get_presets() {
        return array(
            // General presets
            'selfie' => array(
                'id' => 'selfie',
                'name' => 'Selfie',
                'description' => 'A close-up self-portrait of the character',
                'category' => 'general',
                'config' => array(
                    'steps' => 20,
                    'cfg_scale' => 7.0,
                    'width' => 512,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'high quality portrait, detailed, professional photography'
                )
            ),
            
            'portrait' => array(
                'id' => 'portrait',
                'name' => 'Portrait',
                'description' => 'A portrait showing the character in detail',
                'category' => 'general',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 768,
                    'height' => 1024,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'high quality portrait, detailed, professional'
                )
            ),
            
            'surroundings' => array(
                'id' => 'surroundings',
                'name' => 'Surroundings',
                'description' => 'The environment and scene around the character',
                'category' => 'general',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 768,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'detailed environment, scenic, immersive'
                )
            )
        );
    }
    
    /**
     * Get preset by ID
     * 
     * @param string $id Preset ID
     * @param string $character_filename Optional character filename to check for character-specific preset
     * @return array|null Preset data or null if not found
     */
    public static function get_preset($id, $character_filename = '') {
        // Check character-specific preset first if filename provided
        if (!empty($character_filename) && class_exists('PMV_Character_Presets_Manager')) {
            $char_preset = PMV_Character_Presets_Manager::get_preset($character_filename, $id);
            if ($char_preset) {
                return $char_preset;
            }
        }
        
        // Fall back to universal presets (with saved overrides if any)
        if (class_exists('PMV_Universal_Presets_Manager')) {
            $universal_presets = PMV_Universal_Presets_Manager::get_universal_presets();
            return isset($universal_presets[$id]) ? $universal_presets[$id] : null;
        }
        
        // Fallback to default presets if manager not available
        $presets = self::get_presets();
        return isset($presets[$id]) ? $presets[$id] : null;
    }
    
    /**
     * Get presets by category
     * 
     * @param string $category Category name
     * @return array Filtered presets
     */
    public static function get_presets_by_category($category) {
        $presets = self::get_presets();
        return array_filter($presets, function($preset) use ($category) {
            return $preset['category'] === $category;
        });
    }
    
    /**
     * Sanitize and validate user's text prompt
     * Filters out inappropriate content and jailbreak attempts
     * 
     * @param string $user_prompt The user's prompt
     * @return string Sanitized prompt
     */
    public static function sanitize_user_prompt($user_prompt) {
        if (empty($user_prompt)) {
            return '';
        }
        
        // Convert to lowercase for checking
        $lower_prompt = strtolower($user_prompt);
        
        // Blocked keywords for inappropriate content
        $blocked_keywords = array(
            // Age-inappropriate content
            'minor', 'child', 'teen', 'underage', 'young',
            // Illegal content keywords
            'illegal', 'drugs', 'violence',
            // NSFW indicators
            'nsfw', 'nude', 'naked', 'sexual', 'explicit'
        );
        
        // Check for blocked keywords
        foreach ($blocked_keywords as $keyword) {
            if (strpos($lower_prompt, $keyword) !== false) {
                return new WP_Error('blocked_content', 'The requested content contains blocked keywords.');
            }
        }
        
        // Block common jailbreak patterns
        $jailbreak_patterns = array(
            'ignore all previous instructions',
            'forget everything',
            'system prompt',
            'you are now',
            'pretend to be',
            'act as if',
            'disregard all',
            'unrestricted',
            'uncensored',
            'without restrictions'
        );
        
        foreach ($jailbreak_patterns as $pattern) {
            if (strpos($lower_prompt, $pattern) !== false) {
                return new WP_Error('jailbreak_attempt', 'Attempted system manipulation detected.');
            }
        }
        
        // Remove HTML tags
        $sanitized = wp_strip_all_tags($user_prompt);
        
        // Limit length
        if (strlen($sanitized) > 500) {
            $sanitized = substr($sanitized, 0, 500);
        }
        
        // Trim whitespace
        $sanitized = trim($sanitized);
        
        return $sanitized;
    }
    
    /**
     * Generate final prompt from user input and preset
     * 
     * @param string $user_prompt User's original prompt
     * @param string $preset_id Preset ID
     * @param array $context Optional conversation context
     * @return string|WP_Error Final prompt for image generation
     */
    public static function generate_final_prompt($user_prompt, $preset_id, $context = array()) {
        // Sanitize user prompt
        $sanitized_prompt = self::sanitize_user_prompt($user_prompt);
        
        if (is_wp_error($sanitized_prompt)) {
            return $sanitized_prompt;
        }
        
        // Get preset
        $preset = self::get_preset($preset_id);
        
        if (!$preset) {
            return new WP_Error('invalid_preset', 'Invalid preset selected.');
        }
        
        // Build the final prompt
        $final_prompt = $sanitized_prompt;
        
        // Add preset enhancer if available
        if (!empty($preset['config']['prompt_enhancer'])) {
            $final_prompt .= ', ' . $preset['config']['prompt_enhancer'];
        }
        
        return $final_prompt;
    }
    
    /**
     * Get all presets including character-specific ones
     * 
     * @param string $character_filename Optional character filename to get character-specific presets
     * @return array Merged presets (universal + character-specific)
     */
    public static function get_all_presets($character_filename = '') {
        // Get universal presets (with saved overrides if any)
        if (class_exists('PMV_Universal_Presets_Manager')) {
            $presets = PMV_Universal_Presets_Manager::get_universal_presets();
        } else {
            $presets = self::get_presets();
        }
        
        // Add character-specific presets if filename provided
        if (!empty($character_filename) && class_exists('PMV_Character_Presets_Manager')) {
            $character_presets = PMV_Character_Presets_Manager::get_character_presets($character_filename);
            
            // Merge character presets with universal presets
            // Character presets override universal presets with same ID
            foreach ($character_presets as $preset_id => $preset) {
                $presets[$preset_id] = $preset;
            }
        }
        
        // Sort presets by category and name
        uasort($presets, function($a, $b) {
            // Custom presets first (if both are custom or both are not)
            if (isset($a['is_custom']) && isset($b['is_custom'])) {
                if ($a['is_custom'] && !$b['is_custom']) {
                    return -1;
                }
                if (!$a['is_custom'] && $b['is_custom']) {
                    return 1;
                }
            }
            
            // Then by sort_order if available
            $order_a = isset($a['sort_order']) ? intval($a['sort_order']) : 999;
            $order_b = isset($b['sort_order']) ? intval($b['sort_order']) : 999;
            if ($order_a !== $order_b) {
                return $order_a <=> $order_b;
            }
            
            // Then by category
            $cat_a = isset($a['category']) ? $a['category'] : '';
            $cat_b = isset($b['category']) ? $b['category'] : '';
            if ($cat_a !== $cat_b) {
                return strcmp($cat_a, $cat_b);
            }
            
            // Finally by name
            $name_a = isset($a['name']) ? $a['name'] : '';
            $name_b = isset($b['name']) ? $b['name'] : '';
            return strcmp($name_a, $name_b);
        });
        
        return $presets;
    }
    
    /**
     * AJAX handler to get available presets
     */
    public static function ajax_get_presets() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        $character_filename = sanitize_file_name($_POST['character_filename'] ?? '');
        
        $presets = self::get_all_presets($character_filename);
        
        wp_send_json_success(array(
            'presets' => $presets
        ));
    }
    
    /**
     * Generate image prompt using LLM based on chat context and preset
     * 
     * @param string $chat_history Last 5 messages from chat
     * @param string $preset_id Preset ID
     * @param string $preset_name Preset name
     * @param string $character_description Character description
     * @return string|WP_Error Generated prompt or error
     */
    private static function generate_prompt_with_llm($chat_history, $preset_id, $preset_name, $character_description) {
        $openai_api_key = get_option('openai_api_key', '');
        $openai_model = get_option('openai_model', 'gpt-3.5-turbo');
        $openai_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1');
        
        if (empty($openai_api_key)) {
            return new WP_Error('no_openai_key', 'OpenAI API key not configured');
        }
        
        // Clean up API base URL
        $api_url = rtrim($openai_base_url, '/');
        if (strpos($api_url, '/chat/completions') === false) {
            if (strpos($api_url, '/v1') === false) {
                $api_url .= '/v1';
            }
            $api_url .= '/chat/completions';
        }
        
        // Build system prompt
        $system_prompt = "You are an expert at creating detailed, descriptive prompts for AI image generation. ";
        $system_prompt .= "Your prompts should be clear, specific, and suitable for Stable Diffusion or similar image generation models. ";
        $system_prompt .= "Include details about appearance, pose, setting, lighting, mood, and style. ";
        $system_prompt .= "Use commas to separate different aspects of the prompt.";
        
        // Build user prompt
        $user_prompt = "Based on the following conversation context and character information, create a detailed image generation prompt.\n\n";
        
        if (!empty($character_description)) {
            $user_prompt .= "Character Description:\n" . $character_description . "\n\n";
        }
        
        if (!empty($chat_history)) {
            $user_prompt .= "Recent Chat Messages:\n" . $chat_history . "\n\n";
        }
        
        $user_prompt .= "The user wants a \"" . $preset_name . "\" type image. ";
        $user_prompt .= "Generate a prompt that makes sense given the conversation context. ";
        $user_prompt .= "For example, if they've been talking about cuddling on a bed and the preset is 'selfie', generate a prompt for the character taking a selfie in bed. ";
        $user_prompt .= "If they've been talking about a beach and the preset is 'surroundings', generate a prompt showing the character's surroundings at the beach.\n\n";
        $user_prompt .= "Generate ONLY the image prompt - no explanations, no meta-commentary, just the prompt text.";
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $openai_api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $openai_model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $user_prompt)
                ),
                'max_tokens' => 500,
                'temperature' => 0.7
            )),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message'] ?? 'OpenAI API error');
        }
        
        return trim($data['choices'][0]['message']['content']);
    }
    
    /**
     * AJAX handler to generate image prompt from chat context and preset using LLM
     */
    public static function ajax_generate_image_prompt() {
        check_ajax_referer('pmv_ajax_nonce', 'nonce');
        
        // Get parameters
        $preset_id = sanitize_text_field($_POST['preset_id'] ?? '');
        $chat_history = sanitize_textarea_field($_POST['chat_history'] ?? '');
        $character_description = sanitize_textarea_field($_POST['character_description'] ?? '');
        $character_filename = sanitize_file_name($_POST['character_filename'] ?? '');
        
        // Get preset first to validate (check character-specific first)
        $preset = self::get_preset($preset_id, $character_filename);
        
        if (!$preset) {
            wp_send_json_error(array(
                'message' => 'Invalid preset selected.'
            ));
            return;
        }
        
        // Generate prompt using LLM based on chat context and preset
        $llm_prompt = self::generate_prompt_with_llm(
            $chat_history,
            $preset_id,
            $preset['name'],
            $character_description
        );
        
        if (is_wp_error($llm_prompt)) {
            wp_send_json_error(array(
                'message' => $llm_prompt->get_error_message()
            ));
            return;
        }
        
        // Now apply character prefix/suffix and preset enhancer to the LLM-generated prompt
        $final_prompt = $llm_prompt;
        
        // Get character-specific prefix/suffix/model if available
        $prompt_prefix = '';
        $prompt_suffix = '';
        $image_model = '';
        if (!empty($character_filename) && class_exists('PMV_Character_Settings_Manager')) {
            $char_settings = PMV_Character_Settings_Manager::get_settings($character_filename);
            if ($char_settings) {
                $prompt_prefix = $char_settings['prompt_prefix'];
                $prompt_suffix = $char_settings['prompt_suffix'];
                $image_model = isset($char_settings['image_model']) ? $char_settings['image_model'] : '';
            }
        }
        
        // Add character prefix first
        if (!empty($prompt_prefix)) {
            $final_prompt = trim($prompt_prefix) . ', ' . $final_prompt;
        }
        
        // Add preset enhancer after LLM prompt
        if (!empty($preset['config']['prompt_enhancer'])) {
            $final_prompt .= ', ' . $preset['config']['prompt_enhancer'];
        }
        
        // Add character suffix last
        if (!empty($prompt_suffix)) {
            $final_prompt .= ', ' . trim($prompt_suffix);
        }
        
        // Get preset model (highest priority), then character model, then empty
        $preset_model = isset($preset['config']['model']) && !empty($preset['config']['model']) 
            ? $preset['config']['model'] 
            : ($image_model ?: '');
        
        wp_send_json_success(array(
            'final_prompt' => $final_prompt,
            'preset_config' => $preset['config'], // Includes loras array
            'character_model' => $image_model, // Character-specific model
            'preset_model' => $preset_model // Preset model (highest priority) or character model or empty
        ));
    }
}

// Register AJAX handlers with HIGH priority to ensure preset handler runs before base handler
add_action('wp_ajax_pmv_get_image_presets', array('PMV_Image_Presets', 'ajax_get_presets'), 5);
add_action('wp_ajax_nopriv_pmv_get_image_presets', array('PMV_Image_Presets', 'ajax_get_presets'), 5);
add_action('wp_ajax_pmv_generate_image_prompt', array('PMV_Image_Presets', 'ajax_generate_image_prompt'), 5);
add_action('wp_ajax_nopriv_pmv_generate_image_prompt', array('PMV_Image_Presets', 'ajax_generate_image_prompt'), 5);

