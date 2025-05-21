<?php
/**
 * Conversation Manager for PNG Metadata Viewer
 *
 * Handles storing, retrieving, and managing character conversations
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

class PMV_Conversation_Manager {
    
    /**
     * Initialize the conversation manager
     */
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_pmv_get_conversations', array(__CLASS__, 'get_conversations'));
        add_action('wp_ajax_pmv_get_conversation', array(__CLASS__, 'get_conversation'));
        add_action('wp_ajax_pmv_save_conversation', array(__CLASS__, 'save_conversation'));
        add_action('wp_ajax_pmv_delete_conversation', array(__CLASS__, 'delete_conversation'));
        
        // Guest versions if needed
        if (get_option('pmv_allow_guest_conversations', false)) {
            add_action('wp_ajax_nopriv_pmv_get_conversations', array(__CLASS__, 'get_conversations_guest'));
            add_action('wp_ajax_nopriv_pmv_get_conversation', array(__CLASS__, 'get_conversation_guest'));
            add_action('wp_ajax_nopriv_pmv_save_conversation', array(__CLASS__, 'save_conversation_guest'));
            add_action('wp_ajax_nopriv_pmv_delete_conversation', array(__CLASS__, 'delete_conversation_guest'));
        }
    }
    
    /**
     * Get conversations for a user and character
     */
    public static function get_conversations() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get character ID
        $character_id = isset($_POST['character_id']) ? sanitize_text_field($_POST['character_id']) : '';
        
        if (empty($character_id)) {
            wp_send_json_error(array('message' => 'Character ID is required'));
            return;
        }
        
        global $wpdb;
        
        // Query conversations for this user and character
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
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log("PMV Conversation Manager: Database error - " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Database error occurred'));
            return;
        }
        
        // Return the conversations
        wp_send_json_success($conversations);
    }
    
    /**
     * Get a specific conversation
     */
    public static function get_conversation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get conversation ID
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        
        if ($conversation_id === 0) {
            wp_send_json_error(array('message' => 'Conversation ID is required'));
            return;
        }
        
        global $wpdb;
        
        // Get the conversation
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$conversation) {
            wp_send_json_error(array('message' => 'Conversation not found'));
            return;
        }
        
        // Parse the messages from JSON string
        if (!empty($conversation['messages'])) {
            $conversation['messages'] = json_decode($conversation['messages'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $conversation['messages'] = array();
            }
        } else {
            $conversation['messages'] = array();
        }
        
        // Return the conversation with messages
        wp_send_json_success($conversation);
    }
    
    /**
     * Save a conversation
     */
    public static function save_conversation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get conversation data
        if (empty($_POST['conversation'])) {
            wp_send_json_error(array('message' => 'Conversation data is required'));
            return;
        }
        
        $conversation_json = stripslashes($_POST['conversation']);
        $conversation_data = json_decode($conversation_json, true);
        
        if (!$conversation_data || json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => 'Invalid conversation data format'));
            return;
        }
        
        // Check if we have a character ID
        if (empty($conversation_data['character_id'])) {
            wp_send_json_error(array('message' => 'Character ID is required'));
            return;
        }
        
        // Check if we have messages
        if (empty($conversation_data['messages']) || !is_array($conversation_data['messages'])) {
            wp_send_json_error(array('message' => 'Conversation messages are required'));
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pmv_conversations';
        
        // Check user conversation limits
        $max_conversations = get_option('pmv_max_conversations_per_user', 50);
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
        
        // If we have an existing conversation ID, update it
        if (!empty($conversation_data['id'])) {
            $conversation_id = intval($conversation_data['id']);
            
            // Check if this conversation belongs to the user
            $owner_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                    $conversation_id,
                    $user_id
                )
            );
            
            if (!$owner_check) {
                wp_send_json_error(array('message' => 'You do not have permission to update this conversation'));
                return;
            }
            
            // Update the conversation
            $result = $wpdb->update(
                $table_name,
                array(
                    'title' => sanitize_text_field($conversation_data['title']),
                    'messages' => json_encode($conversation_data['messages']),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $conversation_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
        } else {
            // Create a new conversation
            
            // Check if we've hit the limit
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
                    // Delete the conversation
                    $wpdb->delete(
                        $table_name,
                        array('id' => $oldest->id),
                        array('%d')
                    );
                }
            }
            
            // Insert the new conversation
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'character_id' => sanitize_text_field($conversation_data['character_id']),
                    'title' => sanitize_text_field($conversation_data['title']),
                    'messages' => json_encode($conversation_data['messages']),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            $conversation_id = $wpdb->insert_id;
        }
        
        // Check if operation was successful
        if ($result === false) {
            error_log("PMV Conversation Manager: Save error - " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to save conversation'));
            return;
        }
        
        // Return success with the conversation ID
        wp_send_json_success(array(
            'id' => $conversation_id,
            'message' => 'Conversation saved successfully'
        ));
    }
    
    /**
     * Delete a conversation
     */
    public static function delete_conversation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get conversation ID
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        
        if ($conversation_id === 0) {
            wp_send_json_error(array('message' => 'Conversation ID is required'));
            return;
        }
        
        global $wpdb;
        
        // Check if this conversation belongs to the user
        $table_name = $wpdb->prefix . 'pmv_conversations';
        $owner_check = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
                $conversation_id,
                $user_id
            )
        );
        
        if (!$owner_check) {
            wp_send_json_error(array('message' => 'You do not have permission to delete this conversation'));
            return;
        }
        
        // Delete the conversation
        $result = $wpdb->delete(
            $table_name,
            array('id' => $conversation_id),
            array('%d')
        );
        
        if ($result === false) {
            error_log("PMV Conversation Manager: Delete error - " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to delete conversation'));
            return;
        }
        
        // Return success
        wp_send_json_success(array('message' => 'Conversation deleted successfully'));
    }
    
    /**
     * Guest versions of the conversation functions
     */
    public static function get_conversations_guest() {
        // Check if guest conversations are allowed
        if (!get_option('pmv_allow_guest_conversations', false)) {
            wp_send_json_error(array('message' => 'Guest conversations not allowed'));
            return;
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // For guests, we store conversations in browser cookies
        wp_send_json_success(array());
    }
    
    public static function get_conversation_guest() {
        // Guest conversations must be handled client-side
        wp_send_json_error(array('message' => 'Feature not available for guest users'));
    }
    
    public static function save_conversation_guest() {
        // Guest conversations must be handled client-side
        wp_send_json_error(array('message' => 'Feature not available for guest users'));
    }
    
    public static function delete_conversation_guest() {
        // Guest conversations must be handled client-side
        wp_send_json_error(array('message' => 'Feature not available for guest users'));
    }
}
