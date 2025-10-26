<?php
/**
* Plugin Name: PNG Metadata Viewer
* Description: Display/manage PNG cards with embedded metadata. Now includes a new Chat button (powered by OpenAI) that replaces the old chat link, content moderation, and an access plan system with WooCommerce integration. All original settings (modal, download button, card styles, filters, secondary button, chat button, chat modal, chat input, and OpenAI settings) are configurable via the settings page. Chat history is saved per user. Access plan tiers provide different image and token limits for a specific duration.
* Version: 4.16-SUBSCRIPTION-SYSTEM
* Author: Your Name
* Text Domain: png-metadata-viewer
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PMV_PLUGIN_FILE', __FILE__);
define('PMV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMV_PLUGIN_URL', trailingslashit(plugins_url('', __FILE__)));
define('PMV_VERSION', '4.16-SUBSCRIPTION-SYSTEM');

// Start output buffering to catch any unexpected output during activation
if (!defined('DOING_AJAX') && !defined('DOING_CRON') && !defined('DOING_AUTOSAVE')) {
    ob_start();
}

// CRITICAL FIX: Include conversation database functions FIRST
require_once PMV_PLUGIN_DIR . 'conversations-database.php';

// Include required files - CORRECTED PATHS BASED ON ACTUAL STRUCTURE
if (file_exists(PMV_PLUGIN_DIR . 'includes/metadata-reader.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/metadata-reader.php';
}

// Include admin settings - CORRECT PATH TO INCLUDES FOLDER
require_once PMV_PLUGIN_DIR . 'includes/admin-settings.php';

if (file_exists(PMV_PLUGIN_DIR . 'includes/enqueue.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/enqueue.php';
} else {
    // Fallback - include from root if exists
    if (file_exists(PMV_PLUGIN_DIR . 'enqueue.php')) {
        require_once PMV_PLUGIN_DIR . 'enqueue.php';
    }
}

if (file_exists(PMV_PLUGIN_DIR . 'includes/word-tracker.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/word-tracker.php';
}

// NEW_INSERT: include image usage tracker
if (file_exists(PMV_PLUGIN_DIR . 'includes/image-usage-tracker.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/image-usage-tracker.php';
}

if (file_exists(PMV_PLUGIN_DIR . 'includes/shortcodes.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/shortcodes.php';
}

if (file_exists(PMV_PLUGIN_DIR . 'includes/grid-helper.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/grid-helper.php';
}

if (file_exists(PMV_PLUGIN_DIR . 'includes/ajax-handlers.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/ajax-handlers.php';
}

// Load SwarmUI API handler
if (file_exists(PMV_PLUGIN_DIR . 'includes/swarmui-api-handler.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/swarmui-api-handler.php';
}

// Load cache handler if it exists
if (file_exists(PMV_PLUGIN_DIR . 'includes/cache-handler.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/cache-handler.php';
}

// Load integration file if it exists
if (file_exists(PMV_PLUGIN_DIR . 'integration.php')) {
    require_once PMV_PLUGIN_DIR . 'integration.php';
}

// Load validation utilities
if (file_exists(PMV_PLUGIN_DIR . 'includes/validation-utils.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/validation-utils.php';
}

// Load logging system
if (file_exists(PMV_PLUGIN_DIR . 'includes/logging.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/logging.php';
}

// Load database optimizer
if (file_exists(PMV_PLUGIN_DIR . 'includes/database-optimizer.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/database-optimizer.php';
}

// Load rate limiter
if (file_exists(PMV_PLUGIN_DIR . 'includes/rate-limiter.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/rate-limiter.php';
}

// Load image presets system
if (file_exists(PMV_PLUGIN_DIR . 'includes/image-presets.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/image-presets.php';
}

// Add hook to backup settings before plugin updates
add_action('upgrader_process_complete', 'pmv_backup_settings_before_update', 10, 2);

/**
 * Backup settings before plugin updates
 */
function pmv_backup_settings_before_update($upgrader, $hook_extra) {
    // Only backup if this is our plugin being updated
    if (isset($hook_extra['plugin']) && strpos($hook_extra['plugin'], 'png-metadata-viewer') !== false) {
        // Check if settings manager is available
        if (class_exists('PMV_Settings_Manager')) {
            $settings_manager = PMV_Settings_Manager::getInstance();
            $settings_manager->backup_settings();
            error_log('PMV: Settings backed up before plugin update');
        }
    }
}

