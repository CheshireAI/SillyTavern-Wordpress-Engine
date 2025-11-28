<?php
// includes/shortcodes.php - AJAX VERSION with FIXED chat button
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// AJAX-powered shortcode handler with working chat button
function png_metadata_viewer_shortcode($atts) {
    // Prevent output during plugin activation
    if (defined('WP_INSTALLING') || (defined('WP_PLUGIN_DIR') && !function_exists('add_action'))) {
        return '';
    }
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
        
        // Sort dropdown
        echo '<div class="pmv-filter-group pmv-sort-group">';
        echo '<label for="pmv-sort">Sort by:</label>';
        echo '<select id="pmv-sort" class="pmv-filter-select">';
        echo '<option value="">Default Order</option>';
        echo '<option value="name">Name A-Z</option>';
        echo '<option value="name_desc">Name Z-A</option>';
        echo '<option value="votes_popular">Most Popular</option>';
        echo '<option value="votes_hated">Most Hated</option>';
        echo '<option value="votes_recent">Recently Voted</option>';
        echo '</select>';
        echo '</div>';
        
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
    
    // Top pagination container - Will be populated by AJAX
    echo '<div class="pmv-pagination pmv-pagination-top">
        <!-- Pagination will be loaded by AJAX -->
    </div>';

    
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
    
    // Bottom pagination container - Will be populated by AJAX
    echo '<div class="pmv-pagination pmv-pagination-bottom">
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
        if (urlParams.get("pmv_sort")) {
            $("#pmv-sort").val(urlParams.get("pmv_sort"));
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

/**
 * Credit Status Shortcode
 * Displays current user's credit balances and usage
 */
function pmv_subscription_status_shortcode($atts) {
    // Prevent output during plugin activation
    if (defined('WP_INSTALLING') || (defined('WP_PLUGIN_DIR') && !function_exists('add_action'))) {
        return '';
    }
    $atts = shortcode_atts(array(
        'show_limits' => 'true',
        'show_upgrade' => 'true',
        'style' => 'default'
    ), $atts);
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        return '<div class="pmv-subscription-status pmv-subscription-unavailable">Credit system not available</div>';
    }
    
    $subscription_system = PMV_Subscription_System::getInstance();
    $user_id = get_current_user_id();
    $credits = $subscription_system->get_user_credits($user_id);
    $usage = $subscription_system->get_current_usage($user_id);
    
    ob_start();
    
    $status_class = 'pmv-subscription-credits';
    $status_icon = '💰';
    
    echo '<div class="pmv-subscription-status ' . esc_attr($status_class) . ' pmv-style-' . esc_attr($atts['style']) . '">';
    echo '<div class="pmv-subscription-header">';
    echo '<span class="pmv-subscription-icon">' . $status_icon . '</span>';
    echo '<h3 class="pmv-subscription-title">Credit System</h3>';
    echo '</div>';
    
    if ($atts['show_limits'] === 'true') {
        echo '<div class="pmv-subscription-limits">';
        
        // Get daily reward status
        $daily_reward_status = $subscription_system->get_daily_reward_status($user_id);
        
        // Daily reward information
        if ($daily_reward_status && $daily_reward_status['daily_image_reward'] > 0) {
            echo '<div class="pmv-daily-reward-info">';
            echo '<h4>🎁 Daily Login Reward</h4>';
            echo '<p>Log in daily to get free credits!</p>';
            
            if ($daily_reward_status['can_claim']) {
                echo '<div class="pmv-reward-available">';
                echo '<span class="pmv-reward-text">✅ Available now!</span>';
                echo '<span class="pmv-reward-amount">+' . $daily_reward_status['daily_image_reward'] . ' image, +' . $daily_reward_status['daily_text_reward'] . ' text credits</span>';
                echo '</div>';
            } else {
                echo '<div class="pmv-reward-claimed">';
                echo '<span class="pmv-reward-text">⏰ Already claimed today</span>';
                echo '<span class="pmv-reward-next">Next reward: ' . $daily_reward_status['next_reward_time'] . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        // Image credits
        echo '<div class="pmv-limit-item">';
        echo '<span class="pmv-limit-label">Image Credits:</span>';
        echo '<span class="pmv-limit-value">';
        echo '<span class="pmv-usage-info">' . number_format($usage['today_images']) . ' used today</span>';
        echo '<span class="pmv-remaining-info">' . number_format($credits['image_credits']) . ' remaining</span>';
        echo '</span>';
        echo '</div>';
        
        // Progress bar for image credits
        if ($credits['image_credits'] > 0) {
            $image_progress = ($usage['today_images'] / max(1, $credits['image_credits'])) * 100;
            $progress_class = '';
            if ($image_progress >= 90) {
                $progress_class = 'danger';
            } elseif ($image_progress >= 75) {
                $progress_class = 'warning';
            }
            
            echo '<div class="pmv-usage-progress">';
            echo '<div class="pmv-progress-bar">';
            echo '<div class="pmv-progress-fill ' . $progress_class . '" style="width: ' . min(100, $image_progress) . '%"></div>';
            echo '</div>';
            echo '<span class="pmv-progress-text">' . number_format($usage['today_images']) . ' used today</span>';
            echo '</div>';
        }
        
        // Text credits
        echo '<div class="pmv-limit-item">';
        echo '<span class="pmv-limit-label">Text Credits:</span>';
        echo '<span class="pmv-limit-value">';
        echo '<span class="pmv-usage-info">' . number_format($usage['monthly_tokens']) . ' used this month</span>';
        echo '<span class="pmv-remaining-info">' . number_format($credits['text_credits']) . ' remaining</span>';
        echo '</span>';
        echo '</div>';
        
        // Progress bar for text credits
        if ($credits['text_credits'] > 0) {
            $text_progress = ($usage['monthly_tokens'] / max(1, $credits['text_credits'])) * 100;
            $progress_class = '';
            if ($text_progress >= 90) {
                $progress_class = 'danger';
            } elseif ($text_progress >= 75) {
                $progress_class = 'warning';
            }
            
            echo '<div class="pmv-usage-progress">';
            echo '<div class="pmv-progress-bar">';
            echo '<div class="pmv-progress-fill ' . $progress_class . '" style="width: ' . min(100, $text_progress) . '%"></div>';
            echo '</div>';
            echo '<span class="pmv-progress-text">' . number_format($usage['monthly_tokens']) . ' used this month</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    if ($atts['show_upgrade'] === 'true' && ($credits['image_credits'] < 100 || $credits['text_credits'] < 10000)) {
        echo '<div class="pmv-subscription-upgrade">';
        echo '<p>Need more credits? Purchase additional image and text credits!</p>';
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="pmv-upgrade-button">Buy Credits</a>';
        echo '</div>';
    }
    
    echo '</div>';
    
    return ob_get_clean();
}

add_shortcode('pmv_subscription_status', 'pmv_subscription_status_shortcode');

/**
 * Credit Details Shortcode
 * Displays detailed information about user's current credit balances
 */
function pmv_access_plan_details_shortcode($atts) {
    // Prevent output during plugin activation
    if (defined('WP_INSTALLING') || (defined('WP_PLUGIN_DIR') && !function_exists('add_action'))) {
        return '';
    }
    $atts = shortcode_atts(array(
        'show_progress' => 'true',
        'show_usage' => 'true',
        'style' => 'default'
    ), $atts);
    
    // Check if subscription system is available
    if (!class_exists('PMV_Subscription_System')) {
        return '<div class="pmv-access-plan-details pmv-access-plan-unavailable">Credit system not available</div>';
    }
    
    $subscription_system = PMV_Subscription_System::getInstance();
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return '<div class="pmv-access-plan-details pmv-guest-user">Please log in to view your credit balances.</div>';
    }
    
    $credits = $subscription_system->get_user_credits($user_id);
    
    if ($credits['image_credits'] <= 0 && $credits['text_credits'] <= 0) {
        return '<div class="pmv-access-plan-details pmv-no-plan">No credits available. <a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="pmv-upgrade-button">Purchase Credits</a></div>';
    }
    
    ob_start();
    
    $tier_class = 'pmv-access-plan-credits';
    $tier_icon = '💰';
    
    echo '<div class="pmv-access-plan-details ' . esc_attr($tier_class) . ' pmv-style-' . esc_attr($atts['style']) . '">';
    echo '<div class="pmv-access-plan-header">';
    echo '<span class="pmv-access-plan-icon">' . $tier_icon . '</span>';
    echo '<h3 class="pmv-access-plan-title">Credit System</h3>';
    echo '</div>';
    
    echo '<div class="pmv-access-plan-info">';
    
    // Show usage information if requested
    if ($atts['show_usage'] === 'true') {
        $current_usage = $subscription_system->get_current_usage($user_id);
        
        echo '<div class="pmv-plan-usage">';
        echo '<h4>Current Usage & Credits</h4>';
        
        // Image credits usage
        echo '<div class="pmv-usage-item">';
        echo '<span class="pmv-usage-label">Image Credits:</span>';
        echo '<span class="pmv-usage-value">';
        echo '<span class="pmv-usage-info">' . number_format($current_usage['today_images']) . ' used today</span>';
        echo '<span class="pmv-remaining-info">' . number_format($credits['image_credits']) . ' remaining</span>';
        echo '</span>';
        echo '</div>';
        
        // Progress bar for image credits
        if ($credits['image_credits'] > 0) {
            $image_progress = ($current_usage['today_images'] / max(1, $credits['image_credits'])) * 100;
            $progress_class = '';
            if ($image_progress >= 90) {
                $progress_class = 'danger';
            } elseif ($image_progress >= 75) {
                $progress_class = 'warning';
            }
            
            echo '<div class="pmv-usage-progress">';
            echo '<div class="pmv-progress-bar">';
            echo '<div class="pmv-progress-fill ' . $progress_class . '" style="width: ' . min(100, $image_progress) . '%"></div>';
            echo '</div>';
            echo '<span class="pmv-progress-text">' . number_format($current_usage['today_images']) . ' used today</span>';
            echo '</div>';
        }
        
        // Text credits usage
        echo '<div class="pmv-usage-item">';
        echo '<span class="pmv-usage-label">Text Credits:</span>';
        echo '<span class="pmv-usage-value">';
        echo '<span class="pmv-usage-info">' . number_format($current_usage['monthly_tokens']) . ' used this month</span>';
        echo '<span class="pmv-remaining-info">' . number_format($credits['text_credits']) . ' remaining</span>';
        echo '</span>';
        echo '</div>';
        
        // Progress bar for text credits
        if ($credits['text_credits'] > 0) {
            $text_progress = ($current_usage['monthly_tokens'] / max(1, $credits['text_credits'])) * 100;
            $progress_class = '';
            if ($text_progress >= 90) {
                $progress_class = 'danger';
            } elseif ($text_progress >= 75) {
                $progress_class = 'warning';
            }
            
            echo '<div class="pmv-usage-progress">';
            echo '<div class="pmv-progress-bar">';
            echo '<div class="pmv-progress-fill ' . $progress_class . '" style="width: ' . min(100, $text_progress) . '%"></div>';
            echo '</div>';
            echo '<span class="pmv-progress-text">' . number_format($current_usage['monthly_tokens']) . ' used this month</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '<div class="pmv-plan-dates">';
    echo '<div class="pmv-plan-date-item">';
    echo '<span class="pmv-plan-date-label">Credit Type:</span>';
    echo '<span class="pmv-plan-date-value">Cumulative & Non-Expiring</span>';
    echo '</div>';
    echo '<div class="pmv-plan-date-item">';
    echo '<span class="pmv-plan-date-label">Status:</span>';
    echo '<span class="pmv-plan-date-value">Active</span>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
    
    // Show purchase option if credits are low
    if ($credits['image_credits'] < 100 || $credits['text_credits'] < 10000) {
        echo '<div class="pmv-access-plan-upgrade">';
        echo '<p>Running low on credits? Purchase more to continue using the service!</p>';
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="pmv-upgrade-button">Buy More Credits</a>';
        echo '</div>';
    }
    
    echo '</div>';
    
    return ob_get_clean();
}

add_shortcode('pmv_access_plan_details', 'pmv_access_plan_details_shortcode');
