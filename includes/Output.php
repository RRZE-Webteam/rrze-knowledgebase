<?php

namespace RRZE\Knowledgebase;

class Output
{
    private $category;
    private $subcategories;

    private $has_target_groups = false;

    private string $target_group = '';

    private string $target_group_get_param = '';

    private string $target_group_color = '#fff';

    public function __construct($object)
    {
        $target_group_ids = get_terms([
            'taxonomy'   => 'rrze-kb-target-group',
            'hide_empty' => false,
            'fields'     => 'ids',
            'number'     => 1,
        ]);

        if (!is_wp_error($target_group_ids) && !empty($target_group_ids)) {
            $this->has_target_groups = true;
        }
        if (!empty($_GET['target-group'])) {
            $this->target_group = sanitize_title($_GET['target-group']);
            $this->target_group_get_param = '?target-group=' . $this->target_group;
        }
        if ($this->has_target_groups) {
            $target_group_term = get_term_by('slug', $this->target_group, 'rrze-kb-target-group');
            if ($target_group_term && ! is_wp_error($target_group_term)) {
                $term_color = get_term_meta($target_group_term->term_id, 'term_color', true);
                if ( ! empty($term_color)) {
                    $this->target_group_color = sanitize_hex_color($term_color);
                } else {
                    $this->target_group_color = '#04316a';
                }
            }
        }

        if ( is_post_type_archive()) {
            $this->subcategories = Helper::get_category_terms_by_target_group($this->target_group);
        } elseif (is_a($object, 'WP_Term')) {
            $this->category = $object;
            if ( ! isset($this->category->term_id)) {
                $this->subcategories = [];
            } else {
                $this->subcategories = get_categories([
                    'taxonomy'   => 'rrze-kb-category',
                    'parent'     => $this->category->term_id,
                    'hide_empty' => true,
                    'include_children' => false,
                ]);
            }
        } elseif (is_a($object, 'WP_Post')) {
            $terms = get_the_terms( $object->ID, 'rrze-kb-category' );
            $this->category = $terms[0] ?? null;
        }
    }

    public function render_archive()
    {
        if (is_post_type_archive()) {
            $options = (new Settings)->get_options();
            $title = $options['name'] ?? '';
            $page_class = 'rrze-kb-start-page';
        } else {
            $title = single_cat_title('', false);
            $page_class = 'rrze-kb-category-page';
        }

        $context_menu = Helper::make_context_menu($this->category, $this->target_group);

        $output = '<div class="' . $page_class . '">'
            . Helper::make_breadcrumbs($this->category, $this->target_group_get_param)
            . '<div class="rrze-kb-category-inner">';

        $output .= '<div class="entry-content"><h1 class="entry-title">' . $title . '</h1>';

        if (is_post_type_archive()) {
            $output .= $this->has_target_groups ? Helper::render_target_group_dropdown($this->target_group, $this->target_group_color) : '';
            $output .= Helper::render_search();
        }

        if ((is_tax('rrze-kb-category')|| is_tax('rrze-kb-target-group')) && !empty($this->category->description)) {
            $output .= '<div class="kb-category-description">' . esc_html($this->category->description) . '</div>';
        }

        if ((is_tax('rrze-kb-category')|| is_tax('rrze-kb-target-group')) && have_posts()) {
            $output .= '<h2>' . __('Articles', 'rrze-knowledgebase') . '</h2><div class="kb-article-list"><ul class="rrze-kb-article-list">';

            while (have_posts()) : the_post();

                $classes = get_post_class('', get_the_ID());
                if (!empty($_GET['target-group'])) {
                    $target_group = sanitize_title($_GET['target-group']);
                    $article_target_groups = get_the_terms(get_the_ID(), 'rrze-kb-target-group');
                    $article_target_groups_slugs = wp_list_pluck($article_target_groups, 'slug');
                    if ($article_target_groups && !is_wp_error($article_target_groups) && !in_array($target_group, $article_target_groups_slugs)) {
                        continue;
                    }
                }
                $output  .= '<li class="' . esc_attr(implode(' ', $classes)) . '">'
                            . '<a href="' . get_the_permalink() . $this->target_group_get_param . '">'
                            . '<span class="dashicons dashicons-media-document"></span>'
                            . '<span class="link-text">' . get_the_title() . '</span>'
                            . '</a>'
                            . '</li>';

            endwhile;

            $output .= '</ul></div>';
        }

        if ( ! empty($this->subcategories)) {
            $output .= $this->render_subcategories($this->subcategories, $this->target_group);
        }

        if (empty($this->subcategories) && !have_posts()) {
            $output .= '<p class="nothing-found">' . __('No articles found.', 'rrze-knowledgebase') . '</p>';
        }

        $output .= self::render_recent_articles(10, $this->category ? $this->category->term_id : null);

        $output .= '</div>';

        // Context Menu
        if (!empty($context_menu)) {
            $output .= '<div class="rrze-kb-sidebar">';

            if ($this->has_target_groups) {
                $output .= '<div class="rrze-kb-target-group">' . Helper::render_target_group_dropdown($this->target_group, $this->target_group_color) . '</div>';
            }

            $output .=  '<nav class="rrze-kb-context-menu" aria-label="' . __('Side Menu', 'rrze-knowledgebase') . '">' . $context_menu . '</nav>'
                . '</div>';
        }

        $output .= '</div></div>';

        wp_enqueue_style('dashicons');
        wp_enqueue_script('rrze-knowledgebase-script');
        return $output;
    }

