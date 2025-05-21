<?php
/**
 * PNG Metadata Viewer - AJAX Handlers with Fixed Token Handling
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX hooks
add_action('wp_ajax_pmv_test_api_directly', 'pmv_test_api_directly_callback');
add_action('wp_ajax_nopriv_pmv_test_api_directly', 'pmv_test_api_directly_callback');
add_action('wp_ajax_start_character_chat', 'pmv_handle_chat_request');
add_action('wp_ajax_nopriv_start_character_chat', 'pmv_handle_chat_request');
add_action('wp_ajax_pmv_save_conversation', 'pmv_save_conversation_callback');
add_action('wp_ajax_pmv_get_conversations', 'pmv_get_conversations_callback');
add_action('wp_ajax_pmv_get_conversation', 'pmv_get_conversation_callback');
add_action('wp_ajax_pmv_delete_conversation', 'pmv_delete_conversation_callback');

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
 * Enhanced chat handler with proper token handling
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
            error_log('PMV Chat - Invalid max_tokens setting, using default: 1000');
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
            ]
        ]);

    } catch (Exception $e) {
        error_log('PMV Chat - Exception: ' . $e->getMessage());
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
 * Build conversation messages with character context
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
    
    // 3. Add conversation history
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

/**
 * Save conversation handler
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
        $data = [
            'user_id' => $user_id,
            'character_id' => sanitize_text_field($conversation['character_id']),
            'title' => sanitize_text_field(substr($conversation['title'], 0, 255)),
            'messages' => json_encode($conversation['messages']),
            'updated_at' => current_time('mysql')
        ];

        if (!empty($conversation['id'])) {
            // Update existing
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $conversation['id'], 'user_id' => $user_id],
                ['%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            // Create new
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                $table_name,
                $data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
        }

        if ($result === false) {
            throw new Exception('Database operation failed');
        }

        wp_send_json_success([
            'id' => $result ? $wpdb->insert_id : $conversation['id']
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get conversations list
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
                "SELECT id, title, created_at, updated_at 
                FROM $table_name 
                WHERE user_id = %d AND character_id = %s 
                ORDER BY updated_at DESC",
                $user_id,
                $character_id
            ),
            ARRAY_A
        );

        wp_send_json_success($conversations);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get single conversation
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
        wp_send_json_success($conversation);

    } catch (Exception $e) {
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
        $result = $wpdb->delete(
            $table_name,
            ['id' => $conversation_id, 'user_id' => $user_id],
            ['%d', '%d']
        );

        if ($result === false) {
            throw new Exception('Delete operation failed');
        }

        wp_send_json_success();

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
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