// Load content moderation system
if (file_exists(PMV_PLUGIN_DIR . 'includes/content-moderation.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/content-moderation.php';
}

// Load subscription system
if (file_exists(PMV_PLUGIN_DIR . 'includes/subscription-system.php')) {
    require_once PMV_PLUGIN_DIR . 'includes/subscription-system.php';
}

// Load voting system - only if WordPress is ready
if (file_exists(PMV_PLUGIN_DIR . 'includes/voting-system.php') && function_exists('add_action')) {
    error_log('PMV: Loading voting system...');
    require_once PMV_PLUGIN_DIR . 'includes/voting-system.php';
    error_log('PMV: Voting system loaded successfully');
} else {
    error_log('PMV: Voting system not loaded - file exists: ' . (file_exists(PMV_PLUGIN_DIR . 'includes/voting-system.php') ? 'YES' : 'NO') . ', add_action exists: ' . (function_exists('add_action') ? 'YES' : 'NO'));
}

// CRITICAL FIX: Enhanced script enqueuing with proper priority and dependencies
function pmv_enqueue_scripts() {
    // Only enqueue on frontend
    if (is_admin()) return;
    
    // Register scripts first
    wp_register_script(
        'png-metadata-viewer-script',
        PMV_PLUGIN_URL . '/script.js',
        array('jquery', 'masonry', 'jquery-masonry'),
        PMV_VERSION,
        true
    );
    
    wp_register_script(
        'pmv-chat-core',
        PMV_PLUGIN_URL . '/js/chat-core.js',
        array('jquery'),
        PMV_VERSION,
        true
    );
    
    wp_register_script(
        'png-metadata-viewer-chat',
        PMV_PLUGIN_URL . '/chat.js',
        array('jquery', 'pmv-chat-core'),
        PMV_VERSION,
        true
    );
    
    // CRITICAL FIX: Register the FIXED conversation manager script - CORRECT PATH
    wp_register_script(
        'pmv-conversation-manager',
        PMV_PLUGIN_URL . '/js/conversation-manager.js',
        array('jquery'),
        PMV_VERSION,
        true
    );
    
    // Register filter enhancements script
    wp_register_script(
        'pmv-filter-enhancements',
        PMV_PLUGIN_URL . '/js/filter-enhancements.js',
        array('jquery'),
        PMV_VERSION,
        true
    );
    
    // Register styles - CORRECT PATHS
    wp_register_style(
        'pmv-conversation-styles',
        PMV_PLUGIN_URL . '/css/conversation-styles.css',
        array(),
        PMV_VERSION
    );
    
    wp_register_style(
        'pmv-enhanced-styles',
        PMV_PLUGIN_URL . '/enhanced-styles.css',
        array(),
        PMV_VERSION
    );

    wp_register_style(
        'pmv-metadata-viewer-styles',
        PMV_PLUGIN_URL . '/css/png-metadata-viewer-styles.css',
        array(),
        PMV_VERSION
    );
    
    // Enqueue scripts
    wp_enqueue_script('png-metadata-viewer-chat');
    wp_enqueue_script('pmv-conversation-manager');
    wp_enqueue_script('pmv-filter-enhancements');
    
    // Enqueue styles
    wp_enqueue_style('pmv-conversation-styles');
    wp_enqueue_style('dashicons');
    wp_enqueue_style('pmv-metadata-viewer-styles');
    wp_enqueue_style('pmv-voting-styles', trailingslashit(PMV_PLUGIN_URL) . 'css/voting-system-styles.css', array(), PMV_VERSION);
    
    // Enqueue enhanced styles with high priority to ensure dark theme
    wp_enqueue_style(
        'pmv-enhanced-styles',
        PMV_PLUGIN_URL . '/enhanced-styles.css',
        array(),
        PMV_VERSION . '.' . filemtime(PMV_PLUGIN_DIR . 'enhanced-styles.css')
    );
    
    // Main plugin styles - enhanced-styles.css contains all the styling
    // wp_enqueue_style(
    //     'png-metadata-viewer-style', 
    //     PMV_PLUGIN_URL . '/style.css', 
    //     array(), 
    //     PMV_VERSION
    // );
    
    error_log('PMV: All scripts and styles enqueued successfully');
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_scripts', 10);

// CRITICAL FIX: Enhanced script localization that runs AFTER enqueuing
function pmv_localize_scripts() {
    // Only run on frontend
    if (is_admin()) return;
    
    // Get current user data for conversation manager
    $current_user = wp_get_current_user();
    
    // CRITICAL FIX: Check if conversation table exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'pmv_conversations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // CRITICAL FIX: Check if AJAX handlers are registered
    $ajax_handlers_status = array(
        'save' => has_action('wp_ajax_pmv_save_conversation'),
        'get_conversations' => has_action('wp_ajax_pmv_get_conversations'),
        'get_conversation' => has_action('wp_ajax_pmv_get_conversation'),
        'delete' => has_action('wp_ajax_pmv_delete_conversation'),
        'swarmui_models' => has_action('wp_ajax_pmv_get_available_models'),
        'swarmui_generate' => has_action('wp_ajax_pmv_generate_image'),
        'swarmui_prompt' => has_action('wp_ajax_pmv_generate_image_prompt'),
        'swarmui_test' => has_action('wp_ajax_pmv_test_swarmui_connection'),
        'credit_status' => has_action('wp_ajax_pmv_get_credit_status'),
        'credit_test' => has_action('wp_ajax_pmv_test_credit_system')
    );
    
    // Build comprehensive AJAX data object
    $ajax_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'plugin_url' => PMV_PLUGIN_URL,
        'nonce' => wp_create_nonce('pmv_ajax_nonce'),
        
        // CRITICAL: Complete user account data for conversation management
        'user_id' => $current_user->ID,
        'user_display_name' => $current_user->display_name ?: $current_user->user_login,
        'is_logged_in' => is_user_logged_in(),
        'login_url' => wp_login_url(get_permalink()),
        'register_url' => wp_registration_url(),
        
        // API settings with CORRECT option names
        'api_key' => get_option('openai_api_key', ''),
        'api_model' => get_option('openai_model', 'gpt-3.5-turbo'),
        'api_base_url' => get_option('openai_api_base_url', 'https://api.openai.com/v1'),
        'temperature' => get_option('openai_temperature', 0.7),
        'max_tokens' => get_option('openai_max_tokens', 4000),
        
        // Chat settings
        'chat_button_text' => get_option('png_metadata_chat_button_text', 'Chat'),
        'cards_per_page' => get_option('png_metadata_cards_per_page', 12),
        'conversation_enabled' => true,
        
        // Guest user limits for credit display
        'guest_limits' => array(
            'daily_images' => intval(get_option('pmv_guest_daily_limit', 50)),
            'monthly_tokens' => intval(get_option('pmv_guest_daily_token_limit', 10000))
        ),
        
        // CRITICAL: Enhanced debug info for troubleshooting
        'debug' => array(
            'api_key_set' => !empty(get_option('openai_api_key', '')),
            'api_base_url' => get_option('openai_api_base_url', 'https://api.openai.com/v1'),
            'model' => get_option('openai_model', 'gpt-3.5-turbo'),
            'version' => PMV_VERSION,
            'user_logged_in' => is_user_logged_in(),
            'user_id' => $current_user->ID,
            'table_exists' => $table_exists,
            'table_name' => $table_name,
            'ajax_handlers' => $ajax_handlers_status,
            'nonce_action' => 'pmv_ajax_nonce',
            'current_url' => get_permalink(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_dir' => PMV_PLUGIN_DIR,
            'plugin_url' => PMV_PLUGIN_URL,
            'scripts_enqueued' => array(
                'conversation_manager' => wp_script_is('pmv-conversation-manager', 'enqueued'),
                'chat' => wp_script_is('png-metadata-viewer-chat', 'enqueued'),
                'main' => wp_script_is('png-metadata-viewer-script', 'enqueued')
            )
        )
    );
    
    // Debug nonce creation
    error_log("PMV: Nonce created: " . substr($ajax_data['nonce'], 0, 10) . "...");
    error_log("PMV: User ID: " . $ajax_data['user_id']);
    error_log("PMV: Is logged in: " . ($ajax_data['is_logged_in'] ? 'YES' : 'NO'));
    error_log("PMV: Current user object: " . ($current_user ? 'EXISTS' : 'NULL'));
    
    // CRITICAL FIX: Localize ALL scripts that need AJAX data
    $scripts_to_localize = array(
        'png-metadata-viewer-chat',
        'pmv-conversation-manager',
        'png-metadata-viewer-script'
    );
    
    $localized_count = 0;
    foreach ($scripts_to_localize as $script_handle) {
        if (wp_script_is($script_handle, 'enqueued')) {
            wp_localize_script($script_handle, 'pmv_ajax_object', $ajax_data);
            $localized_count++;
        }
    }
    
    // Log for debugging
    error_log("PMV: AJAX data localized for $localized_count scripts. Table exists: " . ($table_exists ? 'YES' : 'NO'));
    error_log("PMV: AJAX handlers status: " . json_encode($ajax_handlers_status));
    
    if (!$table_exists) {
        error_log("PMV WARNING: Conversation table does not exist! This will cause save failures.");
    }
    
    $missing_handlers = array_filter($ajax_handlers_status, function($status) { return !$status; });
    if (!empty($missing_handlers)) {
        error_log("PMV WARNING: Missing AJAX handlers: " . implode(', ', array_keys($missing_handlers)));
    }
}
// CRITICAL FIX: Run localization AFTER script enqueuing
add_action('wp_enqueue_scripts', 'pmv_localize_scripts', 20);

// Add simple AJAX test action for debugging
add_action('wp_ajax_wp_ajax_test', 'pmv_test_ajax');
add_action('wp_ajax_nopriv_wp_ajax_test', 'pmv_test_ajax');

function pmv_test_ajax() {
    // Clean any output buffers to prevent unwanted output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    wp_send_json_success(array(
        'message' => 'Basic AJAX is working',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'is_logged_in' => is_user_logged_in()
    ));
}

