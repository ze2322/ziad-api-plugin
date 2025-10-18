<?php
/**
 * Block Registrar
 * 
 * @package Ziad\APIPlugin
 */

namespace Ziad\APIPlugin\Blocks;

defined('ABSPATH') || exit;

class BlockRegistrar {

    public function __construct() {
        error_log('Ziad API Plugin: BlockRegistrar __construct called');
        
        // Register block type VERY early - before init
        add_action('plugins_loaded', [$this, 'register_block_type'], 1);
        
        // Also try on init as backup
        add_action('init', [$this, 'register_block_type'], 1);
        
        // Enqueue editor assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets'], 10);
        
        // Enqueue frontend assets on wp_enqueue_scripts with high priority
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 999);
        
        // Also enqueue when rendering the block
        add_action('wp_footer', [$this, 'ensure_frontend_assets'], 1);
        
        // Add shortcode as fallback
        add_shortcode('ziad_api_table', [$this, 'render_block']);
        
        // Debug: Log when WordPress tries to render content
        add_filter('the_content', [$this, 'debug_content'], 1);
        
        // IMPORTANT: Hook into render_block to ensure our callback runs
        add_filter('render_block', [$this, 'intercept_block_render'], 10, 2);
    }

    /**
     * Debug content filter
     */
    public function debug_content($content) {
        if (!is_admin() && has_block('ziad-api-plugin/data-table', $content)) {
            error_log('==== DEBUG: Content has our block ====');
            error_log('Content length: ' . strlen($content));
            error_log('Content preview: ' . substr($content, 0, 500));
        }
        return $content;
    }

    /**
     * Intercept block rendering to ensure our block renders
     */
    public function intercept_block_render($block_content, $block) {
        // Only process our block
        if ($block['blockName'] !== 'ziad-api-plugin/data-table') {
            return $block_content;
        }
        
        error_log('==== INTERCEPT: Our block is being rendered ====');
        error_log('Block attributes: ' . print_r($block['attrs'], true));
        error_log('Current block content: ' . substr($block_content, 0, 200));
        
        // If content is empty, call our render function
        if (empty($block_content)) {
            error_log('Block content is empty, calling render_block manually');
            $block_content = $this->render_block($block['attrs']);
        }
        
        return $block_content;
    }

    /**
     * Register the block type
     */
    public function register_block_type() {
        static $registered = false;
        
        // Prevent double registration
        if ($registered) {
            error_log('Ziad API Plugin: Block already registered, skipping');
            return;
        }
        
        error_log('Ziad API Plugin: register_block_type called');
        
        if (!function_exists('register_block_type')) {
            error_log('Ziad API Plugin ERROR: register_block_type function does not exist');
            return;
        }
        
        // Register with column toggle attributes
        $result = register_block_type('ziad-api-plugin/data-table', [
            'api_version'     => 2,
            'editor_script'   => 'ziad-api-block-editor',
            'render_callback' => [$this, 'render_block'],
            'attributes'      => [
                'showTitle' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showId' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showFirstName' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showLastName' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showEmail' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showDate' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
            ],
        ]);
        
        if ($result) {
            $registered = true;
            error_log('Ziad API Plugin: Block type registered successfully');
            error_log('Ziad API Plugin: Render callback: ' . print_r($result->render_callback, true));
        } else {
            error_log('Ziad API Plugin ERROR: Block registration failed');
        }
    }

    /**
     * Enqueue block assets for the EDITOR only
     */
    public function enqueue_block_editor_assets() {
        error_log('Ziad API Plugin: enqueue_block_editor_assets called');
        
        $script_path = ZIAD_API_PLUGIN_DIR . 'src/Blocks/assets/block.js';
        $script_url = ZIAD_API_PLUGIN_URL . 'src/Blocks/assets/block.js';
        
        if (!file_exists($script_path)) {
            error_log('Ziad API Plugin ERROR: block.js not found at: ' . $script_path);
            return;
        }
        
        wp_enqueue_script(
            'ziad-api-block-editor',
            $script_url,
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            filemtime($script_path),
            true
        );
        
        error_log('Ziad API Plugin: Editor script enqueued successfully');
    }

    /**
     * Enqueue frontend assets for the PUBLIC site
     */
    public function enqueue_frontend_assets() {
        // Don't load in admin
        if (is_admin()) {
            error_log('Ziad API Plugin: Skipping frontend assets in admin');
            return;
        }
        
        error_log('Ziad API Plugin: enqueue_frontend_assets called');
        error_log('Is admin: ' . (is_admin() ? 'Yes' : 'No'));
        error_log('Has block: ' . (has_block('ziad-api-plugin/data-table') ? 'Yes' : 'No'));
        
        // Always enqueue on frontend pages (we'll check for block presence later)
        $this->load_frontend_scripts();
    }

    /**
     * Ensure frontend assets are loaded in footer
     */
    public function ensure_frontend_assets() {
        if (is_admin()) {
            return;
        }
        
        // Check if scripts are already enqueued
        if (!wp_script_is('ziad-api-block-frontend', 'enqueued')) {
            error_log('Ziad API Plugin: Scripts not enqueued, loading now in footer');
            $this->load_frontend_scripts();
        }
    }

    /**
     * Load frontend scripts and styles
     */
    private function load_frontend_scripts() {
        $frontend_script_path = ZIAD_API_PLUGIN_DIR . 'src/Blocks/assets/frontend.js';
        $frontend_script_url = ZIAD_API_PLUGIN_URL . 'src/Blocks/assets/frontend.js';
        
        error_log('Ziad API Plugin: Attempting to load frontend.js from: ' . $frontend_script_path);
        
        if (!file_exists($frontend_script_path)) {
            error_log('Ziad API Plugin ERROR: frontend.js not found at: ' . $frontend_script_path);
            return;
        }
        
        // Enqueue frontend JavaScript
        wp_enqueue_script(
            'ziad-api-block-frontend',
            $frontend_script_url,
            [], // No dependencies
            filemtime($frontend_script_path),
            true // Load in footer
        );

        // Localize script for AJAX - THIS IS CRITICAL
        wp_localize_script('ziad-api-block-frontend', 'ziadApiPlugin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ziad_api_nonce'),
        ]);
        
        error_log('Ziad API Plugin: Frontend script enqueued successfully');
        error_log('Ziad API Plugin: AJAX URL: ' . admin_url('admin-ajax.php'));

        // Enqueue block CSS
        $style_path = ZIAD_API_PLUGIN_DIR . 'src/Blocks/assets/block.css';
        $style_url = ZIAD_API_PLUGIN_URL . 'src/Blocks/assets/block.css';
        
        if (file_exists($style_path)) {
            wp_enqueue_style(
                'ziad-api-block-style',
                $style_url,
                [],
                filemtime($style_path)
            );
            error_log('Ziad API Plugin: Block CSS enqueued');
        }
    }

    /**
     * Render block on frontend
     * 
     * @param array $attributes Block attributes
     * @return string
     */
    public function render_block($attributes = []) {
        error_log('=== RENDER_BLOCK CALLED ===');
        error_log('Attributes: ' . print_r($attributes, true));
        error_log('Is Admin: ' . (is_admin() ? 'Yes' : 'No'));
        
        // Ensure frontend assets are loaded when block is rendered
        if (!is_admin()) {
            $this->load_frontend_scripts();
        }
        
        // Get all attributes with defaults
        $show_title = isset($attributes['showTitle']) ? $attributes['showTitle'] : true;
        $show_id = isset($attributes['showId']) ? $attributes['showId'] : true;
        $show_first_name = isset($attributes['showFirstName']) ? $attributes['showFirstName'] : true;
        $show_last_name = isset($attributes['showLastName']) ? $attributes['showLastName'] : true;
        $show_email = isset($attributes['showEmail']) ? $attributes['showEmail'] : true;
        $show_date = isset($attributes['showDate']) ? $attributes['showDate'] : true;
        
        // ONLY CHANGE: Added proper output buffering
        ob_start();
        ?>
        <div class="wp-block-ziad-api-plugin-data-table" 
             data-show-id="<?php echo esc_attr($show_id ? '1' : '0'); ?>"
             data-show-firstname="<?php echo esc_attr($show_first_name ? '1' : '0'); ?>"
             data-show-lastname="<?php echo esc_attr($show_last_name ? '1' : '0'); ?>"
             data-show-email="<?php echo esc_attr($show_email ? '1' : '0'); ?>"
             data-show-date="<?php echo esc_attr($show_date ? '1' : '0'); ?>"
             style="margin: 20px 0; padding: 20px; background: #fff; border: 2px solid #0073aa; border-radius: 8px;">
            <div style="background: #0073aa; color: white; padding: 10px; margin: -20px -20px 20px -20px; border-radius: 6px 6px 0 0;">
                <strong><?php echo esc_html__('📊 API Data Table', 'ziad-api-plugin'); ?></strong>
            </div>
            
            <?php if ($show_title): ?>
                <h2 style="color: #0073aa; margin-top: 0;"><?php echo esc_html__('API Data Table', 'ziad-api-plugin'); ?></h2>
            <?php endif; ?>
            
            <p><?php echo esc_html__('Below is your dynamic data table fetched via AJAX:', 'ziad-api-plugin'); ?></p>
            <div id="ziad-api-data-table" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;">
                <div style="text-align: center; padding: 20px;">
                    <div class="spinner is-active" style="float: none; margin: 10px auto; visibility: visible;"></div>
                    <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('Loading data...', 'ziad-api-plugin'); ?></p>
                </div>
            </div>
        </div>
        <?php
        $output = ob_get_clean();
        
        error_log('Block output created, length: ' . strlen($output) . ' bytes');
        
        return $output;
    }
}