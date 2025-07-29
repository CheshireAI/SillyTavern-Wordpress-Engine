<?php
/**
 * PNG Metadata Viewer Integration Script
 * 
 * This script should be added to your theme's functions.php or a custom plugin file
 * to integrate the enhanced filtering and display features.
 */

// Make sure we're not directly accessed
if (!defined('ABSPATH')) {
    exit;
}

// Include image generation handlers
require_once plugin_dir_path(__FILE__) . 'includes/swarmui-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/nanogpt-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/unified-image-handler.php';

/**
 * Enqueue Select2 library for enhanced dropdown filtering
 */
function pmv_enqueue_select2() {
    // Only load on pages with our shortcode
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'png_metadata_viewer')) {
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_select2');

/**
 * Add enhanced tag extraction to the metadata reader
 * 
 * This function hooks into the metadata extraction process to collect
 * and categorize tags for filtering purposes.
 */
function pmv_process_tags_for_filtering($metadata, $file_path) {
    // Skip if no metadata or no data field
    if (!isset($metadata['data'])) {
        return $metadata;
    }
    
    // Process tags
    $tags = array();
    if (isset($metadata['data']['tags'])) {
        if (is_array($metadata['data']['tags'])) {
            $tags = $metadata['data']['tags'];
        } elseif (is_string($metadata['data']['tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $metadata['data']['tags'])));
        }
    }
    
    // Add creator as a tag if it exists
    if (!empty($metadata['data']['creator'])) {
        $creator = trim($metadata['data']['creator']);
        if (!in_array($creator, $tags)) {
            $tags[] = $creator;
        }
    }
    
    // Categorize tags for filter groups (optional)
    $categorized_tags = array(
        'categories' => array(),
        'character_types' => array(),
        'creators' => array()
    );
    
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (empty($tag)) continue;
        
        // You can customize this logic based on your tagging system
        if (strpos(strtolower($tag), 'character') !== false || 
            strpos(strtolower($tag), 'personality') !== false) {
            $categorized_tags['character_types'][] = $tag;
        } elseif (strpos(strtolower($tag), 'creator') !== false || 
                 strpos(strtolower($tag), 'author') !== false ||
                 $tag === $metadata['data']['creator']) {
            $categorized_tags['creators'][] = $tag;
        } else {
            $categorized_tags['categories'][] = $tag;
        }
    }
    
    // Store the categorized tags for potential future use
    $metadata['data']['_categorized_tags'] = $categorized_tags;
    $metadata['data']['tags'] = $tags; // Ensure tags is updated
    
    return $metadata;
}
add_filter('pmv_process_metadata', 'pmv_process_tags_for_filtering', 10, 2);

/**
 * Add character book enhancements to AJAX chat handling
 *
 * This function ensures character book data is properly included in the chat context
 */
function pmv_enhance_chat_context($messages, $character_data, $user_message) {
    // If no character book or no entries, return unchanged
    if (!isset($character_data['data']['character_book']) || 
        !isset($character_data['data']['character_book']['entries']) ||
        empty($character_data['data']['character_book']['entries'])) {
        return $messages;
    }
    
    // Find system message index (usually first message)
    $system_index = null;
    foreach ($messages as $i => $message) {
        if ($message['role'] === 'system') {
            $system_index = $i;
            break;
        }
    }
    
    if ($system_index === null) {
        return $messages; // No system message found
    }
    
    // Process character book entries
    $relevant_entries = array();
    foreach ($character_data['data']['character_book']['entries'] as $entry) {
        // Skip disabled entries
        if (isset($entry['enabled']) && $entry['enabled'] === false) {
            continue;
        }
        
        // Check if any of the entry keys are contained in the user message
        $keys = isset($entry['keys']) && is_array($entry['keys']) ? $entry['keys'] : array();
        $is_relevant = false;
        
        foreach ($keys as $key) {
            // Check case sensitivity
            if (isset($entry['case_sensitive']) && $entry['case_sensitive']) {
                $is_relevant = strpos($user_message, $key) !== false;
            } else {
                $is_relevant = stripos($user_message, $key) !== false;
            }
            
            if ($is_relevant) {
                break;
            }
        }
        
        if ($is_relevant && !empty($entry['content'])) {
            $relevant_entries[] = array(
                'keys' => is_array($entry['keys']) ? implode(', ', $entry['keys']) : $entry['keys'],
                'content' => $entry['content'],
                'order' => isset($entry['insertion_order']) ? intval($entry['insertion_order']) : 0
            );
        }
    }
    
    // Sort entries by insertion order
    usort($relevant_entries, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    // Add relevant entries to system message
    if (!empty($relevant_entries)) {
        $lorebook_content = "\n\nRelevant context from character's knowledge base:\n";
        
        foreach ($relevant_entries as $entry) {
            $lorebook_content .= "- {$entry['content']}\n\n";
        }
        
        // Append to system message
        $messages[$system_index]['content'] .= $lorebook_content;
    }
    
    return $messages;
}
add_filter('pmv_chat_messages', 'pmv_enhance_chat_context', 10, 3);

/**
 * Add debugging tools for administrators
 */
function pmv_add_debug_info() {
    if (!current_user_can('administrator') || !is_admin()) {
        return;
    }
    
    // Add debug tab to admin page
    add_filter('pmv_admin_tabs', function($tabs) {
        $tabs['debug'] = 'Debug Tools';
        return $tabs;
    });
    
    // Add debug tab content
    add_action('pmv_admin_tab_content_debug', function() {
        ?>
        <h3>PNG Metadata Viewer Debugging Tools</h3>
        
        <div class="card" style="padding: 15px; margin-bottom: 20px;">
            <h4>Extract and Display Metadata</h4>
            <p>Select a PNG file to extract and display its metadata structure:</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pmv_debug_extract', 'pmv_debug_nonce'); ?>
                <input type="file" name="pmv_debug_file" accept=".png">
                <input type="submit" name="pmv_debug_extract" class="button button-primary" value="Extract Metadata">
            </form>
        </div>
        
        <?php
        // Handle metadata extraction
        if (isset($_POST['pmv_debug_extract']) && wp_verify_nonce($_POST['pmv_debug_nonce'], 'pmv_debug_extract')) {
            if (isset($_FILES['pmv_debug_file']) && $_FILES['pmv_debug_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    require_once(PMV_PLUGIN_DIR . 'includes/metadata-reader.php');
                    
                    $tmp_file = $_FILES['pmv_debug_file']['tmp_name'];
                    $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($tmp_file);
                    
                    echo '<div class="card" style="padding: 15px;">';
                    echo '<h4>Metadata Structure for: ' . esc_html($_FILES['pmv_debug_file']['name']) . '</h4>';
                    echo '<pre style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 500px;">';
                    print_r($metadata);
                    echo '</pre>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="notice notice-error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Please select a valid PNG file.</p></div>';
            }
        }
    });
}
add_action('init', 'pmv_add_debug_info');

/**
 * Register custom styles for enhanced display
 */
function pmv_register_enhanced_styles() {
    // Remove this function to prevent conflicts with main plugin enqueuing
    // The enhanced styles are already enqueued in the main plugin file
    return;
    
    // wp_register_style(
    //     'pmv-enhanced-styles',
    //     plugins_url('enhanced-styles.css', __FILE__),
    //     array(),
    //     filemtime(plugin_dir_path(__FILE__) . 'enhanced-styles.css')
    // );
    
    // Only enqueue on pages with our shortcode
    // global $post;
    // if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'png_metadata_viewer')) {
    //     wp_enqueue_style('pmv-enhanced-styles');
    // }
}
add_action('wp_enqueue_scripts', 'pmv_register_enhanced_styles');

/**
 * Add AJAX handler for tag filtering
 */
function pmv_filter_tags_ajax_handler() {
    // Security check
    check_ajax_referer('pmv_ajax_nonce', 'nonce');
    
    $filter_data = isset($_POST['filter_data']) ? json_decode(stripslashes($_POST['filter_data']), true) : array();
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    // Get upload directory
    $upload_dir = wp_upload_dir();
    $png_cards_dir = $upload_dir['basedir'] . '/png-cards/';
    $url_base = $upload_dir['baseurl'] . '/png-cards/';
    
    // Get all PNG files
    $all_files = glob($png_cards_dir . '*.png');
    
    // Apply filters
    $filtered_files = array();
    foreach ($all_files as $file) {
        try {
            $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            
            // Skip if no tags
            if (!isset($metadata['data']['tags'])) {
                continue;
            }
            
            // Get tags
            $tags = $metadata['data']['tags'];
            if (!is_array($tags)) {
                if (is_string($tags)) {
                    $tags = array_filter(array_map('trim', explode(',', $tags)));
                } else {
                    $tags = array();
                }
            }
            
            // Add creator as tag
            if (!empty($metadata['data']['creator'])) {
                $tags[] = $metadata['data']['creator'];
            }
            
            // Check if file matches all filter groups
            $matches_all = true;
            foreach ($filter_data as $filter_type => $selected_tags) {
                if (empty($selected_tags)) {
                    continue; // Skip empty filters
                }
                
                // Check if file has at least one tag from this filter group
                $matches_group = false;
                foreach ($selected_tags as $tag) {
                    if (in_array($tag, $tags)) {
                        $matches_group = true;
                        break;
                    }
                }
                
                if (!$matches_group) {
                    $matches_all = false;
                    break;
                }
            }
            
            if ($matches_all) {
                $filtered_files[] = $file;
            }
            
        } catch (Exception $e) {
            // Skip files with errors
            continue;
        }
    }
    
    // Pagination
    $per_page = (int)get_option('png_metadata_cards_per_page', 12);
    $total_filtered = count($filtered_files);
    $total_pages = ceil($total_filtered / $per_page);
    $offset = ($page - 1) * $per_page;
    $page_files = array_slice($filtered_files, $offset, $per_page);
    
    // Build card HTML
    $cards_html = '';
    foreach ($page_files as $file) {
        try {
            $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            $file_url = $url_base . basename($file);
            
            // Generate card HTML (similar to shortcode function)
            // [HTML generation code would go here]
            $cards_html .= '...'; // Abbreviated for response
            
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Return data
    wp_send_json_success(array(
        'cards' => $cards_html,
        'total' => $total_filtered,
        'pages' => $total_pages,
        'current_page' => $page
    ));
}
add_action('wp_ajax_pmv_filter_tags', 'pmv_filter_tags_ajax_handler');
add_action('wp_ajax_nopriv_pmv_filter_tags', 'pmv_filter_tags_ajax_handler');
