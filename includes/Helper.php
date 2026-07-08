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

    public static function make_breadcrumbs($category = null) {

        if (!is_a($category, 'WP_Term')) {
            return '';
        }

        $output = '<ul class="kb-category-breadcrumbs">'
                  . '<li><a href="' . esc_url(get_post_type_archive_link('rrze-kb-article')) . '" class="kb-category-back-link">' . $kb_name . '</a></li>';
        if ($parents) {
            foreach ($parents as $parent) {
                $output .= '<li><a href="' . esc_url(get_category_link($parent->term_id)) . '" class="kb-category-back-link">' . esc_html($parent->name) . '</a></li>';
            }
        }
        $output .= '<li><span class="kb-category-current-item">' . $title . '</span></li>'
                   . '</ul>';
    }

    public static function make_context_menu() {

        $output = '';

        return $output;
    }

}