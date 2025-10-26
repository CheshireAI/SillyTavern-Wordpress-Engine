# SwarmUI Connection Troubleshooting

## Error: cURL error 28: Timeout

You're getting a connection timeout error when trying to connect to your SwarmUI server at `100.107.40.105:7801`.

## Quick Fixes

### 1. Test the Connection

I've created a diagnostic script. Run it to check your server:
```bash
php test-swarmui-connection.php
```

This will show you:
- If the server is reachable
- If the port is accessible  
- DNS resolution status
- Detailed error information

### 2. Check if SwarmUI is Running

Try accessing your SwarmUI server directly in a browser:
- http://100.107.40.105:7801/API/GetNewSession
- Or: http://100.107.40.105:7801

If you get a connection error or timeout in your browser, SwarmUI is not running or not accessible.

### 3. Common Issues and Solutions

#### Issue: Server Not Running
**Solution**: Start SwarmUI on your server

#### Issue: Firewall Blocking Port
**Solution**: 
```bash
# Check if port is open
telnet 100.107.40.105 7801

# Or use netcat
nc -zv 100.107.40.105 7801
```

If connection fails, add firewall rule:
```bash
# Ubuntu/Debian
sudo ufw allow 7801/tcp

# Or iptables
sudo iptables -A INPUT -p tcp --dport 7801 -j ACCEPT
```

#### Issue: Wrong IP/Port
**Solution**: Verify your SwarmUI settings in WordPress Admin:
1. Go to WordPress Admin → PNG Metadata Viewer Settings
2. Check the SwarmUI API URL setting
3. Should be: `http://100.107.40.105:7801` or `https://...`

#### Issue: Slow Server Response
**Solution**: I've already increased the timeout from 10s to 30s in the latest code changes. The system now waits longer for responses.

### 4. Verify Your Settings

In WordPress Admin, check these settings:
- **SwarmUI API URL**: Should be `http://100.107.40.105:7801`
- **SwarmUI User Token**: Should be set if authentication is enabled
- **SwarmUI Enabled**: Must be checked

### 5. Network Diagnostics

Run these commands on your server (if you have SSH access):

```bash
# Check if SwarmUI process is running
ps aux | grep swarm

# Check if port is listening
sudo netstat -tuln | grep 7801
# OR
sudo ss -tuln | grep 7801

# Test local connection
curl -X POST http://localhost:7801/API/GetNewSession -H "Content-Type: application/json" -d '{}'
```

### 6. WordPress Configuration

Verify these options in your database or wp-config:
- `pmv_swarmui_api_url` = `http://100.107.40.105:7801`
- `pmv_swarmui_enabled` = `1`

### 7. Test Without Preset System

Since this error appeared after implementing the preset system, you can test if it's the preset system:

1. Try creating an image with the old system (if you have it backed up)
2. Check if the SwarmUI admin panel is working
3. Try a simple API call:

```bash
curl -X POST http://100.107.40.105:7801/API/GetNewSession \
  -H "Content-Type: application/json" \
  -H "Cookie: swarm_user_token=YOUR_TOKEN" \
  -d '{}'
```

### 8. Alternative: Use Nano-GPT

If SwarmUI continues to have connection issues, you can switch to Nano-GPT:

1. Go to WordPress Admin → PNG Metadata Viewer Settings
2. Enable Nano-GPT API
3. Configure Nano-GPT API URL and API Key
4. The system will automatically use Nano-GPT for image generation

## What I've Fixed

I've made the following improvements to handle connection issues better:

1. **Increased Timeout**: Changed from 10 seconds to 30 seconds for API calls
2. **Better Error Handling**: More descriptive error messages
3. **Diagnostic Script**: Created `test-swarmui-connection.php` to help debug

## Still Having Issues?

If you continue to have connection problems:

1. **Check SwarmUI Logs**: Look at SwarmUI server logs for errors
2. **Check WordPress Logs**: Look at WordPress debug logs
3. **Contact Server Admin**: If this is a hosted server, contact your host
4. **Try Different Port**: SwarmUI might be running on a different port

## Next Steps

1. Run the diagnostic script: `php test-swarmui-connection.php`
2. Check server accessibility in browser
3. Verify SwarmUI is running on the server
4. Check firewall rules
5. Try the connection from a different network (to rule out network issues)

## Need More Help?

Check these logs:
- WordPress debug log: `wp-content/debug.log`
- PHP error log: Check your hosting control panel
- SwarmUI logs: On the SwarmUI server
- Browser console: Press F12 and check for errors

