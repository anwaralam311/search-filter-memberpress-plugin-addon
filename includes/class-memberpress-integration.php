<?php

class SFMP_MemberPress_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // We'll add hooks as needed
    }

    /**
     * Check if user has access to a specific case
     */
    public function user_has_access_to_case($case_id) {
        // If case is free, everyone has access
        if ($this->is_case_free($case_id)) {
            return true;
        }

        // Get the required product ID for this case
        $required_product_id = $this->get_required_product_for_case($case_id);
        
        if (!$required_product_id) {
            // If no specific product required, check if user has any MemberPress membership
            return $this->user_has_any_membership();
        }

        // Check if user has the specific membership
        return $this->user_has_product_access($required_product_id);
    }

    /**
     * Check if a case is marked as free
     */
    public function is_case_free($case_id) {
        return get_post_meta($case_id, '_case_is_free', true) === '1';
    }

    /**
     * Determine which product is required for a case
     */
    public function get_required_product_for_case($case_id) {
        $settings = SFMP_Settings_Page::get_settings();
        $experience_group = wp_get_post_terms($case_id, 'experience_group', array('fields' => 'slugs'));
        
        if (empty($experience_group)) {
            return $settings['standard_product_id'];
        }

        $experience = $experience_group[0];

        // Map experience levels to products
        switch ($experience) {
            case 'foundation':
                return $settings['free_product_id'] ?: $settings['standard_product_id'];
            case 'intermediate':
                return $settings['standard_product_id'];
            case 'advanced':
                return $settings['premium_product_id'] ?: $settings['standard_product_id'];
            default:
                return $settings['standard_product_id'];
        }
    }

    /**
     * Check if user has access to a specific product
     */
    public function user_has_product_access($product_id) {
        if (!class_exists('MeprUser')) {
            return false;
        }

        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            return false;
        }

        $user = new MeprUser($current_user->ID);
        return $user->is_active_on_membership($product_id);
    }

    /**
     * Check if user has any active MemberPress membership
     */
    public function user_has_any_membership() {
        if (!class_exists('MeprUser')) {
            return false;
        }

        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            return false;
        }

        $user = new MeprUser($current_user->ID);
        $active_memberships = $user->active_product_subscriptions('ids');

        return !empty($active_memberships);
    }

    /**
     * Get access level label for a case
     */
    public function get_access_label($case_id) {
        if ($this->is_case_free($case_id)) {
            return array(
                'label' => __('Free', 'search-filter-memberpress'),
                'class' => 'sfmp-access-free',
                'icon' => '🔓'
            );
        }

        if ($this->user_has_access_to_case($case_id)) {
            return array(
                'label' => __('Unlocked', 'search-filter-memberpress'),
                'class' => 'sfmp-access-unlocked',
                'icon' => '✅'
            );
        }

        return array(
            'label' => __('Locked', 'search-filter-memberpress'),
            'class' => 'sfmp-access-locked',
            'icon' => '🔒'
        );
    }

    /**
     * Get upgrade URL for a case
     */
    public function get_upgrade_url($case_id) {
        $required_product_id = $this->get_required_product_for_case($case_id);
        
        if (!$required_product_id) {
            return home_url('/pricing/'); // Fallback URL
        }

        return get_permalink($required_product_id);
    }
}