// Add nonce test endpoint
add_action('wp_ajax_pmv_test_nonce', 'pmv_test_nonce');
add_action('wp_ajax_nopriv_pmv_test_nonce', 'pmv_test_nonce');

function pmv_test_nonce() {
    // Clean any output buffers to prevent unwanted output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isset($_POST['nonce'])) {
        wp_send_json_error('No nonce provided');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    wp_send_json_success(array(
        'message' => 'Nonce is valid',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'is_logged_in' => is_user_logged_in(),
        'nonce_received' => substr($_POST['nonce'], 0, 10) . '...'
    ));
}

// Add simple credit test endpoint
add_action('wp_ajax_pmv_test_simple_credits', 'pmv_test_simple_credits');
add_action('wp_ajax_nopriv_pmv_test_simple_credits', 'pmv_test_simple_credits');

function pmv_test_simple_credits() {
    // Clean any output buffers to prevent unwanted output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isset($_POST['nonce'])) {
        wp_send_json_error('No nonce provided');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }
    
    // Simple credit check without complex methods
    $image_credits = intval(get_user_meta($user_id, '_pmv_image_credits', true)) ?: 0;
    $text_credits = intval(get_user_meta($user_id, '_pmv_text_credits', true)) ?: 0;
    
    wp_send_json_success(array(
        'message' => 'Simple credit test successful',
        'user_id' => $user_id,
        'credits' => array(
            'image_credits' => $image_credits,
            'text_credits' => $text_credits
        ),
        'timestamp' => current_time('mysql')
    ));
}

