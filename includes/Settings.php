<?php

namespace RRZE\Knowledgebase;

class Settings
{
    private string $option_name = 'rrze-kb';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('update_option_rrze-kb',[$this, 'modify_slug'], 10, 2);

    }

    public function add_options_page(): void
    {
        add_options_page(
            __('RRZE Knowledgebase Settings', 'rrze-knowledgebase'),
            __('RRZE Knowledgebase', 'rrze-knowledgebase'),
            'manage_options',
            'rrze-kb-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'rrze-kb-group',
            $this->option_name,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
            ]
        );
    }

    public function render_settings_page(): void
    {
        $options = get_option($this->option_name, [
            'name' => __('Knowledge Base', 'rrze-knowledgebase'),
            'slug' => __('knowledgebase', 'rrze-knowledgebase'),
            'layout' => 'table',
        ]);

        $active_tab = $_GET['tab'] ?? 'data';
        ?>
        <div class="wrap">
            <h1><?php _e('RRZE Knowledgebase Settings', 'rrze-knowledgebase') ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=rrze-kb-settings&tab=data"
                   class="nav-tab <?php echo $active_tab === 'data' ? 'nav-tab-active' : ''; ?>">
                    Data
                </a>

                <a href="?page=rrze-kb-settings&tab=layout"
                   class="nav-tab <?php echo $active_tab === 'layout' ? 'nav-tab-active' : ''; ?>">
                    Layout
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('rrze-kb-group');
                ?>

                <table class="form-table">
                    <?php if ($active_tab === 'data') : ?>

                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-name"><?php _e('Name', 'rrze-knowledgebase'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-name"
                                    name="<?php echo esc_attr($this->option_name); ?>[name]"
                                    value="<?php echo esc_attr($options['name'] ?? __('Knowledge Base', 'rrze-knowledgebase')); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-slug"><?php _e('Slug', 'rrze-knowledgebase'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-slug"
                                    name="<?php echo esc_attr($this->option_name); ?>[slug]"
                                    value="<?php echo esc_attr($options['slug'] ?? __('knowledgebase', 'rrze-knowledgebase')); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>

                    <?php elseif ($active_tab === 'layout') : ?>

                        <tr>
                            <th scope="row"><?php _e('Layout', 'rrze-knowledgebase'); ?></th>
                            <td>
                                <?php
                                $layouts = [
                                    'table' => __('Table', 'rrze-knowledgebase'),
                                    'grid'  => __('Grid', 'rrze-knowledgebase'),
                                    'list'  => __('List', 'rrze-knowledgebase'),
                                ];

                                foreach ($layouts as $value => $label) :
                                    ?>
                                    <label style="display:block; margin-bottom:8px;">
                                        <input
                                            type="radio"
                                            name="<?php echo esc_attr($this->option_name); ?>[rrze-kb-layout]"
                                            value="<?php echo esc_attr($value); ?>"
                                            <?php checked($options['rrze-kb-layout'] ?? 'table', $value); ?>
                                        >
                                        <?php echo esc_html($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                    <?php endif; ?>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function sanitize_settings(array $input): array
    {
        $output = [];

        // Name
        $output['name'] = isset($input['name'])
            ? sanitize_text_field($input['name'])
            : '';

        // Slug
        $output['slug'] = isset($input['slug'])
            ? sanitize_title($input['slug'])
            : '';

        // Layout
        $allowed_layouts = ['table', 'grid', 'list'];

        $output['layout'] = in_array($input['layout'] ?? '', $allowed_layouts, true)
            ? $input['layout']
            : 'table';

        return $output;
    }

    public function modify_slug($old, $new) {

        $old_slug = sanitize_title( $old['slug'] ?? '' );
        $new_slug = sanitize_title( $new['slug'] ?? '' );

        if ( $old_slug !== $new_slug ) {
            update_option( 'rrze_kb_flush_rewrite', 1 );
        }

    }


}