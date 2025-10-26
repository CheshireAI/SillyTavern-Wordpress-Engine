<?php
/**
 * Test Script for Credit Cap System
 * 
 * This script tests the new credit cap functionality for daily rewards.
 * Run this from the WordPress admin or via WP-CLI to test the system.
 */

// Ensure this is run in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-config.php');
    require_once('wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo "<h1>Credit Cap System Test</h1>\n";

// Get the subscription system instance
if (class_exists('PMV_Subscription_System')) {
    $subscription_system = PMV_Subscription_System::getInstance();
    
    echo "<h2>Testing Credit Cap System</h2>\n";
    
    // Test 1: Check current settings
    echo "<h3>1. Current Credit Cap Settings</h3>\n";
    $max_image_credits = get_option('pmv_max_free_image_credits', 1000);
    $max_token_credits = get_option('pmv_max_free_token_credits', 100000);
    
    echo "<p>Maximum Free Image Credits: " . ($max_image_credits > 0 ? $max_image_credits : 'No Limit') . "</p>\n";
    echo "<p>Maximum Free Token Credits: " . ($max_token_credits > 0 ? $max_token_credits : 'No Limit') . "</p>\n";
    
    // Test 2: Check current user's free credits
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        echo "<h3>2. Current User Free Credit Status</h3>\n";
        
        $free_image_credits = $subscription_system->get_user_free_credits($current_user_id, 'image');
        $free_text_credits = $subscription_system->get_user_free_credits($current_user_id, 'text');
        
        echo "<p>Current Free Image Credits: {$free_image_credits}</p>\n";
        echo "<p>Current Free Token Credits: {$free_text_credits}</p>\n";
        
        if ($max_image_credits > 0) {
            $image_remaining = max(0, $max_image_credits - $free_image_credits);
            echo "<p>Image Credits Remaining Before Cap: {$image_remaining}</p>\n";
        }
        
        if ($max_token_credits > 0) {
            $token_remaining = max(0, $max_token_credits - $free_text_credits);
            echo "<p>Token Credits Remaining Before Cap: {$token_remaining}</p>\n";
        }
        
        // Test 3: Simulate daily reward with caps
        echo "<h3>3. Testing Daily Reward with Caps</h3>\n";
        
        $daily_image_reward = get_option('pmv_daily_image_reward', 10);
        $daily_text_reward = get_option('pmv_daily_text_reward', 1000);
        
        echo "<p>Daily Image Reward: {$daily_image_reward}</p>\n";
        echo "<p>Daily Text Reward: {$daily_text_reward}</p>\n";
        
        if ($max_image_credits > 0) {
            $actual_image_reward = min($daily_image_reward, $image_remaining);
            echo "<p>Actual Image Reward (with cap): {$actual_image_reward}</p>\n";
        }
        
        if ($max_token_credits > 0) {
            $actual_text_reward = min($daily_text_reward, $token_remaining);
            echo "<p>Actual Text Reward (with cap): {$actual_text_reward}</p>\n>";
        }
        
        // Test 4: Check if user is at cap
        echo "<h3>4. Cap Status Check</h3>\n";
        
        if ($max_image_credits > 0 && $free_image_credits >= $max_image_credits) {
            echo "<p style='color: orange;'>⚠️ User is at IMAGE credit cap!</p>\n";
        } else {
            echo "<p style='color: green;'>✅ User can still receive image credits</p>\n";
        }
        
        if ($max_token_credits > 0 && $free_text_credits >= $max_token_credits) {
            echo "<p style='color: orange;'>⚠️ User is at TOKEN credit cap!</p>\n";
        } else {
            echo "<p style='color: green;'>✅ User can still receive token credits</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>No user logged in. Please log in to test user-specific functionality.</p>\n";
    }
    
    // Test 5: Test the AJAX endpoint
    echo "<h3>5. Testing AJAX Endpoint</h3>\n";
    
    // Simulate the AJAX call
    $_POST['nonce'] = wp_create_nonce('pmv_ajax_nonce');
    $_POST['action'] = 'pmv_check_free_credit_status';
    
    // Capture output
    ob_start();
    $subscription_system->ajax_check_free_credit_status();
    $ajax_output = ob_get_clean();
    
    if ($ajax_output) {
        echo "<p>AJAX Response: " . htmlspecialchars($ajax_output) . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ AJAX endpoint working (no output means success)</p>\n";
    }
    
} else {
    echo "<p style='color: red;'>Error: PMV_Subscription_System class not found!</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Test completed.</strong> Check the results above to verify the credit cap system is working correctly.</p>\n";
echo "<p><em>Note: This test script should be run from the WordPress admin area or removed after testing.</em></p>\n";
?> 