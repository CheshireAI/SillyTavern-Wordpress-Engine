# Credit Display Troubleshooting Guide

## Current Issue
The credit display in the chat modal header is showing "0" for both TOKEN CREDITS and IMAGE CREDITS, even though the status shortcode shows actual credit balances.

## Root Cause Analysis
The issue was caused by **missing nonce verification** in the AJAX credit endpoints, resulting in HTTP 400 errors. The JavaScript was correctly detecting the user as logged in and attempting to fetch credits, but the server was rejecting the requests due to security validation failures.

**UPDATE**: After fixing the nonce verification, we discovered a secondary issue: **unwanted output before JSON responses**. The server was returning HTTP 400 with response text `"0"`, which suggests something was outputting content before the JSON response, breaking the response format.

**FINAL SOLUTION**: Instead of debugging complex AJAX issues, we created a **working credit endpoint that mimics exactly what the working subscription shortcode does**. This approach bypasses all the complex subscription system methods and uses the same direct PHP calls that the shortcode uses successfully.

**ADDITIONAL FIX**: We also resolved the **fixed 25 tokens per message** issue by removing premature credit consumption that was based on estimated tokens instead of actual API usage.

## What We've Implemented

### 1. **Fixed Security Issue**
- Added nonce verification to `ajax_get_credit_status()` method
- Added nonce verification to `test_credit_system()` method  
- Added nonce verification to `ajax_check_daily_reward()` method
- All credit-related AJAX endpoints now properly validate security tokens

### 2. **Created Working Credit Endpoint**
- Added `pmv_get_working_credits` endpoint that mimics the shortcode exactly
- Uses the same `get_user_credits()` and `get_current_usage()` calls as the shortcode
- Bypasses complex subscription system methods that were causing issues
- Returns the exact same data structure that the shortcode displays

### 3. **Fixed Fixed Token Consumption Issue**
- Removed premature rate limiting that consumed credits based on estimated tokens
- Added credit consumption **after** API response based on actual tokens used
- This ensures credits consumed match actual API usage, not rough estimates
- Eliminates the "25 tokens per message" problem

### 4. **Simplified JavaScript Logic**
- Replaced complex multi-endpoint testing with single working endpoint
- Direct credit display update without fallback complexity
- Cleaner error handling and logging
- Immediate fallback to guest credits if the working endpoint fails

### 5. **Fixed Output Buffer Issue**
- Added output buffer cleanup to all AJAX endpoints
- Added debug endpoint to check for unwanted output
- Enhanced error handling with comprehensive logging

### 6. **Enhanced Error Handling**
- Added comprehensive debugging to the credit loading function
- Added checks for missing AJAX object and URL
- Added user authentication checks
- Added guest user support with fallback limits

### 7. **Improved User Experience**
- Added immediate guest credit display as fallback
- Added retry mechanism for timing issues
- Added nonce test endpoint for debugging
- Enhanced error logging with HTTP response details

### 8. **Guest User Support**
- Added guest limits to the AJAX object
- Guest users now see their daily/monthly limits instead of errors
- Guest limits are styled differently (yellow/orange) to distinguish from logged-in users

### 9. **Debug Function**
- Added `window.debugCredits()` function for troubleshooting
- Can be called from browser console to diagnose issues

## Troubleshooting Steps

### Step 1: Check Browser Console
Open the browser console and look for:
- PMV credit loading messages
- AJAX object status
- Credit endpoint responses
- **HTTP 400 errors** (indicates nonce/security issues)

### Step 2: Run Debug Function
In the browser console, run:
```javascript
debugCredits()
```

This will show:
- AJAX object status
- User authentication status
- Guest limits (if available)
- Credit element status
- Test the credit endpoint directly

### Step 3: Check User Status
Verify if the user is logged in:
- Check `pmv_ajax_object.user_id`
- Check `pmv_ajax_object.is_logged_in`

### Step 4: Check AJAX Object
Verify the AJAX object is properly localized:
- Check if `pmv_ajax_object` exists
- Check if `pmv_ajax_object.ajax_url` is set
- Check if `pmv_ajax_object.nonce` is set

### Step 5: Test Nonce Validation (NEW)
Test the nonce validation specifically:
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_test_nonce',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Nonce test successful:', response);
    },
    error: function(xhr, status, error) {
        console.error('Nonce test failed:', {xhr, status, error});
    }
});
```

### Step 6: Test Working Credit Endpoint (NEW - RECOMMENDED)
Test the working credit endpoint that mimics the shortcode exactly:
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_get_working_credits',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Working credit endpoint successful:', response);
        if (response.success && response.data.credits) {
            console.log('Credits:', response.data.credits);
            console.log('Usage:', response.data.usage);
        }
    },
    error: function(xhr, status, error) {
        console.error('Working credit endpoint failed:', {xhr, status, error});
    }
});
```

