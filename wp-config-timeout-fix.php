<?php
/**
 * Add this code to your wp-config.php file
 * 
 * This increases PHP execution time and memory limits for image generation
 * Add these lines BEFORE the line that says "/* That's all, stop editing! */"
 * 
 * Or, place this entire file in wp-content/mu-plugins/ (must-use plugins directory)
 * and it will automatically apply to all requests
 */

// Increase execution time to 6 minutes (360 seconds)
ini_set('max_execution_time', 360);
set_time_limit(360);

// Increase input time
ini_set('max_input_time', 360);

// Increase memory limit for image processing
ini_set('memory_limit', '512M');

// For AJAX requests (image generation), extend even more
if (defined('DOING_AJAX') && DOING_AJAX) {
    set_time_limit(0); // No limit for AJAX
    ini_set('max_execution_time', 0);
}

// Log when this is loaded (for debugging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('PMV: Timeout settings loaded - max_execution_time: ' . ini_get('max_execution_time'));
}

