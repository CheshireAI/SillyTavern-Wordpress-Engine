<?php
// includes/admin-settings-ui.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function pmv_layout_tab_content() {
    ?>
    <table class="form-table">
        <tr><th>Modal Background</th><td><input type="color" name="png_metadata_modal_bg_color" value="<?= esc_attr(get_option('png_metadata_modal_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Modal Text Color</th><td><input type="color" name="png_metadata_modal_text_color" value="<?= esc_attr(get_option('png_metadata_modal_text_color', '#000000')) ?>"></td></tr>
        <tr><th>Box Background</th><td><input type="color" name="png_metadata_box_bg_color" value="<?= esc_attr(get_option('png_metadata_box_bg_color', '#f8f9fa')) ?>"></td></tr>
        <tr><th>Card Name Text</th><td><input type="color" name="png_metadata_card_name_text_color" value="<?= esc_attr(get_option('png_metadata_card_name_text_color', '#212529')) ?>"></td></tr>
        <tr><th>Card Name BG</th><td><input type="color" name="png_metadata_card_name_bg_color" value="<?= esc_attr(get_option('png_metadata_card_name_bg_color', '#e9ecef')) ?>"></td></tr>
        <tr><th>Card BG</th><td><input type="color" name="png_metadata_card_bg_color" value="<?= esc_attr(get_option('png_metadata_card_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Card Tags Text</th><td><input type="color" name="png_metadata_card_tags_text_color" value="<?= esc_attr(get_option('png_metadata_card_tags_text_color', '#6c757d')) ?>"></td></tr>
        <tr><th>Card Tags BG</th><td><input type="color" name="png_metadata_card_tags_bg_color" value="<?= esc_attr(get_option('png_metadata_card_tags_bg_color', '#f8f9fa')) ?>"></td></tr>
        <tr><th>Pagination Text</th><td><input type="color" name="png_metadata_pagination_button_text_color" value="<?= esc_attr(get_option('png_metadata_pagination_button_text_color', '#007bff')) ?>"></td></tr>
        <tr><th>Pagination BG</th><td><input type="color" name="png_metadata_pagination_button_bg_color" value="<?= esc_attr(get_option('png_metadata_pagination_button_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Active Page Text</th><td><input type="color" name="png_metadata_pagination_button_active_text_color" value="<?= esc_attr(get_option('png_metadata_pagination_button_active_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Active Page BG</th><td><input type="color" name="png_metadata_pagination_button_active_bg_color" value="<?= esc_attr(get_option('png_metadata_pagination_button_active_bg_color', '#007bff')) ?>"></td></tr>
        <tr><th>Cards Per Page</th><td><input type="number" name="png_metadata_cards_per_page" value="<?= esc_attr(get_option('png_metadata_cards_per_page', 12)) ?>" min="1" max="100"></td></tr>
    </table>
    
    <h3>Filter Settings</h3>
    <table class="form-table">
        <tr><th>Filter 1 Title</th><td><input type="text" name="png_metadata_filter1_title" value="<?= esc_attr(get_option('png_metadata_filter1_title', 'Category')) ?>"></td></tr>
        <tr><th>Filter 1 Values</th><td><textarea name="png_metadata_filter1_list" rows="5" style="width:100%"><?= esc_textarea(get_option('png_metadata_filter1_list', "General\nNSFW\nAnime\nRealistic")) ?></textarea></td></tr>
        <tr><th>Filter 2 Title</th><td><input type="text" name="png_metadata_filter2_title" value="<?= esc_attr(get_option('png_metadata_filter2_title', 'Style')) ?>"></td></tr>
        <tr><th>Filter 2 Values</th><td><textarea name="png_metadata_filter2_list" rows="5" style="width:100%"><?= esc_textarea(get_option('png_metadata_filter2_list', "Digital Art\nPainting\n3D Render\nPhotograph")) ?></textarea></td></tr>
        <tr><th>Filter 3 Title</th><td><input type="text" name="png_metadata_filter3_title" value="<?= esc_attr(get_option('png_metadata_filter3_title', 'Tags')) ?>"></td></tr>
        <tr><th>Filter 3 Values</th><td><textarea name="png_metadata_filter3_list" rows="5" style="width:100%"><?= esc_textarea(get_option('png_metadata_filter3_list', "Fantasy\nSci-Fi\nHorror\nRomance")) ?></textarea></td></tr>
        <tr><th>Filter 4 Title</th><td><input type="text" name="png_metadata_filter4_title" value="<?= esc_attr(get_option('png_metadata_filter4_title', 'Rating')) ?>"></td></tr>
        <tr><th>Filter 4 Values</th><td><textarea name="png_metadata_filter4_list" rows="5" style="width:100%"><?= esc_textarea(get_option('png_metadata_filter4_list', "Safe\nQuestionable\nExplicit")) ?></textarea></td></tr>
    </table>
    <?php
}

function pmv_buttons_tab_content() {
    ?>
    <h3>Main Button</h3>
    <table class="form-table">
        <tr><th>Text Color</th><td><input type="color" name="png_metadata_button_text_color" value="<?= esc_attr(get_option('png_metadata_button_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Background</th><td><input type="color" name="png_metadata_button_bg_color" value="<?= esc_attr(get_option('png_metadata_button_bg_color', '#007bff')) ?>"></td></tr>
        <tr><th>Hover Text</th><td><input type="color" name="png_metadata_button_hover_text_color" value="<?= esc_attr(get_option('png_metadata_button_hover_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Hover BG</th><td><input type="color" name="png_metadata_button_hover_bg_color" value="<?= esc_attr(get_option('png_metadata_button_hover_bg_color', '#0069d9')) ?>"></td></tr>
    </table>
    
    <h3>Secondary Button</h3>
    <table class="form-table">
        <tr><th>Button Text</th><td><input type="text" name="png_metadata_secondary_button_text" value="<?= esc_attr(get_option('png_metadata_secondary_button_text', 'Learn More')) ?>"></td></tr>
        <tr><th>Button Link</th><td><input type="url" name="png_metadata_secondary_button_link" value="<?= esc_attr(get_option('png_metadata_secondary_button_link', '#')) ?>" style="width:100%"></td></tr>
        <tr><th>Text Color</th><td><input type="color" name="png_metadata_secondary_button_text_color" value="<?= esc_attr(get_option('png_metadata_secondary_button_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Background</th><td><input type="color" name="png_metadata_secondary_button_bg_color" value="<?= esc_attr(get_option('png_metadata_secondary_button_bg_color', '#6c757d')) ?>"></td></tr>
        <tr><th>Hover Text</th><td><input type="color" name="png_metadata_secondary_button_hover_text_color" value="<?= esc_attr(get_option('png_metadata_secondary_button_hover_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Hover BG</th><td><input type="color" name="png_metadata_secondary_button_hover_bg_color" value="<?= esc_attr(get_option('png_metadata_secondary_button_hover_bg_color', '#5a6268')) ?>"></td></tr>
    </table>
    
    <h3>Chat Button</h3>
    <table class="form-table">
        <tr><th>Button Text</th><td><input type="text" name="png_metadata_chat_button_text" value="<?= esc_attr(get_option('png_metadata_chat_button_text', 'Chat')) ?>"></td></tr>
        <tr><th>Text Color</th><td><input type="color" name="png_metadata_chat_button_text_color" value="<?= esc_attr(get_option('png_metadata_chat_button_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Background</th><td><input type="color" name="png_metadata_chat_button_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_button_bg_color', '#28a745')) ?>"></td></tr>
        <tr><th>Hover Text</th><td><input type="color" name="png_metadata_chat_button_hover_text_color" value="<?= esc_attr(get_option('png_metadata_chat_button_hover_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Hover BG</th><td><input type="color" name="png_metadata_chat_button_hover_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_button_hover_bg_color', '#218838')) ?>"></td></tr>
    </table>
    <?php
}

function pmv_chat_tab_content() {
    ?>
    <table class="form-table">
        <tr><th>Chat Modal Background</th><td><input type="color" name="png_metadata_chat_modal_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_modal_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Name Text Color</th><td><input type="color" name="png_metadata_chat_modal_name_text_color" value="<?= esc_attr(get_option('png_metadata_chat_modal_name_text_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Name Background</th><td><input type="color" name="png_metadata_chat_modal_name_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_modal_name_bg_color', '#007bff')) ?>"></td></tr>
        <tr><th>Input Text Color</th><td><input type="color" name="png_metadata_chat_input_text_color" value="<?= esc_attr(get_option('png_metadata_chat_input_text_color', '#495057')) ?>"></td></tr>
        <tr><th>Input Background</th><td><input type="color" name="png_metadata_chat_input_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_input_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>User Bubble BG</th><td><input type="color" name="png_metadata_chat_user_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_user_bg_color', '#e2f1ff')) ?>"></td></tr>
        <tr><th>User Text Color</th><td><input type="color" name="png_metadata_chat_user_text_color" value="<?= esc_attr(get_option('png_metadata_chat_user_text_color', '#333333')) ?>"></td></tr>
        <tr><th>Bot Bubble BG</th><td><input type="color" name="png_metadata_chat_bot_bg_color" value="<?= esc_attr(get_option('png_metadata_chat_bot_bg_color', '#f0f0f0')) ?>"></td></tr>
        <tr><th>Bot Text Color</th><td><input type="color" name="png_metadata_chat_bot_text_color" value="<?= esc_attr(get_option('png_metadata_chat_bot_text_color', '#212529')) ?>"></td></tr>
        <tr><th>Chat History Font Size</th><td><input type="number" name="png_metadata_chat_history_font_size" value="<?= esc_attr(get_option('png_metadata_chat_history_font_size', 14)) ?>" min="10" max="24" step="1"> px</td></tr>
    </table>
    <?php
}

function pmv_gallery_tab_content() {
    ?>
    <h3>Card Styling</h3>
    <table class="form-table">
        <tr><th>Card Border Width</th><td><input type="number" name="png_metadata_card_border_width" value="<?= esc_attr(get_option('png_metadata_card_border_width', 1)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Card Border Color</th><td><input type="color" name="png_metadata_card_border_color" value="<?= esc_attr(get_option('png_metadata_card_border_color', '#dee2e6')) ?>"></td></tr>
        <tr><th>Card Border Radius</th><td><input type="number" name="png_metadata_card_border_radius" value="<?= esc_attr(get_option('png_metadata_card_border_radius', 4)) ?>" min="0" step="1"> px</td></tr>
    </table>
    
    <h3>Button Styling</h3>
    <table class="form-table">
        <tr><th>Button Border Width</th><td><input type="number" name="png_metadata_button_border_width" value="<?= esc_attr(get_option('png_metadata_button_border_width', 1)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Button Border Color</th><td><input type="color" name="png_metadata_button_border_color" value="<?= esc_attr(get_option('png_metadata_button_border_color', '#007bff')) ?>"></td></tr>
        <tr><th>Button Border Radius</th><td><input type="number" name="png_metadata_button_border_radius" value="<?= esc_attr(get_option('png_metadata_button_border_radius', 4)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Button Margin</th><td><input type="text" name="png_metadata_button_margin" value="<?= esc_attr(get_option('png_metadata_button_margin', '5px')) ?>"></td></tr>
        <tr><th>Button Padding</th><td><input type="text" name="png_metadata_button_padding" value="<?= esc_attr(get_option('png_metadata_button_padding', '8px 15px')) ?>"></td></tr>
    </table>
    
    <h3>Gallery Container Styling</h3>
    <table class="form-table">
        <tr><th>Gallery Background</th><td><input type="color" name="png_metadata_gallery_bg_color" value="<?= esc_attr(get_option('png_metadata_gallery_bg_color', '#ffffff')) ?>"></td></tr>
        <tr><th>Gallery Padding</th><td><input type="text" name="png_metadata_gallery_padding" value="<?= esc_attr(get_option('png_metadata_gallery_padding', '0px')) ?>"></td></tr>
        <tr><th>Gallery Border Radius</th><td><input type="number" name="png_metadata_gallery_border_radius" value="<?= esc_attr(get_option('png_metadata_gallery_border_radius', 0)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Gallery Border Width</th><td><input type="number" name="png_metadata_gallery_border_width" value="<?= esc_attr(get_option('png_metadata_gallery_border_width', 0)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Gallery Border Color</th><td><input type="color" name="png_metadata_gallery_border_color" value="<?= esc_attr(get_option('png_metadata_gallery_border_color', '#dee2e6')) ?>"></td></tr>
        <tr><th>Gallery Box Shadow</th><td><input type="text" name="png_metadata_gallery_box_shadow" value="<?= esc_attr(get_option('png_metadata_gallery_box_shadow', 'none')) ?>" placeholder="e.g., 0 0 10px rgba(0,0,0,0.1)"></td></tr>
    </table>
    
    <h3>Filters Container Styling</h3>
    <table class="form-table">
        <tr><th>Filters Background</th><td><input type="color" name="png_metadata_filters_bg_color" value="<?= esc_attr(get_option('png_metadata_filters_bg_color', '#f5f5f5')) ?>"></td></tr>
        <tr><th>Filters Padding</th><td><input type="text" name="png_metadata_filters_padding" value="<?= esc_attr(get_option('png_metadata_filters_padding', '15px')) ?>"></td></tr>
        <tr><th>Filters Border Radius</th><td><input type="number" name="png_metadata_filters_border_radius" value="<?= esc_attr(get_option('png_metadata_filters_border_radius', 8)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Filters Border Width</th><td><input type="number" name="png_metadata_filters_border_width" value="<?= esc_attr(get_option('png_metadata_filters_border_width', 0)) ?>" min="0" step="1"> px</td></tr>
        <tr><th>Filters Border Color</th><td><input type="color" name="png_metadata_filters_border_color" value="<?= esc_attr(get_option('png_metadata_filters_border_color', '#dee2e6')) ?>"></td></tr>
        <tr><th>Filters Box Shadow</th><td><input type="text" name="png_metadata_filters_box_shadow" value="<?= esc_attr(get_option('png_metadata_filters_box_shadow', '0 1px 3px rgba(0,0,0,0.1)')) ?>" placeholder="e.g., 0 0 10px rgba(0,0,0,0.1)"></td></tr>
    </table>
    <?php
}

function pmv_openai_tab_content() {
    ?>
    <table class="form-table">
        <tr>
            <th>API Base URL</th>
            <td>
                <input type="url" id="openai_api_base_url" name="openai_api_base_url" value="<?= esc_attr(get_option('openai_api_base_url', 'https://api.openai.com/v1/')) ?>" style="width:100%">
                <p class="description">Base URL for the OpenAI API or compatible service.</p>
                <div id="api-url-preview" style="margin-top:10px; padding:10px; background:#f8f8f8; border:1px solid #ddd; border-radius:4px;">
                    <strong>Final API endpoint will be:</strong> <span id="final-api-url"></span>
                </div>
            </td>
        </tr>
        <tr>
            <th>API Key</th>
            <td>
                <input type="password" name="openai_api_key" value="<?= esc_attr(get_option('openai_api_key')) ?>" style="width:100%">
                <p class="description">Your OpenAI API key or compatible service API key.</p>
            </td>
        </tr>
        <tr>
            <th>Model</th>
            <td>
                <input type="text" name="openai_model" value="<?= esc_attr(get_option('openai_model', 'gpt-3.5-turbo')) ?>" style="width:100%">
                <p class="description">Model identifier (e.g., gpt-3.5-turbo, gpt-4, or a custom model name for alternative services).</p>
            </td>
        </tr>
        <tr>
            <th>Temperature</th>
            <td>
                <input type="number" name="openai_temperature" value="<?= esc_attr(get_option('openai_temperature', 0.7)) ?>" min="0" max="2" step="0.1">
                <p class="description">Controls randomness: 0 is focused/deterministic, 1 is balanced, 2 is more random. Valid range: 0-2.</p>
            </td>
        </tr>
        <tr>
            <th>Max Tokens</th>
            <td>
                <input type="number" name="openai_max_tokens" value="<?= esc_attr(get_option('openai_max_tokens', 1000)) ?>" min="50" max="8000" step="50">
                <p class="description">Maximum tokens to generate in the completion. Default: 1000. Range varies by model.</p>
            </td>
        </tr>
        <tr>
            <th>Presence Penalty</th>
            <td>
                <input type="number" name="openai_presence_penalty" value="<?= esc_attr(get_option('openai_presence_penalty', 0.6)) ?>" min="-2" max="2" step="0.1">
                <p class="description">Positive values penalize new tokens based on whether they appear in the text so far. Range: -2.0 to 2.0.</p>
            </td>
        </tr>
        <tr>
            <th>Frequency Penalty</th>
            <td>
                <input type="number" name="openai_frequency_penalty" value="<?= esc_attr(get_option('openai_frequency_penalty', 0.3)) ?>" min="-2" max="2" step="0.1">
                <p class="description">Positive values penalize new tokens based on their frequency in the text so far. Range: -2.0 to 2.0.</p>
            </td>
        </tr>
        <tr>
            <th>Test Connection</th>
            <td>
                <button type="button" id="pmv-test-api-connection" class="button button-secondary">Test API Connection</button>
                <span id="pmv-test-result" style="margin-left:10px; display:none;"></span>
            </td>
        </tr>
    </table>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function updateApiUrlPreview() {
            let baseUrl = $('#openai_api_base_url').val().trim();
            let finalUrl = '';
            baseUrl = baseUrl.replace(/\/+$/, '');
            
            if (baseUrl.includes('/chat/completions')) {
                finalUrl = baseUrl;
            } else if (baseUrl.includes('/v1')) {
                finalUrl = baseUrl + '/chat/completions';
            } else {
                finalUrl = baseUrl + '/v1/chat/completions';
            }
            
            $('#final-api-url').text(finalUrl);
            $('.notice').remove();
            if (!baseUrl.includes('api.openai.com') && !baseUrl.includes('/v1')) {
                $('#final-api-url').after('<p class="notice" style="color:#d63638; margin-top:5px;">Warning: URL does not contain "/v1" path. Make sure this matches your API provider\'s requirements.</p>');
            }
        }
        
        updateApiUrlPreview();
        $('#openai_api_base_url').on('input change', function() {
            $('.notice').remove();
            updateApiUrlPreview();
        });
        
        $('#pmv-test-api-connection').on('click', function() {
            const $button = $(this);
            const $result = $('#pmv-test-result');
            $button.prop('disabled', true).text('Testing...');
            $result.removeClass('pmv-success pmv-error').hide();
            
            const data = {
                'action': 'pmv_test_api_connection',
                'api_base_url': $('#openai_api_base_url').val(),
                'api_key': $('input[name="openai_api_key"]').val(),
                'model': $('input[name="openai_model"]').val(),
                'nonce': '<?= wp_create_nonce('pmv_test_api_connection') ?>'
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $result.addClass('pmv-success').text('Success! API connection working properly.').show();
                } else {
                    $result.addClass('pmv-error').text('Error: ' + response.data).show();
                }
                $button.prop('disabled', false).text('Test API Connection');
            }).fail(function() {
                $result.addClass('pmv-error').text('Error: Could not perform test. Check browser console for details.').show();
                $button.prop('disabled', false).text('Test API Connection');
            });
        });
    });
    </script>
    
    <style>
    #pmv-test-result.pmv-success { color: #00a32a; font-weight: bold; }
    #pmv-test-result.pmv-error { color: #d63638; font-weight: bold; }
    </style>
    
    <div class="card" style="max-width:800px; margin-top:20px; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:4px;">
        <h3>OpenAI API Configuration</h3>
        <p>The chat feature requires an OpenAI API key or compatible service to function. If you don't have an API key yet, you can sign up at the <a href="https://platform.openai.com/signup" target="_blank">OpenAI website</a>.</p>
        
        <h4>Instructions:</h4>
        <ol>
            <li>Enter your API key in the field above.</li>
            <li>Set the Base URL that matches your API provider:
                <ul>
                    <li>For OpenAI: <code>https://api.openai.com/v1</code> (default)</li>
                    <li>For Azure OpenAI: <code>https://YOUR_RESOURCE_NAME.openai.azure.com/openai/deployments/YOUR_DEPLOYMENT_NAME</code></li>
                    <li>For other providers: Check their documentation</li>
                </ul>
            </li>
            <li>Enter the model name</li>
            <li>Adjust generation parameters</li>
            <li>Test connection before saving</li>
        </ol>
        <p><strong>Note:</strong> Your API key is stored in your WordPress database.</p>
    </div>
    <?php
}

