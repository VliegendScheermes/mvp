<?php
// admin/overview.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle form submissions
if (isset($_POST['submit_match_selection'])) {
    $match_id = intval($_POST['match_id']);
    $selected_players = isset($_POST['selected_players']) ? array_map('intval', $_POST['selected_players']) : array();
    
    // Clear existing selections
    $wpdb->delete($wpdb->prefix . 'mvp_match_selections', array('match_id' => $match_id));
    
    // Add new selections
    foreach ($selected_players as $player_id) {
        $wpdb->insert(
            $wpdb->prefix . 'mvp_match_selections',
            array(
                'match_id' => $match_id,
                'player_id' => $player_id,
                'minutes_played' => 90 // Default value
            )
        );
    }
    
    echo '<div class="notice notice-success"><p>' . __('Match selection updated successfully!', 'mvp-player') . '</p></div>';
}

if (isset($_POST['toggle_rating']) || (isset($_POST['match_id']) && isset($_POST['is_open_for_rating']))) {
    $match_id = intval($_POST['match_id']);
    $is_open = intval($_POST['is_open_for_rating']);
    
    $update_result = $wpdb->update(
        $wpdb->prefix . 'mvp_matches',
        array('is_open_for_rating' => $is_open),
        array('id' => $match_id),
        array('%d'),
        array('%d')
    );
    
    if ($update_result !== false) {
        echo '<div class="notice notice-success"><p>' . __('Rating status updated!', 'mvp-player') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Error updating rating status!', 'mvp-player') . '</p></div>';
    }
}

// Get latest PLAYED match (in the past)
$latest_played_match = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}mvp_matches WHERE match_date <= NOW() ORDER BY match_date DESC LIMIT 1");

// Get next upcoming match (in the future)
$next_match = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}mvp_matches WHERE match_date > NOW() ORDER BY match_date ASC LIMIT 1");

// Get recent played matches (last 5 in the past)
$recent_played_matches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_matches WHERE match_date <= NOW() ORDER BY match_date DESC LIMIT 5");

// Get upcoming matches (next 3 in the future)
$upcoming_matches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_matches WHERE match_date > NOW() ORDER BY match_date ASC LIMIT 3");

// Get all active players
$active_players = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mvp_players WHERE status = 'active' ORDER BY jersey_number ASC");

