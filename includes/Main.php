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
        $options = (new Settings)->get_options();
        $accent_color = $options['accent-color'];
        $contrast_color = Helper::get_contrast_color($accent_color);
        $css = ':root {--rrze-kb-accent-color: ' . $accent_color . '; --rrze-kb-contrast-color: ' . $contrast_color . '; }';
        wp_add_inline_style('rrze-knowledgebase-style', $css);

        wp_register_script(
            'rrze-knowledgebase-script',
            plugins_url('assets/js/rrze-knowledgebase.js', plugin()->getBasename()),
            [],
            plugin()->getVersion(true),
            ['in_footer' => true]
        );
        /*wp_localize_script('rrze-knowledgebase-script', 'rrze_knowledgebase_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce( 'rrze-knowledgebase-ajax-nonce' ),
        ]);*/
    }

    public function admin_enqueue_scripts()
    {
        wp_enqueue_style(
            'rrze-knowledgebase-admin-style',
            plugins_url('assets/css/rrze-knowledgebase-admin.css', plugin()->getBasename()),
            [],
            plugin()->getVersion(true)
        );

        $screen = get_current_screen();
        if (
            isset($screen->taxonomy) &&
            $screen->taxonomy === 'rrze-kb-target-group'
        ) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            wp_add_inline_script(
                'wp-color-picker',
                'jQuery(function($){ $(".color-picker").wpColorPicker({
                    palettes: [
                        "#04316a",
                        "#c50f3c",
                        "#18b4f1",
                        "#7bb725",
                        "#fdb735",
                        "#8c9fb1"
                        ]
                    });
                });'
            );
        }

        /*wp_enqueue_script(
            'rrze-knowledgebase-admin-script',
            plugins_url('assets/js/rrze-knowledgebase-admin.js', plugin()->getBasename()),
            [],
            plugin()->getVersion(true)
        );*/
    }


}
