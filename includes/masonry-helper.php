<?php
/**
 * Masonry Helper Functions
 * 
 * These functions help ensure the proper DOM structure for Masonry grids.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Adds a proper masonry container to the page
 * This helps ensure consistent layouts across themes
 */
function pmv_add_masonry_container_filter($content) {
    // Only run on pages with our shortcode
    if (has_shortcode($content, 'png_metadata_viewer')) {
        // Add a container with proper CSS to ensure Masonry works optimally
        $content = '<div class="pmv-masonry-container">' . $content . '</div>';
        
        // Also add some inline CSS to ensure consistent spacing and layouts
        $inline_css = '
        <style>
            .pmv-masonry-container {
                box-sizing: border-box;
                padding: 0;
                width: 100%;
                max-width: 100%;
                overflow: hidden;
                position: relative;
            }
            
            /* Clear any theme interference with Masonry layout */
            .pmv-masonry-container .png-cards {
                margin-left: -12px !important;
                margin-right: -12px !important;
                width: calc(100% + 24px) !important;
            }
            
            /* Ensure consistent padding/margins */
            .pmv-masonry-container .png-card {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 12px !important;
            }
            
            /* Ensure cards have appropriate box-sizing */
            .pmv-masonry-container * {
                box-sizing: border-box;
            }
            
            /* Fix for older browsers */
            .grid-sizer {
                display: block !important;
                float: left !important;
            }
        </style>';
        
        // Add the inline CSS before the container
        $content = $inline_css . $content;
    }
    
    return $content;
}
add_filter('the_content', 'pmv_add_masonry_container_filter', 99);

/**
 * Provides an easy way to add a debug overlay to the masonry grid
 * Can be activated by adding ?pmv_debug=1 to the URL
 */
function pmv_add_debug_helper() {
    if (!isset($_GET['pmv_debug']) || $_GET['pmv_debug'] != 1) {
        return;
    }
    
    // Only show to administrators
    if (!current_user_can('administrator')) {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add debug toggle button
        $('<button id="pmv-debug-toggle" style="position:fixed;top:10px;right:10px;z-index:9999;padding:10px;background:#f00;color:#fff;border:none;">Debug Layout</button>')
            .appendTo('body')
            .on('click', function() {
                $('.png-card').toggleClass('debug-border');
                $('.grid-sizer').toggleClass('debug-visible');
            });
        
        // Add debug styles
        $('<style>\
            .debug-border { border: 2px dashed red !important; }\
            .grid-sizer.debug-visible { background: rgba(255,0,0,0.3); height: 50px; visibility: visible !important; }\
        </style>').appendTo('head');
        
        // Expose masonry instance globally for console debugging
        window.pmvMasonryInstance = $('.png-cards').data('masonry');
        
        // Log helpful info to console
        console.log('PMV Debug Mode Active');
        console.log('Cards count:', $('.png-card').length);
        console.log('Images count:', $('.png-image-container img').length);
        console.log('Masonry instance:', window.pmvMasonryInstance);
        
        // Add grid guides
        $('.png-cards-loading-wrapper').append('<div id="debug-grid-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:100;pointer-events:none;display:none;"></div>');
        
        for (let i = 0; i < 4; i++) {
            $('#debug-grid-overlay').append('<div style="position:absolute;top:0;bottom:0;width:1px;background:rgba(255,0,0,0.5);left:' + (25 * (i+1)) + '%"></div>');
        }
        
        // Toggle grid guides
        $('<button id="pmv-grid-toggle" style="position:fixed;top:60px;right:10px;z-index:9999;padding:10px;background:#00f;color:#fff;border:none;">Show Grid</button>')
            .appendTo('body')
            .on('click', function() {
                $('#debug-grid-overlay').toggle();
            });
    });
    </script>
    <?php
}
add_action('wp_footer', 'pmv_add_debug_helper', 999);

/**
 * Helper function to ensure the plugin loads consistently across different themes
 */
function pmv_ensure_plugin_compatibility() {
    // Check if we're using Masonry
    $has_masonry = wp_script_is('masonry-js', 'registered') || wp_script_is('masonry', 'registered');
    
    // If not, register it as a fallback
    if (!$has_masonry) {
        wp_register_script(
            'masonry-js', 
            'https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js', 
            array('jquery'), 
            null, 
            true
        );
    }
    
    // Ensure imagesLoaded is available
    $has_imagesloaded = wp_script_is('imagesloaded-js', 'registered') || wp_script_is('imagesloaded', 'registered');
    
    if (!$has_imagesloaded) {
        wp_register_script(
            'imagesloaded-js', 
            'https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.min.js', 
            array('jquery'), 
            null, 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'pmv_ensure_plugin_compatibility', 5);