// Get selected players for latest played match
$selected_players = array();
if ($latest_played_match) {
    $selected_players = $wpdb->get_col($wpdb->prepare(
        "SELECT player_id FROM {$wpdb->prefix}mvp_match_selections WHERE match_id = %d",
        $latest_played_match->id
    ));
}
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
            <h2><?php _e('Recent Played Matches', 'mvp-player'); ?></h2>
            
            <?php if (!empty($recent_played_matches)): ?>
                <div class="mvp-matches-dropdown">
                    <?php foreach ($recent_played_matches as $index => $match): ?>
                        <div class="mvp-match-accordion">
                            <div class="mvp-match-header" data-match-id="<?php echo $match->id; ?>">
                                <div class="mvp-match-card">
                                    <div class="match-main-info">
                                        <div class="match-teams-large"><?php echo esc_html($match->home_team . ' vs ' . $match->away_team); ?></div>
                                        <div class="match-meta">
                                            <span class="match-date"><?php echo date_i18n('M j, Y H:i', strtotime($match->match_date)); ?></span>
                                            <span class="match-separator">•</span>
                                            <span class="match-time-ago"><?php echo human_time_diff(strtotime($match->match_date), current_time('timestamp')) . ' ago'; ?></span>
                                        </div>
                                    </div>
                                    <div class="match-controls">
                                        <div class="rating-control">
                                            <span class="rating-label"><?php _e('Rating:', 'mvp-player'); ?></span>
                                            <span class="rating-status <?php echo $match->is_open_for_rating ? 'open' : 'closed'; ?>">
                                                <?php echo $match->is_open_for_rating ? __('Open', 'mvp-player') : __('Closed', 'mvp-player'); ?>
                                            </span>
                                            <form method="post" class="mvp-rating-toggle" style="display: inline;">
                                                <input type="hidden" name="match_id" value="<?php echo $match->id; ?>">
                                                <label class="mvp-switch">
                                                    <input type="hidden" name="is_open_for_rating" value="<?php echo $match->is_open_for_rating ? 0 : 1; ?>">
                                                    <input type="checkbox" <?php checked($match->is_open_for_rating); ?> onchange="this.form.submit()">
                                                    <span class="mvp-slider"></span>
                                                </label>
                                                <input type="submit" name="toggle_rating" style="display: none;">
                                            </form>
                                        </div>
                                        <button type="button" class="mvp-expand-btn">
                                            <span class="expand-text"><?php _e('Manage Players', 'mvp-player'); ?></span>
                                            <span class="expand-icon">▼</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mvp-match-content" style="display: none;">
                                <?php
                                // Get selected players for this specific match
                                $match_selected_players = $wpdb->get_col($wpdb->prepare(
                                    "SELECT player_id FROM {$wpdb->prefix}mvp_match_selections WHERE match_id = %d",
                                    $match->id
                                ));
                                ?>
                                
                                <div class="mvp-match-selection-wrapper">
                                    <h4><?php _e('Match Selection', 'mvp-player'); ?></h4>
                                    <form method="post" class="mvp-match-selection">
                                        <input type="hidden" name="match_id" value="<?php echo $match->id; ?>">
                                        
                                        <div class="mvp-player-lists">
                                            <div class="mvp-selected-players">
                                                <h5><?php _e('Selected Players', 'mvp-player'); ?></h5>
                                                <div class="mvp-player-list" id="selected-players-<?php echo $match->id; ?>">
                                                    <?php foreach ($active_players as $player): ?>
                                                        <?php if (in_array($player->id, $match_selected_players)): ?>
                                                            <div class="mvp-player-item" data-player-id="<?php echo $player->id; ?>">
                                                                <input type="checkbox" name="selected_player_ids[]" value="<?php echo $player->id; ?>">
                                                                <input type="hidden" name="selected_players[]" value="<?php echo $player->id; ?>">
                                                                <span class="jersey-number"><?php echo $player->jersey_number ? '#' . $player->jersey_number : '-'; ?></span>
                                                                <span class="player-name"><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></span>
                                                                <span class="position"><?php echo $player->position; ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mvp-move-buttons">
                                                <button type="button" class="button mvp-move-btn" data-action="move-to-available" data-match-id="<?php echo $match->id; ?>" title="<?php _e('Move selected players to available', 'mvp-player'); ?>">
                                                    ▶<br><?php _e('Move to<br>Available', 'mvp-player'); ?>
                                                </button>
                                                <button type="button" class="button mvp-move-btn" data-action="move-to-selected" data-match-id="<?php echo $match->id; ?>" title="<?php _e('Move selected players to selected', 'mvp-player'); ?>">
                                                    ◀<br><?php _e('Move to<br>Selected', 'mvp-player'); ?>
                                                </button>
                                            </div>
                                            
                                            <div class="mvp-available-players">
                                                <h5><?php _e('Available Players', 'mvp-player'); ?></h5>
                                                <div class="mvp-player-list" id="available-players-<?php echo $match->id; ?>">
                                                    <?php foreach ($active_players as $player): ?>
                                                        <?php if (!in_array($player->id, $match_selected_players)): ?>
                                                            <div class="mvp-player-item" data-player-id="<?php echo $player->id; ?>">
                                                                <input type="checkbox" name="available_player_ids[]" value="<?php echo $player->id; ?>">
                                                                <span class="jersey-number"><?php echo $player->jersey_number ? '#' . $player->jersey_number : '-'; ?></span>
                                                                <span class="player-name"><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></span>
                                                                <span class="position"><?php echo $player->position; ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <p class="submit">
                                            <input type="submit" name="submit_match_selection" class="button-primary" value="<?php _e('Save Selection', 'mvp-player'); ?>">
                                        </p>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mvp-no-matches">
                    <p><?php _e('No played matches found yet.', 'mvp-player'); ?></p>
                    <p><a href="?page=mvp-matches"><?php _e('Add your first match', 'mvp-player'); ?></a></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upcoming_matches)): ?>
                <h3><?php _e('Upcoming Matches', 'mvp-player'); ?></h3>
                <div class="mvp-upcoming-matches">
                    <?php foreach ($upcoming_matches as $match): ?>
                        <div class="mvp-match-card upcoming-card">
                            <div class="match-main-info">
                                <div class="match-teams-large"><?php echo esc_html($match->home_team . ' vs ' . $match->away_team); ?></div>
                                <div class="match-meta">
                                    <span class="match-date"><?php echo date_i18n('M j, Y H:i', strtotime($match->match_date)); ?></span>
                                    <span class="match-separator">•</span>
                                    <span class="match-time-until"><?php echo 'in ' . human_time_diff(current_time('timestamp'), strtotime($match->match_date)); ?></span>
                                </div>
                            </div>
                            <div class="match-controls">
                                <span class="upcoming-badge"><?php _e('Upcoming', 'mvp-player'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Match accordion functionality
    $('.mvp-match-header').on('click', function(e) {
        // Don't trigger accordion if clicking on form elements
        if ($(e.target).closest('.mvp-rating-toggle, .mvp-switch, input, label').length) {
            return;
        }
        
        var header = $(this);
        var content = header.next('.mvp-match-content');
        var expandBtn = header.find('.mvp-expand-btn');
        var isActive = header.hasClass('active');
        
        // Close all other accordions
        $('.mvp-match-header').removeClass('active');
        $('.mvp-match-content').slideUp(300);
        $('.mvp-expand-btn').removeClass('expanded');
        
        if (!isActive) {
            // Open this accordion
            header.addClass('active');
            content.slideDown(300);
            expandBtn.addClass('expanded');
        }
    });
    
    // Prevent accordion from closing when clicking expand button
    $('.mvp-expand-btn').on('click', function(e) {
        e.stopPropagation();
        var header = $(this).closest('.mvp-match-header');
        header.click();
    });
    
    // Player movement functionality for overview accordion
    $(document).on('click', '.mvp-move-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var button = $(this);
        var action = button.data('action');
        var matchId = button.data('match-id');
        var matchContent = button.closest('.mvp-match-content');
        
        if (action === 'move-to-available') {
            // Move selected players to available
            var selectedPlayers = matchContent.find('#selected-players-' + matchId + ' input[name="selected_player_ids[]"]:checked');
            
            selectedPlayers.each(function() {
                var checkbox = $(this);
                var playerItem = checkbox.closest('.mvp-player-item');
                var playerId = playerItem.data('player-id');
                
                // Clone and modify item
                var newItem = playerItem.clone();
                newItem.find('input[name="selected_player_ids[]"]').attr('name', 'available_player_ids[]').prop('checked', false);
                newItem.find('input[name="selected_players[]"]').remove();
                
                // Add to available list
                matchContent.find('#available-players-' + matchId).append(newItem);
                
                // Remove from selected list
                playerItem.remove();
            });
            
        } else if (action === 'move-to-selected') {
            // Move available players to selected
            var availablePlayers = matchContent.find('#available-players-' + matchId + ' input[name="available_player_ids[]"]:checked');
            
            availablePlayers.each(function() {
                var checkbox = $(this);
                var playerItem = checkbox.closest('.mvp-player-item');
                var playerId = playerItem.data('player-id');
                
                // Clone and modify item
                var newItem = playerItem.clone();
                newItem.find('input[name="available_player_ids[]"]').attr('name', 'selected_player_ids[]').prop('checked', false);
                newItem.append('<input type="hidden" name="selected_players[]" value="' + playerId + '">');
                
                // Add to selected list
                matchContent.find('#selected-players-' + matchId).append(newItem);
                
                // Remove from available list
                playerItem.remove();
            });
        }
        
        // Update button states
        updateMoveButtonStates(matchContent, matchId);
    });
    
    // Update move button states based on selections
    function updateMoveButtonStates(matchContent, matchId) {
        var selectedCount = matchContent.find('#selected-players-' + matchId + ' input[name="selected_player_ids[]"]:checked').length;
        var availableCount = matchContent.find('#available-players-' + matchId + ' input[name="available_player_ids[]"]:checked').length;
        
        matchContent.find('[data-action="move-to-available"]').prop('disabled', selectedCount === 0);
        matchContent.find('[data-action="move-to-selected"]').prop('disabled', availableCount === 0);
    }
    
    // Bind checkbox change events for move buttons
    $(document).on('change', 'input[name="selected_player_ids[]"], input[name="available_player_ids[]"]', function() {
        var matchContent = $(this).closest('.mvp-match-content');
        var matchId = matchContent.closest('.mvp-match-accordion').find('[name="match_id"]').val();
        updateMoveButtonStates(matchContent, matchId);
    });
});
</script>

