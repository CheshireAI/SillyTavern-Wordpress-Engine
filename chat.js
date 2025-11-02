/* PNG Metadata Viewer - Full Screen Chat */
(function($) {
    'use strict';
    
    // Wait for document ready
    $(document).ready(function() {
        
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
                    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                    padding: 20px 25px;
                    border-bottom: 1px solid #404040;
                    position: relative;
                    z-index: 10000;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                }
                .header-left {
                    display: flex;
                    align-items: center;
                }
                .conversation-menu-btn {
                    background: linear-gradient(135deg, #23404e 0%, #2d5363 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-size: 18px;
                    width: 40px;
                    height: 40px;
                    margin-right: 15px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .conversation-menu-btn:hover {
                    background: linear-gradient(135deg, #2d5363 0%, #3a6b7a 100%);
                    transform: translateY(-2px) scale(1.05);
                    box-shadow: 0 4px 16px rgba(35, 64, 78, 0.4);
                }
                .header-actions {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-left: auto;
                }
                .image-settings-btn {
                    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-size: 18px;
                    width: 40px;
                    height: 40px;
                    margin-right: 15px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(0, 96, 100, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .image-settings-btn:hover {
                    background: linear-gradient(135deg, #00838f 0%, #0097a7 100%);
                    transform: translateY(-2px) scale(1.05);
                    box-shadow: 0 4px 16px rgba(0, 96, 100, 0.4);
                }
                .close-fullscreen-btn {
                    background: linear-gradient(135deg, #2d5363 0%, #23404e 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-size: 18px;
                    width: 40px;
                    height: 40px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .close-fullscreen-btn:hover {
                    background: linear-gradient(135deg, #23404e 0%, #1a2a35 100%);
                    transform: translateY(-2px) scale(1.05);
                    box-shadow: 0 4px 16px rgba(35, 64, 78, 0.4);
                }
                
                .chat-modal-name {
                    color: #e0e0e0;
                    font-weight: bold;
                    font-size: 18px;
                    text-align: center;
                    flex: 1;
                    margin: 0 20px;
                }
                
                .credit-balance-display {
                    display: flex;
                    align-items: center;
                    gap: 20px;
                    background: linear-gradient(135deg, #23404e 0%, #2d5363 100%);
                    padding: 12px 20px;
                    border-radius: 20px;
                    box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    position: relative;
                    margin-right: 20px;
                }
                
                .credit-balance-display::before {
                    content: '💰 Credits';
                    position: absolute;
                    top: -8px;
                    left: 20px;
                    background: #1a1a1a;
                    color: #e0e0e0;
                    font-size: 10px;
                    padding: 3px 10px;
                    border-radius: 10px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                }
                
                .credit-label {
                    color: #e0e0e0;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-right: 8px;
                }
                
                .credit-amount {
                    color: #ffffff;
                    font-size: 14px;
                    font-weight: bold;
                    background: rgba(255, 255, 255, 0.1);
                    padding: 6px 12px;
                    border-radius: 12px;
                    min-width: 50px;
                    text-align: center;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    transition: all 0.3s ease;
                    margin-right: 15px;
                }
                
                .credit-amount:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: scale(1.05);
                    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1);
                }
                
                .credit-amount.low-credits {
                    background: rgba(220, 53, 69, 0.2);
                    color: #ff6b6b;
                    animation: pulse 2s infinite;
                }
                
                .credit-amount.guest-credits {
                    background: rgba(255, 193, 7, 0.2);
                    color: #ffc107;
                    border-color: rgba(255, 193, 7, 0.4);
                }
                
                /* Ensure proper spacing between credit groups */
                .credit-balance-display > *:not(:last-child) {
                    margin-right: 0;
                }
                
                /* Credit group container for better organization */
                .credit-group {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .refresh-credits-btn {
                    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 50%;
                    font-size: 14px;
                    width: 28px;
                    height: 28px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0, 96, 100, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-left: 15px;
                }
                
                .refresh-credits-btn:hover {
                    background: linear-gradient(135deg, #00838f 0%, #0097a7 100%);
                    transform: rotate(180deg) scale(1.1);
                    box-shadow: 0 4px 8px rgba(0, 96, 100, 0.4);
                }
                
                /* Ensure the header actions are properly spaced */
                .header-actions {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-left: 20px;
                }
                
                /* Image settings button styling */
                .image-settings-btn {
                    background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    width: 36px;
                    height: 36px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .image-settings-btn:hover {
                    background: linear-gradient(135deg, #ffa500 0%, #ffb84d 100%);
                    transform: translateY(-2px) scale(1.05);
                }
                
                .image-gen-btn {
                    background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-size: 18px;
                    width: 44px;
                    height: 44px;
                    margin-right: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(255, 140, 0, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .image-gen-btn:hover {
                    background: linear-gradient(135deg, #ffa500 0%, #ffb84d 100%);
                    transform: translateY(-2px) scale(1.05);
                    box-shadow: 0 4px 16px rgba(255, 140, 0, 0.4);
                }
                
                .image-gen-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.85);
                    z-index: 10003;
                    display: none;
                    align-items: center;
                    justify-content: center;
                }
                .image-gen-modal.active {
                    display: flex;
                }
                .image-gen-modal-content {
                    background: #2d2d2d;
                    border-radius: 16px;
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    border: 1px solid #444;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
                }
                .image-gen-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #444;
                    background: #1a1a1a;
                    border-radius: 16px 16px 0 0;
                }
                .image-gen-modal-header h3 {
                    margin: 0;
                    color: #e0e0e0;
                    font-size: 20px;
                }
                .image-gen-modal-close {
                    background: #2d5363;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    width: 32px;
                    height: 32px;
                    cursor: pointer;
                    font-size: 18px;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .image-gen-modal-close:hover {
                    background: #23404e;
                    transform: scale(1.1);
                }
                .image-gen-modal-body {
                    padding: 20px;
                }
                .preset-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 12px;
                    margin-bottom: 20px;
                }
                .preset-card {
                    background: #1a1a1a;
                    border: 2px solid #444;
                    border-radius: 12px;
                    padding: 15px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-align: center;
                }
                .preset-card:hover {
                    border-color: #ffa500;
                    background: #252525;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(255, 140, 0, 0.2);
                }
                .preset-card.selected {
                    border-color: #ffa500;
                    background: #2a2520;
                    box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.3);
                }
                .preset-card-name {
                    font-weight: bold;
                    color: #e0e0e0;
                    margin-bottom: 5px;
                    font-size: 16px;
                }
                .preset-card-desc {
                    font-size: 12px;
                    color: #aaa;
                    line-height: 1.4;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
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
                    background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
                    position: relative;
                }
                
                .fullscreen-chat-content::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: radial-gradient(circle at 20% 80%, rgba(35, 64, 78, 0.03) 0%, transparent 50%),
                                radial-gradient(circle at 80% 20%, rgba(0, 96, 100, 0.03) 0%, transparent 50%);
                    pointer-events: none;
                }
                
                .fullscreen-chat-history {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px;
                    background: transparent;
                    position: relative;
                    z-index: 1;
                }
                
                .fullscreen-chat-input-container {
                    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
                    padding: 20px;
                    border-top: 1px solid #404040;
                    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                    position: relative;
                }
                
                .fullscreen-chat-input-container::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, #404040, transparent);
                }
                
                .fullscreen-chat-input {
                    width: 100%;
                    padding: 12px 16px;
                    border: 2px solid #333333;
                    border-radius: 25px;
                    background: #1a1a1a;
                    color: #ffffff;
                    font-size: 15px;
                    resize: vertical;
                    min-height: 50px;
                    transition: all 0.3s ease;
                    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                
                .fullscreen-chat-input:focus {
                    outline: none;
                    border-color: #23404e;
                    box-shadow: 0 0 0 3px rgba(35, 64, 78, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.1);
                    background: #1f1f1f;
                }
                
                .fullscreen-chat-send-btn {
                    background: linear-gradient(135deg, #23404e 0%, #2d5363 100%);
                    color: #ffffff;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 25px;
                    cursor: pointer;
                    margin-left: 12px;
                    font-weight: 600;
                    font-size: 15px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(35, 64, 78, 0.3);
                    position: relative;
                    overflow: hidden;
                }
                
                .fullscreen-chat-send-btn::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                    transition: left 0.5s ease;
                }
                
                .fullscreen-chat-send-btn:hover {
                    background: linear-gradient(135deg, #2d5363 0%, #3a6b7a 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(35, 64, 78, 0.4);
                }
                
                .fullscreen-chat-send-btn:hover::before {
                    left: 100%;
                }
                
                .fullscreen-chat-send-btn:active {
                    transform: translateY(0);
                    box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);
                }
                
                .fullscreen-chat-send-btn:disabled {
                    background: #666;
                    cursor: not-allowed;
                    transform: none;
                }
                
                .chat-message {
                    margin-bottom: 6px;
                    padding: 12px 16px;
                    border-radius: 18px;
                    max-width: 75%;
                    position: relative;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                    transition: all 0.3s ease;
                    animation: messageSlideIn 0.4s ease-out;
                }
                
                .chat-message:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
                }
                
                .chat-message.user {
                    background: linear-gradient(135deg, #23404e 0%, #2d5363 100%);
                    color: #ffffff;
                    margin-left: auto;
                    text-align: right;
                    border: none;
                    border-bottom-right-radius: 6px;
                }
                
                .chat-message.user::before {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    right: -8px;
                    width: 0;
                    height: 0;
                    border: 8px solid transparent;
                    border-left-color: #2d5363;
                    border-bottom: none;
                    border-right: none;
                }
                
                .chat-message.bot {
                    background: linear-gradient(135deg, #2a2a2a 0%, #333333 100%);
                    color: #ffffff;
                    margin-right: auto;
                    border: none;
                    border-bottom-left-radius: 6px;
                }
                
                .chat-message.bot::before {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    left: -8px;
                    width: 0;
                    height: 0;
                    border: 8px solid transparent;
                    border-right-color: #333333;
                    border-bottom: none;
                    border-left: none;
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
                
                .speaker-name {
                    font-weight: 700;
                    margin-bottom: 4px;
                    display: block;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    opacity: 0.9;
                }
                
                .chat-message-content-wrapper {
                    word-wrap: break-word;
                    line-height: 1.4;
                    font-size: 15px;
                }
                
                .chat-message-content-wrapper img {
                    max-width: 100%;
                    border-radius: 12px;
                    margin-top: 10px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
                
                .typing-indicator {
                    color: #888;
                    font-style: italic;
                    padding: 12px 16px;
                    background: linear-gradient(135deg, #2a2a2a 0%, #333333 100%);
                    border-radius: 18px;
                    margin-bottom: 15px;
                    display: inline-block;
                    margin-right: auto;
                    border-bottom-left-radius: 6px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                    position: relative;
                    animation: typingPulse 1.5s ease-in-out infinite;
                }
                
                .typing-indicator::before {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    left: -8px;
                    width: 0;
                    height: 0;
                    border: 8px solid transparent;
                    border-right-color: #333333;
                    border-bottom: none;
                    border-left: none;
                }
                
                @keyframes typingPulse {
                    0%, 100% { opacity: 0.7; }
                    50% { opacity: 1; }
                }
                
                .spinner {
                    border: 3px solid #333;
                    border-top: 3px solid #23404e;
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
                    background: #232323;
                    border-right: 1px solid #404040;
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
                    border-bottom: 1px solid #404040;
                    background: #232629;
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
                    background: #23404e;
                    color: #e0e0e0;
                    border: 1px solid #232629;
                    padding: 8px 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 12px;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }
                
                .new-chat-btn:hover, .save-chat-btn:hover, .export-chat-btn:hover {
                    background: #2d5363;
                    transform: translateY(-1px);
                }
                
                .close-sidebar-btn {
                    background: #2d5363;
                    color: #fff;
                    border: 1px solid #2d5363;
                    padding: 8px 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 12px;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }
                
                .close-sidebar-btn:hover {
                    background: #23404e;
                    transform: translateY(-1px);
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
                    background: #2d5363;
                    color: #fff;
                    border: 1px solid #2d5363;
                    border-radius: 8px;
                    width: 30px;
                    height: 30px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: all 0.2s ease;
                }
                
                .close-settings-modal:hover {
                    background: #23404e;
                    transform: translateY(-1px);
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
                    background: #23404e;
                    color: #e0e0e0;
                    border: 1px solid #232629;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }
                
                .button-primary:hover {
                    background: #2d5363;
                    transform: translateY(-1px);
                }
                
                .button-secondary {
                    background: #6c757d;
                    color: #fff;
                    border: 1px solid #6c757d;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }
                
                .button-secondary:hover {
                    background: #545b62;
                    transform: translateY(-1px);
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
                
                .credit-balance-display {
                    display: none !important; /* Hide credit display on mobile to save space */
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
                        background: #2a2a2a !important;
                        border-top: 1px solid #404040 !important;
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
                        padding: 14px 18px !important;
                        border: 2px solid #333333 !important;
                        border-radius: 20px !important;
                        background: #1a1a1a !important;
                        color: #ffffff !important;
                        font-size: 16px !important; /* Prevent zoom on iOS */
                        resize: none !important;
                        min-height: 44px !important;
                        max-height: 120px !important;
                        transition: all 0.3s ease !important;
                        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
                    }
                    
                    #chat-input:focus {
                        outline: none !important;
                        border-color: #23404e !important;
                        background: #1f1f1f !important;
                        color: #ffffff !important;
                        box-shadow: 0 0 0 3px rgba(35, 64, 78, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
                    }
                    
                    .fullscreen-chat-send-btn {
                        background: linear-gradient(135deg, #23404e 0%, #2d5363 100%) !important;
                        color: #ffffff !important;
                        border: none !important;
                        padding: 14px 20px !important;
                        border-radius: 20px !important;
                        cursor: pointer !important;
                        font-size: 15px !important;
                        font-weight: 600 !important;
                        flex-shrink: 0 !important;
                        min-height: 44px !important;
                        transition: all 0.3s ease !important;
                        box-shadow: 0 4px 12px rgba(35, 64, 78, 0.3) !important;
                    }
                    
                    .chat-message {
                        margin-bottom: 6px !important;
                        padding: 10px 14px !important;
                        border-radius: 16px !important;
                        max-width: 85% !important;
                        word-wrap: break-word !important;
                        font-size: 14px !important;
                        line-height: 1.4 !important;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
                        animation: messageSlideIn 0.4s ease-out !important;
                    }
                    
                    .chat-message.user {
                        background: linear-gradient(135deg, #23404e 0%, #2d5363 100%) !important;
                        color: #ffffff !important;
                        margin-left: auto !important;
                        border: none !important;
                        border-bottom-right-radius: 6px !important;
                    }
                    
                    .chat-message.bot {
                        background: linear-gradient(135deg, #2a2a2a 0%, #333333 100%) !important;
                        color: #ffffff !important;
                        margin-right: auto !important;
                        border: none !important;
                        border-bottom-left-radius: 6px !important;
                    }
                    
                    .speaker-name {
                        color: #e0e0e0 !important;
                        font-weight: bold !important;
                        margin-bottom: 3px !important;
                        display: block !important;
                    }
                    
                    .chat-message-content-wrapper {
                        color: inherit !important;
                        word-wrap: break-word !important;
                    }
                    
                    .conversation-sidebar {
                        width: 280px !important;
                        height: 100dvh !important; /* Use dynamic viewport height */
                        background: #232323 !important;
                        border-right: 1px solid #404040 !important;
                    }
                    
                    /* Ensure buttons stay visible */
                    .conversation-menu-btn,
                    .image-settings-btn,
                    .close-fullscreen-btn {
                        width: 32px !important;
                        height: 32px !important;
                        font-size: 16px !important;
                        margin-right: 5px !important;
                        border-radius: 8px !important;
                        transition: all 0.2s ease !important;
                    }
                    
                    .conversation-menu-btn:hover,
                    .image-settings-btn:hover,
                    .close-fullscreen-btn:hover {
                        transform: translateY(-1px) !important;
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
                let tagHtml = tags.map(tag => `<span class="tag-item">${escapeHtml(tag)}</span>`).join(', ');
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

        // Create conversation sidebar
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
                    <div class="conversation-list"></div>
                </div>
            `;

            // Append to body instead of .chat-main since we're in fullscreen mode
            $('body').append(sidebarHtml);
            
            // Initialize the conversation manager
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.init(chatState.characterData, chatState.characterId);
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

        // Start full screen chat (UPDATED to use PMV_ConversationManager)
        function startFullScreenChat(metadata, fileUrl) {
            try {
                const parsedData = parseCharacterData(metadata);
                const character = extractCharacterInfo(parsedData);
                
                // Extract character filename from fileUrl if provided
                let characterFile = '';
                if (fileUrl) {
                    const urlParts = fileUrl.split('/');
                    characterFile = urlParts[urlParts.length - 1] || '';
                } else if (typeof metadata === 'string') {
                    // Try to extract filename from metadata if it contains file info
                    try {
                        const metaObj = JSON.parse(metadata);
                        if (metaObj.file_url) {
                            const urlParts = metaObj.file_url.split('/');
                            characterFile = urlParts[urlParts.length - 1] || '';
                        }
                    } catch (e) {
                        // Not JSON, ignore
                    }
                }

                // Set up chat state
                chatState.characterData = character;
                chatState.characterId = generateCharacterId(parsedData);
                chatState.characterFile = characterFile; // Store filename for image generation
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
                    style="display: flex; align-items: center; justify-content: space-between; background: #2d2d2d; padding: 10px; border-bottom: 1px solid #404040; flex-shrink: 0; min-height: 60px; max-height: 60px;"
                ` : '';
                const contentMobileStyles = isMobile ? `
                    style="flex: 1; overflow-y: auto; padding: 10px; background: #1a1a1a; min-height: 0;"
                ` : '';
                const historyMobileStyles = isMobile ? `
                    style="padding: 10px; background: #1a1a1a; color: #e0e0e0; position: relative;"
                ` : '';
                const inputContainerMobileStyles = isMobile ? `
                    style="padding: 10px; background: #2a2a2a; border-top: 1px solid #404040; flex-shrink: 0; position: relative; z-index: 10001;"
                ` : '';
                const inputRowMobileStyles = isMobile ? `
                    style="display: flex; align-items: flex-end; gap: 8px;"
                ` : '';
                const inputMobileStyles = isMobile ? `
                    style="flex: 1; padding: 16px 20px; border: 2px solid #333333; border-radius: 25px; background: #1a1a1a; color: #ffffff; font-size: 16px; resize: none; min-height: 60px; max-height: 120px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);"
                ` : '';
                const imageGenButtonMobileStyles = isMobile ? `
                    style="background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%); color: #ffffff; border: none; padding: 16px; border-radius: 25px; cursor: pointer; font-size: 20px; flex-shrink: 0; min-height: 60px; min-width: 60px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3); display: flex; align-items: center; justify-content: center;"
                ` : '';
                const sendButtonMobileStyles = isMobile ? `
                    style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 16px 24px; border-radius: 25px; cursor: pointer; font-size: 15px; font-weight: 600; flex-shrink: 0; min-height: 60px; box-shadow: 0 4px 12px rgba(35, 64, 78, 0.3);"
                ` : '';
                const messageMobileStyles = isMobile ? `
                    style="margin-bottom: 16px; padding: 14px 16px; border-radius: 16px; max-width: 85%; word-wrap: break-word; font-size: 14px; line-height: 1.5; background: linear-gradient(135deg, #2a2a2a 0%, #333333 100%); color: #ffffff; margin-right: auto; border: none; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);"
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
                            <div class="credit-balance-display" ${hideOnMobile}>
                                <span class="credit-label">Token Credits:</span>
                                <span id="token-credits" class="credit-amount">Loading...</span>
                                <span class="credit-label">Image Credits:</span>
                                <span id="image-credits" class="credit-amount">Loading...</span>
                                <button id="refresh-credits" class="refresh-credits-btn" title="Refresh Credits">🔄</button>
                            </div>
                            <div class="header-actions">
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
                                <button id="image-gen-btn" class="image-gen-btn" title="Generate Image" ${imageGenButtonMobileStyles}>🎨</button>
                                <button id="send-chat" class="fullscreen-chat-send-btn" ${sendButtonMobileStyles}>Send</button>
                            </div>
                            <!-- Image Generation Panel -->
                            <div class="image-generation-panel" style="display: none;">
                                <div class="image-panel-header">
                                    <h4>🎨 Image Generation</h4>
                                    <button class="close-image-panel">✕</button>
                                </div>
                                <div class="image-panel-content">
                                    <div class="preset-section">
                                        <label>What do you want to create?</label>
                                        <select id="image-preset">
                                            <option value="">Select a type...</option>
                                            <optgroup label="Character">
                                                <option value="selfie">Selfie - Close-up portrait</option>
                                                <option value="portrait">Portrait - Detailed character view</option>
                                                <option value="full_body">Full Body - Complete character</option>
                                            </optgroup>
                                            <optgroup label="Environment">
                                                <option value="surroundings">Surroundings - Current scene</option>
                                                <option value="landscape">Landscape - Wide scenic view</option>
                                                <option value="room">Room/Interior - Indoor space</option>
                                            </optgroup>
                                            <optgroup label="Action">
                                                <option value="action">Action - Dynamic movement</option>
                                                <option value="pose">Pose - Specific stance</option>
                                            </optgroup>
                                            <optgroup label="Style">
                                                <option value="closeup">Close-up - Detailed focus</option>
                                                <option value="cute">Cute - Adorable style</option>
                                                <option value="serious">Serious - Dramatic tone</option>
                                            </optgroup>
                                        </select>
                                        <p class="preset-description" id="preset-description" style="margin-top: 8px; color: #999; font-size: 0.9em;"></p>
                                    </div>
                                    <div class="user-prompt-section" style="margin-top: 15px;">
                                        <label>Describe what you want to see:</label>
                                        <textarea id="user-description" placeholder="E.g., 'showing the character smiling' or 'the character in a forest'..." rows="3"></textarea>
                                    </div>
                                    <div class="generated-prompt-section" style="margin-top: 15px; display: none;" id="final-prompt-section">
                                        <label>Ready to Generate:</label>
                                        <textarea id="generated-prompt" placeholder="The AI will prepare the prompt..." rows="3" readonly></textarea>
                                        <div class="prompt-actions">
                                            <button id="create-image-btn" class="button-primary">🎨 Create Image</button>
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

                // Image settings button removed - no frontend settings needed

                // Create and show conversation sidebar
                createConversationSidebar();

                // Load token usage stats
                loadTokenUsageStats();
                // NEW_INSERT: also load image usage stats (defaults to SwarmUI provider)
                loadImageUsageStats();
                // Load credit balances from subscription system
                if (window.PMV_ChatCore && window.PMV_ChatCore.loadCreditBalances) {
                    // Add a small delay to ensure DOM elements are ready
                    setTimeout(() => {
                        window.PMV_ChatCore.loadCreditBalances();
                        // Start periodic updates
                        window.PMV_ChatCore.startCreditBalanceUpdates();
                    }, 100);
                }

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
                
                // Add direct click handler for image button after chat is created
                setTimeout(function() {
                    const $imgBtn = $('#image-gen-btn');
                    console.log('After chat init - Image button exists?', $imgBtn.length);
                    if ($imgBtn.length > 0) {
                        console.log('Binding direct click handler to image button');
                        $imgBtn.off('click.directImageGen').on('click.directImageGen', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            console.log('=== DIRECT IMAGE BUTTON CLICK FIRED ===');
                            
                            if (typeof window.openImageGenModal === 'function') {
                                console.log('Calling window.openImageGenModal');
                                window.openImageGenModal();
                            } else {
                                console.error('window.openImageGenModal not found');
                                // Fallback: create modal directly
                                if ($('#image-gen-modal').length === 0) {
                                    console.log('Creating modal directly as fallback');
                                    $('body').append(`
                                        <div id="image-gen-modal" class="image-gen-modal">
                                            <div class="image-gen-modal-content">
                                                <div class="image-gen-modal-header">
                                                    <h3>🎨 Generate Image</h3>
                                                    <button class="image-gen-modal-close">✕</button>
                                                </div>
                                                <div class="image-gen-modal-body">
                                                    <div class="preset-grid" id="preset-grid"></div>
                                                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #444;">
                                                        <button id="generate-image-btn" class="button-primary" style="width: 100%; padding: 12px; font-size: 16px;">Generate Image</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                }
                                $('#image-gen-modal').addClass('active');
                            }
                            return false;
                        });
                    }
                }, 300);
                
                // Scroll to bottom for initial message
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
                


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
                let content = '';

                if ($msg.hasClass('user')) {
                    role = 'user';
                    content = $msg.find('.chat-message-content-wrapper').text() || $msg.text();
                    content = content.replace(/^You:\s*/i, '');
                } else if ($msg.hasClass('bot')) {
                    role = 'assistant';
                    
                    // Check if this is an image message
                    const $img = $msg.find('.chat-message-content-wrapper img');
                    if ($img.length > 0) {
                        // This is an image message - extract the image URL
                        const imgSrc = $img.attr('src');
                        if (imgSrc) {
                            content = `[Generated Image: ${imgSrc}]`;
                        } else {
                            content = $msg.find('.chat-message-content-wrapper').text() || $msg.text();
                        }
                    } else {
                        // Regular text message
                        content = $msg.find('.chat-message-content-wrapper').text() || $msg.text();
                    }
                    
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
                $(document).trigger('pmv:message:added', [{type: 'error', content: errorMessage}]);
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
            .on('pmv_start_chat', function(e, metadata, fileUrl) {
                if (metadata) {
                    // Decode if URL encoded
                    try {
                        if (typeof metadata === 'string' && metadata.indexOf('%') !== -1) {
                            metadata = decodeURIComponent(metadata);
                        }
                    } catch (err) {
                        console.warn('Failed to decode metadata:', err);
                    }
                    startFullScreenChat(metadata, fileUrl);
                }
            })
            .on('click', '.close-modal, #png-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('close-modal')) {
                    $('#png-modal').hide();
                }
            })
            .on('click', '#image-gen-btn, .image-gen-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log('Image gen button clicked - handler fired');
                console.log('Button element:', this);
                console.log('Button ID:', $(this).attr('id'));
                
                // Try calling the function directly
                try {
                    if (typeof openImageGenModal === 'function') {
                        console.log('Calling openImageGenModal directly');
                        openImageGenModal();
                    } else if (typeof window.openImageGenModal === 'function') {
                        console.log('Calling window.openImageGenModal');
                        window.openImageGenModal();
                    } else {
                        console.error('openImageGenModal not found anywhere');
                        // Create modal directly as fallback
                        console.log('Creating modal directly');
                        if ($('#image-gen-modal').length === 0) {
                            $('body').append(`
                                <div id="image-gen-modal" class="image-gen-modal">
                                    <div class="image-gen-modal-content">
                                        <div class="image-gen-modal-header">
                                            <h3>🎨 Generate Image</h3>
                                            <button class="image-gen-modal-close">✕</button>
                                        </div>
                                        <div class="image-gen-modal-body">
                                            <div class="preset-grid" id="preset-grid"></div>
                                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #444;">
                                                <button id="generate-image-btn" class="button-primary" style="width: 100%; padding: 12px; font-size: 16px;">Generate Image</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `);
                        }
                        $('#image-gen-modal').addClass('active');
                        console.log('Modal created and should be visible');
                    }
                } catch (err) {
                    console.error('Error in image gen handler:', err);
                    alert('Error opening image modal: ' + err.message);
                }
            })
            .on('click', '.image-gen-modal-close, .image-gen-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('image-gen-modal-close')) {
                    e.stopPropagation();
                    closeImageGenModal();
                }
            })
            .on('click', '.preset-card', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('.preset-card').removeClass('selected');
                $(this).addClass('selected');
                console.log('Preset selected:', $(this).data('preset-id'));
            })
            .on('click', '#generate-image-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Generate image button clicked');
                generateImageFromPreset();
            })
            
            // Chat interface handlers (UPDATED to delegate to PMV_ConversationManager)
            .on('click', '.close-chat-btn, .close-fullscreen-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeFullScreenChat();
            })
            .on('click', '.menu-btn, .conversation-menu-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Simple toggle using CSS class for both mobile and desktop
                $('.conversation-sidebar').toggleClass('open');
            })
            .on('click', '.close-sidebar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
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
                
                // Validate message
                if (!message) return;
                
                // Limit message length - increased to prevent truncation
                if (message.length > 10000) {
                    alert('Message is too long (maximum 10000 characters)');
                    return;
                }
                
                // Check for potentially harmful content
                if (message.includes('<script') || message.includes('javascript:')) {
                    alert('Message contains invalid content');
                    return;
                }

                // Slash commands completely disabled - only preset-based image generation is used
                if (message.startsWith('/')) {
                    alert('Slash commands are disabled. Please use the 🎨 button for image generation.');
                    return;
                }

                // Add user message
                $('#chat-history').append(`<div class="chat-message user"><span class="speaker-name">You:</span><span class="chat-message-content-wrapper">${escapeHtml(message)}</span></div>`);

                // Clear input and disable send button
                $('#chat-input').val('');
                const $sendButton = $('#send-chat');
                $sendButton.prop('disabled', true).text('Sending...');

                // Add typing indicator
                $('#chat-history').append('<div class="typing-indicator">Thinking...</div>');
                pushContentUp();

                // Notify PMV_ConversationManager of new user message
                if (window.PMV_ConversationManager) {
                    $(document).trigger('pmv:message:added', [{type: 'user', content: message}]);
                }

                // Collect conversation history (excluding the current message)
                const conversationHistory = collectConversationHistory();
                // Don't remove the last message - it's already been added to the UI
                // The conversation history should include all messages up to this point

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

                            console.log('Bot response length:', botResponse.length);
                            console.log('Bot response preview:', botResponse.substring(0, 200) + '...');
                            console.log('Bot response end:', botResponse.substring(botResponse.length - 100));
                            console.log('Full bot response:', botResponse);

                            // Check if response was truncated
                            if (response.data.choices[0].finish_reason === 'length') {
                                console.warn('Response was truncated due to token limit');
                            }

                            // Add bot response to chat
                            $('#chat-history').append(`<div class="chat-message bot"><span class="speaker-name">${characterName}:</span><span class="chat-message-content-wrapper">${botResponse}</span></div>`);

                            pushContentUp();
                            
                            // Notify PMV_ConversationManager of new assistant message
                            if (window.PMV_ConversationManager) {
                                $(document).trigger('pmv:message:added', [{type: 'assistant', content: botResponse}]);
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
            
            // Image settings button and modal removed - all settings are backend-only
            
            // Load available models
            loadSwarmUIModels();
        }
        
        // Image settings functions removed - all settings are backend-only
        
        // Fix for the SwarmUI API integration
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
                            timeout: 15000, // 15 second timeout
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
            // Get provider from backend settings
            const provider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
            
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
                model = $('#default-model').val(); // For settings modal
            } else {
                model = $('#swarmui-model').val(); // For main image panel
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
                timeout: 300000, // 5 minute timeout to match backend
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
                    console.error('AJAX URL:', pmv_ajax_object.ajax_url);
                    console.error('AJAX handlers status:', pmv_ajax_object.debug?.ajax_handlers);
                    
                    const providerName = provider === 'nanogpt' ? 'Nano-GPT' : 'SwarmUI';
                    let errorMessage = 'Failed to connect to ' + providerName + ' server. Please check your connection and try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. The image generation is taking longer than expected. Please try again.';
                    } else if (status === 'error') {
                        if (xhr.status === 0) {
                            errorMessage = 'Network error: Unable to reach the server. Please check your internet connection.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Server error: The image generation endpoint was not found. Please contact support.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error: Internal server error occurred during image generation.';
                        } else {
                            errorMessage = 'Server error: HTTP ' + xhr.status + ' - ' + (xhr.responseText || error);
                        }
                    }
                    
                    $('#image-results').html(`
                        <div style="background: #2d1b1b; border: 1px solid #721c24; color: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #f8d7da;">❌ Image Generation Failed</h4>
                            <p style="margin: 0; color: #f8d7da;">${errorMessage}</p>
                            <p style="margin: 10px 0 0 0; font-size: 0.9em; color: #f8d7da;">Status: ${status} | Error: ${error}</p>
                            <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #f8d7da;">AJAX URL: ${pmv_ajax_object.ajax_url}</p>
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
                            <button onclick="downloadImage('${imageUrl}', 'generated-image-${index + 1}.png')" style="background: linear-gradient(135deg, #006064 0%, #00838f 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; margin-right: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 96, 100, 0.3);">Download</button>
                            <button onclick="addImageToChat('${imageUrl}')" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Add to Chat</button>
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
                
                // Notify PMV_ConversationManager of new image message
                if (window.PMV_ConversationManager) {
                    $(document).trigger('pmv:message:added', [{type: 'assistant', content: '[Generated Image: ' + imageUrl + ']'}]);
                }
            } else {
                alert('Chat interface not available. Please open a chat first.');
            }
        }
        
        function loadImageSettings() {
            console.log('Loading image settings...');
            
            // Load settings from localStorage or use defaults
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            console.log('Loaded settings from localStorage:', settings);
            
            // Provider is set in backend - get from pmv_ajax_object
            const provider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
            console.log('Using provider from backend:', provider);
            
            // Note: Technical parameters (steps, cfg scale, width, height) are now handled
            // by the preset system server-side, so they are not loaded here
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
            // Provider is configured in backend only - no frontend settings to save
            const settings = {};
            
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
        
        // Slash commands completely disabled - only preset-based image generation is used
        // function handleSlashCommand(message) {
        //     // DISABLED: Slash commands are no longer supported
        //     alert('Slash commands are disabled. Please use the 🎨 button for image generation.');
        // }
        
        // Slash commands completely disabled - only preset-based image generation is used
        /*
        function executeSlashCommand(command, args, commandConfig) {
            // DISABLED: Slash commands are no longer supported
            alert('Slash commands are disabled. Please use the 🎨 button for image generation.');
        }
        */
        
        function generateImageFromSlashCommand(prompt, customPrompt, commandType) {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            
            console.log('generateImageFromSlashCommand called with:', { prompt, customPrompt, commandType });
            
            // For /generate command (freestyle) and custom commands, use the prompt directly without OpenAI processing
            if ((commandType === 'freestyle' && prompt) || commandType === 'custom') {
                // Direct image generation with the provided prompt
                if (prompt) {
                    createImageFromPrompt(prompt);
                } else {
                    console.error('No prompt provided for custom command');
                    alert('Error: No prompt provided for custom command. Please provide a prompt after the command.');
                }
                return;
            }
            
            // Slash commands are disabled - this function should not be called
            alert('Slash commands are disabled. Please use the 🎨 button for image generation.');
        }
        
        function createImageFromPrompt(prompt, presetConfig = null, characterModel = null) {
            const settings = JSON.parse(localStorage.getItem('pmv_image_settings') || '{}');
            
            // Store the prompt for later use (to display as caption)
            if (!window.pmvGeneratedImagePrompts) {
                window.pmvGeneratedImagePrompts = {};
            }
            const promptKey = 'pending_' + Date.now();
            
            // Get provider from backend settings, not localStorage
            const provider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
            let requestData = {
                action: 'pmv_generate_image',
                prompt: prompt,
                images_count: 1,
                nonce: pmv_ajax_object.nonce
            };
            
            // Get model: character-specific first, then preset config, then defaults
            let model = characterModel || (presetConfig && presetConfig.model) || null;
            
            // Add provider-specific parameters
            if (provider === 'nanogpt') {
                requestData.model = model || settings.defaultModel || 'recraft-v3';
                requestData.steps = (presetConfig && presetConfig.steps) || settings.defaultSteps || 10;
                requestData.cfg_scale = (presetConfig && presetConfig.cfg_scale) || settings.defaultScale || 7.5;
                requestData.width = (presetConfig && presetConfig.width) || settings.defaultWidth || 1024;
                requestData.height = (presetConfig && presetConfig.height) || settings.defaultHeight || 1024;
                requestData.negative_prompt = (presetConfig && presetConfig.negative_prompt) || settings.negativePrompt || '';
            } else {
                // SwarmUI defaults
                requestData.model = model || settings.defaultModel || 'OfficialStableDiffusion/sd_xl_base_1.0';
                requestData.steps = (presetConfig && presetConfig.steps) || settings.defaultSteps || 20;
                requestData.cfg_scale = (presetConfig && presetConfig.cfg_scale) || settings.defaultCfgScale || 7.0;
                requestData.width = (presetConfig && presetConfig.width) || settings.defaultWidth || 512;
                requestData.height = (presetConfig && presetConfig.height) || settings.defaultHeight || 512;
                requestData.negative_prompt = (presetConfig && presetConfig.negative_prompt) || settings.negativePrompt || '';
            }
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                timeout: 300000, // 5 minute timeout to match backend
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        displayImageResults(response.data);
                        
                        // Add image to chat with prompt as caption
                        if (response.data.images && response.data.images.length > 0) {
                            const imageUrl = response.data.images[0];
                            // Escape HTML in prompt for safe display
                            const escapedPrompt = $('<div>').text(prompt).html();
                            
                            // Store prompt with image URL for later reference
                            if (!window.pmvGeneratedImagePrompts) {
                                window.pmvGeneratedImagePrompts = {};
                            }
                            window.pmvGeneratedImagePrompts[imageUrl] = prompt;
                            
                            $('#chat-history').append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">Image Generated (${provider.toUpperCase()}):</span>
                                    <div class="chat-message-content-wrapper">
                                        <div class="generated-image-container" style="position: relative; display: inline-block;">
                                            <img src="${imageUrl}" 
                                                 style="max-width: 100%; border-radius: 4px; cursor: help;" 
                                                 loading="lazy"
                                                 title="${escapedPrompt}"
                                                 data-prompt="${escapedPrompt}"
                                                 class="pmv-generated-image" />
                                            <div class="pmv-image-prompt-caption" style="
                                                display: none;
                                                position: absolute;
                                                bottom: 0;
                                                left: 0;
                                                right: 0;
                                                background: rgba(0, 0, 0, 0.85);
                                                color: #fff;
                                                padding: 10px;
                                                font-size: 12px;
                                                line-height: 1.4;
                                                border-radius: 0 0 4px 4px;
                                                max-height: 200px;
                                                overflow-y: auto;
                                                word-wrap: break-word;
                                                z-index: 1000;
                                            ">${escapedPrompt}</div>
                                        </div>
                                    </div>
                                </div>
                            `);
                            
                            // Note: Hover handlers are now handled globally via event delegation
                            // on .generated-image-container to prevent flickering
                            
                            pushContentUp();
                            
                            // Consume image credits after successful generation
                            consumeImageCredits(response.data.images.length);
                            
                            // Notify PMV_ConversationManager of new image message with prompt
                            if (window.PMV_ConversationManager) {
                                $(document).trigger('pmv:message:added', [{
                                    type: 'assistant', 
                                    content: '[Generated Image: ' + imageUrl + ']',
                                    prompt: prompt
                                }]);
                            }
                        }
                    } else {
                        alert('Error creating image: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('createImageFromPrompt error:', {xhr, status, error});
                    console.error('AJAX URL:', pmv_ajax_object.ajax_url);
                    
                    let errorMessage = 'Error creating image: Network error';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Image generation timed out. Please try again.';
                    } else if (status === 'error') {
                        if (xhr.status === 0) {
                            errorMessage = 'Network error: Unable to reach the server.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Server error: Image generation endpoint not found.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error: Internal server error occurred.';
                        } else {
                            errorMessage = 'Server error: HTTP ' + xhr.status;
                        }
                    }
                    
                    alert(errorMessage);
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
        
        // Slash commands completely disabled
        /*
        function showSlashCommandHelp() {
            // DISABLED: Slash commands are no longer supported
            alert('Slash commands are disabled. Please use the 🎨 button for image generation.');
        }
        */
        
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
            // Get provider from backend settings
            const provider = (pmv_ajax_object && pmv_ajax_object.image_provider) || 'swarmui';
            
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
                                <button onclick="createImage()" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 12px 20px; border-radius: 12px; margin-top: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Try Regular Generation</button>
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
                            <button onclick="createImage()" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 12px 20px; border-radius: 12px; margin-top: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Try Regular Generation</button>
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
                        <div class="spinner" style="border: 3px solid #333; border-top: 3px solid #23404e; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 10px;"></div>
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
                                <div style="width: ${overallPercent}%; background: #23404e; height: 100%; border-radius: 10px; transition: width 0.3s;"></div>
                            </div>
                            <p style="color: #e0e0e0; margin: 0;">Generating image: ${overallPercent}% complete</p>
                            <p style="color: #888; font-size: 0.9em; margin: 5px 0 0 0;">Current step: ${currentPercent}%</p>
                            ${data.gen_progress.preview ? `<img src="${data.gen_progress.preview}" style="max-width: 200px; border-radius: 4px; margin-top: 10px;" loading="lazy" />` : ''}
                        </div>
                    `;
                } else if (data.images && Array.isArray(data.images)) {
                    // Multiple images result
                    let imagesHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';
                    
                    data.images.forEach((imageUrl, index) => {
                        imagesHtml += `
                            <div style="background: #1b2d1b; border: 1px solid #155724; border-radius: 8px; padding: 15px;">
                                <img src="${imageUrl}" style="width: 100%; height: auto; border-radius: 4px; margin-bottom: 10px;">
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="downloadImage('${imageUrl}', 'generated_image_${index + 1}.png')" style="background: linear-gradient(135deg, #006064 0%, #00838f 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 96, 100, 0.3);">Download</button>
                                    <button onclick="addImageToChat('${imageUrl}')" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Add to Chat</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    imagesHtml += '</div>';
                    
                    progressHtml = `
                        <div style="text-align: center; padding: 20px; background: #1b2d1b; border: 1px solid #155724; border-radius: 4px;">
                            <p style="color: #d4edda; margin: 0 0 15px 0;">✅ ${data.images.length} image(s) generated successfully!</p>
                            ${imagesHtml}
                        </div>
                    `;
                    
                    // Consume image credits after successful generation
                    consumeImageCredits(data.images.length);
                    
                    // Close WebSocket after getting the images
                    socket.close();
                    $('#create-image-btn').prop('disabled', false).text('Create Image');
                } else if (data.image) {
                    // Single image result
                    const imageUrl = data.image.image;
                    progressHtml = `
                        <div style="text-align: center; padding: 20px; background: #1b2d1b; border: 1px solid #155724; border-radius: 4px;">
                            <p style="color: #d4edda; margin: 0 0 15px 0;">✅ Image generated successfully!</p>
                            <img src="${imageUrl}" style="max-width: 100%; border: 1px solid #444; border-radius: 4px; background: #1a1a1a;" loading="lazy" />
                            <div style="margin-top: 10px;">
                                <button onclick="downloadImage('${imageUrl}', 'generated-image.png')" style="background: linear-gradient(135deg, #006064 0%, #00838f 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; margin-right: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 96, 100, 0.3);">Download</button>
                                <button onclick="addImageToChat('${imageUrl}')" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 8px 16px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Add to Chat</button>
                            </div>
                        </div>
                    `;
                    
                    // Consume image credits after successful generation
                    consumeImageCredits(1);
                    
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
                                                    <button onclick="createImage()" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 12px 20px; border-radius: 12px; margin-top: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Try Regular Generation</button>
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
                            <button onclick="createImage()" style="background: linear-gradient(135deg, #23404e 0%, #2d5363 100%); color: #ffffff; border: none; padding: 12px 20px; border-radius: 12px; margin-top: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(35, 64, 78, 0.3);">Try Regular Generation</button>
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
                    'background': '#2a2a2a',
                    'border-top': '1px solid #404040',
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
                    'background': '#23404e',
                    'color': '#e0e0e0',
                    'border': '1px solid #232629',
                    'padding': '10px 15px',
                    'border-radius': '8px',
                    'cursor': 'pointer',
                    'font-size': '14px',
                    'font-weight': '600',
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
                        'background': '#23404e',
                        'color': '#e0e0e0',
                        'margin-left': 'auto',
                        'border': '1px solid #232629'
                    });
                } else if ($(this).hasClass('bot')) {
                    $(this).css({
                        'background': '#232323',
                        'color': '#e0e0e0',
                        'margin-right': 'auto',
                        'border': '1px solid #404040'
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
                    'background': '#23404e',
                    'color': '#e0e0e0',
                    'border': '1px solid #232629',
                    'padding': '12px 20px',
                    'border-radius': '8px',
                    'cursor': 'pointer',
                    'margin-left': '10px',
                    'font-weight': '600'
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
                        'background': '#23404e',
                        'color': '#e0e0e0',
                        'margin-left': 'auto',
                        'text-align': 'right',
                        'border': '1px solid #232629'
                    });
                } else if ($(this).hasClass('bot')) {
                    $(this).css({
                        'background': '#232323',
                        'color': '#e0e0e0',
                        'margin-right': 'auto',
                        'border': '1px solid #404040'
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
        
        // Consume image credits after successful generation
        function consumeImageCredits(imagesCount) {
            if (!pmv_ajax_object || !pmv_ajax_object.ajax_url || !pmv_ajax_object.nonce) {
                console.warn('PMV: Cannot consume image credits - AJAX object not available');
                return;
            }
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_consume_image_credits',
                    nonce: pmv_ajax_object.nonce,
                    images_count: imagesCount
                },
                success: function(response) {
                    if (response.success) {
                        console.log('PMV: Successfully consumed', response.data.credits_consumed, 'image credit(s)');
                        console.log('PMV: New image credit balance:', response.data.new_balance);
                        
                        // Update credit display if available
                        if (window.PMV_ChatCore && window.PMV_ChatCore.loadCreditBalances) {
                            window.PMV_ChatCore.loadCreditBalances();
                        }
                    } else {
                        console.error('PMV: Failed to consume image credits:', response.data?.message || 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PMV: Error consuming image credits:', {xhr, status, error});
                }
            });
        }
        
        // Make consumeImageCredits available globally
        window.consumeImageCredits = consumeImageCredits;
        
        // Image Generation Modal Functions - exposed in scope
        var openImageGenModal = function() {
            console.log('openImageGenModal called');
            // Create modal if it doesn't exist
            if ($('#image-gen-modal').length === 0) {
                console.log('Creating image gen modal');
                $('body').append(`
                    <div id="image-gen-modal" class="image-gen-modal">
                        <div class="image-gen-modal-content">
                            <div class="image-gen-modal-header">
                                <h3>🎨 Generate Image</h3>
                                <button class="image-gen-modal-close">✕</button>
                            </div>
                            <div class="image-gen-modal-body">
                                <div class="preset-grid" id="preset-grid"></div>
                                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #444;">
                                    <button id="generate-image-btn" class="button-primary" style="width: 100%; padding: 12px; font-size: 16px;">Generate Image</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                loadPresetsIntoModal();
            }
            $('#image-gen-modal').addClass('active');
            console.log('Modal should be visible now');
        };
        
        var closeImageGenModal = function() {
            $('#image-gen-modal').removeClass('active');
        };
        
        var loadPresetsIntoModal = function() {
            // Get character filename from chatState if available
            const characterFilename = chatState.characterFile || '';
            
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
                        const $grid = $('#preset-grid');
                        $grid.empty();
                        
                        // Group presets by category
                        const categories = {};
                        $.each(presets, function(id, preset) {
                            const category = preset.category || 'custom';
                            if (!categories[category]) {
                                categories[category] = [];
                            }
                            categories[category].push({id: id, preset: preset});
                        });
                        
                        // Render presets grouped by category
                        $.each(categories, function(category, items) {
                            // Category header
                            const categoryName = category.charAt(0).toUpperCase() + category.slice(1);
                            $grid.append(`<div class="preset-category-header" style="grid-column: 1 / -1; font-weight: bold; margin-top: 15px; margin-bottom: 10px; color: #fff; text-transform: capitalize;">${categoryName}</div>`);
                            
                            // Presets in this category
                            $.each(items, function(i, item) {
                                const id = item.id;
                                const preset = item.preset;
                                const isCustom = preset.is_custom ? 'custom-preset' : '';
                                $grid.append(`
                                    <div class="preset-card ${isCustom}" data-preset-id="${id}" style="${isCustom ? 'border: 2px solid #ffa500;' : ''}">
                                        <div class="preset-card-name">${preset.name} ${isCustom ? '<span style="font-size: 0.8em; color: #ffa500;">(Custom)</span>' : ''}</div>
                                        <div class="preset-card-desc">${preset.description}</div>
                                    </div>
                                `);
                            });
                        });
                    }
                },
                error: function() {
                    alert('Failed to load presets');
                }
            });
        };
        
        var generateImageFromPreset = function() {
            const selectedPreset = $('.preset-card.selected');
            if (selectedPreset.length === 0) {
                alert('Please select a preset first');
                return;
            }
            
            const presetId = selectedPreset.data('preset-id');
            const presetName = selectedPreset.find('.preset-card-name').text().trim();
            
            // Get conversation context - last 5 messages
            let chatHistory = '';
            let characterDescription = '';
            
            try {
                const messages = collectConversationHistory();
                if (messages && messages.length > 0) {
                    // Get last 5 messages for context
                    const lastMessages = messages.slice(-5);
                    chatHistory = lastMessages.map(msg => 
                        `${msg.role === 'user' ? 'You' : chatState.characterData?.name || 'Character'}: ${msg.content}`
                    ).join('\n');
                }
            } catch (err) {
                console.warn('Could not collect conversation history:', err);
            }
            
            // Get character description if available
            if (chatState.characterData?.description) {
                characterDescription = chatState.characterData.description;
            }
            
            closeImageGenModal();
            
            // Get character filename from chatState if available
            const characterFilename = chatState.characterFile || '';
            
            // Show loading indicator
            $('#chat-history').append(`<div class="chat-message system"><span class="speaker-name">System:</span><span class="chat-message-content-wrapper">Generating image with "${presetName}" preset...</span></div>`);
            pushContentUp();
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_generate_image_prompt',
                    nonce: pmv_ajax_object.nonce,
                    preset_id: presetId,
                    chat_history: chatHistory,
                    character_description: characterDescription,
                    character_filename: characterFilename
                },
                success: function(response) {
                    if (response.success) {
                        const finalPrompt = response.data.final_prompt;
                        const presetConfig = response.data.preset_config;
                        const characterModel = response.data.character_model || null; // Character-specific model if set
                        
                        // Remove loading message
                        $('#chat-history .chat-message.system').last().remove();
                        
                        createImageFromPrompt(finalPrompt, presetConfig, characterModel);
                    } else {
                        // Remove loading message
                        $('#chat-history .chat-message.system').last().remove();
                        alert('Error: ' + (response.data?.message || 'Failed to generate prompt'));
                    }
                },
                error: function() {
                    // Remove loading message
                    $('#chat-history .chat-message.system').last().remove();
                    alert('Failed to generate image prompt');
                }
            });
        };
        
        // Global hover handler for all generated images (using event delegation)
        // This works for both new images and images loaded from saved conversations
        // Use the container instead of the image to prevent flickering when hovering over caption
        $(document).on('mouseenter', '.generated-image-container', function() {
            const $container = $(this);
            const $caption = $container.find('.pmv-image-prompt-caption');
            if ($caption.length) {
                $caption.fadeIn(200);
            }
        });
        
        $(document).on('mouseleave', '.generated-image-container', function() {
            const $container = $(this);
            const $caption = $container.find('.pmv-image-prompt-caption');
            if ($caption.length) {
                $caption.fadeOut(200);
            }
        });
        
        // Expose functions globally for event handlers
        window.openImageGenModal = openImageGenModal;
        window.closeImageGenModal = closeImageGenModal;
    });
    
})(jQuery); // Pass jQuery to the function
