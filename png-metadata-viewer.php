<?php
/**
* Plugin Name: PNG Metadata Viewer
* Description: Display/manage PNG cards with embedded metadata. Now includes a new Chat button (powered by OpenAI) that replaces the old chat link. All original settings (modal, download button, card styles, filters, secondary button, chat button, chat modal, chat input, and OpenAI settings) are configurable via the settings page. Chat history is saved per user.
* Version: 4.12
* Author: Your Name
* Text Domain: png-metadata-viewer
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PMV_PLUGIN_FILE', __FILE__);
define('PMV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMV_PLUGIN_URL', plugins_url('', __FILE__));
define('PMV_VERSION', '4.12');

// Include required files
require_once PMV_PLUGIN_DIR . 'includes/metadata-reader.php';
require_once PMV_PLUGIN_DIR . 'includes/admin-settings.php';
require_once PMV_PLUGIN_DIR . 'includes/enqueue.php';
require_once PMV_PLUGIN_DIR . 'includes/word-tracker.php'; // Add word tracking system
require_once PMV_PLUGIN_DIR . 'includes/shortcodes.php';
require_once PMV_PLUGIN_DIR . 'includes/masonry-helper.php';

// Include the AJAX handler for chat functionality
require_once PMV_PLUGIN_DIR . 'includes/ajax-handlers.php';

// Include and initialize the conversation manager
require_once PMV_PLUGIN_DIR . 'includes/conversation-manager.php';
PMV_Conversation_Manager::init();

// Load integration file if it exists
if (file_exists(PMV_PLUGIN_DIR . 'integration.php')) {
    require_once PMV_PLUGIN_DIR . 'integration.php';
}

// Function to enqueue script files
function pmv_enqueue_scripts() {
    // Register and enqueue main script
    wp_register_script(
        'png-metadata-viewer-script',
        PMV_PLUGIN_URL . '/script.js',
        array('jquery', 'masonry-js', 'imagesloaded-js'),
        PMV_VERSION,
        true
    );
    
    // Register and enqueue chat script
    wp_register_script(
        'png-metadata-viewer-chat',
        PMV_PLUGIN_URL . '/chat.js',
        array('jquery'),
        PMV_VERSION,
        true
    );
    
    // Register and enqueue mobile chat script
    wp_register_script(
        'png-metadata-viewer-mobile-chat',
        PMV_PLUGIN_URL . '/mobile-chat.js',
        array('jquery', 'png-metadata-viewer-chat'),
        PMV_VERSION,
        true
    );
    
    // Register and enqueue conversation manager script
    wp_register_script(
        'pmv-conversation-manager',
        PMV_PLUGIN_URL . '/js/conversation-manager.js',
        array('jquery', 'png-metadata-viewer-chat'),
        PMV_VERSION,
        true
    );
    
    // Register and enqueue chat integration script
    wp_register_script(
        'pmv-chat-integration',
        PMV_PLUGIN_URL . '/js/chat-integration.js',
        array('jquery', 'pmv-conversation-manager', 'png-metadata-viewer-chat', 'png-metadata-viewer-mobile-chat'),
        PMV_VERSION,
        true
    );
    
    // Register conversation styles
    wp_register_style(
        'pmv-conversation-styles',
        PMV_PLUGIN_URL . '/css/conversation-styles.css',
        array(),
        PMV_VERSION
    );
    
    // Register enhanced styles
    wp_register_style(
        'pmv-enhanced-styles',
        PMV_PLUGIN_URL . '/enhanced-styles.css',
        array(),
        PMV_VERSION
    );
    
    // Enqueue scripts and styles
    wp_enqueue_script('png-metadata-viewer-script');
    wp_enqueue_script('png-metadata-viewer-chat');
    wp_enqueue_script('png-metadata-viewer-mobile-chat');
    wp_enqueue_script('pmv-conversation-manager');
    wp_enqueue_script('pmv-chat-integration');
    wp_enqueue_style('pmv-conversation-styles');
    wp_enqueue_style('pmv-enhanced-styles');
    
    // Enqueue dashicons for the UI
    wp_enqueue_style('dashicons');
    
    // Pass data to scripts
    wp_localize_script(
        'png-metadata-viewer-chat',
        'pmv_ajax_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => PMV_PLUGIN_URL,
            'user_id' => get_current_user_id(),
            'nonce' => wp_create_nonce('pmv_ajax_nonce'),
            'chat_button_text' => get_option('png_metadata_chat_button_text', 'Chat'),
            'conversation_enabled' => true
        )
    );
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_scripts');

// Function to enqueue chat scripts and pass necessary data
function pmv_enqueue_chat_scripts() {
    // Add chat-specific styles
    $chat_css = "
        /* Chat styling */
        .chat-message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            max-width: 80%;
        }
        
        .chat-message.user {
            background-color: #e2f1ff;
            color: #333;
            margin-left: auto;
            border-bottom-right-radius: 2px;
        }
        
        .chat-message.bot {
            background-color: #f0f0f0;
            color: #000;
            margin-right: auto;
            border-bottom-left-radius: 2px;
        }
        
        .chat-message.error {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .typing-indicator {
            padding: 8px;
            background-color: #f0f0f0;
            border-radius: 8px;
            margin-bottom: 10px;
            display: inline-block;
            margin-right: auto;
            color: #666;
        }
        
        /* Modal fixes */
        .png-modal-content {
            max-height: 90vh !important;
            overflow-y: auto !important;
        }
        
        #chat-history {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        
        #chat-controls {
            display: flex;
        }
        
        #chat-input {
            flex: 1;
            padding: 8px;
            min-height: 50px;
        }
        
        #send-chat {
            margin-left: 10px;
            padding: 0 20px;
            background: #0073aa;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        /* Character modal styling */
        .character-details {
            padding: 20px;
        }
        
        .character-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .character-image {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .character-image img {
            max-height: 300px;
            max-width: 100%;
        }
        
        .character-tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 15px;
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
        }
        
        .tab-btn.active {
            border-bottom: 2px solid #0073aa;
            color: #0073aa;
        }
        
        .tab-content {
            display: none;
            margin-bottom: 20px;
        }
        
        #tab-basic {
            display: block;
        }
        
        .character-footer {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .lorebook-entry {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-left: 3px solid #0073aa;
        }
        
        .alternate-greeting {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f5f5f5;
        }
    ";
    
    wp_add_inline_style('png-metadata-viewer-style', $chat_css);
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_chat_scripts');

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
        return false;
    }
    
    return true;
}

