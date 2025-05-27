/* PNG Metadata Viewer - Universal Chat System with Message Editing - Consistent Layout for All Devices */
console.log('PNG Metadata Viewer Universal Chat System with Message Editing Loading...');
(function($) {
    'use strict';
    
    // Universal configuration
    const CHAT_CONFIG = {
        breakpoint: 768,
        menuZIndex: 999999,
        overlayZIndex: 999998,
        sidebarWidth: '80%',
        maxSidebarWidth: '350px',
        maxInputHeight: 120,
        minInputHeight: 50,
        headerHeight: 60,
        safeAreaPadding: 20
    };
    
    // Chat state management
    const chatState = {
        initialized: false,
        menuOpen: false,
        conversationsLoaded: false,
        retryCount: 0,
        maxRetries: 5,
        keyboardOpen: false,
        originalViewportHeight: window.innerHeight,
        currentInputHeight: CHAT_CONFIG.minInputHeight,
        isLayoutApplied: false,
        editingEnabled: true,
        hasUnsavedChanges: false,
        autoSaveEnabled: true,
        lastSaveTime: null
    };
    
    // Message editing state
    const editingState = {
        currentlyEditing: null,
        originalContent: null,
        hasUnsavedChanges: false
    };
    
    // Check if we're in a chat context
    function checkIfInChat() {
        return $('.png-metadata-viewer').length > 0 ||
               $('#chat-container').length > 0 ||
               $('#chat-history').length > 0 ||
               $('.chat-container').length > 0 ||
               window.location.search.includes('action=chat');
    }
    
    // Detect mobile devices (for touch-specific features only)
    function isTouchDevice() {
        return ('ontouchstart' in window) || 
               (navigator.maxTouchPoints > 0) || 
               (navigator.msMaxTouchPoints > 0);
    }
    
    // Detect small screens (for layout optimizations)
    function isSmallScreen() {
        return window.innerWidth <= CHAT_CONFIG.breakpoint;
    }
    
    // Detect desktop specifically
    function isDesktop() {
        return window.innerWidth > CHAT_CONFIG.breakpoint;
    }
    
    // Wait for dependencies
    function waitForDependencies(callback, timeout = 10000) {
        const startTime = Date.now();
        function checkDependencies() {
            const hasConversationManager = window.PMV_ConversationManager &&
                                         window.PMV_ConversationManager.isReady;
            const hasChat = window.PMV_Chat && window.PMV_Chat.isReady;
            
            if (hasConversationManager && hasChat) {
                console.log('All chat dependencies ready');
                callback();
                return;
            }
            
            if (Date.now() - startTime > timeout) {
                console.warn('Chat dependencies timeout');
                callback(); // Try anyway
                return;
            }
            
            setTimeout(checkDependencies, 100);
        }
        checkDependencies();
    }
    
    // Add CSS for universal layout with message editing support
    function addUniversalLayoutCSS() {
        if ($('#universal-chat-css').length) return;
        
        const css = `
            <style id="universal-chat-css">
            /* UNIVERSAL LAYOUT: Consistent for all devices */
            .chat-modal-open .chat-container {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                background: #232323 !important;
                color: #f1f1f1 !important;
                z-index: 999999 !important;
            }
            
            /* Header - Fixed height at top */
            .chat-modal-open .chat-container #chat-header {
                position: relative !important;
                height: ${CHAT_CONFIG.headerHeight}px !important;
                min-height: ${CHAT_CONFIG.headerHeight}px !important;
                max-height: ${CHAT_CONFIG.headerHeight}px !important;
                flex-shrink: 0 !important;
                background: #232323 !important;
                color: #f1f1f1 !important;
                backdrop-filter: blur(10px) !important;
                border-bottom: 2px solid rgba(0,0,0,0.1) !important;
                z-index: 10 !important;
                padding: 0 15px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
            }
            
            /* Chat main - optimized height */
            .chat-modal-open .chat-container .chat-main {
                position: relative !important;
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                min-height: 0 !important;
                height: calc(100vh - ${CHAT_CONFIG.headerHeight}px) !important;
                max-height: calc(100vh - ${CHAT_CONFIG.headerHeight}px) !important;
                left: 0 !important;
                width: 100% !important;
            }
            
            /* Chat history box - seamless connection with input */
            .chat-modal-open .chat-container #chat-history {
                position: relative !important;
                flex: 1 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                scroll-behavior: smooth !important;
                background: #232323 !important;
                color: #f1f1f1 !important;
                margin: 8px 8px 0 8px !important;
                border-radius: 12px 12px 0 0 !important;
                padding: 16px !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
                border: 1px solid rgba(0,0,0,0.1) !important;
                border-bottom: none !important;
                padding-bottom: 16px !important;
                min-height: 100px !important;
                
                /* Enhanced scrollbar */
                scrollbar-width: thin !important;
                scrollbar-color: #33452c !important;
            }
            
            /* Webkit scrollbar styling */
            .chat-modal-open .chat-container #chat-history::-webkit-scrollbar {
                width: 6px !important;
                background: rgba(0,0,0,0.05) !important;
            }
            
            .chat-modal-open .chat-container #chat-history::-webkit-scrollbar-track {
                background: rgba(0,0,0,0.05) !important;
                border-radius: 3px !important;
            }
            
            .chat-modal-open .chat-container #chat-history::-webkit-scrollbar-thumb {
                background: #33452c !important;
                border-radius: 3px !important;
            }
             
            /* Input container - seamlessly connected to history */
            .chat-modal-open .chat-container #chat-input-row {
                position: relative !important;
                flex-shrink: 0 !important;
                background: #232323 !important;
                color: #f1f1f1 !important;
                margin: 0 8px 8px 8px !important;
                padding: 12px !important;
                border-radius: 0 0 12px 12px !important;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
                border: 1px solid rgba(0,0,0,0.1) !important;
                border-top: none !important;
                box-sizing: border-box !important;
                z-index: 100 !important;
                min-height: 70px !important;
                max-height: 140px !important;
                width: auto !important;
                overflow: visible !important;
            }
            
            /* Safe area padding for devices with notches */
            @supports (padding: max(0px)) {
                .chat-modal-open .chat-container #chat-input-row {
                    margin-bottom: max(8px, env(safe-area-inset-bottom)) !important;
                }
            }
            
            /* Input wrapper inside container */
            .chat-modal-open .chat-container .input-wrapper {
                display: flex !important;
                align-items: flex-end !important;
                gap: 10px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                flex-direction: row !important;
                justify-content: space-between !important;
            }
            
            /* Text input styling */
            .chat-modal-open .chat-container #chat-input {
                flex: 1 1 auto !important;
                min-height: 46px !important;
                max-height: 100px !important;
                padding: 10px 14px !important;
                border: 2px solid #4b5748 !important;;
                border-radius: 10px !important;
                font-size: 16px !important;
                line-height: 1.4 !important;
                resize: none !important;
                outline: none !important;
                font-family: inherit !important;
                background: #2f322e !important;
                color: #f1f1f1 !important;
                box-sizing: border-box !important;
                transition: border-color 0.2s ease !important;
                display: block !important;
                visibility: visible !important;
                max-width: calc(100% - 100px) !important;
            }
            
            .chat-modal-open .chat-container #chat-input:focus {
                border-color: #4f614a !important;
                background: #2e302e !important;
                box-shadow: 0 0 0 3px rgba(74,144,226,0.1) !important;
            }
            
            /* Send button styling - ALWAYS VISIBLE */
            .chat-modal-open .chat-container .chat-send-button,
            .chat-modal-open .chat-container #send-chat {
                flex: 0 0 auto !important;
                flex-shrink: 0 !important;
                padding: 10px 20px !important;
                background: #3c4138 !important;
                color: white !important;
                border: none !important;
                border-radius: 10px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                min-height: 46px !important;
                min-width: 80px !important;
                max-width: 120px !important;
                box-sizing: border-box !important;
                transition: background-color 0.2s ease !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 1000 !important;
                left: auto !important;
                right: auto !important;
                top: auto !important;
                bottom: auto !important;
                margin: 0 !important;
                transform: none !important;
            }
            
            .chat-modal-open .chat-container .chat-send-button:hover,
            .chat-modal-open .chat-container .chat-send-button:active,
            .chat-modal-open .chat-container #send-chat:hover,
            .chat-modal-open .chat-container #send-chat:active {
                background: #6c6969 !important;
            }
            
            .chat-modal-open .chat-container .chat-send-button:disabled,
            .chat-modal-open .chat-container #send-chat:disabled {
                background: #353a32 !important;
                cursor: not-allowed !important;
            }
            
            /* MESSAGE EDITING STYLES - INTEGRATED WITH UNIVERSAL LAYOUT */
            
            /* Message Header with Actions */
            .chat-modal-open .chat-container .chat-message .message-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                margin-bottom: 8px !important;
                gap: 10px !important;
            }
            
            .chat-modal-open .chat-container .chat-message .speaker-name {
                flex: 1 !important;
                color: #f1f1f1 !important;
                font-weight: 600 !important;
                line-height: 1.4 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.user .speaker-name {
                color: #b1dda8 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.bot .speaker-name {
                color: #aff2df !important;
            }
            
            .chat-modal-open .chat-container .chat-message.error .speaker-name {
                color: #c33 !important;
            }
            
            /* Message Actions */
            .chat-modal-open .chat-container .message-actions {
                display: flex !important;
                gap: 4px !important;
                opacity: 0 !important;
                transition: opacity 0.2s ease !important;
                flex-shrink: 0 !important;
            }
            
            .chat-modal-open .chat-container .chat-message:hover .message-actions {
                opacity: 1 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.editing .message-actions {
                opacity: 1 !important;
            }
            
            .chat-modal-open .chat-container .message-actions button {
                background: rgba(0, 0, 0, 0.1) !important;
                border: none !important;
                border-radius: 4px !important;
                padding: 4px 6px !important;
                cursor: pointer !important;
                color: #666 !important;
                font-size: 12px !important;
                line-height: 1 !important;
                transition: all 0.2s ease !important;
                min-width: 24px !important;
                height: 24px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .chat-modal-open .chat-container .message-actions button:hover {
                background: rgba(0, 0, 0, 0.2) !important;
                color: #93d29a !important;
            }
            
            .chat-modal-open .chat-container .edit-message-btn:hover {
                background: rgba(74, 144, 226, 0.2) !important;
                color: #93d29a !important;
            }
            
            .chat-modal-open .chat-container .regenerate-message-btn:hover {
                background: rgba(255, 193, 7, 0.2) !important;
                color: #93d29a !important;
            }
            
            .chat-modal-open .chat-container .delete-message-btn:hover {
                background: rgba(231, 76, 60, 0.2) !important;
                color: #93d29a !important;
            }
            
            /* Message Content Container */
            .chat-modal-open .chat-container .chat-message .message-content {
                position: relative !important;
                width: 100% !important;
            }
            
            .chat-modal-open .chat-container .chat-message .chat-message-content-wrapper {
                display: block !important;
                word-wrap: break-word !important;
                line-height: 1.6 !important;
                color: #f1f1f1 !important;
            }
            
            /* Edit Interface */
            .chat-modal-open .chat-container .message-edit-container {
                width: 100% !important;
                margin-top: 8px !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea {
                width: 100% !important;
                min-height: 60px !important;
                max-height: 300px !important;
                padding: 12px !important;
                border: 2px solid #2a2a2a !important;
                border-radius: 8px !important;
                background: #1a1f19 !important;
                color: #f1f1f1 !important;
                font-family: inherit !important;
                font-size: 14px !important;
                line-height: 1.6 !important;
                resize: none !important;
                outline: none !important;
                box-sizing: border-box !important;
                transition: border-color 0.2s ease !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea:focus {
                border-color: #90e996 !important;
                box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1) !important;
            }
            
            .chat-modal-open .chat-container .edit-actions {
                display: flex !important;
                gap: 8px !important;
                margin-top: 8px !important;
                justify-content: flex-end !important;
            }
            
            .chat-modal-open .chat-container .edit-actions button {
                padding: 6px 16px !important;
                border: none !important;
                border-radius: 6px !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
                min-width: 70px !important;
            }
            
            .chat-modal-open .chat-container .save-edit-btn {
                background: #425643 !important;
                color: white !important;
            }
            
            .chat-modal-open .chat-container .save-edit-btn:hover {
                background: #3f5d40 !important;
            }
            
            .chat-modal-open .chat-container .cancel-edit-btn {
                background: #6c6969 !important;
                color: white !important;
            }
            
            .chat-modal-open .chat-container .cancel-edit-btn:hover {
                background: #808080 !important;
            }
            
            /* Editing State Styles */
            .chat-modal-open .chat-container .chat-message.editing {
                background: rgba(93, 226, 74, 0.04) !important;;
                border-left: 3px solid #3c453c !important;
                padding-left: 12px !important;
                margin-left: -15px !important;
                border-radius: 0 8px 8px 0 !important;
            }
            
            .chat-modal-open .chat-container.message-editing-mode .chat-message:not(.editing) {
                opacity: 0.6 !important;
            }
            
            .chat-modal-open .chat-container.message-editing-mode .chat-input-container {
                opacity: 0.6 !important;
            }
            
            /* Regenerating state styles */
            .chat-modal-open .chat-container .chat-message.regenerating {
                background: rgba(255, 193, 7, 0.04) !important;
                border-left: 3px solid #ffc107 !important;
                padding-left: 12px !important;
                margin-left: -15px !important;
                border-radius: 0 8px 8px 0 !important;
            }
            
            .chat-modal-open .chat-container .regenerating-indicator {
                color: #ffc107 !important;
                font-style: italic !important;
                font-size: 12px !important;
                margin-top: 4px !important;
                display: flex !important;
                align-items: center !important;
                gap: 5px !important;
            }
            
            .chat-modal-open .chat-container .regenerating-spinner {
                display: inline-block !important;
                width: 12px !important;
                height: 12px !important;
                border: 2px solid #ffc107 !important;
                border-radius: 50% !important;
                border-top-color: transparent !important;
                animation: spin 1s ease-in-out infinite !important;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            /* DESKTOP SPECIFIC FIXES */
            @media (min-width: 769px) {
                /* Desktop container adjustments */
                .chat-modal-open .chat-container {
                    max-width: 1200px !important;
                    margin: 0 auto !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                }
                
                /* Desktop header improvements */
                .chat-modal-open .chat-container #chat-header {
                    padding: 0 24px !important;
                }
                
                /* Desktop chat history improvements */
                .chat-modal-open .chat-container #chat-history {
                    margin: 12px 12px 0 12px !important;
                    padding: 24px !important;
                }
                
                /* Desktop input container fixes */
                .chat-modal-open .chat-container #chat-input-row {
                    margin: 0 12px 12px 12px !important;
                    padding: 16px !important;
                    width: auto !important;
                    max-width: none !important;
                    left: auto !important;
                    right: auto !important;
                }
                
                /* Desktop input wrapper fixes */
                .chat-modal-open .chat-container .input-wrapper {
                    gap: 12px !important;
                    justify-content: stretch !important;
                }
                
                /* Desktop text input fixes */
                .chat-modal-open .chat-container #chat-input {
                    padding: 12px 16px !important;
                    max-width: calc(100% - 120px) !important;
                    width: auto !important;
                }
                
                /* Desktop send button fixes */
                .chat-modal-open .chat-container .chat-send-button,
                .chat-modal-open .chat-container #send-chat {
                    padding: 12px 24px !important;
                    min-width: 100px !important;
                    max-width: 140px !important;
                }
                
                /* Desktop message editing enhancements */
                .chat-modal-open .chat-container .message-actions button {
                    min-width: 26px !important;
                    height: 26px !important;
                }
                
                .chat-modal-open .chat-container .message-edit-textarea {
                    padding: 14px !important;
                    font-size: 15px !important;
                }
                
                .chat-modal-open .chat-container .edit-actions button {
                    padding: 8px 20px !important;
                    min-width: 80px !important;
                }
            }
            
            /* Mobile-specific adjustments for editing */
            @media (max-width: 768px) {
                .chat-modal-open .chat-container .message-actions {
                    opacity: 1 !important; /* Always visible on mobile */
                }
                
                .chat-modal-open .chat-container .message-actions button {
                    min-width: 28px !important;
                    height: 28px !important;
                    padding: 6px !important;
                }
                
                .chat-modal-open .chat-container .chat-message .message-header {
                    flex-wrap: wrap !important;
                    gap: 8px !important;
                }
                
                .chat-modal-open .chat-container .message-edit-textarea {
                    font-size: 16px !important; /* Prevent zoom on iOS */
                    padding: 10px !important;
                }
                
                .chat-modal-open .chat-container .edit-actions {
                    justify-content: stretch !important;
                }
                
                .chat-modal-open .chat-container .edit-actions button {
                    flex: 1 !important;
                    padding: 10px 16px !important;
                    font-size: 16px !important;
                }
            }
            
            /* UNIVERSAL BUTTON FORCE VISIBILITY - MAXIMUM OVERRIDE */
            button#mobile-menu-toggle,
            button#send-chat,
            .mobile-menu-btn,
            .chat-send-button,
            .close-chat-btn,
            #close-chat,
            .edit-message-btn,
            .regenerate-message-btn,
            .delete-message-btn,
            .save-edit-btn,
            .cancel-edit-btn,
            .chat-modal-open .chat-container button#mobile-menu-toggle,
            .chat-modal-open .chat-container button#send-chat,
            .chat-modal-open .chat-container .mobile-menu-btn,
            .chat-modal-open .chat-container .chat-send-button,
            .chat-modal-open .chat-container .close-chat-btn,
            .chat-modal-open .chat-container #close-chat,
            .chat-modal-open .chat-container .edit-message-btn,
            .chat-modal-open .chat-container .regenerate-message-btn,
            .chat-modal-open .chat-container .delete-message-btn,
            .chat-modal-open .chat-container .save-edit-btn,
            .chat-modal-open .chat-container .cancel-edit-btn {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 99999 !important;
                pointer-events: auto !important;
                border: none !important;
                border-radius: 6px !important;
                cursor: pointer !important;
                font-size: 16px !important;
                min-width: auto !important;
                min-height: auto !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                float: none !important;
                clear: none !important;
                left: auto !important;
                right: auto !important;
                top: auto !important;
                bottom: auto !important;
                transform: none !important;
                clip: auto !important;
                clip-path: none !important;
                overflow: visible !important;
            }
            
            /* Menu button specific styling */
            button#mobile-menu-toggle,
            .mobile-menu-btn {
                background: rgba(0,0,0,0.1) !important;
                color: #f1f1f1 !important;
                font-size: 18px !important;
                font-weight: bold !important;
                padding: 8px 12px !important;
            }
            
            /* Close button specific styling */
            button#close-chat,
            .close-chat-btn {
                background: rgba(255,0,0,0.1) !important;
                color: #f1f1f1 !important;
                padding: 8px 12px !important;
            }
            
            /* Send button specific styling */
            button#send-chat,
            .chat-send-button {
                background: #35bd3e !important;
                color: white !important;
                font-weight: 600 !important;
                padding: 10px 20px !important;
            }
            
            /* Character name in header */
            .chat-modal-open .chat-container .chat-modal-name {
                flex: 1 !important;
                text-align: center !important;
                font-weight: 600 !important;
                font-size: 18px !important;
                color: #f1f1f1 !important;
                margin: 0 15px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                transition: color 0.2s ease !important;
            }
            
            /* Modified conversation indicator */
            .chat-modal-open .chat-container .chat-modal-name[title*="unsaved"],
            .chat-modal-open .chat-container .chat-modal-name:contains("*") {
                color: #f39c12 !important;
            }
            
            /* Chat messages styling */
            .chat-modal-open .chat-container .chat-message {
                margin-bottom: 16px !important;
                word-wrap: break-word !important;
                max-width: 100% !important;
                line-height: 1.6 !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                transition: all 0.3s ease !important;
            }
            
            .chat-modal-open .chat-container .chat-message:last-child {
                margin-bottom: 16px !important;
            }
            
            /* Message content styling */
            .chat-modal-open .chat-container .chat-message strong {
                color: #f1f1f1 !important;
                font-weight: 600 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.user strong {
                color: #9effa5 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.bot strong {
                color: #85c5bf !important;
            }
            
            /* Typing indicator */
            .chat-modal-open .chat-container .typing-indicator {
                margin-bottom: 16px !important;
                font-style: italic !important;
                color: #f1f1f1 !important;
                padding: 8px 12px !important;
                background: #3d4740 !important;
                border-radius: 8px !important;
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                animation: pulse 1.5s infinite !important;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 0.6 !important; }
                50% { opacity: 1 !important; }
            }
            
            /* Error message styling */
            .chat-modal-open .chat-container .chat-message.error {
                background: #fee !important;
                border: 1px solid #fcc !important;
                border-radius: 8px !important;
                padding: 12px !important;
            }
            
            .chat-modal-open .chat-container .chat-message.error strong {
                color: #c33 !important;
            }
            
            .chat-modal-open .chat-container .chat-message.error .message-edit-textarea {
                border-color: #aff2df !important;
            }
            
            .chat-modal-open .chat-container .chat-message.error .message-edit-textarea:focus {
                border-color: #35bd3e !important;
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
            }
            
            /* Sidebar styles for menu functionality */
            .pmv-mobile-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -100% !important;
                width: ${CHAT_CONFIG.sidebarWidth} !important;
                max-width: ${CHAT_CONFIG.maxSidebarWidth} !important;
                height: 100vh !important;
                background: #232323 !important;
                z-index: ${CHAT_CONFIG.menuZIndex + 1} !important;
                transition: left 0.3s ease !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
                display: none !important;
            }
            
            .pmv-mobile-sidebar.open {
                left: 0 !important;
                display: block !important;
            }
            
            .pmv-mobile-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0,0,0,0.5) !important;
                z-index: ${CHAT_CONFIG.menuZIndex} !important;
                opacity: 0 !important;
                visibility: hidden !important;
                transition: opacity 0.3s ease, visibility 0.3s ease !important;
                display: none !important;
            }
            
            .pmv-mobile-overlay.open {
                opacity: 1 !important;
                visibility: visible !important;
                display: block !important;
            }
            
            .mobile-sidebar-content {
                padding: 20px !important;
                height: 100% !important;
                overflow-y: auto !important;
                display: flex !important;
                flex-direction: column !important;
            }
            
            .mobile-sidebar-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                margin-bottom: 20px !important;
                padding-bottom: 10px !important;
                border-bottom: 1px solid #eee !important;
            }
            
            .mobile-sidebar-header h3 {
                margin: 0 !important;
                font-size: 18px !important;
                color: #f1f1f1 !important;
            }
            
            .mobile-close-btn {
                background: none !important;
                border: none !important;
                font-size: 24px !important;
                color: #666 !important;
                cursor: pointer !important;
                padding: 5px !important;
                border-radius: 3px !important;
                line-height: 1 !important;
                width: 32px !important;
                height: 32px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .mobile-close-btn:hover {
                background: rgba(0,0,0,0.1) !important;
            }
            
            .mobile-new-chat-btn {
                background: #303030 !important;
                color: white !important;
                border: none !important;
                padding: 12px 20px !important;
                border-radius: 8px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                margin-bottom: 10px !important;
                transition: background-color 0.2s ease !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            .mobile-new-chat-btn:hover {
                background: #404040 !important;
            }
            
            .mobile-save-chat-btn {
                background: #36473B !important;
                color: white !important;
                border: none !important;
                padding: 12px 20px !important;
                border-radius: 8px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                margin-bottom: 20px !important;
                transition: background-color 0.2s ease !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            .mobile-save-chat-btn:hover {
                background: #3B4F41 !important;
            }
            
            .mobile-save-chat-btn:disabled {
                background: #666 !important;
                cursor: not-allowed !important;
            }
            
            .mobile-conversations-list {
                flex: 1 !important;
                overflow-y: auto !important;
            }
            
            .conversation-item {
                padding: 12px !important;
                border-bottom: 1px solid #394335 !important
                cursor: pointer !important;
                transition: background-color 0.2s ease !important;
                border-radius: 6px !important;
                margin-bottom: 5px !important;
            }
            
            .conversation-item:hover {
                background: #363e39 !important;
            }
            
            .conversation-item:last-child {
                border-bottom: none !important;
            }
            
            .conversation-title {
                font-weight: 600 !important;
                color: #f1f1f1 !important;
                margin-bottom: 4px !important;
                font-size: 14px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            
            .conversation-preview {
                color: #666 !important;
                font-size: 13px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            
            .conversation-date {
                color: #999 !important;
                font-size: 12px !important;
                margin-top: 4px !important;
            }
            
            .mobile-loading-container {
                text-align: center !important;
                padding: 20px !important;
                color: #666 !important;
                line-height: 1.5 !important;
            }
            
            .retry-button {
                margin-top: 10px !important;
                padding: 8px 16px !important;
                background: #35bd3e !important;
                color: white !important;
                border: none !important;
                border-radius: 4px !important;
                cursor: pointer !important;
                font-size: 14px !important;
                transition: background-color 0.2s ease !important;
            }
            
            .retry-button:hover {
                background: #1b7f26 !important;
            }
            
            /* Save status indicator */
            .save-status {
                position: fixed !important;
                top: 80px !important;
                right: 20px !important;
                padding: 8px 12px !important;
                border-radius: 6px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                z-index: 100000 !important;
                transition: all 0.3s ease !important;
                pointer-events: none !important;
            }
            
            .save-status.saving {
                background: #ffc107 !important;
                color: #000 !important;
            }
            
            .save-status.saved {
                background: #28a745 !important;
                color: white !important;
            }
            
            .save-status.error {
                background: #dc3545 !important;
                color: white !important;
            }
            
            /* Keyboard adjustments for touch devices */
            .keyboard-open .chat-modal-open .chat-container #chat-input-row {
                /* Input container remains pinned */
            }
            
            /* Accessibility improvements */
            .chat-modal-open .chat-container .message-actions button:focus {
                outline: 2px solid #35bd3e !important;
                outline-offset: 2px !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea:focus {
                outline: none !important; /* Custom focus styling via border and box-shadow */
            }
            
            /* Scrollbar styling for edit textarea */
            .chat-modal-open .chat-container .message-edit-textarea::-webkit-scrollbar {
                width: 6px !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.1) !important;
                border-radius: 3px !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea::-webkit-scrollbar-thumb {
                background: #35bd3e !important;
                border-radius: 3px !important;
            }
            
            .chat-modal-open .chat-container .message-edit-textarea::-webkit-scrollbar-thumb:hover {
                background: #1b7f26 !important;
            }
            
            /* Animation for smooth transitions */
            .chat-modal-open .chat-container .chat-message {
                animation: fadeInMessage 0.3s ease !important;
            }
            
            @keyframes fadeInMessage {
                from {
                    opacity: 0 !important;
                    transform: translateY(10px) !important;
                }
                to {
                    opacity: 1 !important;
                    transform: translateY(0) !important;
                }
            }
            
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .chat-modal-open .chat-container .message-actions button {
                    border: 1px solid currentColor !important;
                }
                
                .chat-modal-open .chat-container .message-edit-textarea {
                    border-width: 3px !important;
                }
                
                .chat-modal-open .chat-container .chat-message.editing {
                    border-left-width: 4px !important;
                }
            }
            
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .chat-modal-open .chat-container .chat-message,
                .chat-modal-open .chat-container .message-edit-container,
                .chat-modal-open .chat-container .message-actions,
                .chat-modal-open .chat-container .message-actions button {
                    transition: none !important;
                }
                
                .chat-modal-open .chat-container .typing-indicator {
                    animation: none !important;
                }
                
                .chat-modal-open .chat-container .chat-message {
                    animation: none !important;
                }
                
                .chat-modal-open .chat-container .regenerating-spinner {
                    animation: none !important;
                }
            }
            </style>
        `;
        $('head').append(css);
        console.log('Universal layout CSS with message editing added');
    }
    
    // Auto-save functionality
    function triggerAutoSave(delay = 2000) {
        if (!chatState.autoSaveEnabled) return;
        
        chatState.hasUnsavedChanges = true;
        updateHeaderWithUnsavedIndicator();
        
        // Debounced auto-save
        clearTimeout(chatState.autoSaveTimeout);
        chatState.autoSaveTimeout = setTimeout(() => {
            performAutoSave();
        }, delay);
    }
    
    function performAutoSave() {
        if (!chatState.hasUnsavedChanges) return;
        
        console.log('Performing auto-save...');
        showSaveStatus('saving');
        
        if (window.PMV_ConversationManager && window.PMV_ConversationManager.saveCurrentConversation) {
            // Use the existing save method but don't prompt for title
            const messages = collectEditableConversationHistory();
            if (messages.length === 0) {
                console.log('No messages to save');
                return;
            }
            
            // Auto-generate title if this is a new conversation
            const $modal = $('#png-modal').find('.png-modal-content');
            const characterData = $modal.data('characterData');
            const character = window.PMV_Chat ? window.PMV_Chat.extractCharacterInfo(characterData) : {};
            const characterName = character.name || 'AI';
            
            let title = `Chat with ${characterName}`;
            if (window.PMV_ConversationManager.currentConversationId) {
                // Keep existing title for updates
                const existingConv = window.PMV_ConversationManager.conversations.find(c => c.id == window.PMV_ConversationManager.currentConversationId);
                if (existingConv) {
                    title = existingConv.title;
                }
            } else {
                // Add timestamp for new conversations
                title += ` - ${new Date().toLocaleDateString()}`;
            }
            
            const conversationData = {
                character_id: window.PMV_ConversationManager.characterId,
                title: title,
                messages: messages
            };
            
            if (window.PMV_ConversationManager.currentConversationId) {
                conversationData.id = window.PMV_ConversationManager.currentConversationId;
            }
            
            // Save via AJAX directly to avoid user prompts
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_save_conversation',
                    conversation: JSON.stringify(conversationData),
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.PMV_ConversationManager.currentConversationId = response.data.id;
                        chatState.hasUnsavedChanges = false;
                        chatState.lastSaveTime = new Date();
                        updateHeaderWithSavedIndicator();
                        showSaveStatus('saved');
                        
                        // Refresh conversation list
                        if (window.PMV_ConversationManager.loadConversationList) {
                            window.PMV_ConversationManager.loadConversationList();
                        }
                        if (window.PMV_ConversationManager.loadConversationListMobile) {
                            window.PMV_ConversationManager.loadConversationListMobile();
                        }
                        
                        console.log('Auto-save successful');
                    } else {
                        showSaveStatus('error');
                        console.error('Auto-save failed:', response.data?.message);
                    }
                },
                error: function(xhr) {
                    showSaveStatus('error');
                    console.error('Auto-save error:', xhr.responseText);
                }
            });
        } else {
            showSaveStatus('error');
            console.warn('ConversationManager not available for auto-save');
        }
    }
    
    function showSaveStatus(status) {
        // Remove existing status
        $('.save-status').remove();
        
        let text = '';
        switch(status) {
            case 'saving':
                text = 'Saving...';
                break;
            case 'saved':
                text = 'Saved ✓';
                break;
            case 'error':
                text = 'Save Error ✗';
                break;
        }
        
        const $status = $(`<div class="save-status ${status}">${text}</div>`);
        $('body').append($status);
        
        // Auto-hide after delay
        if (status !== 'saving') {
            setTimeout(() => {
                $status.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        }
    }
    
    function updateHeaderWithUnsavedIndicator() {
        const $headerName = $('.chat-modal-name');
        if (!$headerName.text().includes('*')) {
            $headerName.text($headerName.text() + ' *').attr('title', 'Unsaved changes');
        }
    }
    
    function updateHeaderWithSavedIndicator() {
        const $headerName = $('.chat-modal-name');
        $headerName.text($headerName.text().replace(' *', '')).removeAttr('title');
    }
    
    // Message editing functionality
    function generateMessageId() {
        return 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    function createEditableMessage(content, messageClass, speakerName, messageId) {
        const isUser = messageClass === 'user';
        const isBot = messageClass === 'bot';
        const isError = messageClass === 'error';
        
        // Create action buttons based on message type
        let actionButtons = `
            <button class="edit-message-btn" title="Edit message">
                <span class="dashicons dashicons-edit"></span>
            </button>
            <button class="delete-message-btn" title="Delete message">
                <span class="dashicons dashicons-trash"></span>
            </button>
        `;
        
        // Add regenerate button for bot messages only
        if (isBot && !isError) {
            actionButtons = `
                <button class="edit-message-btn" title="Edit message">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button class="regenerate-message-btn" title="Regenerate response">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button class="delete-message-btn" title="Delete message">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            `;
        }
        
        const messageHtml = `
            <div class="chat-message ${messageClass}" data-message-id="${messageId}">
                <div class="message-header">
                    <strong class="speaker-name">${escapeHtml(speakerName)}:</strong>
                    <div class="message-actions">
                        ${actionButtons}
                    </div>
                </div>
                <div class="message-content">
                    <span class="chat-message-content-wrapper">${escapeHtml(content)}</span>
                    <div class="message-edit-container" style="display: none;">
                        <textarea class="message-edit-textarea" rows="3">${escapeHtml(content)}</textarea>
                        <div class="edit-actions">
                            <button class="save-edit-btn">Save</button>
                            <button class="cancel-edit-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        return messageHtml;
    }
    
    function addEditableMessage(content, messageClass, speakerName) {
        const messageId = generateMessageId();
        const messageHtml = createEditableMessage(content, messageClass, speakerName, messageId);
        
        $('#chat-history').append(messageHtml);
        
        // Trigger auto-save for new messages
        triggerAutoSave();
        
        setTimeout(() => {
            forceScrollToBottom();
        }, 50);
        
        return messageId;
    }
    
    function startEditingMessage($message) {
        if (editingState.currentlyEditing) {
            cancelMessageEdit($(editingState.currentlyEditing));
        }
        
        const $contentWrapper = $message.find('.chat-message-content-wrapper');
        const $editContainer = $message.find('.message-edit-container');
        const $textarea = $message.find('.message-edit-textarea');
        
        editingState.originalContent = $contentWrapper.text();
        editingState.currentlyEditing = $message[0];
        editingState.hasUnsavedChanges = false;
        
        $contentWrapper.hide();
        $editContainer.show();
        
        $textarea.focus();
        autoResizeTextarea($textarea[0]);
        
        $message.addClass('editing');
        $('.chat-container').addClass('message-editing-mode');
        
        updateEditingUI(true);
    }
    
    function saveMessageEdit($message) {
        const $textarea = $message.find('.message-edit-textarea');
        const $contentWrapper = $message.find('.chat-message-content-wrapper');
        const $editContainer = $message.find('.message-edit-container');
        
        const newContent = $textarea.val().trim();
        
        if (newContent === '') {
            alert('Message cannot be empty');
            return;
        }
        
        $contentWrapper.html(escapeHtml(newContent));
        
        $editContainer.hide();
        $contentWrapper.show();
        
        $message.removeClass('editing');
        $('.chat-container').removeClass('message-editing-mode');
        editingState.currentlyEditing = null;
        editingState.originalContent = null;
        editingState.hasUnsavedChanges = false;
        
        updateEditingUI(false);
        
        // Trigger auto-save after editing
        triggerAutoSave(1000); // Shorter delay for edits
        
        setTimeout(() => {
            forceScrollToBottom();
        }, 50);
        
        // Trigger custom event
        $(document).trigger('message:edited', [$message]);
        
        console.log('Message edited and will be auto-saved');
    }
    
    function cancelMessageEdit($message) {
        const $contentWrapper = $message.find('.chat-message-content-wrapper');
        const $editContainer = $message.find('.message-edit-container');
        const $textarea = $message.find('.message-edit-textarea');
        
        if (editingState.originalContent !== null) {
            $textarea.val(editingState.originalContent);
        }
        
        $editContainer.hide();
        $contentWrapper.show();
        
        $message.removeClass('editing');
        $('.chat-container').removeClass('message-editing-mode');
        editingState.currentlyEditing = null;
        editingState.originalContent = null;
        editingState.hasUnsavedChanges = false;
        
        updateEditingUI(false);
    }
    
    function deleteMessage($message) {
        const messageContent = $message.find('.chat-message-content-wrapper').text();
        const confirmDelete = confirm(`Are you sure you want to delete this message?\n\n"${messageContent.substring(0, 100)}${messageContent.length > 100 ? '...' : ''}"`);
        
        if (confirmDelete) {
            if (editingState.currentlyEditing === $message[0]) {
                cancelMessageEdit($message);
            }
            
            $message.fadeOut(200, function() {
                $(this).remove();
                
                // Trigger auto-save after deletion
                triggerAutoSave(1000);
                
                // Trigger custom event
                $(document).trigger('message:deleted', [$message]);
            });
        }
    }
    
    function regenerateMessage($message) {
        if (editingState.currentlyEditing) {
            cancelMessageEdit($(editingState.currentlyEditing));
        }
        
        // Check if this is a bot message
        if (!$message.hasClass('bot')) {
            alert('Can only regenerate bot responses');
            return;
        }
        
        // Check if we have access to the chat functionality
        if (!window.sendChatMessage || !window.PMV_Chat) {
            alert('Regenerate functionality not available');
            return;
        }
        
        // Get the conversation history up to this message
        const allMessages = [];
        $('.chat-message').each(function() {
            const $msg = $(this);
            if ($msg[0] === $message[0]) {
                return false; // Stop before the message we're regenerating
            }
            
            if ($msg.hasClass('error') || $msg.hasClass('typing-indicator')) {
                return; // Skip error messages and typing indicators
            }
            
            let role = 'assistant';
            let content = $msg.find('.chat-message-content-wrapper').text();
            
            if ($msg.hasClass('user')) {
                role = 'user';
            }
            
            allMessages.push({
                role: role,
                content: content.trim()
            });
        });
        
        // Get the last user message
        const lastUserMessage = allMessages.filter(m => m.role === 'user').pop();
        if (!lastUserMessage) {
            alert('No user message found to regenerate from');
            return;
        }
        
        // Add regenerating state
        $message.addClass('regenerating');
        const $contentWrapper = $message.find('.chat-message-content-wrapper');
        
        // Add regenerating indicator
        const $regeneratingIndicator = $(`
            <div class="regenerating-indicator">
                <span class="regenerating-spinner"></span>
                Regenerating response...
            </div>
        `);
        $message.find('.message-content').append($regeneratingIndicator);
        
        // Disable message actions
        $message.find('.message-actions button').prop('disabled', true);
        
        // Get character data and conversation state
        const $modal = $('#png-modal').find('.png-modal-content');
        const characterData = $modal.data('characterData');
        const botId = $modal.data('botId');
        
        // Remove the conversation history starting from this message
        const conversationHistory = allMessages.slice(0, -1); // Remove the user message that triggered this response
        
        // Prepare payload for regeneration
        let characterDataStr;
        try {
            characterDataStr = typeof characterData === 'object' ?
                JSON.stringify(characterData) : characterData;
        } catch (e) {
            console.error('Error stringifying character data:', e);
            handleRegenerateError($message, 'Failed to process character data');
            return;
        }
        
        const ajaxData = {
            action: 'start_character_chat',
            character_data: characterDataStr,
            user_message: lastUserMessage.content,
            bot_id: botId || 'default_bot',
            nonce: pmv_ajax_object.nonce
        };
        
        // Add conversation history excluding the current response
        if (conversationHistory.length > 0) {
            ajaxData.conversation_history = JSON.stringify(conversationHistory);
        }
        
        console.log('Regenerating message with payload:', {
            userMessage: lastUserMessage.content,
            historyLength: conversationHistory.length
        });
        
        // Make AJAX request
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',  
            data: ajaxData,
            success: function(response) {
                console.log('Regenerate API response:', response);
                
                if (response.success && response.data && response.data.choices && response.data.choices[0]) {
                    const newResponse = response.data.choices[0].message.content;
                    const characterName = escapeHtml(response.data.character?.name || characterData?.name || characterData?.data?.name || 'AI');
                    
                    // Update the message content
                    $contentWrapper.html(escapeHtml(newResponse));
                    
                    // Update the textarea for editing as well
                    $message.find('.message-edit-textarea').val(newResponse);
                    
                    // Remove regenerating state
                    $message.removeClass('regenerating');
                    $regeneratingIndicator.remove();
                    $message.find('.message-actions button').prop('disabled', false);
                    
                    // Trigger auto-save
                    triggerAutoSave(1000);
                    
                    // Scroll to show the updated message
                    setTimeout(() => {
                        forceScrollToBottom();
                    }, 50);
                    
                    // Trigger custom event
                    $(document).trigger('message:regenerated', [$message, newResponse]);
                    
                    console.log('Message regenerated successfully');
                } else {
                    handleRegenerateError($message, response.data?.message || 'API request failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Regenerate AJAX error:', {xhr, status, error});
                handleRegenerateError($message, error || 'Connection error');
            }
        });
    }
    
    function handleRegenerateError($message, error) {
        // Remove regenerating state
        $message.removeClass('regenerating');
        $message.find('.regenerating-indicator').remove();
        $message.find('.message-actions button').prop('disabled', false);
        
        // Show error message
        alert('Failed to regenerate message: ' + error);
        
        console.error('Regenerate error:', error);
    }
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        const newHeight = Math.min(Math.max(textarea.scrollHeight, 60), 300);
        textarea.style.height = newHeight + 'px';
    }
    
    function updateEditingUI(isEditing) {
        const $chatContainer = $('.chat-container');
        const $sendButton = $('#send-chat');
        const $chatInput = $('#chat-input');
        
        if (isEditing) {
            $chatContainer.addClass('message-editing-mode');
            $sendButton.prop('disabled', true).text('Editing...');
            $chatInput.prop('disabled', true).attr('placeholder', 'Finish editing to continue...');
        } else {
            $chatContainer.removeClass('message-editing-mode');
            $sendButton.prop('disabled', false).text('Send');
            $chatInput.prop('disabled', false).attr('placeholder', 'Type your message...');
        }
    }
    
    function markConversationAsModified() {
        chatState.hasUnsavedChanges = true;
        updateHeaderWithUnsavedIndicator();
    }
    
    function clearConversationModified() {
        chatState.hasUnsavedChanges = false;
        updateHeaderWithSavedIndicator();
    }
    
    function collectEditableConversationHistory() {
        const messages = [];
        
        $('.chat-message').each(function() {
            const $msg = $(this);
            
            if ($msg.hasClass('error') || $msg.hasClass('typing-indicator')) {
                return;
            }
            
            let role = 'assistant';
            let content = $msg.find('.chat-message-content-wrapper').text();
            
            if ($msg.hasClass('user')) {
                role = 'user';
            }
            
            messages.push({
                role: role,
                content: content.trim()
            });
        });
        
        return messages;
    }
    
    function convertExistingMessages() {
        $('.chat-message').each(function() {
            const $message = $(this);
            
            if ($message.find('.message-actions').length > 0 || $message.hasClass('typing-indicator')) {
                return;
            }
            
            const $content = $message.find('.chat-message-content-wrapper');
            const content = $content.text();
            const messageClass = $message.hasClass('user') ? 'user' : ($message.hasClass('bot') ? 'bot' : 'error');
            const speakerName = $message.find('strong').first().text().replace(':', '');
            const messageId = generateMessageId();
            
            $message.attr('data-message-id', messageId);
            
            // Create appropriate action buttons
            const isBot = messageClass === 'bot';
            const isError = messageClass === 'error';
            
            let actionsHtml = `
                <div class="message-actions">
                    <button class="edit-message-btn" title="Edit message">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
            `;
            
            // Add regenerate button for bot messages only
            if (isBot && !isError) {
                actionsHtml += `
                    <button class="regenerate-message-btn" title="Regenerate response">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                `;
            }
            
            actionsHtml += `
                    <button class="delete-message-btn" title="Delete message">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            `;
            
            const $speaker = $message.find('strong').first();
            $speaker.wrap('<div class="message-header"></div>');
            $speaker.parent().append(actionsHtml);
            
            $content.wrap('<div class="message-content"></div>');
            const editContainer = `
                <div class="message-edit-container" style="display: none;">
                    <textarea class="message-edit-textarea" rows="3">${escapeHtml(content)}</textarea>
                    <div class="edit-actions">
                        <button class="save-edit-btn">Save</button>
                        <button class="cancel-edit-btn">Cancel</button>
                    </div>
                </div>
            `;
            $content.parent().append(editContainer);
        });
    }
    
    // Setup event handlers for message editing
    function setupMessageEditingHandlers() {
        $(document)
            .on('click', '.edit-message-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $message = $(this).closest('.chat-message');
                startEditingMessage($message);
            })
            .on('click', '.regenerate-message-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $message = $(this).closest('.chat-message');
                regenerateMessage($message);
            })
            .on('click', '.delete-message-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $message = $(this).closest('.chat-message');
                deleteMessage($message);
            })
            .on('click', '.save-edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $message = $(this).closest('.chat-message');
                saveMessageEdit($message);
            })
            .on('click', '.cancel-edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $message = $(this).closest('.chat-message');
                cancelMessageEdit($message);
            })
            .on('input', '.message-edit-textarea', function() {
                autoResizeTextarea(this);
                editingState.hasUnsavedChanges = true;
            })
            .on('keydown', '.message-edit-textarea', function(e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    const $message = $(this).closest('.chat-message');
                    saveMessageEdit($message);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    const $message = $(this).closest('.chat-message');
                    cancelMessageEdit($message);
                }
            });
    }
    
    // Apply universal layout structure with desktop fixes
    function setupUniversalLayout() {
        console.log('Setting up universal layout with editing support');
        
        const $chatMain = $('.chat-main');
        const $chatHistory = $('#chat-history');
        const $inputRow = $('#chat-input-row');
        const $chatHeader = $('#chat-header');
        
        if (!$chatMain.length || !$chatHistory.length || !$inputRow.length) {
            console.warn('Required elements not found for universal layout');
            return;
        }
        
        // Clear any conflicting styles
        $chatMain.removeAttr('style');
        $chatHistory.removeAttr('style');
        $inputRow.removeAttr('style');
        $chatHeader.removeAttr('style');
        
        // Ensure input wrapper exists with proper structure
        ensureInputWrapperStructure();
        
        // Apply desktop-specific fixes if needed
        if (isDesktop()) {
            applyDesktopSpecificFixes();
        }
        
        // Convert existing messages to editable format
        setTimeout(() => {
            convertExistingMessages();
        }, 100);
        
        chatState.isLayoutApplied = true;
        
        // Immediate scroll to bottom
        setTimeout(() => {
            forceScrollToBottom();
        }, 100);
        
        console.log('Universal layout setup complete with editing support');
    }
    
    // Ensure proper input wrapper structure  
    function ensureInputWrapperStructure() {
        const $inputRow = $('#chat-input-row');
        const $input = $('#chat-input');
        let $sendButton = $('#send-chat, .chat-send-button');
        
        console.log('Ensuring input wrapper structure...');
        
        // Remove existing wrapper if malformed
        if ($inputRow.find('.input-wrapper').length > 0) {
            const $wrapper = $inputRow.find('.input-wrapper');
            const $children = $wrapper.children();
            $wrapper.replaceWith($children);
        }
        
        // Ensure send button exists
        if (!$sendButton.length) {
            console.log('Creating send button...');
            $sendButton = $(`
                <button id="send-chat" class="chat-send-button" type="button">
                    Send
                </button>
            `);
            
            // Add click handler
            $sendButton.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Send button clicked');
                if (window.sendChatMessage) {
                    window.sendChatMessage();
                }
            });
        }
        
        // Create proper wrapper structure
        const $wrapper = $('<div class="input-wrapper"></div>');
        $inputRow.append($wrapper);
        $wrapper.append($input);
        $wrapper.append($sendButton);
        
        console.log('Input wrapper structure created');
    }
    
    // Apply desktop-specific layout fixes
    function applyDesktopSpecificFixes() {
        console.log('🖥️ Applying desktop-specific fixes...');
        
        const $inputRow = $('#chat-input-row');
        const $wrapper = $('.input-wrapper');
        const $input = $('#chat-input');
        const $sendButton = $('#send-chat, .chat-send-button');
        
        // Force proper flex behavior
        $wrapper.css({
            'display': 'flex',
            'flex-direction': 'row',
            'align-items': 'flex-end',
            'gap': '12px',
            'width': '100%',
            'box-sizing': 'border-box',
            'justify-content': 'space-between'
        });
        
        // Input constraints
        $input.css({
            'flex': '1 1 auto',
            'max-width': 'calc(100% - 140px)',
            'width': 'auto',
            'box-sizing': 'border-box'
        });
        
        // Button constraints
        $sendButton.css({
            'flex': '0 0 auto',
            'flex-shrink': '0',
            'min-width': '100px',
            'max-width': '140px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'position': 'relative',
            'z-index': '1000'
        });
        
        // Container fixes
        $inputRow.css({
            'width': '100%',
            'max-width': 'none',
            'position': 'relative',
            'overflow': 'visible'
        });
        
        // Verify button is visible
        setTimeout(() => {
            verifyButtonVisibility($sendButton);
        }, 100);
        
        console.log('✅ Desktop fixes applied');
    }
    
    // Verify button visibility and apply emergency fix if needed
    function verifyButtonVisibility($sendButton) {
        if (!$sendButton.length) return;
        
        const buttonRect = $sendButton[0].getBoundingClientRect();
        const windowWidth = window.innerWidth;
        
        console.log('Button visibility check:', {
            left: buttonRect.left,
            right: buttonRect.right,
            windowWidth: windowWidth,
            isVisible: buttonRect.right <= windowWidth && buttonRect.left >= 0
        });
        
        if (buttonRect.right > windowWidth || buttonRect.left < 0) {
            console.log('⚠️ Button off-screen, applying emergency fix...');
            applyEmergencyDesktopFix();
        }
    }
    
    // Emergency desktop fix for off-screen buttons
    function applyEmergencyDesktopFix() {
        console.log('🚨 Applying emergency desktop fix...');
        
        const $inputRow = $('#chat-input-row');
        const $input = $('#chat-input');
        
        // Remove existing send buttons
        $('#send-chat, .chat-send-button').remove();
        
        // Create emergency button with absolute positioning
        const $emergencyButton = $(`
            <button id="send-chat-emergency" class="chat-send-button emergency-desktop" type="button">
                Send
            </button>
        `);
        
        $emergencyButton.css({
            'position': 'absolute',
            'right': '16px',
            'bottom': '16px',
            'background': '#4a90e2',
            'color': 'white',
            'border': 'none',
            'padding': '12px 20px',
            'border-radius': '8px',
            'cursor': 'pointer',
            'font-size': '16px',
            'font-weight': '600',
            'z-index': '99999',
            'min-width': '80px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        // Add click handler
        $emergencyButton.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Emergency send button clicked');
            if (window.sendChatMessage) {
                window.sendChatMessage();
            }
        });
        
        // Append to input row with relative positioning
        $inputRow.css('position', 'relative').append($emergencyButton);
        
        // Adjust input to make room
        $input.css({
            'width': 'calc(100% - 120px)',
            'max-width': 'calc(100% - 120px)',
            'padding-right': '16px'
        });
        
        console.log('🚨 Emergency desktop fix applied');
    }
    
    // Enhanced textarea resize handler
    function handleTextareaResize() {
        $(document).off('input.universalTextarea focus.universalTextarea');
        
        $(document).on('input.universalTextarea', '#chat-input', function() {
            const textarea = this;
            
            // Reset height to calculate scroll height
            textarea.style.height = 'auto';
            
            // Calculate new height within limits
            const newHeight = Math.min(
                Math.max(textarea.scrollHeight, CHAT_CONFIG.minInputHeight),
                CHAT_CONFIG.maxInputHeight
            );
            
            textarea.style.height = newHeight + 'px';
            chatState.currentInputHeight = newHeight;
            
            // Scroll to bottom after resize
            setTimeout(forceScrollToBottom, 50);
        });
        
        $(document).on('focus.universalTextarea', '#chat-input', function() {
            setTimeout(forceScrollToBottom, 300);
        });
    }
    
    // Enhanced keyboard detection
    function handleMobileKeyboard() {
        if (!isTouchDevice()) return;
        
        const initialHeight = window.innerHeight;
        chatState.originalViewportHeight = initialHeight;
        
        function onViewportChange() {
            const currentHeight = window.innerHeight;
            const heightDiff = chatState.originalViewportHeight - currentHeight;
            
            const wasKeyboardOpen = chatState.keyboardOpen;
            chatState.keyboardOpen = heightDiff > 150;
            
            if (wasKeyboardOpen !== chatState.keyboardOpen) {
                $('body').toggleClass('keyboard-open', chatState.keyboardOpen);
                console.log('Keyboard state changed:', chatState.keyboardOpen);
                
                setTimeout(forceScrollToBottom, 150);
            }
        }
        
        // Use visual viewport API if available
        if ('visualViewport' in window) {
            window.visualViewport.addEventListener('resize', onViewportChange);
        } else {
            $(window).on('resize.mobileKeyboard', function() {
                setTimeout(onViewportChange, 100);
            });
        }
    }
    
    // Reliable scroll to bottom function
    function forceScrollToBottom() {
        const $chatHistory = $('#chat-history');
        if (!$chatHistory.length) return;
        
        const chatHistory = $chatHistory[0];
        
        requestAnimationFrame(() => {
            chatHistory.scrollTop = chatHistory.scrollHeight;
            
            // Double-check after a brief delay
            setTimeout(() => {
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }, 50);
        });
        
        console.log('Scroll to bottom executed');
    }
    
    // Enhanced message observer
    function setupMessageObserver() {
        const chatHistory = document.getElementById('chat-history');
        if (!chatHistory) return;
        
        const observer = new MutationObserver(function(mutations) {
            let hasNewMessage = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    const hasMessage = Array.from(mutation.addedNodes).some(node => 
                        node.nodeType === Node.ELEMENT_NODE && 
                        (node.classList.contains('chat-message') || 
                         node.classList.contains('typing-indicator'))
                    );
                    if (hasMessage) hasNewMessage = true;
                }
            });
            
            if (hasNewMessage) {
                console.log('New message detected');
                setTimeout(() => {
                    convertExistingMessages();
                    forceScrollToBottom();
                    // Trigger auto-save for new messages
                    triggerAutoSave();
                }, 100);
            }
        });
        
        observer.observe(chatHistory, {
            childList: true,
            subtree: true
        });
        
        console.log('Message observer setup complete');
    }
    
    // Create sidebar overlay
    function createMobileOverlay() {
        if ($('.pmv-mobile-overlay').length) return;
        $('body').append('<div class="pmv-mobile-overlay"></div>');
        $('.pmv-mobile-overlay').on('click', closeMobileMenu);
    }
    
    // Create sidebar with enhanced conversation loading and save button
    function createMobileSidebar() {
        if ($('.pmv-mobile-sidebar').length) return;
        
        const sidebar = `
            <div class="pmv-mobile-sidebar">
                <div class="mobile-sidebar-content">
                    <div class="mobile-sidebar-header">
                        <h3>Conversations</h3>
                        <button class="mobile-close-btn">×</button>
                    </div>
                    <button class="mobile-new-chat-btn">🔄 New Chat</button>
                    <button class="mobile-save-chat-btn">💾 Save Conversation</button>
                    <div class="mobile-conversations-list">
                        <div class="mobile-loading-container">Loading conversations...</div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(sidebar);
        
        // Setup sidebar event handlers
        $('.mobile-close-btn').on('click', closeMobileMenu);
        
        $('.mobile-new-chat-btn').on('click', function() {
            console.log('New chat button clicked');
            
            // Check for unsaved changes
            if (chatState.hasUnsavedChanges) {
                const confirmNew = confirm('You have unsaved changes. Starting a new conversation will lose these changes. Continue?');
                if (!confirmNew) {
                    return;
                }
            }
            
            if (window.PMV_ConversationManager && window.PMV_ConversationManager.startNewConversation) {
                window.PMV_ConversationManager.startNewConversation();
            } else {
                console.log('Starting new conversation by clearing current chat');
                $('#chat-history').empty();
                if ($('#png-modal').find('.png-modal-content').data('characterData')) {
                    const characterData = $('#png-modal').find('.png-modal-content').data('characterData');
                    const character = window.PMV_Chat ? window.PMV_Chat.extractCharacterInfo(characterData) : {name: 'AI', first_mes: 'Hello!'};
                    
                    if (window.PMV_MobileChat && window.PMV_MobileChat.addEditableMessage) {
                        window.PMV_MobileChat.addEditableMessage(
                            character.first_mes || `Hello, I am ${character.name}. How can I help you today?`,
                            'bot',
                            character.name
                        );
                    } else {
                        $('#chat-history').append(`
                            <div class="chat-message bot">
                                <strong>${escapeHtml(character.name)}:</strong>
                                <span class="chat-message-content-wrapper">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                            </div>
                        `);
                    }
                }
                $('#png-modal').find('.png-modal-content').data('isNewConversation', true);
                clearConversationModified();
            }
            closeMobileMenu();
        });
        
        $('.mobile-save-chat-btn').on('click', function() {
            console.log('Save chat button clicked');
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            // Disable button during save
            $btn.prop('disabled', true).text('Saving...');
            
            if (window.PMV_ConversationManager && window.PMV_ConversationManager.saveCurrentConversation) {
                // Use manual save which prompts for title
                window.PMV_ConversationManager.saveCurrentConversation();
                
                // Re-enable button after a short delay
                setTimeout(() => {
                    $btn.prop('disabled', false).text(originalText);
                }, 2000);
            } else {
                // Fallback: trigger auto-save
                performAutoSave();
                
                setTimeout(() => {
                    $btn.prop('disabled', false).text(originalText);
                }, 2000);
            }
            
            // Don't close menu so user can see the result
        });
    }
    
    // Open mobile menu
    function openMobileMenu() {
        console.log('Opening menu');
        chatState.menuOpen = true;
        $('.pmv-mobile-overlay').show().addClass('open');
        $('.pmv-mobile-sidebar').show().addClass('open');
        $('body').addClass('mobile-menu-open');
        
        // Load conversations when menu opens
        loadConversations();
    }
    
    // Close mobile menu
    function closeMobileMenu() {
        console.log('Closing menu');
        chatState.menuOpen = false;
        $('.pmv-mobile-sidebar').removeClass('open');
        $('.pmv-mobile-overlay').removeClass('open');
        setTimeout(() => {
            $('.pmv-mobile-overlay').hide();
            $('.pmv-mobile-sidebar').hide();
        }, 300);
        $('body').removeClass('mobile-menu-open');
    }
    
    // Enhanced conversation loading
    function loadConversations() {
        const $container = $('.mobile-conversations-list');
        
        // Reset retry count
        chatState.retryCount = 0;
        
        console.log('=== Mobile Conversation Loading ===');
        console.log('Device info:', {
            userAgent: navigator.userAgent,
            screen: window.innerWidth + 'x' + window.innerHeight,
            touch: isTouchDevice(),
            small: isSmallScreen()
        });
        
        // Show loading immediately
        $container.html('<div class="mobile-loading-container">🔄 Loading conversations...</div>');
        
        // Try multiple methods in parallel for better success rate
        attemptAllMethods();
    }
    
    function attemptAllMethods() {
        const $container = $('.mobile-conversations-list');
        let methodsCompleted = 0;
        let successfulMethod = null;
        let conversationsFound = false;
        
        const totalMethods = 3;
        
        function handleMethodComplete(method, success, data = null) {
            methodsCompleted++;
            console.log(`Method ${method} completed:`, {success, data});
            
            if (success && !conversationsFound) {
                conversationsFound = true;
                successfulMethod = method;
                console.log(`✅ Success with method: ${method}`);
                
                if (data && data.length > 0) {
                    displayConversations(data);
                } else {
                    displayNoConversations();
                }
                return;
            }
            
            // All methods completed without success
            if (methodsCompleted >= totalMethods && !conversationsFound) {
                console.log('❌ All methods failed');
                displayConversationsError(true);
            }
        }
        
        // Method 1: Direct AJAX (most reliable)
        console.log('🔄 Trying Method 1: Direct AJAX');
        if (typeof pmv_ajax_object !== 'undefined' && pmv_ajax_object.ajax_url) {
            
            // Get character ID from current chat context
            let characterId = 'default';
            try {
                const $modal = $('#png-modal').find('.png-modal-content');
                const characterData = $modal.data('characterData');
                if (characterData) {
                    const character = window.PMV_Chat ? window.PMV_Chat.extractCharacterInfo(characterData) : null;
                    if (character && character.name) {
                        characterId = 'char_' + character.name.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    }
                }
                
                // Also try from conversation manager
                if (window.PMV_ConversationManager && window.PMV_ConversationManager.characterId) {
                    characterId = window.PMV_ConversationManager.characterId;
                }
            } catch (e) {
                console.warn('Error getting character ID:', e);
            }
            
            console.log('Using character ID:', characterId);
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                timeout: 8000,
                data: {
                    action: 'pmv_get_conversations',
                    character_id: characterId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    console.log('Direct AJAX success:', response);
                    if (response && response.success && Array.isArray(response.data)) {
                        handleMethodComplete('Direct AJAX', true, response.data);
                    } else {
                        handleMethodComplete('Direct AJAX', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Direct AJAX failed:', {status, error, responseText: xhr.responseText});
                    handleMethodComplete('Direct AJAX', false);
                }
            });
        } else {
            handleMethodComplete('Direct AJAX', false);
        }
        
        // Method 2: ConversationManager mobile method
        console.log('🔄 Trying Method 2: ConversationManager Mobile');
        setTimeout(() => {
            try {
                if (window.PMV_ConversationManager && typeof window.PMV_ConversationManager.loadConversationListMobile === 'function') {
                    
                    // Hook into the manager's success/error handling
                    const originalSuccess = window.PMV_ConversationManager.renderConversationListMobile;
                    const originalError = window.PMV_ConversationManager.showMobileConversationError;
                    
                    // Temporarily override methods to capture results
                    window.PMV_ConversationManager.renderConversationListMobile = function() {
                        // Call original method
                        if (originalSuccess) originalSuccess.call(this);
                        
                        // Check if conversations were loaded
                        setTimeout(() => {
                            if (this.conversations && this.conversations.length >= 0) {
                                handleMethodComplete('ConversationManager Mobile', true, this.conversations);
                            } else {
                                handleMethodComplete('ConversationManager Mobile', false);
                            }
                            
                            // Restore original method
                            this.renderConversationListMobile = originalSuccess;
                        }, 500);
                    };
                    
                    window.PMV_ConversationManager.showMobileConversationError = function(error) {
                        console.log('ConversationManager mobile error:', error);
                        handleMethodComplete('ConversationManager Mobile', false);
                        
                        // Restore original method
                        this.showMobileConversationError = originalError;
                    };
                    
                    // Call the method
                    window.PMV_ConversationManager.loadConversationListMobile();
                    
                } else {
                    handleMethodComplete('ConversationManager Mobile', false);
                }
            } catch (e) {
                console.error('ConversationManager mobile method error:', e);
                handleMethodComplete('ConversationManager Mobile', false);
            }
        }, 100);
        
        // Method 3: ConversationManager regular method
        console.log('🔄 Trying Method 3: ConversationManager Regular');
        setTimeout(() => {
            try {
                if (window.PMV_ConversationManager && typeof window.PMV_ConversationManager.loadConversationList === 'function') {
                    
                    const beforeConversations = window.PMV_ConversationManager.conversations ? [...window.PMV_ConversationManager.conversations] : [];
                    
                    // Call the method
                    window.PMV_ConversationManager.loadConversationList();
                    
                    // Check for changes
                    const checkForChanges = (attempts = 0) => {
                        setTimeout(() => {
                            const afterConversations = window.PMV_ConversationManager.conversations || [];
                            
                            if (JSON.stringify(afterConversations) !== JSON.stringify(beforeConversations)) {
                                console.log('ConversationManager regular method succeeded');
                                handleMethodComplete('ConversationManager Regular', true, afterConversations);
                            } else if (attempts < 8) {
                                checkForChanges(attempts + 1);
                            } else {
                                console.log('ConversationManager regular method timeout');
                                handleMethodComplete('ConversationManager Regular', false);
                            }
                        }, 400);
                    };
                    
                    checkForChanges();
                    
                } else {
                    handleMethodComplete('ConversationManager Regular', false);
                }
            } catch (e) {
                console.error('ConversationManager regular method error:', e);
                handleMethodComplete('ConversationManager Regular', false);
            }
        }, 200);
    }
    
    // Display conversations in sidebar
    function displayConversations(conversations) {
        const $container = $('.mobile-conversations-list');
        
        if (!conversations || conversations.length === 0) {
            displayNoConversations();
            return;
        }
        
        console.log('Displaying', conversations.length, 'conversations');
        
        let html = '';
        conversations.forEach(conv => {
            const title = conv.title || conv.character_name || conv.name || 'Unnamed Chat';
            const preview = conv.preview || conv.last_message || conv.description || 'No messages';
            const date = conv.date || conv.updated_at || conv.created_at || '';
            const id = conv.id || conv.conversation_id || conv.uuid;
            
            // Format date if present
            let formattedDate = '';
            if (date) {
                try {
                    const dateObj = new Date(date);
                    formattedDate = dateObj.toLocaleDateString();
                } catch (e) {
                    formattedDate = String(date);
                }
            }
            
            html += `
                <div class="conversation-item" data-conversation-id="${escapeHtml(id)}">
                    <div class="conversation-title">${escapeHtml(title)}</div>
                    <div class="conversation-preview">${escapeHtml(preview)}</div>
                    ${formattedDate ? `<div class="conversation-date">${escapeHtml(formattedDate)}</div>` : ''}
                </div>
            `;
        });
        
        $container.html(html);
        
        // Reset retry count on success
        chatState.retryCount = 0;
        chatState.conversationsLoaded = true;
    }
    
    // Display when no conversations exist but system is working
    function displayNoConversations() {
        $('.mobile-conversations-list').html(`
            <div class="mobile-loading-container">
                No conversations yet.<br>
                <small>Start chatting to create your first conversation!</small>
                <br><br>
                <button class="retry-button" onclick="window.PMV_MobileChat.retryLoadConversations()">
                    🔄 Refresh
                </button>
            </div>
        `);
        chatState.conversationsLoaded = true;
    }
    
    // Display error state with retry option
    function displayConversationsError(showRetry = true) {
        const retryButton = showRetry ? `
            <button class="retry-button" onclick="window.PMV_MobileChat.retryLoadConversations()">
                🔄 Retry Loading
            </button>
        ` : '';
        
        $('.mobile-conversations-list').html(`
            <div class="mobile-loading-container">
                ⚠️ Failed to load conversations.<br>
                <small>Check your connection and try again.</small>
                <br>
                ${retryButton}
                <br><br>
                <button class="retry-button" onclick="window.PMV_MobileChat.debugConversationSystem()">
                    🔍 Debug Info
                </button>
            </div>
        `);
    }
    
    // Escape HTML function
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
    
    // Setup event handlers with maximum specificity
    function setupEventHandlers() {
        // Remove all existing handlers first
        $(document).off('click.universalChat');
        
        $(document)
            .on('click.universalChat', '#mobile-menu-toggle, .mobile-menu-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Menu toggle clicked (universal handler)');
                openMobileMenu();
            })
            .on('click.universalChat', '.mobile-close-btn, .pmv-mobile-overlay', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMobileMenu();
            })
            .on('click.universalChat', '.conversation-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const conversationId = $(this).data('conversation-id') || $(this).attr('data-conversation-id');
                console.log('Conversation item clicked:', conversationId);
                
                // Check for unsaved changes before loading new conversation
                if (chatState.hasUnsavedChanges) {
                    const confirmLoad = confirm('You have unsaved changes. Loading a conversation will lose these changes. Continue?');
                    if (!confirmLoad) {
                        return;
                    }
                }
                
                if (conversationId) {
                    if (window.PMV_ConversationManager && window.PMV_ConversationManager.loadConversation) {
                        console.log('Loading conversation via ConversationManager.loadConversation()');
                        window.PMV_ConversationManager.loadConversation(conversationId);
                    } else if (window.PMV_Chat?.loadConversationIntoChat) {
                        console.log('Loading conversation via PMV_Chat');
                        // Try to load from localStorage or other source
                        try {
                            const stored = localStorage.getItem('pmv_conversation_' + conversationId);
                            if (stored) {
                                const conversationData = JSON.parse(stored);
                                window.PMV_Chat.loadConversationIntoChat(conversationData);
                            } else {
                                console.warn('Conversation not found in localStorage');
                            }
                        } catch (e) {
                            console.error('Error loading conversation:', e);
                        }
                    } else {
                        console.warn('No conversation loading method available');
                    }
                    closeMobileMenu();
                } else {
                    console.warn('No conversation ID found');
                }
            })
            .on('click.universalChat', '.retry-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Retry button clicked');
                window.PMV_MobileChat.retryLoadConversations();
            });
        
        // Additional handler specifically for menu button with higher specificity
        $('body').on('click.menuForce', '[id="mobile-menu-toggle"], .mobile-menu-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Menu button clicked (force handler)');
            openMobileMenu();
        });
        
        // Setup message editing handlers
        setupMessageEditingHandlers();
    }
    
    // Initialize universal system with message editing
    function initializeUniversalSystem() {
        if (chatState.initialized) {
            return;
        }
        
        console.log('Initializing universal chat system with message editing...');
        
        // Add universal CSS with editing support
        addUniversalLayoutCSS();
        
        // Setup components
        handleTextareaResize();
        setupEventHandlers();
        handleMobileKeyboard();
        createMobileOverlay();
        createMobileSidebar();
        
        chatState.initialized = true;
        console.log('Universal chat system with message editing initialized');
    }
    
    // Apply universal layout when chat starts - with message editing
    function applyUniversalLayoutToChat() {
        if (!checkIfInChat()) return;
        
        console.log('Applying universal layout with editing to existing chat');
        
        // Wait for chat elements to exist
        const checkForChatElements = () => {
            const $chatContainer = $('.chat-container');
            const $chatHistory = $('#chat-history');
            const $inputRow = $('#chat-input-row');
            
            if ($chatContainer.length && $chatHistory.length && $inputRow.length) {
                setupUniversalLayout();
                setupMessageObserver();
                
                // Enhanced button fixing with desktop support
                setTimeout(() => {
                    forceButtonVisibility();
                    if (isDesktop()) {
                        // Additional desktop-specific button fixes
                        ensureDesktopButtonVisibility();
                    }
                    forceScrollToBottom();
                }, 200);
            } else {
                setTimeout(checkForChatElements, 100);
            }
        };
        
        checkForChatElements();
    }
    
    // Enhanced desktop button visibility check
    function ensureDesktopButtonVisibility() {
        console.log('🖥️ Ensuring desktop button visibility...');
        
        const $sendButton = $('#send-chat, .chat-send-button');
        
        if (!$sendButton.length) {
            console.log('❌ Send button not found, creating emergency button');
            createEmergencyDesktopButton();
            return;
        }
        
        // Check if button is visible
        setTimeout(() => {
            const buttonRect = $sendButton[0].getBoundingClientRect();
            const windowWidth = window.innerWidth;
            
            if (buttonRect.right > windowWidth || buttonRect.left < 0) {
                console.log('❌ Button off-screen on desktop, fixing...');
                fixOffScreenDesktopButton($sendButton);
            } else {
                console.log('✅ Desktop button is visible');
            }
        }, 100);
    }
    
    // Create emergency desktop button
    function createEmergencyDesktopButton() {
        const $inputRow = $('#chat-input-row');
        if (!$inputRow.length) return;
        
        const $emergencyButton = $(`
            <button id="send-chat-emergency" class="chat-send-button emergency-desktop" type="button">
                Send
            </button>
        `);
        
        $emergencyButton.css({
            'position': 'absolute',
            'right': '16px',
            'bottom': '16px',
            'background': '#4a90e2',
            'color': 'white',
            'border': 'none',
            'padding': '12px 20px',
            'border-radius': '8px',
            'cursor': 'pointer',
            'font-size': '16px',
            'font-weight': '600',
            'z-index': '99999',
            'min-width': '80px'
        });
        
        $emergencyButton.on('click', function(e) {
            e.preventDefault();
            console.log('Emergency desktop button clicked');
            if (window.sendChatMessage) {
                window.sendChatMessage();
            }
        });
        
        $inputRow.css('position', 'relative').append($emergencyButton);
        console.log('Emergency desktop button created');
    }
    
    // Fix off-screen desktop button
    function fixOffScreenDesktopButton($button) {
        const $inputRow = $('#chat-input-row');
        const $input = $('#chat-input');
        
        // Try repositioning with CSS first
        $button.css({
            'position': 'absolute',
            'right': '16px',
            'bottom': '16px',
            'left': 'auto',
            'top': 'auto',
            'transform': 'none',
            'margin': '0'
        });
        
        $inputRow.css('position', 'relative');
        $input.css('padding-right', '120px');
        
        console.log('Desktop button repositioned');
    }
    
    // Force button visibility - aggressive approach with DOM creation
    function forceButtonVisibility() {
        console.log('🔧 Forcing button visibility...');
        
        // First, ensure buttons exist in the DOM
        ensureButtonsExist();
        
        // Find all buttons that should be visible
        const buttons = $([
            '#mobile-menu-toggle',
            '#send-chat', 
            '#close-chat',
            '.mobile-menu-btn',
            '.chat-send-button',
            '.close-chat-btn',
            '.edit-message-btn',
            '.regenerate-message-btn',
            '.delete-message-btn',
            '.save-edit-btn',
            '.cancel-edit-btn'
        ].join(', '));
        
        console.log('Found buttons:', buttons.length);
        
        buttons.each(function() {
            const $btn = $(this);
            console.log('Forcing visibility for:', $btn.attr('id') || $btn.attr('class'));
            
            // Remove any hiding styles
            $btn.css({
                'display': 'block !important',
                'visibility': 'visible !important',
                'opacity': '1 !important',
                'position': 'relative',
                'z-index': '99999',
                'pointer-events': 'auto',
                'width': 'auto',
                'height': 'auto',
                'clip': 'auto',
                'clip-path': 'none',
                'transform': 'none',
                'left': 'auto',
                'right': 'auto',
                'top': 'auto',
                'bottom': 'auto'
            });
            
            // Add visible class
            $btn.addClass('force-visible');
        });
        
        console.log('✅ Button visibility forced');
    }
    
    // Ensure critical buttons exist in DOM
    function ensureButtonsExist() {
        console.log('🔧 Ensuring buttons exist in DOM...');
        
        // Check for send button
        if ($('#send-chat, .chat-send-button').length === 0) {
            console.log('❌ Send button missing, creating it...');
            
            // Find input container
            const $inputRow = $('#chat-input-row');
            const $inputWrapper = $inputRow.find('.input-wrapper');
            const $input = $('#chat-input');
            
            if ($input.length > 0) {
                let $container = $inputWrapper.length > 0 ? $inputWrapper : $inputRow;
                
                // Create send button
                const $sendBtn = $(`
                    <button id="send-chat" class="chat-send-button force-visible" type="button">
                        Send
                    </button>
                `);
                
                // Add click handler
                $sendBtn.on('click', function(e) {
                    e.preventDefault();
                    console.log('Send button clicked');
                    if (window.sendChatMessage) {
                        window.sendChatMessage();
                    }
                });
                
                // Add to DOM
                $container.append($sendBtn);
                console.log('✅ Send button created and added');
            }
        }
        
        // Check for menu button
        if ($('#mobile-menu-toggle, .mobile-menu-btn').length === 0) {
            console.log('❌ Menu button missing, creating it...');
            
            const $header = $('#chat-header');
            if ($header.length > 0) {
                const $menuBtn = $(`
                    <button id="mobile-menu-toggle" class="mobile-menu-btn force-visible" type="button">
                        ☰
                    </button>
                `);
                
                // Add click handler
                $menuBtn.on('click', function(e) {
                    e.preventDefault();
                    console.log('Menu button clicked');
                    openMobileMenu();
                });
                
                // Add to beginning of header
                $header.prepend($menuBtn);
                console.log('✅ Menu button created and added');
            }
        }
        
        // Check for close button
        if ($('#close-chat, .close-chat-btn').length === 0) {
            console.log('❌ Close button missing, creating it...');
            
            const $header = $('#chat-header');
            if ($header.length > 0) {
                const $closeBtn = $(`
                    <button id="close-chat" class="close-chat-btn force-visible" type="button">
                        ×
                    </button>
                `);
                
                // Add click handler
                $closeBtn.on('click', function(e) {
                    e.preventDefault();
                    console.log('Close button clicked');
                    $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
                    $('#png-modal').removeClass('fullscreen-chat');
                    if (window.PMV_Chat) {
                        window.PMV_Chat.resetChatState();
                    }
                });
                
                // Add to end of header
                $header.append($closeBtn);
                console.log('✅ Close button created and added');
            }
        }
        
        console.log('✅ Button existence check complete');
    }
    
    // Public API with enhanced message editing support
    window.PMV_MobileChat = {
        init: initializeUniversalSystem,
        openMenu: openMobileMenu,
        closeMenu: closeMobileMenu,
        
        // Message editing functionality
        addEditableMessage: addEditableMessage,
        convertExistingMessages: convertExistingMessages,
        getConversationHistory: collectEditableConversationHistory,
        isEditing: () => editingState.currentlyEditing !== null,
        getCurrentEdit: () => editingState,
        markAsModified: markConversationAsModified,
        clearModified: clearConversationModified,
        
        // Auto-save functionality
        triggerAutoSave: triggerAutoSave,
        performAutoSave: performAutoSave,
        hasUnsavedChanges: () => chatState.hasUnsavedChanges,
        enableAutoSave: () => { chatState.autoSaveEnabled = true; },
        disableAutoSave: () => { chatState.autoSaveEnabled = false; },
        
        // Enhanced conversation loading integration
        loadConversationsList: function() {
            console.log('loadConversationsList called - delegating to loadConversations');
            return this.loadConversations();
        },
        
        loadConversations: loadConversations,
        fixChatVisibility: function() {
            $('#chat-history .chat-message').show().css({
                'visibility': 'visible',
                'opacity': '1'
            });
        },
        forceScrollToBottom: forceScrollToBottom,
        setupMobileFlexLayout: setupUniversalLayout,
        setupMessageObserver: setupMessageObserver,
        applyMobileLayoutToChat: applyUniversalLayoutToChat,
        isInitialized: () => chatState.initialized,
        isMobile: isSmallScreen,
        isTouchDevice: isTouchDevice,
        isSmallScreen: isSmallScreen,
        isDesktop: isDesktop,
        
        // Enhanced desktop support methods
        fixDesktopSendButton: function() {
            if (isDesktop()) {
                ensureDesktopButtonVisibility();
            }
        },
        
        applyDesktopFixes: function() {
            if (isDesktop()) {
                applyDesktopSpecificFixes();
            }
        },
        
        // Advanced API methods
        retryLoadConversations: function() {
            console.log('Manual retry requested');
            chatState.retryCount = 0;
            chatState.conversationsLoaded = false;
            loadConversations();
        },
        
        clearConversations: function() {
            $('.mobile-conversations-list').html('<div class="mobile-loading-container">No conversations</div>');
        },
        
        updateConversationList: function(conversations) {
            displayConversations(conversations);
        },
        
        // Debug methods
        getState: () => chatState,
        getConfig: () => CHAT_CONFIG,
        
        // Debug conversation system
        debugConversationSystem: function() {
            console.log('=== PMV Conversation System Debug ===');
            console.log('Window size:', window.innerWidth + 'x' + window.innerHeight);
            console.log('User agent:', navigator.userAgent);
            console.log('Touch device:', isTouchDevice());
            console.log('Small screen:', isSmallScreen());
            console.log('Desktop:', isDesktop());
            console.log('Chat state:', chatState);
            console.log('Editing state:', editingState);
            console.log('PMV_ConversationManager:', window.PMV_ConversationManager);
            console.log('pmv_ajax_object:', typeof pmv_ajax_object !== 'undefined' ? pmv_ajax_object : 'NOT AVAILABLE');
            
            if (window.PMV_ConversationManager) {
                console.log('ConversationManager methods:');
                console.log('- loadConversationList:', typeof window.PMV_ConversationManager.loadConversationList);
                console.log('- loadConversationListMobile:', typeof window.PMV_ConversationManager.loadConversationListMobile);
                console.log('- conversations array:', window.PMV_ConversationManager.conversations);
                console.log('- characterId:', window.PMV_ConversationManager.characterId);
                console.log('- isReady:', window.PMV_ConversationManager.isReady);
            }
            
            console.log('=====================================');
            
            // Show debug info in UI
            $('.mobile-conversations-list').html(`
                <div class="mobile-loading-container" style="text-align: left; font-size: 12px; line-height: 1.4;">
                    <strong>🔍 Debug Information:</strong><br><br>
                    <strong>Device:</strong> ${window.innerWidth}x${window.innerHeight}<br>
                    <strong>Touch:</strong> ${isTouchDevice()}<br>
                    <strong>Small screen:</strong> ${isSmallScreen()}<br>
                    <strong>Desktop:</strong> ${isDesktop()}<br>
                    <strong>ConversationManager:</strong> ${!!window.PMV_ConversationManager}<br>
                    <strong>AJAX object:</strong> ${typeof pmv_ajax_object !== 'undefined'}<br>
                    <strong>Editing enabled:</strong> ${chatState.editingEnabled}<br>
                    <strong>Currently editing:</strong> ${editingState.currentlyEditing !== null}<br>
                    <strong>Has unsaved changes:</strong> ${chatState.hasUnsavedChanges}<br>
                    <strong>Auto-save enabled:</strong> ${chatState.autoSaveEnabled}<br>
                    ${window.PMV_ConversationManager ? `<strong>Character ID:</strong> ${window.PMV_ConversationManager.characterId}<br>` : ''}
                    ${window.PMV_ConversationManager ? `<strong>Manager ready:</strong> ${window.PMV_ConversationManager.isReady}<br>` : ''}
                    <br>
                    <button class="retry-button" onclick="window.PMV_MobileChat.retryLoadConversations()">
                        🔄 Try Loading Again
                    </button>
                </div>
            `);
        },
        
        // Manual layout fixes
        forceLayoutUpdate: function() {
            if (chatState.isLayoutApplied) {
                setupUniversalLayout();
                forceScrollToBottom();
            }
        },
        
        // Force button visibility - for desktop issues
        forceButtonVisibility: forceButtonVisibility,
        
        // Emergency button fix with desktop support
        emergencyButtonFix: function() {
            console.log('🚨 EMERGENCY BUTTON FIX ACTIVATED');
            
            // Enhanced diagnostics
            console.log('=== ENHANCED BUTTON DIAGNOSIS ===');
            console.log('Desktop mode:', isDesktop());
            console.log('Chat input exists:', $('#chat-input').length > 0);
            console.log('Input row exists:', $('#chat-input-row').length > 0);
            console.log('Input wrapper exists:', $('.input-wrapper').length > 0);
            console.log('Send button exists:', $('#send-chat, .chat-send-button').length > 0);
            console.log('Chat container exists:', $('.chat-container').length > 0);
            console.log('Chat modal open:', $('body').hasClass('chat-modal-open'));
            console.log('Currently editing:', editingState.currentlyEditing !== null);
            
            if (isDesktop()) {
                console.log('🖥️ Applying desktop-specific emergency fixes...');
                this.emergencyDesktopButtonFix();
            } else {
                console.log('📱 Applying mobile emergency fixes...');
                this.emergencyMobileButtonFix();
            }
            
            console.log('🚨 EMERGENCY FIX COMPLETE');
        },
        
        emergencyDesktopButtonFix: function() {
            console.log('🖥️ DESKTOP EMERGENCY BUTTON FIX...');
            
            // Remove problematic elements
            $('#send-chat, .chat-send-button').remove();
            
            const $inputRow = $('#chat-input-row');
            const $input = $('#chat-input');
            
            if (!$input.length) {
                console.log('❌ No input found!');
                return;
            }
            
            // Create desktop-optimized button
            const $desktopButton = $(`
                <button id="send-chat-desktop" class="chat-send-button desktop-emergency" type="button">
                    Send
                </button>
            `);
            
            // Desktop-specific positioning
            $desktopButton.css({
                'position': 'absolute',
                'right': '16px',
                'top': '50%',
                'transform': 'translateY(-50%)',
                'background': '#4a90e2',
                'color': 'white',
                'border': 'none',
                'padding': '12px 24px',
                'border-radius': '8px',
                'cursor': 'pointer',
                'font-size': '16px',
                'font-weight': '600',
                'z-index': '999999',
                'min-width': '100px',
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            $desktopButton.on('click', function(e) {
                e.preventDefault();
                console.log('Desktop emergency button clicked');
                if (window.sendChatMessage) {
                    window.sendChatMessage();
                }
            });
            
            // Prepare container
            $inputRow.css({
                'position': 'relative',
                'padding-right': '120px'
            });
            
            $input.css({
                'width': '100%',
                'padding-right': '16px'
            });
            
            $inputRow.append($desktopButton);
            
            console.log('✅ Desktop emergency button created');
        },
        
        emergencyMobileButtonFix: function() {
            console.log('📱 MOBILE EMERGENCY BUTTON FIX...');
            
            // Standard mobile button creation
            const $input = $('#chat-input');
            if (!$input.length) return;
            
            const $mobileButton = $(`
                <button id="send-chat-mobile" class="chat-send-button mobile-emergency" type="button">
                    Send
                </button>
            `);
            
            $mobileButton.css({
                'background': '#4a90e2',
                'color': 'white',
                'border': 'none',
                'padding': '12px 20px',
                'border-radius': '8px',
                'cursor': 'pointer',
                'font-size': '16px',
                'font-weight': '600',
                'margin-left': '10px',
                'display': 'inline-block',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            $mobileButton.on('click', function(e) {
                e.preventDefault();
                console.log('Mobile emergency button clicked');
                if (window.sendChatMessage) {
                    window.sendChatMessage();
                }
            });
            
            $input.after($mobileButton);
            
            console.log('✅ Mobile emergency button created');
        },
        
        // Message editing specific methods
        startEditing: function(messageElement) {
            const $message = $(messageElement);
            if ($message.length) {
                startEditingMessage($message);
            }
        },
        
        cancelEditing: function() {
            if (editingState.currentlyEditing) {
                cancelMessageEdit($(editingState.currentlyEditing));
            }
        },
        
        saveCurrentEdit: function() {
            if (editingState.currentlyEditing) {
                saveMessageEdit($(editingState.currentlyEditing));
            }
        },
        
        deleteMessage: function(messageElement) {
            const $message = $(messageElement);
            if ($message.length) {
                deleteMessage($message);
            }
        },
        
        regenerateMessage: function(messageElement) {
            const $message = $(messageElement);
            if ($message.length) {
                regenerateMessage($message);
            }
        },
        
        // Utility methods
        enableEditing: function() {
            chatState.editingEnabled = true;
            $('.message-actions').show();
        },
        
        disableEditing: function() {
            chatState.editingEnabled = false;
            $('.message-actions').hide();
            
            // Cancel any current editing
            if (editingState.currentlyEditing) {
                cancelMessageEdit($(editingState.currentlyEditing));
            }
        },
        
        isEditingEnabled: function() {
            return chatState.editingEnabled;
        }
    };
    
    // Auto-initialize
    $(document).ready(function() {
        console.log('DOM ready, initializing universal chat system with message editing');
        initializeUniversalSystem();
    });
    
    // Watch for chat container creation with enhanced desktop support
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                const addedChatContainer = Array.from(mutation.addedNodes).some(node => 
                    node.nodeType === Node.ELEMENT_NODE && 
                    (node.classList.contains('chat-container') || 
                     node.querySelector('.chat-container'))
                );
                
                if (addedChatContainer) {
                    console.log('Chat container detected, applying universal layout with editing');
                    setTimeout(() => {
                        applyUniversalLayoutToChat();
                        // Enhanced button fixes
                        setTimeout(() => {
                            forceButtonVisibility();
                            if (isDesktop()) {
                                ensureDesktopButtonVisibility();
                            }
                        }, 500);
                    }, 100);
                }
                
                // Watch for button creation specifically
                const addedButtons = Array.from(mutation.addedNodes).some(node => 
                    node.nodeType === Node.ELEMENT_NODE && 
                    (node.id === 'mobile-menu-toggle' || 
                     node.id === 'send-chat' ||
                     node.classList.contains('mobile-menu-btn') ||
                     node.classList.contains('chat-send-button') ||
                     node.classList.contains('edit-message-btn') ||
                     node.classList.contains('regenerate-message-btn') ||
                     node.classList.contains('delete-message-btn'))
                );
                
                if (addedButtons) {
                    console.log('Buttons detected, forcing visibility');
                    setTimeout(() => {
                        forceButtonVisibility();
                        if (isDesktop()) {
                            ensureDesktopButtonVisibility();
                        }
                    }, 100);
                }
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Enhanced resize handling
    $(window).on('orientationchange resize', function() {
        if (chatState.initialized) {
            setTimeout(() => {
                console.log('Window orientation/size changed');
                if (isTouchDevice()) {
                    chatState.originalViewportHeight = window.innerHeight;
                }
                if (chatState.isLayoutApplied) {
                    if (isDesktop()) {
                        // Re-apply desktop fixes on resize
                        applyDesktopSpecificFixes();
                    }
                    forceScrollToBottom();
                }
            }, 300);
        }
    });
    
    // Handle page visibility changes
    $(document).on('visibilitychange', function() {
        if (!document.hidden && chatState.isLayoutApplied) {
            // Page became visible, ensure layout is correct
            setTimeout(() => {
                forceScrollToBottom();
                if (isDesktop()) {
                    ensureDesktopButtonVisibility();
                }
            }, 100);
        }
    });
    
    // Handle beforeunload for unsaved changes
    $(window).on('beforeunload', function(e) {
        if (editingState.currentlyEditing && editingState.hasUnsavedChanges) {
            const message = 'You have unsaved message edits. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
        
        // Check for unsaved conversation changes
        if (chatState.hasUnsavedChanges) {
            const message = 'You have unsaved conversation changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
    
    // Custom events for message editing
    $(document).on('chat:started', function() {
        console.log('Chat started event detected, initializing editing');
        setTimeout(() => {
            if (chatState.editingEnabled) {
                convertExistingMessages();
            }
        }, 200);
    });
    
    $(document).on('message:added', function() {
        console.log('Message added event detected, converting to editable and triggering auto-save');
        setTimeout(() => {
            if (chatState.editingEnabled) {
                convertExistingMessages();
            }
            // Trigger auto-save for new messages
            triggerAutoSave();
        }, 100);
    });
    
    // Error handling for uncaught errors
    window.addEventListener('error', function(event) {
        if (event.error && event.error.message && 
            (event.error.message.includes('PMV_MobileChat') || 
             event.error.message.includes('message editing'))) {
            console.error('PMV_MobileChat/Message Editing error:', event.error);
        }
    });
    
    // Keyboard shortcuts for editing
    $(document).on('keydown', function(e) {
        // Global shortcuts only when not typing in input fields
        if (!$(e.target).is('input, textarea, [contenteditable]')) {
            // Escape key to cancel editing
            if (e.key === 'Escape' && editingState.currentlyEditing) {
                e.preventDefault();
                cancelMessageEdit($(editingState.currentlyEditing));
            }
            // Ctrl+S to save conversation
            else if (e.key === 's' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                if (chatState.hasUnsavedChanges) {
                    performAutoSave();
                }
            }
        }
    });
    
    console.log('PNG Metadata Viewer Universal Chat System with Message Editing Loaded');
    
})(jQuery);
