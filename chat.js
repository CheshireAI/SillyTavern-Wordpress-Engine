// PNG Metadata Viewer - Core Chat Module
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
        
        // CRITICAL FIX: Add hard-coded styles directly to ensure compatibility
        $('head').append(`
            <style id="pmv-chat-core-fixes">
                /* Force all chat messages to be visible with high contrast */
                .chat-message {
                    color: #FFFFFF !important;
                    background-color: #333333 !important;
                    padding: 12px !important;
                    margin: 8px 0 !important;
                    border-radius: 8px !important;
                    max-width: 85% !important;
                    align-self: flex-start !important;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
                    font-size: 16px !important;
                    line-height: 1.5 !important;
                    position: relative !important;
                    z-index: 50 !important;
                }
                .chat-message strong {
                    color: #FFFFFF !important;
                    font-weight: bold !important;
                }
                .chat-message.user {
                    background-color: #4a6da7 !important;
                    align-self: flex-end !important;
                }
                .chat-message.bot {
                    background-color: #444444 !important;
                }
                .chat-message.error {
                    background-color: #8B0000 !important;
                }
                
                /* Force sidebar to be visible when open */
                #chat-sidebar:not(.minimized) {
                    background-color: #222222 !important;
                    color: #FFFFFF !important;
                    left: 0 !important;
                    z-index: 99999999 !important;
                }
                #chat-sidebar.minimized {
                    left: -100% !important;
                }
                
                /* Fix all text in sidebar */
                #chat-sidebar h3, 
                #chat-sidebar .conversation-btn,
                #conversation-list, 
                .loading-conversations {
                    color: #FFFFFF !important;
                    font-size: 16px !important;
                }
                
                /* Fix chat history area */
                #chat-history {
                    background-color: #1a1a1a !important;
                    color: #FFFFFF !important;
                    padding: 20px !important;
                    padding-bottom: 90px !important;
                }
                
                /* Fix input area */
                #chat-input {
                    background-color: #FFFFFF !important;
                    color: #000000 !important;
                    border: 1px solid #444444 !important;
                    padding: 10px !important;
                    font-size: 16px !important;
                }
                
                #send-chat {
                    background-color: #4a6da7 !important;
                    color: white !important;
                    border: none !important;
                    padding: 10px 15px !important;
                    font-size: 16px !important;
                    font-weight: bold !important;
                    cursor: pointer !important;
                }
                
                /* Fix chat modal name */
                .chat-modal-name {
                    color: #FFFFFF !important;
                    font-size: 18px !important;
                    font-weight: bold !important;
                    text-align: center !important;
                }
                
                /* Fix chat header */
                #chat-header {
                    background-color: #222222 !important;
                    padding: 10px !important;
                    border-bottom: 1px solid #444444 !important;
                }
                
                /* Fix close button */
                #close-chat {
                    color: #FFFFFF !important;
                    background: transparent !important;
                    font-size: 24px !important;
                }
                
                /* Force body to be fixed when chat is open */
                .chat-modal-open {
                    overflow: hidden !important;
                    position: fixed !important;
                    width: 100% !important;
                    height: 100% !important;
                }
            </style>
        `);
    });

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
        
        debugCharacterData: function(metadata) {
            try {
                // First step: Parse the metadata
                console.log('Raw metadata type:', typeof metadata);
                
                // Fix for metadata not being a string
                let metadataStr = '';
                if (typeof metadata === 'string') {
                    metadataStr = metadata;
                } else if (typeof metadata === 'object') {
                    try {
                        metadataStr = JSON.stringify(metadata);
                    } catch (e) {
                        console.error('Cannot stringify metadata object:', e);
                        metadataStr = '{"name":"Error Character","error":"Cannot stringify metadata"}';
                    }
                } else {
                    metadataStr = '{"name":"Unknown Character","error":"Invalid metadata type"}';
                }
                
                console.log('Raw metadata (first 100 chars):', metadataStr.substring(0, 100));
                
                // Parse to JSON
                let parsed;
                try {
                    parsed = JSON.parse(metadataStr);
                    console.log('Parsed JSON metadata:', parsed);
                } catch (e) {
                    console.error('JSON parse failed:', e);
                    return {
                        success: false,
                        error: e.message,
                        metadata: metadataStr.substring(0, 100) + '...'
                    };
                }
                
                // Extract character info
                const character = this.extractCharacterInfo(parsed);
                console.log('Extracted character info:', character);
                
                return {
                    success: true,
                    character: character,
                    parsed: parsed,
                    metadata: metadataStr.substring(0, 100) + '...'
                };
            } catch (error) {
                console.error('Character data debug error:', error);
                return {
                    success: false,
                    error: error.message,
                    metadata: 'Error analyzing metadata'
                };
            }
        }
    };

    // Define forceScrollToBottom to handle fixed input area
    window.forceScrollToBottom = function() {
        const $history = $('#chat-history');
        if (!$history.length) return;
        
        // Use requestAnimationFrame to ensure DOM has updated
        requestAnimationFrame(() => {
            // Explicitly set scrollTop to ensure we're at the bottom
            $history.scrollTop($history[0].scrollHeight);
            
            // Double-check with a slight delay to ensure it worked
            setTimeout(() => {
                $history.scrollTop($history[0].scrollHeight);
            }, 50);
        });
    };

    // Helper function to fix chat visibility
    function fixChatVisibility() {
        const $history = $('#chat-history');
        if ($history.length) {
            $history.scrollTop($history[0].scrollHeight);
        }
    }

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
        
        // Build sections without whitespace
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
                return `<div class="lorebook-entry"><div class="entry-header"><strong>Entry ${index + 1}:</strong> ${keys.map(key =>` <span class="key-tag">${escapeHtml(key)}</span>`).join('')}${constant}</div><div class="entry-content">${escapeHtml(content)}</div></div>`;
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
        
        // Build modal HTML without any whitespace
        const html = `<div class="character-modal-wrapper"><div class="character-details"><div class="character-header"><h2>${escapeHtml(name)}</h2>${creator ?` <div class="character-creator">Created by: ${escapeHtml(creator)}</div> `: ''}${version ?` <div class="character-version">Version: ${escapeHtml(version)}</div> `: ''}</div><div class="character-image"><img src="${fileUrl}" alt="${escapeHtml(name)}" style="max-height:140px;"></div><div id="character-info">${sections.join('')}</div><div class="character-footer"><a href="${fileUrl}" class="png-download-button" download>Download</a><button class="png-chat-button" data-metadata="${escapedMetadata}">${window.pmv_chat_button_text || 'Chat'}</button></div></div></div>`;
        
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
                        ${fileUrl ?`
                        <div class="character-actions">
                            <a href="${fileUrl}" class="png-download-button" download>Download Character</a>
                        </div>` : ''}
                    </div>
                </div>
            </div>`
        );
        $('#png-modal').fadeIn();
    }

    // Desktop Sidebar Management
    function setupDesktopSidebar() {
        const $sidebar = $('#chat-sidebar');
        const sidebarWidth = '280px'; // Match the CSS variable
        
        // Set initial state
        if ($sidebar.hasClass('minimized')) {
            $sidebar.css('left', '-' + sidebarWidth);
            $('.chat-main').css({
                'left': '0',
                'width': '100%'
            });
            $('#chat-input-row').css('left', '0');
        } else {
            $sidebar.css('left', '0');
            $('.chat-main').css({
                'left': sidebarWidth,
                'width': 'calc(100% - ' + sidebarWidth + ')'
            });
            $('#chat-input-row').css('left', sidebarWidth);
        }
        
        // Set up toggle event
        $(document).off('click', '#toggle-sidebar');
        $(document).on('click', '#toggle-sidebar', function(e) {
            e.preventDefault();
            
            const isMinimized = $sidebar.hasClass('minimized');
            
            if (isMinimized) {
                // Show sidebar
                $sidebar.removeClass('minimized').css('left', '0');
                $('.chat-main').css({
                    'left': sidebarWidth,
                    'width': 'calc(100% - ' + sidebarWidth + ')'
                });
                $('#chat-input-row').css('left', sidebarWidth);
            } else {
                // Hide sidebar
                $sidebar.addClass('minimized').css('left', '-' + sidebarWidth);
                $('.chat-main').css({
                    'left': '0',
                    'width': '100%'
                });
                $('#chat-input-row').css('left', '0');
            }
            
            // Force scroll after toggle
            setTimeout(window.forceScrollToBottom, 100);
        });
    }

    // Improved chat system with fullscreen support
    function startChat(metadata) {
        try {
            console.log('Starting chat with character data');
            window.chatInProgress = true;
            
            const parsedData = window.PMV_Chat.parseCharacterData(metadata);
            const character = window.PMV_Chat.extractCharacterInfo(parsedData);
            const botId = 'bot_' + (character.name || 'character').replace(/[^a-z0-9]/gi, '_').toLowerCase();
            
            // Create fullscreen chat UI with improved sidebar
            $('#modal-content').html(`
                <div class="chat-container">
                    <div class="chat-sidebar minimized" id="chat-sidebar">
                        <div class="conversation-controls">
                            <button id="new-conversation" class="conversation-btn">New Chat</button>
                            <button id="save-conversation" class="conversation-btn">Save Chat</button>
                        </div>
                        <div class="conversations-panel">
                            <h3>Saved Conversations</h3>
                            <div id="conversation-list" class="conversation-list">
                                <div class="loading-conversations">Loading...</div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-main">
                        <div id="chat-header">
                            <button id="toggle-sidebar" class="sidebar-toggle-btn">
                                <span class="dashicons dashicons-menu-alt3"></span>
                            </button>
                            <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-label="Open menu">☰</button>
                            <div class="chat-modal-name">${escapeHtml(character.name)}</div>
                            <button id="close-chat" class="close-chat-btn">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div id="chat-history">
                            <div class="chat-message bot">
                                <strong>${escapeHtml(character.name)}:</strong> 
                                <span class="chat-message-content-wrapper">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                            </div>
                        </div>
                        <div id="chat-input-row">
                            <textarea id="chat-input" placeholder="Type your message..." rows="2"></textarea>
                            <button id="send-chat" class="chat-send-button">Send</button>
                        </div>
                    </div>
                </div>
            `);

            // Add proper classes and setup for fullscreen mode
            $('body, html').addClass('chat-modal-open');
            
            // Make modal fullscreen for chat
            $('#png-modal').addClass('fullscreen-chat');
            
            // Store character data and bot ID on the modal
            $('#png-modal').find('.png-modal-content').addClass('chat-mode')
                .data({
                    'characterData': parsedData,
                    'botId': botId,
                    'isNewConversation': true
                });

            // Handle input resize
            $('#chat-input').on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            }).focus();
            
            // Show the modal BEFORE applying layout
            $('#png-modal').fadeIn(100, function() {
                // Detect if we're on mobile
                if (window.innerWidth <= 768) {
                    // Initialize mobile chat if mobile-chat.js is loaded
                    if (typeof window.PMV_MobileChat !== 'undefined' && 
                        typeof window.PMV_MobileChat.initMobileChat === 'function') {
                        window.PMV_MobileChat.initMobileChat(character);
                    } else {
                        console.warn('Mobile chat module not loaded or initialized');
                    }
                } else {
                    // Desktop layout
                    setupDesktopChat();
                }
                
                // Apply enhanced styles - here we call enhanceChatLayout if available
                if (typeof window.enhanceChatLayout === 'function') {
                    window.enhanceChatLayout();
                } else {
                    console.warn('enhanceChatLayout function not available');
                    // Basic fallback styling
                    setupBasicChatLayout();
                }
                
                // Force scroll after a brief delay to ensure everything is rendered
                setTimeout(window.forceScrollToBottom, 100);
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

    // Setup desktop chat interface
    function setupDesktopChat() {
        console.log('Setting up desktop chat interface');
        
        // Make sure the sidebar toggle is visible for desktop
        $('#toggle-sidebar').show();
        $('#mobile-menu-toggle').hide();
        
        // Set up desktop sidebar
        setupDesktopSidebar();
        
        // Adjust chat main layout
        $('.chat-main').css({
            'position': 'absolute',
            'top': '0',
            'bottom': '0',
            'right': '0',
            'height': '100%',
            'width': $('#chat-sidebar').hasClass('minimized') ? '100%' : 'calc(100% - 280px)',
            'left': $('#chat-sidebar').hasClass('minimized') ? '0' : '280px',
            'transition': 'left 0.3s ease, width 0.3s ease',
            'overflow': 'hidden',
            'z-index': '1'
        });
        
        // Force scroll to bottom
        window.forceScrollToBottom();
    }

    // Basic chat layout when enhanceChatLayout is not available
    function setupBasicChatLayout() {
        // Ensure fullscreen styling
        $('#png-modal.fullscreen-chat').css({
            'position': 'fixed',
            'top': '0',
            'left': '0',
            'width': '100vw',
            'height': '100vh',
            'margin': '0',
            'padding': '0',
            'background-color': '#1a1a1a',
            'overflow': 'hidden',
            'z-index': '100000'
        });
        
        // Ensure modal content fills the screen
        $('.png-modal-content.chat-mode').css({
            'width': '100%',
            'height': '100%', 
            'margin': '0',
            'padding': '0',
            'overflow': 'hidden',
            'max-width': '100%',
            'max-height': '100%'
        });
        
        // Basic chat container styling
        $('.chat-container').css({
            'display': 'flex',
            'height': '100%',
            'width': '100%',
            'overflow': 'hidden',
            'position': 'relative'
        });
        
        // Ensure input row is visible
        $('#chat-input-row').css({
            'position': 'fixed',
            'bottom': '0',
            'left': $('#chat-sidebar').hasClass('minimized') ? '0' : '280px',
            'right': '0',
            'padding': '15px',
            'background-color': '#222',
            'border-top': '1px solid #444',
            'z-index': '100',
            'display': 'flex',
            'transition': 'left 0.3s ease'
        });
        
        // Force scroll to bottom
        window.forceScrollToBottom();
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

    // Chat message sending with fixed button state
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
        inputField.val('').css('height', 'auto');
        
        // Add typing indicator
        $('#chat-history').append('<div class="typing-indicator">Thinking...</div>');
        
        // Force scroll to bottom with new message
        window.forceScrollToBottom();
        
        // Get character data and conversation state
        const $modal = $('#png-modal').find('.png-modal-content');
        const characterData = $modal.data('characterData');
        const botId = $modal.data('botId');
        const isNewConversation = $modal.data('isNewConversation');
        
        // Collect conversation history (exclude the current message and typing indicator)
        const conversationHistory = window.PMV_Chat.collectConversationHistory();
        
        // Remove the last message (the one we just added) and typing indicator
        conversationHistory.pop(); // Remove current user message
        
        // Disable send button while processing and set text to "Sending..."
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
                    
                    // If mobile chat is active, sync with it
                    if (window.PMV_MobileChat && typeof window.PMV_MobileChat.addMobileMessage === 'function') {
                        window.PMV_MobileChat.addMobileMessage('bot', characterName, botResponse);
                        window.PMV_MobileChat.scrollMobileChat();
                    }
                    
                    // Mark conversation as no longer new after first exchange
                    $modal.data('isNewConversation', false);
                    
                    // Update conversation manager if active
                    if (window.PMV_ConversationManager?.currentConversationId && botResponse) {
                        window.PMV_ConversationManager.updateCurrentConversationWithMessage('assistant', botResponse);
                    }
                    
                    window.forceScrollToBottom();
                } else {
                    handleChatError(response.data?.message || 'API request failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Chat AJAX error:', {xhr, status, error});
                handleChatError(error || 'Connection error');
            },
            complete: function() {
                // Explicitly reset button to "Send" with timeout to ensure it works
                setTimeout(function() {
                    $sendButton.prop('disabled', false)
                               .text('Send')
                               .removeClass('sending');
                }, 100);
                window.forceScrollToBottom();
                
                // If mobile chat module is available, sync with it
                if (window.PMV_MobileChat && typeof window.PMV_MobileChat.syncMobileChat === 'function') {
                    window.PMV_MobileChat.syncMobileChat();
                }
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
        
        // If mobile chat is active, sync error message
        if (window.PMV_MobileChat && typeof window.PMV_MobileChat.addMobileMessage === 'function') {
            window.PMV_MobileChat.addMobileMessage('error', 'Error', error);
            window.PMV_MobileChat.scrollMobileChat();
        }
        
        window.forceScrollToBottom();
        
        // Reset send button on error
        setTimeout(function() {
            $('#send-chat').prop('disabled', false)
                          .text('Send')
                          .removeClass('sending');
        }, 100);
    }

    // Event Handling for Desktop
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
                
                // Also reset mobile chat if it exists
                if (window.PMV_MobileChat && typeof window.PMV_MobileChat.resetMobileChat === 'function') {
                    window.PMV_MobileChat.resetMobileChat();
                }
                
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
            
            // Also reset mobile chat if it exists
            if (window.PMV_MobileChat && typeof window.PMV_MobileChat.resetMobileChat === 'function') {
                window.PMV_MobileChat.resetMobileChat();
            }
            
            // Reset conversation manager if it exists
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.reset();
            }
        })
        .on('click', '#toggle-sidebar', function(e) {
            e.preventDefault();
            const $sidebar = $('#chat-sidebar');
            const isMinimized = $sidebar.hasClass('minimized');
            
            $sidebar.toggleClass('minimized');
            
            // Also update input row positioning based on sidebar state
            if (isMinimized) {
                // Sidebar is being shown
                $('#chat-input-row').css('left', window.innerWidth <= 768 ? '0' : '280px');
            } else {
                // Sidebar is being hidden
                $('#chat-input-row').css('left', '0');
            }
            
            // Force scroll to bottom after sidebar toggle
            setTimeout(window.forceScrollToBottom, 100);
        })
        .on('click', '#send-chat', function() {
            window.sendChatMessage();
        })
        .on('keydown', '#chat-input', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                window.sendChatMessage();
            }
        });

    // Detect orientation changes
    window.addEventListener('orientationchange', function() {
        // Delay the check since orientation changes take time to complete
        setTimeout(function() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile && window.PMV_MobileChat) {
                if (typeof window.PMV_MobileChat.handleOrientationChange === 'function') {
                    window.PMV_MobileChat.handleOrientationChange();
                }
            } else {
                // Fix desktop layout after orientation change
                setupDesktopChat();
                window.forceScrollToBottom();
            }
        }, 300);
    });

    // Responsive functions
    window.addEventListener('resize', function() {
        // Only handle resize if chat is open
        if (!window.chatInProgress) return;
        
        // Delayed execution to avoid multiple rapid calls
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(function() {
            const isMobile = window.innerWidth <= 768;
            const wasMobile = window.lastKnownWidth <= 768;
            
            // Store current width for future comparison
            window.lastKnownWidth = window.innerWidth;
            
            // Only switch modes if we cross the breakpoint
            if (wasMobile !== isMobile) {
                console.log('Switching from', wasMobile ? 'mobile' : 'desktop', 'to', isMobile ? 'mobile' : 'desktop');
                
                if (isMobile && window.PMV_MobileChat) {
                    // Switch to mobile mode
                    if (typeof window.PMV_MobileChat.activateMobileMode === 'function') {
                        window.PMV_MobileChat.activateMobileMode();
                    }
                } else {
                    // Switch to desktop mode
                    setupDesktopChat();
                }
            }
            
            window.forceScrollToBottom();
        }, 250);
    });

    // Store initial width
    window.lastKnownWidth = window.innerWidth;
})(jQuery);
