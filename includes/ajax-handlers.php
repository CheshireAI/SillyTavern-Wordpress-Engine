<?php
/**
 * PNG Metadata Viewer - Complete AJAX Handlers with Message Editing Support, Auto-save Integration, and Fixed Token Handling
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register all AJAX hooks
add_action('wp_ajax_pmv_test_api_directly', 'pmv_test_api_directly_callback');
add_action('wp_ajax_nopriv_pmv_test_api_directly', 'pmv_test_api_directly_callback');
add_action('wp_ajax_start_character_chat', 'pmv_handle_chat_request');
add_action('wp_ajax_nopriv_start_character_chat', 'pmv_handle_chat_request');
add_action('wp_ajax_pmv_save_conversation', 'pmv_save_conversation_callback');
add_action('wp_ajax_pmv_get_conversations', 'pmv_get_conversations_callback');
add_action('wp_ajax_pmv_get_conversation', 'pmv_get_conversation_callback'); 
add_action('wp_ajax_pmv_delete_conversation', 'pmv_delete_conversation_callback');

// Enhanced AJAX hooks for message editing features
add_action('wp_ajax_pmv_update_message', 'pmv_update_message_callback');
add_action('wp_ajax_pmv_delete_message', 'pmv_delete_message_callback');
add_action('wp_ajax_pmv_get_conversation_history', 'pmv_get_conversation_history_callback');
add_action('wp_ajax_pmv_reorder_messages', 'pmv_reorder_messages_callback');
add_action('wp_ajax_pmv_bulk_edit_messages', 'pmv_bulk_edit_messages_callback');

// Auto-save specific AJAX hooks
add_action('wp_ajax_pmv_auto_save_conversation', 'pmv_auto_save_conversation_callback');
add_action('wp_ajax_pmv_check_conversation_status', 'pmv_check_conversation_status_callback');
add_action('wp_ajax_pmv_manual_save_conversation', 'pmv_manual_save_conversation_callback');

// Guest user AJAX hooks (if guest conversations are enabled)
add_action('wp_ajax_nopriv_pmv_save_conversation', 'pmv_save_conversation_guest_callback');
add_action('wp_ajax_nopriv_pmv_get_conversations', 'pmv_get_conversations_guest_callback');
add_action('wp_ajax_nopriv_pmv_get_conversation', 'pmv_get_conversation_guest_callback');
add_action('wp_ajax_nopriv_pmv_delete_conversation', 'pmv_delete_conversation_guest_callback');
add_action('wp_ajax_nopriv_pmv_auto_save_conversation', 'pmv_auto_save_conversation_guest_callback');

/**
 * Direct API test endpoint
 */
