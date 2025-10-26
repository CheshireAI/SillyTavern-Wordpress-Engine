<?php
/**
 * PNG Metadata Viewer - Credit System
 * 
 * Handles WooCommerce credit purchases and cumulative credit balances
 * Version: 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PMV Credit System Class
 */
class PMV_Subscription_System {
    
    private static $instance = null;
    private $wpdb;
    
    // Credit rates configuration - loaded from options
    private $credit_rates = array();
    
    /**
     * Initialize credit rates from options
     */
    private function init_credit_rates() {
        $this->credit_rates = array(
            'image_credits_per_dollar' => intval(get_option('pmv_image_credits_per_dollar', 100)),
            'text_credits_per_dollar' => intval(get_option('pmv_text_credits_per_dollar', 10000)),
            'image_product_name' => get_option('pmv_image_product_name', 'Image Credits'),
            'text_product_name' => get_option('pmv_text_product_name', 'Text Credits'),
            'image_product_description' => get_option('pmv_image_product_description', 'Purchase image generation credits. Credits never expire and are cumulative.'),
            'text_product_description' => get_option('pmv_text_product_description', 'Purchase text generation tokens. Tokens never expire and are cumulative.')
        );
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Initialize credit rates
        $this->init_credit_rates();
        
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pmv_get_credit_status', array($this, 'ajax_get_credit_status'));
        add_action('wp_ajax_nopriv_pmv_get_credit_status', array($this, 'ajax_get_credit_status'));
        add_action('wp_ajax_pmv_force_process_order_credits', array($this, 'ajax_force_process_order_credits'));
        
        // Debug hook registration
        error_log("PMV: Subscription system initialized, waiting for WooCommerce to load...");
        
        // Test hook registration
        add_action('init', array($this, 'test_hook_registration'), 20);
        
        // Add test AJAX action to verify the system is working
        add_action('wp_ajax_pmv_test_credit_system', array($this, 'test_credit_system'));
        add_action('wp_ajax_nopriv_pmv_test_credit_system', array($this, 'test_credit_system'));
        
        // Ensure AJAX actions are registered early
        add_action('init', array($this, 'ensure_ajax_actions_registered'), 5);
        add_action('wp_loaded', array($this, 'ensure_ajax_actions_registered'), 5);
        
        // ENHANCED: Multiple hook registration points with different priorities
        add_action('woocommerce_loaded', array($this, 'register_woocommerce_hooks'), 20);
        add_action('plugins_loaded', array($this, 'register_woocommerce_hooks'), 20);
        add_action('wp_loaded', array($this, 'register_woocommerce_hooks'), 20);
        
        // ENHANCED: Add more hook registration points for better coverage
        add_action('init', array($this, 'register_woocommerce_hooks'), 999);
        add_action('wp', array($this, 'register_woocommerce_hooks'), 999);
        add_action('template_redirect', array($this, 'register_woocommerce_hooks'), 999);
        
        // ENHANCED: Force register hooks after a delay to ensure WooCommerce is ready
        add_action('init', array($this, 'delayed_hook_registration'), 1000);
        
        // Daily login reward system
        add_action('wp_login', array($this, 'check_daily_login_reward'), 10, 2);
        add_action('wp_ajax_pmv_check_daily_reward', array($this, 'ajax_check_daily_reward'));
        add_action('wp_ajax_pmv_check_free_credit_status', array($this, 'ajax_check_free_credit_status'));
        
        // Cron job for checking missed credits
        add_action('pmv_check_missed_credits', array($this, 'check_missed_credits'));
        
        // ENHANCED: Add custom cron schedule for 1-minute intervals
        add_filter('cron_schedules', array($this, 'add_one_minute_schedule'));
        
        // ENHANCED: Schedule automatic order audit every minute
        add_action('init', array($this, 'schedule_automatic_audit'));
        add_action('pmv_automatic_order_audit', array($this, 'automatic_order_audit'));
        
        // ENHANCED: Process orders on every page load/refresh for immediate credit processing
        add_action('init', array($this, 'process_orders_on_page_load'), 999);
        add_action('wp_loaded', array($this, 'process_orders_on_page_load'), 999);
        add_action('template_redirect', array($this, 'process_orders_on_page_load'), 999);
        
        // ENHANCED: Add even more aggressive page load hooks to ensure it runs
        add_action('wp', array($this, 'process_orders_on_page_load'), 999);
        add_action('wp_head', array($this, 'process_orders_on_page_load'), 999);
        add_action('wp_footer', array($this, 'process_orders_on_page_load'), 999);
        add_action('admin_init', array($this, 'process_orders_on_page_load'), 999);
        add_action('admin_footer', array($this, 'process_orders_on_page_load'), 999);
        
        // ENHANCED: Force run on every request to ensure credits are processed
        add_action('init', array($this, 'force_process_orders_immediate'), 1000);
        
        // Refresh rates when options are updated
        add_action('update_option', array($this, 'handle_option_update'), 10, 3);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ensure AJAX actions are properly registered
     */
    public function ensure_ajax_actions_registered() {
        error_log("PMV: Ensuring AJAX actions are registered...");
        
        // Check if AJAX actions are already registered
        if (!has_action('wp_ajax_pmv_get_credit_status')) {
            error_log("PMV: Re-registering AJAX actions...");
            add_action('wp_ajax_pmv_get_credit_status', array($this, 'ajax_get_credit_status'));
            add_action('wp_ajax_nopriv_pmv_get_credit_status', array($this, 'ajax_get_credit_status'));
        }
        
        error_log("PMV: AJAX actions registration check complete");
    }
    
    /**
     * Test method to verify the credit system is working
     */
    public function test_credit_system() {
        try {
            // Clean any output buffers to prevent unwanted output
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            error_log("PMV: test_credit_system called");
            
            // Verify nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
                error_log("PMV: Test endpoint nonce verification failed");
                wp_send_json_error('Invalid nonce');
            }
            
            error_log("PMV: Test endpoint nonce verification successful");
            
            $user_id = get_current_user_id();
            error_log("PMV: Test endpoint user ID: " . $user_id);
            
            $response = array(
                'success' => true,
                'message' => 'Credit system is working',
                'user_id' => $user_id,
                'timestamp' => current_time('mysql')
            );
            
            if ($user_id) {
                error_log("PMV: Test endpoint getting user credits...");
                $credits = $this->get_user_credits($user_id);
                $response['credits'] = $credits;
                error_log("PMV: Test endpoint credits: " . json_encode($credits));
            }
            
            error_log("PMV: Test endpoint sending response: " . json_encode($response));
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log("PMV: Exception in test_credit_system: " . $e->getMessage());
            error_log("PMV: Exception trace: " . $e->getTraceAsString());
            wp_send_json_error('Test endpoint error: ' . $e->getMessage());
        } catch (Error $e) {
            error_log("PMV: Fatal error in test_credit_system: " . $e->getMessage());
            error_log("PMV: Error trace: " . $e->getTraceAsString());
            wp_send_json_error('Test endpoint fatal error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize the credit system
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: WooCommerce not active during init, skipping hook registration");
            return;
        }
        
        error_log("PMV: WooCommerce is active during init, proceeding with setup...");
        
        // Create credit products if they don't exist
        $this->ensure_credit_products_exist();
        
        // Only register hooks if WooCommerce is fully loaded
        if (did_action('woocommerce_loaded')) {
            error_log("PMV: WooCommerce already loaded, registering hooks now...");
            $this->register_woocommerce_hooks();
        } else {
            error_log("PMV: WooCommerce not fully loaded yet, hooks will be registered later");
        }
        
        // Fallback: If WooCommerce hooks haven't been registered yet, register them now
        // This ensures hooks are registered even if woocommerce_loaded action doesn't fire
        if (!has_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'))) {
            error_log("PMV: Fallback: Registering WooCommerce hooks in init method");
            $this->register_woocommerce_hooks();
        }
    }
    
    /**
     * Test if WooCommerce hooks are working by simulating an order event
     */
    public function test_woocommerce_hooks() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot test WooCommerce hooks - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: Testing WooCommerce hooks functionality...");
        
        // Check if our hooks are registered
        $hooks_registered = array(
            'woocommerce_order_status_completed' => has_action('woocommerce_order_status_completed', array($this, 'handle_order_completion')),
            'woocommerce_order_status_processing' => has_action('woocommerce_order_status_processing', array($this, 'handle_order_completion')),
            'woocommerce_payment_complete' => has_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete')),
            'woocommerce_order_status_changed' => has_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change')),
            'woocommerce_checkout_order_processed' => has_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created'))
        );
        
        $all_hooks_registered = true;
        foreach ($hooks_registered as $hook => $has_action) {
            if (!$has_action) {
                $all_hooks_registered = false;
                error_log("PMV: ❌ Hook {$hook} is NOT registered");
            } else {
                error_log("PMV: ✅ Hook {$hook} is registered");
            }
        }
        
        if ($all_hooks_registered) {
            error_log("PMV: ✅ All WooCommerce hooks are properly registered");
        } else {
            error_log("PMV: ❌ Some WooCommerce hooks are missing - attempting to re-register");
            $this->register_woocommerce_hooks();
        }
        
        return $all_hooks_registered;
    }

    /**
     * Check if WooCommerce is active and fully loaded
     */
    private function is_woocommerce_active() {
        // Check if WooCommerce class exists
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Check if WooCommerce is fully initialized
        if (!did_action('woocommerce_loaded')) {
            return false;
        }
        
        // Check if WooCommerce functions are available
        if (!function_exists('wc_get_order')) {
            return false;
        }
        
        // Check if WooCommerce is properly activated
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test hook registration for debugging
     */
    public function test_hook_registration() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: WooCommerce not active during hook test");
            return;
        }
        
        error_log("PMV: Testing WooCommerce hook registration...");
        
        // Check if our hooks are registered
        $hooks_to_check = array(
            'woocommerce_order_status_completed',
            'woocommerce_order_status_processing',
            'woocommerce_payment_complete',
            'woocommerce_order_status_changed',
            'woocommerce_checkout_order_processed'
        );
        
        foreach ($hooks_to_check as $hook) {
            $has_action = has_action($hook, array($this, 'handle_order_completion'));
            $has_action_changed = has_action($hook, array($this, 'handle_order_status_change'));
            $has_action_created = has_action($hook, array($this, 'handle_order_created'));
            
            error_log("PMV: Hook {$hook} - completion: " . ($has_action ? 'YES' : 'NO') . 
                     ", status_changed: " . ($has_action_changed ? 'YES' : 'NO') . 
                     ", order_created: " . ($has_action_created ? 'YES' : 'NO'));
        }
        
