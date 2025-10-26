<?php
/**
 * Enhanced Database functions for conversation management with keyword flagging
 * Updated version with safety features and export functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get conversations for admin view with enhanced filters including keyword flagging
 */
function pmv_get_conversations_for_admin($args = []) {
    global $wpdb;
    
    $defaults = [
        'user_filter' => '',
        'character_filter' => '',
        'keyword_search' => '',
        'safety_filter' => '',
        'date_from' => '',
        'date_to' => '',
        'page' => 1,
        'per_page' => 25
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    // Base query with message content for keyword search
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    $sql = "SELECT DISTINCT c.*, u.user_login, u.user_email, u.display_name,
                   (SELECT COUNT(*) FROM {$messages_table} cm WHERE cm.conversation_id = c.id) as message_count
            FROM {$conversations_table} c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID";
    
    // Add join for keyword search or safety filter
    if (!empty($args['keyword_search']) || !empty($args['safety_filter'])) {
        $sql .= " LEFT JOIN {$messages_table} m ON c.id = m.conversation_id";
    }
    
    $sql .= " WHERE 1=1";
    
    $where_conditions = [];
    $where_values = [];
    
    // Apply filters
    if (!empty($args['user_filter'])) {
        $where_conditions[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($args['user_filter']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    if (!empty($args['character_filter'])) {
        $where_conditions[] = "c.character_name LIKE %s";
        $where_values[] = '%' . $wpdb->esc_like($args['character_filter']) . '%';
    }
    
    if (!empty($args['keyword_search'])) {
        $where_conditions[] = "m.content LIKE %s";
        $where_values[] = '%' . $wpdb->esc_like($args['keyword_search']) . '%';
    }
    
    if (!empty($args['safety_filter'])) {
        $unsafe_keywords = pmv_get_unsafe_keywords();
        if (!empty($unsafe_keywords)) {
            $keyword_conditions = [];
            foreach ($unsafe_keywords as $keyword) {
                $case_sensitive = get_option('pmv_keyword_case_sensitive', 0);
                if ($case_sensitive) {
                    $keyword_conditions[] = "m.content LIKE %s";
                } else {
                    $keyword_conditions[] = "LOWER(m.content) LIKE LOWER(%s)";
                }
                $keyword_values[] = '%' . $wpdb->esc_like($keyword) . '%';
            }
            
            if ($args['safety_filter'] === 'unsafe') {
                $where_conditions[] = "(" . implode(" OR ", $keyword_conditions) . ")";
                $where_values = array_merge($where_values, $keyword_values);
            } elseif ($args['safety_filter'] === 'safe') {
                $where_conditions[] = "NOT (" . implode(" OR ", $keyword_conditions) . ")";
                $where_values = array_merge($where_values, $keyword_values);
            }
        }
    }
    
    if (!empty($args['date_from'])) {
        $where_conditions[] = "c.created_at >= %s";
        $where_values[] = $args['date_from'] . ' 00:00:00';
    }
    
    if (!empty($args['date_to'])) {
        $where_conditions[] = "c.created_at <= %s";
        $where_values[] = $args['date_to'] . ' 23:59:59';
    }
    
    if (!empty($where_conditions)) {
        $sql .= " AND " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY c.updated_at DESC";
    
    // Add pagination
    $offset = ($args['page'] - 1) * $args['per_page'];
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);
    
    if (!empty($where_values)) {
        $sql = $wpdb->prepare($sql, $where_values);
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Get total count of conversations with enhanced filters
 */
function pmv_get_conversations_count($args = []) {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    $sql = "SELECT COUNT(DISTINCT c.id) FROM {$conversations_table} c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID";
    
    // Add join for keyword search or safety filter
    if (!empty($args['keyword_search']) || !empty($args['safety_filter'])) {
        $sql .= " LEFT JOIN {$messages_table} m ON c.id = m.conversation_id";
    }
    
    $sql .= " WHERE 1=1";
    
    $where_conditions = [];
    $where_values = [];
    
    // Apply same filters as main query
    if (!empty($args['user_filter'])) {
        $where_conditions[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($args['user_filter']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    if (!empty($args['character_filter'])) {
        $where_conditions[] = "c.character_name LIKE %s";
        $where_values[] = '%' . $wpdb->esc_like($args['character_filter']) . '%';
    }
    
    if (!empty($args['keyword_search'])) {
        $where_conditions[] = "m.content LIKE %s";
        $where_values[] = '%' . $wpdb->esc_like($args['keyword_search']) . '%';
    }
    
    if (!empty($args['safety_filter'])) {
        $unsafe_keywords = pmv_get_unsafe_keywords();
        if (!empty($unsafe_keywords)) {
            $keyword_conditions = [];
            $keyword_values = [];
            foreach ($unsafe_keywords as $keyword) {
                $case_sensitive = get_option('pmv_keyword_case_sensitive', 0);
                if ($case_sensitive) {
                    $keyword_conditions[] = "m.content LIKE %s";
                } else {
                    $keyword_conditions[] = "LOWER(m.content) LIKE LOWER(%s)";
                }
                $keyword_values[] = '%' . $wpdb->esc_like($keyword) . '%';
            }
            
            if ($args['safety_filter'] === 'unsafe') {
                $where_conditions[] = "(" . implode(" OR ", $keyword_conditions) . ")";
                $where_values = array_merge($where_values, $keyword_values);
            } elseif ($args['safety_filter'] === 'safe') {
                $where_conditions[] = "NOT (" . implode(" OR ", $keyword_conditions) . ")";
                $where_values = array_merge($where_values, $keyword_values);
            }
        }
    }
    
    if (!empty($args['date_from'])) {
        $where_conditions[] = "c.created_at >= %s";
        $where_values[] = $args['date_from'] . ' 00:00:00';
    }
    
    if (!empty($args['date_to'])) {
        $where_conditions[] = "c.created_at <= %s";
        $where_values[] = $args['date_to'] . ' 23:59:59';
    }
    
    if (!empty($where_conditions)) {
        $sql .= " AND " . implode(" AND ", $where_conditions);
    }
    
    if (!empty($where_values)) {
        $sql = $wpdb->prepare($sql, $where_values);
    }
    
    return intval($wpdb->get_var($sql));
}

/**
 * Get unsafe keywords from settings
 */
function pmv_get_unsafe_keywords() {
    $keywords_string = get_option('pmv_unsafe_keywords', '');
    if (empty($keywords_string)) {
        return [];
    }
    
    $keywords = array_filter(array_map('trim', explode(',', $keywords_string)));
    return array_unique($keywords);
}

/**
 * Check if a conversation contains unsafe keywords
 */
function pmv_check_conversation_safety($conversation_id) {
    global $wpdb;
    
    // Check if keyword flagging is enabled
    if (!get_option('pmv_enable_keyword_flagging', 1)) {
        return ['is_unsafe' => false, 'flagged_keywords' => []];
    }
    
    $unsafe_keywords = pmv_get_unsafe_keywords();
    if (empty($unsafe_keywords)) {
        return ['is_unsafe' => false, 'flagged_keywords' => []];
    }
    
    $conversation_id = intval($conversation_id);
    if ($conversation_id <= 0) {
        return ['is_unsafe' => false, 'flagged_keywords' => []];
    }
    
    // Get all messages for this conversation
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT content FROM {$messages_table} WHERE conversation_id = %d",
        $conversation_id
    ));
    
    if (empty($messages)) {
        return ['is_unsafe' => false, 'flagged_keywords' => []];
    }
    
    $flagged_keywords = [];
    $case_sensitive = get_option('pmv_keyword_case_sensitive', 0);
    
    // Check each message for unsafe keywords
    foreach ($messages as $message) {
        $content = $case_sensitive ? $message->content : strtolower($message->content);
        
        foreach ($unsafe_keywords as $keyword) {
            $search_keyword = $case_sensitive ? $keyword : strtolower($keyword);
            
            if (strpos($content, $search_keyword) !== false) {
                if (!in_array($keyword, $flagged_keywords)) {
                    $flagged_keywords[] = $keyword;
                }
            }
        }
    }
    
    return [
        'is_unsafe' => !empty($flagged_keywords),
        'flagged_keywords' => $flagged_keywords
    ];
}

/**
 * Get conversation safety statistics
 */
function pmv_get_conversation_safety_stats($filter_args = []) {
    global $wpdb;
    
    if (!get_option('pmv_enable_keyword_flagging', 1)) {
        return ['total' => 0, 'safe' => 0, 'unsafe' => 0];
    }
    
    // Get total conversations matching filters (excluding safety filter)
    $args_without_safety = $filter_args;
    unset($args_without_safety['safety_filter']);
    
    $total_count = pmv_get_conversations_count($args_without_safety);
    
    // Get unsafe conversations count
    $unsafe_args = $args_without_safety;
    $unsafe_args['safety_filter'] = 'unsafe';
    $unsafe_count = pmv_get_conversations_count($unsafe_args);
    
    $safe_count = $total_count - $unsafe_count;
    
    return [
        'total' => $total_count,
        'safe' => $safe_count,
        'unsafe' => $unsafe_count
    ];
}

/**
 * Delete a conversation and its messages
 */
function pmv_delete_conversation($conversation_id) {
    global $wpdb;
    
    $conversation_id = intval($conversation_id);
    if ($conversation_id <= 0) {
        return false;
    }
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Delete messages first
        $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
        $messages_deleted = $wpdb->delete($messages_table, ['conversation_id' => $conversation_id], ['%d']);
        
        // Delete conversation
        $conversations_table = $wpdb->prefix . 'pmv_conversations';
        $conversation_deleted = $wpdb->delete($conversations_table, ['id' => $conversation_id], ['%d']);
        
        if ($conversation_deleted !== false) {
            $wpdb->query('COMMIT');
            return true;
        } else {
            $wpdb->query('ROLLBACK');
            return false;
        }
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('PMV: Error deleting conversation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get conversation details with messages and safety info for admin view
 */
function pmv_get_conversation_details($conversation_id) {
    global $wpdb;
    
    $conversation_id = intval($conversation_id);
    if ($conversation_id <= 0) {
        return null;
    }
    
    // Get conversation info
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.user_login, u.user_email, u.display_name
         FROM {$conversations_table} c
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.id = %d",
        $conversation_id
    ));
    
    if (!$conversation) {
        return null;
    }
    
    // Fallback for character_name
    $character_name = $conversation->character_name;
    if (empty($character_name) || $character_name === 'Unknown Character') {
        if (!empty($conversation->character_id)) {
            $character_name = $conversation->character_id;
        } else {
            $character_name = 'Unknown Character';
        }
    }
    
    // Get messages
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$messages_table}
         WHERE conversation_id = %d
         ORDER BY created_at ASC",
        $conversation_id
    ));
    
    // Format user display
    $user_display = 'Guest User';
    if ($conversation->user_id) {
        $user_display = $conversation->display_name ?: $conversation->user_login;
        if ($conversation->user_email) {
            $user_display .= ' (' . $conversation->user_email . ')';
        }
    }
    
    // Format messages
    $formatted_messages = [];
    foreach ($messages as $message) {
        $formatted_messages[] = [
            'role' => $message->role,
            'content' => $message->content,
            'timestamp' => date('Y-m-d H:i:s', strtotime($message->created_at))
        ];
    }
    
    // Get safety information
    $safety_info = null;
    if (get_option('pmv_enable_keyword_flagging', 1)) {
        $safety_info = pmv_check_conversation_safety($conversation_id);
    }
    
    return [
        'id' => $conversation->id,
        'character_name' => $character_name,
        'user_display' => $user_display,
        'created_at' => date('Y-m-d H:i:s', strtotime($conversation->created_at)),
        'updated_at' => date('Y-m-d H:i:s', strtotime($conversation->updated_at)),
        'messages' => $formatted_messages,
        'safety_info' => $safety_info
    ];
}

/**
 * Export a single conversation
 */
function pmv_export_conversation($conversation_id) {
    $conversation = pmv_get_conversation_details($conversation_id);
    
    if (!$conversation) {
        return false;
    }
    
    $export_content = "=== CONVERSATION EXPORT ===\n\n";
    $export_content .= "Character: " . $conversation['character_name'] . "\n";
    $export_content .= "User: " . $conversation['user_display'] . "\n";
    $export_content .= "Created: " . $conversation['created_at'] . "\n";
    $export_content .= "Updated: " . $conversation['updated_at'] . "\n";
    $export_content .= "Messages: " . count($conversation['messages']) . "\n";
    
    if ($conversation['safety_info']) {
        $export_content .= "Safety Status: " . ($conversation['safety_info']['is_unsafe'] ? 'UNSAFE' : 'SAFE') . "\n";
        if (!empty($conversation['safety_info']['flagged_keywords'])) {
            $export_content .= "Flagged Keywords: " . implode(', ', $conversation['safety_info']['flagged_keywords']) . "\n";
        }
    }
    
    $export_content .= "\n" . str_repeat("=", 50) . "\n\n";
    
    // Add messages
    foreach ($conversation['messages'] as $index => $message) {
        $speaker = $message['role'] === 'user' ? 'USER' : strtoupper($conversation['character_name']);
        $export_content .= "[" . ($index + 1) . "] " . $speaker . " (" . $message['timestamp'] . "):\n";
        $export_content .= $message['content'] . "\n\n";
    }
    
    $export_content .= "=== END OF CONVERSATION ===\n";
    
    // Generate filename
    $safe_character_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $conversation['character_name']);
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "conversation_{$safe_character_name}_{$timestamp}.txt";
    
    return [
        'content' => $export_content,
        'filename' => $filename
    ];
}

/**
 * Export multiple conversations
 */
function pmv_export_all_conversations($filter_args = []) {
    global $wpdb;
    
    // Remove pagination from filter args for export
    unset($filter_args['page']);
    unset($filter_args['per_page']);
    
    $conversations = pmv_get_conversations_for_admin($filter_args);
    
    if (empty($conversations)) {
        return false;
    }
    
    $export_data = [
        'export_info' => [
            'timestamp' => current_time('c'),
            'total_conversations' => count($conversations),
            'filters_applied' => $filter_args,
            'exported_by' => wp_get_current_user()->user_login ?? 'Unknown'
        ],
        'conversations' => []
    ];
    
    foreach ($conversations as $conv) {
        $conversation_details = pmv_get_conversation_details($conv->id);
        
        if ($conversation_details) {
            $export_data['conversations'][] = [
                'id' => $conv->id,
                'character_name' => $conv->character_name,
                'character_id' => $conv->character_id,
                'user_info' => [
                    'user_id' => $conv->user_id,
                    'display_name' => $conv->display_name,
                    'user_login' => $conv->user_login,
                    'user_email' => $conv->user_email
                ],
                'title' => $conv->title,
                'created_at' => $conversation_details['created_at'],
                'updated_at' => $conversation_details['updated_at'],
                'message_count' => $conv->message_count,
                'messages' => $conversation_details['messages'],
                'safety_info' => $conversation_details['safety_info']
            ];
        }
    }
    
    // Generate filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "conversations_export_{$timestamp}.json";
    
    return [
        'content' => json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        'filename' => $filename,
        'count' => count($conversations)
    ];
}

/**
 * AJAX handler for getting conversation details
 */
function pmv_ajax_get_conversation_details() {
    // Check nonce
    if (!wp_verify_nonce($_GET['_wpnonce'], 'pmv_get_conversation_details')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $conversation_id = intval($_GET['conversation_id']);
    $conversation = pmv_get_conversation_details($conversation_id);
    
    if ($conversation) {
        wp_send_json_success($conversation);
    } else {
        wp_send_json_error(['message' => 'Conversation not found']);
    }
}

/**
 * AJAX handler for exporting single conversation
 */
function pmv_ajax_export_conversation() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_export_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $conversation_id = intval($_POST['conversation_id']);
    $export_data = pmv_export_conversation($conversation_id);
    
    if ($export_data) {
        wp_send_json_success($export_data);
    } else {
        wp_send_json_error(['message' => 'Failed to export conversation']);
    }
}

/**
 * AJAX handler for exporting all conversations
 */
function pmv_ajax_export_all_conversations() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_export_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Get filter parameters
    $filter_args = [
        'user_filter' => sanitize_text_field($_POST['user_filter'] ?? ''),
        'character_filter' => sanitize_text_field($_POST['character_filter'] ?? ''),
        'keyword_search' => sanitize_text_field($_POST['keyword_search'] ?? ''),
        'safety_filter' => sanitize_text_field($_POST['safety_filter'] ?? ''),
        'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
        'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
    ];
    
    $export_data = pmv_export_all_conversations($filter_args);
    
    if ($export_data) {
        wp_send_json_success($export_data);
    } else {
        wp_send_json_error(['message' => 'No conversations found to export']);
    }
}

/**
 * AJAX handler for getting safety statistics
 */
function pmv_ajax_get_safety_stats() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_safety_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $stats = pmv_get_conversation_safety_stats();
    
    // Get recent flagged keywords
    $recent_flags = [];
    if (get_option('pmv_enable_keyword_flagging', 1)) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
        $unsafe_keywords = pmv_get_unsafe_keywords();
        
        if (!empty($unsafe_keywords)) {
            // Get recent messages that contain flagged keywords
            $case_sensitive = get_option('pmv_keyword_case_sensitive', 0);
            $found_keywords = [];
            
            foreach ($unsafe_keywords as $keyword) {
                $search_condition = $case_sensitive ? 
                    "content LIKE %s" : 
                    "LOWER(content) LIKE LOWER(%s)";
                
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$messages_table} WHERE {$search_condition} AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
                    '%' . $wpdb->esc_like($keyword) . '%'
                ));
                
                if ($count > 0) {
                    $found_keywords[] = $keyword;
                }
            }
            
            $recent_flags = array_slice($found_keywords, 0, 10); // Limit to 10 most common
        }
    }
    
    $stats['recent_flags'] = $recent_flags;
    
    wp_send_json_success($stats);
}

