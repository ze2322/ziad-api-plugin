<?php
namespace Ziad\APIPlugin;

defined('ABSPATH') || exit;

class APIClient {

    private $api_url;
    private $cache_key = 'ziad_api_data';
    private $cache_expiry;

    public function __construct() {
        // Use the real API endpoint
        $this->api_url = 'https://miusage.com/v1/challenge/1/';
        $this->cache_expiry = get_option('ziad_api_cache_expiry', 3600);
    }

    /**
     * Get data from cache or API
     */
    public function get_data() {
        // Try to get from cache first
        $cached_data = get_transient($this->cache_key);
        
        if (false !== $cached_data) {
            error_log('Ziad API Plugin: Returning cached data');
            return $cached_data;
        }

        // Fetch fresh data
        return $this->fetch_and_cache_data();
    }

    /**
     * Force refresh data from API
     */
    public function refresh_data() {
        delete_transient($this->cache_key);
        return $this->fetch_and_cache_data();
    }

    /**
     * Fetch data from API and cache it
     */
    private function fetch_and_cache_data() {
        error_log('Ziad API Plugin: Fetching fresh data from API: ' . $this->api_url);

        $response = wp_remote_get($this->api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('Ziad API Plugin Error: ' . $response->get_error_message());
            return $this->get_fallback_data();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Ziad API Plugin: API returned status code ' . $response_code);
            return $this->get_fallback_data();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['data'])) {
            error_log('Ziad API Plugin: Invalid response structure from API');
            error_log('Ziad API Plugin: Response body: ' . substr($body, 0, 500));
            return $this->get_fallback_data();
        }

        // Transform data to our format
        $transformed_data = $this->transform_data($data);

        // Cache the data
        set_transient($this->cache_key, $transformed_data, $this->cache_expiry);

        error_log('Ziad API Plugin: Data fetched and cached successfully');

        return $transformed_data;
    }

    /**
     * Transform API data to our table format
     * 
     * Expected API structure:
     * {
     *   "title": "This amazing table",
     *   "data": {
     *     "headers": ["ID", "First Name", "Last Name", "Email", "Date"],
     *     "rows": {
     *       "1": {"id": 53, "fname": "Chris", "lname": "Test", "email": "chris@test.com", "date": 1760653616}
     *     }
     *   }
     * }
     */
    private function transform_data($raw_data) {
        $api_data = $raw_data['data'];
        
        // Get headers from API
        $headers = $api_data['headers'] ?? ['ID', 'First Name', 'Last Name', 'Email', 'Date'];
        
        // Get rows from API (it's an object, not array)
        $api_rows = $api_data['rows'] ?? [];
        
        $rows = [];
        
        // FIXED: Sort by the object key to maintain consistent order
        // This ensures the same data always appears in the same order
        ksort($api_rows);
        
        foreach ($api_rows as $key => $row) {
            // Sanitize and validate each field
            $id = isset($row['id']) ? absint($row['id']) : 0;
            $fname = isset($row['fname']) ? sanitize_text_field($row['fname']) : '';
            $lname = isset($row['lname']) ? sanitize_text_field($row['lname']) : '';
            $email = isset($row['email']) ? sanitize_email($row['email']) : '';
            
            // Convert Unix timestamp to readable date
            // Validate that it's a valid timestamp
            $timestamp = isset($row['date']) ? absint($row['date']) : 0;
            $date = $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
            
            $rows[] = [
                'id'    => $id,
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'date'  => $date,
            ];
        }

        error_log('Ziad API Plugin: Transformed ' . count($rows) . ' rows');

        return [
            'headers' => array_map('sanitize_text_field', $headers),
            'rows'    => $rows,
            'title'   => isset($raw_data['title']) ? sanitize_text_field($raw_data['title']) : 'API Data Table',
        ];
    }

    /**
     * Get fallback data if API fails
     */
    private function get_fallback_data() {
        return [
            'headers' => ['ID', 'First Name', 'Last Name', 'Email', 'Date'],
            'rows' => [
                [
                    'id'    => 999,
                    'fname' => 'Error',
                    'lname' => 'Loading Data',
                    'email' => 'api-error@example.com',
                    'date'  => current_time('Y-m-d H:i:s'),
                ],
            ],
            'title' => 'API Data Table (Offline)',
        ];
    }

    /**
     * Clear cache
     */
    public function clear_cache() {
        error_log('Ziad API Plugin: Cache cleared');
        return delete_transient($this->cache_key);
    }

    /**
     * Get cache status
     */
    public function get_cache_info() {
        $cached_data = get_transient($this->cache_key);
        
        return [
            'is_cached' => false !== $cached_data,
            'cache_key' => $this->cache_key,
            'expiry'    => $this->cache_expiry,
            'api_url'   => $this->api_url,
        ];
    }
}