<?php
/**
 * Conversation Manager Initialization
 * 
 * This file initializes the conversation manager component for PNG Metadata Viewer
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include conversation manager class
require_once plugin_dir_path(__FILE__) . 'conversation-manager.php';

// Initialize conversation manager
PMV_Conversation_Manager::init();

/**
 * Register and enqueue conversation manager assets
 */
function pmv_enqueue_conversation_assets() {
    // Enqueue conversation manager script
    wp_enqueue_script(
        'pmv-conversation-manager',
        plugin_dir_url(__FILE__) . 'js/conversation-manager.js',
        array('jquery'),
        PLUGIN_VERSION,
        true
    );
    
    // Enqueue chat integration script
    wp_enqueue_script(
        'pmv-chat-integration',
        plugin_dir_url(__FILE__) . 'js/chat-integration.js',
        array('jquery', 'pmv-conversation-manager', 'pmv-chat-script'),
        PLUGIN_VERSION,
        true
    );
    
    // Enqueue conversation styles
    wp_enqueue_style(
        'pmv-conversation-styles',
        plugin_dir_url(__FILE__) . 'css/conversation-styles.css',
        array(),
        PLUGIN_VERSION
    );
    
    // Enqueue dashicons for the UI
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_conversation_assets');

/**
 * Add conversation settings to the admin page
 */
function pmv_add_conversation_settings($settings_tabs) {
    // Add a new tab for conversation settings
    $settings_tabs['conversations'] = 'Conversations';
    return $settings_tabs;
}
add_filter('pmv_settings_tabs', 'pmv_add_conversation_settings');

/**
 * Render conversation settings fields
 */
function pmv_render_conversation_settings() {
    // Only proceed if we're on the conversations tab
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'conversations') {
        return;
    }
    
    ?>
    <h2>Conversation Settings</h2>
    <table class="form-table">
        <tr>
            <th scope="row">Allow Guest Conversations</th>
            <td>
                <label>
                    <input type="checkbox" name="pmv_allow_guest_conversations" value="1" <?php checked(get_option('pmv_allow_guest_conversations'), true); ?>>
                    Allow non-logged-in users to save conversations (stored in browser cookies)
                </label>
                <p class="description">
                    Guest conversations are only stored in the browser and will be lost if cookies are cleared.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">Auto-Save Conversations</th>
            <td>
                <label>
                    <input type="checkbox" name="pmv_auto_save_conversations" value="1" <?php checked(get_option('pmv_auto_save_conversations'), true); ?>>
                    Automatically save conversations after each message
                </label>
                <p class="description">
                    When enabled, conversations will be automatically saved after each message is sent or received.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">Max Conversations per User</th>
            <td>
                <input type="number" name="pmv_max_conversations_per_user" value="<?php echo esc_attr(get_option('pmv_max_conversations_per_user', 50)); ?>" min="1" max="1000">
                <p class="description">
                    Maximum number of conversations each user can save. When this limit is reached, the oldest conversations will be deleted.
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('pmv_render_settings', 'pmv_render_conversation_settings');

/**
 * Add database tables when plugin is activated
 */
function pmv_activate_conversation_manager() {
    PMV_Conversation_Manager::create_tables();
}
register_activation_hook(PMV_PLUGIN_FILE, 'pmv_activate_conversation_manager');
