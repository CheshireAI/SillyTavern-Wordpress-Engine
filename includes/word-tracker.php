<?php
/**
 * Word Usage Tracking System
 * 
 * This file implements token/word counting and usage limits for the OpenAI API
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PMV_Word_Usage_Tracker {
    
    /**
     * Initialize the word usage tracker
     */
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_pmv_get_word_usage_stats', array(__CLASS__, 'ajax_get_word_usage_stats'));
        add_action('wp_ajax_nopriv_pmv_get_word_usage_stats', array(__CLASS__, 'ajax_get_word_usage_stats_guest'));
        
        // Hook into the character chat completion to track word usage
        add_action('pmv_after_chat_completion', array(__CLASS__, 'track_chat_completion'), 10, 3);
        
        // Add admin page for viewing and managing user word usage
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        }
        
        // Check and enforce word limits when needed
        add_filter('pmv_pre_chat_completion', array(__CLASS__, 'check_word_limits'), 10, 3);
    }
    
    /**
     * Track tokens used in a chat completion
     * 
     * @param array $response The API response
     * @param int $user_id The user ID
     * @param string $message The user message
     */
    public static function track_chat_completion($response, $user_id, $message) {
        // Debug logging
        error_log('PMV: Token tracker called - User ID: ' . $user_id . ', Message length: ' . strlen($message));
        
        // Extract token usage from the response
        $prompt_tokens = isset($response['usage']['prompt_tokens']) ? intval($response['usage']['prompt_tokens']) : 0;
        $completion_tokens = isset($response['usage']['completion_tokens']) ? intval($response['usage']['completion_tokens']) : 0;
        $total_tokens = isset($response['usage']['total_tokens']) ? intval($response['usage']['total_tokens']) : 0;
        
        // Fallback calculation if API doesn't return usage info
        if ($total_tokens === 0) {
            // Approximate token count: roughly 4 chars per token
            $message_tokens = ceil(mb_strlen($message) / 4);
            $response_tokens = 0;
            
            if (isset($response['choices'][0]['message']['content'])) {
                $response_tokens = ceil(mb_strlen($response['choices'][0]['message']['content']) / 4);
            }
            
            $total_tokens = $message_tokens + $response_tokens;
        }
        
        // Get current date
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        // Store in user meta if logged in
        if ($user_id > 0) {
            // Daily usage tracking
            $daily_usage = get_user_meta($user_id, 'pmv_daily_token_usage', true);
            if (!is_array($daily_usage)) {
                $daily_usage = array();
            }
            
            if (!isset($daily_usage[$today])) {
                $daily_usage[$today] = $total_tokens;
            } else {
                $daily_usage[$today] += $total_tokens;
            }
            
            // Clean up old entries (keep last 30 days)
            $cutoff = date('Y-m-d', strtotime('-30 days'));
            foreach ($daily_usage as $date => $count) {
                if ($date < $cutoff) {
                    unset($daily_usage[$date]);
                }
            }
            
            update_user_meta($user_id, 'pmv_daily_token_usage', $daily_usage);
            
            // Monthly usage tracking
            $monthly_usage = get_user_meta($user_id, 'pmv_monthly_token_usage', true);
            if (!is_array($monthly_usage)) {
                $monthly_usage = array();
            }
            
            if (!isset($monthly_usage[$current_month])) {
                $monthly_usage[$current_month] = $total_tokens;
            } else {
                $monthly_usage[$current_month] += $total_tokens;
            }
            
            // Clean up old entries (keep last 12 months)
            $cutoff_month = date('Y-m', strtotime('-12 months'));
            foreach ($monthly_usage as $month => $count) {
                if ($month < $cutoff_month) {
                    unset($monthly_usage[$month]);
                }
            }
            
            update_user_meta($user_id, 'pmv_monthly_token_usage', $monthly_usage);
            
            // Log completion details for potential auditing
            $completions_log = get_user_meta($user_id, 'pmv_completions_log', true);
            if (!is_array($completions_log)) {
                $completions_log = array();
            }
            
            $completions_log[] = array(
                'timestamp' => current_time('timestamp'),
                'tokens' => $total_tokens,
                'prompt_tokens' => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'message_length' => mb_strlen($message)
            );
            
            // Keep only the last 100 completions
            if (count($completions_log) > 100) {
                $completions_log = array_slice($completions_log, -100);
            }
            
            update_user_meta($user_id, 'pmv_completions_log', $completions_log);
        } else {
            // Guest user tracking - use session or cookies
            self::track_guest_usage($total_tokens);
        }
        
        // Global site tracking (for all users)
        $site_usage = get_option('pmv_site_token_usage', array());
        
        if (!isset($site_usage[$current_month])) {
            $site_usage[$current_month] = $total_tokens;
        } else {
            $site_usage[$current_month] += $total_tokens;
        }
        
        // Clean up old entries
        $cutoff_month = date('Y-m', strtotime('-12 months'));
        foreach ($site_usage as $month => $count) {
            if ($month < $cutoff_month) {
                unset($site_usage[$month]);
            }
        }
        
        update_option('pmv_site_token_usage', $site_usage);
        
        return $total_tokens;
    }
    
    /**
     * Track usage for guests using cookies
     * 
     * @param int $tokens Number of tokens to add
     */
    private static function track_guest_usage($tokens) {
        // Use cookies to track guest usage
        $cookie_name = 'pmv_guest_usage';
        $cookie_data = array();
        
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (!is_array($cookie_data)) {
                $cookie_data = array();
            }
        }
        
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        // Update daily usage
        if (!isset($cookie_data['daily'][$today])) {
            $cookie_data['daily'][$today] = $tokens;
        } else {
            $cookie_data['daily'][$today] += $tokens;
        }
        
        // Update monthly usage
        if (!isset($cookie_data['monthly'][$current_month])) {
            $cookie_data['monthly'][$current_month] = $tokens;
        } else {
            $cookie_data['monthly'][$current_month] += $tokens;
        }
        
        // Set cookie for 30 days
        setcookie(
            $cookie_name,
            json_encode($cookie_data),
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
    
    /**
     * AJAX handler for getting word usage stats for logged-in users
     */
    public static function ajax_get_word_usage_stats() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Get usage stats
        $stats = self::get_user_usage_stats($user_id);
        
        // Check if user has a monthly limit
        $monthly_limit = get_user_meta($user_id, 'pmv_monthly_token_limit', true);
        if ($monthly_limit) {
            $stats['has_limit'] = true;
            $stats['monthly_limit'] = intval($monthly_limit);
            $stats['is_near_limit'] = $stats['monthly_tokens'] > ($monthly_limit * 0.8); // 80% of limit
        } else {
            $stats['has_limit'] = false;
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for getting word usage stats for guests
     */
    public static function ajax_get_word_usage_stats_guest() {
        // Get guest usage from cookie
        $cookie_name = 'pmv_guest_usage';
        $cookie_data = array();
        
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (!is_array($cookie_data)) {
                $cookie_data = array();
            }
        }
        
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        $today_tokens = isset($cookie_data['daily'][$today]) ? intval($cookie_data['daily'][$today]) : 0;
        $monthly_tokens = isset($cookie_data['monthly'][$current_month]) ? intval($cookie_data['monthly'][$current_month]) : 0;
        
        // Check if there's a guest limit set
        $guest_daily_limit = get_option('pmv_guest_daily_token_limit', 0);
        $has_limit = ($guest_daily_limit > 0);
        
        $stats = array(
            'today_tokens' => $today_tokens,
            'monthly_tokens' => $monthly_tokens,
            'has_limit' => $has_limit
        );
        
        if ($has_limit) {
            $stats['daily_limit'] = intval($guest_daily_limit);
            $stats['is_near_limit'] = $today_tokens > ($guest_daily_limit * 0.8); // 80% of limit
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get usage statistics for a user
     * 
     * @param int $user_id User ID
     * @return array Usage statistics
     */
    public static function get_user_usage_stats($user_id) {
        $today = current_time('Y-m-d');
        $current_month = current_time('Y-m');
        
        // Get daily usage
        $daily_usage = get_user_meta($user_id, 'pmv_daily_token_usage', true);
        if (!is_array($daily_usage)) {
            $daily_usage = array();
        }
        
        $today_tokens = isset($daily_usage[$today]) ? intval($daily_usage[$today]) : 0;
        
        // Get monthly usage
        $monthly_usage = get_user_meta($user_id, 'pmv_monthly_token_usage', true);
        if (!is_array($monthly_usage)) {
            $monthly_usage = array();
        }
        
        $monthly_tokens = isset($monthly_usage[$current_month]) ? intval($monthly_usage[$current_month]) : 0;
        
        return array(
            'today_tokens' => $today_tokens,
            'monthly_tokens' => $monthly_tokens,
            'daily_usage' => $daily_usage,
            'monthly_usage' => $monthly_usage
        );
    }
    
    /**
     * Check word limits before processing a chat completion
     * 
     * @param bool $proceed Whether to proceed with the completion
     * @param int $user_id User ID
     * @param string $message User message
     * @return bool Whether to proceed
     */
    public static function check_word_limits($proceed, $user_id, $message) {
        if (!$proceed) {
            return false; // Already decided not to proceed
        }
        
        // If user is logged in, check their limits
        if ($user_id > 0) {
            $monthly_limit = get_user_meta($user_id, 'pmv_monthly_token_limit', true);
            
            if ($monthly_limit) {
                $stats = self::get_user_usage_stats($user_id);
                
                // If user has exceeded their monthly limit, block the completion
                if ($stats['monthly_tokens'] >= intval($monthly_limit)) {
                    return false;
                }
            }
        } else {
            // Check guest limits
            $guest_daily_limit = get_option('pmv_guest_daily_token_limit', 0);
            
            if ($guest_daily_limit > 0) {
                // Get guest usage from cookie
                $cookie_name = 'pmv_guest_usage';
                $cookie_data = array();
                
                if (isset($_COOKIE[$cookie_name])) {
                    $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
                    if (!is_array($cookie_data)) {
                        $cookie_data = array();
                    }
                }
                
                $today = current_time('Y-m-d');
                $today_tokens = isset($cookie_data['daily'][$today]) ? intval($cookie_data['daily'][$today]) : 0;
                
                // If guest has exceeded their daily limit, block the completion
                if ($today_tokens >= intval($guest_daily_limit)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Add admin menu for word usage tracking
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Token Usage Statistics',
            'Token Usage',
            'manage_options',
            'pmv-token-usage',
            array(__CLASS__, 'render_admin_page')
        );
    }
    
    /**
     * Render the admin page
     */
    public static function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get site-wide usage
        $site_usage = get_option('pmv_site_token_usage', array());
        
        // Process form submissions
        if (isset($_POST['pmv_save_settings']) && isset($_POST['pmv_token_settings_nonce']) && 
            wp_verify_nonce($_POST['pmv_token_settings_nonce'], 'pmv_save_token_settings')) {
            
            // Save guest daily limit
            $guest_daily_limit = isset($_POST['pmv_guest_daily_limit']) ? intval($_POST['pmv_guest_daily_limit']) : 0;
            update_option('pmv_guest_daily_token_limit', $guest_daily_limit);
            
            // Save default user monthly limit
            $default_user_limit = isset($_POST['pmv_default_user_limit']) ? intval($_POST['pmv_default_user_limit']) : 0;
            update_option('pmv_default_user_monthly_limit', $default_user_limit);
            
            // Display success message
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        
        // Get current settings
        $guest_daily_limit = get_option('pmv_guest_daily_token_limit', 0);
        $default_user_limit = get_option('pmv_default_user_monthly_limit', 0);
        
        // Calculate current month's usage
        $current_month = current_time('Y-m');
        $current_month_usage = isset($site_usage[$current_month]) ? intval($site_usage[$current_month]) : 0;
        
        // Start output buffering for the admin page
        ob_start();
        ?>
        <div class="wrap">
            <h1>Token Usage Statistics</h1>
            
            <div class="pmv-usage-dashboard">
                <div class="pmv-card">
                    <h2>Site-Wide Usage</h2>
                    <p>Current month: <strong><?php echo number_format($current_month_usage); ?></strong> tokens</p>
                    
                    <h3>Monthly Breakdown</h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Tokens</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Sort months in descending order
                            krsort($site_usage);
                            
                            foreach ($site_usage as $month => $tokens) {
                                // Format month for display
                                $display_month = date('F Y', strtotime($month . '-01'));
                                
                                echo '<tr>';
                                echo '<td>' . esc_html($display_month) . '</td>';
                                echo '<td>' . number_format($tokens) . '</td>';
                                echo '</tr>';
                            }
                            
                            if (empty($site_usage)) {
                                echo '<tr><td colspan="2">No usage data available yet.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pmv-card">
                    <h2>Usage Settings</h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('pmv_save_token_settings', 'pmv_token_settings_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Guest Daily Limit</th>
                                <td>
                                    <input type="number" name="pmv_guest_daily_limit" value="<?php echo esc_attr($guest_daily_limit); ?>" min="0" step="1000">
                                    <p class="description">Maximum tokens per day for non-logged-in users. Set to 0 for no limit.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Default User Monthly Limit</th>
                                <td>
                                    <input type="number" name="pmv_default_user_limit" value="<?php echo esc_attr($default_user_limit); ?>" min="0" step="1000">
                                    <p class="description">Default monthly token limit for new users. Set to 0 for no limit.</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="pmv_save_settings" class="button button-primary" value="Save Settings">
                        </p>
                    </form>
                </div>
                
                <div class="pmv-card">
                    <h2>Top Users This Month</h2>
                    
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Tokens This Month</th>
                                <th>Monthly Limit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get all users with token usage
                            $users = get_users(array(
                                'meta_key' => 'pmv_monthly_token_usage',
                                'fields' => array('ID', 'user_login', 'user_email')
                            ));
                            
                            // Sort users by current month usage
                            usort($users, function($a, $b) use ($current_month) {
                                $a_usage = get_user_meta($a->ID, 'pmv_monthly_token_usage', true);
                                $b_usage = get_user_meta($b->ID, 'pmv_monthly_token_usage', true);
                                
                                $a_month = isset($a_usage[$current_month]) ? intval($a_usage[$current_month]) : 0;
                                $b_month = isset($b_usage[$current_month]) ? intval($b_usage[$current_month]) : 0;
                                
                                return $b_month - $a_month; // Descending order
                            });
                            
                            // Limit to top 20 users
                            $users = array_slice($users, 0, 20);
                            
                            foreach ($users as $user) {
                                $monthly_usage = get_user_meta($user->ID, 'pmv_monthly_token_usage', true);
                                $month_tokens = isset($monthly_usage[$current_month]) ? intval($monthly_usage[$current_month]) : 0;
                                
                                $monthly_limit = get_user_meta($user->ID, 'pmv_monthly_token_limit', true);
                                $limit_display = $monthly_limit ? number_format($monthly_limit) : 'No limit';
                                
                                echo '<tr>';
                                echo '<td>' . esc_html($user->user_login) . ' (' . esc_html($user->user_email) . ')</td>';
                                echo '<td>' . number_format($month_tokens) . '</td>';
                                echo '<td>' . $limit_display . '</td>';
                                echo '<td><a href="' . admin_url('user-edit.php?user_id=' . $user->ID) . '">Edit User</a></td>';
                                echo '</tr>';
                            }
                            
                            if (empty($users)) {
                                echo '<tr><td colspan="4">No user data available yet.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="pmv-card">
                <h2>Set User Token Limits</h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('pmv_set_user_limit', 'pmv_user_limit_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">User</th>
                            <td>
                                <select name="pmv_user_id">
                                    <option value="">Select a user</option>
                                    <?php
                                    $all_users = get_users(array('fields' => array('ID', 'user_login', 'user_email')));
                                    foreach ($all_users as $u) {
                                        echo '<option value="' . esc_attr($u->ID) . '">' . esc_html($u->user_login) . ' (' . esc_html($u->user_email) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Monthly Token Limit</th>
                            <td>
                                <input type="number" name="pmv_user_monthly_limit" value="0" min="0" step="1000">
                                <p class="description">Set to 0 for no limit.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="pmv_set_user_limit" class="button button-primary" value="Set User Limit">
                    </p>
                </form>
            </div>
            
            <style>
            .pmv-usage-dashboard {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-top: 20px;
            }
            .pmv-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                padding: 15px;
                margin-bottom: 20px;
                min-width: 300px;
                flex: 1;
            }
            .pmv-card h2 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            </style>
        </div>
        <?php
        
        // Output the page content
        echo ob_get_clean();
    }
}

// Initialize the word usage tracker only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    PMV_Word_Usage_Tracker::init();
}

// Test function to verify token counter is working
function pmv_test_token_counter() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Create a test response
    $test_response = array(
        'usage' => array(
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'total_tokens' => 150
        ),
        'choices' => array(
            array(
                'message' => array(
                    'content' => 'This is a test response'
                )
            )
        )
    );
    
    $user_id = get_current_user_id();
    $test_message = 'This is a test message';
    
    // Trigger the token tracking
    do_action('pmv_after_chat_completion', $test_response, $user_id, $test_message);
    
    error_log('PMV: Token counter test completed');
    
    // Return success message
    return 'Token counter test completed. Check error logs for debugging information.';
}

// Add AJAX handler for testing token counter
function pmv_ajax_test_token_counter() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $result = pmv_test_token_counter();
    wp_send_json_success(array('message' => $result));
}
add_action('wp_ajax_pmv_test_token_counter', 'pmv_ajax_test_token_counter');

// Add user profile fields for token usage
function pmv_add_user_token_limit_field($user) {
    // Only show if current user can edit users
    if (!current_user_can('edit_users')) {
        return;
    }
    
    $monthly_limit = get_user_meta($user->ID, 'pmv_monthly_token_limit', true);
    ?>
    <h3>Token Usage Limits</h3>
    <table class="form-table">
        <tr>
            <th><label for="pmv_monthly_token_limit">Monthly Token Limit</label></th>
            <td>
                <input type="number" name="pmv_monthly_token_limit" id="pmv_monthly_token_limit" 
                       value="<?php echo esc_attr($monthly_limit); ?>" class="regular-text" min="0" step="1000" />
                <p class="description">Maximum number of tokens this user can use per month. Set to 0 for no limit.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('edit_user_profile', 'pmv_add_user_token_limit_field');
add_action('show_user_profile', 'pmv_add_user_token_limit_field');

// Save user token limit
function pmv_save_user_token_limit($user_id) {
    if (!current_user_can('edit_users')) {
        return false;
    }
    
    if (isset($_POST['pmv_monthly_token_limit'])) {
        $limit = intval($_POST['pmv_monthly_token_limit']);
        update_user_meta($user_id, 'pmv_monthly_token_limit', $limit);
    }
}
add_action('personal_options_update', 'pmv_save_user_token_limit');
add_action('edit_user_profile_update', 'pmv_save_user_token_limit');

// Initialize new users with default token limit
function pmv_set_default_token_limit($user_id) {
    $default_limit = get_option('pmv_default_user_monthly_limit', 0);
    
    if ($default_limit > 0) {
        update_user_meta($user_id, 'pmv_monthly_token_limit', $default_limit);
    }
}
add_action('user_register', 'pmv_set_default_token_limit');
