<?php
// admin/matches.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Debug: Check current state
echo '<div class="notice notice-info">';
echo '<h3>Debug Information:</h3>';
echo '<p><strong>Form submitted:</strong> ' . (isset($_POST['add_match']) || isset($_POST['edit_match']) || isset($_POST['delete_match']) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Add match:</strong> ' . (isset($_POST['add_match']) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Edit match:</strong> ' . (isset($_POST['edit_match']) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Delete match:</strong> ' . (isset($_POST['delete_match']) ? 'Yes' : 'No') . '</p>';

if (isset($_POST) && !empty($_POST)) {
    echo '<p><strong>POST data:</strong><br>';
    foreach ($_POST as $key => $value) {
        if ($key !== '_wpnonce' && $key !== '_wp_http_referer') {
            echo $key . ' = ' . esc_html($value) . '<br>';
        }
    }
    echo '</p>';
}

$total_matches = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mvp_matches");
echo '<p><strong>Total matches in database:</strong> ' . $total_matches . '</p>';
echo '</div>';

// Handle form submissions
if (isset($_POST['add_match']) || (isset($_POST['match_date']) && isset($_POST['home_team']) && isset($_POST['away_team']) && !isset($_POST['match_id']))) {
    echo '<div class="notice notice-warning"><p>Add match form submission detected!</p></div>';
    
    $match_date = sanitize_text_field($_POST['match_date']);
    $match_time = sanitize_text_field($_POST['match_time']);
    $home_team = sanitize_text_field($_POST['home_team']);
    $away_team = sanitize_text_field($_POST['away_team']);
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>Sanitized data:</strong><br>';
    echo 'Date: "' . $match_date . '"<br>';
    echo 'Time: "' . $match_time . '"<br>';
    echo 'Home team: "' . $home_team . '"<br>';
    echo 'Away team: "' . $away_team . '"<br>';
    echo '</p></div>';
    
    if (!empty($match_date) && !empty($home_team) && !empty($away_team)) {
        $datetime = $match_date . ' ' . ($match_time ?: '00:00:00');
        
        echo '<div class="notice notice-info"><p>Validation passed, attempting database insert...</p></div>';
        echo '<div class="notice notice-info"><p><strong>DateTime:</strong> "' . $datetime . '"</p></div>';
        
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'mvp_matches',
            array(
                'match_date' => $datetime,
                'home_team' => $home_team,
                'away_team' => $away_team,
                'is_open_for_rating' => 0
            ),
            array(
                '%s',    // match_date
                '%s',    // home_team
                '%s',    // away_team
                '%d'     // is_open_for_rating
            )
        );
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>Insert result:</strong> ' . ($insert_result !== false ? 'Success' : 'Failed') . '</p>';
        echo '<p><strong>Insert ID:</strong> ' . $wpdb->insert_id . '</p>';
        echo '<p><strong>Last error:</strong> ' . ($wpdb->last_error ?: 'None') . '</p>';
        echo '<p><strong>Last query:</strong> ' . htmlspecialchars($wpdb->last_query) . '</p>';
        echo '</div>';
        
        if ($insert_result !== false) {
            echo '<div class="notice notice-success"><p>' . __('Match added successfully! Match ID: ', 'mvp-player') . $wpdb->insert_id . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Error adding match: ', 'mvp-player') . ($wpdb->last_error ?: 'Unknown error') . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error">';
        echo '<p>' . __('Validation failed!', 'mvp-player') . '</p>';
        echo '<p>Date empty: ' . (empty($match_date) ? 'Yes' : 'No') . '</p>';
        echo '<p>Home team empty: ' . (empty($home_team) ? 'Yes' : 'No') . '</p>';
        echo '<p>Away team empty: ' . (empty($away_team) ? 'Yes' : 'No') . '</p>';
        echo '</div>';
    }
}

if (isset($_POST['edit_match']) || (isset($_POST['match_id']) && isset($_POST['match_date']) && isset($_POST['home_team']) && isset($_POST['away_team']))) {
    echo '<div class="notice notice-warning"><p>Edit match form submission detected!</p></div>';
    
    $match_id = intval($_POST['match_id']);
    $match_date = sanitize_text_field($_POST['match_date']);
    $match_time = sanitize_text_field($_POST['match_time']);
    $home_team = sanitize_text_field($_POST['home_team']);
    $away_team = sanitize_text_field($_POST['away_team']);
    
    $datetime = $match_date . ' ' . ($match_time ?: '00:00:00');
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>Edit data:</strong><br>';
    echo 'Match ID: ' . $match_id . '<br>';
    echo 'DateTime: "' . $datetime . '"<br>';
    echo 'Home team: "' . $home_team . '"<br>';
    echo 'Away team: "' . $away_team . '"<br>';
    echo '</p></div>';
    
    $update_result = $wpdb->update(
        $wpdb->prefix . 'mvp_matches',
        array(
            'match_date' => $datetime,
            'home_team' => $home_team,
            'away_team' => $away_team
        ),
        array('id' => $match_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>Update result:</strong> ' . ($update_result !== false ? 'Success (' . $update_result . ' rows)' : 'Failed') . '</p>';
    echo '<p><strong>Last error:</strong> ' . ($wpdb->last_error ?: 'None') . '</p>';
    echo '</div>';
    
    if ($update_result !== false) {
        echo '<div class="notice notice-success"><p>' . __('Match updated successfully!', 'mvp-player') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Error updating match: ', 'mvp-player') . ($wpdb->last_error ?: 'Unknown error') . '</p></div>';
    }
}

if (isset($_POST['delete_match'])) {
    echo '<div class="notice notice-warning"><p>Delete match form submission detected!</p></div>';
    
    $match_id = intval($_POST['match_id']);
    
    echo '<div class="notice notice-info"><p><strong>Deleting match ID:</strong> ' . $match_id . '</p></div>';
    
    // Delete ratings first
    $ratings_deleted = $wpdb->delete($wpdb->prefix . 'mvp_ratings', array('match_id' => $match_id));
    echo '<div class="notice notice-info"><p><strong>Ratings deleted:</strong> ' . $ratings_deleted . '</p></div>';
    
    // Delete match selections
    $selections_deleted = $wpdb->delete($wpdb->prefix . 'mvp_match_selections', array('match_id' => $match_id));
    echo '<div class="notice notice-info"><p><strong>Selections deleted:</strong> ' . $selections_deleted . '</p></div>';
    
    // Delete match
    $match_deleted = $wpdb->delete($wpdb->prefix . 'mvp_matches', array('id' => $match_id));
    echo '<div class="notice notice-info"><p><strong>Match deleted:</strong> ' . ($match_deleted ? 'Success' : 'Failed') . '</p></div>';
    
    if ($match_deleted) {
        echo '<div class="notice notice-success"><p>' . __('Match deleted successfully!', 'mvp-player') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Error deleting match!', 'mvp-player') . '</p></div>';
    }
}

// Handle CSV import
if (isset($_POST['import_matches_csv']) && isset($_FILES['matches_csv'])) {
    echo '<div class="notice notice-warning"><p>CSV import detected!</p></div>';
    
    $file = $_FILES['matches_csv'];
    echo '<div class="notice notice-info">';
    echo '<p><strong>File info:</strong><br>';
    echo 'Name: ' . $file['name'] . '<br>';
    echo 'Size: ' . $file['size'] . '<br>';
    echo 'Error: ' . $file['error'] . '<br>';
    echo '</p></div>';
    
    if ($file['error'] === 0) {
        $file_content = file_get_contents($file['tmp_name']);
        $lines = explode("\n", $file_content);
        $imported = 0;
        
        echo '<div class="notice notice-info"><p><strong>Processing ' . count($lines) . ' lines...</strong></p></div>';
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $data = str_getcsv($line);
            echo '<div class="notice notice-info"><p><strong>Line ' . ($line_num + 1) . ':</strong> ' . count($data) . ' columns - ' . implode(' | ', $data) . '</p></div>';
            
            if (count($data) >= 3) {
                $match_date = sanitize_text_field($data[0]);
                $home_team = sanitize_text_field($data[1]);
                $away_team = sanitize_text_field($data[2]);
                
                if (!empty($match_date) && !empty($home_team) && !empty($away_team)) {
                    $insert_result = $wpdb->insert(
                        $wpdb->prefix . 'mvp_matches',
                        array(
                            'match_date' => $match_date,
                            'home_team' => $home_team,
                            'away_team' => $away_team,
                            'is_open_for_rating' => 0
                        )
                    );
                    
                    if ($insert_result !== false) {
                        $imported++;
                        echo '<div class="notice notice-info"><p>✅ Imported: ' . $home_team . ' vs ' . $away_team . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>❌ Failed: ' . $wpdb->last_error . '</p></div>';
                    }
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>' . sprintf(__('%d matches imported successfully!', 'mvp-player'), $imported) . '</p></div>';
    }
}

// Get matches (refresh after operations)
$all_matches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_matches ORDER BY match_date DESC");
$club_name = get_option('mvp_club_name', 'Your Club');

// Handle edit mode
$edit_match = null;
if (isset($_GET['edit']) && intval($_GET['edit'])) {
    $edit_match = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mvp_matches WHERE id = %d",
        intval($_GET['edit'])
    ));
    
    if ($edit_match) {
        echo '<div class="notice notice-info"><p><strong>Edit mode active for match:</strong> ' . $edit_match->home_team . ' vs ' . $edit_match->away_team . '</p></div>';
    }
}

echo '<div class="notice notice-info"><p><strong>After operations:</strong> Total matches: ' . count($all_matches) . '</p></div>';
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
    
    <div class="mvp-admin-container">
        <div class="mvp-left-column">
            <h2><?php _e('Match Overview', 'mvp-player'); ?></h2>
            
            <div class="mvp-matches-overview">
                <div class="mvp-matches-list">
                    <?php 
                    $displayed = 0;
                    foreach ($all_matches as $match): 
                        $is_hidden = $displayed >= 5 ? 'style="display: none;"' : '';
                        $displayed++;
                    ?>
                        <div class="mvp-match-row" <?php echo $is_hidden; ?>>
                            <div class="match-info">
                                <span class="match-date"><?php echo date('M j, Y H:i', strtotime($match->match_date)); ?></span>
                                <span class="match-teams">
                                    <strong><?php echo esc_html($match->home_team); ?></strong> 
                                    vs 
                                    <strong><?php echo esc_html($match->away_team); ?></strong>
                                </span>
                                <span class="rating-status <?php echo $match->is_open_for_rating ? 'open' : 'closed'; ?>">
                                    <?php echo $match->is_open_for_rating ? __('Open for Rating', 'mvp-player') : __('Closed', 'mvp-player'); ?>
                                </span>
                            </div>
                            <div class="match-actions">
                                <a href="?page=mvp-matches&edit=<?php echo $match->id; ?>" class="button button-small">✏️ <?php _e('Edit', 'mvp-player'); ?></a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this match?', 'mvp-player'); ?>')">
                                    <input type="hidden" name="match_id" value="<?php echo $match->id; ?>">
                                    <button type="submit" name="delete_match" class="button button-small delete-btn">🗑️ <?php _e('Delete', 'mvp-player'); ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($all_matches) > 5): ?>
                        <div class="mvp-show-more">
                            <button type="button" id="show-all-matches" class="button">
                                <?php _e('Show All Matches', 'mvp-player'); ?> (<?php echo count($all_matches); ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="mvp-right-column">
            <h2><?php echo $edit_match ? __('Edit Match', 'mvp-player') : __('Add Match', 'mvp-player'); ?></h2>
            
            <form method="post" class="mvp-add-match-form">
                <?php wp_nonce_field('mvp_add_match', 'mvp_nonce'); ?>
                <?php if ($edit_match): ?>
                    <input type="hidden" name="match_id" value="<?php echo $edit_match->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Date', 'mvp-player'); ?> *</th>
                        <td>
                            <input type="date" name="match_date" required class="regular-text"
                                   value="<?php echo $edit_match ? date('Y-m-d', strtotime($edit_match->match_date)) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Time', 'mvp-player'); ?></th>
                        <td>
                            <input type="time" name="match_time" class="regular-text"
                                   value="<?php echo $edit_match ? date('H:i', strtotime($edit_match->match_date)) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Home Team', 'mvp-player'); ?> *</th>
                        <td>
                            <input type="text" name="home_team" required class="regular-text" list="home-team-list"
                                   value="<?php echo $edit_match ? esc_attr($edit_match->home_team) : ''; ?>" 
                                   placeholder="<?php _e('Enter home team name', 'mvp-player'); ?>">
                            <datalist id="home-team-list">
                                <?php if (!empty($club_name)): ?>
                                    <option value="<?php echo esc_attr($club_name); ?>"><?php echo esc_html($club_name); ?> (<?php _e('Your Club', 'mvp-player'); ?>)</option>
                                <?php endif; ?>
                                <?php
                                // Get recent team names for suggestions
                                $recent_home_teams = $wpdb->get_col("SELECT DISTINCT home_team FROM {$wpdb->prefix}mvp_matches ORDER BY match_date DESC LIMIT 10");
                                $recent_away_teams = $wpdb->get_col("SELECT DISTINCT away_team FROM {$wpdb->prefix}mvp_matches ORDER BY match_date DESC LIMIT 10");
                                $all_teams = array_unique(array_merge($recent_home_teams, $recent_away_teams));
                                
                                foreach ($all_teams as $team):
                                    if ($team !== $club_name): ?>
                                        <option value="<?php echo esc_attr($team); ?>"><?php echo esc_html($team); ?></option>
                                    <?php endif;
                                endforeach; ?>
                            </datalist>
                            <p class="description">
                                <?php _e('Start typing to see suggestions. Your club is listed first.', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Away Team', 'mvp-player'); ?> *</th>
                        <td>
                            <input type="text" name="away_team" required class="regular-text" list="away-team-list"
                                   value="<?php echo $edit_match ? esc_attr($edit_match->away_team) : ''; ?>"
                                   placeholder="<?php _e('Enter away team name', 'mvp-player'); ?>">
                            <datalist id="away-team-list">
                                <?php if (!empty($club_name)): ?>
                                    <option value="<?php echo esc_attr($club_name); ?>"><?php echo esc_html($club_name); ?> (<?php _e('Your Club', 'mvp-player'); ?>)</option>
                                <?php endif; ?>
                                <?php foreach ($all_teams as $team):
                                    if ($team !== $club_name): ?>
                                        <option value="<?php echo esc_attr($team); ?>"><?php echo esc_html($team); ?></option>
                                    <?php endif;
                                endforeach; ?>
                            </datalist>
                            <p class="description">
                                <?php _e('Start typing to see suggestions. Your club is listed first.', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="<?php echo $edit_match ? 'edit_match' : 'add_match'; ?>" 
                           class="button-primary" 
                           value="<?php echo $edit_match ? __('Update Match', 'mvp-player') : __('Add Match', 'mvp-player'); ?>">
                    <?php if ($edit_match): ?>
                        <a href="?page=mvp-matches" class="button"><?php _e('Cancel', 'mvp-player'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
            
            <h2><?php _e('Import Matches', 'mvp-player'); ?></h2>
            
            <div class="mvp-import-section">
                <h3><?php _e('CSV Import', 'mvp-player'); ?></h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mvp_import_csv', 'mvp_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('CSV File', 'mvp-player'); ?></th>
                            <td>
                                <input type="file" name="matches_csv" accept=".csv" required>
                                <p class="description">
                                    <?php _e('Upload a CSV file with columns: Date (YYYY-MM-DD HH:MM:SS), Home Team, Away Team', 'mvp-player'); ?>
                                </p>
                                <p class="description">
                                    <strong><?php _e('Example:', 'mvp-player'); ?></strong><br>
                                    <code>2024-03-15 14:30:00,SC Cambuur,Ajax<br>2024-03-22 16:00:00,PSV,SC Cambuur</code>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="import_matches_csv" class="button-secondary" value="<?php _e('Import CSV', 'mvp-player'); ?>">
                    </p>
                </form>
                
                <h3><?php _e('iCal Import', 'mvp-player'); ?></h3>
                <form method="post" enctype="multipart/form-data">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('iCal File', 'mvp-player'); ?></th>
                            <td>
                                <input type="file" name="matches_ical" accept=".ics" disabled>
                                <p class="description">
                                    <?php _e('Coming soon - iCal file import functionality', 'mvp-player'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="import_matches_ical" class="button-secondary" value="<?php _e('Import iCal', 'mvp-player'); ?>" disabled>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.mvp-admin-container {
    display: flex;
    gap: 30px;
}

.mvp-left-column {
    flex: 2;
}

.mvp-right-column {
    flex: 1;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
}

.mvp-matches-overview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.mvp-match-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.mvp-match-row:last-child {
    border-bottom: none;
}

.mvp-match-row:hover {
    background: #f9f9f9;
}

.match-info {
    flex: 1;
}

.match-date {
    display: block;
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.match-teams {
    display: block;
    font-size: 16px;
    margin-bottom: 5px;
}

.rating-status {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 3px;
    text-transform: uppercase;
    font-weight: bold;
}

.rating-status.open {
    background: #d4edda;
    color: #155724;
}

.rating-status.closed {
    background: #f8d7da;
    color: #721c24;
}

.match-actions {
    display: flex;
    gap: 5px;
}

.delete-btn {
    color: #dc3232 !important;
}

.mvp-show-more {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
}

.mvp-import-section {
    background: white;
    padding: 20px;
    margin-top: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.mvp-import-section h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-table th {
    width: 120px;
}

.form-table input[type="text"],
.form-table input[type="date"],
.form-table input[type="time"] {
    width: 100%;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#show-all-matches').on('click', function() {
        $('.mvp-match-row[style*="display: none"]').show();
        $(this).parent().hide();
    });
    
    // Set default home team to club name
    $('input[name="home_team"]').on('focus', function() {
        if ($(this).val() === '') {
            $(this).val('<?php echo esc_js($club_name); ?>');
        }
    });
});
</script>