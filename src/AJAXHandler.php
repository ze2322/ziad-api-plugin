<?php
namespace Ziad\APIPlugin;

defined('ABSPATH') || exit;

class AJAXHandler {

    public function __construct() {
        add_action('wp_ajax_ziad_get_data', [$this, 'handle_get_data']);
        add_action('wp_ajax_nopriv_ziad_get_data', [$this, 'handle_get_data']);
        add_action('wp_ajax_ziad_refresh_data', [$this, 'handle_refresh_data']);
    }

    public function handle_get_data() {
        $client = new APIClient();
        $data = $client->get_data();

        wp_send_json_success([
            'data' => $data,
            'timestamp' => current_time('Y-m-d H:i:s'),
        ]);
    }

    public function handle_refresh_data() {
        check_ajax_referer('ziad_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $client = new APIClient();
        $data = $client->refresh_data();

        wp_send_json_success([
            'data' => $data,
            'timestamp' => current_time('Y-m-d H:i:s'),
        ]);
    }
}
