# PNG Metadata Viewer - Changelog

## Version 4.15-CONTENT-MODERATION (Current)

### 🆕 New Features

#### Content Moderation System
- **AI-Powered Content Scanning**: New system that uses LLM API to automatically scan conversations for underage or illegal content
- **Scheduled Scanning**: Runs automatically three times per day (every 8 hours) via WordPress cron
- **Risk Classification**: Categorizes flagged content by risk level (low, medium, high, critical)
- **Comprehensive Logging**: Detailed moderation log with user, character, risk level, and content details
- **Admin Interface**: Full admin panel for monitoring, manual scanning, and log management
- **Email Notifications**: Automatic alerts to administrators when content is flagged
- **Dark Theme UI**: Consistent dark theme styling matching user preferences

#### Content Moderation Features
- **Underage Content Detection**: Identifies sexual or inappropriate content involving minors (under 18)
- **Illegal Content Detection**: Detects terrorism, violence, and other illegal activities
- **Child Exploitation Detection**: Flags content involving child abuse or exploitation
- **Conservative Flagging**: Only flags clearly problematic content to minimize false positives
- **Fallback System**: Keyword-based detection if AI analysis fails
- **Content Truncation**: Efficiently handles long conversations within API limits

#### Technical Improvements
- **New Database Table**: `wp_pmv_moderation_log` for storing moderation results
- **WordPress Cron Integration**: Scheduled background processing
- **API Rate Limiting**: Built-in protection against overwhelming the LLM API
- **Performance Optimization**: Batch processing and selective scanning
- **Security Features**: Admin-only access, nonce verification, capability checks

### 🔧 Configuration

#### Required Setup
- OpenAI API key must be configured in plugin settings
- Content moderation can be enabled/disabled via admin interface
- Custom notification email address can be set
- Scan frequency is configurable (default: every 8 hours)

#### Admin Access
- New "Content Moderation" submenu under PNG Metadata Viewer
- Manual scan execution
- Real-time moderation log viewing
- Log management and cleanup tools

### 📁 New Files
- `includes/content-moderation.php` - Core moderation system
- `css/content-moderation-styles.css` - Dark theme admin styling
- `CONTENT_MODERATION_README.md` - Comprehensive documentation
- `test-content-moderation.php` - Testing and verification script

### 🔄 Integration
- Automatically loads with main plugin
- Integrates with existing conversation system
- Uses existing OpenAI API configuration
- Follows established plugin architecture patterns

### 📊 Performance
- **Scan Limits**: Maximum 100 conversations per scan
- **API Efficiency**: 0.5-second delays between API calls
- **Content Truncation**: Limits to 8000 characters for API efficiency
- **Background Processing**: Non-blocking operation via WordPress cron

### 🛡️ Security & Privacy
- **Admin Only Access**: Requires manage_options capability
- **Content Privacy**: Only sends content to configured API
- **Secure Storage**: All data stored securely in WordPress database
- **Audit Trail**: Comprehensive logging for compliance purposes

### 📚 Documentation
- Complete setup and configuration guide
- Troubleshooting and maintenance instructions
- API integration details
- Customization and extension guide

---

## Version 4.14-FIXED

### 🐛 Bug Fixes
- Fixed conversation system integration issues
- Resolved database table creation problems
- Corrected AJAX handler registration

### 🔧 Improvements
- Enhanced error handling and logging
- Improved database optimization
- Better WordPress compatibility

---

## Previous Versions

*For earlier version history, please refer to the plugin repository or contact the development team.*
