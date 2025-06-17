<?php
/**
 * PNG Metadata Viewer - FIXED AJAX Handlers 
 * Consolidated conversation system with proper table management
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
// UNIFIED CONVERSATION SYSTEM - FIXED VERSION
// ==============================================================================

/**
 * FIXED: Unified Conversation Handler Class
 */
class PMV_Unified_Conversation_Handler {
    
    private $table_name;
    private $db_version = '1.1';
    
    public function __construct() {
        global $wpdb;
        // FIXED: Use consistent table name
        $this->table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        
        // Database setup
        add_action('admin_init', array($this, 'check_database_version'));
        
        // FIXED: Unified AJAX handlers with consistent naming
        add_action('wp_ajax_pmv_save_conversation', array($this, 'ajax_save_conversation'));
        add_action('wp_ajax_pmv_get_conversations', array($this, 'ajax_get_conversations'));
        add_action('wp_ajax_pmv_get_conversation', array($this, 'ajax_get_conversation'));
        add_action('wp_ajax_pmv_delete_conversation', array($this, 'ajax_delete_conversation'));
        add_action('wp_ajax_pmv_auto_save_conversation', array($this, 'ajax_auto_save_conversation'));
        
        // Guest versions (optional)
        add_action('wp_ajax_nopriv_pmv_save_conversation', array($this, 'ajax_save_conversation_guest'));
        add_action('wp_ajax_nopriv_pmv_get_conversations', array($this, 'ajax_get_conversations_guest'));
    }
    
    /**
     * Initialize and add user data to AJAX object
     */
    public function init() {
        // Add user data to AJAX localization
        add_filter('pmv_ajax_localize_data', array($this, 'add_user_data_to_ajax'));
    }
    
    /**
     * Add user data to AJAX object
     */
    public function add_user_data_to_ajax($ajax_data) {
        if (is_user_logged_in()) {
            $ajax_data['user_id'] = get_current_user_id();
            $ajax_data['user_display_name'] = wp_get_current_user()->display_name;
            $ajax_data['is_logged_in'] = true;
        } else {
            $ajax_data['is_logged_in'] = false;
        }
        
        $ajax_data['login_url'] = wp_login_url(get_permalink());
        $ajax_data['register_url'] = wp_registration_url();
        
        return $ajax_data;
    }
    
    /**
     * FIXED: Create the conversation table with proper structure
     */
    public function create_conversation_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // FIXED: Consistent table structure
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            character_id varchar(255) NOT NULL,
            title varchar(500) NOT NULL,
            messages longtext NOT NULL,
            message_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY character_id (character_id),
            KEY updated_at (updated_at),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store database version
        update_option('pmv_conversation_db_version', $this->db_version);
        
        error_log('PMV: Unified conversation table created successfully');
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_database_version() {
        $installed_version = get_option('pmv_conversation_db_version', '0.0');
        
        if ($installed_version !== $this->db_version) {
            $this->create_conversation_table();
        }
    }
    
    /**
     * Validate user authentication and permissions
     */
    private function validate_user_request($conversation_id = null) {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            return new WP_Error('invalid_nonce', 'Security check failed');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error('user_not_logged_in', 'Please login to save conversations');
        }
        
        $user_id = get_current_user_id();
        
