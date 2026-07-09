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

    public static function make_context_menu($post = null) {
        if (is_null($post)) {
            return '';
        }

        $terms = get_the_terms($post->ID, 'rrze-kb-category');
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }
        $category = $terms[0];
        $parents = [];
        if (!empty($category->parent)) {
            $parents = Helper::get_term_parents_recursive($category->term_id, 'rrze-kb-category');
            $parents = array_reverse($parents);
        }

        $parentIds = array_column($parents, 'term_id');
        $parentIds[] = $category->term_id; // optional: aktuelles Element ebenfalls berücksichtigen

        return self::render_category_tree(0, $parentIds);
    }

    private static function render_category_tree($parent, $parentIds)
    {
        $terms = get_terms([
                               'taxonomy'   => 'rrze-kb-category',
                               'parent'     => $parent,
                               'hide_empty' => false,
                               'orderby'    => 'name',
                               'order'      => 'ASC',
                           ]);

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $output = '<ul>';

        foreach ($terms as $term) {
            $output .= '<li>';
            $output .= '<a href="' . esc_url(get_category_link($term->term_id)) . '">';
            $output .= esc_html($term->name);
            $output .= '</a>';

            if (in_array($term->term_id, $parentIds, true)) {
                $output .= self::render_category_tree($term->term_id, $parentIds);
            }

            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

}