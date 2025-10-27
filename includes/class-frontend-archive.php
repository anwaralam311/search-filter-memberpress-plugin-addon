<?php

class SFMP_Frontend_Archive {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('template_redirect', array($this, 'handle_case_archive'));
        add_shortcode('case_archive', array($this, 'case_archive_shortcode'));
    }

    public function enqueue_frontend_assets() {
        if (is_post_type_archive('case_item') || has_shortcode(get_post()->post_content, 'case_archive')) {
            $settings = SFMP_Settings_Page::get_settings();
            
            if ($settings['enqueue_assets']) {
                wp_enqueue_style(
                    'sfmp-frontend',
                    SFMP_PLUGIN_URL . 'assets/css/frontend.css',
                    array(),
                    SFMP_VERSION
                );

                wp_enqueue_script(
                    'sfmp-frontend',
                    SFMP_PLUGIN_URL . 'assets/js/frontend.js',
                    array('jquery'),
                    SFMP_VERSION,
                    true
                );

                // Localize script for AJAX
                wp_localize_script('sfmp-frontend', 'sfmp_frontend', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('sfmp_frontend_nonce'),
                    'loading_text' => __('Loading...', 'search-filter-memberpress'),
                    'no_results' => __('No cases found.', 'search-filter-memberpress')
                ));
            }
        }
    }

    public function handle_case_archive() {
        if (is_post_type_archive('case_item')) {
            // Set up archive page title
            add_filter('document_title_parts', array($this, 'modify_archive_title'));
            add_filter('the_title', array($this, 'modify_archive_page_title'));
            
            // Load our template
            add_filter('template_include', array($this, 'load_archive_template'));
        }
    }

    public function modify_archive_title($title) {
        if (is_post_type_archive('case_item')) {
            $settings = SFMP_Settings_Page::get_settings();
            $title['title'] = $settings['archive_title'];
        }
        return $title;
    }

    public function modify_archive_page_title($title) {
        if (is_post_type_archive('case_item') && in_the_loop()) {
            $settings = SFMP_Settings_Page::get_settings();
            return $settings['archive_title'];
        }
        return $title;
    }

    public function load_archive_template($template) {
        if (is_post_type_archive('case_item')) {
            $theme_template = locate_template('search-filter-memberpress/archive-case_item.php');
            
            if ($theme_template) {
                return $theme_template;
            }
            
            return SFMP_PLUGIN_PATH . 'templates/archive-case_item.php';
        }
        return $template;
    }

    public function case_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'items_per_page' => '',
            'show_filters' => 'true',
            'show_search' => 'true',
            'show_sort' => 'true'
        ), $atts, 'case_archive');

        ob_start();
        $this->render_archive_content($atts);
        return ob_get_clean();
    }

    public function render_archive_content($shortcode_atts = array()) {
        $settings = SFMP_Settings_Page::get_settings();
        
        // Merge shortcode attributes with settings
        $items_per_page = !empty($shortcode_atts['items_per_page']) ? 
            intval($shortcode_atts['items_per_page']) : 
            $settings['items_per_page'];

        // Get filter parameters
        $filters = $this->get_current_filters();

        // Build query args
        $args = array(
            'post_type' => 'case_item',
            'post_status' => 'publish',
            'posts_per_page' => $items_per_page,
            'paged' => max(1, get_query_var('paged') ?: 1)
        );

        // Add search
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }

        // Add taxonomy filters
        $tax_query = array();

        if (!empty($filters['topics'])) {
            $tax_query[] = array(
                'taxonomy' => 'topic',
                'field' => 'term_id',
                'terms' => array_map('intval', $filters['topics']),
                'operator' => 'IN'
            );
        }

        if (!empty($filters['experience_group'])) {
            $tax_query[] = array(
                'taxonomy' => 'experience_group',
                'field' => 'term_id',
                'terms' => intval($filters['experience_group']),
                'operator' => 'IN'
            );
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Add sorting
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'newest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
                case 'oldest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                case 'title_asc':
                    $args['orderby'] = 'title';
                    $args['order'] = 'ASC';
                    break;
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
        }

        // Apply filters for custom modifications
        $args = apply_filters('sfmp_query_args', $args, $filters);

        // Get cases
        $cases_query = new WP_Query($args);

        // Load template
        $this->load_archive_template_part($cases_query, $filters, $shortcode_atts);
    }

    private function get_current_filters() {
        $filters = array(
            'search' => '',
            'topics' => array(),
            'experience_group' => '',
            'sort' => 'newest'
        );

        // Get from GET parameters
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }

        if (isset($_GET['topics']) && !empty($_GET['topics'])) {
            $filters['topics'] = array_map('intval', (array)$_GET['topics']);
        }

        if (isset($_GET['experience_group']) && !empty($_GET['experience_group'])) {
            $filters['experience_group'] = intval($_GET['experience_group']);
        }

        if (isset($_GET['sort']) && !empty($_GET['sort'])) {
            $filters['sort'] = sanitize_text_field($_GET['sort']);
        }

        return $filters;
    }

    private function load_archive_template_part($cases_query, $filters, $shortcode_atts) {
        $template_path = locate_template('search-filter-memberpress/archive-content.php');
        
        if (!$template_path) {
            $template_path = SFMP_PLUGIN_PATH . 'templates/archive-content.php';
        }

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>' . __('Archive template not found.', 'search-filter-memberpress') . '</p>';
        }
    }
}