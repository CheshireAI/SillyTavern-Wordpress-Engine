# Preset-Based Image Generation System

## Overview

The image generation system has been redesigned to use a **preset-based approach** where users select from predefined safe categories instead of technical parameters. This prevents jailbreaking and inappropriate content while maintaining creative flexibility.

## Key Changes

### 1. User Interface Changes

**Before**: Users could manipulate technical parameters directly:
- Steps (1-100)
- CFG Scale (0.1-20)
- Width (256-2048, in 64px increments)
- Height (256-2048, in 64px increments)
- Model selection
- Negative prompts

**After**: Users now see simple preset categories:
- **Character**: Selfie, Portrait, Full Body
- **Environment**: Surroundings, Landscape, Room/Interior
- **Action**: Action (dynamic movement), Pose
- **Style**: Close-up, Cute, Serious/Dramatic

### 2. Security Enhancements

#### Content Filtering
The new system includes built-in content filtering that blocks:
- Age-inappropriate keywords (minor, child, teen, underage, young)
- Illegal content keywords
- NSFW indicators
- Common jailbreak patterns

#### Hidden Parameters
All technical parameters are now:
- Configured on the server-side
- Invisible to users
- Automatically selected based on the preset
- Cannot be manipulated or bypassed

### 3. Technical Architecture

#### New Files
- `includes/image-presets.php` - Defines all presets with their technical configurations

#### Modified Files
- `chat.js` - Updated image generation panel HTML to show preset selection
- `js/chat-image-generation.js` - Updated to use preset system
- `png-metadata-viewer.php` - Includes the new presets system

#### Preset Configuration
Each preset includes:
```php
'id' => 'selfie',
'name' => 'Selfie',
'description' => 'A close-up self-portrait of the character',
'category' => 'character',
'config' => array(
    'steps' => 20,
    'cfg_scale' => 7.0,
    'width' => 512,
    'height' => 512,
    'negative_prompt' => 'blurry, distorted, low quality, nsfw',
    'prompt_enhancer' => 'high quality portrait, detailed, professional photography'
)
```

### 4. User Workflow

1. **Select Preset Type**: User chooses from preset categories (Character, Environment, Action, Style)
2. **Describe Intent**: User provides a natural language description
3. **AI Processing**: System:
   - Sanitizes user input
   - Filters inappropriate content
   - Applies preset enhancers
   - Generates final prompt
4. **Generate**: Image is created with hidden technical parameters

### 5. Content Safety Features

#### Blocked Keywords
- Age-inappropriate: minor, child, teen, underage, young
- Illegal: illegal, drugs, violence
- NSFW: nsfw, nude, naked, sexual, explicit

#### Blocked Patterns
- "ignore all previous instructions"
- "forget everything"
- "system prompt"
- "you are now"
- "pretend to be"
- "act as if"
- "disregard all"
- "unrestricted"
- "uncensored"
- "without restrictions"

### 6. Preset Types

#### Character Presets
- **Selfie**: Close-up self-portrait (512x512, 20 steps, CFG 7.0)
- **Portrait**: Detailed character view (768x1024, 25 steps, CFG 7.5)
- **Full Body**: Complete character view (512x768, 25 steps, CFG 7.5)

#### Environment Presets
- **Surroundings**: Current scene (768x512, 25 steps, CFG 7.5)
- **Landscape**: Wide scenic view (1024x512, 30 steps, CFG 7.5)
- **Room/Interior**: Indoor space (768x768, 25 steps, CFG 7.5)

#### Action Presets
- **Action**: Dynamic movement (768x512, 25 steps, CFG 8.0)
- **Pose**: Specific stance (512x768, 25 steps, CFG 7.5)

#### Style Presets
- **Close-up**: Detailed focus (512x512, 30 steps, CFG 8.0)
- **Cute**: Adorable style (512x512, 25 steps, CFG 7.5)
- **Serious**: Dramatic tone (512x768, 25 steps, CFG 7.5)

### 7. Backend Implementation

#### New AJAX Handlers
- `pmv_get_image_presets` - Returns all available presets
- `pmv_generate_image_prompt` - Generates sanitized prompt from user input

#### Sanitization Flow
1. User input received
2. Converted to lowercase for checking
3. Scanned for blocked keywords
4. Scanned for jailbreak patterns
5. HTML tags removed
6. Length limited (max 500 characters)
7. Preset enhancer added
8. Final prompt returned

### 8. Benefits

✅ **Enhanced Security**: No direct access to technical parameters
✅ **Content Safety**: Built-in filtering prevents inappropriate content
✅ **Prevents Jailbreaking**: Hidden system prompts cannot be manipulated
✅ **User-Friendly**: Simple, intuitive interface
✅ **Maintains Flexibility**: Wide variety of preset categories
✅ **Professional Results**: Optimized parameters for each preset type

### 9. Migration Notes

- Old settings are preserved for backward compatibility
- Technical parameter fields are now hidden from users
- Settings modal updated to inform about preset system
- Existing slash commands still work but use preset system

### 10. Configuration

To add or modify presets, edit `includes/image-presets.php`:

```php
public static function get_presets() {
    return array(
        'your_preset_id' => array(
            'id' => 'your_preset_id',
            'name' => 'Your Preset Name',
            'description' => 'Description of what this preset does',
            'category' => 'category_name',
            'config' => array(
                'steps' => 25,
                'cfg_scale' => 7.5,
                'width' => 512,
                'height' => 768,
                'negative_prompt' => 'blocked terms',
                'prompt_enhancer' => 'quality enhancement terms'
            )
        )
    );
}
```

### 11. Testing

Test the system:
1. Open image generation panel
2. Select a preset category
3. Enter description
4. Verify prompt generation
5. Check that inappropriate content is blocked
6. Verify image generation with hidden parameters

### 12. Future Enhancements

- Add more preset categories
- Implement preset previews
- Add preset voting/rating system
- Implement smart preset recommendations
- Add preset analytics

---

**Version**: 1.0  
**Date**: 2024  
**Status**: Production Ready

