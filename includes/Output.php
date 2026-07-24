<?php

namespace RRZE\Knowledgebase;

class Output
{
    private $category;
    private $subcategories;

    public function __construct($object)
    {
        if ( is_post_type_archive()) {
            $this->subcategories = get_categories([
                'taxonomy'   => 'rrze-kb-category',
                'parent'     => 0,
                'hide_empty' => false,
                'include_children' => false,
            ]);
        } elseif (is_a($object, 'WP_Term')) {
            $this->category = $object;
            if ( ! isset($this->category->term_id)) {
                $this->subcategories = [];
            } else {
                $this->subcategories = get_categories([
                    'taxonomy'   => 'rrze-kb-category',
                    'parent'     => $this->category->term_id,
                    'hide_empty' => false,
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

        $context_menu = Helper::make_context_menu($this->category);

        $output = '<div class="' . $page_class . '">'
            . Helper::make_breadcrumbs($this->category)
            . '<div class="rrze-kb-category-inner">';

        $output .= '<div class="entry-content"><h1 class="entry-title">' . $title . '</h1>';

        if (is_post_type_archive()) {
            $output .= Helper::render_search();
        }

        if (!is_post_type_archive() && have_posts()) :

            $output .= '<h2>' . __('Articles', 'rrze-knowledgebase') . '</h2><div class="kb-category-post-list"><ul>';

            while (have_posts()) : the_post();

                $classes = get_post_class( '', get_the_ID() );
                $output .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">'
                           . '<a href="' . get_the_permalink() . '">'
                           . '<span class="dashicons dashicons-media-document"></span>'
                           . '<span class="link-text">' . get_the_title() . '</span>'
                           . '</a>'
                           . '</li>';

            endwhile;

            $output .= '</ul></div>';

        endif;

        if ( ! empty($this->subcategories)) :

            $output .= $this->render_subcategories($this->subcategories);

        endif;

        if (empty($this->subcategories) && !have_posts()) :
            $output .= '<p class="nothing-found">' . __('No articles found.', 'rrze-knowledgebase') . '</p>';
        endif;

        $output .= self::render_recent_articles(10, $this->category ? $this->category->term_id : null);

        $output .= '</div>';

        // Context Menu
        if (!empty($context_menu)) {
            $output .= '<nav class="rrze-kb-context-menu" aria-label="' . __('Side Menu', 'rrze-knowledgebase') . '">' . $context_menu . '</nav>';
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
        $context_menu = Helper::make_context_menu($post);
        $last_modified = get_the_modified_date(get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), $post);

        $output = '<div class="rrze-kb-article-page">'
        // Breadcrumbs
            . Helper::make_breadcrumbs($post)
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
                    $contrast_color = Helper::getContrastColor($term_color);
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
        $tags = get_the_terms($post->ID, 'rrze-kb-tag');
        if (!empty($tags)) {
            $names = wp_list_pluck($tags, 'name');
            $output .= '<div class="rrze-kb-tags">' . __('Tags', 'rrze-knowledgebase') . ': ' . implode(', ', $names) . '</div>';
        }
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

    private function render_subcategories($subcategories): string
    {

        if (is_post_type_archive('rrze-kb-article')) {
            $output = '';
        } else {
            $output = '<h2>' . __('Subcategories', 'rrze-knowledgebase') . '</h2>';
        }

        $output .= '<div class="kb-category-grid">';
        foreach ($subcategories as $subcategory) :

            $output .= '<article class="kb-category-card">'
                       . '<h1><a href="' . esc_url(get_category_link($subcategory->term_id)) . '">'
                       . esc_html($subcategory->name) . '</a></h1>';

            if ($subcategory->description) :
                $output .= '<p class="kb-category-description">' . esc_html($subcategory->description) . '</p>';
            endif;

            $subarticles = get_posts([
                                         'post_type'              => 'rrze-kb-article',
                                         'posts_per_page'         => -1,
                                         'no_found_rows'          => true,
                                         'update_post_meta_cache' => false,
                                         'update_post_term_cache' => false,
                                         'tax_query'              => [
                                             [
                                                 'taxonomy'         => 'rrze-kb-category',
                                                 'field'            => 'term_id',
                                                 'terms'            => $subcategory->term_id,
                                                 'include_children' => false,
                                             ],
                                         ],
                                     ]);

            if ( ! empty($subarticles)) :

                $output .= '<ul class="kb-articles">';

                foreach ($subarticles as $subarticle) :

                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_permalink($subarticle->ID)) . '" class="kb-articles">'
                               . '<span class="dashicons dashicons-media-document"></span>'
                               . '<span class="link-text">' . esc_html($subarticle->post_title) . '</span>'
                               . '</a></li>';
                endforeach;

                $output .= '</ul>';

            endif;

            $subsubcategories = get_categories([
                                                   'taxonomy'   => 'rrze-kb-category',
                                                   'parent'     => $subcategory->term_id,
                                                   'hide_empty' => false,
                                               ]);

            if ( ! empty($subsubcategories)) :

                $output .= '<ul class="kb-subsubcategories">';

                foreach ($subsubcategories as $subsubcategory) :
                    $count = (new \WP_Query([
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
                                           ]))->found_posts;

                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_category_link($subsubcategory->term_id)) . '" class="kb-subsubcategories">'
                               . '<span class="dashicons dashicons-category"></span>'
                               . '<span class="link-text">' . esc_html($subsubcategory->name)
                                    . '<span class="count"> (' . $count . ')</span>'
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
        ];
        if ( ! is_null($category)) {
            $args['tax_query'] = [[
               'taxonomy' => 'rrze-kb-category',
               'field' => 'term_id',
               'terms' => $category,
                'include_children' => true,
            ]];
        }
        $recent_articles = get_posts($args);
        $output = '';
        if ( ! empty($recent_articles)) :
            $output .= '<div class="kb-recent-articles">';
            $output .= '<h2>' . __('Recent Articles', 'rrze-knowledgebase') . '</h2>';
            foreach ($recent_articles as $article) :
                $output .= '<article class="kb-recent-article">'
                           . '<h3><a href="' . esc_url(get_permalink($article->ID)) . '">'
                           . esc_html($article->post_title) . '</a></h3>';
                if ($article->post_excerpt) :
                    $output .= '<p>' . esc_html($article->post_excerpt) . '</p>';
                endif;
                $output .= '</article>';
           endforeach;
           $output .= '</div>';
        endif;

        return $output;
    }

}