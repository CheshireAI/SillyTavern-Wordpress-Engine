<?php
/**
 * PNG Metadata Viewer - Voting System
 * Handles upvotes/downvotes for character cards with user authentication
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Voting System Class
 */
class PMV_Voting_System {
    
    private $votes_table;
    private $db_version = '1.0';
    
    public function __construct() {
        error_log('PMV Voting: Constructor called');
        
        // Only initialize if WordPress is ready
        if (!function_exists('add_action')) {
            error_log('PMV Voting: WordPress not ready, returning');
            return;
        }
        
        error_log('PMV Voting: WordPress ready, initializing...');
        
        global $wpdb;
        $this->votes_table = $wpdb->prefix . 'pmv_character_votes';
        
        // Initialize hooks
        add_action('init', array($this, 'init'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 15);
        
        // AJAX handlers
        add_action('wp_ajax_pmv_vote_character', array($this, 'ajax_vote_character'));
        add_action('wp_ajax_pmv_get_vote_stats', array($this, 'ajax_get_vote_stats'));
        
        // AJAX handler for non-logged-in users to view vote stats
        add_action('wp_ajax_nopriv_pmv_get_vote_stats', array($this, 'ajax_get_vote_stats'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'check_database'));
        
        // Add sorting option to character cards
        add_filter('pmv_character_cards_sort_options', array($this, 'add_vote_sort_options'));
        add_filter('pmv_character_cards_query', array($this, 'apply_vote_sorting'), 10, 2);
        
        error_log('PMV Voting: Constructor completed, hooks added');
    }
    
    /**
     * Initialize the voting system
     */
    public function init() {
        // Only run if WordPress is fully loaded
        if (!function_exists('wp_get_current_user')) {
            return;
        }
        
        // Don't create tables during AJAX requests
        if (wp_doing_ajax()) {
            return;
        }
        
        // Ensure tables exist
        if (!$this->votes_table_exists()) {
            $this->create_table();
        }
    }
    
    /**
     * Check if votes table exists
     */
    public function votes_table_exists() {
        global $wpdb;
        
        // Check if WordPress database is available
        if (!$wpdb || !method_exists($wpdb, 'get_var')) {
            return false;
        }
        
        $table_name = $this->votes_table;
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        return $result === $table_name;
    }
    
    /**
     * Create the votes table
     */
    private function create_table() {
        global $wpdb;
        
        // Check if WordPress database is available
        if (!$wpdb || !method_exists($wpdb, 'get_charset_collate')) {
            error_log('PMV Voting: WordPress database not available');
            return;
        }
        
        // Check if ABSPATH is defined
        if (!defined('ABSPATH')) {
            error_log('PMV Voting: ABSPATH not defined');
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->votes_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            character_filename varchar(255) NOT NULL,
            vote_type enum('upvote', 'downvote') NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_character (user_id, character_filename),
            KEY character_filename (character_filename),
            KEY vote_type (vote_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update version
        update_option('pmv_voting_db_version', $this->db_version);
        
        error_log('PMV Voting: Created votes table');
    }
    
    /**
     * Check database on admin init
     */
    public function check_database() {
        // Don't create tables during AJAX requests
        if (wp_doing_ajax()) {
            return;
        }
        
        if (!$this->votes_table_exists()) {
            $this->create_table();
        }
    }
    
    /**
     * Enqueue scripts for voting functionality
     */
    public function enqueue_scripts() {
        if (is_admin()) return;
        
        // Debug logging
        error_log('PMV Voting: enqueue_scripts called');
        
        // Check if required constants are defined
        if (!defined('PMV_PLUGIN_URL') || !defined('PMV_VERSION')) {
            error_log('PMV Voting: Required constants not defined');
            error_log('PMV Voting: PMV_PLUGIN_URL = ' . (defined('PMV_PLUGIN_URL') ? PMV_PLUGIN_URL : 'NOT DEFINED'));
            error_log('PMV Voting: PMV_VERSION = ' . (defined('PMV_VERSION') ? PMV_VERSION : 'NOT DEFINED'));
            return;
        }
        
        // Ensure URL has trailing slash
        $script_url = trailingslashit(PMV_PLUGIN_URL) . 'js/voting-system.js';
        error_log('PMV Voting: Enqueuing script: ' . $script_url);
        wp_enqueue_script('pmv-voting', $script_url, array('jquery'), PMV_VERSION, true);
        
        // Localize script with voting data
        $current_user = wp_get_current_user();
        $voting_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmv_voting_nonce'),
            'user_id' => $current_user->ID,
            'is_logged_in' => is_user_logged_in(),
            'login_url' => wp_login_url(get_permalink()),
            'strings' => array(
                'login_required' => __('Please log in to vote', 'png-metadata-viewer'),
                'vote_success' => __('Vote recorded successfully', 'png-metadata-viewer'),
                'vote_error' => __('Error recording vote', 'png-metadata-viewer'),
                'already_voted' => __('You have already voted on this character', 'png-metadata-viewer')
            )
        );
        
        wp_localize_script('pmv-voting', 'pmv_voting', $voting_data);
        error_log('PMV Voting: Script enqueued and localized successfully');
    }
    
    /**
     * AJAX handler for voting on characters
     */
    public function ajax_vote_character() {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_voting_nonce')) {
                wp_send_json_error(array('message' => 'Security verification failed'));
            }
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'User must be logged in to vote'));
            }
            
            // Get current user
            $user_id = get_current_user_id();
            
            // Validate input
            $character_filename = sanitize_text_field($_POST['character_filename'] ?? '');
            $vote_type = sanitize_text_field($_POST['vote_type'] ?? '');
            
            if (empty($character_filename)) {
                wp_send_json_error(array('message' => 'Character filename is required'));
            }
            
            if (!in_array($vote_type, array('upvote', 'downvote'))) {
                wp_send_json_error(array('message' => 'Invalid vote type'));
            }
            
            // Check if user already voted on this character
            $existing_vote = $this->get_user_vote($user_id, $character_filename);
            
            if ($existing_vote) {
                // Update existing vote
                if ($existing_vote->vote_type === $vote_type) {
                    // Remove vote if clicking same type
                    $this->remove_vote($user_id, $character_filename);
                    $action = 'removed';
                } else {
                    // Change vote type
                    $this->update_vote($user_id, $character_filename, $vote_type);
                    $action = 'changed';
                }
            } else {
                // Create new vote
                $this->create_vote($user_id, $character_filename, $vote_type);
                $action = 'created';
            }
            
            // Get updated vote stats
            $vote_stats = $this->get_character_vote_stats($character_filename);
            
            wp_send_json_success(array(
                'message' => 'Vote ' . $action . ' successfully',
                'action' => $action,
                'vote_stats' => $vote_stats,
                'user_vote' => $this->get_user_vote($user_id, $character_filename)
            ));
            
        } catch (Exception $e) {
            error_log('PMV Voting Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for getting vote statistics
     */
    public function ajax_get_vote_stats() {
        try {
            // For vote stats, we don't require nonce verification for non-logged-in users
            // Only require nonce for logged-in users to prevent CSRF
            if (is_user_logged_in()) {
                if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_voting_nonce')) {
                    wp_send_json_error(array('message' => 'Security verification failed'));
                }
            }
            
            $character_filename = sanitize_text_field($_POST['character_filename'] ?? '');
            
            if (empty($character_filename)) {
                wp_send_json_error(array('message' => 'Character filename is required'));
            }
            
            $vote_stats = $this->get_character_vote_stats($character_filename);
            $user_vote = null;
            
            if (is_user_logged_in()) {
                $user_vote = $this->get_user_vote(get_current_user_id(), $character_filename);
            }
            
            // Debug logging
            error_log('PMV Voting: AJAX response - vote_stats: ' . print_r($vote_stats, true));
            error_log('PMV Voting: AJAX response - user_vote: ' . print_r($user_vote, true));
            
            wp_send_json_success(array(
                'vote_stats' => $vote_stats,
                'user_vote' => $user_vote
            ));
            
        } catch (Exception $e) {
            error_log('PMV Voting Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Get user's vote on a specific character
     */
    private function get_user_vote($user_id, $character_filename) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->votes_table} WHERE user_id = %d AND character_filename = %s",
            $user_id,
            $character_filename
        ));
        
        return $result;
    }
    
    /**
     * Create a new vote
     */
    private function create_vote($user_id, $character_filename, $vote_type) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->votes_table,
            array(
                'user_id' => $user_id,
                'character_filename' => $character_filename,
                'vote_type' => $vote_type
            ),
            array('%d', '%s', '%s')
        );
        
        if ($result === false) {
            throw new Exception('Failed to create vote');
        }
        
        return $result;
    }
    
    /**
     * Update an existing vote
     */
    private function update_vote($user_id, $character_filename, $vote_type) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->votes_table,
            array('vote_type' => $vote_type),
            array(
                'user_id' => $user_id,
                'character_filename' => $character_filename
            ),
            array('%s'),
            array('%d', '%s')
        );
        
        if ($result === false) {
            throw new Exception('Failed to update vote');
        }
        
        return $result;
    }
    
    /**
     * Remove a vote
     */
    private function remove_vote($user_id, $character_filename) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->votes_table,
            array(
                'user_id' => $user_id,
                'character_filename' => $character_filename
            ),
            array('%d', '%s')
        );
        
        if ($result === false) {
            throw new Exception('Failed to remove vote');
        }
        
        return $result;
    }
    
    /**
     * Get vote statistics for a character
     */
    public function get_character_vote_stats($character_filename) {
        global $wpdb;
        
        // Debug logging
        error_log('PMV Voting: Getting vote stats for character: ' . $character_filename);
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN vote_type = 'upvote' THEN 1 END) as upvotes,
                COUNT(CASE WHEN vote_type = 'downvote' THEN 1 END) as downvotes,
                COUNT(*) as total_votes
            FROM {$this->votes_table} 
            WHERE character_filename = %s",
            $character_filename
        ));
        
        // Debug logging
        error_log('PMV Voting: Raw stats result: ' . print_r($stats, true));
        
        if (!$stats) {
            error_log('PMV Voting: No stats found, returning defaults');
            return array(
                'upvotes' => 0,
                'downvotes' => 0,
                'total_votes' => 0,
                'score' => 0
            );
        }
        
        $score = intval($stats->upvotes) - intval($stats->downvotes);
        
        $result = array(
            'upvotes' => intval($stats->upvotes),
            'downvotes' => intval($stats->downvotes),
            'total_votes' => intval($stats->total_votes),
            'score' => $score
        );
        
        error_log('PMV Voting: Final stats: ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * Add vote sorting options to character cards
     */
    public function add_vote_sort_options($sort_options) {
        $sort_options['votes_popular'] = 'Most Popular';
        $sort_options['votes_hated'] = 'Most Hated';
        $sort_options['votes_recent'] = 'Recently Voted';
        return $sort_options;
    }
    
    /**
     * Apply vote-based sorting to character cards query
     */
    public function apply_vote_sorting($query, $sort_by) {
        if (in_array($sort_by, array('votes_popular', 'votes_hated', 'votes_recent'))) {
            // This will be handled in the cache handler when processing results
            $query['sort_by'] = $sort_by;
        }
        return $query;
    }
    
    /**
     * Sort character cards by vote statistics
     */
    public function sort_cards_by_votes($cards, $sort_by) {
        if (empty($cards) || !in_array($sort_by, array('votes_popular', 'votes_hated', 'votes_recent'))) {
            return $cards;
        }
        
        // Get vote stats for all cards
        $cards_with_votes = array();
        foreach ($cards as $card) {
            $filename = basename($card['image_url']);
            $vote_stats = $this->get_character_vote_stats($filename);
            $card['vote_stats'] = $vote_stats;
            $cards_with_votes[] = $card;
        }
        
        // Sort based on vote criteria
        switch ($sort_by) {
            case 'votes_popular':
                usort($cards_with_votes, function($a, $b) {
                    $upvotes_a = isset($a['vote_stats']['upvotes']) ? $a['vote_stats']['upvotes'] : 0;
                    $upvotes_b = isset($b['vote_stats']['upvotes']) ? $b['vote_stats']['upvotes'] : 0;
                    $downvotes_a = isset($a['vote_stats']['downvotes']) ? $a['vote_stats']['downvotes'] : 0;
                    $downvotes_b = isset($b['vote_stats']['downvotes']) ? $b['vote_stats']['downvotes'] : 0;
                    
                    // Calculate net votes (upvotes - downvotes)
                    $net_a = $upvotes_a - $downvotes_a;
                    $net_b = $upvotes_b - $downvotes_b;
                    
                    // First sort by net votes (positive first, then zero, then negative)
                    if ($net_a !== $net_b) {
                        return $net_b - $net_a;
                    }
                    
                    // If net votes are equal, sort by total upvotes (descending)
                    if ($upvotes_a !== $upvotes_b) {
                        return $upvotes_b - $upvotes_a;
                    }
                    
                    // If upvotes are equal, sort by fewer downvotes (ascending)
                    if ($downvotes_a !== $downvotes_b) {
                        return $downvotes_a - $downvotes_b;
                    }
                    
                    return 0;
                });
                break;
                
            case 'votes_hated':
                usort($cards_with_votes, function($a, $b) {
                    $upvotes_a = isset($a['vote_stats']['upvotes']) ? $a['vote_stats']['upvotes'] : 0;
                    $upvotes_b = isset($b['vote_stats']['upvotes']) ? $b['vote_stats']['upvotes'] : 0;
                    $downvotes_a = isset($a['vote_stats']['downvotes']) ? $a['vote_stats']['downvotes'] : 0;
                    $downvotes_b = isset($b['vote_stats']['downvotes']) ? $b['vote_stats']['downvotes'] : 0;
                    
                    // First sort by downvotes (descending)
                    if ($downvotes_a !== $downvotes_b) {
                        return $downvotes_b - $downvotes_a;
                    }
                    
                    // If downvotes are equal, sort by upvotes (ascending - fewer upvotes first)
                    if ($upvotes_a !== $upvotes_b) {
                        return $upvotes_a - $upvotes_b;
                    }
                    
                    return 0;
                });
                break;
                
            case 'votes_recent':
                // This would require storing timestamps in the votes table
                // For now, sort by total votes as a fallback
                usort($cards_with_votes, function($a, $b) {
                    $votes_a = isset($a['vote_stats']['total_votes']) ? $a['vote_stats']['total_votes'] : 0;
                    $votes_b = isset($b['vote_stats']['total_votes']) ? $b['vote_stats']['total_votes'] : 0;
                    return $votes_b - $votes_a;
                });
                break;
        }
        
        return $cards_with_votes;
    }
}

// Initialize the voting system only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    global $pmv_voting_system;
    $pmv_voting_system = new PMV_Voting_System();
} 