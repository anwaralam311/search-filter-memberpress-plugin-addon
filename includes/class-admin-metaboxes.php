<?php

class SFMP_Admin_Metaboxes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_case_meta_boxes'));
        add_action('save_post', array($this, 'save_case_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_case_meta_boxes() {
        add_meta_box(
            'sfmp_case_access',
            __('Case Access Settings', 'search-filter-memberpress'),
            array($this, 'render_access_meta_box'),
            'case_item',
            'side',
            'high'
        );

        add_meta_box(
            'sfmp_case_content_tabs',
            __('Case Content Tabs', 'search-filter-memberpress'),
            array($this, 'render_content_tabs_meta_box'),
            'case_item',
            'normal',
            'high'
        );

        add_meta_box(
            'sfmp_case_audio',
            __('Case Audio Attachment', 'search-filter-memberpress'),
            array($this, 'render_audio_meta_box'),
            'case_item',
            'side',
            'default'
        );
    }

    public function render_access_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('sfmp_save_case_meta', 'sfmp_case_meta_nonce');

        // Get current values
        $is_free = get_post_meta($post->ID, '_case_is_free', true);
        $experience_group = wp_get_post_terms($post->ID, 'experience_group', array('fields' => 'ids'));
        $current_experience = !empty($experience_group) ? $experience_group[0] : '';

        ?>
        <div class="sfmp-meta-field">
            <label for="sfmp_case_is_free">
                <input type="checkbox" id="sfmp_case_is_free" name="sfmp_case_is_free" value="1" <?php checked($is_free, '1'); ?>>
                <?php _e('This case is free for all users', 'search-filter-memberpress'); ?>
            </label>
            <p class="description">
                <?php _e('If checked, this case will be accessible without MemberPress membership.', 'search-filter-memberpress'); ?>
            </p>
        </div>

        <div class="sfmp-meta-field">
            <label for="sfmp_experience_group">
                <strong><?php _e('Experience Group:', 'search-filter-memberpress'); ?></strong>
            </label>
            <?php
            $experience_terms = get_terms(array(
                'taxonomy' => 'experience_group',
                'hide_empty' => false,
            ));

            if (!empty($experience_terms) && !is_wp_error($experience_terms)) {
                echo '<select name="sfmp_experience_group" id="sfmp_experience_group" class="widefat">';
                echo '<option value="">' . __('Select Experience Group', 'search-filter-memberpress') . '</option>';
                foreach ($experience_terms as $term) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($term->term_id),
                        selected($current_experience, $term->term_id, false),
                        esc_html($term->name)
                    );
                }
                echo '</select>';
            }
            ?>
        </div>

        <div class="sfmp-meta-field">
            <label for="sfmp_case_topics">
                <strong><?php _e('Topics:', 'search-filter-memberpress'); ?></strong>
            </label>
            <?php
            $topic_terms = get_terms(array(
                'taxonomy' => 'topic',
                'hide_empty' => false,
            ));

            if (!empty($topic_terms) && !is_wp_error($topic_terms)) {
                echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">';
                foreach ($topic_terms as $term) {
                    $checked = has_term($term->term_id, 'topic', $post->ID) ? 'checked' : '';
                    printf(
                        '<label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="sfmp_case_topics[]" value="%s" %s> %s
                        </label>',
                        esc_attr($term->term_id),
                        $checked,
                        esc_html($term->name)
                    );
                }
                echo '</div>';
            }
            ?>
        </div>
        <?php
    }

    public function render_content_tabs_meta_box($post) {
        $overview = get_post_meta($post->ID, '_case_overview', true);
        $virtual_actor = get_post_meta($post->ID, '_case_virtual_actor', true);
        $notes = get_post_meta($post->ID, '_case_notes', true);
        $rubric = get_post_meta($post->ID, '_case_rubric', true);

        // Use WordPress editor for Overview (required field)
        echo '<div class="sfmp-tab-content">';
        
        echo '<h3>' . __('Overview (Required)', 'search-filter-memberpress') . '</h3>';
        wp_editor(
            $overview,
            'sfmp_case_overview',
            array(
                'textarea_name' => 'sfmp_case_overview',
                'editor_height' => 200,
                'media_buttons' => true,
                'teeny' => false
            )
        );

        echo '<h3>' . __('Virtual Actor', 'search-filter-memberpress') . '</h3>';
        wp_editor(
            $virtual_actor,
            'sfmp_case_virtual_actor',
            array(
                'textarea_name' => 'sfmp_case_virtual_actor',
                'editor_height' => 150,
                'media_buttons' => true,
                'teeny' => false
            )
        );

        echo '<h3>' . __('Notes', 'search-filter-memberpress') . '</h3>';
        wp_editor(
            $notes,
            'sfmp_case_notes',
            array(
                'textarea_name' => 'sfmp_case_notes',
                'editor_height' => 150,
                'media_buttons' => true,
                'teeny' => false
            )
        );

        echo '<h3>' . __('Rubric', 'search-filter-memberpress') . '</h3>';
        wp_editor(
            $rubric,
            'sfmp_case_rubric',
            array(
                'textarea_name' => 'sfmp_case_rubric',
                'editor_height' => 150,
                'media_buttons' => true,
                'teeny' => false
            )
        );

        echo '</div>';
    }

    public function render_audio_meta_box($post) {
        $audio_id = get_post_meta($post->ID, '_case_audio_id', true);
        $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';

        ?>
        <div class="sfmp-meta-field">
            <label for="sfmp_case_audio">
                <strong><?php _e('Audio File:', 'search-filter-memberpress'); ?></strong>
            </label>
            
            <div class="sfmp-audio-preview" style="margin: 10px 0;">
                <?php if ($audio_url): ?>
                    <audio controls style="width: 100%;">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                        <?php _e('Your browser does not support the audio element.', 'search-filter-memberpress'); ?>
                    </audio>
                    <p class="description">
                        <?php echo basename($audio_url); ?>
                    </p>
                <?php else: ?>
                    <p><?php _e('No audio file selected.', 'search-filter-memberpress'); ?></p>
                <?php endif; ?>
            </div>

            <input type="hidden" id="sfmp_case_audio_id" name="sfmp_case_audio_id" value="<?php echo esc_attr($audio_id); ?>">
            
            <button type="button" class="button sfmp-upload-audio" style="margin-right: 5px;">
                <?php _e('Select Audio', 'search-filter-memberpress'); ?>
            </button>
            
            <?php if ($audio_id): ?>
                <button type="button" class="button sfmp-remove-audio">
                    <?php _e('Remove Audio', 'search-filter-memberpress'); ?>
                </button>
            <?php endif; ?>
            
            <p class="description">
                <?php _e('Upload an audio file for the Rubric tab. Supported formats: MP3, WAV, OGG.', 'search-filter-memberpress'); ?>
            </p>
        </div>
        <?php
    }

    public function save_case_meta($post_id, $post) {
        // Check if nonce is set and valid
        if (!isset($_POST['sfmp_case_meta_nonce']) || !wp_verify_nonce($_POST['sfmp_case_meta_nonce'], 'sfmp_save_case_meta')) {
            return;
        }

        // Check if user has permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if ($post->post_type !== 'case_item') {
            return;
        }

        // Save access settings
        $is_free = isset($_POST['sfmp_case_is_free']) ? '1' : '0';
        update_post_meta($post_id, '_case_is_free', $is_free);

        // Save experience group
        if (isset($_POST['sfmp_experience_group'])) {
            $experience_group = intval($_POST['sfmp_experience_group']);
            if ($experience_group > 0) {
                wp_set_post_terms($post_id, array($experience_group), 'experience_group');
            }
        }

        // Save topics
        if (isset($_POST['sfmp_case_topics'])) {
            $topics = array_map('intval', $_POST['sfmp_case_topics']);
            wp_set_post_terms($post_id, $topics, 'topic');
        } else {
            // If no topics selected, remove all
            wp_set_post_terms($post_id, array(), 'topic');
        }

        // Save content tabs
        $content_fields = array(
            'sfmp_case_overview' => '_case_overview',
            'sfmp_case_virtual_actor' => '_case_virtual_actor',
            'sfmp_case_notes' => '_case_notes',
            'sfmp_case_rubric' => '_case_rubric'
        );

        foreach ($content_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $content = wp_kses_post($_POST[$field]);
                update_post_meta($post_id, $meta_key, $content);
            }
        }

        // Save audio ID
        if (isset($_POST['sfmp_case_audio_id'])) {
            $audio_id = intval($_POST['sfmp_case_audio_id']);
            update_post_meta($post_id, '_case_audio_id', $audio_id);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php')) || get_post_type() !== 'case_item') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('sfmp-admin', SFMP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SFMP_VERSION, true);
        wp_enqueue_style('sfmp-admin', SFMP_PLUGIN_URL . 'assets/css/admin.css', array(), SFMP_VERSION);

        // Localize script for AJAX and translations
        wp_localize_script('sfmp-admin', 'sfmp_admin', array(
            'title' => __('Select Audio File', 'search-filter-memberpress'),
            'button_text' => __('Use this audio', 'search-filter-memberpress'),
        ));
    }
}