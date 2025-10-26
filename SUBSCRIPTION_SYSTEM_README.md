# PNG Metadata Viewer - Access Plan System

This document explains how to set up and use the access plan system for the PNG Metadata Viewer plugin.

## Overview

The access plan system integrates with WooCommerce to provide tier-based access control for image generation and token usage. Users can purchase access plans to get higher limits or unlimited access for a specific duration. This system works with ANY WooCommerce payment gateway and doesn't require the WooCommerce Subscriptions plugin.

## Features

- **Three Access Plan Tiers**: Basic, Premium, and Ultimate
- **Automatic Product Creation**: Access plan products are created automatically when the plugin is activated
- **WooCommerce Integration**: Seamlessly integrates with existing WooCommerce stores and ANY payment gateway
- **Duration-Based Access**: Users get access for a specific number of days (default: 30 days)
- **Flexible Limits**: Configurable daily image and monthly token limits for each tier
- **Guest User Support**: Different limits for non-logged-in users
- **Admin Management**: Easy management through the WordPress admin panel
- **User Dashboard**: Users can see their remaining time and plan progress

## Access Plan Tiers

### Basic Plan
- **Daily Images**: 200 per day
- **Monthly Tokens**: 50,000 per month
- **Duration**: 30 days
- **Price**: $9.99 one-time
- **Best for**: Regular users who need moderate access

### Premium Plan
- **Daily Images**: 500 per day
- **Monthly Tokens**: 150,000 per month
- **Duration**: 30 days
- **Price**: $19.99 one-time
- **Best for**: Power users who need higher limits

### Ultimate Plan
- **Daily Images**: Unlimited
- **Monthly Tokens**: Unlimited
- **Duration**: 30 days
- **Price**: $39.99 one-time
- **Best for**: Heavy users who need unlimited access

## Requirements

- WordPress 5.0 or higher
- WooCommerce plugin (free)
- **No WooCommerce Subscriptions plugin required** - works with any payment gateway

## Installation

1. **Install WooCommerce**: The access plan system requires WooCommerce to be installed and activated
2. **Activate the Plugin**: Once WooCommerce is active, the access plan system will automatically create the necessary products
3. **Configure Settings**: Go to the plugin's admin panel and adjust the access plan settings as needed

## Configuration

### Admin Settings

Navigate to **PNG Metadata Viewer > Subscriptions** in your WordPress admin panel to:

- View access plan system status
- Configure free user limits
- Configure guest user limits
- Manage access plan products
- Check system health

### Free User Limits

Configure limits for users without access plans:
- **Daily Image Limit**: Maximum images per day (default: 100)
- **Monthly Token Limit**: Maximum tokens per month (default: 25,000)

### Guest User Limits

Configure limits for non-logged-in users:
- **Daily Image Limit**: Maximum images per day (default: 50)
- **Monthly Token Limit**: Maximum tokens per month (default: 10,000)

## Usage

### For Users

Users can:
1. **View their current plan**: See their access plan status, limits, and remaining time
2. **Purchase access plans**: Buy access plan products through your WooCommerce store
3. **Track progress**: See how much time is left on their current plan
4. **Enjoy plan benefits**: Automatically get access to higher limits based on their plan

### For Administrators

Administrators can:
1. **Monitor usage**: Track how users are using the system
2. **Adjust limits**: Modify limits for different user types
3. **Manage products**: Recreate or modify access plan products
4. **View statistics**: See access plan system health and status

## Shortcodes

### Access Plan Status Display

Use the `[pmv_subscription_status]` shortcode to display a user's current access plan status:

```php
[pmv_subscription_status]
```

#### Shortcode Options

- `show_limits="true|false"` - Show/hide usage limits (default: true)
- `show_upgrade="true|false"` - Show/hide upgrade button (default: true)
- `style="default|minimal"` - Choose display style (default: default)

#### Examples

```php
<!-- Basic display -->
[pmv_subscription_status]

<!-- Minimal display without upgrade button -->
[pmv_subscription_status show_upgrade="false"]

<!-- Custom style -->
[pmv_subscription_status style="minimal"]
```

### Access Plan Details Display

Use the `[pmv_access_plan_details]` shortcode to display detailed information about a user's current access plan including remaining time:

```php
[pmv_access_plan_details]
```

#### Shortcode Options

