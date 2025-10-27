<?php
/**
 * Plugin Name: Search & Filter for MemberPress
 * Plugin URI: https://yourwebsite.com/search-filter-memberpress
 * Description: Advanced case management system with MemberPress integration and accessible filtering.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * Text Domain: search-filter-memberpress
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SFMP_VERSION', '1.0.0');
define('SFMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SFMP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SFMP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
class Search_Filter_MemberPress {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'check_dependencies'), 5);
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'search-filter-memberpress',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function check_dependencies() {
        // Check if MemberPress is active
        if (!$this->is_memberpress_active()) {
            add_action('admin_notices', array($this, 'memberpress_missing_notice'));
            return false;
        }

        // Load and initialize the plugin
        $this->initialize_plugin();
        return true;
    }

    private function is_memberpress_active() {
        // Multiple checks for MemberPress activation
        return class_exists('MeprJobs') || 
               class_exists('MeprAppCtrl') || 
               function_exists('MeprInit');
    }

    public function memberpress_missing_notice() {
        if (!current_user_can('activate_plugins')) return;
        
        $memberpress_link = '<a href="https://memberpress.com/" target="_blank">' . 
                           __('Get MemberPress', 'search-filter-memberpress') . '</a>';
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php _e('Search & Filter for MemberPress', 'search-filter-memberpress'); ?>:</strong>
                <?php 
                printf(
                    __('This plugin requires MemberPress to be installed and activated. %s', 'search-filter-memberpress'),
                    $memberpress_link
                );
                ?>
            </p>
        </div>
        <?php
    }

    private function initialize_plugin() {
        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->initialize_components();

        // Admin notice for successful activation
        add_action('admin_notices', array($this, 'plugin_activated_notice'));
    }

    private function load_dependencies() {
        $includes_path = SFMP_PLUGIN_PATH . 'includes/';

        $files = array(
            'class-cpt-registration',
            'class-taxonomy-setup',
            'class-dummy-setup',
            'class-sample-data',
            'class-admin-metaboxes',
            'class-settings-page',
            'class-memberpress-integration',
            'class-frontend-archive',
            // We'll add other files as we create them
        );

        foreach ($files as $file) {
            $file_path = $includes_path . $file . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    private function initialize_components() {
        // Initialize only if classes exist
        if (class_exists('SFMP_CPT_Registration')) {
            SFMP_CPT_Registration::get_instance();
        }
        
        if (class_exists('SFMP_Taxonomy_Setup')) {
            SFMP_Taxonomy_Setup::get_instance();
        }
        if (class_exists('SFMP_Dummy_Setup')) {
            SFMP_Dummy_Setup::get_instance();
        }
    
        if (class_exists('SFMP_Sample_Data')) {
            SFMP_Sample_Data::get_instance();
        }
        if (class_exists('SFMP_Admin_Metaboxes')) {
            SFMP_Admin_Metaboxes::get_instance();
        }
        if (class_exists('SFMP_Settings_Page')) {
            SFMP_Settings_Page::get_instance();
        }
        if (class_exists('SFMP_MemberPress_Integration')) {
            SFMP_MemberPress_Integration::get_instance();
        }
    
        if (class_exists('SFMP_Frontend_Archive')) {
            SFMP_Frontend_Archive::get_instance();
        }
    }

    

    public function plugin_activated_notice() {
        if (get_transient('sfmp_plugin_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('Search & Filter for MemberPress', 'search-filter-memberpress'); ?>:</strong>
                    <?php _e('Plugin activated successfully! You can now create Cases under the "Cases" menu.', 'search-filter-memberpress'); ?>
                </p>
            </div>
            <?php
            delete_transient('sfmp_plugin_activated');
        }
    }
}

// Initialize the plugin
function sfmp_init() {
    return Search_Filter_MemberPress::get_instance();
}

// Use plugins_loaded hook with priority to ensure MemberPress is loaded first
add_action('plugins_loaded', 'sfmp_init', 11);

// Activation hook
register_activation_hook(__FILE__, 'sfmp_activate_plugin');
function sfmp_activate_plugin() {
    // Set transient for activation notice
    set_transient('sfmp_plugin_activated', true, 30);
    
    // Flush rewrite rules for custom post type
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'sfmp_deactivate_plugin');
function sfmp_deactivate_plugin() {
    // Clean up rewrite rules
    flush_rewrite_rules();
    
    // Clear activation transient
    delete_transient('sfmp_plugin_activated');
}

// Debug function to check plugin status
function sfmp_debug_status() {
    if (!current_user_can('manage_options')) return;
    
    $status = array(
        'Plugin Path' => SFMP_PLUGIN_PATH,
        'MemberPress Active' => class_exists('MeprJobs') ? 'Yes' : 'No',
        'CPT Registered' => post_type_exists('case_item') ? 'Yes' : 'No',
        'Taxonomies Registered' => array(
            'topic' => taxonomy_exists('topic') ? 'Yes' : 'No',
            'experience_group' => taxonomy_exists('experience_group') ? 'Yes' : 'No'
        )
    );
    
    echo '<div class="notice notice-info"><pre>';
    print_r($status);
    echo '</pre></div>';
}
// add_action('admin_notices', 'sfmp_debug_status'); // Uncomment for debugging