// Add working credit endpoint that mimics the shortcode exactly
add_action('wp_ajax_pmv_get_working_credits', 'pmv_get_working_credits');
add_action('wp_ajax_nopriv_pmv_get_working_credits', 'pmv_get_working_credits');

function pmv_get_working_credits() {
    // Clean any output buffers to prevent unwanted output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isset($_POST['nonce'])) {
        wp_send_json_error('No nonce provided');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Check if subscription system is available (exactly like the shortcode)
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $subscription_system = PMV_Subscription_System::getInstance();
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }
    
    // Get credits and usage exactly like the shortcode does
    $credits = $subscription_system->get_user_credits($user_id);
    $usage = $subscription_system->get_current_usage($user_id);
    
    wp_send_json_success(array(
        'message' => 'Credits retrieved successfully',
        'user_id' => $user_id,
        'credits' => $credits,
        'usage' => $usage,
        'timestamp' => current_time('mysql')
    ));
}

// Enhanced version check function
function pmv_check_dependencies() {
    $missing_dependencies = array();
    
    // Check required PHP version
    if (version_compare(PHP_VERSION, '7.0', '<')) {
        $missing_dependencies[] = 'PHP 7.0 or higher (current version: ' . PHP_VERSION . ')';
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $missing_dependencies[] = 'WordPress 5.0 or higher (current version: ' . $wp_version . ')';
    }
    
    // Display notice if dependencies are missing
    if (!empty($missing_dependencies)) {
        // Only add admin notice if not during plugin activation
        if (!wp_doing_ajax() && !wp_doing_cron() && !defined('DOING_AUTOSAVE')) {
            add_action('admin_notices', function() use ($missing_dependencies) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>PNG Metadata Viewer</strong> requires the following to function properly:</p>';
                echo '<ul style="list-style-type: disc; padding-left: 20px;">';
                foreach ($missing_dependencies as $dep) {
                    echo '<li>' . esc_html($dep) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            });
        }
        return false;
    }
    
    return true;
}

