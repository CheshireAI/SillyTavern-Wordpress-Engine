<?php
/**
 * PNG Metadata Viewer - Database Optimizer
 * 
 * Database optimization and query improvements
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PMV Database Optimizer Class
 */
class PMV_Database_Optimizer {
    
    private static $instance = null;
    private $wpdb;
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create database indexes for better performance
     */
    public function createIndexes() {
        $conversations_table = $this->wpdb->prefix . 'pmv_conversations';
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        
        // Indexes for conversations table
        $this->createIndexIfNotExists($conversations_table, 'idx_user_character', 'user_id, character_id');
        $this->createIndexIfNotExists($conversations_table, 'idx_created_at', 'created_at');
        $this->createIndexIfNotExists($conversations_table, 'idx_updated_at', 'updated_at');
        $this->createIndexIfNotExists($conversations_table, 'idx_character_name', 'character_name');
        
        // Indexes for messages table
        $this->createIndexIfNotExists($messages_table, 'idx_conversation_role', 'conversation_id, role');
        $this->createIndexIfNotExists($messages_table, 'idx_created_at', 'created_at');
        // Note: message_order column doesn't exist in current schema
        // $this->createIndexIfNotExists($messages_table, 'idx_message_order', 'conversation_id, message_order');
        
        pmv_log_info('Database indexes created/verified');
    }
    
    /**
     * Create index if it doesn't exist
     */
    private function createIndexIfNotExists($table, $index_name, $columns) {
        $index_exists = $this->wpdb->get_var("
            SELECT COUNT(1) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = '$table' 
            AND index_name = '$index_name'
        ");
        
        if (!$index_exists) {
            $sql = "CREATE INDEX $index_name ON $table ($columns)";
            $result = $this->wpdb->query($sql);
            
            if ($result !== false) {
                pmv_log_info("Created index $index_name on $table");
            } else {
                pmv_log_error("Failed to create index $index_name on $table", array(
                    'error' => $this->wpdb->last_error
                ));
            }
        }
    }
    
    /**
     * Optimize database tables
     */
    public function optimizeTables() {
        $conversations_table = $this->wpdb->prefix . 'pmv_conversations';
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        
        $tables = array($conversations_table, $messages_table);
        
        foreach ($tables as $table) {
            $result = $this->wpdb->query("OPTIMIZE TABLE $table");
            if ($result !== false) {
                pmv_log_info("Optimized table $table");
            } else {
                pmv_log_error("Failed to optimize table $table", array(
                    'error' => $this->wpdb->last_error
                ));
            }
        }
    }
    
    /**
     * Clean up old data
     */
    public function cleanupOldData() {
        $conversations_table = $this->wpdb->prefix . 'pmv_conversations';
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        
        // Delete conversations older than 90 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-90 days'));
        $deleted_conversations = $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $conversations_table WHERE created_at < %s",
            $cutoff_date
        ));
        
        if ($deleted_conversations !== false) {
            pmv_log_info("Cleaned up $deleted_conversations old conversations");
        }
        
