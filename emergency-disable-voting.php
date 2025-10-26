<?php
/**
 * Emergency Script to Disable Voting System
 * Run this if your site is broken after adding the voting system
 */

echo "<h1>Emergency Voting System Disable</h1>";

// Check if we're in WordPress
if (!defined('ABSPATH')) {
    echo "<p>This script must be run from within WordPress.</p>";
    echo "<p>To disable the voting system manually:</p>";
    echo "<ol>";
    echo "<li>Rename 'includes/voting-system.php' to 'includes/voting-system.php.disabled'</li>";
    echo "<li>Remove the voting system include line from 'png-metadata-viewer.php'</li>";
    echo "<li>Remove the voting CSS include line from 'png-metadata-viewer.php'</li>";
    echo "</ol>";
    exit;
}

echo "<p>WordPress detected. Attempting to disable voting system...</p>";

// Method 1: Try to rename the voting system file
$voting_file = ABSPATH . 'wp-content/plugins/characters/includes/voting-system.php';
$disabled_file = ABSPATH . 'wp-content/plugins/characters/includes/voting-system.php.disabled';

if (file_exists($voting_file)) {
    if (rename($voting_file, $disabled_file)) {
        echo "<p style='color: green;'>✓ Voting system file renamed to .disabled</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to rename voting system file</p>";
    }
} else {
    echo "<p>Voting system file not found at: $voting_file</p>";
}

// Method 2: Try to comment out the include in main plugin file
$main_plugin_file = ABSPATH . 'wp-content/plugins/characters/png-metadata-viewer.php';
if (file_exists($main_plugin_file)) {
    $content = file_get_contents($main_plugin_file);
    
    // Comment out voting system include
    $content = str_replace(
        "// Load voting system - only if WordPress is ready",
        "// Load voting system - only if WordPress is ready (DISABLED)",
        $content
    );
    $content = str_replace(
        "if (file_exists(PMV_PLUGIN_DIR . 'includes/voting-system.php') && function_exists('add_action')) {",
        "if (false && file_exists(PMV_PLUGIN_DIR . 'includes/voting-system.php') && function_exists('add_action')) {",
        $content
    );
    
    // Comment out voting CSS include
    $content = str_replace(
        "wp_enqueue_style('pmv-voting-styles', PMV_PLUGIN_URL . 'css/voting-system-styles.css', array(), PMV_VERSION);",
        "// wp_enqueue_style('pmv-voting-styles', PMV_PLUGIN_URL . 'css/voting-system-styles.css', array(), PMV_VERSION);",
        $content
    );
    
    if (file_put_contents($main_plugin_file, $content)) {
        echo "<p style='color: green;'>✓ Main plugin file updated (voting system disabled)</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to update main plugin file</p>";
    }
} else {
    echo "<p>Main plugin file not found at: $main_plugin_file</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Refresh your WordPress site</li>";
echo "<li>Check if the critical error is resolved</li>";
echo "<li>If the site works, you can re-enable the voting system later by fixing the issues</li>";
echo "</ol>";

echo "<h2>To Re-enable Later:</h2>";
echo "<ol>";
echo "<li>Rename 'voting-system.php.disabled' back to 'voting-system.php'</li>";
echo "<li>Uncomment the include lines in 'png-metadata-viewer.php'</li>";
echo "<li>Fix any remaining issues</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> This is a temporary fix. The voting system will need to be properly debugged before re-enabling.</p>";
?> 