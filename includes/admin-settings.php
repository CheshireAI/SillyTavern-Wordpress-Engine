<?php
if (!defined('ABSPATH')) exit;

// Include UI components
require_once plugin_dir_path(__FILE__) . 'admin-settings-ui.php';

/**
 * Register plugin settings - Complete version with keyword flagging
 */
function pmv_register_plugin_settings() {
    // OpenAI Settings
    register_setting('png_metadata_viewer_settings', 'openai_api_key');
    register_setting('png_metadata_viewer_settings', 'openai_api_base_url');
    register_setting('png_metadata_viewer_settings', 'openai_model');
    register_setting('png_metadata_viewer_settings', 'openai_temperature');
    register_setting('png_metadata_viewer_settings', 'openai_max_tokens', array(
        'type' => 'integer',
        'default' => 1000,
        'sanitize_callback' => 'pmv_sanitize_max_tokens'
    ));
    register_setting('png_metadata_viewer_settings', 'openai_presence_penalty');
    register_setting('png_metadata_viewer_settings', 'openai_frequency_penalty');
    
    // Layout Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_cards_per_page');
    register_setting('png_metadata_viewer_settings', 'png_metadata_modal_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_modal_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_box_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_name_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_name_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_tags_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_tags_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_active_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_pagination_button_active_bg_color');
    
    // Filter Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter1_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter1_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter2_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter2_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter3_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter3_list');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter4_title');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filter4_list');
    
    // Button Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_hover_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_text');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_link');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_secondary_button_hover_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_text');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_hover_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_button_hover_bg_color');
    
    // Chat Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_name_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_modal_name_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_input_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_input_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_user_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_user_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_bot_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_bot_text_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_chat_history_font_size');
    
    // Gallery Settings
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_card_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_margin');
    register_setting('png_metadata_viewer_settings', 'png_metadata_button_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_gallery_box_shadow');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_bg_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_padding');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_radius');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_width');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_border_color');
    register_setting('png_metadata_viewer_settings', 'png_metadata_filters_box_shadow');
    
    // Conversation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_allow_guest_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_auto_save_conversations');
    register_setting('png_metadata_viewer_settings', 'pmv_max_conversations_per_user');
    register_setting('png_metadata_viewer_settings', 'pmv_guest_daily_token_limit');
    register_setting('png_metadata_viewer_settings', 'pmv_default_user_monthly_limit');
    
    // Keyword Flagging Settings
    register_setting('png_metadata_viewer_settings', 'pmv_unsafe_keywords', array(
        'sanitize_callback' => 'pmv_sanitize_unsafe_keywords'
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_enable_keyword_flagging');
    register_setting('png_metadata_viewer_settings', 'pmv_keyword_case_sensitive');
    
    // Image Generation Provider Settings
    register_setting('png_metadata_viewer_settings', 'pmv_image_provider');
    
    // SwarmUI API Settings
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_api_url');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_api_key');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_token');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_enabled');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_model');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_global_daily_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_global_monthly_limit', array(
        'type' => 'integer',
        'default' => 1000
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_daily_limit', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_user_monthly_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_guest_daily_limit', array(
        'type' => 'integer',
        'default' => 5
    ));
    
    // Nano-GPT API Settings
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_api_url');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_api_key');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_enabled');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_model');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_global_daily_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_global_monthly_limit', array(
        'type' => 'integer',
        'default' => 1000
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_user_daily_limit', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_user_monthly_limit', array(
        'type' => 'integer',
        'default' => 100
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_guest_daily_limit', array(
        'type' => 'integer',
        'default' => 5
    ));
    
    // Image Generation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_auto_trigger_keywords');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_allow_prompt_editing');
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_steps', array(
        'type' => 'integer',
        'default' => 20
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_cfg_scale', array(
        'type' => 'number',
        'default' => 7.0
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_width', array(
        'type' => 'integer',
        'default' => 512
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_swarmui_default_height', array(
        'type' => 'integer',
        'default' => 512
    ));
    
    // Nano-GPT Image Generation Settings
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_auto_trigger_keywords');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_allow_prompt_editing');
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_steps', array(
        'type' => 'integer',
        'default' => 10
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_scale', array(
        'type' => 'number',
        'default' => 7.5
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_width', array(
        'type' => 'integer',
        'default' => 1024
    ));
    register_setting('png_metadata_viewer_settings', 'pmv_nanogpt_default_height', array(
        'type' => 'integer',
        'default' => 1024
    ));
}
add_action('admin_init', 'pmv_register_plugin_settings');

