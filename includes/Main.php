<?php

namespace RRZE\Knowledgebase;

defined('ABSPATH') || exit;

class Main
{
    /**
     * __construct
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        $settings = new Settings();
        //$settings->onLoaded();
        new CPT();
    }

    public function enqueue_scripts()
    {
        wp_register_style(
            'rrze-knowledgebase-style',
            plugins_url('assets/css/rrze-knowledgebase.css', plugin()->getBasename()),
            [],
            plugin()->getVersion(true)
        );
        /*wp_register_script(
            'rrze-knowledgebase-script',
            plugins_url('assets/js/rrze-knowledgebase.js', plugin()->getBasename()),
            ['jquery'],
            plugin()->getVersion(true)
        );
        wp_localize_script('rrze-knowledgebase-script', 'rrze_knowledgebase_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce( 'rrze-knowledgebase-ajax-nonce' ),
        ]);*/
    }

    public function admin_enqueue_scripts()
    {
        /*wp_enqueue_style(
            'rrze-knowledgebase-admin-style',
            plugins_url('assets/css/rrze-knowledgebase-admin.css', plugin()->getBasename()),
            [],
            plugin()->getVersion(true)
        );*/
        /*wp_enqueue_script(
            'rrze-knowledgebase-admin-script',
            plugins_url('assets/js/rrze-knowledgebase-admin.js', plugin()->getBasename()),
            [],
            plugin()->getVersion(true)
        );*/
    }


}