        // Delete orphaned messages
        $deleted_messages = $this->wpdb->query("
            DELETE m FROM $messages_table m 
            LEFT JOIN $conversations_table c ON m.conversation_id = c.id 
            WHERE c.id IS NULL
        ");
        
        if ($deleted_messages !== false) {
            pmv_log_info("Cleaned up $deleted_messages orphaned messages");
        }
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats() {
        $conversations_table = $this->wpdb->prefix . 'pmv_conversations';
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        
        $stats = array(
            'conversations' => array(
                'total' => $this->wpdb->get_var("SELECT COUNT(*) FROM $conversations_table"),
                'today' => $this->wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE DATE(created_at) = CURDATE()"),
                'this_week' => $this->wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE YEARWEEK(created_at) = YEARWEEK(NOW())"),
                'this_month' => $this->wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")
            ),
            'messages' => array(
                'total' => $this->wpdb->get_var("SELECT COUNT(*) FROM $messages_table"),
                'today' => $this->wpdb->get_var("SELECT COUNT(*) FROM $messages_table WHERE DATE(created_at) = CURDATE()"),
                'this_week' => $this->wpdb->get_var("SELECT COUNT(*) FROM $messages_table WHERE YEARWEEK(created_at) = YEARWEEK(NOW())"),
                'this_month' => $this->wpdb->get_var("SELECT COUNT(*) FROM $messages_table WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")
            ),
            'top_characters' => $this->wpdb->get_results("
                SELECT character_name, COUNT(*) as count 
                FROM $conversations_table 
                GROUP BY character_name 
                ORDER BY count DESC 
                LIMIT 10
            "),
            'active_users' => $this->wpdb->get_results("
                SELECT user_id, COUNT(*) as count 
                FROM $conversations_table 
                WHERE user_id > 0 
                GROUP BY user_id 
                ORDER BY count DESC 
                LIMIT 10
            ")
        );
        
        return $stats;
    }
    
    /**
     * Analyze query performance
     */
    public function analyzeQueryPerformance($query, $params = array()) {
        $start_time = microtime(true);
        
        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }
        
        $result = $this->wpdb->query($query);
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        pmv_log_info("Query executed", array(
            'query' => $query,
            'execution_time_ms' => round($execution_time, 2),
            'rows_affected' => $this->wpdb->num_rows,
            'last_error' => $this->wpdb->last_error
        ));
        
        return array(
            'result' => $result,
            'execution_time_ms' => round($execution_time, 2),
            'rows_affected' => $this->wpdb->num_rows,
            'last_error' => $this->wpdb->last_error
        );
    }
    
    /**
     * Get slow queries
     */
    public function getSlowQueries($limit = 10) {
        // This would require MySQL slow query log to be enabled
        // For now, we'll return a placeholder
        return array(
            'note' => 'Slow query analysis requires MySQL slow query log to be enabled',
            'queries' => array()
        );
    }
    
    /**
     * Optimize specific queries
     */
    public function optimizeConversationQueries() {
        $conversations_table = $this->wpdb->prefix . 'pmv_conversations';
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        
        // Optimize conversation retrieval with proper joins
        $optimized_query = "
            SELECT c.*, 
                   COUNT(m.id) as message_count,
                   MAX(m.created_at) as last_message_at
            FROM $conversations_table c
            LEFT JOIN $messages_table m ON c.id = m.conversation_id
            WHERE c.user_id = %d
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ";
        
        return $optimized_query;
    }
    
    /**
     * Batch operations for better performance
     */
    public function batchInsertMessages($conversation_id, $messages) {
        if (empty($messages)) {
            return false;
        }
        
        $messages_table = $this->wpdb->prefix . 'pmv_conversation_messages';
        $values = array();
        $placeholders = array();
        
        foreach ($messages as $index => $message) {
            $values[] = $conversation_id;
            $values[] = $message['role'];
            $values[] = $message['content'];
            $values[] = current_time('mysql');
            $values[] = $index;
            
            $placeholders[] = "(%d, %s, %s, %s, %d)";
        }
        
        $query = "INSERT INTO $messages_table (conversation_id, role, content, created_at) VALUES " . implode(', ', $placeholders);
        
        $result = $this->wpdb->query($this->wpdb->prepare($query, $values));
        
        if ($result !== false) {
            pmv_log_info("Batch inserted " . count($messages) . " messages", array(
                'conversation_id' => $conversation_id
            ));
        } else {
            pmv_log_error("Failed to batch insert messages", array(
                'conversation_id' => $conversation_id,
                'error' => $this->wpdb->last_error
            ));
        }
        
        return $result;
    }
    
    /**
     * Initialize database optimization
     */
    public function initialize() {
        // Create indexes
        $this->createIndexes();
        
        // Schedule regular cleanup
        if (!wp_next_scheduled('pmv_database_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'pmv_database_cleanup');
        }
        
        // Schedule regular optimization
        if (!wp_next_scheduled('pmv_database_optimization')) {
            wp_schedule_event(time(), 'monthly', 'pmv_database_optimization');
        }
        
        pmv_log_info('Database optimizer initialized');
    }
}

/**
 * Initialize database optimization
 */
function pmv_init_database_optimizer() {
    PMV_Database_Optimizer::getInstance()->initialize();
}

/**
 * Scheduled cleanup hook
 */
function pmv_database_cleanup_cron() {
    $optimizer = PMV_Database_Optimizer::getInstance();
    $optimizer->cleanupOldData();
}

/**
 * Scheduled optimization hook
 */
function pmv_database_optimization_cron() {
    $optimizer = PMV_Database_Optimizer::getInstance();
    $optimizer->optimizeTables();
}

// Initialize on plugin load
// Only initialize database optimizer on admin pages or when explicitly needed
if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'pmv_optimize_database')) {
    add_action('init', 'pmv_init_database_optimizer');
}

// Register cron hooks
add_action('pmv_database_cleanup', 'pmv_database_cleanup_cron');
add_action('pmv_database_optimization', 'pmv_database_optimization_cron'); 