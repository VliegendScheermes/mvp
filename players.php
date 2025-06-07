<?php
// admin/players.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Debug: Check if table exists
$table_name = $wpdb->prefix . 'mvp_players';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

// Debug information
echo '<div class="notice notice-info">';
echo '<h3>Debug Information:</h3>';
echo '<p><strong>Table exists:</strong> ' . ($table_exists ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Table name:</strong> ' . $table_name . '</p>';
echo '<p><strong>Form submitted:</strong> ' . (isset($_POST['add_player']) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Nonce valid:</strong> ' . (isset($_POST['mvp_nonce']) && wp_verify_nonce($_POST['mvp_nonce'], 'mvp_add_player') ? 'Yes' : 'No') . '</p>';

if (isset($_POST)) {
    echo '<p><strong>POST data:</strong><br>';
    foreach ($_POST as $key => $value) {
        if ($key !== 'mvp_nonce') {
            echo $key . ' = ' . esc_html($value) . '<br>';
        }
    }
    echo '</p>';
}

// Check table structure
if ($table_exists) {
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo '<p><strong>Table structure:</strong><br>';
    foreach ($columns as $column) {
        echo $column->Field . ' (' . $column->Type . ')<br>';
    }
    echo '</p>';
    
    $total_players = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p><strong>Total players in database:</strong> ' . $total_players . '</p>';
    
    if ($total_players > 0) {
        $all_players = $wpdb->get_results("SELECT id, first_name, last_name, status FROM $table_name ORDER BY id DESC LIMIT 5");
        echo '<p><strong>Recent players:</strong><br>';
        foreach ($all_players as $player) {
            echo 'ID: ' . $player->id . ' - ' . $player->first_name . ' ' . $player->last_name . ' (' . $player->status . ')<br>';
        }
        echo '</p>';
    }
}
echo '</div>';

// Handle manual table creation
if (isset($_POST['create_tables'])) {
    $charset_collate = $wpdb->get_charset_collate();
    
    $players_table = $wpdb->prefix . 'mvp_players';
    $sql_players = "CREATE TABLE $players_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        jersey_number int(2),
        position varchar(10) NOT NULL,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        status varchar(20) DEFAULT 'inactive',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql_players);
    
    echo '<div class="notice notice-success"><p>Table creation attempted. Result: <pre>' . print_r($result, true) . '</pre></p></div>';
}

// Add button to manually create tables if they don't exist
if (!$table_exists) {
    echo '<div class="notice notice-warning">';
    echo '<p><strong>Warning:</strong> The MVP players table does not exist!</p>';
    echo '<form method="post" style="display: inline;">';
    echo '<input type="hidden" name="create_tables" value="1">';
    echo '<input type="submit" class="button button-primary" value="Create Database Tables">';
    echo '</form>';
    echo '</div>';
}

// Handle form submissions
if (isset($_POST['add_player']) || (isset($_POST['jersey_number']) && isset($_POST['first_name']))) {
    echo '<div class="notice notice-warning"><p>Form submission detected!</p></div>';
    
    // Check nonce (skip for debugging initially)
    $nonce_valid = !isset($_POST['mvp_nonce']) || wp_verify_nonce($_POST['mvp_nonce'], 'mvp_add_player');
    
    if ($nonce_valid) {
        echo '<div class="notice notice-info"><p>Nonce verified successfully (or skipped)!</p></div>';
        
        $jersey_number = intval($_POST['jersey_number']);
        $position = sanitize_text_field($_POST['position']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>Sanitized data:</strong><br>';
        echo 'Jersey: ' . $jersey_number . '<br>';
        echo 'Position: ' . $position . '<br>';
        echo 'First name: ' . $first_name . '<br>';
        echo 'Last name: ' . $last_name . '<br>';
        echo '</p></div>';
        
        // Validate required fields and position
        if (!empty($first_name) && !empty($last_name) && !empty($position) && $position !== '') {
            echo '<div class="notice notice-info"><p>Validation passed, attempting database insert...</p></div>';
            
            $insert_data = array(
                'jersey_number' => $jersey_number > 0 ? $jersey_number : null,
                'position' => $position,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => 'inactive'
            );
            
            echo '<div class="notice notice-info"><p><strong>Insert data:</strong><pre>' . print_r($insert_data, true) . '</pre></p></div>';
            
            $insert_result = $wpdb->insert(
                $wpdb->prefix . 'mvp_players',
                $insert_data,
                array(
                    '%d',    // jersey_number
                    '%s',    // position
                    '%s',    // first_name
                    '%s',    // last_name
                    '%s'     // status
                )
            );
            
            echo '<div class="notice notice-info">';
            echo '<p><strong>Insert result:</strong> ' . ($insert_result !== false ? 'Success' : 'Failed') . '</p>';
            echo '<p><strong>Insert ID:</strong> ' . $wpdb->insert_id . '</p>';
            echo '<p><strong>Last error:</strong> ' . ($wpdb->last_error ?: 'None') . '</p>';
            echo '<p><strong>Last query:</strong> ' . htmlspecialchars($wpdb->last_query) . '</p>';
            echo '</div>';
            
            if ($insert_result !== false) {
                echo '<div class="notice notice-success"><p>' . __('Player added successfully! Player ID: ', 'mvp-player') . $wpdb->insert_id . '</p></div>';
                
                // Force refresh the player lists
                $active_players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_players WHERE status = 'active' ORDER BY jersey_number ASC, first_name ASC");
                $inactive_players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_players WHERE status = 'inactive' ORDER BY jersey_number ASC, first_name ASC");
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error adding player: ', 'mvp-player') . ($wpdb->last_error ?: 'Unknown error') . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error">';
            echo '<p>' . __('Validation failed!', 'mvp-player') . '</p>';
            echo '<p>First name empty: ' . (empty($first_name) ? 'Yes' : 'No') . '</p>';
            echo '<p>Last name empty: ' . (empty($last_name) ? 'Yes' : 'No') . '</p>';
            echo '<p>Position empty: ' . (empty($position) ? 'Yes' : 'No') . '</p>';
            echo '</div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Nonce verification failed!</p></div>';
    }
}

if (isset($_POST['move_players'])) {
    $action = $_POST['move_action'];
    $player_ids = isset($_POST['player_ids']) ? array_map('intval', $_POST['player_ids']) : array();
    
    if (!empty($player_ids)) {
        $new_status = ($action === 'activate') ? 'active' : 'inactive';
        $placeholders = implode(',', array_fill(0, count($player_ids), '%d'));
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}mvp_players SET status = %s WHERE id IN ($placeholders)",
            array_merge(array($new_status), $player_ids)
        ));
        
        echo '<div class="notice notice-success"><p>' . __('Players updated successfully!', 'mvp-player') . '</p></div>';
    }
}

if (isset($_POST['delete_player'])) {
    $player_id = intval($_POST['player_id']);
    
    // Delete ratings first
    $wpdb->delete($wpdb->prefix . 'mvp_ratings', array('player_id' => $player_id));
    // Delete match selections
    $wpdb->delete($wpdb->prefix . 'mvp_match_selections', array('player_id' => $player_id));
    // Delete player
    $wpdb->delete($wpdb->prefix . 'mvp_players', array('id' => $player_id));
    
    echo '<div class="notice notice-success"><p>' . __('Player deleted successfully!', 'mvp-player') . '</p></div>';
}

// Handle file upload
if (isset($_POST['import_players']) && isset($_FILES['player_file'])) {
    $file = $_FILES['player_file'];
    if ($file['error'] === 0) {
        $file_content = file_get_contents($file['tmp_name']);
        $lines = explode("\n", $file_content);
        $imported = 0;
        
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) >= 4) {
                $jersey_number = intval($data[0]);
                $position = sanitize_text_field($data[1]);
                $first_name = sanitize_text_field($data[2]);
                $last_name = sanitize_text_field($data[3]);
                
                if (!empty($first_name) && !empty($last_name)) {
                    $wpdb->insert(
                        $wpdb->prefix . 'mvp_players',
                        array(
                            'jersey_number' => $jersey_number,
                            'position' => $position,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'status' => 'inactive'
                        )
                    );
                    $imported++;
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>' . sprintf(__('%d players imported successfully!', 'mvp-player'), $imported) . '</p></div>';
    }
}

// Get players (moved after form processing to get fresh data)
if (!isset($active_players)) {
    $active_players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_players WHERE status = 'active' ORDER BY jersey_number ASC, first_name ASC");
}
if (!isset($inactive_players)) {
    $inactive_players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_players WHERE status = 'inactive' ORDER BY jersey_number ASC, first_name ASC");
}

// Debug: Check if we have any players at all
$total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mvp_players");
$inactive_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mvp_players WHERE status = 'inactive'");

// Debug output (remove in production)
echo '<div class="notice notice-info"><p><strong>After processing:</strong> Total players: ' . $total_players . ', Inactive players: ' . $inactive_count . '</p></div>';

$positions = array(
    'GK' => __('Goalkeeper', 'mvp-player'),
    'RB' => __('Right Back', 'mvp-player'),
    'RCB' => __('Right Centre Back', 'mvp-player'),
    'LCB' => __('Left Centre Back', 'mvp-player'),
    'LB' => __('Left Back', 'mvp-player'),
    'CDM' => __('Central Defensive Midfielder', 'mvp-player'),
    'RCM' => __('Right Central Midfielder', 'mvp-player'),
    'LCM' => __('Left Central Midfielder', 'mvp-player'),
    'CAM' => __('Central Attacking Midfielder', 'mvp-player'),
    'RW' => __('Right Winger', 'mvp-player'),
    'LW' => __('Left Winger', 'mvp-player'),
    'ST' => __('Striker', 'mvp-player')
);
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
            <h2><?php _e('Add Player', 'mvp-player'); ?></h2>
            
            <form method="post" class="mvp-add-player-form">
                <?php wp_nonce_field('mvp_add_player', 'mvp_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Jersey Number', 'mvp-player'); ?></th>
                        <td>
                            <select name="jersey_number">
                                <option value="0">-</option>
                                <?php for ($i = 1; $i <= 99; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Position', 'mvp-player'); ?> *</th>
                        <td>
                            <select name="position" required>
                                <option value="">-</option>
                                <?php foreach ($positions as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $code . ' - ' . $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('First Name', 'mvp-player'); ?> *</th>
                        <td><input type="text" name="first_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Last Name', 'mvp-player'); ?> *</th>
                        <td><input type="text" name="last_name" required class="regular-text"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="add_player" class="button-primary" value="<?php _e('Add Player', 'mvp-player'); ?>">
                </p>
            </form>
            
            <h2><?php _e('Player Management', 'mvp-player'); ?></h2>
            
            <div class="mvp-player-management">
                <div class="mvp-player-column">
                    <h3><?php _e('Active Players', 'mvp-player'); ?></h3>
                    <form method="post" id="active-players-form">
                        <input type="hidden" name="move_action" value="deactivate">
                        <div class="mvp-player-list active-list">
                            <?php foreach ($active_players as $player): ?>
                                <div class="mvp-player-item">
                                    <input type="checkbox" name="player_ids[]" value="<?php echo $player->id; ?>">
                                    <span class="jersey"><?php echo $player->jersey_number ? '#' . $player->jersey_number : '-'; ?></span>
                                    <span class="position-badge"><?php echo $player->position; ?></span>
                                    <span class="name"><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mvp-player-controls">
                            <button type="button" class="button select-all"><?php _e('Select All', 'mvp-player'); ?></button>
                        </div>
                    </form>
                </div>
                
                <div class="mvp-move-buttons">
                    <button type="button" class="button mvp-move-btn" id="move-to-inactive" title="<?php _e('Move selected players to inactive', 'mvp-player'); ?>">
                        ▶<br><?php _e('Move to<br>Inactive', 'mvp-player'); ?>
                    </button>
                    <button type="button" class="button mvp-move-btn" id="move-to-active" title="<?php _e('Move selected players to active', 'mvp-player'); ?>">
                        ◀<br><?php _e('Move to<br>Active', 'mvp-player'); ?>
                    </button>
                </div>
                
                <div class="mvp-player-column">
                    <h3><?php _e('Inactive Players', 'mvp-player'); ?></h3>
                    <form method="post" id="inactive-players-form">
                        <input type="hidden" name="move_action" value="activate">
                        <div class="mvp-player-list inactive-list">
                            <?php if (empty($inactive_players)): ?>
                                <div class="mvp-no-players">
                                    <p><?php _e('No inactive players yet.', 'mvp-player'); ?></p>
                                    <small><?php _e('New players will appear here after being added.', 'mvp-player'); ?></small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($inactive_players as $player): ?>
                                    <div class="mvp-player-item">
                                        <input type="checkbox" name="player_ids[]" value="<?php echo $player->id; ?>">
                                        <span class="jersey"><?php echo $player->jersey_number ? '#' . $player->jersey_number : '-'; ?></span>
                                        <span class="position-badge"><?php echo $player->position; ?></span>
                                        <span class="name"><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></span>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this player? This will remove all their ratings!', 'mvp-player'); ?>')">
                                            <input type="hidden" name="player_id" value="<?php echo $player->id; ?>">
                                            <button type="submit" name="delete_player" class="delete-btn" title="<?php _e('Delete Player', 'mvp-player'); ?>">🗑️</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mvp-player-controls">
                            <button type="button" class="button select-all"><?php _e('Select All', 'mvp-player'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <h2><?php _e('Import Players', 'mvp-player'); ?></h2>
            
            <form method="post" enctype="multipart/form-data" class="mvp-import-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Import File', 'mvp-player'); ?></th>
                        <td>
                            <input type="file" name="player_file" accept=".csv,.xlsx,.xls" required>
                            <p class="description">
                                <?php _e('Upload a CSV or Excel file with columns: Jersey Number, Position, First Name, Last Name', 'mvp-player'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="import_players" class="button-secondary" value="<?php _e('Import Players', 'mvp-player'); ?>">
                </p>
            </form>
            
            <h3><?php _e('Integrations', 'mvp-player'); ?></h3>
            <p><?php _e('Coming soon', 'mvp-player'); ?></p>
        </div>
    </div>
</div>

<style>
.mvp-admin-container {
    max-width: 1200px;
}

.mvp-add-player-form .form-table th {
    width: 150px;
}

.mvp-player-management {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.mvp-player-column {
    flex: 1;
}

.mvp-player-list {
    border: 1px solid #ddd;
    min-height: 300px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    background: #f9f9f9;
    margin-bottom: 10px;
}

.mvp-player-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    margin-bottom: 5px;
    background: white;
    border-radius: 3px;
    border: 1px solid #eee;
}

.mvp-player-item:hover {
    background: #f0f0f0;
}

.jersey {
    font-weight: bold;
    min-width: 40px;
    color: #666;
}

.position-badge {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    min-width: 40px;
    text-align: center;
}

.name {
    flex: 1;
    font-weight: 500;
}

.delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    padding: 2px;
    opacity: 0.6;
}

.delete-btn:hover {
    opacity: 1;
}

.mvp-player-controls {
    display: flex;
    gap: 10px;
    justify-content: space-between;
}

.mvp-import-form {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin-top: 20px;
}

.active-list {
    border-color: #46b450;
}

.inactive-list {
    border-color: #dc3232;
}

h3 {
    color: #23282d;
    margin-bottom: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.select-all').on('click', function() {
        var form = $(this).closest('form');
        var checkboxes = form.find('input[type="checkbox"]');
        var allChecked = checkboxes.length === checkboxes.filter(':checked').length;
        
        checkboxes.prop('checked', !allChecked);
        $(this).text(allChecked ? '<?php _e('Select All', 'mvp-player'); ?>' : '<?php _e('Deselect All', 'mvp-player'); ?>');
    });
    
    // Auto-scroll for jersey number dropdown
    $('select[name="jersey_number"]').on('focus', function() {
        this.selectedIndex = 19; // Scroll to number 20
    });
});
</script>