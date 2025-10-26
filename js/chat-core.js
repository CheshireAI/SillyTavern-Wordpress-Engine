/**
 * PNG Metadata Viewer - Chat Core Module
 * Core chat functionality extracted from main chat.js
 * Version: 1.0
 */

(function($) {
    'use strict';
    
    // Chat state management
    const chatState = {
        initialized: false,
        chatModeActive: false,
        characterData: null,
        characterId: null,
        originalBodyContent: null,
        sidebarOpen: true
    };

    // Configuration
    const CHAT_CONFIG = {
        breakpoint: 768,
        sidebarWidth: '350px',
        mobileSidebarWidth: '300px',
        autoSaveDelay: 2000,
        maxMessageLength: 5000,
        maxPromptLength: 2000
    };

    // Utility functions
    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    // Parse character data from metadata with validation
    function parseCharacterData(metadataStr) {
        try {
            // Validate input
            if (!metadataStr || typeof metadataStr !== 'string') {
                throw new Error('Invalid metadata format');
            }

            // Limit input length to prevent memory issues
            if (metadataStr.length > 100000) {
                throw new Error('Metadata too large');
            }

            // Try URI decode first
            if (metadataStr.includes('%')) {
                try {
                    metadataStr = decodeURIComponent(metadataStr);
                } catch (e) {
                    // Continue with original string if decode fails
                }
            }

            // Try direct parse
            try {
                const parsed = JSON.parse(metadataStr);
                
                // Validate parsed data structure
                if (typeof parsed !== 'object' || parsed === null) {
                    throw new Error('Invalid data structure');
                }
                
                return parsed;
            } catch (e) {
                // Try cleaning up the JSON string
                const cleanedStr = metadataStr.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
                const parsed = JSON.parse(cleanedStr);
                
                // Validate parsed data structure
                if (typeof parsed !== 'object' || parsed === null) {
                    throw new Error('Invalid data structure');
                }
                
                return parsed;
            }
        } catch (error) {
            throw new Error('Failed to parse character data: ' + error.message);
        }
    }

    // Extract character info from parsed data
    function extractCharacterInfo(characterData) {
        let character = null;

        // Handle different data structures
        if (characterData?.data) {
            character = characterData.data;
        } else if (characterData?.name) {
            character = characterData;
        } else if (Array.isArray(characterData) && characterData.length > 0) {
            character = characterData[0]?.data || characterData[0];
        } else {
            // Recursive search for character object
            const findCharacterObject = (obj) => {
                if (typeof obj !== 'object' || obj === null) return null;
                if (obj.name) return obj;
                for (const key in obj) {
                    const found = findCharacterObject(obj[key]);
                    if (found) return found;
                }
                return null;
            };
            character = findCharacterObject(characterData);
        }

        if (!character) {
            return {
                name: 'AI Character',
                description: '',
                personality: '',
                scenario: '',
                first_mes: 'Hello! How can I help you today?'
            };
        }

        return {
            name: character.name || 'Unnamed Character',
            description: character.description || '',
            personality: character.personality || '',
            scenario: character.scenario || '',
            first_mes: character.first_mes || '',
            mes_example: character.mes_example || '',
            creator: character.creator || '',
            tags: character.tags || [],
            system_prompt: character.system_prompt || `You are ${character.name || 'a helpful assistant'}.`
        };
    }

    // Generate character ID for conversations
    function generateCharacterId(characterData) {
        let name = 'unknown_character';
        try {
            if (typeof characterData === 'string') {
                const parsed = JSON.parse(characterData);
                name = parsed.name || parsed.data?.name || name;
            } else if (typeof characterData === 'object') {
                name = characterData.name || characterData.data?.name || name;
            }
        } catch(e) {
            // Use default name if parsing fails
        }
        return 'char_' + name.toLowerCase().replace(/[^a-z0-9]/g, '_');
    }

    // Simple and reliable scroll to bottom function
    function scrollToBottom() {
        // Try multiple possible scroll containers
        const possibleScrollElements = [
            '.fullscreen-chat-content',
            '#chat-history',
            '.fullscreen-chat-history',
            '.fullscreen-chat'
        ];
        
        let scrollElement = null;
        
        for (const selector of possibleScrollElements) {
            const $element = $(selector);
            if ($element.length) {
                const element = $element[0];
                
                // Check if this element can actually scroll
                if (element.scrollHeight > element.clientHeight) {
                    scrollElement = element;
                    break;
                }
            }
        }
        
        if (scrollElement) {
            // Force scroll to bottom immediately
            scrollElement.scrollTop = scrollElement.scrollHeight;
            
            // Also try smooth scroll as backup
            setTimeout(() => {
                try {
                    scrollElement.scrollTo({
                        top: scrollElement.scrollHeight,
                        behavior: 'smooth'
                    });
                } catch (error) {
                    scrollElement.scrollTop = scrollElement.scrollHeight;
                }
            }, 10);
        }
    }

    // Handle chat errors
    function handleChatError(errorMessage) {
        $('.typing-indicator').remove();
        
        $('#chat-history').append(`
            <div class="chat-message error">
                <span class="speaker-name">Error:</span>
                <span class="chat-message-content-wrapper">${escapeHtml(errorMessage)}</span>
            </div>
        `);
        
        scrollToBottom();
        
        // Notify PMV_ConversationManager of new message
        if (window.PMV_ConversationManager) {
            $(document).trigger('pmv:message:added', [{type: 'error', content: errorMessage}]);
        }

        setTimeout(function() {
            $('#send-chat').prop('disabled', false).text('Send');
        }, 100);
    }

    // Collect conversation history
    function collectConversationHistory() {
        const messages = [];
        $('#chat-history .chat-message').each(function() {
            const $msg = $(this);
            if ($msg.hasClass('typing-indicator') || $msg.hasClass('error')) return;
            
            let role = 'assistant';
            let content = $msg.find('.chat-message-content-wrapper').text() || $msg.text();

            if ($msg.hasClass('user')) {
                role = 'user';
                content = content.replace(/^You:\s*/i, '');
            } else if ($msg.hasClass('bot')) {
                role = 'assistant';
                const namePattern = new RegExp('^[^:]+:\\s*', 'i');
                content = content.replace(namePattern, '');
            }

            if (content.trim()) {
                messages.push({
                    role: role,
                    content: content.trim()
                });
            }
        });
        return messages;
    }

    // Load token usage statistics
    function loadTokenUsageStats() {
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_get_word_usage_stats',
                nonce: pmv_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#today-tokens').text(response.data.today_tokens.toLocaleString());
                    $('#monthly-tokens').text(response.data.monthly_tokens.toLocaleString());
                } else {
                    $('#today-tokens').text('Error');
                    $('#monthly-tokens').text('Error');
                }
            },
            error: function() {
                $('#today-tokens').text('Error');
                $('#monthly-tokens').text('Error');
            }
        });
    }

    // Load image usage statistics
    function loadImageUsageStats(provider = 'swarmui') {
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_get_image_usage_stats',
                nonce: pmv_ajax_object.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    $('#today-images').text(response.data.today_images.toLocaleString());
                    $('#monthly-images').text(response.data.monthly_images.toLocaleString());
                } else {
                    $('#today-images').text('Error');
                    $('#monthly-images').text('Error');
                }
            },
            error: function() {
                $('#today-images').text('Error');
                $('#monthly-images').text('Error');
            }
        });
    }

    // Load credit balances from subscription system
    function loadCreditBalances() {
        // Debug logging
        console.log('PMV: Loading credit balances...');
        console.log('PMV: AJAX object:', typeof pmv_ajax_object !== 'undefined' ? pmv_ajax_object : 'undefined');
        
        if (typeof pmv_ajax_object === 'undefined') {
            console.error('PMV: pmv_ajax_object is not defined!');
            jQuery('#token-credits').text('No AJAX');
            jQuery('#image-credits').text('No AJAX');
            return;
        }
        
        if (!pmv_ajax_object.ajax_url) {
            console.error('PMV: AJAX URL is missing!');
            jQuery('#token-credits').text('No URL');
            jQuery('#image-credits').text('No URL');
            return;
        }
        
        // Enhanced user authentication check with detailed logging
        console.log('PMV: User ID check:', {
            user_id: pmv_ajax_object.user_id,
            user_id_type: typeof pmv_ajax_object.user_id,
            is_logged_in: pmv_ajax_object.is_logged_in,
            is_logged_in_type: typeof pmv_ajax_object.is_logged_in,
            guest_limits: pmv_ajax_object.guest_limits
        });
        
        // Check if user is logged in - use both user_id and is_logged_in flag
        if ((pmv_ajax_object.user_id === 0 || !pmv_ajax_object.user_id) && !pmv_ajax_object.is_logged_in) {
            console.log('PMV: User not logged in, showing guest limits');
            
            // Show guest limits if available
            if (pmv_ajax_object.guest_limits) {
                jQuery('#token-credits').text(pmv_ajax_object.guest_limits.monthly_tokens.toLocaleString());
                jQuery('#image-credits').text(pmv_ajax_object.guest_limits.daily_images.toLocaleString());
                
                // Add guest styling
                jQuery('#token-credits').addClass('guest-credits');
                jQuery('#image-credits').addClass('guest-credits');
            } else {
                jQuery('#token-credits').text('Guest');
                jQuery('#image-credits').text('Guest');
            }
            return;
        }
        
        console.log('PMV: User appears to be logged in, attempting AJAX credit fetch...');
        
        // Check if credit elements exist
        const tokenCreditsElement = jQuery('#token-credits');
        const imageCreditsElement = jQuery('#image-credits');
        
        if (!tokenCreditsElement.length || !imageCreditsElement.length) {
            console.error('PMV: Credit elements not found!', {
                tokenCredits: tokenCreditsElement.length,
                imageCredits: imageCreditsElement.length
            });
            
            // Retry after a short delay in case of timing issues
            setTimeout(() => {
                console.log('PMV: Retrying credit loading after delay...');
                loadCreditBalances();
            }, 200);
            return;
        }
        
        console.log('PMV: Credit elements found, proceeding with AJAX request...');
        
        // Show guest credits immediately as a fallback
        if (pmv_ajax_object.guest_limits) {
            jQuery('#token-credits').text(pmv_ajax_object.guest_limits.monthly_tokens.toLocaleString());
            jQuery('#image-credits').text(pmv_ajax_object.guest_limits.daily_images.toLocaleString());
            jQuery('#token-credits, #image-credits').addClass('guest-credits');
        }
        
        // Debug AJAX object details
        console.log('PMV: AJAX request details:', {
            url: pmv_ajax_object.ajax_url,
            nonce: pmv_ajax_object.nonce,
            nonce_length: pmv_ajax_object.nonce ? pmv_ajax_object.nonce.length : 0,
            user_id: pmv_ajax_object.user_id,
            is_logged_in: pmv_ajax_object.is_logged_in
        });
        
        // Test basic AJAX connectivity first
        console.log('PMV: Testing basic AJAX connectivity...');
        jQuery.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_test_nonce',
                nonce: pmv_ajax_object.nonce
            },
            success: function(response) {
                console.log('PMV: Nonce test successful:', response);
                // Now try the actual credit request
                makeCreditRequest();
            },
            error: function(xhr, status, error) {
                console.error('PMV: Nonce test failed:', {xhr, status, error});
                console.error('PMV: Nonce test HTTP Status:', xhr.status);
                console.error('PMV: Nonce test Response Text:', xhr.responseText);
                // Try the credit request anyway
                makeCreditRequest();
            }
        });
        
        function makeCreditRequest() {
            // Use the working credit endpoint that mimics the shortcode exactly
            console.log('PMV: Getting credits using working endpoint...');
            jQuery.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_get_working_credits',
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    console.log('PMV: Working credit endpoint successful:', response);
                    if (response.success && response.data.credits) {
                        const credits = response.data.credits;
                        const usage = response.data.usage;
                        console.log('PMV: Credits and usage retrieved:', {credits, usage});
                        
                        // Update credit display exactly like the shortcode shows
                        jQuery('#token-credits').text(credits.text_credits.toLocaleString());
                        jQuery('#image-credits').text(credits.image_credits.toLocaleString());
                        
                        // Remove any existing classes
                        jQuery('#token-credits').removeClass('guest-credits low-credits');
                        jQuery('#image-credits').removeClass('guest-credits low-credits');
                        
                        // Add visual indicators for low credits
                        if (credits.text_credits < 1000) {
                            jQuery('#token-credits').addClass('low-credits');
                        }
                        
                        if (credits.image_credits < 10) {
                            jQuery('#image-credits').addClass('low-credits');
                        }
                        
                        console.log('PMV: Credit display updated successfully');
                    } else {
                        console.warn('PMV: Working credit response not successful:', response);
                        fallbackToGuestLimits();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PMV: Working credit endpoint failed:', {xhr, status, error});
                    console.error('PMV: Working credit HTTP Status:', xhr.status);
                    console.error('PMV: Working credit Response Text:', xhr.responseText);
                    
                    // Fallback to guest limits
                    fallbackToGuestLimits();
                }
            });
        }
    }
    
    // Fallback function to show guest limits when AJAX fails
    function fallbackToGuestLimits() {
        console.log('PMV: Falling back to guest limits due to AJAX failure');
        
        if (pmv_ajax_object.guest_limits) {
            jQuery('#token-credits').text(pmv_ajax_object.guest_limits.monthly_tokens.toLocaleString());
            jQuery('#image-credits').text(pmv_ajax_object.guest_limits.daily_images.toLocaleString());
            
            // Add guest styling
            jQuery('#token-credits').removeClass('low-credits').addClass('guest-credits');
            jQuery('#image-credits').removeClass('low-credits').addClass('guest-credits');
        } else {
            jQuery('#token-credits').text('N/A');
            jQuery('#image-credits').text('N/A');
        }
    }

    // Initialize core chat functionality
    function initialize() {
        if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.chat_button_text) {
            window.pmv_chat_button_text = pmv_ajax_object.chat_button_text;
        }

        chatState.initialized = true;
    }
    
    // Start periodic credit balance updates
    function startCreditBalanceUpdates() {
        // Update credits every 30 seconds
        setInterval(function() {
            if (window.PMV_ChatCore && window.PMV_ChatCore.loadCreditBalances) {
                window.PMV_ChatCore.loadCreditBalances();
            }
        }, 30000);
        
        // Add click handler for refresh button
        jQuery(document).on('click', '#refresh-credits', function() {
            if (window.PMV_ChatCore && window.PMV_ChatCore.loadCreditBalances) {
                window.PMV_ChatCore.loadCreditBalances();
                // Add a visual feedback
                jQuery(this).css('transform', 'rotate(360deg)');
                setTimeout(() => {
                    jQuery(this).css('transform', '');
                }, 500);
            }
        });
    }

    // Expose functions to global scope
    window.PMV_ChatCore = {
        chatState,
        CHAT_CONFIG,
        escapeHtml,
        parseCharacterData,
        extractCharacterInfo,
        generateCharacterId,
        scrollToBottom,
        handleChatError,
        collectConversationHistory,
        loadTokenUsageStats,
        loadImageUsageStats,
        loadCreditBalances,
        startCreditBalanceUpdates,
        initialize
    };
    
    // Debug function for troubleshooting
    window.debugCredits = function() {
        console.log('=== PMV Credit Debug ===');
        console.log('AJAX Object:', typeof pmv_ajax_object !== 'undefined' ? pmv_ajax_object : 'undefined');
        console.log('User ID:', pmv_ajax_object?.user_id);
        console.log('Is Logged In:', pmv_ajax_object?.is_logged_in);
        console.log('Guest Limits:', pmv_ajax_object?.guest_limits);
        console.log('Credit Elements:', {
            tokenCredits: jQuery('#token-credits').length ? jQuery('#token-credits').text() : 'Not found',
            imageCredits: jQuery('#image-credits').length ? jQuery('#image-credits').text() : 'Not found'
        });
        
        if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.ajax_url) {
            console.log('Testing credit endpoint...');
            jQuery.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_get_credit_status',
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    console.log('Credit endpoint response:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Credit endpoint error:', {xhr, status, error});
                    
                    // Test the test endpoint
                    console.log('Testing test endpoint...');
                    jQuery.ajax({
                        url: pmv_ajax_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'pmv_test_credit_system',
                            nonce: pmv_ajax_object.nonce
                        },
                        success: function(testResponse) {
                            console.log('Test endpoint response:', testResponse);
                        },
                        error: function(testXhr, testStatus, testError) {
                            console.error('Test endpoint error:', {testXhr, testStatus, testError});
                        }
                    });
                }
            });
        }
    };
    
    // Manual credit loading function for testing
    window.testCreditSystem = function() {
        console.log('=== Testing Credit System ===');
        
        if (typeof pmv_ajax_object === 'undefined') {
            console.error('pmv_ajax_object not available');
            return;
        }
        
        // Test the test endpoint first
        jQuery.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pmv_test_credit_system',
                nonce: pmv_ajax_object.nonce
            },
            success: function(response) {
                console.log('✅ Test endpoint works:', response);
                
                // If test works, try the real credit endpoint
                jQuery.ajax({
                    url: pmv_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pmv_get_credit_status',
                        nonce: pmv_ajax_object.nonce
                    },
                    success: function(creditResponse) {
                        console.log('✅ Credit endpoint works:', creditResponse);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Credit endpoint fails:', {xhr, status, error});
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('❌ Test endpoint fails:', {xhr, status, error});
            }
        });
    };

    // Initialize when document is ready
    jQuery(document).ready(function() {
        initialize();
    });

})(jQuery); 