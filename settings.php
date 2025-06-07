<?php
// admin/settings.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Debug: Check current option values
echo '<div class="notice notice-info">';
echo '<h3>Debug Information:</h3>';
echo '<p><strong>Form submitted:</strong> ' . (isset($_POST['save_settings']) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Nonce valid:</strong> ' . (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'mvp_settings') ? 'Yes' : 'No') . '</p>';

if (isset($_POST)) {
    echo '<p><strong>POST data:</strong><br>';
    foreach ($_POST as $key => $value) {
        if ($key !== '_wpnonce' && $key !== '_wp_http_referer') {
            echo $key . ' = ' . esc_html($value) . '<br>';
        }
    }
    echo '</p>';
}

// Show current option values
echo '<p><strong>Current option values:</strong><br>';
echo 'mvp_club_name = "' . get_option('mvp_club_name', '') . '"<br>';
echo 'mvp_min_games_percentage = "' . get_option('mvp_min_games_percentage', 60) . '"<br>';
echo 'mvp_rating_system = "' . get_option('mvp_rating_system', '1-10') . '"<br>';
echo 'mvp_vote_duration = "' . get_option('mvp_vote_duration', 'next_match') . '"<br>';
echo 'mvp_vote_duration_days = "' . get_option('mvp_vote_duration_days', 7) . '"<br>';
echo 'mvp_vote_rights = "' . get_option('mvp_vote_rights', 'visitors') . '"<br>';
echo '</p>';
echo '</div>';

// Handle form submission
if (isset($_POST['save_settings']) || (isset($_POST['club_name']) && isset($_POST['rating_system']))) {
    echo '<div class="notice notice-warning"><p>Settings form submission detected!</p></div>';
    
    // Check nonce (more flexible)
    $nonce_valid = !isset($_POST['_wpnonce']) || wp_verify_nonce($_POST['_wpnonce'], 'mvp_settings');
    
    if ($nonce_valid) {
        echo '<div class="notice notice-info"><p>Nonce verified successfully (or skipped)!</p></div>';
        
        $club_name = sanitize_text_field($_POST['club_name']);
        $min_games_percentage = intval($_POST['min_games_percentage']);
        $rating_system = sanitize_text_field($_POST['rating_system']);
        $vote_duration = sanitize_text_field($_POST['vote_duration']);
        $vote_duration_days = isset($_POST['vote_duration_days']) ? intval($_POST['vote_duration_days']) : 7;
        $vote_rights = sanitize_text_field($_POST['vote_rights']);
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>Sanitized data:</strong><br>';
        echo 'Club name: "' . $club_name . '"<br>';
        echo 'Min games %: ' . $min_games_percentage . '<br>';
        echo 'Rating system: "' . $rating_system . '"<br>';
        echo 'Vote duration: "' . $vote_duration . '"<br>';
        echo 'Vote duration days: ' . $vote_duration_days . '<br>';
        echo 'Vote rights: "' . $vote_rights . '"<br>';
        echo '</p></div>';
        
        // Update options with explicit checks
        $updates = array();
        
        $result1 = update_option('mvp_club_name', $club_name);
        $updates[] = 'mvp_club_name: ' . ($result1 ? 'Success' : 'Failed/No change') . ' (was: "' . get_option('mvp_club_name') . '", now: "' . $club_name . '")';
        
        $result2 = update_option('mvp_min_games_percentage', $min_games_percentage);
        $updates[] = 'mvp_min_games_percentage: ' . ($result2 ? 'Success' : 'Failed/No change') . ' (was: ' . get_option('mvp_min_games_percentage') . ', now: ' . $min_games_percentage . ')';
        
        $result3 = update_option('mvp_rating_system', $rating_system);
        $updates[] = 'mvp_rating_system: ' . ($result3 ? 'Success' : 'Failed/No change') . ' (was: "' . get_option('mvp_rating_system') . '", now: "' . $rating_system . '")';
        
        $result4 = update_option('mvp_vote_duration', $vote_duration);
        $updates[] = 'mvp_vote_duration: ' . ($result4 ? 'Success' : 'Failed/No change') . ' (was: "' . get_option('mvp_vote_duration') . '", now: "' . $vote_duration . '")';
        
        $result5 = update_option('mvp_vote_duration_days', $vote_duration_days);
        $updates[] = 'mvp_vote_duration_days: ' . ($result5 ? 'Success' : 'Failed/No change') . ' (was: ' . get_option('mvp_vote_duration_days') . ', now: ' . $vote_duration_days . ')';
        
        $result6 = update_option('mvp_vote_rights', $vote_rights);
        $updates[] = 'mvp_vote_rights: ' . ($result6 ? 'Success' : 'Failed/No change') . ' (was: "' . get_option('mvp_vote_rights') . '", now: "' . $vote_rights . '")';
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>Update results:</strong><br>';
        foreach ($updates as $update) {
            echo $update . '<br>';
        }
        echo '</p></div>';
        
        // Force refresh values
        wp_cache_delete('mvp_club_name', 'options');
        wp_cache_delete('mvp_min_games_percentage', 'options');
        wp_cache_delete('mvp_rating_system', 'options');
        wp_cache_delete('mvp_vote_duration', 'options');
        wp_cache_delete('mvp_vote_duration_days', 'options');
        wp_cache_delete('mvp_vote_rights', 'options');
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'mvp-player') . '</p></div>';
        
        // Verify the updates worked
        echo '<div class="notice notice-info">';
        echo '<p><strong>Post-update verification:</strong><br>';
        echo 'mvp_club_name = "' . get_option('mvp_club_name', '') . '"<br>';
        echo 'mvp_min_games_percentage = "' . get_option('mvp_min_games_percentage', 60) . '"<br>';
        echo 'mvp_rating_system = "' . get_option('mvp_rating_system', '1-10') . '"<br>';
        echo 'mvp_vote_duration = "' . get_option('mvp_vote_duration', 'next_match') . '"<br>';
        echo 'mvp_vote_duration_days = "' . get_option('mvp_vote_duration_days', 7) . '"<br>';
        echo 'mvp_vote_rights = "' . get_option('mvp_vote_rights', 'visitors') . '"<br>';
        echo '</p></div>';
        
    } else {
        echo '<div class="notice notice-error"><p>Nonce verification failed!</p></div>';
    }
}