function pmv_upload_files_tab_content() {
    ?>
    <div class="card">
        <h2>Upload PNG Files</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('pmv_file_upload', 'pmv_upload_nonce'); ?>
            <input type="file" name="pmv_png_upload[]" multiple accept=".png" style="margin-bottom:15px;">
            <p class="description">Select multiple PNG files to upload</p>
            <input type="submit" name="pmv_direct_upload_submit" class="button button-primary" value="Upload Files">
        </form>
        
        <?php if (!empty($GLOBALS['pmv_upload_results'])) : ?>
            <div class="notice notice-<?= $GLOBALS['pmv_upload_results']['uploaded'] > 0 ? 'success' : 'error' ?>" style="margin-top:20px;">
                <p>
                    <?php if ($GLOBALS['pmv_upload_results']['uploaded'] > 0) : ?>
                        Successfully uploaded <?= $GLOBALS['pmv_upload_results']['uploaded'] ?> file(s).
                    <?php endif; ?>
                    
                    <?php if (!empty($GLOBALS['pmv_upload_results']['errors'])) : ?>
                        <br>Errors:
                        <ul style="margin:5px 0 0 20px;">
                            <?php foreach ($GLOBALS['pmv_upload_results']['errors'] as $error) : ?>
                                <li><?= esc_html($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-top:20px;">
        <h2>Uploaded Files</h2>
        <?php
        $upload_dir = wp_upload_dir();
        $target_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
        
        if (file_exists($target_dir)) {
            $files = glob($target_dir . '*.png');
            if (!empty($files)) {
                echo '<ul>';
                foreach ($files as $file) {
                    $filename = basename($file);
                    echo '<li>' . esc_html($filename) . ' - ';
                    echo '<a href="' . esc_url(wp_nonce_url(
                        admin_url('admin-post.php?action=pmv_delete_file&file=' . $filename),
                        'pmv_delete_file'
                    )) . '" class="delete" onclick="return confirm(\'Are you sure you want to delete this file?\')">Delete</a>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No files uploaded yet.</p>';
            }
        } else {
            echo '<p>Upload directory not found.</p>';
        }
        ?>
    </div>
    <?php
}
