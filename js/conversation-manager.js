/**
 * PMV Conversation Manager - FIXED VERSION
 * Handles saving, loading, and managing chat conversations
 * Version: 4.14-FIXED
 */
(function($) {
    'use strict';
    
    // Ensure jQuery is loaded
    if (typeof $ === 'undefined') {
        console.error('PMV: jQuery is not loaded!');
        return;
    }
    
            // Wait for document ready and ensure dependencies
        $(document).ready(function() {
            // Global error handler for unhandled promise rejections
            window.addEventListener('unhandledrejection', function(event) {
                console.error('PMV: Unhandled promise rejection:', event.reason);
            });
            
            // Global error handler for JavaScript errors
            window.addEventListener('error', function(event) {
                if (event.filename && event.filename.includes('conversation-manager')) {
                    console.error('PMV: JavaScript error in conversation manager:', event.error);
                }
            });
        
        // Validate environment before initialization
        function validateEnvironment() {
            const issues = [];
            
            if (typeof jQuery === 'undefined') {
                issues.push('jQuery not loaded');
            }
            
            if (typeof pmv_ajax_object === 'undefined') {
                issues.push('AJAX object not available');
            } else {
                if (!pmv_ajax_object.ajax_url) issues.push('AJAX URL missing');
                if (!pmv_ajax_object.nonce) issues.push('Nonce missing');
            }
            
            return issues;
        }
        
        // Wait for AJAX object with improved error handling
        function waitForAjaxObject(retries = 15, delay = 300) {
            const issues = validateEnvironment();
            
            if (issues.length === 0) {
                initializeConversationManager();
                return;
            }
            
            if (retries > 0) {
                setTimeout(() => waitForAjaxObject(retries - 1, delay), delay);
                return;
            }
            
            showInitializationError(issues);
        }
        
        function showInitializationError(issues) {
            const errorHtml = `
                <div class="pmv-init-error" style="
                    position: fixed; top: 20px; right: 20px; z-index: 999999;
                    background: #ffebee; border: 2px solid #f44336; border-radius: 8px;
                    padding: 20px; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                ">
                    <h4 style="margin: 0 0 10px 0; color: #d32f2f;">⚠️ Conversation System Error</h4>
                    <p style="margin: 0 0 10px 0; color: #666;">Initialization failed:</p>
                    <ul style="margin: 0 0 15px 20px; color: #666;">
                        ${issues.map(issue => `<li>${issue}</li>`).join('')}
                    </ul>
                    <div>
                        <button onclick="location.reload()" style="
                            background: #1976d2; color: white; border: none; padding: 8px 16px;
                            border-radius: 4px; cursor: pointer; margin-right: 8px;
                        ">🔄 Reload Page</button>
                        <button onclick="this.parentElement.parentElement.remove()" style="
                            background: #666; color: white; border: none; padding: 8px 16px;
                            border-radius: 4px; cursor: pointer;
                        ">✕ Dismiss</button>
                    </div>
                </div>
            `;
            $('body').append(errorHtml);
        }
        
        function initializeConversationManager() {
            // Create conversation manager with improved error handling
            window.PMV_ConversationManager = {
                // Core state
                currentConversationId: null,
                characterId: null,
                characterData: null,
                saveInProgress: false,
                hasUnsavedChanges: false,
                isInitialized: false,
                
                // Timers and observers
                autoSaveTimer: null,
                messageObserver: null,
                saveDebounceTimer: null,
                
                // Initialize with enhanced error handling
                init: function(characterData, characterId) {
                    try {
                        console.log('=== PMV Conversation Manager Init ===', {
                            characterData: characterData,
                            characterId: characterId,
                            ajaxUrl: pmv_ajax_object?.ajax_url,
                            userLoggedIn: pmv_ajax_object?.is_logged_in
                        });
                        
                        // Allow re-initialization if sidebar was recreated
                        const sidebarExists = $('.conversation-sidebar').length > 0;
                        if (this.isInitialized && !sidebarExists) {
                            console.log('PMV: Sidebar was removed, resetting initialization state');
                            this.isInitialized = false;
                        }
                        
                        if (this.isInitialized) {
                            console.log('PMV: Already initialized, skipping');
                            // But still refresh the guest message if needed
                            if (!pmv_ajax_object.is_logged_in && $('.conversation-list').length > 0) {
                                const hasContent = $('.conversation-list').children().length > 0;
                                if (!hasContent) {
                                    this.showGuestMessage();
                                }
                            }
                            return true;
                        }
                        
                        this.characterData = characterData;
                        this.characterId = characterId;
                        
                        // Validate inputs
                        if (!characterId) {
                            throw new Error('Character ID is required');
                        }
                        
                        // Check system prerequisites
                        const systemCheck = this.checkSystemPrerequisites();
                        if (!systemCheck.success) {
                            throw new Error('System check failed: ' + systemCheck.error);
                        }
                        
                        // Initialize components
                        this.setupEventHandlers();
                        this.setupMessageObserver();
                        
                        // Wait for sidebar DOM to be ready before showing content
                        setTimeout(() => {
                            if (pmv_ajax_object.is_logged_in) {
                                this.loadConversationList();
                            } else {
                                this.showGuestMessage();
                            }
                        }, 100);
                        
                        this.isInitialized = true;
                        console.log('PMV: Conversation manager initialized successfully');
                        
                        return true;
                        
                    } catch (error) {
                        console.error('PMV: Initialization failed:', error);
                        this.showError('Initialization failed: ' + error.message);
                        return false;
                    }
                },
                
                // Check system prerequisites
                checkSystemPrerequisites: function() {
                    try {
                        if (!pmv_ajax_object) {
                            return { success: false, error: 'AJAX object not available' };
                        }
                        
                        if (!pmv_ajax_object.ajax_url) {
                            return { success: false, error: 'AJAX URL not configured' };
                        }
                        
                        if (!pmv_ajax_object.nonce) {
                            return { success: false, error: 'Security nonce not available' };
                        }
                        
                        // Test jQuery functionality
                        try {
                            $('<div>').remove();
                        } catch (e) {
                            return { success: false, error: 'jQuery not functioning properly' };
                        }
                        
                        return { success: true };
                        
                    } catch (error) {
                        return { success: false, error: error.message };
                    }
                },
                
                // Enhanced event handler setup with better error handling
                setupEventHandlers: function() {
                    try {
                        const self = this;
                        console.log('PMV: Setting up event handlers...');
                        
                        // Remove existing handlers to prevent duplicates
                        $(document).off('.pmvConv');
                        $(window).off('.pmvConv');
                        
                        // Conversation management buttons
                        $(document).on('click.pmvConv', '#new-conversation', function(e) {
                            e.preventDefault();
                            self.safeExecute('startNewConversation', function() {
                                self.startNewConversation();
                            });
                        });
                        
                        $(document).on('click.pmvConv', '#save-conversation', function(e) {
                            e.preventDefault();
                            self.safeExecute('manualSave', function() {
                                self.manualSave();
                            });
                        });
                        
                        $(document).on('click.pmvConv', '#export-conversation', function(e) {
                            e.preventDefault();
                            self.safeExecute('exportConversation', function() {
                                self.exportConversation();
                            });
                        });
                        
                        // Conversation list interactions
                        $(document).on('click.pmvConv', '.conversation-item', function(e) {
                            if ($(e.target).hasClass('delete-conversation') || $(e.target).closest('.delete-conversation').length) {
                                return; // Don't load conversation if delete button was clicked
                            }
                            
                            const conversationId = $(this).data('conversation-id');
                            if (conversationId) {
                                self.safeExecute('loadConversation', function() {
                                    self.loadConversation(conversationId);
                                });
                            }
                        });
                        
                        $(document).on('click.pmvConv', '.delete-conversation', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const conversationId = $(this).data('id');
                            if (conversationId) {
                                self.safeExecute('deleteConversation', function() {
                                    self.deleteConversation(conversationId);
                                });
                            }
                        });
                        
                        // Add event handler for editing conversation titles
                        $(document).on('click.pmvConv', '.edit-conversation-title', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const conversationId = $(this).data('id');
                            const currentTitle = $(this).data('title');
                            if (conversationId && currentTitle) {
                                self.safeExecute('editConversationTitle', function() {
                                    self.editConversationTitle(conversationId, currentTitle);
                                });
                            }
                        });
                        
                        // Add event handler for clicking on conversation titles to edit
                        $(document).on('click.pmvConv', '.conversation-title', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const conversationId = $(this).data('conversation-id');
                            const currentTitle = $(this).data('original-title');
                            if (conversationId && currentTitle) {
                                self.safeExecute('editConversationTitle', function() {
                                    self.editConversationTitle(conversationId, currentTitle);
                                });
                            }
                        });
                        
                        // Add event handler for close sidebar button
                        $(document).on('click.pmvConv', '.close-sidebar-btn', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('PMV: Close sidebar button clicked');
                            if (window.forceCloseSidebar) {
                                window.forceCloseSidebar();
                            } else {
                                $('.conversation-sidebar').removeClass('open');
                            }
                        });
                        
                        // Keyboard shortcuts
                        $(document).on('keydown.pmvConv', function(e) {
                            try {
                                // Ctrl+S / Cmd+S to save
                                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                                    e.preventDefault();
                                    if (self.hasUnsavedChanges && !self.saveInProgress) {
                                        self.manualSave();
                                    }
                                }
                                
                                // Ctrl+N / Cmd+N for new conversation
                                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                                    e.preventDefault();
                                    self.startNewConversation();
                                }
                            } catch (error) {
                                console.error('PMV: Keyboard shortcut error:', error);
                            }
                        });
                        
                        // Before unload warning
                        $(window).on('beforeunload.pmvConv', function() {
                            if (self.hasUnsavedChanges) {
                                return 'You have unsaved changes. Are you sure you want to leave?';
                            }
                        });
                        
                        console.log('PMV: Event handlers set up successfully');
                        
                    } catch (error) {
                        console.error('PMV: Failed to setup event handlers:', error);
                        throw error;
                    }
                },
                
                // Safe execution wrapper for all functions
                safeExecute: function(functionName, func) {
                    try {
                        console.log(`PMV: Executing ${functionName}...`);
                        return func();
                    } catch (error) {
                        console.error(`PMV: Error in ${functionName}:`, error);
                        this.showToast(`Error in ${functionName}: ${error.message}`, 'error');
                        return null;
                    }
                },
                
                // Enhanced message observer
                setupMessageObserver: function() {
                    try {
                        const self = this;
                        console.log('PMV: Setting up message observer...');
                        
                        // Custom event listener for message additions
                        $(document).on('pmv:message:added', function(e, messageData) {
                            self.onMessageAdded(messageData);
                        });
                        
                        // DOM observer for chat history changes
                        const chatHistory = document.getElementById('chat-history');
                        if (chatHistory && window.MutationObserver) {
                            if (this.messageObserver) {
                                this.messageObserver.disconnect();
                            }
                            
                            this.messageObserver = new MutationObserver(function(mutations) {
                                let hasNewMessages = false;
                                
                                mutations.forEach(function(mutation) {
                                    if (mutation.addedNodes.length > 0) {
                                        Array.from(mutation.addedNodes).forEach(function(node) {
                                            if (node.nodeType === Node.ELEMENT_NODE && 
                                                (node.classList.contains('chat-message') || 
                                                 node.querySelector('.chat-message'))) {
                                                hasNewMessages = true;
                                            }
                                        });
                                    }
                                });
                                
                                if (hasNewMessages) {
                                    self.onMessageAdded();
                                }
                            });
                            
                            this.messageObserver.observe(chatHistory, {
                                childList: true,
                                subtree: true
                            });
                            
                            console.log('PMV: Message observer active');
                        }
                        
                                            } catch (error) {
                            console.error('PMV: Failed to setup message observer:', error);
                        }
                        
                        // Add CSS animations for new message indicator
                        this.addScrollIndicatorCSS();
                    },
                
                // Handle new message additions
                onMessageAdded: function(messageData) {
                    try {
                        console.log('PMV: New message detected:', messageData);
                        this.hasUnsavedChanges = true;
                        this.updateSaveButton();
                        this.debounceAutoSave();
                    } catch (error) {
                        console.error('PMV: Error handling new message:', error);
                    }
                },
                
                // Debounced auto-save
                debounceAutoSave: function() {
                    if (this.saveDebounceTimer) {
                        clearTimeout(this.saveDebounceTimer);
                    }
                    
                    // Auto-save disabled by default - only manual saves
                    // Uncomment below to enable auto-save with 30 second delay
                    /*
                    this.saveDebounceTimer = setTimeout(() => {
                        if (this.hasUnsavedChanges && !this.saveInProgress && pmv_ajax_object.is_logged_in) {
                            console.log('PMV: Auto-saving...');
                            this.manualSave();
                        }
                    }, 30000);
                    */
                },
                
                // Update save button appearance
                updateSaveButton: function() {
                    try {
                        const $saveBtn = $('#save-conversation');
                        if ($saveBtn.length === 0) return;
                        
                        if (this.hasUnsavedChanges && !this.saveInProgress) {
                            $saveBtn.addClass('has-changes').prop('disabled', false);
                            if (!$saveBtn.data('original-text')) {
                                $saveBtn.data('original-text', $saveBtn.text());
                            }
                        } else {
                            $saveBtn.removeClass('has-changes');
                            if (this.saveInProgress) {
                                $saveBtn.prop('disabled', true);
                            } else {
                                $saveBtn.prop('disabled', false);
                                const originalText = $saveBtn.data('original-text') || '💾 Save';
                                $saveBtn.text(originalText);
                            }
                        }
                    } catch (error) {
                        console.error('PMV: Error updating save button:', error);
                    }
                },
                
                // Start new conversation
                startNewConversation: function() {
                    try {
                        console.log('PMV: Starting new conversation...');
                        
                        if (this.hasUnsavedChanges) {
                            if (!confirm('You have unsaved changes. Start a new conversation anyway?')) {
                                return;
                            }
                        }
                        
                        // Reset state
                        this.currentConversationId = null;
                        this.hasUnsavedChanges = false;
                        
                        // Clear chat history
                        const $chatHistory = $('#chat-history');
                        $chatHistory.empty();
                        
                        // Add initial message if available
                        if (this.characterData && this.characterData.first_mes) {
                            $chatHistory.append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">${this.escapeHtml(this.characterData.name)}:</span>
                                    <span class="chat-message-content-wrapper">${this.escapeHtml(this.characterData.first_mes)}</span>
                                </div>
                            `);
                        }
                        
                        // Update UI
                        $('.conversation-item').removeClass('active');
                        this.updateSaveButton();
                        this.scrollToBottom();
                        
                        console.log('PMV: New conversation started successfully');
                        
                    } catch (error) {
                        console.error('PMV: Error starting new conversation:', error);
                        this.showToast('Failed to start new conversation', 'error');
                    }
                },
                
                // Enhanced manual save with better error handling
                manualSave: function() {
                    try {
                        console.log('PMV: Manual save initiated...');
                        
                        if (this.saveInProgress) {
                            console.log('PMV: Save already in progress');
                            this.showToast('Save already in progress...', 'warning');
                            return;
                        }
                        
                        if (!pmv_ajax_object.is_logged_in) {
                            this.showToast('Please login to save conversations', 'error');
                            return;
                        }
                        
                        const messages = this.collectMessages();
                        if (!messages || messages.length === 0) {
                            this.showToast('No messages to save', 'warning');
                            return;
                        }
                        
                        console.log('PMV: Collected', messages.length, 'messages for save');
                        
                        // Generate default title
                        let defaultTitle = 'Chat with ' + (this.characterData?.name || 'Character');
                        const firstUserMessage = messages.find(m => m.role === 'user');
                        if (firstUserMessage) {
                            defaultTitle = firstUserMessage.content.substring(0, 50);
                            if (firstUserMessage.content.length > 50) {
                                defaultTitle += '...';
                            }
                        }
                        
                        // Show title input dialog
                        this.showTitleInputDialog(defaultTitle, (userTitle) => {
                            if (userTitle === null) {
                                // User cancelled
                                return;
                            }
                            
                            const conversationData = {
                                id: this.currentConversationId,
                                character_id: this.characterId,
                                character_name: this.characterData?.name || 'Unknown Character',
                                title: userTitle || defaultTitle,
                                messages: messages
                            };
                            
                            this.performSave(conversationData);
                        });
                        
                    } catch (error) {
                        console.error('PMV: Manual save error:', error);
                        this.showToast('Save failed: ' + error.message, 'error');
                    }
                },
                
                // Show title input dialog for conversation titles
                showTitleInputDialog: function(defaultTitle, callback) {
                    try {
                        // Remove any existing dialog
                        $('.pmv-title-dialog').remove();
                        
                        const dialogId = 'pmv-title-dialog-' + Date.now();
                        const $dialog = $(`
                            <div id="${dialogId}" class="pmv-title-dialog" style="
                                position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                                background: rgba(0, 0, 0, 0.8); z-index: 999999; 
                                display: flex; align-items: center; justify-content: center;
                                backdrop-filter: blur(5px);
                            ">
                                <div class="dialog-content" style="
                                    background: #2d2d2d; border: 1px solid #444; border-radius: 12px;
                                    padding: 30px; max-width: 500px; width: 90%; 
                                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
                                    animation: dialogSlideIn 0.3s ease-out;
                                ">
                                    <h3 style="
                                        margin: 0 0 20px 0; color: #ffffff; font-size: 20px;
                                        text-align: center; font-weight: 600;
                                    ">💾 Save Conversation</h3>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <label for="conversation-title" style="
                                            display: block; margin-bottom: 8px; color: #e0e0e0;
                                            font-weight: 500; font-size: 14px;
                                        ">Conversation Title:</label>
                                        <input type="text" id="conversation-title" 
                                               value="${this.escapeHtml(defaultTitle)}"
                                               placeholder="Enter a title for this conversation..."
                                               style="
                                                   width: 100%; padding: 12px 16px; 
                                                   background: #1a1a1a; border: 2px solid #444;
                                                   border-radius: 8px; color: #ffffff; font-size: 16px;
                                                   box-sizing: border-box; transition: border-color 0.2s ease;
                                               "
                                               maxlength="200"
                                        >
                                    </div>
                                    
                                    <div style="
                                        display: flex; gap: 12px; justify-content: flex-end;
                                        margin-top: 25px;
                                    ">
                                        <button class="cancel-save" style="
                                            background: #555; color: #ffffff; border: none;
                                            padding: 12px 24px; border-radius: 8px; cursor: pointer;
                                            font-size: 14px; font-weight: 500; transition: background 0.2s ease;
                                        ">Cancel</button>
                                        <button class="confirm-save" style="
                                            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                                            color: #ffffff; border: none; padding: 12px 24px;
                                            border-radius: 8px; cursor: pointer; font-size: 14px;
                                            font-weight: 600; transition: all 0.2s ease;
                                        ">💾 Save Conversation</button>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        // Add CSS animations
                        if (!$('#pmv-dialog-css').length) {
                            $('head').append(`
                                <style id="pmv-dialog-css">
                                    @keyframes dialogSlideIn {
                                        from {
                                            opacity: 0;
                                            transform: scale(0.9) translateY(-20px);
                                        }
                                        to {
                                            opacity: 1;
                                            transform: scale(1) translateY(0);
                                        }
                                    }
                                    
                                    .pmv-title-dialog .dialog-content input:focus {
                                        outline: none;
                                        border-color: #007bff;
                                        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
                                    }
                                    
                                    .pmv-title-dialog .cancel-save:hover {
                                        background: #666 !important;
                                    }
                                    
                                    .pmv-title-dialog .confirm-save:hover {
                                        background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
                                        transform: translateY(-1px);
                                        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
                                    }
                                </style>
                            `);
                        }
                        
                        // Append to body
                        $('body').append($dialog);
                        
                        // Focus on input
                        setTimeout(() => {
                            $(`#${dialogId} #conversation-title`).focus().select();
                        }, 100);
                        
                        // Event handlers
                        const self = this;
                        
                        // Cancel button
                        $(`#${dialogId} .cancel-save`).on('click', function() {
                            $dialog.fadeOut(200, function() {
                                $(this).remove();
                                callback(null);
                            });
                        });
                        
                        // Confirm button
                        $(`#${dialogId} .confirm-save`).on('click', function() {
                            const title = $(`#${dialogId} #conversation-title`).val().trim();
                            if (!title) {
                                self.showToast('Please enter a title for the conversation', 'warning');
                                return;
                            }
                            
                            $dialog.fadeOut(200, function() {
                                $(this).remove();
                                callback(title);
                            });
                        });
                        
                        // Enter key to save
                        $(`#${dialogId} #conversation-title`).on('keydown', function(e) {
                            if (e.key === 'Enter') {
                                $(`#${dialogId} .confirm-save`).click();
                            } else if (e.key === 'Escape') {
                                $(`#${dialogId} .cancel-save`).click();
                            }
                        });
                        
                        // Click outside to cancel
                        $(`#${dialogId}`).on('click', function(e) {
                            if (e.target === this) {
                                $(`#${dialogId} .cancel-save`).click();
                            }
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error showing title dialog:', error);
                        // Fallback to default title if dialog fails
                        callback(defaultTitle);
                    }
                },
                
                // Edit existing conversation title
                editConversationTitle: function(conversationId, currentTitle) {
                    try {
                        console.log('PMV: Editing conversation title for ID:', conversationId, 'Current title:', currentTitle);
                        
                        // Show title input dialog with current title
                        this.showTitleInputDialog(currentTitle, (newTitle) => {
                            if (newTitle === null || newTitle === currentTitle) {
                                // User cancelled or title unchanged
                                return;
                            }
                            
                            // Update the title in the database
                            this.updateConversationTitle(conversationId, newTitle);
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error editing conversation title:', error);
                        this.showToast('Failed to edit title: ' + error.message, 'error');
                    }
                },
                
                // Update conversation title in database
                updateConversationTitle: function(conversationId, newTitle) {
                    try {
                        console.log('PMV: Updating conversation title to:', newTitle);
                        
                        const self = this;
                        
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'pmv_update_conversation_title',
                                conversation_id: conversationId,
                                title: newTitle,
                                nonce: pmv_ajax_object.nonce
                            },
                            timeout: 15000,
                            success: function(response) {
                                if (response.success) {
                                    self.showToast('Title updated successfully ✓', 'success');
                                    // Refresh the conversation list to show updated title
                                    self.loadConversationList();
                                } else {
                                    const errorMsg = response.data?.message || 'Failed to update title';
                                    self.showToast('Update failed: ' + errorMsg, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('PMV: Update title error:', { xhr, status, error });
                                self.showToast('Error updating title: ' + error, 'error');
                            }
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error updating conversation title:', error);
                        this.showToast('Failed to update title: ' + error.message, 'error');
                    }
                },
                
                // Perform the actual save operation
                performSave: function(conversationData) {
                    const self = this;
                    
                    try {
                        console.log('PMV: Performing save...', conversationData);
                        
                        // Update UI
                        const $saveBtn = $('#save-conversation');
                        const originalText = $saveBtn.data('original-text') || $saveBtn.text();
                        $saveBtn.prop('disabled', true).text('💾 Saving...');
                        this.saveInProgress = true;
                        
                        // Show progress
                        this.showToast('Saving conversation...', 'info');
                        
                        // Prepare AJAX data
                        const ajaxData = {
                            action: 'pmv_save_conversation',
                            conversation: JSON.stringify(conversationData),
                            nonce: pmv_ajax_object.nonce
                        };
                        
                        console.log('PMV: AJAX save request starting...');
                        
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            dataType: 'json',
                            data: ajaxData,
                            timeout: 30000,
                            success: function(response) {
                                self.handleSaveSuccess(response, originalText);
                            },
                            error: function(xhr, status, error) {
                                self.handleSaveError(xhr, status, error, originalText);
                            },
                            complete: function() {
                                self.saveInProgress = false;
                                $saveBtn.prop('disabled', false);
                                self.updateSaveButton();
                            }
                        });
                        
                    } catch (error) {
                        this.saveInProgress = false;
                        throw error;
                    }
                },
                
                // Handle successful save
                handleSaveSuccess: function(response, originalText) {
                    try {
                        console.log('PMV: Save response:', response);
                        
                        if (response.success && response.data) {
                            this.currentConversationId = response.data.id;
                            this.hasUnsavedChanges = false;
                            this.showToast('Conversation saved successfully ✓', 'success');
                            this.loadConversationList(); // Refresh list
                            
                            console.log('PMV: Save successful, ID:', response.data.id);
                        } else {
                            const errorMsg = response.data?.message || response.data || 'Save failed';
                            console.error('PMV: Save failed:', errorMsg);
                            this.showToast('Save failed: ' + errorMsg, 'error');
                        }
                    } catch (error) {
                        console.error('PMV: Error handling save success:', error);
                        this.showToast('Error processing save response', 'error');
                    }
                },
                
                // Handle save error
                handleSaveError: function(xhr, status, error, originalText) {
                    console.error('PMV: Save AJAX error:', { xhr, status, error });
                    console.error('PMV: Response text:', xhr.responseText);
                    
                    let errorMsg = 'Failed to save conversation';
                    
                    switch (xhr.status) {
                        case 0:
                            errorMsg = 'Network connection error';
                            break;
                        case 403:
                            errorMsg = 'Permission denied - please refresh page';
                            break;
                        case 404:
                            errorMsg = 'Save endpoint not found';
                            break;
                        case 500:
                            errorMsg = 'Server error - check logs';
                            break;
                        default:
                            if (xhr.responseJSON?.data?.message) {
                                errorMsg = xhr.responseJSON.data.message;
                            }
                    }
                    
                    this.showToast('Save error: ' + errorMsg, 'error');
                },
                
                // Collect messages from chat
                collectMessages: function() {
                    try {
                        const messages = [];
                        
                        $('#chat-history .chat-message').each(function() {
                            const $msg = $(this);
                            
                            // Skip system messages
                            if ($msg.hasClass('typing-indicator') || $msg.hasClass('error') || $msg.hasClass('system')) {
                                return;
                            }
                            
                            let role = $msg.hasClass('user') ? 'user' : 'assistant';
                            let content = '';
                            let hasImages = false;
                            
                            // Get content
                            const $wrapper = $msg.find('.chat-message-content-wrapper');
                            let metadata = {};
                            
                            if ($wrapper.length) {
                                // Check if this message contains an image
                                const $img = $wrapper.find('img');
                                if ($img.length > 0) {
                                    // This is an image message - extract the image URL and prompt
                                    const imgSrc = $img.attr('src');
                                    if (imgSrc) {
                                        content = `[Generated Image: ${imgSrc}]`;
                                        hasImages = true;
                                        
                                        // Extract prompt from data attribute or global store
                                        const prompt = $img.attr('data-prompt') || 
                                                     (window.pmvGeneratedImagePrompts && window.pmvGeneratedImagePrompts[imgSrc]) || 
                                                     '';
                                        if (prompt) {
                                            metadata.prompt = prompt;
                                        }
                                    } else {
                                        content = $wrapper.text().trim();
                                    }
                                } else {
                                    content = $wrapper.text().trim();
                                }
                            } else {
                                content = $msg.text().trim();
                            }
                            
                            // Clean up content
                            const $speaker = $msg.find('.speaker-name');
                            if ($speaker.length) {
                                const speakerText = $speaker.text();
                                if (content.startsWith(speakerText)) {
                                    content = content.substring(speakerText.length).replace(/^:\s*/, '').trim();
                                }
                            }
                            
                            if (content) {
                                const messageData = {
                                    role: role,
                                    content: content,
                                    timestamp: new Date().toISOString(),
                                    hasImages: hasImages
                                };
                                
                                // Add metadata if available (e.g., prompt for images)
                                if (Object.keys(metadata).length > 0) {
                                    messageData.metadata = metadata;
                                    // Also add prompt at top level for easier access
                                    if (metadata.prompt) {
                                        messageData.prompt = metadata.prompt;
                                    }
                                }
                                
                                messages.push(messageData);
                            }
                        });
                        
                        console.log('PMV: Collected', messages.length, 'messages');
                        return messages;
                        
                    } catch (error) {
                        console.error('PMV: Error collecting messages:', error);
                        return [];
                    }
                },
                
                // Load conversation list
                loadConversationList: function() {
                    try {
                        if (!pmv_ajax_object.is_logged_in) {
                            this.showGuestMessage();
                            return;
                        }
                        
                        console.log('PMV: Loading conversation list...');
                        
                        const $list = $('.conversation-list');
                        $list.html(`
                            <div class="loading-conversations" style="text-align: center; padding: 20px; color: #666;">
                                <div class="spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 10px;"></div>
                                Loading conversations...
                            </div>
                        `);
                        
                        const self = this;
                        
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'pmv_get_conversations',
                                character_id: this.characterId,
                                nonce: pmv_ajax_object.nonce
                            },
                            timeout: 15000,
                            success: function(response) {
                                console.log('PMV: Load conversations response:', response);
                                if (response.success) {
                                    self.displayConversations(response.data || []);
                                } else {
                                    self.showError('Failed to load conversations: ' + (response.data?.message || 'Unknown error'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('PMV: Load conversations error:', { xhr, status, error });
                                self.showError('Error loading conversations: ' + error);
                            }
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error in loadConversationList:', error);
                        this.showError('Failed to load conversations: ' + error.message);
                    }
                },
                
                // Display conversations in list
                displayConversations: function(conversations) {
                    try {
                        console.log('PMV: Displaying', conversations.length, 'conversations');
                        
                        const $list = $('.conversation-list');
                        
                        if (!conversations || conversations.length === 0) {
                            $list.html(`
                                <div class="no-conversations" style="text-align: center; padding: 40px 20px; color: #999;">
                                    <p style="font-size: 16px; margin-bottom: 10px;">📝 No saved conversations</p>
                                    <p>Start chatting and save your conversations!</p>
                                </div>
                            `);
                            return;
                        }
                        
                        let html = '';
                        conversations.forEach(conv => {
                            const date = new Date(conv.updated_at || conv.created_at).toLocaleDateString();
                            const time = new Date(conv.updated_at || conv.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            const isActive = conv.id == this.currentConversationId;
                            
                            let displayTitle = conv.title;
                            if (displayTitle.length > 40) {
                                displayTitle = displayTitle.substring(0, 40) + '...';
                            }
                            
                            html += `
                                <div class="conversation-item ${isActive ? 'active' : ''}" 
                                     data-conversation-id="${conv.id}" 
                                     title="${this.escapeHtml(conv.title)}"
                                     style="position: relative; padding: 15px; margin-bottom: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; cursor: pointer; transition: all 0.2s ease;">
                                    <div class="conversation-info" style="padding-right: 70px;">
                                        <div class="conversation-title" style="font-weight: 500; margin-bottom: 5px; font-size: 14px; word-break: break-word; cursor: pointer;" data-conversation-id="${conv.id}" data-original-title="${this.escapeHtml(conv.title)}">${this.escapeHtml(displayTitle)}</div>
                                        <div class="conversation-date" style="font-size: 12px; color: rgba(255, 255, 255, 0.6);">
                                            ${date} ${time} • ${conv.message_count} message${conv.message_count !== 1 ? 's' : ''}
                                        </div>
                                    </div>
                                    <div class="conversation-actions" style="position: absolute; top: 8px; right: 8px; display: flex; gap: 4px;">
                                        <button class="edit-conversation-title" data-id="${conv.id}" data-title="${this.escapeHtml(conv.title)}" title="Edit title" 
                                                style="background: transparent; border: none; color: #007bff; cursor: pointer; font-size: 14px; opacity: 0.7; padding: 4px 6px; border-radius: 3px; transition: all 0.2s ease;">
                                            ✏️
                                        </button>
                                        <button class="delete-conversation" data-id="${conv.id}" title="Delete conversation" 
                                                style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 14px; opacity: 0.7; padding: 4px 6px; border-radius: 3px; transition: all 0.2s ease;">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $list.html(html);
                        console.log('PMV: Conversations displayed successfully');
                        
                    } catch (error) {
                        console.error('PMV: Error displaying conversations:', error);
                        this.showError('Error displaying conversations: ' + error.message);
                    }
                },
                
                // Load specific conversation
                loadConversation: function(conversationId) {
                    try {
                        if (!conversationId) return;
                        
                        if (this.hasUnsavedChanges) {
                            if (!confirm('You have unsaved changes. Load this conversation anyway?')) {
                                return;
                            }
                        }
                        
                        console.log('PMV: Loading conversation:', conversationId);
                        
                        const self = this;
                        
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'pmv_get_conversation',
                                conversation_id: conversationId,
                                nonce: pmv_ajax_object.nonce
                            },
                            timeout: 15000,
                            success: function(response) {
                                if (response.success && response.data) {
                                    self.displayConversation(response.data);
                                    self.currentConversationId = conversationId;
                                    self.hasUnsavedChanges = false;
                                    
                                    $('.conversation-item').removeClass('active');
                                    $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
                                    
                                    self.updateSaveButton();
                                    self.showToast('Conversation loaded ✓', 'success');
                                } else {
                                    self.showToast('Failed to load conversation', 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('PMV: Load conversation error:', { xhr, status, error });
                                self.showToast('Error loading conversation: ' + error, 'error');
                            }
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error in loadConversation:', error);
                        this.showToast('Failed to load conversation: ' + error.message, 'error');
                    }
                },
                
                // Display loaded conversation
                displayConversation: function(conversation) {
                    try {
                        console.log('PMV: Displaying conversation with', conversation.messages?.length || 0, 'messages');
                        console.log('PMV: Conversation data:', conversation);
                        
                        const $chatHistory = $('#chat-history');
                        $chatHistory.empty();
                        
                        if (conversation.messages && Array.isArray(conversation.messages)) {
                            conversation.messages.forEach((msg, index) => {
                                console.log('PMV: Processing message', index, ':', msg);
                                
                                const messageClass = msg.role === 'user' ? 'user' : 'bot';
                                const speakerName = msg.role === 'user' ? 'You' : (this.characterData?.name || 'AI');
                                
                                let contentHtml = '';
                                
                                // Check if this is an image message
                                if (msg.content && msg.content.startsWith('[Generated Image: ') && msg.content.endsWith(']')) {
                                    const imgUrl = msg.content.replace('[Generated Image: ', '').replace(']', '');
                                    const prompt = msg.prompt || msg.metadata?.prompt || '';
                                    const escapedPrompt = prompt ? this.escapeHtml(prompt) : '';
                                    
                                    // Store prompt for hover display
                                    if (!window.pmvGeneratedImagePrompts) {
                                        window.pmvGeneratedImagePrompts = {};
                                    }
                                    if (prompt) {
                                        window.pmvGeneratedImagePrompts[imgUrl] = prompt;
                                    }
                                    
                                    contentHtml = `
                                        <div class="generated-image-container" style="position: relative; display: inline-block;">
                                            <img src="${this.escapeHtml(imgUrl)}" 
                                                 style="max-width: 100%; border-radius: 4px; cursor: help;" 
                                                 loading="lazy"
                                                 ${escapedPrompt ? `title="${escapedPrompt}" data-prompt="${escapedPrompt}"` : ''}
                                                 class="pmv-generated-image" />
                                            ${escapedPrompt ? `
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
                                            ` : ''}
                                        </div>
                                    `;
                                } else {
                                    contentHtml = this.escapeHtml(msg.content);
                                }
                                
                                const messageHtml = `
                                    <div class="chat-message ${messageClass}" data-message-index="${index}">
                                        <span class="speaker-name">${this.escapeHtml(speakerName)}:</span>
                                        <span class="chat-message-content-wrapper">${contentHtml}</span>
                                    </div>
                                `;
                                
                                console.log('PMV: Adding message HTML:', messageHtml);
                                $chatHistory.append(messageHtml);
                            });
                        } else {
                            console.warn('PMV: No messages found in conversation or messages is not an array');
                            $chatHistory.append(`
                                <div class="chat-message bot">
                                    <span class="speaker-name">System:</span>
                                    <span class="chat-message-content-wrapper">No messages found in this conversation.</span>
                                </div>
                            `);
                        }
                        
                        this.scrollToBottom();
                        console.log('PMV: Conversation displayed successfully');
                        
                    } catch (error) {
                        console.error('PMV: Error displaying conversation:', error);
                        this.showToast('Error displaying conversation: ' + error.message, 'error');
                    }
                },
                
                // Delete conversation
                deleteConversation: function(conversationId) {
                    try {
                        if (!conversationId) return;
                        
                        if (!confirm('Are you sure you want to delete this conversation? This cannot be undone.')) {
                            return;
                        }
                        
                        console.log('PMV: Deleting conversation:', conversationId);
                        
                        const $item = $(`.conversation-item[data-conversation-id="${conversationId}"]`);
                        $item.css('opacity', '0.5');
                        
                        const self = this;
                        
                        $.ajax({
                            url: pmv_ajax_object.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'pmv_delete_conversation',
                                conversation_id: conversationId,
                                nonce: pmv_ajax_object.nonce
                            },
                            timeout: 15000,
                            success: function(response) {
                                if (response.success) {
                                    self.showToast('Conversation deleted ✓', 'success');
                                    
                                    $item.slideUp(300, function() {
                                        $(this).remove();
                                        
                                        if (conversationId == self.currentConversationId) {
                                            self.currentConversationId = null;
                                            self.startNewConversation();
                                        }
                                        
                                        if ($('.conversation-item').length === 0) {
                                            self.displayConversations([]);
                                        }
                                    });
                                } else {
                                    self.showToast('Failed to delete conversation', 'error');
                                    $item.css('opacity', '1');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('PMV: Delete error:', { xhr, status, error });
                                self.showToast('Error deleting conversation: ' + error, 'error');
                                $item.css('opacity', '1');
                            }
                        });
                        
                    } catch (error) {
                        console.error('PMV: Error in deleteConversation:', error);
                        this.showToast('Failed to delete conversation: ' + error.message, 'error');
                    }
                },
                
                // Export conversation
                exportConversation: function() {
                    try {
                        const messages = this.collectMessages();
                        if (!messages || messages.length === 0) {
                            this.showToast('No messages to export', 'warning');
                            return;
                        }
                        
                        let exportText = `Conversation with ${this.characterData?.name || 'Character'}\n`;
                        exportText += `Exported: ${new Date().toLocaleString()}\n`;
                        exportText += '='.repeat(60) + '\n\n';
                        
                        messages.forEach((msg, index) => {
                            const speaker = msg.role === 'user' ? 'You' : (this.characterData?.name || 'AI');
                            exportText += `[${index + 1}] ${speaker}:\n${msg.content}\n\n`;
                        });
                        
                        // Create download
                        const blob = new Blob([exportText], { type: 'text/plain;charset=utf-8' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        
                        const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                        a.download = `conversation-${this.characterData?.name || 'export'}-${timestamp}.txt`;
                        
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        
                        this.showToast('Conversation exported ✓', 'success');
                        
                    } catch (error) {
                        console.error('PMV: Export error:', error);
                        this.showToast('Export failed: ' + error.message, 'error');
                    }
                },
                
                // Show guest message
                showGuestMessage: function() {
                    // Ensure conversation-list has dark background before adding content
                    const $list = $('.conversation-list');
                    $list.css({
                        'background': '#1a1a1a',
                        'color': '#e0e0e0'
                    });
                    
                    $list.html(`
                        <div class="guest-message" style="text-align: center; padding: 40px 20px; color: #e0e0e0 !important; background: #1a1a1a !important; border-radius: 8px; margin: 15px; border: 1px solid #444 !important;">
                            <p style="margin-bottom: 15px; font-size: 18px; color: #ffffff !important;">💬 Save your conversations!</p>
                            <p style="margin-bottom: 10px; color: #e0e0e0 !important;">
                                <a href="${pmv_ajax_object.login_url}" style="color: #007cba !important; text-decoration: none; font-weight: bold; transition: color 0.2s;">Login to save your chat history.</a>
                            </p>
                            <p style="color: #e0e0e0 !important;">
                                New user? <a href="${pmv_ajax_object.register_url}" style="color: #007cba !important; text-decoration: none; transition: color 0.2s;">Create an account</a>
                            </p>
                        </div>
                    `);
                },
                
                // Show error
                showError: function(message) {
                    $('.conversation-list').html(`
                        <div class="error-message" style="padding: 20px; color: #e74c3c; background: #ffeaea; border: 1px solid #ffcccc; border-radius: 4px;">
                            <h4>⚠️ Error</h4>
                            <p>${message}</p>
                            <button onclick="location.reload()" class="button">🔄 Refresh</button>
                        </div>
                    `);
                },
                
                // Enhanced toast notifications
                showToast: function(message, type = 'success') {
                    const colors = {
                        success: '#27ae60',
                        error: '#e74c3c',
                        warning: '#f39c12',
                        info: '#3498db'
                    };
                    
                    const icons = {
                        success: '✅',
                        error: '❌',
                        warning: '⚠️',
                        info: 'ℹ️'
                    };
                    
                    const toastId = 'pmv-toast-' + Date.now();
                    const $toast = $(`
                        <div id="${toastId}" class="pmv-toast" style="
                            position: fixed; bottom: 20px; right: 20px; z-index: 999999;
                            background: ${colors[type]}; color: white; padding: 12px 20px;
                            border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                            opacity: 0; transform: translateY(20px); transition: all 0.3s ease;
                            max-width: 350px; font-size: 14px; cursor: pointer;
                        ">
                            ${icons[type]} ${message}
                        </div>
                    `);
                    
                    $('body').append($toast);
                    
                    // Show animation
                    setTimeout(() => {
                        $toast.css({ opacity: 1, transform: 'translateY(0)' });
                    }, 10);
                    
                    // Click to dismiss
                    $toast.on('click', function() {
                        $(this).css({ opacity: 0, transform: 'translateY(-20px)' });
                        setTimeout(() => $(this).remove(), 300);
                    });
                    
                    // Auto-hide
                    const duration = type === 'error' ? 8000 : 4000;
                    setTimeout(() => {
                        if ($('#' + toastId).length) {
                            $toast.css({ opacity: 0, transform: 'translateY(-20px)' });
                            setTimeout(() => $toast.remove(), 300);
                        }
                    }, duration);
                },
                
                // Utility functions
                escapeHtml: function(str) {
                    if (!str) return '';
                    const div = document.createElement('div');
                    div.textContent = str;
                    return div.innerHTML;
                },
                
                scrollToBottom: function() {
                    // Use the simple scroll function if available
                    if (typeof scrollToBottom === 'function') {
                        scrollToBottom();
                    } else {
                        // Fallback to original method
                        const $chatHistory = $('#chat-history');
                        if ($chatHistory.length) {
                            const chatHistoryElement = $chatHistory[0];
                            
                            // Simple smooth scroll to bottom
                            chatHistoryElement.scrollTo({
                                top: chatHistoryElement.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    }
                },
                
                // Force scroll to bottom (used when user explicitly wants to see new messages)
                forceScrollToBottom: function() {
                    // Use the simple scroll function if available
                    if (typeof scrollToBottom === 'function') {
                        scrollToBottom();
                    } else {
                        // Fallback to original method
                        const $chatHistory = $('#chat-history');
                        if ($chatHistory.length) {
                            const chatHistoryElement = $chatHistory[0];
                            chatHistoryElement.scrollTo({
                                top: chatHistoryElement.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    }
                },
                
                // Check if user is near bottom of chat
                isNearBottom: function() {
                    const $chatHistory = $('#chat-history');
                    if ($chatHistory.length) {
                        const chatHistoryElement = $chatHistory[0];
                        return chatHistoryElement.scrollTop + chatHistoryElement.clientHeight >= chatHistoryElement.scrollHeight - 20;
                    }
                    return true;
                },
                
                // Show new message indicator
                showNewMessageIndicator: function() {
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
                        const self = this;
                        indicator.on('click', function() {
                            self.forceScrollToBottom();
                            $(this).fadeOut(300, function() {
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
                },
                
                // Add CSS animation for bounce effect
                addScrollIndicatorCSS: function() {
                    if (!$('#pmv-scroll-indicator-css').length) {
                        $('head').append(`
                            <style id="pmv-scroll-indicator-css">
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
                            </style>
                        `);
                    }
                },
                
                // Cleanup
                destroy: function() {
                    try {
                        console.log('PMV: Cleaning up conversation manager...');
                        
                        // Clear timers
                        if (this.autoSaveTimer) {
                            clearTimeout(this.autoSaveTimer);
                        }
                        if (this.saveDebounceTimer) {
                            clearTimeout(this.saveDebounceTimer);
                        }
                        
                        // Disconnect observer
                        if (this.messageObserver) {
                            this.messageObserver.disconnect();
                        }
                        
                        // Remove event listeners
                        $(document).off('.pmvConv');
                        $(window).off('.pmvConv');
                        
                        this.isInitialized = false;
                        console.log('PMV: Cleanup completed');
                        
                    } catch (error) {
                        console.error('PMV: Error during cleanup:', error);
                    }
                }
            };
            
            console.log('PMV: Conversation Manager FIXED VERSION ready');
        }
        
        // Start initialization
        waitForAjaxObject();
    });
    
})(jQuery);
