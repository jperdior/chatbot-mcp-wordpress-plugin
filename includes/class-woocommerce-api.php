<?php
/**
 * WooCommerce API Management Class
 * 
 * Handles automatic generation and management of WooCommerce REST API keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCWC_WooCommerce_API {
    
    /**
     * Generate WooCommerce API keys for the current user
     * 
     * @param string $description Description for the API key
     * @param string $permissions Permissions level (read, write, read_write)
     * @return array Result array with success status and data
     */
    public function generate_api_keys($description, $permissions = 'read') {
        global $wpdb;
        
        try {
            // Get current user
            $current_user = wp_get_current_user();
            if (!$current_user || !$current_user->ID) {
                return array(
                    'success' => false,
                    'message' => 'No valid user found'
                );
            }
            
            // Validate permissions
            $valid_permissions = array('read', 'write', 'read_write');
            if (!in_array($permissions, $valid_permissions)) {
                $permissions = 'read';
            }
            
            // Generate consumer key and secret
            $consumer_key = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();
            
            // Prepare data for insertion
            $key_data = array(
                'user_id' => $current_user->ID,
                'description' => sanitize_text_field($description),
                'permissions' => $permissions,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'nonces' => '',
                'truncated_key' => substr($consumer_key, -7),
                'last_access' => null
            );
            
            // Insert into WooCommerce API keys table
            $table_name = $wpdb->prefix . 'woocommerce_api_keys';
            $result = $wpdb->insert($table_name, $key_data);
            
            if ($result === false) {
                return array(
                    'success' => false,
                    'message' => 'Failed to create API key in database'
                );
            }
            
            $key_id = $wpdb->insert_id;
            
            // Log the API key creation
            $this->log_api_key_creation($key_id, $description, $current_user->ID);
            
            return array(
                'success' => true,
                'key_id' => $key_id,
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'permissions' => $permissions,
                'description' => $description
            );
            
        } catch (Exception $e) {
            error_log('SCWC API Key Generation Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while generating API keys'
            );
        }
    }
    
    /**
     * Delete a WooCommerce API key
     * 
     * @param int $key_id The API key ID to delete
     * @return bool Success status
     */
    public function delete_api_key($key_id) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'woocommerce_api_keys';
            $result = $wpdb->delete($table_name, array('key_id' => $key_id), array('%d'));
            
            if ($result !== false) {
                $this->log_api_key_deletion($key_id);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('SCWC API Key Deletion Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get API key information by ID
     * 
     * @param int $key_id The API key ID
     * @return array|null API key data or null if not found
     */
    public function get_api_key($key_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woocommerce_api_keys';
        $key_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE key_id = %d", $key_id),
            ARRAY_A
        );
        
        return $key_data;
    }
    
    /**
     * List all API keys for the current user
     * 
     * @return array List of API keys
     */
    public function list_user_api_keys() {
        global $wpdb;
        
        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->ID) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'woocommerce_api_keys';
        $keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT key_id, description, permissions, truncated_key, last_access 
                 FROM {$table_name} 
                 WHERE user_id = %d 
                 ORDER BY key_id DESC",
                $current_user->ID
            ),
            ARRAY_A
        );
        
        return $keys ?: array();
    }
    
    /**
     * Test API key validity
     * 
     * @param string $consumer_key Consumer key
     * @param string $consumer_secret Consumer secret
     * @return array Test result
     */
    public function test_api_key($consumer_key, $consumer_secret) {
        try {
            // Create a test request to WooCommerce API
            $url = get_site_url() . '/wp-json/wc/v3/products';
            
            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
                ),
                'timeout' => 30
            );
            
            $response = wp_remote_get($url . '?per_page=1', $args);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => 'API test failed: ' . $response->get_error_message()
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code === 200) {
                return array(
                    'success' => true,
                    'message' => 'API key is valid and working'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'API key validation failed (HTTP ' . $response_code . ')'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'API test error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Update API key last access time
     * 
     * @param int $key_id The API key ID
     */
    public function update_last_access($key_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woocommerce_api_keys';
        $wpdb->update(
            $table_name,
            array('last_access' => current_time('mysql')),
            array('key_id' => $key_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Log API key creation
     * 
     * @param int $key_id The created key ID
     * @param string $description Key description
     * @param int $user_id User ID
     */
    private function log_api_key_creation($key_id, $description, $user_id) {
        error_log(sprintf(
            'SCWC: API Key created - ID: %d, Description: %s, User: %d',
            $key_id,
            $description,
            $user_id
        ));
    }
    
    /**
     * Log API key deletion
     * 
     * @param int $key_id The deleted key ID
     */
    private function log_api_key_deletion($key_id) {
        error_log(sprintf(
            'SCWC: API Key deleted - ID: %d',
            $key_id
        ));
    }
    
    /**
     * Check if WooCommerce API is properly configured
     * 
     * @return array Status array
     */
    public function check_woocommerce_api_status() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce is not active'
            );
        }
        
        // Check if REST API is enabled
        if (get_option('woocommerce_api_enabled') !== 'yes') {
            return array(
                'success' => false,
                'message' => 'WooCommerce REST API is not enabled'
            );
        }
        
        // Check database table
        global $wpdb;
        $table_name = $wpdb->prefix . 'woocommerce_api_keys';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            return array(
                'success' => false,
                'message' => 'WooCommerce API keys table does not exist'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'WooCommerce API is properly configured'
        );
    }
}
