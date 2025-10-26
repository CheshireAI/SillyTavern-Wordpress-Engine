# Quick Fix for 504 Gateway Timeout

## The Issue

Your web server (nginx/Apache) is timing out before PHP finishes processing image generation requests.

## I've Already Made Code Changes

The plugin now automatically increases PHP timeouts for image generation requests. However, you also need to configure your web server.

## Which Web Server Are You Using?

### Check Your Web Server

Run this command to find out:

```bash
# For nginx
which nginx && echo "Using nginx" || echo "nginx not found"

# For Apache  
which apache2 && echo "Using Apache" || which httpd && echo "Using Apache (httpd)"

# Check running process
ps aux | grep -E "nginx|apache2|httpd" | grep -v grep
```

## Quick Fixes

### Option A: If Using Nginx

**Step 1**: Edit your nginx configuration:
```bash
sudo nano /etc/nginx/sites-available/your-site-name
# OR
sudo nano /etc/nginx/nginx.conf
```

**Step 2**: Find the `location ~ \.php$` block and add:
```nginx
location ~ \.php$ {
    # ... existing config ...
    
    # Add these lines
    fastcgi_read_timeout 360;
    proxy_read_timeout 360;
    proxy_connect_timeout 360;
    proxy_send_timeout 360;
    fastcgi_send_timeout 360;
}
```

**Step 3**: If you have a server {} block, add:
```nginx
server {
    # ... existing config ...
    
    # Add these at the top level
    proxy_read_timeout 360;
    proxy_connect_timeout 360;
    proxy_send_timeout 360;
}
```

**Step 4**: Test and reload:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Option B: If Using Apache

**Step 1**: Edit Apache configuration:
```bash
sudo nano /etc/apache2/apache2.conf
# OR for specific site
sudo nano /etc/apache2/sites-available/your-site.conf
```

**Step 2**: Add or modify Timeout:
```apache
Timeout 360
ProxyTimeout 360
```

**Step 3**: If using mod_proxy, add to your VirtualHost:
```apache
<VirtualHost *:80>
    # ... existing config ...
    ProxyTimeout 360
    Timeout 360
</VirtualHost>
```

**Step 4**: Reload Apache:
```bash
sudo systemctl reload apache2
```

### Option C: If Using PHP-FPM

**Step 1**: Edit PHP-FPM configuration:
```bash
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
# OR
sudo nano /etc/php/8.0/fpm/pool.d/www.conf
# (Use your PHP version)
```

**Step 2**: Find and set:
```ini
request_terminate_timeout = 360
```

**Step 3**: Restart PHP-FPM:
```bash
sudo systemctl restart php7.4-fpm
# OR
sudo systemctl restart php-fpm
```

### Option D: wp-config.php (Easiest, But May Not Fix Web Server Timeouts)

Add these lines to your `wp-config.php` (before "That's all, stop editing!"):

```php
// Increase timeouts for image generation
ini_set('max_execution_time', 360);
ini_set('max_input_time', 360);
ini_set('memory_limit', '512M');
set_time_limit(360);
```

## After Making Changes

1. **Clear browser cache**
2. **Try generating an image again**
3. **Check if error persists**

## Still Getting 504?

If you're still getting 504 errors after all this, the issue might be:

1. **SwarmUI is taking longer than 6 minutes** - Check SwarmUI logs
2. **Server resources exhausted** - Check memory/CPU usage
3. **CDN/Proxy in front** - If using Cloudflare or similar, check their timeout settings

## Test Your Changes

Create a test file `timeout-test.php` in your WordPress root:

```php
<?php
set_time_limit(360);
sleep(120); // Sleep for 2 minutes
echo "Success! Server timeout is at least 2 minutes.";
```

If you get a 504 before seeing the success message, your web server timeout is still too short.

## Need More Help?

Check logs:
```bash
# Nginx error log
tail -f /var/log/nginx/error.log

# Apache error log  
tail -f /var/log/apache2/error.log

# PHP-FPM log
tail -f /var/log/php-fpm.log

# Or find logs
sudo find /var/log -name "*error*" -type f
```