<style>
.mvp-admin-container {
    display: flex;
    gap: 20px;
}

.mvp-left-column {
    flex: 1;
}

.mvp-latest-match {
    background: #f1f1f1;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.mvp-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    margin-right: 10px;
}

.mvp-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.mvp-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.mvp-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .mvp-slider {
    background-color: #2196F3;
}

input:checked + .mvp-slider:before {
    transform: translateX(26px);
}

.mvp-player-lists {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.mvp-selected-players,
.mvp-available-players {
    flex: 1;
}

.mvp-player-list {
    border: 1px solid #ddd;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: #f9f9f9;
}

.mvp-player-item {
    display: flex;
    align-items: center;
    padding: 8px;
    margin-bottom: 5px;
    background: white;
    border-radius: 3px;
    gap: 10px;
}

.jersey-number {
    font-weight: bold;
    min-width: 30px;
}

.player-name {
    flex: 1;
}

.position {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.mvp-move-buttons {
    display: flex;
    flex-direction: column;
    gap: 15px;
    justify-content: center;
    align-items: center;
    min-width: 120px;
    padding: 20px 0;
}

.mvp-move-btn {
    background: #0073aa !important;
    color: white !important;
    border: none !important;
    padding: 15px 20px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    text-align: center !important;
    line-height: 1.2 !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 4px rgba(0,115,170,0.2) !important;
    width: 100% !important;
}

.mvp-move-btn:hover {
    background: #005a87 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0,115,170,0.3) !important;
}

.mvp-move-btn:disabled {
    background: #ccc !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

.mvp-recent-matches {
    max-height: 300px;
    overflow-y: auto;
}

.mvp-match-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.rating-status.open {
    color: green;
    font-weight: bold;
}

.rating-status.closed {
    color: red;
}
</style>