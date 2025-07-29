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
        
        // Active filters container (will be populated by JavaScript)
        echo '<div class="pmv-active-filters" style="display: none;"></div>';
        
        echo '</div>';
        echo '</div>';
    }
    

    
    // Main gallery container - AJAX will populate this
    echo '<div class="pmv-gallery-container" data-folder="' . esc_attr($folder) . '" data-category="' . esc_attr($category) . '" data-cards-per-page="' . esc_attr($cards_per_page) . '">';
    
    // Simplified gallery wrapper - no loading overlays
    echo '<div class="png-cards-loading">';
    echo '<div class="png-cards">';
    // Cards will be loaded by AJAX
    echo '</div>'; // Close png-cards
    echo '</div>'; // Close png-cards-loading
    
    // Simple loading indicator (smaller, for filter changes)
    echo '<div class="pmv-ajax-loading" style="display: none;">
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
        $(document).on("pmv_pagination_updated", function(event, pagination) {
            console.log("PMV AJAX: pagination updated event received", pagination);
            updatePaginationInfo(pagination);
        });

        // Trigger initial load if grid is ready
        if (window.pmvGrid) {
            window.pmvGrid.loadCards(1, window.pmvGrid.getCurrentFilters());
        }
    });
    </script>';
    
    return ob_get_clean();
}

add_shortcode('png_metadata_viewer', 'png_metadata_viewer_shortcode');
