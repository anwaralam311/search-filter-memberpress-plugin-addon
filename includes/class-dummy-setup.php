<?php

class SFMP_Dummy_Setup {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'create_dummy_products'));
        add_action('admin_notices', array($this, 'dummy_setup_notice'));
    }

    public function create_dummy_products() {
        // Only run once and only for admins
        if (!current_user_can('manage_options') || get_option('sfmp_dummy_created')) {
            return;
        }

        // Check if MemberPress product class exists
        if (!class_exists('MeprProduct')) {
            return;
        }

        $products = array(
            array(
                'title' => 'Free Access Plan',
                'price' => 0.00,
                'slug' => 'free-access-plan',
                'description' => 'Free access to basic cases'
            ),
            array(
                'title' => 'Standard Plan', 
                'price' => 49.00,
                'slug' => 'standard-plan',
                'description' => 'Access to standard cases'
            ),
            array(
                'title' => 'Premium Plan',
                'price' => 99.00, 
                'slug' => 'premium-plan',
                'description' => 'Access to all premium cases'
            )
        );

        $created_products = array();

        foreach ($products as $product_data) {
            $product = new MeprProduct();
            $product->post_title = $product_data['title'];
            $product->post_name = $product_data['slug'];
            $product->post_content = $product_data['description'];
            $product->post_status = 'publish';
            $product->price = $product_data['price'];
            $product->period = 1;
            $product->period_type = 'years';
            $product->signup_button_text = 'Get Access';

            if ($product->store()) {
                $created_products[$product_data['title']] = $product->ID;
            }
        }

        if (!empty($created_products)) {
            update_option('sfmp_dummy_products', $created_products);
            update_option('sfmp_dummy_created', true);
        }
    }

    public function dummy_setup_notice() {
        if (get_option('sfmp_dummy_created') && current_user_can('manage_options')) {
            $products = get_option('sfmp_dummy_products', array());
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Search & Filter for MemberPress - Development Mode</strong><br>
                    ✅ Dummy products created for testing:<br>
                    <?php foreach ($products as $name => $id): ?>
                        • <?php echo esc_html($name); ?> (ID: <?php echo esc_html($id); ?>)<br>
                    <?php endforeach; ?>
                    <em>These are test products and can be deleted later.</em>
                </p>
            </div>
            <?php
        }
    }

    // Method to get dummy product IDs
    public static function get_dummy_product_ids() {
        return get_option('sfmp_dummy_products', array(
            'Free Access Plan' => 0,
            'Standard Plan' => 0, 
            'Premium Plan' => 0
        ));
    }
}