function pmv_test_api_directly_callback() {
    try {
        verify_pmv_nonce();
        
        $api_key = get_option('openai_api_key');
        if (empty($api_key)) {
            throw new Exception('API key is missing');
        }

        $api_base_url = get_option('openai_api_base_url', 'https://api.deepseek.com');
        $model = get_option('openai_model', 'deepseek-chat');
        $endpoint = rtrim($api_base_url, '/') . '/chat/completions';
        
        // Get max_tokens setting with validation
        $max_tokens = intval(get_option('openai_max_tokens', 1000));
        if ($max_tokens < 1 || $max_tokens > 8192) {
            $max_tokens = 1000; // Fallback to safe default
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Say hello in one short sentence.']
            ],
            'max_tokens' => $max_tokens,
            'temperature' => floatval(get_option('openai_temperature', 0.7)),
            'stream' => false
        ];

        error_log('PMV API Test - Payload: ' . json_encode($payload));

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? 'API Error';
            error_log('PMV API Test - Error: ' . $error_message);
            throw new Exception($error_message);
        }

        wp_send_json_success([
            'model' => $model,
            'response' => $body,
            'settings' => [
                'max_tokens' => $max_tokens,
                'temperature' => floatval(get_option('openai_temperature', 0.7))
            ]
        ]);

    } catch (Exception $e) {
        error_log('PMV API Test - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Enhanced chat handler with proper token handling, message editing support, and auto-save integration
 */
function pmv_handle_chat_request() {
    try {
        verify_pmv_nonce();
        check_required_params(['character_data', 'user_message']);

        $user_message = sanitize_textarea_field($_POST['user_message']);
        $character_data = json_decode(wp_unslash($_POST['character_data']), true);
        $bot_id = sanitize_text_field($_POST['bot_id'] ?? 'default_bot');
        
        // Extract conversation history if provided
        $conversation_history = isset($_POST['conversation_history']) ? 
            json_decode(wp_unslash($_POST['conversation_history']), true) : [];

        // Build comprehensive character context
        $character_context = build_character_context($character_data);
        
        // Build conversation messages with proper context
        $messages = build_conversation_messages($character_context, $user_message, $conversation_history);

        // Get max_tokens setting with validation
        $max_tokens = intval(get_option('openai_max_tokens', 1000));
        if ($max_tokens < 1 || $max_tokens > 8192) {
            $max_tokens = 1000; // Fallback to safe default
        }

        $payload = [
            'model' => get_option('openai_model', 'deepseek-chat'),
            'messages' => $messages,
            'temperature' => floatval(get_option('openai_temperature', 0.7)),
            'max_tokens' => $max_tokens
        ];

        error_log('PMV Chat - Request payload: ' . json_encode($payload));

        $response = wp_remote_post(get_deepseek_endpoint(), [
            'headers' => get_api_headers(),
            'body' => json_encode($payload),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? 'API Error';
            error_log('PMV Chat - API Error: ' . $error_message);
            throw new Exception($error_message);
        }

        // Track token usage for this completion
        $user_id = get_current_user_id();
        do_action('pmv_after_chat_completion', $body, $user_id, $user_message);

        wp_send_json_success([
            'choices' => $body['choices'],
            'character' => [
                'name' => $character_context['name']
            ],
            'usage' => $body['usage'] ?? null,
            'settings' => [
                'max_tokens' => $max_tokens,
                'temperature' => floatval(get_option('openai_temperature', 0.7))
            ],
            'message_editing_enabled' => true,
            'auto_save_enabled' => true
        ]);

    } catch (Exception $e) {
        error_log('PMV Chat - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Auto-save conversation handler - optimized for frequent calls
 */
function pmv_auto_save_conversation_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            // For auto-save, we can be more lenient - just return success for guests
            wp_send_json_success(['message' => 'Auto-save not available for guests', 'guest_mode' => true]);
            return;
        }
        
        check_required_params(['conversation_data']);
        
        $conversation_data = json_decode(wp_unslash($_POST['conversation_data']), true);
        if (!$conversation_data || !is_array($conversation_data)) {
            throw new Exception('Invalid conversation data format');
        }
        
        // Validate required fields for auto-save
        if (empty($conversation_data['character_id']) || empty($conversation_data['messages'])) {
            wp_send_json_success(['message' => 'Insufficient data for auto-save', 'skipped' => true]);
            return;
        }
        
        // Auto-generate title if not provided
        if (empty($conversation_data['title'])) {
            $character_name = $conversation_data['character_name'] ?? 'AI';
            $conversation_data['title'] = 'Chat with ' . $character_name . ' - ' . date('M j, Y g:i A');
        }
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Check if this is an update to existing conversation
        $conversation_id = isset($conversation_data['id']) ? intval($conversation_data['id']) : null;
        
        $data = [
            'user_id' => $user_id,
            'character_id' => sanitize_text_field($conversation_data['character_id']),
            'title' => sanitize_text_field(substr($conversation_data['title'], 0, 255)),
            'messages' => json_encode($conversation_data['messages']),
            'updated_at' => current_time('mysql')
        ];
        
        if ($conversation_id) {
            // Update existing conversation
            $owner_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                    $conversation_id,
                    $user_id
                )
            );
            
            if ($owner_check) {
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    ['id' => $conversation_id, 'user_id' => $user_id],
                    ['%d', '%s', '%s', '%s', '%s'],
                    ['%d', '%d']
                );
                
                if ($result !== false) {
                    wp_send_json_success([
                        'id' => $conversation_id,
                        'message' => 'Auto-saved successfully',
                        'action' => 'updated',
                        'timestamp' => current_time('mysql')
                    ]);
                    return;
                }
            }
        }
        
        // Create new conversation
        $data['created_at'] = current_time('mysql');
        
        // Check conversation limit for auto-save
        $max_conversations = get_option('pmv_max_conversations_per_user', 50);
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
        
        if ($count >= $max_conversations) {
            // Delete oldest conversation to make room
            $oldest = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM $table_name 
                     WHERE user_id = %d 
                     ORDER BY updated_at ASC 
                     LIMIT 1",
                    $user_id
                )
            );
            
            if ($oldest) {
                $wpdb->delete(
                    $table_name,
                    ['id' => $oldest->id],
                    ['%d']
                );
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result !== false) {
            $new_conversation_id = $wpdb->insert_id;
            wp_send_json_success([
                'id' => $new_conversation_id,
                'message' => 'Auto-saved successfully',
                'action' => 'created',
                'timestamp' => current_time('mysql')
            ]);
        } else {
            throw new Exception('Failed to auto-save conversation');
        }
        
    } catch (Exception $e) {
        error_log('PMV Auto-save - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage(), 'auto_save_error' => true]);
    }
}

