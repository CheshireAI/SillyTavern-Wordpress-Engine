/**
 * PNG Metadata Viewer - Image Generation Module
 * Image generation functionality extracted from main chat.js
 * Version: 1.0
 */

(function($) {
    'use strict';
    
    // Image generation state
    const imageState = {
        currentProvider: 'swarmui',
        currentModel: '',
        settings: {},
        isGenerating: false
    };

    // Initialize image generation functionality
    function initializeImageGeneration() {
        // Use event delegation for all dynamically created elements
        $(document).off('click.imageSettings');
        
        // Image settings button
        $(document).on('click.imageSettings', '#image-settings-btn, .image-settings-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openImageSettings();
        });
        
        // Close settings modal
        $(document).on('click.imageSettings', '.close-settings-modal', function(e) {
            e.preventDefault();
            closeImageSettings();
        });
        
        // Handle clicking outside modal to close
        $(document).on('click.imageSettings', '#image-settings-modal', function(e) {
            if (e.target === this) {
                closeImageSettings();
            }
        });
        
        // Save settings
        $(document).on('click.imageSettings', '#save-settings', function(e) {
            e.preventDefault();
            saveImageSettings();
        });
        
        // Test connection
        $(document).on('click.imageSettings', '#test-connection', function(e) {
            e.preventDefault();
            testSwarmUIConnection();
        });
        
        // Export settings
        $(document).on('click.imageSettings', '#export-settings', function(e) {
            e.preventDefault();
            exportImageSettings();
        });
        
        // Import settings
        $(document).on('click.imageSettings', '#import-settings', function(e) {
            e.preventDefault();
            $('#import-file').click();
        });
        
        // Import file change
        $(document).on('change.imageSettings', '#import-file', function(e) {
            importImageSettings(e);
        });
        
        // Preset selection change
        $(document).on('change.imageSettings', '#image-preset', function(e) {
            e.preventDefault();
            updatePresetDescription();
        });
        
        // User description input
        $(document).on('input.imageSettings', '#user-description', function(e) {
            e.preventDefault();
            if ($('#image-preset').val()) {
                generateImagePromptFromPreset();
            }
        });
        
        // Create image
        $(document).on('click.imageSettings', '#create-image-btn', function(e) {
            e.preventDefault();
            createImage();
        });
        
        // Auto-trigger image generation based on keywords
        setupAutoTrigger();
        
        // Load saved settings
        loadImageSettings();
        
        // Load available models
        loadSwarmUIModels();
    }

    // Open image settings modal
    function openImageSettings() {
        const modal = $('#image-settings-modal');
        
        // Load settings
        loadImageSettings();
        
        // Show modal
        modal.show();
    }

    // Close image settings modal
    function closeImageSettings() {
        $('#image-settings-modal').hide();
    }

    // Load SwarmUI models
    function loadSwarmUIModels() {
        if (typeof pmv_ajax_object === 'undefined') {
            return;
        }
        
        // First get a session, then load models
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_get_swarmui_session',
                nonce: pmv_ajax_object.nonce
            },
            success: function(sessionResponse) {
                if (sessionResponse.success) {
                    // Now load models with session
                    $.ajax({
                        url: pmv_ajax_object.ajax_url,
                        type: 'POST',
                        timeout: 15000,
                        data: {
                            action: 'pmv_get_available_models',
                            nonce: pmv_ajax_object.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                populateModelDropdown(response.data);
                            } else {
                                $('#swarmui-model').html('<option value="">Error loading models</option>');
                            }
                        },
                        error: function() {
                            $('#swarmui-model').html('<option value="">Error loading models</option>');
                        }
                    });
                } else {
                    $('#swarmui-model').html('<option value="">Session creation failed</option>');
                }
            },
            error: function() {
                $('#swarmui-model').html('<option value="">Session creation failed</option>');
            }
        });
    }

    // Populate model dropdown
    function populateModelDropdown(modelsData) {
        const $select = $('#swarmui-model');
        const $defaultSelect = $('#default-model');
        
        $select.empty();
        $defaultSelect.empty();
        
        // Handle the response format from SwarmUI API
        let models = [];
        
        if (modelsData.models && typeof modelsData.models === 'object') {
            // Handle legacy format (organized by type)
            // Add Stable-Diffusion models first (these are the main models)
            if (modelsData.models['Stable-Diffusion'] && Array.isArray(modelsData.models['Stable-Diffusion'])) {
                const $optgroup = $('<optgroup>').attr('label', 'Stable Diffusion Models');
                modelsData.models['Stable-Diffusion'].forEach(model => {
                    $optgroup.append($('<option>').val(model).text(model));
                    models.push(model);
                });
                $select.append($optgroup);
                $defaultSelect.append($optgroup.clone());
            }
            
            // Add other model types if needed
            Object.keys(modelsData.models).forEach(category => {
                if (category !== 'Stable-Diffusion' && Array.isArray(modelsData.models[category]) && modelsData.models[category].length > 0) {
                    const $optgroup = $('<optgroup>').attr('label', category);
                    modelsData.models[category].forEach(model => {
                        $optgroup.append($('<option>').val(model).text(model));
                        models.push(model);
                    });
                    $select.append($optgroup);
                    $defaultSelect.append($optgroup.clone());
                }
            });
        }
        
        // If no models found, add a default option
        if (models.length === 0) {
            $select.append($('<option>').val('').text('No models available'));
            $defaultSelect.append($('<option>').val('').text('No models available'));
        } else {
            // Set default model if available
            const defaultModel = localStorage.getItem('pmv_default_model') || 'OfficialStableDiffusion/sd_xl_base_1.0';
            if (models.includes(defaultModel)) {
                $select.val(defaultModel);
                $defaultSelect.val(defaultModel);
            }
        }
    }

    // Test SwarmUI connection
    function testSwarmUIConnection() {
        $('#test-connection').prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_test_swarmui_connection',
                nonce: pmv_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Connection successful! Available models: ' + (response.data.models_count || 0));
                } else {
                    alert('✗ Connection failed: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('✗ Connection failed: Network error');
            },
            complete: function() {
                $('#test-connection').prop('disabled', false).text('Test Connection');
            }
        });
    }

    // Update preset description when selected
    function updatePresetDescription() {
        const presetId = $('#image-preset').val();
        if (!presetId) {
            $('#preset-description').text('');
            $('#final-prompt-section').hide();
            return;
        }
        
        // Get character filename from chatState if available
        const characterFilename = window.chatState?.characterFile || '';
        
        // Load presets from server
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_get_image_presets',
                nonce: pmv_ajax_object.nonce,
                character_filename: characterFilename
            },
            success: function(response) {
                if (response.success && response.data.presets) {
                    const presets = response.data.presets;
                    if (presets[presetId]) {
                        const preset = presets[presetId];
                        $('#preset-description').text(preset.description);
                        // Store preset config in data attribute for later use
                        $('#image-preset').data('preset-config', preset.config);
                        
                        // If user description exists, generate prompt
                        if ($('#user-description').val()) {
                            generateImagePromptFromPreset();
                        }
                    }
                }
            }
        });
    }
    
    // Generate prompt from preset
    function generateImagePromptFromPreset() {
        const presetId = $('#image-preset').val();
        const userDescription = $('#user-description').val();
        
        if (!presetId) {
            $('#final-prompt-section').hide();
            return;
        }
        
        if (!userDescription || !userDescription.trim()) {
            $('#final-prompt-section').hide();
            return;
        }
        
        // Get character filename from chatState if available
        const characterFilename = window.chatState?.characterFile || '';
        
        // Send to server for prompt generation with content filtering
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_generate_image_prompt',
                nonce: pmv_ajax_object.nonce,
                user_prompt: userDescription,
                preset_id: presetId,
                context: window.PMV_ChatCore ? JSON.stringify(window.PMV_ChatCore.collectConversationHistory().slice(-3)) : '{}',
                character_filename: characterFilename
            },
            success: function(response) {
                if (response.success) {
                    $('#generated-prompt').val(response.data.final_prompt);
                    $('#final-prompt-section').show();
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to generate prompt'));
                    $('#final-prompt-section').hide();
                }
            },
            error: function() {
                alert('Network error while generating prompt');
                $('#final-prompt-section').hide();
            }
        });
    }
    
    // Create image with validation
    function createImage() {
        const prompt = $('#generated-prompt').val();
        const presetId = $('#image-preset').val();
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        // Get provider from backend settings, not localStorage
        const provider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
        
        // Validate preset
        if (!presetId) {
            alert('Please select a type of image to create.');
            return;
        }
        
        // Validate prompt
        if (!prompt || !prompt.trim()) {
            alert('Please describe what you want to see.');
            return;
        }
        
        // Get preset config
        const presetConfig = $('#image-preset').data('preset-config');
        if (!presetConfig) {
            alert('Invalid preset configuration. Please refresh and try again.');
            return;
        }
        
        // Get default model - use hidden technical parameters from preset
        let model = $('#default-model').val() || $('#swarmui-model').val();
        
        // Validate model
        if (!model || !model.trim()) {
            // Fallback to default model
            model = 'OfficialStableDiffusion/sd_xl_base_1.0';
        }
        
        // Use preset configuration
        const steps = presetConfig.steps || 20;
        const cfgScale = presetConfig.cfg_scale || 7.0;
        const width = presetConfig.width || 512;
        const height = presetConfig.height || 512;
        const negativePrompt = presetConfig.negative_prompt || 'blurry, low quality, distorted';
        
        $('#create-image-btn').prop('disabled', true).text('Creating...');
        
        // Add progress indicator
        $('#image-results').html(`
            <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                <div class="spinner" style="border: 3px solid #333; border-top: 3px solid #007bff; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 10px;"></div>
                <p style="color: #e0e0e0; margin: 0;">Generating image...</p>
            </div>
        `);
        
        // Make AJAX request
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_generate_image_websocket',
                nonce: pmv_ajax_object.nonce,
                prompt: prompt,
                model: model,
                images_count: 1,
                width: width,
                height: height,
                steps: steps,
                cfg_scale: cfgScale,
                negative_prompt: negativePrompt
            },
            success: function(response) {
                if (response.success) {
                    displayImageResults(response.data);
                } else {
                    $('#image-results').html(`
                        <div style="text-align: center; padding: 20px; background: #2d2d2d; border: 1px solid #dc3545; border-radius: 4px;">
                            <p style="color: #dc3545; margin: 0;">Error: ${response.data?.message || 'Unknown error'}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#image-results').html(`
                    <div style="text-align: center; padding: 20px; background: #2d2d2d; border: 1px solid #dc3545; border-radius: 4px;">
                        <p style="color: #dc3545; margin: 0;">Network error occurred</p>
                    </div>
                `);
            },
            complete: function() {
                $('#create-image-btn').prop('disabled', false).text('🎨 Create Image');
            }
        });
    }

    // Display image results
    function displayImageResults(data) {
        if (!data || !data.images || !Array.isArray(data.images)) {
            $('#image-results').html(`
                <div style="text-align: center; padding: 20px; background: #2d2d2d; border: 1px solid #dc3545; border-radius: 4px;">
                    <p style="color: #dc3545; margin: 0;">No images generated</p>
                </div>
            `);
            return;
        }
        
        let resultsHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';
        
        data.images.forEach((image, index) => {
            resultsHtml += `
                <div style="background: #2d2d2d; border: 1px solid #444; border-radius: 8px; padding: 15px;">
                    <img src="${image.url}" alt="Generated image ${index + 1}" style="width: 100%; height: auto; border-radius: 4px; margin-bottom: 10px;">
                    <div style="display: flex; gap: 10px;">
                        <button onclick="downloadImage('${image.url}', 'generated_image_${index + 1}.png')" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Download</button>
                        <button onclick="addImageToChat('${image.url}')" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Add to Chat</button>
                    </div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
        $('#image-results').html(resultsHtml);
        
        // Consume image credits after successful generation
        if (window.consumeImageCredits) {
            window.consumeImageCredits(data.images.length);
        }
    }

    // Download image
    window.downloadImage = function(imageUrl, filename) {
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Add image to chat
    window.addImageToChat = function(imageUrl) {
        if (window.PMV_ChatCore && window.PMV_ChatCore.chatState.chatModeActive) {
            $('#chat-history').append(`
                <div class="chat-message bot">
                    <span class="speaker-name">Image:</span>
                    <span class="chat-message-content-wrapper">
                        <img src="${imageUrl}" alt="Generated image" style="max-width: 100%; border-radius: 4px;">
                    </span>
                </div>
            `);
            window.PMV_ChatCore.scrollToBottom();
        }
    };

    // Load image settings
    function loadImageSettings() {
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        
        // Provider is configured in backend only - no need to load it here
    }

    // Save image settings
    function saveImageSettings() {
        // Provider is configured in backend only - no settings to save from frontend
        const settings = {};
        
        localStorage.setItem('pmv_image_settings', JSON.stringify(settings));
        
        // Update current state
        Object.assign(imageState.settings, settings);
            // Provider is configured in backend only - get from pmv_ajax_object
            imageState.currentProvider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
        
        alert('Settings saved successfully!');
    }

    // Update image panel with settings (no longer needed, but kept for compatibility)
    function updateImagePanelWithSettings(settings) {
        // Preset system uses server-side configuration
        // No client-side technical parameters to set
    }


    // Export image settings
    function exportImageSettings() {
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        const dataStr = JSON.stringify(settings, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = 'pmv_image_settings.json';
        link.click();
    }

    // Import image settings
    function importImageSettings(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                localStorage.setItem('pmv_image_settings', JSON.stringify(settings));
                loadImageSettings();
                alert('Settings imported successfully!');
            } catch (error) {
                alert('Error importing settings: Invalid file format');
            }
        };
        reader.readAsText(file);
    }

    // Expose functions to global scope
    window.PMV_ImageGeneration = {
        imageState,
        initializeImageGeneration,
        openImageSettings,
        closeImageSettings,
        loadSwarmUIModels,
        populateModelDropdown,
        testSwarmUIConnection,
        createImage,
        displayImageResults,
        loadImageSettings,
        saveImageSettings,
        updateImagePanelWithSettings,
        exportImageSettings,
        importImageSettings,
        updatePresetDescription,
        generateImagePromptFromPreset
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initializeImageGeneration();
    });

})(jQuery); 