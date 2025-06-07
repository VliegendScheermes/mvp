phppublic function save_rating() {
    // Verify nonce
    if (!check_ajax_referer('mvp_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $match_id = intval($_POST['match_id']);
    $player_id = intval($_POST['player_id']);
    $rating = floatval($_POST['rating']);
    
    // Validate inputs
    if (!$match_id || !$player_id || !$rating) {
        wp_send_json_error('Missing required data');
        return;
    }
    
    // Validate rating based on system
    $rating_system = get_option('mvp_rating_system', '1-10');
    if ($rating_system === '1-10' && ($rating < 1 || $rating > 10)) {
        wp_send_json_error('Invalid rating range for 1-10 system');
        return;
    } elseif ($rating_system === 'stars' && ($rating < 0.5 || $rating > 5)) {
        wp_send_json_error('Invalid rating range for star system');
        return;
    }
    
    // Check if match exists and is open
    global $wpdb;
    $match = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mvp_matches WHERE id = %d AND is_open_for_rating = 1",
        $match_id
    ));
    
    if (!$match) {
        wp_send_json_error('Match not found or voting is closed');
        return;
    }
    
    // Check voting rights
    $vote_rights = get_option('mvp_vote_rights', 'visitors');
    $voter_id = null;
    $voter_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($vote_rights === 'users' && !is_user_logged_in()) {
        wp_send_json_error('You must be logged in to vote');
        return;
    } elseif ($vote_rights === 'admins' && !current_user_can('manage_options')) {
        wp_send_json_error('Admin access required to vote');
        return;
    }
    
    if (is_user_logged_in()) {
        $voter_id = get_current_user_id();
    }
    
    // Check if already voted for this player
    $where_clause = $voter_id ? 
        $wpdb->prepare("voter_id = %d", $voter_id) : 
        $wpdb->prepare("voter_ip = %s AND voter_id IS NULL", $voter_ip);
        
    $existing_vote = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mvp_ratings 
         WHERE match_id = %d AND player_id = %d AND ({$where_clause})",
        $match_id, $player_id
    ));
    
    if ($existing_vote) {
        wp_send_json_error('You have already rated this player');
        return;
    }
    
    // Save rating
    $result = $wpdb->insert(
        $wpdb->prefix . 'mvp_ratings',
        array(
            'match_id' => $match_id,
            'player_id' => $player_id,
            'rating' => $rating,
            'voter_ip' => $voter_ip,
            'voter_id' => $voter_id
        ),
        array('%d', '%d', '%f', '%s', '%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
        return;
    }
    
    // Get player name for response
    $player = $wpdb->get_row($wpdb->prepare(
        "SELECT first_name, last_name FROM {$wpdb->prefix}mvp_players WHERE id = %d",
        $player_id
    ));
    
    $player_name = $player ? $player->first_name . ' ' . $player->last_name : 'Unknown Player';
    
    wp_send_json_success(array(
        'message' => sprintf('Rating saved for %s: %s', $player_name, $rating),
        'rating' => $rating,
        'player_name' => $player_name
    ));
}