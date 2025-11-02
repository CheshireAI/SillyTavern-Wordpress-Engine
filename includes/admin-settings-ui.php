<?php
if (!defined('ABSPATH')) exit;

/**
 * Layout tab content
 */
function pmv_layout_tab_content() {
    ?>
    <h3>Layout Settings</h3>
    <table class="form-table">
        <tr>
            <th>Cards per Page</th>
            <td>
                <input type="number" name="png_metadata_cards_per_page" 
                    value="<?= esc_attr(get_option('png_metadata_cards_per_page', 12)) ?>" 
                    min="1" max="50" step="1">
                <p class="description">Number of character cards to display per page.</p>
            </td>
        </tr>
        <tr>
            <th>Modal Background Color</th>
            <td>
                <input type="color" name="png_metadata_modal_bg_color"
value="<?= esc_attr(get_option('png_metadata_modal_bg_color', '#1a1a1a')) ?>">
            </td>
        </tr>
        <tr>
            <th>Modal Text Color</th>
            <td>
                <input type="color" name="png_metadata_modal_text_color"
value="<?= esc_attr(get_option('png_metadata_modal_text_color', '#e0e0e0')) ?>">
            </td>
        </tr>
        <tr>
            <th>Box Background Color</th>
            <td>
                <input type="color" name="png_metadata_box_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_box_bg_color', '#f8f9fa')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Name Text Color</th>
            <td>
                <input type="color" name="png_metadata_card_name_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_name_text_color', '#212529')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Name Background Color</th>
            <td>
                <input type="color" name="png_metadata_card_name_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_name_bg_color', '#e9ecef')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Background Color</th>
            <td>
                <input type="color" name="png_metadata_card_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_bg_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Tags Text Color</th>
            <td>
                <input type="color" name="png_metadata_card_tags_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_tags_text_color', '#6c757d')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Tags Background Color</th>
            <td>
                <input type="color" name="png_metadata_card_tags_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_tags_bg_color', '#f8f9fa')) ?>">
            </td>
        </tr>
        <tr>
            <th>Pagination Button Text Color</th>
            <td>
                <input type="color" name="png_metadata_pagination_button_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_pagination_button_text_color', '#007bff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Pagination Button Background Color</th>
            <td>
                                <input type="color" name="png_metadata_pagination_button_bg_color"
                       value="<?= esc_attr(get_option('png_metadata_pagination_button_bg_color', '#23404e')) ?>">
            </td>
        </tr>
        <tr>
            <th>Pagination Active Button Text Color</th>
            <td>
                <input type="color" name="png_metadata_pagination_button_active_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_pagination_button_active_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Pagination Active Button Background Color</th>
            <td>
                                <input type="color" name="png_metadata_pagination_button_active_bg_color"
                       value="<?= esc_attr(get_option('png_metadata_pagination_button_active_bg_color', '#2d5363')) ?>">
            </td>
        </tr>
    </table>
    
    <h3>Filter Settings</h3>
    <table class="form-table">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <tr>
            <th>Filter <?= $i ?> Title</th>
            <td>
                <input type="text" name="png_metadata_filter<?= $i ?>_title" 
                    value="<?= esc_attr(get_option("png_metadata_filter{$i}_title", 
                        $i == 1 ? 'Category' : ($i == 2 ? 'Style' : ($i == 3 ? 'Tags' : 'Rating')))) ?>" 
                    style="width: 300px;">
            </td>
        </tr>
        <tr>
            <th>Filter <?= $i ?> Options</th>
            <td>
                <textarea name="png_metadata_filter<?= $i ?>_list" 
                    style="width: 400px; height: 100px;"><?= esc_textarea(get_option("png_metadata_filter{$i}_list", 
                        $i == 1 ? "General\nNSFW\nAnime\nRealistic" : 
                        ($i == 2 ? "Digital Art\nPainting\n3D Render\nPhotograph" : 
                        ($i == 3 ? "Fantasy\nSci-Fi\nHorror\nRomance" : "Safe\nQuestionable\nExplicit")))) ?></textarea>
                <p class="description">One option per line.</p>
            </td>
        </tr>
        <?php endfor; ?>
    </table>
    <?php
}

/**
 * Buttons tab content
 */
