<?php
/**
 * PNG Metadata Viewer - Rate Limiter
 * 
 * Rate limiting functionality to prevent abuse
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PMV Rate Limiter Class
 */
class PMV_Rate_Limiter {
    
    private static $instance = null;
    private $wpdb;
    
    // Rate limit configurations
    private $limits = array(
        'chat_message' => array(
            'requests' => 10,
            'window' => 60, // seconds
            'description' => 'Chat messages per minute'
        ),
        'image_generation' => array(
            'requests' => 5,
            'window' => 300, // seconds
            'description' => 'Image generations per 5 minutes'
        ),
        'conversation_save' => array(
            'requests' => 20,
            'window' => 60, // seconds
            'description' => 'Conversation saves per minute'
        ),
        'api_test' => array(
            'requests' => 3,
            'window' => 300, // seconds
            'description' => 'API tests per 5 minutes'
        )
    );
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->createRateLimitTable();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create rate limit tracking table
     */
    private function createRateLimitTable() {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            ip_address varchar(45) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_action (user_id, action),
            KEY ip_action (ip_address, action),
            KEY created_at (created_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if an action is allowed for the user
     */
    public function isAllowed($action, $user_id = null) {
        if (!isset($this->limits[$action])) {
            return true; // No limit configured
        }
        
        $limit = $this->limits[$action];
        $user_id = $user_id ?: get_current_user_id();
        $ip_address = $this->getClientIP();
        
        // Check subscription-based limits for image generation
        if ($action === 'image_generation' && class_exists('PMV_Subscription_System')) {
            $subscription_system = PMV_Subscription_System::getInstance();
            if (!$subscription_system->can_generate_image($user_id)) {
                pmv_log_warning("Insufficient image credits for image generation", array(
                    'action' => $action,
                    'user_id' => $user_id
                ));
                return false;
            }
        }
        
        // Check subscription-based limits for text generation
        if ($action === 'chat_message' && class_exists('PMV_Subscription_System')) {
            $subscription_system = PMV_Subscription_System::getInstance();
            if (!$subscription_system->can_generate_token($user_id)) {
                pmv_log_warning("Insufficient text credits for chat message", array(
                    'action' => $action,
                    'user_id' => $user_id
                ));
                return false;
            }
        }
        
        // Clean old entries
        $this->cleanOldEntries($action, $limit['window']);
        
        // Count recent requests
        $recent_requests = $this->countRecentRequests($action, $user_id, $ip_address, $limit['window']);
        
        if ($recent_requests >= $limit['requests']) {
            pmv_log_warning("Rate limit exceeded", array(
                'action' => $action,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'limit' => $limit['requests'],
                'window' => $limit['window']
            ));
            
            return false;
        }
        
        // Record this request
        $this->recordRequest($action, $user_id, $ip_address);
        
        // NOTE: Credits are no longer consumed here for image_generation
        // because it's an asynchronous operation. Credits will be consumed
        // after successful image generation via a separate endpoint.
        // For chat_message, credits are consumed after actual API usage.
        
        // Only consume credits for synchronous actions that complete immediately
        if ($action === 'chat_message' && $user_id > 0 && class_exists('PMV_Subscription_System')) {
            $this->consumeCreditsForAction($action, $user_id);
        }
        
        return true;
    }
    
    /**
     * Consume credits for successful actions
     */
    private function consumeCreditsForAction($action, $user_id) {
        $subscription_system = PMV_Subscription_System::getInstance();
        
        switch ($action) {
            case 'image_generation':
                // Consume 1 image credit
                if ($subscription_system->consume_image_credits($user_id, 1)) {
                    pmv_log_info("Consumed 1 image credit for user {$user_id}", array(
                        'action' => $action,
                        'user_id' => $user_id,
                        'credits_consumed' => 1
                    ));
                }
                break;
                
            case 'chat_message':
                // Consume text credits based on message length (estimate)
                $message_length = isset($_POST['message']) ? strlen($_POST['message']) : 100;
                $tokens_estimate = max(1, intval($message_length / 4)); // Rough estimate: 4 chars = 1 token
                
                if ($subscription_system->consume_text_credits($user_id, $tokens_estimate)) {
                    pmv_log_info("Consumed {$tokens_estimate} text credits for user {$user_id}", array(
                        'action' => $action,
                        'user_id' => $user_id,
                        'credits_consumed' => $tokens_estimate,
                        'message_length' => $message_length
                    ));
                    
                    // NOTE: We do NOT call track_text_usage here because:
                    // 1. The word tracker (track_chat_completion) already records actual API token usage
                    // 2. Calling both would create double counting and inflated usage numbers
                    // 3. The subscription system should only track credit consumption, not usage statistics
                }
                break;
        }
    }
    
    /**
     * Get remaining requests for an action
     */
    public function getRemainingRequests($action, $user_id = null) {
        if (!isset($this->limits[$action])) {
            return -1; // No limit
        }
        
        $limit = $this->limits[$action];
        $user_id = $user_id ?: get_current_user_id();
        $ip_address = $this->getClientIP();
        
        // Clean old entries
        $this->cleanOldEntries($action, $limit['window']);
        
        // Count recent requests
        $recent_requests = $this->countRecentRequests($action, $user_id, $ip_address, $limit['window']);
        
        return max(0, $limit['requests'] - $recent_requests);
    }
    
    /**
     * Get rate limit info
     */
    public function getRateLimitInfo($action) {
        if (!isset($this->limits[$action])) {
            return null;
        }
        
        $limit = $this->limits[$action];
        $remaining = $this->getRemainingRequests($action);
        
        return array(
            'action' => $action,
            'limit' => $limit['requests'],
            'window' => $limit['window'],
            'description' => $limit['description'],
            'remaining' => $remaining,
            'reset_time' => time() + $limit['window']
        );
    }
    
    /**
     * Clean old rate limit entries
     */
    private function cleanOldEntries($action, $window) {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        $cutoff_time = date('Y-m-d H:i:s', time() - $window);
        
        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $table_name WHERE action = %s AND created_at < %s",
            $action,
            $cutoff_time
        ));
    }
    
