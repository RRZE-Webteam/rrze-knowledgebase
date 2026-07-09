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
        } elseif (is_a($object, 'WP_Post')) {
            $terms = get_the_terms( $object->ID, 'rrze-kb-category' );
            $this->category = $terms[0] ?? null;
        }
    }

    public function render_archive()
    {
        $title = single_cat_title('', false);

        $output = '<div class="rrze-kb-category-page">'
            . Helper::make_breadcrumbs($this->category);

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

    public function render_single(): string
    {
        global $post;
        $modifiedHtml = Helper::make_toc(get_the_content());
        $toc = $modifiedHtml['toc'] ?? '';
        $content = $modifiedHtml['html'] ?? '';
        $context_menu = Helper::make_context_menu($post);
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
                      . '<header class="entry-header"><h1 class="entry-title">' . get_the_title() . '</h1></header>'
                      . $content
                . '</article>';
        // Context Menu
        if (!empty($context_menu)) {
            $output .= '<nav class="rrze-kb-context-menu" aria-label="' . __('Side Menu', 'rrze-knowledgebase') . '">' . $context_menu . '</nav>';
        }
        $output .='</div></div>';

        wp_enqueue_script('rrze-knowledgebase-script');
        return wp_kses_post($output);
    }

    private function render_subcategories($subcategories): string
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