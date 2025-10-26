# Nano-GPT Integration Guide

## Overview

Your WordPress plugin now supports both **SwarmUI** and **Nano-GPT** as image generation providers. The system automatically routes requests to the appropriate provider based on your settings.

## Features

### ✅ Complete Integration
- **Provider Selection**: Choose between SwarmUI and Nano-GPT in admin settings
- **Unified Interface**: Single image generation interface that works with both providers
- **Provider-Specific Settings**: Different default parameters for each provider
- **Model Management**: Automatic model loading for each provider
- **Usage Tracking**: Separate usage limits and tracking for each provider
- **Error Handling**: Comprehensive error handling for both providers

### 🔧 Technical Implementation

#### Backend Components
1. **`includes/nanogpt-api-handler.php`** - Nano-GPT specific API handler
2. **`includes/unified-image-handler.php`** - Routes requests to appropriate provider
3. **`includes/admin-settings.php`** - Registers nano-gpt settings
4. **`includes/admin-settings-ui.php`** - Admin UI for nano-gpt configuration

#### Frontend Components
1. **Provider Selection Dropdown** - Choose between SwarmUI and Nano-GPT
2. **Provider-Specific Defaults** - Different parameters for each provider
3. **Model Loading** - Automatic model loading for selected provider
4. **Image Generation** - Unified interface that works with both providers

## Configuration

### 1. Admin Settings

Navigate to your WordPress admin panel and configure the image generation settings:

#### Provider Selection
- **Image Generation Provider**: Choose between "SwarmUI" and "Nano-GPT"

#### Nano-GPT Settings
- **API URL**: `https://nano-gpt.com/api` (default)
- **API Key**: Your nano-gpt API key
- **Default Model**: Select from available models
- **Usage Limits**: Configure daily/monthly limits
- **Image Parameters**: Steps, scale, width, height

#### SwarmUI Settings (existing)
- All existing SwarmUI settings remain unchanged

### 2. Provider-Specific Defaults

#### Nano-GPT Defaults
- **Steps**: 10 (vs 20 for SwarmUI)
- **Scale**: 7.5 (vs 7.0 for SwarmUI)
- **Width/Height**: 1024x1024 (vs 512x512 for SwarmUI)

#### SwarmUI Defaults
- **Steps**: 20
- **Scale**: 7.0
- **Width/Height**: 512x512

## Usage

### 1. Image Generation Interface

The image generation interface automatically adapts to your selected provider:

1. **Open Image Settings** (🎨 button)
2. **Select Provider** (SwarmUI or Nano-GPT)
3. **Choose Model** (automatically loads provider-specific models)
4. **Configure Parameters** (provider-specific defaults applied)
5. **Generate Image** (unified interface)

### 2. API Format

The system handles the different API formats automatically:

#### Nano-GPT API Request
```json
{
  "prompt": "your prompt here",
  "model": "nano-gpt-1.3b",
  "width": 1024,
  "height": 1024,
  "nImages": 1,
  "num_steps": 10,
  "scale": 7.5
}
```

#### Nano-GPT API Response
```json
{
  "image": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
  "cost": 0.001,
  "inputTokens": 10,
  "outputTokens": 1
}
```

### 3. Error Handling

The system provides comprehensive error handling:

- **Authentication Errors**: Clear messages for API key issues
- **Network Errors**: Connection timeout and server error handling
- **Rate Limiting**: Usage limit enforcement
- **Provider-Specific Errors**: Different error messages for each provider

## Testing

### 1. Connection Test

Use the "Test Connection" button in admin settings to verify:
- API URL accessibility
- API key validity
- Model availability

### 2. Image Generation Test

Test image generation with:
- Simple prompts first
- Different models
- Various parameters

### 3. Debug Information

Check the browser console and WordPress error logs for detailed debugging information.

## Troubleshooting

### Common Issues

1. **"API key not configured"**
   - Set your nano-gpt API key in admin settings

2. **"Authentication failed"**
   - Verify your API key is correct
   - Check API key permissions

3. **"Models not loading"**
   - Test connection first
   - Check API URL is correct

4. **"Image generation failed"**
   - Check prompt content
   - Verify model selection
   - Review usage limits

### Debug Steps

1. **Test API Connection**
   ```bash
   curl -X GET "https://nano-gpt.com/api/models" \
     -H "x-api-key: YOUR_API_KEY"
   ```

2. **Test Image Generation**
   ```bash
   curl -X POST "https://nano-gpt.com/api/generate-image" \
     -H "Content-Type: application/json" \
     -H "x-api-key: YOUR_API_KEY" \
     -d '{
       "prompt": "A beautiful landscape",
       "model": "nano-gpt-1.3b",
       "width": 1024,
       "height": 1024,
       "nImages": 1,
       "num_steps": 10,
       "scale": 7.5
     }'
   ```

## Advanced Configuration

### Custom Parameters

You can customize the nano-gpt integration by modifying:

1. **`includes/nanogpt-api-handler.php`** - API call format
2. **`chat.js`** - Frontend behavior
3. **Admin settings** - Default parameters

### Usage Limits

Configure separate limits for:
- **Global daily/monthly limits**
- **User daily/monthly limits**
- **Guest daily limits**

### Model Management

The system automatically:
- Loads available models from the API
- Caches model lists
- Handles model selection
- Validates model availability

## Migration from SwarmUI

If you're switching from SwarmUI to Nano-GPT:

1. **Update Settings**: Change provider in admin settings
2. **Configure API**: Set nano-gpt API key and URL
3. **Test Connection**: Verify API connectivity
4. **Adjust Parameters**: Update default parameters as needed
5. **Test Generation**: Generate test images

## Support

For issues or questions:

1. **Check Error Logs**: WordPress and browser console logs
2. **Test API Directly**: Use curl commands above
3. **Verify Settings**: Check admin configuration
4. **Review Documentation**: This guide and inline code comments

## Future Enhancements

Potential improvements:
- **Real-time Progress**: WebSocket support for progress updates
- **Batch Generation**: Multiple images in one request
- **Advanced Parameters**: More nano-gpt specific options
- **Caching**: Image result caching
- **Analytics**: Detailed usage analytics 