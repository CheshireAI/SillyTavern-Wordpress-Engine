/**
 * PMV Conversation Manager - Fixed with jQuery wrapper
 * Handles saving, loading, and managing chat conversations
 */
(function($) {
    'use strict';
    
    // Wait for jQuery to be ready
    $(document).ready(function() {
        console.log('=== PMV Conversation Manager Loading ===');
        
        window.PMV_ConversationManager = {
            // State management
            currentConversationId: null,
            characterId: null,
            characterData: null,
            saveInProgress: false,
            hasUnsavedChanges: false,
            messageObserver: null,
            
            // Initialize the conversation manager
            init: function(characterData, characterId) {
                console.log('=== PMV Conversation Manager Init ===', {
                    characterData: characterData,
                    characterId: characterId,
                    ajaxUrl: pmv_ajax_object?.ajax_url,
                    userLoggedIn: pmv_ajax_object?.is_logged_in,
                    nonce: pmv_ajax_object?.nonce
                });
                
                // Check if pmv_ajax_object exists
                if (typeof pmv_ajax_object === 'undefined') {
                    console.error('PMV: pmv_ajax_object is not defined!');
                    this.showError('Configuration error. Please refresh the page.');
                    return;
                }
                
                this.characterData = characterData;
                this.characterId = characterId;
                
                // Load conversations if user is logged in
                if (pmv_ajax_object.is_logged_in) {
                    this.loadConversationList();
                    this.setupEventHandlers();
                    this.setupMessageObserver();
                } else {
                    this.showGuestMessage();
                }
            },
            
            // Show error in conversation list
            showError: function(message) {
                $('.conversation-list').html(`
                    <div class="error-message" style="padding: 20px; color: #e74c3c;">
                        <strong>Error:</strong> ${message}
                    </div>
                `);
            },
            
            // Show guest message
            showGuestMessage: function() {
                $('.conversation-list').html(`
                    <div class="guest-message">
                        <p>Please <a href="${pmv_ajax_object.login_url}">login</a> to save conversations.</p>
                        <p>Or <a href="${pmv_ajax_object.register_url}">create an account</a> to get started.</p>
                    </div>
                `);
            },
            
            // Set up event handlers
            setupEventHandlers: function() {
                const self = this;
                
                // Remove any existing handlers first
                $(document).off('click.pmvConversation');
                
                // New conversation button
                $(document).on('click.pmvConversation', '#new-conversation', function(e) {
                    e.preventDefault();
                    console.log('New conversation clicked');
                    self.startNewConversation();
                });
                
                // Save conversation button
                $(document).on('click.pmvConversation', '#save-conversation', function(e) {
                    e.preventDefault();
                    console.log('Save conversation clicked');
                    self.manualSave();
                });
                
                // Export conversation button
                $(document).on('click.pmvConversation', '#export-conversation', function(e) {
                    e.preventDefault();
                    console.log('Export conversation clicked');
                    self.exportConversation();
                });
                
                // Delete conversation handler
                $(document).on('click.pmvConversation', '.delete-conversation', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const conversationId = $(this).data('id');
                    console.log('Delete conversation clicked:', conversationId);
                    self.deleteConversation(conversationId);
                });
                
                // Load conversation handler
                $(document).on('click.pmvConversation', '.conversation-item', function(e) {
                    if (!$(e.target).hasClass('delete-conversation')) {
                        e.preventDefault();
                        const conversationId = $(this).data('conversation-id');
                        console.log('Load conversation clicked:', conversationId);
                        self.loadConversation(conversationId);
                    }
                });
            },
            
            // Setup message observer to track changes
            setupMessageObserver: function() {
                const self = this;
                
                // Listen for new messages being added
                $(document).on('message:added', function(e, data) {
                    console.log('New message added:', data);
                    self.hasUnsavedChanges = true;
                    self.updateSaveButton();
                });
                
                // Watch for DOM changes in chat history
                const chatHistory = document.getElementById('chat-history');
                if (chatHistory && !this.messageObserver) {
                    this.messageObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes.length > 0) {
                                mutation.addedNodes.forEach(function(node) {
                                    if (node.classList && node.classList.contains('chat-message')) {
                                        self.hasUnsavedChanges = true;
                                        self.updateSaveButton();
                                    }
                                });
                            }
                        });
                    });
                    
                    this.messageObserver.observe(chatHistory, {
                        childList: true,
                        subtree: true
                    });
                }
            },
            
            // Update save button state
            updateSaveButton: function() {
                const $saveBtn = $('#save-conversation');
                if (this.hasUnsavedChanges && !this.saveInProgress) {
                    $saveBtn.addClass('has-changes').prop('disabled', false);
                } else {
                    $saveBtn.removeClass('has-changes');
                }
            },
            
            // Start new conversation
            startNewConversation: function() {
                if (this.hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Start a new conversation anyway?')) {
                        return;
                    }
                }
                
                // Clear current conversation
                this.currentConversationId = null;
                this.hasUnsavedChanges = false;
                
                // Clear chat history
                $('#chat-history').empty();
                
                // Add initial bot message if available
                if (this.characterData && this.characterData.first_mes) {
                    $('#chat-history').append(`
                        <div class="chat-message bot">
                            <span class="speaker-name">${this.escapeHtml(this.characterData.name)}:</span>
                            <span class="chat-message-content-wrapper">${this.escapeHtml(this.characterData.first_mes)}</span>
                        </div>
                    `);
                }
                
                // Update UI
                $('.conversation-item').removeClass('active');
                this.updateSaveButton();
            },
            
            // Manual save
            manualSave: function() {
                const self = this;
                
                if (this.saveInProgress) {
                    console.log('Save already in progress');
                    return;
                }
                
                if (!pmv_ajax_object.is_logged_in) {
                    alert('Please login to save conversations');
                    return;
                }
                
                const messages = this.collectMessages();
                if (!messages || messages.length === 0) {
                    alert('No messages to save');
                    return;
                }
                
                // Generate title from first user message or use default
                let title = 'Chat with ' + (this.characterData?.name || 'Character');
                const firstUserMessage = messages.find(m => m.role === 'user');
                if (firstUserMessage) {
                    title = firstUserMessage.content.substring(0, 50) + '...';
                }
                
                const conversationData = {
                    id: this.currentConversationId,
                    character_id: this.characterId,
                    title: title,
                    messages: messages
                };
                
                console.log('Saving conversation:', conversationData);
                console.log('AJAX URL:', pmv_ajax_object.ajax_url);
                console.log('Nonce:', pmv_ajax_object.nonce);
                
                // Update UI
                const $saveBtn = $('#save-conversation');
                $saveBtn.prop('disabled', true).text('Saving...');
                this.saveInProgress = true;
                
                // Make AJAX request
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
                        console.log('Save response:', response);
                        
                        if (response.success && response.data) {
                            self.currentConversationId = response.data.id;
                            self.hasUnsavedChanges = false;
                            self.showToast('Conversation saved successfully');
                            self.loadConversationList();
                        } else {
                            const errorMsg = response.data?.message || 'Failed to save conversation';
                            console.error('Save failed:', errorMsg);
                            self.showToast(errorMsg, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Save AJAX error:', {xhr, status, error});
                        console.error('Response:', xhr.responseText);
                        let errorMsg = 'Failed to save conversation';
                        
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMsg = xhr.responseJSON.data.message || xhr.responseJSON.data;
                        } else if (xhr.responseText) {
                            // Check for PHP errors in response
                            if (xhr.responseText.indexOf('Fatal error') !== -1 || 
                                xhr.responseText.indexOf('Warning') !== -1) {
                                errorMsg = 'Server error. Check PHP error logs.';
                                console.error('PHP Error in response:', xhr.responseText);
                            }
                        }
                        
                        self.showToast(errorMsg, 'error');
                    },
                    complete: function() {
                        self.saveInProgress = false;
                        $saveBtn.prop('disabled', false).text('💾 Save');
                        self.updateSaveButton();
                    }
                });
            },
            
            // Collect messages from chat history
            collectMessages: function() {
                const messages = [];
                
                $('#chat-history .chat-message').each(function() {
                    const $msg = $(this);
                    
                    // Skip typing indicators and errors
                    if ($msg.hasClass('typing-indicator') || $msg.hasClass('error')) {
                        return;
                    }
                    
                    let role = 'assistant';
                    let content = $msg.find('.chat-message-content-wrapper').text().trim();
                    
                    if ($msg.hasClass('user')) {
                        role = 'user';
                    }
                    
                    // Remove speaker name from content if present
                    const speakerName = $msg.find('.speaker-name').text();
                    if (speakerName && content.startsWith(speakerName)) {
                        content = content.substring(speakerName.length).trim();
                    }
                    
                    if (content) {
                        messages.push({
                            role: role,
                            content: content
                        });
                    }
                });
                
                console.log('Collected messages:', messages.length);
                return messages;
            },
            
            // Load conversation list
            loadConversationList: function() {
                const self = this;
                
                if (!pmv_ajax_object.is_logged_in) {
                    this.showGuestMessage();
                    return;
                }
                
                const $list = $('.conversation-list');
                $list.html('<div class="loading-container">Loading conversations...</div>');
                
                console.log('Loading conversations for character:', this.characterId);
                console.log('AJAX URL:', pmv_ajax_object.ajax_url);
                
                $.ajax({
                    url: pmv_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pmv_get_conversations',
                        character_id: this.characterId,
                        nonce: pmv_ajax_object.nonce
                    },
                    success: function(response) {
                        console.log('Load conversations response:', response);
                        if (response.success && response.data) {
                            self.displayConversations(response.data);
                        } else {
                            const errorMsg = response.data?.message || 'Failed to load conversations';
                            $list.html(`<div class="error-message">${errorMsg}</div>`);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Load conversations error:', {xhr, status, error});
                        console.error('Response:', xhr.responseText);
                        $list.html('<div class="error-message">Error loading conversations. Check console for details.</div>');
                    }
                });
            },
            
            // Display conversations
            displayConversations: function(conversations) {
                const $list = $('.conversation-list');
                
                if (!conversations || conversations.length === 0) {
                    $list.html('<div class="no-conversations">No saved conversations yet</div>');
                    return;
                }
                
                let html = '';
                conversations.forEach(conv => {
                    const date = new Date(conv.created_at).toLocaleDateString();
                    const isActive = conv.id == this.currentConversationId;
                    
                    html += `
                        <div class="conversation-item ${isActive ? 'active' : ''}" data-conversation-id="${conv.id}">
                            <div class="conversation-info">
                                <div class="conversation-title">${this.escapeHtml(conv.title)}</div>
                                <div class="conversation-date">${date}</div>
                            </div>
                            <button class="delete-conversation" data-id="${conv.id}" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    `;
                });
                
                $list.html(html);
            },
            
            // Load a specific conversation
            loadConversation: function(conversationId) {
                const self = this;
                
                if (this.hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Load this conversation anyway?')) {
                        return;
                    }
                }
                
                console.log('Loading conversation:', conversationId);
                
                $.ajax({
                    url: pmv_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pmv_get_conversation',
                        conversation_id: conversationId,
                        nonce: pmv_ajax_object.nonce
                    },
                    success: function(response) {
                        console.log('Load conversation response:', response);
                        if (response.success && response.data) {
                            self.displayConversation(response.data);
                            self.currentConversationId = conversationId;
                            self.hasUnsavedChanges = false;
                            
                            // Update active state
                            $('.conversation-item').removeClass('active');
                            $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
                        } else {
                            self.showToast('Failed to load conversation', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Load conversation error:', {xhr, status, error});
                        self.showToast('Error loading conversation', 'error');
                    }
                });
            },
            
            // Display loaded conversation
            displayConversation: function(conversation) {
                const $chatHistory = $('#chat-history');
                $chatHistory.empty();
                
                if (conversation.messages && Array.isArray(conversation.messages)) {
                    conversation.messages.forEach(msg => {
                        const messageClass = msg.role === 'user' ? 'user' : 'bot';
                        const speakerName = msg.role === 'user' ? 'You' : (this.characterData?.name || 'AI');
                        
                        $chatHistory.append(`
                            <div class="chat-message ${messageClass}">
                                <span class="speaker-name">${this.escapeHtml(speakerName)}:</span>
                                <span class="chat-message-content-wrapper">${this.escapeHtml(msg.content)}</span>
                            </div>
                        `);
                    });
                }
                
                // Scroll to bottom
                $chatHistory.scrollTop($chatHistory[0].scrollHeight);
            },
            
            // Delete conversation
            deleteConversation: function(conversationId) {
                const self = this;
                
                if (!confirm('Are you sure you want to delete this conversation?')) {
                    return;
                }
                
                console.log('Deleting conversation:', conversationId);
                
                $.ajax({
                    url: pmv_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pmv_delete_conversation',
                        conversation_id: conversationId,
                        nonce: pmv_ajax_object.nonce
                    },
                    success: function(response) {
                        console.log('Delete response:', response);
                        if (response.success) {
                            self.showToast('Conversation deleted');
                            
                            // Clear current if it was deleted
                            if (conversationId == self.currentConversationId) {
                                self.currentConversationId = null;
                                self.startNewConversation();
                            }
                            
                            self.loadConversationList();
                        } else {
                            self.showToast('Failed to delete conversation', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', {xhr, status, error});
                        self.showToast('Error deleting conversation', 'error');
                    }
                });
            },
            
            // Export conversation
            exportConversation: function() {
                const messages = this.collectMessages();
                if (!messages || messages.length === 0) {
                    this.showToast('No messages to export', 'error');
                    return;
                }
                
                let exportText = `Conversation with ${this.characterData?.name || 'Character'}\n`;
                exportText += `Date: ${new Date().toLocaleString()}\n`;
                exportText += '='.repeat(50) + '\n\n';
                
                messages.forEach(msg => {
                    const speaker = msg.role === 'user' ? 'You' : (this.characterData?.name || 'AI');
                    exportText += `${speaker}: ${msg.content}\n\n`;
                });
                
                // Create download
                const blob = new Blob([exportText], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `conversation-${this.characterData?.name || 'export'}-${Date.now()}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                this.showToast('Conversation exported');
            },
            
            // Show toast notification
            showToast: function(message, type = 'success') {
                const toastClass = type === 'error' ? 'toast-error' : 'toast-success';
                const $toast = $(`<div class="pmv-toast ${toastClass}">${message}</div>`);
                
                $('body').append($toast);
                
                setTimeout(() => {
                    $toast.addClass('show');
                }, 10);
                
                setTimeout(() => {
                    $toast.removeClass('show');
                    setTimeout(() => $toast.remove(), 300);
                }, 3000);
            },
            
            // HTML escape helper
            escapeHtml: function(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },
            
            // Cleanup operations
            cleanupSaveOperations: function() {
                if (this.messageObserver) {
                    this.messageObserver.disconnect();
                }
            }
        };
        
        // Add CSS for toast notifications if not already added
        if (!document.getElementById('pmv-conversation-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'pmv-conversation-toast-styles';
            style.innerHTML = `
                .pmv-toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #333;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 4px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    opacity: 0;
                    transform: translateY(20px);
                    transition: all 0.3s ease;
                    z-index: 100000;
                    max-width: 300px;
                }
                
                .pmv-toast.show {
                    opacity: 1;
                    transform: translateY(0);
                }
                
                .pmv-toast.toast-error {
                    background: #e74c3c;
                }
                
                .pmv-toast.toast-success {
                    background: #27ae60;
                }
                
                .guest-message {
                    text-align: center;
                    padding: 40px 20px;
                    color: #999;
                }
                
                .guest-message a {
                    color: #007cba;
                    text-decoration: none;
                }
                
                .guest-message a:hover {
                    text-decoration: underline;
                }
                
                #save-conversation.has-changes {
                    background: #e74c3c;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0% { opacity: 1; }
                    50% { opacity: 0.7; }
                    100% { opacity: 1; }
                }
                
                .error-message {
                    text-align: center;
                    padding: 20px;
                    color: #e74c3c;
                }
            `;
            document.head.appendChild(style);
        }
    });
    
})(jQuery);
