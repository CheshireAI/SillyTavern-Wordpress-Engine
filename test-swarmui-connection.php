<?php
/**
 * SwarmUI Connection Test
 * 
 * This script helps diagnose connection issues with SwarmUI
 * Run this file directly to test connectivity
 */

// Security check
if (file_exists('../../../wp-load.php')) {
    require_once('../../../wp-load.php');
} else {
    die('WordPress not found. Please run this from the WordPress root or comment out the wp-load check.');
}

echo "<h1>SwarmUI Connection Diagnostic</h1>";

// Get current settings
$api_url = get_option('pmv_swarmui_api_url', '');
$user_token = get_option('pmv_swarmui_user_token', '');

echo "<h2>Current Configuration</h2>";
echo "<p><strong>API URL:</strong> " . esc_html($api_url) . "</p>";
echo "<p><strong>User Token:</strong> " . (empty($user_token) ? 'Not set' : '***' . substr($user_token, -4)) . "</p>";

if (empty($api_url)) {
    echo "<p style='color: red;'>❌ ERROR: API URL is not configured. Please set it in WordPress admin settings.</p>";
    exit;
}

// Test 1: Basic HTTP connectivity
echo "<h2>Test 1: Basic Connectivity</h2>";
$test_url = trailingslashit($api_url) . 'API/GetNewSession';

$ch = curl_init($test_url);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '{}',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Accept: application/json'
    )
));

if (!empty($user_token)) {
    curl_setopt($ch, CURLOPT_COOKIE, 'swarm_user_token=' . $user_token);
}

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ cURL Error: " . esc_html($error) . "</p>";
    
    if (strpos($error, 'Timeout') !== false || strpos($error, 'Failed to connect') !== false) {
        echo "<h3>Possible Solutions:</h3>";
        echo "<ol>";
        echo "<li><strong>Check if SwarmUI server is running</strong> - Can you access " . esc_html($api_url) . " in your browser?</li>";
        echo "<li><strong>Check firewall rules</strong> - Is port 7801 accessible?</li>";
        echo "<li><strong>Check server IP</strong> - Is the IP address (100.107.40.105) correct?</li>";
        echo "<li><strong>Check network connectivity</strong> - Can you ping the server?</li>";
        echo "</ol>";
        
        // Try to ping
        echo "<h3>Ping Test</h3>";
        $ip = parse_url($api_url, PHP_URL_HOST);
        echo "Attempting to ping " . esc_html($ip) . "...<br>";
        $ping_result = shell_exec("ping -c 3 " . escapeshellarg($ip) . " 2>&1");
        echo "<pre>" . esc_html($ping_result) . "</pre>";
    }
} else if ($http_code === 200) {
    echo "<p style='color: green;'>✅ Connection successful! HTTP Code: " . $http_code . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . esc_html($response) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ HTTP Code: " . $http_code . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . esc_html($response) . "</pre>";
}

// Test 2: DNS Resolution
echo "<h2>Test 2: DNS Resolution</h2>";
$host = parse_url($api_url, PHP_URL_HOST);
echo "<p>Resolving host: " . esc_html($host) . "</p>";
$ip = gethostbyname($host);
echo "<p>Resolved to: " . esc_html($ip) . "</p>";

if ($ip === $host) {
    echo "<p style='color: red;'>❌ DNS resolution failed</p>";
} else {
    echo "<p style='color: green;'>✅ DNS resolution successful</p>";
}

// Test 3: Port Access
echo "<h2>Test 3: Port Access</h2>";
$port = parse_url($api_url, PHP_URL_PORT) ?: 7801;
echo "<p>Testing port: " . $port . "</p>";

$connection = @fsockopen($ip, $port, $errno, $errstr, 3);
if ($connection) {
    echo "<p style='color: green;'>✅ Port " . $port . " is accessible</p>";
    fclose($connection);
} else {
    echo "<p style='color: red;'>❌ Cannot connect to port " . $port . "</p>";
    echo "<p>Error: " . $errstr . " (Code: " . $errno . ")</p>";
    echo "<h3>Possible Issues:</h3>";
    echo "<ul>";
    echo "<li>SwarmUI server might not be running</li>";
    echo "<li>Firewall blocking the port</li>";
    echo "<li>Port number is incorrect</li>";
    echo "</ul>";
}

// Test 4: Recommended Settings
echo "<h2>Recommended Settings</h2>";
echo "<p>Based on your current setup, here are recommended adjustments:</p>";
echo "<ul>";
echo "<li><strong>Timeout Settings:</strong> Consider increasing timeout from 10 seconds to 30 seconds if your server is slow</li>";
echo "<li><strong>API URL Format:</strong> Should be http://100.107.40.105:7801 or https://100.107.40.105:7801</li>";
echo "<li><strong>SSL:</strong> If using HTTPS, make sure SSL verification is disabled or the certificate is valid</li>";
echo "</ul>";

// Test 5: WordPress Remote Functions
echo "<h2>Test 5: WordPress wp_remote Functions</h2>";
$response = wp_remote_get($api_url, array(
    'timeout' => 10,
    'sslverify' => false
));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Error: " . $response->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Connection successful via WordPress functions</p>";
    echo "<p>HTTP Code: " . wp_remote_retrieve_response_code($response) . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If all tests failed, check if SwarmUI is running on the server</li>";
echo "<li>Verify the IP address and port in WordPress settings</li>";
echo "<li>Check firewall rules to allow port 7801</li>";
echo "<li>Try accessing the API URL directly in a browser</li>";
echo "<li>Contact your server administrator if issues persist</li>";
echo "</ol>";

?>