### Step 7: Test Simple Credit Endpoint (Alternative)
Test the simplified credit endpoint:
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_test_simple_credits',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Simple credit test successful:', response);
    },
    error: function(xhr, status, error) {
        console.error('Simple credit test failed:', {xhr, status, error});
    }
});
```

### Step 8: Check for Output Buffer Issues (Debugging)
Test the debug endpoint to check for unwanted output:
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_debug_output',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Output debug successful:', response);
        if (response.data.output_buffers && response.data.output_buffers.length > 0) {
            console.warn('Unwanted output detected:', response.data.output_buffers);
        }
    },
    error: function(xhr, status, error) {
        console.error('Output debug failed:', {xhr, status, error});
    }
});
```

## Expected Behavior After Fix

### Logged-in Users
- Should see actual credit balances
- Credits should update every 30 seconds
- Low credit warnings should appear when appropriate
- **No more HTTP 400 errors**

### Guest Users
- Should see guest limits (daily/monthly)
- Credits should be styled differently (yellow/orange)
- No AJAX calls should be made

## Specific "Stuck at Zero" Debugging

### Check 1: User Authentication Status
```javascript
console.log('User ID:', pmv_ajax_object?.user_id);
console.log('Is Logged In:', pmv_ajax_object?.is_logged_in);
```

### Check 2: Guest Limits Availability
```javascript
console.log('Guest Limits:', pmv_ajax_object?.guest_limits);
```

### Check 3: Credit Elements Status
```javascript
console.log('Token Credits Element:', jQuery('#token-credits').length ? jQuery('#token-credits').text() : 'Not found');
console.log('Image Credits Element:', jQuery('#image-credits').length ? jQuery('#image-credits').text() : 'Not found');
```

### Check 4: Nonce Validation (CRITICAL)
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_test_nonce',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Nonce validation successful:', response);
    },
    error: function(xhr, status, error) {
        console.error('Nonce validation failed:', {xhr, status, error});
        console.error('HTTP Status:', xhr.status);
        console.error('Response:', xhr.responseText);
    }
});
```

### Check 5: Credit Endpoint Test
```javascript
jQuery.ajax({
    url: pmv_ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'pmv_get_credit_status',
        nonce: pmv_ajax_object.nonce
    },
    success: function(response) {
        console.log('Credit endpoint response:', response);
    },
    error: function(xhr, status, error) {
        console.error('Credit endpoint error:', {xhr, status, error});
        console.error('HTTP Status:', xhr.status);
        console.error('Response:', xhr.responseText);
    }
});
```

## Common Issues and Solutions

### Issue 1: "No AJAX" Displayed
**Cause**: `pmv_ajax_object` is not defined
**Solution**: Check if scripts are properly enqueued and localized

### Issue 2: "No URL" Displayed
**Cause**: `pmv_ajax_object.ajax_url` is missing
**Solution**: Verify `pmv_ajax_object.ajax_url` is set

### Issue 3: "Guest" Displayed
**Cause**: User is not logged in or detected as guest
**Solution**: Check user authentication and login status

### Issue 4: "N/A" Displayed
**Cause**: AJAX failed and no guest limits available
**Solution**: Check network connectivity and server status

### Issue 5: "0" Displayed (Current Issue)
**Cause**: Logic error in credit loading or display
**Solution**: 
1. Check if user is actually logged in
2. Verify credit endpoint returns correct data
3. Check if credit elements are properly updated
4. Verify timing of credit loading function calls

## Quick Fix Attempts

### Fix 1: Force Refresh Credits
```javascript
if (window.PMV_ChatCore && window.PMV_ChatCore.loadCreditBalances) {
    window.PMV_ChatCore.loadCreditBalances();
}
```

### Fix 2: Check Credit Elements
```javascript
jQuery('#token-credits').text('Testing...');
jQuery('#image-credits').text('Testing...');
```

### Fix 3: Verify AJAX Object
```javascript
console.log('Full AJAX Object:', pmv_ajax_object);
```

## Next Steps
If the issue persists after running these debugging steps, the problem is likely:
1. A server-side issue with the credit endpoint
2. A WordPress authentication issue
3. A JavaScript timing issue where credits load before the modal is ready
4. A CSS issue hiding the credit display

## FIXED: Image Generation No Longer Decreases Image Credits

### Issue Description
Image generation was not decreasing image credits because of a design flaw in the rate limiter system. The problem was:

1. **Premature Credit Consumption**: Credits were consumed at the **rate limit check stage**, not when images were actually generated
2. **Asynchronous Mismatch**: The rate limiter was designed for synchronous operations, but WebSocket image generation is asynchronous
3. **Failed Generation Still Consumes Credits**: If WebSocket generation failed or was cancelled, credits were already gone

### Root Cause
The rate limiter's `isAllowed()` method was calling `consumeCreditsForAction()` immediately when the rate limit check passed, but this happened **before** the actual image generation via WebSocket. This created a chicken-and-egg problem where:

- Rate limiter checks if user has credits and allows the action
- **Credits are consumed immediately** when rate limit check passes
- WebSocket connection is established for actual image generation
- If WebSocket fails or is cancelled, credits are already gone

### Solution Implemented

#### 1. **Modified Rate Limiter** (`includes/rate-limiter.php`)
- **Before**: Credits consumed immediately when rate limit check passes
- **After**: Credits only consumed for synchronous actions (chat messages)
- **Image generation**: Credits checked for availability but not consumed until after successful generation

```php
// NOTE: Credits are no longer consumed here for image_generation
// because it's an asynchronous operation. Credits will be consumed
// after successful image generation via a separate endpoint.
// For chat_message, credits are consumed after actual API usage.

