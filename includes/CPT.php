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
        //add_action('add_meta_boxes', [$this, 'render_metabox']);
    }

    public function register_post_type()
    {
        $labels = [
            'name'               => _x('KB Article', 'post type general name', 'rrze-knowledgebase'),
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
            'featured_image'        => __( 'KB Article icon', 'rrze-knowledgebase' ),    //used in post.php
            'set_featured_image'    => __( 'Set KB article icon', 'rrze-knowledgebase' ),    //used in post.php
            'remove_featured_image' => __( 'Remove KB article icon', 'rrze-knowledgebase' ), //used in post.php
            'use_featured_image'    => __( 'Use as KB article icon', 'rrze-knowledgebase' ), //used in post.php
            'insert_into_item'      => __( 'Insert into KB article', 'rrze-knowledgebase' ),  //used in post.php
            'uploaded_to_this_item' => __( 'Uploaded to this KB article', 'rrze-knowledgebase' ), //used in post.php

        ];
        
        $args = [
            'labels'             => $labels,
            'hierarchical'       => false,
            'public'             => true,
            'show_ui'            => true,
            'supports'           => ['title', 'editor', 'revisions', 'author', 'excerpt', 'page-attributes', 'thumbnail'],
            'menu_icon'          => 'dashicons-lightbulb',
            'capability_type'    => 'page',
            'has_archive'        => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'rewrite'            => ['slug' => self::POST_TYPE],
            'show_in_rest'       => false,
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
        ];
        register_taxonomy('rrze-kb-tag', self::POST_TYPE, $args);
    }

}