/**
 * AJAX handler for testing keyword system
 */
function pmv_ajax_test_keywords() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_safety_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $keywords_string = sanitize_textarea_field($_POST['keywords'] ?? '');
    $test_text = sanitize_textarea_field($_POST['text'] ?? '');
    
    if (empty($keywords_string) || empty($test_text)) {
        wp_send_json_error(['message' => 'Keywords and test text are required']);
        return;
    }
    
    // Parse keywords
    $keywords = array_filter(array_map('trim', explode(',', $keywords_string)));
    
    if (empty($keywords)) {
        wp_send_json_error(['message' => 'No valid keywords found']);
        return;
    }
    
    // Test keywords against text
    $matched_keywords = [];
    $case_sensitive = get_option('pmv_keyword_case_sensitive', 0);
    $search_text = $case_sensitive ? $test_text : strtolower($test_text);
    
    foreach ($keywords as $keyword) {
        $search_keyword = $case_sensitive ? $keyword : strtolower($keyword);
        
        if (strpos($search_text, $search_keyword) !== false) {
            $matched_keywords[] = $keyword;
        }
    }
    
    wp_send_json_success([
        'is_unsafe' => !empty($matched_keywords),
        'matched_keywords' => $matched_keywords,
        'total_keywords_tested' => count($keywords)
    ]);
}