// Enhanced activation function that ensures table creation
function pmv_activate_plugin() {
    global $wpdb;
    
    // Create upload directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    
    if (!file_exists($png_cards_dir)) {
        wp_mkdir_p($png_cards_dir);
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
    if (!get_option('png_metadata_cards_per_page')) {
        update_option('png_metadata_cards_per_page', 12);
    }
    
    // Add default filter titles if they don't exist
    if (!get_option('png_metadata_filter1_title')) {
        update_option('png_metadata_filter1_title', 'Category');
    }
    
    if (!get_option('png_metadata_filter2_title')) {
        update_option('png_metadata_filter2_title', 'Style');
    }
    
    if (!get_option('png_metadata_filter3_title')) {
        update_option('png_metadata_filter3_title', 'Creator');
    }
    
    if (!get_option('png_metadata_filter4_title')) {
        update_option('png_metadata_filter4_title', 'Version');
    }
    
    // Initialize token usage options
    if (!get_option('pmv_guest_daily_token_limit')) {
        update_option('pmv_guest_daily_token_limit', 5000); // Default: 5000 tokens per day for guests
    }
    
    if (!get_option('pmv_default_user_monthly_limit')) {
        update_option('pmv_default_user_monthly_limit', 100000); // Default: 100K tokens per month for users
    }
    
    // Add OpenAI specific default settings if they don't exist
    if (!get_option('openai_api_base_url')) {
        update_option('openai_api_base_url', 'https://api.openai.com/v1/');
    }
    
    if (!get_option('openai_model')) {
        update_option('openai_model', 'gpt-3.5-turbo');
    }
    
    if (!get_option('openai_temperature')) {
        update_option('openai_temperature', 0.7);
    }
    
    // Set default styling for chat button if not set
    if (!get_option('png_metadata_chat_button_text')) {
        update_option('png_metadata_chat_button_text', 'Chat');
    }
    
    if (!get_option('png_metadata_chat_button_text_color')) {
        update_option('png_metadata_chat_button_text_color', '#ffffff');
    }
    
    if (!get_option('png_metadata_chat_button_bg_color')) {
        update_option('png_metadata_chat_button_bg_color', '#28a745');
    }
    
    // Set default conversation manager settings
    if (!get_option('pmv_allow_guest_conversations')) {
        update_option('pmv_allow_guest_conversations', false);
    }
    
    if (!get_option('pmv_auto_save_conversations')) {
        update_option('pmv_auto_save_conversations', true);
    }
    
    if (!get_option('pmv_max_conversations_per_user')) {
        update_option('pmv_max_conversations_per_user', 50);
    }
    
    // CRITICAL: Create conversation table directly here
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'pmv_conversations';
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        character_id varchar(255) NOT NULL,
        title text,
        messages longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY character_id (character_id(191))
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    // Log the creation attempt
    error_log("PMV Plugin Activation: Table creation attempted for $table_name");
    error_log("PMV Plugin Activation: dbDelta result: " . print_r($result, true));
    
    // Verify table was created
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if ($table_exists) {
        error_log("PMV Plugin Activation: Table $table_name created successfully");
    } else {
        error_log("PMV Plugin Activation: Failed to create table $table_name");
        // Try again with direct query
        $wpdb->query($sql);
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            error_log("PMV Plugin Activation: Table $table_name created on second attempt");
        } else {
            error_log("PMV Plugin Activation: CRITICAL - Unable to create table $table_name");
        }
    }
}
register_activation_hook(__FILE__, 'pmv_activate_plugin');

