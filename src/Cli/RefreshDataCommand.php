<?php
/**
 * WP CLI Command for Ziad API Plugin
 * 
 * @package Ziad\APIPlugin
 */

namespace Ziad\APIPlugin\CLI;

use Ziad\APIPlugin\APIClient;

defined('ABSPATH') || exit;

class RefreshDataCommand {

    public function __construct() {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('ziad-api refresh', [$this, 'refresh']);
        }
    }

    /**
     * Force refresh API data (bypasses cache)
     * 
     * ## EXAMPLES
     * 
     *     wp ziad-api refresh
     * 
     * @when after_wp_load
     */
    public function refresh($args, $assoc_args) {
        \WP_CLI::log('Forcing API data refresh...');
        
        $client = new APIClient();
        $result = $client->refresh_data();
        
        if (!empty($result) && isset($result['rows'])) {
            $row_count = count($result['rows']);
            \WP_CLI::success("Data refreshed successfully! Retrieved {$row_count} rows.");
        } else {
            \WP_CLI::error('Failed to refresh data.');
        }
    }
}