// Only consume credits for synchronous actions that complete immediately
if ($action === 'chat_message' && $user_id > 0 && class_exists('PMV_Subscription_System')) {
    $this->consumeCreditsForAction($action, $user_id);
}
```

#### 2. **New Credit Consumption Endpoint** (`includes/swarmui-api-handler.php`)
- Added `ajax_consume_image_credits()` method
- Called **after** successful image generation
- Consumes credits based on actual number of images generated
- Returns new credit balance for UI updates

```php
/**
 * AJAX: Consume image credits after successful image generation
 * This is called from the frontend after images are successfully generated
 */
public function ajax_consume_image_credits() {
    // Validate nonce and user authentication
    // Consume credits for the generated images
    // Return new balance for UI updates
}
```

#### 3. **Updated Frontend JavaScript** (`chat.js`)
- Added `consumeImageCredits()` function
- Called after successful WebSocket image generation
- Called after successful regular image generation
- Updates credit display automatically
- Made function globally available for other modules

```javascript
// Consume image credits after successful generation
function consumeImageCredits(imagesCount) {
    // AJAX call to consume credits
    // Update credit display
    // Handle errors gracefully
}

// Make consumeImageCredits available globally
window.consumeImageCredits = consumeImageCredits;
```

#### 4. **Enhanced WebSocket Handling**
- Added support for multiple images in WebSocket responses
- Credits consumed based on actual number of images generated
- Proper error handling without credit consumption

#### 5. **Unified Handler Support** (`includes/unified-image-handler.php`)
- New endpoint works regardless of active image provider
- Consistent credit consumption across all providers

### How It Works Now

#### **Before (Broken)**:
1. User requests image generation
2. Rate limiter checks credits and allows action
3. **Credits consumed immediately** ❌
4. WebSocket generation starts
5. If generation fails, credits are already gone ❌

#### **After (Fixed)**:
1. User requests image generation
2. Rate limiter checks credits are available (but doesn't consume)
3. WebSocket generation starts
4. **Credits consumed only after successful generation** ✅
5. If generation fails, no credits are consumed ✅

### Benefits of the Fix

1. **Accurate Credit Tracking**: Credits only consumed for actually generated images
2. **Better User Experience**: No lost credits due to failed generations
3. **Consistent Behavior**: Same logic for WebSocket and regular image generation
4. **Error Resilience**: Failed generations don't affect credit balance
5. **Real-time Updates**: Credit display updates immediately after consumption

### Testing the Fix

1. **Generate an image successfully**: Credits should decrease by 1
2. **Generate multiple images**: Credits should decrease by the number generated
3. **Cancel generation mid-process**: Credits should not decrease
4. **Check credit balance**: Should update immediately after successful generation
5. **Verify error handling**: Failed generations should not consume credits

### Files Modified

- `includes/rate-limiter.php` - Modified credit consumption logic
- `includes/swarmui-api-handler.php` - Added new credit consumption endpoint
- `includes/unified-image-handler.php` - Added endpoint to unified handler
- `chat.js` - Added credit consumption calls and global function
- `js/chat-image-generation.js` - Added credit consumption to results display

### Credit Consumption Flow

```
User Requests Image → Rate Limiter Checks Credits → WebSocket Generation Starts
                                                           ↓
                                                    Generation Completes
                                                           ↓
                                                    Credits Consumed (1 per image)
                                                           ↓
                                                    Credit Display Updates
                                                           ↓
                                                    User Sees New Balance
```

This fix ensures that image generation now properly decreases image credits as expected, with credits only consumed after successful generation rather than prematurely during the rate limit check.
