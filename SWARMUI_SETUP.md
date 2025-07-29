# SwarmUI Integration Setup Guide

This guide will help you set up the SwarmUI integration with proper authentication for your PNG Metadata Viewer plugin.

## Prerequisites

1. **SwarmUI Server**: You need a running SwarmUI instance
2. **User Account**: You need a user account on your SwarmUI server
3. **WordPress Admin Access**: You need admin access to your WordPress site

## Step 1: Get Your SwarmUI User Token

1. **Log into your SwarmUI web interface**
   - Open your browser and navigate to your SwarmUI server (e.g., `http://localhost:7801`)
   - Log in with your user credentials

2. **Extract the User Token**
   - Open your browser's Developer Tools (F12)
   - Go to the **Application** or **Storage** tab
   - Look for **Cookies** in the left sidebar
   - Find the cookie named `swarm_user_token`
   - Copy the value of this cookie

   **Alternative method:**
   - In the Developer Tools, go to the **Console** tab
   - Type: `document.cookie.split(';').find(c => c.trim().startsWith('swarm_user_token=')).split('=')[1]`
   - This will display your user token

## Step 2: Configure WordPress Settings

1. **Go to WordPress Admin**
   - Navigate to your WordPress admin panel
   - Go to **Settings** → **PNG Metadata Viewer**

2. **Configure SwarmUI Settings**
   - **Enable SwarmUI Integration**: Check this box
   - **SwarmUI API URL**: Enter your SwarmUI server URL (e.g., `http://localhost:7801`)
   - **SwarmUI API Key**: Leave empty (not needed with user token authentication)
   - **SwarmUI User Token**: Paste the user token you copied in Step 1
   - **Default Model**: Set your preferred default model (e.g., `OfficialStableDiffusion/sd_xl_base_1.0`)

3. **Set Usage Limits** (Optional)
   - Configure daily/monthly limits for users and guests
   - Set global limits to prevent abuse

4. **Save Settings**
   - Click **Save Changes**

## Step 3: Test the Integration

1. **Use the Test Script**
   - Upload the `test-swarmui.php` file to your WordPress root directory
   - Access it via browser: `https://yoursite.com/test-swarmui.php`
   - Run each test to verify the integration

2. **Test from the Admin Panel**
   - Go to **Settings** → **PNG Metadata Viewer** → **SwarmUI** tab
   - Click **Test Connection** button
   - Verify that the connection is successful

## Step 4: Test Image Generation in Chat

1. **Start a Chat**
   - Go to your PNG Metadata Viewer page
   - Click on a character image to open the modal
   - Click **Chat** to start a conversation

2. **Test Image Generation**
   - In the chat, click the **🎨** button in the top right
   - Configure your image generation settings
   - Try generating an image using `/generate` command or the image panel

## Troubleshooting

### Common Issues

1. **"Connection failed" error**
   - Verify your SwarmUI API URL is correct
   - Check that your SwarmUI server is running
   - Ensure the user token is valid and not expired

2. **"Session creation failed" error**
   - Verify your user token is correct
   - Check that your SwarmUI server requires authentication
   - Try logging out and back into SwarmUI to get a fresh token

3. **"No models available" error**
   - Check that your SwarmUI server has models loaded
   - Verify the user token has access to the models
   - Check SwarmUI server logs for any errors

4. **"Image generation failed" error**
   - Check that the model specified exists on your SwarmUI server
   - Verify the generation parameters are valid
   - Check SwarmUI server logs for detailed error messages

### Debug Steps

1. **Check WordPress Error Logs**
   - Look in your WordPress error logs for any PHP errors
   - Check the browser console for JavaScript errors

2. **Check SwarmUI Server Logs**
   - Look at your SwarmUI server logs for API request errors
   - Verify that requests are reaching the server

3. **Test Direct API Calls**
   - Use curl or Postman to test direct API calls to SwarmUI
   - Example curl command:
     ```bash
     curl -H "Content-Type: application/json" \
          -H "Cookie: swarm_user_token=YOUR_TOKEN" \
          -d '{}' \
          -X POST http://localhost:7801/API/GetNewSession
     ```

## API Endpoints Used

The integration uses the following SwarmUI API endpoints:

- `POST /API/GetNewSession` - Create a new session
- `POST /API/ListT2IParams` - Get available models
- `POST /API/GenerateText2Image` - Generate images

## Security Notes

1. **User Token Security**
   - Keep your user token secure and don't share it
   - The token is stored in WordPress options (encrypted in production)
   - Consider rotating the token periodically

2. **Rate Limiting**
   - The plugin includes built-in rate limiting
   - Configure appropriate limits in the admin settings
   - Monitor usage to prevent abuse

3. **Network Security**
   - Ensure your SwarmUI server is properly secured
   - Use HTTPS in production environments
   - Consider firewall rules to restrict access

## Advanced Configuration

### Custom Models
You can specify custom models in the settings. The default model will be used when no specific model is selected.

### Image Parameters
Configure default image generation parameters:
- **Steps**: Number of denoising steps (1-100)
- **CFG Scale**: Guidance scale (0.1-20)
- **Width/Height**: Image dimensions (256-2048, multiples of 64)

### Slash Commands
The chat interface supports several slash commands:
- `/generate [prompt]` - Generate an image with custom prompt
- `/self` - Generate a selfie based on character and context
- `/look` - Generate an image of current surroundings
- `/custom1`, `/custom2`, `/custom3` - Custom commands

## Support

If you encounter issues:

1. Check the WordPress error logs
2. Verify SwarmUI server is running and accessible
3. Test with the provided test script
4. Check that all settings are configured correctly

For additional help, refer to the SwarmUI documentation or contact your system administrator. 