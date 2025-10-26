<?php
/**
 * PNG Metadata Viewer - Logging System
 * 
 * Comprehensive logging functionality with different levels and contexts
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PMV Logging Class
 */
class PMV_Logger {
    
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    private static $instance = null;
    private $log_file;
    private $enabled;
    private $log_level;
    
    private function __construct() {
        $this->enabled = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_level = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? self::LEVEL_DEBUG : self::LEVEL_ERROR;
        $this->log_file = WP_CONTENT_DIR . '/pmv-logs/';
        
        // Create log directory if it doesn't exist
        if (!file_exists($this->log_file)) {
            wp_mkdir_p($this->log_file);
        }
        
        $this->log_file .= 'pmv-' . date('Y-m-d') . '.log';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log a message with context
     */
    public function log($level, $message, $context = array()) {
        if (!$this->enabled) {
            return;
        }
        
        // Check if we should log this level
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $log_entry = $this->formatLogEntry($level, $message, $context);
        
        // Write to file
        $this->writeToFile($log_entry);
        
        // Also write to WordPress debug log if enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($log_entry);
        }
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = array()) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = array()) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Check if we should log this level
     */
    private function shouldLog($level) {
        $levels = array(
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4
        );
        
        $current_level = $levels[$this->log_level] ?? 3;
        $message_level = $levels[$level] ?? 3;
        
        return $message_level >= $current_level;
    }
    
    /**
     * Format log entry
     */
    private function formatLogEntry($level, $message, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? "User:$user_id" : "Guest";
        
        $log_entry = "[$timestamp] [$level] [$user_info] $message";
        
        if (!empty($context)) {
            $context_str = json_encode($context, JSON_UNESCAPED_SLASHES);
            $log_entry .= " Context: $context_str";
        }
        
        return $log_entry;
    }
    
    /**
     * Write log entry to file
     */
    private function writeToFile($log_entry) {
        $log_entry .= PHP_EOL;
        
        // Use WordPress file system API if available
        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        if ($wp_filesystem) {
            $wp_filesystem->put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        } else {
            // Fallback to direct file writing
            file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Get log file path
     */
    public function getLogFile() {
        return $this->log_file;
    }
    
    /**
     * Clear old log files (keep last 30 days)
     */
    public function cleanupOldLogs() {
        $log_dir = WP_CONTENT_DIR . '/pmv-logs/';
        $files = glob($log_dir . 'pmv-*.log');
        $cutoff_time = time() - (30 * 24 * 60 * 60); // 30 days
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log statistics
     */
    public function getLogStats() {
        $log_dir = WP_CONTENT_DIR . '/pmv-logs/';
        $files = glob($log_dir . 'pmv-*.log');
        
        $stats = array(
            'total_files' => count($files),
            'total_size' => 0,
            'oldest_file' => null,
            'newest_file' => null
        );
        
        foreach ($files as $file) {
            $size = filesize($file);
            $stats['total_size'] += $size;
            
            $mtime = filemtime($file);
            if (!$stats['oldest_file'] || $mtime < $stats['oldest_file']) {
                $stats['oldest_file'] = $mtime;
            }
            if (!$stats['newest_file'] || $mtime > $stats['newest_file']) {
                $stats['newest_file'] = $mtime;
            }
        }
        
        return $stats;
    }
}

/**
 * Convenience functions for logging
 */

/**
 * Log debug message
 */
function pmv_log_debug($message, $context = array()) {
    PMV_Logger::getInstance()->debug($message, $context);
}

/**
 * Log info message
 */
function pmv_log_info($message, $context = array()) {
    PMV_Logger::getInstance()->info($message, $context);
}

/**
 * Log warning message
 */
function pmv_log_warning($message, $context = array()) {
    PMV_Logger::getInstance()->warning($message, $context);
}

/**
 * Log error message
 */
function pmv_log_error($message, $context = array()) {
    PMV_Logger::getInstance()->error($message, $context);
}

/**
 * Log critical message
 */
function pmv_log_critical($message, $context = array()) {
    PMV_Logger::getInstance()->critical($message, $context);
}

/**
 * Log AJAX request
 */
function pmv_log_ajax($action, $data = array(), $result = null) {
    $context = array(
        'action' => $action,
        'data' => $data,
        'result' => $result,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    
    pmv_log_info("AJAX Request: $action", $context);
}

/**
 * Log database operation
 */
function pmv_log_db($operation, $table, $data = array(), $result = null) {
    $context = array(
        'operation' => $operation,
        'table' => $table,
        'data' => $data,
        'result' => $result
    );
    
    pmv_log_info("Database Operation: $operation on $table", $context);
}

/**
 * Log file operation
 */
function pmv_log_file($operation, $file_path, $result = null) {
    $context = array(
        'operation' => $operation,
        'file_path' => $file_path,
        'result' => $result
    );
    
    pmv_log_info("File Operation: $operation", $context);
}

/**
 * Log security event
 */
function pmv_log_security($event, $details = array()) {
    $context = array(
        'event' => $event,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => get_current_user_id()
    );
    
    pmv_log_warning("Security Event: $event", $context);
}

/**
 * Initialize logging system
 */
function pmv_init_logging() {
    // Clean up old logs weekly
    $last_cleanup = get_option('pmv_last_log_cleanup', 0);
    if (time() - $last_cleanup > 7 * 24 * 60 * 60) { // 7 days
        PMV_Logger::getInstance()->cleanupOldLogs();
        update_option('pmv_last_log_cleanup', time());
    }
}

// Initialize logging system
add_action('init', 'pmv_init_logging'); 