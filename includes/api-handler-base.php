<?php
/**
 * Base API Handler
 * 
 * Contains common logic for all API handlers (SwarmUI, NanoGPT, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class PMV_API_Handler_Base {

    protected $api_base_url;
    protected $api_key;
    protected $provider_id; // e.g., 'swarmui', 'nanogpt'

    public function __construct($provider_id) {
        $this->provider_id = $provider_id;
        $this->api_base_url = get_option("pmv_{$provider_id}_api_url", '');
        $this->api_key = get_option("pmv_{$provider_id}_api_key", '');

        // Initialize hooks
        add_action('init', array($this, 'init'));
        
        // Only register provider-specific actions if we're not using the unified handler
        // This prevents conflicts with the unified handler's generic action names
        if (!class_exists('PMV_Unified_Image_Handler') || !isset($GLOBALS['pmv_using_unified_handler'])) {
            add_action("wp_ajax_pmv_generate_image_{$provider_id}", array($this, 'ajax_generate_image'));
            add_action("wp_ajax_pmv_get_available_models_{$provider_id}", array($this, 'ajax_get_available_models'));
            add_action("wp_ajax_pmv_test_{$provider_id}_connection", array($this, 'ajax_test_connection'));
            add_action("wp_ajax_pmv_generate_image_prompt_{$provider_id}", array($this, 'ajax_generate_image_prompt'));
            
            // Guest handlers
            add_action("wp_ajax_nopriv_pmv_generate_image_{$provider_id}", array($this, 'ajax_generate_image_guest'));
        }
    }

    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_api_url");
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_api_key");
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_enabled");
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_default_model");
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_global_daily_limit", array('type' => 'integer', 'default' => 100));
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_global_monthly_limit", array('type' => 'integer', 'default' => 1000));
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_user_daily_limit", array('type' => 'integer', 'default' => 10));
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_user_monthly_limit", array('type' => 'integer', 'default' => 100));
        register_setting('png_metadata_viewer_settings', "pmv_{$this->provider_id}_guest_daily_limit", array('type' => 'integer', 'default' => 5));
    }

    abstract protected function generate_image($prompt, $model, $images_count, $params);
    abstract protected function get_available_models();
    abstract protected function test_connection();

    public function ajax_generate_image() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $user_id = get_current_user_id();
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $model = sanitize_text_field($_POST['model']);
        $images_count = intval($_POST['images_count'] ?? 1);

        $limit_check = $this->check_image_generation_limits($user_id, $images_count);
        if (!$limit_check['allowed']) {
            wp_send_json_error(array('message' => $limit_check['message']));
        }

        $result = $this->generate_image($prompt, $model, $images_count, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $this->track_image_generation_usage($user_id, $images_count, $prompt, $model);
        wp_send_json_success($result);
    }

    public function ajax_generate_image_guest() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $prompt = sanitize_textarea_field($_POST['prompt']);
        $model = sanitize_text_field($_POST['model']);
        $images_count = intval($_POST['images_count'] ?? 1);

        $limit_check = $this->check_image_generation_limits(0, $images_count);
        if (!$limit_check['allowed']) {
            wp_send_json_error(array('message' => $limit_check['message']));
        }

        $result = $this->generate_image($prompt, $model, $images_count, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $this->track_image_generation_usage(0, $images_count, $prompt, $model);
        wp_send_json_success($result);
    }

    public function ajax_get_available_models() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        $result = $this->get_available_models();

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
        }
        
        wp_send_json_success($result);
    }

    public function ajax_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $result = $this->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'details' => $result->get_error_data()
            ));
        }

        wp_send_json_success($result);
    }
    
    public function ajax_generate_image_prompt() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check if this is a preset-based request (has preset_id) - let image-presets.php handle it
        // The preset handler should take priority, so we skip processing here if preset_id is present
        if (isset($_POST['preset_id']) && !empty($_POST['preset_id'])) {
            // Don't process - let PMV_Image_Presets::ajax_generate_image_prompt handle it
            // Return early without sending response to let the preset handler process it
            return;
        }
        
        $chat_history = sanitize_textarea_field($_POST['chat_history'] ?? '');
        $custom_prompt = sanitize_textarea_field($_POST['custom_prompt'] ?? '');
        $custom_template = sanitize_textarea_field($_POST['custom_template'] ?? '');
        
        // Make chat_history optional - use custom_prompt or character description if available
        if (empty($chat_history) && empty($custom_prompt)) {
            wp_send_json_error(array('message' => 'Either chat history or a custom prompt is required'));
        }
        
        $prompt = $this->generate_image_prompt_from_chat($chat_history, $custom_prompt, $custom_template);
        
        if (is_wp_error($prompt)) {
            wp_send_json_error(array('message' => $prompt->get_error_message()));
        }
        
        wp_send_json_success(array('prompt' => $prompt));
    }

    public function check_image_generation_limits($user_id, $requested_count) {
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        if ($user_id > 0) {
            $daily_limit = get_user_meta($user_id, "pmv_{$this->provider_id}_daily_limit", true) ?: get_option("pmv_{$this->provider_id}_user_daily_limit", 10);
            $monthly_limit = get_user_meta($user_id, "pmv_{$this->provider_id}_monthly_limit", true) ?: get_option("pmv_{$this->provider_id}_user_monthly_limit", 100);
            
            $daily_usage = get_user_meta($user_id, "pmv_{$this->provider_id}_daily_usage", true) ?: array();
            $monthly_usage = get_user_meta($user_id, "pmv_{$this->provider_id}_monthly_usage", true) ?: array();
            
            $current_daily = isset($daily_usage[$today]) ? $daily_usage[$today] : 0;
            $current_monthly = isset($monthly_usage[$current_month]) ? $monthly_usage[$current_month] : 0;
            
            if ($current_daily + $requested_count > $daily_limit) {
                return array('allowed' => false, 'message' => "Daily limit exceeded.");
            }
            if ($current_monthly + $requested_count > $monthly_limit) {
                return array('allowed' => false, 'message' => "Monthly limit exceeded.");
            }
        } else {
            $guest_daily_limit = get_option("pmv_{$this->provider_id}_guest_daily_limit", 5);
            $cookie_name = "pmv_guest_{$this->provider_id}_usage";
            $guest_usage = isset($_COOKIE[$cookie_name]) ? json_decode(stripslashes($_COOKIE[$cookie_name]), true) : array();
            
            $current_daily = isset($guest_usage[$today]) ? $guest_usage[$today] : 0;
            
            if ($current_daily + $requested_count > $guest_daily_limit) {
                return array('allowed' => false, 'message' => 'Guest daily limit exceeded.');
            }
        }
        
        return array('allowed' => true);
    }
    
    public function track_image_generation_usage($user_id, $count, $prompt, $model) {
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        if ($user_id > 0) {
            $daily_usage = get_user_meta($user_id, "pmv_{$this->provider_id}_daily_usage", true) ?: array();
            $monthly_usage = get_user_meta($user_id, "pmv_{$this->provider_id}_monthly_usage", true) ?: array();

            $daily_usage[$today] = (isset($daily_usage[$today]) ? $daily_usage[$today] : 0) + $count;
            $monthly_usage[$current_month] = (isset($monthly_usage[$current_month]) ? $monthly_usage[$current_month] : 0) + $count;

            update_user_meta($user_id, "pmv_{$this->provider_id}_daily_usage", $daily_usage);
            update_user_meta($user_id, "pmv_{$this->provider_id}_monthly_usage", $monthly_usage);
        } else {
            $cookie_name = "pmv_guest_{$this->provider_id}_usage";
            $guest_usage = isset($_COOKIE[$cookie_name]) ? json_decode(stripslashes($_COOKIE[$cookie_name]), true) : array();
            $guest_usage[$today] = (isset($guest_usage[$today]) ? $guest_usage[$today] : 0) + $count;
            setcookie($cookie_name, json_encode($guest_usage), time() + (86400 * 30), '/');
        }
    }

    private function generate_image_prompt_from_chat($chat_history, $custom_prompt = '', $custom_template = '') {
        $openai_api_key = get_option('openai_api_key', '');
        $openai_model = get_option('openai_model', 'gpt-3.5-turbo');
        
        if (empty($openai_api_key)) {
            return new WP_Error('no_openai_key', 'OpenAI API key not configured');
        }
        
        // Use custom_prompt if provided, otherwise use custom_template
        if (!empty($custom_prompt)) {
            // If we have a custom prompt (like from a slash command), use it directly
            $system_prompt = $custom_template ?: 'You are an expert at creating detailed, descriptive prompts for AI image generation.';
            $user_prompt = $custom_prompt;
        } else {
            // Fallback to the original behavior for auto-triggered generation
            $system_prompt = $custom_template ?: 'You are an expert at creating detailed, descriptive prompts for AI image generation.';
            $user_prompt = "Based on this chat history:\n\n$chat_history\n\nCreate a detailed image generation prompt.";
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
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
            'timeout' => 30
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
} 