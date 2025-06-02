<?php
// includes/shortcodes.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Main shortcode handler for PNG metadata viewer with server-side pagination
function png_metadata_viewer_shortcode($atts) {
    // Normalize attributes
    $atts = shortcode_atts(array(
        'folder' => '',
        'category' => '',
        'hide_filters' => 'no',
        'cards_per_page' => get_option('png_metadata_cards_per_page', 12),
        'page' => 1, // Add page parameter
    ), $atts);
    
    // Handle parameters
    $folder = sanitize_text_field($atts['folder']);
    $category = sanitize_text_field($atts['category']);
    $hide_filters = ($atts['hide_filters'] === 'yes');
    $cards_per_page = intval($atts['cards_per_page']);
    $current_page = max(1, intval($_GET['pmv_page'] ?? $atts['page'])); // Get page from URL or shortcode
    
    // Ensure cards_per_page is at least 1
    if ($cards_per_page < 1) {
        $cards_per_page = 12;
    }
    
    // Get upload directory
    $upload_dir = wp_upload_dir();
    $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
    $png_cards_url = trailingslashit($upload_dir['baseurl']) . 'png-cards/';
    
    // If folder specified, look in subfolder
    if (!empty($folder)) {
        $png_cards_dir .= trailingslashit(sanitize_file_name($folder));
        $png_cards_url .= trailingslashit(sanitize_file_name($folder));
    }
    
    // Check if directory exists
    if (!file_exists($png_cards_dir)) {
        return '<p>PNG cards directory not found: ' . esc_html($png_cards_dir) . '</p>';
    }
    
    // Get all PNG files (but don't process them yet)
    $all_files = glob($png_cards_dir . '*.png');
    
    if (empty($all_files)) {
        return '<p>No PNG files found in the directory.</p>';
    }
    
    $total_files = count($all_files);
    $total_pages = ceil($total_files / $cards_per_page);
    
    // Ensure current page is valid
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }
    
    // Calculate offset for current page
    $offset = ($current_page - 1) * $cards_per_page;
    
    // Get only the files for the current page
    $files_for_page = array_slice($all_files, $offset, $cards_per_page);
    
    // Process tags for filtering - we still need all files for filters, but we can cache this
    $all_tags = get_transient('pmv_all_tags_' . md5($png_cards_dir));
    $filter_data = get_transient('pmv_filter_data_' . md5($png_cards_dir));
    
    if ($all_tags === false || $filter_data === false) {
        $all_tags = array();
        $filter1_tags = array();
        $filter2_tags = array();
        $filter3_tags = array();
        $filter4_tags = array();
        
        // Get filter tag lists from settings
        $filter1_list = get_option('png_metadata_filter1_list', '');
        $filter1_title = get_option('png_metadata_filter1_title', 'Category');
        if (!empty($filter1_list)) {
            $filter1_tags = array_map('trim', explode(',', $filter1_list));
        }
        
        $filter2_list = get_option('png_metadata_filter2_list', '');
        $filter2_title = get_option('png_metadata_filter2_title', 'Style');
        if (!empty($filter2_list)) {
            $filter2_tags = array_map('trim', explode(',', $filter2_list));
        }
        
        $filter3_list = get_option('png_metadata_filter3_list', '');
        $filter3_title = get_option('png_metadata_filter3_title', 'Creator');
        if (!empty($filter3_list)) {
            $filter3_tags = array_map('trim', explode(',', $filter3_list));
        }
        
        $filter4_list = get_option('png_metadata_filter4_list', '');
        $filter4_title = get_option('png_metadata_filter4_title', 'Version');
        if (!empty($filter4_list)) {
            $filter4_tags = array_map('trim', explode(',', $filter4_list));
        }
        
        // Cache for 1 hour - this prevents reprocessing all files for tag counting
        $filter_data = compact('filter1_tags', 'filter2_tags', 'filter3_tags', 'filter4_tags', 
                              'filter1_title', 'filter2_title', 'filter3_title', 'filter4_title');
        set_transient('pmv_filter_data_' . md5($png_cards_dir), $filter_data, HOUR_IN_SECONDS);
        set_transient('pmv_all_tags_' . md5($png_cards_dir), $all_tags, HOUR_IN_SECONDS);
    }
    
    extract($filter_data); // Extract filter variables
    
    // Process ONLY the files for the current page
    $characters = array();
    foreach ($files_for_page as $file) {
        try {
            // Extract metadata
            $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            
            // Skip processing if no metadata found
            if (empty($metadata)) {
                continue;
            }
            
            // Extract tags from character data
            $tags = array();
            if (isset($metadata['data']['tags'])) {
                if (is_array($metadata['data']['tags'])) {
                    $tags = $metadata['data']['tags'];
                } elseif (is_string($metadata['data']['tags'])) {
                    $tags = array_filter(array_map('trim', explode(',', $metadata['data']['tags'])));
                }
            }
            
            // Add creator as a tag if available
            if (!empty($metadata['data']['creator'])) {
                $creator = trim($metadata['data']['creator']);
                if (!in_array($creator, $tags)) {
                    $tags[] = $creator;
                }
            }
            
            // Store character data for rendering
            $characters[] = array(
                'file' => $file,
                'url' => $png_cards_url . basename($file),
                'metadata' => $metadata,
                'tags' => $tags
            );
            
        } catch (Exception $e) {
            // Skip files that can't be processed
            error_log('PNG Metadata error: ' . $e->getMessage() . ' in file: ' . basename($file));
            continue;
        }
    }
    
    // Filter by category if specified (this may reduce the results further)
    if (!empty($category)) {
        $category = strtolower(trim($category));
        $characters = array_filter($characters, function($char) use ($category) {
            foreach ($char['tags'] as $tag) {
                if (strtolower(trim($tag)) === $category) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Start output buffering
    ob_start();
    
    // Add pagination info
    echo '<div class="pmv-pagination-info" style="text-align: center; margin: 10px 0; color: #666;">';
    echo sprintf('Showing %d-%d of %d characters (Page %d of %d)', 
        $offset + 1, 
        min($offset + $cards_per_page, $total_files), 
        $total_files, 
        $current_page, 
        $total_pages
    );
    echo '</div>';
    
    // Add modal container (shared by all cards)
    echo '<div id="png-modal" class="png-modal">
        <div class="png-modal-content">
            <span class="close-modal">&times;</span>
            <div id="modal-content"></div>
        </div>
    </div>';
    
    // Filter section (simplified for pagination)
    if (!$hide_filters) {
        echo '<div class="png-filters-container">';
        echo '<p style="text-align: center; color: #666; font-style: italic;">Note: Filters will reset pagination</p>';
        echo '</div>';
    }
    
    // Cards container - NO loading spinner needed since we're only loading what we need
    echo '<div class="png-cards-loading-wrapper">';
    echo '<div class="png-cards">';
    echo '<div class="grid-sizer"></div>';
    
    // Add cards - only the ones for this page
    foreach ($characters as $character) {
        // Get character data
        $metadata = $character['metadata'];
        $file_url = $character['url'];
        $tags = $character['tags'];
        
        // Extract needed info
        $name = isset($metadata['data']['name']) ? $metadata['data']['name'] : 
               (isset($metadata['name']) ? $metadata['name'] : basename($character['file']));
        
        $description = isset($metadata['data']['description']) ? $metadata['data']['description'] : 
                      (isset($metadata['description']) ? $metadata['description'] : '');
        
        $tags_str = is_array($tags) ? implode(', ', $tags) : '';
        $chat_button_text = get_option('png_metadata_chat_button_text', 'Chat');
        
        // Convert metadata to string
        $metadata_encoded = json_encode($metadata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        if ($metadata_encoded === false) {
            $metadata_encoded = '{}';
            error_log('PNG Metadata Viewer: Failed to encode metadata for ' . basename($character['file']));
        }
        
        $metadata_string = htmlspecialchars($metadata_encoded, ENT_QUOTES, 'UTF-8');
        
        // Build the card HTML
        echo '<div class="png-card" data-tags="' . esc_attr($tags_str) . '" data-metadata="' . $metadata_string . '" data-file-url="' . esc_url($file_url) . '">';
        
        // Image container
        echo '<div class="png-image-container">';
        echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr($name) . '" loading="lazy">';
        echo '</div>';
        
        // Card info
        echo '<div class="png-card-info">';
        echo '<div class="png-card-name">' . esc_html($name) . '</div>';
        
        if (!empty($description)) {
            echo '<div class="png-card-description">' . esc_html(substr($description, 0, 150)) . (strlen($description) > 150 ? '...' : '') . '</div>';
        }
        
        if (!empty($tags_str)) {
            echo '<div class="png-card-tags">' . esc_html($tags_str) . '</div>';
        }
        echo '</div>';
        
        // Card buttons
        echo '<div class="png-card-buttons">';
        echo '<a href="' . esc_url($file_url) . '" class="png-download-button" download>Download</a>';
        echo '<button class="png-chat-button" data-metadata="' . $metadata_string . '">' . esc_html($chat_button_text) . '</button>';
        
        // Secondary button if configured
        $secondary_button_text = get_option('png_metadata_secondary_button_text');
        $secondary_button_link = get_option('png_metadata_secondary_button_link');
        
        if (!empty($secondary_button_text) && !empty($secondary_button_link)) {
            echo '<a href="' . esc_url($secondary_button_link) . '" class="png-secondary-button" target="_blank">' . esc_html($secondary_button_text) . '</a>';
        }
        
        echo '</div></div>';
    }
    
    echo '</div></div>';
    
    // Enhanced pagination with URL-based navigation
    if ($total_pages > 1) {
        echo '<div class="pmv-pagination">';
        
        $base_url = remove_query_arg('pmv_page');
        
        // Previous button
        if ($current_page > 1) {
            $prev_url = add_query_arg('pmv_page', $current_page - 1, $base_url);
            echo '<a href="' . esc_url($prev_url) . '" class="pmv-page-link pmv-prev">‹ Previous</a>';
        }
        
        // Page numbers (show 5 pages around current)
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            $page_url = add_query_arg('pmv_page', 1, $base_url);
            echo '<a href="' . esc_url($page_url) . '" class="pmv-page-link">1</a>';
            if ($start_page > 2) {
                echo '<span class="pmv-ellipsis">…</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                echo '<span class="pmv-page-link pmv-page-current">' . $i . '</span>';
            } else {
                $page_url = add_query_arg('pmv_page', $i, $base_url);
                echo '<a href="' . esc_url($page_url) . '" class="pmv-page-link">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<span class="pmv-ellipsis">…</span>';
            }
            $page_url = add_query_arg('pmv_page', $total_pages, $base_url);
            echo '<a href="' . esc_url($page_url) . '" class="pmv-page-link">' . $total_pages . '</a>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $next_url = add_query_arg('pmv_page', $current_page + 1, $base_url);
            echo '<a href="' . esc_url($next_url) . '" class="pmv-page-link pmv-next">Next ›</a>';
        }
        
        echo '</div>';
        
        // Add CSS for pagination
        echo '<style>
        .pmv-pagination {
            text-align: center;
            margin: 20px 0;
            padding: 20px 0;
        }
        .pmv-page-link {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #007cba;
            background: white;
            transition: all 0.2s;
        }
        .pmv-page-link:hover {
            background: #f0f0f0;
            border-color: #999;
        }
        .pmv-page-current {
            background: #007cba !important;
            color: white !important;
            border-color: #007cba !important;
        }
        .pmv-ellipsis {
            padding: 8px 4px;
            color: #666;
        }
        .pmv-pagination-info {
            font-size: 14px;
            margin-bottom: 15px;
        }
        </style>';
    }
    
    // Add JavaScript for immediate display (no complex loading needed)
    echo '<script>
    jQuery(document).ready(function($) {
        console.log("PMV: Server-side pagination active, showing ' . count($characters) . ' cards");
        
        // Simple masonry init since we only have a few cards
        if (typeof $.fn.masonry !== "undefined") {
            $(".png-cards").masonry({
                itemSelector: ".png-card",
                columnWidth: ".grid-sizer",
                percentPosition: true,
                gutter: 15
            });
        }
        
        // Make cards visible immediately
        $(".png-card").css("opacity", "1");
    });
    </script>';
    
    return ob_get_clean();
}

// Add cache clearing function
function pmv_clear_pagination_cache() {
    global $wpdb;
    
    // Clear all PMV transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pmv_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pmv_%'");
}

// Clear cache when files are added/removed (you can call this manually or hook it to file operations)
// pmv_clear_pagination_cache();

add_shortcode('png_metadata_viewer', 'png_metadata_viewer_shortcode');