        // If conversation ID is provided, verify ownership
        if ($conversation_id) {
            global $wpdb;
            $owner_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$this->table_name} WHERE id = %d",
                $conversation_id
            ));
            
            if ($owner_id && $owner_id != $user_id) {
                return new WP_Error('access_denied', 'You do not have permission to access this conversation');
            }
        }
        
        return $user_id;
    }
    
    /**
     * FIXED: Enhanced JSON decoding that handles WordPress escaping
     */
    private function decode_conversation_json($json_string) {
        error_log("=== PMV JSON DECODE DEBUG ===");
        error_log("Original length: " . strlen($json_string));
        error_log("Original preview: " . substr($json_string, 0, 200));
        
        // Remove WordPress escaping in multiple passes
        $clean_json = $json_string;
        
        // Method 1: wp_unslash (WordPress recommended)
        if (function_exists('wp_unslash')) {
            $clean_json = wp_unslash($clean_json);
        }
        
        // Method 2: Manual stripslashes in case wp_unslash isn't enough
        if (get_magic_quotes_gpc() || strpos($clean_json, '\\"') !== false) {
            $clean_json = stripslashes($clean_json);
        }
        
        // Method 3: Remove double escaping
        $clean_json = str_replace('\\"', '"', $clean_json);
        $clean_json = str_replace("\\'", "'", $clean_json);
        $clean_json = str_replace('\\\\', '\\', $clean_json);
        
        error_log("Cleaned length: " . strlen($clean_json));
        error_log("Cleaned preview: " . substr($clean_json, 0, 200));
        
        // Try to decode
        $decoded = json_decode($clean_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            return false;
        }
        
        error_log("JSON Decode Success!");
        return $decoded;
    }
    
    /**
     * Sanitize and validate conversation data
     */
    private function sanitize_conversation_data($data) {
        if (empty($data)) {
            return new WP_Error('invalid_data', 'No conversation data provided');
        }
        
        // Decode JSON if it's a string
        if (is_string($data)) {
            $data = $this->decode_conversation_json($data);
            if ($data === false) {
                return new WP_Error('invalid_json', 'Invalid JSON data');
            }
        }
        
        // Validate required fields
        if (empty($data['character_id'])) {
            return new WP_Error('missing_character', 'Character ID is required');
        }
        
        if (empty($data['title'])) {
            return new WP_Error('missing_title', 'Conversation title is required');
        }
        
        if (empty($data['messages']) || !is_array($data['messages'])) {
            return new WP_Error('missing_messages', 'Valid messages array is required');
        }
        
        // Validate messages structure
        foreach ($data['messages'] as $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                return new WP_Error('invalid_message', 'Each message must have role and content');
            }
            
            if (!in_array($message['role'], ['user', 'assistant'])) {
                return new WP_Error('invalid_role', 'Message role must be user or assistant');
            }
        }
        
        // Sanitize data
        $sanitized = array(
            'character_id' => sanitize_text_field($data['character_id']),
            'title' => sanitize_text_field(substr($data['title'], 0, 500)),
            'messages' => $this->sanitize_messages($data['messages']),
            'message_count' => count($data['messages'])
        );
        
        if (isset($data['id']) && is_numeric($data['id'])) {
            $sanitized['id'] = intval($data['id']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize messages array
     */
    private function sanitize_messages($messages) {
        $sanitized = array();
        
        foreach ($messages as $message) {
            $sanitized[] = array(
                'role' => sanitize_text_field($message['role']),
                'content' => wp_kses_post($message['content']),
                'timestamp' => isset($message['timestamp']) ? 
                    sanitize_text_field($message['timestamp']) : 
                    current_time('mysql')
            );
        }
        
        return $sanitized;
    }
    
    /**
     * FIXED: Main save conversation AJAX handler
     */
    public function ajax_save_conversation() {
        error_log("=== PMV SAVE CONVERSATION START ===");
        
        try {
            $user_id = $this->validate_user_request();
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            // FIXED: Check for conversation parameter (matches frontend)
            $conversation_raw = $_POST['conversation'] ?? '';
            if (empty($conversation_raw)) {
                error_log("PMV Save: No conversation data in POST");
                wp_send_json_error('No conversation data provided');
                return;
            }
            
            error_log("PMV Save: Raw conversation data length: " . strlen($conversation_raw));
            
            $conversation_data = $this->sanitize_conversation_data($conversation_raw);
            if (is_wp_error($conversation_data)) {
                error_log("PMV Save: Sanitization failed: " . $conversation_data->get_error_message());
                wp_send_json_error(array(
                    'message' => $conversation_data->get_error_message(),
                    'code' => $conversation_data->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            // Check if this is an update or create
            $conversation_id = isset($conversation_data['id']) ? $conversation_data['id'] : null;
            $is_update = false;
            
            if ($conversation_id) {
                // Verify ownership for updates
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, user_id FROM {$this->table_name} WHERE id = %d",
                    $conversation_id
                ));
                
                if ($existing && $existing->user_id == $user_id) {
                    $is_update = true;
                } else {
                    $conversation_id = null; // Force create new if ownership validation fails
                }
            }
            
            $data = array(
                'user_id' => $user_id,
                'character_id' => $conversation_data['character_id'],
                'title' => $conversation_data['title'],
                'messages' => wp_json_encode($conversation_data['messages']),
                'message_count' => $conversation_data['message_count'],
                'updated_at' => current_time('mysql')
            );
            
            if ($is_update) {
                // Update existing conversation
                $result = $wpdb->update(
                    $this->table_name,
                    $data,
                    array('id' => $conversation_id),
                    array('%d', '%s', '%s', '%s', '%d', '%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    error_log("PMV Save: Update failed: " . $wpdb->last_error);
                    wp_send_json_error('Failed to update conversation');
                    return;
                }
                
                $action = 'updated';
                
            } else {
                // Create new conversation
                $data['created_at'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $this->table_name,
                    $data,
                    array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
                );
                
                if ($result === false) {
                    error_log("PMV Save: Insert failed: " . $wpdb->last_error);
                    wp_send_json_error('Failed to create conversation');
                    return;
                }
                
                $conversation_id = $wpdb->insert_id;
                $action = 'created';
            }
            
            // Clean up old conversations (keep only latest 20 per character)
            $this->cleanup_old_conversations($user_id, $conversation_data['character_id']);
            
            error_log("PMV Save: Success - ID: $conversation_id, Action: $action");
            
            wp_send_json_success(array(
                'id' => $conversation_id,
                'action' => $action,
                'message' => 'Conversation saved successfully',
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            error_log('PMV Save error: ' . $e->getMessage());
            wp_send_json_error('Save failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Auto-save conversation AJAX handler
     */
    public function ajax_auto_save_conversation() {
        // For auto-save, use the same logic as manual save
        return $this->ajax_save_conversation();
    }
    
    /**
     * AJAX handler for getting user conversations
     */
    public function ajax_get_conversations() {
        try {
            $user_id = $this->validate_user_request();
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            $character_id = sanitize_text_field($_POST['character_id'] ?? '');
            if (empty($character_id)) {
                wp_send_json_error('Character ID is required');
                return;
            }
            
            global $wpdb;
            
            $conversations = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, message_count, created_at, updated_at 
                 FROM {$this->table_name} 
                 WHERE user_id = %d AND character_id = %s 
                 ORDER BY updated_at DESC 
                 LIMIT 50",
                $user_id,
                $character_id
            ));
            
            if ($wpdb->last_error) {
                error_log('PMV Database error: ' . $wpdb->last_error);
                wp_send_json_error('Database error occurred');
                return;
            }
            
            wp_send_json_success($conversations);
            
        } catch (Exception $e) {
            error_log('PMV Get conversations error: ' . $e->getMessage());
            wp_send_json_error('Failed to load conversations: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for getting a specific conversation
     */
    public function ajax_get_conversation() {
        try {
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            if (empty($conversation_id)) {
                wp_send_json_error('Conversation ID is required');
                return;
            }
            
            $user_id = $this->validate_user_request($conversation_id);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT id, title, messages, created_at, updated_at 
                 FROM {$this->table_name} 
                 WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ));
            
            if (!$conversation) {
                wp_send_json_error('Conversation not found');
                return;
            }
            
            // Decode messages
            $conversation->messages = json_decode($conversation->messages, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('PMV JSON decode error for conversation ' . $conversation_id);
                wp_send_json_error('Conversation data is corrupted');
                return;
            }
            
            wp_send_json_success($conversation);
            
        } catch (Exception $e) {
            error_log('PMV Get conversation error: ' . $e->getMessage());
            wp_send_json_error('Failed to load conversation: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for deleting conversations
     */
    public function ajax_delete_conversation() {
        try {
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            if (empty($conversation_id)) {
                wp_send_json_error('Conversation ID is required');
                return;
            }
            
            $user_id = $this->validate_user_request($conversation_id);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            $result = $wpdb->delete(
                $this->table_name,
                array(
                    'id' => $conversation_id,
                    'user_id' => $user_id
                ),
                array('%d', '%d')
            );
            
            if ($result === false) {
                wp_send_json_error('Failed to delete conversation');
                return;
            }
            
            if ($result === 0) {
                wp_send_json_error('Conversation not found or access denied');
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'Conversation deleted successfully'
            ));
            
        } catch (Exception $e) {
            error_log('PMV Delete conversation error: ' . $e->getMessage());
            wp_send_json_error('Delete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Guest conversation handlers (fallback to localStorage)
     */
    public function ajax_save_conversation_guest() {
        wp_send_json_error('Please login to save conversations to your account');
    }
    
    public function ajax_get_conversations_guest() {
        wp_send_json_success(array()); // Empty array for guests
    }
    
    /**
     * Clean up old conversations to prevent database bloat
     */
    private function cleanup_old_conversations($user_id, $character_id, $keep_count = 20) {
        global $wpdb;
        
        // Get conversations older than the keep count
        $old_conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE user_id = %d AND character_id = %s 
             ORDER BY updated_at DESC 
             LIMIT 100 OFFSET %d",
            $user_id,
            $character_id,
            $keep_count
        ));
        
        if (!empty($old_conversations)) {
            $ids_to_delete = array_map(function($conv) { return $conv->id; }, $old_conversations);
            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '%d'));
            
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
                ...$ids_to_delete
            ));
            
            error_log("PMV: Cleaned up " . count($ids_to_delete) . " old conversations for user $user_id");
        }
    }
}

// FIXED: Initialize the unified conversation handler
$pmv_unified_conversation_handler = new PMV_Unified_Conversation_Handler();

// ==============================================================================
// EXISTING GALLERY AJAX HANDLERS (PRESERVED)
// ==============================================================================

/**
 * Enhanced get character cards AJAX handler
 */
add_action('wp_ajax_pmv_get_character_cards', 'pmv_get_character_cards_enhanced_callback');
add_action('wp_ajax_nopriv_pmv_get_character_cards', 'pmv_get_character_cards_enhanced_callback');

function pmv_get_character_cards_enhanced_callback() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed. Please refresh the page and try again.');
        }
        
        // Get parameters with enhanced validation
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, min(50, intval($_POST['per_page'] ?? 12)));
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $creator = sanitize_text_field($_POST['creator'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'newest');
        $folder = sanitize_text_field($_POST['folder'] ?? '');
        
        // Validate sort option
        $valid_sorts = array('newest', 'oldest', 'name_asc', 'name_desc');
        if (!in_array($sort, $valid_sorts)) {
            $sort = 'newest';
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir']) || !is_writable($upload_dir['basedir'])) {
            throw new Exception('Uploads directory not properly configured.');
        }
        
        $png_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
        $png_dir_url = trailingslashit($upload_dir['baseurl']) . 'png-cards/';
        
        // Handle folder parameter
        if (!empty($folder)) {
            $png_dir .= trailingslashit(sanitize_file_name($folder));
            $png_dir_url .= trailingslashit(sanitize_file_name($folder));
        }
        
        // Create directory if missing
        if (!is_dir($png_dir)) {
            if (!wp_mkdir_p($png_dir)) {
                throw new Exception('Directory not found and could not be created: ' . $png_dir);
            }
        }
        
        if (!is_readable($png_dir)) {
            throw new Exception('Directory not readable: ' . $png_dir);
        }
        
        // Get all files first (for total counting)
        $all_files = glob($png_dir . '*.png');
        
        if (empty($all_files)) {
            wp_send_json_success([
                'cards' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $per_page,
                    'total_items' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ]);
            return;
        }
        
        // Process all files for filtering and sorting
        $all_characters = pmv_process_all_files_enhanced($all_files, $png_dir, $png_dir_url);
        
        // Apply filters
        $filtered_characters = pmv_apply_filters_enhanced($all_characters, $search, $category, $creator);
        
        // Apply sorting
        $sorted_characters = pmv_apply_sorting_enhanced($filtered_characters, $sort);
        
        $total_filtered = count($sorted_characters);
        $total_pages = ceil($total_filtered / $per_page);
        
        // Ensure page is valid
        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
        }
        
        // Get characters for current page
        $offset = ($page - 1) * $per_page;
        $page_characters = array_slice($sorted_characters, $offset, $per_page);
        
        // Prepare response data
        $character_cards = array();
        foreach ($page_characters as $character) {
            $character_cards[] = [
                'file_path' => $character['file_path'],
                'file_url' => $character['file_url'],
                'file_name' => $character['file_name'],
                'name' => $character['name'],
                'creator' => $character['creator'],
                'description' => pmv_truncate_text($character['description'], 150),
                'tags' => $character['tags'],
                'metadata' => $character['metadata'],
                'created_at' => $character['created_at']
            ];
        }
        
        $response_data = [
            'cards' => $character_cards,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_filtered,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
        
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        error_log("AJAX Gallery Error: " . $e->getMessage());
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }
}

// Include helper functions for character processing
function pmv_process_all_files_enhanced($files, $png_dir, $png_dir_url) {
    $characters = array();
    
    foreach ($files as $file_path) {
        $file_name = basename($file_path);
        $file_url = $png_dir_url . $file_name;
        
        if (!is_readable($file_path)) {
            continue;
        }
        
        // Extract character data
        $character_data = pmv_extract_character_from_png_enhanced($file_path);
        
        if (!$character_data) {
            continue;
        }
        
        // Handle both normalized and direct character data
        $char_info = isset($character_data['data']) ? $character_data['data'] : $character_data;
        
        // Extract and normalize data
        $name = $char_info['name'] ?? 'Unknown Character';
        $creator = $char_info['creator'] ?? 'Unknown';
        $description = $char_info['description'] ?? '';
        
        // Extract tags
        $tags = array();
        if (isset($char_info['tags'])) {
            if (is_array($char_info['tags'])) {
                $tags = $char_info['tags'];
            } elseif (is_string($char_info['tags'])) {
                $tags = array_filter(array_map('trim', explode(',', $char_info['tags'])));
            }
        }
        
        $characters[] = [
            'file_path' => $file_path,
            'file_url' => $file_url,
            'file_name' => $file_name,
            'name' => $name,
            'creator' => $creator,
            'description' => $description,
            'tags' => $tags,
            'metadata' => $character_data,
            'created_at' => date('Y-m-d H:i:s', filemtime($file_path)),
            'modified_time' => filemtime($file_path)
        ];
    }
    
    return $characters;
}

function pmv_apply_filters_enhanced($characters, $search, $category, $creator) {
    if (empty($search) && empty($category) && empty($creator)) {
        return $characters;
    }
    
    return array_filter($characters, function($character) use ($search, $category, $creator) {
        // Search filter
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $searchable_text = strtolower(implode(' ', [
                $character['name'],
                $character['description'],
                $character['creator'],
                implode(' ', $character['tags'])
            ]));
            
            if (strpos($searchable_text, $search_lower) === false) {
                return false;
            }
        }
        
        // Category filter (search in tags)
        if (!empty($category)) {
            $category_lower = strtolower($category);
            $tag_match = false;
            
            foreach ($character['tags'] as $tag) {
                if (strtolower(trim($tag)) === $category_lower) {
                    $tag_match = true;
                    break;
                }
            }
            
            if (!$tag_match) {
                return false;
            }
        }
        
        // Creator filter
        if (!empty($creator)) {
            $creator_lower = strtolower($creator);
            if (strtolower(trim($character['creator'])) !== $creator_lower) {
                return false;
            }
        }
        
        return true;
    });
}

function pmv_apply_sorting_enhanced($characters, $sort) {
    switch ($sort) {
        case 'oldest':
            usort($characters, function($a, $b) {
                return $a['modified_time'] - $b['modified_time'];
            });
            break;
            
        case 'name_asc':
            usort($characters, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
            break;
            
        case 'name_desc':
            usort($characters, function($a, $b) {
                return strcasecmp($b['name'], $a['name']);
            });
            break;
            
        case 'newest':
        default:
            usort($characters, function($a, $b) {
                return $b['modified_time'] - $a['modified_time'];
            });
            break;
    }
    
    return $characters;
}

function pmv_extract_character_from_png_enhanced($file_path) {
    try {
        if (!class_exists('PNG_Metadata_Extractor')) {
            error_log('PNG_Metadata_Extractor class not found');
            return false;
        }
        
        return PNG_Metadata_Extractor::extract_character_data($file_path);
    } catch (Exception $e) {
        error_log("Character extraction failed for " . basename($file_path) . ": " . $e->getMessage());
        return false;
    }
}

function pmv_truncate_text($text, $length) {
    if (strlen($text) <= $length) return $text;
    $text = substr($text, 0, $length);
    return (preg_match('/\s/', $text) ? preg_replace('/\s+?(\S+)?$/', '', $text) : $text) . '...';
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

/**
 * FIXED: Process chat request with CORRECTED API option names
 */
function pmv_process_chat_request($character_data, $user_message, $conversation_history, $bot_id) {
    $character = isset($character_data['data']) ? $character_data['data'] : $character_data;
    
    // CORRECTED: Get API settings with correct option names
    $api_key = get_option('openai_api_key', '');
    $api_model = get_option('openai_model', 'gpt-3.5-turbo');
    $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1');
    $temperature = floatval(get_option('openai_temperature', 0.7));
    $max_tokens = intval(get_option('openai_max_tokens', 1000));
    
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
    
    // Make API request
    $response = wp_remote_post($api_base_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress-PNG-Metadata-Viewer/1.0'
        ],
        'body' => json_encode($request_payload),
        'timeout' => 300
    ]);
    
    if (is_wp_error($response)) {
        throw new Exception('API request failed: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($response_code !== 200) {
        $error_message = 'HTTP ' . $response_code;
        if ($data && isset($data['error']['message'])) {
            $error_message .= ': ' . $data['error']['message'];
        }
        throw new Exception($error_message);
    }
    
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response format');
    }
    
    return [
        'choices' => $data['choices'],
        'character' => $character,
        'usage' => $data['usage'] ?? []
    ];
}

?>