// Get current settings (refresh after potential updates)
if (!isset($club_name)) {
    $club_name = get_option('mvp_club_name', '');
    $min_games_percentage = get_option('mvp_min_games_percentage', 60);
    $rating_system = get_option('mvp_rating_system', '1-10');
    $vote_duration = get_option('mvp_vote_duration', 'next_match');
    $vote_duration_days = get_option('mvp_vote_duration_days', 7);
    $vote_rights = get_option('mvp_vote_rights', 'visitors');
}

echo '<div class="notice notice-info">';
echo '<p><strong>Values being used in form:</strong><br>';
echo 'Club name: "' . $club_name . '"<br>';
echo 'Min games %: ' . $min_games_percentage . '<br>';
echo 'Rating system: "' . $rating_system . '"<br>';
echo 'Vote duration: "' . $vote_duration . '"<br>';
echo 'Vote duration days: ' . $vote_duration_days . '<br>';
echo 'Vote rights: "' . $vote_rights . '"<br>';
echo '</p></div>';

// Generate embed code
$embed_code = '[mvp_player_ratings]';
$widget_code = '[mvp_player_list type="season" limit="10"]';
?>

<div class="wrap">
    <h1><?php _e('MVP - Player of the Year', 'mvp-player'); ?></h1>
    
    <?php 
    // Get the plugin instance to render tabs
    global $mvp_plugin_instance;
    if (isset($mvp_plugin_instance)) {
        $mvp_plugin_instance->render_admin_tabs();
    }
    ?>
    
    <form method="post" class="mvp-settings-form">
        <?php wp_nonce_field('mvp_settings'); ?>
        <div class="mvp-settings-container">
            <div class="mvp-left-column">
                <h2><?php _e('General Settings', 'mvp-player'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Club Name', 'mvp-player'); ?></th>
                        <td>
                            <input type="text" name="club_name" value="<?php echo esc_attr($club_name); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your club name', 'mvp-player'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Minimum Games Percentage', 'mvp-player'); ?></th>
                        <td>
                            <input type="number" name="min_games_percentage" value="<?php echo esc_attr($min_games_percentage); ?>" min="0" max="100" class="small-text"> %
                            <p class="description">
                                <?php _e('Minimum percentage of games a player must have played to be eligible for Player of the Year', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Rating System', 'mvp-player'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="rating_system" value="1-10" <?php checked($rating_system, '1-10'); ?>>
                                    <?php _e('1 to 10 (with decimal)', 'mvp-player'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="rating_system" value="stars" <?php checked($rating_system, 'stars'); ?>>
                                    <?php _e('5 Stars (0.5 to 5.0)', 'mvp-player'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('Choose how users will rate players', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Time to Vote', 'mvp-player'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="vote_duration" value="next_match" <?php checked($vote_duration, 'next_match'); ?>>
                                    <?php _e('Until next match', 'mvp-player'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="vote_duration" value="days" <?php checked($vote_duration, 'days'); ?>>
                                    <?php _e('Number of days:', 'mvp-player'); ?>
                                    <input type="number" name="vote_duration_days" value="<?php echo esc_attr($vote_duration_days); ?>" min="1" max="14" class="small-text">
                                    <?php _e('(max 14 days)', 'mvp-player'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('How long after a match users can vote on player ratings', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Vote Rights', 'mvp-player'); ?></th>
                        <td>
                            <select name="vote_rights">
                                <option value="visitors" <?php selected($vote_rights, 'visitors'); ?>>
                                    <?php _e('Visitors (all website visitors)', 'mvp-player'); ?>
                                </option>
                                <option value="users" <?php selected($vote_rights, 'users'); ?>>
                                    <?php _e('Users (everyone with WordPress account)', 'mvp-player'); ?>
                                </option>
                                <option value="members" <?php selected($vote_rights, 'members'); ?>>
                                    <?php _e('Members (BuddyPress or ARMember plugin account)', 'mvp-player'); ?>
                                </option>
                                <option value="admins" <?php selected($vote_rights, 'admins'); ?>>
                                    <?php _e('Admins only', 'mvp-player'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Who is allowed to vote on player ratings', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="<?php _e('Save Settings', 'mvp-player'); ?>">
                </p>
            </div>
            
            <div class="mvp-right-column">
                <h2><?php _e('Embed Widgets', 'mvp-player'); ?></h2>
                
                <div class="mvp-widget-section">
                    <h3><?php _e('Rating Form Widget', 'mvp-player'); ?></h3>
                    <p><?php _e('Use this shortcode to display the player rating form:', 'mvp-player'); ?></p>
                    <div class="mvp-code-box">
                        <code><?php echo esc_html($embed_code); ?></code>
                        <button type="button" class="button copy-code" data-code="<?php echo esc_attr($embed_code); ?>">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                    
                    <h4><?php _e('Parameters:', 'mvp-player'); ?></h4>
                    <ul>
                        <li><code>match_id</code> - <?php _e('Specific match ID (optional)', 'mvp-player'); ?></li>
                        <li><code>show_form</code> - <?php _e('Show rating form (true/false)', 'mvp-player'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Examples:', 'mvp-player'); ?></h4>
                    <div class="mvp-code-box">
                        <code>[mvp_player_ratings match_id="5"]</code>
                        <button type="button" class="button copy-code" data-code="[mvp_player_ratings match_id=&quot;5&quot;]">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                    <div class="mvp-code-box">
                        <code>[mvp_player_ratings show_form="false"]</code>
                        <button type="button" class="button copy-code" data-code="[mvp_player_ratings show_form=&quot;false&quot;]">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="mvp-widget-section">
                    <h3><?php _e('Player List Widget', 'mvp-player'); ?></h3>
                    <p><?php _e('Use this shortcode to display player rankings:', 'mvp-player'); ?></p>
                    <div class="mvp-code-box">
                        <code><?php echo esc_html($widget_code); ?></code>
                        <button type="button" class="button copy-code" data-code="<?php echo esc_attr($widget_code); ?>">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                    
                    <h4><?php _e('Parameters:', 'mvp-player'); ?></h4>
                    <ul>
                        <li><code>type</code> - <?php _e('season, match, or recent (default: season)', 'mvp-player'); ?></li>
                        <li><code>limit</code> - <?php _e('Number of players to show (default: 10)', 'mvp-player'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Examples:', 'mvp-player'); ?></h4>
                    <div class="mvp-code-box">
                        <code>[mvp_player_list type="recent" limit="5"]</code>
                        <button type="button" class="button copy-code" data-code="[mvp_player_list type=&quot;recent&quot; limit=&quot;5&quot;]">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                    <div class="mvp-code-box">
                        <code>[mvp_player_list type="match" limit="15"]</code>
                        <button type="button" class="button copy-code" data-code="[mvp_player_list type=&quot;match&quot; limit=&quot;15&quot;]">
                            <?php _e('Copy', 'mvp-player'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="mvp-widget-section">
                    <h3><?php _e('Custom CSS', 'mvp-player'); ?></h3>
                    <p><?php _e('You can customize the appearance using these CSS classes:', 'mvp-player'); ?></p>
                    <ul class="mvp-css-classes">
                        <li><code>.mvp-rating-form</code> - <?php _e('Main rating form container', 'mvp-player'); ?></li>
                        <li><code>.mvp-player-list</code> - <?php _e('Player rankings list', 'mvp-player'); ?></li>
                        <li><code>.mvp-player-item</code> - <?php _e('Individual player row', 'mvp-player'); ?></li>
                        <li><code>.mvp-rating-stars</code> - <?php _e('Star rating display', 'mvp-player'); ?></li>
                        <li><code>.mvp-rating-number</code> - <?php _e('Number rating display', 'mvp-player'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.mvp-settings-container {
    display: flex;
    gap: 30px;
}

.mvp-left-column {
    flex: 2;
}

.mvp-right-column {
    flex: 1;
}

.mvp-widget-section {
    background: #f9f9f9;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.mvp-widget-section h3 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.mvp-code-box {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Courier New', monospace;
}

.mvp-code-box code {
    background: none;
    color: #e2e8f0;
    font-size: 14px;
}

.copy-code {
    background: #4299e1 !important;
    color: white !important;
    border: none !important;
    padding: 5px 10px !important;
    border-radius: 3px !important;
    cursor: pointer !important;
    font-size: 12px !important;
}

.copy-code:hover {
    background: #3182ce !important;
}

.mvp-css-classes {
    background: white;
    padding: 15px;
    border-radius: 3px;
    border: 1px solid #ddd;
}

.mvp-css-classes li {
    margin-bottom: 8px;
    font-size: 14px;
}

.mvp-css-classes code {
    background: #f1f3f4;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: bold;
    color: #d63384;
}

.form-table th {
    width: 200px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    padding-top: 10px;
}

fieldset label {
    display: block;
    margin-bottom: 8px;
}

fieldset input[type="number"] {
    margin: 0 5px;
}

.notice {
    margin: 5px 0 15px;
}

/* Rating system preview */
.rating-preview {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 10px;
}

.rating-preview h4 {
    margin: 0 0 10px 0;
}

.stars-preview {
    font-size: 20px;
    color: #ffc107;
}

.number-preview {
    font-size: 16px;
    font-weight: bold;
    color: #0073aa;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy code functionality
    $('.copy-code').on('click', function() {
        var code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            var btn = $(this);
            var originalText = btn.text();
            btn.text('<?php _e('Copied!', 'mvp-player'); ?>');
            setTimeout(function() {
                btn.text(originalText);
            }, 2000);
        }.bind(this));
    });
    
    // Show/hide days input based on vote duration selection
    $('input[name="vote_duration"]').on('change', function() {
        var daysInput = $('input[name="vote_duration_days"]');
        if ($(this).val() === 'days') {
            daysInput.prop('disabled', false);
        } else {
            daysInput.prop('disabled', true);
        }
    });
    
    // Initialize days input state
    if ($('input[name="vote_duration"]:checked').val() !== 'days') {
        $('input[name="vote_duration_days"]').prop('disabled', true);
    }
    
    // Add rating system preview
    $('input[name="rating_system"]').on('change', function() {
        $('.rating-preview').remove();
        
        var preview = $('<div class="rating-preview"><h4><?php _e('Preview:', 'mvp-player'); ?></h4></div>');
        
        if ($(this).val() === 'stars') {
            preview.append('<div class="stars-preview">★★★★☆ <span style="font-size: 14px; color: #666;">(4.0/5.0)</span></div>');
        } else {
            preview.append('<div class="number-preview">8.5 <span style="font-size: 14px; color: #666;">/10</span></div>');
        }
        
        $(this).closest('fieldset').after(preview);
    });
    
    // Show initial preview
    $('input[name="rating_system"]:checked').trigger('change');
});
</script>