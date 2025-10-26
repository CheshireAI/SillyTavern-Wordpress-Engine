<?php
/**
 * PNG Metadata Viewer Cache Handler
 * Implements WordPress Transients API with Redis/Object Cache support
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * PNG Metadata Viewer Cache Handler
 * Implements WordPress Transients API with Redis/Object Cache support
 */
class PMV_Cache_Handler {
    
    private static $instance = null;
    private $cache_prefix = 'pmv_cache_';
    private $cache_version = '1.0';
    private $cache_time = 3600; // 1 hour default
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached character cards
     */
    public function get_cached_cards($cache_key) {
        // Check if object caching is available (Redis, Memcached, etc.)
        if (wp_using_ext_object_cache()) {
            $cached = wp_cache_get($cache_key, 'pmv_cards');
            if ($cached !== false) {
                error_log('PMV Cache: Retrieved from object cache');
                return $cached;
            }
        }
        
        // Fallback to transients (database cache)
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log('PMV Cache: Retrieved from transient cache');
            return $cached;
        }
        
        return false;
    }
    
    /**
     * Set cached character cards
     */
    public function set_cached_cards($cache_key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = $this->cache_time;
        }
        
        // Use object cache if available
        if (wp_using_ext_object_cache()) {
            wp_cache_set($cache_key, $data, 'pmv_cards', $expiration);
            error_log('PMV Cache: Stored in object cache');
        }
        
        // Always set transient as fallback
        set_transient($cache_key, $data, $expiration);
        error_log('PMV Cache: Stored in transient cache');
        
        return true;
    }
    
    /**
     * Generate cache key based on parameters
     */
    public function generate_cache_key($params) {
        $key_parts = array(
            $this->cache_prefix,
            $this->cache_version,
            md5(serialize($params))
        );
        
        return implode('_', $key_parts);
    }
    
    /**
     * Clear all PMV caches
     */
    public function clear_all_caches() {
        global $wpdb;
        
        // Clear transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . '%'
            )
        );
        
        // Clear object cache if available
        if (wp_using_ext_object_cache()) {
            wp_cache_flush_group('pmv_cards');
        }
        
        // Clear page cache if W3TC or similar is active
        if (function_exists('w3tc_flush_posts')) {
            w3tc_flush_posts();
        }
        
        error_log('PMV Cache: All caches cleared');
        
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $stats = array(
            'object_cache_enabled' => wp_using_ext_object_cache(),
            'redis_available' => class_exists('Redis') || defined('WP_REDIS_HOST'),
            'transient_count' => 0,
            'estimated_size' => 0
        );
        
        // Count PMV transients
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        $stats['transient_count'] = intval($count);
        
        return $stats;
    }
}

// Enhanced AJAX handler with caching
add_action('wp_ajax_pmv_get_character_cards', 'pmv_get_character_cards_cached');
add_action('wp_ajax_nopriv_pmv_get_character_cards', 'pmv_get_character_cards_cached');

