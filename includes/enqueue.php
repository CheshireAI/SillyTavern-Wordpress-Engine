<?php
// includes/enqueue.php - Updated for DeepSeek API
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function pmv_enqueue_frontend_assets() {
    // Get correct paths - very important
    $plugin_url = plugins_url('', dirname(__FILE__)); // Get URL to plugin root
    
    // Remove the problematic style.css enqueuing since it doesn't exist
    // wp_enqueue_style(
    //     'png-metadata-viewer-style', 
    //     $plugin_url . '/style.css', 
    //     array(), 
    //     '4.11'
    // );
    
    // Enqueue Select2 for enhanced filtering
    wp_enqueue_style(
        'select2', 
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        array(),
        null
    );
    
    wp_enqueue_script(
        'select2', 
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 
        array('jquery'), 
        null, 
        true
    );
    
    // Load Masonry via WordPress core (jquery-masonry already includes the library)
    wp_enqueue_script('jquery-masonry');
    
    // Enqueue pagination handler
    wp_enqueue_script(
        'pmv-pagination-handler',
        $plugin_url . '/js/pagination-handler.js',
        array('jquery'),
        '4.11',
        true
    );
    
    // Enqueue filter enhancements
    wp_enqueue_script(
        'pmv-filter-enhancements',
        $plugin_url . '/js/filter-enhancements.js',
        array('jquery', 'select2'),
        '4.11',
        true
    );
    
    // Enqueue conversation manager script (front-end only function)
    wp_enqueue_script(
        'pmv-conversation-manager',
        $plugin_url . '/js/conversation-manager.js',
        array('jquery'),
        '4.11',
        true
    );
    
    // Enqueue enhanced styles - this contains all the dark theme styling
    wp_enqueue_style(
        'pmv-enhanced-styles',
        $plugin_url . '/enhanced-styles.css',
        array(),
        '4.11'
    );

    // Enqueue content moderation styles
    wp_enqueue_style(
        'pmv-content-moderation-styles',
        $plugin_url . '/css/content-moderation-styles.css',
        array(),
        '4.11'
    );

    // Enqueue core chat module script
    // wp_enqueue_script(
    //     'png-metadata-viewer-chat', 
    //     $plugin_url . '/chat.js', 
    //     array('jquery'), 
    //     '4.11', 
    //     true
    // );
    
    // Localize script with AJAX URL and deepseek info
    $user_id = get_current_user_id();
    $word_tracking_enabled = class_exists('PMV_Word_Usage_Tracker');
    
    // Get DeepSeek specific settings
    $api_base_url = get_option('openai_api_base_url', 'https://api.deepseek.com');
    $model = get_option('openai_model', 'deepseek-chat');
    
    // wp_localize_script(
    //     'png-metadata-viewer-chat', // Localize to chat script for early access
    //     'pmv_ajax_object', 
    //     array(
    //         'ajax_url' => admin_url('admin-ajax.php'),
    //         'plugin_url' => $plugin_url,
    //         'nonce' => wp_create_nonce('pmv_ajax_nonce'),
    //         'user_id' => $user_id,
    //         'word_tracking_enabled' => $word_tracking_enabled,
    //         'chat_button_text' => get_option('png_metadata_chat_button_text', 'Chat'),
    //         'api_model' => $model,
    //         'api_base_url' => $api_base_url,
    //         'debug_info' => 'API: DeepSeek - ' . (empty(get_option('openai_api_key')) ? 'Not Configured' : 'Configured'),
    //         'version' => '4.11'
    //     )
    // );

    // Front-end function ends here; admin assets are registered separately.
}

/**
 * Enqueue admin-only scripts and styles.
 */
