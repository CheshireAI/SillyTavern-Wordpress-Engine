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
            // Character-focused presets
            'selfie' => array(
                'id' => 'selfie',
                'name' => 'Selfie',
                'description' => 'A close-up self-portrait of the character',
                'category' => 'character',
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
                'category' => 'character',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 768,
                    'height' => 1024,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'high quality portrait, detailed, professional'
                )
            ),
            
            'full_body' => array(
                'id' => 'full_body',
                'name' => 'Full Body',
                'description' => 'Show the complete character from head to toe',
                'category' => 'character',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 512,
                    'height' => 768,
                    'negative_prompt' => 'blurry, distorted, low quality, cropped, nsfw',
                    'prompt_enhancer' => 'full body shot, complete character, detailed'
                )
            ),
            
            // Environment presets
            'surroundings' => array(
                'id' => 'surroundings',
                'name' => 'Surroundings',
                'description' => 'The environment and scene around the character',
                'category' => 'environment',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 768,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'detailed environment, scenic, immersive'
                )
            ),
            
            'landscape' => array(
                'id' => 'landscape',
                'name' => 'Landscape',
                'description' => 'A wide scenic landscape view',
                'category' => 'environment',
                'config' => array(
                    'steps' => 30,
                    'cfg_scale' => 7.5,
                    'width' => 1024,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'beautiful landscape, scenic, detailed environment'
                )
            ),
            
            'room' => array(
                'id' => 'room',
                'name' => 'Room/Interior',
                'description' => 'An interior space or room',
                'category' => 'environment',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 768,
                    'height' => 768,
                    'negative_prompt' => 'blurry, distorted, low quality, cluttered, nsfw',
                    'prompt_enhancer' => 'detailed interior, cozy room, well-lit'
                )
            ),
            
            // Action presets
            'action' => array(
                'id' => 'action',
                'name' => 'Action',
                'description' => 'Character engaged in dynamic action',
                'category' => 'action',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 8.0,
                    'width' => 768,
                    'height' => 512,
                    'negative_prompt' => 'blurry, static, boring, low quality, nsfw',
                    'prompt_enhancer' => 'dynamic action, movement, energy, detailed'
                )
            ),
            
            'pose' => array(
                'id' => 'pose',
                'name' => 'Pose',
                'description' => 'Character in a specific pose or stance',
                'category' => 'action',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 512,
                    'height' => 768,
                    'negative_prompt' => 'blurry, awkward, low quality, nsfw',
                    'prompt_enhancer' => 'natural pose, elegant stance, detailed anatomy'
                )
            ),
            
            // Mood/atmosphere presets
            'closeup' => array(
                'id' => 'closeup',
                'name' => 'Close-up',
                'description' => 'An extreme close-up focus on specific details',
                'category' => 'detail',
                'config' => array(
                    'steps' => 30,
                    'cfg_scale' => 8.0,
                    'width' => 512,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'extreme close-up, detailed, sharp focus'
                )
            ),
            
            'cute' => array(
                'id' => 'cute',
                'name' => 'Cute',
                'description' => 'A cute, adorable rendition',
                'category' => 'style',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 512,
                    'height' => 512,
                    'negative_prompt' => 'blurry, distorted, low quality, frightening, scary, nsfw',
                    'prompt_enhancer' => 'cute, adorable, charming, cheerful'
                )
            ),
            
            'serious' => array(
                'id' => 'serious',
                'name' => 'Serious/Dramatic',
                'description' => 'A more serious or dramatic portrayal',
                'category' => 'style',
                'config' => array(
                    'steps' => 25,
                    'cfg_scale' => 7.5,
                    'width' => 512,
                    'height' => 768,
                    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
                    'prompt_enhancer' => 'dramatic lighting, serious expression, cinematic'
                )
            )
        );
    }
    
    /**
     * Get preset by ID
     * 
     * @param string $id Preset ID
     * @return array|null Preset data or null if not found
     */
    public static function get_preset($id) {
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
     * AJAX handler to get available presets
     */
    public static function ajax_get_presets() {
        check_ajax_referer('pmv_nonce', 'nonce');
        
        wp_send_json_success(array(
            'presets' => self::get_presets()
        ));
    }
    
    /**
     * AJAX handler to generate image prompt from user input and preset
     */
    public static function ajax_generate_image_prompt() {
        check_ajax_referer('pmv_nonce', 'nonce');
        
        // Get parameters
        $user_prompt = sanitize_text_field($_POST['user_prompt'] ?? '');
        $preset_id = sanitize_text_field($_POST['preset_id'] ?? '');
        $context_json = sanitize_text_field($_POST['context'] ?? '{}');
        
        // Sanitize user prompt with content filtering
        $sanitized = self::sanitize_user_prompt($user_prompt);
        
        if (is_wp_error($sanitized)) {
            wp_send_json_error(array(
                'message' => $sanitized->get_error_message()
            ));
            return;
        }
        
        if (empty($sanitized)) {
            wp_send_json_error(array(
                'message' => 'Please describe what you want to see.'
            ));
            return;
        }
        
        // Get preset
        $preset = self::get_preset($preset_id);
        
        if (!$preset) {
            wp_send_json_error(array(
                'message' => 'Invalid preset selected.'
            ));
            return;
        }
        
        // Build final prompt
        $final_prompt = $sanitized;
        
        // Add preset enhancer if available
        if (!empty($preset['config']['prompt_enhancer'])) {
            $final_prompt .= ', ' . $preset['config']['prompt_enhancer'];
        }
        
        wp_send_json_success(array(
            'final_prompt' => $final_prompt,
            'preset_config' => $preset['config']
        ));
    }
}

// Register AJAX handlers
add_action('wp_ajax_pmv_get_image_presets', array('PMV_Image_Presets', 'ajax_get_presets'));
add_action('wp_ajax_nopriv_pmv_get_image_presets', array('PMV_Image_Presets', 'ajax_get_presets'));
add_action('wp_ajax_pmv_generate_image_prompt', array('PMV_Image_Presets', 'ajax_generate_image_prompt'));
add_action('wp_ajax_nopriv_pmv_generate_image_prompt', array('PMV_Image_Presets', 'ajax_generate_image_prompt'));

