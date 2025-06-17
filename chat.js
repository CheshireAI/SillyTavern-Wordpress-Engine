/* PNG Metadata Viewer - Full Screen Chat (FIXED jQuery) */
console.log('PNG Metadata Viewer: Full Screen Chat Loading...');

// Wrap everything in a proper jQuery wrapper for WordPress
(function($) {
    'use strict';
    
    // Wait for document ready
    $(document).ready(function() {
        console.log('PNG Metadata Viewer: Document ready, initializing chat...');
        
        // Configuration
        const CHAT_CONFIG = {
            breakpoint: 768,
            sidebarWidth: '350px',
            mobileSidebarWidth: '300px',
            autoSaveDelay: 2000
        };

        // Global state management (SIMPLIFIED - no conversation management)
        const chatState = {
            initialized: false,
            chatModeActive: false,
            characterData: null,
            characterId: null,
            originalBodyContent: null,
            sidebarOpen: true
        };

        // Ensure modal HTML exists for character details - FULL SCREEN VERSION
        function ensureModalExists() {
            if ($('#png-modal').length === 0) {
                console.log('Creating FULL SCREEN modal HTML structure...');
                $('body').append(`
                    <div id="png-modal" class="png-modal" style="display: none;">
                        <div class="png-modal-content">
                            <div class="png-modal-body">
                                <div id="modal-content">
                                    <!-- Modal content will be inserted here -->
                                </div>
                                <button class="close-modal">&times;</button>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Add FULL SCREEN CSS
            addFullScreenCSS();
        }

        // Add FULL SCREEN CSS (keep existing CSS - it's good)
        function addFullScreenCSS() {
            if ($('#png-fullscreen-css').length === 0) {
                $('head').append(`
                    <style id="png-fullscreen-css">
                    /* FULL SCREEN Modal */
                    .png-modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100vw;
                        height: 100vh;
                        background: rgba(0, 0, 0, 0.95);
                        z-index: 999999;
                        display: none;
                        overflow-y: auto;
                    }
                    
                    .png-modal.show {
                        display: block !important;
                    }
                    
                    .png-modal-content {
                        background: #2a2a2a;
                        min-height: 100vh;
                        width: 100%;
                        color: white;
                        position: relative;
                    }
                    
                    .png-modal-body {
                        padding: 40px;
                        max-width: 1200px;
                        margin: 0 auto;
                    }
                    
                    .close-modal {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #ff4444;
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        cursor: pointer;
                        font-size: 20px;
                        z-index: 1000000;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    /* Character Modal Styles */
                    .character-modal-wrapper {
                        width: 100%;
                    }
                    
                    .character-header h2 {
                        margin: 0 0 10px 0;
                        color: #fff;
                        font-size: 2rem;
                    }
                    
                    .character-creator {
                        color: #ccc;
                        font-size: 14px;
                        margin-bottom: 15px;
                    }
                    
                    .character-image {
                        text-align: center;
                        margin: 20px 0;
                    }
                    
                    .character-image img {
                        max-width: 100%;
                        max-height: 70vh;
                        border-radius: 8px;
                        object-fit: contain;
                    }
                    
                    .character-section {
                        margin-bottom: 25px;
                    }
                    
                    .character-section h3 {
                        color: #fff;
                        margin-bottom: 10px;
                        font-size: 1.2rem;
                    }
                    
                    .character-field {
                        background: rgba(255, 255, 255, 0.05);
                        padding: 15px;
                        border-radius: 6px;
                        line-height: 1.6;
                    }
                    
                    .character-footer {
                        display: flex;
                        gap: 15px;
                        justify-content: center;
                        margin-top: 30px;
                    }
                    
                    .png-download-button, .png-chat-button {
                        padding: 12px 24px;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 16px;
                        text-decoration: none;
                        display: inline-block;
                        transition: all 0.3s ease;
                    }
                    
                    .png-download-button {
                        background: #28a745;
                        color: white;
                    }
                    
                    .png-chat-button {
                        background: #007bff;
                        color: white;
                    }
                    
                    .png-download-button:hover {
                        background: #218838;
                    }
                    
                    .png-chat-button:hover {
                        background: #0056b3;
                    }
                    
                    .tags-container {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                    }
                    
                    .tag-item {
                        background: rgba(59, 130, 246, 0.2);
                        color: #60a5fa;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 12px;
                    }
                    
                    /* FULL SCREEN CHAT STYLES */
                    .fullscreen-chat-container {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100vw;
                        height: 100vh;
                        background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
                        color: white;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        z-index: 999999;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }
                    
                    .chat-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 15px 20px;
                        background: rgba(0, 0, 0, 0.3);
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                        flex-shrink: 0;
                    }
                    
                    .chat-modal-name {
                        font-size: 18px;
                        font-weight: 600;
                        color: white;
                        text-align: center;
                        flex: 1;
                        margin: 0 15px;
                    }
                    
                    .menu-btn, .close-chat-btn {
                        background: rgba(255, 255, 255, 0.1);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        color: white;
                        border-radius: 6px;
                        padding: 10px 15px;
                        cursor: pointer;
                        font-size: 14px;
                    }
                    
                    /* CONVERSATION SIDEBAR STYLES */
                    .chat-main {
                        display: flex;
                        flex: 1;
                        overflow: hidden;
                    }
                    
                    .conversation-sidebar {
                        width: ${CHAT_CONFIG.sidebarWidth};
                        background: rgba(0, 0, 0, 0.95);
                        border-right: 1px solid rgba(255, 255, 255, 0.1);
                        display: flex;
                        flex-direction: column;
                        flex-shrink: 0;
                        overflow: hidden;
                    }
                    
                    .chat-content {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }
                    
                    .sidebar-header {
                        padding: 20px;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    }
                    
                    .sidebar-header h3 {
                        margin: 0 0 15px 0;
                        color: #fff;
                        font-size: 18px;
                    }
                    
                    .sidebar-actions {
                        display: flex;
                        gap: 10px;
                        margin-bottom: 15px;
                        flex-wrap: wrap;
                    }
                    
                    .new-chat-btn, .save-chat-btn, .export-chat-btn {
                        flex: 1;
                        padding: 10px 15px;
                        background: #5e655a;
                        color: white;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 13px;
                        min-width: 80px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .new-chat-btn:hover, .save-chat-btn:hover, .export-chat-btn:hover {
                        background: #6b7260;
                    }
                    
                    .close-sidebar-btn {
                        display: none; /* Hidden on desktop */
                    }
                    
                    .conversation-list {
                        flex: 1;
                        overflow-y: auto;
                        padding: 20px;
                    }
                    
                    .conversation-item {
                        position: relative;
                        padding: 15px;
                        margin-bottom: 10px;
                        background: rgba(255, 255, 255, 0.05);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        border-radius: 8px;
                        cursor: pointer;
                        color: white;
                        transition: all 0.2s ease;
                    }
                    
                    .conversation-item:hover {
                        background: rgba(255, 255, 255, 0.1);
                    }
                    
                    .conversation-item.active {
                        background: rgba(59, 130, 246, 0.2);
                        border-color: #3b82f6;
                    }
                    
                    .conversation-info {
                        padding-right: 30px;
                    }
                    
                    .conversation-title {
                        font-weight: 500;
                        margin-bottom: 5px;
                        font-size: 14px;
                    }
                    
                    .conversation-date {
                        font-size: 12px;
                        color: rgba(255, 255, 255, 0.6);
                    }
                    
                    .delete-conversation {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        background: transparent;
                        border: none;
                        color: #ef4444;
                        cursor: pointer;
                        font-size: 16px;
                        opacity: 0.7;
                        transition: opacity 0.2s ease;
                        padding: 5px;
                    }
                    
                    .conversation-item:hover .delete-conversation {
                        opacity: 1;
                    }
                    
                    .no-conversations {
                        text-align: center;
                        color: rgba(255, 255, 255, 0.6);
                        padding: 40px 20px;
                        font-style: italic;
                    }
                    
                    .loading-container {
                        text-align: center;
                        color: rgba(255, 255, 255, 0.6);
                        padding: 40px 20px;
                    }
                    
                    .chat-history {
                        flex: 1;
                        overflow-y: auto;
                        padding: 20px;
                        display: flex;
                        flex-direction: column;
                        gap: 15px;
                    }
                    
                    .chat-message {
                        padding: 15px;
                        border-radius: 8px;
                        max-width: 80%;
                        word-wrap: break-word;
                    }
                    
                    .chat-message.user {
                        background: rgba(59, 130, 246, 0.2);
                        margin-left: auto;
                        border: 1px solid rgba(59, 130, 246, 0.3);
                    }
                    
                    .chat-message.bot {
                        background: rgba(255, 255, 255, 0.05);
                        margin-right: auto;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                    }
                    
                    .chat-message.error {
                        background: rgba(220, 53, 69, 0.2);
                        margin-right: auto;
                        border: 1px solid rgba(220, 53, 69, 0.3);
                    }
                    
                    .speaker-name {
                        font-weight: 600;
                        margin-bottom: 8px;
                        display: block;
                    }
                    
                    .chat-message-content-wrapper {
                        line-height: 1.5;
                    }
                    
                    .typing-indicator {
                        background: rgba(255, 255, 255, 0.05);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        padding: 15px;
                        border-radius: 8px;
                        margin-right: auto;
                        max-width: 80%;
                        font-style: italic;
                        color: rgba(255, 255, 255, 0.7);
                    }
                    
                    .chat-input-container {
                        padding: 20px;
                        border-top: 1px solid rgba(255, 255, 255, 0.1);
                        background: rgba(0, 0, 0, 0.2);
                    }
                    
                    .input-wrapper {
                        display: flex;
                        gap: 15px;
                        align-items: flex-end;
                    }
                    
                    #chat-input {
                        flex: 1;
                        background: rgba(255, 255, 255, 0.1);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        color: white;
                        padding: 15px;
                        border-radius: 8px;
                        resize: vertical;
                        min-height: 50px;
                        max-height: 150px;
                        font-family: inherit;
                        font-size: 14px;
                    }
                    
                    #chat-input::placeholder {
                        color: rgba(255, 255, 255, 0.5);
                    }
                    
                    .chat-send-button {
                        background: #007bff;
                        color: white;
                        border: none;
                        padding: 15px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .chat-send-button:hover {
                        background: #0056b3;
                    }
                    
                    .chat-send-button:disabled {
                        background: #6c757d;
                        cursor: not-allowed;
                    }
                    
                    /* Mobile responsiveness */
                    @media (max-width: 768px) {
                        .fullscreen-chat-container {
                            height: 100vh;
                        }
                        
                        .conversation-sidebar {
                            position: absolute;
                            left: 0;
                            top: 0;
                            height: 100%;
                            width: ${CHAT_CONFIG.mobileSidebarWidth};
                            z-index: 2;
                            transform: translateX(-100%);
                            transition: transform 0.3s ease;
                        }
                        
                        .conversation-sidebar.open {
                            transform: translateX(0);
                        }
                        
                        .close-sidebar-btn {
                            display: block;
                            background: rgba(255, 255, 255, 0.1);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                            color: white;
                            border-radius: 6px;
                            padding: 8px 12px;
                            cursor: pointer;
                            font-size: 14px;
                            width: 100%;
                            margin-top: 10px;
                        }
                        
                        .menu-btn {
                            display: block;
                        }
                        
                        .chat-message {
                            max-width: 90%;
                        }
                        
                        .chat-input-container {
                            padding: 15px 20px;
                        }
                        
                        #chat-input {
                            font-size: 16px;
                            min-height: 44px;
                        }
                        
                        .chat-send-button {
                            min-height: 44px;
                            padding: 12px 20px;
                        }
                    }
                    </style>
                `);
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

        // Parse character data from metadata
        function parseCharacterData(metadataStr) {
            try {
                if (!metadataStr || typeof metadataStr !== 'string') {
                    throw new Error('Invalid metadata format');
                }

                // Try URI decode first
                if (metadataStr.includes('%')) {
                    try {
                        metadataStr = decodeURIComponent(metadataStr);
                    } catch (e) {
                        console.warn('URI decode failed:', e.message);
                    }
                }

                // Try direct parse
                try {
                    return JSON.parse(metadataStr);
                } catch (e) {
                    // Try cleaning up the JSON string
                    const cleanedStr = metadataStr.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
                    return JSON.parse(cleanedStr);
                }
            } catch (error) {
                console.error('Error parsing character data:', error);
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
                console.error('ID generation error:', e);
            }
            return 'char_' + name.toLowerCase().replace(/[^a-z0-9]/g, '_');
        }

        // Build character modal HTML
        function buildCharacterModalHtml(character, fileUrl, rawMetadata) {
            const name = character.name || 'Unnamed Character';
            const description = character.description || '';
            const personality = character.personality || '';
            const scenario = character.scenario || '';
            const firstMes = character.first_mes || '';
            const mesExample = character.mes_example || '';
            const creator = character.creator || '';
            const tags = character.tags || [];

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
                sections.push(`
                    <div class="character-section">
                        <h3>Description</h3>
                        <div class="character-field">${escapeHtml(description)}</div>
                    </div>
                `);
            }

            if (personality) {
                sections.push(`
                    <div class="character-section">
                        <h3>Personality</h3>
                        <div class="character-field">${escapeHtml(personality)}</div>
                    </div>
                `);
            }

            if (scenario) {
                sections.push(`
                    <div class="character-section">
                        <h3>Scenario</h3>
                        <div class="character-field">${escapeHtml(scenario)}</div>
                    </div>
                `);
            }

            if (firstMes) {
                sections.push(`
                    <div class="character-section">
                        <h3>First Message</h3>
                        <div class="character-field">${escapeHtml(firstMes)}</div>
                    </div>
                `);
            }

            if (tags.length > 0) {
                let tagHtml = tags.map(tag => `<span class="tag-item">${escapeHtml(tag)}</span>`).join('');
                sections.push(`
                    <div class="character-section">
                        <h3>Tags</h3>
                        <div class="character-field">
                            <div class="tags-container">${tagHtml}</div>
                        </div>
                    </div>
                `);
            }

            return `
                <div class="character-modal-wrapper">
                    <div class="character-details">
                        <div class="character-header">
                            <h2>${escapeHtml(name)}</h2>
                            ${creator ? `<div class="character-creator">Created by: ${escapeHtml(creator)}</div>` : ''}
                        </div>
                        
                        <div class="character-image">
                            <img src="${fileUrl}" alt="${escapeHtml(name)}">
                        </div>
                        
                        <div id="character-info">
                            ${sections.join('')}
                        </div>
                        
                        <div class="character-footer">
                            <a href="${fileUrl}" class="png-download-button" download>Download</a>
                            <button class="png-chat-button" data-metadata="${escapedMetadata}">
                                ${window.pmv_chat_button_text || 'Chat'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Open character modal
        function openCharacterModal(card) {
            try {
                const $card = $(card);
                const metadataStr = $card.attr('data-metadata');
                if (!metadataStr) {
                    throw new Error('No metadata found on card');
                }

                const fileUrl = $card.data('file-url') || $card.attr('data-file-url') || $card.find('img').attr('src');
                const characterData = parseCharacterData(metadataStr);
                const character = extractCharacterInfo(characterData);
                const modalHtml = buildCharacterModalHtml(character, fileUrl, metadataStr);

                ensureModalExists();
                $('#modal-content').html(modalHtml);
                $('#png-modal').addClass('show');

            } catch (error) {
                console.error('Error opening character modal:', error);
                ensureModalExists();
                $('#modal-content').html(`
                    <div class="character-modal-wrapper">
                        <div class="character-details">
                            <div class="character-header">
                                <h2>Error Loading Character</h2>
                            </div>
                            <div class="character-section">
                                <p>Error: ${escapeHtml(error.message)}</p>
                            </div>
                        </div>
                    </div>
                `);
                $('#png-modal').addClass('show');
            }
        }

        // Create conversation sidebar (SIMPLIFIED - delegates to PMV_ConversationManager)
        function createConversationSidebar() {
            const sidebarHtml = `
                <div class="conversation-sidebar ${chatState.sidebarOpen ? 'open' : ''}">
                    <div class="sidebar-header">
                        <h3>Conversations</h3>
                        <div class="sidebar-actions">
                            <button id="new-conversation" class="new-chat-btn">🔄 New</button>
                            <button id="save-conversation" class="save-chat-btn">💾 Save</button>
                            <button id="export-conversation" class="export-chat-btn">📥 Export</button>
                        </div>
                        <button class="close-sidebar-btn">Close Menu</button>
                    </div>
                    <div class="conversation-list">
                        <div class="loading-container">Loading conversations...</div>
                    </div>
                </div>
            `;

            $('.chat-main').prepend(sidebarHtml);
            
            // Initialize the REAL conversation manager
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.init(chatState.characterData, chatState.characterId);
            }
        }

        // Start full screen chat (UPDATED to use PMV_ConversationManager)
        function startFullScreenChat(metadata) {
            try {
                const parsedData = parseCharacterData(metadata);
                const character = extractCharacterInfo(parsedData);

                // Set up chat state
                chatState.characterData = character;
                chatState.characterId = generateCharacterId(parsedData);
                chatState.chatModeActive = true;
                chatState.originalBodyContent = $('body').html();

                // Create full screen chat UI
                const chatHtml = `
                    <div class="fullscreen-chat-container">
                        <div class="chat-header">
                            <button class="menu-btn">☰ Conversations</button>
                            <div class="chat-modal-name">${escapeHtml(character.name)}</div>
                            <button class="close-chat-btn">✕ Close</button>
                        </div>
                        <div class="chat-main">
                            <!-- Sidebar will be inserted here -->
                            <div class="chat-content">
                                <div class="chat-history" id="chat-history">
                                    <div class="chat-message bot">
                                        <span class="speaker-name">${escapeHtml(character.name)}:</span>
                                        <span class="chat-message-content-wrapper">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                                    </div>
                                </div>
                                <div class="chat-input-container">
                                    <div class="input-wrapper">
                                        <textarea id="chat-input" placeholder="Type your message..." rows="1"></textarea>
                                        <button id="send-chat" class="chat-send-button">Send</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Replace body content with chat
                $('body').html(chatHtml);

                // Create and show conversation sidebar
                createConversationSidebar();

                // Focus input
                setTimeout(() => {
                    $('#chat-input').focus();
                }, 300);

            } catch (error) {
                console.error('Error starting full screen chat:', error);
                alert('Error starting chat: ' + error.message);
            }
        }

        // Close full screen chat (UPDATED)
        function closeFullScreenChat() {
            // Use PMV_ConversationManager's enhanced cleanup if available
            if (window.PMV_ConversationManager && window.PMV_ConversationManager.cleanupSaveOperations) {
                if (window.PMV_ConversationManager.hasUnsavedChangesFlag) {
                    const confirmClose = confirm('You have unsaved changes. Are you sure you want to close?');
                    if (!confirmClose) return;
                }
                window.PMV_ConversationManager.cleanupSaveOperations();
            }

            if (chatState.originalBodyContent) {
                $('body').html(chatState.originalBodyContent);
                setTimeout(initialize, 100);
            } else {
                window.location.reload();
            }

            // Reset chat state
            chatState.chatModeActive = false;
            chatState.characterData = null;
            chatState.characterId = null;
            chatState.originalBodyContent = null;
            chatState.sidebarOpen = true;
        }

        // Collect conversation history (SIMPLIFIED)
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
                $(document).trigger('message:added', [{type: 'error', content: errorMessage}]);
            }

            setTimeout(function() {
                $('#send-chat').prop('disabled', false).text('Send');
            }, 100);
        }

        // Initialize everything
        function initialize() {
            console.log('Initializing PNG Metadata Viewer chat...');
            ensureModalExists();

            if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.chat_button_text) {
                window.pmv_chat_button_text = pmv_ajax_object.chat_button_text;
            }

            chatState.initialized = true;
        }

        // Event handlers (UPDATED to delegate conversation management)
        $(document)
            // Character modal and chat button handlers (PRESERVED)
            .on('click', '.png-image-container img, .png-card img, .character-card img, img[data-metadata]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $card = $(this).closest('.png-card, .character-card, [data-metadata]');
                if ($card.length) openCharacterModal($card);
            })
            .on('click', '.png-chat-button, button[data-metadata]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const metadata = $(this).attr('data-metadata') || $(this).closest('[data-metadata]').attr('data-metadata');
                if (metadata) startFullScreenChat(metadata);
            })
            .on('click', '.close-modal, #png-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('close-modal')) {
                    $('#png-modal').removeClass('show');
                }
            })
            
            // Chat interface handlers (UPDATED to delegate to PMV_ConversationManager)
            .on('click', '.close-chat-btn', function(e) {
                e.preventDefault();
                closeFullScreenChat();
            })
            .on('click', '.menu-btn', function(e) {
                e.preventDefault();
                if ($(window).width() <= 768) {
                    $('.conversation-sidebar').toggleClass('open');
                } else {
                    chatState.sidebarOpen = !chatState.sidebarOpen;
                    if (chatState.sidebarOpen) {
                        $('.conversation-sidebar').show();
                    } else {
                        $('.conversation-sidebar').hide();
                    }
                }
            })
            .on('click', '.close-sidebar-btn', function(e) {
                e.preventDefault();
                $('.conversation-sidebar').removeClass('open');
            })
            
            // Chat input handlers - UPDATED WITH REAL API CALL
            .on('click', '#send-chat', function(e) {
                e.preventDefault();
                const message = $('#chat-input').val().trim();
                if (!message) return;

                // Add user message
                $('#chat-history').append(`
                    <div class="chat-message user">
                        <span class="speaker-name">You:</span>
                        <span class="chat-message-content-wrapper">${escapeHtml(message)}</span>
                    </div>
                `);

                // Clear input and disable send button
                $('#chat-input').val('');
                const $sendButton = $('#send-chat');
                $sendButton.prop('disabled', true).text('Sending...');

                // Add typing indicator
                $('#chat-history').append('<div class="typing-indicator">Thinking...</div>');
                scrollToBottom();

                // Notify PMV_ConversationManager of new user message
                if (window.PMV_ConversationManager) {
                    $(document).trigger('message:added', [{type: 'user', content: message}]);
                }

                // Collect conversation history (excluding the current message)
                const conversationHistory = collectConversationHistory();
                conversationHistory.pop(); // Remove the message we just added

                // Prepare character data for API
                let characterDataStr;
                try {
                    characterDataStr = JSON.stringify({
                        name: chatState.characterData.name,
                        description: chatState.characterData.description,
                        personality: chatState.characterData.personality,
                        scenario: chatState.characterData.scenario,
                        first_mes: chatState.characterData.first_mes,
                        mes_example: chatState.characterData.mes_example,
                        system_prompt: chatState.characterData.system_prompt
                    });
                } catch (e) {
                    console.error('Error preparing character data:', e);
                    handleChatError('Failed to process character data');
                    return;
                }

                // Build AJAX payload
                const ajaxData = {
                    action: 'start_character_chat',
                    character_data: characterDataStr,
                    user_message: message,
                    bot_id: chatState.characterId || 'default_bot',
                    nonce: pmv_ajax_object.nonce
                };

                // Add conversation history if available
                if (conversationHistory.length > 0) {
                    ajaxData.conversation_history = JSON.stringify(conversationHistory);
                }

                console.log('Sending chat request:', {
                    characterName: chatState.characterData.name,
                    messageLength: message.length,
                    historyLength: conversationHistory.length
                });

                // Make AJAX request to backend
                $.ajax({
                    url: pmv_ajax_object.ajax_url,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        console.log('API response received:', response);
                        $('.typing-indicator').remove();

                        if (response.success && response.data && response.data.choices && response.data.choices[0]) {
                            const botResponse = response.data.choices[0].message.content;
                            const characterName = escapeHtml(chatState.characterData.name || 'AI');

                            // Add bot response to chat
                            $('#chat-history').append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">${characterName}:</span>
                                    <span class="chat-message-content-wrapper">${escapeHtml(botResponse)}</span>
                                </div>
                            `);

                            scrollToBottom();
                            
                            // Notify PMV_ConversationManager of new assistant message
                            if (window.PMV_ConversationManager) {
                                $(document).trigger('message:added', [{type: 'assistant', content: botResponse}]);
                            }
                            
                        } else {
                            handleChatError(response.data?.message || 'API request failed. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Chat AJAX error:', {xhr, status, error});
                        let errorMessage = 'Connection error. Please check your internet connection and try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        } else if (xhr.responseText) {
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                errorMessage = errorData.data?.message || errorMessage;
                            } catch (e) {
                                // Keep default error message
                            }
                        }
                        
                        handleChatError(errorMessage);
                    },
                    complete: function() {
                        // Reset send button
                        setTimeout(function() {
                            $sendButton.prop('disabled', false).text('Send');
                        }, 100);
                    }
                });
            })
            .on('keydown', '#chat-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    $('#send-chat').click();
                }
            });

        // Scroll to bottom of chat
        function scrollToBottom() {
            const $chatHistory = $('#chat-history');
            if ($chatHistory.length) {
                $chatHistory.scrollTop($chatHistory[0].scrollHeight);
            }
        }

        // Initialize when DOM is ready
        initialize();

        // Export functions globally (PRESERVED)
        window.openCharacterModal = openCharacterModal;
        window.startChat = startFullScreenChat;
    });
    
})(jQuery); // Pass jQuery to the function