/**
 * Sanitize unsafe keywords
 */
function pmv_sanitize_unsafe_keywords($value) {
    if (empty($value)) return '';
    
    // Split by comma, trim whitespace, remove empty entries
    $keywords = array_filter(array_map('trim', explode(',', $value)));
    
    // Remove duplicates and sort
    $keywords = array_unique($keywords);
    sort($keywords);
    
    return implode(', ', $keywords);
}

/**
 * Sanitize max_tokens value to ensure it's within valid range
 */
function pmv_sanitize_max_tokens($value) {
    $value = intval($value);
    // Ensure value is within valid range for most AI APIs
    if ($value < 1) {
        $value = 1;
    }
    if ($value > 8192) {
        $value = 8192;  // Max supported by most APIs
    }
    return $value;
}

/**
 * Admin menu registration
 */
function pmv_add_admin_menu() {
    add_options_page(
        'PNG Metadata Settings',
        'PNG Metadata',
        'manage_options',
        'png-metadata-viewer',
        'pmv_admin_page_wrapper'
    );
}
add_action('admin_menu', 'pmv_add_admin_menu');

/**
 * Admin page wrapper with all tabs
 */
function pmv_admin_page_wrapper() {
    if (!current_user_can('manage_options')) return;
    
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'layout';
    $tabs = [
        'layout' => 'Layout & Filters',
        'buttons' => 'Buttons & Styling',
        'chat' => 'Chat & Modal',
        'gallery' => 'Gallery Styling',
        'openai' => 'OpenAI Settings',
        'conversations' => 'Conversations',
        'safety' => 'Content Safety',
        'swarmui' => 'SwarmUI Settings',
        'upload' => 'Upload Files'
    ];
    
    // Add tab for API testing
    if (current_user_can('administrator')) {
        $tabs['api_test'] = 'API Test';
    }
    ?>
    <div class="wrap">
        <h1>PNG Metadata Viewer</h1>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                <a href="?page=png-metadata-viewer&tab=<?= esc_attr($tab_id) ?>" 
                   class="nav-tab <?= $current_tab === $tab_id ? 'nav-tab-active' : '' ?>">
                    <?= esc_html($tab_name) ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <div class="pmv-tab-content">
            <?php if ($current_tab === 'upload') : ?>
                <?php pmv_upload_files_tab_content(); ?>
            <?php elseif ($current_tab === 'api_test') : ?>
                <?php pmv_api_test_tab_content(); ?>
            <?php elseif ($current_tab === 'safety') : ?>
                <?php pmv_safety_tab_content(); ?>
            <?php elseif ($current_tab === 'swarmui') : ?>
                <form method="post" action="options.php" id="pmv-settings-form">
                    <?php
                    settings_fields('png_metadata_viewer_settings');
                    do_settings_sections('png-metadata-viewer-' . $current_tab);
                    pmv_swarmui_tab_content();
                    ?>
                    
                    <!-- Hidden fields container for preserving other tab settings -->
                    <div id="pmv-hidden-fields"></div>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
            <?php elseif ($current_tab === 'conversations') : ?>
                <?php pmv_render_tab_content($current_tab); ?>
            <?php else : ?>
                <form method="post" action="options.php" id="pmv-settings-form">
                    <?php
                    settings_fields('png_metadata_viewer_settings');
                    do_settings_sections('png-metadata-viewer-' . $current_tab);
                    pmv_render_tab_content($current_tab);
                    ?>
                    
                    <!-- Hidden fields container for preserving other tab settings -->
                    <div id="pmv-hidden-fields"></div>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // All possible settings that might not be on the current tab
                    var allSettings = {
                        // OpenAI Settings
                        'openai_api_key': '<?= esc_js(get_option('openai_api_key', '')) ?>',
                        'openai_api_base_url': '<?= esc_js(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?>',
                        'openai_model': '<?= esc_js(get_option('openai_model', 'gpt-3.5-turbo')) ?>',
                        'openai_temperature': '<?= esc_js(get_option('openai_temperature', 0.7)) ?>',
                        'openai_max_tokens': '<?= esc_js(get_option('openai_max_tokens', 1000)) ?>',
                        'openai_presence_penalty': '<?= esc_js(get_option('openai_presence_penalty', 0.6)) ?>',
                        'openai_frequency_penalty': '<?= esc_js(get_option('openai_frequency_penalty', 0.3)) ?>',
                        
                        // Layout Settings
                        'png_metadata_cards_per_page': '<?= esc_js(get_option('png_metadata_cards_per_page', 12)) ?>',
                        'png_metadata_modal_bg_color': '<?= esc_js(get_option('png_metadata_modal_bg_color', '#ffffff')) ?>',
                        'png_metadata_modal_text_color': '<?= esc_js(get_option('png_metadata_modal_text_color', '#000000')) ?>',
                        'png_metadata_box_bg_color': '<?= esc_js(get_option('png_metadata_box_bg_color', '#f8f9fa')) ?>',
                        'png_metadata_card_name_text_color': '<?= esc_js(get_option('png_metadata_card_name_text_color', '#212529')) ?>',
                        'png_metadata_card_name_bg_color': '<?= esc_js(get_option('png_metadata_card_name_bg_color', '#e9ecef')) ?>',
                        'png_metadata_card_bg_color': '<?= esc_js(get_option('png_metadata_card_bg_color', '#ffffff')) ?>',
                        'png_metadata_card_tags_text_color': '<?= esc_js(get_option('png_metadata_card_tags_text_color', '#6c757d')) ?>',
                        'png_metadata_card_tags_bg_color': '<?= esc_js(get_option('png_metadata_card_tags_bg_color', '#f8f9fa')) ?>',
                        'png_metadata_pagination_button_text_color': '<?= esc_js(get_option('png_metadata_pagination_button_text_color', '#007bff')) ?>',
                        'png_metadata_pagination_button_bg_color': '<?= esc_js(get_option('png_metadata_pagination_button_bg_color', '#ffffff')) ?>',
                        'png_metadata_pagination_button_active_text_color': '<?= esc_js(get_option('png_metadata_pagination_button_active_text_color', '#ffffff')) ?>',
                        'png_metadata_pagination_button_active_bg_color': '<?= esc_js(get_option('png_metadata_pagination_button_active_bg_color', '#007bff')) ?>',
                        'png_metadata_filter1_title': '<?= esc_js(get_option('png_metadata_filter1_title', 'Category')) ?>',
                        'png_metadata_filter1_list': '<?= esc_js(get_option('png_metadata_filter1_list', 'General\nNSFW\nAnime\nRealistic')) ?>',
                        'png_metadata_filter2_title': '<?= esc_js(get_option('png_metadata_filter2_title', 'Style')) ?>',
                        'png_metadata_filter2_list': '<?= esc_js(get_option('png_metadata_filter2_list', 'Digital Art\nPainting\n3D Render\nPhotograph')) ?>',
                        'png_metadata_filter3_title': '<?= esc_js(get_option('png_metadata_filter3_title', 'Tags')) ?>',
                        'png_metadata_filter3_list': '<?= esc_js(get_option('png_metadata_filter3_list', 'Fantasy\nSci-Fi\nHorror\nRomance')) ?>',
                        'png_metadata_filter4_title': '<?= esc_js(get_option('png_metadata_filter4_title', 'Rating')) ?>',
                        'png_metadata_filter4_list': '<?= esc_js(get_option('png_metadata_filter4_list', 'Safe\nQuestionable\nExplicit')) ?>',
                        
                        // Button Settings
                        'png_metadata_button_text_color': '<?= esc_js(get_option('png_metadata_button_text_color', '#ffffff')) ?>',
                        'png_metadata_button_bg_color': '<?= esc_js(get_option('png_metadata_button_bg_color', '#007bff')) ?>',
                        'png_metadata_button_hover_text_color': '<?= esc_js(get_option('png_metadata_button_hover_text_color', '#ffffff')) ?>',
                        'png_metadata_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_button_hover_bg_color', '#0069d9')) ?>',
                        'png_metadata_secondary_button_text': '<?= esc_js(get_option('png_metadata_secondary_button_text', 'Learn More')) ?>',
                        'png_metadata_secondary_button_link': '<?= esc_js(get_option('png_metadata_secondary_button_link', '#')) ?>',
                        'png_metadata_secondary_button_text_color': '<?= esc_js(get_option('png_metadata_secondary_button_text_color', '#ffffff')) ?>',
                        'png_metadata_secondary_button_bg_color': '<?= esc_js(get_option('png_metadata_secondary_button_bg_color', '#6c757d')) ?>',
                        'png_metadata_secondary_button_hover_text_color': '<?= esc_js(get_option('png_metadata_secondary_button_hover_text_color', '#ffffff')) ?>',
                        'png_metadata_secondary_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_secondary_button_hover_bg_color', '#5a6268')) ?>',
                        'png_metadata_chat_button_text': '<?= esc_js(get_option('png_metadata_chat_button_text', 'Chat')) ?>',
                        'png_metadata_chat_button_text_color': '<?= esc_js(get_option('png_metadata_chat_button_text_color', '#ffffff')) ?>',
                        'png_metadata_chat_button_bg_color': '<?= esc_js(get_option('png_metadata_chat_button_bg_color', '#28a745')) ?>',
                        'png_metadata_chat_button_hover_text_color': '<?= esc_js(get_option('png_metadata_chat_button_hover_text_color', '#ffffff')) ?>',
                        'png_metadata_chat_button_hover_bg_color': '<?= esc_js(get_option('png_metadata_chat_button_hover_bg_color', '#218838')) ?>',
                        
                        // Chat Settings
                        'png_metadata_chat_modal_bg_color': '<?= esc_js(get_option('png_metadata_chat_modal_bg_color', '#ffffff')) ?>',
                        'png_metadata_chat_modal_name_text_color': '<?= esc_js(get_option('png_metadata_chat_modal_name_text_color', '#ffffff')) ?>',
                        'png_metadata_chat_modal_name_bg_color': '<?= esc_js(get_option('png_metadata_chat_modal_name_bg_color', '#007bff')) ?>',
                        'png_metadata_chat_input_text_color': '<?= esc_js(get_option('png_metadata_chat_input_text_color', '#495057')) ?>',
                        'png_metadata_chat_input_bg_color': '<?= esc_js(get_option('png_metadata_chat_input_bg_color', '#ffffff')) ?>',
                        'png_metadata_chat_user_bg_color': '<?= esc_js(get_option('png_metadata_chat_user_bg_color', '#e2f1ff')) ?>',
                        'png_metadata_chat_user_text_color': '<?= esc_js(get_option('png_metadata_chat_user_text_color', '#333333')) ?>',
                        'png_metadata_chat_bot_bg_color': '<?= esc_js(get_option('png_metadata_chat_bot_bg_color', '#f0f0f0')) ?>',
                        'png_metadata_chat_bot_text_color': '<?= esc_js(get_option('png_metadata_chat_bot_text_color', '#212529')) ?>',
                        'png_metadata_chat_history_font_size': '<?= esc_js(get_option('png_metadata_chat_history_font_size', 14)) ?>',
                        
                        // Gallery Settings
                        'png_metadata_card_border_width': '<?= esc_js(get_option('png_metadata_card_border_width', 1)) ?>',
                        'png_metadata_card_border_color': '<?= esc_js(get_option('png_metadata_card_border_color', '#dee2e6')) ?>',
                        'png_metadata_card_border_radius': '<?= esc_js(get_option('png_metadata_card_border_radius', 4)) ?>',
                        'png_metadata_button_border_width': '<?= esc_js(get_option('png_metadata_button_border_width', 1)) ?>',
                        'png_metadata_button_border_color': '<?= esc_js(get_option('png_metadata_button_border_color', '#007bff')) ?>',
                        'png_metadata_button_border_radius': '<?= esc_js(get_option('png_metadata_button_border_radius', 4)) ?>',
                        'png_metadata_button_margin': '<?= esc_js(get_option('png_metadata_button_margin', '5px')) ?>',
                        'png_metadata_button_padding': '<?= esc_js(get_option('png_metadata_button_padding', '8px 15px')) ?>',
                        'png_metadata_gallery_bg_color': '<?= esc_js(get_option('png_metadata_gallery_bg_color', '#ffffff')) ?>',
                        'png_metadata_gallery_padding': '<?= esc_js(get_option('png_metadata_gallery_padding', '0px')) ?>',
                        'png_metadata_gallery_border_radius': '<?= esc_js(get_option('png_metadata_gallery_border_radius', 0)) ?>',
                        'png_metadata_gallery_border_width': '<?= esc_js(get_option('png_metadata_gallery_border_width', 0)) ?>',
                        'png_metadata_gallery_border_color': '<?= esc_js(get_option('png_metadata_gallery_border_color', '#dee2e6')) ?>',
                        'png_metadata_gallery_box_shadow': '<?= esc_js(get_option('png_metadata_gallery_box_shadow', 'none')) ?>',
                        'png_metadata_filters_bg_color': '<?= esc_js(get_option('png_metadata_filters_bg_color', '#f5f5f5')) ?>',
                        'png_metadata_filters_padding': '<?= esc_js(get_option('png_metadata_filters_padding', '15px')) ?>',
                        'png_metadata_filters_border_radius': '<?= esc_js(get_option('png_metadata_filters_border_radius', 8)) ?>',
                        'png_metadata_filters_border_width': '<?= esc_js(get_option('png_metadata_filters_border_width', 0)) ?>',
                        'png_metadata_filters_border_color': '<?= esc_js(get_option('png_metadata_filters_border_color', '#dee2e6')) ?>',
                        'png_metadata_filters_box_shadow': '<?= esc_js(get_option('png_metadata_filters_box_shadow', '0 1px 3px rgba(0,0,0,0.1)')) ?>',
                        
                        // Conversation Settings
                        'pmv_allow_guest_conversations': '<?= esc_js(get_option('pmv_allow_guest_conversations', 1)) ?>',
                        'pmv_auto_save_conversations': '<?= esc_js(get_option('pmv_auto_save_conversations', 1)) ?>',
                        'pmv_max_conversations_per_user': '<?= esc_js(get_option('pmv_max_conversations_per_user', 50)) ?>',
                        'pmv_guest_daily_token_limit': '<?= esc_js(get_option('pmv_guest_daily_token_limit', 10000)) ?>',
                        'pmv_default_user_monthly_limit': '<?= esc_js(get_option('pmv_default_user_monthly_limit', 100000)) ?>',
                        
                        // Keyword Flagging Settings
                        'pmv_unsafe_keywords': '<?= esc_js(get_option('pmv_unsafe_keywords', '')) ?>',
                        'pmv_enable_keyword_flagging': '<?= esc_js(get_option('pmv_enable_keyword_flagging', 1)) ?>',
                        'pmv_keyword_case_sensitive': '<?= esc_js(get_option('pmv_keyword_case_sensitive', 0)) ?>',
                        
                        // SwarmUI Settings
                        'pmv_image_provider': '<?= esc_js(get_option('pmv_image_provider', 'swarmui')) ?>',
                        'pmv_swarmui_enabled': '<?= esc_js(get_option('pmv_swarmui_enabled', 0)) ?>',
                        'pmv_swarmui_api_url': '<?= esc_js(get_option('pmv_swarmui_api_url', '')) ?>',
                        'pmv_swarmui_api_key': '<?= esc_js(get_option('pmv_swarmui_api_key', '')) ?>',
                        'pmv_swarmui_user_token': '<?= esc_js(get_option('pmv_swarmui_user_token', '')) ?>',
                        'pmv_swarmui_default_model': '<?= esc_js(get_option('pmv_swarmui_default_model', 'OfficialStableDiffusion/sd_xl_base_1.0')) ?>',
                        'pmv_swarmui_global_daily_limit': '<?= esc_js(get_option('pmv_swarmui_global_daily_limit', 100)) ?>',
                        'pmv_swarmui_global_monthly_limit': '<?= esc_js(get_option('pmv_swarmui_global_monthly_limit', 1000)) ?>',
                        'pmv_swarmui_user_daily_limit': '<?= esc_js(get_option('pmv_swarmui_user_daily_limit', 10)) ?>',
                        'pmv_swarmui_user_monthly_limit': '<?= esc_js(get_option('pmv_swarmui_user_monthly_limit', 100)) ?>',
                        'pmv_swarmui_guest_daily_limit': '<?= esc_js(get_option('pmv_swarmui_guest_daily_limit', 5)) ?>',
                        'pmv_swarmui_auto_trigger_keywords': '<?= esc_js(get_option('pmv_swarmui_auto_trigger_keywords', 'generate image, create image, draw, picture, photo')) ?>',
                        'pmv_swarmui_allow_prompt_editing': '<?= esc_js(get_option('pmv_swarmui_allow_prompt_editing', 1)) ?>',
                        'pmv_swarmui_default_steps': '<?= esc_js(get_option('pmv_swarmui_default_steps', 20)) ?>',
                        'pmv_swarmui_default_cfg_scale': '<?= esc_js(get_option('pmv_swarmui_default_cfg_scale', 7.0)) ?>',
                        'pmv_swarmui_default_width': '<?= esc_js(get_option('pmv_swarmui_default_width', 512)) ?>',
                        'pmv_swarmui_default_height': '<?= esc_js(get_option('pmv_swarmui_default_height', 512)) ?>',
                        
                        // Nano-GPT Settings
                        'pmv_nanogpt_enabled': '<?= esc_js(get_option('pmv_nanogpt_enabled', 0)) ?>',
                        'pmv_nanogpt_api_url': '<?= esc_js(get_option('pmv_nanogpt_api_url', 'https://nano-gpt.com/api')) ?>',
                        'pmv_nanogpt_api_key': '<?= esc_js(get_option('pmv_nanogpt_api_key', '')) ?>',
                        'pmv_nanogpt_default_model': '<?= esc_js(get_option('pmv_nanogpt_default_model', 'recraft-v3')) ?>',
                        'pmv_nanogpt_global_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_global_daily_limit', 100)) ?>',
                        'pmv_nanogpt_global_monthly_limit': '<?= esc_js(get_option('pmv_nanogpt_global_monthly_limit', 1000)) ?>',
                        'pmv_nanogpt_user_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_user_daily_limit', 10)) ?>',
                        'pmv_nanogpt_user_monthly_limit': '<?= esc_js(get_option('pmv_nanogpt_user_monthly_limit', 100)) ?>',
                        'pmv_nanogpt_guest_daily_limit': '<?= esc_js(get_option('pmv_nanogpt_guest_daily_limit', 5)) ?>',
                        'pmv_nanogpt_auto_trigger_keywords': '<?= esc_js(get_option('pmv_nanogpt_auto_trigger_keywords', 'generate image, create image, draw, picture, photo')) ?>',
                        'pmv_nanogpt_allow_prompt_editing': '<?= esc_js(get_option('pmv_nanogpt_allow_prompt_editing', 1)) ?>',
                        'pmv_nanogpt_default_steps': '<?= esc_js(get_option('pmv_nanogpt_default_steps', 10)) ?>',
                        'pmv_nanogpt_default_scale': '<?= esc_js(get_option('pmv_nanogpt_default_scale', 7.5)) ?>',
                        'pmv_nanogpt_default_width': '<?= esc_js(get_option('pmv_nanogpt_default_width', 1024)) ?>',
                        'pmv_nanogpt_default_height': '<?= esc_js(get_option('pmv_nanogpt_default_height', 1024)) ?>'
                    };
                    
                    // Before form submission, add hidden fields for all settings not in current tab
                    $('#pmv-settings-form').on('submit', function() {
                        var currentFormFields = {};
                        
                        // Track which fields are already in the form
                        $(this).find('input, select, textarea').each(function() {
                            var name = $(this).attr('name');
                            if (name && name !== 'submit' && name !== '_wpnonce' && name !== '_wp_http_referer' && name !== 'option_page' && name !== 'action') {
                                currentFormFields[name] = true;
                            }
                        });
                        
                        // Add hidden fields for missing settings
                        var hiddenContainer = $('#pmv-hidden-fields');
                        hiddenContainer.empty(); // Clear any previous hidden fields
                        
                        $.each(allSettings, function(settingName, settingValue) {
                            if (!currentFormFields[settingName]) {
                                var input = $('<input>').attr({
                                    type: 'hidden',
                                    name: settingName,
                                    value: settingValue
                                });
                                hiddenContainer.append(input);
                            }
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render tab content
 */
function pmv_render_tab_content($tab) {
    switch ($tab) {
        case 'layout':
            pmv_layout_tab_content();
            break;
        case 'buttons':
            pmv_buttons_tab_content();
            break;
        case 'chat':
            pmv_chat_tab_content();
            break;
        case 'gallery':
            pmv_gallery_tab_content();
            break;
        case 'openai':
            pmv_openai_tab_content();
            break;
        case 'conversations':
            pmv_conversations_tab_content();
            break;
    }
}

/**
 * API Test tab content
 */
function pmv_api_test_tab_content() {
    ?>
    <div id="pmv-api-test">
        <h3>API Configuration Test</h3>
        <p>Test your API connection and settings:</p>
        
        <div id="api-test-results" style="margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <div id="test-status">Ready to test...</div>
            <div id="test-details" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>
        </div>
        
        <button id="test-api-btn" class="button button-primary">Test API Connection</button>
        
        <h3>Token Counter Test</h3>
        <p>Test the token counter functionality:</p>
        
        <div id="token-test-results" style="margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <div id="token-test-status">Ready to test...</div>
        </div>
        
        <button id="test-token-counter-btn" class="button button-secondary">Test Token Counter</button>
        
        <h4>Current Settings</h4>
        <ul>
            <li><strong>API Base URL:</strong> <?= esc_html(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?></li>
            <li><strong>Model:</strong> <?= esc_html(get_option('openai_model', 'deepseek-chat')) ?></li>
            <li><strong>Temperature:</strong> <?= esc_html(get_option('openai_temperature', 0.7)) ?></li>
            <li><strong>Max Tokens:</strong> <?= esc_html(get_option('openai_max_tokens', 1000)) ?></li>
            <li><strong>API Key:</strong> <?= get_option('openai_api_key') ? 'Set (***hidden***)' : 'Not set' ?></li>
        </ul>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-api-btn').click(function() {
            const button = $(this);
            const results = $('#api-test-results');
            const status = $('#test-status');
            const details = $('#test-details');
            
            button.prop('disabled', true).text('Testing...');
            status.text('Testing API connection...');
            details.text('');
            results.css('background', '#fff3cd');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_api_directly',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.text('✅ API Test Successful!');
                        results.css('background', '#d4edda');
                        
                        if (response.data && response.data.response) {
                            const apiResponse = response.data.response;
                            details.html(
                                '<strong>Response Details:</strong><br>' +
                                'Model: ' + (apiResponse.model || 'N/A') + '<br>' +
                                'Usage: ' + JSON.stringify(apiResponse.usage || {}) + '<br>' +
                                'Response: ' + (apiResponse.choices && apiResponse.choices[0] 
                                    ? apiResponse.choices[0].message.content 
                                    : 'No response content')
                            );
                        }
                    } else {
                        status.text('❌ API Test Failed');
                        results.css('background', '#f8d7da');
                        details.text('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    status.text('❌ Connection Error');
                    results.css('background', '#f8d7da');
                    details.text('Network error: ' + error);
                },
                complete: function() {
                    button.prop('disabled', false).text('Test API Connection');
                }
            });
        });
        
        // Token counter test
        $('#test-token-counter-btn').click(function() {
            const button = $(this);
            const results = $('#token-test-results');
            const status = $('#token-test-status');
            
            button.prop('disabled', true).text('Testing...');
            status.text('Testing token counter...');
            results.css('background', '#fff3cd');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_token_counter',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.text('✅ Token Counter Test Successful!');
                        results.css('background', '#d4edda');
                    } else {
                        status.text('❌ Token Counter Test Failed');
                        results.css('background', '#f8d7da');
                    }
                },
                error: function(xhr, status, error) {
                    status.text('❌ Connection Error');
                    results.css('background', '#f8d7da');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Token Counter');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for admin conversation deletion
 */
function pmv_ajax_admin_delete_conversation() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_admin_delete_conversation')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $conversation_id = intval($_POST['conversation_id']);
    if ($conversation_id <= 0) {
        wp_send_json_error(['message' => 'Invalid conversation ID']);
        return;
    }
    
    // Delete conversation using the function from conversations-database.php
    $result = pmv_delete_conversation($conversation_id);
    
    if ($result) {
        wp_send_json_success(['message' => 'Conversation deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete conversation']);
    }
}

/**
 * AJAX handler for testing API directly
 */
function pmv_ajax_test_api_directly() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $api_key = get_option('openai_api_key', '');
    $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1/');
    $model = get_option('openai_model', 'gpt-3.5-turbo');
    $temperature = floatval(get_option('openai_temperature', 0.7));
    $max_tokens = intval(get_option('openai_max_tokens', 100));
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key not configured']);
        return;
    }
    
    // Prepare test request
    $test_data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello! This is a test message to verify the API connection. Please respond with a brief confirmation.'
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => min($max_tokens, 100) // Limit for test
    ];
    
    // Make API request
    $response = wp_remote_post(rtrim($api_base_url, '/') . '/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($test_data)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Request error: ' . $response->get_error_message()]);
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        $error_data = json_decode($response_body, true);
        $error_message = 'HTTP ' . $response_code;
        
        if (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
        }
        
        wp_send_json_error(['message' => $error_message]);
        return;
    }
    
    $api_response = json_decode($response_body, true);
    
    if (!$api_response) {
        wp_send_json_error(['message' => 'Invalid JSON response from API']);
        return;
    }
    
    wp_send_json_success([
        'message' => 'API test successful!',
        'response' => $api_response
    ]);
}

