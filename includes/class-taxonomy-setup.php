<?php

class SFMP_Taxonomy_Setup {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Wait for taxonomies to be registered first, then seed terms
        add_action('init', array($this, 'seed_initial_terms'), 20);
    }

    public function seed_initial_terms() {
        // Only seed if MemberPress is active and taxonomies exist
        if (!class_exists('MeprJobs') && !class_exists('MeprAppCtrl')) {
            return;
        }

        if (!taxonomy_exists('topic') || !taxonomy_exists('experience_group')) {
            error_log('SFMP: Taxonomies not found for seeding terms');
            return;
        }

        $this->seed_topics();
        $this->seed_experience_groups();
        
        error_log('SFMP: Initial terms seeded');
    }

    private function seed_topics() {
        $topics = array(
            'Leadership Development',
            'Team Collaboration', 
            'Conflict Resolution',
            'Strategic Planning',
            'Change Management',
            'Performance Evaluation',
            'Client Relations',
            'Project Management',
            'Communication Skills',
            'Decision Making',
            'Innovation Process',
            'Quality Assurance'
        );

        foreach ($topics as $topic) {
            if (!term_exists($topic, 'topic')) {
                $result = wp_insert_term($topic, 'topic');
                if (is_wp_error($result)) {
                    error_log('SFMP: Failed to insert topic - ' . $topic . ' - ' . $result->get_error_message());
                }
            }
        }
    }

    private function seed_experience_groups() {
        $experience_levels = array(
            'Foundation',
            'Intermediate', 
            'Advanced'
        );

        foreach ($experience_levels as $level) {
            if (!term_exists($level, 'experience_group')) {
                $result = wp_insert_term($level, 'experience_group');
                if (is_wp_error($result)) {
                    error_log('SFMP: Failed to insert experience group - ' . $level . ' - ' . $result->get_error_message());
                }
            }
        }
    }
}