    public function render_single(): string
    {
        global $post;
        $modifiedHtml = Helper::make_toc(get_the_content());
        $toc = $modifiedHtml['toc'] ?? '';
        $content = $modifiedHtml['html'] ?? '';
        $context_menu = Helper::make_context_menu($post, $this->target_group);
        $last_modified = get_the_modified_date(get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), $post);

        $output = '<div class="rrze-kb-article-page">'
        // Breadcrumbs
            . Helper::make_breadcrumbs($post, $this->target_group_get_param)
            . '<div class="rrze-kb-article-inner">';
        // ToC
        if (!empty($toc)) {
            $output .= '<div class="rrze-kb-toc-container"><h2 id="rrze-kb-toc-title">' . __('Table of contents', 'rrze-knowledgebase') . '</h2>' . '<nav class="rrze-kb-toc" aria-labelledby="rrze-kb-toc-title">' . $toc . '</nav></div>';
        }
        // Article
        $output .= '<article class="entry-content">'
                      . '<header class="entry-header"><h1 class="entry-title">' . get_the_title() . '</h1>';

        $target_groups = get_the_terms($post->ID, 'rrze-kb-target-group');
        if (!empty($target_groups) && !is_wp_error($target_groups)) {
            $output .= '<div class="rrze-kb-target-groups">' . __('Target Groups', 'rrze-knowledgebase') . ': ' . '<ul class="kb-target-groups">';
            foreach ($target_groups as $target_group) {
                $link = get_term_link($target_group);
                $term_color = get_term_meta($target_group->term_id, 'term_color', true);
                if (empty($term_color)) {
                    $term_color = '#04316a';
                }
                $contrast_color = get_term_meta($target_group->term_id, 'term_contrast_color', true);
                if (empty($contrast_color)) {
                    $contrast_color = Helper::get_contrast_color($term_color);
                }
                if (!is_wp_error($link)) {
                    $output .= '<li><a href="' . esc_url($link) . '" style="background-color: ' . sanitize_hex_color($term_color) . '; color: ' . sanitize_hex_color($contrast_color) . ';">'
                               . esc_html($target_group->name)
                               . '</a></li>';
                } else {
                    $output .= '<li>' . esc_html($target_group->name) . '</li>';
                }
            }
            $output .= '</ul></div>';
        }
        $output .= '</header>'
                      . $content;

        $dependencies = get_post_meta($post->ID, 'rrze_kb_dependencies', true);
        if (!empty($dependencies)) {
            $output .= '<div class="rrze-kb-dependencies">'
                . '<h2>' . __('Dependencies', 'rrze-knowledgebase') . '</h2>'
                . '<ul class="wp-block-list">';
            foreach ($dependencies as $dependency) {
                $output .= '<li><a href="' . esc_url($dependency['url']) . '">' . esc_html($dependency['text']) . '</a></li>';
            }

            $output .= '</ul></div>';
        }

        $output .= '<footer class="entry-footer">';
        /*$tags = get_the_terms($post->ID, 'rrze-kb-tag');
        if (!empty($tags)) {
            $names = wp_list_pluck($tags, 'name');
            $output .= '<div class="rrze-kb-tags">' . __('Tags', 'rrze-knowledgebase') . ': ' . implode(', ', $names) . '</div>';
        }*/
        /* translators: %s: Date + time */
        $output .= '<div class="last-modified">' . sprintf(__('Last modified: %s', 'rrze-knowledgebase'), $last_modified) . '</div>';
        $output .= '</footer>';

        $output .= '</article>';

        // Context Menu
        if (!empty($context_menu)) {
            $output .= '<nav class="rrze-kb-context-menu" aria-label="' . __('Side Menu', 'rrze-knowledgebase') . '">' . $context_menu . '</nav>';
        }
        $output .='</div></div>';

