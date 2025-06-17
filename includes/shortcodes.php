<?php
// includes/shortcodes.php - AJAX VERSION with FIXED chat button
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// AJAX-powered shortcode handler with working chat button
function png_metadata_viewer_shortcode($atts) {
    // Normalize attributes
    $atts = shortcode_atts(array(
        'folder' => '',
        'category' => '',
        'hide_filters' => 'no',
        'cards_per_page' => get_option('png_metadata_cards_per_page', 12),
        'page' => 1,
    ), $atts);
    
    // Handle parameters
    $folder = sanitize_text_field($atts['folder']);
    $category = sanitize_text_field($atts['category']);
    $hide_filters = ($atts['hide_filters'] === 'yes');
    $cards_per_page = intval($atts['cards_per_page']);
    
    // Get active filters from URL parameters
    $active_filters = array(
        'filter1' => sanitize_text_field($_GET['pmv_filter1'] ?? ''),
        'filter2' => sanitize_text_field($_GET['pmv_filter2'] ?? ''),
        'filter3' => sanitize_text_field($_GET['pmv_filter3'] ?? ''),
        'filter4' => sanitize_text_field($_GET['pmv_filter4'] ?? ''),
        'search' => sanitize_text_field($_GET['pmv_search'] ?? '')
    );
    
    // Ensure cards_per_page is at least 1
    if ($cards_per_page < 1) {
        $cards_per_page = 12;
    }
    
    // Get filter settings from admin
    $filter_settings = array(
        'filter1' => array(
            'title' => get_option('png_metadata_filter1_title', 'Category'),
            'list' => get_option('png_metadata_filter1_list', '')
        ),
        'filter2' => array(
            'title' => get_option('png_metadata_filter2_title', 'Style'),
            'list' => get_option('png_metadata_filter2_list', '')
        ),
        'filter3' => array(
            'title' => get_option('png_metadata_filter3_title', 'Tags'),
            'list' => get_option('png_metadata_filter3_list', '')
        ),
        'filter4' => array(
            'title' => get_option('png_metadata_filter4_title', 'Rating'),
            'list' => get_option('png_metadata_filter4_list', '')
        )
    );
    
    // Start output buffering
    ob_start();
    
    // Add modal container (shared by all cards)
    echo '<div id="png-modal" class="png-modal">
        <div class="png-modal-content">
            <span class="close-modal">&times;</span>
            <div id="modal-content"></div>
        </div>
    </div>';
    
    // Filter section - Only show if not hidden
    if (!$hide_filters) {
        echo '<div class="png-filters-container">';
        echo '<div class="pmv-filters-wrapper">';
        
        // Search box
        echo '<div class="pmv-filter-group pmv-search-group">';
        echo '<label for="pmv-search">Search:</label>';
        echo '<input type="text" id="pmv-search" value="' . esc_attr($active_filters['search']) . '" placeholder="Search characters...">';
        echo '</div>';
        
        // Dynamic filters based on admin settings
        for ($i = 1; $i <= 4; $i++) {
            $filter_key = "filter{$i}";
            $filter_data = $filter_settings[$filter_key];
            
            if (!empty($filter_data['title']) && !empty($filter_data['list'])) {
                $filter_options = array_filter(array_map('trim', explode("\n", $filter_data['list'])));
                
                if (!empty($filter_options)) {
                    echo '<div class="pmv-filter-group">';
                    echo '<label for="pmv-' . $filter_key . '">' . esc_html($filter_data['title']) . ':</label>';
                    echo '<select id="pmv-' . $filter_key . '" class="pmv-filter-select">';
                    echo '<option value="">All ' . esc_html($filter_data['title']) . '</option>';
                    
                    foreach ($filter_options as $option) {
                        $option = trim($option);
                        if (empty($option)) continue;
                        
                        $selected = (strtolower($active_filters[$filter_key]) === strtolower($option)) ? 'selected' : '';
                        echo '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html($option) . '</option>';
                    }
                    
                    echo '</select>';
                    echo '</div>';
                }
            }
        }
        
        // Filter buttons
        echo '<div class="pmv-filter-actions">';
        echo '<button id="pmv-apply-filters" class="pmv-filter-button pmv-filter-apply">Apply Filters</button>';
        echo '<button id="pmv-clear-filters" class="pmv-filter-button pmv-filter-clear">Clear All</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    // Pagination info (will be updated by AJAX)
    echo '<div class="pmv-pagination-info" style="text-align: center; margin: 10px 0; color: #666; font-size: 14px; padding: 10px;">
        <span style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px;">Loading character gallery...</span>
    </div>';
    
    // LOADING OVERLAY - Will show during initial load and filter changes
    echo '<div id="pmv-loading-overlay" class="pmv-loading-overlay">
        <div class="pmv-loading-content">
            <div class="pmv-spinner">
                <div class="pmv-spinner-ring"></div>
                <div class="pmv-spinner-ring"></div>
                <div class="pmv-spinner-ring"></div>
            </div>
            <div class="pmv-loading-text">Loading Characters...</div>
            <div class="pmv-loading-subtext">Please wait while we load your gallery</div>
        </div>
    </div>';
    
    // Main gallery container - AJAX will populate this
    echo '<div class="pmv-gallery-container" data-folder="' . esc_attr($folder) . '" data-category="' . esc_attr($category) . '" data-cards-per-page="' . esc_attr($cards_per_page) . '">';
    
    // Masonry wrapper for centering
    echo '<div class="pmv-masonry-wrapper">';
    echo '<div class="png-cards-loading">';
    echo '<div class="png-cards">';
    echo '<div class="grid-sizer"></div>'; // Required for masonry
    // Cards will be loaded by AJAX
    echo '</div>'; // Close png-cards
    echo '</div>'; // Close png-cards-loading
    echo '</div>'; // Close pmv-masonry-wrapper
    
    // AJAX loading indicator (smaller, for filter changes)
    echo '<div class="pmv-ajax-loading" style="display: none;">
        <div class="pmv-spinner">
            <div class="pmv-spinner-ring"></div>
            <div class="pmv-spinner-ring"></div>
            <div class="pmv-spinner-ring"></div>
        </div>
        <div>Loading...</div>
    </div>';
    
    echo '</div>'; // Close pmv-gallery-container
    
    // Pagination container - Will be populated by AJAX
    echo '<div class="pmv-pagination">
        <!-- Pagination will be loaded by AJAX -->
    </div>';
    
    // Enhanced JavaScript for AJAX functionality
    echo '<script>
    jQuery(document).ready(function($) {
        console.log("PMV AJAX: Shortcode initialized");
        
        // Set initial filter values from URL
        const urlParams = new URLSearchParams(window.location.search);
        
        // Update filter inputs with URL values
        if (urlParams.get("pmv_search")) {
            $("#pmv-search").val(urlParams.get("pmv_search"));
        }
        if (urlParams.get("pmv_filter1")) {
            $("#pmv-filter1").val(urlParams.get("pmv_filter1"));
        }
        if (urlParams.get("pmv_filter2")) {
            $("#pmv-filter2").val(urlParams.get("pmv_filter2"));
        }
        if (urlParams.get("pmv_filter3")) {
            $("#pmv-filter3").val(urlParams.get("pmv_filter3"));
        }
        if (urlParams.get("pmv_filter4")) {
            $("#pmv-filter4").val(urlParams.get("pmv_filter4"));
        }
        
        // Function to update pagination info
        function updatePaginationInfo(pagination) {
            const $info = $(".pmv-pagination-info");
            if (pagination && pagination.total_items > 0) {
                const start = (pagination.current_page - 1) * pagination.per_page + 1;
                const end = Math.min(start + pagination.per_page - 1, pagination.total_items);
                
                $info.html(
                    `Showing ${start}-${end} of ${pagination.total_items} characters ` +
                    `(Page ${pagination.current_page} of ${pagination.total_pages})`
                );
            } else {
                $info.html("No characters found");
            }
        }
        
        // Listen for successful card loading
        $(document).on("pmv_cards_loaded", function(event, data) {
            console.log("PMV AJAX: Cards loaded event received", data);
            if (data && data.pagination) {
                updatePaginationInfo(data.pagination);
                console.log("PMV AJAX: Pagination info updated");
            }
        });
        
        // Listen for loading errors
        $(document).on("pmv_load_error", function(event, error) {
            console.log("PMV AJAX: Load error event received", error);
            $(".pmv-pagination-info").html(`<span style="color: #d63638;">Error: ${error}</span>`);
        });
        
        console.log("PMV AJAX: Event listeners attached");
    });
    </script>';
    
    return ob_get_clean();
}

add_shortcode('png_metadata_viewer', 'png_metadata_viewer_shortcode');

// AJAX handler to get character cards
add_action('wp_ajax_pmv_get_character_cards', 'pmv_ajax_get_character_cards');
add_action('wp_ajax_nopriv_pmv_get_character_cards', 'pmv_ajax_get_character_cards');

function pmv_ajax_get_character_cards() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    // Get parameters
    $page = max(1, intval($_POST['page'] ?? 1));
    $per_page = max(1, intval($_POST['per_page'] ?? 12));
    $folder = sanitize_text_field($_POST['folder'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    
    // Get filters
    $filters = array(
        'search' => sanitize_text_field($_POST['search'] ?? ''),
        'filter1' => sanitize_text_field($_POST['filter1'] ?? ''),
        'filter2' => sanitize_text_field($_POST['filter2'] ?? ''),
        'filter3' => sanitize_text_field($_POST['filter3'] ?? ''),
        'filter4' => sanitize_text_field($_POST['filter4'] ?? '')
    );
    
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
        wp_send_json_error(array('message' => 'PNG cards directory not found'));
    }
    
    // Get all PNG files
    $all_files = glob($png_cards_dir . '*.png');
    
    if (empty($all_files)) {
        wp_send_json_success(array(
            'cards' => array(),
            'pagination' => array(
                'total_items' => 0,
                'total_pages' => 0,
                'current_page' => 1,
                'per_page' => $per_page,
                'has_prev' => false,
                'has_next' => false
            )
        ));
    }
    
    // Process all files
    $all_characters = array();
    foreach ($all_files as $file) {
        try {
            // Extract metadata
            $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
            
            if (empty($metadata)) {
                continue;
            }
            
            // Extract tags
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
            
            // Get name and description
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
            
        } catch (Exception $e) {
            error_log('PNG Metadata AJAX error: ' . $e->getMessage());
            continue;
        }
    }
    
    // Apply filters
    $filtered_characters = $all_characters;
    
    // Category filter
    if (!empty($category)) {
        $category = strtolower(trim($category));
        $filtered_characters = array_filter($filtered_characters, function($char) use ($category) {
            foreach ($char['tags'] as $tag) {
                if (strtolower(trim($tag)) === $category) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Apply other filters
    foreach ($filters as $filter_key => $filter_value) {
        if (empty($filter_value) || $filter_key === 'search') continue;
        
        $filter_value = strtolower(trim($filter_value));
        $filtered_characters = array_filter($filtered_characters, function($char) use ($filter_value) {
            foreach ($char['tags'] as $tag) {
                if (strtolower(trim($tag)) === $filter_value) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Apply search
    if (!empty($filters['search'])) {
        $search_term = strtolower(trim($filters['search']));
        $filtered_characters = array_filter($filtered_characters, function($char) use ($search_term) {
            $searchable = strtolower($char['name'] . ' ' . $char['description'] . ' ' . implode(' ', $char['tags']));
            return strpos($searchable, $search_term) !== false;
        });
    }
    
    // Calculate pagination
    $total = count($filtered_characters);
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Get page of results
    $page_characters = array_slice($filtered_characters, $offset, $per_page);
    
    // Format for response
    $cards = array();
    foreach ($page_characters as $char) {
        $cards[] = array(
            'file_url' => $char['file_url'],
            'name' => $char['name'],
            'description' => substr($char['description'], 0, 150) . (strlen($char['description']) > 150 ? '...' : ''),
            'tags' => $char['tags'],
            'metadata' => $char['metadata']
        );
    }
    
    // Send response
    wp_send_json_success(array(
        'cards' => $cards,
        'pagination' => array(
            'total_items' => $total,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        )
    ));
}
