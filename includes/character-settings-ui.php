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
    
    // Get all existing character settings (for the map)
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
            <div id="character-settings-loading" style="text-align: center; padding: 40px;">
                <span class="spinner is-active" style="float: none;"></span>
                <p>Loading character cards...</p>
            </div>
            <div id="character-settings-table-wrapper" style="display: none;">
                <div style="margin-bottom: 15px;">
                    <label>
                        Items per page: 
                        <select id="character-settings-per-page" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </label>
                </div>
                <table class="wp-list-table widefat fixed striped" id="character-settings-table">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Character</th>
                            <th>Character Name</th>
                            <th>Prompt Prefix</th>
                            <th>Prompt Suffix</th>
                            <th>Image Model</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="character-settings-tbody">
                        <!-- Cards loaded via AJAX -->
                    </tbody>
                </table>
                <div id="character-settings-pagination" style="margin-top: 20px; text-align: center;"></div>
            </div>
            <div id="character-settings-empty" style="display: none;">
                <div class="notice notice-warning">
                    <p><strong>No character cards found.</strong> Please upload character cards first.</p>
                </div>
            </div>
        </div>
        </div>
        
        <div id="preset-settings" class="settings-tab" style="display: none;">
            <h3>Character-Specific Presets</h3>
            <p>Create custom presets for each character. These will appear in the image generation modal when chatting with that character.</p>
            <div id="character-presets-container">
                <div id="character-presets-loading" style="text-align: center; padding: 40px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <p>Loading character cards...</p>
                </div>
                <div id="character-presets-list" style="display: none;">
                    <!-- Character presets loaded via AJAX -->
                </div>
                <div id="character-presets-empty" style="display: none;">
                    <div class="notice notice-warning">
                        <p><strong>No character cards found.</strong> Please upload character cards first.</p>
                    </div>
                </div>
                <div id="character-presets-pagination" style="margin-top: 20px; text-align: center;"></div>
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
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; color: #000;">
                    <h3 id="universal-preset-editor-title" style="color: #000;">Edit Universal Preset</h3>
                    <form id="universal-preset-editor-form">
                        <input type="hidden" id="universal-preset-editor-preset-id" name="preset_id">
                        <table class="form-table">
                            <tr>
                                <th style="color: #000;"><label style="color: #000;">Preset ID</label></th>
                                <td><input type="text" id="universal-preset-id" name="preset_id" class="regular-text" readonly style="background: #f0f0f0; color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-name" style="color: #000;">Preset Name</label></th>
                                <td><input type="text" id="universal-preset-name" name="preset_name" class="regular-text" required style="color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-description" style="color: #000;">Description</label></th>
                                <td><textarea id="universal-preset-description" name="preset_description" rows="3" class="large-text" style="color: #000;"></textarea></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-category" style="color: #000;">Category</label></th>
                                <td>
                                    <select id="universal-preset-category" name="preset_category" style="color: #000;">
                                        <option value="character">Character</option>
                                        <option value="environment">Environment</option>
                                        <option value="action">Action</option>
                                        <option value="style">Style</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-steps" style="color: #000;">Steps</label></th>
                                <td><input type="number" id="universal-preset-steps" name="steps" value="20" min="1" max="100" class="small-text" style="color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-cfg-scale" style="color: #000;">CFG Scale</label></th>
                                <td><input type="number" id="universal-preset-cfg-scale" name="cfg_scale" value="7.0" min="0.1" max="20" step="0.1" class="small-text" style="color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-width" style="color: #000;">Width</label></th>
                                <td><input type="number" id="universal-preset-width" name="width" value="512" min="256" max="2048" step="64" class="small-text" style="color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-height" style="color: #000;">Height</label></th>
                                <td><input type="number" id="universal-preset-height" name="height" value="512" min="256" max="2048" step="64" class="small-text" style="color: #000;"></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-negative-prompt" style="color: #000;">Negative Prompt</label></th>
                                <td><textarea id="universal-preset-negative-prompt" name="negative_prompt" rows="2" class="large-text" style="color: #000;"></textarea></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-enhancer" style="color: #000;">Prompt Enhancer</label></th>
                                <td><textarea id="universal-preset-enhancer" name="prompt_enhancer" rows="2" class="large-text" style="color: #000;"></textarea></td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label for="universal-preset-model" style="color: #000;">Model</label></th>
                                <td>
                                    <select id="universal-preset-model" name="preset_model" style="width: 100%; color: #000;">
                                        <option value="">Use character/default model</option>
                                    </select>
                                    <p class="description" style="color: #666; font-size: 13px;">Optional: Select a specific model for this preset. If not set, will use character-specific model or default.</p>
                                </td>
                            </tr>
                            <tr>
                                <th style="color: #000;"><label style="color: #000;">LoRAs</label></th>
                                <td>
                                    <div id="universal-preset-loras-container" style="margin-bottom: 10px;">
                                        <!-- LoRA entries will be added here -->
                                    </div>
                                    <button type="button" id="add-universal-preset-lora-btn" class="button button-small" style="color: #000;">+ Add LoRA</button>
                                    <p class="description" style="color: #666; font-size: 13px;">Optional: Add LoRAs (Low-Rank Adaptations) for this preset. Each LoRA requires a name (e.g., "Pony/zy_AmateurStyle_v2") and weight (default: 1).</p>
                                </td>
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
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; color: #000;">
                <h3 id="preset-editor-title" style="color: #000;">Add/Edit Preset</h3>
                <form id="preset-editor-form">
                    <input type="hidden" id="preset-editor-filename" name="filename">
                    <input type="hidden" id="preset-editor-preset-id" name="preset_id">
                    <table class="form-table">
                        <tr>
                            <th style="color: #000;"><label for="preset-id" style="color: #000;">Preset ID</label></th>
                            <td><input type="text" id="preset-id" name="preset_id" class="regular-text" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only" style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-name" style="color: #000;">Preset Name</label></th>
                            <td><input type="text" id="preset-name" name="preset_name" class="regular-text" required style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-description" style="color: #000;">Description</label></th>
                            <td><textarea id="preset-description" name="preset_description" rows="3" class="large-text" style="color: #000;"></textarea></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-category" style="color: #000;">Category</label></th>
                            <td>
                                <select id="preset-category" name="preset_category" style="color: #000;">
                                    <option value="custom">Custom</option>
                                    <option value="character">Character</option>
                                    <option value="environment">Environment</option>
                                    <option value="action">Action</option>
                                    <option value="style">Style</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-steps" style="color: #000;">Steps</label></th>
                            <td><input type="number" id="preset-steps" name="steps" value="20" min="1" max="100" class="small-text" style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-cfg-scale" style="color: #000;">CFG Scale</label></th>
                            <td><input type="number" id="preset-cfg-scale" name="cfg_scale" value="7.0" min="0.1" max="20" step="0.1" class="small-text" style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-width" style="color: #000;">Width</label></th>
                            <td><input type="number" id="preset-width" name="width" value="512" min="256" max="2048" step="64" class="small-text" style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-height" style="color: #000;">Height</label></th>
                            <td><input type="number" id="preset-height" name="height" value="512" min="256" max="2048" step="64" class="small-text" style="color: #000;"></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-negative-prompt" style="color: #000;">Negative Prompt</label></th>
                            <td><textarea id="preset-negative-prompt" name="negative_prompt" rows="2" class="large-text" style="color: #000;">blurry, distorted, low quality</textarea></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-enhancer" style="color: #000;">Prompt Enhancer</label></th>
                            <td><textarea id="preset-enhancer" name="prompt_enhancer" rows="2" class="large-text" placeholder="e.g., high quality, detailed, professional" style="color: #000;"></textarea></td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label for="preset-model" style="color: #000;">Model</label></th>
                            <td>
                                <select id="preset-model" name="preset_model" style="width: 100%; color: #000;">
                                    <option value="">Use character/default model</option>
                                </select>
                                <p class="description" style="color: #666; font-size: 13px;">Optional: Select a specific model for this preset. If not set, will use character-specific model or default.</p>
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #000;"><label style="color: #000;">LoRAs</label></th>
                            <td>
                                <div id="preset-loras-container" style="margin-bottom: 10px;">
                                    <!-- LoRA entries will be added here -->
                                </div>
                                <button type="button" id="add-preset-lora-btn" class="button button-small" style="color: #000;">+ Add LoRA</button>
                                <p class="description" style="color: #666; font-size: 13px;">Optional: Add LoRAs (Low-Rank Adaptations) for this preset. Each LoRA requires a name (e.g., "Pony/zy_AmateurStyle_v2") and weight (default: 1).</p>
                            </td>
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
        // Pagination state
        var currentPage = {
            prefixSuffix: 1,
            presets: 1
        };
        var perPage = {
            prefixSuffix: 25,
            presets: 25
        };
        var totalPages = {
            prefixSuffix: 1,
            presets: 1
        };
        
        // Load character cards for prefix/suffix settings
        function loadCharacterCardsForSettings(page, tab) {
            var loadingSelector = tab === 'prefixSuffix' ? '#character-settings-loading' : '#character-presets-loading';
            var wrapperSelector = tab === 'prefixSuffix' ? '#character-settings-table-wrapper' : '#character-presets-list';
            var emptySelector = tab === 'prefixSuffix' ? '#character-settings-empty' : '#character-presets-empty';
            var tbodySelector = tab === 'prefixSuffix' ? '#character-settings-tbody' : null;
            var paginationSelector = tab === 'prefixSuffix' ? '#character-settings-pagination' : '#character-presets-pagination';
            
            $(loadingSelector).show();
            $(wrapperSelector).hide();
            $(emptySelector).hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_character_cards_for_settings',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                    page: page,
                    per_page: perPage[tab]
                },
                success: function(response) {
                    $(loadingSelector).hide();
                    
                    if (response.success && response.data) {
                        if (response.data.cards && response.data.cards.length > 0) {
                            $(wrapperSelector).show();
                            $(emptySelector).hide();
                            
                            if (tab === 'prefixSuffix') {
                                renderPrefixSuffixTable(response.data.cards, response.data.settings_map || {});
                            } else {
                                renderPresetsList(response.data.cards);
                            }
                            
                            if (response.data.pagination) {
                                totalPages[tab] = response.data.pagination.total_pages;
                                renderPagination(paginationSelector, response.data.pagination, tab);
                                currentPage[tab] = page;
                            }
                        } else {
                            // No cards but successful response
                            $(wrapperSelector).hide();
                            $(emptySelector).show();
                        }
                    } else {
                        // Error response
                        $(wrapperSelector).hide();
                        $(emptySelector).show();
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Error loading character cards.';
                        $(emptySelector).html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $(loadingSelector).hide();
                    $(emptySelector).show();
                    var errorMsg = 'Error loading character cards. ';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg += xhr.responseJSON.data.message;
                    } else {
                        errorMsg += 'Please try again.';
                    }
                    console.error('PMV Settings AJAX Error:', {xhr, status, error, responseText: xhr.responseText});
                    $(emptySelector).html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
                }
            });
        }
        
        // Render prefix/suffix table
        function renderPrefixSuffixTable(cards, settingsMap) {
            var tbody = $('#character-settings-tbody');
            tbody.empty();
            
            // Load models for dropdown
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_available_models',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    var models = [];
                    if (response.success && response.data) {
                        // Parse models from response - ListT2IParams with subtype='Model' returns files array
                        // Similar structure to LoRAs: {folders: [], files: [{name, title, ...}]}
                        if (response.data.files && Array.isArray(response.data.files)) {
                            // Models are in the files array, each file has name (with path) and title
                            response.data.files.forEach(function(file) {
                                var modelName = file.name || '';
                                var modelTitle = file.title || file.name || '';
                                if (modelName && modelName.indexOf('/') !== -1) {
                                    // Only include if it looks like a model path (contains /)
                                    models.push({
                                        name: modelName,
                                        title: modelTitle || modelName
                                    });
                                }
                            });
                        } else if (response.data.models && typeof response.data.models === 'object') {
                            // Fallback: Check for models in nested structure
                            Object.keys(response.data.models).forEach(function(category) {
                                if (Array.isArray(response.data.models[category])) {
                                    response.data.models[category].forEach(function(model) {
                                        if (typeof model === 'string' && model.indexOf('/') !== -1) {
                                            models.push({name: model, title: model});
                                        }
                                    });
                                }
                            });
                        }
                    }
                    
                    // Build model dropdown HTML
                    var modelSelectHtml = '<select class="image-model-input" style="width: 100%; color: #000;"><option value="">Use default model</option>';
                    models.forEach(function(model) {
                        modelSelectHtml += '<option value="' + model.name.replace(/"/g, '&quot;') + '">' + model.title.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
                    });
                    modelSelectHtml += '</select>';
                    
                    // Render table rows
                    cards.forEach(function(card) {
                        var setting = settingsMap && settingsMap[card.filename] ? settingsMap[card.filename] : null;
                        var prefix = setting ? setting.prompt_prefix : '';
                        var suffix = setting ? setting.prompt_suffix : '';
                        var model = setting ? setting.image_model : '';
                        var settingName = setting ? setting.character_name : card.name;
                        
                        var row = $('<tr>').attr('data-filename', card.filename);
                        row.append('<td><img src="' + card.url + '" alt="' + card.name.replace(/"/g, '&quot;') + '" style="width: 80px; height: auto; border-radius: 4px;"></td>');
                        row.append('<td><input type="text" class="character-name-input" value="' + settingName.replace(/"/g, '&quot;') + '" placeholder="' + card.name.replace(/"/g, '&quot;') + '" style="width: 100%; color: #000;"></td>');
                        row.append('<td><textarea class="prompt-prefix-input" rows="2" placeholder="e.g., (character name), detailed, high quality" style="color: #000;">' + prefix.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</textarea></td>');
                        row.append('<td><textarea class="prompt-suffix-input" rows="2" placeholder="e.g., masterpiece, best quality" style="color: #000;">' + suffix.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</textarea></td>');
                        
                        // Model dropdown - create from HTML string
                        var $modelSelect = $(modelSelectHtml);
                        if (model) {
                            $modelSelect.val(model);
                        }
                        row.append('<td>' + $modelSelect[0].outerHTML + '</td>');
                        row.append('<td><button class="button button-primary save-character-setting" data-filename="' + card.filename.replace(/"/g, '&quot;') + '">Save</button></td>');
                        tbody.append(row);
                    });
                },
                error: function() {
                    // Fallback: render without models
                    cards.forEach(function(card) {
                        var setting = settingsMap && settingsMap[card.filename] ? settingsMap[card.filename] : null;
                        var prefix = setting ? setting.prompt_prefix : '';
                        var suffix = setting ? setting.prompt_suffix : '';
                        var model = setting ? setting.image_model : '';
                        var settingName = setting ? setting.character_name : card.name;
                        
                        var row = $('<tr>').attr('data-filename', card.filename);
                        row.append('<td><img src="' + card.url + '" alt="' + card.name.replace(/"/g, '&quot;') + '" style="width: 80px; height: auto; border-radius: 4px;"></td>');
                        row.append('<td><input type="text" class="character-name-input" value="' + settingName.replace(/"/g, '&quot;') + '" placeholder="' + card.name.replace(/"/g, '&quot;') + '" style="width: 100%; color: #000;"></td>');
                        row.append('<td><textarea class="prompt-prefix-input" rows="2" placeholder="e.g., (character name), detailed, high quality" style="color: #000;">' + prefix.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</textarea></td>');
                        row.append('<td><textarea class="prompt-suffix-input" rows="2" placeholder="e.g., masterpiece, best quality" style="color: #000;">' + suffix.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</textarea></td>');
                        row.append('<td><input type="text" class="image-model-input" value="' + model.replace(/"/g, '&quot;') + '" placeholder="Model name (optional)" style="width: 100%; color: #000;"></td>');
                        row.append('<td><button class="button button-primary save-character-setting" data-filename="' + card.filename.replace(/"/g, '&quot;') + '">Save</button></td>');
                        tbody.append(row);
                    });
                }
            });
        }
        
        // Render presets list
        function renderPresetsList(cards) {
            var container = $('#character-presets-list');
            container.empty();
            
            cards.forEach(function(card) {
                var section = $('<div>').addClass('character-presets-section').attr('data-filename', card.filename).css({
                    'margin-bottom': '30px',
                    'padding': '20px',
                    'border': '1px solid #ddd',
                    'border-radius': '8px',
                    'background': '#f9f9f9'
                });
                
                var header = $('<h4>');
                header.append($('<img>').attr('src', card.url).attr('alt', card.name).css({
                    'width': '50px',
                    'height': 'auto',
                    'vertical-align': 'middle',
                    'border-radius': '4px',
                    'margin-right': '10px'
                }));
                header.append(card.name + ' (' + card.filename + ')');
                section.append(header);
                
                var presetsDiv = $('<div>').addClass('presets-list').css('margin-top', '15px');
                presetsDiv.html('<p style="color: #666;">Loading presets...</p>');
                section.append(presetsDiv);
                
                // Load presets for this character
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pmv_get_character_presets',
                        nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>',
                        filename: card.filename
                    },
                    success: function(response) {
                        if (response.success && response.data.presets) {
                            var presets = response.data.presets;
                            if (Object.keys(presets).length === 0) {
                                presetsDiv.html('<p style="color: #666;">No custom presets yet. Click "Add Preset" below to create one.</p><button class="button button-secondary add-preset-btn" data-filename="' + card.filename.replace(/"/g, '&quot;') + '" style="margin-top: 15px;">Add Preset</button>');
                            } else {
                                var table = $('<table>').addClass('wp-list-table widefat fixed striped');
                                var thead = $('<thead>').append('<tr><th>Preset Name</th><th>Description</th><th>Category</th><th style="width: 150px;">Actions</th></tr>');
                                var tbody = $('<tbody>');
                                
                                Object.keys(presets).forEach(function(presetId) {
                                    var preset = presets[presetId];
                                    var row = $('<tr>').attr('data-preset-id', presetId);
                                    row.append('<td><strong>' + preset.name.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</strong></td>');
                                    row.append('<td>' + preset.description.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>');
                                    row.append('<td>' + preset.category + '</td>');
                                    row.append('<td><button class="button button-small edit-preset-btn" data-filename="' + card.filename.replace(/"/g, '&quot;') + '" data-preset-id="' + presetId + '">Edit</button> <button class="button button-small delete-preset-btn" data-filename="' + card.filename.replace(/"/g, '&quot;') + '" data-preset-id="' + presetId + '">Delete</button></td>');
                                    tbody.append(row);
                                });
                                
                                table.append(thead).append(tbody);
                                presetsDiv.empty().append(table);
                                presetsDiv.append($('<button>').addClass('button button-secondary add-preset-btn').attr('data-filename', card.filename).css('margin-top', '15px').text('Add Preset'));
                            }
                        }
                    }
                });
                
                container.append(section);
            });
        }
        
        // Render pagination
        function renderPagination(selector, pagination, tab) {
            var $pagination = $(selector);
            $pagination.empty();
            
            if (pagination.total_pages <= 1) {
                return;
            }
            
            var startItem = (pagination.current_page - 1) * pagination.per_page + 1;
            var endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
            
            var info = $('<div>').addClass('pmv-pagination-info').css('margin-bottom', '10px');
            info.text('Showing ' + startItem + '-' + endItem + ' of ' + pagination.total_items + ' characters (Page ' + pagination.current_page + ' of ' + pagination.total_pages + ')');
            $pagination.append(info);
            
            var links = $('<div>').addClass('pmv-pagination-links');
            
            // Previous
            if (pagination.has_prev) {
                links.append($('<a>').addClass('pmv-page-link').attr('href', '#').attr('data-page', pagination.current_page - 1).attr('data-tab', tab).text('« Previous'));
            } else {
                links.append($('<span>').addClass('pmv-page-disabled').text('« Previous'));
            }
            
            // Page numbers
            var maxVisible = 7;
            var startPage = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2));
            var endPage = Math.min(pagination.total_pages, startPage + maxVisible - 1);
            
            if (startPage > 1) {
                links.append($('<a>').addClass('pmv-page-link').attr('href', '#').attr('data-page', 1).attr('data-tab', tab).text('1'));
                if (startPage > 2) {
                    links.append($('<span>').text('...'));
                }
            }
            
            for (var i = startPage; i <= endPage; i++) {
                if (i === pagination.current_page) {
                    links.append($('<span>').addClass('pmv-page-current').text(i));
                } else {
                    links.append($('<a>').addClass('pmv-page-link').attr('href', '#').attr('data-page', i).attr('data-tab', tab).text(i));
                }
            }
            
            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    links.append($('<span>').text('...'));
                }
                links.append($('<a>').addClass('pmv-page-link').attr('href', '#').attr('data-page', pagination.total_pages).attr('data-tab', tab).text(pagination.total_pages));
            }
            
            // Next
            if (pagination.has_next) {
                links.append($('<a>').addClass('pmv-page-link').attr('href', '#').attr('data-page', pagination.current_page + 1).attr('data-tab', tab).text('Next »'));
            } else {
                links.append($('<span>').addClass('pmv-page-disabled').text('Next »'));
            }
            
            $pagination.append(links);
        }
        
        // Page change handler
        $(document).on('click', '.pmv-page-link', function(e) {
            e.preventDefault();
            var page = parseInt($(this).data('page'));
            var tab = $(this).data('tab');
            if (page && tab) {
                loadCharacterCardsForSettings(page, tab);
            }
        });
        
        // Per-page change handler
        $('#character-settings-per-page').on('change', function() {
            perPage.prefixSuffix = parseInt($(this).val());
            loadCharacterCardsForSettings(1, 'prefixSuffix');
        });
        
        // Load initial data when prefix/suffix tab is shown
        var prefixSuffixLoaded = false;
        $(document).on('click', '.nav-tab', function() {
            var target = $(this).attr('href');
            if (target === '#prefix-suffix-settings' && !prefixSuffixLoaded) {
                loadCharacterCardsForSettings(1, 'prefixSuffix');
                prefixSuffixLoaded = true;
            } else if (target === '#preset-settings') {
                loadCharacterCardsForSettings(1, 'presets');
            }
        });
        
        // Initial load if prefix/suffix tab is active
        if ($('#prefix-suffix-settings').is(':visible')) {
            loadCharacterCardsForSettings(1, 'prefixSuffix');
            prefixSuffixLoaded = true;
        }
        
        // Prefix/Suffix Settings
        $(document).on('click', '.save-character-setting', function() {
            var button = $(this);
            var row = button.closest('tr');
            var filename = button.data('filename');
            var name = row.find('.character-name-input').val();
            var prefix = row.find('.prompt-prefix-input').val();
            var suffix = row.find('.prompt-suffix-input').val();
            var model = row.find('.image-model-input').val();
            
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
                    prompt_suffix: suffix,
                    image_model: model
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
        
        // Function to load models for preset editor dropdowns
        // Cache models to avoid multiple AJAX calls
        var modelsCache = null;
        var modelsLoading = false;
        
        function loadModelsForPresetEditor(selectId, selectedModel) {
            var $select = $(selectId);
            $select.html('<option value="">Use character/default model</option>');
            
            // Use cached models if available
            if (modelsCache && modelsCache.length > 0) {
                modelsCache.forEach(function(model) {
                    var option = $('<option>').val(model.name).text(model.title);
                    if (selectedModel && model.name === selectedModel) {
                        option.prop('selected', true);
                    }
                    $select.append(option);
                });
                if (selectedModel) {
                    $select.val(selectedModel);
                }
                return;
            }
            
            // Don't make multiple simultaneous requests
            if (modelsLoading) {
                // Wait for existing request to complete
                setTimeout(function() {
                    loadModelsForPresetEditor(selectId, selectedModel);
                }, 500);
                return;
            }
            
            modelsLoading = true;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_available_models',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var models = [];
                        // Parse models from response - ListT2IParams with subtype='Model' returns files array
                        // Similar structure to LoRAs: {folders: [], files: [{name, title, ...}]}
                        if (response.data.files && Array.isArray(response.data.files)) {
                            // Models are in the files array, each file has name (with path) and title
                            response.data.files.forEach(function(file) {
                                var modelName = file.name || '';
                                var modelTitle = file.title || file.name || '';
                                if (modelName && modelName.indexOf('/') !== -1) {
                                    // Only include if it looks like a model path (contains /)
                                    models.push({
                                        name: modelName,
                                        title: modelTitle || modelName
                                    });
                                }
                            });
                        } else if (response.data.models && typeof response.data.models === 'object') {
                            // Fallback: Check for models in nested structure
                            Object.keys(response.data.models).forEach(function(category) {
                                if (Array.isArray(response.data.models[category])) {
                                    response.data.models[category].forEach(function(model) {
                                        if (typeof model === 'string' && model.indexOf('/') !== -1) {
                                            models.push({name: model, title: model});
                                        }
                                    });
                                }
                            });
                        }
                        
                        // Cache the models
                        modelsCache = models;
                        
                        models.forEach(function(model) {
                            var option = $('<option>').val(model.name).text(model.title);
                            if (selectedModel && model.name === selectedModel) {
                                option.prop('selected', true);
                            }
                            $select.append(option);
                        });
                    }
                    if (selectedModel) {
                        $select.val(selectedModel);
                    }
                    modelsLoading = false;
                },
                error: function() {
                    // Keep default option if models fail to load
                    modelsLoading = false;
                }
            });
        }
        
        // LoRA Management Functions
        // Cache LoRAs to avoid multiple AJAX calls
        var lorasCache = null;
        var lorasLoading = false;
        
        function loadAvailableLoras(callback) {
            // Use cached LoRAs if available
            if (lorasCache && lorasCache.length > 0) {
                if (callback) callback(lorasCache);
                return;
            }
            
            // Don't make multiple simultaneous requests
            if (lorasLoading) {
                setTimeout(function() {
                    loadAvailableLoras(callback);
                }, 500);
                return;
            }
            
            lorasLoading = true;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmv_get_available_loras',
                    nonce: '<?= wp_create_nonce('pmv_ajax_nonce') ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var loras = [];
                        // Parse LoRAs from response - SwarmUI returns files array with name and title
                        if (response.data.files && Array.isArray(response.data.files)) {
                            response.data.files.forEach(function(file) {
                                // Use the full filename as the identifier (SwarmUI expects full path/name)
                                var loraName = file.name || '';
                                var loraTitle = file.title || file.name || '';
                                
                                // If name includes folder path, keep it (e.g., "Pony/filename.safetensors")
                                // Otherwise, just use the filename
                                if (loraName) {
                                    loras.push({
                                        name: loraName,
                                        title: loraTitle || loraName
                                    });
                                }
                            });
                        } else if (response.data.list && Array.isArray(response.data.list)) {
                            response.data.list.forEach(function(item) {
                                var loraName = item.name || item.path || '';
                                var loraTitle = item.title || item.name || '';
                                if (loraName) {
                                    loras.push({
                                        name: loraName,
                                        title: loraTitle || loraName
                                    });
                                }
                            });
                        }
                        
                        // Cache the LoRAs
                        lorasCache = loras;
                        
                        if (callback) callback(loras);
                    } else {
                        if (callback) callback([]);
                    }
                    lorasLoading = false;
                },
                error: function() {
                    if (callback) callback([]);
                    lorasLoading = false;
                }
            });
        }
        
        function addLoraEntry(containerId, loraName = '', loraWeight = '1', loraTencWeight = '') {
            var $container = $(containerId);
            var index = $container.find('.lora-entry').length;
            
            // Create the entry with a select dropdown for LoRA name
            var $entry = $('<div>').addClass('lora-entry').css({
                'margin-bottom': '10px',
                'padding': '10px',
                'border': '1px solid #ddd',
                'border-radius': '4px',
                'background': '#f9f9f9'
            });
            
            var $row = $('<div>').css({
                'display': 'flex',
                'gap': '10px',
                'align-items': 'center'
            });
            
            // LoRA name dropdown (will be populated asynchronously)
            var $loraSelect = $('<select>').addClass('lora-name').css({
                'flex': '1',
                'color': '#000',
                'padding': '5px'
            });
            $loraSelect.append($('<option>').val('').text('Loading LoRAs...'));
            
            // Load LoRAs and populate dropdown
            loadAvailableLoras(function(loras) {
                $loraSelect.empty();
                $loraSelect.append($('<option>').val('').text('Select LoRA...'));
                
                loras.forEach(function(lora) {
                    var $option = $('<option>').val(lora.name).text(lora.title);
                    if (loraName && lora.name === loraName) {
                        $option.prop('selected', true);
                    }
                    $loraSelect.append($option);
                });
                
                // If custom LoRA name was provided and not found in list, add it
                if (loraName && !loras.find(function(l) { return l.name === loraName; })) {
                    $loraSelect.append($('<option>').val(loraName).text(loraName + ' (custom)').prop('selected', true));
                }
            });
            
            $row.append($loraSelect);
            
            // Weight input
            var $weightInput = $('<input>').attr({
                'type': 'number',
                'class': 'lora-weight',
                'placeholder': 'Weight',
                'value': loraWeight,
                'min': '0',
                'max': '2',
                'step': '0.1'
            }).css({
                'width': '100px',
                'color': '#000',
                'padding': '5px'
            });
            $row.append($weightInput);
            
            // TenC Weight input
            var $tencWeightInput = $('<input>').attr({
                'type': 'number',
                'class': 'lora-tenc-weight',
                'placeholder': 'TenC Weight',
                'value': loraTencWeight,
                'min': '0',
                'max': '2',
                'step': '0.1'
            }).css({
                'width': '120px',
                'color': '#000',
                'padding': '5px'
            });
            $row.append($tencWeightInput);
            
            // Remove button
            var $removeBtn = $('<button>').attr('type', 'button').addClass('button button-small remove-lora-btn').text('Remove').css('color', '#000');
            $row.append($removeBtn);
            
            $entry.append($row);
            $container.append($entry);
        }
        
        function getLorasFromContainer(containerId) {
            var loras = [];
            $(containerId + ' .lora-entry').each(function() {
                var $entry = $(this);
                // Get LoRA name from select dropdown (not input)
                var name = $entry.find('.lora-name').val().trim();
                var weight = $entry.find('.lora-weight').val().trim() || '1';
                var tencWeight = $entry.find('.lora-tenc-weight').val().trim();
                
                if (name) {
                    loras.push({
                        name: name,
                        weight: weight,
                        tenc_weight: tencWeight || ''
                    });
                }
            });
            return loras;
        }
        
        function loadLorasIntoContainer(containerId, loras) {
            var $container = $(containerId);
            $container.empty();
            
            if (loras && Array.isArray(loras) && loras.length > 0) {
                loras.forEach(function(lora) {
                    addLoraEntry(containerId, lora.name || '', lora.weight || '1', lora.tenc_weight || '');
                });
            }
        }
        
        // Remove LoRA entry
        $(document).on('click', '.remove-lora-btn', function() {
            $(this).closest('.lora-entry').remove();
        });
        
        // Add LoRA buttons
        $('#add-preset-lora-btn').on('click', function() {
            addLoraEntry('#preset-loras-container');
        });
        
        $('#add-universal-preset-lora-btn').on('click', function() {
            addLoraEntry('#universal-preset-loras-container');
        });
        
        // Add preset button
        $(document).on('click', '.add-preset-btn', function() {
            isEditing = false;
            currentFilename = $(this).data('filename');
            presetForm[0].reset();
            $('#preset-editor-filename').val(currentFilename);
            $('#preset-editor-preset-id').val('');
            $('#preset-editor-title').text('Add New Preset');
            $('#preset-id').prop('readonly', false);
            loadModelsForPresetEditor('#preset-model', '');
            loadLorasIntoContainer('#preset-loras-container', []);
            presetModal.css('display', 'block');
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
                        // Load models first, then set model value
                        loadModelsForPresetEditor('#preset-model', preset.config.model || '');
                        // Load LoRAs
                        loadLorasIntoContainer('#preset-loras-container', preset.config.loras || []);
                        $('#preset-editor-title').text('Edit Preset: ' + preset.name);
                        presetModal.css('display', 'block');
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
                model: $('#preset-model').val() || '',
                loras: getLorasFromContainer('#preset-loras-container'),
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
                        presetModal.css('display', 'none');
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
            presetModal.css('display', 'none');
        });
        
        // Close modal on background click
        presetModal.on('click', function(e) {
            if (e.target === this) {
                presetModal.css('display', 'none');
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
                        // Load models first, then set model value
                        loadModelsForPresetEditor('#universal-preset-model', preset.config.model || '');
                        // Load LoRAs
                        loadLorasIntoContainer('#universal-preset-loras-container', preset.config.loras || []);
                        $('#universal-preset-editor-title').text('Edit Universal Preset: ' + preset.name);
                        universalPresetModal.css('display', 'block');
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
                prompt_enhancer: $('#universal-preset-enhancer').val(),
                model: $('#universal-preset-model').val() || '',
                loras: getLorasFromContainer('#universal-preset-loras-container')
            };
            
            $(this).prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        universalPresetModal.css('display', 'none');
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
                        universalPresetModal.css('display', 'none');
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
            universalPresetModal.css('display', 'none');
        });
        
        // Close modal on background click
        universalPresetModal.on('click', function(e) {
            if (e.target === this) {
                universalPresetModal.css('display', 'none');
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

