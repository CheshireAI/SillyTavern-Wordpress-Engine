<?php
/**
 * Unified Image Generation Handler
 * 
 * Handles image generation across multiple providers (SwarmUI, Nano-GPT)
 * Automatically selects the appropriate provider based on settings
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'swarmui-api-handler.php';
require_once 'nanogpt-api-handler.php';

class PMV_Unified_Image_Handler {
    
    private $active_handler;
    
    public function __construct() {
        // Set global flag to indicate unified handler is being used
        $GLOBALS['pmv_using_unified_handler'] = true;
        
        $provider = get_option('pmv_image_provider', 'swarmui');
        
        if ($provider === 'nanogpt') {
            $this->active_handler = new PMV_NanoGPT_API_Handler();
        } else {
            $this->active_handler = new PMV_SwarmUI_API_Handler();
        }
        
        add_action('wp_ajax_pmv_generate_image', array($this->active_handler, 'ajax_generate_image'));
        add_action('wp_ajax_nopriv_pmv_generate_image', array($this->active_handler, 'ajax_generate_image_guest'));
        add_action('wp_ajax_pmv_get_available_models', array($this->active_handler, 'ajax_get_available_models'));
        add_action('wp_ajax_pmv_test_image_connection', array($this->active_handler, 'ajax_test_connection'));
        // Note: pmv_generate_image_prompt is handled by PMV_Image_Presets (preset-based system)
        // Don't register the base handler here to avoid conflicts
        
        // Credit consumption endpoint (works for all providers)
        add_action('wp_ajax_pmv_consume_image_credits', array($this->active_handler, 'ajax_consume_image_credits'));
    }
}

// Initialize the unified image handler only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    new PMV_Unified_Image_Handler();
} 