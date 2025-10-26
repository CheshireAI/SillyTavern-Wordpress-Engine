# Content Moderation System

## Overview

The Content Moderation System is a comprehensive solution that uses AI (LLM API) to automatically scan conversations for underage or illegal content. It runs three times per day and provides detailed logging and admin controls.

## Features

- **Automatic Scanning**: Runs every 8 hours (3 times per day)
- **AI-Powered Analysis**: Uses OpenAI/LLM API for intelligent content detection
- **Risk Classification**: Categorizes content by risk level (low, medium, high, critical)
- **Comprehensive Logging**: Tracks all flagged content with detailed information
- **Admin Interface**: Full admin panel for monitoring and manual scanning
- **Email Notifications**: Alerts administrators when content is flagged
- **Dark Theme UI**: Consistent with user preferences

## What Gets Flagged

The system specifically looks for:
1. **Underage Content**: Sexual or inappropriate content involving minors (under 18)
2. **Terrorism**: Content promoting or describing terrorist activities
3. **Illegal Activities**: Any other clearly illegal content
4. **Child Exploitation**: Content involving child abuse or exploitation

**Note**: The system is conservative and only flags content that is clearly problematic. It does NOT flag:
- Adult content (18+)
- Violence in appropriate contexts (gaming, fiction)
- Controversial but legal topics
- General NSFW content

## Setup

### 1. Prerequisites

- WordPress site with the PNG Metadata Viewer plugin
- OpenAI API key configured in plugin settings
- Admin access to WordPress

### 2. Installation

The content moderation system is automatically included with the plugin. No additional installation is required.

### 3. Configuration

1. Go to **PNG Metadata Viewer > Content Moderation** in your WordPress admin
2. Ensure "Enable Content Moderation" is checked
3. Verify your OpenAI API key is configured in the main plugin settings
4. Set your preferred notification email address

### 4. API Configuration

Make sure you have configured your OpenAI API settings in **PNG Metadata Viewer > Settings**:
- OpenAI API Key
- Model (recommended: gpt-3.5-turbo or gpt-4)
- API Base URL (if using a different provider)

## How It Works

### Automatic Scanning

1. **Scheduled Execution**: The system runs every 8 hours via WordPress cron
2. **Content Detection**: Scans conversations created/updated since the last scan
3. **AI Analysis**: Sends conversation content to the LLM API for analysis
4. **Risk Assessment**: AI determines if content should be flagged and assigns risk levels
5. **Logging**: All flagged content is logged with detailed information
6. **Notifications**: Admins receive email alerts when content is flagged

### Manual Scanning

Admins can run manual scans at any time from the admin interface:
1. Go to **Content Moderation** admin page
2. Click "Run Manual Scan"
3. Monitor progress and results

### Content Analysis Process

1. **Conversation Retrieval**: Gets conversations updated since last scan
2. **Message Compilation**: Combines all messages in each conversation
3. **Content Truncation**: Limits content to 8000 characters for API efficiency
4. **AI Prompt**: Sends structured prompt to LLM for analysis
5. **Response Parsing**: Extracts JSON response with risk assessment
6. **Fallback Handling**: Uses keyword-based fallback if JSON parsing fails

## Admin Interface

### Main Dashboard

- **Status Overview**: Current system status, last scan time, next scheduled scan
- **Manual Controls**: Button to run immediate scans
- **System Information**: Scan interval and configuration details

### Moderation Log

- **Recent Entries**: Last 50 flagged conversations
- **Risk Levels**: Color-coded risk indicators
- **Content Details**: User, character, risk category, and action taken
- **Log Management**: Clear log functionality

### Settings

- **Enable/Disable**: Toggle the entire moderation system
- **Notification Email**: Set custom email for alerts
- **Integration**: Works with existing plugin settings

## Database Schema

The system creates a new table: `wp_pmv_moderation_log`

```sql
CREATE TABLE wp_pmv_moderation_log (
    id int(11) NOT NULL AUTO_INCREMENT,
    conversation_id int(11) NOT NULL,
    user_id bigint(20) UNSIGNED NULL,
    character_name varchar(255) NOT NULL,
    scan_date datetime DEFAULT CURRENT_TIMESTAMP,
    content_type enum('conversation','message') NOT NULL,
    content_id int(11) NOT NULL,
    content_preview text NOT NULL,
    risk_level enum('low','medium','high','critical') NOT NULL,
    risk_category varchar(100) NOT NULL,
    risk_description text NOT NULL,
    flagged_content text NOT NULL,
    action_taken varchar(100) DEFAULT 'flagged',
    reviewed_by bigint(20) UNSIGNED NULL,
    reviewed_at datetime NULL,
    review_notes text NULL,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id),
    KEY user_id (user_id),
    KEY risk_level (risk_level),
    KEY scan_date (scan_date),
    KEY action_taken (action_taken)
);
```

## Risk Levels

### Low Risk
- Minor content concerns
- Questionable but not clearly problematic
- Requires human review

