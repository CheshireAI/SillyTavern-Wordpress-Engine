# PMV Voting System

A comprehensive upvote/downvote system for character cards in the PNG Metadata Viewer plugin.

## Features

- **User Authentication Required**: Only logged-in users can vote
- **Upvote/Downvote System**: Users can upvote or downvote character cards
- **Vote Management**: Users can change their votes or remove them
- **Vote Statistics**: Real-time display of upvotes, downvotes, and overall score
- **Sorting Options**: Sort characters by highest rated, most popular, or recently voted
- **Responsive Design**: Dark theme styling that matches the existing plugin design
- **AJAX Integration**: Smooth, non-refreshing voting experience

## Installation

1. **Include the voting system**: The voting system is automatically included when the main plugin loads
2. **Database tables**: The system automatically creates the required database table on first use
3. **CSS and JavaScript**: Styles and scripts are automatically enqueued

## Database Structure

The system creates a `pmv_character_votes` table with the following structure:

```sql
CREATE TABLE wp_pmv_character_votes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    character_filename varchar(255) NOT NULL,
    vote_type enum('upvote', 'downvote') NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_character (user_id, character_filename),
    KEY character_filename (character_filename),
    KEY vote_type (vote_type),
    KEY created_at (created_at)
);
```

## Usage

### Frontend Display

Voting buttons are automatically added to each character card:

- **Upvote Button (▲)**: Green up arrow with vote count
- **Downvote Button (▼)**: Red down arrow with vote count
- **Score Display**: Overall score (upvotes - downvotes) with color coding

### Sorting Options

Users can sort character cards by:

1. **Default Order**: Original file order
2. **Name A-Z**: Alphabetical by character name
3. **Name Z-A**: Reverse alphabetical
4. **Highest Rated**: By vote score (upvotes - downvotes)
5. **Most Popular**: By total number of votes
6. **Recently Voted**: By most recent voting activity

### User Experience

- **Logged-in users**: Can vote, change votes, and see their voting history
- **Guest users**: See vote statistics but are prompted to log in when trying to vote
- **Real-time updates**: Vote counts and scores update immediately after voting
- **Visual feedback**: Buttons show current vote state and provide hover effects

## API Endpoints

### Vote on Character
- **Action**: `pmv_vote_character`
- **Method**: POST
- **Parameters**:
  - `nonce`: Security nonce
  - `character_filename`: Name of the character file
  - `vote_type`: 'upvote' or 'downvote'
- **Response**: Vote statistics and user vote status

### Get Vote Statistics
- **Action**: `pmv_get_vote_stats`
- **Method**: POST
- **Parameters**:
  - `nonce`: Security nonce
  - `character_filename`: Name of the character file
- **Response**: Vote counts and user's current vote

## Security Features

- **Nonce Verification**: All AJAX requests require valid nonces
- **User Authentication**: Voting requires user login
- **Input Sanitization**: All user inputs are properly sanitized
- **SQL Injection Protection**: Prepared statements for database queries
- **Rate Limiting**: Built-in protection against rapid voting

## Styling

The voting system uses a dark theme that matches the existing plugin design:

- **Dark Backgrounds**: #1a1a1a, #2a2a2a
- **Border Colors**: #404040, #606060
- **Text Colors**: #f0f0f0, #ccc
- **Accent Colors**: 
  - Green (#4CAF50) for upvotes and positive scores
  - Red (#f44336) for downvotes and negative scores
  - Blue (#2196F3) for informational elements

## Customization

### Adding Custom Sort Options

```php
add_filter('pmv_character_cards_sort_options', function($sort_options) {
    $sort_options['custom_sort'] = 'Custom Sort Label';
    return $sort_options;
});
```

### Modifying Vote Display

The voting buttons are added via JavaScript and can be customized by modifying:
- `js/voting-system.js` - Frontend functionality
- `css/voting-system-styles.css` - Visual styling

### Database Hooks

The system provides hooks for custom functionality:

```php
// After vote is recorded
do_action('pmv_vote_recorded', $user_id, $character_filename, $vote_type);

// After vote is updated
do_action('pmv_vote_updated', $user_id, $character_filename, $vote_type);

// After vote is removed
do_action('pmv_vote_removed', $user_id, $character_filename);
```

## Troubleshooting

### Common Issues

1. **Voting buttons not appearing**: Check if JavaScript is loading and the voting system is initialized
2. **Database errors**: Verify WordPress database connection and table creation
3. **Permission denied**: Ensure user is logged in and has proper capabilities
4. **AJAX failures**: Check browser console for JavaScript errors and verify nonce validity

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testing

Use the included test script to verify system functionality:

```bash
php test-voting-system.php
```

## Performance Considerations

- **Caching**: Vote statistics are calculated on-demand but can be cached
- **Database Indexes**: Proper indexing on user_id and character_filename
- **AJAX Optimization**: Minimal data transfer for vote operations
- **Lazy Loading**: Vote buttons are added after cards are rendered

## Future Enhancements

- **Vote History**: User dashboard showing voting activity
- **Advanced Analytics**: Detailed voting trends and statistics
- **Moderation Tools**: Admin controls for managing votes
- **Social Features**: Share highly-rated characters
- **Vote Weighting**: Different vote values based on user reputation

## Support

For issues or questions about the voting system:

1. Check the WordPress error logs
2. Verify all required files are present
3. Test with a clean WordPress installation
4. Review browser console for JavaScript errors

## License

This voting system is part of the PNG Metadata Viewer plugin and follows the same licensing terms. 