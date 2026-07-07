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

}