// Add OpenAI model selection to admin settings
function pmv_add_openai_model_field() {
    register_setting('png_metadata_viewer_settings', 'openai_model');
    
    add_settings_field(
        'openai_model',
        'OpenAI Model',
        'pmv_render_openai_model_field',
        'options-general.php?page=png-metadata-viewer&tab=openai',
        'openai_section'
    );
}
add_action('admin_init', 'pmv_add_openai_model_field');

// Add conversation manager settings to admin settings
function pmv_add_conversation_settings() {
    // Register settings
    register_setting('png_metadata_viewer_settings', 'pmv_allow_guest_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_auto_save_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_max_conversations_per_user');
    
    // Add new tab
    add_filter('pmv_settings_tabs', function($tabs) {
        $tabs['conversations'] = 'Conversations';
        return $tabs;
    });
    
    // Add settings section
    add_settings_section(
        'conversation_section',
        'Conversation Manager Settings',
        function() {
            echo '<p>Configure settings for the conversation manager feature.</p>';
        },
        'options-general.php?page=png-metadata-viewer&tab=conversations'
    );
    
    // Add settings fields
    add_settings_field(
        'pmv_allow_guest_conversations',
        'Allow Guest Conversations',
        function() {
            $value = get_option('pmv_allow_guest_conversations', false);
            echo '<input type="checkbox" name="pmv_allow_guest_conversations" value="1" ' . checked(1, $value, false) . '>';
            echo '<p class="description">Allow non-logged-in users to save conversations (stored in browser cookies).</p>';
        },
        'options-general.php?page=png-metadata-viewer&tab=conversations',
        'conversation_section'
    );
    
    add_settings_field(
        'pmv_auto_save_conversations',
        'Auto-Save Conversations',
        function() {
            $value = get_option('pmv_auto_save_conversations', true);
            echo '<input type="checkbox" name="pmv_auto_save_conversations" value="1" ' . checked(1, $value, false) . '>';
            echo '<p class="description">Automatically save conversations after each message.</p>';
        },
        'options-general.php?page=png-metadata-viewer&tab=conversations',
        'conversation_section'
    );
    
    add_settings_field(
        'pmv_max_conversations_per_user',
        'Max Conversations Per User',
        function() {
            $value = get_option('pmv_max_conversations_per_user', 50);
            echo '<input type="number" name="pmv_max_conversations_per_user" value="' . esc_attr($value) . '" min="1" max="1000">';
            echo '<p class="description">Maximum number of conversations each user can save. When this limit is reached, the oldest conversations will be deleted.</p>';
        },
        'options-general.php?page=png-metadata-viewer&tab=conversations',
        'conversation_section'
    );
}
add_action('admin_init', 'pmv_add_conversation_settings');