- `show_progress="true|false"` - Show/hide progress bar (default: true)
- `style="default|minimal"` - Choose display style (default: default)

#### Examples

```php
<!-- Full details with progress bar -->
[pmv_access_plan_details]

<!-- Minimal display without progress -->
[pmv_access_plan_details show_progress="false"]
```

## Integration Points

### Rate Limiting

The access plan system integrates with the existing rate limiter to:
- Check access plan limits before allowing image generation
- Enforce daily and monthly limits based on user plan
- Provide appropriate error messages when limits are exceeded

### Image Usage Tracking

The system works with the image usage tracker to:
- Display current usage vs. limits
- Show progress towards daily/monthly limits
- Provide visual indicators when approaching limits

### WooCommerce Integration

The system automatically:
- Creates access plan products on plugin activation
- Handles order completion and plan activation
- Manages access plan expiration
- Integrates with WooCommerce user accounts
- Works with ANY payment gateway (Stripe, PayPal, etc.)

## Troubleshooting

### Common Issues

1. **Products Not Created**
   - Ensure WooCommerce is active
   - Check if the access plan system class is loaded
   - Use the "Recreate Access Plan Products" button in admin

2. **Limits Not Working**
   - Verify access plan products exist in WooCommerce
   - Check user access plan status
   - Ensure proper user meta is set

3. **WooCommerce Not Detected**
   - Install and activate WooCommerce plugin
   - Check plugin compatibility
   - Verify WooCommerce is properly configured

### Debug Information

Enable WordPress debug logging to see detailed information about:
- Product creation
- Access plan activation
- Limit checking
- Error conditions
- Plan expiration

## API Reference

### Access Plan System Class

```php
$subscription_system = PMV_Subscription_System::getInstance();

// Get user's access plan tier
$tier = $subscription_system->get_user_subscription_tier($user_id);

// Get user's limits
$limits = $subscription_system->get_user_limits($user_id);

// Get user's access plan details
$access_plan = $subscription_system->get_user_access_plan($user_id);

// Check if user can generate images
$can_generate = $subscription_system->can_generate_image($user_id);

// Check if user can use tokens
$can_use_tokens = $subscription_system->can_use_tokens($user_id, $token_count);
```

### AJAX Endpoints

- `pmv_get_subscription_status` - Get current user's access plan status
- `pmv_recreate_subscription_products` - Recreate access plan products (admin only)
- `pmv_check_subscription_status` - Check system status (admin only)

## Customization

### Modifying Plan Limits

To modify the default plan limits, edit the `$subscription_tiers` array in `includes/subscription-system.php`:

```php
private $subscription_tiers = array(
    'basic' => array(
        'daily_images' => 300, // Change from 200 to 300
        'monthly_tokens' => 75000, // Change from 50000 to 75000
        'price' => 12.99, // Change price
        'duration_days' => 45, // Change from 30 to 45 days
        // ... other settings
    ),
    // ... other tiers
);
```

### Adding New Plans

To add a new access plan tier:

1. Add the plan configuration to the `$subscription_tiers` array
2. The system will automatically create the product on next activation
3. Update the shortcode display logic if needed

### Custom Limit Logic

Override the default limit checking by extending the access plan system class:

```php
class Custom_Subscription_System extends PMV_Subscription_System {
    public function can_generate_image($user_id = null) {
        // Custom logic here
        return parent::can_generate_image($user_id);
    }
}
```

## Security Considerations

- All AJAX endpoints include nonce verification
- Admin functions check for proper user capabilities
- User data is properly sanitized and validated
- Access plan status is verified before granting access

## Performance Notes

- Access plan checks are cached in user meta
- Product queries are optimized for minimal database impact
- Rate limiting integrates efficiently with existing systems
- Guest user tracking uses cookies for minimal server load

## Support

For issues or questions about the access plan system:

1. Check the WordPress admin panel for system status
2. Review the error logs for detailed information
3. Verify WooCommerce integration is working
4. Test with a simple access plan purchase

## Changelog

### Version 1.0
- Initial release
- Basic, Premium, and Ultimate access plan tiers
- WooCommerce integration (works with ANY payment gateway)
- Duration-based access (30 days by default)
- Admin management interface
- Shortcode support for plan status and details
- Rate limiting integration
- Image usage tracking integration
- User dashboard with remaining time display