        wp_enqueue_style('dashicons');
        wp_enqueue_script('rrze-knowledgebase-script');
        return wp_kses_post($output);
    }

    private function render_subcategories($subcategories, $target_group = ''): string
    {

        if (is_post_type_archive('rrze-kb-article')) {
            $output = '';
        } else {
            $output = '<h2>' . __('Subcategories', 'rrze-knowledgebase') . '</h2>';
        }

        $output .= '<div class="kb-category-grid">';
        foreach ($subcategories as $subcategory) :

            $output .= '<article class="kb-category-card">'
                       . '<h1><a href="' . esc_url(get_category_link($subcategory->term_id)) . $this->target_group_get_param .  '">'
                       . esc_html($subcategory->name) . '</a></h1>';

            if ($subcategory->description) :
                $output .= '<p class="kb-category-description">' . esc_html($subcategory->description) . '</p>';
            endif;

            $args = [
                'post_type'              => 'rrze-kb-article',
                'posts_per_page'         => -1,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'tax_query'              => [
                    'relation' => 'AND',
                    [
                        'taxonomy'         => 'rrze-kb-category',
                        'field'            => 'term_id',
                        'terms'            => $subcategory->term_id,
                        'include_children' => false,
                    ],
                ],
            ];
            if (!empty($target_group)) {
                $args['tax_query'][] = [
                    'relation' => 'OR',
                    [
                        'taxonomy' => 'rrze-kb-target-group',
                        'field'    => 'slug',
                        'terms'    => $target_group,
                    ],
                    [
                        'taxonomy' => 'rrze-kb-target-group',
                        'operator' => 'NOT EXISTS',
                    ],
                ];
            }
            $subarticles = get_posts($args);

            if ( ! empty($subarticles)) :

                $output .= '<ul class="kb-articles">';

                foreach ($subarticles as $subarticle) :

                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_permalink($subarticle->ID)) . $this->target_group_get_param . '" class="kb-articles">'
                               . '<span class="dashicons dashicons-media-document"></span>'
                               . '<span class="link-text">' . esc_html($subarticle->post_title) . '</span>'
                               . '</a></li>';
                endforeach;

                $output .= '</ul>';

            endif;

            $subsubcategories = get_categories([
                                                   'taxonomy'   => 'rrze-kb-category',
                                                   'parent'     => $subcategory->term_id,
                                                   'hide_empty' => true,
                                               ]);

            if ( ! empty($subsubcategories)) :

                $output .= '<ul class="kb-subsubcategories">';

                foreach ($subsubcategories as $subsubcategory) :
                    /*$count = (new \WP_Query([
                                               'post_type'      => 'rrze-kb-article',
                                               'post_status'    => 'publish',
                                               'posts_per_page' => 1, // only 1 article is loaded, but all are counted
                                               'fields'         => 'ids',
                                               'tax_query'      => [
                                                   [
                                                       'taxonomy' => 'rrze-kb-category',
                                                       'field'    => 'term_id',
                                                       'terms'    => $subsubcategory->term_id,
                                                   ],
                                               ],
                                           ]))->found_posts;*/

                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_category_link($subsubcategory->term_id)) . $this->target_group_get_param . '" class="kb-subsubcategories">'
                               . '<span class="dashicons dashicons-category"></span>'
                               . '<span class="link-text">' . esc_html($subsubcategory->name)
                               //     . '<span class="count"> (' . $count . ')</span>'
                               . '</span>'
                               . '</a></li>';
                endforeach;

                $output .= '</ul>';

            endif;

            $output .= '</article>';

        endforeach;

        $output .= '</div>';

        return $output;
    }

    public function render_recent_articles($number = 3, $category = null, ) {

        $args = [
            'post_type' => 'rrze-kb-article',
            'posts_per_page' => (int) $number,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => ['relation' => 'AND']
        ];
        if ( ! is_null($category)) {
            $args['tax_query'][] = [
               'taxonomy' => 'rrze-kb-category',
               'field' => 'term_id',
               'terms' => $category,
                'include_children' => true,
            ];
        }
        if (!empty($_GET['target-group'])) {
            $args['tax_query'][] = [
                'relation' => 'OR',
                [
                    'taxonomy' => 'rrze-kb-target-group',
                    'field'    => 'slug',
                    'terms'    => sanitize_title($_GET['target-group']),
                    'include_children' => true,
                ],
                [
                    'taxonomy' => 'rrze-kb-target-group',
                    'operator' => 'NOT EXISTS',
                ],
            ];
        }
        $recent_articles = get_posts($args);
        $output = '';
        if ( ! empty($recent_articles)) :
            $output .= '<div class="rrze-kb-recent-articles">';
            $output .= '<h2>' . __('Recent Articles', 'rrze-knowledgebase') . '</h2><ul class="rrze-kb-article-list">';
            foreach ($recent_articles as $article) :
                $output .= '<li class="kb-recent-article">'
                           . '<a href="' . esc_url(get_permalink($article->ID)) . $this->target_group_get_param . '">'
                           . '<span class="dashicons dashicons-media-document"></span>'
                           . '<span class="link-text">' . esc_html($article->post_title) . '</span></a>';
                if ($article->post_excerpt) :
                    $output .= '<p>' . esc_html($article->post_excerpt) . '</p>';
                endif;
                $output .= '</li>';
           endforeach;
           $output .= '</ul></div>';
        endif;

        return $output;
    }

}