function pmv_enqueue_admin_assets() {
    $plugin_url = plugins_url('', dirname(__FILE__));

    // Media uploader & admin helpers
    wp_enqueue_media();
    wp_enqueue_script(
        'pmv-admin-js',
        $plugin_url . '/admin.js',
        array('jquery'),
        '4.11',
        true
    );

    // Re-use Select2 & styles in admin when needed (settings pages, etc.)
    wp_enqueue_style(
        'select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        array(),
        null
    );
    wp_enqueue_script(
        'select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        array('jquery'),
        null,
        true
    );
    
    // Enqueue content moderation styles for admin
    wp_enqueue_style(
        'pmv-content-moderation-styles',
        $plugin_url . '/css/content-moderation-styles.css',
        array(),
        '4.11'
    );
}

// Register hooks
add_action('wp_enqueue_scripts', 'pmv_enqueue_frontend_assets');
add_action('admin_enqueue_scripts', 'pmv_enqueue_admin_assets');

/**
 * Add dynamic CSS based on plugin settings
 */
function png_metadata_viewer_dynamic_styles() {
    // Your existing dynamic styles code...
    // (code retained as in your original file)
    
    // Get all relevant options with defaults
    $modal_bg_color = esc_attr(get_option('png_metadata_modal_bg_color', '#1a1a1a'));
    $modal_text_color = esc_attr(get_option('png_metadata_modal_text_color', '#e0e0e0'));
    $box_bg_color = esc_attr(get_option('png_metadata_box_bg_color', '#f5f5f5'));
    
    $card_name_text_color = esc_attr(get_option('png_metadata_card_name_text_color', '#212529'));
    $card_name_bg_color = esc_attr(get_option('png_metadata_card_name_bg_color', '#e9ecef'));
    $card_bg_color = esc_attr(get_option('png_metadata_card_bg_color', '#ffffff'));
    $card_tags_text_color = esc_attr(get_option('png_metadata_card_tags_text_color', '#6c757d'));
    $card_tags_bg_color = esc_attr(get_option('png_metadata_card_tags_bg_color', '#f8f9fa'));
    
    $buttonTextColor = esc_attr(get_option('png_metadata_button_text_color', '#ffffff'));
    // Use CSS variables for unified dark-teal palette
    $buttonBgColor = 'var(--button-bg)';
    $buttonHoverTextColor = 'var(--text-main)';
    $buttonHoverBgColor = 'var(--button-hover)';
    
    $button_border_width = intval(get_option('png_metadata_button_border_width', 1));
    $button_border_color = esc_attr(get_option('png_metadata_button_border_color', '#0073aa'));
    $button_border_radius = intval(get_option('png_metadata_button_border_radius', 4));
    $button_margin = esc_attr(get_option('png_metadata_button_margin', '5px'));
    $button_padding = esc_attr(get_option('png_metadata_button_padding', '8px 15px'));
    
    // Chat button specific styling
    $chatButtonText = esc_attr(get_option('png_metadata_chat_button_text', 'Chat'));
    $chatButtonTextColor = 'var(--text-main)';
    $chatButtonBgColor = 'var(--button-bg)';
    $chatButtonHoverTextColor = 'var(--text-main)';
    $chatButtonHoverBgColor = 'var(--button-hover)';
    $chatButtonBorderWidth = 1;
    $chatButtonBorderColor = 'var(--border-main)';
    $chatButtonBorderRadius = intval(get_option('png_metadata_chat_button_border_radius', $button_border_radius));
    
    $pagination_button_text_color = esc_attr(get_option('png_metadata_pagination_button_text_color', '#e0e0e0'));
    $pagination_button_bg_color = esc_attr(get_option('png_metadata_pagination_button_bg_color', '#23404e'));
    $pagination_button_active_text_color = esc_attr(get_option('png_metadata_pagination_button_active_text_color', '#ffffff'));
    $pagination_button_active_bg_color = esc_attr(get_option('png_metadata_pagination_button_active_bg_color', '#2d5363'));
    
    $chat_input_text_color = esc_attr(get_option('png_metadata_chat_input_text_color', '#000000'));
    $chat_input_bg_color = esc_attr(get_option('png_metadata_chat_input_bg_color', '#ffffff'));
    $chatUserBgColor = esc_attr(get_option('png_metadata_chat_user_bg_color', '#e2f1ff'));
    $chatUserTextColor = esc_attr(get_option('png_metadata_chat_user_text_color', '#333333'));
    $chatBotBgColor = esc_attr(get_option('png_metadata_chat_bot_bg_color', '#f0f0f0'));
    $chatBotTextColor = esc_attr(get_option('png_metadata_chat_bot_text_color', '#000000'));
    
    $chat_history_font_size = intval(get_option('png_metadata_chat_history_font_size', 14));
    $card_border_width = intval(get_option('png_metadata_card_border_width', 1));
    $card_border_color = esc_attr(get_option('png_metadata_card_border_color', '#cccccc'));
    $card_border_radius = intval(get_option('png_metadata_card_border_radius', 4));
    
    // Gallery container styling options
    $gallery_bg_color = esc_attr(get_option('png_metadata_gallery_bg_color', '#ffffff'));
    $gallery_padding = esc_attr(get_option('png_metadata_gallery_padding', '0px'));
    $gallery_border_radius = intval(get_option('png_metadata_gallery_border_radius', 0));
    $gallery_border_width = intval(get_option('png_metadata_gallery_border_width', 0));
    $gallery_border_color = esc_attr(get_option('png_metadata_gallery_border_color', '#dee2e6'));
    $gallery_box_shadow = esc_attr(get_option('png_metadata_gallery_box_shadow', 'none'));
    
    // Filters container styling options
    $filters_bg_color = esc_attr(get_option('png_metadata_filters_bg_color', '#f5f5f5'));
    $filters_padding = esc_attr(get_option('png_metadata_filters_padding', '15px'));
    $filters_border_radius = intval(get_option('png_metadata_filters_border_radius', 8));
    $filters_border_width = intval(get_option('png_metadata_filters_border_width', 0));
    $filters_border_color = esc_attr(get_option('png_metadata_filters_border_color', '#dee2e6'));
    $filters_box_shadow = esc_attr(get_option('png_metadata_filters_box_shadow', '0 1px 3px rgba(0,0,0,0.1)'));
    
    // Only output CSS if not during plugin activation
    if (!wp_doing_ajax() && !wp_doing_cron() && !defined('DOING_AUTOSAVE') && !defined('WP_INSTALLING')) {
        // Output the dynamic CSS
        echo "<style>
            /* Modal styles */
            .png-modal-content {
                background-color: {$modal_bg_color};
                color: {$modal_text_color};
            }
            
            /* Card styles */
            .png-card {
                border-width: {$card_border_width}px;
                border-color: {$card_border_color};
                border-radius: {$card_border_radius}px;
                background-color: {$card_bg_color};
            }
            
            .png-card-name {
                color: {$card_name_text_color};
                background-color: {$card_name_bg_color};
            }
            
            .png-card-tags {
                color: {$card_tags_text_color};
                background-color: {$card_tags_bg_color};
            }
            
            /* Box and filter styles */
            .metadata-box, .png-filters-container {
                background-color: {$box_bg_color};
            }
            
            /* Standard button styles */
            .png-download-button,
            .png-secondary-button,
            #new-chat.chat-send-button,
            #load-sessions.chat-send-button,
            #chat-send.chat-send-button {
                background-color: {$buttonBgColor};
                color: {$buttonTextColor};
                border: {$button_border_width}px solid {$button_border_color};
                border-radius: {$button_border_radius}px;
                margin: {$button_margin};
                padding: {$button_padding};
                text-decoration: none;
                display: inline-block;
                text-align: center;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .png-download-button:hover,
            .png-secondary-button:hover,
            #new-chat.chat-send-button:hover,
            #load-sessions.chat-send-button:hover,
            #chat-send.chat-send-button:hover {
                background-color: {$buttonHoverBgColor};
                color: {$buttonHoverTextColor};
            }
            
            /* Chat button specific styles */
            .png-chat-button {
                background-color: {$chatButtonBgColor} !important;
                color: {$chatButtonTextColor} !important;
                border: {$chatButtonBorderWidth}px solid {$chatButtonBorderColor} !important;
                border-radius: {$chatButtonBorderRadius}px !important;
                margin: {$button_margin};
                padding: {$button_padding};
                text-decoration: none;
                display: inline-block;
                text-align: center;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .png-chat-button:hover {
                background-color: {$chatButtonHoverBgColor} !important;
                color: {$chatButtonHoverTextColor} !important;
            }
            
            /* Pagination styles */
            .pmv-page-link {
                color: {$pagination_button_text_color};
                background-color: {$pagination_button_bg_color};
            }
            
            .pmv-page-current {
                background-color: {$pagination_button_active_bg_color};
                color: {$pagination_button_active_text_color};
            }
            
            /* Chat styles */
            #chat-input {
                background-color: {$chat_input_bg_color};
            color: {$chat_input_text_color};
        }
        
        .chat-message.user {
            background-color: {$chatUserBgColor};
            color: {$chatUserTextColor};
        }
        
        .chat-message.bot {
            background-color: {$chatBotBgColor};
            color: {$chatBotTextColor};
        }
        
        #chat-history {
            font-size: {$chat_history_font_size}px;
        }
        
        /* Gallery container styles */
        .png-cards-loading-wrapper,
        .png-cards {
            background-color: {$gallery_bg_color};
            padding: {$gallery_padding};
            border-radius: {$gallery_border_radius}px;
            border: {$gallery_border_width}px solid {$gallery_border_color};
            box-shadow: {$gallery_box_shadow};
        }
        
        /* Filters container styles */
        .png-filters-container {
            background-color: {$filters_bg_color};
            padding: {$filters_padding};
            border-radius: {$filters_border_radius}px;
            border: {$filters_border_width}px solid {$filters_border_color};
            box-shadow: {$filters_box_shadow};
        }
        
        /* Enhanced Select2 styling */
        .select2-container--default .select2-selection--multiple {
            background-color: #333;
            border: 1px solid #555;
            border-radius: 4px;
            padding: 4px;
            min-height: 38px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: {$buttonBgColor};
            color: {$buttonTextColor};
            border: none;
            border-radius: 3px;
            margin: 3px;
            padding: 4px 8px;
            font-size: 13px;
            font-weight: normal;
            display: inline-flex;
            align-items: center;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255, 255, 255, 0.8);
            margin-right: 6px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            background: transparent;
            padding: 0 2px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: white;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }
        
        .select2-dropdown {
            background-color: #333;
            border: 1px solid #555;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .select2-container--default .select2-results__option {
            color: #ccc;
            padding: 6px 10px;
        }
        
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: {$buttonBgColor};
            color: white;
        }
        
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .select2-search--dropdown .select2-search__field {
            background: #444;
            color: white;
            border: 1px solid #555;
            border-radius: 3px;
            padding: 6px;
        }
        
        .select2-container--default .select2-search--inline .select2-search__field {
            background: transparent;
            color: #fff;
        }
        
        /* Typing indicator animation */
        .typing-dots {
            display: inline-block;
        }
        
        .typing-dots span {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            margin-right: 3px;
            background: #333;
            animation: typing-animation 1.4s infinite both;
        }
        
        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing-animation {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* Word usage display */
        #word-usage-info {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            padding: 8px;
            border-top: 1px solid #eee;
        }
        
        .near-limit {
            color: #d9534f;
            font-weight: bold;
        }
        
        /* Simple chat modal styling */
        .png-metadata-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            overflow: auto;
        }
        
        .png-modal-content.chat-mode {
            max-width: 800px;
            height: 80vh;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        
        #chat-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
        }
        
        #chat-history {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #f8f9fa;
        }
        
        #chat-input-row {
            display: flex;
            padding: 10px 15px;
            background-color: #fff;
            border-top: 1px solid #ddd;
        }
        
        .typing-indicator {
            background-color: #f0f0f0;
            padding: 10px 15px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 15px;
            color: #888;
        }
    </style>";
        } // Close the if statement
    } // Close the function

// Add dynamic styles to front end only
add_action('wp_head', 'png_metadata_viewer_dynamic_styles');
