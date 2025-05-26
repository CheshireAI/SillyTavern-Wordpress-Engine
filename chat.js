// PNG Metadata Viewer - Core Chat Module - UNIVERSAL LAYOUT
console.log('PNG Metadata Viewer Chat Module Loaded');
(function($) {
    // Debug logging for initialization
    $(document).ready(function() {
        console.log('Chat module DOM ready');
        // Check for AJAX object
        if (typeof pmv_ajax_object !== 'undefined') {
            console.log('AJAX object available:', pmv_ajax_object.ajax_url);
            console.log('API Model:', pmv_ajax_object.api_model || 'Not specified');
            console.log('API Base URL:', pmv_ajax_object.api_base_url || 'Not specified');
        } else {
            console.error('AJAX object not available! The pmv_ajax_object must be localized in PHP.');
            alert('PNG Metadata Viewer: Chat module initialization error. See console for details.');
        }
        // Get and store chat button text from settings or use default
        window.pmv_chat_button_text = pmv_ajax_object?.chat_button_text || 'Chat';
    });

    // Screen size detection (for layout optimizations only)
    function isSmallScreen() {
        return window.innerWidth <= 768;
    }

    // Desktop detection
    function isDesktop() {
        return window.innerWidth > 768;
    }

    // Touch device detection (for touch-specific features only)
    function isTouchDevice() {
        return ('ontouchstart' in window) || 
               (navigator.maxTouchPoints > 0) || 
               (navigator.msMaxTouchPoints > 0);
    }

    // Initialize PMV_Chat object with enhanced methods
    window.PMV_Chat = {
        parseCharacterData: function(metadataStr) {
            try {
                if (!metadataStr || typeof metadataStr !== 'string') {
                    console.error('Invalid metadata:', metadataStr);
                    throw new Error('Invalid metadata format');
                }
                console.log('Parsing metadata, length:', metadataStr.length);
                
                // First attempt URI decode if needed
                if (metadataStr.indexOf('%') !== -1) {
                    try {
                        metadataStr = decodeURIComponent(metadataStr);
                        console.log('URI decoded metadata');
                    } catch (e) {
                        console.warn('URI decode failed:', e.message);
                    }
                }
                
                // Then attempt HTML entity decoding
                if (metadataStr.indexOf('&quot;') !== -1 || metadataStr.indexOf('&#039;') !== -1) {
                    try {
                        const decodedStr = $('<div/>').html(metadataStr).text();
                        const result = JSON.parse(decodedStr);
                        console.log('Successfully parsed metadata after HTML decoding');
                        return result;
                    } catch (e) {
                        console.warn('HTML decode parse failed:', e.message);
                    }
                }
                
                // Then try direct parse
                try {
                    const result = JSON.parse(metadataStr);
                    console.log('Successfully parsed metadata directly');
                    return result;
                } catch (e) {
                    console.warn('Direct parse failed:', e.message);
                    // Try cleaning up the JSON string
                    try {
                        const cleanedStr = metadataStr.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
                        const result = JSON.parse(cleanedStr);
                        console.log('Successfully parsed metadata after cleaning');
                        return result;
                    } catch (e2) {
                        console.warn('Cleaned parse failed:', e2.message);
                        throw new Error('JSON parse error: ' + e.message);
                    }
                }
            } catch (error) {
                console.error('Error parsing character data:', error);
                throw new Error('Failed to parse character data: ' + error.message);
            }
        },

        extractCharacterInfo: function(characterData) {
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
                console.warn('Could not find character info in data, using default');
                return {
                    name: 'AI Character',
                    description: '',
                    personality: '',
                    scenario: '',
                    system_prompt: 'You are a helpful assistant.'
                };
            }

            // Important: extract system_prompt properly
            const systemPrompt = character.system_prompt ||
                            characterData.system_prompt ||
                            `You are ${character.name || 'a helpful assistant'}.`;

            return {
                name: character.name || 'Unnamed Character',
                description: character.description || '',
                personality: character.personality || '',
                scenario: character.scenario || '',
                first_mes: character.first_mes || '',
                mes_example: character.mes_example || '',
                creator: character.creator || '',
                character_version: character.character_version || '',
                system_prompt: systemPrompt,
                post_history_instructions: character.post_history_instructions || '',
                tags: character.tags || [],
                alternate_greetings: character.alternate_greetings || [],
                character_book: character.character_book || null,
                creator_notes: character.creator_notes || ''
            };
        },

        resetChatState: function() {
            window.chatInProgress = false;
            console.log('Chat state reset');
            // Remove body and html classes when chat is closed
            $('body, html').removeClass('chat-modal-open');
        },

        // Collect conversation history
        collectConversationHistory: function() {
            const messages = [];
            $('.chat-message').each(function() {
                const $msg = $(this);
                // Skip error messages and typing indicators
                if ($msg.hasClass('error') || $msg.hasClass('typing-indicator')) {
                    return;
                }

                let role = 'assistant';
                let content = $msg.text();

                if ($msg.hasClass('user')) {
                    role = 'user';
                    content = content.replace(/^You:\s*/i, '');
                } else if ($msg.hasClass('bot')) {
                    role = 'assistant';
                    // Extract just the content after the character name
                    const namePattern = new RegExp('^[^:]+:\\s*', 'i');
                    content = content.replace(namePattern, '');
                }

                messages.push({
                    role: role,
                    content: content.trim()
                });
            });
            return messages;
        },

        // Load conversation into chat history
        loadConversationIntoChat: function(conversationData) {
            console.log('Loading conversation into chat:', conversationData);
            const $chatHistory = $('#chat-history');
            if (!$chatHistory.length) {
                console.error('Chat history element not found');
                return;
            }

            // Clear current chat history
            $chatHistory.empty();

            // Get character name from modal data or conversation data
            const $modal = $('#png-modal').find('.png-modal-content');
            const characterData = $modal.data('characterData');
            const character = this.extractCharacterInfo(characterData || conversationData.character || {});
            const characterName = character.name || 'AI';

            // Add messages from conversation
            if (conversationData.messages && Array.isArray(conversationData.messages)) {
                conversationData.messages.forEach(msg => {
                    const isUser = msg.role === 'user';
                    const messageClass = isUser ? 'user' : 'bot';
                    const speakerName = isUser ? 'You' : characterName;
                    $chatHistory.append(`
                        <div class="chat-message ${messageClass}">
                            <strong>${escapeHtml(speakerName)}:</strong>
                            <span class="chat-message-content-wrapper">${escapeHtml(msg.content)}</span>
                        </div>
                    `);
                });
            } else if (conversationData.content) {
                // Legacy format support
                $chatHistory.html(conversationData.content);
            }

            // Update conversation state
            $modal.data('isNewConversation', false);

            // Update character name in header if needed
            $('.chat-modal-name').text(characterName);

            // Force scroll to bottom
            setTimeout(() => {
                window.forceScrollToBottom();
                
                // Additional universal layout fixes
                if (window.PMV_MobileChat) {
                    window.PMV_MobileChat.setupMobileFlexLayout();
                    window.PMV_MobileChat.forceScrollToBottom();
                    window.PMV_MobileChat.fixChatVisibility();
                }
            }, 100);
        },

        isReady: true
    };

    // Enhanced forceScrollToBottom - universal version
    window.forceScrollToBottom = function() {
        const $history = $('#chat-history');
        if (!$history.length) return;

        if (window.PMV_MobileChat) {
            // Use universal scroll function
            window.PMV_MobileChat.forceScrollToBottom();
        } else {
            // Fallback scroll behavior
            requestAnimationFrame(() => {
                $history.scrollTop($history[0].scrollHeight);
                setTimeout(() => {
                    $history.scrollTop($history[0].scrollHeight);
                }, 50);
            });
        }
    };

    // Modal Management System
    function openCharacterModal(card) {
        try {
            const $card = $(card);
            const metadataStr = $card.attr('data-metadata');
            const fileUrl = $card.data('file-url');

            if (!metadataStr) throw new Error('No metadata found on this card');

            const characterData = window.PMV_Chat.parseCharacterData(metadataStr);
            const character = window.PMV_Chat.extractCharacterInfo(characterData);

            const modalHtml = buildCharacterModalHtml(character, fileUrl, metadataStr);
            $('#modal-content').html(modalHtml);
            $('#png-modal').fadeIn();
            setupTabSwitching();
        } catch (error) {
            console.error('Error opening character modal:', error);
            showErrorModal(error.message, $card.data('file-url'));
        }
    }

    // Character modal HTML builder
    function buildCharacterModalHtml(character, fileUrl, rawMetadata) {
        console.log('Building character modal HTML for:', character.name);
        
        const name = character.name || 'Unnamed Character';
        const description = character.description || '';
        const personality = character.personality || '';
        const scenario = character.scenario || '';
        const firstMes = character.first_mes || '';
        const mesExample = character.mes_example || '';
        const creator = character.creator || '';
        const version = character.character_version || '';
        const systemPrompt = character.system_prompt || '';
        const postHistory = character.post_history_instructions || '';
        const creatorNotes = character.creator_notes || '';
        const tags = character.tags || [];
        const alternateGreetings = character.alternate_greetings || [];
        const characterBook = character.character_book || null;

        // Safely encode metadata for chat button
        let escapedMetadata;
        try {
            escapedMetadata = encodeURIComponent(typeof rawMetadata === 'string' ? rawMetadata : JSON.stringify(rawMetadata));
        } catch (e) {
            console.error('Error encoding metadata:', e);
            escapedMetadata = '';
        }

        // Build sections
        let sections = [];

        if (description) {
            sections.push(`<div class="character-section"><h3>Description</h3><div class="character-field">${escapeHtml(description)}</div></div>`);
        }

        if (personality) {
            sections.push(`<div class="character-section"><h3>Personality</h3><div class="character-field">${escapeHtml(personality)}</div></div>`);
        }

        if (scenario) {
            sections.push(`<div class="character-section"><h3>Scenario</h3><div class="character-field">${escapeHtml(scenario)}</div></div>`);
        }

        if (firstMes) {
            sections.push(`<div class="character-section"><h3>First Message</h3><div class="character-field">${escapeHtml(firstMes)}</div></div>`);
        }

        if (alternateGreetings.length > 0) {
            let greetings = alternateGreetings.map((greeting, index) =>
                `<div class="greeting-item"><strong>Greeting ${index + 1}:</strong><div>${escapeHtml(greeting)}</div></div>`
            ).join('');
            sections.push(`<div class="character-section"><h3>Alternate Greetings</h3><div class="character-field">${greetings}</div></div>`);
        }

        if (mesExample) {
            sections.push(`<div class="character-section"><h3>Example Messages</h3><div class="character-field example-messages">${escapeHtml(mesExample)}</div></div>`);
        }

        if (systemPrompt) {
            sections.push(`<div class="character-section"><h3>System Prompt</h3><div class="character-field system-prompt">${escapeHtml(systemPrompt)}</div></div>`);
        }

        if (postHistory) {
            sections.push(`<div class="character-section"><h3>Post-History Instructions</h3><div class="character-field">${escapeHtml(postHistory)}</div></div>`);
        }

        if (characterBook && characterBook.entries && characterBook.entries.length > 0) {
            let entries = characterBook.entries.map((entry, index) => {
                const keys = entry.keys || [];
                const content = entry.content || '';
                const constant = entry.constant ? ' (Always Active)' : '';
                return `<div class="lorebook-entry"><div class="entry-header"><strong>Entry ${index + 1}:</strong> ${keys.map(key => `<span class="key-tag">${escapeHtml(key)}</span>`).join('')}${constant}</div><div class="entry-content">${escapeHtml(content)}</div></div>`;
            }).join('');
            sections.push(`<div class="character-section"><h3>Character Book (${characterBook.entries.length} entries)</h3><div class="character-field">${entries}</div></div>`);
        }

        if (tags.length > 0) {
            let tagHtml = tags.map(tag => `<span class="tag-item">${escapeHtml(tag)}</span>`).join('');
            sections.push(`<div class="character-section"><h3>Tags</h3><div class="character-field"><div class="tags-container">${tagHtml}</div></div></div>`);
        }

        if (creatorNotes) {
            sections.push(`<div class="character-section"><h3>Creator Notes</h3><div class="character-field">${escapeHtml(creatorNotes)}</div></div>`);
        }

        // Tech info section
        sections.push(`<div class="character-section"><h3>Technical Information</h3><div class="character-field"><div class="tech-info"><div class="tech-row"><strong>Character Version:</strong> ${version || 'Not specified'}</div><div class="tech-row"><strong>Creator:</strong> ${creator || 'Unknown'}</div><div class="tech-row"><strong>Has Character Book:</strong> ${characterBook && characterBook.entries && characterBook.entries.length > 0 ? 'Yes (' + characterBook.entries.length + ' entries)' : 'No'}</div><div class="tech-row"><strong>Alternate Greetings:</strong> ${alternateGreetings.length}</div><div class="tech-row"><strong>Tags:</strong> ${tags.length}</div></div></div></div>`);

        // Build modal HTML
        const html = `<div class="character-modal-wrapper"><div class="character-details"><div class="character-header"><h2>${escapeHtml(name)}</h2>${creator ? `<div class="character-creator">Created by: ${escapeHtml(creator)}</div>` : ''}${version ? `<div class="character-version">Version: ${escapeHtml(version)}</div>` : ''}</div><div class="character-image"><img src="${fileUrl}" alt="${escapeHtml(name)}"></div><div id="character-info">${sections.join('')}</div><div class="character-footer"><a href="${fileUrl}" class="png-download-button" download>Download</a><button class="png-chat-button" data-metadata="${escapedMetadata}">${window.pmv_chat_button_text || 'Chat'}</button></div></div></div>`;

        return html;
    }

    // Simplified setupTabSwitching function
    function setupTabSwitching() {
        // No tabs needed - simple layout
        console.log('Character modal loaded - simple layout');
    }

    // Error modal function
    function showErrorModal(errorMessage, fileUrl) {
        $('#modal-content').html(
            `<div class="character-modal-wrapper">
                <div class="character-details">
                    <div class="character-header">
                        <h2>Error Loading Character</h2>
                    </div>
                    <div class="character-section">
                        <p>${escapeHtml(errorMessage)}</p>
                        <p>Please check the browser console for more details.</p>
                        ${fileUrl ? `
                        <div class="character-actions">
                            <a href="${fileUrl}" class="png-download-button" download>Download Character</a>
                        </div>` : ''}
                    </div>
                </div>
            </div>`
        );
        $('#png-modal').fadeIn();
    }

    // Universal chat system - same layout for all devices with desktop enhancements
    function startChat(metadata) {
        try {
            console.log('Starting chat with character data');
            window.chatInProgress = true;

            const parsedData = window.PMV_Chat.parseCharacterData(metadata);
            const character = window.PMV_Chat.extractCharacterInfo(parsedData);
            const botId = 'bot_' + (character.name || 'character').replace(/[^a-z0-9]/gi, '_').toLowerCase();

            // Create universal fullscreen chat UI - same for all devices
            const chatHtml = `
                <div class="chat-container universal-layout">
                    <div class="chat-main">
                        <div id="chat-header">
                            <button id="mobile-menu-toggle" class="mobile-menu-btn">☰</button>
                            <div class="chat-modal-name">${escapeHtml(character.name)}</div>
                            <button id="close-chat" class="close-chat-btn">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div id="chat-history" class="chat-history">
                            <div class="chat-message bot">
                                <strong>${escapeHtml(character.name)}:</strong>
                                <span class="chat-message-content-wrapper">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                            </div>
                        </div>
                        <div id="chat-input-row" class="chat-input-container">
                            <div class="input-wrapper">
                                <textarea id="chat-input" placeholder="Type your message..." rows="1"></textarea>
                                <button id="send-chat" class="chat-send-button">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#modal-content').html(chatHtml);

            // Add proper classes and setup for fullscreen mode
            $('body, html').addClass('chat-modal-open');
            $('#png-modal').addClass('fullscreen-chat');

            // Store character data and bot ID on the modal
            $('#png-modal').find('.png-modal-content').addClass('chat-mode')
                .data({
                    'characterData': parsedData,
                    'botId': botId,
                    'isNewConversation': true
                });

            // Handle input resize with universal support
            $('#chat-input').on('input', function() {
                const maxHeight = 120;
                const minHeight = 50;
                
                this.style.height = 'auto';
                const newHeight = Math.min(Math.max(this.scrollHeight, minHeight), maxHeight);
                this.style.height = newHeight + 'px';
                
                // Universal layout updates
                setTimeout(() => {
                    window.forceScrollToBottom();
                }, 50);
            }).focus();

            // Show the modal and apply layout
            $('#png-modal').fadeIn(100, function() {
                console.log('Applying universal layout');
                
                if (window.PMV_MobileChat) {
                    // Initialize universal system if not already done
                    if (!window.PMV_MobileChat.isInitialized()) {
                        window.PMV_MobileChat.init();
                    }
                    
                    setTimeout(() => {
                        window.PMV_MobileChat.setupMobileFlexLayout();
                        window.PMV_MobileChat.setupMessageObserver();
                        window.PMV_MobileChat.forceScrollToBottom();
                        
                        // Apply desktop-specific fixes if needed
                        if (isDesktop()) {
                            console.log('🖥️ Applying desktop-specific chat fixes...');
                            window.PMV_MobileChat.applyDesktopFixes();
                            window.PMV_MobileChat.fixDesktopSendButton();
                        }
                    }, 200);
                }

                // Force scroll after layout
                setTimeout(window.forceScrollToBottom, 300);
            });

            // Initialize the conversation manager if available
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.init(parsedData, botId);
            }
        } catch (error) {
            console.error('Error starting chat:', error);
            alert('Error starting chat: ' + error.message);
            window.chatInProgress = false;
            // Remove body classes on error
            $('body, html').removeClass('chat-modal-open');
        }
    }

    // Helper function for HTML escaping
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

    // Enhanced chat message sending with universal support and desktop fixes
    window.sendChatMessage = function() {
        const inputField = $('#chat-input');
        const message = inputField.val().trim();
        if (!message) return;

        // Add user message to UI
        $('#chat-history').append(`
            <div class="chat-message user">
                <strong>You:</strong>
                <span class="chat-message-content-wrapper">${escapeHtml(message)}</span>
            </div>
        `);

        // Reset textarea height after sending
        inputField.val('').css('height', 'auto');
        const defaultHeight = 50;
        inputField.css('height', defaultHeight + 'px');

        // Add typing indicator
        $('#chat-history').append('<div class="typing-indicator">Thinking...</div>');

        // Enhanced scroll handling for universal layout
        setTimeout(() => {
            window.forceScrollToBottom();
        }, 50);

        // Get character data and conversation state
        const $modal = $('#png-modal').find('.png-modal-content');
        const characterData = $modal.data('characterData');
        const botId = $modal.data('botId');
        const isNewConversation = $modal.data('isNewConversation');

        // Collect conversation history (exclude the current message and typing indicator)
        const conversationHistory = window.PMV_Chat.collectConversationHistory();
        // Remove the last message (the one we just added) and typing indicator
        conversationHistory.pop(); // Remove current user message

        // Disable send button while processing
        const $sendButton = $('#send-chat');
        $sendButton.prop('disabled', true)
                   .text('Sending...')
                   .addClass('sending');

        // Prepare payload with character context and conversation history
        let characterDataStr;
        try {
            characterDataStr = typeof characterData === 'object' ?
                JSON.stringify(characterData) : characterData;
        } catch (e) {
            console.error('Error stringifying character data:', e);
            handleChatError('Failed to process character data');
            return;
        }

        // Build the AJAX payload
        const ajaxData = {
            action: 'start_character_chat',
            character_data: characterDataStr,
            user_message: message,
            bot_id: botId || 'default_bot',
            nonce: pmv_ajax_object.nonce
        };

        // Add conversation history if this is not a new conversation
        if (!isNewConversation && conversationHistory.length > 0) {
            ajaxData.conversation_history = JSON.stringify(conversationHistory);
        }

        console.log('Sending chat request with:', {
            characterName: characterData?.name || characterData?.data?.name,
            messageLength: message.length,
            historyLength: conversationHistory.length,
            isNewConversation: isNewConversation
        });

        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('API response received:', response);
                $('.typing-indicator').remove();

                if (response.success && response.data && response.data.choices && response.data.choices[0]) {
                    const botResponse = response.data.choices[0].message.content;
                    const characterName = escapeHtml(response.data.character?.name || characterData?.name || characterData?.data?.name || 'AI');

                    // Add bot message
                    $('#chat-history').append(`
                        <div class="chat-message bot">
                            <strong>${characterName}:</strong>
                            <span class="chat-message-content-wrapper">${escapeHtml(botResponse)}</span>
                        </div>
                    `);

                    // Mark conversation as no longer new after first exchange
                    $modal.data('isNewConversation', false);

                    // Update conversation manager if active
                    if (window.PMV_ConversationManager?.currentConversationId && botResponse) {
                        window.PMV_ConversationManager.updateCurrentConversationWithMessage('assistant', botResponse);
                    }

                    // Enhanced scroll handling
                    setTimeout(() => {
                        window.forceScrollToBottom();
                    }, 50);
                } else {
                    handleChatError(response.data?.message || 'API request failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Chat AJAX error:', {xhr, status, error});
                handleChatError(error || 'Connection error');
            },
            complete: function() {
                // Reset button
                setTimeout(function() {
                    $sendButton.prop('disabled', false)
                               .text('Send')
                               .removeClass('sending');
                }, 100);
                
                // Final scroll check
                setTimeout(() => {
                    window.forceScrollToBottom();
                }, 200);
            }
        });
    };

    // Store original send function in window scope
    window.originalSendChatMessage = window.sendChatMessage;

    function handleChatError(error) {
        $('.typing-indicator').remove();
        $('#chat-history').append(`
            <div class="chat-message error">
                <strong>Error:</strong>
                <span class="chat-message-content-wrapper">${escapeHtml(error)}</span>
            </div>
        `);
        
        // Enhanced error scroll handling
        setTimeout(() => {
            window.forceScrollToBottom();
        }, 50);

        // Reset send button on error
        setTimeout(function() {
            $('#send-chat').prop('disabled', false)
                          .text('Send')
                          .removeClass('sending');
        }, 100);
    }

    // Function to load conversations - enhanced for universal layout
    window.loadConversationsIntoSidebar = function() {
        console.log('Load conversations function called (universal layout)');
        if (window.PMV_MobileChat && window.PMV_MobileChat.loadConversations) {
            window.PMV_MobileChat.loadConversations();
        } else {
            console.warn('PMV_MobileChat not available for conversation loading');
        }
    };

    // Event Handling with universal enhancements and desktop support
    $(document)
        .on('click', '.png-image-container img', function(e) {
            console.log('Character card image clicked');
            e.preventDefault();
            openCharacterModal($(this).closest('.png-card'));
        })
        .on('click', '.png-chat-button', function(e) {
            console.log('Chat button clicked');
            e.preventDefault();
            const metadata = $(this).attr('data-metadata') ||
                            $(this).closest('.png-card').attr('data-metadata');
            console.log('Chat button metadata retrieved:', metadata ? 'Yes (length: ' + metadata.length + ')' : 'No');
            if (metadata) startChat(metadata);
            else alert('No character data found. Cannot start chat.');
        })
        .on('click', '.close-modal, #png-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('close-modal')) {
                $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
                $('#png-modal').removeClass('fullscreen-chat');
                // Reset chat state
                window.PMV_Chat.resetChatState();
                // Reset conversation manager if it exists
                if (window.PMV_ConversationManager) {
                    window.PMV_ConversationManager.reset();
                }
            }
        })
        .on('click', '#close-chat', function(e) {
            e.preventDefault();
            $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
            $('#png-modal').removeClass('fullscreen-chat');
            // Reset chat state
            window.PMV_Chat.resetChatState();
            // Reset conversation manager if it exists
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.reset();
            }
        })
        .on('click', '#send-chat', function() {
            window.sendChatMessage();
        })
        .on('click', '#mobile-menu-toggle', function(e) {
            e.preventDefault();
            console.log('Menu toggle clicked');
            if (window.PMV_MobileChat && window.PMV_MobileChat.openMenu) {
                window.PMV_MobileChat.openMenu();
            } else {
                console.warn('PMV_MobileChat.openMenu not available');
            }
        })
        .on('click', '.conversation-item', function(e) {
            e.preventDefault();
            const conversationId = $(this).data('conversation-id');
            console.log('Conversation item clicked:', conversationId);
            if (conversationId && window.PMV_ConversationManager?.loadConversation) {
                window.PMV_ConversationManager.loadConversation(conversationId);
                // Close menu after loading conversation
                if (window.PMV_MobileChat && window.PMV_MobileChat.closeMenu) {
                    window.PMV_MobileChat.closeMenu();
                }
            } else {
                console.warn('Cannot load conversation:', {
                    conversationId,
                    hasManager: !!window.PMV_ConversationManager,
                    hasLoadFunction: !!window.PMV_ConversationManager?.loadConversation
                });
            }
        })
        .on('keydown', '#chat-input', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                window.sendChatMessage();
            }
        });

    // Enhanced responsive functions with universal support and desktop fixes
    window.addEventListener('resize', function() {
        if (!window.chatInProgress) return;

        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(function() {
            console.log('Window resized, updating layout');
            if (window.PMV_MobileChat) {
                setTimeout(() => {
                    window.PMV_MobileChat.setupMobileFlexLayout();
                    window.PMV_MobileChat.forceScrollToBottom();
                    
                    // Apply desktop fixes after resize if needed
                    if (isDesktop()) {
                        window.PMV_MobileChat.applyDesktopFixes();
                        window.PMV_MobileChat.fixDesktopSendButton();
                    }
                }, 100);
            }
            window.forceScrollToBottom();
        }, 250);
    });

    // Enhanced orientation change handling
    window.addEventListener('orientationchange', function() {
        if (!window.chatInProgress) return;
        
        console.log('Orientation changed');
        setTimeout(function() {
            if (window.PMV_MobileChat) {
                window.PMV_MobileChat.setupMobileFlexLayout();
                window.PMV_MobileChat.forceScrollToBottom();
                
                // Apply appropriate fixes based on new orientation
                if (isDesktop()) {
                    window.PMV_MobileChat.applyDesktopFixes();
                    window.PMV_MobileChat.fixDesktopSendButton();
                }
            }
            window.forceScrollToBottom();
        }, 500); // Longer delay for orientation changes
    });

    console.log('PNG Metadata Viewer Chat Module Ready');

})(jQuery);