/**
 * Manual save conversation handler - prompts for title, more validation
 */
function pmv_manual_save_conversation_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        check_required_params(['conversation_data']);
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception('Authentication required for manual save');
        }
        
        $conversation_data = json_decode(wp_unslash($_POST['conversation_data']), true);
        validate_conversation_data($conversation_data);
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Manual save requires explicit title
        if (empty($conversation_data['title'])) {
            throw new Exception('Title is required for manual save');
        }
        
        $conversation_id = isset($conversation_data['id']) ? intval($conversation_data['id']) : null;
        
        $data = [
            'user_id' => $user_id,
            'character_id' => sanitize_text_field($conversation_data['character_id']),
            'title' => sanitize_text_field(substr($conversation_data['title'], 0, 255)),
            'messages' => json_encode($conversation_data['messages']),
            'updated_at' => current_time('mysql')
        ];
        
        if ($conversation_id) {
            // Update existing conversation
            $owner_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                    $conversation_id,
                    $user_id
                )
            );
            
            if (!$owner_check) {
                throw new Exception('You do not have permission to update this conversation');
            }
            
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $conversation_id, 'user_id' => $user_id],
                ['%d', '%s', '%s', '%s', '%s'],
                ['%d', '%d']
            );
        } else {
            // Create new conversation
            $data['created_at'] = current_time('mysql');
            
            // Check conversation limit
            $max_conversations = get_option('pmv_max_conversations_per_user', 50);
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                    $user_id
                )
            );
            
            if ($count >= $max_conversations) {
                throw new Exception('Maximum number of conversations reached. Please delete some old conversations first.');
            }
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
            $conversation_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            throw new Exception('Database operation failed');
        }
        
        wp_send_json_success([
            'id' => $conversation_id,
            'message' => 'Conversation saved successfully',
            'action' => isset($conversation_data['id']) ? 'updated' : 'created',
            'manual_save' => true,
            'timestamp' => current_time('mysql')
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Manual Save - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Check conversation status - for auto-save coordination
 */
function pmv_check_conversation_status_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_success(['guest_mode' => true, 'has_conversations' => false]);
            return;
        }
        
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
        $character_id = isset($_POST['character_id']) ? sanitize_text_field($_POST['character_id']) : null;
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        $response = ['user_id' => $user_id];
        
        if ($conversation_id) {
            // Check specific conversation
            $conversation = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, title, updated_at, 
                     JSON_LENGTH(messages) as message_count
                     FROM $table_name 
                     WHERE id = %d AND user_id = %d",
                    $conversation_id,
                    $user_id
                ),
                ARRAY_A
            );
            
            if ($conversation) {
                $response['conversation'] = $conversation;
                $response['exists'] = true;
            } else {
                $response['exists'] = false;
            }
        }
        
        if ($character_id) {
            // Get conversations for character
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name 
                     WHERE user_id = %d AND character_id = %s",
                    $user_id,
                    $character_id
                )
            );
            $response['character_conversation_count'] = intval($count);
        }
        
        // Get total conversation count for user
        $total_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
        $response['total_conversations'] = intval($total_count);
        $response['max_conversations'] = intval(get_option('pmv_max_conversations_per_user', 50));
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        error_log('PMV Status Check - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Update a specific message in a conversation (for message editing)
 */
function pmv_update_message_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }
        
        check_required_params(['conversation_id', 'message_index', 'new_content']);
        
        $conversation_id = intval($_POST['conversation_id']);
        $message_index = intval($_POST['message_index']);
        $new_content = sanitize_textarea_field($_POST['new_content']);
        
        if (empty($new_content)) {
            throw new Exception('Message content cannot be empty');
        }
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Get the conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Parse messages
        $messages = json_decode($conversation['messages'], true);
        if (!is_array($messages) || !isset($messages[$message_index])) {
            throw new Exception('Invalid message index');
        }
        
        // Store original content for audit
        $original_content = $messages[$message_index]['content'];
        
        // Update the message content
        $messages[$message_index]['content'] = $new_content;
        $messages[$message_index]['edited'] = true;
        $messages[$message_index]['edited_at'] = current_time('mysql');
        
        // Update the conversation
        $result = $wpdb->update(
            $table_name,
            [
                'messages' => json_encode($messages),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        
        if ($result === false) {
            throw new Exception('Failed to update message');
        }
        
        // Log the edit action
        do_action('pmv_message_edited', $conversation_id, $message_index, $original_content, $new_content);
        
        wp_send_json_success([
            'message' => 'Message updated successfully',
            'updated_content' => $new_content,
            'edited_at' => current_time('mysql'),
            'conversation_id' => $conversation_id,
            'message_index' => $message_index
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Update Message - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Delete a specific message from a conversation (for message editing)
 */
function pmv_delete_message_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }
        
        check_required_params(['conversation_id', 'message_index']);
        
        $conversation_id = intval($_POST['conversation_id']);
        $message_index = intval($_POST['message_index']);
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Get the conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Parse messages
        $messages = json_decode($conversation['messages'], true);
        if (!is_array($messages) || !isset($messages[$message_index])) {
            throw new Exception('Invalid message index');
        }
        
        // Prevent deleting the last message
        if (count($messages) <= 1) {
            throw new Exception('Cannot delete the last message in a conversation');
        }
        
        // Store deleted content for audit
        $deleted_content = $messages[$message_index]['content'];
        
        // Remove the message
        array_splice($messages, $message_index, 1);
        
        // Update the conversation
        $result = $wpdb->update(
            $table_name,
            [
                'messages' => json_encode($messages),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        
        if ($result === false) {
            throw new Exception('Failed to delete message');
        }
        
        // Log the delete action
        do_action('pmv_message_deleted', $conversation_id, $message_index, $deleted_content);
        
        wp_send_json_success([
            'message' => 'Message deleted successfully',
            'remaining_messages' => count($messages),
            'conversation_id' => $conversation_id,
            'deleted_index' => $message_index
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Delete Message - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get conversation history formatted for editing
 */
function pmv_get_conversation_history_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }
        
        check_required_params(['conversation_id']);
        
        $conversation_id = intval($_POST['conversation_id']);
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Get the conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Parse messages
        $messages = json_decode($conversation['messages'], true);
        if (!is_array($messages)) {
            $messages = [];
        }
        
        // Add metadata to messages for editing
        foreach ($messages as $index => &$message) {
            $message['index'] = $index;
            $message['can_edit'] = true;
            $message['can_delete'] = count($messages) > 1; // Don't allow deleting if only one message
            $message['can_regenerate'] = isset($message['role']) && $message['role'] === 'assistant';
            $message['is_edited'] = isset($message['edited']) && $message['edited'];
            $message['edited_at'] = $message['edited_at'] ?? null;
        }
        
        wp_send_json_success([
            'conversation_id' => $conversation_id,
            'title' => $conversation['title'],
            'messages' => $messages,
            'character_id' => $conversation['character_id'],
            'created_at' => $conversation['created_at'],
            'updated_at' => $conversation['updated_at'],
            'message_editing_enabled' => true,
            'auto_save_enabled' => true
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Get Conversation History - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Reorder messages in a conversation
 */
function pmv_reorder_messages_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }
        
        check_required_params(['conversation_id', 'message_order']);
        
        $conversation_id = intval($_POST['conversation_id']);
        $message_order = json_decode(wp_unslash($_POST['message_order']), true);
        
        if (!is_array($message_order)) {
            throw new Exception('Invalid message order format');
        }
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Get the conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Parse messages
        $messages = json_decode($conversation['messages'], true);
        if (!is_array($messages)) {
            throw new Exception('Invalid conversation format');
        }
        
        // Validate order array
        if (count($message_order) !== count($messages)) {
            throw new Exception('Message order count mismatch');
        }
        
        // Reorder messages
        $reordered_messages = [];
        foreach ($message_order as $old_index) {
            if (!isset($messages[$old_index])) {
                throw new Exception('Invalid message index in order array');
            }
            $reordered_messages[] = $messages[$old_index];
        }
        
        // Update the conversation
        $result = $wpdb->update(
            $table_name,
            [
                'messages' => json_encode($reordered_messages),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        
        if ($result === false) {
            throw new Exception('Failed to reorder messages');
        }
        
        wp_send_json_success([
            'message' => 'Messages reordered successfully',
            'new_order' => $message_order,
            'conversation_id' => $conversation_id
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Reorder Messages - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Bulk edit multiple messages
 */
function pmv_bulk_edit_messages_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }
        
        check_required_params(['conversation_id', 'message_edits']);
        
        $conversation_id = intval($_POST['conversation_id']);
        $message_edits = json_decode(wp_unslash($_POST['message_edits']), true);
        
        if (!is_array($message_edits)) {
            throw new Exception('Invalid message edits format');
        }
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Get the conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Parse messages
        $messages = json_decode($conversation['messages'], true);
        if (!is_array($messages)) {
            throw new Exception('Invalid conversation format');
        }
        
        // Apply bulk edits
        $edited_count = 0;
        foreach ($message_edits as $edit) {
            if (!isset($edit['index']) || !isset($edit['content'])) {
                continue;
            }
            
            $index = intval($edit['index']);
            $content = sanitize_textarea_field($edit['content']);
            
            if (isset($messages[$index]) && !empty($content)) {
                $messages[$index]['content'] = $content;
                $messages[$index]['edited'] = true;
                $messages[$index]['edited_at'] = current_time('mysql');
                $edited_count++;
            }
        }
        
        if ($edited_count === 0) {
            throw new Exception('No valid edits to apply');
        }
        
        // Update the conversation
        $result = $wpdb->update(
            $table_name,
            [
                'messages' => json_encode($messages),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        
        if ($result === false) {
            throw new Exception('Failed to apply bulk edits');
        }
        
        wp_send_json_success([
            'message' => 'Bulk edits applied successfully',
            'edited_count' => $edited_count,
            'conversation_id' => $conversation_id
        ]);
        
    } catch (Exception $e) {
        error_log('PMV Bulk Edit Messages - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Save conversation handler with message editing support and auto-save integration
 */
function pmv_save_conversation_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        check_required_params(['conversation']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            throw new Exception('Authentication required');
        }

        $conversation = json_decode(wp_unslash($_POST['conversation']), true);
        validate_conversation_data($conversation);

        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Check user conversation limits
        $max_conversations = get_option('pmv_max_conversations_per_user', 50);
        
        $data = [
            'user_id' => $user_id,
            'character_id' => sanitize_text_field($conversation['character_id']),
            'title' => sanitize_text_field(substr($conversation['title'], 0, 255)),
            'messages' => json_encode($conversation['messages']),
            'updated_at' => current_time('mysql')
        ];

        if (!empty($conversation['id'])) {
            // Update existing conversation
            $conversation_id = intval($conversation['id']);
            
            // Verify ownership
            $owner_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                    $conversation_id,
                    $user_id
                )
            );
            
            if (!$owner_check) {
                throw new Exception('You do not have permission to update this conversation');
            }
            
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $conversation_id, 'user_id' => $user_id],
                ['%d', '%s', '%s', '%s', '%s'],
                ['%d', '%d']
            );
        } else {
            // Create new conversation
            
            // Check conversation limit
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                    $user_id
                )
            );
            
            if ($count >= $max_conversations) {
                // Delete the oldest conversation
                $oldest = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id FROM $table_name 
                         WHERE user_id = %d 
                         ORDER BY updated_at ASC 
                         LIMIT 1",
                        $user_id
                    )
                );
                
                if ($oldest) {
                    $wpdb->delete(
                        $table_name,
                        ['id' => $oldest->id],
                        ['%d']
                    );
                }
            }
            
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                $table_name,
                $data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
            $conversation_id = $wpdb->insert_id;
        }

        if ($result === false) {
            throw new Exception('Database operation failed');
        }

        wp_send_json_success([
            'id' => $conversation_id,
            'message' => 'Conversation saved successfully',
            'message_editing_enabled' => true,
            'auto_save_enabled' => true,
            'timestamp' => current_time('mysql')
        ]);

    } catch (Exception $e) {
        error_log('PMV Save Conversation - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get conversations list with message editing metadata and auto-save info
 */
function pmv_get_conversations_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        $character_id = sanitize_text_field($_POST['character_id'] ?? '');

        if (!$user_id || !$character_id) {
            throw new Exception('Invalid request');
        }

        $table_name = $wpdb->prefix . 'pmv_conversations';
        $conversations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, created_at, updated_at, 
                        JSON_LENGTH(messages) as message_count,
                        SUBSTRING(JSON_EXTRACT(messages, '$[-1].content'), 1, 100) as preview
                FROM $table_name 
                WHERE user_id = %d AND character_id = %s 
                ORDER BY updated_at DESC",
                $user_id,
                $character_id
            ),
            ARRAY_A
        );

        // Add editing metadata and clean up preview
        foreach ($conversations as &$conversation) {
            $conversation['can_edit'] = true;
            $conversation['can_delete'] = true;
            $conversation['message_editing_enabled'] = true;
            $conversation['auto_save_enabled'] = true;
            
            // Clean up preview (remove quotes from JSON_EXTRACT)
            if ($conversation['preview']) {
                $conversation['preview'] = trim($conversation['preview'], '"');
                if (strlen($conversation['preview']) >= 100) {
                    $conversation['preview'] .= '...';
                }
            }
            
            // Add relative time
            $conversation['last_updated_relative'] = human_time_diff(strtotime($conversation['updated_at'])) . ' ago';
        }

        wp_send_json_success($conversations);

    } catch (Exception $e) {
        error_log('PMV Get Conversations - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get single conversation with message editing support
 */
function pmv_get_conversation_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        $conversation_id = intval($_POST['conversation_id'] ?? 0);

        if (!$user_id || !$conversation_id) {
            throw new Exception('Invalid request');
        }

        $table_name = $wpdb->prefix . 'pmv_conversations';
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );

        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        $conversation['messages'] = json_decode($conversation['messages'], true);
        $conversation['message_editing_enabled'] = true;
        $conversation['can_edit_messages'] = true;
        $conversation['auto_save_enabled'] = true;
        
        wp_send_json_success($conversation);

    } catch (Exception $e) {
        error_log('PMV Get Conversation - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Delete conversation
 */
function pmv_delete_conversation_callback() {
    global $wpdb;
    
    try {
        verify_pmv_nonce();
        $user_id = get_current_user_id();
        $conversation_id = intval($_POST['conversation_id'] ?? 0);

        if (!$user_id || !$conversation_id) {
            throw new Exception('Invalid request');
        }

        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Verify ownership before deletion
        $owner_check = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            )
        );
        
        if (!$owner_check) {
            throw new Exception('You do not have permission to delete this conversation');
        }
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%d', '%d']
        );

        if ($result === false) {
            throw new Exception('Delete operation failed');
        }

        wp_send_json_success(['message' => 'Conversation deleted successfully']);

    } catch (Exception $e) {
        error_log('PMV Delete Conversation - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Guest conversation handlers
 */
function pmv_save_conversation_guest_callback() {
    try {
        verify_pmv_nonce();
        
        // Check if guest conversations are allowed
        if (!get_option('pmv_allow_guest_conversations', false)) {
            throw new Exception('Guest conversations are not allowed');
        }
        
        // For guests, return success but note that storage is client-side
        wp_send_json_success([
            'message' => 'Guest conversation handling is client-side only',
            'guest_mode' => true
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function pmv_get_conversations_guest_callback() {
    try {
        verify_pmv_nonce();
        
        if (!get_option('pmv_allow_guest_conversations', false)) {
            throw new Exception('Guest conversations are not allowed');
        }
        
        // Return empty array for guests (handled client-side)
        wp_send_json_success([]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function pmv_get_conversation_guest_callback() {
    try {
        verify_pmv_nonce();
        
        if (!get_option('pmv_allow_guest_conversations', false)) {
            throw new Exception('Guest conversations are not allowed');
        }
        
        wp_send_json_error(['message' => 'Guest conversation loading must be handled client-side']);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function pmv_delete_conversation_guest_callback() {
    try {
        verify_pmv_nonce();
        
        if (!get_option('pmv_allow_guest_conversations', false)) {
            throw new Exception('Guest conversations are not allowed');
        }
        
        wp_send_json_error(['message' => 'Guest conversation deletion must be handled client-side']);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function pmv_auto_save_conversation_guest_callback() {
    try {
        verify_pmv_nonce();
        
        if (!get_option('pmv_allow_guest_conversations', false)) {
            throw new Exception('Guest conversations are not allowed');
        }
        
        // For guests, auto-save is handled client-side
        wp_send_json_success([
            'message' => 'Guest auto-save is handled client-side',
            'guest_mode' => true
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Build comprehensive character context from character data
 */
function build_character_context($character_data) {
    // Extract character info based on structure
    $character = null;
    
    if (isset($character_data['data'])) {
        $character = $character_data['data'];
    } elseif (isset($character_data['name'])) {
        $character = $character_data;
    } else {
        // Recursively search for character object
        $character = find_character_object($character_data);
    }
    
    if (!$character) {
        throw new Exception('Invalid character data provided');
    }
    
    // Build comprehensive context
    $context = [
        'name' => $character['name'] ?? 'AI Assistant',
        'description' => $character['description'] ?? '',
        'personality' => $character['personality'] ?? '',
        'scenario' => $character['scenario'] ?? '',
        'first_mes' => $character['first_mes'] ?? '',
        'mes_example' => $character['mes_example'] ?? '',
        'system_prompt' => $character['system_prompt'] ?? '',
        'post_history_instructions' => $character['post_history_instructions'] ?? '',
        'creator_notes' => $character['creator_notes'] ?? '',
        'character_book' => $character['character_book'] ?? [],
        'extensions' => $character['extensions'] ?? []
    ];
    
    return $context;
}

/**
 * Recursively find character object in data structure
 */
function find_character_object($data) {
    if (!is_array($data)) return null;
    
    // Check if current level has character indicators
    if (isset($data['name']) && (isset($data['description']) || isset($data['personality']))) {
        return $data;
    }
    
    // Search nested arrays/objects
    foreach ($data as $value) {
        if (is_array($value)) {
            $result = find_character_object($value);
            if ($result) return $result;
        }
    }
    
    return null;
}

/**
 * Build conversation messages with character context and editing support
 */
function build_conversation_messages($character, $user_message, $conversation_history = []) {
    $messages = [];
    
    // 1. Build system prompt with character context
    $system_prompt = build_system_prompt($character);
    $messages[] = ['role' => 'system', 'content' => $system_prompt];
    
    // 2. Add character book context if available
    $lorebook_context = extract_relevant_lorebook_entries($character, $user_message);
    if (!empty($lorebook_context)) {
        $messages[] = ['role' => 'system', 'content' => "Additional context about {$character['name']}:\n" . $lorebook_context];
    }
    
    // 3. Add conversation history (with edited messages)
    if (!empty($conversation_history)) {
        foreach ($conversation_history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
    } else {
        // 4. Add first message if no history
        if (!empty($character['first_mes'])) {
            $messages[] = ['role' => 'assistant', 'content' => $character['first_mes']];
        }
        
        // 5. Add example conversation if available
        if (!empty($character['mes_example'])) {
            $example_messages = parse_message_example($character['mes_example'], $character['name']);
            $messages = array_merge($messages, $example_messages);
        }
    }
    
    // 6. Add current user message
    $messages[] = ['role' => 'user', 'content' => $user_message];
    
    // 7. Add post-history instructions if available
    if (!empty($character['post_history_instructions'])) {
        $messages[] = ['role' => 'system', 'content' => $character['post_history_instructions']];
    }
    
    return $messages;
}

/**
 * Build comprehensive system prompt
 */
function build_system_prompt($character) {
    $prompt_parts = [];
    
    // Start with custom system prompt if available
    if (!empty($character['system_prompt'])) {
        $prompt_parts[] = $character['system_prompt'];
    } else {
        // Build default system prompt
        $prompt_parts[] = "You are {$character['name']}.";
    }
    
    // Add description
    if (!empty($character['description'])) {
        $prompt_parts[] = "Description: " . $character['description'];
    }
    
    // Add personality
    if (!empty($character['personality'])) {
        $prompt_parts[] = "Personality: " . $character['personality'];
    }
    
    // Add scenario
    if (!empty($character['scenario'])) {
        $prompt_parts[] = "Scenario: " . $character['scenario'];
    }
    
    // Add creator notes
    if (!empty($character['creator_notes'])) {
        $prompt_parts[] = "Creator Notes: " . $character['creator_notes'];
    }
    
    // Add behavioral instructions
    $prompt_parts[] = "Stay in character as {$character['name']} at all times.";
    $prompt_parts[] = "Respond naturally and engage in conversation.";
    $prompt_parts[] = "Do not mention that you are an AI or language model.";
    
    return implode("\n\n", $prompt_parts);
}

/**
 * Extract relevant lorebook entries based on user message
 */
function extract_relevant_lorebook_entries($character, $user_message) {
    if (empty($character['character_book']['entries'])) {
        return '';
    }
    
    $relevant_entries = [];
    $user_message_lower = strtolower($user_message);
    
    foreach ($character['character_book']['entries'] as $entry) {
        if (!$entry['enabled']) continue;
        
        // Check if any of the entry's keys are mentioned in the user message
        foreach ($entry['keys'] as $key) {
            $key_lower = strtolower($key);
            
            if ($entry['case_sensitive']) {
                $found = strpos($user_message, $key) !== false;
            } else {
                $found = strpos($user_message_lower, $key_lower) !== false;
            }
            
            if ($found) {
                $relevant_entries[] = [
                    'content' => $entry['content'],
                    'order' => $entry['insertion_order'] ?? 0
                ];
                break; // Found one key, no need to check others for this entry
            }
        }
    }
    
    // Sort by insertion order
    usort($relevant_entries, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    // Combine content
    $content = '';
    foreach ($relevant_entries as $entry) {
        $content .= $entry['content'] . "\n\n";
    }
    
    return trim($content);
}

/**
 * Parse message examples into conversation format
 */
function parse_message_example($mes_example, $character_name) {
    $messages = [];
    
    // Try to parse structured examples
    if (preg_match_all('/<START>(.*?)<\/START>/s', $mes_example, $matches)) {
        foreach ($matches[1] as $example) {
            $lines = explode("\n", trim($example));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Parse format like "Character: message" or "{{user}}: message"
                if (preg_match('/^([^:]+):\s*(.+)$/s', $line, $match)) {
                    $speaker = trim($match[1]);
                    $content = trim($match[2]);
                    
                    if ($speaker === '{{user}}' || $speaker === 'User' || $speaker === 'You') {
                        $messages[] = ['role' => 'user', 'content' => $content];
                    } elseif ($speaker === $character_name || $speaker === '{{char}}') {
                        $messages[] = ['role' => 'assistant', 'content' => $content];
                    }
                }
            }
        }
    }
    
    return $messages;
}

/*********************
 * Helper Functions
 *********************/

function verify_pmv_nonce() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        throw new Exception('Security verification failed');
    }
}

function check_required_params($params) {
    foreach ($params as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
}

function get_deepseek_endpoint() {
    return rtrim(get_option('openai_api_base_url', 'https://api.deepseek.com'), '/') . '/chat/completions';
}

function get_api_headers() {
    return [
        'Authorization' => 'Bearer ' . get_option('openai_api_key'),
        'Content-Type' => 'application/json'
    ];
}

function validate_conversation_data($data) {
    $required = ['character_id', 'messages'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing conversation $field");
        }
    }
    
    if (!is_array($data['messages'])) {
        throw new Exception('Invalid messages format');
    }
}

/**
 * Hook to add message editing capabilities to existing conversations
 */
add_action('pmv_conversation_loaded', 'pmv_add_message_editing_capabilities');

function pmv_add_message_editing_capabilities($conversation_data) {
    // This hook can be used by other parts of the system to know when 
    // message editing capabilities should be enabled
    do_action('pmv_enable_message_editing', $conversation_data);
}

/**
 * Log message editing actions for audit trail
 */
add_action('pmv_message_edited', 'pmv_log_message_edit', 10, 4);
add_action('pmv_message_deleted', 'pmv_log_message_delete', 10, 3);

function pmv_log_message_edit($conversation_id, $message_index, $old_content, $new_content) {
    $user_id = get_current_user_id();
    error_log("PMV Message Edit - Conversation: $conversation_id, Message: $message_index, User: $user_id");
    
    // You can extend this to write to a custom audit log table if needed
    do_action('pmv_audit_log', 'message_edited', [
        'conversation_id' => $conversation_id,
        'message_index' => $message_index,
        'user_id' => $user_id,
        'old_content_length' => strlen($old_content),
        'new_content_length' => strlen($new_content)
    ]);
}

function pmv_log_message_delete($conversation_id, $message_index, $deleted_content) {
    $user_id = get_current_user_id();
    error_log("PMV Message Delete - Conversation: $conversation_id, Message: $message_index, User: $user_id");
    
    // You can extend this to write to a custom audit log table if needed
    do_action('pmv_audit_log', 'message_deleted', [
        'conversation_id' => $conversation_id,
        'message_index' => $message_index,
        'user_id' => $user_id,
        'deleted_content_length' => strlen($deleted_content)
    ]);
}

/**
 * Rate limiting for message editing operations
 */
add_action('init', 'pmv_setup_message_editing_rate_limits');

function pmv_setup_message_editing_rate_limits() {
    // You can implement rate limiting here if needed
    // For example, limit users to X message edits per hour
}

/**
 * Clean up old conversations periodically
 */
add_action('wp_scheduled_delete', 'pmv_cleanup_old_conversations');

function pmv_cleanup_old_conversations() {
    global $wpdb;
    
    $retention_days = get_option('pmv_conversation_retention_days', 90);
    if ($retention_days <= 0) {
        return; // Retention disabled
    }
    
    $table_name = $wpdb->prefix . 'pmv_conversations';
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
    
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE updated_at < %s",
            $cutoff_date
        )
    );
    
    if ($deleted) {
        error_log("PMV: Cleaned up $deleted old conversations older than $retention_days days");
    }
}

/**
 * Auto-save monitoring and coordination
 */
add_action('pmv_auto_save_triggered', 'pmv_handle_auto_save_event', 10, 3);

function pmv_handle_auto_save_event($user_id, $conversation_id, $trigger_type) {
    // Log auto-save events for monitoring
    error_log("PMV Auto-save triggered - User: $user_id, Conversation: $conversation_id, Trigger: $trigger_type");
    
    // You can add additional auto-save coordination logic here
    // For example, debouncing, conflict resolution, etc.
}

/**
 * Enhanced error logging for debugging
 */
function pmv_enhanced_error_log($message, $context = []) {
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    error_log('PMV Enhanced Log: ' . json_encode($log_entry));
}

/**
 * Performance monitoring for AJAX handlers
 */
function pmv_monitor_ajax_performance($action, $start_time) {
    $duration = microtime(true) - $start_time;
    
    if ($duration > 2.0) { // Log slow requests (>2 seconds)
        pmv_enhanced_error_log("Slow AJAX request detected", [
            'action' => $action,
            'duration' => $duration,
            'memory_usage' => memory_get_peak_usage(true)
        ]);
    }
}
?>