// CRITICAL FIX: Enhanced activation function with proper error handling
function pmv_activate_plugin() {
    global $wpdb;
    
    error_log('PMV: Plugin activation started');
    
    // Create upload directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    
    if (!file_exists($png_cards_dir)) {
        wp_mkdir_p($png_cards_dir);
        error_log('PMV: Created upload directory: ' . $png_cards_dir);
    }
    
    // Create directory structure for JS/CSS
    $js_dir = PMV_PLUGIN_DIR . 'js/';
    $css_dir = PMV_PLUGIN_DIR . 'css/';
    
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    
    // Set default options if they don't exist
    $default_options = array(
        'png_metadata_cards_per_page' => 12,
        'png_metadata_filter1_title' => 'Category',
        'png_metadata_filter2_title' => 'Style',
        'png_metadata_filter3_title' => 'Creator',
        'png_metadata_filter4_title' => 'Version',
        'pmv_allow_guest_conversations' => 1,
        'pmv_auto_save_conversations' => 1,
        'pmv_max_conversations_per_user' => 50,
        'pmv_guest_daily_token_limit' => 5000,
        'pmv_default_user_monthly_limit' => 100000,
        'pmv_guest_daily_limit' => 50,
        'pmv_guest_monthly_limit' => 10000,
        
        // Credit System Defaults
        'pmv_image_credits_per_dollar' => 100,
        'pmv_text_credits_per_dollar' => 10000,
        'pmv_image_product_name' => 'Image Credits',
        'pmv_text_product_name' => 'Text Credits',
        'pmv_image_product_description' => 'Purchase image generation credits. Credits never expire and are cumulative.',
        'pmv_text_product_description' => 'Purchase text generation tokens. Tokens never expire and are cumulative.',
        
        // Daily Reward Defaults
        'pmv_daily_reward_enabled' => 1,
        'pmv_daily_image_reward' => 10,
        'pmv_daily_text_reward' => 1000,
        'openai_api_base_url' => 'https://api.openai.com/v1/',
        'openai_model' => 'gpt-3.5-turbo',
        'openai_temperature' => 0.7,
        'openai_max_tokens' => 1000,
        'openai_presence_penalty' => 0.6,
        'openai_frequency_penalty' => 0.3,
        'png_metadata_chat_button_text' => 'Chat',
        'png_metadata_chat_button_text_color' => '#ffffff',
        'png_metadata_chat_button_bg_color' => '#28a745'
    );
    
    foreach ($default_options as $option => $value) {
        if (!get_option($option)) {
            update_option($option, $value);
        }
    }
    
    error_log('PMV: Default options set');
    
    // CRITICAL FIX: Create conversation table using the function from conversations-database.php
    if (function_exists('pmv_create_conversation_tables')) {
        pmv_create_conversation_tables();
        error_log('PMV: ✅ Conversation tables created via dedicated function');
    } else {
        error_log('PMV: ⚠️ pmv_create_conversation_tables function not found, creating table manually');
        
        // Fallback table creation
        $charset_collate = $wpdb->get_charset_collate();
        $conversations_table = $wpdb->prefix . 'pmv_conversations';
        $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
        
        // Create conversations table
        $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NULL,
            character_id varchar(255) NOT NULL,
            character_name varchar(255) NOT NULL,
            title varchar(500) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY character_id (character_id),
            KEY created_at (created_at),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        // Create messages table
        $messages_sql = "CREATE TABLE IF NOT EXISTS $messages_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            conversation_id int(11) NOT NULL,
            role enum('user','assistant') NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($conversations_sql);
        dbDelta($messages_sql);
        
        // Verify table creation
        $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table;
        $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
        
        if ($conversations_exists && $messages_exists) {
            error_log("PMV: ✅ Tables created successfully via fallback method");
            update_option('pmv_conversation_db_version', '1.4');
        } else {
            error_log("PMV: ❌ Failed to create tables via fallback method");
        }
    }
    
    // Create subscription products if WooCommerce is active
    if (class_exists('WooCommerce') && class_exists('PMV_Subscription_System')) {
        $subscription_system = PMV_Subscription_System::getInstance();
        $subscription_system->create_subscription_products();
        error_log('PMV: Subscription products created');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    error_log('PMV: Plugin activation completed');
    
    // Clear any output buffer that might have been generated during activation
    if (ob_get_level()) {
        ob_end_clean();
    }
}
register_activation_hook(__FILE__, 'pmv_activate_plugin');

// Add hook to backup settings when plugin is deactivated
add_action('deactivated_plugin', 'pmv_backup_settings_on_deactivation');

/**
 * Backup settings when plugin is deactivated
 */
function pmv_backup_settings_on_deactivation($plugin) {
    // Only backup if this is our plugin being deactivated
    if (strpos($plugin, 'png-metadata-viewer') !== false) {
        // Check if settings manager is available
        if (class_exists('PMV_Settings_Manager')) {
            $settings_manager = PMV_Settings_Manager::getInstance();
            $settings_manager->backup_settings();
            error_log('PMV: Settings backed up on plugin deactivation');
        }
    }
}

// Add hook to backup settings before WordPress updates
add_action('wp_version_check', 'pmv_backup_settings_before_wp_update');
add_action('wp_update_plugins', 'pmv_backup_settings_before_wp_update');

// Add settings backup dashboard widget
add_action('wp_dashboard_setup', 'pmv_add_dashboard_widget');

/**
 * Backup settings before WordPress updates
 */
function pmv_backup_settings_before_wp_update() {
    // Check if settings manager is available
    if (class_exists('PMV_Settings_Manager')) {
        $settings_manager = PMV_Settings_Manager::getInstance();
        $settings_manager->backup_settings();
        error_log('PMV: Settings backed up before WordPress update');
    }
}

/**
 * Add settings backup dashboard widget
 */
function pmv_add_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'pmv_settings_backup_widget',
            'PNG Metadata Viewer - Settings Protection',
            'pmv_settings_backup_dashboard_widget'
        );
    }
}

