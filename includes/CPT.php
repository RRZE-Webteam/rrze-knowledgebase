<?php

namespace RRZE\Knowledgebase;

defined('ABSPATH') || exit;

class CPT
{
    const POST_TYPE = 'rrze-kb-article';

    public function __construct()
    {
        add_action('init', [$this, 'register_post_type'], 9);
        add_action('init', [$this, 'register_taxonomies'], 9);
        add_action('add_meta_boxes', [$this, 'add_meta_box'] );
        add_action( 'save_post', [$this, 'save_postdata'] );
        add_filter('single_template', [$this, 'include_single_template']);
        add_filter('archive_template', [$this, 'include_archive_template']);
        add_action('pre_get_posts', [$this, 'modify_archive_query']);
        add_action('init', [$this, 'flush_rewrite'], 99);
    }

    public function register_post_type()
    {
        $labels = [
            'name'               => _x('KB Articles', 'post type general name', 'rrze-knowledgebase'),
            'singular_name'      => _x('KB Article', 'post type singular name', 'rrze-knowledgebase'),
            'menu_name'          => _x('Knowledge Base', 'admin menu', 'rrze-knowledgebase'),
            'name_admin_bar'     => _x('KB Articles', 'add new on admin bar', 'rrze-knowledgebase'),
            'add_new'            => _x('Add New', 'admin menu', 'rrze-knowledgebase'),
            'add_new_item'       => __('Add New KB Article', 'rrze-knowledgebase'),
            'new_item'           => __('New KB Article', 'rrze-knowledgebase'),
            'edit_item'          => __('Edit KB Article', 'rrze-knowledgebase'),
            'view_item'          => __('View KB Article', 'rrze-knowledgebase'),
            'all_items'          => __('All KB Articles', 'rrze-knowledgebase'),
            'search_items'       => __('Search KB Articles', 'rrze-knowledgebase'),
            'parent_item_colon'  => __('Parent KB Articles:', 'rrze-knowledgebase'),
            'not_found'          => __('No KB articles found.', 'rrze-knowledgebase'),
            'not_found_in_trash' => __('No KB articles found in Trash.', 'rrze-knowledgebase'),
            'featured_image'        => __( 'KB Article image', 'rrze-knowledgebase' ),    //used in post.php
            'set_featured_image'    => __( 'Set KB article image', 'rrze-knowledgebase' ),    //used in post.php
            'remove_featured_image' => __( 'Remove KB article image', 'rrze-knowledgebase' ), //used in post.php
            'use_featured_image'    => __( 'Use as KB article image', 'rrze-knowledgebase' ), //used in post.php
            'insert_into_item'      => __( 'Insert into KB article', 'rrze-knowledgebase' ),  //used in post.php
            'uploaded_to_this_item' => __( 'Uploaded to this KB article', 'rrze-knowledgebase' ), //used in post.php

        ];

        $options = get_option('rrze-kb');
        $slug    = $options[ 'slug' ] ?? 'knowledge-base';
        $args = [
            'labels'             => $labels,
            'hierarchical'       => false,
            'public'             => true,
            'show_ui'            => true,
            'supports'           => ['title', 'editor', 'revisions', 'author', 'excerpt', 'page-attributes', 'thumbnail'],
            'menu_icon'          => 'dashicons-lightbulb',
            'capability_type'    => 'page',
            'has_archive' => sanitize_title($slug),
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'rewrite' => [
                'slug' => sanitize_title($slug),
                'with_front' => false,
            ],
            'show_in_rest'       => true,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register_taxonomies() {

        // Categories
        $labels = [
            'name'              => _x('KB Categories', 'Taxonomy general name', 'rrze-knowledgebase'),
            'singular_name'     => _x('KB Category', 'Taxonomy singular name', 'rrze-knowledgebase'),
            'plural_name'     => _x('KB Categories', 'Taxonomy plural name', 'rrze-knowledgebase'),
            'edit_item'     => _x('Edit KB Category', 'Taxonomy singular name', 'rrze-knowledgebase'),
            'add_new_item'     => _x('Add New KB Category', 'Taxonomy singular name', 'rrze-knowledgebase'),
        ];
        $args = [
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'capabilities'      => [
                'manage_terms'  => 'manage_options',
                'edit_terms'    => 'manage_options',
                'delete_terms'  => 'manage_options',
                'assign_terms'  => 'edit_pages'
            ],
            'rewrite'           => [
                'slug' => 'kb-category',
                'with_front' => false,
                'hierarchical' => true
            ],
        ];
        register_taxonomy('rrze-kb-category', self::POST_TYPE, $args);

        // Tags
        $labels = [
            'name'              => _x('KB Tags', 'Taxonomy general name', 'rrze-knowledgebase'),
            'singular_name'     => _x('KB Tag', 'Taxonomy singular name', 'rrze-knowledgebase'),
            'plural_name'     => _x('KB Tags', 'Taxonomy plural name', 'rrze-knowledgebase'),
            'edit_item'     => _x('Edit KB Tag', 'Taxonomy singular name', 'rrze-knowledgebase'),
            'add_new_item'     => _x('Add New KB Tag', 'Taxonomy singular name', 'rrze-knowledgebase'),
        ];
        $args = [
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'capabilities'      => [
                'manage_terms'  => 'manage_options',
                'edit_terms'    => 'manage_options',
                'delete_terms'  => 'manage_options',
                'assign_terms'  => 'edit_pages'
            ],
            'rewrite'           => [
                'slug' => 'kb-tag',
                'with_front' => false,
                'hierarchical' => true
            ],
        ];
        register_taxonomy('rrze-kb-tag', self::POST_TYPE, $args);
    }

    public function add_meta_box() {
        add_meta_box(
            'kb_article_views',
            __('KB Article views', 'rrze-knowledgebase'),
            [$this, 'meta_box_html'],
            self::POST_TYPE,
            'side'
        );
    }

    public function meta_box_html($post) {
        $value = get_post_meta( $post->ID, 'kb_article_views', true );
        ?>
        <label for="kb_article_views"><?php _e('Article views', 'rrze-knowledgebase'); ?></label>
        <input id="kb-article-views" name="kb-article-views" type="number" value="<?php echo esc_html($value); ?>" min="0">
        <?php
    }

    public  function save_postdata( $post_id ) {
        if ( array_key_exists( 'kb_article_views', $_POST ) ) {
            $views = (int) $_POST['kb_article_views'];
            update_post_meta(
                $post_id,
                'kb_article_views',
                $views
            );
        }
    }

    public static function include_archive_template($template_path)
    {
        $current_object = get_queried_object();
        if (isset($current_object->query_var) && $current_object->name != self::POST_TYPE)
            return $template_path;
        if (isset($current_object->taxonomy) && !in_array($current_object->taxonomy, ['rrze-kb-category', 'rrze-kb-tag']))
            return $template_path;

        if ($theme_file = locate_template(array('archive-kb-article.php'))) {
            $template_path = $theme_file;
        } else {
            $template_path = plugin()->getPath() . '/templates/archive-kb-article.php';
        }
        wp_enqueue_style('rrze-knowledgebase-style');

        return $template_path;
    }

    public function include_single_template($template_path)
    {
        global $post;
        if (!($post->post_type == self::POST_TYPE)) {
            return $template_path;
        }

        $template_path = plugin()->getPath() . 'templates/single-kb-article.php';

        wp_enqueue_style('rrze-knowledgebase-style');

        return $template_path;
    }

    public function modify_archive_query($query) {
        if (
            is_admin()
            || !$query->is_main_query()
            || !is_tax('rrze-kb-category')
        ) {
            return;
        }

        $term = get_queried_object();

        $query->set('tax_query', [
            [
                'taxonomy'         => 'rrze-kb-category',
                'field'            => 'term_id',
                'terms'            => [$term->term_id],
                'include_children' => false,
            ]
        ]);

    }

    public function flush_rewrite() {
        if ( ! get_option( 'rrze_kb_flush_rewrite' ) ) {
            return;
        }

        flush_rewrite_rules();
        delete_option( 'rrze_kb_flush_rewrite' );
    }

}