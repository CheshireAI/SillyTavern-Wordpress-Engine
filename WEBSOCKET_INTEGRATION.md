# WebSocket Integration for SwarmUI

This document explains the WebSocket integration that has been added to your SwarmUI image generation system.

## Overview

The network requests you observed show that your SwarmUI server supports WebSocket connections for real-time image generation updates. The WebSocket endpoint is:

```
ws://100.107.40.105:7801/API/GenerateText2ImageWS
```

## What's New

### 1. Enhanced Error Handling
- Better error messages for different HTTP status codes
- WebSocket support detection
- Improved session management

### 2. Dark Theme Styling
- Complete dark theme override for the image generation interface
- Consistent styling with your preferred dark background and light text
- Better visual feedback for loading states and errors

### 3. WebSocket Support Detection
- Automatic detection of WebSocket availability
- Fallback to HTTP requests when WebSocket is not available
- Real-time progress updates (future enhancement)

## How It Works

### Current Implementation
1. **HTTP Fallback**: The system primarily uses HTTP requests for image generation
2. **WebSocket Detection**: Automatically detects if WebSocket is available
3. **Enhanced UI**: Better error messages and loading indicators

### Future WebSocket Implementation
The WebSocket connection can be used for:
- Real-time progress updates during image generation
- Immediate notification when generation starts/completes
- Reduced server load compared to polling

## Testing Your Setup

### 1. Use the Test Page
Access `https://yoursite.com/test-swarmui.php` to test:
- Basic connection to SwarmUI
- WebSocket connectivity
- Session creation
- Model loading
- Image generation

### 2. Check WebSocket Support
The test page includes a WebSocket test that will:
- Try to connect to the WebSocket endpoint
- Show if the connection is successful
- Provide feedback on WebSocket availability

## Configuration

### Required Settings
1. **SwarmUI API URL**: `http://100.107.40.105:7801`
2. **User Token**: Get from SwarmUI web interface cookies
3. **Enable Integration**: Check the box in WordPress admin

### Optional Settings
- **Default Model**: Set your preferred model
- **Usage Limits**: Configure daily/monthly limits
- **Auto-trigger Keywords**: Words that automatically trigger image generation

## Network Requests Explained

The requests you observed:

1. **WebSocket Connection**: `ws://100.107.40.105:7801/API/GenerateText2ImageWS`
   - Real-time connection for image generation updates
   - Currently detected but not fully utilized

2. **Model Loading**: `http://100.107.40.105:7801/ViewSpecial/Stable-Diffusion/...`
   - Loading the STOIQOAfroditeFLUXXL_F1DAlpha model
   - This is the model being used for generation

3. **Image Results**: `data:image/jpeg;base64,...`
   - Base64-encoded generated images
   - Multiple images were generated in sequence

4. **Final Image**: `http://100.107.40.105:7801/View/local/raw/...`
   - The final generated PNG image
   - Stored on the SwarmUI server

## Troubleshooting

### WebSocket Issues
- **Connection Failed**: WebSocket is optional, HTTP fallback will work
- **Port Issues**: Ensure port 7801 is accessible
- **Firewall**: Check if WebSocket connections are blocked

### Image Generation Issues
- **Authentication**: Verify your user token is correct
- **Model Loading**: Check if the model exists on your SwarmUI server
- **Network**: Ensure the SwarmUI server is accessible

### Dark Theme Issues
- **CSS Loading**: Make sure the dark theme CSS is loaded
- **Browser Cache**: Clear browser cache if styles don't update
- **WordPress**: Check if the CSS file is being served correctly

## Next Steps

### Immediate
1. Test the WebSocket connection using the test page
2. Verify image generation works with the enhanced UI
3. Check that the dark theme is applied correctly

### Future Enhancements
1. **Real-time Progress**: Implement WebSocket for live progress updates
2. **Batch Generation**: Support for generating multiple images simultaneously
3. **Advanced Parameters**: Add more SwarmUI-specific parameters
4. **Image History**: Store and display previously generated images

## API Endpoints Used

- `GET /API/GetModels` - Get available models
- `POST /API/GenerateText2Image` - Generate images via HTTP
- `WS /API/GenerateText2ImageWS` - WebSocket endpoint (detected)
- `GET /View/local/raw/...` - Retrieve generated images

## Support

If you encounter issues:
1. Check the WordPress error logs
2. Use the test page to isolate problems
3. Verify SwarmUI server logs
4. Test WebSocket connectivity separately

The system is designed to gracefully fall back to HTTP requests if WebSocket is not available, so your image generation will continue to work regardless of WebSocket support. 