/**
 * Get conversations for a specific character and user (frontend use)
 */
function pmv_get_user_conversations($user_id, $character_id) {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    $sql = $wpdb->prepare(
        "SELECT c.id, c.title, c.created_at, c.updated_at,
                (SELECT COUNT(*) FROM {$messages_table} cm WHERE cm.conversation_id = c.id) as message_count
         FROM {$conversations_table} c
         WHERE user_id = %d AND character_id = %s
         ORDER BY updated_at DESC
         LIMIT 50",
        $user_id,
        $character_id
    );
    
    return $wpdb->get_results($sql);
}

/**
 * Save a conversation
 */
function pmv_save_conversation($conversation_data) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return ['success' => false, 'message' => 'User not logged in'];
    }
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        $conversation_id = $conversation_data['id'] ?? null;
        
        if ($conversation_id) {
            // Update existing conversation
            $result = $wpdb->update(
                $conversations_table,
                [
                    'title' => $conversation_data['title'],
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $conversation_id, 'user_id' => $user_id],
                ['%s', '%s'],
                ['%d', '%d']
            );
            
            if ($result === false) {
                throw new Exception('Failed to update conversation');
            }
            
            // Delete old messages
            $wpdb->delete($messages_table, ['conversation_id' => $conversation_id], ['%d']);
        } else {
            // Create new conversation
            $result = $wpdb->insert(
                $conversations_table,
                [
                    'user_id' => $user_id,
                    'character_id' => $conversation_data['character_id'],
                    'character_name' => $conversation_data['character_name'] ?? 'Unknown',
                    'title' => $conversation_data['title'],
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                throw new Exception('Failed to create conversation');
            }
            
            $conversation_id = $wpdb->insert_id;
        }
        
        // Save messages
        if (!empty($conversation_data['messages'])) {
            foreach ($conversation_data['messages'] as $message) {
                $wpdb->insert(
                    $messages_table,
                    [
                        'conversation_id' => $conversation_id,
                        'role' => $message['role'],
                        'content' => $message['content'],
                        'created_at' => current_time('mysql')
                    ],
                    ['%d', '%s', '%s', '%s']
                );
            }
        }
        
        $wpdb->query('COMMIT');
        
        return [
            'success' => true,
            'id' => $conversation_id,
            'message' => 'Conversation saved successfully'
        ];
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('PMV: Error saving conversation: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to save conversation: ' . $e->getMessage()
        ];
    }
}

