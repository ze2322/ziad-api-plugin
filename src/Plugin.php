<?php
namespace Ziad\APIPlugin;

defined('ABSPATH') || exit;

class Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Don't initialize here - wait for init() method
    }

    /**
     * Initialize plugin components
     * Called from the bootstrap file
     */
    public function init() {
        // Hook into WordPress init action
        add_action('init', [$this, 'init_components']);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        error_log('Ziad API Plugin: init_components called');
        
        // Initialize AJAX handler
        if (class_exists('Ziad\\APIPlugin\\AJAXHandler')) {
            new AJAXHandler();
            error_log('Ziad API Plugin: AJAXHandler initialized');
        } else {
            error_log('Ziad API Plugin ERROR: AJAXHandler class not found');
        }

        // Initialize Admin Page
        if (class_exists('Ziad\\APIPlugin\\AdminPage')) {
            new AdminPage();
            error_log('Ziad API Plugin: AdminPage initialized');
        } else {
            error_log('Ziad API Plugin ERROR: AdminPage class not found');
        }

        // Initialize Blocks
        if (class_exists('Ziad\\APIPlugin\\Blocks\\BlockRegistrar')) {
            new Blocks\BlockRegistrar();
            error_log('Ziad API Plugin: BlockRegistrar initialized');
        } else {
            error_log('Ziad API Plugin ERROR: BlockRegistrar class not found');
        }
        
        // Initialize CLI Commands
        if (defined('WP_CLI') && WP_CLI) {
            if (class_exists('Ziad\\APIPlugin\\CLI\\RefreshDataCommand')) {
                new CLI\RefreshDataCommand();
                error_log('Ziad API Plugin: CLI commands registered');
            }
        }
    }

    /**
     * Enqueue admin scripts/styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_ziad-api-plugin') {
            return;
        }

        $admin_css_path = ZIAD_API_PLUGIN_DIR . 'assets/admin.css';
        $admin_js_path = ZIAD_API_PLUGIN_DIR . 'assets/admin.js';

        if (file_exists($admin_css_path)) {
            wp_enqueue_style(
                'ziad-api-admin-style',
                ZIAD_API_PLUGIN_URL . 'assets/admin.css',
                [],
                ZIAD_API_PLUGIN_VERSION
            );
        }

        if (file_exists($admin_js_path)) {
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
    }
}