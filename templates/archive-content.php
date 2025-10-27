<?php
/**
 * Archive content template for cases
 */

global $wp_query;
$settings = SFMP_Settings_Page::get_settings();
$memberpress_integration = SFMP_MemberPress_Integration::get_instance();
?>

<div class="sfmp-archive-header">
    <h1 class="sfmp-archive-title"><?php echo esc_html($settings['archive_title']); ?></h1>
    
    <?php if (!empty($filters['search']) || !empty($filters['topics']) || !empty($filters['experience_group'])): ?>
        <div class="sfmp-active-filters">
            <strong><?php _e('Active Filters:', 'search-filter-memberpress'); ?></strong>
            <?php if (!empty($filters['search'])): ?>
                <span class="sfmp-filter-chip">
                    <?php printf(__('Search: "%s"', 'search-filter-memberpress'), esc_html($filters['search'])); ?>
                    <a href="<?php echo remove_query_arg('search'); ?>" class="sfmp-remove-filter">×</a>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($filters['topics'])): 
                foreach ($filters['topics'] as $topic_id):
                    $topic = get_term($topic_id); 
                    if ($topic && !is_wp_error($topic)): ?>
                        <span class="sfmp-filter-chip">
                            <?php echo esc_html($topic->name); ?>
                            <a href="<?php echo remove_query_arg('topics'); ?>" class="sfmp-remove-filter">×</a>
                        </span>
                    <?php endif;
                endforeach;
            endif; ?>
            
            <?php if (!empty($filters['experience_group'])): 
                $experience = get_term($filters['experience_group']); 
                if ($experience && !is_wp_error($experience)): ?>
                    <span class="sfmp-filter-chip">
                        <?php echo esc_html($experience->name); ?>
                        <a href="<?php echo remove_query_arg('experience_group'); ?>" class="sfmp-remove-filter">×</a>
                    </span>
                <?php endif;
            endif; ?>
            
            <a href="<?php echo get_post_type_archive_link('case_item'); ?>" class="sfmp-clear-filters">
                <?php _e('Clear All', 'search-filter-memberpress'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="sfmp-archive-content">
    <aside class="sfmp-filters-sidebar">
        <form method="get" action="<?php echo get_post_type_archive_link('case_item'); ?>" class="sfmp-filters-form">
            
            <!-- Search -->
            <div class="sfmp-filter-group">
                <label for="sfmp-search" class="sfmp-filter-label">
                    <?php _e('Search Cases', 'search-filter-memberpress'); ?>
                </label>
                <input 
                    type="text" 
                    id="sfmp-search" 
                    name="search" 
                    value="<?php echo esc_attr($filters['search']); ?>" 
                    placeholder="<?php _e('Search by title or content...', 'search-filter-memberpress'); ?>"
                    class="sfmp-search-input"
                >
            </div>

            <!-- Topics -->
            <div class="sfmp-filter-group">
                <label class="sfmp-filter-label">
                    <?php _e('Filter by Topic', 'search-filter-memberpress'); ?>
                </label>
                <div class="sfmp-topics-filter">
                    <?php
                    $topics = get_terms(array(
                        'taxonomy' => 'topic',
                        'hide_empty' => true,
                    ));

                    if (!empty($topics) && !is_wp_error($topics)):
                        foreach ($topics as $topic):
                            $checked = in_array($topic->term_id, $filters['topics']) ? 'checked' : '';
                            ?>
                            <label class="sfmp-topic-checkbox">
                                <input 
                                    type="checkbox" 
                                    name="topics[]" 
                                    value="<?php echo esc_attr($topic->term_id); ?>" 
                                    <?php echo $checked; ?>
                                >
                                <span><?php echo esc_html($topic->name); ?></span>
                            </label>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Experience Group -->
            <div class="sfmp-filter-group">
                <label for="sfmp-experience-group" class="sfmp-filter-label">
                    <?php _e('Experience Level', 'search-filter-memberpress'); ?>
                </label>
                <select id="sfmp-experience-group" name="experience_group" class="sfmp-select">
                    <option value=""><?php _e('All Levels', 'search-filter-memberpress'); ?></option>
                    <?php
                    $experience_groups = get_terms(array(
                        'taxonomy' => 'experience_group',
                        'hide_empty' => true,
                    ));

                    if (!empty($experience_groups) && !is_wp_error($experience_groups)):
                        foreach ($experience_groups as $group):
                            $selected = $filters['experience_group'] == $group->term_id ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($group->term_id); ?>" <?php echo $selected; ?>>
                                <?php echo esc_html($group->name); ?>
                            </option>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </select>
            </div>

            <!-- Sort -->
            <div class="sfmp-filter-group">
                <label for="sfmp-sort" class="sfmp-filter-label">
                    <?php _e('Sort By', 'search-filter-memberpress'); ?>
                </label>
                <select id="sfmp-sort" name="sort" class="sfmp-select">
                    <option value="newest" <?php selected($filters['sort'], 'newest'); ?>>
                        <?php _e('Newest First', 'search-filter-memberpress'); ?>
                    </option>
                    <option value="oldest" <?php selected($filters['sort'], 'oldest'); ?>>
                        <?php _e('Oldest First', 'search-filter-memberpress'); ?>
                    </option>
                    <option value="title_asc" <?php selected($filters['sort'], 'title_asc'); ?>>
                        <?php _e('Title A-Z', 'search-filter-memberpress'); ?>
                    </option>
                </select>
            </div>

            <!-- Submit Buttons -->
            <div class="sfmp-filter-actions">
                <button type="submit" class="sfmp-apply-filters">
                    <?php _e('Apply Filters', 'search-filter-memberpress'); ?>
                </button>
                <a href="<?php echo get_post_type_archive_link('case_item'); ?>" class="sfmp-reset-filters">
                    <?php _e('Reset', 'search-filter-memberpress'); ?>
                </a>
            </div>
        </form>
    </aside>

    <main class="sfmp-results-main">
        <?php if ($cases_query->have_posts()): ?>
            <div class="sfmp-results-count">
                <?php
                printf(
                    _n(
                        'Showing %d case',
                        'Showing %d cases',
                        $cases_query->found_posts,
                        'search-filter-memberpress'
                    ),
                    $cases_query->found_posts
                );
                ?>
            </div>

            <div class="sfmp-cases-grid">
                <?php while ($cases_query->have_posts()): $cases_query->the_post(); 
                    $case_id = get_the_ID();
                    $access_info = $memberpress_integration->get_access_label($case_id);
                    $topics = wp_get_post_terms($case_id, 'topic');
                    $experience = wp_get_post_terms($case_id, 'experience_group');
                    ?>
                    
                    <article class="sfmp-case-card <?php echo esc_attr($access_info['class']); ?>">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="sfmp-case-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="sfmp-case-content">
                            <h3 class="sfmp-case-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <div class="sfmp-case-meta">
                                <?php if (!empty($experience)): ?>
                                    <span class="sfmp-experience-level">
                                        <?php echo esc_html($experience[0]->name); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="sfmp-access-status <?php echo esc_attr($access_info['class']); ?>">
                                    <?php echo esc_html($access_info['icon'] . ' ' . $access_info['label']); ?>
                                </span>
                            </div>

                            <?php if (!empty($topics)): ?>
                                <div class="sfmp-case-topics">
                                    <?php foreach ($topics as $topic): ?>
                                        <span class="sfmp-topic-badge"><?php echo esc_html($topic->name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="sfmp-case-excerpt">
                                <?php the_excerpt(); ?>
                            </div>

                            <div class="sfmp-case-actions">
                                <?php if ($memberpress_integration->user_has_access_to_case($case_id)): ?>
                                    <a href="<?php the_permalink(); ?>" class="sfmp-view-case">
                                        <?php _e('View Case', 'search-filter-memberpress'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($memberpress_integration->get_upgrade_url($case_id)); ?>" class="sfmp-upgrade-access">
                                        <?php _e('Upgrade to Access', 'search-filter-memberpress'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($cases_query->max_num_pages > 1): ?>
                <div class="sfmp-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => get_pagenum_link(1) . '%_%',
                        'format' => 'page/%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $cases_query->max_num_pages,
                        'prev_text' => __('« Previous', 'search-filter-memberpress'),
                        'next_text' => __('Next »', 'search-filter-memberpress'),
                    ));
                    ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="sfmp-no-results">
                <h3><?php _e('No cases found', 'search-filter-memberpress'); ?></h3>
                <p><?php _e('Try adjusting your search filters or browse all cases.', 'search-filter-memberpress'); ?></p>
                <a href="<?php echo get_post_type_archive_link('case_item'); ?>" class="sfmp-browse-all">
                    <?php _e('Browse All Cases', 'search-filter-memberpress'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
    </main>
</div>