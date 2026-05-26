<?php

namespace RRZE\Knowledgebase;

class Output
{
    private $category;
    private $subcategories;

    public function __construct($category)
    {
        $this->category = $category;
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

    public function render()
    {
        $parent = ( isset( $this->category->parent ) ) ? get_term_by( 'id', $this->category->parent, 'rrze-kb-category' ) : false;

        $output = '<div class="rrze-kb-category-page">'
                  . ($parent ? '<a href="' . esc_url(get_category_link($parent->term_id)) . '" class="kb-category-back-link"><span class="dashicons dashicons-arrow-up-alt2"></span>' . $parent->name . '</a>' : '')
                  . '<h1>' . single_cat_title('', false) . '</h1>';

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

        else:

            $output .= '<p class="nothing-found">' . __('No articles found.', 'rrze-knowledgebase') . '</p>';

        endif;



        $output .= '</div>';

        wp_enqueue_style('dashicons');
        return $output;
    }

    private function render_subcategories($subcategories)
    {
        $output = '<div class="kb-subcategory-grid">';

        foreach ($subcategories as $subcategory) :
            //var_dump($subcategory);

            $output .= '<article class="kb-subcategory-card">'
                       . '<h1><a href="' . esc_url(get_category_link($subcategory->term_id)) . '">'
                       . esc_html($subcategory->name) . '</a></h1>';

            if ($subcategory->description) :
                $output .= '<p class="kb-subcategory-description">' . esc_html($subcategory->description) . '</p>';
            endif;

            $subarticles = get_posts([
                                         'post_type'      => 'rrze-kb-article',
                                         'numberposts'   => -1,
                                         'tax_query' => [[
                                             'taxonomy' => 'rrze-kb-category',
                                             'field'    => 'term_id',
                                             'terms'    => $subcategory->term_id,
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
                    // print "<pre>"; print_r($subsubcategory); print "</pre>";
                    $output .= '<li class="">'
                               . '<a href="' . esc_url(get_category_link($subsubcategory->term_id)) . '" class="kb-subsubcategories">'
                               . '<span class="dashicons dashicons-category"></span>'
                               . esc_html($subsubcategory->name)
                               . '</a></li>';
                endforeach;

                $output .= '</ul>';

            endif;

            $output .= '</article>';

        endforeach;

        $output .= '</div>';

        return $output;
    }

}