/**
 * Dashboard widget content
 */
function pmv_settings_backup_dashboard_widget() {
    if (!class_exists('PMV_Settings_Manager')) {
        echo '<p>Settings backup system not available.</p>';
        return;
    }
    
    try {
        $settings_manager = PMV_Settings_Manager::getInstance();
        $backup = get_option('pmv_settings_backup');
        $last_backup = get_option('pmv_settings_backup_last_backup', 0);
        $current_time = current_time('timestamp');
        $validation_result = $settings_manager->validate_settings();
        
        // Ensure validation_result has the expected structure
        if (!is_array($validation_result) || !isset($validation_result['issues'])) {
            $validation_result = array(
                'issues' => array(),
                'repairs' => 0,
                'total_settings' => 0
            );
        }
        
        // Calculate backup age
        $backup_age = $last_backup ? human_time_diff($last_backup, $current_time) : 'Never';
        
        // Determine status
        $status_class = 'good';
        $status_text = 'Protected';
        
        if (!$backup) {
            $status_class = 'critical';
            $status_text = 'No Backup';
        } elseif ($current_time - $last_backup > 86400) { // 24 hours
            $status_class = 'warning';
            $status_text = 'Outdated';
        }
        
        if (is_array($validation_result['issues']) && count($validation_result['issues']) > 0) {
            $status_class = 'warning';
            $status_text = 'Issues Found';
        }
    } catch (Exception $e) {
        // If dashboard widget fails, show safe defaults
        error_log('PMV Dashboard Widget Error: ' . $e->getMessage());
        $status_class = 'critical';
        $status_text = 'Error';
        $backup_age = 'Unknown';
        $validation_result = array(
            'issues' => array(),
            'repairs' => 0,
            'total_settings' => 0
        );
    }
    
    ?>
    <div class="pmv-dashboard-widget">
        <div class="pmv-status-indicator <?php echo $status_class; ?>">
            <span class="status-text"><?php echo $status_text; ?></span>
        </div>
        
        <div class="pmv-widget-content">
            <p><strong>Last Backup:</strong> <?php echo $backup_age; ?></p>
            <p><strong>Total Settings:</strong> <?php echo $validation_result['total_settings']; ?></p>
            <p><strong>Issues Found:</strong> <?php echo is_array($validation_result['issues']) ? count($validation_result['issues']) : 0; ?></p>
            <p><strong>Auto-Repairs:</strong> <?php echo $validation_result['repairs']; ?></p>
        </div>
        
        <div class="pmv-widget-actions">
            <a href="<?php echo admin_url('admin.php?page=png-metadata-viewer&tab=settings_backup'); ?>" class="button button-primary">
                Manage Settings
            </a>
            <?php if (!$backup || ($current_time - $last_backup > 3600)): ?>
                <button type="button" class="button button-secondary" onclick="pmvQuickBackup()">
                    Quick Backup
                </button>
            <?php endif; ?>
        </div>
        
        <div class="pmv-widget-footer">
            <small>Settings are automatically protected during updates</small>
        </div>
    </div>
    
    <style>
    .pmv-dashboard-widget {
        padding: 10px 0;
    }
    
    .pmv-status-indicator {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 15px;
    }
    
    .pmv-status-indicator.good {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .pmv-status-indicator.warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .pmv-status-indicator.critical {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .pmv-widget-content p {
        margin: 8px 0;
        font-size: 13px;
    }
    
    .pmv-widget-actions {
        margin: 15px 0;
    }
    
    .pmv-widget-actions .button {
        margin-right: 8px;
        margin-bottom: 5px;
    }
    
    .pmv-widget-footer {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 11px;
    }
    </style>
    
    <script>
    function pmvQuickBackup() {
        var button = event.target;
        var originalText = button.textContent;
        
        button.disabled = true;
        button.textContent = 'Backing up...';
        
        // Use fetch API for the AJAX request
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=pmv_backup_settings&nonce=<?php echo wp_create_nonce('pmv_settings_nonce'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.textContent = 'Backed up!';
                button.style.background = '#d4edda';
                button.style.color = '#155724';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                button.textContent = 'Failed';
                button.style.background = '#f8d7da';
                button.style.color = '#721c24';
            }
        })
        .catch(error => {
            button.textContent = 'Error';
            button.style.background = '#f8d7da';
            button.style.color = '#721c24';
        })
        .finally(() => {
            setTimeout(() => {
                button.disabled = false;
                button.textContent = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 2000);
        });
    }
    </script>
    <?php
}

// CRITICAL FIX: Enhanced table check with automatic repair
function pmv_ensure_conversation_table_exists() {
    // Only check if user is admin
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table;
    $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
    
    if (!$conversations_exists || !$messages_exists) {
        error_log("PMV: Missing tables - Conversations: " . ($conversations_exists ? 'EXISTS' : 'MISSING') . ", Messages: " . ($messages_exists ? 'EXISTS' : 'MISSING'));
        
        // Try to create missing tables
        if (function_exists('pmv_create_conversation_tables')) {
            pmv_create_conversation_tables();
            
            // Re-check
            $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table;
            $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
            
            if ($conversations_exists && $messages_exists) {
                error_log("PMV: ✅ Missing tables recreated successfully");
                
                // Only add admin notice if not during plugin activation
                if (!wp_doing_ajax() && !wp_doing_cron() && !defined('DOING_AUTOSAVE')) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible">';
                        echo '<p><strong>PNG Metadata Viewer:</strong> Missing conversation tables were recreated successfully.</p>';
                        echo '</div>';
                    });
                }
            } else {
                error_log("PMV: ❌ Failed to recreate missing tables");
                
                // Only add admin notice if not during plugin activation
                if (!wp_doing_ajax() && !wp_doing_cron() && !defined('DOING_AUTOSAVE')) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error">';
                        echo '<p><strong>PNG Metadata Viewer Error:</strong> Could not create conversation tables. ';
                        echo 'Please deactivate and reactivate the plugin, or check database permissions.</p>';
                        echo '</div>';
                    });
                }
            }
        }
    }
}
add_action('admin_init', 'pmv_ensure_conversation_table_exists');

