<?php
/**
 * PNG Metadata Viewer - AJAX Handlers with CORRECTED API Option Names
 * Uses the PNG_Metadata_Extractor from metadata-reader.php (no duplicate class)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure the metadata reader is loaded
if (!class_exists('PNG_Metadata_Extractor')) {
    $metadata_reader_path = plugin_dir_path(__FILE__) . 'metadata-reader.php';
    if (file_exists($metadata_reader_path)) {
        require_once $metadata_reader_path;
    } else {
        error_log('PNG Metadata Viewer: metadata-reader.php not found at: ' . $metadata_reader_path);
    }
}

// ==============================================================================
// AJAX HANDLERS WITH CORRECTED API OPTION NAMES
// ==============================================================================

/**
 * Get character cards AJAX handler with pagination support
 */
add_action('wp_ajax_pmv_get_character_cards', 'pmv_get_character_cards_callback');
add_action('wp_ajax_nopriv_pmv_get_character_cards', 'pmv_get_character_cards_callback');

function pmv_get_character_cards_callback() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed. Possible CSRF attack.');
        }
        
        // Get pagination parameters
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, intval($_POST['per_page'] ?? 12));
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir']) || !is_writable($upload_dir['basedir'])) {
            throw new Exception('Uploads directory not configured properly');
        }
        
        $png_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
        $png_dir_url = trailingslashit($upload_dir['baseurl']) . 'png-cards/';
        
        // Create directory if missing
        if (!is_dir($png_dir)) {
            if (!wp_mkdir_p($png_dir)) {
                throw new Exception('Failed to create directory: ' . $png_dir);
            }
        }
        
        if (!is_readable($png_dir)) {
            throw new Exception('Directory not readable: ' . $png_dir);
        }
        
        // Get all files first (for counting)
        $all_files = glob($png_dir . '*.png');
        
        if (empty($all_files)) {
            wp_send_json_success([
                'cards' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $per_page,
                    'total_items' => 0,
                    'total_pages' => 0
                ]
            ]);
            return;
        }
        
        // If we have search or category filters, we need to process files differently
        $filtered_files = $all_files;
        
        if (!empty($search) || !empty($category)) {
            $filtered_files = pmv_filter_files_by_criteria($all_files, $png_dir, $search, $category);
        }
        
        $total_files = count($filtered_files);
        $total_pages = ceil($total_files / $per_page);
        
        // Ensure page is valid
        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
        }
        
        // Calculate offset and get files for current page
        $offset = ($page - 1) * $per_page;
        $files_for_page = array_slice($filtered_files, $offset, $per_page);
        
        error_log("Processing page $page: " . count($files_for_page) . " files (total: $total_files)");
        
        // Process only the files for current page
        $character_cards = [];
        foreach ($files_for_page as $file_path) {
            $file_name = basename($file_path);
            $file_url = $png_dir_url . $file_name;
            
            if (!is_readable($file_path)) {
                error_log("File not readable: " . $file_path);
                continue;
            }
            
            // Extract character data
            $character_data = pmv_extract_character_from_png($file_path);
            
            if (!$character_data) {
                error_log("Skipped file (invalid metadata): " . $file_name);
                continue;
            }
            
            // Handle both normalized and direct character data
            $char_info = isset($character_data['data']) ? $character_data['data'] : $character_data;
            
            // Validate required fields
            if (empty($char_info['name'])) {
                $char_info['name'] = 'Unknown Character';
                error_log("Missing name in: " . $file_name);
            }
            
            $character_cards[] = [
                'file_path' => $file_path,
                'file_url' => $file_url,
                'file_name' => $file_name,
                'name' => $char_info['name'],
                'creator' => $char_info['creator'] ?? 'Unknown',
                'description' => pmv_truncate_text($char_info['description'] ?? '', 150),
                'tags' => $char_info['tags'] ?? [],
                'metadata' => $character_data,
                'created_at' => date('Y-m-d H:i:s', filemtime($file_path))
            ];
        }
        
        // Sort by creation date (newest first) - only for current page
        usort($character_cards, function($a, $b) {
            return filemtime($b['file_path']) - filemtime($a['file_path']);
        });
        
        $response_data = [
            'cards' => $character_cards,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_files,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'debug' => [
                'files_processed' => count($character_cards),
                'files_found' => count($files_for_page),
                'total_files' => $total_files,
                'search' => $search,
                'category' => $category
            ]
        ];
        
        error_log('Successfully processed ' . count($character_cards) . ' character cards for page ' . $page);
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        error_log("CRITICAL ERROR: " . $e->getMessage());
        wp_send_json_error([
            'message' => $e->getMessage(),
            'directory' => $png_dir ?? 'undefined',
            'directory_exists' => isset($png_dir) ? (is_dir($png_dir) ? 'Yes' : 'No') : 'undefined',
            'file_count' => count($files_for_page ?? []),
            'php_version' => phpversion(),
            'server_os' => PHP_OS,
            'extractor_available' => class_exists('PNG_Metadata_Extractor') ? 'Yes' : 'No'
        ]);
    }
}

