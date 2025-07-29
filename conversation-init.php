<?php
/**
 * Conversation Manager Initialization
 * This file ensures the conversation manager is properly initialized
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

// The conversation manager is now handled by the unified handler in ajax-handlers.php
// This file is kept for backward compatibility but doesn't need to do anything

/**
 * Ensure conversation table exists
 */
function pmv_ensure_conversation_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pmv_conversations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        // Table will be created by the unified handler
        error_log('PMV: Conversation table needs to be created');
    }
}

// Check on init
add_action('init', 'pmv_ensure_conversation_table');
