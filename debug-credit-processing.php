<?php
/**
 * Debug Credit Processing
 * 
 * Simple page to test and debug credit processing
 */

// Include WordPress
require_once('wp-config.php');

// Check if user is logged in and has admin privileges
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

// Get nonce for AJAX calls
$nonce = wp_create_nonce('pmv_ajax_nonce');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Credit Processing</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: #ffffff; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { background: #2a2a2a; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .button:hover { background: #005a87; }
        .result { background: #333; padding: 15px; margin: 10px 0; border-radius: 4px; white-space: pre-wrap; }
        .success { border-left: 4px solid #4CAF50; }
        .error { border-left: 4px solid #f44336; }
        .info { border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug Credit Processing</h1>
        
        <div class="section">
            <h2>🚀 Force Process All Orders</h2>
            <p>This will manually trigger the order processing that should happen automatically on page load.</p>
            <button class="button" onclick="forceProcessOrders()">Force Process All Orders</button>
            <div id="forceProcessResult" class="result"></div>
        </div>
        
        <div class="section">
            <h2>📊 Check Recent Orders</h2>
            <p>Check what orders exist and their credit status.</p>
            <button class="button" onclick="checkRecentOrders()">Check Recent Orders</button>
            <div id="recentOrdersResult" class="result"></div>
        </div>
        
        <div class="section">
            <h2>🧪 Test Hook Execution</h2>
            <p>Test if WooCommerce hooks are working.</p>
            <button class="button" onclick="testHookExecution()">Test Hook Execution</button>
            <div id="hookTestResult" class="result"></div>
        </div>
        
        <div class="section">
            <h2>📋 System Status</h2>
            <p>Check the overall system status.</p>
            <button class="button" onclick="checkSystemStatus()">Check System Status</button>
            <div id="systemStatusResult" class="result"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = 'result ' + type;
        }

        function forceProcessOrders() {
            showResult('forceProcessResult', 'Force processing all orders...', 'info');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=pmv_force_process_all_orders&nonce=<?php echo $nonce; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data.result;
                    let message = '✅ Orders processed successfully!\n\n';
                    message += `Orders Checked: ${result.orders_checked}\n`;
                    message += `Orders With Credits: ${result.orders_with_credits}\n`;
                    message += `Orders Processed: ${result.orders_processed}\n`;
                    
                    showResult('forceProcessResult', message, 'success');
                } else {
                    showResult('forceProcessResult', '❌ Failed: ' + data.data, 'error');
                }
            })
            .catch(error => {
                showResult('forceProcessResult', 'Network error: ' + error.message, 'error');
            });
        }

        function checkRecentOrders() {
            showResult('recentOrdersResult', 'Checking recent orders...', 'info');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=pmv_force_process_order_credits&nonce=<?php echo $nonce; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    let message = 'Recent Orders Check:\n\n';
                    message += `Total Orders Found: ${result.total_orders}\n`;
                    message += `Orders Processed: ${result.orders_processed}\n`;
                    
                    if (result.orders && result.orders.length > 0) {
                        message += '\nOrder Details:\n';
                        result.orders.forEach((order, index) => {
                            message += `  ${index + 1}. Order ${order.order_id} (${order.status})\n`;
                            message += `     User: ${order.user_id}, Total: $${order.total}\n`;
                            message += `     Contains Credits: ${order.contains_credits ? 'Yes' : 'No'}\n`;
                            message += `     Credits Processed: ${order.credits_processed ? 'Yes' : 'No'}\n`;
                        });
                    }
                    
                    showResult('recentOrdersResult', message, 'success');
                } else {
                    showResult('recentOrdersResult', 'Error: ' + data.data, 'error');
                }
            })
            .catch(error => {
                showResult('recentOrdersResult', 'Network error: ' + error.message, 'error');
            });
        }

        function testHookExecution() {
            showResult('hookTestResult', 'Testing hook execution...', 'info');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=pmv_test_hook_execution&nonce=<?php echo $nonce; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('hookTestResult', '✅ Hook execution test successful!\n\n' + data.data, 'success');
                } else {
                    showResult('hookTestResult', '❌ Hook execution test failed: ' + data.data, 'error');
                }
            })
            .catch(error => {
                showResult('hookTestResult', 'Network error: ' + error.message, 'error');
            });
        }

        function checkSystemStatus() {
            showResult('systemStatusResult', 'Checking system status...', 'info');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=pmv_get_credit_status&nonce=<?php echo $nonce; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const status = data.data;
                    let message = 'System Status:\n\n';
                    message += `Category Created: ${status.category_created ? 'Yes' : 'No'}\n`;
                    message += `Image Credits Product: ${status.image_product_active ? 'Active' : 'Inactive'} (ID: ${status.image_product_id})\n`;
                    message += `Text Credits Product: ${status.text_product_active ? 'Active' : 'Inactive'} (ID: ${status.text_product_id})\n`;
                    message += `Credit Rates: $1 = ${status.image_credits_per_dollar} image credits, $1 = ${status.text_credits_per_dollar} text credits\n`;
                    message += `Hook Execution Test: ${status.hook_execution_test ? '✓ Success' : '✗ Failed'}\n`;
                    
                    showResult('systemStatusResult', message, status.hook_execution_test ? 'success' : 'error');
                } else {
                    showResult('systemStatusResult', 'Error: ' + data.data, 'error');
                }
            })
            .catch(error => {
                showResult('systemStatusResult', 'Network error: ' + error.message, 'error');
            });
        }

        // Auto-check system status on page load
        window.onload = function() {
            checkSystemStatus();
        };
    </script>
</body>
</html>