        error_log("PMV: Hook registration test completed");
    }
    
    /**
     * ENHANCED: Force register hooks after a delay to ensure WooCommerce is ready
     */
    public function delayed_hook_registration() {
        error_log("PMV: 🕐 DELAYED HOOK REGISTRATION: Attempting to register WooCommerce hooks after delay...");
        
        // Check if WooCommerce is available
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: ❌ DELAYED HOOK REGISTRATION: WooCommerce still not active, trying fallback...");
            
            // Try fallback registration
            if ($this->fallback_hook_registration()) {
                error_log("PMV: ✅ DELAYED HOOK REGISTRATION: Fallback hooks registered successfully");
                return;
            } else {
                error_log("PMV: ❌ DELAYED HOOK REGISTRATION: Fallback also failed, will try again later");
                return;
            }
        }
        
        // Check if hooks are already registered
        if ($this->are_hooks_registered()) {
            error_log("PMV: ✅ DELAYED HOOK REGISTRATION: Hooks already registered, skipping");
            return;
        }
        
        error_log("PMV: 🚀 DELAYED HOOK REGISTRATION: Registering WooCommerce hooks now...");
        $this->register_woocommerce_hooks();
        
        // Also check for any processing orders that might need credits
        $this->check_processing_orders_immediate();
    }

    /**
     * Check if WooCommerce hooks are already registered
     */
    private function are_hooks_registered() {
        return has_action('woocommerce_order_status_completed', array($this, 'handle_order_completion')) ||
               has_action('woocommerce_order_status_processing', array($this, 'handle_order_processing')) ||
               has_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'));
    }
    
    /**
     * Register WooCommerce hooks after WooCommerce is fully loaded
     */
    public function register_woocommerce_hooks() {
        // Check if hooks are already registered to prevent duplicates
        if ($this->are_hooks_registered()) {
            error_log("PMV: Hooks already registered, skipping duplicate registration");
            return;
        }
        
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot register WooCommerce hooks - WooCommerce not active");
            return;
        }
        
        error_log("PMV: Registering WooCommerce hooks after WooCommerce loaded...");
        
        // Remove any existing hooks to prevent duplicates
        remove_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10);
        remove_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 5);
        remove_action('woocommerce_order_status_processing', array($this, 'handle_order_completion'), 10);
        remove_action('woocommerce_order_status_processing', array($this, 'handle_order_completion'), 5);
        remove_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 10);
        remove_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 5);
        
        // Re-register hooks with proper timing
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10);
        add_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 10);
        add_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 10);
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        add_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created'), 10, 3);
        
        // Additional hooks for better coverage with different priorities
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 5, 4);
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 5);
        add_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 5);
        
        // Hook into order save to catch all changes
        add_action('woocommerce_update_order', array($this, 'handle_order_update'), 10, 2);
        add_action('woocommerce_save_order', array($this, 'handle_order_save'), 10, 2);
        
        // Catch manual status changes in admin
        add_action('woocommerce_admin_order_status_changed', array($this, 'handle_admin_order_status_change'), 10, 3);
        
        // Catch all post updates for orders
        add_action('post_updated', array($this, 'handle_post_updated'), 10, 3);
        
        // WordPress post save hook for orders - this should catch everything
        add_action('save_post', array($this, 'handle_save_post'), 10, 3);
        
        // Comprehensive order save hook - this should catch everything
        add_action('woocommerce_order_object_updated_props', array($this, 'handle_order_updated_props'), 10, 2);
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 1, 4);
        
        // Immediate trigger for processing status orders
        add_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 1);
        
        // Additional hooks for new order creation and payment
        add_action('woocommerce_new_order', array($this, 'handle_new_order'), 10, 1);
        add_action('woocommerce_order_status_pending', array($this, 'handle_order_pending'), 10, 1);
        add_action('woocommerce_order_status_on_hold', array($this, 'handle_order_on_hold'), 10, 1);
        add_action('woocommerce_order_status_failed', array($this, 'handle_order_failed'), 10, 1);
        
        // Hook into order creation at multiple points
        add_action('woocommerce_checkout_create_order', array($this, 'handle_checkout_create_order'), 10, 2);
        add_action('woocommerce_checkout_order_created', array($this, 'handle_checkout_order_created'), 10, 1);
        
        // Auto-complete credit-only orders
        add_action('woocommerce_payment_complete', array($this, 'auto_complete_credit_orders'), 5);
        
        // Ensure credit products are virtual and downloadable
        add_filter('woocommerce_product_is_virtual', array($this, 'make_credit_products_virtual'), 10, 2);
        add_filter('woocommerce_product_is_downloadable', array($this, 'make_credit_products_downloadable'), 10, 2);
        
        // Allow immediate completion of credit orders
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'allow_credit_orders_completion'), 10, 2);
        
        // ENHANCED: Add more comprehensive hooks for new orders
        add_action('woocommerce_order_created', array($this, 'handle_order_created_comprehensive'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change_comprehensive'), 5, 4);
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete_comprehensive'), 5, 1);
        
        // ENHANCED: Hook into order creation at the earliest possible point
        add_action('woocommerce_checkout_order_created', array($this, 'handle_checkout_order_created_immediate'), 1, 1);
        add_action('woocommerce_new_order', array($this, 'handle_new_order_immediate'), 1, 1);
        
        // ENHANCED: Hook into order save at multiple points to catch everything
        add_action('woocommerce_save_order', array($this, 'handle_save_order_comprehensive'), 1, 2);
        add_action('save_post', array($this, 'handle_save_post_comprehensive'), 1, 3);
        
        // ENHANCED: Add filter to catch order creation during checkout
        add_filter('woocommerce_checkout_create_order', array($this, 'handle_checkout_create_order_enhanced'), 1, 2);
        
        error_log("PMV: WooCommerce hooks and filters registered successfully");
        
        // ENHANCED: Immediately check for any processing orders that might have been missed
        add_action('init', array($this, 'check_processing_orders_immediate'), 999);
        
        // ENHANCED: Set up cron job if not already scheduled
        $this->setup_credit_check_cron();
        
        // ENHANCED: Log successful hook registration
        error_log("PMV: ✅ SUCCESS: All WooCommerce hooks registered successfully!");
        error_log("PMV: Hook count - Order status: " . has_action('woocommerce_order_status_changed'));
        error_log("PMV: Hook count - Order processing: " . has_action('woocommerce_order_status_processing'));
        error_log("PMV: Hook count - Order completed: " . has_action('woocommerce_order_status_completed'));
    }
    
    /**
     * AJAX: Force process credits for an existing order
     */
    public function ajax_force_process_order_credits() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
        }
        
        error_log("PMV: Force processing credits for order: {$order_id}");
        
        // Clear any existing processed flag to force reprocessing
        $order = wc_get_order($order_id);
        if ($order && $order->get_user_id()) {
            $processed_key = "_pmv_credits_processed_{$order_id}";
            delete_user_meta($order->get_user_id(), $processed_key);
            error_log("PMV: Cleared processed flag for order {$order_id}");
        }
        
        // Process the order credits
        $result = $this->check_and_process_credit_order($order_id);
        
        if ($result) {
            wp_send_json_success('Credits processed for order ' . $order_id);
        } else {
            wp_send_json_error('No credits to process for order ' . $order_id);
        }
    }
    
    /**
     * Create credit products on plugin activation
     */
    public function create_subscription_products() {
        if (!$this->is_woocommerce_active()) {
            return false;
        }
        
        // Create image credits product
        $this->create_credit_product('image');
        
        // Create text credits product
        $this->create_credit_product('text');
        
        return true;
    }
    
    /**
     * Create a single credit product
     */
    private function create_credit_product($credit_type) {
        // Check if product already exists
        $existing_product = $this->get_credit_product($credit_type);
        if ($existing_product) {
            // Update existing product to ensure it's properly configured
            $this->update_credit_product($existing_product, $credit_type);
            return $existing_product->get_id();
        }
        
        // Create new product
        $product = new WC_Product_Simple();
        
        if ($credit_type === 'image') {
            $product->set_name($this->credit_rates['image_product_name']);
            $product->set_description($this->credit_rates['image_product_description']);
            $product->set_short_description($this->credit_rates['image_product_description']);
            $product->set_regular_price(1.00); // $1 base price
            $product->set_price(1.00);
        } else {
            $product->set_name($this->credit_rates['text_product_name']);
            $product->set_description($this->credit_rates['text_product_description']);
            $product->set_short_description($this->credit_rates['text_product_description']);
            $product->set_regular_price(1.00); // $1 base price
            $product->set_price(1.00);
        }
        
        // Product settings
        $product->set_virtual(true);
        $product->set_downloadable(false);
        
        // Stock settings - ensure unlimited availability
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Product status
        $product->set_status('publish');
        
        // Ensure credit products can be completed immediately
        // Note: set_virtual(true) automatically makes the product not require shipping
        
        // Save product first to get the ID
        $product_id = $product->save();
        
        if ($product_id) {
            // Set product category using wp_set_object_terms
            $this->create_pmv_category();
            wp_set_object_terms($product_id, 'PMV Credits', 'product_cat');
            
            // Store credit type information as product meta
            update_post_meta($product_id, '_pmv_credit_type', $credit_type);
            update_post_meta($product_id, '_pmv_credits_per_dollar', $credit_type === 'image' ? $this->credit_rates['image_credits_per_dollar'] : $this->credit_rates['text_credits_per_dollar']);
            
            // Ensure stock status is correct
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_manage_stock', 'no');
            update_post_meta($product_id, '_stock', '');
            
            error_log("PMV: Created credit product: {$credit_type} credits (ID: {$product_id})");
        } else {
            error_log("PMV: Failed to create credit product: {$credit_type}");
        }
        
        return $product_id;
    }
    
    /**
     * Update existing credit product
     */
    private function update_credit_product($product, $credit_type) {
        $product_id = $product->get_id();
        
        // Update product details
        if ($credit_type === 'image') {
            $product->set_name($this->credit_rates['image_product_name']);
            $product->set_description($this->credit_rates['image_product_description']);
            $product->set_short_description($this->credit_rates['image_product_description']);
        } else {
            $product->set_name($this->credit_rates['text_product_name']);
            $product->set_description($this->credit_rates['text_product_description']);
            $product->set_short_description($this->credit_rates['text_product_description']);
        }
        
        // Ensure stock settings are correct
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Ensure credit products can be completed immediately
        $product->set_virtual(true);
        $product->set_downloadable(false);
        // Note: set_virtual(true) automatically makes the product not require shipping
        
        // Save the product
        $product->save();
        
        // Update meta to ensure consistency
        update_post_meta($product_id, '_stock_status', 'instock');
        update_post_meta($product_id, '_manage_stock', 'no');
        update_post_meta($product_id, '_stock', '');
        update_post_meta($product_id, '_pmv_credits_per_dollar', $credit_type === 'image' ? $this->credit_rates['image_credits_per_dollar'] : $this->credit_rates['text_credits_per_dollar']);
        
        error_log("PMV: Updated credit product: {$credit_type} credits (ID: {$product_id})");
    }
    
    /**
     * Create PMV Credits category
     */
    private function create_pmv_category() {
        $category_name = 'PMV Credits';
        $category_slug = 'pmv-credits';
        
        $existing_category = get_term_by('slug', $category_slug, 'product_cat');
        if (!$existing_category) {
            wp_insert_term($category_name, 'product_cat', array(
                'slug' => $category_slug,
                'description' => 'Credit products for PNG Metadata Viewer plugin'
            ));
        }
    }
    
    /**
     * Get credit product by type
     */
    private function get_credit_product($credit_type) {
        $products = wc_get_products(array(
            'meta_key' => '_pmv_credit_type',
            'meta_value' => $credit_type,
            'limit' => 1,
            'status' => 'publish'
        ));
        
        return !empty($products) ? $products[0] : false;
    }
    
    /**
     * Ensure credit products exist
     */
    private function ensure_credit_products_exist() {
        error_log("PMV: Ensuring credit products exist...");
        
        $image_result = $this->create_credit_product('image');
        $text_result = $this->create_credit_product('text');
        
        error_log("PMV: Image credits product result: " . ($image_result ? "Created/Updated (ID: {$image_result})" : "Failed"));
        error_log("PMV: Text credits product result: " . ($text_result ? "Created/Updated (ID: {$text_result})" : "Failed"));
        
        // Verify products exist
        $image_product = $this->get_credit_product('image');
        $text_product = $this->get_credit_product('text');
        
        if ($image_product && $text_product) {
            error_log("PMV: Both credit products verified successfully");
        } else {
            error_log("PMV: Credit product verification failed - Image: " . ($image_product ? "Yes" : "No") . ", Text: " . ($text_product ? "Yes" : "No"));
        }
    }
    
    /**
     * Force recreate credit products (admin function)
     */
    public function force_recreate_credit_products() {
        error_log("PMV: Force recreating credit products...");
        
        // Delete existing products first
        $image_product = $this->get_credit_product('image');
        $text_product = $this->get_credit_product('text');
        
        if ($image_product) {
            $image_product->delete(true);
            error_log("PMV: Deleted existing image credits product");
        }
        
        if ($text_product) {
            $text_product->delete(true);
            error_log("PMV: Deleted existing text credits product");
        }
        
        // Recreate products
        $this->ensure_credit_products_exist();
        
        return true;
    }
    
    /**
     * Handle order status change - more reliable than just completion
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        error_log("PMV: 🔥 ORDER STATUS CHANGED HOOK FIRED - Order: {$order_id}, Old: '{$old_status}', New: '{$new_status}'");
        
        // Process credits for any order status that indicates the order is valid
        if (in_array($new_status, ['completed', 'processing', 'pending', 'on-hold'])) {
            error_log("PMV: 🚀 Processing credits for order status change to '{$new_status}'");
            
            // Check if this is a new order with processing status (no status change)
            if ($old_status === '' && $new_status === 'processing') {
                error_log("PMV: 🆕 New order created with processing status - processing credits immediately");
            }
            
            // Get order details for debugging
            if ($order) {
                error_log("PMV: 📋 Order details - ID: {$order_id}, User: {$order->get_user_id()}, Total: {$order->get_total()}");
                
                // Check if order contains credit products
                $contains_credits = false;
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                        error_log("PMV: 📦 Order item - Product: {$product->get_name()} (ID: {$product->get_id()}), Credit Type: " . ($credit_type ?: 'NOT FOUND'));
                        if ($credit_type) {
                            $contains_credits = true;
                        }
                    }
                }
                error_log("PMV: 💳 Order contains credit products: " . ($contains_credits ? 'YES' : 'NO'));
                
                if ($contains_credits) {
                    error_log("PMV: 🎯 Order {$order_id} contains credit products - calling process_order_credits");
                    $this->process_order_credits($order_id);
                } else {
                    error_log("PMV: ⏭️ Order {$order_id} does not contain credit products - skipping");
                }
            } else {
                error_log("PMV: ❌ Could not get order object for ID: {$order_id}");
            }
        } else {
            error_log("PMV: ⏭️ Skipping credits for order status: '{$new_status}' (not a valid status for credits)");
        }
    }
    
    /**
     * Handle order completion and add credits
     */
    public function handle_order_completion($order_id) {
        error_log("PMV: 🔥 ORDER COMPLETION HOOK FIRED for order: {$order_id}");
        error_log("PMV: Order completion hook called with order ID: {$order_id}");
        
        // Get order details for debugging
        $order = wc_get_order($order_id);
        if ($order) {
            error_log("PMV: Order status: " . $order->get_status());
            error_log("PMV: Order user ID: " . $order->get_user_id());
            error_log("PMV: Order total: " . $order->get_total());
            error_log("PMV: Order payment method: " . $order->get_payment_method());
            error_log("PMV: Order payment method title: " . $order->get_payment_method_title());
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                // ENHANCED: Add null check for item
                if (!$item) {
                    error_log("PMV: Order completion - Item is null in order {$order_id}, skipping");
                    continue;
                }
                
                $product = $item->get_product();
                if ($product) {
                    // ENHANCED: Add additional safety checks for product object
                    if (is_object($product) && method_exists($product, 'get_id')) {
                        $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                        error_log("PMV: Order item - Product ID: {$product->get_id()}, Name: {$product->get_name()}, Credit Type: " . ($credit_type ?: 'NOT FOUND'));
                        if ($credit_type) {
                            $contains_credits = true;
                        }
                    } else {
                        error_log("PMV: Order completion - Invalid product object for item in order {$order_id}");
                    }
                } else {
                    error_log("PMV: Order completion - No product found for item in order {$order_id}");
                }
            }
            error_log("PMV: Order contains credit products: " . ($contains_credits ? 'YES' : 'NO'));
        } else {
            error_log("PMV: Could not get order object for ID: {$order_id}");
        }
        
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle order processing status specifically
     */
    public function handle_order_processing($order_id) {
        error_log("PMV: 🔥 ORDER PROCESSING HOOK FIRED for order: {$order_id}");
        error_log("PMV: Order processing hook called with order ID: {$order_id}");
        
        // Get order details for debugging
        $order = wc_get_order($order_id);
        if ($order) {
            error_log("PMV: Order status: " . $order->get_status());
            error_log("PMV: Order user ID: " . $order->get_user_id());
            error_log("PMV: Order total: " . $order->get_total());
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                // ENHANCED: Add null check for item
                if (!$item) {
                    error_log("PMV: Order processing - Item is null in order {$order_id}, skipping");
                    continue;
                }
                
                $product = $item->get_product();
                if ($product) {
                    // ENHANCED: Add additional safety checks for product object
                    if (is_object($product) && method_exists($product, 'get_id')) {
                        $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                        error_log("PMV: Order item - Product ID: {$product->get_id()}, Name: {$product->get_name()}, Credit Type: " . ($credit_type ?: 'NOT FOUND'));
                        if ($credit_type) {
                            $contains_credits = true;
                        }
                    } else {
                        error_log("PMV: Order processing - Invalid product object for item in order {$order_id}");
                    }
                } else {
                    error_log("PMV: Order processing - No product found for item in order {$order_id}");
                }
            }
            error_log("PMV: Order contains credit products: " . ($contains_credits ? 'YES' : 'NO'));
        } else {
            error_log("PMV: Could not get order object for ID: {$order_id}");
        }
        
        // Force immediate processing for processing status orders
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle payment complete
     */
    public function handle_order_payment_complete($order_id) {
        error_log("PMV: 🔥 PAYMENT COMPLETE HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle order created
     */
    public function handle_order_created($order_id, $posted_data, $order) {
        error_log("PMV: 🔥 ORDER CREATED HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle order update
     */
    public function handle_order_update($order_id, $order) {
        error_log("PMV: 🔥 ORDER UPDATE HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle order save
     */
    public function handle_order_save($order_id, $order) {
        error_log("PMV: 🔥 ORDER SAVE HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }
    
    /**
     * Handle admin order status change
     */
    public function handle_admin_order_status_change($order_id, $old_status, $new_status) {
        error_log("PMV: 🔥 ADMIN ORDER STATUS CHANGED - Order: {$order_id}, Old: {$old_status}, New: {$new_status}");
        
        // Process credits for any valid status
        if (in_array($new_status, ['completed', 'processing', 'pending', 'on-hold'])) {
            error_log("PMV: 🚀 Processing credits for admin status change to {$new_status}");
            $this->check_and_process_credit_order($order_id);
        }
    }
    
    /**
     * Handle post updated
     */
    public function handle_post_updated($post_id, $post_after, $post_before) {
        if (get_post_type($post_id) === 'shop_order') {
            error_log("PMV: 🔥 POST UPDATED HOOK FIRED for order: {$post_id}");
            $this->check_and_process_credit_order($post_id);
        }
    }
    
    /**
     * Check if order contains credit products and process if needed
     */
    public function check_and_process_credit_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return false;
        }
        
        // Check if order contains credit products
        $contains_credits = false;
        foreach ($order->get_items() as $item) {
            // ENHANCED: Add null check for item
            if (!$item) {
                error_log("PMV: check_and_process_credit_order - Item is null in order {$order_id}, skipping");
                continue;
            }
            
            $product = $item->get_product();
            if ($product && is_object($product) && method_exists($product, 'get_id')) {
                $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                if ($credit_type) {
                    $contains_credits = true;
                    break;
                }
            }
        }
        
        if ($contains_credits) {
            error_log("PMV: Order {$order_id} contains credit products, forcing processing");
            $this->process_order_credits($order_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Process order credits - centralized method
     */
    private function process_order_credits($order_id) {
        error_log("PMV: Processing credits for order: {$order_id}");
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("PMV: Order completion - No order found for ID: {$order_id}");
            return;
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("PMV: Order completion - No user ID for order: {$order_id}");
            return; // Guest orders not supported for credits
        }
        
        error_log("PMV: Processing order completion for user {$user_id}, order {$order_id}");
        
        // Check if credits were already processed for this order
        $processed_key = "_pmv_credits_processed_{$order_id}";
        if (get_user_meta($user_id, $processed_key, true)) {
            error_log("PMV: Credits already processed for order {$order_id}, skipping");
            return;
        }
        
        $credits_added = false;
        $order_items = $order->get_items();
        error_log("PMV: Order has " . count($order_items) . " items");
        
        foreach ($order_items as $item) {
            // ENHANCED: Add null check for item
            if (!$item) {
                error_log("PMV: Order completion - Item is null in order {$order_id}, skipping");
                continue;
            }
            
            $product = $item->get_product();
            if (!$product) {
                error_log("PMV: Order completion - No product found for item in order {$order_id}");
                continue;
            }
            
            // ENHANCED: Add additional safety checks for product object
            if (!is_object($product) || !method_exists($product, 'get_id')) {
                error_log("PMV: Order completion - Invalid product object for item in order {$order_id}");
                continue;
            }
            
            error_log("PMV: Processing item - Product ID: {$product->get_id()}, Name: {$product->get_name()}");
            
            $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
            error_log("PMV: Credit type for product {$product->get_id()}: " . ($credit_type ?: 'NOT FOUND'));
            
            if (!$credit_type) {
                error_log("PMV: Order completion - No credit type found for product {$product->get_id()} in order {$order_id}");
                continue;
            }
            
            // Calculate credits based on quantity and price
            $quantity = $item->get_quantity();
            $total_price = $item->get_total();
            $credits_per_dollar = get_post_meta($product->get_id(), '_pmv_credits_per_dollar', true);
            
            error_log("PMV: Item details - Quantity: {$quantity}, Total Price: {$total_price}, Credits per Dollar: {$credits_per_dollar}");
            
            $credits_to_add = intval($total_price * $credits_per_dollar);
            
            error_log("PMV: Order completion - Processing {$credit_type} credits: quantity={$quantity}, total_price={$total_price}, credits_per_dollar={$credits_per_dollar}, credits_to_add={$credits_to_add}");
            
            if ($credits_to_add > 0) {
                $old_balance = $this->get_user_credits($user_id);
                error_log("PMV: User {$user_id} old credit balance: " . json_encode($old_balance));
                
                $this->add_user_credits($user_id, $credit_type, $credits_to_add);
                
                error_log("PMV: Added {$credits_to_add} {$credit_type} credits to user {$user_id} from order {$order_id}");
                
                // Verify credits were added
                $new_balance = $this->get_user_credits($user_id);
                error_log("PMV: User {$user_id} new credit balance: " . json_encode($new_balance));
                
                $credits_added = true;
            } else {
                error_log("PMV: Order completion - No credits to add for {$credit_type} in order {$order_id} (credits_to_add = {$credits_to_add})");
            }
        }
        
        // Mark this order as processed to prevent duplicate credit addition
        if ($credits_added) {
            update_user_meta($user_id, $processed_key, current_time('timestamp'));
            error_log("PMV: Marked order {$order_id} as processed for user {$user_id}");
        } else {
            error_log("PMV: No credits were added for order {$order_id}, not marking as processed");
        }
    }
    
    /**
     * Add credits to user
     */
    private function add_user_credits($user_id, $credit_type, $amount, $source = 'purchase') {
        if (!$user_id || !in_array($credit_type, ['image', 'text'])) {
            error_log("PMV: Invalid parameters for add_user_credits - user_id: {$user_id}, credit_type: {$credit_type}, amount: {$amount}");
            return false;
        }
        
        $meta_key = "_pmv_{$credit_type}_credits";
        $current_credits = intval(get_user_meta($user_id, $meta_key, true)) ?: 0;
        $new_balance = $current_credits + $amount;
        
        error_log("PMV: Adding credits - User: {$user_id}, Type: {$credit_type}, Current: {$current_credits}, Adding: {$amount}, New Balance: {$new_balance}, Source: {$source}");
        
        $result = update_user_meta($user_id, $meta_key, $new_balance);
        error_log("PMV: update_user_meta result for {$meta_key}: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            // Track free credits separately if this is a daily reward
            if ($source === 'daily_reward') {
                $this->track_free_credits($user_id, $credit_type, $amount);
            }
            
            // Log the transaction
            $this->log_credit_transaction($user_id, $credit_type, $amount, $source, $new_balance);
            error_log("PMV: Successfully added {$amount} {$credit_type} credits to user {$user_id}. New balance: {$new_balance}");
            return true;
        } else {
            error_log("PMV: FAILED to add {$amount} {$credit_type} credits to user {$user_id}");
            return false;
        }
    }
    
    /**
     * Log credit transactions
     */
    private function log_credit_transaction($user_id, $credit_type, $amount, $action, $new_balance) {
        $transactions = get_user_meta($user_id, "_pmv_{$credit_type}_transactions", true);
        if (!is_array($transactions)) {
            $transactions = array();
        }
        
        $transactions[] = array(
            'timestamp' => current_time('timestamp'),
            'action' => $action,
            'amount' => $amount,
            'balance' => $new_balance,
            'description' => $action === 'purchase' ? 'Credit purchase' : 'Credit usage'
        );
        
        // Keep only last 100 transactions
        if (count($transactions) > 100) {
            $transactions = array_slice($transactions, -100);
        }
        
        update_user_meta($user_id, "_pmv_{$credit_type}_transactions", $transactions);
    }
    
    /**
     * Track free credits (daily rewards) separately from purchased credits
     */
    private function track_free_credits($user_id, $credit_type, $amount) {
        $meta_key = "_pmv_{$credit_type}_free_credits";
        $current_free_credits = intval(get_user_meta($user_id, $meta_key, true)) ?: 0;
        $new_free_balance = $current_free_credits + $amount;
        
        update_user_meta($user_id, $meta_key, $new_free_balance);
        error_log("PMV: Tracked free credits - User: {$user_id}, Type: {$credit_type}, Free Credits: {$new_free_balance}");
    }
    
    /**
     * Get user's free credits (daily rewards only)
     */
    public function get_user_free_credits($user_id, $credit_type) {
        if (!$user_id || !in_array($credit_type, ['image', 'text'])) {
            return 0;
        }
        
        $meta_key = "_pmv_{$credit_type}_free_credits";
        return intval(get_user_meta($user_id, $meta_key, true)) ?: 0;
    }
    
    /**
     * Get user's current credit balances
     */
    public function get_user_credits($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array(
                'image_credits' => 0,
                'text_credits' => 0
            );
        }
        
        $image_credits = intval(get_user_meta($user_id, '_pmv_image_credits', true)) ?: 0;
        $text_credits = intval(get_user_meta($user_id, '_pmv_text_credits', true)) ?: 0;
        
        return array(
            'image_credits' => $image_credits,
            'text_credits' => $text_credits
        );
    }
    
    /**
     * Check if user can generate images
     */
    public function can_generate_image($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            // Guest user - check daily limit
            $daily_limit = get_option('pmv_guest_daily_limit', 50);
            $cookie_name = 'pmv_guest_swarmui_usage';
            $cookie_data = array();
            
            if (isset($_COOKIE[$cookie_name])) {
                $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
                if (!is_array($cookie_data)) {
                    $cookie_data = array();
                }
            }
            
            $today = current_time('Y-m-d');
            $today_images = isset($cookie_data[$today]) ? intval($cookie_data[$today]) : 0;
            
            return $today_images < $daily_limit;
        }
        
        // Logged-in user - check credits
        $credits = $this->get_user_credits($user_id);
        return $credits['image_credits'] > 0;
    }
    
    /**
     * Check if user can generate text
     */
    public function can_generate_token($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            // Guest user - check monthly limit
            $monthly_limit = get_option('pmv_guest_monthly_limit', 10000);
            $current_month = current_time('Y-m');
            $token_cookie_name = 'pmv_guest_token_usage';
            $token_cookie_data = array();
            
            if (isset($_COOKIE[$token_cookie_name])) {
                $token_cookie_data = json_decode(stripslashes($_COOKIE[$token_cookie_name]), true);
                if (!is_array($token_cookie_data)) {
                    $token_cookie_data = array();
                }
            }
            
            $monthly_tokens = isset($token_cookie_data[$current_month]) ? intval($token_cookie_data[$current_month]) : 0;
            
            return $monthly_tokens < $monthly_limit;
        }
        
        // Logged-in user - check credits
        $credits = $this->get_user_credits($user_id);
        return $credits['text_credits'] > 0;
    }
    
    /**
     * Consume image credits and track usage
     */
    public function consume_image_credits($user_id, $amount = 1) {
        if (!$user_id) {
            return false;
        }
        
        $current_credits = intval(get_user_meta($user_id, '_pmv_image_credits', true)) ?: 0;
        
        if ($current_credits < $amount) {
            error_log("PMV: Insufficient image credits for user {$user_id}. Required: {$amount}, Available: {$current_credits}");
            return false;
        }
        
        $new_balance = $current_credits - $amount;
        update_user_meta($user_id, '_pmv_image_credits', $new_balance);
        
        // Track usage for the image usage tracker
        $this->track_image_usage($user_id, $amount);
        
        // Log the transaction
        $this->log_credit_transaction($user_id, 'image', -$amount, 'usage', $new_balance);
        
        error_log("PMV: Consumed {$amount} image credits from user {$user_id}. New balance: {$new_balance}");
        
        return true;
    }
    
    /**
     * Consume text credits and track usage
     */
    public function consume_text_credits($user_id, $amount = 1) {
        if (!$user_id) {
            return false;
        }
        
        $current_credits = intval(get_user_meta($user_id, '_pmv_text_credits', true)) ?: 0;
        
        if ($current_credits < $amount) {
            error_log("PMV: Insufficient text credits for user {$user_id}. Required: {$amount}, Available: {$current_credits}");
            return false;
        }
        
        $new_balance = $current_credits - $amount;
        update_user_meta($user_id, '_pmv_text_credits', $new_balance);
        
        // NOTE: We do NOT call track_text_usage here because:
        // 1. The word tracker (track_chat_completion) already records actual API token usage
        // 2. Calling both would create double counting and inflated usage numbers
        // 3. The subscription system should only track credit consumption, not usage statistics
        
        // Log the transaction
        $this->log_credit_transaction($user_id, 'text', -$amount, 'usage', $new_balance);
        
        error_log("PMV: Consumed {$amount} text credits from user {$user_id}. New balance: {$new_balance}");
        
        return true;
    }
    
    /**
     * Track image usage for the usage tracker
     */
    private function track_image_usage($user_id, $amount) {
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        // Update daily usage
        $daily_usage = get_user_meta($user_id, 'pmv_swarmui_daily_usage', true);
        if (!is_array($daily_usage)) {
            $daily_usage = array();
        }
        
        if (!isset($daily_usage[$today])) {
            $daily_usage[$today] = 0;
        }
        $daily_usage[$today] += $amount;
        update_user_meta($user_id, 'pmv_swarmui_daily_usage', $daily_usage);
        
        // Update monthly image usage (separate from token usage)
        $monthly_image_usage = get_user_meta($user_id, 'pmv_monthly_image_usage', true);
        if (!is_array($monthly_image_usage)) {
            $monthly_image_usage = array();
        }
        
        if (!isset($monthly_image_usage[$current_month])) {
            $monthly_image_usage[$current_month] = 0;
        }
        $monthly_image_usage[$current_month] += $amount;
        update_user_meta($user_id, 'pmv_monthly_image_usage', $monthly_image_usage);
        
        error_log("PMV: Tracked image usage for user {$user_id}: Daily: {$daily_usage[$today]}, Monthly: {$monthly_image_usage[$current_month]}");
    }
    
    // NOTE: track_text_usage method removed to eliminate double tracking
    // The word tracker (track_chat_completion) already handles actual API token usage
    // This prevents inflated usage numbers from estimated vs. actual token counts
    
    /**
     * Get user limits - now returns credit-based limits instead of daily/monthly quotas
     */
    public function get_user_limits($user_id) {
        if (!$user_id) {
            // Guest users get free limits from options
            return array(
                'image_credits' => intval(get_option('pmv_guest_daily_limit', 50)),
                'text_credits' => intval(get_option('pmv_guest_monthly_limit', 10000))
            );
        }
        
        // Logged-in users get their actual credit balances
        $credits = $this->get_user_credits($user_id);
        return array(
            'image_credits' => $credits['image_credits'],
            'text_credits' => $credits['text_credits']
        );
    }
    
    /**
     * Get current usage for a user
     */
    public function get_current_usage($user_id) {
        if (!$user_id) {
            // Guest users - get from cookies
            $cookie_name = 'pmv_guest_swarmui_usage';
            $cookie_data = [];
            
            if (isset($_COOKIE[$cookie_name])) {
                $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
                if (!is_array($cookie_data)) {
                    $cookie_data = [];
                }
            }
            
            $today = current_time('Y-m-d');
            $current_month = current_time('Y-m');
            
            $today_images = isset($cookie_data[$today]) ? intval($cookie_data[$today]) : 0;
            $monthly_tokens = 0;
            foreach ($cookie_data as $date => $count) {
                if (strpos($date, $current_month) === 0) {
                    $monthly_tokens += intval($count);
                }
            }
            
            return array(
                'today_images' => $today_images,
                'monthly_tokens' => $monthly_tokens
            );
        }
        
        // Logged-in users - get from user meta
        $daily_usage = get_user_meta($user_id, 'pmv_swarmui_daily_usage', true);
        $monthly_usage = get_user_meta($user_id, 'pmv_monthly_token_usage', true);
        $monthly_image_usage = get_user_meta($user_id, 'pmv_monthly_image_usage', true);
        
        if (!is_array($daily_usage)) {
            $daily_usage = array();
        }
        if (!is_array($monthly_usage)) {
            $monthly_usage = array();
        }
        if (!is_array($monthly_image_usage)) {
            $monthly_image_usage = array();
        }
        
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        $today_images = isset($daily_usage[$today]) ? intval($daily_usage[$today]) : 0;
        $monthly_tokens = isset($monthly_usage[$current_month]) ? intval($monthly_usage[$current_month]) : 0;
        $monthly_images = isset($monthly_image_usage[$current_month]) ? intval($monthly_image_usage[$current_month]) : 0;
        
        return array(
            'today_images' => $today_images,
            'monthly_tokens' => $monthly_tokens,
            'monthly_images' => $monthly_images
        );
    }
    
    /**
     * Get user's access plan (for compatibility)
     */
    public function get_user_access_plan($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $credits = $this->get_user_credits($user_id);
        
        if ($credits['image_credits'] > 0 || $credits['text_credits'] > 0) {
            return array(
                'tier' => 'credits',
                'image_credits' => $credits['image_credits'],
                'text_credits' => $credits['text_credits'],
                'start_date' => 'N/A',
                'expiry_date' => 'Never',
                'duration_days' => -1,
                'days_remaining' => -1,
                'start_date_formatted' => 'N/A',
                'expiry_date_formatted' => 'Never'
            );
        }
        
        return false;
    }
    
    /**
     * Get subscription tiers (for compatibility)
     */
    public function get_subscription_tiers() {
        return array(
            'credits' => array(
                'name' => 'Credit System',
                'daily_images' => -1,
                'monthly_tokens' => -1,
                'price' => 0,
                'duration_days' => -1,
                'description' => 'Pay-per-use credit system. Credits never expire and are cumulative.'
            )
        );
    }
    
    /**
     * Refresh credit rates from options
     */
    public function refresh_credit_rates() {
        $this->init_credit_rates();
    }
    
    /**
     * Handle option updates
     */
    public function handle_option_update($option_name, $old_value, $new_value) {
        // Check if any credit-related options were updated
        $credit_options = array(
            'pmv_image_credits_per_dollar',
            'pmv_text_credits_per_dollar',
            'pmv_image_product_name',
            'pmv_text_product_name',
            'pmv_image_product_description',
            'pmv_text_product_description'
        );
        
        if (in_array($option_name, $credit_options)) {
            $this->refresh_credit_rates();
            $this->update_existing_products();
        }
    }
    
    /**
     * Update existing products when options change
     */
    private function update_existing_products() {
        // Update image credits product
        $image_product = $this->get_credit_product('image');
        if ($image_product) {
            $product_id = $image_product->get_id();
            update_post_meta($product_id, '_pmv_credits_per_dollar', $this->credit_rates['image_credits_per_dollar']);
            
            // Update product name and description
            wp_update_post(array(
                'ID' => $product_id,
                'post_title' => $this->credit_rates['image_product_name'],
                'post_content' => $this->credit_rates['image_product_description'],
                'post_excerpt' => $this->credit_rates['image_product_description']
            ));
        }
        
        // Update text credits product
        $text_product = $this->get_credit_product('text');
        if ($text_product) {
            $product_id = $text_product->get_id();
            update_post_meta($product_id, '_pmv_credits_per_dollar', $this->credit_rates['text_credits_per_dollar']);
            
            // Update product name and description
            wp_update_post(array(
                'ID' => $product_id,
                'post_title' => $this->credit_rates['text_product_name'],
                'post_content' => $this->credit_rates['text_product_description'],
                'post_excerpt' => $this->credit_rates['text_product_description']
            ));
        }
    }
    
    /**
     * AJAX handler for getting credit status
     */
    public function ajax_get_credit_status() {
        try {
            // Clean any output buffers to prevent unwanted output
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            error_log("PMV: ajax_get_credit_status called for user: " . get_current_user_id());
            
            // Verify nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
                error_log("PMV: Nonce verification failed");
                wp_send_json_error('Invalid nonce');
            }
            
            error_log("PMV: Nonce verification successful");
            
            $user_id = get_current_user_id();
            error_log("PMV: User ID: " . $user_id);
            
            if (!$user_id) {
                error_log("PMV: No user ID found");
                wp_send_json_error('User not logged in');
            }
            
            error_log("PMV: Getting user credits...");
            $credits = $this->get_user_credits($user_id);
            error_log("PMV: Credits retrieved: " . json_encode($credits));
            
            error_log("PMV: Getting current usage...");
            $usage = $this->get_current_usage($user_id);
            error_log("PMV: Usage retrieved: " . json_encode($usage));
            
            error_log("PMV: Checking image generation capability...");
            $can_generate_image = $this->can_generate_image($user_id);
            error_log("PMV: Can generate image: " . ($can_generate_image ? 'YES' : 'NO'));
            
            error_log("PMV: Checking text generation capability...");
            $can_generate_text = $this->can_generate_token($user_id);
            error_log("PMV: Can generate text: " . ($can_generate_text ? 'YES' : 'NO'));
            
            $response = array(
                'credits' => $credits,
                'usage' => $usage,
                'can_generate_image' => $can_generate_image,
                'can_generate_text' => $can_generate_text
            );
            
            error_log("PMV: Sending successful response: " . json_encode($response));
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log("PMV: Exception in ajax_get_credit_status: " . $e->getMessage());
            error_log("PMV: Exception trace: " . $e->getTraceAsString());
            wp_send_json_error('Server error: ' . $e->getMessage());
        } catch (Error $e) {
            error_log("PMV: Fatal error in ajax_get_credit_status: " . $e->getMessage());
            error_log("PMV: Error trace: " . $e->getTraceAsString());
            wp_send_json_error('Fatal server error: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed credit product status
     */
    public function get_credit_product_status() {
        $status = array();
        
        // Check image credits product
        $image_product = $this->get_credit_product('image');
        if ($image_product) {
            $status['image'] = array(
                'id' => $image_product->get_id(),
                'name' => $image_product->get_name(),
                'status' => $image_product->get_status(),
                'stock_status' => $image_product->get_stock_status(),
                'manage_stock' => $image_product->get_manage_stock(),
                'stock_quantity' => $image_product->get_stock_quantity(),
                'price' => $image_product->get_price(),
                'regular_price' => $image_product->get_regular_price(),
                'catalog_visibility' => $image_product->get_catalog_visibility(),
                'meta' => array(
                    'credit_type' => get_post_meta($image_product->get_id(), '_pmv_credit_type', true),
                    'credits_per_dollar' => get_post_meta($image_product->get_id(), '_pmv_credits_per_dollar', true)
                )
            );
        } else {
            $status['image'] = 'Not found';
        }
        
        // Check text credits product
        $text_product = $this->get_credit_product('text');
        if ($text_product) {
            $status['text'] = array(
                'id' => $text_product->get_id(),
                'name' => $text_product->get_name(),
                'status' => $text_product->get_status(),
                'stock_status' => $text_product->get_stock_status(),
                'manage_stock' => $text_product->get_manage_stock(),
                'stock_quantity' => $text_product->get_stock_quantity(),
                'price' => $text_product->get_price(),
                'regular_price' => $text_product->get_regular_price(),
                'catalog_visibility' => $text_product->get_catalog_visibility(),
                'meta' => array(
                    'credit_type' => get_post_meta($text_product->get_id(), '_pmv_credit_type', true),
                    'credits_per_dollar' => get_post_meta($text_product->get_id(), '_pmv_credits_per_dollar', true)
                )
            );
        } else {
            $status['text'] = 'Not found';
        }
        
        return $status;
    }

    /**
     * Manually add credits to user (for testing/admin use)
     */
    public function manually_add_credits($user_id, $credit_type, $amount, $reason = 'Manual addition') {
        if (!$user_id || !in_array($credit_type, ['image', 'text'])) {
            return false;
        }
        
        $this->add_user_credits($user_id, $credit_type, $amount);
        
        error_log("PMV: Manually added {$amount} {$credit_type} credits to user {$user_id}. Reason: {$reason}");
        
        return true;
    }
    
    /**
     * Check if order has been processed for credits
     */
    public function check_order_credit_status($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array('error' => 'Order not found');
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return array('error' => 'Guest order - no user ID');
        }
        
        $status = array(
            'order_id' => $order_id,
            'user_id' => $user_id,
            'order_status' => $order->get_status(),
            'items' => array(),
            'user_credits_before' => array(),
            'user_credits_after' => array()
        );
        
        // Get current user credits
        $status['user_credits_before'] = $this->get_user_credits($user_id);
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            
            $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
            if (!$credit_type) {
                continue;
            }
            
            $item_info = array(
                'product_id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'credit_type' => $credit_type,
                'quantity' => $item->get_quantity(),
                'total_price' => $item->get_total(),
                'credits_per_dollar' => get_post_meta($product->get_id(), '_pmv_credits_per_dollar', true),
                'credits_to_add' => intval($item->get_total() * get_post_meta($product->get_id(), '_pmv_credits_per_dollar', true))
            );
            
            $status['items'][] = $item_info;
        }
        
        // Get user credits after processing
        $status['user_credits_after'] = $this->get_user_credits($user_id);
        
        return $status;
    }

    /**
     * Manually test credit processing for a specific order (for debugging)
     */
    public function debug_order_credit_processing($order_id) {
        error_log("PMV: 🧪 DEBUG: Testing credit processing for order: {$order_id}");
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("PMV: DEBUG: Order not found for ID: {$order_id}");
            return array('error' => 'Order not found');
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("PMV: DEBUG: No user ID for order {$order_id}");
            return array('error' => 'Guest order - no user ID');
        }
        
        error_log("PMV: DEBUG: Order details - Status: {$order->get_status()}, User: {$user_id}, Total: {$order->get_total()}");
        
        // Check if order contains credit products
        $contains_credits = false;
        $credit_items = array();
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                $credits_per_dollar = get_post_meta($product->get_id(), '_pmv_credits_per_dollar', true);
                
                error_log("PMV: DEBUG: Item - Product: {$product->get_name()} (ID: {$product->get_id()}), Credit Type: " . ($credit_type ?: 'NOT FOUND') . ", Credits per Dollar: {$credits_per_dollar}");
                
                if ($credit_type) {
                    $contains_credits = true;
                    $credit_items[] = array(
                        'product_id' => $product->get_id(),
                        'product_name' => $product->get_name(),
                        'credit_type' => $credit_type,
                        'quantity' => $item->get_quantity(),
                        'total_price' => $item->get_total(),
                        'credits_per_dollar' => $credits_per_dollar,
                        'credits_to_add' => intval($item->get_total() * $credits_per_dollar)
                    );
                }
            }
        }
        
        error_log("PMV: DEBUG: Order contains credit products: " . ($contains_credits ? 'YES' : 'NO'));
        
        if (!$contains_credits) {
            return array('error' => 'Order does not contain credit products');
        }
        
        // Check if credits were already processed
        $processed_key = "_pmv_credits_processed_{$order_id}";
        $already_processed = get_user_meta($user_id, $processed_key, true);
        error_log("PMV: DEBUG: Credits already processed: " . ($already_processed ? 'YES' : 'NO'));
        
        // Get current user credits
        $current_credits = $this->get_user_credits($user_id);
        error_log("PMV: DEBUG: Current user credits: " . json_encode($current_credits));
        
        return array(
            'success' => true,
            'order_id' => $order_id,
            'user_id' => $user_id,
            'order_status' => $order->get_status(),
            'contains_credits' => $contains_credits,
            'credit_items' => $credit_items,
            'already_processed' => $already_processed,
            'current_credits' => $current_credits
        );
    }

    /**
     * Manually process credits for an existing order
     */
    public function manually_process_order_credits($order_id) {
        error_log("PMV: 🚀 MANUAL CREDIT PROCESSING requested for order: {$order_id}");
        
        try {
            $result = $this->process_order_credits($order_id);
            if ($result !== false) {
                error_log("PMV: ✅ Manual credit processing completed for order: {$order_id}");
                return true;
            } else {
                error_log("PMV: ❌ Manual credit processing failed for order: {$order_id}");
                return false;
            }
        } catch (Exception $e) {
            error_log("PMV: ❌ Manual credit processing failed with exception: " . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("PMV: ❌ Manual credit processing failed with fatal error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and give daily login reward
     */
    public function check_daily_login_reward($user_login, $user) {
        if (!$user || !$user->ID) {
            return;
        }
        
        $user_id = $user->ID;
        $today = current_time('Y-m-d');
        
        // Check if user already got today's reward
        $last_reward_date = get_user_meta($user_id, '_pmv_last_daily_reward', true);
        
        if ($last_reward_date === $today) {
            error_log("PMV: User {$user_id} already received daily reward for {$today}");
            return;
        }
        
        // Get daily reward amounts and caps from options
        $daily_image_reward = intval(get_option('pmv_daily_image_reward', 10));
        $daily_text_reward = intval(get_option('pmv_daily_text_reward', 1000));
        $max_free_image_credits = intval(get_option('pmv_max_free_image_credits', 1000));
        $max_free_token_credits = intval(get_option('pmv_max_free_token_credits', 100000));
        
        // Check current user credits
        $current_image_credits = $this->get_user_credits($user_id, 'image');
        $current_text_credits = $this->get_user_credits($user_id, 'text');
        
        // Calculate how many free credits the user has (excluding purchased credits)
        $free_image_credits = $this->get_user_free_credits($user_id, 'image');
        $free_text_credits = $this->get_user_free_credits($user_id, 'text');
        
        // Apply image credit cap
        if ($daily_image_reward > 0 && $max_free_image_credits > 0) {
            $remaining_free_image_capacity = max(0, $max_free_image_credits - $free_image_credits);
            $actual_image_reward = min($daily_image_reward, $remaining_free_image_capacity);
            
            if ($actual_image_reward > 0) {
                $this->add_user_credits($user_id, 'image', $actual_image_reward, 'daily_reward');
                error_log("PMV: Daily login reward - Added {$actual_image_reward} image credits to user {$user_id} (capped at {$max_free_image_credits} free credits)");
            } else {
                error_log("PMV: Daily login reward - Image credits capped for user {$user_id} (already at max free credits: {$free_image_credits})");
            }
        } elseif ($daily_image_reward > 0) {
            // No cap set, give full reward
            $this->add_user_credits($user_id, 'image', $daily_image_reward, 'daily_reward');
            error_log("PMV: Daily login reward - Added {$daily_image_reward} image credits to user {$user_id}");
        }
        
        // Apply text credit cap
        if ($daily_text_reward > 0 && $max_free_token_credits > 0) {
            $remaining_free_text_capacity = max(0, $max_free_token_credits - $free_text_credits);
            $actual_text_reward = min($daily_text_reward, $remaining_free_text_capacity);
            
            if ($actual_text_reward > 0) {
                $this->add_user_credits($user_id, 'text', $actual_text_reward, 'daily_reward');
                error_log("PMV: Daily login reward - Added {$actual_text_reward} text credits to user {$user_id} (capped at {$max_free_token_credits} free credits)");
            } else {
                error_log("PMV: Daily login reward - Text credits capped for user {$user_id} (already at max free credits: {$free_text_credits})");
            }
        } elseif ($daily_text_reward > 0) {
            // No cap set, give full reward
            $this->add_user_credits($user_id, 'text', $daily_text_reward, 'daily_reward');
            error_log("PMV: Daily login reward - Added {$daily_text_reward} text credits to user {$user_id}");
        }
        
        // Mark today's reward as given
        update_user_meta($user_id, '_pmv_last_daily_reward', $today);
        
        // Log the reward transaction
        if (($daily_image_reward > 0 && $actual_image_reward > 0) || ($daily_text_reward > 0 && $actual_text_reward > 0)) {
            error_log("PMV: Daily login reward completed for user {$user_id} - Image: {$actual_image_reward}, Text: {$actual_text_reward}");
        }
    }
    
    /**
     * AJAX: Check daily reward status
     */
    public function ajax_check_daily_reward() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $today = current_time('Y-m-d');
        $last_reward_date = get_user_meta($user_id, '_pmv_last_daily_reward', true);
        
        $daily_image_reward = intval(get_option('pmv_daily_image_reward', 10));
        $daily_text_reward = intval(get_option('pmv_daily_text_reward', 1000));
        
        $can_claim = ($last_reward_date !== $today);
        
        wp_send_json_success(array(
            'can_claim' => $can_claim,
            'last_reward_date' => $last_reward_date,
            'today' => $today,
            'daily_image_reward' => $daily_image_reward,
            'daily_text_reward' => $daily_text_reward,
            'message' => $can_claim ? 'You can claim today\'s reward!' : 'You already claimed today\'s reward'
        ));
    }
    
    /**
     * Get daily reward status for a user
     */
    public function get_daily_reward_status($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $today = current_time('Y-m-d');
        $last_reward_date = get_user_meta($user_id, '_pmv_last_daily_reward', true);
        
        $daily_image_reward = intval(get_option('pmv_daily_image_reward', 10));
        $daily_text_reward = intval(get_option('pmv_daily_text_reward', 1000));
        
        return array(
            'can_claim' => ($last_reward_date !== $today),
            'last_reward_date' => $last_reward_date,
            'today' => $today,
            'daily_image_reward' => $daily_image_reward,
            'daily_text_reward' => $daily_text_reward,
            'next_reward_time' => $this->get_next_reward_time($last_reward_date)
        );
    }
    
    /**
     * AJAX: Check free credit status for current user
     */
    public function ajax_check_free_credit_status() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        // Get current free credit amounts
        $free_image_credits = $this->get_user_free_credits($user_id, 'image');
        $free_text_credits = $this->get_user_free_credits($user_id, 'text');
        
        // Get cap limits from options
        $max_image_credits = intval(get_option('pmv_max_free_image_credits', 1000));
        $max_text_credits = intval(get_option('pmv_max_free_token_credits', 100000));
        
        wp_send_json_success(array(
            'free_image_credits' => $free_image_credits,
            'free_text_credits' => $free_text_credits,
            'max_image_credits' => $max_image_credits,
            'max_text_credits' => $max_text_credits,
            'image_credits_remaining' => $max_image_credits > 0 ? max(0, $max_image_credits - $free_image_credits) : -1,
            'text_credits_remaining' => $max_text_credits > 0 ? max(0, $max_text_credits - $free_text_credits) : -1
        ));
    }
    
    /**
     * Calculate next reward time
     */
    private function get_next_reward_time($last_reward_date) {
        if (!$last_reward_date) {
            return 'Now';
        }
        
        $last_timestamp = strtotime($last_reward_date);
        $next_timestamp = $last_timestamp + (24 * 60 * 60); // 24 hours
        $now = current_time('timestamp');
        
        if ($next_timestamp <= $now) {
            return 'Now';
        }
        
        $diff = $next_timestamp - $now;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        if ($hours > 0) {
            return "in {$hours}h {$minutes}m";
        } else {
            return "in {$minutes}m";
        }
    }

    /**
     * Manually trigger daily reward for testing
     */
    public function manually_trigger_daily_reward($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array('error' => 'No user ID provided');
        }
        
        // Remove the last reward date to allow claiming again
        delete_user_meta($user_id, '_pmv_last_daily_reward');
        
        // Trigger the reward
        $this->check_daily_login_reward('', get_user_by('ID', $user_id));
        
        // Get new balance
        $new_balance = $this->get_user_credits($user_id);
        $daily_reward_status = $this->get_daily_reward_status($user_id);
        
        return array(
            'success' => true,
            'message' => 'Daily reward manually triggered',
            'new_balance' => $new_balance,
            'reward_status' => $daily_reward_status
        );
    }
    
    /**
     * Manually trigger order processing for testing
     */
    public function manually_trigger_order_processing($order_id) {
        error_log("PMV: 🧪 MANUALLY TRIGGERING ORDER PROCESSING for order: {$order_id}");
        
        // Remove any processed flags
        $order = wc_get_order($order_id);
        if ($order) {
            $user_id = $order->get_user_id();
            if ($user_id) {
                $processed_key = "_pmv_credits_processed_{$order_id}";
                delete_user_meta($user_id, $processed_key);
                error_log("PMV: Removed processed flag for order {$order_id}");
            }
        }
        
        // Process the order
        $this->process_order_credits($order_id);
        
        // Get new balance
        if ($order && $order->get_user_id()) {
            $new_balance = $this->get_user_credits($order->get_user_id());
            return array(
                'success' => true,
                'message' => "Order processing manually triggered for order {$order_id}",
                'new_balance' => $new_balance
            );
        }
        
        return array(
            'success' => true,
            'message' => "Order processing manually triggered for order {$order_id}"
        );
    }

    /**
     * Auto-complete credit-only orders
     */
    public function auto_complete_credit_orders($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $items = $order->get_items();
        $contains_credit_product = false;

        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                $contains_credit_product = true;
                break;
            }
        }

        if ($contains_credit_product) {
            error_log("PMV: Auto-completing order {$order_id} as it contains only credit products.");
            $order->update_status('completed');
            error_log("PMV: Order {$order_id} status updated to 'completed'.");
        }
    }

    /**
     * Ensure credit products are virtual
     */
    public function make_credit_products_virtual($is_virtual, $product) {
        if (get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
            return true;
        }
        return $is_virtual;
    }

    /**
     * Ensure credit products are downloadable
     */
    public function make_credit_products_downloadable($is_downloadable, $product) {
        if (get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
            return false;
        }
        return $is_downloadable;
    }

    /**
     * Allow immediate completion of credit orders
     */
    public function allow_credit_orders_completion($statuses, $order) {
        if ($order->has_status('completed') || $order->has_status('processing')) {
            return $statuses;
        }
        return array_merge($statuses, array('completed', 'processing'));
    }

    /**
     * Set up the cron job to check for missed credit orders
     */
    public function setup_credit_check_cron() {
        // Only schedule if not already scheduled
        if (!wp_next_scheduled('pmv_check_missed_credits')) {
            // ENHANCED: Run every 15 minutes instead of hourly for better responsiveness
            wp_schedule_event(time(), 'fifteen_minutes', 'pmv_check_missed_credits');
            error_log("PMV: Cron job 'pmv_check_missed_credits' scheduled for every 15 minutes.");
        }
    }

    /**
     * Check for orders that should have credits but don't
     */
    public function check_missed_credit_orders() {
        error_log("PMV: Checking for missed credit orders...");

        // Very simple query to avoid any SQL errors
        $orders_to_process = $this->wpdb->get_results(
            "SELECT ID FROM {$this->wpdb->posts} 
             WHERE post_type = 'shop_order' 
             AND post_status IN ('wc-completed', 'wc-processing') 
             LIMIT 10"
        );

        if (empty($orders_to_process)) {
            error_log("PMV: No missed credit orders found.");
            return;
        }

        foreach ($orders_to_process as $order_data) {
            $order_id = $order_data->ID;
            error_log("PMV: Found missed credit order: {$order_id}");
            $this->process_order_credits($order_id);
            error_log("PMV: Processed missed credit order: {$order_id}");
        }
    }

    /**
     * Manually process credits for a specific order ID (for testing/debugging)
     */
    public function manually_process_specific_order($order_id) {
        error_log("PMV: 🧪 MANUALLY PROCESSING SPECIFIC ORDER: {$order_id}");
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("PMV: ❌ Order not found for ID: {$order_id}");
            return array('error' => 'Order not found');
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("PMV: ❌ No user ID for order {$order_id}");
            return array('error' => 'Guest order - no user ID');
        }
        
        error_log("PMV: 📋 Order details - ID: {$order_id}, User: {$user_id}, Status: {$order->get_status()}");
        
        // Check if order contains credit products
        $contains_credits = false;
        $credit_items = array();
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                $credits_per_dollar = get_post_meta($product->get_id(), '_pmv_credits_per_dollar', true);
                
                error_log("PMV: 📦 Item - Product: {$product->get_name()} (ID: {$product->get_id()}), Credit Type: " . ($credit_type ?: 'NOT FOUND') . ", Credits per Dollar: {$credits_per_dollar}");
                
                if ($credit_type) {
                    $contains_credits = true;
                    $credit_items[] = array(
                        'product_id' => $product->get_id(),
                        'product_name' => $product->get_name(),
                        'credit_type' => $credit_type,
                        'quantity' => $item->get_quantity(),
                        'total_price' => $item->get_total(),
                        'credits_per_dollar' => $credits_per_dollar,
                        'credits_to_add' => intval($item->get_total() * $credits_per_dollar)
                    );
                }
            }
        }
        
        if (!$contains_credits) {
            error_log("PMV: ❌ Order does not contain credit products");
            return array('error' => 'Order does not contain credit products');
        }
        
        // Get current user credits
        $current_credits = $this->get_user_credits($user_id);
        error_log("PMV: 💰 Current user credits: " . json_encode($current_credits));
        
        // Check if credits were already processed
        $processed_key = "_pmv_credits_processed_{$order_id}";
        $already_processed = get_user_meta($user_id, $processed_key, true);
        
        if ($already_processed) {
            error_log("PMV: ⚠️ Credits already processed for order {$order_id} - clearing flag to reprocess");
            delete_user_meta($user_id, $processed_key);
        }
        
        // Process the order credits
        error_log("PMV: 🚀 Processing credits for order {$order_id}...");
        $this->process_order_credits($order_id);
        
        // Get new balance
        $new_credits = $this->get_user_credits($user_id);
        error_log("PMV: 💰 New user credits: " . json_encode($new_credits));
        
        // Calculate credits added
        $credits_added = array(
            'image' => $new_credits['image_credits'] - $current_credits['image_credits'],
            'text' => $new_credits['text_credits'] - $current_credits['text_credits']
        );
        
        error_log("PMV: ✅ Credits added - Image: {$credits_added['image']}, Text: {$credits_added['text']}");
        
        return array(
            'success' => true,
            'order_id' => $order_id,
            'user_id' => $user_id,
            'order_status' => $order->get_status(),
            'credit_items' => $credit_items,
            'credits_before' => $current_credits,
            'credits_after' => $new_credits,
            'credits_added' => $credits_added,
            'was_already_processed' => $already_processed ? true : false
        );
    }

    /**
     * Check for orders with processing status that might not have had credits processed
     */
    public function check_processing_orders_for_credits() {
        if (!$this->is_woocommerce_active()) {
            return false;
        }
        
        error_log("PMV: 🔍 Checking for processing orders that need credits...");
        
        // Get orders with processing status
        $processing_orders = wc_get_orders(array(
            'status' => 'processing',
            'limit' => 100, // Limit to prevent performance issues
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $orders_processed = 0;
        $orders_with_credits = 0;
        
        foreach ($processing_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                $orders_with_credits++;
                
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🔍 Found processing order {$order_id} with credits that haven't been processed - processing now");
                    $this->process_order_credits($order_id);
                    $orders_processed++;
                } else {
                    error_log("PMV: ✅ Order {$order_id} already has credits processed");
                }
            }
        }
        
        error_log("PMV: 🔍 Processing orders check complete - Found {$orders_with_credits} orders with credits, processed {$orders_processed} new orders");
        
        return array(
            'total_orders' => count($processing_orders),
            'orders_with_credits' => $orders_with_credits,
            'orders_processed' => $orders_processed
        );
    }

    /**
     * Get user's subscription tier (for compatibility - now returns credit status)
     */
    public function get_user_subscription_tier($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'guest';
        }
        
        $credits = $this->get_user_credits($user_id);
        
        // Return credit-based status instead of tiers
        if ($credits['image_credits'] > 1000 && $credits['text_credits'] > 100000) {
            return 'premium'; // High credit balance
        } elseif ($credits['image_credits'] > 100 && $credits['text_credits'] > 10000) {
            return 'basic'; // Medium credit balance
        } else {
            return 'free'; // Low or no credits
        }
    }

    /**
     * Test if WooCommerce hooks are actually firing by simulating an order event
     */
    public function test_woocommerce_hook_execution() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot test WooCommerce hooks - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: 🧪 Testing WooCommerce hook execution...");
        
        // Check if our hooks are registered
        $hooks_registered = array(
            'woocommerce_order_status_completed' => has_action('woocommerce_order_status_completed', array($this, 'handle_order_completion')),
            'woocommerce_order_status_processing' => has_action('woocommerce_order_status_processing', array($this, 'handle_order_completion')),
            'woocommerce_payment_complete' => has_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete')),
            'woocommerce_order_status_changed' => has_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change')),
            'woocommerce_checkout_order_processed' => has_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created'))
        );
        
        $all_hooks_registered = true;
        foreach ($hooks_registered as $hook => $has_action) {
            if (!$has_action) {
                $all_hooks_registered = false;
                error_log("PMV: ❌ Hook {$hook} is NOT registered");
            } else {
                error_log("PMV: ✅ Hook {$hook} is registered");
            }
        }
        
        if ($all_hooks_registered) {
            error_log("PMV: ✅ All WooCommerce hooks are properly registered");
        } else {
            error_log("PMV: ❌ Some WooCommerce hooks are missing - attempting to re-register");
            $this->register_woocommerce_hooks();
        }
        
        return $all_hooks_registered;
    }

    /**
     * Comprehensive audit of all orders to find any that need credits processed
     */
    public function audit_all_orders_for_credits() {
        if (!$this->is_woocommerce_active()) {
            return false;
        }
        
        error_log("PMV: 🔍 Starting comprehensive order audit for credits...");
        
        try {
            // Get all orders from the last 30 days that might need processing
            $orders = wc_get_orders(array(
                'status' => array('processing', 'completed', 'on-hold'),
                'limit' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
                'date_created' => '>' . (time() - (30 * 24 * 60 * 60)) // Last 30 days
            ));
            
            $total_orders = count($orders);
            $orders_processed = 0;
            $orders_with_credits = 0;
            $errors = 0;
            
            error_log("PMV: Found {$total_orders} orders to audit");
            
            foreach ($orders as $order) {
                try {
                    $order_id = $order->get_id();
                    $user_id = $order->get_user_id();
                    
                    if (!$user_id) {
                        continue; // Skip guest orders
                    }
                    
                    // Check if order contains credit products
                    $contains_credits = false;
                    $credit_items = array();
                    
                    foreach ($order->get_items() as $item) {
                        if (!$item) {
                            continue;
                        }
                        
                        $product = $item->get_product();
                        if ($product && is_object($product) && method_exists($product, 'get_id')) {
                            $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                            if ($credit_type) {
                                $contains_credits = true;
                                $credit_items[] = array(
                                    'product_id' => $product->get_id(),
                                    'product_name' => $product->get_name(),
                                    'credit_type' => $credit_type,
                                    'quantity' => $item->get_quantity(),
                                    'total_price' => $item->get_total()
                                );
                            }
                        }
                    }
                    
                    if ($contains_credits) {
                        $orders_with_credits++;
                        
                        // Check if credits were already processed
                        $processed_key = "_pmv_credits_processed_{$order_id}";
                        $already_processed = get_user_meta($user_id, $processed_key, true);
                        
                        if (!$already_processed) {
                            error_log("PMV: 🔍 Order {$order_id} needs credit processing - processing now");
                            
                            // Process the credits
                            $this->process_order_credits($order_id);
                            $orders_processed++;
                            
                            error_log("PMV: ✅ Order {$order_id} credits processed successfully");
                        } else {
                            error_log("PMV: ✅ Order {$order_id} already has credits processed");
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    error_log("PMV: ❌ Error processing order {$order_id}: " . $e->getMessage());
                } catch (Error $e) {
                    $errors++;
                    error_log("PMV: ❌ Fatal error processing order {$order_id}: " . $e->getMessage());
                }
            }
            
            error_log("PMV: 🔍 Order audit completed - Total: {$total_orders}, With Credits: {$orders_with_credits}, Processed: {$orders_processed}, Errors: {$errors}");
            
            return array(
                'total_orders' => $total_orders,
                'orders_with_credits' => $orders_with_credits,
                'orders_processed' => $orders_processed,
                'errors' => $errors,
                'timestamp' => current_time('timestamp')
            );
            
        } catch (Exception $e) {
            error_log("PMV: ❌ Critical error in order audit: " . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("PMV: ❌ Fatal error in order audit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cron job method to check for missed credits
     * This runs hourly to catch any orders that might have been missed
     */
    public function check_missed_credits() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cron job check_missed_credits - WooCommerce not active");
            return;
        }
        
        error_log("PMV: 🕐 Cron job: Checking for missed credits...");
        
        // Check processing orders first
        $processing_result = $this->check_processing_orders_for_credits();
        
        // Also check completed orders that might have been missed
        $completed_orders = wc_get_orders(array(
            'status' => 'completed',
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $completed_processed = 0;
        $completed_with_credits = 0;
        
        foreach ($completed_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                $completed_with_credits++;
                
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🕐 Cron job: Found completed order {$order_id} with credits that haven't been processed - processing now");
                    $this->process_order_credits($order_id);
                    $completed_processed++;
                }
            }
        }
        
        error_log("PMV: 🕐 Cron job: Missed credits check complete - Processing orders: {$processing_result['orders_processed']}, Completed orders: {$completed_processed}");
        
        // Log summary for monitoring
        $total_processed = $processing_result['orders_processed'] + $completed_processed;
        if ($total_processed > 0) {
            error_log("PMV: 🕐 Cron job: Successfully processed credits for {$total_processed} orders");
        }
    }

    /**
     * Manually trigger the cron job for testing purposes
     */
    public function manually_trigger_cron_job() {
        error_log("PMV: 🚀 Manually triggering cron job...");
        $this->check_missed_credits();
        return true;
    }

    /**
     * Check if the cron job is properly scheduled
     */
    public function check_cron_job_status() {
        $next_scheduled = wp_next_scheduled('pmv_check_missed_credits');
        $is_scheduled = $next_scheduled !== false;
        
        $status = array(
            'is_scheduled' => $is_scheduled,
            'next_run' => $is_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'Not scheduled',
            'current_time' => date('Y-m-d H:i:s'),
            'time_until_next' => $is_scheduled ? human_time_diff(time(), $next_scheduled) : 'N/A'
        );
        
        // If not scheduled, try to schedule it
        if (!$is_scheduled) {
            error_log("PMV: Cron job not scheduled, attempting to schedule...");
            if (!wp_next_scheduled('pmv_check_missed_credits')) {
                // ENHANCED: Use 15-minute interval for better responsiveness
                wp_schedule_event(time(), 'fifteen_minutes', 'pmv_check_missed_credits');
                error_log("PMV: Cron job 'pmv_check_missed_credits' scheduled for every 15 minutes.");
                $status['rescheduled'] = true;
                $status['next_run'] = date('Y-m-d H:i:s', wp_next_scheduled('pmv_check_missed_credits'));
            }
        }
        
        return $status;
    }

    /**
     * Force process all pending orders for credits
     * This is useful for catching up on any orders that might have been missed
     */
    public function force_process_all_pending_orders() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot force process orders - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: 🚀 Force processing all pending orders for credits...");
        
        $statuses_to_check = ['processing', 'completed', 'pending', 'on-hold'];
        $total_processed = 0;
        $total_orders = 0;
        
        foreach ($statuses_to_check as $status) {
            $orders = wc_get_orders(array(
                'status' => $status,
                'limit' => 100,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            foreach ($orders as $order) {
                $order_id = $order->get_id();
                $user_id = $order->get_user_id();
                
                if (!$user_id) {
                    continue; // Skip guest orders
                }
                
                // Check if order contains credit products
                $contains_credits = false;
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                        $contains_credits = true;
                        break;
                    }
                }
                
                if ($contains_credits) {
                    $total_orders++;
                    
                    // Check if credits were already processed
                    $processed_key = "_pmv_credits_processed_{$order_id}";
                    if (!get_user_meta($user_id, $processed_key, true)) {
                        error_log("PMV: 🚀 Force processing: Found order {$order_id} with status '{$status}' that needs credits - processing now");
                        $this->process_order_credits($order_id);
                        $total_processed++;
                    } else {
                        error_log("PMV: ✅ Force processing: Order {$order_id} already has credits processed");
                    }
                }
            }
        }
        
        error_log("PMV: 🚀 Force processing complete - Found {$total_orders} orders with credits, processed {$total_processed} new orders");
        
        return array(
            'total_orders' => $total_orders,
            'orders_processed' => $total_processed
        );
    }

    /**
     * Check if WooCommerce hooks are properly registered
     */
    public function check_woocommerce_hooks_status() {
        $hooks_status = array(
            'woocommerce_order_status_completed' => has_action('woocommerce_order_status_completed', array($this, 'handle_order_completion')),
            'woocommerce_order_status_processing' => has_action('woocommerce_order_status_processing', array($this, 'handle_order_processing')),
            'woocommerce_payment_complete' => has_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete')),
            'woocommerce_order_status_changed' => has_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change')),
            'woocommerce_checkout_order_processed' => has_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created')),
            'woocommerce_admin_order_status_changed' => has_action('woocommerce_admin_order_status_changed', array($this, 'handle_admin_order_status_change')),
            'woocommerce_update_order' => has_action('woocommerce_update_order', array($this, 'handle_order_update')),
            'woocommerce_save_order' => has_action('woocommerce_save_order', array($this, 'handle_order_save')),
            'post_updated' => has_action('post_updated', array($this, 'handle_post_updated')),
            'pmv_check_missed_credits' => has_action('pmv_check_missed_credits', array($this, 'check_missed_credits'))
        );
        
        $all_hooks_registered = true;
        foreach ($hooks_status as $hook => $has_action) {
            if (!$has_action) {
                $all_hooks_registered = false;
            }
        }
        
        return array(
            'hooks_status' => $hooks_status,
            'all_hooks_registered' => $all_hooks_registered,
            'total_hooks' => count($hooks_status),
            'registered_hooks' => array_sum($hooks_status)
        );
    }

    /**
     * Handle new order creation
     */
    public function handle_new_order($order_id) {
        error_log("PMV: 🔥 NEW ORDER HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * Handle order pending status
     */
    public function handle_order_pending($order_id) {
        error_log("PMV: 🔥 ORDER PENDING HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * Handle order on-hold status
     */
    public function handle_order_on_hold($order_id) {
        error_log("PMV: 🔥 ORDER ON-HOLD HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * Handle order failed status
     */
    public function handle_order_failed($order_id) {
        error_log("PMV: 🔥 ORDER FAILED HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * Handle checkout order creation
     */
    public function handle_checkout_create_order($order, $data) {
        error_log("PMV: 🔥 CHECKOUT CREATE ORDER HOOK FIRED for order: " . $order->get_id());
        // Don't process credits here as the order isn't fully created yet
    }

    /**
     * Handle checkout order created
     */
    public function handle_checkout_order_created($order_id) {
        error_log("PMV: 🔥 CHECKOUT ORDER CREATED HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * Handle order object updated properties - this is a comprehensive hook
     */
    public function handle_order_updated_props($order, $updated_props) {
        $order_id = $order->get_id();
        error_log("PMV: 🔥 ORDER UPDATED PROPS HOOK FIRED for order: {$order_id}");
        error_log("PMV: Updated properties: " . implode(', ', $updated_props));
        
        // Check if status was updated
        if (in_array('status', $updated_props)) {
            $new_status = $order->get_status();
            error_log("PMV: Order status updated to: {$new_status}");
            
            // Process credits for valid statuses
            if (in_array($new_status, ['completed', 'processing', 'pending', 'on-hold'])) {
                error_log("PMV: 🚀 Processing credits for order status update to '{$new_status}'");
                $this->check_and_process_credit_order($order_id);
            }
        }
        
        // Also check if this is a new order being created
        if (in_array('id', $updated_props) && $order_id > 0) {
            error_log("PMV: 🆕 New order created with ID: {$order_id}");
            $this->check_and_process_credit_order($order_id);
        }
    }

    /**
     * Handle save post
     */
    public function handle_save_post($post_id, $post, $update) {
        if ($post->post_type === 'shop_order') {
            error_log("PMV: 🔥 SAVE POST HOOK FIRED for order: {$post_id}");
            $this->check_and_process_credit_order($post_id);
        }
    }

    /**
     * Test if WooCommerce hooks are actually firing by simulating order events
     */
    public function test_hook_execution() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot test hooks - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: 🧪 Testing hook execution by simulating order events...");
        
        try {
            // Test if we can manually trigger the hooks
            $test_order_id = 999999;
            
            // Test order status changed hook
            error_log("PMV: 🧪 Testing woocommerce_order_status_changed hook...");
            do_action('woocommerce_order_status_changed', $test_order_id, 'pending', 'processing', null);
            
            // Test order completion hook
            error_log("PMV: 🧪 Testing woocommerce_order_status_completed hook...");
            do_action('woocommerce_order_status_completed', $test_order_id);
            
            // Test order processing hook
            error_log("PMV: 🧪 Testing woocommerce_order_status_processing hook...");
            do_action('woocommerce_order_status_processing', $test_order_id);
            
            // Test new order hook
            error_log("PMV: 🧪 Testing woocommerce_new_order hook...");
            do_action('woocommerce_new_order', $test_order_id);
            
            error_log("PMV: ✅ Hook execution test completed");
            return true;
            
        } catch (Exception $e) {
            error_log("PMV: ❌ Hook execution test failed with exception: " . $e->getMessage());
            error_log("PMV: Exception trace: " . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("PMV: ❌ Hook execution test failed with fatal error: " . $e->getMessage());
            error_log("PMV: Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Force process credits for any processing orders that haven't been processed yet
     * This is useful for catching orders that might have been missed by hooks
     */
    public function force_process_processing_orders() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot force process processing orders - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: 🚀 Force processing all processing orders for credits...");
        
        // Get all processing orders
        $processing_orders = wc_get_orders(array(
            'status' => 'processing',
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $total_orders = 0;
        $orders_processed = 0;
        
        foreach ($processing_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                $total_orders++;
                
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🚀 Force processing: Found processing order {$order_id} that needs credits - processing now");
                    $this->process_order_credits($order_id);
                    $orders_processed++;
                } else {
                    error_log("PMV: ✅ Force processing: Order {$order_id} already has credits processed");
                }
            }
        }
        
        error_log("PMV: 🚀 Force processing complete - Found {$total_orders} processing orders with credits, processed {$orders_processed} new orders");
        
        return array(
            'total_orders' => $total_orders,
            'orders_processed' => $orders_processed
        );
    }

    /**
     * ENHANCED: Force register all hooks from admin interface
     */
    public function force_register_hooks() {
        error_log("PMV: 🚀 FORCE REGISTER HOOKS: Admin requested force registration of all hooks...");
        
        // First try normal registration
        if ($this->is_woocommerce_active()) {
            error_log("PMV: 🚀 FORCE REGISTER HOOKS: WooCommerce is active, using normal registration...");
            $this->register_woocommerce_hooks();
            return true;
        }
        
        // If WooCommerce isn't fully active, try fallback
        error_log("PMV: 🚀 FORCE REGISTER HOOKS: WooCommerce not fully active, trying fallback...");
        if ($this->fallback_hook_registration()) {
            error_log("PMV: ✅ FORCE REGISTER HOOKS: Fallback hooks registered successfully");
            return true;
        }
        
        // Last resort - try to register basic hooks anyway
        error_log("PMV: 🚨 FORCE REGISTER HOOKS: Last resort - trying to register basic hooks...");
        try {
            add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10);
            add_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 10);
            add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
            add_action('init', array($this, 'check_processing_orders_immediate'), 999);
            
            error_log("PMV: ✅ FORCE REGISTER HOOKS: Basic hooks registered as last resort");
            return true;
            
        } catch (Exception $e) {
            error_log("PMV: ❌ FORCE REGISTER HOOKS: All attempts failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all existing WooCommerce hooks to prevent duplicates
     */
    private function clear_all_hooks() {
        error_log("PMV: 🧹 Clearing all existing WooCommerce hooks...");
        
        // Remove all our hooks
        remove_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10);
        remove_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 5);
        remove_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 10);
        remove_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 5);
        remove_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 1);
        remove_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 10);
        remove_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 5);
        remove_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        remove_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 5, 4);
        remove_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created'), 10, 3);
        remove_action('woocommerce_update_order', array($this, 'handle_order_update'), 10, 2);
        remove_action('woocommerce_save_order', array($this, 'handle_order_save'), 10, 2);
        remove_action('woocommerce_admin_order_status_changed', array($this, 'handle_admin_order_status_change'), 10, 3);
        remove_action('post_updated', array($this, 'handle_post_updated'), 10, 3);
        remove_action('save_post', array($this, 'handle_save_post'), 10, 3);
        remove_action('woocommerce_order_object_updated_props', array($this, 'handle_order_updated_props'), 10, 2);
        remove_action('woocommerce_new_order', array($this, 'handle_new_order'), 10, 1);
        remove_action('woocommerce_order_status_pending', array($this, 'handle_order_pending'), 10, 1);
        remove_action('woocommerce_order_status_on_hold', array($this, 'handle_order_on_hold'), 10, 1);
        remove_action('woocommerce_order_status_failed', array($this, 'handle_order_failed'), 10, 1);
        remove_action('woocommerce_checkout_create_order', array($this, 'handle_checkout_create_order'), 10, 2);
        remove_action('woocommerce_checkout_order_created', array($this, 'handle_checkout_order_created'), 10, 1);
        remove_action('woocommerce_payment_complete', array($this, 'auto_complete_credit_orders'), 5);
        
        error_log("PMV: ✅ All existing hooks cleared");
    }

    /**
     * Manually trigger order status change for testing
     * This simulates what happens when an order status is changed
     */
    public function manually_trigger_order_status_change($order_id, $old_status = 'pending', $new_status = 'processing') {
        error_log("PMV: 🧪 Manually triggering order status change for testing...");
        error_log("PMV: Order: {$order_id}, Old: {$old_status}, New: {$new_status}");
        
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: ❌ Cannot trigger status change - WooCommerce not active");
            return false;
        }
        
        // Check if hooks are registered
        $hooks_status = $this->check_woocommerce_hooks_status();
        if (!$hooks_status['all_hooks_registered']) {
            error_log("PMV: ❌ Hooks not properly registered, attempting to register now...");
            $this->force_register_hooks();
        }
        
        // Manually trigger the order status changed hook
        do_action('woocommerce_order_status_changed', $order_id, $old_status, $new_status, null);
        
        // Also trigger the specific status hooks
        if ($new_status === 'processing') {
            do_action('woocommerce_order_status_processing', $order_id);
        } elseif ($new_status === 'completed') {
            do_action('woocommerce_order_status_completed', $order_id);
        }
        
        error_log("PMV: ✅ Order status change hooks triggered successfully");
        return true;
    }

    /**
     * Check and force process credits for a specific order
     * This is useful for debugging specific orders
     */
    public function debug_and_force_process_order($order_id) {
        error_log("PMV: 🔍 Debugging and force processing order: {$order_id}");
        
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: ❌ Cannot process order - WooCommerce not active");
            return array('error' => 'WooCommerce not active');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("PMV: ❌ Order {$order_id} not found");
            return array('error' => 'Order not found');
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("PMV: ❌ Order {$order_id} has no user ID (guest order)");
            return array('error' => 'Guest orders not supported');
        }
        
        // Get order details
        $order_status = $order->get_status();
        $order_total = $order->get_total();
        $order_items = $order->get_items();
        
        error_log("PMV: 📋 Order details - Status: {$order_status}, Total: {$order_total}, Items: " . count($order_items));
        
        // Check if order contains credit products
        $contains_credits = false;
        $credit_items = array();
        
        foreach ($order_items as $item) {
            $product = $item->get_product();
            if ($product) {
                $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                $product_name = $product->get_name();
                $product_id = $product->get_id();
                $quantity = $item->get_quantity();
                $total = $item->get_total();
                
                error_log("PMV: 📦 Item - Product: {$product_name} (ID: {$product_id}), Credit Type: " . ($credit_type ?: 'NOT FOUND'));
                
                if ($credit_type) {
                    $contains_credits = true;
                    $credit_items[] = array(
                        'product_name' => $product_name,
                        'product_id' => $product_id,
                        'credit_type' => $credit_type,
                        'quantity' => $quantity,
                        'total' => $total
                    );
                }
            }
        }
        
        if (!$contains_credits) {
            error_log("PMV: ❌ Order {$order_id} does not contain credit products");
            return array('error' => 'Order does not contain credit products');
        }
        
        // Check if credits were already processed
        $processed_key = "_pmv_credits_processed_{$order_id}";
        $already_processed = get_user_meta($user_id, $processed_key, true);
        
        if ($already_processed) {
            error_log("PMV: ✅ Order {$order_id} already has credits processed at: " . date('Y-m-d H:i:s', $already_processed));
            return array(
                'message' => 'Credits already processed',
                'processed_at' => date('Y-m-d H:i:s', $already_processed),
                'order_status' => $order_status,
                'credit_items' => $credit_items
            );
        }
        
        // Get current user credits
        $current_credits = $this->get_user_credits($user_id);
        error_log("PMV: 💳 Current credits - Image: {$current_credits['image_credits']}, Text: {$current_credits['text_credits']}");
        
        // Force process the order credits
        error_log("PMV: 🚀 Force processing credits for order {$order_id}");
        $this->process_order_credits($order_id);
        
        // Get new user credits
        $new_credits = $this->get_user_credits($user_id);
        error_log("PMV: 💳 New credits - Image: {$new_credits['image_credits']}, Text: {$new_credits['text_credits']}");
        
        // Check if credits were actually processed
        $credits_added = array(
            'image' => $new_credits['image_credits'] - $current_credits['image_credits'],
            'text' => $new_credits['text_credits'] - $current_credits['text_credits']
        );
        
        return array(
            'message' => 'Credits processed successfully',
            'order_status' => $order_status,
            'credit_items' => $credit_items,
            'credits_before' => $current_credits,
            'credits_after' => $new_credits,
            'credits_added' => $credits_added,
            'processed_at' => current_time('Y-m-d H:i:s')
        );
    }

    /**
     * ENHANCED: Comprehensive order created handler
     */
    public function handle_order_created_comprehensive($order_id) {
        error_log("PMV: 🔥 ENHANCED ORDER CREATED HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * ENHANCED: Comprehensive order status change handler
     */
    public function handle_order_status_change_comprehensive($order_id, $old_status, $new_status, $order) {
        error_log("PMV: 🔥 ENHANCED ORDER STATUS CHANGE HOOK FIRED - Order: {$order_id}, Old: {$old_status}, New: {$new_status}");
        
        // Process credits for any valid status
        if (in_array($new_status, ['completed', 'processing', 'pending', 'on-hold'])) {
            error_log("PMV: 🚀 Processing credits for enhanced status change to {$new_status}");
            $this->check_and_process_credit_order($order_id);
        }
    }

    /**
     * ENHANCED: Comprehensive payment complete handler
     */
    public function handle_payment_complete_comprehensive($order_id) {
        error_log("PMV: 🔥 ENHANCED PAYMENT COMPLETE HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * ENHANCED: Immediate checkout order created handler
     */
    public function handle_checkout_order_created_immediate($order_id) {
        error_log("PMV: 🔥 IMMEDIATE CHECKOUT ORDER CREATED HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * ENHANCED: Immediate new order handler
     */
    public function handle_new_order_immediate($order_id) {
        error_log("PMV: 🔥 IMMEDIATE NEW ORDER HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * ENHANCED: Comprehensive save order handler
     */
    public function handle_save_order_comprehensive($order_id, $order) {
        error_log("PMV: 🔥 ENHANCED SAVE ORDER HOOK FIRED for order: {$order_id}");
        $this->check_and_process_credit_order($order_id);
    }

    /**
     * ENHANCED: Comprehensive save post handler
     */
    public function handle_save_post_comprehensive($post_id, $post, $update) {
        if ($post->post_type === 'shop_order') {
            error_log("PMV: 🔥 ENHANCED SAVE POST HOOK FIRED for order: {$post_id}");
            $this->check_and_process_credit_order($post_id);
        }
    }

    /**
     * ENHANCED: Enhanced checkout create order handler
     */
    public function handle_checkout_create_order_enhanced($order, $data) {
        error_log("PMV: 🔥 ENHANCED CHECKOUT CREATE ORDER HOOK FIRED for order: " . $order->get_id());
        // Don't process credits here as the order isn't fully created yet, but log it
        error_log("PMV: Order creation in progress - will process when order is fully created");
    }

    /**
     * ENHANCED: Immediate check for processing orders that might have been missed
     */
    public function check_processing_orders_immediate() {
        // Only run this once per request to avoid performance issues
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        error_log("PMV: 🚀 IMMEDIATE CHECK: Looking for processing orders that need credits...");
        
        // Get recent processing orders (last 24 hours)
        $processing_orders = wc_get_orders(array(
            'status' => 'processing',
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'date_created' => '>' . (time() - 86400) // Last 24 hours
        ));
        
        $orders_checked = 0;
        $orders_processed = 0;
        
        foreach ($processing_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            $orders_checked++;
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🚀 IMMEDIATE CHECK: Found processing order {$order_id} with credits that haven't been processed - processing now");
                    $this->process_order_credits($order_id);
                    $orders_processed++;
                } else {
                    error_log("PMV: ✅ IMMEDIATE CHECK: Order {$order_id} already has credits processed");
                }
            }
        }
        
        if ($orders_checked > 0) {
            error_log("PMV: 🚀 IMMEDIATE CHECK: Checked {$orders_checked} processing orders, processed {$orders_processed} new orders");
        }
    }

    /**
     * ENHANCED: Manually process credits for new orders (for immediate processing)
     */
    public function manually_process_new_orders() {
        if (!$this->is_woocommerce_active()) {
            error_log("PMV: Cannot process new orders - WooCommerce not active");
            return false;
        }
        
        error_log("PMV: 🚀 MANUAL PROCESSING: Looking for new orders that need credits...");
        
        // Get recent orders (last 2 hours) that might need credits
        $recent_orders = wc_get_orders(array(
            'status' => array('processing', 'completed', 'pending', 'on-hold'),
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'date_created' => '>' . (time() - 7200) // Last 2 hours
        ));
        
        $orders_checked = 0;
        $orders_processed = 0;
        $orders_with_credits = 0;
        
        foreach ($recent_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            $orders_checked++;
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                $orders_with_credits++;
                
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🚀 MANUAL PROCESSING: Found order {$order_id} with credits that haven't been processed - processing now");
                    $this->process_order_credits($order_id);
                    $orders_processed++;
                } else {
                    error_log("PMV: ✅ MANUAL PROCESSING: Order {$order_id} already has credits processed");
                }
            }
        }
        
        error_log("PMV: 🚀 MANUAL PROCESSING: Checked {$orders_checked} recent orders, found {$orders_with_credits} with credits, processed {$orders_processed} new orders");
        
        return array(
            'total_orders' => count($recent_orders),
            'orders_checked' => $orders_checked,
            'orders_with_credits' => $orders_with_credits,
            'orders_processed' => $orders_processed
        );
    }

    /**
     * Add custom cron schedule for 1-minute intervals
     */
    public function add_one_minute_schedule($schedules) {
        $schedules['one_minute'] = array(
            'interval' => 60, // 1 minute in seconds
            'display' => __('Every 1 Minute', 'text_domain')
        );
        return $schedules;
    }

    /**
     * ENHANCED: Fallback hook registration - register hooks even if WooCommerce isn't fully loaded
     */
    public function fallback_hook_registration() {
        error_log("PMV: 🚨 FALLBACK HOOK REGISTRATION: Attempting to register hooks even if WooCommerce isn't fully loaded...");
        
        // Check if WooCommerce class exists at minimum
        if (!class_exists('WooCommerce')) {
            error_log("PMV: ❌ FALLBACK HOOK REGISTRATION: WooCommerce class not found, cannot register hooks");
            return false;
        }
        
        // Try to register hooks anyway
        try {
            error_log("PMV: 🚀 FALLBACK HOOK REGISTRATION: Registering hooks with basic WooCommerce support...");
            
            // Register basic hooks that should work
            add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10);
            add_action('woocommerce_order_status_processing', array($this, 'handle_order_processing'), 10);
            add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
            add_action('woocommerce_payment_complete', array($this, 'handle_order_payment_complete'), 10);
            
            // Also add the immediate processing check
            add_action('init', array($this, 'check_processing_orders_immediate'), 999);
            
            error_log("PMV: ✅ FALLBACK HOOK REGISTRATION: Basic hooks registered successfully");
            return true;
            
        } catch (Exception $e) {
            error_log("PMV: ❌ FALLBACK HOOK REGISTRATION: Failed to register hooks: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ENHANCED: Schedule automatic order audit every minute
     */
    public function schedule_automatic_audit() {
        // Only schedule if not already scheduled
        if (!wp_next_scheduled('pmv_automatic_order_audit')) {
            wp_schedule_event(time(), 'one_minute', 'pmv_automatic_order_audit');
            error_log("PMV: Cron job 'pmv_automatic_order_audit' scheduled for every 1 minute.");
        }
    }

    /**
     * ENHANCED: Automatic order audit
     */
    public function automatic_order_audit() {
        error_log("PMV: 🚀 Automatic order audit triggered...");
        $this->audit_all_orders_for_credits();
        error_log("PMV: Automatic order audit completed.");
    }

    /**
     * ENHANCED: Process orders on every page load/refresh for immediate credit processing
     */
    public function process_orders_on_page_load() {
        // Only run once per page load to avoid performance issues
        static $already_ran = false;
        if ($already_ran) {
            return false;
        }
        $already_ran = true;
        
        if (!$this->is_woocommerce_active()) {
            return false;
        }
        
        error_log("PMV: 🔍 PAGE LOAD: Comprehensive check for orders that need credits...");
        
        try {
            // Check ALL recent orders regardless of status (last 7 days to keep it fast)
            $all_orders = wc_get_orders(array(
                'limit' => 100, // Increased limit to catch more orders
                'orderby' => 'date',
                'order' => 'DESC',
                'date_created' => '>' . (time() - (7 * 24 * 60 * 60)) // Last 7 days
            ));
            
            $orders_processed = 0;
            $orders_with_credits = 0;
            $orders_checked = 0;
            
            foreach ($all_orders as $order) {
                try {
                    $order_id = $order->get_id();
                    $user_id = $order->get_user_id();
                    $order_status = $order->get_status();
                    
                    if (!$user_id) {
                        continue; // Skip guest orders
                    }
                    
                    $orders_checked++;
                    
                    // Check if order contains credit products
                    $contains_credits = false;
                    
                    foreach ($order->get_items() as $item) {
                        if (!$item) {
                            continue;
                        }
                        
                        $product = $item->get_product();
                        if ($product && is_object($product) && method_exists($product, 'get_id')) {
                            $credit_type = get_post_meta($product->get_id(), '_pmv_credit_type', true);
                            if ($credit_type) {
                                $contains_credits = true;
                                break;
                            }
                        }
                    }
                    
                    if ($contains_credits) {
                        $orders_with_credits++;
                        
                        // Check if credits were already processed
                        $processed_key = "_pmv_credits_processed_{$order_id}";
                        $already_processed = get_user_meta($user_id, $processed_key, true);
                        
                        if (!$already_processed) {
                            error_log("PMV: 🚀 PAGE LOAD: Order {$order_id} (Status: {$order_status}) needs credits - processing now");
                            
                            // Process the credits
                            $this->process_order_credits($order_id);
                            $orders_processed++;
                            
                            error_log("PMV: ✅ PAGE LOAD: Order {$order_id} credits processed successfully");
                        } else {
                            error_log("PMV: ✅ PAGE LOAD: Order {$order_id} already has credits processed");
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("PMV: ❌ Error processing order {$order_id} on page load: " . $e->getMessage());
                } catch (Error $e) {
                    error_log("PMV: ❌ Fatal error processing order {$order_id} on page load: " . $e->getMessage());
                }
            }
            
            if ($orders_checked > 0) {
                error_log("PMV: 🚀 PAGE LOAD: Comprehensive check complete - Checked {$orders_checked} orders, Found {$orders_with_credits} with credits, Processed {$orders_processed} new orders");
            }
            
            return array(
                'orders_checked' => $orders_checked,
                'orders_with_credits' => $orders_with_credits,
                'orders_processed' => $orders_processed
            );
            
        } catch (Exception $e) {
            error_log("PMV: ❌ Error in page load order processing: " . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("PMV: ❌ Fatal error in page load order processing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ENHANCED: Force run on every request to ensure credits are processed
     */
    public function force_process_orders_immediate() {
        // Only run this once per request to avoid performance issues
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        error_log("PMV: 🚀 IMMEDIATE CHECK: Looking for processing orders that need credits...");
        
        // Get recent processing orders (last 24 hours)
        $processing_orders = wc_get_orders(array(
            'status' => 'processing',
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'date_created' => '>' . (time() - 86400) // Last 24 hours
        ));
        
        $orders_checked = 0;
        $orders_processed = 0;
        
        foreach ($processing_orders as $order) {
            $order_id = $order->get_id();
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue; // Skip guest orders
            }
            
            $orders_checked++;
            
            // Check if order contains credit products
            $contains_credits = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_pmv_credit_type', true)) {
                    $contains_credits = true;
                    break;
                }
            }
            
            if ($contains_credits) {
                // Check if credits were already processed
                $processed_key = "_pmv_credits_processed_{$order_id}";
                if (!get_user_meta($user_id, $processed_key, true)) {
                    error_log("PMV: 🚀 IMMEDIATE CHECK: Found processing order {$order_id} with credits that haven't been processed - processing now");
                    $this->process_order_credits($order_id);
                    $orders_processed++;
                } else {
                    error_log("PMV: ✅ IMMEDIATE CHECK: Order {$order_id} already has credits processed");
                }
            }
        }
        
        if ($orders_checked > 0) {
            error_log("PMV: 🚀 IMMEDIATE CHECK: Checked {$orders_checked} processing orders, processed {$orders_processed} new orders");
        }
    }

}
