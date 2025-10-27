<?php

class SFMP_Sample_Data {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'create_sample_cases'));
        add_action('wp_ajax_sfmp_create_sample_data', array($this, 'ajax_create_sample_data'));
    }

    public function create_sample_cases() {
        // Only create if requested and no cases exist
        if (!isset($_GET['sfmp_create_samples']) || !current_user_can('manage_options')) {
            return;
        }

        if ($this->sample_data_exists()) {
            return;
        }

        $this->create_sample_case_items();
        wp_redirect(admin_url('edit.php?post_type=case_item&sfmp_samples_created=1'));
        exit;
    }

    private function sample_data_exists() {
        $existing_cases = get_posts(array(
            'post_type' => 'case_item',
            'numberposts' => 1,
            'post_status' => 'publish'
        ));
        
        return !empty($existing_cases);
    }

    private function create_sample_case_items() {
        $sample_cases = array(
            array(
                'title' => 'Team Collaboration Challenge',
                'excerpt' => 'A scenario exploring effective team collaboration strategies in remote work environments.',
                'topic' => 'Team Collaboration',
                'experience_group' => 'Foundation',
                'is_free' => true,
                'content_tabs' => array(
                    'overview' => '<p>This case examines collaboration challenges in distributed teams and proposes solutions for improving communication and productivity.</p>',
                    'virtual_actor' => '<p>As a team leader, you notice communication breakdowns affecting project timelines. How would you address this?</p>',
                    'notes' => '<p>Key considerations: time zones, communication tools, cultural differences.</p>',
                    'rubric' => '<p>Evaluation based on: communication strategy, tool selection, outcome measurement.</p>'
                )
            ),
            array(
                'title' => 'Strategic Planning Workshop',
                'excerpt' => 'Developing a comprehensive strategic plan for organizational growth and market expansion.',
                'topic' => 'Strategic Planning', 
                'experience_group' => 'Intermediate',
                'is_free' => false,
                'content_tabs' => array(
                    'overview' => '<p>This case guides through the process of creating a 3-year strategic plan with measurable objectives.</p>',
                    'virtual_actor' => '<p>As a strategic planner, you need to align department goals with overall organizational vision.</p>',
                    'notes' => '<p>Focus on SWOT analysis, stakeholder engagement, and resource allocation.</p>',
                    'rubric' => '<p>Assessment criteria: strategic alignment, feasibility analysis, implementation timeline.</p>'
                )
            ),
            array(
                'title' => 'Conflict Resolution Scenario',
                'excerpt' => 'Managing interpersonal conflicts in high-pressure project environments.',
                'topic' => 'Conflict Resolution',
                'experience_group' => 'Advanced', 
                'is_free' => false,
                'content_tabs' => array(
                    'overview' => '<p>Explore techniques for resolving conflicts while maintaining team cohesion and project momentum.</p>',
                    'virtual_actor' => '<p>Two senior team members have conflicting approaches to problem-solving, causing project delays.</p>',
                    'notes' => '<p>Consider: personality types, communication styles, organizational culture.</p>',
                    'rubric' => '<p>Evaluate: conflict resolution approach, communication effectiveness, outcome sustainability.</p>'
                )
            )
        );

        foreach ($sample_cases as $case_data) {
            $post_id = wp_insert_post(array(
                'post_type' => 'case_item',
                'post_title' => $case_data['title'],
                'post_content' => $case_data['excerpt'],
                'post_excerpt' => $case_data['excerpt'],
                'post_status' => 'publish'
            ));

            if ($post_id && !is_wp_error($post_id)) {
                // Set taxonomy terms
                wp_set_post_terms($post_id, array($case_data['topic']), 'topic');
                wp_set_post_terms($post_id, array($case_data['experience_group']), 'experience_group');

                // Set meta fields
                update_post_meta($post_id, '_case_is_free', $case_data['is_free']);
                update_post_meta($post_id, '_case_overview', $case_data['content_tabs']['overview']);
                update_post_meta($post_id, '_case_virtual_actor', $case_data['content_tabs']['virtual_actor']);
                update_post_meta($post_id, '_case_notes', $case_data['content_tabs']['notes']);
                update_post_meta($post_id, '_case_rubric', $case_data['content_tabs']['rubric']);

                // Add featured image (placeholder)
                $this->set_placeholder_image($post_id);
            }
        }

        update_option('sfmp_sample_cases_created', true);
    }

    private function set_placeholder_image($post_id) {
        // You can add a placeholder image logic here
        // For now, we'll just log that we would set an image
        error_log("SFMP: Would set featured image for case ID: " . $post_id);
    }

    public function ajax_create_sample_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'sfmp_sample_data')) {
            wp_die('Unauthorized');
        }

        $this->create_sample_case_items();
        wp_send_json_success('Sample cases created successfully!');
    }
}