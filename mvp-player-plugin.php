<?php
/**
 * Plugin Name: MVP - Player of the Year
 * Plugin URI: https://yourwebsite.com/mvp-plugin
 * Description: A comprehensive plugin for rating football players and determining the player of the year.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: mvp-player
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MVP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MVP_PLUGIN_VERSION', '1.0.0');

class MVPPlayerPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_ajax_mvp_save_rating', array($this, 'save_rating'));
        add_action('wp_ajax_nopriv_mvp_save_rating', array($this, 'save_rating'));
        add_shortcode('mvp_player_ratings', array($this, 'display_ratings_shortcode'));
        add_shortcode('mvp_player_list', array($this, 'display_player_list_shortcode'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('mvp-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Players table
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
        
        // Matches table
        $matches_table = $wpdb->prefix . 'mvp_matches';
        $sql_matches = "CREATE TABLE $matches_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            match_date datetime NOT NULL,
            home_team varchar(100) NOT NULL,
            away_team varchar(100) NOT NULL,
            is_open_for_rating tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Match selections table
        $selections_table = $wpdb->prefix . 'mvp_match_selections';
        $sql_selections = "CREATE TABLE $selections_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            match_id mediumint(9) NOT NULL,
            player_id mediumint(9) NOT NULL,
            minutes_played int(3) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY match_id (match_id),
            KEY player_id (player_id)
        ) $charset_collate;";
        
        // Ratings table
        $ratings_table = $wpdb->prefix . 'mvp_ratings';
        $sql_ratings = "CREATE TABLE $ratings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            match_id mediumint(9) NOT NULL,
            player_id mediumint(9) NOT NULL,
            rating decimal(3,2) NOT NULL,
            voter_ip varchar(45),
            voter_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY match_id (match_id),
            KEY player_id (player_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_players);
        dbDelta($sql_matches);
        dbDelta($sql_selections);
        dbDelta($sql_ratings);
    }
    
    private function set_default_options() {
        add_option('mvp_club_name', '');
        add_option('mvp_min_games_percentage', 60);
        add_option('mvp_rating_system', '1-10');
        add_option('mvp_vote_duration', 'next_match');
        add_option('mvp_vote_rights', 'visitors');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('MVP - Player of the Year', 'mvp-player'),
            __('MVP', 'mvp-player'),
            'manage_options',
            'mvp-overview',
            array($this, 'admin_overview_page'),
            'dashicons-awards',
            30
        );
        
        add_submenu_page(
            'mvp-overview',
            __('Overview', 'mvp-player'),
            __('Overview', 'mvp-player'),
            'manage_options',
            'mvp-overview',
            array($this, 'admin_overview_page')
        );
        
        add_submenu_page(
            'mvp-overview',
            __('Players', 'mvp-player'),
            __('Players', 'mvp-player'),
            'manage_options',
            'mvp-players',
            array($this, 'admin_players_page')
        );
        
        add_submenu_page(
            'mvp-overview',
            __('Matches', 'mvp-player'),
            __('Matches', 'mvp-player'),
            'manage_options',
            'mvp-matches',
            array($this, 'admin_matches_page')
        );
        
        add_submenu_page(
            'mvp-overview',
            __('Settings', 'mvp-player'),
            __('Settings', 'mvp-player'),
            'manage_options',
            'mvp-settings',
            array($this, 'admin_settings_page')
        );
    }
    
    public function render_admin_tabs() {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'mvp-overview';
        $tabs = array(
            'mvp-overview' => __('Overview', 'mvp-player'),
            'mvp-players' => __('Players', 'mvp-player'),
            'mvp-matches' => __('Matches', 'mvp-player'),
            'mvp-settings' => __('Settings', 'mvp-player')
        );
        
        echo '<div class="mvp-admin-tabs">';
        foreach ($tabs as $tab_key => $tab_name) {
            $active_class = ($current_page === $tab_key) ? ' mvp-tab-active' : '';
            $url = admin_url('admin.php?page=' . $tab_key);
            echo '<a href="' . esc_url($url) . '" class="mvp-tab' . $active_class . '">' . esc_html($tab_name) . '</a>';
        }
        echo '</div>';
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'mvp-') !== false) {
            wp_enqueue_script('mvp-admin', MVP_PLUGIN_URL . 'assets/admin.js', array('jquery'), MVP_PLUGIN_VERSION, true);
            wp_enqueue_style('mvp-admin', MVP_PLUGIN_URL . 'assets/admin.css', array(), MVP_PLUGIN_VERSION);
            wp_localize_script('mvp-admin', 'mvp_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mvp_nonce')
            ));
        }
    }
    
    public function frontend_scripts() {
        wp_enqueue_script('mvp-frontend', MVP_PLUGIN_URL . 'assets/frontend.js', array('jquery'), MVP_PLUGIN_VERSION, true);
        wp_enqueue_style('mvp-frontend', MVP_PLUGIN_URL . 'assets/frontend.css', array(), MVP_PLUGIN_VERSION);
        wp_localize_script('mvp-frontend', 'mvp_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mvp_nonce')
        ));
    }
    
    public function admin_overview_page() {
        include MVP_PLUGIN_PATH . 'admin/overview.php';
    }
    
    public function admin_players_page() {
        include MVP_PLUGIN_PATH . 'admin/players.php';
    }
    
    public function admin_matches_page() {
        include MVP_PLUGIN_PATH . 'admin/matches.php';
    }
    
    public function admin_settings_page() {
        include MVP_PLUGIN_PATH . 'admin/settings.php';
    }
    
    public function save_rating() {
        check_ajax_referer('mvp_nonce', 'nonce');
        
        $match_id = intval($_POST['match_id']);
        $player_id = intval($_POST['player_id']);
        $rating = floatval($_POST['rating']);
        
        // Validate rating based on system
        $rating_system = get_option('mvp_rating_system', '1-10');
        if ($rating_system === '1-10' && ($rating < 1 || $rating > 10)) {
            wp_die('Invalid rating');
        } elseif ($rating_system === 'stars' && ($rating < 0.5 || $rating > 5)) {
            wp_die('Invalid rating');
        }
        
        // Check voting rights
        $vote_rights = get_option('mvp_vote_rights', 'visitors');
        $voter_id = null;
        $voter_ip = $_SERVER['REMOTE_ADDR'];
        
        if ($vote_rights === 'users' && !is_user_logged_in()) {
            wp_die('Login required');
        } elseif ($vote_rights === 'admins' && !current_user_can('manage_options')) {
            wp_die('Admin access required');
        }
        
        if (is_user_logged_in()) {
            $voter_id = get_current_user_id();
        }
        
        // Check if already voted
        global $wpdb;
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mvp_ratings 
             WHERE match_id = %d AND player_id = %d AND (voter_ip = %s OR voter_id = %d)",
            $match_id, $player_id, $voter_ip, $voter_id
        ));
        
        if ($existing_vote) {
            wp_die('Already voted');
        }
        
        // Save rating
        $wpdb->insert(
            $wpdb->prefix . 'mvp_ratings',
            array(
                'match_id' => $match_id,
                'player_id' => $player_id,
                'rating' => $rating,
                'voter_ip' => $voter_ip,
                'voter_id' => $voter_id
            )
        );
        
        wp_send_json_success();
    }
    
    public function display_ratings_shortcode($atts) {
        $atts = shortcode_atts(array(
            'match_id' => 0,
            'show_form' => 'true'
        ), $atts);
        
        ob_start();
        include MVP_PLUGIN_PATH . 'templates/rating-form.php';
        return ob_get_clean();
    }
    
    public function display_player_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'season',
            'limit' => 10
        ), $atts);
        
        ob_start();
        include MVP_PLUGIN_PATH . 'templates/player-list.php';
        return ob_get_clean();
    }
    
    public function get_positions() {
        return array(
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
    }
}

// Initialize the plugin
global $mvp_plugin_instance;
$mvp_plugin_instance = new MVPPlayerPlugin();