<?php
/**
 * Check SwarmUI API Endpoints
 * Tests different API endpoints to see what's available
 */

echo "=== SwarmUI API Endpoints Check ===\n";
echo "Testing different API endpoints...\n\n";

$api_url = 'http://100.107.40.105:7801';

// List of endpoints to test
$endpoints = [
    'GET /' => '',
    'GET /API' => '/API',
    'GET /API/' => '/API/',
    'POST /API/GetNewSession' => '/API/GetNewSession',
    'POST /API/ListModels' => '/API/ListModels',
    'GET /API/GetModels' => '/API/GetModels',
    'GET /API/GetSession' => '/API/GetSession',
    'GET /API/GetStatus' => '/API/GetStatus',
    'GET /API/GetVersion' => '/API/GetVersion',
    'GET /API/GetInfo' => '/API/GetInfo'
];

foreach ($endpoints as $name => $endpoint) {
    echo "Testing $name...\n";
    
    $url = $api_url . $endpoint;
    
    if (strpos($name, 'POST') === 0) {
        // POST request
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: SwarmUI-Test/1.0'
                ],
                'content' => json_encode(array())
            ]
        ]);
    } else {
        // GET request
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                    'User-Agent: SwarmUI-Test/1.0'
                ]
            ]
        ]);
    }
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        echo "  ✗ Failed: " . ($error['message'] ?? 'Unknown error') . "\n";
    } else {
        $response_code = $http_response_header[0] ?? 'Unknown';
        echo "  ✓ Success: $response_code\n";
        echo "  Response length: " . strlen($response) . " bytes\n";
        
        // Try to parse as JSON
        $json_data = json_decode($response, true);
        if ($json_data) {
            echo "  JSON response: " . json_encode(array_slice($json_data, 0, 3)) . "\n";
        } else {
            echo "  Text response: " . substr($response, 0, 200) . "\n";
        }
    }
    
    echo "\n";
}

echo "=== Endpoint Check Complete ===\n";
?> 