/**
 * AJAX handler for PNG file upload
 */
function pmv_ajax_upload_png() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_upload_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed');
        return;
    }
    
    $file = $_FILES['file'];
    
    // Validate file type
    if ($file['type'] !== 'image/png') {
        wp_send_json_error('Only PNG files are allowed');
        return;
    }
    
    // Validate file size (e.g., max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        wp_send_json_error('File size too large (max 10MB)');
        return;
    }
    
    // Use WordPress upload handling
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    $upload_overrides = [
        'test_form' => false,
        'mimes' => ['png' => 'image/png']
    ];
    
    $movefile = wp_handle_upload($file, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        // File uploaded successfully
        $file_path = $movefile['file'];
        $file_url = $movefile['url'];
        
        // Try to extract metadata
        $metadata = pmv_extract_png_metadata($file_path);
        
        if ($metadata) {
            wp_send_json_success([
                'message' => 'PNG uploaded and metadata extracted successfully',
                'file_url' => $file_url,
                'metadata' => $metadata
            ]);
        } else {
            wp_send_json_success([
                'message' => 'PNG uploaded but no metadata found',
                'file_url' => $file_url
            ]);
        }
    } else {
        wp_send_json_error('Upload failed: ' . $movefile['error']);
    }
}

/**
 * Helper function to extract PNG metadata (basic implementation)
 */
