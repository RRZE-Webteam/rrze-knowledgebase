<?php

namespace RRZE\Knowledgebase;

class Settings
{
    private string $option_name = 'my_plugin_settings';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_options_page(): void
    {
        add_options_page(
            'Plugin Settings',
            'Plugin Settings',
            'manage_options',
            'my-plugin-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'my_plugin_settings_group',
            $this->option_name
        );
    }

    public function render_settings_page(): void
    {
        $options = get_option($this->option_name, [
            'rrze-kb-slug' => '',
            'layout' => 'table',
        ]);

        $active_tab = $_GET['tab'] ?? 'data';
        ?>
        <div class="wrap">
            <h1>Plugin Settings</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=my-plugin-settings&tab=data"
                   class="nav-tab <?php echo $active_tab === 'data' ? 'nav-tab-active' : ''; ?>">
                    Data
                </a>

                <a href="?page=my-plugin-settings&tab=layout"
                   class="nav-tab <?php echo $active_tab === 'layout' ? 'nav-tab-active' : ''; ?>">
                    Layout
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('my_plugin_settings_group');
                ?>

                <table class="form-table">
                    <?php if ($active_tab === 'data') : ?>

                        <tr>
                            <th scope="row">
                                <label for="rrze-kb-slug">rrze-kb-slug</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="rrze-kb-slug"
                                    name="<?php echo esc_attr($this->option_name); ?>[rrze-kb-slug]"
                                    value="<?php echo esc_attr($options['rrze-kb-slug'] ?? ''); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>

                    <?php elseif ($active_tab === 'layout') : ?>

                        <tr>
                            <th scope="row">Layout</th>
                            <td>
                                <?php
                                $layouts = [
                                    'table' => 'Table',
                                    'grid'  => 'Grid',
                                    'list'  => 'List',
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

                    <?php endif; ?>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}