    /**
     * Count recent requests
     */
    private function countRecentRequests($action, $user_id, $ip_address, $window) {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        $cutoff_time = date('Y-m-d H:i:s', time() - $window);
        
        // Count by user ID first, then by IP if no user ID
        if ($user_id > 0) {
            $count = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE action = %s AND user_id = %d AND created_at > %s",
                $action,
                $user_id,
                $cutoff_time
            ));
        } else {
            $count = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE action = %s AND ip_address = %s AND created_at > %s",
                $action,
                $ip_address,
                $cutoff_time
            ));
        }
        
        return intval($count);
    }
    
    /**
     * Record a request
     */
    private function recordRequest($action, $user_id, $ip_address) {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        
        $this->wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Reset rate limits for a user
     */
    public function resetUserLimits($user_id) {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        
        $deleted = $this->wpdb->delete(
            $table_name,
            array('user_id' => $user_id),
            array('%d')
        );
        
        if ($deleted !== false) {
            pmv_log_info("Reset rate limits for user", array('user_id' => $user_id));
        }
        
        return $deleted;
    }
    
    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats() {
        $table_name = $this->wpdb->prefix . 'pmv_rate_limits';
        
        $stats = array();
        
        foreach ($this->limits as $action => $limit) {
            $stats[$action] = array(
                'total_requests' => $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE action = %s",
                    $action
                )),
                'recent_requests' => $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name 
                     WHERE action = %s AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
                    $action,
                    $limit['window']
                )),
                'unique_users' => $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE action = %s",
                    $action
                )),
                'unique_ips' => $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE action = %s",
                    $action
                ))
            );
        }
        
        return $stats;
    }
    
    /**
     * Update rate limit configuration
     */
    public function updateLimit($action, $requests, $window) {
        if (isset($this->limits[$action])) {
            $this->limits[$action]['requests'] = intval($requests);
            $this->limits[$action]['window'] = intval($window);
            
            pmv_log_info("Updated rate limit configuration", array(
                'action' => $action,
                'requests' => $requests,
                'window' => $window
            ));
            
            return true;
        }
        
        return false;
    }
}

/**
 * Rate limiting middleware for AJAX requests
 */
function pmv_check_rate_limit($action) {
    $limiter = PMV_Rate_Limiter::getInstance();
    
    if (!$limiter->isAllowed($action)) {
        $info = $limiter->getRateLimitInfo($action);
        
        pmv_log_security("Rate limit exceeded", array(
            'action' => $action,
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
        
        wp_send_json_error(array(
            'message' => 'Rate limit exceeded. Please wait before making more requests.',
            'code' => 'rate_limit_exceeded',
            'limit_info' => $info
        ));
        
        return false;
    }
    
    return true;
}

/**
 * Add rate limit headers to response
 */
function pmv_add_rate_limit_headers($action) {
    $limiter = PMV_Rate_Limiter::getInstance();
    $info = $limiter->getRateLimitInfo($action);
    
    if ($info) {
        header('X-RateLimit-Limit: ' . $info['limit']);
        header('X-RateLimit-Remaining: ' . $info['remaining']);
        header('X-RateLimit-Reset: ' . $info['reset_time']);
    }
}

/**
 * Initialize rate limiter
 */
function pmv_init_rate_limiter() {
    PMV_Rate_Limiter::getInstance();
}

// Initialize rate limiter
add_action('init', 'pmv_init_rate_limiter'); 