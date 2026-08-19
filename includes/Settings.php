<?php

namespace RRZE\Knowledgebase;

class Settings
{
    private string $option_name = 'rrze-kb';
    private array $defaults;

    public function __construct()
    {
        $this->defaults = [
            'name' => __('Knowledge Base', 'rrze-knowledgebase'),
            'slug' => __('knowledgebase', 'rrze-knowledgebase'),
            'search-title' => __('What are you looking for?', 'rrze-knowledgebase'),
            'layout' => 'table',
            'accent-color' => '#04316a',
        ];
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('update_option_rrze-kb',[$this, 'modify_slug'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_color_picker']);
    }
    
    public function get_options(): array
    {
        $options = get_option($this->option_name, $this->defaults);
        return array_merge($this->defaults, $options);
    }

    public function get_defaults($key = false): array|string
    {
        if ($key) {
            $defaults = $this->defaults;
            return $defaults[ $key ] ?? '';
        }
        return $this->defaults;
    }

    public function add_options_page(): void
    {
        add_options_page(
            __('RRZE Knowledge Base Settings', 'rrze-knowledgebase'),
            __('RRZE Knowledge Base', 'rrze-knowledgebase'),
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
        $options = self::get_options();

        $active_tab = isset($_GET['tab']) ? sanitize_title(wp_unslash($_GET['tab'])) : 'data';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('RRZE Knowledge Base Settings', 'rrze-knowledgebase') ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=rrze-kb-settings&tab=data"
                   class="nav-tab <?php echo $active_tab === 'data' ? 'nav-tab-active' : ''; ?>">
                    Data
                </a>

                <!--<a href="?page=rrze-kb-settings&tab=layout"
                   class="nav-tab <?php //echo $active_tab === 'layout' ? 'nav-tab-active' : ''; ?>">
                    Layout
                </a>-->
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('rrze-kb-group');
                ?>

                <table class="form-table">
                    <?php if ($active_tab === 'data') : ?>

                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-name"><?php esc_html_e('Name', 'rrze-knowledgebase'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-name"
                                    name="<?php echo esc_attr($this->option_name); ?>[name]"
                                    value="<?php echo esc_html($options['name']); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-slug"><?php esc_html_e('Slug', 'rrze-knowledgebase'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-slug"
                                    name="<?php echo esc_attr($this->option_name); ?>[slug]"
                                    value="<?php echo esc_attr($options['slug']); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-search-title">
                                    <?php esc_html_e('Search Title', 'rrze-knowledgebase'); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-search-title"
                                    name="<?php echo esc_attr($this->option_name); ?>[search-title]"
                                    value="<?php echo esc_attr($options['search-title']); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>

                    <?php elseif ($active_tab === 'layout') : ?>

                        <tr>
                            <th scope="row"><?php esc_html_e('Layout', 'rrze-knowledgebase'); ?></th>
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
                                            name="<?php echo esc_attr($this->option_name); ?>[layout]"
                                            value="<?php echo esc_attr($value); ?>"
                                            <?php checked($options['layout'] ?? 'table', $value); ?>
                                        >
                                        <?php echo esc_html($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($this->option_name); ?>-accent-color">
                                    <?php esc_html_e('Accent Color', 'rrze-knowledgebase'); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($this->option_name); ?>-accent-color"
                                    name="<?php echo esc_attr($this->option_name); ?>[accent-color]"
                                    value="<?php echo esc_attr($options['accent-color'] ?? '#04316a'); ?>"
                                    class="rrze-kb-color-picker"
                                    data-default-color="#04316a"
                                >
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
        $output = get_option($this->option_name, $this->defaults);

        // Name
        if (isset($input['name'])) {
            $output['name'] = sanitize_text_field($input['name']);
        }

        // Slug
        if (isset($input['slug'])) {
            $output['slug'] = sanitize_title($input['slug']);
        }

        // Name
        if (isset($input['search-title'])) {
            $output['search-title'] = sanitize_text_field($input['search-title']);
        }

        // Layout
        $allowed_layouts = ['table', 'grid', 'list'];
        if (isset($input['layout']) && in_array($input['layout'], $allowed_layouts, true)) {
            $output['layout'] = sanitize_text_field($input['layout']);
        }

        // Accent Color
        if (isset($input['accent-color'])) {
            $output['accent-color'] = sanitize_hex_color($input['accent-color']);
        }

        unset($output['accent_color']);

        return $output;
    }

    public function modify_slug($old, $new) {

        $old_slug = sanitize_title( $old['slug'] ?? '' );
        $new_slug = sanitize_title( $new['slug'] ?? '' );

        if ( $old_slug !== $new_slug ) {
            update_option( 'rrze_kb_flush_rewrite', 1 );
        }

    }

    public function enqueue_color_picker($hook): void
    {
        if ($hook !== 'settings_page_rrze-kb-settings') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(function($){
            $(".rrze-kb-color-picker").wpColorPicker({
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

        /*wp_add_inline_script(
            'wp-color-picker',
            'jQuery(function($){
            $(".rrze-kb-color-picker").wpColorPicker({
                palettes: [
                    "#04316a",
                    "#041e42",
                    "#c50f3c",
                    "#971b2f",
                    "#18b4f1",
                    "#005287",
                    "#7bb725",
                    "#266141",
                    "#fdb735",
                    "#e87722",
                    "#8c9fb1",
                    "#2f586e"
                    ]
                });
            });'
        );*/
    }


}