function pmv_buttons_tab_content() {
    ?>
    <h3>Button Settings</h3>
    <table class="form-table">
        <tr>
            <th>Button Text Color</th>
            <td>
                <input type="color" name="png_metadata_button_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_button_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Button Background Color</th>
            <td>
                <input type="color" name="png_metadata_button_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_button_bg_color', '#007bff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Button Hover Text Color</th>
            <td>
                <input type="color" name="png_metadata_button_hover_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_button_hover_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Button Hover Background Color</th>
            <td>
                <input type="color" name="png_metadata_button_hover_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_button_hover_bg_color', '#0069d9')) ?>">
            </td>
        </tr>
        <tr>
            <th>Button Border Width</th>
            <td>
                <input type="number" name="png_metadata_button_border_width" 
                    value="<?= esc_attr(get_option('png_metadata_button_border_width', 1)) ?>" 
                    min="0" max="10" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Button Border Color</th>
            <td>
                <input type="color" name="png_metadata_button_border_color" 
                    value="<?= esc_attr(get_option('png_metadata_button_border_color', '#007bff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Button Border Radius</th>
            <td>
                <input type="number" name="png_metadata_button_border_radius" 
                    value="<?= esc_attr(get_option('png_metadata_button_border_radius', 4)) ?>" 
                    min="0" max="50" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Button Margin</th>
            <td>
                <input type="text" name="png_metadata_button_margin" 
                    value="<?= esc_attr(get_option('png_metadata_button_margin', '5px')) ?>" 
                    style="width: 200px;">
                <p class="description">CSS margin value (e.g., "5px", "10px 5px").</p>
            </td>
        </tr>
        <tr>
            <th>Button Padding</th>
            <td>
                <input type="text" name="png_metadata_button_padding" 
                    value="<?= esc_attr(get_option('png_metadata_button_padding', '8px 15px')) ?>" 
                    style="width: 200px;">
                <p class="description">CSS padding value (e.g., "8px 15px").</p>
            </td>
        </tr>
    </table>
    
    <h3>Secondary Button</h3>
    <table class="form-table">
        <tr>
            <th>Secondary Button Text</th>
            <td>
                <input type="text" name="png_metadata_secondary_button_text" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_text', 'Learn More')) ?>" 
                    style="width: 300px;">
            </td>
        </tr>
        <tr>
            <th>Secondary Button Link</th>
            <td>
                <input type="url" name="png_metadata_secondary_button_link" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_link', '#')) ?>" 
                    style="width: 400px;">
            </td>
        </tr>
        <tr>
            <th>Secondary Button Text Color</th>
            <td>
                <input type="color" name="png_metadata_secondary_button_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Secondary Button Background Color</th>
            <td>
                <input type="color" name="png_metadata_secondary_button_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_bg_color', '#6c757d')) ?>">
            </td>
        </tr>
        <tr>
            <th>Secondary Button Hover Text Color</th>
            <td>
                <input type="color" name="png_metadata_secondary_button_hover_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_hover_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Secondary Button Hover Background Color</th>
            <td>
                <input type="color" name="png_metadata_secondary_button_hover_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_secondary_button_hover_bg_color', '#5a6268')) ?>">
            </td>
        </tr>
    </table>
    
    <h3>Chat Button</h3>
    <table class="form-table">
        <tr>
            <th>Chat Button Text</th>
            <td>
                <input type="text" name="png_metadata_chat_button_text" 
                    value="<?= esc_attr(get_option('png_metadata_chat_button_text', 'Chat')) ?>" 
                    style="width: 300px;">
            </td>
        </tr>
        <tr>
            <th>Chat Button Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_button_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_button_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Button Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_button_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_button_bg_color', '#28a745')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Button Hover Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_button_hover_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_button_hover_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Button Hover Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_button_hover_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_button_hover_bg_color', '#218838')) ?>">
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Chat tab content
 */
function pmv_chat_tab_content() {
    ?>
    <h3>Chat Modal Settings</h3>
    <table class="form-table">
        <tr>
            <th>Chat Modal Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_modal_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_modal_bg_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Modal Name Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_modal_name_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_modal_name_text_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Modal Name Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_modal_name_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_modal_name_bg_color', '#007bff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Input Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_input_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_input_text_color', '#495057')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat Input Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_input_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_input_bg_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>User Message Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_user_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_user_bg_color', '#e2f1ff')) ?>">
            </td>
        </tr>
        <tr>
            <th>User Message Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_user_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_user_text_color', '#333333')) ?>">
            </td>
        </tr>
        <tr>
            <th>Bot Message Background Color</th>
            <td>
                <input type="color" name="png_metadata_chat_bot_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_bot_bg_color', '#f0f0f0')) ?>">
            </td>
        </tr>
        <tr>
            <th>Bot Message Text Color</th>
            <td>
                <input type="color" name="png_metadata_chat_bot_text_color" 
                    value="<?= esc_attr(get_option('png_metadata_chat_bot_text_color', '#212529')) ?>">
            </td>
        </tr>
        <tr>
            <th>Chat History Font Size</th>
            <td>
                <input type="number" name="png_metadata_chat_history_font_size" 
                    value="<?= esc_attr(get_option('png_metadata_chat_history_font_size', 14)) ?>" 
                    min="10" max="24" step="1">
                <span>px</span>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Gallery tab content
 */
function pmv_gallery_tab_content() {
    ?>
    <h3>Gallery Styling</h3>
    <table class="form-table">
        <tr>
            <th>Gallery Background Color</th>
            <td>
                <input type="color" name="png_metadata_gallery_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_bg_color', '#ffffff')) ?>">
            </td>
        </tr>
        <tr>
            <th>Gallery Padding</th>
            <td>
                <input type="text" name="png_metadata_gallery_padding" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_padding', '0px')) ?>" 
                    style="width: 200px;">
                <p class="description">CSS padding value (e.g., "15px", "10px 5px").</p>
            </td>
        </tr>
        <tr>
            <th>Gallery Border Radius</th>
            <td>
                <input type="number" name="png_metadata_gallery_border_radius" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_border_radius', 0)) ?>" 
                    min="0" max="50" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Gallery Border Width</th>
            <td>
                <input type="number" name="png_metadata_gallery_border_width" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_border_width', 0)) ?>" 
                    min="0" max="10" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Gallery Border Color</th>
            <td>
                <input type="color" name="png_metadata_gallery_border_color" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_border_color', '#dee2e6')) ?>">
            </td>
        </tr>
        <tr>
            <th>Gallery Box Shadow</th>
            <td>
                <input type="text" name="png_metadata_gallery_box_shadow" 
                    value="<?= esc_attr(get_option('png_metadata_gallery_box_shadow', 'none')) ?>" 
                    style="width: 400px;">
                <p class="description">CSS box-shadow value (e.g., "0 2px 4px rgba(0,0,0,0.1)" or "none").</p>
            </td>
        </tr>
    </table>
    
    <h3>Card Styling</h3>
    <table class="form-table">
        <tr>
            <th>Card Border Width</th>
            <td>
                <input type="number" name="png_metadata_card_border_width" 
                    value="<?= esc_attr(get_option('png_metadata_card_border_width', 1)) ?>" 
                    min="0" max="10" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Card Border Color</th>
            <td>
                <input type="color" name="png_metadata_card_border_color" 
                    value="<?= esc_attr(get_option('png_metadata_card_border_color', '#dee2e6')) ?>">
            </td>
        </tr>
        <tr>
            <th>Card Border Radius</th>
            <td>
                <input type="number" name="png_metadata_card_border_radius" 
                    value="<?= esc_attr(get_option('png_metadata_card_border_radius', 4)) ?>" 
                    min="0" max="50" step="1">
                <span>px</span>
            </td>
        </tr>
    </table>
    
    <h3>Filters Styling</h3>
    <table class="form-table">
        <tr>
            <th>Filters Background Color</th>
            <td>
                <input type="color" name="png_metadata_filters_bg_color" 
                    value="<?= esc_attr(get_option('png_metadata_filters_bg_color', '#f5f5f5')) ?>">
            </td>
        </tr>
        <tr>
            <th>Filters Padding</th>
            <td>
                <input type="text" name="png_metadata_filters_padding" 
                    value="<?= esc_attr(get_option('png_metadata_filters_padding', '15px')) ?>" 
                    style="width: 200px;">
                <p class="description">CSS padding value (e.g., "15px", "10px 5px").</p>
            </td>
        </tr>
        <tr>
            <th>Filters Border Radius</th>
            <td>
                <input type="number" name="png_metadata_filters_border_radius" 
                    value="<?= esc_attr(get_option('png_metadata_filters_border_radius', 8)) ?>" 
                    min="0" max="50" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Filters Border Width</th>
            <td>
                <input type="number" name="png_metadata_filters_border_width" 
                    value="<?= esc_attr(get_option('png_metadata_filters_border_width', 0)) ?>" 
                    min="0" max="10" step="1">
                <span>px</span>
            </td>
        </tr>
        <tr>
            <th>Filters Border Color</th>
            <td>
                <input type="color" name="png_metadata_filters_border_color" 
                    value="<?= esc_attr(get_option('png_metadata_filters_border_color', '#dee2e6')) ?>">
            </td>
        </tr>
        <tr>
            <th>Filters Box Shadow</th>
            <td>
                <input type="text" name="png_metadata_filters_box_shadow" 
                    value="<?= esc_attr(get_option('png_metadata_filters_box_shadow', '0 1px 3px rgba(0,0,0,0.1)')) ?>" 
                    style="width: 400px;">
                <p class="description">CSS box-shadow value (e.g., "0 2px 4px rgba(0,0,0,0.1)" or "none").</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * OpenAI tab content
 */
function pmv_openai_tab_content() {
    ?>
    <h3>OpenAI API Settings</h3>
    <table class="form-table">
        <tr>
            <th>API Key</th>
            <td>
                <input type="password" name="openai_api_key" 
                    value="<?= esc_attr(get_option('openai_api_key', '')) ?>" 
                    style="width: 400px;">
                <p class="description">Your OpenAI API key or compatible API key.</p>
            </td>
        </tr>
        <tr>
            <th>API Base URL</th>
            <td>
                <input type="url" name="openai_api_base_url" 
                    value="<?= esc_attr(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?>" 
                    style="width: 400px;">
                <p class="description">Base URL for the API (leave default for OpenAI, change for compatible APIs).</p>
            </td>
        </tr>
        <tr>
            <th>Model</th>
            <td>
                <input type="text" name="openai_model" 
                    value="<?= esc_attr(get_option('openai_model', 'gpt-3.5-turbo')) ?>" 
                    style="width: 300px;">
                <p class="description">Model to use (e.g., gpt-3.5-turbo, gpt-4, deepseek-chat).</p>
            </td>
        </tr>
        <tr>
            <th>Temperature</th>
            <td>
                <input type="number" name="openai_temperature" 
                    value="<?= esc_attr(get_option('openai_temperature', 0.7)) ?>" 
                    min="0" max="2" step="0.1" style="width: 100px;">
                <p class="description">Controls randomness (0-2). Higher values make output more random.</p>
            </td>
        </tr>
        <tr>
            <th>Max Tokens</th>
            <td>
                <input type="number" name="openai_max_tokens" 
                    value="<?= esc_attr(get_option('openai_max_tokens', 1000)) ?>" 
                    min="1" max="8192" step="1" style="width: 100px;">
                <p class="description">Maximum tokens to generate (1-8192).</p>
            </td>
        </tr>
        <tr>
            <th>Presence Penalty</th>
            <td>
                <input type="number" name="openai_presence_penalty" 
                    value="<?= esc_attr(get_option('openai_presence_penalty', 0.6)) ?>" 
                    min="-2" max="2" step="0.1" style="width: 100px;">
                <p class="description">Penalty for new topics (-2 to 2).</p>
            </td>
        </tr>
        <tr>
            <th>Frequency Penalty</th>
            <td>
                <input type="number" name="openai_frequency_penalty" 
                    value="<?= esc_attr(get_option('openai_frequency_penalty', 0.3)) ?>" 
                    min="-2" max="2" step="0.1" style="width: 100px;">
                <p class="description">Penalty for repetition (-2 to 2).</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Enhanced Conversations tab content with admin viewer
 */
function pmv_conversations_tab_content() {
    // Get filter parameters
    $user_filter = isset($_GET['user_filter']) ? sanitize_text_field($_GET['user_filter']) : '';
    $character_filter = isset($_GET['character_filter']) ? sanitize_text_field($_GET['character_filter']) : '';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $keyword_search = isset($_GET['keyword_search']) ? sanitize_text_field($_GET['keyword_search']) : '';
    $safety_filter = isset($_GET['safety_filter']) ? sanitize_text_field($_GET['safety_filter']) : '';
    $current_page = isset($_GET['conv_page']) ? max(1, intval($_GET['conv_page'])) : 1;
    $per_page = 25;
    
    // Get conversations
    $args = [
        'user_filter' => $user_filter,
        'character_filter' => $character_filter,
        'keyword_search' => $keyword_search,
        'safety_filter' => $safety_filter,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'page' => $current_page,
        'per_page' => $per_page
    ];
    
    $conversations = pmv_get_conversations_for_admin($args);
    $total_conversations = pmv_get_conversations_count($args);
    $total_pages = ceil($total_conversations / $per_page);
    
    // 1. Add a PHP variable for the export nonce at the top of pmv_conversations_tab_content:
    $pmv_export_nonce = wp_create_nonce('pmv_export_nonce');
    ?>
    
    <style>
    .pmv-admin-conversations {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        margin: 20px 0;
    }
    .pmv-filters {
        background: #f8f9fa;
        padding: 20px;
        border-bottom: 1px solid #e1e1e1;
    }
    .pmv-filter-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        align-items: end;
    }
    .pmv-filter-group {
        flex: 1;
    }
    .pmv-filter-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .pmv-filter-group input, .pmv-filter-group select {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    .pmv-conversations-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pmv-conversations-table th {
        background: #f1f1f1;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #ddd;
    }
    .pmv-conversations-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: top;
    }
    .pmv-conversations-table tr:hover {
        background: #f8f9fa;
    }
    .pmv-conversation-actions {
        display: flex;
        gap: 5px;
    }
    .pmv-btn {
        padding: 4px 8px;
        border: 1px solid #ddd;
        background: #f7f7f7;
        color: #333;
        text-decoration: none;
        border-radius: 3px;
        font-size: 12px;
        cursor: pointer;
    }
    .pmv-btn:hover {
        background: #e6e6e6;
    }
    .pmv-btn-danger {
        background: #dc3545;
        color: white;
        border-color: #dc3545;
    }
    .pmv-btn-danger:hover {
        background: #c82333;
        color: white;
    }
    .pmv-pagination {
        padding: 20px;
        text-align: center;
        border-top: 1px solid #e1e1e1;
        background: #f8f9fa;
    }
    .pmv-stats {
        padding: 15px 20px;
        background: #e8f4f8;
        border-bottom: 1px solid #b8daff;
        font-size: 14px;
    }
    .pmv-modal {
        display: none;
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
    }
    .pmv-modal-content {
        background-color: #fefefe;
        margin: 2% auto;
        padding: 0;
        border-radius: 8px;
        width: 90%;
        max-width: 1000px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .pmv-modal-header {
        background: #007cba;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .pmv-modal-header h3 {
        margin: 0;
        color: white;
    }
    .pmv-close {
        color: white;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    .pmv-close:hover {
        background: rgba(255,255,255,0.2);
    }
    .pmv-modal-body {
        padding: 20px;
        max-height: 70vh;
        overflow-y: auto;
    }
    .pmv-conversation-meta {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #007cba;
    }
    .pmv-message {
        margin-bottom: 15px;
        padding: 12px;
        border-radius: 6px;
        border-left: 4px solid #ddd;
    }
    .pmv-message.user {
        background: #e3f2fd;
        border-left-color: #1976d2;
    }
    .pmv-message.assistant {
        background: #f3e5f5;
        border-left-color: #7b1fa2;
    }
    .pmv-message-header {
        font-weight: bold;
        margin-bottom: 8px;
        color: #555;
        font-size: 13px;
    }
    .pmv-message-content {
        line-height: 1.5;
        word-wrap: break-word;
    }
    </style>
    
    <div class="pmv-admin-conversations">
        <!-- Filters -->
        <div class="pmv-filters">
            <form method="get" id="pmv-conversation-filters">
                <input type="hidden" name="page" value="png-metadata-viewer">
                <input type="hidden" name="tab" value="conversations">
                
                <div class="pmv-filter-row">
                    <div class="pmv-filter-group">
                        <label>User (Name/Email/Login)</label>
                        <input type="text" name="user_filter" value="<?= esc_attr($user_filter) ?>" 
                               placeholder="Search users...">
                    </div>
                    <div class="pmv-filter-group">
                        <label>Character Name</label>
                        <input type="text" name="character_filter" value="<?= esc_attr($character_filter) ?>" 
                               placeholder="Search characters...">
                    </div>
                    <div class="pmv-filter-group">
                        <label>Keyword Search</label>
                        <input type="text" name="keyword_search" value="<?= esc_attr($keyword_search) ?>" placeholder="Search by keyword...">
                    </div>
                    <div class="pmv-filter-group">
                        <label>Safety</label>
                        <select name="safety_filter">
                            <option value="" <?= $safety_filter === '' ? 'selected' : '' ?>>All</option>
                            <option value="safe" <?= $safety_filter === 'safe' ? 'selected' : '' ?>>Safe</option>
                            <option value="unsafe" <?= $safety_filter === 'unsafe' ? 'selected' : '' ?>>Unsafe</option>
                        </select>
                    </div>
                    <div class="pmv-filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?= esc_attr($date_from) ?>">
                    </div>
                    <div class="pmv-filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?= esc_attr($date_to) ?>">
                    </div>
                    <div class="pmv-filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="button button-primary">🔍 Filter</button>
                        <a href="?page=png-metadata-viewer&tab=conversations" class="button">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Stats -->
        <div class="pmv-stats">
            📊 Showing <strong><?= count($conversations) ?></strong> of <strong><?= $total_conversations ?></strong> conversations
            <?php if ($current_page > 1 || $total_pages > 1): ?>
                (Page <?= $current_page ?> of <?= $total_pages ?>)
            <?php endif; ?>
            <?php if ($user_filter || $character_filter || $date_from || $date_to || $keyword_search || $safety_filter): ?>
                <em>- Filtered results</em>
            <?php endif; ?>
        </div>
        
        <!-- Conversations Table -->
        <?php if (empty($conversations)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <?php if ($user_filter || $character_filter || $date_from || $date_to || $keyword_search || $safety_filter): ?>
                    <h3>🔍 No conversations found matching your filters</h3>
                    <p>Try adjusting your search criteria or <a href="?page=png-metadata-viewer&tab=conversations">clear filters</a>.</p>
                <?php else: ?>
                    <h3>📝 No conversations saved yet</h3>
                    <p>Conversations will appear here when users start chatting with characters.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="pmv-conversations-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">User</th>
                        <th style="width: 150px;">Character</th>
                        <th>Title</th>
                        <th style="width: 80px;">Messages</th>
                        <th style="width: 130px;">Last Updated</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conversations as $conv): ?>
                        <tr>
                            <td>
                                <?php if ($conv->user_id): ?>
                                    <strong><?= esc_html($conv->display_name ?: $conv->user_login) ?></strong>
                                    <?php if ($conv->user_email): ?>
                                        <br><small style="color: #666;"><?= esc_html($conv->user_email) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <em style="color: #999;">Guest User</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
<?php
if (!empty($conv->character_name) && $conv->character_name !== 'Unknown Character') {
    echo esc_html($conv->character_name);
} elseif (!empty($conv->character_id)) {
    echo esc_html($conv->character_id);
} else {
    echo 'Unknown Character';
}
?>
</strong>
                            </td>
                            <td>
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                    <?= esc_html($conv->title ?: 'Untitled Conversation') ?>
                                </div>
                                <small style="color: #666;">
                                    Created: <?= date('M j, Y g:i A', strtotime($conv->created_at)) ?>
                                </small>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: #007cba; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                    <?= intval($conv->message_count) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('M j, Y', strtotime($conv->updated_at)) ?>
                                <br><small style="color: #666;"><?= date('g:i A', strtotime($conv->updated_at)) ?></small>
                            </td>
                            <td>
                                <div class="pmv-conversation-actions">
                                    <button class="pmv-btn view-conversation" 
                                            data-id="<?= esc_attr($conv->id) ?>" 
                                            title="View conversation">
                                        👁️ View
                                    </button>
                                    <button class="pmv-btn pmv-btn-danger delete-conversation" 
                                            data-id="<?= esc_attr($conv->id) ?>" 
                                            title="Delete conversation">
                                        🗑️ Delete
                                    </button>
                                    <a class="pmv-btn export-conversation" data-id="<?= esc_attr($conv->id) ?>" data-nonce="<?= esc_attr($pmv_export_nonce) ?>" href="#" title="Export conversation">⬇️ Export</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pmv-pagination">
                    <?php
                    $base_url = add_query_arg([
                        'page' => 'png-metadata-viewer',
                        'tab' => 'conversations',
                        'user_filter' => $user_filter,
                        'character_filter' => $character_filter,
                        'keyword_search' => $keyword_search,
                        'safety_filter' => $safety_filter,
                        'date_from' => $date_from,
                        'date_to' => $date_to
                    ], admin_url('admin.php'));
                    
                    // Previous page
                    if ($current_page > 1): ?>
                        <a href="<?= esc_url(add_query_arg('conv_page', $current_page - 1, $base_url)) ?>" class="button">« Previous</a>
                    <?php endif;
                    
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="<?= esc_url(add_query_arg('conv_page', 1, $base_url)) ?>" class="button">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif;
                    endif;
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="<?= esc_url(add_query_arg('conv_page', $i, $base_url)) ?>" 
                           class="button <?= $i === $current_page ? 'button-primary' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="<?= esc_url(add_query_arg('conv_page', $total_pages, $base_url)) ?>" class="button"><?= $total_pages ?></a>
                    <?php endif;
                    
                    // Next page
                    if ($current_page < $total_pages): ?>
                        <a href="<?= esc_url(add_query_arg('conv_page', $current_page + 1, $base_url)) ?>" class="button">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Conversation Settings -->
    <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
        <h3>Conversation Management Settings</h3>
        <table class="form-table">
            <tr>
                <th>Allow Guest Conversations</th>
                <td>
                    <input type="checkbox" name="pmv_allow_guest_conversations" value="1" 
                        <?= checked(1, get_option('pmv_allow_guest_conversations', 1)) ?>>
                    <p class="description">Allow non-logged-in users to chat with characters.</p>
                </td>
            </tr>
            <tr>
                <th>Auto-save Conversations</th>
                <td>
                    <input type="checkbox" name="pmv_auto_save_conversations" value="1" 
                        <?= checked(1, get_option('pmv_auto_save_conversations', 1)) ?>>
                    <p class="description">Automatically save user conversations (for logged-in users).</p>
                </td>
            </tr>
            <tr>
                <th>Max Conversations per User</th>
                <td>
                    <input type="number" name="pmv_max_conversations_per_user" 
                        value="<?= esc_attr(get_option('pmv_max_conversations_per_user', 50)) ?>" 
                        min="1" max="1000" step="1">
                    <p class="description">Maximum number of conversations each user can save.</p>
                </td>
            </tr>
            <tr>
                <th>Guest Daily Token Limit</th>
                <td>
                    <input type="number" name="pmv_guest_daily_token_limit" 
                        value="<?= esc_attr(get_option('pmv_guest_daily_token_limit', 10000)) ?>" 
                        min="0" step="1000">
                    <p class="description">Daily token limit for guest users (0 = no limit).</p>
                </td>
            </tr>
            <tr>
                <th>Default User Monthly Limit</th>
                <td>
                    <input type="number" name="pmv_default_user_monthly_limit" 
                        value="<?= esc_attr(get_option('pmv_default_user_monthly_limit', 100000)) ?>" 
                        min="0" step="1000">
                    <p class="description">Default monthly token limit for registered users (0 = no limit).</p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Conversation Detail Modal -->
    <div id="conversation-modal" class="pmv-modal">
        <div class="pmv-modal-content">
            <div class="pmv-modal-header">
                <h3 id="modal-title">Conversation Details</h3>
                <button class="pmv-close">&times;</button>
            </div>
            <div class="pmv-modal-body" id="modal-body">
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner" style="display: inline-block; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p>Loading conversation...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Define AJAX URL for WordPress admin
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery(document).ready(function($) {
        // CSS for spinner animation
        $('<style>').text(`
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `).appendTo('head');
        
        // View conversation
        $('.view-conversation').on('click', function(e) {
            e.preventDefault();
            const conversationId = $(this).data('id');
            const modal = $('#conversation-modal');
            const modalBody = $('#modal-body');
            
            modal.show();
            modalBody.html(`
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner" style="display: inline-block; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p>Loading conversation...</p>
                </div>
            `);
            
            const nonce = '<?= wp_create_nonce("pmv_get_conversation_details") ?>';
            const url = `<?= admin_url('admin-ajax.php') ?>?action=pmv_get_conversation_details&conversation_id=${conversationId}&_wpnonce=${nonce}`;
            
            $.get(url)
                .done(function(response) {
                    if (response.success && response.data) {
                        displayConversationDetails(response.data);
                    } else {
                        modalBody.html(`
                            <div style="text-align: center; padding: 40px; color: #dc3545;">
                                <h4>❌ Error Loading Conversation</h4>
                                <p>${response.data?.message || 'Failed to load conversation details'}</p>
                            </div>
                        `);
                    }
                })
                .fail(function(xhr) {
                    modalBody.html(`
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <h4>❌ Connection Error</h4>
                            <p>Failed to load conversation details. Please try again.</p>
                            <p><small>Error: ${xhr.status} ${xhr.statusText}</small></p>
                        </div>
                    `);
                });
        });
        
        // Display conversation details
        function displayConversationDetails(conversation) {
            let displayName = '';
            if (conversation.character_name && conversation.character_name !== 'Unknown Character') {
                displayName = conversation.character_name;
            } else if (conversation.character_id) {
                displayName = conversation.character_id;
            } else {
                displayName = 'Unknown Character';
            }
            $('#modal-title').text(`Conversation: ${displayName}`);
            
            let html = `
                <div class="pmv-conversation-meta">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                        <div><strong>Character:</strong> ${escapeHtml(conversation.character_name)}</div>
                        <div><strong>User:</strong> ${escapeHtml(conversation.user_display)}</div>
                        <div><strong>Created:</strong> ${conversation.created_at}</div>
                        <div><strong>Updated:</strong> ${conversation.updated_at}</div>
                    </div>
                    <div><strong>Messages:</strong> ${conversation.messages.length}</div>
                </div>
                
                <div style="max-height: 60vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: white;">
            `;
            
            if (conversation.messages.length === 0) {
                html += '<p style="text-align: center; color: #666; font-style: italic;">No messages in this conversation.</p>';
            } else {
                conversation.messages.forEach(function(message, index) {
                    const roleClass = message.role === 'user' ? 'user' : 'assistant';
                    const roleName = message.role === 'user' ? 'User' : conversation.character_name;
                    
                    html += `
                        <div class="pmv-message ${roleClass}">
                            <div class="pmv-message-header">
                                ${escapeHtml(roleName)} • ${message.timestamp}
                            </div>
                            <div class="pmv-message-content">
                                ${escapeHtml(message.content).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
            }
            
            html += '</div>';
            $('#modal-body').html(html);
        }
        
        // Delete conversation
        $('.delete-conversation').on('click', function(e) {
            e.preventDefault();
            const conversationId = $(this).data('id');
            const $button = $(this);
            const $row = $button.closest('tr');
            
            if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
                return;
            }
            
            $button.prop('disabled', true).text('🔄 Deleting...');
            
            $.post(ajaxurl, {
                action: 'pmv_admin_delete_conversation',
                conversation_id: conversationId,
                nonce: '<?= wp_create_nonce("pmv_admin_delete_conversation") ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.pmv-conversations-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to delete conversation'));
                    $button.prop('disabled', false).text('🗑️ Delete');
                }
            })
            .fail(function() {
                alert('Network error. Please try again.');
                $button.prop('disabled', false).text('🗑️ Delete');
            });
        });
        
        // Close modal
        $('.pmv-close, #conversation-modal').on('click', function(e) {
            if (e.target === this) {
                $('#conversation-modal').hide();
            }
        });
        
        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#conversation-modal').hide();
            }
        });
        
        // Export conversation
        $('.export-conversation').on('click', function(e) {
            e.preventDefault();
            var conversationId = $(this).data('id');
            var nonce = $(this).data('nonce');
            var $btn = $(this);
            $btn.prop('disabled', true).text('⬇️ Exporting...');
            $.post(ajaxurl, {
                action: 'pmv_export_conversation',
                conversation_id: conversationId,
                nonce: nonce
            }, function(response) {
                $btn.prop('disabled', false).text('⬇️ Export');
                if (response.success && response.data && response.data.content && response.data.filename) {
                    var blob = new Blob([response.data.content], {type: 'text/plain'});
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Export failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('⬇️ Export');
                alert('Export failed: Network error');
            });
        });
        
        // Utility function
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    });
    </script>
    <?php
}

/**
 * Upload Files tab content
 */
function pmv_upload_files_tab_content() {
    ?>
    <h3>Upload PNG Files</h3>
    <div id="pmv-upload-container">
        <div id="drop-zone" style="border: 2px dashed #ccc; padding: 50px; text-align: center; margin: 20px 0;">
            <p>Drop PNG files here or click to select</p>
            <input type="file" id="file-input" multiple accept=".png" style="display: none;">
            <button type="button" id="select-files" class="button button-primary">Select PNG Files</button>
        </div>
        
        <div id="upload-progress" style="margin: 20px 0;"></div>
        <div id="upload-results" style="margin: 20px 0;"></div>
    </div>
    
    <h3>Register Existing Character Cards</h3>
    <p>If you've uploaded character card files via SCP, FTP, or other direct methods, use this button to register them with the system. The system will automatically scan for new files when you visit this page.</p>
    <div style="margin: 20px 0;">
        <button type="button" id="register-existing-cards" class="button button-secondary">Register Existing Character Cards</button>
        <div id="register-progress" style="margin: 10px 0; display: none;"></div>
        <div id="register-results" style="margin: 10px 0;"></div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Auto-scan for new files on page load
        var registerButton = $('#register-existing-cards');
        var registerProgressDiv = $('#register-progress');
        var registerResultsDiv = $('#register-results');
        
        // Auto-trigger scan on page load (only if not already done)
        if (sessionStorage.getItem('pmv_auto_scan_done') !== 'true') {
            registerButton.trigger('click');
            sessionStorage.setItem('pmv_auto_scan_done', 'true');
        }
    });
    </script>
    
    <h3>Directory Information</h3>
    <div id="directory-info" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
        <p><strong>Character cards should be placed in:</strong></p>
        <code id="png-cards-path">Loading...</code>
        <p><strong>Current file count:</strong> <span id="png-cards-count">Loading...</span> <button type="button" id="refresh-count" class="button button-small" style="margin-left: 10px;">Refresh</button></p>
        <p><strong>Note:</strong> Files uploaded via the admin interface will automatically be placed in this directory. Files uploaded via SCP, FTP, or other direct methods need to be placed here manually and then registered using the button above.</p>
    </div>
    
    <h3>Test Character Cards</h3>
    <p>Test if your character cards are working correctly:</p>
    <div style="margin: 20px 0;">
        <button type="button" id="test-character-cards" class="button button-secondary">Test Character Cards</button>
        <div id="test-progress" style="margin: 10px 0; display: none;"></div>
        <div id="test-results" style="margin: 10px 0;"></div>
    </div>
    
    <h3>Upload Status</h3>
    <div id="upload-status">
        <p>Ready to upload files...</p>
    </div>
    
    <script type="text/javascript">
    // Define AJAX URL for WordPress admin
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery(document).ready(function($) {
        var dropZone = $('#drop-zone');
        var fileInput = $('#file-input');
        var selectButton = $('#select-files');
        var progressDiv = $('#upload-progress');
        var resultsDiv = $('#upload-results');
        var statusDiv = $('#upload-status');
        var registerButton = $('#register-existing-cards');
        var registerProgressDiv = $('#register-progress');
        var registerResultsDiv = $('#register-results');
        var refreshCountButton = $('#refresh-count');
        var testButton = $('#test-character-cards');
        var testProgressDiv = $('#test-progress');
        var testResultsDiv = $('#test-results');
        
        // Load directory information
        loadDirectoryInfo();
        
        // File selection
        selectButton.on('click', function() {
            fileInput.click();
        });
        
        // Refresh count button
        refreshCountButton.on('click', function() {
            loadDirectoryInfo();
        });
        
        // Test character cards button
        testButton.on('click', function() {
            testButton.prop('disabled', true).text('Testing...');
            testProgressDiv.html('<p>Testing character card system...</p>').show();
            testResultsDiv.html('');
            
            // Test by trying to load the first page of character cards
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_character_cards',
                    page: 1,
                    per_page: 1,
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var cards = response.data.cards;
                        if (cards && cards.length > 0) {
                            testResultsDiv.html('<div style="color: green;"><h4>✅ Character Cards Working!</h4><p>Successfully loaded ' + cards.length + ' character card(s).</p><p><strong>Sample card:</strong> ' + cards[0].name + '</p></div>');
                        } else {
                            testResultsDiv.html('<div style="color: orange;"><h4>⚠️ No Character Cards Found</h4><p>The system is working but no character cards were found. Try uploading some cards or registering existing ones.</p></div>');
                        }
                    } else {
                        testResultsDiv.html('<div style="color: red;"><h4>❌ Character Card System Error</h4><p>Error: ' + response.data + '</p><p>Check your server logs for more details.</p></div>');
                    }
                },
                error: function() {
                    testResultsDiv.html('<div style="color: red;"><h4>❌ Network Error</h4><p>Unable to test character cards. Check your server connection and try again.</p></div>');
                },
                complete: function() {
                    testButton.prop('disabled', false).text('Test Character Cards');
                    testProgressDiv.html('<p style="color: green;">Test complete!</p>');
                }
            });
        });
        
        // Function to load directory information
        function loadDirectoryInfo() {
            var pathElement = $('#png-cards-path');
            var countElement = $('#png-cards-count');
            
            // Get the png-cards directory path
            var uploadDir = '<?= wp_upload_dir()['basedir'] ?>';
            var pngCardsPath = uploadDir + '/png-cards/';
            pathElement.text(pngCardsPath);
            
            // Check if directory exists and count files
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_register_existing_cards',
                    nonce: '<?= wp_create_nonce('pmv_register_cards_nonce') ?>',
                    just_count: true
                },
                success: function(response) {
                    if (response.success) {
                        countElement.text(response.data.total_processed + ' PNG files found');
                    } else {
                        countElement.text('Directory not found or empty');
                    }
                },
                error: function() {
                    countElement.text('Unable to check directory');
                }
            });
        }
        
        fileInput.on('change', function() {
            handleFiles(this.files);
        });
        
        // Drag and drop
        dropZone.on('dragover', function(e) {
            e.preventDefault();
            $(this).css('background-color', '#f0f0f0');
        });
        
        dropZone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).css('background-color', '');
        });
        
        dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).css('background-color', '');
            var files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });
        
        // Register existing cards button
        registerButton.on('click', function() {
            if (!confirm('This will scan the png-cards directory and register all existing PNG files. Continue?')) {
                return;
            }
            
            registerButton.prop('disabled', true).text('Processing...');
            registerProgressDiv.html('<p>Scanning for existing character cards...</p>').show();
            registerResultsDiv.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_register_existing_cards',
                    nonce: '<?= wp_create_nonce('pmv_register_cards_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var results = response.data.results;
                        var html = '<h4>Registration Results:</h4><ul>';
                        
                        results.forEach(function(result) {
                            var color = result.status === 'success' ? 'green' : 
                                      (result.status === 'warning' ? 'orange' : 'red');
                            html += '<li style="color: ' + color + ';">' + result.file + ': ' + result.message + '</li>';
                        });
                        
                        html += '</ul>';
                        html += '<p><strong>Summary:</strong> ' + response.data.message + '</p>';
                        
                        registerResultsDiv.html(html);
                        registerProgressDiv.html('<p style="color: green;">Registration complete!</p>');
                    } else {
                        registerResultsDiv.html('<p style="color: red;">Error: ' + response.data + '</p>');
                        registerProgressDiv.html('<p style="color: red;">Registration failed</p>');
                    }
                },
                error: function() {
                    registerResultsDiv.html('<p style="color: red;">Network error during registration</p>');
                    registerProgressDiv.html('<p style="color: red;">Registration failed</p>');
                },
                complete: function() {
                    registerButton.prop('disabled', false).text('Register Existing Character Cards');
                }
            });
        });
        
        function handleFiles(files) {
            var pngFiles = [];
            
            // Filter PNG files
            for (var i = 0; i < files.length; i++) {
                if (files[i].type === 'image/png') {
                    pngFiles.push(files[i]);
                }
            }
            
            if (pngFiles.length === 0) {
                alert('Please select PNG files only.');
                return;
            }
            
            statusDiv.html('<p>Found ' + pngFiles.length + ' PNG files. Processing...</p>');
            progressDiv.html('<div style="width: 100%; background: #f0f0f0; border-radius: 5px;"><div id="progress-bar" style="width: 0%; background: #007bff; height: 20px; border-radius: 5px; transition: width 0.3s;"></div></div>');
            
            uploadFiles(pngFiles);
        }
        
        function uploadFiles(files) {
            var uploaded = 0;
            var total = files.length;
            var results = [];
            
            function uploadNext() {
                if (uploaded >= total) {
                    progressDiv.html('<p style="color: green;">Upload complete!</p>');
                    displayResults(results);
                    return;
                }
                
                var file = files[uploaded];
                var formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'pmv_upload_png');
                formData.append('nonce', '<?= wp_create_nonce('pmv_upload_nonce') ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        uploaded++;
                        var percent = Math.round((uploaded / total) * 100);
                        $('#progress-bar').css('width', percent + '%');
                        
                        if (response.success) {
                            results.push({
                                file: file.name,
                                status: 'success',
                                message: response.data.message || 'Uploaded successfully'
                            });
                        } else {
                            results.push({
                                file: file.name,
                                status: 'error',
                                message: response.data || 'Upload failed'
                            });
                        }
                        
                        uploadNext();
                    },
                    error: function() {
                        uploaded++;
                        results.push({
                            file: file.name,
                            status: 'error',
                            message: 'Network error'
                        });
                        uploadNext();
                    }
                });
            }
            
            uploadNext();
        }
        
        function displayResults(results) {
            var html = '<h4>Upload Results:</h4><ul>';
            results.forEach(function(result) {
                var color = result.status === 'success' ? 'green' : 'red';
                html += '<li style="color: ' + color + ';">' + result.file + ': ' + result.message + '</li>';
            });
            html += '</ul>';
            resultsDiv.html(html);
        }
    });
    </script>
    <?php
}

function pmv_safety_tab_content() {
    ?>
    <h3>Keyword Flagging System</h3>
    <p>Configure automatic flagging of conversations containing specific keywords or phrases. Conversations containing any of the listed keywords will be marked as <strong>Unsafe</strong>.</p>
    <form method="post" action="options.php">
        <?php settings_fields('png_metadata_viewer_settings'); ?>
        <table class="form-table">
            <tr>
                <th>Enable Keyword Flagging</th>
                <td>
                    <input type="checkbox" name="pmv_enable_keyword_flagging" value="1" <?= checked(1, get_option('pmv_enable_keyword_flagging', 1)) ?>>
                    <p class="description">Enable automatic flagging of conversations containing unsafe keywords.</p>
                </td>
            </tr>
            <tr>
                <th>Unsafe Keywords</th>
                <td>
                    <textarea name="pmv_unsafe_keywords" style="width: 100%; height: 150px; max-width: 600px;" placeholder="keyword1, keyword2, phrase one, phrase two"><?= esc_textarea(get_option('pmv_unsafe_keywords', '')) ?></textarea>
                    <p class="description">
                        <strong>Comma-separated list of keywords or phrases</strong> that will flag conversations as "Unsafe".<br>
                        Example: <code>violence, adult content, inappropriate, nsfw, explicit, sexual, drug, weapon</code><br>
                        Current keywords: <strong><?= count(array_filter(array_map('trim', explode(',', get_option('pmv_unsafe_keywords', ''))))) ?></strong>
                    </p>
                </td>
            </tr>
            <tr>
                <th>Case Sensitive Matching</th>
                <td>
                    <input type="checkbox" name="pmv_keyword_case_sensitive" value="1" <?= checked(1, get_option('pmv_keyword_case_sensitive', 0)) ?>>
                    <p class="description">Enable case-sensitive keyword matching. If disabled, "Violence" will match "violence" and "VIOLENCE".</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Safety Settings'); ?>
    </form>

    <h3>Testing & Statistics</h3>
    <div id="pmv-safety-stats" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4>Current Safety Statistics</h4>
        <div id="safety-stats-content">
            <p>Loading statistics...</p>
        </div>
        <button type="button" id="test-keyword-system" class="button button-secondary">🔍 Test Keyword System</button>
        <button type="button" id="refresh-safety-stats" class="button button-secondary">🔄 Refresh Statistics</button>
    </div>

    <script>
    // Define AJAX URL for WordPress admin
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery(document).ready(function($) {
        // Load safety statistics on page load
        loadSafetyStats();
        $('#refresh-safety-stats').click(function() { loadSafetyStats(); });
        $('#test-keyword-system').click(function() {
            var keywords = $('textarea[name="pmv_unsafe_keywords"]').val();
            if (!keywords.trim()) { alert('Please enter some keywords to test first.'); return; }
            var testText = prompt('Enter text to test against keywords:', 'This is a test message with some content.');
            if (testText) { testKeywords(keywords, testText); }
        });
        function loadSafetyStats() {
            $('#safety-stats-content').html('<p>Loading statistics...</p>');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'pmv_get_safety_stats', nonce: '<?= wp_create_nonce('pmv_safety_nonce') ?>' },
                success: function(response) {
                    if (response.success) { displaySafetyStats(response.data); }
                    else { $('#safety-stats-content').html('<p style="color: #dc3545;">Error loading statistics: ' + (response.data || 'Unknown error') + '</p>'); }
                },
                error: function() { $('#safety-stats-content').html('<p style="color: #dc3545;">Failed to load statistics.</p>'); }
            });
        }
        function displaySafetyStats(stats) {
            var html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
            html += '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745;"><h5 style="margin: 0 0 5px 0; color: #28a745;">✅ Safe Conversations</h5><span style="font-size: 24px; font-weight: bold;">' + (stats.safe || 0) + '</span></div>';
            html += '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545;"><h5 style="margin: 0 0 5px 0; color: #dc3545;">⚠️ Unsafe Conversations</h5><span style="font-size: 24px; font-weight: bold;">' + (stats.unsafe || 0) + '</span></div>';
            html += '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #007cba;"><h5 style="margin: 0 0 5px 0; color: #007cba;">📊 Total Conversations</h5><span style="font-size: 24px; font-weight: bold;">' + (stats.total || 0) + '</span></div>';
            if (stats.total > 0) { var unsafePercent = Math.round((stats.unsafe / stats.total) * 100); html += '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;"><h5 style="margin: 0 0 5px 0; color: #ffc107;">📈 Unsafe Percentage</h5><span style="font-size: 24px; font-weight: bold;">' + unsafePercent + '%</span></div>'; }
            html += '</div>';
            if (stats.recent_flags && stats.recent_flags.length > 0) {
                html += '<h5 style="margin: 20px 0 10px 0;">Recent Flagged Keywords:</h5>';
                html += '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';
                html += stats.recent_flags.map(function(keyword) { return '<span style="background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 12px; font-size: 12px;">' + keyword + '</span>'; }).join('');
                html += '</div>';
            }
            $('#safety-stats-content').html(html);
        }
        function testKeywords(keywords, testText) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'pmv_test_keywords', keywords: keywords, text: testText, nonce: '<?= wp_create_nonce('pmv_safety_nonce') ?>' },
                success: function(response) {
                    if (response.success) {
                        var result = response.data;
                        var message = 'Test Results:\n\n';
                        message += 'Text: "' + testText + '"\n';
                        message += 'Result: ' + (result.is_unsafe ? '⚠️ UNSAFE' : '✅ SAFE') + '\n';
                        if (result.matched_keywords && result.matched_keywords.length > 0) {
                            message += 'Matched keywords: ' + result.matched_keywords.join(', ') + '\n';
                        }
                        alert(message);
                    } else { alert('Test failed: ' + (response.data || 'Unknown error')); }
                },
                error: function() { alert('Test failed: Network error'); }
            });
        }
    });
    </script>
    <?php
}

/**
 * SwarmUI tab content
 */
function pmv_swarmui_tab_content() {
    ?>
    <h3>Image Generation Provider</h3>
        <table class="form-table">
            <tr>
                <th>Active Provider</th>
                <td>
                    <select name="pmv_image_provider" style="width: 300px;">
                        <option value="swarmui" <?= selected(get_option('pmv_image_provider', 'swarmui'), 'swarmui') ?>>SwarmUI</option>
                        <option value="nanogpt" <?= selected(get_option('pmv_image_provider', 'swarmui'), 'nanogpt') ?>>Nano-GPT</option>
                    </select>
                    <p class="description">Select which image generation provider to use.</p>
                </td>
            </tr>
        </table>
        
        <h3>SwarmUI API Settings</h3>
        <table class="form-table">
            <tr>
                <th>Enable SwarmUI Integration</th>
                <td>
                    <input type="checkbox" name="pmv_swarmui_enabled" 
                        value="1" <?= checked(get_option('pmv_swarmui_enabled', 0), 1, false) ?>>
                    <p class="description">Enable image generation via SwarmUI API.</p>
                </td>
            </tr>
            <tr>
                <th>SwarmUI API URL</th>
                <td>
                    <input type="url" name="pmv_swarmui_api_url" 
                        value="<?= esc_attr(get_option('pmv_swarmui_api_url', '')) ?>" 
                        style="width: 400px;">
                    <p class="description">Base URL for your SwarmUI instance (e.g., http://localhost:8188).</p>
                </td>
            </tr>
            <tr>
                <th>SwarmUI API Key</th>
                <td>
                    <input type="password" name="pmv_swarmui_api_key" 
                        value="<?= esc_attr(get_option('pmv_swarmui_api_key', '')) ?>" 
                        style="width: 400px;">
                    <p class="description">API key for SwarmUI (if required).</p>
                </td>
            </tr>
            <tr>
                <th>SwarmUI User Token</th>
                <td>
                    <input type="password" name="pmv_swarmui_user_token" 
                        value="<?= esc_attr(get_option('pmv_swarmui_user_token', '')) ?>" 
                        style="width: 400px;">
                    <p class="description">User token for SwarmUI authentication. Get this from your SwarmUI web interface by logging in and checking browser cookies for 'swarm_user_token'.</p>
                </td>
            </tr>
            <tr>
                <th>Default Model</th>
                <td>
                    <input type="text" name="pmv_swarmui_default_model" 
                        value="<?= esc_attr(get_option('pmv_swarmui_default_model', 'OfficialStableDiffusion/sd_xl_base_1.0')) ?>" 
                        style="width: 400px;">
                    <p class="description">Default model to use for image generation.</p>
                </td>
            </tr>
        </table>
        
        <h3>Nano-GPT API Settings</h3>
        <table class="form-table">
            <tr>
                <th>Enable Nano-GPT Integration</th>
                <td>
                    <input type="checkbox" name="pmv_nanogpt_enabled" 
                        value="1" <?= checked(get_option('pmv_nanogpt_enabled', 0), 1, false) ?>>
                    <p class="description">Enable image generation via Nano-GPT API.</p>
                </td>
            </tr>
            <tr>
                <th>Nano-GPT API URL</th>
                <td>
                    <input type="url" name="pmv_nanogpt_api_url" 
                        value="<?= esc_attr(get_option('pmv_nanogpt_api_url', 'https://nano-gpt.com/api')) ?>" 
                        style="width: 400px;">
                    <p class="description">Base URL for Nano-GPT API (default: https://nano-gpt.com/api).</p>
                </td>
            </tr>
            <tr>
                <th>Nano-GPT API Key</th>
                <td>
                    <input type="password" name="pmv_nanogpt_api_key" 
                        value="<?= esc_attr(get_option('pmv_nanogpt_api_key', '')) ?>" 
                        style="width: 400px;">
                    <p class="description">API key for Nano-GPT. Get this from your Nano-GPT account.</p>
                </td>
            </tr>
            <tr>
                <th>Default Model</th>
                <td>
                    <input type="text" name="pmv_nanogpt_default_model" 
                        value="<?= esc_attr(get_option('pmv_nanogpt_default_model', 'recraft-v3')) ?>" 
                        style="width: 400px;">
                    <p class="description">Default model to use for image generation (e.g., recraft-v3).</p>
                </td>
            </tr>
        </table>
        
        <h3>Usage Limits</h3>
        <table class="form-table">
            <tr>
                <th>Global Daily Limit</th>
                <td>
                    <input type="number" name="pmv_swarmui_global_daily_limit" 
                        value="<?= esc_attr(get_option('pmv_swarmui_global_daily_limit', 100)) ?>" 
                        min="1" max="10000" step="1">
                    <p class="description">Maximum images that can be generated across all users per day.</p>
                </td>
            </tr>
            <tr>
                <th>Global Monthly Limit</th>
                <td>
                    <input type="number" name="pmv_swarmui_global_monthly_limit" 
                        value="<?= esc_attr(get_option('pmv_swarmui_global_monthly_limit', 1000)) ?>" 
                        min="1" max="100000" step="1">
                    <p class="description">Maximum images that can be generated across all users per month.</p>
                </td>
            </tr>
            <tr>
                <th>User Daily Limit</th>
                <td>
                    <input type="number" name="pmv_swarmui_user_daily_limit" 
                        value="<?= esc_attr(get_option('pmv_swarmui_user_daily_limit', 10)) ?>" 
                        min="1" max="1000" step="1">
                    <p class="description">Maximum images that can be generated per user per day.</p>
                </td>
            </tr>
            <tr>
                <th>User Monthly Limit</th>
                <td>
                    <input type="number" name="pmv_swarmui_user_monthly_limit" 
                        value="<?= esc_attr(get_option('pmv_swarmui_user_monthly_limit', 100)) ?>" 
                        min="1" max="10000" step="1">
                    <p class="description">Maximum images that can be generated per user per month.</p>
                </td>
            </tr>
            <tr>
                <th>Guest Daily Limit</th>
                <td>
                    <input type="number" name="pmv_swarmui_guest_daily_limit" 
                        value="<?= esc_attr(get_option('pmv_swarmui_guest_daily_limit', 5)) ?>" 
                        min="1" max="100" step="1">
                    <p class="description">Maximum images that can be generated per guest user per day.</p>
                </td>
            </tr>
        </table>
        
        <h3>Image Generation Settings</h3>
        <table class="form-table">
            <tr>
                <th>Auto-trigger Keywords</th>
                <td>
                    <textarea name="pmv_swarmui_auto_trigger_keywords" 
                        style="width: 400px; height: 100px;"><?= esc_textarea(get_option('pmv_swarmui_auto_trigger_keywords', 'generate image, create image, draw, picture, photo')) ?></textarea>
                    <p class="description">Comma-separated keywords that will automatically trigger image prompt generation from chat history.</p>
                </td>
            </tr>
            <tr>
                <th>Allow Prompt Editing</th>
                <td>
                    <input type="checkbox" name="pmv_swarmui_allow_prompt_editing" 
                        value="1" <?= checked(get_option('pmv_swarmui_allow_prompt_editing', 1), 1, false) ?>>
                    <p class="description">Allow users to edit the generated image prompt before sending to the selected provider.</p>
                </td>
            </tr>
            <tr>
                <th>Default Image Parameters</th>
                <td>
                    <p>Steps: <input type="number" name="pmv_swarmui_default_steps" value="<?= esc_attr(get_option('pmv_swarmui_default_steps', 20)) ?>" min="1" max="100" style="width: 80px;"></p>
                    <p>CFG Scale: <input type="number" name="pmv_swarmui_default_cfg_scale" value="<?= esc_attr(get_option('pmv_swarmui_default_cfg_scale', 7.0)) ?>" min="0.1" max="20" step="0.1" style="width: 80px;"></p>
                    <p>Width: <input type="number" name="pmv_swarmui_default_width" value="<?= esc_attr(get_option('pmv_swarmui_default_width', 512)) ?>" min="256" max="2048" step="64" style="width: 80px;"></p>
                    <p>Height: <input type="number" name="pmv_swarmui_default_height" value="<?= esc_attr(get_option('pmv_swarmui_default_height', 512)) ?>" min="256" max="2048" step="64" style="width: 80px;"></p>
                </td>
            </tr>
        </table>
        
        <h3>Test Connections</h3>
    <div class="card" style="padding: 15px; margin-top: 20px;">
        <p>Test your API connections:</p>
        <button type="button" id="test-swarmui-connection" class="button button-secondary">Test SwarmUI Connection</button>
        <button type="button" id="test-nanogpt-connection" class="button button-secondary">Test Nano-GPT Connection</button>
        <div id="swarmui-test-result" style="margin-top: 10px;"></div>
        <div id="nanogpt-test-result" style="margin-top: 10px;"></div>
    </div>
    
    <script>
    // Define AJAX URL for WordPress admin
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery(document).ready(function($) {
        $('#test-swarmui-connection').on('click', function() {
            var button = $(this);
            var resultDiv = $('#swarmui-test-result');
            
            button.prop('disabled', true).text('Testing...');
            resultDiv.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_swarmui_connection',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var html = '<div style="color: green; margin-top: 10px;">';
                        html += '<h4>✓ SwarmUI Connection successful!</h4>';
                        html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                        html += '<li><strong>Models found:</strong> ' + response.data.models_count + '</li>';
                        html += '<li><strong>API URL:</strong> ' + response.data.api_url + '</li>';
                        html += '<li><strong>Session ID:</strong> ' + response.data.session_id + '</li>';
                        html += '<li><strong>Authentication:</strong> ' + (response.data.has_auth ? 'Enabled' : 'Disabled') + '</li>';
                        
                        if (response.data.model_load_test) {
                            html += '<li><strong>Model loading test:</strong> ' + response.data.model_load_test + '</li>';
                        }
                        
                        if (response.data.folders && response.data.folders.length > 0) {
                            html += '<li><strong>Folders:</strong> ' + response.data.folders.join(', ') + '</li>';
                        }
                        
                        html += '</ul>';
                        
                        // Show first few models if available
                        if (response.data.models && response.data.models.length > 0) {
                            html += '<div style="margin-top: 10px;"><strong>Sample models:</strong><ul style="margin: 5px 0; padding-left: 20px;">';
                            for (var i = 0; i < Math.min(5, response.data.models.length); i++) {
                                html += '<li>' + response.data.models[i].name + '</li>';
                            }
                            if (response.data.models.length > 5) {
                                html += '<li>... and ' + (response.data.models.length - 5) + ' more</li>';
                            }
                            html += '</ul></div>';
                        }
                        
                        html += '</div>';
                        resultDiv.html(html);
                    } else {
                        var errorMsg = response.data.message || 'Unknown error';
                        var details = '';
                        if (response.data.details) {
                            details = '<br><small>Details: ' + JSON.stringify(response.data.details) + '</small>';
                        }
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ SwarmUI Connection failed: ' + errorMsg + details + '</div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div style="color: red; margin-top: 10px;">✗ SwarmUI Connection failed: Network error</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test SwarmUI Connection');
                }
            });
        });
        
        $('#test-nanogpt-connection').on('click', function() {
            var button = $(this);
            var resultDiv = $('#nanogpt-test-result');
            
            button.prop('disabled', true).text('Testing...');
            resultDiv.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_test_nanogpt_connection',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div style="color: green; margin-top: 10px;">✓ Nano-GPT Connection successful! Available models: ' + response.data.models_count + '</div>');
                    } else {
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Nano-GPT Connection failed: ' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Nano-GPT Connection failed: Network error</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Nano-GPT Connection');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Subscriptions tab content
 */
function pmv_subscriptions_tab_content() {
    // Check if WooCommerce is active
    $woocommerce_active = class_exists('WooCommerce');
    $subscription_system = null;
    
    if ($woocommerce_active && class_exists('PMV_Subscription_System')) {
        $subscription_system = PMV_Subscription_System::getInstance();
    }
    ?>
    
    <h3>Subscription System Status</h3>
    <table class="form-table">
        <tr>
            <th>WooCommerce Status</th>
            <td>
                <?php if ($woocommerce_active): ?>
                    <span style="color: green;">✓ Active</span>
                <?php else: ?>
                    <span style="color: red;">✗ Not Active</span>
                    <p class="description">WooCommerce must be installed and activated to use the subscription system.</p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Subscription System</th>
            <td>
                <?php if ($subscription_system): ?>
                    <span style="color: green;">✓ Loaded</span>
                <?php else: ?>
                    <span style="color: red;">✗ Not Available</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
            <?php if ($subscription_system): ?>
            <h3>Credit System Configuration</h3>
            
            <!-- Image Credits Settings -->
            <h4>Image Credits</h4>
            <table class="form-table">
                <tr>
                    <th>Product Name</th>
                    <td>
                        <input type="text" name="pmv_image_product_name" 
                            value="<?= esc_attr(get_option('pmv_image_product_name', 'Image Credits')) ?>" 
                            style="width: 300px;">
                        <p class="description">Name of the image credits product</p>
                    </td>
                </tr>
                <tr>
                    <th>Credits per Dollar</th>
                    <td>
                        <input type="number" name="pmv_image_credits_per_dollar" 
                            value="<?= esc_attr(get_option('pmv_image_credits_per_dollar', 100)) ?>" 
                            min="1" max="10000" step="1">
                        <p class="description">How many image credits a user gets for $1</p>
                    </td>
                </tr>
                <tr>
                    <th>Product Description</th>
                    <td>
                        <textarea name="pmv_image_product_description" 
                            style="width: 400px; height: 80px;"><?= esc_textarea(get_option('pmv_image_product_description', 'Purchase image generation credits. Credits never expire and are cumulative.')) ?></textarea>
                        <p class="description">Description of what the image credits product includes</p>
                    </td>
                </tr>
            </table>
            
            <!-- Text Credits Settings -->
            <h4>Text Credits</h4>
            <table class="form-table">
                <tr>
                    <th>Product Name</th>
                    <td>
                        <input type="text" name="pmv_text_product_name" 
                            value="<?= esc_attr(get_option('pmv_text_product_name', 'Text Credits')) ?>" 
                            style="width: 300px;">
                        <p class="description">Name of the text credits product</p>
                    </td>
                </tr>
                <tr>
                    <th>Credits per Dollar</th>
                    <td>
                        <input type="number" name="pmv_text_credits_per_dollar" 
                            value="<?= esc_attr(get_option('pmv_text_credits_per_dollar', 10000)) ?>" 
                            min="1000" max="1000000" step="1000">
                        <p class="description">How many text tokens a user gets for $1</p>
                    </td>
                </tr>
                <tr>
                    <th>Product Description</th>
                    <td>
                        <textarea name="pmv_text_product_description" 
                            style="width: 400px; height: 80px;"><?= esc_textarea(get_option('pmv_text_product_description', 'Purchase text generation tokens. Tokens never expire and are cumulative.')) ?></textarea>
                        <p class="description">Description of what the text credits product includes</p>
                    </td>
                </tr>
            </table>
            
    
        
        <h3>Daily Login Rewards</h3>
        <table class="form-table">
            <tr>
                <th>Enable Daily Rewards</th>
                <td>
                    <label>
                        <input type="checkbox" name="pmv_daily_reward_enabled" value="1" 
                            <?= checked(1, get_option('pmv_daily_reward_enabled', 1), false) ?>>
                        Give users free credits every day they log in
                    </label>
                    <p class="description">This encourages daily engagement by giving users free credits each day they visit.</p>
                </td>
            </tr>
            <tr>
                <th>Daily Image Credit Reward</th>
                <td>
                    <input type="number" name="pmv_daily_image_reward" 
                        value="<?= esc_attr(get_option('pmv_daily_image_reward', 10)) ?>" 
                        min="0" max="100" step="1">
                    <p class="description">Number of free image credits given daily (0 to disable).</p>
                </td>
            </tr>
            <tr>
                <th>Daily Text Credit Reward</th>
                <td>
                    <input type="number" name="pmv_daily_text_reward" 
                        value="<?= esc_attr(get_option('pmv_daily_text_reward', 1000)) ?>" 
                        min="0" max="10000" step="100">
                    <p class="description">Number of free text credits given daily (0 to disable).</p>
                </td>
            </tr>
            
            <tr>
                <th>Maximum Free Token Credits</th>
                <td>
                    <input type="number" name="pmv_max_free_token_credits" 
                        value="<?= esc_attr(get_option('pmv_max_free_token_credits', 100000)) ?>" 
                        min="0" max="1000000" step="1000">
                    <p class="description">Maximum free token credits a user can accumulate from daily rewards (0 = no limit).</p>
                </td>
            </tr>
            
            <tr>
                <th>Maximum Free Image Credits</th>
                <td>
                    <input type="number" name="pmv_max_free_image_credits" 
                        value="<?= esc_attr(get_option('pmv_max_free_image_credits', 1000)) ?>" 
                        min="0" max="10000" step="10">
                    <p class="description">Maximum free image credits a user can accumulate from daily rewards (0 = no limit).</p>
                </td>
            </tr>
            
            <tr>
                <th>Current User Free Credit Status</th>
                <td>
                    <button type="button" id="check-free-credit-status" class="button button-secondary">Check Current User Status</button>
                    <div id="free-credit-status-result" style="margin-top: 10px;"></div>
                    <p class="description">Check how many free credits the current user has accumulated vs. the cap limits.</p>
                </td>
            </tr>
        </table>
        
        <h3>Guest User Limits</h3>
        <table class="form-table">
            <tr>
                <th>Guest Daily Image Limit</th>
                <td>
                    <input type="number" name="pmv_guest_daily_limit" 
                        value="<?= esc_attr(get_option('pmv_guest_daily_limit', 50)) ?>" 
                        min="1" max="1000" step="1">
                    <p class="description">Maximum images per day for non-logged-in users.</p>
                </td>
            </tr>
            <tr>
                <th>Guest Monthly Token Limit</th>
                <td>
                    <input type="number" name="pmv_guest_monthly_limit" 
                        value="<?= esc_attr(get_option('pmv_guest_monthly_limit', 10000)) ?>" 
                        min="1000" max="1000000" step="1000">
                    <p class="description">Maximum tokens per month for non-logged-in users.</p>
                </td>
            </tr>
        </table>
        
        <h3>Access Plan Management</h3>
        <div class="card" style="padding: 15px; margin-top: 20px;">
            <p>Manage access plan products and settings:</p>
            <button type="button" id="recreate-subscription-products" class="button button-secondary">Recreate Access Plan Products</button>
            <button type="button" id="check-subscription-status" class="button button-secondary">Check System Status</button>
            <div id="subscription-management-result" style="margin-top: 10px;"></div>
        </div>
        
        <script>
        // Define AJAX URL for WordPress admin
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        jQuery(document).ready(function($) {
            $('#recreate-subscription-products').on('click', function() {
                var button = $(this);
                var resultDiv = $('#subscription-management-result');
                
                button.prop('disabled', true).text('Recreating...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_recreate_subscription_products',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: green; margin-top: 10px;">✓ Subscription products recreated successfully!</div>');
                        } else {
                            resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Failed to recreate subscription products: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Failed to recreate subscription products: Network error</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Recreate Subscription Products');
                    }
                });
            });
            
            $('#check-subscription-status').on('click', function() {
                var button = $(this);
                var resultDiv = $('#subscription-management-result');
                
                button.prop('disabled', true).text('Checking...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_check_subscription_status',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div style="color: green; margin-top: 10px;">✓ System Status Check Complete</div>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            html += '<li><strong>WooCommerce:</strong> Active</li>';
                            html += '<li><strong>Subscription System:</strong> Loaded</li>';
                            html += '<li><strong>Products Created:</strong> ' + response.data.products_count + '</li>';
                            html += '<li><strong>Category Created:</strong> ' + (response.data.category_created ? 'Yes' : 'No') + '</li>';
                            html += '</ul>';
                            resultDiv.html(html);
                        } else {
                            resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Status check failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Status check failed: Network error</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Check System Status');
                    }
                });
            });
        });
        </script>
        
        <h3>Credit System Debugging</h3>
        <table class="form-table">
            <tr>
                <th>Check Credit Products</th>
                <td>
                    <button type="button" id="check-credit-status" class="button button-secondary">Check Credit System Status</button>
                    <div id="credit-status-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Recreate Credit Products</th>
                <td>
                    <button type="button" id="recreate-credit-products" class="button button-primary">Force Recreate Credit Products</button>
                    <p class="description">This will delete and recreate the credit products. Use if products are showing as out of stock.</p>
                </td>
            </tr>
            <tr>
                <th>Manual Credit Addition</th>
                <td>
                    <p><strong>Add credits to a specific user:</strong></p>
                    <p>
                        <label>User ID: <input type="number" id="manual-user-id" min="1" style="width: 80px;"></label>
                        <label>Credit Type: 
                            <select id="manual-credit-type">
                                <option value="image">Image Credits</option>
                                <option value="text">Text Credits</option>
                            </select>
                        </label>
                        <label>Amount: <input type="number" id="manual-amount" min="1" style="width: 80px;"></label>
                        <button type="button" id="add-manual-credits" class="button button-secondary">Add Credits</button>
                    </p>
                    <div id="manual-credit-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Check Order Credit Status</th>
                <td>
                    <p><strong>Check if an order was processed for credits:</strong></p>
                    <p>
                        <label>Order ID: <input type="number" id="check-order-id" min="1" style="width: 80px;"></label>
                        <button type="button" id="check-order-credits" class="button button-secondary">Check Order</button>
                    </p>
                    <div id="order-credit-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Manually Process Order Credits</th>
                <td>
                    <p><strong>Force process credits for an existing order:</strong></p>
                    <p>
                        <label>Order ID: <input type="number" id="process-order-id" min="1" style="width: 80px;"></label>
                        <button type="button" id="process-order-credits" class="button button-primary">Process Credits</button>
                    </p>
                    <p class="description">Use this if credits weren't added when the order was completed. This will process the order again.</p>
                    <div id="process-order-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Test Daily Reward</th>
                <td>
                    <p><strong>Manually trigger daily reward for testing:</strong></p>
                    <p>
                        <label>User ID: <input type="number" id="daily-reward-user-id" min="1" style="width: 80px;" placeholder="Leave empty for current user"></label>
                        <button type="button" id="trigger-daily-reward" class="button button-secondary">Trigger Daily Reward</button>
                    </p>
                    <p class="description">Use this to test the daily reward system. This will give the user their daily credits.</p>
                    <div id="daily-reward-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Test WooCommerce Hooks</th>
                <td>
                    <p><strong>Test if WooCommerce hooks are working properly:</strong></p>
                    <button type="button" id="test-woocommerce-hooks" class="button button-secondary">Test WooCommerce Hooks</button>
                    <p class="description">Use this to test if the WooCommerce order hooks are properly registered and working.</p>
                    <div id="woocommerce-hooks-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Test Hook System & Force Register</th>
                <td>
                    <p><strong>Comprehensive hook system test and force registration:</strong></p>
                    <button type="button" id="test-hook-system" class="button button-primary">Test Hook System & Force Register</button>
                    <p class="description">Use this to test the entire hook system, force register hooks if needed, and verify everything is working. This will attempt to fix any hook registration issues.</p>
                    <div id="hook-system-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Basic AJAX Test</th>
                <td>
                    <p><strong>Test basic AJAX functionality:</strong></p>
                    <button type="button" id="test-ajax-basic" class="button button-secondary">Test Basic AJAX</button>
                    <p class="description">Use this to test if the basic AJAX system is working before testing the more complex hook system.</p>
                    <div id="ajax-basic-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Manually Trigger Order Processing</th>
                <td>
                    <p><strong>Force process an order for credits:</strong></p>
                    <p>
                        <label>Order ID: <input type="number" id="trigger-order-id" min="1" style="width: 80px;"></label>
                        <button type="button" id="trigger-order-processing" class="button button-primary">Trigger Processing</button>
                    </p>
                    <p class="description">Use this to manually trigger credit processing for an order. Useful if automatic processing failed.</p>
                    <div id="trigger-order-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Debug Order Credits</th>
                <td>
                    <p><strong>Debug credit processing for a specific order:</strong></p>
                    <p>
                        <label>Order ID: <input type="number" id="debug-order-id" min="1" style="width: 80px;"></label>
                        <button type="button" id="debug-order-credits" class="button button-secondary">Debug Order</button>
                    </p>
                    <p class="description">Use this to debug why credits weren't added for a specific order.</p>
                    <div id="debug-order-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Check Processing Orders</th>
                <td>
                    <p><strong>Check for processing orders that need credits:</strong></p>
                    <button type="button" id="check-processing-orders" class="button button-primary">Check Processing Orders</button>
                    <p class="description">Use this to find and process credits for orders with 'processing' status that might have been missed.</p>
                    <div id="processing-orders-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Process Specific Order</th>
                <td>
                    <p><strong>Manually process credits for a specific order:</strong></p>
                    <p>
                        <label>Order ID: <input type="number" id="process-specific-order-id" min="1" style="width: 80px;"></label>
                        <button type="button" id="process-specific-order" class="button button-primary">Process Order</button>
                    </p>
                    <p class="description">Use this to manually process credits for a specific order ID. Useful for testing order 11744.</p>
                    <div id="process-specific-order-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Comprehensive Order Audit</th>
                <td>
                    <p><strong>Audit all orders to find any that need credits processed:</strong></p>
                    <button type="button" id="audit-all-orders" class="button button-primary">Audit All Orders</button>
                    <p class="description">Use this to scan all orders and identify any that need credits processed. This will process all missed orders automatically.</p>
                    <div id="audit-all-orders-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Test Hook Execution</th>
                <td>
                    <p><strong>Test if WooCommerce hooks are actually firing:</strong></p>
                    <button type="button" id="test-hook-execution" class="button button-secondary">Test Hook Execution</button>
                    <p class="description">Use this to test if the WooCommerce hooks are actually being triggered and executing properly.</p>
                    <div id="hook-execution-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
            <tr>
                <th>Process New Orders</th>
                <td>
                    <p><strong>Manually process credits for new orders:</strong></p>
                    <button type="button" id="process-new-orders" class="button button-primary">Process New Orders</button>
                    <p class="description">Use this to immediately process credits for new orders that might not have been processed automatically. This checks orders from the last 2 hours.</p>
                    <div id="new-orders-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
        </table>
        
        <script>
        // Define AJAX URL for WordPress admin
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        jQuery(document).ready(function($) {
            // Check credit system status
            $('#check-credit-status').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Checking...');
                
                $.post(ajaxurl, {
                    action: 'pmv_check_subscription_status',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<div class="notice notice-info"><p><strong>Credit System Status:</strong></p>';
                        html += '<p>Category Created: ' + (data.category_created ? 'Yes' : 'No') + '</p>';
                        html += '<p>Image Credits Product: ' + (typeof data.product_status.image === 'object' ? 'Active (ID: ' + data.product_status.image.id + ')' : 'Not Found') + '</p>';
                        html += '<p>Text Credits Product: ' + (typeof data.product_status.text === 'object' ? 'Active (ID: ' + data.product_status.text.id + ')' : 'Not Found') + '</p>';
                        html += '<p>Credit Rates: $1 = ' + data.credit_rates.image_credits_per_dollar + ' image credits, $1 = ' + data.credit_rates.text_credits_per_dollar + ' text credits</p>';
                        html += '</div>';
                        $('#credit-status-result').html(html);
                    } else {
                        $('#credit-status-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#credit-status-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Check Credit System Status');
                });
            });
            
            // Recreate credit products
            $('#recreate-credit-products').click(function() {
                if (!confirm('This will delete and recreate all credit products. Continue?')) {
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Recreating...');
                
                $.post(ajaxurl, {
                    action: 'pmv_recreate_subscription_products',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#credit-status-result').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        $('#credit-status-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#credit-status-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Force Recreate Credit Products');
                });
            });
            
            // Add manual credits
            $('#add-manual-credits').click(function() {
                var userId = $('#manual-user-id').val();
                var creditType = $('#manual-credit-type').val();
                var amount = $('#manual-amount').val();
                
                if (!userId || !amount) {
                    alert('Please fill in all fields');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Adding...');
                
                $.post(ajaxurl, {
                    action: 'pmv_manually_add_credits',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    user_id: userId,
                    credit_type: creditType,
                    amount: amount,
                    reason: 'Admin manual addition'
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                        html += '<p>New Balance: ' + response.data.new_balance.image_credits + ' image credits, ' + response.data.new_balance.text_credits + ' text credits</p></div>';
                        $('#manual-credit-result').html(html);
                    } else {
                        $('#manual-credit-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#manual-credit-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Add Credits');
                });
            });
            
            // Check order credit status
            $('#check-order-credits').click(function() {
                var orderId = $('#check-order-id').val();
                
                if (!orderId) {
                    alert('Please enter an order ID');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Checking...');
                
                $.post(ajaxurl, {
                    action: 'pmv_check_order_credit_status',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    order_id: orderId
                })
                .done(function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<div class="notice notice-info"><p><strong>Order Credit Status:</strong></p>';
                        html += '<p>Order ID: ' + data.order_id + ', User ID: ' + data.user_id + ', Status: ' + data.order_status + '</p>';
                        
                        if (data.items.length > 0) {
                            html += '<p><strong>Items:</strong></p>';
                            data.items.forEach(function(item) {
                                html += '<p>- ' + item.product_name + ' (' + item.credit_type + '): ' + item.credits_to_add + ' credits for $' + item.total_price + '</p>';
                            });
                        }
                        
                        html += '<p><strong>Credits Before:</strong> ' + data.user_credits_before.image_credits + ' image, ' + data.user_credits_before.text_credits + ' text</p>';
                        html += '<p><strong>Credits After:</strong> ' + data.user_credits_after.image_credits + ' image, ' + data.user_credits_after.text_credits + ' text</p>';
                        html += '</div>';
                        $('#order-credit-result').html(html);
                    } else {
                        $('#order-credit-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#order-credit-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Check Order');
                });
            });
            
            // Process order credits
            $('#process-order-credits').click(function() {
                var orderId = $('#process-order-id').val();
                
                if (!orderId) {
                    alert('Please enter an order ID');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Processing...');
                
                $.post(ajaxurl, {
                    action: 'pmv_manually_process_order_credits',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    order_id: orderId
                })
                .done(function(response) {
                    if (response.success) {
                        $('#process-order-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#process-order-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#process-order-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Process Credits');
                });
            });
            
            // Trigger daily reward
            $('#trigger-daily-reward').click(function() {
                var userId = $('#daily-reward-user-id').val();
                
                var button = $(this);
                button.prop('disabled', true).text('Triggering...');
                
                var data = {
                    action: 'pmv_manually_trigger_daily_reward',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                };
                
                if (userId) {
                    data.user_id = userId;
                }
                
                $.post(ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                        html += '<p><strong>New Balance:</strong> ' + response.data.new_balance.image_credits + ' image credits, ' + response.data.new_balance.text_credits + ' text credits</p>';
                        html += '</div>';
                        $('#daily-reward-result').html(html);
                    } else {
                        $('#daily-reward-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#daily-reward-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Trigger Daily Reward');
                });
            });
            
            // Test WooCommerce Hooks
            $('#test-woocommerce-hooks').on('click', function() {
                var button = $(this);
                var resultDiv = $('#woocommerce-hooks-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_test_woocommerce_hooks',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: green; margin-top: 10px;">✓ WooCommerce Hooks working!</div>');
                        } else {
                            resultDiv.html('<div style="color: red; margin-top: 10px;">✗ WooCommerce Hooks not working: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ WooCommerce Hooks test failed: Network error</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test WooCommerce Hooks');
                    }
                });
            });
            
            // Check free credit status
            $('#check-free-credit-status').click(function() {
                var button = $(this);
                var resultDiv = $('#free-credit-status-result');
                
                button.prop('disabled', true).text('Checking...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_check_free_credit_status',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<div class="notice notice-info"><p><strong>Free Credit Status:</strong></p>';
                            html += '<p>Image Credits: ' + data.free_image_credits + ' / ' + data.max_image_credits + ' (Cap: ' + (data.max_image_credits > 0 ? data.max_image_credits : 'No Limit') + ')</p>';
                            html += '<p>Token Credits: ' + data.free_text_credits + ' / ' + data.max_text_credits + ' (Cap: ' + (data.max_text_credits > 0 ? data.max_text_credits : 'No Limit') + ')</p>';
                            if (data.max_image_credits > 0 && data.free_image_credits >= data.max_image_credits) {
                                html += '<p><strong>⚠️ Image credits are at the cap limit!</strong></p>';
                            }
                            if (data.max_text_credits > 0 && data.free_text_credits >= data.max_text_credits) {
                                html += '<p><strong>⚠️ Token credits are at the cap limit!</strong></p>';
                            }
                            html += '</div>';
                            resultDiv.html(html);
                        } else {
                            resultDiv.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div class="notice notice-error"><p>Request failed</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Check Current User Status');
                    }
                });
            });
            
            // Test Hook System & Force Register
            $('#test-hook-system').on('click', function() {
                var button = $(this);
                var resultDiv = $('#hook-system-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_test_hook_system',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<div class="notice notice-success"><p><strong>Hook System Test Successful!</strong></p>';
                            
                            // Display WooCommerce status
                            html += '<p><strong>WooCommerce Status:</strong></p>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            html += '<li>WooCommerce Active: ' + (data.woocommerce_active ? '✓ Yes' : '✗ No') + '</li>';
                            html += '<li>WooCommerce Loaded: ' + (data.woocommerce_loaded ? '✓ Yes' : '✗ No') + '</li>';
                            html += '</ul>';
                            
                            // Display hook status
                            html += '<p><strong>Hook Registration Status:</strong></p>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            html += '<li>Force Register Result: ' + (data.force_register_result ? '✓ Success' : '✗ Failed') + '</li>';
                            html += '<li>Hook Execution Test: ' + (data.hook_execution_test ? '✓ Success' : '✗ Failed') + '</li>';
                            html += '<li>All Hooks Registered: ' + (data.final_hook_status.all_hooks_registered ? '✓ Yes' : '✗ No') + '</li>';
                            html += '<li>Total Hooks: ' + data.final_hook_status.total_hooks + '</li>';
                            html += '<li>Registered Hooks: ' + data.final_hook_status.registered_hooks + '</li>';
                            html += '</ul>';
                            
                            // Display individual hook status
                            html += '<p><strong>Individual Hook Status:</strong></p>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            for (var hook in data.final_hook_status.hooks_status) {
                                var status = data.final_hook_status.hooks_status[hook] ? '✓ Registered' : '✗ Not Registered';
                                html += '<li>' + hook + ': ' + status + '</li>';
                            }
                            html += '</ul>';
                            
                            html += '<p><em>Hooks have been force registered and tested. New orders should now automatically process credits.</em></p>';
                            html += '</div>';
                            
                            resultDiv.html(html);
                        } else {
                            resultDiv.html('<div class="notice notice-error"><p>Hook System Test Failed: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Network error';
                        if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.data) {
                                    errorMessage = response.data;
                                }
                            } catch (e) {
                                errorMessage = 'Response parsing error: ' + xhr.responseText.substring(0, 100);
                            }
                        }
                        resultDiv.html('<div class="notice notice-error"><p>Hook System Test Failed: ' + errorMessage + '</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Hook System & Force Register');
                    }
                });
            });
            
            // Test Basic AJAX
            $('#test-ajax-basic').on('click', function() {
                var button = $(this);
                var resultDiv = $('#ajax-basic-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_test_ajax_basic',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<div class="notice notice-success"><p><strong>Basic AJAX Test Successful!</strong></p>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            html += '<li>Message: ' + data.message + '</li>';
                            html += '<li>Timestamp: ' + data.timestamp + '</li>';
                            html += '<li>User ID: ' + data.user_id + '</li>';
                            html += '<li>WordPress Version: ' + data.wordpress_version + '</li>';
                            html += '<li>PHP Version: ' + data.php_version + '</li>';
                            html += '<li>Memory Limit: ' + data.memory_limit + '</li>';
                            html += '<li>Max Execution Time: ' + data.max_execution_time + '</li>';
                            html += '</ul>';
                            html += '<p><em>Basic AJAX functionality is working correctly.</em></p>';
                            html += '</div>';
                            
                            resultDiv.html(html);
                        } else {
                            resultDiv.html('<div class="notice notice-error"><p>Basic AJAX Test Failed: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Network error';
                        if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.data) {
                                    errorMessage = response.data;
                                }
                            } catch (e) {
                                errorMessage = 'Response parsing error: ' + xhr.responseText.substring(0, 100);
                            }
                        }
                        resultDiv.html('<div class="notice notice-error"><p>Basic AJAX Test Failed: ' + errorMessage + '</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Basic AJAX');
                    }
                });
            });
            
            // Trigger order processing
            $('#trigger-order-processing').click(function() {
                var orderId = $('#trigger-order-id').val();
                
                if (!orderId) {
                    alert('Please enter an order ID');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Triggering...');
                
                $.post(ajaxurl, {
                    action: 'pmv_manually_trigger_order_processing',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    order_id: orderId
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                        if (response.data.new_balance) {
                            html += '<p><strong>New Balance:</strong> ' + response.data.new_balance.image_credits + ' image credits, ' + response.data.new_balance.text_credits + ' text credits</p>';
                        }
                        html += '</div>';
                        $('#trigger-order-result').html(html);
                    } else {
                        $('#trigger-order-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#trigger-order-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Trigger Processing');
                });
            });
            
            // Debug order credits
            $('#debug-order-credits').click(function() {
                var orderId = $('#debug-order-id').val();
                
                if (!orderId) {
                    alert('Please enter an order ID');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Debugging...');
                
                $.post(ajaxurl, {
                    action: 'pmv_debug_order_credits',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    order_id: orderId
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-info"><p><strong>Debug Order Credits:</strong></p>';
                        html += '<p>Order ID: ' + response.data.order_id + ', User ID: ' + response.data.user_id + ', Status: ' + response.data.order_status + '</p>';
                        html += '<p>Contains Credits: ' + (response.data.contains_credits ? 'YES' : 'NO') + '</p>';
                        html += '<p>Already Processed: ' + (response.data.already_processed ? 'YES' : 'NO') + '</p>';
                        
                        if (response.data.credit_items && response.data.credit_items.length > 0) {
                            html += '<p><strong>Credit Items:</strong></p>';
                            response.data.credit_items.forEach(function(item) {
                                html += '<p>- ' + item.product_name + ' (' + item.credit_type + '): ' + item.credits_to_add + ' credits for $' + item.total_price + '</p>';
                            });
                        }
                        
                        html += '<p><strong>Current Credits:</strong> ' + response.data.current_credits.image_credits + ' image, ' + response.data.current_credits.text_credits + ' text</p>';
                        html += '</div>';
                        $('#debug-order-result').html(html);
                    } else {
                        $('#debug-order-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#debug-order-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Debug Order');
                });
            });
            
            // Check processing orders
            $('#check-processing-orders').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Checking...');
                
                $.post(ajaxurl, {
                    action: 'pmv_check_processing_orders',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-info"><p><strong>Processing Orders Check Complete:</strong></p>';
                        html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                        html += '<li>Total Orders: ' + response.data.total_orders + '</li>';
                        html += '<li>Orders with Credits: ' + response.data.orders_with_credits + '</li>';
                        html += '<li>Orders Processed: ' + response.data.orders_processed + '</li>';
                        html += '</ul>';
                        html += '</div>';
                        $('#processing-orders-result').html(html);
                    } else {
                        $('#processing-orders-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#processing-orders-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Check Processing Orders');
                });
            });
            
            // Process specific order
            $('#process-specific-order').click(function() {
                var orderId = $('#process-specific-order-id').val();
                
                if (!orderId) {
                    alert('Please enter an order ID');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Processing...');
                
                $.post(ajaxurl, {
                    action: 'pmv_process_specific_order',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    order_id: orderId
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p><strong>Order Processed Successfully:</strong></p>';
                        html += '<p>Order ID: ' + response.data.order_id + ', User ID: ' + response.data.user_id + ', Status: ' + response.data.order_status + '</p>';
                        
                        if (response.data.credit_items && response.data.credit_items.length > 0) {
                            html += '<p><strong>Credit Items:</strong></p>';
                            response.data.credit_items.forEach(function(item) {
                                html += '<p>- ' + item.product_name + ' (' + item.credit_type + '): ' + item.credits_to_add + ' credits for $' + item.total_price + '</p>';
                            });
                        }
                        
                        html += '<p><strong>Credits Before:</strong> ' + response.data.credits_before.image_credits + ' image, ' + response.data.credits_before.text_credits + ' text</p>';
                        html += '<p><strong>Credits After:</strong> ' + response.data.credits_after.image_credits + ' image, ' + response.data.credits_after.text_credits + ' text</p>';
                        html += '<p><strong>Credits Added:</strong> ' + response.data.credits_added.image + ' image, ' + response.data.credits_added.text + ' text</p>';
                        
                        if (response.data.was_already_processed) {
                            html += '<p><em>Note: This order was already processed before, but has been reprocessed.</em></p>';
                        }
                        
                        html += '</div>';
                        $('#process-specific-order-result').html(html);
                    } else {
                        $('#process-specific-order-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#process-specific-order-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Process Order');
                });
            });
            
            // Comprehensive order audit
            $('#audit-all-orders').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Auditing...');
                
                $.post(ajaxurl, {
                    action: 'pmv_audit_all_orders',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-info"><p><strong>Order Audit Complete:</strong></p>';
                        html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                        html += '<li>Total Orders Audited: ' + response.data.total_orders + '</li>';
                        html += '<li>Orders with Credits: ' + response.data.orders_with_credits + '</li>';
                        html += '<li>Orders Already Processed: ' + response.data.orders_already_processed + '</li>';
                        html += '<li>Orders Newly Processed: ' + response.data.orders_processed + '</li>';
                        html += '</ul>';
                        
                        if (response.data.orders_needing_processing && response.data.orders_needing_processing.length > 0) {
                            html += '<p><strong>Orders That Were Processed:</strong></p>';
                            html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            response.data.orders_needing_processing.forEach(function(order) {
                                html += '<li>Order ' + order.order_id + ' (User ' + order.user_id + ', Status: ' + order.status + ')</li>';
                            });
                            html += '</ul>';
                        }
                        
                        html += '</div>';
                        $('#audit-all-orders-result').html(html);
                    } else {
                        $('#audit-all-orders-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#audit-all-orders-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Audit All Orders');
                });
            });
            
            // Test hook execution
            $('#test-hook-execution').on('click', function() {
                var button = $(this);
                var resultDiv = $('#hook-execution-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_test_hook_execution',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: green; margin-top: 10px;">✓ Hook Execution Test Successful!</div>');
                        } else {
                            // Handle both string and object responses
                            var errorMessage = '';
                            if (typeof response.data === 'string') {
                                errorMessage = response.data;
                            } else if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            } else {
                                errorMessage = 'Unknown error occurred';
                            }
                            resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Hook Execution Test Failed: ' + errorMessage + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: red; margin-top: 10px;">✗ Hook Execution Test Failed: Network error</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Hook Execution');
                    }
                });
            });
            
            // Process new orders
            $('#process-new-orders').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Processing...');
                
                $.post(ajaxurl, {
                    action: 'pmv_manually_process_new_orders',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-info"><p><strong>New Orders Processing Complete:</strong></p>';
                        html += '<ul style="margin: 10px 0; padding-left: 20px;">';
                        html += '<li>Total Recent Orders: ' + response.data.total_orders + '</li>';
                        html += '<li>Orders Checked: ' + response.data.orders_checked + '</li>';
                        html += '<li>Orders with Credits: ' + response.data.orders_with_credits + '</li>';
                        html += '<li>Orders Processed: ' + response.data.orders_processed + '</li>';
                        html += '</ul>';
                        html += '<p><em>This checked orders from the last 2 hours. New orders should now have credits automatically processed.</em></p>';
                        html += '</div>';
                        $('#new-orders-result').html(html);
                    } else {
                        $('#new-orders-result').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#new-orders-result').html('<div class="notice notice-error"><p>Request failed</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Process New Orders');
                });
            });
        });
        </script>
    <?php else: ?>
        <div class="notice notice-warning">
            <p><strong>Subscription System Not Available</strong></p>
            <p>To enable the subscription system:</p>
            <ol>
                <li>Install and activate WooCommerce plugin</li>
                <li>Install and activate WooCommerce Subscriptions plugin (if you want recurring subscriptions)</li>
                <li>Refresh this page</li>
            </ol>
        </div>
    <?php endif; ?>
    
    <?php
}

// Include character settings UI
if (file_exists(plugin_dir_path(__FILE__) . 'character-settings-ui.php')) {
    require_once plugin_dir_path(__FILE__) . 'character-settings-ui.php';
}
