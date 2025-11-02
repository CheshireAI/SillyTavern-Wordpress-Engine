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
        <p>Configure prompt prefix, suffix, and custom presets for each character. Custom presets will appear alongside universal presets in the image generation modal.</p>
        
        <ul class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <li><a href="#prefix-suffix-settings" class="nav-tab nav-tab-active" onclick="jQuery('.settings-tab').hide(); jQuery('#prefix-suffix-settings').show(); jQuery(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active'); return false;">Prefix & Suffix</a></li>
            <li><a href="#preset-settings" class="nav-tab" onclick="jQuery('.settings-tab').hide(); jQuery('#preset-settings').show(); jQuery(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active'); return false;">Custom Presets</a></li>
            <li><a href="#universal-presets" class="nav-tab" onclick="jQuery('.settings-tab').hide(); jQuery('#universal-presets').show(); jQuery(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active'); return false;">Edit Universal Presets</a></li>
        </ul>
        
        <div id="prefix-suffix-settings" class="settings-tab">
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
        </div>
        
        <div id="preset-settings" class="settings-tab" style="display: none;">
            <h3>Character-Specific Presets</h3>
            <p>Create custom presets for each character. These will appear in the image generation modal when chatting with that character.</p>
            <div id="character-presets-container">
                <?php if (empty($png_cards)): ?>
                    <div class="notice notice-warning">
                        <p><strong>No character cards found.</strong> Please upload character cards first.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($png_cards as $card): ?>
                        <?php
                        // Get character presets
                        $character_presets = array();
                        if (class_exists('PMV_Character_Presets_Manager')) {
                            $character_presets = PMV_Character_Presets_Manager::get_character_presets($card['filename']);
                        }
                        ?>
                        <div class="character-presets-section" data-filename="<?= esc_attr($card['filename']) ?>" style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                            <h4>
                                <img src="<?= esc_url($card['url']) ?>" alt="<?= esc_attr($card['name']) ?>" style="width: 50px; height: auto; vertical-align: middle; border-radius: 4px; margin-right: 10px;">
                                <?= esc_html($card['name']) ?> (<?= esc_html($card['filename']) ?>)
                            </h4>
                            <div class="presets-list" style="margin-top: 15px;">
                                <?php if (empty($character_presets)): ?>
                                    <p style="color: #666;">No custom presets yet. Click "Add Preset" below to create one.</p>
                                    <button class="button button-secondary add-preset-btn" data-filename="<?= esc_attr($card['filename']) ?>" style="margin-top: 15px;">Add Preset</button>
                                <?php else: ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th>Preset Name</th>
                                                <th>Description</th>
                                                <th>Category</th>
                                                <th style="width: 150px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($character_presets as $preset): ?>
                                                <tr data-preset-id="<?= esc_attr($preset['id']) ?>">
                                                    <td><strong><?= esc_html($preset['name']) ?></strong></td>
                                                    <td><?= esc_html($preset['description']) ?></td>
                                                    <td><?= esc_html($preset['category']) ?></td>
                                                    <td>
                                                        <button class="button button-small edit-preset-btn" data-filename="<?= esc_attr($card['filename']) ?>" data-preset-id="<?= esc_attr($preset['id']) ?>">Edit</button>
                                                        <button class="button button-small delete-preset-btn" data-filename="<?= esc_attr($card['filename']) ?>" data-preset-id="<?= esc_attr($preset['id']) ?>">Delete</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <button class="button button-secondary add-preset-btn" data-filename="<?= esc_attr($card['filename']) ?>" style="margin-top: 15px;">Add Preset</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="universal-presets" class="settings-tab" style="display: none;">
            <h3>Universal Presets</h3>
            <p>Edit the default presets that are available to all characters. These can be overridden by character-specific presets.</p>
            <div id="universal-presets-list">
                <?php
                // Get universal presets
                $universal_presets = array();
                if (class_exists('PMV_Universal_Presets_Manager')) {
                    $universal_presets = PMV_Universal_Presets_Manager::get_universal_presets();
                } else {
                    $universal_presets = PMV_Image_Presets::get_presets();
                }
                
                if (empty($universal_presets)):
                ?>
                    <p style="color: #666;">No universal presets found.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Preset ID</th>
                                <th>Preset Name</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($universal_presets as $preset_id => $preset): ?>
                                <tr data-preset-id="<?= esc_attr($preset_id) ?>">
                                    <td><code><?= esc_html($preset_id) ?></code></td>
                                    <td><strong><?= esc_html($preset['name']) ?></strong></td>
                                    <td><?= esc_html($preset['description']) ?></td>
                                    <td><?= esc_html($preset['category']) ?></td>
                                    <td>
                                        <button class="button button-small edit-universal-preset-btn" data-preset-id="<?= esc_attr($preset_id) ?>">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Universal Preset Editor Modal -->
            <div id="universal-preset-editor-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
                    <h3 id="universal-preset-editor-title">Edit Universal Preset</h3>
                    <form id="universal-preset-editor-form">
                        <input type="hidden" id="universal-preset-editor-preset-id" name="preset_id">
                        <table class="form-table">
                            <tr>
                                <th><label>Preset ID</label></th>
                                <td><input type="text" id="universal-preset-id" name="preset_id" class="regular-text" readonly style="background: #f0f0f0;"></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-name">Preset Name</label></th>
                                <td><input type="text" id="universal-preset-name" name="preset_name" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-description">Description</label></th>
                                <td><textarea id="universal-preset-description" name="preset_description" rows="3" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-category">Category</label></th>
                                <td>
                                    <select id="universal-preset-category" name="preset_category">
                                        <option value="character">Character</option>
                                        <option value="environment">Environment</option>
                                        <option value="action">Action</option>
                                        <option value="style">Style</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-steps">Steps</label></th>
                                <td><input type="number" id="universal-preset-steps" name="steps" value="20" min="1" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-cfg-scale">CFG Scale</label></th>
                                <td><input type="number" id="universal-preset-cfg-scale" name="cfg_scale" value="7.0" min="0.1" max="20" step="0.1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-width">Width</label></th>
                                <td><input type="number" id="universal-preset-width" name="width" value="512" min="256" max="2048" step="64" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-height">Height</label></th>
                                <td><input type="number" id="universal-preset-height" name="height" value="512" min="256" max="2048" step="64" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-negative-prompt">Negative Prompt</label></th>
                                <td><textarea id="universal-preset-negative-prompt" name="negative_prompt" rows="2" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="universal-preset-enhancer">Prompt Enhancer</label></th>
                                <td><textarea id="universal-preset-enhancer" name="prompt_enhancer" rows="2" class="large-text"></textarea></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="button" id="save-universal-preset-btn" class="button button-primary">Save Preset</button>
                            <button type="button" id="revert-universal-preset-btn" class="button button-secondary">Revert to Default</button>
                            <button type="button" id="cancel-universal-preset-btn" class="button">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="character-settings-message" style="margin-top: 20px; display: none;"></div>
        
        <!-- Preset Editor Modal -->
        <div id="preset-editor-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
                <h3 id="preset-editor-title">Add/Edit Preset</h3>
                <form id="preset-editor-form">
                    <input type="hidden" id="preset-editor-filename" name="filename">
                    <input type="hidden" id="preset-editor-preset-id" name="preset_id">
                    <table class="form-table">
                        <tr>
                            <th><label for="preset-name">Preset ID</label></th>
                            <td><input type="text" id="preset-id" name="preset_id" class="regular-text" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only"></td>
                        </tr>
                        <tr>
                            <th><label for="preset-name">Preset Name</label></th>
                            <td><input type="text" id="preset-name" name="preset_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="preset-description">Description</label></th>
                            <td><textarea id="preset-description" name="preset_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="preset-category">Category</label></th>
                            <td>
                                <select id="preset-category" name="preset_category">
                                    <option value="custom">Custom</option>
                                    <option value="character">Character</option>
                                    <option value="environment">Environment</option>
                                    <option value="action">Action</option>
                                    <option value="style">Style</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="preset-steps">Steps</label></th>
                            <td><input type="number" id="preset-steps" name="steps" value="20" min="1" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="preset-cfg-scale">CFG Scale</label></th>
                            <td><input type="number" id="preset-cfg-scale" name="cfg_scale" value="7.0" min="0.1" max="20" step="0.1" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="preset-width">Width</label></th>
                            <td><input type="number" id="preset-width" name="width" value="512" min="256" max="2048" step="64" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="preset-height">Height</label></th>
                            <td><input type="number" id="preset-height" name="height" value="512" min="256" max="2048" step="64" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="preset-negative-prompt">Negative Prompt</label></th>
                            <td><textarea id="preset-negative-prompt" name="negative_prompt" rows="2" class="large-text">blurry, distorted, low quality</textarea></td>
                        </tr>
                        <tr>
                            <th><label for="preset-enhancer">Prompt Enhancer</label></th>
                            <td><textarea id="preset-enhancer" name="prompt_enhancer" rows="2" class="large-text" placeholder="e.g., high quality, detailed, professional"></textarea></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="button" id="save-preset-btn" class="button button-primary">Save Preset</button>
                        <button type="button" id="cancel-preset-btn" class="button">Cancel</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Prefix/Suffix Settings
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
        
        // Preset Management
        var presetModal = $('#preset-editor-modal');
        var presetForm = $('#preset-editor-form');
        var isEditing = false;
        var currentFilename = '';
        var currentPresetId = '';
        
        // Add preset button
        $(document).on('click', '.add-preset-btn', function() {
            isEditing = false;
            currentFilename = $(this).data('filename');
            presetForm[0].reset();
            $('#preset-editor-filename').val(currentFilename);
            $('#preset-editor-preset-id').val('');
            $('#preset-editor-title').text('Add New Preset');
            $('#preset-id').prop('readonly', false);
            presetModal.show();
        });
        
        // Edit preset button
        $(document).on('click', '.edit-preset-btn', function() {
            isEditing = true;
            currentFilename = $(this).data('filename');
            currentPresetId = $(this).data('preset-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_character_presets',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    filename: currentFilename
                },
                success: function(response) {
                    if (response.success && response.data.presets && response.data.presets[currentPresetId]) {
                        var preset = response.data.presets[currentPresetId];
                        $('#preset-editor-filename').val(currentFilename);
                        $('#preset-editor-preset-id').val(preset.id);
                        $('#preset-id').val(preset.id).prop('readonly', true);
                        $('#preset-name').val(preset.name);
                        $('#preset-description').val(preset.description);
                        $('#preset-category').val(preset.category);
                        $('#preset-steps').val(preset.config.steps || 20);
                        $('#preset-cfg-scale').val(preset.config.cfg_scale || 7.0);
                        $('#preset-width').val(preset.config.width || 512);
                        $('#preset-height').val(preset.config.height || 512);
                        $('#preset-negative-prompt').val(preset.config.negative_prompt || '');
                        $('#preset-enhancer').val(preset.config.prompt_enhancer || '');
                        $('#preset-editor-title').text('Edit Preset: ' + preset.name);
                        presetModal.show();
                    }
                }
            });
        });
        
        // Delete preset button
        $(document).on('click', '.delete-preset-btn', function() {
            if (!confirm('Are you sure you want to delete this preset?')) {
                return;
            }
            
            var filename = $(this).data('filename');
            var presetId = $(this).data('preset-id');
            var btn = $(this);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_delete_character_preset',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    filename: filename,
                    preset_id: presetId
                },
                success: function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(function() {
                            $(this).remove();
                            if ($(this).siblings().length === 0) {
                                $(this).closest('.presets-list').html('<p style="color: #666;">No custom presets yet. Click "Add Preset" to create one.</p><button class="button button-secondary add-preset-btn" data-filename="' + filename + '" style="margin-top: 15px;">Add Preset</button>');
                            }
                        });
                        showMessage('Preset deleted successfully', 'success');
                    } else {
                        showMessage('Error: ' + (response.data.message || 'Failed to delete preset'), 'error');
                    }
                },
                error: function() {
                    showMessage('Network error: Failed to delete preset', 'error');
                }
            });
        });
        
        // Save preset
        $('#save-preset-btn').on('click', function() {
            var formData = {
                action: 'pmv_save_character_preset',
                nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                filename: $('#preset-editor-filename').val(),
                preset_id: $('#preset-id').val(),
                preset_name: $('#preset-name').val(),
                preset_description: $('#preset-description').val(),
                preset_category: $('#preset-category').val(),
                steps: parseInt($('#preset-steps').val()) || 20,
                cfg_scale: parseFloat($('#preset-cfg-scale').val()) || 7.0,
                width: parseInt($('#preset-width').val()) || 512,
                height: parseInt($('#preset-height').val()) || 512,
                negative_prompt: $('#preset-negative-prompt').val(),
                prompt_enhancer: $('#preset-enhancer').val(),
                is_active: true,
                sort_order: 0
            };
            
            if (!formData.preset_id || !formData.preset_name) {
                alert('Preset ID and Name are required');
                return;
            }
            
            $(this).prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        presetModal.hide();
                        showMessage('Preset saved successfully', 'success');
                        // Reload page to refresh preset list
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage('Error: ' + (response.data.message || 'Failed to save preset'), 'error');
                    }
                    $('#save-preset-btn').prop('disabled', false).text('Save Preset');
                },
                error: function() {
                    showMessage('Network error: Failed to save preset', 'error');
                    $('#save-preset-btn').prop('disabled', false).text('Save Preset');
                }
            });
        });
        
        // Cancel preset editor
        $('#cancel-preset-btn').on('click', function() {
            presetModal.hide();
        });
        
        // Close modal on background click
        presetModal.on('click', function(e) {
            if (e.target === this) {
                presetModal.hide();
            }
        });
        
        // Universal Preset Management
        var universalPresetModal = $('#universal-preset-editor-modal');
        var universalPresetForm = $('#universal-preset-editor-form');
        
        // Edit universal preset button
        $(document).on('click', '.edit-universal-preset-btn', function() {
            var presetId = $(this).data('preset-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_universal_presets',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success && response.data.presets && response.data.presets[presetId]) {
                        var preset = response.data.presets[presetId];
                        $('#universal-preset-editor-preset-id').val(presetId);
                        $('#universal-preset-id').val(presetId);
                        $('#universal-preset-name').val(preset.name);
                        $('#universal-preset-description').val(preset.description);
                        $('#universal-preset-category').val(preset.category);
                        $('#universal-preset-steps').val(preset.config.steps || 20);
                        $('#universal-preset-cfg-scale').val(preset.config.cfg_scale || 7.0);
                        $('#universal-preset-width').val(preset.config.width || 512);
                        $('#universal-preset-height').val(preset.config.height || 512);
                        $('#universal-preset-negative-prompt').val(preset.config.negative_prompt || '');
                        $('#universal-preset-enhancer').val(preset.config.prompt_enhancer || '');
                        $('#universal-preset-editor-title').text('Edit Universal Preset: ' + preset.name);
                        universalPresetModal.show();
                    }
                }
            });
        });
        
        // Save universal preset
        $('#save-universal-preset-btn').on('click', function() {
            var formData = {
                action: 'pmv_save_universal_preset',
                nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                preset_id: $('#universal-preset-editor-preset-id').val(),
                name: $('#universal-preset-name').val(),
                description: $('#universal-preset-description').val(),
                category: $('#universal-preset-category').val(),
                steps: parseInt($('#universal-preset-steps').val()) || 20,
                cfg_scale: parseFloat($('#universal-preset-cfg-scale').val()) || 7.0,
                width: parseInt($('#universal-preset-width').val()) || 512,
                height: parseInt($('#universal-preset-height').val()) || 512,
                negative_prompt: $('#universal-preset-negative-prompt').val(),
                prompt_enhancer: $('#universal-preset-enhancer').val()
            };
            
            $(this).prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        universalPresetModal.hide();
                        showMessage('Universal preset updated successfully', 'success');
                        // Reload page to refresh preset list
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage('Error: ' + (response.data.message || 'Failed to save preset'), 'error');
                    }
                    $('#save-universal-preset-btn').prop('disabled', false).text('Save Preset');
                },
                error: function() {
                    showMessage('Network error: Failed to save preset', 'error');
                    $('#save-universal-preset-btn').prop('disabled', false).text('Save Preset');
                }
            });
        });
        
        // Revert universal preset to default
        $('#revert-universal-preset-btn').on('click', function() {
            if (!confirm('Are you sure you want to revert this preset to its default values?')) {
                return;
            }
            
            var presetId = $('#universal-preset-editor-preset-id').val();
            
            $(this).prop('disabled', true).text('Reverting...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_delete_universal_preset_override',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    preset_id: presetId
                },
                success: function(response) {
                    if (response.success) {
                        universalPresetModal.hide();
                        showMessage('Preset reverted to default successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage('Error: ' + (response.data.message || 'Failed to revert preset'), 'error');
                    }
                    $('#revert-universal-preset-btn').prop('disabled', false).text('Revert to Default');
                },
                error: function() {
                    showMessage('Network error: Failed to revert preset', 'error');
                    $('#revert-universal-preset-btn').prop('disabled', false).text('Revert to Default');
                }
            });
        });
        
        // Cancel universal preset editor
        $('#cancel-universal-preset-btn').on('click', function() {
            universalPresetModal.hide();
        });
        
        // Close modal on background click
        universalPresetModal.on('click', function(e) {
            if (e.target === this) {
                universalPresetModal.hide();
            }
        });
        
        function showMessage(message, type) {
            var msgDiv = $('#character-settings-message');
            msgDiv.removeClass('notice-error notice-success')
                  .addClass('notice notice-' + type)
                  .html('<p>' + message + '</p>')
                  .show();
            
            setTimeout(function() {
                msgDiv.fadeOut();
            }, 3000);
        }
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

