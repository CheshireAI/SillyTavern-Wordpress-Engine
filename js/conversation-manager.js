/* PNG Metadata Viewer - Conversation Manager Module with Auto-save and Message Editing Support */
console.log('PNG Metadata Viewer Conversation Manager Module with Auto-save Loaded');
(function($) {
    // Global conversation state with enhanced tracking
    window.PMV_ConversationManager = {
        currentConversationId: null,
        conversations: [],
        characterData: null,
        characterId: null,
        isReady: false,
        currentAjaxRequest: null,
        
        // Enhanced state tracking for auto-save and editing
        hasUnsavedChangesFlag: false,
        lastSaveTime: null,
        autoSaveEnabled: true,
        autoSaveTimer: null,
        saveInProgress: false,
        
        // Initialize the conversation manager with enhanced features
        init: function(characterData, characterId) {
            this.characterData = characterData;
            this.characterId = characterId || this.generateCharacterId(characterData);
            this.hasUnsavedChangesFlag = false;
            this.lastSaveTime = null;
            
            this.loadConversationList();
            this.setupEventHandlers();
            this.setupAutoSave();
            this.isReady = true;
            
            console.log('Conversation Manager initialized with auto-save for:', this.characterId);
        },
        
        // Generate consistent character ID
        generateCharacterId: function(characterData) {
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
        },
        
        // Setup auto-save functionality
        setupAutoSave: function() {
            console.log('Setting up auto-save functionality');
            
            // Clear any existing timer
            if (this.autoSaveTimer) {
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = null;
            }
            
            // Listen for message editing events
            $(document).on('message:added message:edited message:deleted message:regenerated', (e, data) => {
                console.log('Message event detected, triggering auto-save:', e.type);
                this.markAsModified();
                this.scheduleAutoSave(e.type === 'message:added' ? 3000 : 1000);
            });
            
            console.log('Auto-save setup complete');
        },
        
        // Schedule auto-save with debouncing
        scheduleAutoSave: function(delay = 2000) {
            if (!this.autoSaveEnabled || this.saveInProgress) return;
            
            // Clear existing timer
            if (this.autoSaveTimer) {
                clearTimeout(this.autoSaveTimer);
            }
            
            // Schedule new save
            this.autoSaveTimer = setTimeout(() => {
                this.performAutoSave();
            }, delay);
            
            console.log(`Auto-save scheduled in ${delay}ms`);
        },
        
        // Perform automatic save
        performAutoSave: function() {
            if (!this.hasUnsavedChangesFlag || this.saveInProgress) {
                console.log('No unsaved changes or save in progress, skipping auto-save');
                return;
            }
            
            console.log('Performing auto-save...');
            this.saveInProgress = true;
            
            const messages = this.collectMessagesFromDOM();
            if (messages.length === 0) {
                console.log('No messages to auto-save');
                this.saveInProgress = false;
                return;
            }
            
            // Auto-generate title if this is a new conversation
            let title = this.generateAutoTitle();
            
            // If we have an existing conversation, keep the existing title
            if (this.currentConversationId) {
                const existingConv = this.conversations.find(c => c.id == this.currentConversationId);
                if (existingConv && existingConv.title) {
                    title = existingConv.title;
                }
            }
            
            const conversationData = {
                character_id: this.characterId,
                title: title,
                messages: messages
            };
            
            if (this.currentConversationId) {
                conversationData.id = this.currentConversationId;
            }
            
            // Show auto-save indicator
            this.showSaveStatus('saving');
            
            // Save via AJAX
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_save_conversation',
                    conversation: JSON.stringify(conversationData),
                    nonce: pmv_ajax_object.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentConversationId = response.data.id;
                        this.hasUnsavedChangesFlag = false;
                        this.lastSaveTime = new Date();
                        
                        // Update conversation lists
                        this.loadConversationList();
                        if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                            this.loadConversationListMobile();
                        }
                        
                        this.showSaveStatus('saved');
                        this.updateHeaderSaveStatus(false);
                        
                        console.log('Auto-save successful:', response.data.id);
                    } else {
                        this.showSaveStatus('error');
                        console.error('Auto-save failed:', response.data?.message);
                    }
                },
                error: (xhr) => {
                    this.showSaveStatus('error');
                    console.error('Auto-save error:', xhr.responseText);
                },
                complete: () => {
                    this.saveInProgress = false;
                }
            });
        },
        
        // Manual save method (with user prompt for title)
        manualSave: function() {
            console.log('Manual save triggered');
            
            if (this.saveInProgress) {
                console.log('Save already in progress');
                return;
            }
            
            this.saveCurrentConversation();
        },
        
        // Generate automatic title for conversations
        generateAutoTitle: function() {
            const characterName = this.getCharacterName();
            const now = new Date();
            const dateStr = now.toLocaleDateString();
            const timeStr = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            return `Chat with ${characterName} - ${dateStr} ${timeStr}`;
        },
        
        // Show save status indicator
        showSaveStatus: function(status) {
            // Remove existing status
            $('.save-status').remove();
            
            let text = '';
            let className = '';
            switch(status) {
                case 'saving':
                    text = 'Saving...';
                    className = 'saving';
                    break;
                case 'saved':
                    text = 'Saved ✓';
                    className = 'saved';
                    break;
                case 'error':
                    text = 'Save Error ✗';
                    className = 'error';
                    break;
            }
            
            const $status = $(`<div class="save-status ${className}">${text}</div>`);
            $('body').append($status);
            
            // Auto-hide after delay
            if (status !== 'saving') {
                setTimeout(() => {
                    $status.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            }
        },
        
        // Update header to show save status
        updateHeaderSaveStatus: function(hasUnsaved) {
            const $headerName = $('.chat-modal-name');
            if (hasUnsaved) {
                if (!$headerName.text().includes('*')) {
                    $headerName.text($headerName.text() + ' *').attr('title', 'Unsaved changes');
                }
            } else {
                $headerName.text($headerName.text().replace(' *', '')).removeAttr('title');
            }
        },
        
        // Mark conversation as having unsaved changes
        markAsModified: function() {
            this.hasUnsavedChangesFlag = true;
            this.updateHeaderSaveStatus(true);
            console.log('Conversation marked as modified');
        },
        
        // Clear unsaved changes flag
        clearModified: function() {
            this.hasUnsavedChangesFlag = false;
            this.updateHeaderSaveStatus(false);
            console.log('Conversation modification flag cleared');
        },
        
        // Check if there are unsaved changes
        hasUnsavedChanges: function() {
            return this.hasUnsavedChangesFlag;
        },
        
        // Load conversation list (Desktop)
        loadConversationList: function() {
            const self = this;
            $('#conversation-list').html('<div class="loading-conversations">Loading conversations...</div>');
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_get_conversations',
                    character_id: this.characterId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.conversations = response.data;
                        self.renderConversationList();
                        // Also update mobile if it exists
                        if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                            self.renderConversationListMobile();
                        }
                    } else {
                        self.showConversationError(response.data?.message || 'Failed to load conversations');
                    }
                },
                error: function(xhr) {
                    self.showConversationError('Connection error - check console');
                    console.error('Conversation load error:', xhr.responseText);
                }
            });
        },
        
        // Load conversation list for mobile
        loadConversationListMobile: function() {
            const self = this;
            // Target mobile-specific elements
            $('.mobile-conversations-list, .mobile-conversation-list').html('<div class="loading-conversations" style="color: #999; padding: 10px;">Loading conversations...</div>');
            
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_get_conversations',
                    character_id: this.characterId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.conversations = response.data;
                        self.renderConversationListMobile();
                        // Also update desktop if it exists
                        if ($('#conversation-list').length) {
                            self.renderConversationList();
                        }
                    } else {
                        self.showMobileConversationError(response.data?.message || 'Failed to load conversations');
                    }
                },
                error: function(xhr) {
                    self.showMobileConversationError('Connection error - check console');
                    console.error('Mobile conversation load error:', xhr.responseText);
                }
            });
        },
        
        // Render conversation list (Desktop)
        renderConversationList: function() {
            const $list = $('#conversation-list');
            if (!$list.length) return;
            
            $list.empty();
            if (this.conversations.length === 0) {
                $list.html('<div class="no-conversations">No saved conversations</div>');
                return;
            }
            const listHtml = this.conversations.map(conv => {
                const isActive = conv.id === this.currentConversationId;
                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" data-id="${conv.id}" data-conversation-id="${conv.id}">
                        <div class="conversation-info">
                            <div class="conversation-title">${this.escapeHtml(conv.title)}</div>
                            <div class="conversation-date">${new Date(conv.updated_at).toLocaleString()}</div>
                        </div>
                        <div class="conversation-actions">
                            <button class="delete-conversation" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            $list.html(listHtml);
        },
        
        // Render conversation list for Mobile - FIXED
        renderConversationListMobile: function() {
            const $mobileList = $('.mobile-conversations-list, .mobile-conversation-list');
            if (!$mobileList.length) {
                console.log('No mobile conversation list element found');
                return;
            }
            
            $mobileList.empty();
            
            if (this.conversations.length === 0) {
                $mobileList.html('<div class="no-conversations" style="color: #999; padding: 10px;">No saved conversations</div>');
                return;
            }
            
            console.log('Rendering mobile conversations:', this.conversations);
            
            const listHtml = this.conversations.map(conv => {
                const isActive = conv.id == this.currentConversationId;
                const conversationId = String(conv.id); // Ensure it's a string
                
                console.log('Creating mobile conversation item:', {
                    id: conv.id,
                    conversationId: conversationId,
                    title: conv.title,
                    isActive: isActive
                });
                
                return `
                    <div class="mobile-conversation-item conversation-item ${isActive ? 'active' : ''}" 
                         data-id="${conversationId}" 
                         data-conversation-id="${conversationId}"
                         style="
                            position: relative;
                            padding: 12px;
                            margin-bottom: 8px;
                            background: ${isActive ? '#434d47' : 'rgba(255,255,255,0.1)'};
                            border-radius: 8px;
                            cursor: pointer;
                            color: white;
                            border: ${isActive ? '2px solid rgba(59, 63, 56, 0.3)' : '1px solid rgba(71, 80, 69, 0.41)'};
                            transition: all 0.3s ease;
                         ">
                        <div style="
                            font-weight: ${isActive ? 'bold' : 'normal'};
                            margin-bottom: 4px;
                            padding-right: 30px;
                            font-size: 14px;
                            line-height: 1.3;
                            word-wrap: break-word;
                        ">${this.escapeHtml(conv.title || 'Untitled Conversation')}</div>
                        <div style="
                            font-size: 12px;
                            color: ${isActive ? 'rgba(255,255,255,0.9)' : '#ccc'};
                        ">${new Date(conv.updated_at).toLocaleString()}</div>
                        <button class="mobile-delete-conversation" 
                                data-id="${conversationId}" 
                                title="Delete conversation"
                                style="
                                    position: absolute;
                                    top: 8px;
                                    right: 8px;
                                    background: rgba(231, 76, 60, 0.8);
                                    border: none;
                                    border-radius: 50%;
                                    color: white;
                                    width: 20px;
                                    height: 20px;
                                    font-size: 12px;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    opacity: 0.7;
                                    transition: opacity 0.3s;
                                    z-index: 10;
                                "
                                onmouseover="this.style.opacity='1'"
                                onmouseout="this.style.opacity='0.7'">×</button>
                    </div>
                `;
            }).join('');
            
            $mobileList.html(listHtml);
            
            // Set up event handlers after rendering
            this.setupMobileEventHandlers();
            this.debugMobileConversations(); // Add debug logging
            
            console.log('Mobile conversation list rendered with', this.conversations.length, 'items');
        },
        
        // Setup mobile-specific event handlers - FIXED
        setupMobileEventHandlers: function() {
            const self = this;
            
            // Remove existing handlers to prevent duplicates
            $(document).off('click.mobileConv', '.mobile-conversation-item');
            $(document).off('click.mobileConv', '.mobile-delete-conversation');
            
            console.log('Setting up mobile event handlers');
            
            // Mobile conversation item click - Use event delegation properly
            $(document).on('click.mobileConv', '.mobile-conversation-item', function(e) {
                // Don't trigger if delete button was clicked
                if ($(e.target).hasClass('mobile-delete-conversation') || $(e.target).closest('.mobile-delete-conversation').length) {
                    console.log('Delete button clicked, ignoring conversation click');
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const $item = $(this);
                const conversationId = $item.attr('data-id') || $item.attr('data-conversation-id');
                
                console.log('Mobile conversation clicked - element:', $item);
                console.log('Mobile conversation clicked - ID from data-id:', $item.attr('data-id'));
                console.log('Mobile conversation clicked - ID from data-conversation-id:', $item.attr('data-conversation-id'));
                console.log('Final conversation ID:', conversationId);
                
                if (!conversationId || conversationId === 'undefined') {
                    console.error('Mobile conversation ID is undefined or invalid');
                    self.showToast('Error: Invalid conversation ID', 'error');
                    return;
                }
                
                // Check for unsaved changes before loading
                if (self.hasUnsavedChangesFlag) {
                    const confirmLoad = confirm('You have unsaved changes. Loading a conversation will lose these changes. Continue?');
                    if (!confirmLoad) {
                        return;
                    }
                }
                
                // Load the conversation
                console.log('Loading mobile conversation:', conversationId);
                self.loadConversation(conversationId);
                
                // Close mobile menu
                if (window.PMV_MobileChat && typeof window.PMV_MobileChat.closeMenu === 'function') {
                    window.PMV_MobileChat.closeMenu();
                }
            });
            
            // Mobile delete conversation
            $(document).on('click.mobileConv', '.mobile-delete-conversation', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $deleteBtn = $(this);
                const conversationId = $deleteBtn.attr('data-id');
                
                console.log('Mobile delete clicked - element:', $deleteBtn);
                console.log('Mobile delete clicked - ID:', conversationId);
                
                if (!conversationId || conversationId === 'undefined') {
                    console.error('Mobile delete conversation ID is undefined');
                    return;
                }
                
                if (confirm('Delete this conversation permanently?')) {
                    self.deleteConversation(conversationId);
                }
            });
        },
        
        // Debug function for mobile conversations
        debugMobileConversations: function() {
            console.log('=== Mobile Conversation Debug ===');
            console.log('Current conversations:', this.conversations);
            console.log('Current conversation ID:', this.currentConversationId);
            
            $('.mobile-conversation-item').each(function(index) {
                const $item = $(this);
                console.log(`Item ${index}:`, {
                    element: $item,
                    'data-id': $item.attr('data-id'),
                    'data-conversation-id': $item.attr('data-conversation-id'),
                    classes: $item.attr('class')
                });
            });
            
            console.log('=================================');
        },
        
        // Setup event handlers (Desktop) with enhanced auto-save integration
        setupEventHandlers: function() {
            const self = this;
            $(document)
                .off('click', '#new-conversation')
                .off('click', '#save-conversation')
                .off('click', '.conversation-item')
                .off('click', '.delete-conversation')
                .on('click', '#new-conversation', function() {
                    self.startNewConversation();
                })
                .on('click', '#save-conversation', function() {
                    self.manualSave();
                })
                .on('click', '.conversation-item', function(e) {
                    // Don't trigger if delete button was clicked
                    if ($(e.target).hasClass('delete-conversation') || $(e.target).closest('.delete-conversation').length) {
                        return;
                    }
                    
                    // Check for unsaved changes
                    if (self.hasUnsavedChangesFlag) {
                        const confirmLoad = confirm('You have unsaved changes. Loading a conversation will lose these changes. Continue?');
                        if (!confirmLoad) {
                            return;
                        }
                    }
                    
                    const id = $(this).data('id');
                    self.loadConversation(id);
                })
                .on('click', '.delete-conversation', function(e) {
                    e.stopPropagation();
                    const id = $(this).closest('.conversation-item').data('id');
                    self.deleteConversation(id);
                });
        },
        
        // Enhanced save current conversation with message editing support
        saveCurrentConversation: function() {
            const self = this;
            
            if (this.saveInProgress) {
                console.log('Save already in progress');
                return;
            }
            
            // Collect messages using enhanced method that supports editing
            const messages = this.collectMessagesFromDOM();
            if (messages.length === 0) {
                this.showToast('Cannot save empty conversation', 'error');
                return;
            }
            
            // For manual save, always prompt for title
            const defaultTitle = this.currentConversationId ? 
                (this.conversations.find(c => c.id == this.currentConversationId)?.title || this.generateAutoTitle()) :
                this.generateAutoTitle();
            
            const title = prompt('Conversation title:', defaultTitle) || defaultTitle;
            
            const conversationData = {
                character_id: this.characterId,
                title: title.substring(0, 255),
                messages: messages
            };
            
            if (this.currentConversationId) {
                conversationData.id = this.currentConversationId;
            }
            
            this.saveInProgress = true;
            $('#save-conversation').prop('disabled', true).text('Saving...');
            
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
                        self.currentConversationId = response.data.id;
                        self.hasUnsavedChangesFlag = false;
                        self.lastSaveTime = new Date();
                        
                        self.loadConversationList();
                        // If mobile exists, also refresh mobile list
                        if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                            self.loadConversationListMobile();
                        }
                        
                        self.updateHeaderSaveStatus(false);
                        self.showToast('Conversation saved');
                        console.log('Manual save successful:', response.data.id);
                    } else {
                        self.showToast(response.data?.message || 'Save failed', 'error');
                    }
                },
                error: function(xhr) {
                    self.showToast('Save failed - check console', 'error');
                    console.error('Save error:', xhr.responseText);
                },
                complete: function() {
                    self.saveInProgress = false;
                    $('#save-conversation').prop('disabled', false).text('Save');
                }
            });
        },
        
        // Load conversation with improved error handling and unsaved changes check
        loadConversation: function(conversationId) {
            const self = this;
            console.log('Loading conversation:', conversationId);
            
            // Validate conversation ID
            if (!conversationId || conversationId === 'undefined' || conversationId === 'null') {
                console.error('Invalid conversation ID:', conversationId);
                self.showToast('Error: Invalid conversation ID', 'error');
                return;
            }
            
            // Clear any existing loading states
            $('.loading-conversation').remove();
            
            // Show loading in both desktop and mobile
            $('#chat-history, #chat-messages').html('<div class="loading-conversation">Loading...</div>');
            
            // Clear any existing AJAX requests
            if (this.currentAjaxRequest) {
                this.currentAjaxRequest.abort();
            }
            
            this.currentAjaxRequest = $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                timeout: 15000, // 15 second timeout
                data: {
                    action: 'pmv_get_conversation',
                    conversation_id: conversationId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    console.log('Load conversation response:', response);
                    if (response.success) {
                        self.currentConversationId = conversationId;
                        self.hasUnsavedChangesFlag = false; // Clear unsaved changes when loading
                        self.renderConversation(response.data);
                        // Update both desktop and mobile lists
                        self.renderConversationList();
                        if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                            self.renderConversationListMobile();
                        }
                        self.updateHeaderSaveStatus(false);
                        self.showToast('Conversation loaded');
                    } else {
                        $('#chat-history, #chat-messages').html('<div class="error-message">Failed to load conversation: ' + (response.data?.message || 'Unknown error') + '</div>');
                        self.showToast('Load failed: ' + (response.data?.message || 'Unknown error'), 'error');
                        console.error('Load failed:', response);
                    }
                },
                error: function(xhr, status, error) {
                    if (status !== 'abort') {
                        $('#chat-history, #chat-messages').html('<div class="error-message">Connection error. Please try again.</div>');
                        self.showToast('Load failed - connection error', 'error');
                        console.error('Load error:', {xhr, status, error, conversationId});
                        console.error('Response text:', xhr.responseText);
                    }
                },
                complete: function() {
                    self.currentAjaxRequest = null;
                }
            });
        },
        
        // Load conversation into chat (compatibility method for mobile)
        loadConversationIntoChat: function(conversationData) {
            this.currentConversationId = conversationData.id;
            this.hasUnsavedChangesFlag = false; // Clear unsaved changes
            this.renderConversation(conversationData);
            // Update lists
            this.renderConversationList();
            if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                this.renderConversationListMobile();
            }
            this.updateHeaderSaveStatus(false);
        },
        
        // Delete conversation
        deleteConversation: function(conversationId) {
            if (!conversationId || conversationId === 'undefined') {
                console.error('Invalid conversation ID for deletion:', conversationId);
                return;
            }
            
            if (!confirm('Permanently delete this conversation?')) return;
            const self = this;
            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_delete_conversation',
                    conversation_id: conversationId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (conversationId == self.currentConversationId) {
                            self.currentConversationId = null;
                            self.hasUnsavedChangesFlag = false;
                            // Clear chat
                            $('#chat-history, #chat-messages').empty();
                            self.addFirstMessage();
                            self.updateHeaderSaveStatus(false);
                        }
                        // Refresh both lists
                        self.loadConversationList();
                        if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                            self.loadConversationListMobile();
                        }
                        self.showToast('Conversation deleted');
                    } else {
                        self.showToast('Delete failed: ' + (response.data?.message || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr) {
                    self.showToast('Delete failed - check console', 'error');
                    console.error('Delete error:', xhr.responseText);
                }
            });
        },
        
        // Start new conversation with unsaved changes check
        startNewConversation: function() {
            // Check for unsaved changes
            if (this.hasUnsavedChangesFlag) {
                const confirmNew = confirm('You have unsaved changes. Starting a new conversation will lose these changes. Continue?');
                if (!confirmNew) {
                    return;
                }
            }
            
            // Clear any ongoing requests
            if (this.currentAjaxRequest) {
                this.currentAjaxRequest.abort();
            }
            
            this.currentConversationId = null;
            this.hasUnsavedChangesFlag = false;
            $('#chat-history, #chat-messages').empty();
            this.addFirstMessage();
            // Update both lists
            this.renderConversationList();
            if ($('.mobile-conversations-list, .mobile-conversation-list').length) {
                this.renderConversationListMobile();
            }
            this.updateHeaderSaveStatus(false);
            $('#chat-input').focus();
        },
        
        // Update current conversation with new message (for auto-save integration)
        updateCurrentConversationWithMessage: function(role, content) {
            // This method is called when new messages are added
            console.log('Message added to conversation:', {role, content, conversationId: this.currentConversationId});
            this.markAsModified();
            this.scheduleAutoSave(3000); // Auto-save in 3 seconds
        },
        
        // Enhanced message collection with editing support
        collectMessagesFromDOM: function() {
            const messages = [];
            
            // Use mobile chat's method if available (supports editing)
            if (window.PMV_MobileChat && window.PMV_MobileChat.getConversationHistory) {
                return window.PMV_MobileChat.getConversationHistory();
            }
            
            // Fallback method
            $('.chat-message').each(function() {
                const $msg = $(this);
                
                // Skip error messages and typing indicators
                if ($msg.hasClass('error') || $msg.hasClass('typing-indicator')) {
                    return;
                }
                
                let role = $msg.hasClass('user') ? 'user' : 'assistant';
                let content = $msg.find('.chat-message-content-wrapper').text() || 
                             $msg.text().replace(/^\s*(You|AI):\s*/i, '');
                             
                if (content.trim()) {
                    messages.push({ role, content: content.trim() });
                }
            });
            
            return messages;
        },
        
        // Enhanced conversation rendering with editing support
        renderConversation: function(conversation) {
            const $history = $('#chat-history, #chat-messages');
            $history.empty();
            
            if (!conversation.messages || conversation.messages.length === 0) {
                this.addFirstMessage();
                return;
            }
            
            conversation.messages.forEach(msg => {
                const isUser = msg.role === 'user';
                const name = isUser ? 'You' : this.getCharacterName();
                
                // Use mobile chat's method if available (supports editing)
                if (window.PMV_MobileChat && window.PMV_MobileChat.addEditableMessage) {
                    window.PMV_MobileChat.addEditableMessage(
                        msg.content,
                        isUser ? 'user' : 'bot',
                        name
                    );
                } else {
                    // Fallback method
                    $history.append(`
                        <div class="chat-message ${isUser ? 'user' : 'bot'}">
                            <strong>${name}:</strong>
                            <span class="chat-message-content-wrapper">${this.escapeHtml(msg.content)}</span>
                        </div>
                    `);
                }
            });
            
            // Scroll to bottom
            $history.scrollTop($history[0].scrollHeight);
            
            // Apply layout fixes
            if (window.PMV_Chat && window.PMV_Chat.fixChatVisibility) {
                window.PMV_Chat.fixChatVisibility();
            }
            if (window.forceScrollToBottom) {
                window.forceScrollToBottom();
            }
            
            // Convert messages to editable format if needed
            setTimeout(() => {
                if (window.PMV_MobileChat && window.PMV_MobileChat.convertExistingMessages) {
                    window.PMV_MobileChat.convertExistingMessages();
                }
            }, 200);
        },
        
        // Add first message with editing support
        addFirstMessage: function() {
            try {
                const firstMes = this.characterData.first_mes ||
                                this.characterData.data?.first_mes ||
                                `Hello, I'm ${this.getCharacterName()}`;
                
                const characterName = this.getCharacterName();
                
                // Use mobile chat's method if available (supports editing)
                if (window.PMV_MobileChat && window.PMV_MobileChat.addEditableMessage) {
                    window.PMV_MobileChat.addEditableMessage(
                        firstMes,
                        'bot',
                        characterName
                    );
                } else {
                    // Fallback method
                    $('#chat-history, #chat-messages').append(`
                        <div class="chat-message bot">
                            <strong>${characterName}:</strong>
                            <span class="chat-message-content-wrapper">${this.escapeHtml(firstMes)}</span>
                        </div>
                    `);
                }
            } catch(e) {
                console.error('Error adding first message:', e);
            }
        },
        
        // Export conversation functionality
        exportConversation: function(format = 'json') {
            console.log('Exporting conversation as:', format);
            
            const messages = this.collectMessagesFromDOM();
            if (messages.length === 0) {
                this.showToast('No messages to export', 'error');
                return;
            }
            
            const exportData = {
                character: this.getCharacterName(),
                character_data: this.characterData,
                timestamp: new Date().toISOString(),
                messages: messages,
                format: format,
                exported_by: 'PNG Metadata Viewer Conversation Manager',
                version: '1.0',
                conversation_id: this.currentConversationId
            };
            
            if (format === 'json') {
                const dataStr = JSON.stringify(exportData, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                
                const downloadLink = document.createElement('a');
                downloadLink.href = url;
                downloadLink.download = `conversation_${this.getCharacterName().replace(/[^a-z0-9]/gi, '_')}_${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                URL.revokeObjectURL(url);
                
                this.showToast('Conversation exported');
            } else {
                this.showToast('Format not supported yet', 'error');
            }
        },
        
        // Reset conversation manager
        reset: function() {
            console.log('Resetting conversation manager');
            
            // Clear auto-save timer
            if (this.autoSaveTimer) {
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = null;
            }
            
            // Abort any ongoing requests
            if (this.currentAjaxRequest) {
                this.currentAjaxRequest.abort();
                this.currentAjaxRequest = null;
            }
            
            // Reset state
            this.currentConversationId = null;
            this.hasUnsavedChangesFlag = false;
            this.saveInProgress = false;
            this.lastSaveTime = null;
            
            // Remove save status
            $('.save-status').remove();
            
            console.log('Conversation manager reset complete');
        },
        
        // Helper methods
        getCharacterName: function() {
            try {
                return this.characterData.name ||
                      this.characterData.data?.name ||
                      'AI Character';
            } catch(e) {
                return 'Unknown Character';
            }
        },
        
        showConversationError: function(message) {
            $('#conversation-list').html(
                `<div class="conversation-error">${this.escapeHtml(message)}</div>`
            );
        },
        
        showMobileConversationError: function(message) {
            $('.mobile-conversations-list, .mobile-conversation-list').html(
                `<div class="conversation-error" style="color: #ff6b6b; padding: 10px;">${this.escapeHtml(message)}</div>`
            );
        },
        
        showToast: function(message, type = 'success') {
            const toast = $(`
                <div class="pmv-toast pmv-toast-${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'error' ? '#ff4444' : '#44ff44'};
                    color: white;
                    padding: 10px 15px;
                    border-radius: 5px;
                    z-index: 10000;
                    font-weight: bold;
                ">
                    ${this.escapeHtml(message)}
                </div>
            `).appendTo('body');
            setTimeout(() => toast.remove(), 3000);
        },
        
        escapeHtml: function(str) {
            if (typeof str !== 'string') return '';
            return String(str).replace(/[&<>"']/g, m => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;',
                '"': '&quot;', "'": '&#39;'
            }[m]));
        }
    };
})(jQuery);
