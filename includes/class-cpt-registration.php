<?php

class SFMP_CPT_Registration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_case_item_cpt'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Debug hook to verify registration
        add_action('admin_init', array($this, 'debug_registration'));
    }

    public function register_case_item_cpt() {
        // First, check if we should register (MemberPress is active)
        if (!class_exists('MeprJobs') && !class_exists('MeprAppCtrl')) {
            return;
        }

        $labels = array(
            'name'                  => _x('Cases', 'Post Type General Name', 'search-filter-memberpress'),
            'singular_name'         => _x('Case', 'Post Type Singular Name', 'search-filter-memberpress'),
            'menu_name'             => __('Cases', 'search-filter-memberpress'),
            'name_admin_bar'        => __('Case', 'search-filter-memberpress'),
            'archives'              => __('Case Archives', 'search-filter-memberpress'),
            'attributes'            => __('Case Attributes', 'search-filter-memberpress'),
            'parent_item_colon'     => __('Parent Case:', 'search-filter-memberpress'),
            'all_items'             => __('All Cases', 'search-filter-memberpress'),
            'add_new_item'          => __('Add New Case', 'search-filter-memberpress'),
            'add_new'               => __('Add New', 'search-filter-memberpress'),
            'new_item'              => __('New Case', 'search-filter-memberpress'),
            'edit_item'             => __('Edit Case', 'search-filter-memberpress'),
            'update_item'           => __('Update Case', 'search-filter-memberpress'),
            'view_item'             => __('View Case', 'search-filter-memberpress'),
            'view_items'            => __('View Cases', 'search-filter-memberpress'),
            'search_items'          => __('Search Case', 'search-filter-memberpress'),
            'not_found'             => __('Not found', 'search-filter-memberpress'),
            'not_found_in_trash'    => __('Not found in Trash', 'search-filter-memberpress'),
            'featured_image'        => __('Case Image', 'search-filter-memberpress'),
            'set_featured_image'    => __('Set case image', 'search-filter-memberpress'),
            'remove_featured_image' => __('Remove case image', 'search-filter-memberpress'),
            'use_featured_image'    => __('Use as case image', 'search-filter-memberpress'),
            'insert_into_item'      => __('Insert into case', 'search-filter-memberpress'),
            'uploaded_to_this_item' => __('Uploaded to this case', 'search-filter-memberpress'),
            'items_list'            => __('Cases list', 'search-filter-memberpress'),
            'items_list_navigation' => __('Cases list navigation', 'search-filter-memberpress'),
            'filter_items_list'     => __('Filter cases list', 'search-filter-memberpress'),
        );

        $args = array(
            'label'                 => __('Case', 'search-filter-memberpress'),
            'description'           => __('Case studies and scenarios', 'search-filter-memberpress'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
            'taxonomies'            => array(), // We'll register taxonomies separately
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-portfolio',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'archive_slug'          => 'cases',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'cases',
        );

        register_post_type('case_item', $args);
        
        // Debug log
        error_log('SFMP: Case Item CPT registered');
    }

    public function register_taxonomies() {
        // First, check if we should register (MemberPress is active)
        if (!class_exists('MeprJobs') && !class_exists('MeprAppCtrl')) {
            return;
        }

        // Register topic taxonomy
        $topic_labels = array(
            'name'              => _x('Topics', 'taxonomy general name', 'search-filter-memberpress'),
            'singular_name'     => _x('Topic', 'taxonomy singular name', 'search-filter-memberpress'),
            'search_items'      => __('Search Topics', 'search-filter-memberpress'),
            'all_items'         => __('All Topics', 'search-filter-memberpress'),
            'parent_item'       => __('Parent Topic', 'search-filter-memberpress'),
            'parent_item_colon' => __('Parent Topic:', 'search-filter-memberpress'),
            'edit_item'         => __('Edit Topic', 'search-filter-memberpress'),
            'update_item'       => __('Update Topic', 'search-filter-memberpress'),
            'add_new_item'      => __('Add New Topic', 'search-filter-memberpress'),
            'new_item_name'     => __('New Topic Name', 'search-filter-memberpress'),
            'menu_name'         => __('Topics', 'search-filter-memberpress'),
        );

        $topic_args = array(
            'hierarchical'      => true,
            'labels'            => $topic_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'topic'),
        );

        register_taxonomy('topic', array('case_item'), $topic_args);

        // Register experience group taxonomy
        $experience_labels = array(
            'name'              => _x('Experience Groups', 'taxonomy general name', 'search-filter-memberpress'),
            'singular_name'     => _x('Experience Group', 'taxonomy singular name', 'search-filter-memberpress'),
            'search_items'      => __('Search Experience Groups', 'search-filter-memberpress'),
            'all_items'         => __('All Experience Groups', 'search-filter-memberpress'),
            'parent_item'       => __('Parent Experience Group', 'search-filter-memberpress'),
            'parent_item_colon' => __('Parent Experience Group:', 'search-filter-memberpress'),
            'edit_item'         => __('Edit Experience Group', 'search-filter-memberpress'),
            'update_item'       => __('Update Experience Group', 'search-filter-memberpress'),
            'add_new_item'      => __('Add New Experience Group', 'search-filter-memberpress'),
            'new_item_name'     => __('New Experience Group Name', 'search-filter-memberpress'),
            'menu_name'         => __('Experience Groups', 'search-filter-memberpress'),
        );

        $experience_args = array(
            'hierarchical'      => true,
            'labels'            => $experience_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'experience-group'),
        );

        register_taxonomy('experience_group', array('case_item'), $experience_args);
        
        // Debug log
        error_log('SFMP: Taxonomies registered');
    }

    public function debug_registration() {
        if (!current_user_can('manage_options')) return;
        
        // Check if CPT and taxonomies are registered
        $debug_info = array(
            'CPT "case_item" exists' => post_type_exists('case_item') ? '✅ Yes' : '❌ No',
            'Taxonomy "topic" exists' => taxonomy_exists('topic') ? '✅ Yes' : '❌ No',
            'Taxonomy "experience_group" exists' => taxonomy_exists('experience_group') ? '✅ Yes' : '❌ No',
            'MemberPress classes exist' => (class_exists('MeprJobs') || class_exists('MeprAppCtrl')) ? '✅ Yes' : '❌ No'
        );
        
        // Log to error log for debugging
        error_log('SFMP Debug: ' . print_r($debug_info, true));
    }
}