function pmv_get_character_cards_cached() {
    try {
        // Debug: Log the start of the AJAX request
        error_log('PMV Cache: AJAX request started');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            error_log('PMV Cache: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security verification failed'));
        }
        
        error_log('PMV Cache: Nonce verification passed');
        
        // Get cache handler
        $cache_handler = PMV_Cache_Handler::get_instance();
        
        // Build cache key from request parameters
        $cache_params = array(
            'page' => max(1, intval($_POST['page'] ?? 1)),
            'per_page' => max(1, intval($_POST['per_page'] ?? 12)),
            'folder' => sanitize_text_field($_POST['folder'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'filter1' => sanitize_text_field($_POST['filter1'] ?? ''),
            'filter2' => sanitize_text_field($_POST['filter2'] ?? ''),
            'filter3' => sanitize_text_field($_POST['filter3'] ?? ''),
            'filter4' => sanitize_text_field($_POST['filter4'] ?? ''),
            'sort_by' => sanitize_text_field($_POST['sort_by'] ?? '')
        );
        
        error_log('PMV Cache: Parameters: ' . json_encode($cache_params));
        error_log('PMV Cache: Sort by parameter: ' . ($cache_params['sort_by'] ?: 'NOT SET'));
        
        // Generate cache key
        $cache_key = $cache_handler->generate_cache_key($cache_params);
        error_log('PMV Cache: Cache key: ' . $cache_key);
        
        // Try to get from cache
        $cached_response = $cache_handler->get_cached_cards($cache_key);
        
        // Skip cache for vote-based sorting to ensure fresh results
        if ($cached_response !== false && empty($cache_params['sort_by'])) {
            error_log('PMV Cache: Cache hit, returning cached response');
            // Add cache hit header
            $cached_response['cache_hit'] = true;
            wp_send_json_success($cached_response);
            return;
        }
        
        if ($cached_response !== false && !empty($cache_params['sort_by'])) {
            error_log('PMV Cache: Cache hit but bypassing for vote-based sorting: ' . $cache_params['sort_by']);
        }
        
        error_log('PMV Cache: Cache miss, processing cards');
        
        // Not in cache, process normally
        $response = pmv_process_character_cards($cache_params);
        
        if ($response) {
            error_log('PMV Cache: Cards processed successfully, caching response');
            // Cache the response
            $cache_handler->set_cached_cards($cache_key, $response);
            
            // Add cache miss header
            $response['cache_hit'] = false;
            wp_send_json_success($response);
        } else {
            error_log('PMV Cache: Failed to process cards');
            wp_send_json_error(array('message' => 'Failed to process cards'));
        }
        
    } catch (Exception $e) {
        error_log('PMV Cache Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// Function to process character cards (extracted from original)
function pmv_process_character_cards($params) {
    $start_time = microtime(true);
    
    error_log('PMV Cache: Starting character card processing');
    
    // Get upload directory
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    $png_cards_url = trailingslashit($upload_dir['baseurl']) . 'png-cards/';
    
    // If folder specified, look in subfolder
    if (!empty($params['folder'])) {
        $png_cards_dir .= trailingslashit(sanitize_file_name($params['folder']));
        $png_cards_url .= trailingslashit(sanitize_file_name($params['folder']));
    }
    
    // Check if directory exists
    if (!file_exists($png_cards_dir)) {
        error_log('PMV Cache: Directory not found: ' . $png_cards_dir);
        return false;
    }
    
    // Get all PNG files with error handling
    $all_files = glob($png_cards_dir . '*.png');
    
    if (empty($all_files)) {
        error_log('PMV Cache: No PNG files found in: ' . $png_cards_dir);
        return array(
            'cards' => array(),
            'pagination' => array(
                'total_items' => 0,
                'total_pages' => 0,
                'current_page' => 1,
                'per_page' => $params['per_page'],
                'has_prev' => false,
                'has_next' => false
            )
        );
    }
    
    error_log('PMV Cache: Processing ' . count($all_files) . ' files');
    
    // Process files and apply filters
    $all_characters = array();
    $processed_count = 0;
    $error_count = 0;
    
    foreach ($all_files as $file) {
        try {
            // Extract metadata with timeout protection
            $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            
            if (empty($metadata)) {
                $error_count++;
                continue;
            }
            
            // Extract tags efficiently
            $tags = array();
            if (isset($metadata['data']['tags'])) {
                if (is_array($metadata['data']['tags'])) {
                    $tags = $metadata['data']['tags'];
                } elseif (is_string($metadata['data']['tags'])) {
                    $tags = array_filter(array_map('trim', explode(',', $metadata['data']['tags'])));
                }
            }
            
            // Add creator as tag
            if (!empty($metadata['data']['creator'])) {
                $creator = trim($metadata['data']['creator']);
                if (!in_array($creator, $tags)) {
                    $tags[] = $creator;
                }
            }
            
            // Get name and description efficiently
            $name = isset($metadata['data']['name']) ? $metadata['data']['name'] : 
                   (isset($metadata['name']) ? $metadata['name'] : basename($file, '.png'));
            
            $description = isset($metadata['data']['description']) ? $metadata['data']['description'] : 
                          (isset($metadata['description']) ? $metadata['description'] : '');
            
            $all_characters[] = array(
                'file' => $file,
                'file_url' => $png_cards_url . basename($file),
                'metadata' => $metadata,
                'tags' => $tags,
                'name' => $name,
                'description' => $description
            );
            
            $processed_count++;
            
        } catch (Exception $e) {
            error_log('PMV Cache: Error processing file ' . basename($file) . ': ' . $e->getMessage());
            $error_count++;
            continue;
        }
    }
    
    error_log('PMV Cache: Successfully processed ' . $processed_count . ' files, ' . $error_count . ' errors');
    
    // Apply filters efficiently
    $filtered_characters = $all_characters;
    
    // Category filter
    if (!empty($params['category'])) {
        $category = strtolower(trim($params['category']));
        $filtered_characters = array_filter($filtered_characters, function($char) use ($category) {
            foreach ($char['tags'] as $tag) {
                if (strtolower(trim($tag)) === $category) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Apply other filters efficiently
    foreach (array('filter1', 'filter2', 'filter3', 'filter4') as $filter_key) {
        if (!empty($params[$filter_key])) {
            $filter_value = strtolower(trim($params[$filter_key]));
            $filtered_characters = array_filter($filtered_characters, function($char) use ($filter_value) {
                foreach ($char['tags'] as $tag) {
                    if (strtolower(trim($tag)) === $filter_value) {
                        return true;
                    }
                }
                return false;
            });
        }
    }
    
    // Apply search efficiently
    if (!empty($params['search'])) {
        $search_term = strtolower(trim($params['search']));
        $filtered_characters = array_filter($filtered_characters, function($char) use ($search_term) {
            $searchable = strtolower($char['name'] . ' ' . $char['description'] . ' ' . implode(' ', $char['tags']));
            return strpos($searchable, $search_term) !== false;
        });
    }
    
            // Apply vote-based sorting if requested
        if (!empty($params['sort_by']) && in_array($params['sort_by'], array('votes_popular', 'votes_hated', 'votes_recent'))) {
            error_log('PMV Cache: About to apply vote sorting for: ' . $params['sort_by']);
            error_log('PMV Cache: Characters before sorting: ' . count($filtered_characters));
            
            if (function_exists('pmv_sort_characters_by_votes')) {
                try {
                    $filtered_characters = pmv_sort_characters_by_votes($filtered_characters, $params['sort_by']);
                    error_log('PMV Cache: Vote sorting applied successfully for ' . $params['sort_by']);
                    error_log('PMV Cache: Characters after sorting: ' . count($filtered_characters));
                    
                    // Debug: Log first few characters after sorting
                    $debug_count = min(3, count($filtered_characters));
                    for ($i = 0; $i < $debug_count; $i++) {
                        $char = $filtered_characters[$i];
                        $upvotes = isset($char['vote_stats']['upvotes']) ? $char['vote_stats']['upvotes'] : 0;
                        $downvotes = isset($char['vote_stats']['downvotes']) ? $char['vote_stats']['downvotes'] : 0;
                        $net = $upvotes - $downvotes;
                        error_log('PMV Cache: After sorting, char ' . ($i+1) . ': ' . basename($char['file_url']) . ' - Up: ' . $upvotes . ', Down: ' . $downvotes . ', Net: ' . $net);
                    }
                } catch (Exception $e) {
                    error_log('PMV Cache: Error in vote sorting: ' . $e->getMessage());
                }
            } else {
                error_log('PMV Cache: Vote sorting function not available');
            }
        } else {
            error_log('PMV Cache: No vote sorting requested or invalid sort_by: ' . ($params['sort_by'] ?? 'NOT SET'));
        }
        
        // Debug: Log the current state before pagination
        error_log('PMV Cache: Characters after filtering and sorting: ' . count($filtered_characters));
    
    // Calculate pagination
    $total = count($filtered_characters);
    $total_pages = ceil($total / $params['per_page']);
    $offset = ($params['page'] - 1) * $params['per_page'];
    
    // Get page of results
    $page_characters = array_slice($filtered_characters, $offset, $params['per_page']);
    
    // Format for response efficiently
    $cards = array();
    foreach ($page_characters as $char) {
        $cards[] = array(
            'file_url' => $char['file_url'],
            'name' => $char['name'],
            'description' => $char['description'], // FIXED: Remove truncation, show full description
            'tags' => $char['tags'],
            'metadata' => $char['metadata']
        );
    }
    
    $processing_time = microtime(true) - $start_time;
    error_log('PMV Cache: Processing completed in ' . round($processing_time, 3) . ' seconds');
    
    // Debug: Log the response structure
    $response = array(
        'cards' => $cards,
        'pagination' => array(
            'total_items' => $total,
            'total_pages' => $total_pages,
            'current_page' => $params['page'],
            'per_page' => $params['per_page'],
            'has_prev' => $params['page'] > 1,
            'has_next' => $params['page'] < $total_pages
        ),
        'performance' => array(
            'processing_time' => round($processing_time, 3),
            'total_files' => count($all_files),
            'processed_files' => $processed_count,
            'error_count' => $error_count
        )
    );
    
    error_log('PMV Cache: Response structure: ' . json_encode(array_keys($response)));
    error_log('PMV Cache: Cards count: ' . count($cards));
    
    return $response;
}

// Add cache clearing hooks
add_action('pmv_character_uploaded', array('PMV_Cache_Handler', 'clear_all_caches'));
add_action('pmv_character_deleted', array('PMV_Cache_Handler', 'clear_all_caches'));
add_action('pmv_settings_updated', array('PMV_Cache_Handler', 'clear_all_caches'));

/**
 * Sort characters by vote statistics
 */
function pmv_sort_characters_by_votes($characters, $sort_by) {
    try {
        if (empty($characters) || !in_array($sort_by, array('votes_popular', 'votes_hated', 'votes_recent'))) {
            return $characters;
        }
        
        // Check if vote stats function is available
        if (!function_exists('pmv_get_character_vote_stats')) {
            error_log('PMV Cache: Vote stats function not available');
            return $characters;
        }
        
        // Get vote stats for all characters
        $characters_with_votes = array();
        foreach ($characters as $char) {
            // Extract filename from file_url or use a fallback
            $filename = '';
            if (isset($char['file_url'])) {
                $filename = basename($char['file_url']);
            } elseif (isset($char['file'])) {
                $filename = basename($char['file']);
            } else {
                // Skip this character if we can't get a filename
                $characters_with_votes[] = $char;
                continue;
            }
            
            error_log('PMV Cache: Processing character file: ' . $filename);
            
            try {
                $vote_stats = pmv_get_character_vote_stats($filename);
                $char['vote_stats'] = $vote_stats;
                error_log('PMV Cache: Vote stats for ' . $filename . ': ' . print_r($vote_stats, true));
                
                // Debug: Log the actual values being used for sorting
                $upvotes = isset($vote_stats['upvotes']) ? $vote_stats['upvotes'] : 0;
                $downvotes = isset($vote_stats['downvotes']) ? $vote_stats['downvotes'] : 0;
                $net = $upvotes - $downvotes;
                error_log('PMV Cache: Character ' . $filename . ' - Up: ' . $upvotes . ', Down: ' . $downvotes . ', Net: ' . $net);
                
            } catch (Exception $e) {
                error_log('PMV Cache: Error getting vote stats for ' . $filename . ': ' . $e->getMessage());
                $char['vote_stats'] = array('upvotes' => 0, 'downvotes' => 0, 'total_votes' => 0, 'score' => 0);
            }
            
            $characters_with_votes[] = $char;
        }
        
        // Sort based on vote criteria
        error_log('PMV Cache: Sorting by: ' . $sort_by);
        error_log('PMV Cache: Characters before sorting: ' . count($characters_with_votes));
        
        switch ($sort_by) {
            case 'votes_popular':
                error_log('PMV Cache: Sorting by popularity (positive votes first, then zero, then negative)');
                usort($characters_with_votes, function($a, $b) {
                    $upvotes_a = isset($a['vote_stats']['upvotes']) ? $a['vote_stats']['upvotes'] : 0;
                    $upvotes_b = isset($b['vote_stats']['upvotes']) ? $b['vote_stats']['upvotes'] : 0;
                    $downvotes_a = isset($a['vote_stats']['downvotes']) ? $a['vote_stats']['downvotes'] : 0;
                    $downvotes_b = isset($b['vote_stats']['downvotes']) ? $b['vote_stats']['downvotes'] : 0;
                    
                    // Calculate net votes (upvotes - downvotes)
                    $net_a = $upvotes_a - $downvotes_a;
                    $net_b = $upvotes_b - $downvotes_b;
                    
                    // First sort by net votes (positive first, then zero, then negative)
                    if ($net_a !== $net_b) {
                        error_log('PMV Cache: Comparing net votes: ' . $net_a . ' vs ' . $net_b . ' for ' . basename($a['file_url']) . ' vs ' . basename($b['file_url']));
                        return $net_b - $net_a;
                    }
                    
                    // If net votes are equal, sort by total upvotes (descending)
                    if ($upvotes_a !== $upvotes_b) {
                        error_log('PMV Cache: Net votes equal, comparing upvotes: ' . $upvotes_a . ' vs ' . $upvotes_b . ' for ' . basename($a['file_url']) . ' vs ' . basename($b['file_url']));
                        return $upvotes_b - $upvotes_a;
                    }
                    
                    // If upvotes are equal, sort by fewer downvotes (ascending)
                    if ($downvotes_a !== $downvotes_b) {
                        error_log('PMV Cache: Upvotes equal, comparing downvotes: ' . $downvotes_a . ' vs ' . $downvotes_b . ' for ' . basename($a['file_url']) . ' vs ' . basename($b['file_url']));
                        return $downvotes_a - $downvotes_b;
                    }
                    
                    return 0;
                });
                break;
                
            case 'votes_hated':
                error_log('PMV Cache: Sorting by most hated (downvotes first, then 0, then upvotes)');
                usort($characters_with_votes, function($a, $b) {
                    $upvotes_a = isset($a['vote_stats']['upvotes']) ? $a['vote_stats']['upvotes'] : 0;
                    $upvotes_b = isset($b['vote_stats']['upvotes']) ? $b['vote_stats']['upvotes'] : 0;
                    $downvotes_a = isset($a['vote_stats']['downvotes']) ? $a['vote_stats']['downvotes'] : 0;
                    $downvotes_b = isset($b['vote_stats']['downvotes']) ? $b['vote_stats']['downvotes'] : 0;
                    
                    // First sort by downvotes (descending)
                    if ($downvotes_a !== $downvotes_b) {
                        error_log('PMV Cache: Comparing downvotes: ' . $downvotes_a . ' vs ' . $downvotes_b . ' for ' . basename($a['file_url']) . ' vs ' . basename($b['file_url']));
                        return $downvotes_b - $downvotes_a;
                    }
                    
                    // If downvotes are equal, sort by upvotes (ascending - fewer upvotes first)
                    if ($upvotes_a !== $upvotes_b) {
                        error_log('PMV Cache: Downvotes equal, comparing upvotes: ' . $upvotes_a . ' vs ' . $upvotes_b . ' for ' . basename($a['file_url']) . ' vs ' . basename($b['file_url']));
                        return $upvotes_a - $upvotes_b;
                    }
                    
                    return 0;
                });
                break;
                
            case 'votes_recent':
                error_log('PMV Cache: Sorting by recent (fallback to total votes)');
                // This would require storing timestamps in the votes table
                // For now, sort by total votes as a fallback
                usort($characters_with_votes, function($a, $b) {
                    $votes_a = isset($a['vote_stats']['total_votes']) ? $a['vote_stats']['total_votes'] : 0;
                    $votes_b = isset($b['vote_stats']['total_votes']) ? $b['vote_stats']['total_votes'] : 0;
                    return $votes_b - $votes_a;
                });
                break;
        }
        
        error_log('PMV Cache: Characters after sorting: ' . count($characters_with_votes));
        
        return $characters_with_votes;
        
    } catch (Exception $e) {
        error_log('PMV Cache: Error in vote sorting: ' . $e->getMessage());
        return $characters; // Return original characters if sorting fails
    }
}

/**
 * Get vote statistics for a character (wrapper function)
 */
function pmv_get_character_vote_stats($character_filename) {
    // Check if voting system is available
    if (class_exists('PMV_Voting_System')) {
        try {
            // Get the global voting system instance instead of creating a new one
            global $pmv_voting_system;
            if (isset($pmv_voting_system) && is_object($pmv_voting_system)) {
                return $pmv_voting_system->get_character_vote_stats($character_filename);
            } else {
                // Fallback: try to get vote stats directly from database
                global $wpdb;
                $votes_table = $wpdb->prefix . 'pmv_character_votes';
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$votes_table'") === $votes_table) {
                    $stats = $wpdb->get_row($wpdb->prepare(
                        "SELECT 
                            COUNT(CASE WHEN vote_type = 'upvote' THEN 1 END) as upvotes,
                            COUNT(CASE WHEN vote_type = 'downvote' THEN 1 END) as downvotes,
                            COUNT(*) as total_votes
                        FROM {$votes_table} 
                        WHERE character_filename = %s",
                        $character_filename
                    ));
                    
                    if ($stats) {
                        $score = intval($stats->upvotes) - intval($stats->downvotes);
                        return array(
                            'upvotes' => intval($stats->upvotes),
                            'downvotes' => intval($stats->downvotes),
                            'total_votes' => intval($stats->total_votes),
                            'score' => $score
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log('PMV Cache: Error getting vote stats: ' . $e->getMessage());
        }
    }
    
    // Return default stats if voting system not available
    return array(
        'upvotes' => 0,
        'downvotes' => 0,
        'total_votes' => 0,
        'score' => 0
    );
}

// Add admin bar cache clear button
add_action('admin_bar_menu', 'pmv_add_cache_clear_button', 999);

function pmv_add_cache_clear_button($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $args = array(
        'id' => 'pmv-clear-cache',
        'title' => 'Clear PMV Cache',
        'href' => admin_url('admin-ajax.php?action=pmv_clear_cache&nonce=' . wp_create_nonce('pmv_clear_cache')),
        'meta' => array(
            'class' => 'pmv-clear-cache-button',
            'title' => 'Clear PNG Metadata Viewer Cache'
        )
    );
    
    $wp_admin_bar->add_node($args);
}

// AJAX handler for clearing cache
add_action('wp_ajax_pmv_clear_cache', 'pmv_ajax_clear_cache');

function pmv_ajax_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    if (!wp_verify_nonce($_GET['nonce'], 'pmv_clear_cache')) {
        wp_die('Invalid nonce');
    }
    
    $cache_handler = PMV_Cache_Handler::get_instance();
    $cache_handler->clear_all_caches();
    
    wp_redirect(wp_get_referer());
    exit;
}

// Add cache info to admin settings page
add_action('pmv_settings_page_footer', 'pmv_display_cache_info');

function pmv_display_cache_info() {
    $cache_handler = PMV_Cache_Handler::get_instance();
    $stats = $cache_handler->get_cache_stats();
    ?>
    <div class="pmv-cache-info" style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-radius: 5px;">
        <h3>Cache Information</h3>
        <p><strong>Object Cache:</strong> <?php echo $stats['object_cache_enabled'] ? '✅ Enabled' : '❌ Disabled'; ?></p>
        <p><strong>Redis:</strong> <?php echo $stats['redis_available'] ? '✅ Available' : '❌ Not Available'; ?></p>
        <p><strong>Cached Items:</strong> <?php echo $stats['transient_count']; ?></p>
        <p>
            <a href="<?php echo admin_url('admin-ajax.php?action=pmv_clear_cache&nonce=' . wp_create_nonce('pmv_clear_cache')); ?>" 
               class="button button-secondary">Clear All Caches</a>
        </p>
    </div>
    <?php
}

?> 