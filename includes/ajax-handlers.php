<?php
/**
 * PNG Metadata Viewer - UNIFIED AJAX Handlers 
 * Enhanced conversation system matching conversations-database.php schema
 * Version: 4.16-UNIFIED
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include metadata reader if needed
if (!class_exists('PNG_Metadata_Extractor')) {
    $metadata_reader_path = plugin_dir_path(__FILE__) . 'metadata-reader.php';
    if (file_exists($metadata_reader_path)) {
        require_once $metadata_reader_path;
    }
}

/**
 * UNIFIED: Conversation Handler Class using the same schema as conversations-database.php
 */
class PMV_Unified_Conversation_Handler {
    
    private $conversations_table;
    private $messages_table;
    private $db_version = '1.5';
    
    public function __construct() {
        global $wpdb;
        $this->conversations_table = $wpdb->prefix . 'pmv_conversations';
        $this->messages_table = $wpdb->prefix . 'pmv_conversation_messages';
        
        // Initialize hooks
        add_action('init', array($this, 'init'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 15);
        
        // AJAX handlers - using proper WordPress hooks
        add_action('wp_ajax_pmv_save_conversation', array($this, 'ajax_save_conversation'));
        add_action('wp_ajax_pmv_get_conversations', array($this, 'ajax_get_conversations'));
        add_action('wp_ajax_pmv_get_conversation', array($this, 'ajax_get_conversation'));
        add_action('wp_ajax_pmv_delete_conversation', array($this, 'ajax_delete_conversation'));
        add_action('wp_ajax_pmv_update_conversation_title', array($this, 'ajax_update_conversation_title'));
        add_action('wp_ajax_pmv_debug_conversation_system', array($this, 'ajax_debug_system'));
        
        // Guest handlers
        add_action('wp_ajax_nopriv_pmv_save_conversation', array($this, 'ajax_save_conversation_guest'));
        add_action('wp_ajax_nopriv_pmv_get_conversations', array($this, 'ajax_get_conversations_guest'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'check_database'));
        
        // Debug logging
        add_action('init', array($this, 'log_handler_status'));
    }
    
    /**
     * Log handler registration status for debugging
     */
    public function log_handler_status() {
        // Only log in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $handlers = array(
                'pmv_save_conversation',
                'pmv_get_conversations',
                'pmv_get_conversation',
                'pmv_delete_conversation'
            );
            
            foreach ($handlers as $handler) {
                $registered = has_action("wp_ajax_$handler");
                if (!$registered) {
                    error_log("PMV: Handler $handler is NOT REGISTERED");
                }
            }
        }
    }
    
    /**
     * Initialize the handler
     */
    public function init() {
        // Ensure tables exist
        if (!$this->tables_exist()) {
            pmv_log_info('Conversation tables missing, creating...');
            $this->create_tables();
        }
        
        // Validate table structure
        $this->validate_table_structure();
    }
    
    /**
     * Enhanced script enqueuing
     */
    public function enqueue_scripts() {
        if (is_admin()) return;
        
        // Get current user data
        $current_user = wp_get_current_user();
        
        // Build localization data
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmv_ajax_nonce'),
            'user_id' => $current_user->ID,
            'user_display_name' => $current_user->display_name ?: $current_user->user_login,
            'is_logged_in' => is_user_logged_in(),
            'login_url' => wp_login_url(get_permalink()),
            'register_url' => wp_registration_url(),
            'chat_button_text' => get_option('png_metadata_chat_button_text', 'Chat'),
            'debug' => array(
                'conversations_table_exists' => $this->table_exists($this->conversations_table),
                'messages_table_exists' => $this->table_exists($this->messages_table),
                'ajax_handlers' => array(
                    'save' => has_action('wp_ajax_pmv_save_conversation'),
                    'get_conversations' => has_action('wp_ajax_pmv_get_conversations'),
                    'get_conversation' => has_action('wp_ajax_pmv_get_conversation'),
                    'delete' => has_action('wp_ajax_pmv_delete_conversation')
                ),
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => defined('PMV_VERSION') ? PMV_VERSION : '4.16-UNIFIED'
            )
        );
        
        // Only localize scripts that exist: png-metadata-viewer-chat, pmv-conversation-manager
        $scripts = array('png-metadata-viewer-chat', 'pmv-conversation-manager');
        foreach ($scripts as $script) {
            if (wp_script_is($script, 'enqueued')) {
                wp_localize_script($script, 'pmv_ajax_object', $ajax_data);
            }
        }
    }
    
    /**
     * Check if specific table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $wpdb->get_var($query) === $table_name;
    }
    
    /**
     * Check if both tables exist
     */
    private function tables_exist() {
        return $this->table_exists($this->conversations_table) && $this->table_exists($this->messages_table);
    }
    
    /**
     * Validate table structure
     */
    private function validate_table_structure() {
        if (!$this->tables_exist()) return false;
        
        global $wpdb;
        
        // Check conversations table
        $conv_columns = $wpdb->get_results("DESCRIBE {$this->conversations_table}");
        $conv_column_names = array_column($conv_columns, 'Field');
        $required_conv_columns = array('id', 'user_id', 'character_id', 'character_name', 'title', 'created_at', 'updated_at');
        $missing_conv_columns = array_diff($required_conv_columns, $conv_column_names);
        
        // Check messages table
        $msg_columns = $wpdb->get_results("DESCRIBE {$this->messages_table}");
        $msg_column_names = array_column($msg_columns, 'Field');
        $required_msg_columns = array('id', 'conversation_id', 'role', 'content', 'created_at');
        $missing_msg_columns = array_diff($required_msg_columns, $msg_column_names);
        
        if (!empty($missing_conv_columns) || !empty($missing_msg_columns)) {
            error_log('PMV: Table structure issues - Conv missing: ' . implode(', ', $missing_conv_columns) . 
                     ' | Msg missing: ' . implode(', ', $missing_msg_columns));
            return false;
        }
        
        return true;
    }
    
    /**
     * Create database tables using the same schema as conversations-database.php
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Conversations table - EXACT same schema as conversations-database.php
        $conversations_sql = "CREATE TABLE IF NOT EXISTS {$this->conversations_table} (
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
        
        // Messages table - EXACT same schema as conversations-database.php
        $messages_sql = "CREATE TABLE IF NOT EXISTS {$this->messages_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            conversation_id int(11) NOT NULL,
            role enum('user','assistant') NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $conv_result = dbDelta($conversations_sql);
        $msg_result = dbDelta($messages_sql);
        
        // Verify table creation
        if ($this->tables_exist()) {
            pmv_log_info('Unified conversation tables created successfully');
            update_option('pmv_conversation_db_version', $this->db_version);
            
            // Test table functionality
            $this->test_table_functionality();
        } else {
            pmv_log_error('Failed to create unified conversation tables');
        }
    }
    
    /**
     * Test table functionality
     */
    private function test_table_functionality() {
        global $wpdb;
        
        // Test conversations table
        $test_conv_data = array(
            'user_id' => 1,
            'character_id' => 'test_functionality',
            'character_name' => 'Test Character',
            'title' => 'Test Conversation'
        );
        
        $insert_result = $wpdb->insert($this->conversations_table, $test_conv_data);
        
        if ($insert_result) {
            $test_conv_id = $wpdb->insert_id;
            
            // Test messages table
            $test_msg_data = array(
                'conversation_id' => $test_conv_id,
                'role' => 'user',
                'content' => 'Test message'
            );
            
            $msg_insert_result = $wpdb->insert($this->messages_table, $test_msg_data);
            
            if ($msg_insert_result) {
                $test_msg_id = $wpdb->insert_id;
                
                // Clean up test data
                $wpdb->delete($this->messages_table, array('id' => $test_msg_id), array('%d'));
                $wpdb->delete($this->conversations_table, array('id' => $test_conv_id), array('%d'));
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PMV: Unified table functionality test passed');
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PMV: Messages table functionality test failed: ' . $wpdb->last_error);
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PMV: Conversations table functionality test failed: ' . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Check database status
     */
    public function check_database() {
        $installed_version = get_option('pmv_conversation_db_version', '0.0');
        
        if (version_compare($installed_version, $this->db_version, '<') || !$this->tables_exist()) {
            $this->create_tables();
        }
        
        $this->maybe_upgrade_conversations_table();
    }
    
    /**
     * Ensure the conversations table has all required columns (auto-migrate)
     */
    private function maybe_upgrade_conversations_table() {
        global $wpdb;
        $table = $this->conversations_table;
        // Add character_name column if missing
        $column = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE 'character_name'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `character_name` varchar(255) DEFAULT NULL AFTER `character_id`");
        }
        // Add any future columns here as needed
    }
    
    /**
     * Enhanced request validation with proper sanitization
     */
    private function validate_request($conversation_id = null) {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            return new WP_Error('invalid_nonce', 'Security verification failed. Please refresh the page and try again.');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'You must be logged in to save conversations. Please login and try again.');
        }
        
        $user_id = get_current_user_id();
        
        // Validate user ID
        if (!$user_id || $user_id <= 0) {
            return new WP_Error('invalid_user', 'Invalid user ID.');
        }
        
        // Verify conversation ownership if ID provided
        if ($conversation_id) {
            // Sanitize conversation ID
            $conversation_id = intval($conversation_id);
            if ($conversation_id <= 0) {
                return new WP_Error('invalid_conversation_id', 'Invalid conversation ID.');
            }
            
            global $wpdb;
            $owner_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$this->conversations_table} WHERE id = %d",
                $conversation_id
            ));
            
            if ($owner_id && $owner_id != $user_id) {
                return new WP_Error('access_denied', 'You do not have permission to access this conversation.');
            }
        }
        
        return $user_id;
    }
    
    /**
     * Enhanced JSON handling
     */
    private function parse_conversation_json($json_string) {
        if (is_array($json_string)) {
            return $json_string;
        }
        
        if (!is_string($json_string)) {
            return new WP_Error('invalid_data', 'Conversation data must be a JSON string or array');
        }
        
        // Clean up WordPress slashes
        $clean_json = wp_unslash($json_string);
        
        $decoded = json_decode($clean_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = 'JSON decode error: ' . json_last_error_msg();
            error_log('PMV: ' . $error_msg);
            error_log('PMV: Raw JSON (first 500 chars): ' . substr($clean_json, 0, 500));
            return new WP_Error('json_error', $error_msg);
        }
        
        return $decoded;
    }
    
    /**
     * Enhanced conversation data validation
     */
    private function validate_conversation_data($data) {
        if (empty($data)) {
            return new WP_Error('empty_data', 'No conversation data provided');
        }
        
        // Parse JSON if needed
        if (is_string($data)) {
            $data = $this->parse_conversation_json($data);
            if (is_wp_error($data)) {
                return $data;
            }
        }
        
        // Validate required fields
        $required_fields = array('character_id', 'title', 'messages');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Required field missing: $field");
            }
        }
        
        // Validate messages array
        if (!is_array($data['messages'])) {
            return new WP_Error('invalid_messages', 'Messages must be an array');
        }
        
        foreach ($data['messages'] as $index => $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                return new WP_Error('invalid_message', "Message at index $index is missing role or content");
            }
            
            if (!in_array($message['role'], array('user', 'assistant'))) {
                return new WP_Error('invalid_role', "Invalid message role at index $index: {$message['role']}");
            }
        }
        
        // Sanitize and return validated data
        return array(
            'id' => isset($data['id']) ? intval($data['id']) : null,
            'character_id' => sanitize_text_field($data['character_id']),
            'character_name' => sanitize_text_field($data['character_name'] ?? 'Unknown Character'),
            'title' => sanitize_text_field(substr($data['title'], 0, 500)),
            'messages' => $this->sanitize_messages($data['messages']),
            'message_count' => count($data['messages'])
        );
    }
    
    /**
     * Sanitize messages array with enhanced validation
     */
    private function sanitize_messages($messages) {
        if (!is_array($messages)) {
            return array();
        }
        
        $sanitized = array();
        $max_messages = 1000; // Prevent excessive messages
        $max_content_length = 10000; // Prevent extremely long messages
        
        foreach ($messages as $index => $message) {
            // Limit number of messages
            if ($index >= $max_messages) {
                break;
            }
            
            // Validate message structure
            if (!is_array($message) || !isset($message['role']) || !isset($message['content'])) {
                continue;
            }
            
            // Validate role
            $role = sanitize_text_field($message['role']);
            if (!in_array($role, array('user', 'assistant', 'system'))) {
                continue;
            }
            
            // Sanitize and validate content
            $content = wp_kses_post($message['content']);
            if (empty($content) || strlen($content) > $max_content_length) {
                continue;
            }
            
            // Validate timestamp if provided
            $timestamp = current_time('c');
            if (isset($message['timestamp'])) {
                $timestamp_raw = sanitize_text_field($message['timestamp']);
                if (strtotime($timestamp_raw) !== false) {
                    $timestamp = $timestamp_raw;
                }
            }
            
            $sanitized[] = array(
                'role' => $role,
                'content' => $content,
                'timestamp' => $timestamp
            );
        }
        
        return $sanitized;
    }
    
    /**
     * UNIFIED: Enhanced save conversation handler using two-table approach
     */
    public function ajax_save_conversation() {
        try {
            error_log('=== PMV UNIFIED SAVE CONVERSATION START ===');
            
            // Validate request
            $user_id = $this->validate_request();
            if (is_wp_error($user_id)) {
                error_log('PMV: Request validation failed: ' . $user_id->get_error_message());
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            // Get and validate conversation data
            $conversation_raw = $_POST['conversation'] ?? '';
            if (empty($conversation_raw)) {
                wp_send_json_error(array(
                    'message' => 'No conversation data provided',
                    'code' => 'empty_data'
                ));
                return;
            }
            
            $conversation_data = $this->validate_conversation_data($conversation_raw);
            if (is_wp_error($conversation_data)) {
                error_log('PMV: Data validation failed: ' . $conversation_data->get_error_message());
                wp_send_json_error(array(
                    'message' => $conversation_data->get_error_message(),
                    'code' => $conversation_data->get_error_code()
                ));
                return;
            }
            
            // Perform the save operation using two-table approach
            $result = $this->perform_unified_save_operation($user_id, $conversation_data);
            
            if (is_wp_error($result)) {
                error_log('PMV: Save operation failed: ' . $result->get_error_message());
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code()
                ));
                return;
            }
            
            error_log('PMV: Unified save successful - ID: ' . $result['id'] . ', Action: ' . $result['action']);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_save_conversation: ' . $e->getMessage());
            error_log('PMV: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array(
                'message' => 'Save operation failed due to an internal error',
                'code' => 'internal_error'
            ));
        }
    }
    
    /**
     * Perform save operation using unified two-table approach (same as conversations-database.php)
     */
    private function perform_unified_save_operation($user_id, $conversation_data) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Check if this is an update or create
            $conversation_id = $conversation_data['id'];
            $is_update = false;
            
            if ($conversation_id) {
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, user_id FROM {$this->conversations_table} WHERE id = %d",
                    $conversation_id
                ));
                
                if ($existing && $existing->user_id == $user_id) {
                    $is_update = true;
                } else {
                    $conversation_id = null; // Force create new
                }
            }
            
            // Prepare conversation data
            $conv_data = array(
                'user_id' => $user_id,
                'character_id' => $conversation_data['character_id'],
                'character_name' => $conversation_data['character_name'],
                'title' => $conversation_data['title'],
                'updated_at' => current_time('mysql')
            );
            
            if ($is_update) {
                // Update existing conversation
                $result = $wpdb->update(
                    $this->conversations_table,
                    $conv_data,
                    array('id' => $conversation_id, 'user_id' => $user_id),
                    array('%d', '%s', '%s', '%s', '%s'),
                    array('%d', '%d')
                );
                
                if ($result === false) {
                    throw new Exception('Failed to update conversation: ' . $wpdb->last_error);
                }
                
                // Delete old messages
                $wpdb->delete($this->messages_table, array('conversation_id' => $conversation_id), array('%d'));
                
                $action = 'updated';
                
            } else {
                // Create new conversation
                $conv_data['created_at'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $this->conversations_table,
                    $conv_data,
                    array('%d', '%s', '%s', '%s', '%s', '%s')
                );
                
                if ($result === false) {
                    throw new Exception('Failed to create conversation: ' . $wpdb->last_error);
                }
                
                $conversation_id = $wpdb->insert_id;
                $action = 'created';
            }
            
            // Save messages to separate table
            if (!empty($conversation_data['messages'])) {
                foreach ($conversation_data['messages'] as $message) {
                    $msg_result = $wpdb->insert(
                        $this->messages_table,
                        array(
                            'conversation_id' => $conversation_id,
                            'role' => $message['role'],
                            'content' => $message['content'],
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s')
                    );
                    
                    if ($msg_result === false) {
                        throw new Exception('Failed to save message: ' . $wpdb->last_error);
                    }
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Clean up old conversations (keep only latest 50 per character per user)
            $this->cleanup_old_conversations($user_id, $conversation_data['character_id']);
            
            return array(
                'id' => $conversation_id,
                'action' => $action,
                'message' => 'Conversation saved successfully',
                'timestamp' => current_time('mysql')
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('save_failed', $e->getMessage());
        }
    }
    
    /**
     * Clean up old conversations
     */
    private function cleanup_old_conversations($user_id, $character_id, $keep_count = 50) {
        global $wpdb;
        
        $old_conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->conversations_table} 
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
            
            // Delete messages first
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->messages_table} WHERE conversation_id IN ($placeholders)",
                ...$ids_to_delete
            ));
            
            // Delete conversations
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->conversations_table} WHERE id IN ($placeholders)",
                ...$ids_to_delete
            ));
            
            error_log('PMV: Cleaned up ' . count($ids_to_delete) . ' old conversations');
        }
    }
    
    /**
     * Get conversations AJAX handler using unified approach
     */
    public function ajax_get_conversations() {
        try {
            $user_id = $this->validate_request();
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            $character_id = sanitize_text_field($_POST['character_id'] ?? '');
            if (empty($character_id)) {
                wp_send_json_error(array(
                    'message' => 'Character ID is required',
                    'code' => 'missing_character_id'
                ));
                return;
            }
            
            global $wpdb;
            
            // Use same query structure as conversations-database.php
            $conversations = $wpdb->get_results($wpdb->prepare(
                "SELECT c.id, c.title, c.created_at, c.updated_at,
                        (SELECT COUNT(*) FROM {$this->messages_table} m WHERE m.conversation_id = c.id) as message_count
                 FROM {$this->conversations_table} c
                 WHERE c.user_id = %d AND c.character_id = %s 
                 ORDER BY c.updated_at DESC 
                 LIMIT 50",
                $user_id,
                $character_id
            ));
            
            if ($wpdb->last_error) {
                error_log('PMV: Database error in get_conversations: ' . $wpdb->last_error);
                wp_send_json_error(array(
                    'message' => 'Database error occurred',
                    'code' => 'database_error'
                ));
                return;
            }
            
            wp_send_json_success($conversations ?: array());
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_get_conversations: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to load conversations',
                'code' => 'internal_error'
            ));
        }
    }
    
    /**
     * Get single conversation AJAX handler using unified approach
     */
    public function ajax_get_conversation() {
        try {
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            if (empty($conversation_id)) {
                wp_send_json_error(array(
                    'message' => 'Conversation ID is required',
                    'code' => 'missing_id'
                ));
                return;
            }
            
            $user_id = $this->validate_request($conversation_id);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            // Get conversation using same approach as conversations-database.php
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT id, character_id, character_name, title, created_at, updated_at 
                 FROM {$this->conversations_table} 
                 WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ));
            
            if (!$conversation) {
                wp_send_json_error(array(
                    'message' => 'Conversation not found',
                    'code' => 'not_found'
                ));
                return;
            }
            
            // Get messages from separate table
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT role, content, created_at 
                 FROM {$this->messages_table} 
                 WHERE conversation_id = %d 
                 ORDER BY created_at ASC",
                $conversation_id
            ));
            
            if ($wpdb->last_error) {
                error_log('PMV: Database error getting messages: ' . $wpdb->last_error);
                wp_send_json_error(array(
                    'message' => 'Error loading conversation messages',
                    'code' => 'database_error'
                ));
                return;
            }
            
            // Format response same as conversations-database.php
            $conversation->messages = $messages ?: array();
            
            wp_send_json_success($conversation);
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_get_conversation: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to load conversation',
                'code' => 'internal_error'
            ));
        }
    }
    
    /**
     * Delete conversation AJAX handler using unified approach
     */
    public function ajax_delete_conversation() {
        try {
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            if (empty($conversation_id)) {
                wp_send_json_error(array(
                    'message' => 'Conversation ID is required',
                    'code' => 'missing_id'
                ));
                return;
            }
            
            $user_id = $this->validate_request($conversation_id);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            try {
                // Delete messages first (same as conversations-database.php)
                $messages_deleted = $wpdb->delete(
                    $this->messages_table,
                    array('conversation_id' => $conversation_id),
                    array('%d')
                );
                
                // Delete conversation
                $conversation_deleted = $wpdb->delete(
                    $this->conversations_table,
                    array(
                        'id' => $conversation_id,
                        'user_id' => $user_id
                    ),
                    array('%d', '%d')
                );
                
                if ($conversation_deleted !== false) {
                    $wpdb->query('COMMIT');
                    wp_send_json_success(array(
                        'message' => 'Conversation deleted successfully'
                    ));
                } else {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error(array(
                        'message' => 'Conversation not found or access denied',
                        'code' => 'not_found'
                    ));
                }
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_delete_conversation: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to delete conversation',
                'code' => 'internal_error'
            ));
        }
    }
    
    /**
     * Update conversation title AJAX handler
     */
    public function ajax_update_conversation_title() {
        try {
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            $new_title = sanitize_text_field($_POST['title'] ?? '');
            
            if (empty($conversation_id)) {
                wp_send_json_error(array(
                    'message' => 'Conversation ID is required',
                    'code' => 'missing_id'
                ));
                return;
            }
            
            if (empty($new_title)) {
                wp_send_json_error(array(
                    'message' => 'Title is required',
                    'code' => 'missing_title'
                ));
                return;
            }
            
            // Limit title length
            if (strlen($new_title) > 500) {
                wp_send_json_error(array(
                    'message' => 'Title is too long (max 500 characters)',
                    'code' => 'title_too_long'
                ));
                return;
            }
            
            $user_id = $this->validate_request($conversation_id);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code()
                ));
                return;
            }
            
            global $wpdb;
            
            // Update the conversation title
            $result = $wpdb->update(
                $this->conversations_table,
                array(
                    'title' => $new_title,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'id' => $conversation_id,
                    'user_id' => $user_id
                ),
                array('%s', '%s'),
                array('%d', '%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Title updated successfully',
                    'title' => $new_title
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Failed to update title',
                    'code' => 'update_failed'
                ));
            }
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_update_conversation_title: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to update title',
                'code' => 'internal_error'
            ));
        }
    }
    
    /**
     * Debug system AJAX handler with unified diagnostics
     */
    public function ajax_debug_system() {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
                wp_send_json_error('Invalid nonce');
                return;
            }
            
            global $wpdb;
            
            // Get table columns
            $conv_columns = array();
            $msg_columns = array();
            
            if ($this->table_exists($this->conversations_table)) {
                $conv_cols = $wpdb->get_results("DESCRIBE {$this->conversations_table}");
                foreach ($conv_cols as $col) {
                    $conv_columns[] = $col->Field;
                }
            }
            
            if ($this->table_exists($this->messages_table)) {
                $msg_cols = $wpdb->get_results("DESCRIBE {$this->messages_table}");
                foreach ($msg_cols as $col) {
                    $msg_columns[] = $col->Field;
                }
            }
            
            $debug_info = array(
                'conversations_table_exists' => $this->table_exists($this->conversations_table),
                'messages_table_exists' => $this->table_exists($this->messages_table),
                'conversations_table_name' => $this->conversations_table,
                'messages_table_name' => $this->messages_table,
                'conversations_columns' => $conv_columns,
                'messages_columns' => $msg_columns,
                'test_functionality' => false,
                'user_logged_in' => is_user_logged_in(),
                'user_id' => get_current_user_id(),
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => defined('PMV_VERSION') ? PMV_VERSION : '4.16-UNIFIED',
                'php_version' => PHP_VERSION,
                'mysql_version' => $wpdb->db_version(),
                'ajax_handlers' => array(
                    'save' => has_action('wp_ajax_pmv_save_conversation'),
                    'get_conversations' => has_action('wp_ajax_pmv_get_conversations'),
                    'get_conversation' => has_action('wp_ajax_pmv_get_conversation'),
                    'delete' => has_action('wp_ajax_pmv_delete_conversation')
                ),
                'required_columns_check' => array(
                    'conversations' => array(
                        'id' => in_array('id', $conv_columns),
                        'user_id' => in_array('user_id', $conv_columns),
                        'character_id' => in_array('character_id', $conv_columns),
                        'character_name' => in_array('character_name', $conv_columns),
                        'title' => in_array('title', $conv_columns),
                        'created_at' => in_array('created_at', $conv_columns),
                        'updated_at' => in_array('updated_at', $conv_columns)
                    ),
                    'messages' => array(
                        'id' => in_array('id', $msg_columns),
                        'conversation_id' => in_array('conversation_id', $msg_columns),
                        'role' => in_array('role', $msg_columns),
                        'content' => in_array('content', $msg_columns),
                        'created_at' => in_array('created_at', $msg_columns)
                    )
                )
            );
            
            // Test basic functionality if tables exist and have required columns
            if ($debug_info['conversations_table_exists'] && 
                $debug_info['required_columns_check']['conversations']['character_name']) {
                
                $test_data = array(
                    'user_id' => 1,
                    'character_id' => 'debug_test_' . time(),
                    'character_name' => 'Debug Test Character',
                    'title' => 'Debug Test',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                );
                
                $insert_result = $wpdb->insert($this->conversations_table, $test_data);
                if ($insert_result) {
                    $test_conv_id = $wpdb->insert_id;
                    
                    if ($debug_info['messages_table_exists']) {
                        $test_msg_data = array(
                            'conversation_id' => $test_conv_id,
                            'role' => 'user',
                            'content' => 'Test message',
                            'created_at' => current_time('mysql')
                        );
                        
                        $msg_insert_result = $wpdb->insert($this->messages_table, $test_msg_data);
                        if ($msg_insert_result) {
                            $test_msg_id = $wpdb->insert_id;
                            
                            // Clean up test data
                            $wpdb->delete($this->messages_table, array('id' => $test_msg_id), array('%d'));
                            $debug_info['test_functionality'] = true;
                        }
                    }
                    
                    $wpdb->delete($this->conversations_table, array('id' => $test_conv_id), array('%d'));
                }
            }
            
            wp_send_json_success($debug_info);
            
        } catch (Exception $e) {
            error_log('PMV: Exception in ajax_debug_system: ' . $e->getMessage());
            wp_send_json_error('Debug system failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Guest handlers
     */
    public function ajax_save_conversation_guest() {
        wp_send_json_error(array(
            'message' => 'Please login to save conversations to your account',
            'code' => 'login_required'
        ));
    }
    
    public function ajax_get_conversations_guest() {
        wp_send_json_success(array()); // Empty array for guests
    }
}

// Initialize the unified conversation handler only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    global $pmv_unified_conversation_handler;
    $pmv_unified_conversation_handler = new PMV_Unified_Conversation_Handler();
}