// Check for table existence on admin init and create if missing
function pmv_ensure_conversation_table_exists() {
    global $wpdb;
    
    // Only check if user is admin
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    $table_name = $wpdb->prefix . 'pmv_conversations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        error_log("PMV: Conversation table missing, attempting to create...");
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            character_id varchar(255) NOT NULL,
            title text,
            messages longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY character_id (character_id(191))
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Verify creation
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            error_log("PMV: Conversation table created successfully");
        } else {
            error_log("PMV: Failed to create conversation table");
        }
    }
}
add_action('admin_init', 'pmv_ensure_conversation_table_exists');

// Add dynamic CSS output for gallery styling
function pmv_output_dynamic_styles() {
    // Only include on pages that have our shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'png_metadata_viewer')) {
        return;
    }
    
    echo '<style>
        /* Set custom background for filters container */
        .png-filters-container {
            background: ' . esc_attr(get_option('png_metadata_filters_bg_color', '#f5f5f5')) . ';
            padding: ' . esc_attr(get_option('png_metadata_filters_padding', '15px')) . ';
            border-radius: ' . intval(get_option('png_metadata_filters_border_radius', 8)) . 'px;
            border: ' . intval(get_option('png_metadata_filters_border_width', 0)) . 'px solid ' . esc_attr(get_option('png_metadata_filters_border_color', '#dee2e6')) . ';
            box-shadow: ' . esc_attr(get_option('png_metadata_filters_box_shadow', '0 1px 3px rgba(0,0,0,0.1)')) . ';
        }
        
        /* Set gallery background */
        .png-cards-loading-wrapper {
            background-color: ' . esc_attr(get_option('png_metadata_gallery_bg_color', '#ffffff')) . ';
            padding: ' . esc_attr(get_option('png_metadata_gallery_padding', '0px')) . ';
            border-radius: ' . intval(get_option('png_metadata_gallery_border_radius', 0)) . 'px;
            border: ' . intval(get_option('png_metadata_gallery_border_width', 0)) . 'px solid ' . esc_attr(get_option('png_metadata_gallery_border_color', '#dee2e6')) . ';
            box-shadow: ' . esc_attr(get_option('png_metadata_gallery_box_shadow', 'none')) . ';
        }
    </style>';
}
add_action('wp_head', 'pmv_output_dynamic_styles');

// Performance optimization for masonry initialization
function pmv_add_js_optimization() {
    // Only include on pages that have our shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'png_metadata_viewer')) {
        return;
    }
    
    ?>
    <script>
    // Pre-init code to prepare elements before masonry loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set fixed height on image containers to prevent layout shifts
        var imageContainers = document.querySelectorAll('.png-image-container');
        for (var i = 0; i < imageContainers.length; i++) {
            imageContainers[i].style.height = '180px';
        }
        
        // Hide cards initially to prevent FOUC (Flash of Unstyled Content)
        var cards = document.querySelectorAll('.png-card');
        for (var i = 0; i < cards.length; i++) {
            cards[i].style.opacity = '0';
        }
    });
    </script>
    <?php
}
add_action('wp_head', 'pmv_add_js_optimization', 100);

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

// Add hooks for token usage tracking with OpenAI API
function pmv_add_token_usage_hooks() {
    // Hook to run after chat completion to track token usage
    add_action('pmv_after_chat_completion', function($response, $user_id, $message) {
        if (class_exists('PMV_Word_Usage_Tracker')) {
            PMV_Word_Usage_Tracker::track_chat_completion($response, $user_id, $message);
        }
    }, 10, 3);
    
    // Hook to check limits before processing
    add_filter('pmv_pre_chat_completion', function($proceed, $user_id, $message) {
        if (class_exists('PMV_Word_Usage_Tracker')) {
            return PMV_Word_Usage_Tracker::check_word_limits($proceed, $user_id, $message);
        }
        return $proceed;
    }, 10, 3);
}
add_action('init', 'pmv_add_token_usage_hooks');