### Medium Risk
- Moderate content issues
- Some problematic elements
- May require action

### High Risk
- Significant content problems
- Clear violations
- Immediate attention needed

### Critical Risk
- Severe content violations
- Potential legal issues
- Immediate action required

## Risk Categories

- **underage_content**: Content involving minors inappropriately
- **illegal_content**: Terrorism, violence, or other illegal activities
- **child_exploitation**: Child abuse or exploitation content
- **other**: Miscellaneous problematic content

## API Integration

### OpenAI API Usage

The system uses the OpenAI Chat Completions API with:
- **Model**: Configurable (default: gpt-3.5-turbo)
- **Temperature**: 0.1 (for consistent responses)
- **Max Tokens**: 1000
- **Timeout**: 5 minutes (as per user preference)

### Prompt Engineering

The system prompt is carefully crafted to:
- Focus on specific problematic content types
- Provide structured JSON responses
- Be conservative in flagging content
- Avoid false positives

### Fallback System

If the AI response isn't valid JSON, the system falls back to keyword-based detection:
- Searches for risk indicators in the response
- Assigns risk levels based on keyword presence
- Maintains functionality even with API issues

## Performance Considerations

### Rate Limiting

- **API Calls**: Limited to prevent overwhelming the LLM API
- **Scan Limits**: Maximum 100 conversations per scan
- **Delays**: 0.5-second delays between API calls

### Resource Management

- **Content Truncation**: Limits conversation length for API efficiency
- **Batch Processing**: Processes conversations in manageable batches
- **Background Execution**: Uses WordPress cron for non-blocking operation

### Database Optimization

- **Indexed Queries**: Efficient database lookups
- **Selective Scanning**: Only scans new/updated content
- **Log Rotation**: Configurable log retention

## Monitoring and Maintenance

### Regular Tasks

1. **Review Flagged Content**: Check moderation log regularly
2. **Monitor API Usage**: Track OpenAI API consumption
3. **Review System Logs**: Check WordPress error logs for issues
4. **Update Settings**: Adjust configuration as needed

### Troubleshooting

#### Common Issues

1. **API Key Errors**
   - Verify OpenAI API key is correct
   - Check API key permissions and billing
   - Ensure API base URL is correct

2. **Scan Failures**
   - Check WordPress cron is working
   - Verify database permissions
   - Review error logs for specific issues

3. **Performance Issues**
   - Reduce scan frequency if needed
   - Check API rate limits
   - Monitor server resources

#### Debug Mode

Enable WordPress debug mode to see detailed logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Maintenance

1. **Log Cleanup**: Clear old moderation logs periodically
2. **Database Optimization**: Run database optimization tools
3. **API Monitoring**: Track API usage and costs
4. **System Updates**: Keep plugin and WordPress updated

## Security Considerations

### Data Protection

- **Content Privacy**: Conversation content is only sent to the configured API
- **User Anonymity**: User IDs are logged but not exposed in public interfaces
- **Secure Storage**: All data is stored securely in WordPress database

### Access Control

- **Admin Only**: Moderation interface requires admin privileges
- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Capability Checks**: Functions verify user permissions

### API Security

- **Secure Transmission**: HTTPS for all API communications
- **Key Management**: API keys stored securely in WordPress options
- **Rate Limiting**: Built-in protection against API abuse

## Customization

### Modifying Risk Levels

To add custom risk levels, edit the `content-moderation.php` file:
```php
// Add new risk level to the enum
'risk_level' => 'low,medium,high,critical,custom'
```

### Custom Risk Categories

Add new categories by modifying the prompt and parsing logic:
```php
// Add to system prompt
"5. Custom category: [your description]"
```

### Scan Frequency

Adjust scan frequency by changing the interval:
```php
private $scan_interval_hours = 6; // Every 6 hours instead of 8
```

## Support and Updates

### Getting Help

1. **Check Logs**: Review WordPress error logs first
2. **Plugin Settings**: Verify all configuration options
3. **API Status**: Check OpenAI API status
4. **WordPress Health**: Ensure WordPress cron is working

### Updates

The content moderation system is updated with the main plugin. Check for updates regularly to get:
- Bug fixes
- Performance improvements
- New features
- Security updates

## Legal and Compliance

### Content Responsibility

- **User Content**: Users are responsible for their conversation content
- **Moderation**: The system assists with content monitoring but doesn't guarantee compliance
- **Legal Requirements**: Ensure compliance with local laws and regulations

### Data Retention

- **Log Storage**: Moderation logs are retained according to WordPress settings
- **User Privacy**: Respect user privacy and data protection laws
- **Audit Trail**: Maintain logs for compliance and moderation purposes

## Conclusion

The Content Moderation System provides a robust, AI-powered solution for monitoring conversation content. It automatically detects problematic content while maintaining performance and providing comprehensive admin controls. Regular monitoring and maintenance ensure optimal operation and compliance with content standards.
