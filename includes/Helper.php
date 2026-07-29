<?php

namespace RRZE\Knowledgebase;

class Helper
{
    /**
     * Gibt alle Parent-Terms eines Terms zurück.
     *
     * @param int    $term_id  Die ID des Terms.
     * @param string $taxonomy Die Taxonomie.
     * @return WP_Term[]
     */
    public static function get_term_parents_recursive($term_id, $taxonomy) {
        $parents = [];

        $term = get_term($term_id, $taxonomy);

        if (is_wp_error($term) || !$term) {
            return $parents;
        }

        while ($term->parent) {
            $term = get_term($term->parent, $taxonomy);

            if (is_wp_error($term) || !$term) {
                break;
            }

            $parents[] = $term;
        }

        return $parents;
    }

    public static function make_breadcrumbs($object) {

        if (is_a($object, 'WP_Post')) {
            $title = $object->post_title ?? '';
            $categories = get_the_terms($object->ID, 'rrze-kb-category');
            $category = $categories[0] ?? null;
        } elseif (is_a($object, 'WP_Term')) {
            $title = $object->name ?? '';
            $category = $object;
        } else {
            return '';
        }

        if (isset($category->parent)) {
            $parents = Helper::get_term_parents_recursive($category->term_id, 'rrze-kb-category');
            $parents = array_reverse($parents);
        } else {
            $parents = false;
        }

        $options = get_option('rrze-kb');
        $kb_name = $options['name'] ?? __('Knowledge Base', 'rrze-knowledgebase');

        /* translators: %s: Knowledge Base name */
        $output = '<nav class="kb-category-navigation" aria-label="' . sprintf(esc_attr__('%s Breadcrumb Navigation', 'rrze-knowledgebase'), $kb_name) . '">'
                  . '<ul class="kb-category-breadcrumbs">'
                  . '<li><a href="' . esc_url(get_post_type_archive_link('rrze-kb-article')) . '" class="kb-category-back-link">' . $kb_name . '</a></li>';
        if ($parents) {
            foreach ($parents as $parent) {
                $output .= '<li><a href="' . esc_url(get_category_link($parent->term_id)) . '" class="kb-category-back-link">' . esc_html($parent->name) . '</a></li>';
            }
        }
        if (is_a($object, 'WP_Post') && $category != null) {
            $output .= '<li><a href="' . esc_url(get_category_link($category->term_id)) . '" class="kb-category-back-link">' . esc_html($category->name) . '</a></li>';
        }
        $output .= '<li><span class="kb-category-current-item">' . $title . '</span></li>'
                   . '</ul>'
                   . '</nav>';

        return $output;
    }

    public static function make_toc(string $html) {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        if (empty($headings->length)) {
            return [
                'toc'  => '',
                'html' => $html,
            ];
        }

        $used_ids = [];

        $toc = '';
        $current_level = 0;
        $first = true;

        foreach ($headings as $heading) {

            $level = (int) substr($heading->nodeName, 1);
            $text  = trim($heading->textContent);

            // Vorhandene ID übernehmen
            if ($heading->hasAttribute('id')) {
                $id = sanitize_title($heading->getAttribute('id'));
            } else {
                $id = sanitize_title($text);
            }

            if ($id === '') {
                $id = 'section';
            }

            // ID eindeutig machen
            $base = $id;
            $i = 2;

            while (isset($used_ids[$id])) {
                $id = "{$base}-{$i}";
                $i++;
            }

            $used_ids[$id] = true;

            $heading->setAttribute('id', $id);

            if ($first) {
                $toc .= '<ul>';
                $current_level = $level;
                $first = false;
            }

            if ($level > $current_level) {

                while ($level > $current_level) {
                    $toc .= '<ul>';
                    $current_level++;
                }

            } elseif ($level < $current_level) {

                while ($level < $current_level) {
                    $toc .= '</li></ul>';
                    $current_level--;
                }

                $toc .= '</li>';

            } else {

                $toc .= '</li>';
            }

            $toc .= sprintf(
                '<li><a href="#%s">%s</a>',
                esc_attr($id),
                esc_html($text)
            );
        }

        if (!$first) {

            while ($current_level > 1) {
                $toc .= '</li></ul>';
                $current_level--;
            }

            $toc .= '</li></ul>';
        }

        return [
            'toc'  => $toc,
            'html' => $dom->saveHTML(),
        ];
    }

