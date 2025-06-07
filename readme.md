# MVP - Player of the Year WordPress Plugin

A comprehensive WordPress plugin for rating football players and determining the player of the year.

## 📋 Features

- **Player Management**: Add players, manage positions, and activate/deactivate players
- **Match Management**: Schedule matches, select players, and open/close voting
- **Rating System**: Choose between 1-10 numeric or 5-star rating system
- **Voting Rights**: Configurable voting permissions (visitors, users, members, admins)
- **Automatic Rankings**: Season rankings with automatic player of the year calculation
- **Import Functionality**: Import players and matches via CSV
- **Shortcodes**: Easy integration into your website
- **Responsive Design**: Works perfectly on all devices

## 🚀 Installation

### Step 1: Plugin Files

Create the following folder structure in your WordPress `/wp-content/plugins/` directory:

```
mvp-player-plugin/
├── mvp-player-plugin.php
├── admin/
│   ├── overview.php
│   ├── players.php
│   ├── matches.php
│   └── settings.php
├── templates/
│   ├── rating-form.php
│   └── player-list.php
├── assets/
│   ├── admin.css
│   ├── admin.js
│   ├── frontend.css
│   └── frontend.js
└── README.md
```

### Step 2: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "MVP - Player of the Year"
3. Click "Activate"

### Step 3: Basic Configuration

1. Go to **MVP Plugin → Settings**
2. Enter your club name
3. Set the rating system (1-10 or stars)
4. Configure voting rights
5. Click "Save Settings"

## ⚙️ Configuration

### Adding Players

1. Go to **MVP Plugin → Players**
2. Fill in player details:
   - Jersey Number (1-99)
   - Position (dropdown with abbreviations)
   - First Name (required)
   - Last Name (required)
3. Click "Add Player"
4. Move players from "Inactive" to "Active" to make them available

### Scheduling Matches

1. Go to **MVP Plugin → Matches**
2. Add match:
   - Date (required)
   - Time (optional)
   - Home Team (defaults to your club name)
   - Away Team (required)
3. Click "Add Match"

### Creating Match Selection

1. Go to **MVP Plugin → Overview**
2. Select the latest match
3. Move players from "Available" to "Selected"
4. Set rating status to "Open"
5. Click "Save Selection"

## 🎯 Using Shortcodes

### Rating Form

```php
[mvp_player_ratings]
```

**Parameters:**
- `match_id="5"` - Specific match ID
- `show_form="false"` - Hide rating form

**Examples:**
```php
[mvp_player_ratings match_id="5"]
[mvp_player_ratings show_form="false"]
```

### Player Rankings

```php
[mvp_player_list]
```

**Parameters:**
- `type="season"` - List type (season/match/recent)
- `limit="10"` - Number of players

**Examples:**
```php
[mvp_player_list type="season" limit="15"]
[mvp_player_list type="recent" limit="5"]
[mvp_player_list type="match" limit="11"]
```

## 🎨 Custom Styling

### CSS Classes

```css
.mvp-rating-form { /* Rating form container */ }
.mvp-player-list { /* Player rankings list */ }
.mvp-player-item { /* Individual player row */ }
.mvp-rating-stars { /* Star rating display */ }
.mvp-rating-number { /* Number rating display */ }
```

### Color Customization

```css
:root {
  --mvp-primary-color: #007cba;
  --mvp-success-color: #28a745;
  --mvp-star-color: #ffc107;
}
```

## 📊 Import Functionality

### Import Players (CSV)

Format: `Jersey Number,Position,First Name,Last Name`

Example:
```csv
1,GK,John,Smith
10,CAM,Marco,Johnson
9,ST,Kevin,Williams
```

### Import Matches (CSV)

Format: `Date,Home Team,Away Team`

Example:
```csv
2024-03-15 14:30:00,FC United,Ajax
2024-03-22 16:00:00,PSV,FC United
```

## 🔧 Settings Explained

### Minimum Games Percentage
Percentage of matches a player must play to be eligible for player of the year (default 60%).

### Rating System
- **1-10**: Traditional numeric system with decimals
- **5 Stars**: Visual star system (0.5 - 5.0)

### Time to Vote
- **Until Next Match**: Voting remains open until next match
- **Number of Days**: Fixed period after match (max 14 days)

### Vote Rights
- **Visitors**: All website visitors can vote
- **Users**: Only logged-in WordPress users
- **Members**: Only members (BuddyPress/ARMember)
- **Admins**: Only administrators

## 🎮 Keyboard Shortcuts

**In Rating Form:**
- `1-9`: Quick rating for focused player
- `Enter`: Submit rating for focused player
- `Esc`: Clear rating for focused player

**In Admin:**
- `Ctrl+S`: Save settings

## 📱 Mobile Features