/**
 * Get conversation for loading
 */
function pmv_get_conversation($conversation_id, $user_id = null) {
    global $wpdb;
    
    if (!$user_id) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return null;
        }
    }
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    // Get conversation
    $conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$conversations_table} WHERE id = %d AND user_id = %d",
        $conversation_id,
        $user_id
    ));
    
    if (!$conversation) {
        return null;
    }
    
    // Get messages
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT role, content, created_at FROM {$messages_table} 
         WHERE conversation_id = %d ORDER BY created_at ASC",
        $conversation_id
    ));
    
    return [
        'id' => $conversation->id,
        'character_id' => $conversation->character_id,
        'character_name' => $conversation->character_name,
        'title' => $conversation->title,
        'messages' => $messages
    ];
}

// Register AJAX handlers
// Removed pmv_get_conversation_details to avoid conflict with content moderation system
add_action('wp_ajax_pmv_export_conversation', 'pmv_ajax_export_conversation');
add_action('wp_ajax_pmv_export_all_conversations', 'pmv_ajax_export_all_conversations');
add_action('wp_ajax_pmv_get_safety_stats', 'pmv_ajax_get_safety_stats');
add_action('wp_ajax_pmv_test_keywords', 'pmv_ajax_test_keywords');

/**
 * Create database tables for conversations if they don't exist
 */
