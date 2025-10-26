# PNG Metadata Viewer - Settings Backup & Restore System

## Overview

The PNG Metadata Viewer plugin now includes a comprehensive settings backup and restore system to prevent data loss during plugin updates, WordPress updates, or any other system changes.

## Features

### 🔄 Automatic Backup System
- **Hourly Backups**: Settings are automatically backed up every hour
- **Pre-Save Backups**: Settings are backed up before any form submission
- **Update Protection**: Settings are backed up before plugin updates
- **WordPress Update Protection**: Settings are backed up before WordPress updates
- **Deactivation Protection**: Settings are backed up when the plugin is deactivated

### 📁 Manual Backup & Restore
- **Manual Backup**: Create a backup at any time
- **Restore from Backup**: Restore settings from the last backup
- **Export Settings**: Download settings as a JSON file
- **Import Settings**: Restore settings from a JSON file

### 🛡️ Settings Health & Validation
- **Automatic Validation**: Settings are validated for common issues
- **Auto-Repair**: Common issues are automatically fixed
- **Health Monitoring**: Real-time status of your settings
- **Migration System**: Automatic migration of old settings to new formats

## How It Works

### 1. Automatic Protection
The system automatically protects your settings by:
- Creating backups at regular intervals
- Backing up before any risky operations
- Storing backups in the WordPress database
- Maintaining version history

### 2. Smart Restoration
When settings are lost or corrupted:
- The system detects missing or invalid settings
- Automatically restores from the most recent backup
- Creates a new backup with the restored settings
- Logs all restoration activities

### 3. Health Monitoring
The system continuously monitors your settings:
- Validates color values (ensures proper hex format)
- Checks numeric values (ensures valid ranges)
- Identifies missing required settings
- Provides detailed health reports

## Accessing the Settings Backup System

### 1. Navigate to the Admin Panel
- Go to **PNG Metadata** → **Settings Backup** in your WordPress admin
- Or use the direct URL: `/wp-admin/admin.php?page=png-metadata-viewer&tab=settings_backup`

### 2. Main Dashboard
The Settings Backup tab provides:
- **Current Status**: Overview of your settings health
- **Backup Controls**: Manual backup and restore options
- **Export/Import**: File-based backup options
- **Health Summary**: Detailed analysis of your settings

## Using the System

### Creating a Manual Backup
1. Go to the **Settings Backup** tab
2. Click **"Backup Settings Now"**
3. Wait for confirmation
4. Your settings are now safely backed up

### Restoring from Backup
1. Go to the **Settings Backup** tab
2. Click **"Restore from Backup"**
3. Confirm the action
4. Your settings will be restored from the last backup

### Exporting Settings
1. Go to the **Settings Backup** tab
2. Click **"Export Settings"**
3. A JSON file will be downloaded
4. Store this file safely for future use

### Importing Settings
1. Go to the **Settings Backup** tab
2. Choose a JSON file using the file picker
3. Click **"Import Settings"**
4. Your settings will be restored from the file

### Validating Settings
1. Go to the **Settings Backup** tab
2. Click **"Validate Settings"**
3. Review the health report
4. Any issues found will be automatically repaired

## Settings Health Indicators

### 🟢 Healthy Settings
- All required settings are present
- Color values are properly formatted
- Numeric values are within valid ranges
- Recent backup exists

### 🟡 Warning Status
- Some settings may have issues
- Backup is older than expected
- Minor formatting issues detected

### 🔴 Critical Issues
- Required settings are missing
- Multiple validation errors
- No backup available
- Settings corruption detected

## Backup Storage

### Database Storage
- Backups are stored in the WordPress `wp_options` table
- Uses the option name `pmv_settings_backup`
- Includes timestamp and version information
- Automatically managed and cleaned up

### File Export
- Settings can be exported as JSON files
- Files include metadata and version information
- Can be stored locally or in cloud storage
- Useful for long-term archiving

## Troubleshooting

### Settings Not Backing Up
1. Check if you have admin permissions
2. Verify the plugin is properly activated
3. Check for JavaScript errors in the browser console
4. Ensure AJAX is working on your site

### Restore Not Working
1. Verify you have a valid backup
2. Check if the backup is corrupted
3. Try exporting and re-importing settings
4. Check the WordPress error log

### Validation Errors
1. Run the validation tool
2. Review the detailed error report
3. Check if auto-repair fixed the issues
4. Manually correct any remaining issues

## Advanced Features

### Custom Backup Schedules
The system automatically creates backups:
- Every hour during normal operation
- Before any settings form submission
- Before plugin updates
- Before WordPress updates
- When the plugin is deactivated

### Migration System
- Automatically migrates old settings to new formats
- Handles version upgrades seamlessly
- Maintains backward compatibility
- Logs all migration activities

### Performance Optimization
- Backups are created asynchronously
- Minimal impact on page load times
- Efficient storage and retrieval
- Automatic cleanup of old backups

## Security Features

### Access Control
- Only administrators can access backup features
- Nonce verification for all AJAX requests
- Proper permission checking
- Secure file handling

### Data Protection
- Settings are encrypted in transit
- Backups are stored securely
- No sensitive data in logs
- Secure import/export process

## Best Practices

### Regular Maintenance
1. **Weekly**: Check settings health status
2. **Monthly**: Create manual backups
3. **Before Updates**: Verify backup status
4. **After Updates**: Validate settings health

### Backup Strategy
1. **Local Backups**: Keep exported JSON files
2. **Database Backups**: Regular WordPress database backups
3. **Cloud Storage**: Store exports in cloud storage
4. **Version Control**: Track settings changes over time

### Monitoring
1. **Health Checks**: Regular validation runs
2. **Backup Status**: Monitor backup frequency
3. **Error Logs**: Review any validation issues
4. **Performance**: Monitor backup system performance

## Support

If you encounter issues with the settings backup system:

1. **Check the Health Status**: Look for warning indicators
2. **Run Validation**: Use the validation tool to identify issues
3. **Review Logs**: Check WordPress error logs for details
4. **Contact Support**: Provide detailed error information

## Technical Details

### Database Schema
- `pmv_settings_backup`: Main backup storage
- `pmv_settings_version`: Version tracking
- `pmv_settings_migration_version`: Migration tracking
- `pmv_settings_backup_last_backup`: Last backup timestamp

### File Structure
- `includes/admin-settings.php`: Main settings manager
- `includes/admin-settings-ui.php`: User interface
- `png-metadata-viewer.php`: Plugin integration

### Hooks and Actions
- `admin_init`: Settings validation and migration
- `upgrader_process_complete`: Pre-update backup
- `deactivated_plugin`: Deactivation backup
- `wp_version_check`: WordPress update backup

## Changelog

### Version 1.0
- Initial release of settings backup system
- Automatic backup functionality
- Manual backup and restore
- Settings validation and health monitoring
- Export/import functionality
- Migration system for old settings

---

**Note**: This system is designed to be completely transparent and requires no configuration. It automatically protects your settings while providing powerful manual control when needed.
