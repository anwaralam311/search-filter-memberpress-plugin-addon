<?php
/**
 * Template Name: Case Archive
 */

get_header();
?>

<div class="sfmp-archive-wrapper">
    <div class="sfmp-container">
        <?php
        // Render the archive content
        if (function_exists('SFMP_Frontend_Archive::get_instance')) {
            SFMP_Frontend_Archive::get_instance()->render_archive_content();
        } else {
            echo '<p>' . __('Case archive functionality not available.', 'search-filter-memberpress') . '</p>';
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>