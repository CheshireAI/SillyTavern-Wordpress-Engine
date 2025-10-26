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
        
        // Generate prompt
        $(document).on('click.imageSettings', '#generate-prompt-btn', function(e) {
            e.preventDefault();
            generateImagePrompt();
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

    // Create image with validation
    function createImage() {
        const prompt = $('#generated-prompt').val() || $('#custom-prompt').val();
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        const provider = settings.provider || 'swarmui';
        
        // Validate prompt
        if (!prompt || !prompt.trim()) {
            alert('Please generate or enter a prompt first.');
            return;
        }
        
        // Limit prompt length
        if (prompt.length > 2000) {
            alert('Prompt is too long (maximum 2000 characters)');
            return;
        }
        
        // Check for potentially harmful content
        if (prompt.includes('<script') || prompt.includes('javascript:')) {
            alert('Prompt contains invalid content');
            return;
        }
        
        // Get model based on provider
        let model;
        if (provider === 'nanogpt') {
            model = $('#default-model').val();
        } else {
            model = $('#swarmui-model').val();
        }
        
        // Validate model
        if (!model || !model.trim()) {
            alert('Please select a model.');
            return;
        }
        
        // Validate and sanitize parameters
        let steps = parseInt($('#image-steps').val()) || 20;
        let cfgScale = parseFloat($('#image-cfg-scale').val()) || 7.0;
        let width = parseInt($('#image-width').val()) || 512;
        let height = parseInt($('#image-height').val()) || 512;
        
        // Validate parameter ranges
        if (steps < 1 || steps > 100) {
            steps = 20;
        }
        if (cfgScale < 0.1 || cfgScale > 20.0) {
            cfgScale = 7.0;
        }
        if (width < 256 || width > 2048 || width % 64 !== 0) {
            width = 512;
        }
        if (height < 256 || height > 2048 || height % 64 !== 0) {
            height = 512;
        }
        
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
                negative_prompt: $('#negative-prompt').val() || ''
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
                $('#create-image-btn').prop('disabled', false).text('Create Image');
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
        
        // Populate form fields
        $('#custom-prompt-template').val(settings.custom_prompt_template || '');
        $('#use-full-history').prop('checked', settings.use_full_history !== false);
        $('#auto-trigger-keywords').val(settings.auto_trigger_keywords || '');
        $('#allow-prompt-editing').prop('checked', settings.allow_prompt_editing !== false);
        $('#image-provider').val(settings.provider || 'swarmui');
        $('#default-steps').val(settings.default_steps || 20);
        $('#default-cfg-scale').val(settings.default_cfg_scale || 7.0);
        $('#default-width').val(settings.default_width || 512);
        $('#default-height').val(settings.default_height || 512);
        $('#negative-prompt').val(settings.negative_prompt || '');
        
        // Load slash command templates
        $('#slash-self-template').val(settings.slash_self_template || '');
        $('#slash-generate-template').val(settings.slash_generate_template || '');
        $('#slash-look-template').val(settings.slash_look_template || '');
        $('#slash-custom1-template').val(settings.slash_custom1_template || '');
        $('#slash-custom2-template').val(settings.slash_custom2_template || '');
        $('#slash-custom3-template').val(settings.slash_custom3_template || '');
        
        // Update image panel with settings
        updateImagePanelWithSettings(settings);
    }

    // Save image settings
    function saveImageSettings() {
        const settings = {
            custom_prompt_template: $('#custom-prompt-template').val(),
            use_full_history: $('#use-full-history').is(':checked'),
            auto_trigger_keywords: $('#auto-trigger-keywords').val(),
            allow_prompt_editing: $('#allow-prompt-editing').is(':checked'),
            provider: $('#image-provider').val(),
            default_steps: parseInt($('#default-steps').val()) || 20,
            default_cfg_scale: parseFloat($('#default-cfg-scale').val()) || 7.0,
            default_width: parseInt($('#default-width').val()) || 512,
            default_height: parseInt($('#default-height').val()) || 512,
            negative_prompt: $('#negative-prompt').val(),
            slash_self_template: $('#slash-self-template').val(),
            slash_generate_template: $('#slash-generate-template').val(),
            slash_look_template: $('#slash-look-template').val(),
            slash_custom1_template: $('#slash-custom1-template').val(),
            slash_custom2_template: $('#slash-custom2-template').val(),
            slash_custom3_template: $('#slash-custom3-template').val()
        };
        
        localStorage.setItem('pmv_image_settings', JSON.stringify(settings));
        
        // Update current state
        Object.assign(imageState.settings, settings);
        imageState.currentProvider = settings.provider;
        
        alert('Settings saved successfully!');
    }

    // Update image panel with settings
    function updateImagePanelWithSettings(settings) {
        $('#image-steps').val(settings.default_steps || 20);
        $('#image-cfg-scale').val(settings.default_cfg_scale || 7.0);
        $('#image-width').val(settings.default_width || 512);
        $('#image-height').val(settings.default_height || 512);
    }

    // Setup auto-trigger for image generation
    function setupAutoTrigger() {
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        const keywords = settings.auto_trigger_keywords || '';
        
        if (keywords) {
            const keywordList = keywords.split(',').map(k => k.trim().toLowerCase());
            
            // Monitor chat input for keywords
            $(document).on('keyup', '#chat-input', function() {
                const input = $(this).val().toLowerCase();
                
                for (const keyword of keywordList) {
                    if (input.includes(keyword)) {
                        // Auto-trigger image generation
                        generateImagePrompt();
                        break;
                    }
                }
            });
        }
    }

    // Generate image prompt from chat history
    function generateImagePrompt() {
        const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
        const template = settings.custom_prompt_template || 'Create an image based on the conversation context';
        
        let context = '';
        
        if (settings.use_full_history !== false) {
            // Use full chat history
            const messages = window.PMV_ChatCore.collectConversationHistory();
            context = messages.map(msg => msg.content).join(' ');
        } else {
            // Use only last message
            const lastMessage = $('#chat-history .chat-message').last().find('.chat-message-content-wrapper').text();
            context = lastMessage;
        }
        
        // Apply template
        const prompt = template.replace('{context}', context).replace('{history}', context);
        
        $('#generated-prompt').val(prompt);
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
        setupAutoTrigger,
        generateImagePrompt,
        exportImageSettings,
        importImageSettings
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initializeImageGeneration();
    });

})(jQuery); 