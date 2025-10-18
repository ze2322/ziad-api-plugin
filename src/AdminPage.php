<?php
namespace Ziad\APIPlugin;

defined('ABSPATH') || exit;

class AdminPage {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_page() {
        add_menu_page(
            __('Ziad API Plugin', 'ziad-api-plugin'),
            __('Ziad API', 'ziad-api-plugin'),
            'manage_options',
            'ziad-api-plugin',
            [$this, 'render_admin_page'],
            'dashicons-database',
            30
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_ziad-api-plugin') return;

        wp_enqueue_style(
            'ziad-api-admin-style',
            ZIAD_API_PLUGIN_URL . 'assets/admin.css',
            [],
            ZIAD_API_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ziad-api-admin-script',
            ZIAD_API_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            ZIAD_API_PLUGIN_VERSION,
            true
        );

        wp_localize_script('ziad-api-admin-script', 'ziadApiPlugin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ziad_api_nonce'),
        ]);
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="ziad-api-controls">
                <button id="ziad-refresh-data" class="button button-primary">
                    <?php _e('Refresh Data', 'ziad-api-plugin'); ?>
                </button>
                <span id="ziad-last-updated" style="margin-left: 15px;"></span>
            </div>

            <div class="ziad-api-content">
                <h2><?php _e('API Data Table', 'ziad-api-plugin'); ?></h2>
                <p><?php _e('Below is your dynamic data table fetched via AJAX:', 'ziad-api-plugin'); ?></p>
                <div id="ziad-api-data-table" class="ziad-api-loading">
                    <span class="spinner is-active"></span> <?php _e('Loading data...', 'ziad-api-plugin'); ?>
                </div>
            </div>
        </div>
        <?php
    }
}
