<?php

namespace RRZE\Knowledgebase;

class Output
{
    private $category;
    private $subcategories;

    public function __construct($object)
    {
        //var_dump($object);
        if ( is_post_type_archive()) {
            $this->subcategories = get_categories([
                'taxonomy'   => 'rrze-kb-category',
                'parent'     => 0,
                'hide_empty' => false,
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
                ]);
            }
        }
    }

    public function render_archive()
    {
        if (isset($this->category->parent)) {
            $parents = Helper::get_term_parents_recursive($this->category->term_id, 'rrze-kb-category');
            $parents = array_reverse($parents);
        } else {
            $parents = false;
        }
        $options = get_option('rrze-kb');
        $kb_name = $options['name'] ?? __('Knowledge Base', 'rrze-knowledgebase');

        $title = single_cat_title('', false);

        $output = '<div class="rrze-kb-category-page">'
            . '<ul class="kb-category-breadcrumbs">'
            . '<li><a href="' . esc_url(get_post_type_archive_link('rrze-kb-article')) . '" class="kb-category-back-link">' . $kb_name . '</a></li>';
        if ($parents) {
            foreach ($parents as $parent) {
                $output .= '<li><a href="' . esc_url(get_category_link($parent->term_id)) . '" class="kb-category-back-link">' . esc_html($parent->name) . '</a></li>';
            }
        }
        $output .= '<li><span class="kb-category-current-item">' . $title . '</span></li>'
            . '</ul>';

        $output .= '<h1>' . $title . '</h1>';

        if ( ! empty($this->subcategories)) :

            $output .= $this->render_subcategories($this->subcategories);

        endif;

        if (have_posts()) :

            $output .= '<div class="kb-category-post-list">';

            while (have_posts()) : the_post();

                $classes = get_post_class( '', get_the_ID() );
                $output .= '<article class="' . esc_attr( implode( ' ', $classes ) ) . '">'
                           . '<h1><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h1>'
                           . '</article>';

            endwhile;

            $output .= '</div>';

        endif;

        if (empty($this->subcategories) && !have_posts()) :
            $output .= '<p class="nothing-found">' . __('No articles found.', 'rrze-knowledgebase') . '</p>';
        endif;

        $output .= '</div>';

        wp_enqueue_style('dashicons');
        return $output;
    }

    public function render_single() {
        $output = '<div class="rrze-kb-article-page">'
            . '<nav class="kb-category-navigation">' . '</nav>';


        $output .= '</div>';
        return $output;
    }

    private function render_subcategories($subcategories)
    {
        $output = '<div class="kb-category-grid">';

        foreach ($subcategories as $subcategory) :
            //var_dump($subcategory);

            $output .= '<article class="kb-category-card">'
                       . '<h1><a href="' . esc_url(get_category_link($subcategory->term_id)) . '">'
                       . esc_html($subcategory->name) . '</a></h1>';

            if ($subcategory->description) :
                $output .= '<p class="kb-category-description">' . esc_html($subcategory->description) . '</p>';
            endif;

            $subarticles = get_posts([
                                         'post_type'      => 'rrze-kb-article',
                                         'numberposts'   => -1,
                                         'tax_query' => [[
                                             'taxonomy' => 'rrze-kb-category',
                                             'field'    => 'term_id',
                                             'terms'    => $subcategory->term_id,
                                             'include_children' => false,
                                         ]]
                                     ]);

            if ( ! empty($subarticles)) :

                $output .= '<ul class="kb-articles">';

                foreach ($subarticles as $subarticle) :

                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_permalink($subarticle->ID)) . '" class="kb-articles">'
                               .'<span class="dashicons dashicons-media-document"></span>'
                               . esc_html($subarticle->post_title)
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
                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_category_link($subsubcategory->term_id)) . '" class="kb-subsubcategories">'
                               . '<span class="dashicons dashicons-category"></span>'
                               . esc_html($subsubcategory->name)
                               . '</a> (' . esc_html($subsubcategory->count) . ')</li>';
                endforeach;

                $output .= '</ul>';

            endif;

            $output .= '</article>';

        endforeach;

        $output .= '</div>';

        return $output;
    }

}