    public static function make_context_menu($object = null, $target_group = '')
    {
        if (is_null($object)) {
            return '';
        }

        if (is_a($object, 'WP_Post')) {
            $terms = get_the_terms($object->ID, 'rrze-kb-category');
            if (empty($terms) || is_wp_error($terms)) {
                return '';
            }
            $category = $terms[0];
            $currentPostId = $object->ID;
        } elseif (is_a($object, 'WP_Term')) {
            $category = $object;
            $currentPostId = null;
        } else {
            return '';
        }

        $parents = [];
        if (!empty($category->parent)) {
            $parents = Helper::get_term_parents_recursive($category->term_id, 'rrze-kb-category');
            $parents = array_reverse($parents);
        }

        $parentIds = array_column($parents, 'term_id');
        $parentIds[] = $category->term_id;

        return self::render_category_tree(0, $parentIds, $category->term_id, $currentPostId, $target_group);
    }

    private static function render_category_tree($parent, $parentIds, $currentCategoryId, $currentPostId, $target_group = '')
    {
        $terms = get_terms([
                               'taxonomy'   => 'rrze-kb-category',
                               'parent'     => $parent,
                               'hide_empty' => false,
                               'orderby'    => 'name',
                               'order'      => 'ASC',
                           ]);
        //$terms = self::get_category_terms_by_target_group($target_group, true);

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        if (!empty($target_group)) {
            $target_group_link_param = '?target-group=' . $target_group;
        } else {
            $target_group_link_param = '';
        }

        $output = '<ul>';

        foreach ($terms as $term) {

            $is_parent = false;
            if (in_array($term->term_id, $parentIds, true)) {
                $is_parent = true;
            }

            $output .= '<li' . ($is_parent ? ' class="parent"' : '') . '>'
                . '<a href="' . esc_url(get_category_link($term->term_id)) . $target_group_link_param . '">'
                . '<span class="dashicons dashicons-category"></span>'
                . '<span class="link-text">' . esc_html($term->name) . '</span>'
                . '</a>';

            if (in_array($term->term_id, $parentIds, true)) {

                // Nur in der aktuell geöffneten Kategorie die Beiträge anzeigen
                if ($term->term_id === $currentCategoryId) {

                    $posts = get_posts([
                                           'post_type'      => 'rrze-kb-article',
                                           'posts_per_page' => -1,
                                           'orderby'        => 'title',
                                           'order'          => 'ASC',
                                           'no_found_rows'          => true,
                                           'update_post_meta_cache' => false,
                                           'update_post_term_cache' => false,
                                           'tax_query'      => [
                                               [
                                                   'taxonomy' => 'rrze-kb-category',
                                                   'field'    => 'term_id',
                                                   'terms'    => $term->term_id,
                                                   'include_children' => false,
                                               ],
                                           ],
                                       ]);
                    if ($posts) {
                        $output .= '<ul class="rrze-kb-article-list">';

                        foreach ($posts as $post) {
                            if ($post->ID === $currentPostId){
                                $class = ' class="current"';
                                $link_open = '<span class="current-item">';
                                $link_close = '</span>';
                            } else {
                                $class = '';
                                $link_open = '<a href="' . get_the_permalink($post->ID) . $target_group_link_param . '">';
                                $link_close = '</a>';
                            }

                            $output .= sprintf(
                                '<li%s>%s<span class="dashicons dashicons-media-document"></span><span class="link-text">%s</span>%s</li>',
                                $class,
                                $link_open,
                                esc_html(get_the_title($post->ID)),
                                $link_close
                            );
                        }

                        $output .= '</ul>';
                    }
                }

                // Unterkategorien
                $output .= self::render_category_tree(
                    $term->term_id,
                    $parentIds,
                    $currentCategoryId,
                    $currentPostId,
                    $target_group
                );

            }

            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    public static function render_search() {
        $options = (new Settings)->get_options();
        $search_title = $options['search-title'];
        $kb_name = $options['name'] ?? __('Knowledge Base', 'rrze-knowledgebase');
        $categories = get_terms([
            'taxonomy'   => 'rrze-kb-category',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
                                ]);
        $category_options = [];
        foreach ($categories as $category) {
            $category_options[$category->term_id] = $category->name;
        }
        $category_selected = [];
        if (!empty($_GET['kb-category'])) {
            array_walk($_GET['kb-category'], 'absint');
            $category_selected = $_GET['kb-category'];
        }
        $order_options = [
          'title_asc' => __('Title (A-Z)', 'rrze-knowledgebase'),
          'title_desc' => __('Title (Z-A)', 'rrze-knowledgebase'),
          'date_asc' => __('Date (oldest first)', 'rrze-knowledgebase'),
          'date_desc' => __('Date (newest first)', 'rrze-knowledgebase'),
        ];
        $order_selected = 'title_asc';
        if (!empty($_GET['kb-order'])) {
            $order_selected = in_array($_GET['kb-order'], array_keys($order_options) ? : []) ? $_GET['kb-order'] : 'title_asc';
        }
        $target_group_input = '';
        if (isset($_GET['target-group'])) {
            $target_group_input = '<input type="hidden" name="target-group" value="' . sanitize_title($_GET['target-group']) . '">';
        }
        // Search form
        $output = '<div class="rrze-kb-search">'
            . '<h2 class="rrze-kb-search-title">' . $search_title . '</h2>'
                  /* translators: %s: Knowledge Base name */
            . '<form class="rrze-kb-search-form" method="get">'
                  . '<div class="kb-search-input-wrapper">'
                  . '<input type="search" name="kb-search" class="" value="' . (isset($_GET['kb-search']) ? sanitize_text_field($_GET['kb-search']) : '') . '" aria-label="' . sprintf(__('Search the %s', 'rrze-knowledgebase'), $kb_name) . '" placeholder="' . sprintf(__('Search the %s', 'rrze-knowledgebase'), $kb_name) . '" />'
                  . '<button class="search-submit">' . __('Search', 'rrze-knowledgebase') . '</button>'
                  . '<input type="hidden" name="post_type" value="rrze-kb-article" />'
                  . $target_group_input
                  . '</div>'
                  . self::render_checklist_section('kb-category', __('Category', 'rrze-knowledgebase'), $category_options, $category_selected)
					. self::render_radiolist_section('kb-order', __('Order by', 'rrze-knowledgebase'), $order_options, $order_selected)
				. '</form>';

        // Search results
        if (!empty($_GET['kb-search']) || !empty($_GET['kb-category'])) {

            $search = !empty($_GET['kb-search']) ? sanitize_text_field($_GET['kb-search']) : '';
            $search_categories = !empty($_GET['kb-category']) ? array_map('absint', $_GET['kb-category']) : [];
            $order = !empty($_GET['kb-order']) ? sanitize_text_field($_GET['kb-order']) : 'title_asc';
            $target_group = !empty($_GET['target-group']) ? sanitize_title($_GET['target-group']) : '';
            $search_results = self::get_articles($search, $search_categories, $order, $target_group);

            $output .= '<div class="rrze-kb-search-results"><h3>' . __('Search Results', 'rrze-knowledgebase') . '</h3>';

            if ( empty($search_results)) {
                $output .= '<p>' . __('No results found.', 'rrze-knowledgebase') . '</p>';
            } else {
                $articles_grouped = self::group_articles_by_category($search_results);

                foreach ($articles_grouped as $group => $articles) {
                    $output .= '<h4>'. $group . '</h4>'
                        . '<ul class="rrze-kb-article-list">';
                    foreach ($articles as $article) {
                        $output .= '<li class="rrze-kb-article"><a href="' . get_the_permalink($article->ID) . '">'
                                   . '<span class="dashicons dashicons-media-document"></span>'
                                   . '<span class="link-text">' . esc_html(get_the_title($article->ID)) . '</span>'
                                   . '</a></li>';
                    }

                    $output .= '</ul>';
                }
            }
            $output .= '</div>';

        }

        $output .= '</div>';

        return $output;
    }

    private static function render_checklist_section(string $name, string $label, array $options, array $selected): string
    {
        $output = '<div class="filter-' . esc_attr($name) . '">';
        $output .= '<button type="button" class="checklist-toggle">'
                   . $label . '<span class="icon-wrapper" aria-hidden="true"></span></button>';
        $output .= '<div class="checklist">';
        foreach ($options as $id => $option) {
            $checked = in_array($id, $selected);
            $output .= '<label><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . $id . '" '
                       . checked($checked, true, false) . '>' . esc_html($option) . '</label>';
        }
        $output .= '<button type="submit" class="submit-filter" value="' . __('Apply filter', 'rrze-knowledgebase') . '">' . __('Apply filter', 'rrze-knowledgebase') . '</button>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    private static function render_radiolist_section(string $name, string $label, array $options, string $selected): string
    {
        $output = '<div class="filter-' . esc_attr($name) . '">';
        $output .= '<button type="button" class="checklist-toggle">'
                   . $label . '<span class="icon-wrapper" aria-hidden="true"></span></button>';
        $output .= '<div class="checklist">';
        foreach ($options as $key => $value) {
            $checked = $key == $selected;
            $output .= '<label><input type="radio" name="' . esc_attr($name) . '" value="' . $key . '" '
                       . checked($checked, true, false) . '>' . esc_html($value) . '</label>';
        }
        $output .= '<button type="submit" class="submit-filter">' . __('Apply filter', 'rrze-knowledgebase') . '</button>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    private static function get_articles($search = '', $categories = [], $order = 'title_asc', $target_group = '') {
        $args = [
            'post_type'              => 'rrze-kb-article',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        if ( ! empty($search)) {
            $args[ 's' ] = sanitize_text_field($search);
        }
        if ( ! empty($categories)) {
            $args[ 'tax_query' ][] = [
                'taxonomy'         => 'rrze-kb-category',
                'field'            => 'term_id',
                'terms'            => array_map('absint', $categories),
                'operator'         => 'IN',
                'include_children' => true,
            ];
        }
        if ( ! empty($target_group)) {
            $args[ 'tax_query' ][] = [
                'taxonomy'         => 'rrze-kb-target-group',
                'field'            => 'slug',
                'terms'            => sanitize_title($target_group),
                'include_children' => true,
            ];
        }
        switch ($order) {
            case 'title_asc':
                $args[ 'orderby' ] = 'title';
                $args[ 'order' ]   = 'ASC';
                break;
            case 'title_desc':
                $args[ 'orderby' ] = 'title';
                $args[ 'order' ]   = 'DESC';
                break;
            case 'date_asc':
                $args[ 'orderby' ] = 'modified';
                $args[ 'order' ]   = 'ASC';
                break;
            case 'date_desc':
                $args[ 'orderby' ] = 'modified';
                $args[ 'order' ]   = 'DESC';
                break;
        }
        $articles = get_posts($args);

        return $articles;
    }

    private static function group_articles_by_category($articles) {
        $articles_grouped = [];
        foreach ($articles as $article) {
            $top_terms = self::get_top_terms($article->ID, 'rrze-kb-category');
            if (empty($top_terms)) {
                $articles_grouped[__('Uncategorized', 'rrze-knowledgebase')][] = $article;
            } else {
                foreach ($top_terms as $term) {
                    $articles_grouped[ $term->name ][] = $article;
                }
            }
        }
        return $articles_grouped;
    }

    private static function get_top_terms($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $top_terms = [];

        foreach ($terms as $term) {
            while ($term->parent != 0) {
                $term = get_term($term->parent, $taxonomy);
            }

            $top_terms[$term->term_id] = $term;
        }

        return array_values($top_terms);
    }

    public static /**
     * Gibt '#000000' oder '#FFFFFF' zurück – je nachdem,
     * welche Farbe den höheren Kontrast zum angegebenen Hex-Farbwert hat.
     *
     * @param string $hex Hex-Farbwert, z.B. "#3498db" oder "3498db"
     * @return string "#000000" oder "#FFFFFF"
     */
    function get_contrast_color(string $hex): string
    {
        $hex = ltrim($hex, '#');

        // Kurzschreibweise (#abc -> #aabbcc)
        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new \InvalidArgumentException('Ungültiger Hex-Farbwert.');
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // sRGB -> lineare RGB-Werte
        $convert = function ($c) {
            return ($c <= 0.04045)
                ? $c / 12.92
                : pow(($c + 0.055) / 1.055, 2.4);
        };

        $r = $convert($r);
        $g = $convert($g);
        $b = $convert($b);

        // Relative Luminanz nach WCAG
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Kontrastverhältnisse
        $contrastWhite = (1.05) / ($luminance + 0.05);
        $contrastBlack = ($luminance + 0.05) / 0.05;

        return ($contrastWhite > $contrastBlack) ? '#FFFFFF' : '#000000';
    }

    public static function render_target_group_dropdown($selected = '') {

        $output = '<form method="get" class="rrze-kb-target-group-select">';
        $output .= wp_dropdown_categories([
                'taxonomy'        => 'rrze-kb-target-group',
                'depth'           => 1,
                'name'            => 'target-group',
                'show_option_none' => __('All target groups', 'rrze-knowledgebase'),
                'option_none_value' => '',
                'hide_empty'      => true,
                'value_field'     => 'slug',
                'selected'        => $selected,
                'echo'            => false,
            ]);
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key === 'target-group')
                    continue;
                $output .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        $output .= '<button type="submit">' . esc_html('Filter', 'rrze-knowledgebase') . '</button></form>';
        return $output;
    }

    public static function get_category_terms_by_target_group($target_group, $include_children = false) {
        if (empty($target_group)) {
            $terms = get_categories([
                'taxonomy'   => 'rrze-kb-category',
                'parent'     => 0,
                'hide_empty' => false,
                'include_children' => $include_children,
            ]);
            return $terms;
        }

        $post_ids = get_posts([
              'post_type'      => 'rrze-kb-article',
              'post_status'    => 'publish',
              'posts_per_page' => -1,
              'fields'         => 'ids',
              'tax_query' => [
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
              ]
          ]);

        if (!empty($post_ids)) {
            $terms = wp_get_object_terms($post_ids, 'rrze-kb-category', [
                'orderby' => 'name',
                'include_children' => $include_children,
            ]);
        } else {
            $terms = [];
        }
        return $terms;
    }

}