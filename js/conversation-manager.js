/* PNG Metadata Viewer - Conversation Manager Module */
console.log('PNG Metadata Viewer Conversation Manager Module Loaded');

(function($) {
    // Global conversation state
    window.PMV_ConversationManager = {
        currentConversationId: null,
        conversations: [],
        characterData: null,
        characterId: null,
        
        // Initialize the conversation manager
        init: function(characterData, characterId) {
            this.characterData = characterData;
            this.characterId = characterId || this.generateCharacterId(characterData);
            this.loadConversationList();
            this.setupEventHandlers();
            console.log('Conversation Manager initialized for:', this.characterId);
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
        
        // Load conversation list
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
        
        // Render conversation list
        renderConversationList: function() {
            const $list = $('#conversation-list');
            $list.empty();

            if (this.conversations.length === 0) {
                $list.html('<div class="no-conversations">No saved conversations</div>');
                return;
            }

            const listHtml = this.conversations.map(conv => {
                const isActive = conv.id === this.currentConversationId;
                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" data-id="${conv.id}">
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
        
        // Setup event handlers
        setupEventHandlers: function() {
            const self = this;
            
            $(document)
                .on('click', '#new-conversation', function() {
                    self.startNewConversation();
                })
                .on('click', '#save-conversation', function() {
                    self.saveCurrentConversation();
                })
                .on('click', '.conversation-item', function() {
                    const id = $(this).data('id');
                    self.loadConversation(id);
                })
                .on('click', '.delete-conversation', function(e) {
                    e.stopPropagation();
                    const id = $(this).closest('.conversation-item').data('id');
                    self.deleteConversation(id);
                });
        },
        
        // Save current conversation
        saveCurrentConversation: function() {
            const self = this;
            const messages = this.collectMessagesFromDOM();

            if (messages.length === 0) {
                this.showToast('Cannot save empty conversation', 'error');
                return;
            }

            const defaultTitle = `Chat with ${this.getCharacterName()} - ${new Date().toLocaleDateString()}`;
            const title = prompt('Conversation title:', defaultTitle) || defaultTitle;

            const conversationData = {
                character_id: this.characterId,
                title: title.substring(0, 255),
                messages: messages
            };

            if (this.currentConversationId) {
                conversationData.id = this.currentConversationId;
            }

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
                        self.loadConversationList();
                        self.showToast('Conversation saved');
                    } else {
                        self.showToast(response.data?.message || 'Save failed', 'error');
                    }
                },
                error: function(xhr) {
                    self.showToast('Save failed - check console', 'error');
                    console.error('Save error:', xhr.responseText);
                },
                complete: function() {
                    $('#save-conversation').prop('disabled', false).text('Save');
                }
            });
        },
        
        // Load conversation
        loadConversation: function(conversationId) {
            const self = this;
            $('#chat-history').html('<div class="loading-conversation">Loading...</div>');

            $.ajax({
                url: pmv_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pmv_get_conversation',
                    conversation_id: conversationId,
                    nonce: pmv_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentConversationId = conversationId;
                        self.renderConversation(response.data);
                        self.renderConversationList();
                    } else {
                        self.showToast('Load failed: ' + (response.data?.message || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr) {
                    self.showToast('Load failed - check console', 'error');
                    console.error('Load error:', xhr.responseText);
                }
            });
        },
        
        // Delete conversation
        deleteConversation: function(conversationId) {
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
                        if (conversationId === self.currentConversationId) {
                            self.currentConversationId = null;
                        }
                        self.loadConversationList();
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
        
        // Start new conversation
        startNewConversation: function() {
            this.currentConversationId = null;
            $('#chat-history').empty();
            this.addFirstMessage();
            this.renderConversationList();
            $('#chat-input').focus();
        },
        
        // Helper methods
        collectMessagesFromDOM: function() {
            const messages = [];
            $('.chat-message').each(function() {
                const $msg = $(this);
                let role = $msg.hasClass('user') ? 'user' : 'assistant';
                let content = $msg.text().replace(/^\s*(You|AI):\s*/i, '');
                messages.push({ role, content });
            });
            return messages;
        },
        
        renderConversation: function(conversation) {
            const $history = $('#chat-history');
            $history.empty();
            
            conversation.messages.forEach(msg => {
                const isUser = msg.role === 'user';
                const name = isUser ? 'You' : this.getCharacterName();
                $history.append(`
                    <div class="chat-message ${isUser ? 'user' : 'bot'}">
                        <strong>${name}:</strong> ${this.escapeHtml(msg.content)}
                    </div>
                `);
            });
            
            $history.scrollTop($history[0].scrollHeight);
            window.PMV_Chat.fixChatVisibility();
        },
        
        addFirstMessage: function() {
            try {
                const firstMes = this.characterData.first_mes || 
                                this.characterData.data?.first_mes || 
                                `Hello, I'm ${this.getCharacterName()}`;
                                
                $('#chat-history').append(`
                    <div class="chat-message bot">
                        <strong>${this.getCharacterName()}:</strong> ${this.escapeHtml(firstMes)}
                    </div>
                `);
            } catch(e) {
                console.error('Error adding first message:', e);
            }
        },
        
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
        
        showToast: function(message, type = 'success') {
            const toast = $(`
                <div class="pmv-toast pmv-toast-${type}">
                    ${this.escapeHtml(message)}
                </div>
            `).appendTo('body');
            
            setTimeout(() => toast.remove(), 3000);
        },
        
        escapeHtml: function(str) {
            return String(str).replace(/[&<>"']/g, m => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', 
                '"': '&quot;', "'": '&#39;'
            }[m]));
        }
    };

})(jQuery);
