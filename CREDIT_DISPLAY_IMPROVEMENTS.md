# Chat Modal Header Credit Display Improvements

## Overview
This document outlines the improvements made to the chat modal header to display remaining token credits and image credits instead of usage statistics.

## Changes Made

### 1. HTML Structure Updates (`chat.js`)
- **Replaced usage display**: Changed from showing "Tokens Today/Month" and "Images Today/Month" to showing current credit balances
- **New credit display**: Added `credit-balance-display` div with token and image credit amounts
- **Refresh button**: Added a refresh button (🔄) to manually update credit balances
- **Responsive design**: Credit display is hidden on mobile devices (≤768px) to save space

### 2. JavaScript Functionality (`js/chat-core.js`)
- **New function**: `loadCreditBalances()` - Fetches credit balances from the subscription system via AJAX
- **Credit status endpoint**: Uses existing `pmv_get_credit_status` AJAX endpoint
- **Low credit warnings**: Automatically adds visual indicators when credits are low:
  - Token credits < 1,000: Red pulsing animation
  - Image credits < 10: Red pulsing animation
- **Periodic updates**: Automatically refreshes credit balances every 30 seconds
- **Manual refresh**: Click handler for the refresh button with visual feedback

### 3. CSS Styling (`chat.js`)
- **Modern design**: Gradient background with subtle borders and shadows
- **Visual hierarchy**: Credit label with emoji icon (💰 Credits) positioned above the display
- **Interactive elements**: Hover effects on credit amounts and refresh button
- **Responsive layout**: Adapts to different screen sizes
- **Animation effects**: Smooth transitions and hover animations

### 4. Integration Points
- **Chat initialization**: Credit balances are loaded when the full-screen chat starts
- **Periodic updates**: Background updates every 30 seconds to keep balances current
- **Error handling**: Graceful fallback to "N/A" or "Error" if credit loading fails
- **Mobile optimization**: Credit display is hidden on small screens to maintain usability

## Technical Details

### AJAX Endpoint
- **Action**: `pmv_get_credit_status`
- **Response**: 
  ```json
  {
    "success": true,
    "data": {
      "credits": {
        "text_credits": 1250,
        "image_credits": 45
      },
      "usage": {...},
      "can_generate_image": true,
      "can_generate_text": true
    }
  }
  ```

### CSS Classes
- `.credit-balance-display` - Main container with gradient background
- `.credit-label` - Text labels for credit types
- `.credit-amount` - Credit amount display with hover effects
- `.credit-amount.low-credits` - Low credit warning styling
- `.refresh-credits-btn` - Refresh button with rotation animation

### JavaScript Functions
- `loadCreditBalances()` - Main function to fetch and display credits
- `startCreditBalanceUpdates()` - Sets up periodic updates and event handlers
- Event handlers for refresh button clicks and hover effects

## Benefits

### User Experience
- **Real-time information**: Users can see their current credit balances at a glance
- **Low credit warnings**: Visual indicators help users know when to purchase more credits
- **Easy refresh**: Manual refresh button for immediate updates
- **Clean design**: Modern, professional appearance that fits the existing UI

### Technical Benefits
- **Efficient updates**: Periodic background updates without user intervention
- **Responsive design**: Works seamlessly across desktop and mobile devices
- **Error resilience**: Graceful handling of network issues or API failures
- **Performance**: Lightweight implementation with minimal impact on chat performance

## Mobile Considerations

### Responsive Behavior
- **Desktop**: Full credit display with all features visible
- **Mobile (≤768px)**: Credit display is hidden to save vertical space
- **Touch-friendly**: Refresh button sized appropriately for mobile interaction

### Space Optimization
- **Header height**: Maintains reasonable header height on mobile devices
- **Content priority**: Focuses on essential chat functionality on small screens
- **Performance**: Reduces DOM complexity on mobile devices

## Future Enhancements

### Potential Improvements
1. **Credit purchase integration**: Direct link to credit purchase page
2. **Usage history**: Expandable section showing recent credit usage
3. **Credit expiration**: Display countdown for credits with expiration dates
4. **Tier indicators**: Visual representation of user's subscription tier
5. **Notifications**: Push notifications for low credit warnings

### Technical Enhancements
1. **WebSocket updates**: Real-time credit balance updates via WebSocket
2. **Caching**: Local storage caching for offline credit display
3. **Analytics**: Track credit usage patterns and user behavior
4. **A/B testing**: Test different credit display layouts and messaging

## Testing

### Test File
- **Location**: `test-credit-display.html`
- **Purpose**: Demonstrates credit display functionality and styling
- **Features**: Interactive demo with simulated credit scenarios
- **Responsive testing**: Test different screen sizes and interactions

### Test Scenarios
1. **Normal credits**: Display with adequate credit balances
2. **Low credits**: Visual warnings for low credit situations
3. **Refresh functionality**: Manual refresh button operation
4. **Mobile responsiveness**: Behavior on small screen devices
5. **Error handling**: Graceful degradation when API calls fail

## Conclusion

The credit display improvements provide users with immediate visibility into their available credits while maintaining a clean, professional interface. The implementation is efficient, responsive, and integrates seamlessly with the existing chat system architecture.

The solution addresses the user's request to show remaining credits instead of usage statistics, providing a more useful and actionable information display in the chat modal header.
