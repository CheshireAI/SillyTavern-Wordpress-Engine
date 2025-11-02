<?php
/**
 * Character Settings Admin UI
 * 
 * Admin interface for managing character-specific prompt prefix/suffix settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Character Settings tab content
 */
function pmv_character_settings_tab_content() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get all character cards
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    $png_cards = array();
    
    if (file_exists($png_cards_dir)) {
        $files = glob($png_cards_dir . '*.png');
        foreach ($files as $file) {
            $filename = basename($file);
            $metadata = null;
            if (class_exists('PNG_Metadata_Reader')) {
                $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            }
            $name = isset($metadata['data']['name']) ? $metadata['data']['name'] : pathinfo($filename, PATHINFO_FILENAME);
            $png_cards[] = array(
                'filename' => $filename,
                'name' => $name,
                'url' => trailingslashit($upload_dir['baseurl']) . 'png-cards/' . $filename
            );
        }
    }
    
    // Get all existing character settings
    $all_settings = array();
    if (class_exists('PMV_Character_Settings_Manager')) {
        $all_settings = PMV_Character_Settings_Manager::get_all_settings();
    }
    
    // Create a map of filename => settings
    $settings_map = array();
    foreach ($all_settings as $setting) {
        $settings_map[$setting['character_filename']] = $setting;
    }
    ?>
    <div class="wrap">
        <h2>Character Settings</h2>
        <p>Configure prompt prefix and suffix for each character. These will be automatically prepended and appended to all image generation prompts for the selected character.</p>
        
        <div id="character-settings-container">
            <?php if (empty($png_cards)): ?>
                <div class="notice notice-warning">
                    <p><strong>No character cards found.</strong> Please upload character cards first.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Character</th>
                            <th>Character Name</th>
                            <th>Prompt Prefix</th>
                            <th>Prompt Suffix</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($png_cards as $card): ?>
                            <?php 
                            $setting = isset($settings_map[$card['filename']]) ? $settings_map[$card['filename']] : null;
                            $prefix = $setting ? $setting['prompt_prefix'] : '';
                            $suffix = $setting ? $setting['prompt_suffix'] : '';
                            $setting_name = $setting ? $setting['character_name'] : $card['name'];
                            ?>
                            <tr data-filename="<?= esc_attr($card['filename']) ?>">
                                <td>
                                    <img src="<?= esc_url($card['url']) ?>" alt="<?= esc_attr($card['name']) ?>" style="width: 80px; height: auto; border-radius: 4px;">
                                </td>
                                <td>
                                    <input type="text" class="character-name-input" 
                                           value="<?= esc_attr($setting_name) ?>" 
                                           placeholder="<?= esc_attr($card['name']) ?>"
                                           style="width: 100%;">
                                </td>
                                <td>
                                    <textarea class="prompt-prefix-input" 
                                              rows="2" 
                                              placeholder="e.g., (character name), detailed, high quality"><?= esc_textarea($prefix) ?></textarea>
                                </td>
                                <td>
                                    <textarea class="prompt-suffix-input" 
                                              rows="2" 
                                              placeholder="e.g., masterpiece, best quality"><?= esc_textarea($suffix) ?></textarea>
                                </td>
                                <td>
                                    <button class="button button-primary save-character-setting" 
                                            data-filename="<?= esc_attr($card['filename']) ?>">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div id="character-settings-message" style="margin-top: 20px; display: none;"></div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.save-character-setting').on('click', function() {
            var button = $(this);
            var row = button.closest('tr');
            var filename = button.data('filename');
            var name = row.find('.character-name-input').val();
            var prefix = row.find('.prompt-prefix-input').val();
            var suffix = row.find('.prompt-suffix-input').val();
            
            button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_save_character_settings',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    filename: filename,
                    name: name,
                    prompt_prefix: prefix,
                    prompt_suffix: suffix
                },
                success: function(response) {
                    if (response.success) {
                        $('#character-settings-message')
                            .removeClass('notice-error')
                            .addClass('notice notice-success')
                            .html('<p>Settings saved successfully for ' + filename + '</p>')
                            .show();
                        
                        setTimeout(function() {
                            $('#character-settings-message').fadeOut();
                        }, 3000);
                    } else {
                        $('#character-settings-message')
                            .removeClass('notice-success')
                            .addClass('notice notice-error')
                            .html('<p>Error: ' + (response.data.message || 'Failed to save settings') + '</p>')
                            .show();
                    }
                    button.prop('disabled', false).text('Save');
                },
                error: function() {
                    $('#character-settings-message')
                        .removeClass('notice-success')
                        .addClass('notice notice-error')
                        .html('<p>Network error: Failed to save settings</p>')
                        .show();
                    button.prop('disabled', false).text('Save');
                }
            });
        });
    });
    </script>
    
    <style>
    #character-settings-container table td textarea {
        width: 100%;
        min-width: 200px;
    }
    #character-settings-container table img {
        max-width: 80px;
        height: auto;
    }
    </style>
    <?php
}