- Touch/swipe support for star ratings
- Responsive design for all screen sizes
- Optimized for touchscreen usage

## 🔍 Troubleshooting

### Plugin Won't Activate
- Check if all files are uploaded correctly
- Check PHP error logs
- Ensure WordPress 5.0+ and PHP 7.4+

### Ratings Not Saving
- Check AJAX functionality
- Verify nonce verification works
- Check database permissions

### Shortcodes Not Displaying
- Check if shortcode is spelled correctly
- Verify players/matches exist
- Check plugin activation

### Styling Issues
- Check CSS cache
- Check theme conflicts
- Use browser developer tools

## 🏗️ Database Schema

The plugin automatically creates these tables:

### wp_mvp_players
| Column | Type | Description |
|--------|------|-------------|
| id | mediumint(9) | Primary key |
| jersey_number | int(2) | Jersey number |
| position | varchar(10) | Position abbreviation |
| first_name | varchar(100) | First name |
| last_name | varchar(100) | Last name |
| status | varchar(20) | active/inactive |
| created_at | datetime | Creation date |

### wp_mvp_matches
| Column | Type | Description |
|--------|------|-------------|
| id | mediumint(9) | Primary key |
| match_date | datetime | Match date/time |
| home_team | varchar(100) | Home team |
| away_team | varchar(100) | Away team |
| is_open_for_rating | tinyint(1) | Rating status |
| created_at | datetime | Creation date |

### wp_mvp_match_selections
| Column | Type | Description |
|--------|------|-------------|
| id | mediumint(9) | Primary key |
| match_id | mediumint(9) | Match reference |
| player_id | mediumint(9) | Player reference |
| minutes_played | int(3) | Minutes played |
| created_at | datetime | Creation date |

### wp_mvp_ratings
| Column | Type | Description |
|--------|------|-------------|
| id | mediumint(9) | Primary key |
| match_id | mediumint(9) | Match reference |
| player_id | mediumint(9) | Player reference |
| rating | decimal(3,2) | Rating value |
| voter_ip | varchar(45) | Voter IP |
| voter_id | mediumint(9) | Voter user ID |
| created_at | datetime | Vote date |

## 🧩 Hooks & Filters

### Actions
```php
// Custom action after rating is saved
add_action('mvp_rating_saved', 'my_custom_function', 10, 3);
function my_custom_function($rating_id, $player_id, $rating) {
    // Your custom code here
}
```

### Filters
```php
// Modify rating validation
add_filter('mvp_validate_rating', 'my_rating_validation', 10, 2);
function my_rating_validation($is_valid, $rating) {
    // Your custom validation logic
    return $is_valid;
}
```

## 🎯 Position Abbreviations

| Code | Full Name |
|------|-----------|
| GK | Goalkeeper |
| RB | Right Back |
| RCB | Right Centre Back |
| LCB | Left Centre Back |
| LB | Left Back |
| CDM | Central Defensive Midfielder |
| RCM | Right Central Midfielder |
| LCM | Left Central Midfielder |
| CAM | Central Attacking Midfielder |
| RW | Right Winger |
| LW | Left Winger |
| ST | Striker |

## 📱 Progressive Web App Features

- Offline rating storage (localStorage)
- Touch gestures for mobile rating
- Responsive breakpoints for all devices
- Optimized loading for slow connections

## 🔐 Security Features

- Nonce verification for all AJAX requests
- IP-based duplicate vote prevention
- SQL injection protection
- XSS prevention with proper escaping

## 📈 Analytics Integration

The plugin is ready for Google Analytics integration:

```javascript
// Track rating submissions
gtag('event', 'rating_submit', {
  'event_category': 'mvp_plugin',
  'event_label': 'player_rating'
});
```

## 📞 Support

For questions or issues:
1. Check this documentation
2. Review browser console for JavaScript errors
3. Check WordPress debug logs
4. Contact support with error details

## 🔄 Updates

When updating the plugin:
1. Backup your database
2. Update plugin files
3. Check settings
4. Test functionality

## 📝 Changelog

### Version 1.0.0
- Initial release
- Complete player rating system
- Admin interface
- Frontend shortcodes
- Import functionality
- Responsive design

## 🤝 Contributing

Improvements are welcome! For major changes:
1. Open an issue first
2. Fork the repository
3. Create a feature branch
4. Submit a pull request

## 📄 License

GPL v2 or later - See [LICENSE](LICENSE) file for details.

## 🏆 Credits

- Plugin development inspired by football community needs
- Icons from WordPress Dashicons
- Responsive design following WordPress standards

---

**Plugin Name**: MVP - Player of the Year  
**Version**: 1.0.0  
**Compatibility**: WordPress 5.0+, PHP 7.4+  
**Author**: [Your Name]  
**Plugin URI**: [Your Website]  
**Support**: [Support Email]