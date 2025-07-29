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
                            <div id="modal-content">
                                <!-- Modal content will be inserted here -->
                            </div>
                            <button class="close-modal">&times;</button>
                        </div>
                    </div>
                `);
            }

            // Add FULL SCREEN CSS
            addFullScreenCSS();
        }

        // Add FULL SCREEN CSS (keep existing CSS - it's good)
        function addFullScreenCSS() {
            const css = `
                .fullscreen-chat {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: #1a1a1a;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                }
                
                .fullscreen-chat-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    background: #2d2d2d;
                    padding: 15px;
                    border-bottom: 1px solid #444;
                    position: relative;
                    z-index: 10000;
                }
                .header-left {
                    display: flex;
                    align-items: center;
                }
                .conversation-menu-btn {
                    background: #444;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 20px;
                    width: 36px;
                    height: 36px;
                    margin-right: 10px;
                    cursor: pointer;
                    transition: background-color 0.2s ease;
                }
                .conversation-menu-btn:hover {
                    background: #555;
                }
                .header-actions {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-left: auto;
                }
                .image-settings-btn {
                    background: #007bff;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 20px;
                    width: 36px;
                    height: 36px;
                    margin-right: 10px;
                    cursor: pointer;
                    transition: background-color 0.2s ease;
                }
                .image-settings-btn:hover {
                    background: #0056b3;
                }
                .close-fullscreen-btn {
                    background: #dc3545;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 20px;
                    width: 36px;
                    height: 36px;
                    cursor: pointer;
                    transition: background-color 0.2s ease;
                }
                .close-fullscreen-btn:hover {
                    background: #c82333;
                }
                .input-flex-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .input-flex-row textarea {
                    flex: 1 1 auto;
                    min-width: 0;
                    margin-right: 0;
                }
                .fullscreen-chat-send-btn {
                    margin: 0;
                }
                
                .fullscreen-chat-content {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px;
                    background: #1a1a1a;
                }
                
                .fullscreen-chat-history {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px;
                    background: #1a1a1a;
                }
                
                .fullscreen-chat-input-container {
                    background: #2d2d2d;
                    padding: 15px;
                    border-top: 1px solid #444;
                }
                
                .fullscreen-chat-input {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #444;
                    border-radius: 4px;
                    background: #1a1a1a;
                    color: #e0e0e0;
                    font-size: 14px;
                    resize: vertical;
                    min-height: 60px;
                }
                
                .fullscreen-chat-input:focus {
                    outline: none;
                    border-color: #007bff;
                }
                
                .fullscreen-chat-send-btn {
                    background: #007bff;
                    color: #ffffff;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-left: 10px;
                }
                
                .fullscreen-chat-send-btn:hover {
                    background: #0056b3;
                }
                
                .fullscreen-chat-send-btn:disabled {
                    background: #666;
                    cursor: not-allowed;
                }
                
                .chat-message {
                    margin-bottom: 15px;
                    padding: 10px;
                    border-radius: 8px;
                    max-width: 80%;
                }
                
                .chat-message.user {
                    background: #2d4a7d;
                    color: #e0e0e0;
                    margin-left: auto;
                    text-align: right;
                }
                
                .chat-message.bot {
                    background: #2d2d2d;
                    color: #e0e0e0;
                    margin-right: auto;
                }
                
                .speaker-name {
                    font-weight: bold;
                    margin-bottom: 5px;
                    display: block;
                }
                
                .chat-message-content-wrapper {
                    word-wrap: break-word;
                }
                
                .chat-message-content-wrapper img {
                    max-width: 100%;
                    border-radius: 4px;
                    margin-top: 5px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .spinner {
                    border: 3px solid #333;
                    border-top: 3px solid #007bff;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    animation: spin 1s linear infinite;
                    display: inline-block;
                }
                
                .conversation-sidebar {
                    position: fixed;
                    top: 0;
                    left: -350px;
                    width: 350px;
                    height: 100vh;
                    background: #2d2d2d;
                    border-right: 1px solid #444;
                    z-index: 10001;
                    transition: left 0.3s ease;
                    overflow-y: auto;
                    display: block;
                }
                
                .conversation-sidebar.open {
                    left: 0;
                }
                
                .conversation-sidebar.hidden {
                    display: none;
                }
                
                .sidebar-header {
                    padding: 15px;
                    border-bottom: 1px solid #444;
                    background: #1a1a1a;
                }
                
                .sidebar-header h3 {
                    margin: 0 0 10px 0;
                    color: #e0e0e0;
                }
                
                .sidebar-actions {
                    display: flex;
                    gap: 5px;
                    margin-bottom: 10px;
                }
                
                .new-chat-btn, .save-chat-btn, .export-chat-btn {
                    background: #007bff;
                    color: #fff;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .new-chat-btn:hover, .save-chat-btn:hover, .export-chat-btn:hover {
                    background: #0056b3;
                }
                
                .close-sidebar-btn {
                    background: #dc3545;
                    color: #fff;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .close-sidebar-btn:hover {
                    background: #c82333;
                }
                
                .conversation-list {
                    padding: 15px;
                }
                
                .loading-container {
                    color: #888;
                    text-align: center;
                    padding: 20px;
                }
                
                .image-settings-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.8);
                    z-index: 10002;
                    display: none;
                    align-items: center;
                    justify-content: center;
                }
                
                .image-settings-content {
                    background: #2d2d2d;
                    border-radius: 8px;
                    max-width: 800px;
                    max-height: 90vh;
                    overflow-y: auto;
                    width: 90%;
                    border: 1px solid #444;
                }
                
                .settings-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #444;
                    background: #1a1a1a;
                }
                
                .settings-header h3 {
                    margin: 0;
                    color: #e0e0e0;
                }
                
                .close-settings-modal {
                    background: #dc3545;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    width: 30px;
                    height: 30px;
                    cursor: pointer;
                    font-size: 16px;
                }
                
                .close-settings-modal:hover {
                    background: #c82333;
                }
                
                .settings-body {
                    padding: 20px;
                }
                
                .settings-section {
                    margin-bottom: 25px;
                }
                
                .settings-section h4 {
                    color: #e0e0e0;
                    margin-bottom: 15px;
                    border-bottom: 1px solid #444;
                    padding-bottom: 5px;
                }
                
                .setting-group {
                    margin-bottom: 15px;
                }
                
                .setting-group label {
                    display: block;
                    color: #e0e0e0;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                
                .setting-group input[type="text"],
                .setting-group input[type="number"],
                .setting-group select,
                .setting-group textarea {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #444;
                    border-radius: 4px;
                    background: #1a1a1a;
                    color: #e0e0e0;
                    font-size: 14px;
                }
                
                .setting-group input[type="checkbox"] {
                    margin-right: 8px;
                }
                
                .setting-description {
                    color: #888;
                    font-size: 12px;
                    margin-top: 5px;
                    font-style: italic;
                }
                
                .settings-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #444;
                }
                
                .button-primary {
                    background: #007bff;
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                }
                
                .button-primary:hover {
                    background: #0056b3;
                }
                
                .button-secondary {
                    background: #6c757d;
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                }
                
                .button-secondary:hover {
                    background: #545b62;
                }
                
                /* MOBILE FIXES FOR CHAT LAYOUT */
                @media (max-width: 768px) {
                    .fullscreen-chat {
                        height: 100dvh !important; /* Use dynamic viewport height to account for mobile browser UI */
                        max-height: 100dvh !important;
                        overflow: hidden !important;
                    }
                    
                    .fullscreen-chat-header {
                        padding: 10px !important;
                        flex-shrink: 0 !important;
                        min-height: 60px !important;
                        max-height: 60px !important;
                    }
                    
                    .chat-modal-name {
                        font-size: 14px !important;
                        color: #e0e0e0 !important;
                        font-weight: bold !important;
                    }
                    
                    .token-usage-display,
                    .image-usage-display {
                        display: none !important; /* Hide usage displays on mobile to save space */
                    }
                    
                    .fullscreen-chat-content {
                        flex: 1 !important;
                        overflow-y: auto !important;
                        padding: 10px !important;
                        background: #1a1a1a !important;
                        min-height: 0 !important; /* Allow flex item to shrink */
                    }
                    
                    .fullscreen-chat-history {
                        padding: 10px !important;
                        background: #1a1a1a !important;
                        color: #e0e0e0 !important;
                    }
                    
                    .fullscreen-chat-input-container {
                        padding: 10px !important;
                        background: #2d2d2d !important;
                        border-top: 1px solid #444 !important;
                        flex-shrink: 0 !important;
                        position: relative !important;
                        z-index: 10001 !important;
                    }
                    
                    .input-flex-row {
                        display: flex !important;
                        align-items: flex-end !important;
                        gap: 8px !important;
                    }
                    
                    #chat-input {
                        flex: 1 !important;
                        padding: 10px !important;
                        border: 1px solid #444 !important;
                        border-radius: 4px !important;
                        background: #1a1a1a !important;
                        color: #e0e0e0 !important;
                        font-size: 16px !important; /* Prevent zoom on iOS */
                        resize: none !important;
                        min-height: 40px !important;
                        max-height: 120px !important;
                    }
                    
                    #chat-input:focus {
                        outline: none !important;
                        border-color: #007bff !important;
                        background: #1a1a1a !important;
                        color: #e0e0e0 !important;
                    }
                    
                    .fullscreen-chat-send-btn {
                        background: #007bff !important;
                        color: #ffffff !important;
                        border: none !important;
                        padding: 10px 15px !important;
                        border-radius: 4px !important;
                        cursor: pointer !important;
                        font-size: 14px !important;
                        flex-shrink: 0 !important;
                        min-height: 40px !important;
                    }
                    
                    .chat-message {
                        margin-bottom: 12px !important;
                        padding: 12px !important;
                        border-radius: 8px !important;
                        max-width: 85% !important;
                        word-wrap: break-word !important;
                        font-size: 14px !important;
                        line-height: 1.4 !important;
                    }
                    
                    .chat-message.user {
                        background: #2d4a7d !important;
                        color: #e0e0e0 !important;
                        margin-left: auto !important;
                        border: 1px solid #4a6fa5 !important;
                    }
                    
                    .chat-message.bot {
                        background: #333 !important;
                        color: #e0e0e0 !important;
                        margin-right: auto !important;
                        border: 1px solid #555 !important;
                    }
                    
                    .speaker-name {
                        color: #e0e0e0 !important;
                        font-weight: bold !important;
                        margin-bottom: 5px !important;
                        display: block !important;
                    }
                    
                    .chat-message-content-wrapper {
                        color: inherit !important;
                        word-wrap: break-word !important;
                    }
                    
                    .conversation-sidebar {
                        width: 280px !important;
                        height: 100dvh !important; /* Use dynamic viewport height */
                    }
                    
                    /* Ensure buttons stay visible */
                    .conversation-menu-btn,
                    .image-settings-btn,
                    .close-fullscreen-btn {
                        width: 32px !important;
                        height: 32px !important;
                        font-size: 16px !important;
                        margin-right: 5px !important;
                    }
                }
                
                /* Additional mobile viewport fix for iOS */
                @supports (-webkit-touch-callout: none) {
                    @media (max-width: 768px) {
                        .fullscreen-chat {
                            height: -webkit-fill-available !important;
                        }
                        
                        .conversation-sidebar {
                            height: -webkit-fill-available !important;
                        }
                    }
                }
            `;
            
            if (!document.getElementById('fullscreen-chat-css')) {
                const style = document.createElement('style');
                style.id = 'fullscreen-chat-css';
                style.textContent = css;
                document.head.appendChild(style);
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
                            <img src="${fileUrl}" alt="${escapeHtml(name)}" loading="lazy">
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
                $('#png-modal').show();

                // FIXED: Proper modal height calculation to prevent card cutoff
                setTimeout(function() {
                    var $modal = $('#png-modal');
                    var $modalCards = $modal.find('.png-cards');
                    if ($modal.is(':visible') && $modalCards.length) {
                        $modalCards.imagesLoaded(function() {
                            if ($modalCards.data('masonry')) {
                                $modalCards.masonry('layout');
                            }
                            
                            // FIXED: Calculate proper height to prevent cutoff
                            var maxBottom = 0;
                            $modalCards.find('.png-card').each(function() {
                                var cardBottom = $(this).position().top + $(this).outerHeight(true);
                                if (cardBottom > maxBottom) maxBottom = cardBottom;
                            });
                            
                            // FIXED: Add sufficient padding to prevent cutoff
                            if (maxBottom > 0) {
                                var containerHeight = maxBottom + 120; // Add 120px padding
                                $modalCards.css('height', containerHeight + 'px');
                                $modal.find('.pmv-masonry-wrapper').css('min-height', containerHeight + 'px');
                            }
                            
                            // FIXED: Ensure modal content can scroll properly
                            $modal.find('.png-modal-content').css({
                                'max-height': '95vh',
                                'overflow-y': 'auto'
                            });
                            
                            $(window).trigger('resize');
                        });
                    }
                }, 200); // FIXED: Increased delay for better layout calculation

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
                $('#png-modal').show();
            }
        }

        // Create conversation sidebar (SIMPLIFIED - delegates to PMV_ConversationManager)
        function createConversationSidebar() {
            console.log('Creating conversation sidebar...');
            const sidebarHtml = `
                <div class="conversation-sidebar ${chatState.sidebarOpen ? 'open' : ''}">
                    <div class="sidebar-header">
                        <h3>Conversations</h3>
                        <div class="sidebar-actions">
                            <button id="new-conversation" class="new-chat-btn">🔄 New</button>
                            <button id="save-conversation" class="save-chat-btn">💾 Save</button>
                            <button id="export-conversation" class="export-chat-btn">📥 Export</button>
                        </div>
                        <button class="close-sidebar-btn" onclick="console.log('Close button clicked via onclick'); window.forceCloseSidebar();">Close Menu</button>
                    </div>
                    <div class="conversation-list"></div>
                </div>
            `;

            // Append to body instead of .chat-main since we're in fullscreen mode
            $('body').append(sidebarHtml);
            console.log('Sidebar created and appended to body');
            console.log('Sidebar element exists:', $('.conversation-sidebar').length);
            console.log('Sidebar initial state:', $('.conversation-sidebar').hasClass('open'));
            console.log('Close button exists:', $('.close-sidebar-btn').length);
            console.log('Close button element:', $('.close-sidebar-btn')[0]);
            
            // Test if the close button is clickable
            setTimeout(() => {
                const closeBtn = $('.close-sidebar-btn')[0];
                if (closeBtn) {
                    console.log('Close button found, testing clickability...');
                    console.log('Button text:', $(closeBtn).text());
                    console.log('Button classes:', $(closeBtn).attr('class'));
                    console.log('Button onclick:', closeBtn.onclick);
                    
                    // Add a test click handler
                    $(closeBtn).on('click.test', function(e) {
                        console.log('Test click handler fired!');
                        e.preventDefault();
                        e.stopPropagation();
                        window.forceCloseSidebar();
                    });
                } else {
                    console.error('Close button not found!');
                }
            }, 100);
            
            // Initialize the REAL conversation manager
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.init(chatState.characterData, chatState.characterId);
            }
            
            // Add direct click handler for close button
            setTimeout(() => {
                $('.close-sidebar-btn').off('click.direct').on('click.direct', function(e) {
                    console.log('Direct close button handler fired!');
                    e.preventDefault();
                    e.stopPropagation();
                    window.forceCloseSidebar();
                });
                console.log('Direct close button handler attached');
            }, 200);
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

                // Notify masonry system that chat is starting
                $(document).trigger('pmv_start_chat');
                
                // Also notify via global API if available
                if (window.pmvMasonry && window.pmvMasonry.setChatModalState) {
                    window.pmvMasonry.setChatModalState(true);
                }

                // Create full screen chat UI with inline styles for mobile compatibility
                const isMobile = window.innerWidth <= 768;
                const mobileStyles = isMobile ? `
                    style="position: fixed; top: 0; left: 0; width: 100vw; height: 100dvh; background: #1a1a1a; z-index: 9999; display: flex; flex-direction: column; overflow: hidden;"
                ` : '';
                const headerMobileStyles = isMobile ? `
                    style="display: flex; align-items: center; justify-content: space-between; background: #2d2d2d; padding: 10px; border-bottom: 1px solid #444; flex-shrink: 0; min-height: 60px; max-height: 60px;"
                ` : '';
                const contentMobileStyles = isMobile ? `
                    style="flex: 1; overflow-y: auto; padding: 10px; background: #1a1a1a; min-height: 0;"
                ` : '';
                const historyMobileStyles = isMobile ? `
                    style="padding: 10px; background: #1a1a1a; color: #e0e0e0; position: relative;"
                ` : '';
                const inputContainerMobileStyles = isMobile ? `
                    style="padding: 10px; background: #2d2d2d; border-top: 1px solid #444; flex-shrink: 0; position: relative; z-index: 10001;"
                ` : '';
                const inputRowMobileStyles = isMobile ? `
                    style="display: flex; align-items: flex-end; gap: 8px;"
                ` : '';
                const inputMobileStyles = isMobile ? `
                    style="flex: 1; padding: 10px; border: 1px solid #444; border-radius: 4px; background: #1a1a1a; color: #e0e0e0; font-size: 16px; resize: none; min-height: 40px; max-height: 120px;"
                ` : '';
                const sendButtonMobileStyles = isMobile ? `
                    style="background: #007bff; color: #ffffff; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; flex-shrink: 0; min-height: 40px;"
                ` : '';
                const messageMobileStyles = isMobile ? `
                    style="margin-bottom: 12px; padding: 12px; border-radius: 8px; max-width: 85%; word-wrap: break-word; font-size: 14px; line-height: 1.4; background: #333; color: #e0e0e0; margin-right: auto; border: 1px solid #555;"
                ` : '';
                const speakerNameMobileStyles = isMobile ? `
                    style="color: #e0e0e0; font-weight: bold; margin-bottom: 5px; display: block;"
                ` : '';
                const contentWrapperMobileStyles = isMobile ? `
                    style="color: inherit; word-wrap: break-word;"
                ` : '';
                const nameMobileStyles = isMobile ? `
                    style="font-size: 14px; color: #e0e0e0; font-weight: bold;"
                ` : '';
                const hideOnMobile = isMobile ? 'style="display: none;"' : '';

                const chatHtml = `
                    <div class="fullscreen-chat" ${mobileStyles}>
                        <div class="fullscreen-chat-header" ${headerMobileStyles}>
                            <div class="header-left">
                                <button class="conversation-menu-btn" title="Open Conversation Menu">☰</button>
                            </div>
                            <div class="chat-modal-name" ${nameMobileStyles}>${escapeHtml(character.name)}</div>
                            <div class="token-usage-display" ${hideOnMobile}>
                                <span class="token-label">Tokens Today:</span>
                                <span id="today-tokens">Loading...</span>
                                <span class="token-label">Month:</span>
                                <span id="monthly-tokens">Loading...</span>
                            </div>
                            <div class="image-usage-display" ${hideOnMobile}>
                                <span class="token-label">Images Today:</span>
                                <span id="today-images">Loading...</span>
                                <span class="token-label">Month:</span>
                                <span id="monthly-images">Loading...</span>
                            </div>
                            <div class="header-actions">
                                <button id="image-settings-btn" class="image-settings-btn" title="Image Generation Settings">🎨</button>
                                <button class="close-fullscreen-btn">✕</button>
                            </div>
                        </div>
                        <div class="fullscreen-chat-content" ${contentMobileStyles}>
                            <div class="fullscreen-chat-history" id="chat-history" ${historyMobileStyles} style="position: relative;">
                                <div class="chat-message bot" ${messageMobileStyles}>
                                    <span class="speaker-name" ${speakerNameMobileStyles}>${escapeHtml(character.name)}:</span>
                                    <span class="chat-message-content-wrapper" ${contentWrapperMobileStyles}>${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="fullscreen-chat-input-container" ${inputContainerMobileStyles}>
                            <div class="input-wrapper input-flex-row" ${inputRowMobileStyles}>
                                <textarea id="chat-input" placeholder="Type your message..." rows="1" ${inputMobileStyles}></textarea>
                                <button id="send-chat" class="fullscreen-chat-send-btn" ${sendButtonMobileStyles}>Send</button>
                            </div>
                            <!-- Image Generation Panel -->
                            <div class="image-generation-panel" style="display: none;">
                                <div class="image-panel-header">
                                    <h4>🎨 Image Generation</h4>
                                    <button class="close-image-panel">✕</button>
                                </div>
                                <div class="image-panel-content">
                                    <div class="prompt-section">
                                        <label>Custom Prompt (optional):</label>
                                        <textarea id="custom-prompt" placeholder="Add additional context or modify the prompt..." rows="2"></textarea>
                                    </div>
                                    <div class="model-section">
                                        <label>Model:</label>
                                        <select id="swarmui-model">
                                            <option value="">Loading models...</option>
                                        </select>
                                    </div>
                                    <div class="parameters-section">
                                        <div class="param-group">
                                            <label>Steps:</label>
                                            <input type="number" id="image-steps" value="20" min="1" max="100">
                                        </div>
                                        <div class="param-group">
                                            <label>CFG Scale:</label>
                                            <input type="number" id="image-cfg-scale" value="7.0" min="0.1" max="20" step="0.1">
                                        </div>
                                        <div class="param-group">
                                            <label>Width:</label>
                                            <input type="number" id="image-width" value="512" min="256" max="2048" step="64">
                                        </div>
                                        <div class="param-group">
                                            <label>Height:</label>
                                            <input type="number" id="image-height" value="512" min="256" max="2048" step="64">
                                        </div>
                                    </div>
                                    <div class="generated-prompt-section">
                                        <label>Generated Prompt:</label>
                                        <textarea id="generated-prompt" placeholder="Click 'Generate Prompt' to create a prompt from chat history..." rows="4"></textarea>
                                        <div class="prompt-actions">
                                            <button id="generate-prompt-btn" class="button-secondary">Generate Prompt</button>
                                            <button id="create-image-btn" class="button-primary">Create Image</button>
                                        </div>
                                    </div>
                                    <div class="image-results" id="image-results"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Replace body content with chat
                $('body').html(chatHtml);
                console.log('Chat HTML created, checking for buttons...');
                console.log('Close button exists:', $('.close-fullscreen-btn').length);
                console.log('Menu button exists:', $('.conversation-menu-btn').length);
                console.log('Image settings button exists:', $('#image-settings-btn').length);

                // Create image settings modal outside the chat container
                const modalHtml = `
                    <!-- Image Settings Modal -->
                    <div id="image-settings-modal" class="image-settings-modal">
                        <div class="image-settings-content">
                            <div class="settings-header">
                                <h3>🎨 Image Generation Settings</h3>
                                <button class="close-settings-modal">✕</button>
                            </div>
                            <div class="settings-body">
                                <div class="settings-section">
                                    <h4>Custom Prompt Workflow</h4>
                                    <div class="setting-group">
                                        <label>Custom Prompt Template:</label>
                                        <textarea id="custom-prompt-template" placeholder="Summarize the story so far, and create a comma separated list to create an image generation prompt" rows="3"></textarea>
                                        <p class="setting-description">This template will be used to generate image prompts from chat history.</p>
                                    </div>
                                    <div class="setting-group">
                                        <label>
                                            <input type="checkbox" id="use-full-history"> Use full chat history
                                        </label>
                                        <p class="setting-description">If unchecked, only the last message will be used for prompt generation.</p>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Auto-trigger Keywords</h4>
                                    <div class="setting-group">
                                        <label>Trigger Keywords:</label>
                                        <input type="text" id="auto-trigger-keywords" placeholder="send me a picture, take a picture, draw me">
                                        <p class="setting-description">Comma-separated keywords that will automatically trigger image generation.</p>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Prompt Editing</h4>
                                    <div class="setting-group">
                                        <label>
                                            <input type="checkbox" id="allow-prompt-editing" checked> Allow prompt editing before sending
                                        </label>
                                        <p class="setting-description">If checked, users can edit the generated prompt before it's sent to SwarmUI.</p>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Provider & Model</h4>
                                    <div class="setting-group">
                                        <label>Image Generation Provider:</label>
                                        <select id="image-provider">
                                            <option value="swarmui">SwarmUI</option>
                                            <option value="nanogpt">Nano-GPT</option>
                                        </select>
                                        <p class="setting-description">Select which image generation service to use.</p>
                                    </div>
                                    <div class="setting-group">
                                        <label>Default Model:</label>
                                        <select id="default-model">
                                            <option value="">Loading models...</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Parameters</h4>
                                    <div class="setting-group">
                                        <label>Default Steps:</label>
                                        <input type="number" id="default-steps" value="20" min="1" max="100">
                                    </div>
                                    <div class="setting-group">
                                        <label>Default CFG Scale:</label>
                                        <input type="number" id="default-cfg-scale" value="7.0" min="0.1" max="20" step="0.1">
                                    </div>
                                    <div class="setting-group">
                                        <label>Default Width:</label>
                                        <input type="number" id="default-width" value="512" min="256" max="2048" step="64">
                                    </div>
                                    <div class="setting-group">
                                        <label>Default Height:</label>
                                        <input type="number" id="default-height" value="512" min="256" max="2048" step="64">
                                    </div>
                                    <div class="setting-group">
                                        <label>Negative Prompt:</label>
                                        <textarea id="negative-prompt" placeholder="Things to avoid in generated images (e.g., blurry, low quality, distorted)" rows="3"></textarea>
                                        <p class="setting-description">Specify what you don't want in the generated images.</p>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Slash Commands</h4>
                                    <div class="setting-group">
                                        <label>/self Command Template:</label>
                                        <textarea id="slash-self-template" placeholder="Create a selfie prompt based on the character description and current chat context..." rows="3"></textarea>
                                    </div>
                                    <div class="setting-group">
                                        <label>/generate Command Template:</label>
                                        <textarea id="slash-generate-template" placeholder="Generate an image based on the following prompt: {prompt}" rows="2"></textarea>
                                    </div>
                                    <div class="setting-group">
                                        <label>/look Command Template:</label>
                                        <textarea id="slash-look-template" placeholder="Create a prompt for an image showing the current surroundings..." rows="3"></textarea>
                                    </div>
                                    <div class="setting-group">
                                        <label>/custom1 Command Template:</label>
                                        <textarea id="slash-custom1-template" placeholder="Custom prompt template 1: {prompt}" rows="2"></textarea>
                                    </div>
                                    <div class="setting-group">
                                        <label>/custom2 Command Template:</label>
                                        <textarea id="slash-custom2-template" placeholder="Custom prompt template 2: {prompt}" rows="2"></textarea>
                                    </div>
                                    <div class="setting-group">
                                        <label>/custom3 Command Template:</label>
                                        <textarea id="slash-custom3-template" placeholder="Custom prompt template 3: {prompt}" rows="2"></textarea>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h4>Import/Export Settings</h4>
                                    <div class="setting-group">
                                        <button id="export-settings" class="button-secondary">Export Settings</button>
                                        <button id="import-settings" class="button-secondary">Import Settings</button>
                                        <input type="file" id="import-file" accept=".json" style="display: none;">
                                    </div>
                                </div>
                                
                                <div class="settings-actions">
                                    <button id="save-settings" class="button-primary">Save Settings</button>
                                    <button id="test-connection" class="button-secondary">Test Connection</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Append modal to body (outside chat container)
                $('body').append(modalHtml);

                // Create and show conversation sidebar
                createConversationSidebar();

                // Load token usage stats
                loadTokenUsageStats();
                // NEW_INSERT: also load image usage stats (defaults to SwarmUI provider)
                loadImageUsageStats();

                // Focus input
                setTimeout(() => {
                    $('#chat-input').focus();
                }, 300);
                
                // Force apply mobile styles or ensure desktop styles
                setTimeout(() => {
                    if (window.innerWidth <= 768) {
                        forceApplyMobileStyles();
                    } else {
                        ensureDesktopStyles();
                    }
                    // Apply responsive modal fixes for all screen sizes
                    applyResponsiveModalFixes();
                }, 500);
                
                // Initialize image generation functionality
                initializeImageGeneration();
                
                // Scroll to bottom for initial message
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
                
                // Test button functionality after a short delay
                setTimeout(function() {
                    console.log('Testing button functionality...');
                    console.log('Close button clickable:', $('.close-fullscreen-btn').is(':visible'));
                    console.log('Menu button clickable:', $('.conversation-menu-btn').is(':visible'));
                    console.log('Image settings button clickable:', $('#image-settings-btn').is(':visible'));
                    
                    // Test if event handlers are attached
                    const closeBtn = $('.close-fullscreen-btn')[0];
                    const menuBtn = $('.conversation-menu-btn')[0];
                    const settingsBtn = $('#image-settings-btn')[0];
                    
                    if (closeBtn) {
                        console.log('Close button event handlers:', $._data(closeBtn, 'events'));
                    }
                    if (menuBtn) {
                        console.log('Menu button event handlers:', $._data(menuBtn, 'events'));
                    }
                    if (settingsBtn) {
                        console.log('Settings button event handlers:', $._data(settingsBtn, 'events'));
                    }
                }, 500);

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

            // Notify masonry system that chat is ending (but don't trigger reloads)
            $(document).trigger('pmv_end_chat');
            
            // Also notify via global API if available
            if (window.pmvMasonry && window.pmvMasonry.setChatModalState) {
                window.pmvMasonry.setChatModalState(false);
            }

            if (chatState.originalBodyContent) {
                $('body').html(chatState.originalBodyContent);
                // Don't call initialize() as it might trigger AJAX reloads
                // Just ensure the page is in a good state
                if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.chat_button_text) {
                    window.pmv_chat_button_text = pmv_ajax_object.chat_button_text;
                }
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
            
            pushContentUp();
            
            // Notify PMV_ConversationManager of new message
            if (window.PMV_ConversationManager) {
                $(document).trigger('message:added', [{type: 'error', content: errorMessage}]);
            }

            setTimeout(function() {
                $('#send-chat').prop('disabled', false).text('Send');
            }, 100);
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

        // NEW_INSERT: function to fetch image usage stats
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

        // Initialize everything
        function initialize() {
            console.log('Initializing PNG Metadata Viewer chat...');
            ensureModalExists();

            if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.chat_button_text) {
                window.pmv_chat_button_text = pmv_ajax_object.chat_button_text;
            }

            chatState.initialized = true;
        }
        
        // Debug function to test button functionality
        window.testChatButtons = function() {
            console.log('Testing chat button functionality...');
            console.log('Close button:', $('.close-fullscreen-btn').length);
            console.log('Menu button:', $('.conversation-menu-btn').length);
            console.log('Image settings button:', $('#image-settings-btn').length);
            
            // Test clicking each button
            $('.close-fullscreen-btn').trigger('click');
            setTimeout(() => {
                $('.conversation-menu-btn').trigger('click');
            }, 1000);
            setTimeout(() => {
                $('#image-settings-btn').trigger('click');
            }, 2000);
        };

        // Debug function to test close sidebar button
        window.testCloseSidebarButton = function() {
            console.log('=== Testing Close Sidebar Button ===');
            console.log('Sidebar exists:', $('.conversation-sidebar').length);
            console.log('Sidebar is open:', $('.conversation-sidebar').hasClass('open'));
            console.log('Close button exists:', $('.close-sidebar-btn').length);
            
            const closeBtn = $('.close-sidebar-btn')[0];
            if (closeBtn) {
                console.log('Close button details:');
                console.log('- Text:', $(closeBtn).text());
                console.log('- Classes:', $(closeBtn).attr('class'));
                console.log('- Visible:', $(closeBtn).is(':visible'));
                console.log('- Clickable:', $(closeBtn).is(':enabled'));
                
                // Test manual click
                console.log('Testing manual click...');
                $(closeBtn).trigger('click');
                
                // Check if sidebar closed
                setTimeout(() => {
                    console.log('Sidebar open after click:', $('.conversation-sidebar').hasClass('open'));
                }, 100);
            } else {
                console.error('Close button not found!');
            }
        };

        // Force close sidebar function
        window.forceCloseSidebar = function() {
            console.log('=== Force Closing Sidebar ===');
            const sidebar = $('.conversation-sidebar');
            console.log('Sidebar before:', sidebar.hasClass('open'));
            
            // Remove the open class
            sidebar.removeClass('open');
            
            // Force apply the transform via CSS
            sidebar.css({
                'transform': 'translateX(-100%)',
                'transition': 'transform 0.3s ease'
            });
            
            console.log('Sidebar after:', sidebar.hasClass('open'));
            console.log('Sidebar transform:', sidebar.css('transform'));
            
            // Verify the change
            setTimeout(() => {
                console.log('Sidebar final state - open class:', sidebar.hasClass('open'));
                console.log('Sidebar final transform:', sidebar.css('transform'));
            }, 100);
        };

        // Force open sidebar function
        window.forceOpenSidebar = function() {
            console.log('=== Force Opening Sidebar ===');
            const sidebar = $('.conversation-sidebar');
            console.log('Sidebar before:', sidebar.hasClass('open'));
            
            // Add the open class
            sidebar.addClass('open');
            
            // Force apply the transform via CSS
            sidebar.css({
                'transform': 'translateX(0)',
                'transition': 'transform 0.3s ease'
            });
            
            console.log('Sidebar after:', sidebar.hasClass('open'));
            console.log('Sidebar transform:', sidebar.css('transform'));
            
            // Verify the change
            setTimeout(() => {
                console.log('Sidebar final state - open class:', sidebar.hasClass('open'));
                console.log('Sidebar final transform:', sidebar.css('transform'));
            }, 100);
        };

        // Comprehensive test function for close button
        window.debugCloseButton = function() {
            console.log('=== COMPREHENSIVE CLOSE BUTTON DEBUG ===');
            
            // Check if sidebar exists
            const sidebar = $('.conversation-sidebar');
            console.log('1. Sidebar exists:', sidebar.length > 0);
            if (sidebar.length > 0) {
                console.log('   - Sidebar classes:', sidebar.attr('class'));
                console.log('   - Sidebar is open:', sidebar.hasClass('open'));
                console.log('   - Sidebar CSS display:', sidebar.css('display'));
                console.log('   - Sidebar CSS visibility:', sidebar.css('visibility'));
                console.log('   - Sidebar CSS z-index:', sidebar.css('z-index'));
            }
            
            // Check if close button exists
            const closeBtn = $('.close-sidebar-btn');
            console.log('2. Close button exists:', closeBtn.length > 0);
            if (closeBtn.length > 0) {
                console.log('   - Button text:', closeBtn.text());
                console.log('   - Button classes:', closeBtn.attr('class'));
                console.log('   - Button is visible:', closeBtn.is(':visible'));
                console.log('   - Button is enabled:', closeBtn.is(':enabled'));
                console.log('   - Button CSS pointer-events:', closeBtn.css('pointer-events'));
                console.log('   - Button CSS cursor:', closeBtn.css('cursor'));
                console.log('   - Button onclick attribute:', closeBtn.attr('onclick'));
                
                // Test all possible click methods
                console.log('3. Testing click methods...');
                
                // Method 1: jQuery trigger
                console.log('   Testing jQuery trigger...');
                closeBtn.trigger('click');
                
                setTimeout(() => {
                    console.log('   Sidebar open after jQuery trigger:', sidebar.hasClass('open'));
                    
                    // Method 2: Direct DOM click
                    console.log('   Testing direct DOM click...');
                    closeBtn[0].click();
                    
                    setTimeout(() => {
                        console.log('   Sidebar open after DOM click:', sidebar.hasClass('open'));
                        
                        // Method 3: Manual class removal
                        console.log('   Testing manual class removal...');
                        sidebar.removeClass('open');
                        console.log('   Sidebar open after manual removal:', sidebar.hasClass('open'));
                        
                        // Method 4: Test if CSS is preventing clicks
                        console.log('4. Testing CSS interference...');
                        const originalPointerEvents = closeBtn.css('pointer-events');
                        closeBtn.css('pointer-events', 'auto');
                        console.log('   Set pointer-events to auto');
                        
                        setTimeout(() => {
                            closeBtn.trigger('click');
                            setTimeout(() => {
                                console.log('   Sidebar open after pointer-events fix:', sidebar.hasClass('open'));
                                closeBtn.css('pointer-events', originalPointerEvents);
                                console.log('=== DEBUG COMPLETE ===');
                            }, 100);
                        }, 100);
                    }, 100);
                }, 100);
            } else {
                console.error('Close button not found!');
            }
        };

        // Event handlers (UPDATED to delegate conversation management)
        $(document)
            // Character modal and chat button handlers (PRESERVED)
            .on('click', '.png-image-container img, .png-card img, .character-card img, img[data-metadata]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $card = $(this).closest('.png-card, .character-card, [data-metadata]');
                if ($card.length) openCharacterModal($card);
            })
            .on('openCharacterModal', function(e, $card) {
                console.log('openCharacterModal event triggered');
                if ($card && $card.length) {
                    openCharacterModal($card);
                }
            })
            .on('click', '.png-chat-button, button[data-metadata]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const metadata = $(this).attr('data-metadata') || $(this).closest('[data-metadata]').attr('data-metadata');
                if (metadata) startFullScreenChat(metadata);
            })
            .on('click', '.close-modal, #png-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('close-modal')) {
                    $('#png-modal').hide();
                }
            })
            
            // Chat interface handlers (UPDATED to delegate to PMV_ConversationManager)
            .on('click', '.close-chat-btn, .close-fullscreen-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close button clicked!');
                closeFullScreenChat();
            })
            .on('click', '.menu-btn, .conversation-menu-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Menu button clicked!');
                console.log('Sidebar exists:', $('.conversation-sidebar').length);
                console.log('Sidebar current state:', $('.conversation-sidebar').hasClass('open'));
                console.log('Window width:', $(window).width());
                
                // Simple toggle using CSS class for both mobile and desktop
                $('.conversation-sidebar').toggleClass('open');
                console.log('Toggled sidebar open class. New state:', $('.conversation-sidebar').hasClass('open'));
            })
            .on('click', '.close-sidebar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close sidebar button clicked!');
                window.forceCloseSidebar();
            })
            
            // Add additional event handler for close button with different selector
            .on('click', '.conversation-sidebar .close-sidebar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close sidebar button clicked (alternative handler)!');
                window.forceCloseSidebar();
            })
            
            // Add event delegation for dynamically created close button
            .on('click', '.close-sidebar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close sidebar button clicked (delegated handler)!');
                console.log('Button element:', this);
                console.log('Button text:', $(this).text());
                window.forceCloseSidebar();
            })
            
            // Add click handler for any element with close-sidebar-btn class
            .on('click', '[class*="close-sidebar-btn"]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close sidebar button clicked (wildcard handler)!');
                console.log('Element:', this);
                console.log('Classes:', $(this).attr('class'));
                window.forceCloseSidebar();
            })
            
            // Chat input handlers - UPDATED WITH REAL API CALL
            .on('click', '#send-chat', function(e) {
                e.preventDefault();
                const message = $('#chat-input').val().trim();
                if (!message) return;

                console.log('Send button clicked, message:', message);

                // Check for slash commands
                if (message.startsWith('/')) {
                    handleSlashCommand(message);
                    return;
                }

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
                pushContentUp();

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

                            console.log('Bot response:', botResponse);

                            // Add bot response to chat
                            $('#chat-history').append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">${characterName}:</span>
                                    <span class="chat-message-content-wrapper">${escapeHtml(botResponse)}</span>
                                </div>
                            `);

                            pushContentUp();
                            
                            // Notify PMV_ConversationManager of new assistant message
                            if (window.PMV_ConversationManager) {
                                $(document).trigger('message:added', [{type: 'assistant', content: botResponse}]);
                            }
                            
                            // Update token usage display
                            loadTokenUsageStats();
                            
                        } else {
                            console.error('API response error:', response);
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
                        
                        console.error('Final error message:', errorMessage);
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



        // Simple and reliable scroll to bottom function
        function scrollToBottom() {
            console.log('=== scrollToBottom called ===');
            
            // Try multiple possible scroll containers
            const possibleScrollElements = [
                '.fullscreen-chat-content',
                '#chat-history',
                '.fullscreen-chat-history',
                '.fullscreen-chat'
            ];
            
            let scrollElement = null;
            let elementName = '';
            
            for (const selector of possibleScrollElements) {
                const $element = $(selector);
                if ($element.length) {
                    const element = $element[0];
                    console.log(`Found element: ${selector}`);
                    console.log(`- scrollHeight: ${element.scrollHeight}`);
                    console.log(`- clientHeight: ${element.clientHeight}`);
                    console.log(`- scrollTop: ${element.scrollTop}`);
                    console.log(`- overflow: ${getComputedStyle(element).overflow}`);
                    console.log(`- overflowY: ${getComputedStyle(element).overflowY}`);
                    
                    // Check if this element can actually scroll
                    if (element.scrollHeight > element.clientHeight) {
                        scrollElement = element;
                        elementName = selector;
                        console.log(`Selected ${selector} as scroll element`);
                        break;
                    }
                }
            }
            
            if (scrollElement) {
                console.log(`Scrolling ${elementName} to bottom...`);
                
                // Force scroll to bottom immediately
                scrollElement.scrollTop = scrollElement.scrollHeight;
                console.log(`Set scrollTop to ${scrollElement.scrollHeight}`);
                
                // Also try smooth scroll as backup
                setTimeout(() => {
                    try {
                        scrollElement.scrollTo({
                            top: scrollElement.scrollHeight,
                            behavior: 'smooth'
                        });
                        console.log('Smooth scroll executed');
                    } catch (error) {
                        console.log('Smooth scroll failed, using instant scroll');
                        scrollElement.scrollTop = scrollElement.scrollHeight;
                    }
                }, 10);
            } else {
                console.log('No scrollable element found!');
                console.log('Available elements:');
                possibleScrollElements.forEach(selector => {
                    const $element = $(selector);
                    console.log(`- ${selector}: ${$element.length} elements`);
                    if ($element.length) {
                        const element = $element[0];
                        console.log(`  - scrollHeight: ${element.scrollHeight}, clientHeight: ${element.clientHeight}`);
                    }
                });
            }
        }
        
        // Debug function for testing scroll
        window.testScroll = function() {
            console.log('=== Testing Scroll Function ===');
            
            const $chatContent = $('.fullscreen-chat-content');
            const $chatHistory = $('#chat-history');
            
            console.log('Chat content exists:', $chatContent.length > 0);
            console.log('Chat history exists:', $chatHistory.length > 0);
            
            if ($chatContent.length) {
                const element = $chatContent[0];
                console.log('Chat content - Scroll position:', element.scrollTop);
                console.log('Chat content - Scroll height:', element.scrollHeight);
                console.log('Chat content - Client height:', element.clientHeight);
                console.log('Chat content - Should scroll:', element.scrollHeight > element.clientHeight);
                console.log('Chat content - CSS overflow:', getComputedStyle(element).overflow);
                console.log('Chat content - CSS overflowY:', getComputedStyle(element).overflowY);
            }
            
            if ($chatHistory.length) {
                const element = $chatHistory[0];
                console.log('Chat history - Scroll position:', element.scrollTop);
                console.log('Chat history - Scroll height:', element.scrollHeight);
                console.log('Chat history - Client height:', element.clientHeight);
                console.log('Chat history - Should scroll:', element.scrollHeight > element.clientHeight);
                console.log('Chat history - CSS overflow:', getComputedStyle(element).overflow);
                console.log('Chat history - CSS overflowY:', getComputedStyle(element).overflowY);
            }
            
            scrollToBottom();
        };
        
        // Force scroll function that adds CSS if needed
        window.forceScroll = function() {
            console.log('=== Force Scroll Function ===');
            
            // First try normal scroll
            scrollToBottom();
            
            // If that doesn't work, try forcing CSS
            setTimeout(() => {
                const $chatContent = $('.fullscreen-chat-content');
                if ($chatContent.length) {
                    console.log('Forcing overflow-y: auto on chat content');
                    $chatContent.css({
                        'overflow-y': 'auto',
                        'max-height': '70vh',
                        'height': 'auto'
                    });
                    scrollToBottom();
                }
            }, 100);
        };

        // Legacy functions for backward compatibility
        function pushContentUp() {
            console.log('=== pushContentUp called ===');
            scrollToBottom();
        }

        function forceScrollToBottom() {
            scrollToBottom();
        }

        function isNearBottom() {
            const $chatHistory = $('#chat-history');
            if ($chatHistory.length) {
                const chatHistoryElement = $chatHistory[0];
                const threshold = 50; // pixels from bottom
                return chatHistoryElement.scrollTop + chatHistoryElement.clientHeight >= 
                       chatHistoryElement.scrollHeight - threshold;
            }
            return true;
        }

        function showNewMessageIndicator() {
            // Remove existing indicator
            $('.new-message-indicator').remove();
            
            const $chatHistory = $('#chat-history');
            if ($chatHistory.length) {
                const indicator = $(`
                    <div class="new-message-indicator" style="
                        position: absolute;
                        bottom: 20px;
                        right: 20px;
                        background: #007bff;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 20px;
                        font-size: 12px;
                        cursor: pointer;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        z-index: 1000;
                        animation: bounce 0.6s ease-in-out;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    ">
                        <span>📬</span>
                        <span>New message</span>
                        <span>↓</span>
                    </div>
                `);
                
                $chatHistory.append(indicator);
                
                // Add click handler to scroll to bottom
                indicator.on('click', () => {
                    scrollToBottom();
                    indicator.fadeOut(300, function() {
                        $(this).remove();
                    });
                });
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    indicator.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        // Add CSS animation for bounce effect
        function addScrollIndicatorCSS() {
            if (!$('#scroll-indicator-css').length) {
                $('head').append(`
                    <style id="scroll-indicator-css">
                        @keyframes bounce {
                            0%, 20%, 50%, 80%, 100% {
                                transform: translateY(0);
                            }
                            40% {
                                transform: translateY(-10px);
                            }
                            60% {
                                transform: translateY(-5px);
                            }
                        }
                        
                        .new-message-indicator:hover {
                            background: #0056b3 !important;
                            transform: translateY(-2px);
                            transition: all 0.2s ease;
                        }
                        
                        /* Animation for new messages appearing */
                        .chat-message {
                            animation: messageSlideIn 0.3s ease-out;
                        }
                        
                        @keyframes messageSlideIn {
                            from {
                                opacity: 0;
                                transform: translateY(20px);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                        
                        /* Push-up effect for existing messages */
                        .chat-message.push-up {
                            transition: transform 0.3s ease-out;
                        }
                    </style>
                `);
            }
        }

        // Setup scroll event listeners for chat history
        function setupChatScrollListeners() {
            // No longer needed since we always scroll to new messages
        }

        // Initialize when DOM is ready
        initialize();
        
        // Add scroll indicator CSS
        addScrollIndicatorCSS();
        

        
        // Setup scroll event listeners
        setupChatScrollListeners();

        // Image Generation Functions
        function initializeImageGeneration() {
            console.log('Initializing image generation...');
            
            // Use event delegation for all dynamically created elements
            // Remove any existing handlers first to prevent duplicates
            $(document).off('click.imageSettings');
            
            // Use more specific event delegation that will catch dynamically created elements
            $(document).on('click.imageSettings', '#image-settings-btn, .image-settings-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Image settings button clicked!');
                openImageSettings();
            });
            
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
            
            $(document).on('click.imageSettings', '#save-settings', function(e) {
                e.preventDefault();
                saveImageSettings();
            });
            
            $(document).on('click.imageSettings', '#test-connection', function(e) {
                e.preventDefault();
                testSwarmUIConnection();
            });
            
            $(document).on('click.imageSettings', '#export-settings', function(e) {
                e.preventDefault();
                exportImageSettings();
            });
            
            $(document).on('click.imageSettings', '#import-settings', function(e) {
                e.preventDefault();
                $('#import-file').click();
            });
            
            $(document).on('change.imageSettings', '#import-file', function(e) {
                importImageSettings(e);
            });
            
            $(document).on('click.imageSettings', '#generate-prompt-btn', function(e) {
                e.preventDefault();
                generateImagePrompt();
            });
            
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
        
        function openImageSettings() {
            console.log('Opening image settings...');
            
            // Ensure modal exists
            ensureModalExists();
            
            // Debug modal state
            const modal = $('#image-settings-modal');
            console.log('Modal found, current display:', modal.css('display'));
            console.log('Modal z-index:', modal.css('z-index'));
            console.log('Modal position:', modal.css('position'));
            
            // Load settings
            loadImageSettings();
            
            // Show modal with debugging
            modal.show();
            console.log('Modal display after show:', modal.css('display'));
            console.log('Modal visibility:', modal.is(':visible'));
            
            // Debug any AJAX calls that might be triggered
            console.log('About to load models for settings...');
            
            // Add error handling to catch any AJAX failures
            $(document).ajaxError(function(event, xhr, settings, error) {
                console.error('AJAX Error in settings:', {
                    url: settings.url,
                    type: settings.type,
                    data: settings.data,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
            });
        }
        
        function closeImageSettings() {
            $('#image-settings-modal').hide();
        }
        
        // Fix for the SwarmUI API integration
        function loadSwarmUIModels() {
            console.log('Loading SwarmUI models...');
            
            if (typeof pmv_ajax_object === 'undefined') {
                console.error('AJAX object not available');
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
                    console.log('Session response:', sessionResponse);
                    if (sessionResponse.success) {
                        // Now load models with session
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            timeout: 15000, // 15 second timeout
                            data: {
                                action: 'pmv_get_available_models',
                                nonce: pmv_ajax_object.nonce
                            },
                            success: function(response) {
                                console.log('Models response:', response);
                                if (response.success && response.data) {
                                    populateModelDropdown(response.data);
                                } else {
                                    console.error('No models in response or error:', response);
                                    $('#swarmui-model').html('<option value="">Error loading models</option>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Models AJAX error:', {xhr, status, error});
                                $('#swarmui-model').html('<option value="">Error loading models</option>');
                            }
                        });
                    } else {
                        console.error('Session creation failed:', sessionResponse);
                        $('#swarmui-model').html('<option value="">Session creation failed</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Session AJAX error:', {xhr, status, error});
                    $('#swarmui-model').html('<option value="">Session creation failed</option>');
                }
            });
        }
        
        function populateModelDropdown(modelsData) {
            const $select = $('#swarmui-model');
            const $defaultSelect = $('#default-model');
            
            $select.empty();
            $defaultSelect.empty();
            
            // Handle the response format from SwarmUI API
            let models = [];
            
            // SwarmUI API returns models in 'files' array, not 'models' object
            if (modelsData.files && Array.isArray(modelsData.files)) {
                // Process SwarmUI files array
                modelsData.files.forEach(file => {
                    const modelName = file.name || file.title || 'Unknown Model';
                    const displayName = file.title || file.name || 'Unknown Model';
                    
                    $select.append($('<option>').val(modelName).text(displayName));
                    $defaultSelect.append($('<option>').val(modelName).text(displayName));
                    models.push(modelName);
                });
            } else if (modelsData.models && typeof modelsData.models === 'object') {
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
        
        function createImage() {
            const prompt = $('#generated-prompt').val() || $('#custom-prompt').val();
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            const provider = settings.provider || 'swarmui';
            
            // Get model based on provider
            let model;
            if (provider === 'nanogpt') {
                model = $('#default-model').val(); // For settings modal
            } else {
                model = $('#swarmui-model').val(); // For main image panel
            }
            
            const steps = $('#image-steps').val();
            const cfgScale = $('#image-cfg-scale').val();
            const width = $('#image-width').val();
            const height = $('#image-height').val();
            
            if (!prompt.trim()) {
                alert('Please generate or enter a prompt first.');
                return;
            }
            
            if (!model) {
                alert('Please select a model.');
                return;
            }
            
            $('#create-image-btn').prop('disabled', true).text('Creating...');
            
            // Add progress indicator with better styling
            $('#image-results').html(`
                <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                    <div class="spinner" style="border: 3px solid #333; border-top: 3px solid #007bff; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 10px;"></div>
                    <p style="color: #e0e0e0; margin: 0;">Generating image with ${provider === 'nanogpt' ? 'Nano-GPT' : 'SwarmUI'}...</p>
                    <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">This may take 30-60 seconds</p>
                </div>
            `);
            
            // Detect WebSocket support automatically (allows override via settings)
            let supportsWebSocket = ('WebSocket' in window);
            if (settings.hasOwnProperty('supportsWebSocket')) {
                supportsWebSocket = settings.supportsWebSocket;
            }
            
            // Add WebSocket option for SwarmUI
            if (provider === 'swarmui' && supportsWebSocket) {
                const useWebSocket = confirm('WebSocket connection is available for real-time progress updates. Would you like to use it?\n\nClick OK for WebSocket (real-time updates)\nClick Cancel for regular generation');
                if (useWebSocket) {
                    const negativePrompt = $('#negative-prompt').val() || '';
                    createImageWithWebSocket(prompt, model, steps, cfgScale, width, height, negativePrompt);
                    return;
                }
            }
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_generate_image',
                    prompt: prompt,
                    model: model,
                    steps: steps,
                    cfg_scale: cfgScale,
                    width: width,
                    height: height,
                    images_count: 1,
                    provider: provider,
                    nonce: pmv_ajax_object.nonce,
                    character_id: window.PMV_ConversationManager?.characterId || 'unknown'
                },
                success: function(response) {
                    if (response.success) {
                        displayImageResults(response.data);
                        
                        // Show WebSocket support info if available
                        if (response.data.supports_websocket) {
                            console.log('WebSocket support detected:', response.data.websocket_url);
                            // In the future, we could implement real-time progress updates via WebSocket
                        }
                    } else {
                        const errorMessage = response.data?.message || 'Unknown error occurred';
                        $('#image-results').html(`
                            <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                                <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ Image Generation Failed</h4>
                                <p style="margin: 0; color: #f8d7da;">${errorMessage}</p>
                                ${response.data?.supports_websocket ? '<p style="margin: 10px 0 0 0; font-size: 0.9em; color: #f8d7da;">💡 Tip: WebSocket connection is available for better performance.</p>' : ''}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Image generation error:', {xhr, status, error});
                    const providerName = provider === 'nanogpt' ? 'Nano-GPT' : 'SwarmUI';
                    $('#image-results').html(`
                        <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ Network Error</h4>
                            <p style="margin: 0; color: #f8d7da;">Failed to connect to ${providerName} server. Please check your connection and try again.</p>
                            <p style="margin: 10px 0 0 0; font-size: 0.9em; color: #f8d7da;">Error: ${error}</p>
                        </div>
                    `);
                },
                complete: function() {
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                }
            });
        }
        
        function displayImageResults(data) {
            const $results = $('#image-results');
            
            if (!data.images || data.images.length === 0) {
                $results.html(`
                    <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                        <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ No Images Generated</h4>
                        <p style="margin: 0; color: #f8d7da;">The image generation completed but no images were returned.</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div style="margin: 10px 0;">';
            
            // Add success message with provider info
            const providerName = data.provider === 'nanogpt' ? 'Nano-GPT' : 'SwarmUI';
            if (data.supports_websocket) {
                html += `
                    <div style="background: #1b2d1b; border: 1px solid #155724; color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #d4edda;">✅ Image generated successfully with ${providerName}!</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #d4edda;">💡 WebSocket connection available for real-time updates</p>
                    </div>
                `;
            } else {
                html += `
                    <div style="background: #1b2d1b; border: 1px solid #155724; color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #d4edda;">✅ Image generated successfully with ${providerName}!</p>
                    </div>
                `;
            }
            
            // Display each generated image
            data.images.forEach((imageUrl, index) => {
                html += `
                    <div style="margin-bottom: 15px; text-align: center;">
                        <img src="${imageUrl}" style="max-width: 100%; border: 1px solid #444; border-radius: 4px; background: #1a1a1a;" loading="lazy" />
                        <div style="margin-top: 10px;">
                            <button onclick="downloadImage('${imageUrl}', 'generated-image-${index + 1}.png')" style="background: #007bff; color: #ffffff; border: 1px solid #0056b3; padding: 5px 10px; border-radius: 3px; margin-right: 5px;">Download</button>
                            <button onclick="addImageToChat('${imageUrl}')" style="background: #28a745; color: #ffffff; border: 1px solid #1e7e34; padding: 5px 10px; border-radius: 3px;">Add to Chat</button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $results.html(html);
        }
        
        // Add helper functions for image actions
        function downloadImage(imageUrl, filename) {
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function addImageToChat(imageUrl) {
            if (typeof $('#chat-history').length !== 'undefined' && $('#chat-history').length > 0) {
                $('#chat-history').append(`
                    <div class="chat-message bot">
                        <span class="speaker-name">Generated Image:</span>
                        <div class="chat-message-content-wrapper">
                            <img src="${imageUrl}" style="max-width: 100%; border-radius: 4px;" loading="lazy" />
                        </div>
                    </div>
                `);
                pushContentUp();
            } else {
                alert('Chat interface not available. Please open a chat first.');
            }
        }
        
        function setupAutoTrigger() {
            $('#chat-input').on('input', function() {
                const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
                const autoTriggerKeywords = (settings.autoTriggerKeywords || 'send me a picture, take a picture, draw me').split(',').map(k => k.trim());
                
                const text = $(this).val().toLowerCase();
                const hasTriggerKeyword = autoTriggerKeywords.some(keyword => 
                    text.includes(keyword.toLowerCase())
                );
                
                if (hasTriggerKeyword) {
                    // Show a subtle hint
                    if (!$('#image-trigger-hint').length) {
                        $('.chat-input-container').append(`
                            <div id="image-trigger-hint" style="color: #ffc107; font-size: 12px; margin-top: 5px;">
                                💡 Tip: Click "🎨" in the top right to configure image generation
                            </div>
                        `);
                    }
                } else {
                    $('#image-trigger-hint').remove();
                }
            });
        }
        
        function loadImageSettings() {
            console.log('Loading image settings...');
            
            // Load settings from localStorage or use defaults
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            console.log('Loaded settings from localStorage:', settings);
            
            // Load provider selection
            const provider = settings.provider || 'swarmui';
            console.log('Selected provider:', provider);
            $('#image-provider').val(provider);
            
            $('#custom-prompt-template').val(settings.customPromptTemplate || 'Summarize the story so far, and create a comma separated list to create an image generation prompt');
            $('#use-full-history').prop('checked', settings.useFullHistory !== false);
            $('#auto-trigger-keywords').val(settings.autoTriggerKeywords || 'send me a picture, take a picture, draw me');
            $('#allow-prompt-editing').prop('checked', settings.allowPromptEditing !== false);
            
            // Load provider-specific defaults
            if (provider === 'nanogpt') {
                $('#default-steps').val(settings.defaultSteps || 10);
                $('#default-cfg-scale').val(settings.defaultScale || 7.5);
                $('#default-width').val(settings.defaultWidth || 1024);
                $('#default-height').val(settings.defaultHeight || 1024);
            } else {
                $('#default-steps').val(settings.defaultSteps || 20);
                $('#default-cfg-scale').val(settings.defaultCfgScale || 7.0);
                $('#default-width').val(settings.defaultWidth || 512);
                $('#default-height').val(settings.defaultHeight || 512);
            }
            
            $('#negative-prompt').val(settings.negativePrompt || '');
            
            // Load slash command templates
            $('#slash-self-template').val(settings.slashSelfTemplate || 'Create a selfie prompt based on the character description and current chat context. Focus on the character\'s appearance, expression, and current situation.');
            $('#slash-generate-template').val(settings.slashGenerateTemplate || 'Generate an image based on the following prompt: {prompt}');
            $('#slash-look-template').val(settings.slashLookTemplate || 'Create a prompt for an image showing the current surroundings and environment based on the chat context. Focus on the setting, atmosphere, and what the character would see around them.');
            $('#slash-custom1-template').val(settings.slashCustom1Template || 'Custom prompt template 1: {prompt}');
            $('#slash-custom2-template').val(settings.slashCustom2Template || 'Custom prompt template 2: {prompt}');
            $('#slash-custom3-template').val(settings.slashCustom3Template || 'Custom prompt template 3: {prompt}');
            
            // Load models for the selected provider
            console.log('About to load models for provider:', provider);
            loadModelsForProvider(provider);
            
            // Add provider change handler
            $('#image-provider').off('change').on('change', function() {
                const newProvider = $(this).val();
                console.log('Provider changed to:', newProvider);
                loadModelsForProvider(newProvider);
                
                // Update provider-specific defaults
                if (newProvider === 'nanogpt') {
                    $('#default-steps').val(10);
                    $('#default-cfg-scale').val(7.5);
                    $('#default-width').val(1024);
                    $('#default-height').val(1024);
                } else {
                    $('#default-steps').val(20);
                    $('#default-cfg-scale').val(7.0);
                    $('#default-width').val(512);
                    $('#default-height').val(512);
                }
            });
        }
        
        function loadModelsForProvider(provider) {
            console.log('loadModelsForProvider called with provider:', provider);
            if (provider === 'swarmui') {
                console.log('Loading SwarmUI models for settings...');
                loadSwarmUIModelsForSettings();
            } else if (provider === 'nanogpt') {
                console.log('Loading Nano-GPT models for settings...');
                loadNanoGPTModelsForSettings();
            } else {
                console.log('Unknown provider:', provider);
            }
        }
        
        function loadNanoGPTModelsForSettings() {
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_get_available_models_nanogpt',
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success && response.data.models) {
                        populateSettingsModelDropdown(response.data.models);
                    } else {
                        console.error('Failed to load Nano-GPT models:', response);
                        $('#default-model').html('<option value="">Failed to load models</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load Nano-GPT models:', error);
                    console.error('Response:', xhr.responseText);
                    $('#default-model').html('<option value="">Failed to load models</option>');
                }
            });
        }
        
        function saveImageSettings() {
            const settings = {
                provider: $('#image-provider').val(),
                customPromptTemplate: $('#custom-prompt-template').val(),
                useFullHistory: $('#use-full-history').is(':checked'),
                autoTriggerKeywords: $('#auto-trigger-keywords').val(),
                allowPromptEditing: $('#allow-prompt-editing').is(':checked'),
                defaultSteps: parseInt($('#default-steps').val()),
                defaultCfgScale: parseFloat($('#default-cfg-scale').val()),
                defaultWidth: parseInt($('#default-width').val()),
                defaultHeight: parseInt($('#default-height').val()),
                defaultModel: $('#default-model').val(),
                negativePrompt: $('#negative-prompt').val(),
                slashSelfTemplate: $('#slash-self-template').val(),
                slashGenerateTemplate: $('#slash-generate-template').val(),
                slashLookTemplate: $('#slash-look-template').val(),
                slashCustom1Template: $('#slash-custom1-template').val(),
                slashCustom2Template: $('#slash-custom2-template').val(),
                slashCustom3Template: $('#slash-custom3-template').val()
            };
            
            // Update slash commands
            settings.slashCommands = {
                '/self': {
                    type: 'selfie',
                    description: 'Generate a selfie based on character description and current situation',
                    template: settings.slashSelfTemplate
                },
                '/generate': {
                    type: 'freestyle',
                    description: 'Free-form image generation with custom prompt',
                    template: settings.slashGenerateTemplate
                },
                '/look': {
                    type: 'surroundings',
                    description: 'Generate an image of the current surroundings',
                    template: settings.slashLookTemplate
                },
                '/custom1': {
                    type: 'custom',
                    description: 'Custom command 1',
                    template: settings.slashCustom1Template
                },
                '/custom2': {
                    type: 'custom',
                    description: 'Custom command 2',
                    template: settings.slashCustom2Template
                },
                '/custom3': {
                    type: 'custom',
                    description: 'Custom command 3',
                    template: settings.slashCustom3Template
                }
            };
            
            localStorage.setItem('pmv_image_settings', JSON.stringify(settings));
            
            // Update the image generation panel with new settings
            updateImagePanelWithSettings(settings);
            
            closeImageSettings();
            
            // Show success message
            alert('Image generation settings saved!');
        }
        
        function loadSwarmUIModelsForSettings() {
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
                            timeout: 15000, // 15 second timeout
                            data: {
                                action: 'pmv_get_available_models',
                                nonce: pmv_ajax_object.nonce
                            },
                            success: function(response) {
                                if (response.success && response.data) {
                                    populateSettingsModelDropdown(response.data);
                                } else {
                                    console.error('Failed to load SwarmUI models:', response);
                                    $('#default-model').html('<option value="">Failed to load models</option>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Failed to load SwarmUI models:', error);
                                console.error('Response:', xhr.responseText);
                                console.error('Status:', status);
                                console.error('XHR Status:', xhr.status);
                                
                                let errorMessage = 'Failed to load models';
                                if (xhr.status === 504) {
                                    errorMessage = 'Request timed out. Please try again.';
                                } else if (xhr.status === 0) {
                                    errorMessage = 'Network error. Please check your connection.';
                                } else if (xhr.status >= 500) {
                                    errorMessage = 'Server error. Please try again later.';
                                }
                                
                                $('#default-model').html(`<option value="">${errorMessage}</option>`);
                            }
                        });
                    } else {
                        console.error('Failed to get SwarmUI session:', sessionResponse);
                        $('#default-model').html('<option value="">Failed to get session</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to get SwarmUI session:', error);
                    console.error('Response:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('XHR Status:', xhr.status);
                    
                    let errorMessage = 'Failed to get session';
                    if (xhr.status === 504) {
                        errorMessage = 'Session request timed out. Please try again.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your connection.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    $('#default-model').html(`<option value="">${errorMessage}</option>`);
                }
            });
        }
        
        function populateSettingsModelDropdown(modelsData) {
            const $select = $('#default-model');
            $select.empty();
            
            let models = [];
            
            // Handle SwarmUI API format - models are in the 'models' array from the AJAX response
            if (modelsData.models && Array.isArray(modelsData.models)) {
                modelsData.models.forEach(file => {
                    const modelName = file.name || file.title || 'Unknown Model';
                    const displayName = file.title || file.name || 'Unknown Model';
                    
                    $select.append($('<option>').val(modelName).text(displayName));
                    models.push(modelName);
                });
            } else if (modelsData.files && Array.isArray(modelsData.files)) {
                // Fallback to files array if models array is not available
                modelsData.files.forEach(file => {
                    const modelName = file.name || file.title || 'Unknown Model';
                    const displayName = file.title || file.name || 'Unknown Model';
                    
                    $select.append($('<option>').val(modelName).text(displayName));
                    models.push(modelName);
                });
            } else if (modelsData.models && typeof modelsData.models === 'object') {
                // Handle legacy format (organized by type)
                Object.keys(modelsData.models).forEach(category => {
                    const categoryModels = modelsData.models[category];
                    if (Array.isArray(categoryModels)) {
                        const $optgroup = $('<optgroup>').attr('label', category);
                        categoryModels.forEach(model => {
                            $optgroup.append($('<option>').val(model).text(model));
                        });
                        $select.append($optgroup);
                        models = models.concat(categoryModels);
                    }
                });
            } else if (Array.isArray(modelsData)) {
                models = modelsData;
                models.forEach(model => {
                    $select.append($('<option>').val(model).text(model));
                });
            }
            
            // Set saved default model
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            if (settings.defaultModel && models.includes(settings.defaultModel)) {
                $select.val(settings.defaultModel);
            } else if (models.length > 0) {
                $select.val(models[0]);
            }
        }
        
        function updateImagePanelWithSettings(settings) {
            $('#image-steps').val(settings.defaultSteps || 20);
            $('#image-cfg-scale').val(settings.defaultCfgScale || 7.0);
            $('#image-width').val(settings.defaultWidth || 512);
            $('#image-height').val(settings.defaultHeight || 512);
            
            // Update model if available
            if (settings.defaultModel) {
                $('#swarmui-model').val(settings.defaultModel);
            }
        }
        
        function handleSlashCommand(message) {
            const command = message.split(' ')[0].toLowerCase();
            const args = message.substring(command.length).trim();
            
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            const commands = settings.slashCommands || getDefaultSlashCommands();
            
            if (commands[command]) {
                executeSlashCommand(command, args, commands[command]);
            } else {
                // Show available commands
                showSlashCommandHelp();
            }
        }
        
        function getDefaultSlashCommands() {
            return {
                '/self': {
                    type: 'selfie',
                    description: 'Generate a selfie based on character description and current situation',
                    template: 'Create a selfie prompt based on the character description and current chat context. Focus on the character\'s appearance, expression, and current situation.'
                },
                '/generate': {
                    type: 'freestyle',
                    description: 'Direct image generation with custom prompt (no OpenAI processing)',
                    template: 'Generate an image based on the following prompt: {prompt}'
                },
                '/look': {
                    type: 'surroundings',
                    description: 'Generate an image of the current surroundings',
                    template: 'Create a prompt for an image showing the current surroundings and environment based on the chat context. Focus on the setting, atmosphere, and what the character would see around them.'
                },
                '/custom1': {
                    type: 'custom',
                    description: 'Custom command 1',
                    template: 'Custom prompt template 1: {prompt}'
                },
                '/custom2': {
                    type: 'custom',
                    description: 'Custom command 2',
                    template: 'Custom prompt template 2: {prompt}'
                },
                '/custom3': {
                    type: 'custom',
                    description: 'Custom command 3',
                    template: 'Custom prompt template 3: {prompt}'
                }
            };
        }
        
        function executeSlashCommand(command, args, commandConfig) {
            let prompt = '';
            let customPrompt = '';
            
            switch (commandConfig.type) {
                case 'selfie':
                    customPrompt = commandConfig.template;
                    break;
                case 'freestyle':
                    if (!args) {
                        alert('Please provide a prompt for /generate command. Example: /generate a beautiful sunset');
                        return;
                    }
                    prompt = args; // Use the prompt directly for /generate
                    customPrompt = commandConfig.template.replace('{prompt}', args);
                    break;
                case 'surroundings':
                    customPrompt = commandConfig.template;
                    break;
                case 'custom':
                    if (args) {
                        prompt = args;
                        customPrompt = commandConfig.template.replace('{prompt}', args);
                    } else {
                        customPrompt = commandConfig.template;
                    }
                    break;
            }
            
            // Add command message to chat
            $('#chat-history').append(`
                <div class="chat-message user">
                    <span class="speaker-name">You:</span>
                    <span class="chat-message-content-wrapper">${escapeHtml(command + (args ? ' ' + args : ''))}</span>
                </div>
            `);
            
            // Clear input
            $('#chat-input').val('');
            
            // Generate and create image
            generateImageFromSlashCommand(prompt, customPrompt);
        }
        
        function generateImageFromSlashCommand(prompt, customPrompt) {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            
            // For /generate command, use the prompt directly without OpenAI processing
            if (prompt) {
                // Direct image generation with the provided prompt
                createImageFromPrompt(prompt);
                return;
            }
            
            // For other commands (like /selfie, /look), use chat history to generate prompt
            let chatHistory;
            if (settings.useFullHistory !== false) {
                chatHistory = collectConversationHistory().map(msg => 
                    `${msg.role === 'user' ? 'You' : chatState.characterData.name}: ${msg.content}`
                ).join('\n');
            } else {
                const messages = collectConversationHistory();
                if (messages.length > 0) {
                    const lastMsg = messages[messages.length - 1];
                    chatHistory = `${lastMsg.role === 'user' ? 'You' : chatState.characterData.name}: ${lastMsg.content}`;
                } else {
                    chatHistory = '';
                }
            }
            
            // Add character description for selfie commands
            if (chatState.characterData.description) {
                chatHistory += `\n\nCharacter Description: ${chatState.characterData.description}`;
            }
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_generate_image_prompt',
                    chat_history: chatHistory,
                    custom_prompt: customPrompt,
                    custom_template: settings.customPromptTemplate || 'Create a detailed Stable Diffusion image prompt',
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Auto-create image if prompt editing is disabled
                        if (!settings.allowPromptEditing) {
                            createImageFromPrompt(response.data.prompt);
                        } else {
                            // Show prompt for editing
                            showPromptForEditing(response.data.prompt);
                        }
                    } else {
                        alert('Error generating prompt: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error generating prompt: Network error');
                }
            });
        }
        
        function createImageFromPrompt(prompt) {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            
            // Get provider-specific parameters
            const provider = settings.provider || 'swarmui';
            let requestData = {
                action: 'pmv_generate_image',
                prompt: prompt,
                images_count: 1,
                nonce: pmv_ajax_object.nonce
            };
            
            // Add provider-specific parameters
            if (provider === 'nanogpt') {
                requestData.model = settings.defaultModel || 'recraft-v3';
                requestData.steps = settings.defaultSteps || 10;
                requestData.cfg_scale = settings.defaultScale || 7.5;
                requestData.width = settings.defaultWidth || 1024;
                requestData.height = settings.defaultHeight || 1024;
                requestData.negative_prompt = settings.negativePrompt || '';
            } else {
                // SwarmUI defaults
                requestData.model = settings.defaultModel || 'OfficialStableDiffusion/sd_xl_base_1.0';
                requestData.steps = settings.defaultSteps || 20;
                requestData.cfg_scale = settings.defaultCfgScale || 7.0;
                requestData.width = settings.defaultWidth || 512;
                requestData.height = settings.defaultHeight || 512;
                requestData.negative_prompt = settings.negativePrompt || '';
            }
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        displayImageResults(response.data);
                        
                        // Add image to chat
                        if (response.data.images && response.data.images.length > 0) {
                            $('#chat-history').append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">Image Generated (${provider.toUpperCase()}):</span>
                                    <div class="chat-message-content-wrapper">
                                        <img src="${response.data.images[0]}" style="max-width: 100%; border-radius: 4px;" loading="lazy" />
                                    </div>
                                </div>
                            `);
                            pushContentUp();
                        }
                    } else {
                        alert('Error creating image: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error creating image: Network error');
                }
            });
        }
        
        function showPromptForEditing(prompt) {
            // Create a simple prompt editing interface
            const editHtml = `
                <div class="chat-message bot">
                    <span class="speaker-name">Generated Prompt:</span>
                    <div class="chat-message-content-wrapper">
                        <textarea id="edit-prompt" style="width: 100%; min-height: 80px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px; border-radius: 4px; font-family: inherit;">${prompt}</textarea>
                        <div style="margin-top: 10px;">
                            <button id="send-prompt" class="button-primary" style="margin-right: 10px;">Create Image</button>
                            <button id="cancel-prompt" class="button-secondary">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#chat-history').append(editHtml);
            pushContentUp();
            
            $('#send-prompt').on('click', function() {
                const editedPrompt = $('#edit-prompt').val();
                createImageFromPrompt(editedPrompt);
                $('#edit-prompt').closest('.chat-message').remove();
            });
            
            $('#cancel-prompt').on('click', function() {
                $('#edit-prompt').closest('.chat-message').remove();
            });
        }
        
        function showSlashCommandHelp() {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            const commands = settings.slashCommands || getDefaultSlashCommands();
            
            let helpText = 'Available slash commands:\n\n';
            Object.keys(commands).forEach(cmd => {
                helpText += `${cmd}: ${commands[cmd].description}\n`;
            });
            
            alert(helpText);
        }
        
        function exportImageSettings() {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            const exportData = {
                version: '1.0',
                exportDate: new Date().toISOString(),
                settings: settings
            };
            
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'pmv-image-settings.json';
            link.click();
            
            alert('Settings exported successfully!');
        }
        
        function importImageSettings(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const importData = JSON.parse(e.target.result);
                    
                    if (importData.settings) {
                        localStorage.setItem('pmv_image_settings', JSON.stringify(importData.settings));
                        loadImageSettings();
                        alert('Settings imported successfully!');
                    } else {
                        alert('Invalid settings file format.');
                    }
                } catch (error) {
                    alert('Error importing settings: ' + error.message);
                }
            };
            
            reader.readAsText(file);
            event.target.value = ''; // Reset file input
        }

        // Export functions globally (PRESERVED)
        window.openCharacterModal = openCharacterModal;
        window.startChat = startFullScreenChat;

        function createImageWithWebSocket(prompt, model, steps, cfgScale, width, height, negativePrompt = '') {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            const provider = settings.provider || 'swarmui';
            
            if (provider !== 'swarmui') {
                console.log('WebSocket generation only supported for SwarmUI');
                createImage(); // Fallback to regular generation
                return;
            }
            
            $('#create-image-btn').prop('disabled', true).text('Connecting...');
            
            // Show WebSocket progress indicator
            $('#image-results').html(`
                <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                    <div class="spinner" style="border: 3px solid #333; border-top: 3px solid #007bff; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 10px;"></div>
                    <p style="color: #e0e0e0; margin: 0;">Connecting to SwarmUI WebSocket...</p>
                    <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">Real-time progress updates enabled</p>
                </div>
            `);
            
            // Get WebSocket connection info
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_generate_image_websocket',
                    prompt: prompt,
                    model: model,
                    steps: steps,
                    cfg_scale: cfgScale,
                    width: width,
                    height: height,
                    negative_prompt: negativePrompt,
                    images_count: 1,
                    provider: provider,
                    nonce: pmv_ajax_object.nonce,
                    character_id: window.PMV_ConversationManager?.characterId || 'unknown'
                },
                success: function(response) {
                    if (response.success && response.data.websocket_url) {
                        connectWebSocket(response.data.websocket_url, response.data.request_data);
                    } else {
                        console.error('WebSocket connection failed:', response);
                        $('#image-results').html(`
                            <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                                <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ WebSocket Connection Failed</h4>
                                <p style="margin: 0; color: #f8d7da;">${response.data?.message || 'Failed to establish WebSocket connection'}</p>
                                <button onclick="createImage()" style="background: #007bff; color: #ffffff; border: none; padding: 8px 16px; border-radius: 4px; margin-top: 10px;">Try Regular Generation</button>
                            </div>
                        `);
                        $('#create-image-btn').prop('disabled', false).text('Create Image');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('WebSocket setup error:', error);
                    $('#image-results').html(`
                        <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ WebSocket Setup Failed</h4>
                            <p style="margin: 0; color: #f8d7da;">Network error: ${error}</p>
                            <button onclick="createImage()" style="background: #007bff; color: #ffffff; border: none; padding: 8px 16px; border-radius: 4px; margin-top: 10px;">Try Regular Generation</button>
                        </div>
                    `);
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                }
            });
        }
        
        function connectWebSocket(websocketUrl, requestData) {
            // Convert HTTP URL to WebSocket URL
            const wsUrl = websocketUrl.replace('http://', 'ws://').replace('https://', 'wss://');
            
            console.log('Connecting to WebSocket:', wsUrl);
            
            const socket = new WebSocket(wsUrl);
            let progressHtml = '';
            
            socket.onopen = function(event) {
                console.log('WebSocket connected');
                $('#image-results').html(`
                    <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                        <div class="spinner" style="border: 3px solid #333; border-top: 3px solid #007bff; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 10px;"></div>
                        <p style="color: #e0e0e0; margin: 0;">Connected to SwarmUI WebSocket</p>
                        <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">Sending generation request...</p>
                    </div>
                `);
                
                // Send the generation request
                socket.send(JSON.stringify(requestData));
            };
            
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                console.log('WebSocket message:', data);
                
                if (data.status) {
                    // High-level status (queue position etc.) – no percentage yet
                    const waiting = data.status.waiting_gens ?? 0;
                    const running = data.status.live_gens ?? 0;
                    progressHtml = `
                        <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                            <p style="color: #e0e0e0; margin: 0;">${running > 0 ? 'Generation started…' : 'Queued…'}</p>
                            <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">${waiting} waiting • ${running} running</p>
                        </div>
                    `;
                } else if (data.gen_progress) {
                    // Progress update
                    const overallPercent = Math.round((data.gen_progress.overall_percent || 0) * 100);
                    const currentPercent = Math.round((data.gen_progress.current_percent || 0) * 100);
                    
                    progressHtml = `
                        <div style="text-align: center; padding: 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 4px;">
                            <div style="width: 100%; background: #333; border-radius: 10px; height: 20px; margin-bottom: 10px;">
                                <div style="width: ${overallPercent}%; background: #007bff; height: 100%; border-radius: 10px; transition: width 0.3s;"></div>
                            </div>
                            <p style="color: #e0e0e0; margin: 0;">Generating image: ${overallPercent}% complete</p>
                            <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">Current step: ${currentPercent}%</p>
                            ${data.gen_progress.preview ? `<img src="${data.gen_progress.preview}" style="max-width: 200px; border-radius: 4px; margin-top: 10px;" loading="lazy" />` : ''}
                        </div>
                    `;
                } else if (data.image) {
                    // Image result
                    const imageUrl = data.image.image;
                    progressHtml = `
                        <div style="text-align: center; padding: 20px; background: #1b2d1b; border: 1px solid #155724; border-radius: 4px;">
                            <p style="color: #d4edda; margin: 0 0 15px 0;">✅ Image generated successfully!</p>
                            <img src="${imageUrl}" style="max-width: 100%; border: 1px solid #444; border-radius: 4px; background: #1a1a1a;" loading="lazy" />
                            <div style="margin-top: 10px;">
                                <button onclick="downloadImage('${imageUrl}', 'generated-image.png')" style="background: #007bff; color: #ffffff; border: 1px solid #0056b3; padding: 5px 10px; border-radius: 3px; margin-right: 5px;">Download</button>
                                <button onclick="addImageToChat('${imageUrl}')" style="background: #28a745; color: #ffffff; border: 1px solid #1e7e34; padding: 5px 10px; border-radius: 3px;">Add to Chat</button>
                            </div>
                        </div>
                    `;
                    
                    // Close WebSocket after getting the image
                    socket.close();
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                } else if (data.discard_indices) {
                    // Images were discarded
                    console.log('Images discarded:', data.discard_indices);
                } else if (data.error) {
                    // Error occurred
                    progressHtml = `
                        <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ Generation Failed</h4>
                            <p style="margin: 0; color: #f8d7da;">${data.error}</p>
                        </div>
                    `;
                    socket.close();
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                }
                
                if (progressHtml) {
                    $('#image-results').html(progressHtml);
                }
            };
            
            socket.onerror = function(error) {
                console.error('WebSocket error:', error);
                $('#image-results').html(`
                    <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                        <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ WebSocket Error</h4>
                        <p style="margin: 0; color: #f8d7da;">Connection failed. Please try regular generation.</p>
                        <button onclick="createImage()" style="background: #007bff; color: #ffffff; border: none; padding: 8px 16px; border-radius: 4px; margin-top: 10px;">Try Regular Generation</button>
                    </div>
                `);
                $('#create-image-btn').prop('disabled', false).text('Create Image');
            };
            
            socket.onclose = function(event) {
                console.log('WebSocket closed:', event.code, event.reason);
                if (event.code !== 1000) { // Not a normal closure
                    $('#image-results').html(`
                        <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ WebSocket Closed</h4>
                            <p style="margin: 0; color: #f8d7da;">Connection was closed unexpectedly.</p>
                            <button onclick="createImage()" style="background: #007bff; color: #ffffff; border: none; padding: 8px 16px; border-radius: 4px; margin-top: 10px;">Try Regular Generation</button>
                        </div>
                    `);
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                }
            };
        }

        // Force apply mobile styles after chat creation
        function forceApplyMobileStyles() {
            const isMobile = window.innerWidth <= 768;
            if (!isMobile) {
                console.log('Not mobile, skipping mobile styles');
                return;
            }
            
            console.log('Applying mobile styles...');
            
            // Force apply styles to main chat container
            const chatContainer = $('.fullscreen-chat');
            if (chatContainer.length) {
                chatContainer.css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100vw',
                    'height': '100dvh',
                    'background': '#1a1a1a',
                    'z-index': '9998',
                    'display': 'flex',
                    'flex-direction': 'column',
                    'overflow': 'hidden'
                });
            }
            
            // Force apply styles to header
            const header = $('.fullscreen-chat-header');
            if (header.length) {
                header.css({
                    'padding': '10px',
                    'flex-shrink': '0',
                    'min-height': '60px',
                    'max-height': '60px'
                });
            }
            
            // Force apply styles to content area
            const content = $('.fullscreen-chat-content');
            if (content.length) {
                content.css({
                    'flex': '1',
                    'overflow-y': 'auto',
                    'padding': '10px',
                    'background': '#1a1a1a',
                    'min-height': '0'
                });
            }
            
            // Force apply styles to history
            const history = $('#chat-history');
            if (history.length) {
                history.css({
                    'padding': '10px',
                    'background': '#1a1a1a',
                    'color': '#e0e0e0'
                });
            }
            
            // Force apply styles to input container
            const inputContainer = $('.fullscreen-chat-input-container');
            if (inputContainer.length) {
                inputContainer.css({
                    'padding': '10px',
                    'background': '#2d2d2d',
                    'border-top': '1px solid #444',
                    'flex-shrink': '0',
                    'position': 'relative',
                    'z-index': '10001'
                });
            }
            
            // Force apply styles to input row
            const inputRow = $('.input-flex-row');
            if (inputRow.length) {
                inputRow.css({
                    'display': 'flex',
                    'align-items': 'flex-end',
                    'gap': '8px'
                });
            }
            
            // Force apply styles to input
            const input = $('#chat-input');
            if (input.length) {
                input.css({
                    'flex': '1',
                    'padding': '10px',
                    'border': '1px solid #444',
                    'border-radius': '4px',
                    'background': '#1a1a1a',
                    'color': '#e0e0e0',
                    'font-size': '16px',
                    'resize': 'none',
                    'min-height': '40px',
                    'max-height': '120px'
                });
            }
            
            // Force apply styles to send button
            const sendButton = $('.fullscreen-chat-send-btn');
            if (sendButton.length) {
                sendButton.css({
                    'background': '#007bff',
                    'color': '#ffffff',
                    'border': 'none',
                    'padding': '10px 15px',
                    'border-radius': '4px',
                    'cursor': 'pointer',
                    'font-size': '14px',
                    'flex-shrink': '0',
                    'min-height': '40px'
                });
            }
            
            // Force apply styles to chat messages
            const messages = $('.chat-message');
            messages.each(function() {
                $(this).css({
                    'margin-bottom': '12px',
                    'padding': '12px',
                    'border-radius': '8px',
                    'max-width': '85%',
                    'word-wrap': 'break-word',
                    'font-size': '14px',
                    'line-height': '1.4'
                });
                
                if ($(this).hasClass('user')) {
                    $(this).css({
                        'background': '#2d4a7d',
                        'color': '#e0e0e0',
                        'margin-left': 'auto',
                        'border': '1px solid #4a6fa5'
                    });
                } else if ($(this).hasClass('bot')) {
                    $(this).css({
                        'background': '#333',
                        'color': '#e0e0e0',
                        'margin-right': 'auto',
                        'border': '1px solid #555'
                    });
                }
            });
            
            // Force apply styles to speaker names
            const speakerNames = $('.speaker-name');
            speakerNames.css({
                'color': '#e0e0e0',
                'font-weight': 'bold',
                'margin-bottom': '5px',
                'display': 'block'
            });
            
            // Force apply styles to content wrappers
            const contentWrappers = $('.chat-message-content-wrapper');
            contentWrappers.css({
                'color': 'inherit',
                'word-wrap': 'break-word'
            });
            
            // Force apply styles to chat modal name
            const modalName = $('.chat-modal-name');
            modalName.css({
                'font-size': '14px',
                'color': '#e0e0e0',
                'font-weight': 'bold'
            });
            
            // Hide usage displays on mobile
            $('.token-usage-display, .image-usage-display').hide();
            
            // Apply responsive modal fixes
            applyResponsiveModalFixes();
            
            console.log('Mobile styles applied');
        }

        // NEW: Function to handle responsive modal positioning and text visibility
        function applyResponsiveModalFixes() {
            const isMobile = window.innerWidth <= 768;
            const isSmallMobile = window.innerWidth <= 480;
            
            // Fix conversation sidebar positioning
            const sidebar = $('.conversation-sidebar');
            if (sidebar.length) {
                if (isMobile) {
                    sidebar.css({
                        'position': 'fixed',
                        'top': '0',
                        'left': '0',
                        'width': '100%',
                        'height': '100%',
                        'z-index': '10001',
                        'background': 'rgba(0, 0, 0, 0.9)',
                        'transform': sidebar.hasClass('open') ? 'translateX(0)' : 'translateX(-100%)',
                        'transition': 'transform 0.3s ease'
                    });
                } else {
                    sidebar.css({
                        'position': 'fixed',
                        'top': '0',
                        'left': '0',
                        'width': '350px',
                        'height': '100%',
                        'z-index': '10001',
                        'background': '#1a1a1a',
                        'transform': sidebar.hasClass('open') ? 'translateX(0)' : 'translateX(-100%)',
                        'transition': 'transform 0.3s ease'
                    });
                }
            }
            
            // Fix conversation items text visibility
            const conversationItems = $('.conversation-item');
            conversationItems.each(function() {
                const item = $(this);
                if (isMobile) {
                    item.css({
                        'background': 'rgba(255, 255, 255, 0.95)',
                        'color': '#333',
                        'border': '1px solid #ddd',
                        'margin-bottom': isSmallMobile ? '10px' : '8px',
                        'padding': isSmallMobile ? '15px' : '12px',
                        'border-radius': '6px',
                        'box-shadow': '0 2px 4px rgba(0, 0, 0, 0.1)'
                    });
                    
                    // Ensure text is visible
                    item.find('.conversation-title').css({
                        'color': '#333',
                        'font-weight': 'bold',
                        'font-size': isSmallMobile ? '16px' : '14px',
                        'line-height': '1.3'
                    });
                    
                    item.find('.conversation-date').css({
                        'color': '#666',
                        'font-size': isSmallMobile ? '13px' : '12px',
                        'margin-top': '5px'
                    });
                }
            });
            
            // Fix modal content positioning
            const modalContent = $('.png-modal-content.chat-mode');
            if (modalContent.length) {
                if (isSmallMobile) {
                    modalContent.css({
                        'width': '98%',
                        'height': '95vh',
                        'margin': '2.5vh auto',
                        'border-radius': '8px',
                        'box-shadow': '0 10px 30px rgba(0, 0, 0, 0.5)'
                    });
                } else if (isMobile) {
                    modalContent.css({
                        'width': '95%',
                        'height': '90vh',
                        'margin': '2.5vh auto',
                        'border-radius': '8px',
                        'box-shadow': '0 10px 30px rgba(0, 0, 0, 0.5)'
                    });
                }
            }
            
            // Ensure proper z-index layering
            $('.fullscreen-chat').css('z-index', '9998');
            $('.png-modal').css('z-index', '9999');
            $('.conversation-sidebar').css('z-index', '10001');
            
            // Fix chat messages visibility on small screens
            if (isSmallMobile) {
                const chatMessages = $('.chat-message');
                chatMessages.each(function() {
                    $(this).css({
                        'max-width': '90%',
                        'margin-bottom': '15px',
                        'padding': '12px',
                        'font-size': '14px',
                        'line-height': '1.4'
                    });
                });
            }
        }

        // Add window resize handler for responsive fixes
        $(window).on('resize', function() {
            if (chatState.chatModeActive) {
                setTimeout(function() {
                    applyResponsiveModalFixes();
                    if (window.innerWidth <= 768) {
                        forceApplyMobileStyles();
                    } else {
                        ensureDesktopStyles();
                    }
                }, 100);
            }
        });

        // Ensure desktop styles are properly applied
        function ensureDesktopStyles() {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) return;
            
            console.log('Ensuring desktop styles...');
            
            // Reset any mobile-specific overrides on desktop
            const chatContainer = $('.fullscreen-chat');
            if (chatContainer.length) {
                chatContainer.css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100vw',
                    'height': '100vh',
                    'background': '#1a1a1a',
                    'z-index': '9998',
                    'display': 'flex',
                    'flex-direction': 'column'
                });
            }
            
            // Ensure header has proper desktop styling
            const header = $('.fullscreen-chat-header');
            if (header.length) {
                header.css({
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'space-between',
                    'background': '#2d2d2d',
                    'padding': '15px',
                    'border-bottom': '1px solid #444'
                });
            }
            
            // Ensure content area has proper desktop styling
            const content = $('.fullscreen-chat-content');
            if (content.length) {
                content.css({
                    'flex': '1',
                    'overflow-y': 'auto',
                    'padding': '20px',
                    'background': '#1a1a1a'
                });
            }
            
            // Ensure history has proper desktop styling
            const history = $('#chat-history');
            if (history.length) {
                history.css({
                    'padding': '20px',
                    'background': '#1a1a1a'
                });
            }
            
            // Ensure input container has proper desktop styling
            const inputContainer = $('.fullscreen-chat-input-container');
            if (inputContainer.length) {
                inputContainer.css({
                    'background': '#2d2d2d',
                    'padding': '15px',
                    'border-top': '1px solid #444'
                });
            }
            
            // Ensure input has proper desktop styling
            const input = $('#chat-input');
            if (input.length) {
                input.css({
                    'width': '100%',
                    'padding': '12px',
                    'border': '1px solid #444',
                    'border-radius': '4px',
                    'background': '#1a1a1a',
                    'color': '#e0e0e0',
                    'font-size': '14px',
                    'resize': 'vertical',
                    'min-height': '60px'
                });
            }
            
            // Ensure send button has proper desktop styling
            const sendButton = $('.fullscreen-chat-send-btn');
            if (sendButton.length) {
                sendButton.css({
                    'background': '#007bff',
                    'color': '#ffffff',
                    'border': 'none',
                    'padding': '12px 20px',
                    'border-radius': '4px',
                    'cursor': 'pointer',
                    'margin-left': '10px'
                });
            }
            
            // Ensure chat messages have proper desktop styling with better contrast
            const messages = $('.chat-message');
            messages.each(function() {
                $(this).css({
                    'margin-bottom': '15px',
                    'padding': '12px',
                    'border-radius': '8px',
                    'max-width': '80%',
                    'word-wrap': 'break-word',
                    'font-size': '14px',
                    'line-height': '1.4'
                });
                
                if ($(this).hasClass('user')) {
                    $(this).css({
                        'background': '#2d4a7d',
                        'color': '#ffffff',
                        'margin-left': 'auto',
                        'text-align': 'right',
                        'border': '1px solid #4a6fa5'
                    });
                } else if ($(this).hasClass('bot')) {
                    $(this).css({
                        'background': '#444444',
                        'color': '#ffffff',
                        'margin-right': 'auto',
                        'border': '1px solid #666666'
                    });
                }
            });
            
            // Ensure speaker names are visible
            const speakerNames = $('.speaker-name');
            speakerNames.css({
                'color': '#ffffff',
                'font-weight': 'bold',
                'margin-bottom': '5px',
                'display': 'block'
            });
            
            // Ensure content wrappers are visible
            const contentWrappers = $('.chat-message-content-wrapper');
            contentWrappers.css({
                'color': 'inherit',
                'word-wrap': 'break-word'
            });
            
            // Ensure chat modal name is visible on desktop
            const modalName = $('.chat-modal-name');
            modalName.css({
                'font-size': '18px',
                'color': '#ffffff',
                'font-weight': 'bold'
            });
            
            // Show usage displays on desktop
            $('.token-usage-display, .image-usage-display').show();
            
            // Apply responsive modal fixes for desktop
            applyResponsiveModalFixes();
            
            console.log('Desktop styles ensured');
        }



        // Simple push-up effect for new messages
        function pushMessagesUp() {
            const $chatHistory = $('#chat-history');
            if ($chatHistory.length) {
                // Get all messages except the last one (the new one)
                const $existingMessages = $chatHistory.find('.chat-message, .typing-indicator').slice(0, -1);
                const $newMessage = $chatHistory.find('.chat-message, .typing-indicator').last();
                
                if ($existingMessages.length > 0 && $newMessage.length > 0) {
                    const newMessageHeight = $newMessage.outerHeight(true);
                    
                    // Push existing messages up
                    $existingMessages.css({
                        'transform': `translateY(-${newMessageHeight}px)`,
                        'transition': 'transform 0.4s ease-out'
                    });
                    
                    // After animation, reset and scroll to show new content
                    setTimeout(() => {
                        $existingMessages.css({
                            'transform': 'none',
                            'transition': 'none'
                        });
                        
                        // Scroll to show the new message
                        const chatHistoryElement = $chatHistory[0];
                        chatHistoryElement.scrollTop = chatHistoryElement.scrollHeight;
                    }, 400);
                } else {
                    // Fallback to normal scroll
                    const chatHistoryElement = $chatHistory[0];
                    chatHistoryElement.scrollTop = chatHistoryElement.scrollHeight;
                }
            }
        }
    });
    
})(jQuery); // Pass jQuery to the function
