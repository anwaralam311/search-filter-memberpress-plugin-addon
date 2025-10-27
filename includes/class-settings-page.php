<?php

class SFMP_Settings_Page {

    private static $instance = null;
    private $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_scripts'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=case_item',
            __('Search & Filter Settings', 'search-filter-memberpress'),
            __('Settings', 'search-filter-memberpress'),
            'manage_options',
            'search-filter-memberpress-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        register_setting(
            'sfmp_settings_group',
            'sfmp_settings',
            array($this, 'sanitize_settings')
        );

        // General Settings Section
        add_settings_section(
            'sfmp_general_section',
            __('General Settings', 'search-filter-memberpress'),
            array($this, 'general_section_callback'),
            'sfmp_settings_page'
        );

        // MemberPress Integration Section
        add_settings_section(
            'sfmp_memberpress_section',
            __('MemberPress Integration', 'search-filter-memberpress'),
            array($this, 'memberpress_section_callback'),
            'sfmp_settings_page'
        );

        // Performance Section
        add_settings_section(
            'sfmp_performance_section',
            __('Performance Settings', 'search-filter-memberpress'),
            array($this, 'performance_section_callback'),
            'sfmp_settings_page'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        // General Settings Fields
        add_settings_field(
            'archive_title',
            __('Archive Page Title', 'search-filter-memberpress'),
            array($this, 'archive_title_callback'),
            'sfmp_settings_page',
            'sfmp_general_section'
        );

        add_settings_field(
            'items_per_page',
            __('Items Per Page', 'search-filter-memberpress'),
            array($this, 'items_per_page_callback'),
            'sfmp_settings_page',
            'sfmp_general_section'
        );

        // MemberPress Fields
        add_settings_field(
            'free_product_id',
            __('Free Access Product', 'search-filter-memberpress'),
            array($this, 'free_product_id_callback'),
            'sfmp_settings_page',
            'sfmp_memberpress_section'
        );

        add_settings_field(
            'standard_product_id',
            __('Standard Plan Product', 'search-filter-memberpress'),
            array($this, 'standard_product_id_callback'),
            'sfmp_settings_page',
            'sfmp_memberpress_section'
        );

        add_settings_field(
            'premium_product_id',
            __('Premium Plan Product', 'search-filter-memberpress'),
            array($this, 'premium_product_id_callback'),
            'sfmp_settings_page',
            'sfmp_memberpress_section'
        );

        // Performance Fields
        add_settings_field(
            'enable_caching',
            __('Enable Caching', 'search-filter-memberpress'),
            array($this, 'enable_caching_callback'),
            'sfmp_settings_page',
            'sfmp_performance_section'
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration', 'search-filter-memberpress'),
            array($this, 'cache_duration_callback'),
            'sfmp_settings_page',
            'sfmp_performance_section'
        );

        add_settings_field(
            'enqueue_assets',
            __('Load Plugin Assets', 'search-filter-memberpress'),
            array($this, 'enqueue_assets_callback'),
            'sfmp_settings_page',
            'sfmp_performance_section'
        );
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure general display and behavior settings.', 'search-filter-memberpress') . '</p>';
    }

    public function memberpress_section_callback() {
        echo '<p>' . __('Map MemberPress products to case access levels. Use the product IDs from your MemberPress memberships.', 'search-filter-memberpress') . '</p>';
        
        // Show dummy product IDs if available
        $dummy_products = get_option('sfmp_dummy_products', array());
        if (!empty($dummy_products)) {
            echo '<div class="notice notice-info inline">';
            echo '<p><strong>' . __('Development Mode - Dummy Products:', 'search-filter-memberpress') . '</strong></p>';
            foreach ($dummy_products as $name => $id) {
                echo '<p>' . esc_html($name) . ': <code>' . esc_html($id) . '</code></p>';
            }
            echo '</div>';
        }
    }

    public function performance_section_callback() {
        echo '<p>' . __('Optimize plugin performance with caching and asset management.', 'search-filter-memberpress') . '</p>';
    }

    public function archive_title_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['archive_title']) ? $options['archive_title'] : 'Browse Cases by Topic';
        ?>
        <input type="text" name="sfmp_settings[archive_title]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Title displayed on the cases archive page.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function items_per_page_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['items_per_page']) ? $options['items_per_page'] : 15;
        ?>
        <input type="number" name="sfmp_settings[items_per_page]" value="<?php echo esc_attr($value); ?>" min="1" max="50" class="small-text">
        <p class="description"><?php _e('Number of cases to show per page on the archive.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function free_product_id_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['free_product_id']) ? $options['free_product_id'] : '';
        $products = $this->get_memberpress_products();
        ?>
        <select name="sfmp_settings[free_product_id]" class="regular-text">
            <option value=""><?php _e('Select Free Access Product', 'search-filter-memberpress'); ?></option>
            <?php foreach ($products as $product): ?>
                <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($value, $product->ID); ?>>
                    <?php echo esc_html($product->post_title); ?> (ID: <?php echo esc_html($product->ID); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Membership product that grants access to free cases.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function standard_product_id_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['standard_product_id']) ? $options['standard_product_id'] : '';
        $products = $this->get_memberpress_products();
        ?>
        <select name="sfmp_settings[standard_product_id]" class="regular-text">
            <option value=""><?php _e('Select Standard Plan Product', 'search-filter-memberpress'); ?></option>
            <?php foreach ($products as $product): ?>
                <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($value, $product->ID); ?>>
                    <?php echo esc_html($product->post_title); ?> (ID: <?php echo esc_html($product->ID); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Membership product for standard case access.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function premium_product_id_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['premium_product_id']) ? $options['premium_product_id'] : '';
        $products = $this->get_memberpress_products();
        ?>
        <select name="sfmp_settings[premium_product_id]" class="regular-text">
            <option value=""><?php _e('Select Premium Plan Product', 'search-filter-memberpress'); ?></option>
            <?php foreach ($products as $product): ?>
                <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($value, $product->ID); ?>>
                    <?php echo esc_html($product->post_title); ?> (ID: <?php echo esc_html($product->ID); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Membership product for premium case access.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function enable_caching_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['enable_caching']) ? $options['enable_caching'] : 1;
        ?>
        <label>
            <input type="checkbox" name="sfmp_settings[enable_caching]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Enable query caching for better performance', 'search-filter-memberpress'); ?>
        </label>
        <p class="description"><?php _e('Caches archive query results to improve page load times.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function cache_duration_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['cache_duration']) ? $options['cache_duration'] : 3600;
        ?>
        <select name="sfmp_settings[cache_duration]" class="regular-text">
            <option value="900" <?php selected($value, 900); ?>><?php _e('15 Minutes', 'search-filter-memberpress'); ?></option>
            <option value="1800" <?php selected($value, 1800); ?>><?php _e('30 Minutes', 'search-filter-memberpress'); ?></option>
            <option value="3600" <?php selected($value, 3600); ?>><?php _e('1 Hour', 'search-filter-memberpress'); ?></option>
            <option value="7200" <?php selected($value, 7200); ?>><?php _e('2 Hours', 'search-filter-memberpress'); ?></option>
            <option value="86400" <?php selected($value, 86400); ?>><?php _e('24 Hours', 'search-filter-memberpress'); ?></option>
        </select>
        <p class="description"><?php _e('How long to cache query results before refreshing.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    public function enqueue_assets_callback() {
        $options = get_option('sfmp_settings');
        $value = isset($options['enqueue_assets']) ? $options['enqueue_assets'] : 1;
        ?>
        <label>
            <input type="checkbox" name="sfmp_settings[enqueue_assets]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Load plugin CSS and JavaScript files', 'search-filter-memberpress'); ?>
        </label>
        <p class="description"><?php _e('Disable if you want to handle styling and functionality in your theme.', 'search-filter-memberpress'); ?></p>
        <?php
    }

    private function get_memberpress_products() {
        $products = array();

        if (!class_exists('MeprProduct')) {
            return $products;
        }

        // Get all MemberPress products
        $args = array(
            'post_type' => 'memberpressproduct',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $product = new MeprProduct($post->ID);
            if ($product->ID) {
                $products[] = $post;
            }
        }

        return $products;
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        // General settings
        $sanitized['archive_title'] = sanitize_text_field($input['archive_title'] ?? 'Browse Cases by Topic');
        $sanitized['items_per_page'] = absint($input['items_per_page'] ?? 15);
        
        // MemberPress product IDs
        $sanitized['free_product_id'] = absint($input['free_product_id'] ?? 0);
        $sanitized['standard_product_id'] = absint($input['standard_product_id'] ?? 0);
        $sanitized['premium_product_id'] = absint($input['premium_product_id'] ?? 0);
        
        // Performance settings
        $sanitized['enable_caching'] = isset($input['enable_caching']) ? 1 : 0;
        $sanitized['cache_duration'] = absint($input['cache_duration'] ?? 3600);
        $sanitized['enqueue_assets'] = isset($input['enqueue_assets']) ? 1 : 0;

        // Show success message
        add_settings_error(
            'sfmp_settings',
            'sfmp_settings_updated',
            __('Settings saved successfully.', 'search-filter-memberpress'),
            'success'
        );

        return $sanitized;
    }

    public function enqueue_settings_scripts($hook) {
        if ('case_item_page_search-filter-memberpress-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('sfmp-settings', SFMP_PLUGIN_URL . 'assets/css/settings.css', array(), SFMP_VERSION);
    }

    public function settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'search-filter-memberpress'));
        }

        // Show settings errors
        settings_errors('sfmp_settings');
        ?>
        <div class="wrap sfmp-settings-wrap">
            <h1><?php _e('Search & Filter for MemberPress - Settings', 'search-filter-memberpress'); ?></h1>

            <div class="sfmp-settings-container">
                <div class="sfmp-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('sfmp_settings_group');
                        do_settings_sections('sfmp_settings_page');
                        submit_button(__('Save Settings', 'search-filter-memberpress'));
                        ?>
                    </form>
                </div>

                <div class="sfmp-settings-sidebar">
                    <div class="sfmp-settings-box">
                        <h3><?php _e('Quick Setup Guide', 'search-filter-memberpress'); ?></h3>
                        <ol>
                            <li><?php _e('Create MemberPress membership products', 'search-filter-memberpress'); ?></li>
                            <li><?php _e('Map products to access levels below', 'search-filter-memberpress'); ?></li>
                            <li><?php _e('Create cases and set access permissions', 'search-filter-memberpress'); ?></li>
                            <li><?php _e('Visit <code>/cases/</code> on your site to view the archive', 'search-filter-memberpress'); ?></li>
                        </ol>
                    </div>

                    <div class="sfmp-settings-box">
                        <h3><?php _e('Shortcode Usage', 'search-filter-memberpress'); ?></h3>
                        <p><?php _e('Display the cases archive anywhere:', 'search-filter-memberpress'); ?></p>
                        <code>[case_archive]</code>
                        <p><?php _e('With custom parameters:', 'search-filter-memberpress'); ?></p>
                        <code>[case_archive items_per_page="12" show_filters="true"]</code>
                    </div>

                    <div class="sfmp-settings-box">
                        <h3><?php _e('Template Overrides', 'search-filter-memberpress'); ?></h3>
                        <p><?php _e('Override plugin templates in your theme:', 'search-filter-memberpress'); ?></p>
                        <code>theme/search-filter-memberpress/archive-case_item.php</code>
                        <code>theme/search-filter-memberpress/single-case_item.php</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // Public method to get settings
    public static function get_settings() {
        $defaults = array(
            'archive_title' => 'Browse Cases by Topic',
            'items_per_page' => 15,
            'free_product_id' => 0,
            'standard_product_id' => 0,
            'premium_product_id' => 0,
            'enable_caching' => 1,
            'cache_duration' => 3600,
            'enqueue_assets' => 1
        );

        $settings = get_option('sfmp_settings', array());
        return wp_parse_args($settings, $defaults);
    }
}