function pmv_create_conversation_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Conversations table
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NULL,
        character_id varchar(255) NOT NULL,
        character_name varchar(255) NOT NULL,
        title varchar(500) NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY character_id (character_id),
        KEY created_at (created_at),
        KEY updated_at (updated_at)
    ) $charset_collate;";
    
    // Messages table
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    $messages_sql = "CREATE TABLE IF NOT EXISTS $messages_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        conversation_id int(11) NOT NULL,
        role enum('user','assistant') NOT NULL,
        content longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id),
        KEY created_at (created_at),
        FULLTEXT(content)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($conversations_sql);
    dbDelta($messages_sql);
    
    // Add foreign key constraint if not exists
    $wpdb->query("
        ALTER TABLE $messages_table 
        ADD CONSTRAINT fk_conversation_messages 
        FOREIGN KEY (conversation_id) REFERENCES $conversations_table(id) 
        ON DELETE CASCADE
    ");
}

// Hook to create tables on activation
register_activation_hook(__FILE__, 'pmv_create_conversation_tables');

// Create tables on plugin init if they don't exist
add_action('init', function() {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'pmv_conversations';
    $messages_table = $wpdb->prefix . 'pmv_conversation_messages';
    
    // Check if tables exist
    $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table;
    $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
    
    if (!$conversations_exists || !$messages_exists) {
        pmv_create_conversation_tables();
    }
});

?>