/**
 * ENHANCED: Show comprehensive admin notice about system status
 */
function pmv_api_key_status_notice() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    // Only show on PMV admin pages or if debug is requested
    $screen = get_current_screen();
    $show_notice = false;
    
    if ($screen && strpos($screen->id, 'png-metadata') !== false) {
        $show_notice = true;
    }
    
    if (isset($_GET['pmv_system_status']) && $_GET['pmv_system_status'] === '1') {
        $show_notice = true;
    }
    
    if (!$show_notice) {
        return;
    }
    
    global $wpdb;
    
    // Gather system status
    $api_key = get_option('openai_api_key', '');
    $api_base_url = get_option('openai_api_base_url', '');
    $model = get_option('openai_model', '');
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table;
    $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
    
    $issues = array();
    $status = 'success';
    
    if (empty($api_key)) {
        $issues[] = 'OpenAI API key not configured';
        $status = 'warning';
    }
    
    if (!$conversations_exists) {
        $issues[] = 'Conversations table missing';
        $status = 'error';
    }
    
    if (!$messages_exists) {
        $issues[] = 'Messages table missing';
        $status = 'error';
    }
    
    // Check AJAX handlers
    $handlers = array(
        'pmv_save_conversation',
        'pmv_get_conversations', 
        'pmv_get_conversation',
        'pmv_delete_conversation'
    );
    
    $missing_handlers = array();
    foreach ($handlers as $handler) {
        if (!has_action("wp_ajax_$handler")) {
            $missing_handlers[] = $handler;
        }
    }
    
    if (!empty($missing_handlers)) {
        $issues[] = 'AJAX handlers not registered: ' . implode(', ', $missing_handlers);
        $status = 'error';
    }
    
    if (!empty($issues)) {
        ?>
        <div class="notice notice-<?= $status ?>">
            <p><strong>PNG Metadata Viewer System Status:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($issues as $issue): ?>
                <li><?= esc_html($issue) ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                <a href="<?= admin_url('options-general.php?page=png-metadata-viewer&tab=openai') ?>" class="button button-primary">
                    🔧 Fix Configuration
                </a>
                <?php if (!$conversations_exists || !$messages_exists): ?>
                <a href="<?= admin_url('plugins.php?action=deactivate&plugin=' . plugin_basename(__FILE__) . '&_wpnonce=' . wp_create_nonce('deactivate-plugin_' . plugin_basename(__FILE__))) ?>" class="button">
                    Deactivate Plugin
                </a>
                <a href="<?= admin_url('plugins.php?action=activate&plugin=' . plugin_basename(__FILE__) . '&_wpnonce=' . wp_create_nonce('activate-plugin_' . plugin_basename(__FILE__))) ?>" class="button">
                    Reactivate Plugin
                </a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    } else {
        // All good
        $conv_count = $conversations_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table") : 0;
        ?>
        <div class="notice notice-success">
            <p><strong>PNG Metadata Viewer:</strong> ✅ System operational 
            (API: <?= $api_key ? 'configured' : 'not configured' ?>, Database: <?= $conv_count ?> conversations, Handlers: active)
            <?php if ($api_key): ?>
            <a href="<?= admin_url('options-general.php?page=png-metadata-viewer&tab=api_test') ?>">Test API</a>
            <?php endif; ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'pmv_api_key_status_notice');