function pmv_extract_png_metadata($file_path) {
    // This is a basic implementation - you would expand this based on your needs
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Try to read PNG chunks for metadata
    $handle = fopen($file_path, 'rb');
    if (!$handle) {
        return false;
    }
    
    // Skip PNG signature
    fseek($handle, 8);
    
    $metadata = [];
    
    while (!feof($handle)) {
        $chunk_length = unpack('N', fread($handle, 4))[1];
        $chunk_type = fread($handle, 4);
        
        if ($chunk_type === 'tEXt' || $chunk_type === 'iTXt') {
            $chunk_data = fread($handle, $chunk_length);
            
            if ($chunk_type === 'tEXt') {
                $null_pos = strpos($chunk_data, "\0");
                if ($null_pos !== false) {
                    $key = substr($chunk_data, 0, $null_pos);
                    $value = substr($chunk_data, $null_pos + 1);
                    $metadata[$key] = $value;
                }
            }
        } else {
            fseek($handle, $chunk_length, SEEK_CUR);
        }
        
        // Skip CRC
        fseek($handle, 4, SEEK_CUR);
        
        // Stop at IEND chunk
        if ($chunk_type === 'IEND') {
            break;
        }
    }
    
    fclose($handle);
    
    return !empty($metadata) ? $metadata : false;
}

// Register AJAX handlers
add_action('wp_ajax_pmv_admin_delete_conversation', 'pmv_ajax_admin_delete_conversation');
add_action('wp_ajax_pmv_test_api_directly', 'pmv_ajax_test_api_directly');
add_action('wp_ajax_pmv_upload_png', 'pmv_ajax_upload_png');
