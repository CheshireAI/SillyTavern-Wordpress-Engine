# Fixing 504 Gateway Timeout Error

## The Problem

A 504 Gateway Timeout means your web server (nginx, apache, or the proxy in front of it) is closing the connection before PHP finishes processing the image generation request.

Even though we've set PHP to allow 5-minute execution, your web server might have a shorter timeout.

## Solutions

### Solution 1: Nginx Configuration (Most Common)

If you're using Nginx, add this to your site configuration:

```nginx
# In /etc/nginx/sites-available/your-site or nginx.conf
location ~ \.php$ {
    fastcgi_read_timeout 360;  # 6 minutes
    proxy_read_timeout 360;     # 6 minutes
    proxy_connect_timeout 360;  # 6 minutes
    proxy_send_timeout 360;     # 6 minutes
}

# If using reverse proxy
proxy_read_timeout 360;
proxy_connect_timeout 360;
proxy_send_timeout 360;
```

Then reload nginx:
```bash
sudo nginx -t  # Test configuration
sudo systemctl reload nginx
```

### Solution 2: Apache/.htaccess Configuration

Add this to your `.htaccess` file in the WordPress root or plugin directory:

```apache
# Increase timeout limits
php_value max_execution_time 360
php_value max_input_time 360

# For Apache with Proxy
ProxyTimeout 360
Timeout 360
```

### Solution 3: PHP-FPM Configuration

If you're using PHP-FPM, edit `/etc/php/7.x/fpm/pool.d/www.conf` or php-fpm.conf:

```ini
request_terminate_timeout = 360
max_execution_time = 360
```

Then restart PHP-FPM:
```bash
sudo systemctl restart php7.x-fpm
# or
sudo systemctl restart php-fpm
```

### Solution 4: WP-Config.php

Add to your `wp-config.php` file (if you have access):

```php
// Increase script execution time
ini_set('max_execution_time', 360);
ini_set('max_input_time', 360);
set_time_limit(360);

// Increase memory limit for image processing
ini_set('memory_limit', '512M');
```

### Solution 5: Asynchronous Processing (Best for Production)

The best solution for long-running image generation is to make it asynchronous using a queue system, but that's more complex. For now, try the above solutions.

## Quick Test

To check if your current timeout is too short, you can test by adding this to your WordPress:

```php
// In wp-config.php or as a test
ignore_user_abort(true);
set_time_limit(0);

// Then add this to see what the timeout is
sleep(120); // Sleep for 2 minutes
echo "Still running after 2 minutes!";
```

If you get a 504 before the message appears, your web server timeout is less than 2 minutes.

## Immediate Workaround

While you fix the server configuration, you can reduce the complexity of requests:

1. **Use smaller images**: 512x512 instead of larger sizes
2. **Use fewer steps**: 20 steps instead of 30
3. **Use faster models** if available

## Check Your Current Timeouts

Find what's timing out:

```bash
# Check nginx configuration
cat /etc/nginx/nginx.conf | grep timeout
cat /etc/nginx/sites-available/default | grep timeout

# Check Apache configuration  
cat /etc/apache2/apache2.conf | grep Timeout

# Check PHP-FPM
cat /etc/php/7.x/fpm/pool.d/www.conf | grep timeout

# Check PHP settings
php -i | grep max_execution_time
php -i | grep request_terminate_timeout
```

## Recommended Configuration

For image generation, these are the minimum recommended settings:

- **PHP**: `max_execution_time = 360` (6 minutes)
- **Nginx**: `proxy_read_timeout = 360`
- **Apache**: `ProxyTimeout = 360`
- **PHP-FPM**: `request_terminate_timeout = 360`
- **WordPress**: Already configured via our code changes (5 minutes)

## After Making Changes

1. Restart your web server
2. Clear any caching
3. Try the image generation again

## Need Help?

If you're still getting 504 errors after increasing all timeouts, the issue might be:
1. SwarmUI server itself is slow/unresponsive
2. Network congestion
3. Resource limits on the server (memory, CPU)

Check SwarmUI logs for clues.

