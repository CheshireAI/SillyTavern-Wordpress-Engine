/**
 * PNG Metadata Viewer - Chat UI Module
 * UI-related functionality extracted from main chat.js
 * Version: 1.0
 */

(function($) {
    'use strict';
    
    // Ensure modal HTML exists for character details
    function ensureModalExists() {
        if ($('#png-modal').length === 0) {
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

        // Add CSS
        addFullScreenCSS();
    }

    // Add FULL SCREEN CSS
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
                background: #2d5363;
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
                background: #23404e;
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
                background: #2d5363;
                color: #fff;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
            }
            
            .close-sidebar-btn:hover {
                background: #23404e;
            }
            
            .conversation-list {
                padding: 15px;
            }
            
            /* Mobile responsive styles */
            @media (max-width: 768px) {
                .fullscreen-chat {
                    height: 100dvh !important;
                    max-height: 100dvh !important;
                    overflow: hidden !important;
                }
                
                .fullscreen-chat-header {
                    padding: 10px !important;
                    flex-shrink: 0 !important;
                    min-height: 60px !important;
                    max-height: 60px !important;
                }
                
                .fullscreen-chat-content {
                    flex: 1 !important;
                    overflow-y: auto !important;
                    padding: 10px !important;
                    background: #1a1a1a !important;
                    min-height: 0 !important;
                }
                
                .fullscreen-chat-input-container {
                    padding: 10px !important;
                    background: #2d2d2d !important;
                    border-top: 1px solid #444 !important;
                    flex-shrink: 0 !important;
                    position: relative !important;
                    z-index: 10001 !important;
                }
                
                .conversation-sidebar {
                    width: 280px !important;
                    height: 100dvh !important;
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
            escapedMetadata = '';
        }

        // Build sections
        let sections = [];

        if (description) {
            sections.push(`
                <div class="character-section">
                    <h3>Description</h3>
                    <div class="character-field">${window.PMV_ChatCore.escapeHtml(description)}</div>
                </div>
            `);
        }

        if (personality) {
            sections.push(`
                <div class="character-section">
                    <h3>Personality</h3>
                    <div class="character-field">${window.PMV_ChatCore.escapeHtml(personality)}</div>
                </div>
            `);
        }

        if (scenario) {
            sections.push(`
                <div class="character-section">
                    <h3>Scenario</h3>
                    <div class="character-field">${window.PMV_ChatCore.escapeHtml(scenario)}</div>
                </div>
            `);
        }

        if (firstMes) {
            sections.push(`
                <div class="character-section">
                    <h3>First Message</h3>
                    <div class="character-field">${window.PMV_ChatCore.escapeHtml(firstMes)}</div>
                </div>
            `);
        }

        if (tags.length > 0) {
            let tagHtml = tags.map(tag => `<span class="tag-item">${window.PMV_ChatCore.escapeHtml(tag)}</span>`).join('');
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
                        <h2>${window.PMV_ChatCore.escapeHtml(name)}</h2>
                        ${creator ? `<div class="character-creator">Created by: ${window.PMV_ChatCore.escapeHtml(creator)}</div>` : ''}
                    </div>
                    
                    <div class="character-image">
                        <img src="${fileUrl}" alt="${window.PMV_ChatCore.escapeHtml(name)}" loading="lazy">
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
            const characterData = window.PMV_ChatCore.parseCharacterData(metadataStr);
            const character = window.PMV_ChatCore.extractCharacterInfo(characterData);
            const modalHtml = buildCharacterModalHtml(character, fileUrl, metadataStr);

            ensureModalExists();
            $('#modal-content').html(modalHtml);
            $('#png-modal').show();

        } catch (error) {
            ensureModalExists();
            $('#modal-content').html(`
                <div class="character-modal-wrapper">
                    <div class="character-details">
                        <div class="character-header">
                            <h2>Error Loading Character</h2>
                        </div>
                        <div class="character-section">
                            <p>Error: ${window.PMV_ChatCore.escapeHtml(error.message)}</p>
                        </div>
                    </div>
                </div>
            `);
            $('#png-modal').show();
        }
    }

    // Create conversation sidebar
    function createConversationSidebar() {
        // Remove any existing sidebar first
        $('.conversation-sidebar').remove();
        
        const sidebarHtml = `
            <div class="conversation-sidebar ${window.PMV_ChatCore.chatState.sidebarOpen ? 'open' : ''}" style="background: #1a1a1a !important;">
                <div class="sidebar-header" style="background: #1a1a1a !important;">
                    <h3>Conversations</h3>
                    <div class="sidebar-actions">
                        <button id="new-conversation" class="new-chat-btn">🔄 New</button>
                        <button id="save-conversation" class="save-chat-btn">💾 Save</button>
                        <button id="export-conversation" class="export-chat-btn">📥 Export</button>
                    </div>
                    <button class="close-sidebar-btn">Close Menu</button>
                </div>
                <div class="conversation-list" style="background: #1a1a1a !important; color: #e0e0e0 !important;"></div>
            </div>
        `;

        // Append to body instead of .chat-main since we're in fullscreen mode
        $('body').append(sidebarHtml);
        
        // Immediately apply dark background styles to prevent white flash
        const $sidebar = $('.conversation-sidebar').last();
        const $list = $sidebar.find('.conversation-list');
        $sidebar.css('background', '#1a1a1a');
        $sidebar.find('.sidebar-header').css('background', '#1a1a1a');
        $list.css({
            'background': '#1a1a1a',
            'color': '#e0e0e0'
        });
        
        // Initialize the conversation manager
        if (window.PMV_ConversationManager) {
            // Reset initialization state to allow re-initialization
            window.PMV_ConversationManager.isInitialized = false;
            window.PMV_ConversationManager.init(window.PMV_ChatCore.chatState.characterData, window.PMV_ChatCore.chatState.characterId);
        }
        
        // Add click handler for close button
        setTimeout(() => {
            $('.close-sidebar-btn').off('click.direct').on('click.direct', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.forceCloseSidebar();
            });
        }, 200);
    }

    // Force close sidebar function
    window.forceCloseSidebar = function() {
        const sidebar = $('.conversation-sidebar');
        sidebar.removeClass('open');
        sidebar.css({
            'transform': 'translateX(-100%)',
            'transition': 'transform 0.3s ease'
        });
    };

    // Force open sidebar function
    window.forceOpenSidebar = function() {
        const sidebar = $('.conversation-sidebar');
        sidebar.addClass('open');
        sidebar.css({
            'transform': 'translateX(0)',
            'transition': 'transform 0.3s ease'
        });
    };

    // Expose functions to global scope
    window.PMV_ChatUI = {
        ensureModalExists,
        addFullScreenCSS,
        buildCharacterModalHtml,
        openCharacterModal,
        createConversationSidebar
    };

})(jQuery); 