/**
 * Filter files by search and category criteria
 * Only processes files that might match to avoid processing all 500+ files
 */
function pmv_filter_files_by_criteria($files, $png_dir, $search = '', $category = '') {
    if (empty($search) && empty($category)) {
        return $files;
    }
    
    $filtered_files = [];
    $search_lower = strtolower($search);
    $category_lower = strtolower($category);
    
    // Use cached metadata if available to speed up filtering
    $cache_key = 'pmv_metadata_cache_' . md5($png_dir);
    $metadata_cache = get_transient($cache_key);
    
    if ($metadata_cache === false) {
        $metadata_cache = [];
    }
    
    foreach ($files as $file_path) {
        $file_name = basename($file_path);
        $file_modified = filemtime($file_path);
        
        // Check cache first
        if (isset($metadata_cache[$file_name]) && $metadata_cache[$file_name]['modified'] === $file_modified) {
            $character_data = $metadata_cache[$file_name]['data'];
        } else {
            // Extract metadata (this is the expensive operation)
            $character_data = pmv_extract_character_from_png($file_path);
            
            if ($character_data) {
                // Cache the result
                $metadata_cache[$file_name] = [
                    'modified' => $file_modified,
                    'data' => $character_data
                ];
            } else {
                continue; // Skip files without valid metadata
            }
        }
        
        if (!$character_data) {
            continue;
        }
        
        $char_info = isset($character_data['data']) ? $character_data['data'] : $character_data;
        $matches = true;
        
        // Search filter
        if (!empty($search)) {
            $searchable_text = strtolower(implode(' ', [
                $char_info['name'] ?? '',
                $char_info['description'] ?? '',
                $char_info['creator'] ?? '',
                is_array($char_info['tags'] ?? []) ? implode(' ', $char_info['tags']) : ''
            ]));
            
            if (strpos($searchable_text, $search_lower) === false) {
                $matches = false;
            }
        }
        
        // Category filter
        if ($matches && !empty($category)) {
            $char_tags = $char_info['tags'] ?? [];
            if (is_string($char_tags)) {
                $char_tags = array_map('trim', explode(',', $char_tags));
            }
            
            $tag_match = false;
            foreach ($char_tags as $tag) {
                if (strtolower(trim($tag)) === $category_lower) {
                    $tag_match = true;
                    break;
                }
            }
            
            // Also check creator
            if (!$tag_match && isset($char_info['creator'])) {
                if (strtolower(trim($char_info['creator'])) === $category_lower) {
                    $tag_match = true;
                }
            }
            
            if (!$tag_match) {
                $matches = false;
            }
        }
        
        if ($matches) {
            $filtered_files[] = $file_path;
        }
    }
    
    // Update cache (limit to 1000 entries to prevent memory issues)
    if (count($metadata_cache) > 1000) {
        // Keep only the most recently modified files
        uasort($metadata_cache, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        $metadata_cache = array_slice($metadata_cache, 0, 1000, true);
    }
    
    // Cache for 30 minutes
    set_transient($cache_key, $metadata_cache, 30 * MINUTE_IN_SECONDS);
    
    return $filtered_files;
}

/**
 * Get paginated character list (lighter version for quick loading)
 */
add_action('wp_ajax_pmv_get_character_list', 'pmv_get_character_list_callback');
add_action('wp_ajax_nopriv_pmv_get_character_list', 'pmv_get_character_list_callback');

function pmv_get_character_list_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, intval($_POST['per_page'] ?? 20));
        
        $upload_dir = wp_upload_dir();
        $png_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
        
        $all_files = glob($png_dir . '*.png');
        $total_files = count($all_files);
        $total_pages = ceil($total_files / $per_page);
        
        $offset = ($page - 1) * $per_page;
        $files_for_page = array_slice($all_files, $offset, $per_page);
        
        $character_list = [];
        foreach ($files_for_page as $file_path) {
            $file_name = basename($file_path);
            
            // Try to get just the name quickly without full metadata extraction
            $quick_name = pmv_extract_character_name_quick($file_path);
            
            $character_list[] = [
                'file_name' => $file_name,
                'name' => $quick_name ?: pathinfo($file_name, PATHINFO_FILENAME),
                'file_url' => trailingslashit($upload_dir['baseurl']) . 'png-cards/' . $file_name,
                'modified' => filemtime($file_path)
            ];
        }
        
        wp_send_json_success([
            'list' => $character_list,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_files,
                'total_pages' => $total_pages
            ]
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Quick character name extraction (lighter than full metadata)
 */
function pmv_extract_character_name_quick($file_path) {
    try {
        // Try to extract just the name without full metadata processing
        if (class_exists('PNG_Metadata_Extractor')) {
            $result = PNG_Metadata_Extractor::extract_character_data($file_path);
            if ($result && isset($result['data']['name'])) {
                return $result['data']['name'];
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clear pagination and metadata caches
 */
add_action('wp_ajax_pmv_clear_cache', 'pmv_clear_cache_callback');

function pmv_clear_cache_callback() {
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    global $wpdb;
    
    // Clear all PMV transients
    $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pmv_%'");
    $deleted += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pmv_%'");
    
    wp_send_json_success(['message' => "Cleared $deleted cache entries"]);
}

/**
 * API test AJAX handler with CORRECTED option names
 */
add_action('wp_ajax_pmv_test_api_connection', 'pmv_test_api_connection_callback');

function pmv_test_api_connection_callback() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_test_api_connection')) {
            throw new Exception('Security verification failed');
        }
        
        // CORRECTED: Use the correct option names
        $api_key = sanitize_text_field($_POST['api_key'] ?? get_option('openai_api_key', ''));
        $api_base_url = sanitize_text_field($_POST['api_base_url'] ?? get_option('openai_api_base_url', 'https://api.openai.com/v1'));
        $model = sanitize_text_field($_POST['model'] ?? get_option('openai_model', 'gpt-3.5-turbo'));
        
        if (empty($api_key)) {
            throw new Exception('API key is required');
        }
        
        // Clean up the base URL
        $api_base_url = rtrim($api_base_url, '/');
        if (!str_contains($api_base_url, '/chat/completions')) {
            if (!str_contains($api_base_url, '/v1')) {
                $api_base_url .= '/v1';
            }
            $api_base_url .= '/chat/completions';
        }
        
        // Test message
        $test_messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant. Respond with exactly: "API test successful"'],
            ['role' => 'user', 'content' => 'Test connection']
        ];
        
        // Make test request
        $response = wp_remote_post($api_base_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress-PNG-Metadata-Viewer/1.0'
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => $test_messages,
                'max_tokens' => 50,
                'temperature' => 0.1
            ]),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Connection failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            $error_message = 'HTTP ' . $response_code;
            if ($data && isset($data['error']['message'])) {
                $error_message .= ': ' . $data['error']['message'];
            } else {
                $error_message .= ': ' . wp_remote_retrieve_response_message($response);
            }
            throw new Exception($error_message);
        }
        
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        wp_send_json_success([
            'message' => 'API test successful!',
            'response' => $data,
            'model_used' => $data['model'] ?? $model,
            'usage' => $data['usage'] ?? []
        ]);
        
    } catch (Exception $e) {
        error_log("API test error: " . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}

/**
 * Direct API test handler (for admin settings page) with CORRECTED option names
 */
add_action('wp_ajax_pmv_test_api_directly', 'pmv_test_api_directly_callback');

function pmv_test_api_directly_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        if (!current_user_can('administrator')) {
            throw new Exception('Insufficient permissions');
        }
        
        // CORRECTED: Use saved settings with correct option names
        $api_key = get_option('openai_api_key', '');
        $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1');
        $model = get_option('openai_model', 'gpt-3.5-turbo');
        
        if (empty($api_key)) {
            throw new Exception('API key not configured in settings');
        }
        
        // Clean up URL
        $api_base_url = rtrim($api_base_url, '/');
        if (!str_contains($api_base_url, '/chat/completions')) {
            if (!str_contains($api_base_url, '/v1')) {
                $api_base_url .= '/v1';
            }
            $api_base_url .= '/chat/completions';
        }
        
        // Test with simple message
        $response = wp_remote_post($api_base_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "Hello from API test"']
                ],
                'max_tokens' => 20,
                'temperature' => 0
            ]),
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($code !== 200) {
            $error = 'HTTP ' . $code;
            if ($data && isset($data['error']['message'])) {
                $error .= ': ' . $data['error']['message'];
            }
            throw new Exception($error);
        }
        
        wp_send_json_success([
            'response' => $data,
            'endpoint' => $api_base_url
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// ==============================================================================
// CONVERSATION AJAX HANDLERS WITH CORRECTED OPTION NAMES
// ==============================================================================

/**
 * Chat conversation AJAX handlers
 */
add_action('wp_ajax_pmv_get_conversations', 'pmv_get_conversations_callback');
add_action('wp_ajax_nopriv_pmv_get_conversations', 'pmv_get_conversations_callback');

function pmv_get_conversations_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $character_id = sanitize_text_field($_POST['character_id'] ?? 'default');
        $conversations = pmv_get_stored_conversations($character_id);
        
        wp_send_json_success($conversations);
        
    } catch (Exception $e) {
        error_log("Get conversations error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Get single conversation AJAX handler
 */
add_action('wp_ajax_pmv_get_conversation', 'pmv_get_conversation_callback');
add_action('wp_ajax_nopriv_pmv_get_conversation', 'pmv_get_conversation_callback');

function pmv_get_conversation_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        if (empty($conversation_id)) {
            throw new Exception('Conversation ID is required');
        }
        
        $conversation = pmv_get_single_conversation($conversation_id);
        if (!$conversation) {
            throw new Exception('Conversation not found');
        }
        
        wp_send_json_success($conversation);
        
    } catch (Exception $e) {
        error_log("Get conversation error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Delete conversation AJAX handler
 */
add_action('wp_ajax_pmv_delete_conversation', 'pmv_delete_conversation_callback');
add_action('wp_ajax_nopriv_pmv_delete_conversation', 'pmv_delete_conversation_callback');

function pmv_delete_conversation_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        if (empty($conversation_id)) {
            throw new Exception('Conversation ID is required');
        }
        
        $result = pmv_delete_single_conversation($conversation_id);
        if (!$result) {
            throw new Exception('Failed to delete conversation');
        }
        
        wp_send_json_success(['message' => 'Conversation deleted successfully']);
        
    } catch (Exception $e) {
        error_log("Delete conversation error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Save conversation AJAX handler
 */
add_action('wp_ajax_pmv_save_conversation', 'pmv_save_conversation_callback');
add_action('wp_ajax_nopriv_pmv_save_conversation', 'pmv_save_conversation_callback');

function pmv_save_conversation_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $conversation_data = json_decode(stripslashes($_POST['conversation_data'] ?? $_POST['conversation'] ?? ''), true);
        if (!$conversation_data) {
            throw new Exception('Invalid conversation data');
        }
        
        $conversation_id = pmv_save_conversation_data($conversation_data);
        
        wp_send_json_success(['id' => $conversation_id]);
        
    } catch (Exception $e) {
        error_log("Save conversation error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Auto-save conversation AJAX handler
 */
add_action('wp_ajax_pmv_auto_save_conversation', 'pmv_auto_save_conversation_callback');
add_action('wp_ajax_nopriv_pmv_auto_save_conversation', 'pmv_auto_save_conversation_callback');

function pmv_auto_save_conversation_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $conversation_data = json_decode(stripslashes($_POST['conversation_data'] ?? ''), true);
        if (!$conversation_data) {
            throw new Exception('Invalid conversation data');
        }
        
        $conversation_id = pmv_auto_save_conversation_data($conversation_data);
        
        wp_send_json_success(['id' => $conversation_id]);
        
    } catch (Exception $e) {
        error_log("Auto-save conversation error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Character chat AJAX handler (for AI responses) - FIXED WITH CORRECT OPTION NAMES
 */
add_action('wp_ajax_start_character_chat', 'start_character_chat_callback');
add_action('wp_ajax_nopriv_start_character_chat', 'start_character_chat_callback');

function start_character_chat_callback() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        $character_data = json_decode(stripslashes($_POST['character_data'] ?? ''), true);
        $user_message = sanitize_text_field($_POST['user_message'] ?? '');
        $conversation_history = json_decode(stripslashes($_POST['conversation_history'] ?? '[]'), true);
        $bot_id = sanitize_text_field($_POST['bot_id'] ?? 'default_bot');
        
        if (!$character_data || !$user_message) {
            throw new Exception('Missing required chat parameters');
        }
        
        $response = pmv_process_chat_request($character_data, $user_message, $conversation_history, $bot_id);
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        error_log("Character chat error: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// ==============================================================================
// HELPER FUNCTIONS WITH CORRECTED OPTION NAMES
// ==============================================================================

function pmv_extract_character_from_png($file_path) {
    try {
        if (!class_exists('PNG_Metadata_Extractor')) {
            error_log('PNG_Metadata_Extractor class not found');
            return false;
        }
        
        return PNG_Metadata_Extractor::extract_character_data($file_path);
    } catch (Exception $e) {
        error_log("Character extraction failed: " . $e->getMessage());
        return false;
    }
}

function pmv_truncate_text($text, $length) {
    if (strlen($text) <= $length) return $text;
    $text = substr($text, 0, $length);
    return (preg_match('/\s/', $text) ? preg_replace('/\s+?(\S+)?$/', '', $text) : $text) . '...';
}

function pmv_get_stored_conversations($character_id) {
    $conversations = get_option('pmv_conversations_' . $character_id, []);
    
    usort($conversations, function($a, $b) {
        $date_a = strtotime($a['updated_at'] ?? $a['created_at'] ?? '1970-01-01');
        $date_b = strtotime($b['updated_at'] ?? $b['created_at'] ?? '1970-01-01');
        return $date_b - $date_a;
    });
    
    return $conversations;
}

function pmv_get_single_conversation($conversation_id) {
    // First try to find in all character conversations
    $all_options = wp_load_alloptions();
    foreach ($all_options as $option_name => $option_value) {
        if (strpos($option_name, 'pmv_conversations_') === 0) {
            $conversations = maybe_unserialize($option_value);
            if (is_array($conversations)) {
                foreach ($conversations as $conv) {
                    if (isset($conv['id']) && $conv['id'] == $conversation_id) {
                        return $conv;
                    }
                }
            }
        }
    }
    
    return false;
}

function pmv_delete_single_conversation($conversation_id) {
    // Find and delete from all character conversations
    $all_options = wp_load_alloptions();
    foreach ($all_options as $option_name => $option_value) {
        if (strpos($option_name, 'pmv_conversations_') === 0) {
            $conversations = maybe_unserialize($option_value);
            if (is_array($conversations)) {
                $updated_conversations = array_filter($conversations, function($conv) use ($conversation_id) {
                    return !(isset($conv['id']) && $conv['id'] == $conversation_id);
                });
                
                if (count($updated_conversations) !== count($conversations)) {
                    update_option($option_name, $updated_conversations);
                    return true;
                }
            }
        }
    }
    
    return false;
}

function pmv_save_conversation_data($conversation_data) {
    $character_id = $conversation_data['character_id'] ?? 'default';
    $conversation_id = $conversation_data['id'] ?? uniqid('conv_');
    
    $conversations = get_option('pmv_conversations_' . $character_id, []);
    
    $conversation_index = -1;
    foreach ($conversations as $index => $conv) {
        if ($conv['id'] === $conversation_id) {
            $conversation_index = $index;
            break;
        }
    }
    
    $conversation_entry = [
        'id' => $conversation_id,
        'character_id' => $character_id,
        'title' => $conversation_data['title'] ?? 'Untitled Conversation',
        'messages' => $conversation_data['messages'] ?? [],
        'created_at' => $conversation_data['created_at'] ?? current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];
    
    if ($conversation_index >= 0) {
        $conversations[$conversation_index] = $conversation_entry;
    } else {
        $conversations[] = $conversation_entry;
    }
    
    if (count($conversations) > 50) {
        usort($conversations, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        $conversations = array_slice($conversations, 0, 50);
    }
    
    update_option('pmv_conversations_' . $character_id, $conversations);
    
    return $conversation_id;
}

function pmv_auto_save_conversation_data($conversation_data) {
    return pmv_save_conversation_data($conversation_data);
}

/**
 * FIXED: Process chat request with CORRECTED API option names
 */
function pmv_process_chat_request($character_data, $user_message, $conversation_history, $bot_id) {
    $character = isset($character_data['data']) ? $character_data['data'] : $character_data;
    
    // CORRECTED: Get API settings with correct option names (matching admin-settings.php)
    $api_key = get_option('openai_api_key', '');
    $api_model = get_option('openai_model', 'gpt-3.5-turbo');
    $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1');
    $temperature = floatval(get_option('openai_temperature', 0.7));
    $max_tokens = intval(get_option('openai_max_tokens', 1000));
    $presence_penalty = floatval(get_option('openai_presence_penalty', 0.6));
    $frequency_penalty = floatval(get_option('openai_frequency_penalty', 0.3));
    
    // Debug logging to check API settings
    error_log("PMV Chat Request - API Settings:");
    error_log("API Key: " . (empty($api_key) ? 'NOT SET' : 'SET (' . strlen($api_key) . ' chars)'));
    error_log("API Model: " . $api_model);
    error_log("API Base URL: " . $api_base_url);
    error_log("Temperature: " . $temperature);
    error_log("Max Tokens: " . $max_tokens);
    
    if (empty($api_key)) {
        throw new Exception('OpenAI API key not configured');
    }
    
    // Clean up API base URL
    $api_base_url = rtrim($api_base_url, '/');
    if (!str_contains($api_base_url, '/chat/completions')) {
        if (!str_contains($api_base_url, '/v1')) {
            $api_base_url .= '/v1';
        }
        $api_base_url .= '/chat/completions';
    }
    
    // Build system prompt
    $system_prompt = $character['system_prompt'] ?? '';
    if (empty($system_prompt)) {
        $system_prompt = "You are " . ($character['name'] ?? 'an AI assistant') . ".";
        if (!empty($character['personality'])) {
            $system_prompt .= " " . $character['personality'];
        }
        if (!empty($character['scenario'])) {
            $system_prompt .= " Scenario: " . $character['scenario'];
        }
    }
    
    // Build messages array
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];
    
    // Add conversation history
    foreach ($conversation_history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Add current user message
    $messages[] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    // Build request payload
    $request_payload = [
        'model' => $api_model,
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature
    ];
    
    // Add optional parameters if they're not at default values
    if ($presence_penalty !== 0.0) {
        $request_payload['presence_penalty'] = $presence_penalty;
    }
    if ($frequency_penalty !== 0.0) {
        $request_payload['frequency_penalty'] = $frequency_penalty;
    }
    
    // Make API request
    error_log("PMV Chat: Making API request to " . $api_base_url);
    
    $response = wp_remote_post($api_base_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress-PNG-Metadata-Viewer/1.0'
        ],
        'body' => json_encode($request_payload),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log("PMV Chat: API request failed - " . $response->get_error_message());
        throw new Exception('API request failed: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    error_log("PMV Chat: API response code " . $response_code);
    
    if ($response_code !== 200) {
        $error_message = 'HTTP ' . $response_code;
        if ($data && isset($data['error']['message'])) {
            $error_message .= ': ' . $data['error']['message'];
        } else {
            $error_message .= ': ' . wp_remote_retrieve_response_message($response);
        }
        error_log("PMV Chat: API error - " . $error_message);
        throw new Exception($error_message);
    }
    
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        error_log("PMV Chat: Invalid API response format");
        error_log("PMV Chat: Response body: " . $body);
        throw new Exception('Invalid API response format');
    }
    
    error_log("PMV Chat: Successful API response received");
    
    return [
        'choices' => $data['choices'],
        'character' => $character,
        'usage' => $data['usage'] ?? []
    ];
}

function pmv_debug_extraction_methods($file_path) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    try {
        error_log("=== DEBUG EXTRACTION METHODS for " . basename($file_path) . " ===");
        
        if (!class_exists('PNG_Metadata_Extractor')) {
            error_log("PNG_Metadata_Extractor class not available");
            return false;
        }
        
        $result = PNG_Metadata_Extractor::extract_character_data($file_path);
        
        error_log("Final result: " . ($result ? "SUCCESS" : "FAILED"));
        if ($result) {
            error_log("Character name: " . ($result['data']['name'] ?? 'Unknown'));
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Debug extraction error: " . $e->getMessage());
        return false;
    }
}

function pmv_check_extraction_system() {
    $status = [
        'extractor_class_exists' => class_exists('PNG_Metadata_Extractor'),
        'reader_class_exists' => class_exists('PNG_Metadata_Reader'),
        'metadata_reader_file' => file_exists(plugin_dir_path(__FILE__) . 'metadata-reader.php'),
    ];
    
    return $status;
}

add_action('admin_notices', function() {
    if (!current_user_can('administrator')) return;
    
    $status = pmv_check_extraction_system();
    
    if (!$status['extractor_class_exists'] || !$status['reader_class_exists']) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>PNG Metadata Viewer:</strong> Character extraction classes not loaded. ';
        if (!$status['metadata_reader_file']) {
            echo 'metadata-reader.php file is missing.';
        } else {
            echo 'Classes failed to load from metadata-reader.php.';
        }
        echo '</p></div>';
    }
});
?>
