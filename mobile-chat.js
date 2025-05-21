// PNG Metadata Viewer - Mobile Chat Module
// This file handles all mobile-specific chat functionality
console.log('PNG Metadata Viewer Mobile Chat Module Loaded');

(function($) {
    // Create namespace for mobile-specific functions
    window.PMV_MobileChat = {};
    
    // Configuration
    const mobileConfig = {
        breakpoint: 768,
        sidebarSelector: '#chat-sidebar',
        mobileMenuSelector: '#mobile-menu-toggle',
        mobileChatSelector: '.pmv-mobile-chat',
        closeButtonClass: 'mobile-close-btn'
    };
    
    // Add critical CSS directly to head to ensure mobile compatibility
    $(document).ready(function() {
        // CRITICAL: Add mobile-specific styles
        $('head').append(`
            <style id="pmv-mobile-chat-styles">
                /* Force mobile menu button to be visible and correctly positioned */
                #mobile-menu-toggle {
                    display: block !important;
                    position: fixed !important;
                    left: 10px !important;
                    top: 10px !important;
                    z-index: 99999999 !important;
                    background-color: #4a6da7 !important;
                    color: white !important;
                    border: none !important;
                    border-radius: 4px !important;
                    padding: 8px 12px !important;
                    font-size: 24px !important;
                    width: 40px !important;
                    height: 40px !important;
                    line-height: 24px !important;
                    text-align: center !important;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3) !important;
                    cursor: pointer !important;
                }
                
                /* Fix mobile close btn */
                .mobile-close-btn {
                    color: #FFFFFF !important;
                    font-size: 24px !important;
                    font-weight: bold !important;
                    cursor: pointer !important;
                }

                /* Mobile Sidebar Enhancements */
                @media (max-width: 768px) {
                    /* Make sidebar take full width on mobile */
                    #chat-sidebar:not(.minimized) {
                        width: 100% !important;
                        max-width: 100% !important;
                        left: 0 !important;
                        z-index: 999999 !important;
                    }
                    
                    /* Ensure the mobile menu toggle is always visible */
                    #mobile-menu-toggle {
                        display: block !important;
                        position: fixed !important;
                        left: 10px !important;
                        top: 10px !important;
                        z-index: 100000 !important;
                        background-color: #4a6da7 !important;
                        color: white !important;
                        border: none !important;
                        border-radius: 4px !important;
                        padding: 8px 12px !important;
                        font-size: 20px !important;
                        width: 40px !important;
                        height: 40px !important;
                        line-height: 24px !important;
                        text-align: center !important;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.3) !important;
                    }
                    
                    /* Add a bottom close button container */
                    .mobile-button-container {
                        position: fixed !important;
                        bottom: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        padding: 15px !important;
                        text-align: center !important;
                        background-color: #222222 !important;
                        border-top: 1px solid #444444 !important;
                        box-sizing: border-box !important;
                        z-index: 9999999 !important;
                    }
                    
                    /* Style bottom close button */
                    .mobile-button-container .mobile-close-btn {
                        display: inline-block !important;
                        background-color: #4a6da7 !important;
                        color: white !important;
                        border: none !important;
                        border-radius: 4px !important;
                        padding: 10px 20px !important;
                        font-size: 16px !important;
                        font-weight: bold !important;
                        width: 80% !important;
                        text-align: center !important;
                        cursor: pointer !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
                    }
                    
                    /* Extra space at the bottom to account for the button */
                    #conversation-list {
                        padding-bottom: 70px !important;
                    }
                }
                
                /* Parallel Mobile Chat Interface - Completely separate from original layout */
                .pmv-mobile-chat {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #1a1a1a;
                    z-index: 999999;
                    overflow: hidden;
                }
                
                /* Header */
                .pmv-mobile-chat-header {
                    display: flex;
                    align-items: center;
                    height: 50px;
                    background-color: #222222;
                    border-bottom: 1px solid #444;
                    padding: 0 10px;
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    z-index: 5;
                }
                
                .pmv-mobile-chat-title {
                    flex: 1;
                    color: white;
                    font-size: 18px;
                    font-weight: bold;
                    text-align: center;
                }
                
                .pmv-mobile-chat-close {
                    background: transparent;
                    border: none;
                    color: white;
                    font-size: 24px;
                    width: 40px;
                    height: 40px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                /* Messages */
                .pmv-mobile-chat-messages {
                    position: absolute;
                    top: 50px;
                    bottom: 70px;
                    left: 0;
                    right: 0;
                    overflow-y: auto;
                    padding: 15px;
                    background-color: #1a1a1a;
                }
                
                .pmv-mobile-message {
                    display: block;
                    max-width: 85%;
                    margin: 10px 0;
                    padding: 12px;
                    border-radius: 8px;
                    color: white;
                    clear: both;
                    word-break: break-word;
                    position: relative;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                }
                
                .pmv-mobile-message-bot {
                    float: left;
                    background-color: #444;
                    margin-right: 15%;
                    text-align: left;
                }
                
                .pmv-mobile-message-user {
                    float: right;
                    background-color: #4a6da7;
                    margin-left: 15%;
                    text-align: left;
                }
                
                .pmv-mobile-message-error {
                    background-color: #8B0000;
                    margin: 10px auto;
                    text-align: center;
                    float: none;
                    max-width: 90%;
                }
                
                .pmv-mobile-message-sender {
                    font-weight: bold;
                    margin-right: 5px;
                }
                
                /* Input area */
                .pmv-mobile-chat-input {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 70px;
                    background-color: #222;
                    border-top: 1px solid #444;
                    padding: 10px;
                    display: flex;
                    align-items: center;
                    z-index: 5;
                }
                
                .pmv-mobile-chat-textarea {
                    flex: 1;
                    height: 40px;
                    max-height: 40px;
                    border-radius: 4px;
                    border: 1px solid #444;
                    background-color: white;
                    color: black;
                    padding: 8px;
                    margin-right: 10px;
                    font-size: 16px;
                    resize: none;
                }
                
                .pmv-mobile-chat-send {
                    min-width: 70px;
                    height: 40px;
                    background-color: #4a6da7;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    font-weight: bold;
                    font-size: 16px;
                    cursor: pointer;
                }
                
                /* Clearfix */
                .pmv-mobile-message:after {
                    content: "";
                    display: table;
                    clear: both;
                }
                
                /* Only show when on mobile */
                @media (max-width: 768px) {
                    .pmv-mobile-chat.active {
                        display: block;
                    }
                    
                    /* Hide original chat when mobile is active */
                    .pmv-mobile-chat.active ~ .png-modal-content {
                        display: none !important;
                    }
                }
            </style>
        `);
    });
    
    // Initialize the mobile chat interface
    window.PMV_MobileChat.initMobileChat = function(character) {
        console.log('Initializing mobile chat for:', character.name);
        
        // First, setup the mobile menu toggle if it doesn't exist
        setupMobileMenuToggle();
        
        // Then, create the mobile sidebar close button
        setupMobileSidebarCloseButton();
        
        // Set up the dedicated mobile chat interface if needed
        setupMobileChatInterface(character);
        
        // Apply mobile-specific layout adjustments
        applyMobileLayout();
        
        // Set up event handlers for mobile interactions
        setupMobileEventHandlers();
    };
    
    // Enhanced chat layout function
    window.enhanceChatLayout = function() {
        console.log('Applying enhanced chat layout fixes');
        
        // Add fullscreen-chat class to modal immediately
        $('#png-modal').addClass('fullscreen-chat');
        
        // Check if we're on mobile, if so, create mobile chat if not already created
        if (window.innerWidth <= 768) {
            // Get character data
            const $modal = $('#png-modal').find('.png-modal-content');
            const characterData = $modal.data('characterData');
            
            if (characterData) {
                const character = window.PMV_Chat.extractCharacterInfo(characterData);
                if (!$('.pmv-mobile-chat').length) {
                    window.createMobileChat(character);
                } else {
                    $('.pmv-mobile-chat').addClass('active');
                }
            }
            
            // Apply mobile-specific sidebar enhancements
            $('#chat-sidebar').css({
                'width': '100%',
                'max-width': '100%'
            });
            
            $('#mobile-menu-toggle').css({
                'display': 'block',
                'z-index': '1000000'
            });
            
            // Ensure mobile button container is visible
            $('#mobile-button-container').css({
                'display': 'block'
            });
        }
        
        // Fix modal to be fully fullscreen
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
        
        // Fix modal content to fill entire space
        $('.png-modal-content.chat-mode').css({
            'width': '100%',
            'height': '100%',
            'margin': '0',
            'padding': '0',
            'max-width': '100%',
            'max-height': '100%',
            'overflow': 'hidden',
            'background-color': '#1a1a1a',
            'border-radius': '0'
        });
        
        // Ensure chat container uses proper flex layout
        $('.chat-container').css({
            'display': 'flex',
            'height': '100%',
            'width': '100%',
            'overflow': 'hidden'
        });
        
        // Fix sidebar positioning
        $('#chat-sidebar').css({
            'position': 'absolute',
            'top': '0',
            'left': '-100%',
            'bottom': '0',
            'width': '280px',
            'height': '100%',
            'z-index': '99999999',
            'overflow-y': 'auto',
            'background-color': '#222222',
            'color': '#FFFFFF',
            'transition': 'left 0.3s ease',
            'padding': '20px',
            'box-sizing': 'border-box'
        });
        
        // When sidebar is not minimized, show it
        $('#chat-sidebar:not(.minimized)').css({
            'left': '0'
        });
        
        // Fix main content positioning
        const isMobile = window.innerWidth <= 768;
        const $sidebar = $('#chat-sidebar');
        const sidebarWidth = isMobile || $sidebar.hasClass('minimized') ? 0 : '280px';
        
        $('.chat-main').css({
            'position': 'absolute',
            'top': '0',
            'left': sidebarWidth,
            'right': '0',
            'bottom': '0',
            'width': sidebarWidth === '0' ? '100%' : 'calc(100% - 280px)',
            'height': '100%',
            'display': 'flex',
            'flex-direction': 'column',
            'overflow': 'hidden'
        });
        
        // Fix chat history to properly handle messages
        $('#chat-history').css({
            'flex': '1 1 auto',
            'overflow-y': 'auto',
            'overflow-x': 'hidden',
            'padding': '20px',
            'padding-bottom': '90px',
            'background-color': '#1a1a1a',
            'color': '#FFFFFF',
            'position': 'relative',
            'z-index': '5'
        });
        
        // Change to block display on mobile for better reliability 
        if (isMobile) {
            $('#chat-history').css({
                'display': 'block'
            });
        } else {
            $('#chat-history').css({
                'display': 'flex',
                'flex-direction': 'column'
            });
        }
        
        // Style for mobile menu toggle
        $('#mobile-menu-toggle').css({
            'display': isMobile ? 'block' : 'none',
            'position': 'fixed',
            'left': '10px',
            'top': '10px',
            'z-index': '99999999',
            'background-color': '#4a6da7',
            'color': 'white',
            'border': 'none',
            'border-radius': '4px',
            'padding': '8px 12px',
            'font-size': '24px',
            'width': '40px',
            'height': '40px',
            'line-height': '24px',
            'text-align': 'center',
            'cursor': 'pointer'
        });
        
        // FIXED: Position the input row at the bottom with correct positioning
        $('#chat-input-row').css({
            'position': 'fixed',
            'bottom': '0',
            'left': sidebarWidth,
            'right': '0',
            'z-index': '10',
            'background-color': '#222222',
            'padding': '15px',
            'border-top': '1px solid #444444',
            'height': '70px',
            'box-sizing': 'border-box',
            'display': 'flex',
            'align-items': 'center'
        });
        
        // Style the input field
        $('#chat-input').css({
            'flex': '1',
            'background-color': '#FFFFFF',
            'color': '#000000',
            'border': '1px solid #444444',
            'border-radius': '4px',
            'padding': '10px',
            'margin-right': '10px',
            'font-size': '16px',
            'min-height': '40px',
            'max-height': '100px'
        });
        
        // Style the send button
        $('#send-chat').css({
            'background-color': '#4a6da7',
            'color': 'white',
            'border': 'none',
            'border-radius': '4px',
            'padding': '10px 15px',
            'font-size': '16px',
            'font-weight': 'bold',
            'cursor': 'pointer',
            'min-width': '60px'
        });
        
        // Style chat messages
        $('.chat-message').css({
            'padding': '12px',
            'margin': '8px 0',
            'border-radius': '8px',
            'max-width': '85%',
            'color': '#FFFFFF',
            'align-self': 'flex-start',
            'box-shadow': '0 1px 3px rgba(0,0,0,0.2)',
            'background-color': '#444444',
            'font-size': '16px',
            'line-height': '1.5',
            'position': 'relative',
            'z-index': '5',
            'word-break': 'break-word'
        });
        
        // Style user messages
        $('.chat-message.user').css({
            'background-color': '#4a6da7',
            'align-self': 'flex-end',
            'color': '#FFFFFF'
        });
        
        // Style bot messages
        $('.chat-message.bot').css({
            'background-color': '#444444',
            'color': '#FFFFFF'
        });
        
        // Style error messages
        $('.chat-message.error').css({
            'background-color': '#8B0000',
            'color': '#FFFFFF'
        });
        
        // Style typing indicator
        $('.typing-indicator').css({
            'padding': '8px 12px',
            'border-radius': '8px',
            'background-color': 'rgba(0,0,0,0.3)',
            'color': '#FFFFFF',
            'align-self': 'flex-start',
            'margin': '8px 0',
            'font-style': 'italic'
        });
        
        // Style chat header
        $('#chat-header').css({
            'height': '50px',
            'display': 'flex',
            'align-items': 'center',
            'padding': '0 10px',
            'background-color': '#222222',
            'border-bottom': '1px solid #444444'
        });
        
        // Style chat modal name
        $('.chat-modal-name').css({
            'flex': '1',
            'text-align': 'center',
            'font-weight': 'bold',
            'font-size': '18px',
            'color': '#FFFFFF'
        });
        
        // Style close button
        $('#close-chat').css({
            'background': 'transparent',
            'color': '#FFFFFF',
            'border': 'none',
            'font-size': '24px',
            'cursor': 'pointer'
        });
        
        // Force body to respect fullscreen chat
        $('body, html').addClass('chat-modal-open');
        
        // Force scroll to bottom after layout changes
        window.forceScrollToBottom();
        
        // Handle window resize to maintain layout
        $(window).on('resize.chatLayout', function() {
            const isMobileNow = window.innerWidth <= 768;
            const $sidebar = $('#chat-sidebar');
            const newSidebarWidth = (isMobileNow || $sidebar.hasClass('minimized')) ? '0' : '280px';
            
            // Check if we need to switch to mobile view
            if (isMobileNow && !$('.pmv-mobile-chat').length) {
                // Get character data and create mobile chat
                const $modal = $('#png-modal').find('.png-modal-content');
                const characterData = $modal.data('characterData');
                
                if (characterData) {
                    const character = window.PMV_Chat.extractCharacterInfo(characterData);
                    window.createMobileChat(character);
                }
            } else if (!isMobileNow && $('.pmv-mobile-chat.active').length) {
                // Switch from mobile to desktop view
                $('.pmv-mobile-chat').removeClass('active');
                $('.png-modal-content').show();
            }
            
            // Update layout based on current view
            if (!isMobileNow) {
                $('.chat-main').css({
                    'left': newSidebarWidth,
                    'width': newSidebarWidth === '0' ? '100%' : 'calc(100% - 280px)'
                });
                
                $('#chat-input-row').css({
                    'left': newSidebarWidth
                });
                
                // Show/hide appropriate menu toggle based on screen size
                $('#mobile-menu-toggle').css('display', 'none');
                $('#toggle-sidebar').css('display', 'block');
            } else {
                // Apply mobile-specific sidebar enhancements
                $('#chat-sidebar').css({
                    'width': '100%',
                    'max-width': '100%'
                });
                
                $('#mobile-menu-toggle').css({
                    'display': 'block',
                    'z-index': '1000000'
                });
                
                // Ensure mobile button container is visible
                $('#mobile-button-container').css({
                    'display': 'block'
                });
            }
            
            window.forceScrollToBottom();
        });
    };
    
    // Create the mobile menu toggle button
    function setupMobileMenuToggle() {
        console.log('Setting up mobile menu toggle');
        
        // If mobile toggle doesn't exist in the DOM, create it
        if ($('#mobile-menu-toggle').length === 0) {
            // Add the mobile toggle button to the header
            $('#chat-header').prepend(`
                <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-label="Open menu">☰</button>
            `);
        }
        
        // Make sure the mobile toggle is visible and properly styled
        $('#mobile-menu-toggle').css({
            'display': 'block',
            'position': 'fixed',
            'left': '10px',
            'top': '10px',
            'z-index': '99999999',
            'background-color': '#4a6da7',
            'color': 'white',
            'border': 'none',
            'border-radius': '4px',
            'padding': '8px 12px',
            'font-size': '20px',
            'width': '40px',
            'height': '40px',
            'line-height': '24px',
            'text-align': 'center',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.3)',
            'cursor': 'pointer'
        });
        
        // Hide desktop sidebar toggle button
        $('#toggle-sidebar').hide();
    }
    
    // Create the mobile sidebar close button
    function setupMobileSidebarCloseButton() {
        const $sidebar = $(mobileConfig.sidebarSelector);
        
        // Create the mobile close button container if it doesn't exist
        if ($('#mobile-button-container').length === 0) {
            $sidebar.append(`
                <div id="mobile-button-container" class="mobile-button-container">
                    <button class="mobile-close-btn">Close Sidebar</button>
                </div>
            `);
        }
        
        // Style the button container
        $('#mobile-button-container').css({
            'position': 'fixed',
            'bottom': '0',
            'left': '0',
            'width': '100%',
            'padding': '15px',
            'text-align': 'center',
            'background-color': '#222222',
            'border-top': '1px solid #444444',
            'box-sizing': 'border-box',
            'z-index': '9999999'
        });
        
        // Style the button itself
        $('.mobile-close-btn').css({
            'display': 'inline-block',
            'background-color': '#4a6da7',
            'color': 'white',
            'border': 'none',
            'border-radius': '4px',
            'padding': '10px 20px',
            'font-size': '16px',
            'font-weight': 'bold',
            'width': '80%',
            'text-align': 'center',
            'cursor': 'pointer',
            'box-shadow': '0 2px 4px rgba(0,0,0,0.2)'
        });
    }
    
    // Create the alternate mobile chat interface
    function setupMobileChatInterface(character) {
        console.log('Setting up mobile chat interface for:', character.name);
        
        // Check if mobile chat already exists
        if ($(mobileConfig.mobileChatSelector).length > 0) {
            console.log('Mobile chat already exists, activating');
            $(mobileConfig.mobileChatSelector).addClass('active');
            return;
        }
        
        // Create mobile chat container
        const $mobileChat = $(`
            <div class="pmv-mobile-chat active">
                <div class="pmv-mobile-chat-header">
                    <button class="pmv-mobile-chat-close">×</button>
                    <div class="pmv-mobile-chat-title">${escapeHtml(character.name)}</div>
                </div>
                <div class="pmv-mobile-chat-messages">
                    <div class="pmv-mobile-message pmv-mobile-message-bot">
                        <span class="pmv-mobile-message-sender">${escapeHtml(character.name)}:</span>
                        <span class="pmv-mobile-message-content">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                    </div>
                </div>
                <div class="pmv-mobile-chat-input">
                    <textarea class="pmv-mobile-chat-textarea" placeholder="Type your message..."></textarea>
                    <button class="pmv-mobile-chat-send">Send</button>
                </div>
            </div>
        `);
        
        // Add to modal
        $('#png-modal').prepend($mobileChat);
        
        // Setup mobile chat event handlers
        setupMobileChatEventHandlers();
        
        console.log('Mobile chat interface created');
    }
    
    // Apply mobile layout optimizations
    function applyMobileLayout() {
        // Ensure sidebar is full width on mobile
        const $sidebar = $(mobileConfig.sidebarSelector);
        $sidebar.css({
            'width': '100%',
            'max-width': '100%'
        });
        
        // Ensure the mobile toggle button is visible
        $(mobileConfig.mobileMenuSelector).css({
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        // Position the chat main correctly
        $('.chat-main').css({
            'left': '0',
            'width': '100%'
        });
        
        // Position the input row correctly
        $('#chat-input-row').css('left', '0');
        
        // Ensure mobile chat button container is visible
        $('#mobile-button-container').css('display', 'block');
        
        // Force scroll to ensure content is visible
        window.forceScrollToBottom();
    }
    
    // Set up mobile event handlers
    function setupMobileEventHandlers() {
        // Mobile toggle sidebar button event
        $(document).off('click', mobileConfig.mobileMenuSelector);
        $(document).on('click', mobileConfig.mobileMenuSelector, function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            console.log("Mobile menu toggle clicked");
            
            // Show sidebar
            const $sidebar = $(mobileConfig.sidebarSelector);
            $sidebar.removeClass('minimized');
            
            // Ensure full-width sidebar on mobile
            $sidebar.css({
                'width': '100%',
                'max-width': '100%',
                'left': '0'
            });
            
            // Update input position
            $('#chat-input-row').css('left', '0');
            
            // Force scroll to ensure content is visible
            window.forceScrollToBottom();
        });
        
        // Mobile close button for sidebar
        $(document).off('click', '.' + mobileConfig.closeButtonClass);
        $(document).on('click', '.' + mobileConfig.closeButtonClass, function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            // Hide sidebar
            const $sidebar = $(mobileConfig.sidebarSelector);
            $sidebar.addClass('minimized');
            
            // Visual feedback for button press
            $(this).css({
                'transform': 'scale(0.9)',
                'transition': 'transform 0.2s ease'
            });
            
            // Reset button after animation
            setTimeout(() => {
                $(this).css('transform', 'scale(1)');
            }, 200);
            
            // Update input position for mobile
            $('#chat-input-row').css('left', '0');
            
            // Force scroll to bottom after sidebar closed
            setTimeout(window.forceScrollToBottom, 100);
        });
    }
    
    // Setup mobile chat specific events
    function setupMobileChatEventHandlers() {
        // Close mobile chat button
        $('.pmv-mobile-chat-close').off('click').on('click', function() {
            $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
            $('#png-modal').removeClass('fullscreen-chat');
            $('.pmv-mobile-chat').removeClass('active');
            
            // Reset chat state
            window.PMV_Chat.resetChatState();
            
            // Reset conversation manager if it exists
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.reset();
            }
        });
        
        // Mobile send button
        $('.pmv-mobile-chat-send').off('click').on('click', function() {
            const message = $('.pmv-mobile-chat-textarea').val().trim();
            if (!message) return;
            
            // Add user message to mobile UI
            window.PMV_MobileChat.addMobileMessage('user', 'You', message);
            
            // Clear mobile input
            $('.pmv-mobile-chat-textarea').val('');
            
            // Scroll mobile chat to bottom
            window.PMV_MobileChat.scrollMobileChat();
            
            // Also add message to original chat interface (hidden) and trigger send
            $('#chat-input').val(message);
            window.originalSendChatMessage();
        });
        
        // Handle enter key on mobile textarea
        $('.pmv-mobile-chat-textarea').off('keydown').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $('.pmv-mobile-chat-send').click();
            }
        });
    }
    
    // Create and initialize mobile chat 
    window.createMobileChat = function(character) {
        console.log('Creating mobile chat interface for:', character.name);
        
        // Check if already exists
        if ($('.pmv-mobile-chat').length > 0) {
            console.log('Mobile chat already exists, activating');
            $('.pmv-mobile-chat').addClass('active');
            return;
        }
        
        // Create mobile chat container
        const $mobileChat = $(`
            <div class="pmv-mobile-chat active">
                <div class="pmv-mobile-chat-header">
                    <button class="pmv-mobile-chat-close">×</button>
                    <div class="pmv-mobile-chat-title">${escapeHtml(character.name)}</div>
                </div>
                <div class="pmv-mobile-chat-messages">
                    <div class="pmv-mobile-message pmv-mobile-message-bot">
                        <span class="pmv-mobile-message-sender">${escapeHtml(character.name)}:</span>
                        <span class="pmv-mobile-message-content">${escapeHtml(character.first_mes || `Hello, I am ${character.name}. How can I help you today?`)}</span>
                    </div>
                </div>
                <div class="pmv-mobile-chat-input">
                    <textarea class="pmv-mobile-chat-textarea" placeholder="Type your message..."></textarea>
                    <button class="pmv-mobile-chat-send">Send</button>
                </div>
            </div>
        `);
        
        // Add to modal
        $('#png-modal').prepend($mobileChat);
        
        // Setup event handlers
        $('.pmv-mobile-chat-close').on('click', function() {
            $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
            $('#png-modal').removeClass('fullscreen-chat');
            $('.pmv-mobile-chat').removeClass('active');
            window.PMV_Chat.resetChatState();
            if (window.PMV_ConversationManager) {
                window.PMV_ConversationManager.reset();
            }
        });
        
        $('.pmv-mobile-chat-send').on('click', function() {
            const message = $('.pmv-mobile-chat-textarea').val().trim();
            if (!message) return;
            
            // Add user message to mobile UI
            window.PMV_MobileChat.addMobileMessage('user', 'You', message);
            
            // Clear mobile input
            $('.pmv-mobile-chat-textarea').val('');
            
            // Scroll mobile chat to bottom
            window.PMV_MobileChat.scrollMobileChat();
            
            // Also add message to original chat interface (hidden) and trigger send
            $('#chat-input').val(message);
            window.originalSendChatMessage();
        });
        
        // Handle enter key on mobile textarea
        $('.pmv-mobile-chat-textarea').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $('.pmv-mobile-chat-send').click();
            }
        });
        
        console.log('Mobile chat interface created');
    };
    
    // Add a message to mobile chat
    window.PMV_MobileChat.addMobileMessage = function(type, sender, content) {
        const messageClass = `pmv-mobile-message pmv-mobile-message-${type}`;
        const $message = $(`
            <div class="${messageClass}">
                <span class="pmv-mobile-message-sender">${escapeHtml(sender)}:</span>
                <span class="pmv-mobile-message-content">${escapeHtml(content)}</span>
            </div>
        `);
        $('.pmv-mobile-chat-messages').append($message);
    };
    
    // Scroll mobile chat to bottom
    window.PMV_MobileChat.scrollMobileChat = function() {
        const $messages = $('.pmv-mobile-chat-messages');
        if ($messages.length) {
            $messages.scrollTop($messages[0].scrollHeight);
        }
    };
    
    // Update mobile chat with new messages from standard chat
    window.PMV_MobileChat.syncMobileChat = function() {
        // Only process if mobile chat is active
        if (!$('.pmv-mobile-chat.active').length) return;
        
        // Get all messages from standard chat
        const standardMessages = [];
        $('#chat-history .chat-message').each(function() {
            const $msg = $(this);
            
            // Skip typing indicators
            if ($msg.hasClass('typing-indicator')) return;
            
            let type = 'bot';
            if ($msg.hasClass('user')) type = 'user';
            if ($msg.hasClass('error')) type = 'error';
            
            // Extract sender name
            let sender = '';
            const $strong = $msg.find('strong');
            if ($strong.length) {
                sender = $strong.text().replace(/:$/, '').trim();
            } else {
                if (type === 'user') sender = 'You';
                else if (type === 'bot') sender = 'AI';
                else sender = 'System';
            }
            
            // Extract content
            let content = $msg.text();
            if (sender) {
                content = content.replace(new RegExp(`^${sender}:\\s*`), '').trim();
            }
            
            standardMessages.push({
                type: type,
                sender: sender,
                content: content
            });
        });
        
        // Count mobile messages
        const mobileMessageCount = $('.pmv-mobile-message').length;
        
        // If we have more standard messages than mobile messages, add the new ones
        if (standardMessages.length > mobileMessageCount) {
            const newMessages = standardMessages.slice(mobileMessageCount);
            
            newMessages.forEach(function(msg) {
                window.PMV_MobileChat.addMobileMessage(msg.type, msg.sender, msg.content);
            });
            
            window.PMV_MobileChat.scrollMobileChat();
        }
    };
    
    // Reset the mobile chat state
    window.PMV_MobileChat.resetMobileChat = function() {
        $('.pmv-mobile-chat').removeClass('active');
    };
    
    // Handle orientation change for mobile devices
    window.PMV_MobileChat.handleOrientationChange = function() {
        // Force scroll after a delay to ensure layout is complete
        setTimeout(function() {
            window.PMV_MobileChat.scrollMobileChat();
            window.forceScrollToBottom();
        }, 300);
    };
    
    // Activate mobile mode when switching from desktop
    window.PMV_MobileChat.activateMobileMode = function() {
        console.log('Switching to mobile mode');
        
        // Set up mobile elements
        setupMobileMenuToggle();
        setupMobileSidebarCloseButton();
        
        // Get character info from the current chat
        const $modal = $('#png-modal').find('.png-modal-content');
        const characterData = $modal.data('characterData');
        
        if (characterData) {
            const character = window.PMV_Chat.extractCharacterInfo(characterData);
            setupMobileChatInterface(character);
        }
        
        // Apply mobile layout
        applyMobileLayout();
    };
    
    // Function to enhance the mobile sidebar behavior
    window.PMV_MobileChat.enhanceMobileSidebar = function() {
        console.log('Enhancing mobile sidebar functionality');
        
        // Make sure we have a bottom close button container
        if ($('#mobile-button-container').length === 0) {
            $('#chat-sidebar').append(`
                <div id="mobile-button-container" class="mobile-button-container">
                    <button class="mobile-close-btn">Close Sidebar</button>
                </div>
            `);
        }
        
        // Fix the event handling for the mobile menu button
        // Remove any existing event handlers first to prevent duplication
        $(document).off('click', '#mobile-menu-toggle');
        $(document).on('click', '#mobile-menu-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            console.log("Mobile menu toggle clicked");
            
            // Toggle the sidebar
            const $sidebar = $('#chat-sidebar');
            $sidebar.removeClass('minimized');
            
            // Update input row position
            $('#chat-input-row').css('left', window.innerWidth <= 768 ? '0' : '280px');
            
            // Force scroll to ensure things are visible
            window.forceScrollToBottom();
        });
        
        // Fix the close button event handling
        $(document).off('click', '.mobile-close-btn');
        $(document).on('click', '.mobile-close-btn', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            const $sidebar = $('#chat-sidebar');
            $sidebar.addClass('minimized');
            
            // Update input row on mobile
            if (window.innerWidth <= 768) {
                $('#chat-input-row').css('left', '0');
            }
            
            // Force scroll to bottom after sidebar closed
            setTimeout(window.forceScrollToBottom, 100);
        });
    };
    
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
    
    // Initialize mobile chat system on document ready
    $(document).ready(function() {
        // Enhance mobile sidebar
        window.PMV_MobileChat.enhanceMobileSidebar();
        
        // Set up periodic sync for mobile chat
        setInterval(function() {
            if (window.innerWidth <= mobileConfig.breakpoint && $('.pmv-mobile-chat.active').length) {
                window.PMV_MobileChat.syncMobileChat();
            }
        }, 1000);
        
        // Handle orientation changes
        window.addEventListener('orientationchange', function() {
            if (window.innerWidth <= mobileConfig.breakpoint && $('.pmv-mobile-chat.active').length) {
                setTimeout(window.PMV_MobileChat.scrollMobileChat, 200);
            }
        });
        
        // Apply fixes when chat is initialized
        $(document).on('click', '.png-chat-button', function() {
            setTimeout(window.enhanceChatLayout, 100);
            setTimeout(window.enhanceChatLayout, 500);
            setTimeout(window.PMV_MobileChat.enhanceMobileSidebar, 100);
        });
        
        // Store initial width for responsive checks
        window.lastMobileWidth = window.innerWidth;
    });
})(jQuery);
