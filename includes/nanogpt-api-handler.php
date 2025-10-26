<?php
/**
 * Nano-GPT API Integration Handler
 * 
 * Handles image generation via nano-gpt API
 * Supports nano-gpt's REST API format
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'api-handler-base.php';
    
class PMV_NanoGPT_API_Handler extends PMV_API_Handler_Base {
    
    public function __construct() {
        parent::__construct('nanogpt');
        }
        
    protected function generate_image($prompt, $model, $images_count, $params) {
        if (empty($this->api_base_url)) {
            return new WP_Error('no_api_url', 'Nano-GPT API URL not configured');
        }

        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Nano-GPT API key not configured');
        }

        $url = trailingslashit($this->api_base_url) . 'generate-image';

        $request_data = array(
            'prompt' => $prompt,
            'model' => $model,
            'width' => intval($params['width'] ?? 1024),
            'height' => intval($params['height'] ?? 1024),
            'nImages' => $images_count,
            'num_steps' => intval($params['steps'] ?? 10),
            'scale' => floatval($params['cfg_scale'] ?? 7.5)
        );

        if (!empty($params['negative_prompt'])) {
            $request_data['negative_prompt'] = sanitize_textarea_field($params['negative_prompt']);
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $this->api_key,
            'User-Agent' => 'PMV-NanoGPT-Integration/1.0'
        );

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 180,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('api_error', 'Nano-GPT API returned status ' . $response_code . ' Response: ' . $body);
        }

        $data = json_decode($body, true);

        if (empty($data) || isset($data['error'])) {
            return new WP_Error('api_error', $data['error'] ?? 'Invalid response from Nano-GPT API');
        }

        return array(
            'images' => array($data['image']),
            'cost' => $data['cost'] ?? 0,
            'provider' => 'nanogpt',
        );
    }

    protected function get_available_models() {
        if (empty($this->api_base_url) || empty($this->api_key)) {
            return new WP_Error('not_configured', 'Nano-GPT API not configured');
        }

        $url = trailingslashit($this->api_base_url) . 'models';
        $headers = array('x-api-key' => $this->api_key);

        $response = wp_remote_get($url, array('headers' => $headers, 'timeout' => 30, 'sslverify' => false));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return $data;
    }
    
    protected function test_connection() {
        $result = $this->get_available_models();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array(
            'success' => true,
            'message' => 'Connection successful!',
            'models_count' => count($result['models'] ?? []),
            'api_url' => $this->api_base_url,
            'has_auth' => !empty($this->api_key)
        );
    }
}

// Initialize the NanoGPT API handler only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    new PMV_NanoGPT_API_Handler();
} 