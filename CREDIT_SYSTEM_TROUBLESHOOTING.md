# Credit System Troubleshooting Guide

## Issue: Orders Not Automatically Adding Credits

**Problem**: Orders with credit products are not automatically adding credits when completed or when status changes to "processing". Credits are only added when manually processed from the admin settings.

**Affected Orders**: 
- Order 11744: Image Credits (100) + Text Credits (10,000) - Status: processing
- Order 11745: Text Credits (10,000) - Status: processing

## Root Cause Analysis

The issue appears to be **WooCommerce hook execution failure**. Even though the hooks are registered, they may not be firing properly when orders are created or status changes occur.

## Diagnostic Tools Available

### 1. Test WooCommerce Hooks
**Purpose**: Verify that all WooCommerce hooks are properly registered
**Location**: Admin Settings → Credit System Configuration → "Test WooCommerce Hooks"
**What it does**: Checks if all required hooks are registered and accessible

### 2. Test Hook Execution
**Purpose**: Verify that WooCommerce hooks are actually firing and executing
**Location**: Admin Settings → Credit System Configuration → "Test Hook Execution"
**What it does**: Manually triggers a test hook to see if the system responds

### 3. Debug Order Credits
**Purpose**: Analyze a specific order to see why credits weren't added
**Location**: Admin Settings → Credit System Configuration → "Debug Order Credits"
**What it does**: Shows detailed information about an order's credit processing status

### 4. Check Processing Orders
**Purpose**: Find orders with "processing" status that need credits
**Location**: Admin Settings → Credit System Configuration → "Check Processing Orders"
**What it does**: Scans for processing orders and identifies any that need credits processed

### 5. Process Specific Order
**Purpose**: Manually process credits for a specific order ID
**Location**: Admin Settings → Credit System Configuration → "Process Specific Order"
**What it does**: Forces credit processing for a specific order (useful for testing orders 11744/11745)

### 6. Comprehensive Order Audit
**Purpose**: Scan all orders to find any that need credits processed
**Location**: Admin Settings → Credit System Configuration → "Audit All Orders"
**What it does**: Comprehensive scan of all orders to identify and process missed credits

## Step-by-Step Troubleshooting

### Step 1: Test Hook Registration
1. Go to **Admin Settings** → **Credit System Configuration**
2. Click **"Test WooCommerce Hooks"**
3. Verify all hooks show as registered

### Step 2: Test Hook Execution
1. Click **"Test Hook Execution"**
2. Check if the test completes successfully
3. Look for any error messages

### Step 3: Debug Specific Orders
1. Use **"Debug Order Credits"** with order ID 11744
2. Use **"Debug Order Credits"** with order ID 11745
3. Review the detailed information for each order

### Step 4: Check for Missed Orders
1. Click **"Check Processing Orders"**
2. See if any orders are identified as needing credits
3. Note how many orders are found

### Step 5: Comprehensive Audit
1. Click **"Audit All Orders"**
2. This will scan all orders and process any missed credits
3. Review the results to see what was found and processed

### Step 6: Manual Processing (if needed)
1. Use **"Process Specific Order"** with order ID 11744
2. Use **"Process Specific Order"** with order ID 11745
3. Verify credits are added successfully

## Expected Results

### Successful Hook Registration
```
PMV: ✅ Hook woocommerce_order_status_completed is registered
PMV: ✅ Hook woocommerce_order_status_processing is registered
PMV: ✅ Hook woocommerce_payment_complete is registered
PMV: ✅ Hook woocommerce_order_status_changed is registered
PMV: ✅ Hook woocommerce_checkout_order_processed is registered
```

### Successful Hook Execution
```
PMV: 🧪 Testing WooCommerce hook execution...
PMV: ✅ WooCommerce hook execution test completed
```

### Successful Order Processing
```
PMV: 🔥 ORDER STATUS CHANGED HOOK FIRED - Order: 11744, Old: '', New: 'processing'
PMV: 🚀 Processing credits for order status change to 'processing'
PMV: 📋 Order details - ID: 11744, User: 1, Total: 2.00
PMV: 📦 Order item - Product: Image Credits (ID: XXX), Credit Type: image
PMV: 📦 Order item - Product: Text Credits (ID: XXX), Credit Type: text
PMV: 💳 Order contains credit products: YES
PMV: 🎯 Order 11744 contains credit products - calling process_order_credits
```

## Common Issues and Solutions

### Issue 1: Hooks Not Registered
**Symptoms**: "Test WooCommerce Hooks" shows missing hooks
**Solution**: The system will automatically attempt to re-register hooks

### Issue 2: Hooks Not Executing
**Symptoms**: "Test Hook Execution" fails
**Solution**: Check WooCommerce plugin status and WordPress debug logs

### Issue 3: Orders Created with "Processing" Status
**Symptoms**: Orders show as "processing" but credits aren't added
**Solution**: Use "Check Processing Orders" or "Audit All Orders" to find and process missed orders

### Issue 4: Credits Already Processed
**Symptoms**: Debug shows credits were already processed
**Solution**: Use "Process Specific Order" to force reprocessing

## Immediate Fix for Orders 11744 and 11745

1. **Quick Fix**: Use "Process Specific Order" button
   - Enter order ID: `11744` → Click "Process Order"
   - Enter order ID: `11745` → Click "Process Order"

2. **Comprehensive Fix**: Use "Audit All Orders" button
   - This will find and process ALL missed orders automatically

## Prevention Measures

1. **Regular Monitoring**: Use "Check Processing Orders" weekly
2. **Hook Verification**: Use "Test WooCommerce Hooks" after plugin updates
3. **Order Auditing**: Use "Audit All Orders" monthly to catch any missed credits

## Debug Information

### Error Logs to Check
Look for log entries starting with "PMV:" in your WordPress debug log:
- `PMV: 🔥 ORDER STATUS CHANGED HOOK FIRED`
- `PMV: 🚀 Processing credits for order status change`
- `PMV: 📋 Order details`
- `PMV: 💳 Order contains credit products`

### WordPress Debug Log Location
- Usually in `/wp-content/debug.log`
- Or check your hosting control panel for error logs

## Support Information

If the issue persists after using all diagnostic tools:
1. Check WordPress debug logs for "PMV:" entries
2. Verify WooCommerce plugin is active and up to date
3. Check if any other plugins might be interfering with WooCommerce hooks
4. Test with a fresh order to see if the issue is resolved
