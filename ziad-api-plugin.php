<?php
/**
 * Plugin Name: Ziad API Plugin
 * Plugin URI: http://ziad-api-plugin-site.local/
 * Description: A comprehensive API-based plugin with caching, AJAX, admin page, and Gutenberg blocks
 * Version: 1.0.0
 * Author: Ziad
 * Author URI: http://ziad-api-plugin-site.local/
 * License: GPL v2 or later
 * Text Domain: ziad-api-plugin
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ZIAD_API_PLUGIN_VERSION', '1.0.0');
define('ZIAD_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZIAD_API_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ZIAD_API_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ZIAD_API_PLUGIN_FILE', __FILE__);

// Load Composer autoloader
if (file_exists(ZIAD_API_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ZIAD_API_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'Ziad\\APIPlugin\\';
        $base_dir = ZIAD_API_PLUGIN_DIR . 'src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) require $file;
    });
}

$block_registrar_file = ZIAD_API_PLUGIN_DIR . 'src/Blocks/BlockRegistrar.php';
if (file_exists($block_registrar_file)) {
    require_once $block_registrar_file;
    error_log('Ziad API Plugin: BlockRegistrar loaded manually from: ' . $block_registrar_file);
} else {
    error_log('Ziad API Plugin ERROR: BlockRegistrar file does not exist at: ' . $block_registrar_file);
}

/**
 * Initialize the plugin
 */
function ziad_api_plugin_init() {
    // Check if class exists
    if (!class_exists('Ziad\\APIPlugin\\Plugin')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Ziad API Plugin: Plugin class not found. Please run "composer install" in the plugin directory.', 'ziad-api-plugin'); ?></p>
            </div>
            <?php
        });
        return;
    }
    
    // Get plugin instance and initialize
    $plugin = Ziad\APIPlugin\Plugin::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', 'ziad_api_plugin_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    // Set default options
    add_option('ziad_api_cache_expiry', 3600); // 1 hour
    add_option('ziad_api_endpoint', 'https://jsonplaceholder.typicode.com/posts');
    
    // Create options if they don't exist
    if (!get_option('ziad_api_settings')) {
        add_option('ziad_api_settings', [
            'enable_cache' => true,
            'cache_expiry' => 3600,
            'api_endpoint' => 'https://jsonplaceholder.typicode.com/posts',
            'show_admin_notices' => true,
        ]);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log activation
    error_log('Ziad API Plugin activated');
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    // Clean up transients
    delete_transient('ziad_api_data');
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    error_log('Ziad API Plugin deactivated');
});

/**
 * Uninstall hook
 * Note: This should ideally be in uninstall.php, but adding here for reference
 */
register_uninstall_hook(__FILE__, 'ziad_api_plugin_uninstall');

function ziad_api_plugin_uninstall() {
    // Remove options
    delete_option('ziad_api_cache_expiry');
    delete_option('ziad_api_endpoint');
    delete_option('ziad_api_settings');
    
    // Remove all transients
    delete_transient('ziad_api_data');
    
    // Clean up any custom tables if you create them in future
    // global $wpdb;
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ziad_api_data");
}

/**
 * Load plugin textdomain for translations
 */
add_action('init', function() {
    load_plugin_textdomain(
        'ziad-api-plugin',
        false,
        dirname(ZIAD_API_PLUGIN_BASENAME) . '/languages'
    );
});

/**
 * Add settings link on plugins page
 */
add_filter('plugin_action_links_' . ZIAD_API_PLUGIN_BASENAME, function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=ziad-api-plugin'),
        __('Settings', 'ziad-api-plugin')
    );
    
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Check plugin requirements
 */
function ziad_api_plugin_check_requirements() {
    $php_version = phpversion();
    $wp_version = get_bloginfo('version');
    
    $requirements_met = true;
    $messages = [];
    
    // Check PHP version
    if (version_compare($php_version, '7.4', '<')) {
        $requirements_met = false;
        $messages[] = sprintf(
            __('Ziad API Plugin requires PHP 7.4 or higher. You are running PHP %s.', 'ziad-api-plugin'),
            $php_version
        );
    }
    
    // Check WordPress version
    if (version_compare($wp_version, '5.8', '<')) {
        $requirements_met = false;
        $messages[] = sprintf(
            __('Ziad API Plugin requires WordPress 5.8 or higher. You are running WordPress %s.', 'ziad-api-plugin'),
            $wp_version
        );
    }
    
    // Display admin notice if requirements not met
    if (!$requirements_met) {
        add_action('admin_notices', function() use ($messages) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('Ziad API Plugin Error:', 'ziad-api-plugin'); ?></strong></p>
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        });
        
        // Deactivate plugin
        deactivate_plugins(ZIAD_API_PLUGIN_BASENAME);
        
        return false;
    }
    
    return true;
}

// Check requirements on activation and admin init
register_activation_hook(__FILE__, 'ziad_api_plugin_check_requirements');
add_action('admin_init', 'ziad_api_plugin_check_requirements');

/**
 * Debug mode helper
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function ziad_api_debug_log($message) {
        if (is_array($message) || is_object($message)) {
            error_log('Ziad API Plugin: ' . print_r($message, true));
        } else {
            error_log('Ziad API Plugin: ' . $message);
        }
    }
} else {
    function ziad_api_debug_log($message) {
        // Do nothing in production
    }
}