// Add settings link to the plugin page
function pmv_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=png-metadata-viewer">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pmv_add_settings_link');

// Run dependency check
if (pmv_check_dependencies()) {
    // Plugin is good to go
    add_action('admin_init', function() {
        // Any other initialization code here
    });
}

// Debug shortcode for system information
add_shortcode('pmv_debug_conversation', function() {
    if (!current_user_can('manage_options')) {
        return 'Debug info only available to administrators.';
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    $debug_info = array(
        '=== DATABASE ===' => '',
        'Conversations table exists' => $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") ? '✅ YES' : '❌ NO',
        'Messages table exists' => $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") ? '✅ YES' : '❌ NO',
        'Conversations table name' => $conversations_table,
        'Messages table name' => $messages_table,
        'DB Version' => get_option('pmv_conversation_db_version', 'NOT SET'),
        
        '=== USER ACCOUNT ===' => '',
        'User logged in' => is_user_logged_in() ? '✅ YES' : '❌ NO',
        'User ID' => $current_user->ID,
        'User name' => $current_user->display_name ?: 'No display name',
        'User login' => $current_user->user_login ?: 'No login',
        
        '=== AJAX HANDLERS ===' => '',
        'Save action registered' => has_action('wp_ajax_pmv_save_conversation') ? '✅ YES' : '❌ NO',
        'Get conversations registered' => has_action('wp_ajax_pmv_get_conversations') ? '✅ YES' : '❌ NO',
        'Get conversation registered' => has_action('wp_ajax_pmv_get_conversation') ? '✅ YES' : '❌ NO',
        'Delete conversation registered' => has_action('wp_ajax_pmv_delete_conversation') ? '✅ YES' : '❌ NO',
        
        '=== API SETTINGS ===' => '',
        'API Key set' => !empty(get_option('openai_api_key', '')) ? '✅ YES (' . strlen(get_option('openai_api_key', '')) . ' chars)' : '❌ NO',
        'API Model' => get_option('openai_model', 'NOT SET'),
        'API Base URL' => get_option('openai_api_base_url', 'NOT SET'),
        
        '=== SCRIPTS ENQUEUED ===' => '',
        'Main script' => wp_script_is('png-metadata-viewer-script', 'enqueued') ? '✅ YES' : '❌ NO',
        'Chat script' => wp_script_is('png-metadata-viewer-chat', 'enqueued') ? '✅ YES' : '❌ NO',
        'Conversation manager' => wp_script_is('pmv-conversation-manager', 'enqueued') ? '✅ YES' : '❌ NO',
    );
    
    // Count conversations if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$conversations_table'")) {
        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
        $user_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE user_id = %d",
            $current_user->ID
        ));
        
        $debug_info['Total conversations'] = $total_conversations;
        $debug_info['Your conversations'] = $user_conversations;
    }
    
    $output = '<div style="background: #f1f1f1; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 14px; line-height: 1.6;">';
    $output .= '<h3 style="color: #0073aa; margin-top: 0;">🔧 PMV System Diagnostic</h3>';
    
    foreach ($debug_info as $key => $value) {
        if (strpos($key, '===') !== false) {
            $output .= '<h4 style="color: #0073aa; margin-top: 20px; margin-bottom: 10px;">' . $key . '</h4>';
        } else {
            $output .= '<strong>' . $key . ':</strong> ' . $value . '<br>';
        }
    }
    
    $output .= '</div>';
    
    return $output;
});

// Add debug endpoint to check for unwanted output
add_action('wp_ajax_pmv_debug_output', 'pmv_debug_output');
add_action('wp_ajax_nopriv_pmv_debug_output', 'pmv_debug_output');

function pmv_debug_output() {
    // Check for any output buffers
    $output_buffers = array();
    while (ob_get_level()) {
        $output_buffers[] = ob_get_contents();
        ob_end_clean();
    }
    
    // Check for any error output
    $error_log = error_get_last();
    
    wp_send_json_success(array(
        'message' => 'Output debug completed',
        'output_buffers' => $output_buffers,
        'error_log' => $error_log,
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'is_logged_in' => is_user_logged_in()
    ));
}

?>