// ==============================================================================
// GALLERY AJAX HANDLERS - ENHANCED
// ==============================================================================

/**
 * Note: AJAX handler for pmv_get_character_cards is now handled by cache-handler.php
 * to provide caching functionality and better performance
 */

/**
 * Enhanced character chat AJAX handler
 */
add_action('wp_ajax_start_character_chat', 'start_character_chat_enhanced');
add_action('wp_ajax_nopriv_start_character_chat', 'start_character_chat_enhanced');

function start_character_chat_enhanced() {
    try {
        // Increase memory limit for large responses
        ini_set('memory_limit', '256M');
        
        // Validate nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            throw new Exception('Security verification failed');
        }
        
        // Validate and sanitize character data
        $character_data_raw = $_POST['character_data'] ?? '';
        if (empty($character_data_raw)) {
            throw new Exception('Character data is required');
        }
        
        $character_data = json_decode(stripslashes($character_data_raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid character data format');
        }
        
        // Validate character data structure
        if (!is_array($character_data) || empty($character_data)) {
            throw new Exception('Invalid character data structure');
        }
        
        // Validate and sanitize user message
        $user_message = sanitize_text_field($_POST['user_message'] ?? '');
        if (empty($user_message)) {
            throw new Exception('User message is required');
        }
        
        // Limit message length - increased to prevent truncation
        if (strlen($user_message) > 10000) {
            throw new Exception('User message is too long (maximum 10000 characters)');
        }
        
        // Validate and sanitize conversation history
        $conversation_history_raw = $_POST['conversation_history'] ?? '[]';
        $conversation_history = json_decode(stripslashes($conversation_history_raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $conversation_history = array();
        }
        
        // Limit conversation history length - increased limit to prevent truncation
        if (is_array($conversation_history) && count($conversation_history) > 200) {
            $conversation_history = array_slice($conversation_history, -200);
        }
        
        // Validate and sanitize bot ID
        $bot_id = sanitize_text_field($_POST['bot_id'] ?? 'default_bot');
        if (empty($bot_id) || strlen($bot_id) > 255) {
            $bot_id = 'default_bot';
        }
        
        $response = pmv_process_chat_request_enhanced($character_data, $user_message, $conversation_history, $bot_id);
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PMV: Character chat error: ' . $e->getMessage());
        }
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

/**
 * Enhanced chat request processing with better prompt handling
 */
function pmv_process_chat_request_enhanced($character_data, $user_message, $conversation_history, $bot_id) {
    $character = isset($character_data['data']) ? $character_data['data'] : $character_data;
    
    // Get API settings with correct option names
    $api_key = get_option('openai_api_key', '');
    $api_model = get_option('openai_model', 'gpt-3.5-turbo');
    $api_base_url = get_option('openai_api_base_url', 'https://api.openai.com/v1');
    $temperature = floatval(get_option('openai_temperature', 0.7));
    $max_tokens = intval(get_option('openai_max_tokens', 4000));
    $presence_penalty = floatval(get_option('openai_presence_penalty', 0.6));
    $frequency_penalty = floatval(get_option('openai_frequency_penalty', 0.3));
    
    if (empty($api_key)) {
        throw new Exception('OpenAI API key not configured. Please check the plugin settings.');
    }
    
    // Clean up API base URL
    $api_base_url = rtrim($api_base_url, '/');
    if (strpos($api_base_url, '/chat/completions') === false) {
        if (strpos($api_base_url, '/v1') === false) {
            $api_base_url .= '/v1';
        }
        $api_base_url .= '/chat/completions';
    }
    
    // Build comprehensive system prompt
    $system_prompt = '';
    
    // Start with character name
    if (!empty($character['name'])) {
        $system_prompt .= "You are " . $character['name'] . ".\n\n";
    }
    
    // Add description if available
    if (!empty($character['description'])) {
        $system_prompt .= "Character Description:\n" . $character['description'] . "\n\n";
    }
    
    // Add personality if available
    if (!empty($character['personality'])) {
        $system_prompt .= "Personality:\n" . $character['personality'] . "\n\n";
    }
    
    // Add scenario if available
    if (!empty($character['scenario'])) {
        $system_prompt .= "Scenario:\n" . $character['scenario'] . "\n\n";
    }
    
    // Add example dialogue if available
    if (!empty($character['mes_example'])) {
        $system_prompt .= "Example Dialogue:\n" . $character['mes_example'] . "\n\n";
    }
    
    // Add system prompt if provided
    if (!empty($character['system_prompt'])) {
        $system_prompt .= $character['system_prompt'] . "\n\n";
    }
    
    // Add behavioral instructions
    $system_prompt .= "Instructions:\n";
    $system_prompt .= "- Stay in character at all times\n";
    $system_prompt .= "- Respond as " . ($character['name'] ?? 'the character') . " would\n";
    $system_prompt .= "- Keep responses engaging and true to the character\n";
    $system_prompt .= "- Do not break character or mention that you are an AI\n";
    
    // If no system prompt was built, create a basic one
    if (empty(trim($system_prompt))) {
        $system_prompt = "You are " . ($character['name'] ?? 'an AI assistant') . ". Stay in character and respond naturally.";
    }
    
    // Log the system prompt for debugging
    error_log('PMV: System prompt length: ' . strlen($system_prompt));
    error_log('PMV: System prompt preview: ' . substr($system_prompt, 0, 200) . '...');
    
    // Build messages array
    $messages = array(
        array('role' => 'system', 'content' => trim($system_prompt))
    );
    
    // Add conversation history with proper validation
    if (!empty($conversation_history) && is_array($conversation_history)) {
        foreach ($conversation_history as $msg) {
            if (isset($msg['role']) && isset($msg['content']) && !empty(trim($msg['content']))) {
                $messages[] = array(
                    'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                    'content' => trim($msg['content'])
                );
            }
        }
    }
    
    // Add current user message
    if (!empty(trim($user_message))) {
        $messages[] = array(
            'role' => 'user',
            'content' => trim($user_message)
        );
    }
    
    // Log message count for debugging
    error_log('PMV: Total messages in request: ' . count($messages));
    error_log('PMV: User message: ' . $user_message);
    
    // Build request payload with all parameters
    $request_payload = array(
        'model' => $api_model,
        'messages' => $messages,
        'max_tokens' => max(4000, min(16000, $max_tokens)), // Increased limit to prevent truncation
        'temperature' => max(0, min(2, $temperature)), // Ensure valid range
        'presence_penalty' => max(-2, min(2, $presence_penalty)), // Ensure valid range
        'frequency_penalty' => max(-2, min(2, $frequency_penalty)) // Ensure valid range
    );
    
    // Log request details
    error_log('PMV: API Request - Model: ' . $api_model . ', Max Tokens: ' . $request_payload['max_tokens']);
    
    // Make API request with better error handling
    $response = wp_remote_post($api_base_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress-PNG-Metadata-Viewer/' . (defined('PMV_VERSION') ? PMV_VERSION : '1.0')
        ),
        'body' => json_encode($request_payload),
        'timeout' => 300,
        'sslverify' => true
    ));
    
    if (is_wp_error($response)) {
        error_log('PMV: API request failed: ' . $response->get_error_message());
        throw new Exception('API request failed: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Log response details
    error_log('PMV: API Response Code: ' . $response_code);
    error_log('PMV: API Response Length: ' . strlen($body));
    
    $data = json_decode($body, true);
    
    if ($response_code !== 200) {
        $error_message = 'HTTP ' . $response_code;
        if ($data && isset($data['error']['message'])) {
            $error_message .= ': ' . $data['error']['message'];
        }
        error_log('PMV: API error: ' . $error_message);
        error_log('PMV: API error response: ' . $body);
        throw new Exception($error_message);
    }
    
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        error_log('PMV: Invalid API response format');
        error_log('PMV: Response data: ' . print_r($data, true));
        throw new Exception('Invalid API response format');
    }
    
    $ai_response = $data['choices'][0]['message']['content'];
    error_log('PMV: AI Response length: ' . strlen($ai_response));
    error_log('PMV: AI Response preview: ' . substr($ai_response, 0, 100) . '...');
    error_log('PMV: AI Response end: ' . substr($ai_response, -100));
    error_log('PMV: Finish reason: ' . ($data['choices'][0]['finish_reason'] ?? 'unknown'));
    
    // Get user ID for token tracking
    $user_id = get_current_user_id();
    
    // Debug logging
    error_log('PMV: About to trigger token tracking action - User ID: ' . $user_id);
    
    // Trigger token tracking action
    do_action('pmv_after_chat_completion', $data, $user_id, $user_message);
    
    error_log('PMV: Token tracking action triggered');
    
    // Now consume credits based on ACTUAL tokens used, not estimates
    if (isset($data['usage']['total_tokens']) && $data['usage']['total_tokens'] > 0) {
        $actual_tokens = intval($data['usage']['total_tokens']);
        
        // Check if subscription system is available
        if (class_exists('PMV_Subscription_System')) {
            $subscription_system = PMV_Subscription_System::getInstance();
            
            // Consume credits based on actual API tokens used
            if ($subscription_system->consume_text_credits($user_id, $actual_tokens)) {
                error_log("PMV: Consumed {$actual_tokens} text credits for user {$user_id} based on actual API usage");
            } else {
                error_log("PMV: Failed to consume {$actual_tokens} text credits for user {$user_id}");
            }
        }
    } else {
        error_log("PMV: No token usage data available in API response");
    }
    
    $response_data = array(
        'choices' => $data['choices'],
        'character' => $character,
        'usage' => $data['usage'] ?? array()
    );
    
    error_log('PMV: Response data size: ' . strlen(json_encode($response_data)));
    error_log('PMV: AI response in final data: ' . strlen($response_data['choices'][0]['message']['content']));
    
    return $response_data;
}

/**
 * AJAX: Recreate credit products
 */
add_action('wp_ajax_pmv_recreate_subscription_products', 'pmv_ajax_recreate_subscription_products');
function pmv_ajax_recreate_subscription_products() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Force recreate products to fix any issues
        $result = $subscription_system->force_recreate_credit_products();
        
        if ($result) {
            wp_send_json_success('Credit products recreated successfully. Check the error log for details.');
        } else {
            wp_send_json_error('Failed to recreate credit products');
        }
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Check credit system status
 */
add_action('wp_ajax_pmv_check_subscription_status', 'pmv_ajax_check_subscription_status');
function pmv_ajax_check_subscription_status() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Get detailed product status
        $product_status = $subscription_system->get_credit_product_status();
        
        // Check if PMV Credits category exists
        $category = get_term_by('slug', 'pmv-credits', 'product_cat');
        $category_created = $category ? true : false;
        
        wp_send_json_success(array(
            'product_status' => $product_status,
            'category_created' => $category_created,
            'credit_rates' => array(
                'image_credits_per_dollar' => get_option('pmv_image_credits_per_dollar', 100),
                'text_credits_per_dollar' => get_option('pmv_text_credits_per_dollar', 10000)
            )
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Comprehensive order audit for credits
 */
add_action('wp_ajax_pmv_audit_all_orders', 'pmv_ajax_audit_all_orders');
function pmv_ajax_audit_all_orders() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Perform comprehensive order audit
        $result = $subscription_system->audit_all_orders_for_credits();
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to audit orders');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}



/**
 * AJAX: Test hook execution
 */
add_action('wp_ajax_pmv_test_hook_execution', 'pmv_ajax_test_hook_execution');
function pmv_ajax_test_hook_execution() {
    try {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active. Please install and activate WooCommerce plugin.');
            return;
        }
        
        // Check if subscription system is available
        if (!class_exists('PMV_Subscription_System')) {
            wp_send_json_error('Credit system not available. Please check if the subscription system file is properly loaded.');
            return;
        }
        
        // Try to get subscription system instance
        try {
            $subscription_system = PMV_Subscription_System::getInstance();
        } catch (Exception $e) {
            wp_send_json_error('Failed to initialize subscription system: ' . $e->getMessage());
            return;
        } catch (Error $e) {
            wp_send_json_error('Failed to initialize subscription system: ' . $e->getMessage());
            return;
        }
        
        if (!$subscription_system) {
            wp_send_json_error('Subscription system instance is null');
            return;
        }
        
        // Test hook execution
        try {
            $result = $subscription_system->test_hook_execution();
            
            if ($result) {
                wp_send_json_success('Hook execution test completed successfully. Check error logs for details.');
            } else {
                wp_send_json_error('Hook execution test failed - WooCommerce hooks may not be properly registered');
            }
        } catch (Exception $e) {
            wp_send_json_error('Hook execution test failed with exception: ' . $e->getMessage());
        } catch (Error $e) {
            wp_send_json_error('Hook execution test failed with error: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log('PMV: Critical error in pmv_ajax_test_hook_execution: ' . $e->getMessage());
        wp_send_json_error('Critical error: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('PMV: Fatal error in pmv_ajax_test_hook_execution: ' . $e->getMessage());
        wp_send_json_error('Fatal error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually process specific order
 */
add_action('wp_ajax_pmv_process_specific_order', 'pmv_ajax_process_specific_order');
function pmv_ajax_process_specific_order() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Manually process specific order
        $result = $subscription_system->manually_process_specific_order($order_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Check processing orders for credits
 */
add_action('wp_ajax_pmv_check_processing_orders', 'pmv_ajax_check_processing_orders');
function pmv_ajax_check_processing_orders() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Check processing orders for credits
        $result = $subscription_system->check_processing_orders_for_credits();
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to check processing orders');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Debug order credit processing
 */
add_action('wp_ajax_pmv_debug_order_credits', 'pmv_ajax_debug_order_credits');
function pmv_ajax_debug_order_credits() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Debug order credit processing
        $result = $subscription_system->debug_order_credit_processing($order_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually process credits for a specific order
 */
add_action('wp_ajax_pmv_manually_process_order_credits', 'pmv_ajax_manually_process_order_credits');
function pmv_ajax_manually_process_order_credits() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Clear any existing processed flag to force reprocessing
        $order = wc_get_order($order_id);
        if ($order && $order->get_user_id()) {
            $processed_key = "_pmv_credits_processed_{$order_id}";
            delete_user_meta($order->get_user_id(), $processed_key);
        }
        
        // Manually process the order credits
        $result = $subscription_system->check_and_process_credit_order($order_id);
        
        if ($result) {
            wp_send_json_success('Credits successfully processed for order ' . $order_id);
        } else {
            wp_send_json_error('No credits to process for order ' . $order_id);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually trigger daily reward
 */
add_action('wp_ajax_pmv_manually_trigger_daily_reward', 'pmv_ajax_manually_trigger_daily_reward');
function pmv_ajax_manually_trigger_daily_reward() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $user_id = intval($_POST['user_id'] ?? 0);
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        $result = $subscription_system->manually_trigger_daily_reward($user_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually trigger order processing
 */
add_action('wp_ajax_pmv_manually_trigger_order_processing', 'pmv_ajax_manually_trigger_order_processing');
function pmv_ajax_manually_trigger_order_processing() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        $result = $subscription_system->manually_trigger_order_processing($order_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually trigger cron job
 */
add_action('wp_ajax_pmv_manually_trigger_cron', 'pmv_ajax_manually_trigger_cron');
function pmv_ajax_manually_trigger_cron() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Manually trigger the cron job
        $result = $subscription_system->manually_trigger_cron_job();
        
        if ($result) {
            wp_send_json_success('Cron job triggered successfully');
        } else {
            wp_send_json_error('Failed to trigger cron job');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Check cron job status
 */
add_action('wp_ajax_pmv_check_cron_status', 'pmv_ajax_check_cron_status');
function pmv_ajax_check_cron_status() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    try {
        $cron_jobs = _get_cron_array();
        $pmv_crons = array();
        
        foreach ($cron_jobs as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'pmv_') === 0) {
                    foreach ($events as $sig => $event) {
                        $pmv_crons[] = array(
                            'hook' => $hook,
                            'next_run' => $timestamp,
                            'interval' => isset($event['interval']) ? $event['interval'] : 'single',
                            'time_until' => $timestamp - time()
                        );
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'cron_jobs' => $pmv_crons,
            'next_audit' => wp_next_scheduled('pmv_automatic_order_audit'),
            'time_until_next' => wp_next_scheduled('pmv_automatic_order_audit') - time()
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Error checking cron status: ' . $e->getMessage());
    }
}

/**
 * AJAX: Force process all orders immediately (for debugging and manual processing)
 */
add_action('wp_ajax_pmv_force_process_all_orders', 'pmv_ajax_force_process_all_orders');
function pmv_ajax_force_process_all_orders() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Force process orders on page load
        $result = $subscription_system->process_orders_on_page_load();
        
        if ($result === false) {
            wp_send_json_error('Order processing failed');
        } else {
            wp_send_json_success(array(
                'message' => 'Orders processed successfully',
                'result' => $result
            ));
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error processing orders: ' . $e->getMessage());
    }
}

/**
 * AJAX: Check WooCommerce hooks status
 */
add_action('wp_ajax_pmv_check_hooks_status', 'pmv_ajax_check_hooks_status');
function pmv_ajax_check_hooks_status() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Check WooCommerce hooks status
        $status = $subscription_system->check_woocommerce_hooks_status();
        
        wp_send_json_success($status);
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Force register WooCommerce hooks
 */
add_action('wp_ajax_pmv_force_register_hooks', 'pmv_ajax_force_register_hooks');
function pmv_ajax_force_register_hooks() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Force register all hooks
        $result = $subscription_system->force_register_hooks();
        
        if ($result) {
            wp_send_json_success('All WooCommerce hooks successfully registered!');
        } else {
            wp_send_json_error('Failed to register some hooks');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually trigger order status change for testing
 */
add_action('wp_ajax_pmv_trigger_order_status_change', 'pmv_ajax_trigger_order_status_change');
function pmv_ajax_trigger_order_status_change() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    $old_status = sanitize_text_field($_POST['old_status'] ?? 'pending');
    $new_status = sanitize_text_field($_POST['new_status'] ?? 'processing');
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Manually trigger order status change
        $result = $subscription_system->manually_trigger_order_status_change($order_id, $old_status, $new_status);
        
        if ($result) {
            wp_send_json_success('Order status change hooks triggered successfully for order ' . $order_id);
        } else {
            wp_send_json_error('Failed to trigger order status change hooks');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Debug and force process a specific order
 */
add_action('wp_ajax_pmv_debug_and_force_process_order', 'pmv_ajax_debug_and_force_process_order');
function pmv_ajax_debug_and_force_process_order() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        // Debug and force process the order
        $result = $subscription_system->debug_and_force_process_order($order_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually process new orders for credits
 */
add_action('wp_ajax_pmv_manually_process_new_orders', 'pmv_ajax_manually_process_new_orders');
function pmv_ajax_manually_process_new_orders() {
    // Prevent any output before JSON response
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set proper headers for JSON response
    header('Content-Type: application/json');
    
    try {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active');
            return;
        }
        
        // Check if subscription system is available
        if (!class_exists('PMV_Subscription_System')) {
            wp_send_json_error('Credit system not available');
            return;
        }
        
        // Get subscription system instance
        $subscription_system = PMV_Subscription_System::getInstance();
        if (!$subscription_system) {
            wp_send_json_error('Failed to create subscription system instance');
            return;
        }
        
        // Manually process new orders for credits
        $result = $subscription_system->manually_process_new_orders();
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to process new orders');
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('PMV: Critical error in pmv_ajax_manually_process_new_orders: ' . $e->getMessage());
        error_log('PMV: Stack trace: ' . $e->getTraceAsString());
        
        wp_send_json_error('Critical error: ' . $e->getMessage());
        return;
    } catch (Error $e) {
        // Catch PHP 7+ errors
        error_log('PMV: Fatal error in pmv_ajax_manually_process_new_orders: ' . $e->getMessage());
        error_log('PMV: Stack trace: ' . $e->getTraceAsString());
        
        wp_send_json_error('Fatal error: ' . $e->getMessage());
        return;
    }
}

/**
 * AJAX: Test hook system and force register hooks
 */
add_action('wp_ajax_pmv_test_hook_system', 'pmv_ajax_test_hook_system');
function pmv_ajax_test_hook_system() {
    // Prevent any output before JSON response
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set proper headers for JSON response
    header('Content-Type: application/json');
    
    try {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active');
            return;
        }
        
        // Check if subscription system is available
        if (!class_exists('PMV_Subscription_System')) {
            wp_send_json_error('Credit system not available');
            return;
        }
        
        // Get subscription system instance
        $subscription_system = PMV_Subscription_System::getInstance();
        if (!$subscription_system) {
            wp_send_json_error('Failed to create subscription system instance');
            return;
        }
        
        // Test hook system step by step with error handling
        $results = array();
        
        // Step 1: Check initial hook status
        try {
            $results['initial_hook_status'] = $subscription_system->check_woocommerce_hooks_status();
        } catch (Exception $e) {
            $results['initial_hook_status_error'] = $e->getMessage();
            $results['initial_hook_status'] = array('error' => 'Failed to check initial hook status');
        }
        
        // Step 2: Force register hooks
        try {
            $results['force_register_result'] = $subscription_system->force_register_hooks();
        } catch (Exception $e) {
            $results['force_register_error'] = $e->getMessage();
            $results['force_register_result'] = false;
        }
        
        // Step 3: Test hook execution
        try {
            $results['hook_execution_test'] = $subscription_system->test_hook_execution();
        } catch (Exception $e) {
            $results['hook_execution_error'] = $e->getMessage();
            $results['hook_execution_test'] = false;
        }
        
        // Step 4: Get final hook status
        try {
            $results['final_hook_status'] = $subscription_system->check_woocommerce_hooks_status();
        } catch (Exception $e) {
            $results['final_hook_status_error'] = $e->getMessage();
            $results['final_hook_status'] = array('error' => 'Failed to check final hook status');
        }
        
        // Add system status information
        $results['woocommerce_active'] = class_exists('WooCommerce');
        $results['woocommerce_loaded'] = did_action('woocommerce_loaded');
        $results['current_time'] = current_time('mysql');
        $results['php_memory_limit'] = ini_get('memory_limit');
        $results['php_max_execution_time'] = ini_get('max_execution_time');
        
        // Check for any fatal errors
        $error_log = error_get_last();
        if ($error_log && $error_log['type'] === E_ERROR) {
            $results['fatal_error'] = $error_log;
        }
        
        wp_send_json_success($results);
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('PMV: Critical error in pmv_ajax_test_hook_system: ' . $e->getMessage());
        error_log('PMV: Stack trace: ' . $e->getTraceAsString());
        
        wp_send_json_error('Critical error: ' . $e->getMessage());
        return;
    } catch (Error $e) {
        // Catch PHP 7+ errors
        error_log('PMV: Fatal error in pmv_ajax_test_hook_system: ' . $e->getMessage());
        error_log('PMV: Stack trace: ' . $e->getTraceAsString());
        
        wp_send_json_error('Fatal error: ' . $e->getMessage());
        return;
    }
}

/**
 * AJAX: Simple test endpoint to verify AJAX functionality
 */
add_action('wp_ajax_pmv_test_ajax_basic', 'pmv_ajax_test_basic');
function pmv_ajax_test_basic() {
    // Prevent any output before JSON response
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set proper headers for JSON response
    header('Content-Type: application/json');
    
    try {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Simple test response
        $test_data = array(
            'status' => 'success',
            'message' => 'Basic AJAX test successful',
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
        
        wp_send_json_success($test_data);
        
    } catch (Exception $e) {
        error_log('PMV: Error in basic AJAX test: ' . $e->getMessage());
        wp_send_json_error('Test failed: ' . $e->getMessage());
        return;
    } catch (Error $e) {
        error_log('PMV: Fatal error in basic AJAX test: ' . $e->getMessage());
        wp_send_json_error('Test failed: ' . $e->getMessage());
        return;
    }
}

/**
 * AJAX: Check credit status of a specific order
 */
add_action('wp_ajax_pmv_check_order_credit_status', 'pmv_ajax_check_order_credit_status');
function pmv_ajax_check_order_credit_status() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    // Get order ID
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        $result = $subscription_system->check_order_credit_status($order_id);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error checking order credit status: ' . $e->getMessage());
    }
}

/**
 * AJAX: Manually trigger order audit
 */
add_action('wp_ajax_pmv_trigger_order_audit', 'pmv_ajax_trigger_order_audit');
function pmv_ajax_trigger_order_audit() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
    }
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        wp_send_json_error('Credit system not available');
    }
    
    try {
        $subscription_system = PMV_Subscription_System::getInstance();
        $result = $subscription_system->audit_all_orders_for_credits();
        
        if ($result === false) {
            wp_send_json_error('Order audit failed');
        } else {
            wp_send_json_success($result);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error triggering order audit: ' . $e->getMessage());
    }
}



?>
