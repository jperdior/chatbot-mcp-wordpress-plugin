<?php
/**
 * MCP Manager Class
 * 
 * Handles creation and management of WordPress MCP servers in the chatbot service
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCWC_MCP_Manager {
    
    private $api_manager;
    
    public function __construct() {
        $this->api_manager = new SCWC_API_Manager();
    }
    
    /**
     * Create a WordPress MCP server for a chatbot
     * 
     * @param string $chatbot_id The chatbot ID
     * @param string $name Name for the MCP server
     * @param string $wordpress_url WordPress site URL
     * @param string $consumer_key WooCommerce consumer key
     * @param string $consumer_secret WooCommerce consumer secret
     * @return array Result array with success status and data
     */
    public function create_wordpress_mcp_server($chatbot_id, $name, $wordpress_url, $consumer_key, $consumer_secret) {
        try {
            // Prepare the request data according to the API schema
            $request_data = array(
                'name' => $name,
                'description' => 'WordPress MCP server for ' . get_bloginfo('name'),
                'wordpress_url' => $wordpress_url,
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret
            );
            
            // Make the API call to create the WordPress MCP server
            $endpoint = 'chatbots/' . $chatbot_id . '/wordpress-mcp-servers';
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'POST', $request_data);
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Failed to create MCP server: ' . $result['message']
                );
            }
            
            $mcp_server_data = $result['data'];
            
            return array(
                'success' => true,
                'server_id' => $mcp_server_data['id'],
                'mcp_server_url' => $mcp_server_data['mcp_server_url'],
                'name' => $mcp_server_data['name'],
                'description' => $mcp_server_data['description'],
                'is_active' => $mcp_server_data['is_active'],
                'is_validated' => $mcp_server_data['is_validated'],
                'tools_count' => $mcp_server_data['tools_count'],
                'created_at' => $mcp_server_data['created_at'],
                'message' => $mcp_server_data['message']
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Server Creation Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while creating the MCP server'
            );
        }
    }
    
    /**
     * Delete a WordPress MCP server
     * 
     * @param string $server_id The MCP server ID to delete
     * @param string $chatbot_id The chatbot ID that owns the MCP server
     * @return array Result array with success status
     */
    public function delete_mcp_server($server_id, $chatbot_id) {
        scwc_debug_log(' Deleting MCP server - Server ID: ' . $server_id . ', Chatbot ID: ' . $chatbot_id);
        
        if (empty($chatbot_id)) {
            scwc_debug_log(' ERROR: Chatbot ID is required for MCP server deletion');
            return array(
                'success' => false,
                'message' => 'Chatbot ID is required for MCP server deletion'
            );
        }
        
        try {
            // Use the proper RESTful endpoint structure
            $endpoint = 'chatbots/' . $chatbot_id . '/mcp-servers/' . $server_id;
            scwc_debug_log(' MCP delete endpoint: ' . $endpoint);
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'DELETE');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Failed to delete MCP server: ' . $result['message']
                );
            }
            
            return array(
                'success' => true,
                'message' => 'MCP server deleted successfully'
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Server Deletion Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while deleting the MCP server'
            );
        }
    }
    
    /**
     * Get MCP server details
     * 
     * @param string $server_id The MCP server ID
     * @return array Result array with server data
     */
    public function get_mcp_server($server_id) {
        try {
            $endpoint = 'mcp-servers/' . $server_id;
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'GET');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Failed to get MCP server: ' . $result['message']
                );
            }
            
            return array(
                'success' => true,
                'data' => $result['data']
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Server Fetch Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while fetching the MCP server'
            );
        }
    }
    
    /**
     * List all MCP servers for a chatbot
     * 
     * @param string $chatbot_id The chatbot ID
     * @return array Result array with servers list
     */
    public function list_chatbot_mcp_servers($chatbot_id) {
        try {
            $endpoint = 'chatbots/' . $chatbot_id . '/mcp-servers';
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'GET');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Failed to list MCP servers: ' . $result['message']
                );
            }
            
            return array(
                'success' => true,
                'data' => $result['data']
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Servers List Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while listing MCP servers'
            );
        }
    }
    
    /**
     * Test MCP server connection and validation
     * 
     * @param string $server_id The MCP server ID
     * @return array Result array with test status
     */
    public function test_mcp_server($server_id) {
        try {
            // This endpoint might not exist yet, but it's a placeholder for testing functionality
            $endpoint = 'mcp-servers/' . $server_id . '/test';
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'POST');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'MCP server test failed: ' . $result['message']
                );
            }
            
            return array(
                'success' => true,
                'data' => $result['data']
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Server Test Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while testing the MCP server'
            );
        }
    }
    
    /**
     * Refresh MCP server (re-validate and update tools)
     * 
     * @param string $server_id The MCP server ID
     * @return array Result array with refresh status
     */
    public function refresh_mcp_server($server_id) {
        try {
            $endpoint = 'mcp-servers/' . $server_id . '/refresh';
            $result = $this->api_manager->make_chatbot_service_request($endpoint, 'POST');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Failed to refresh MCP server: ' . $result['message']
                );
            }
            
            return array(
                'success' => true,
                'data' => $result['data']
            );
            
        } catch (Exception $e) {
            scwc_debug_log('SCWC MCP Server Refresh Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An error occurred while refreshing the MCP server'
            );
        }
    }
    
    
    /**
     * Update integration configuration
     * 
     * @param string $chatbot_id The chatbot ID
     * @param array $config New configuration data
     * @return array Result array
     */
    public function update_integration_config($chatbot_id, $config) {
        $integration_data = get_option('scwc_integration_' . $chatbot_id);
        
        if (!$integration_data) {
            return array(
                'success' => false,
                'message' => 'Integration not found'
            );
        }
        
        // Merge new configuration
        $updated_data = array_merge($integration_data, $config);
        $updated_data['updated_at'] = current_time('mysql');
        
        // Save updated configuration
        update_option('scwc_integration_' . $chatbot_id, $updated_data);
        
        return array(
            'success' => true,
            'message' => 'Integration configuration updated',
            'data' => $updated_data
        );
    }
    
    /**
     * Get WordPress environment information for MCP server
     * 
     * @return array WordPress environment data
     */
    public function get_wordpress_environment_info() {
        return array(
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
            'rest_api_enabled' => get_option('woocommerce_api_enabled') === 'yes',
            'permalink_structure' => get_option('permalink_structure'),
            'timezone' => get_option('timezone_string') ?: get_option('gmt_offset'),
            'language' => get_locale()
        );
    }
    
    /**
     * Validate MCP server requirements
     * 
     * @return array Validation result
     */
    public function validate_mcp_requirements() {
        $requirements = array();
        
        // Check WooCommerce
        $requirements['woocommerce'] = array(
            'status' => class_exists('WooCommerce'),
            'message' => class_exists('WooCommerce') ? 'WooCommerce is active' : 'WooCommerce not found'
        );
        
        // Check WooCommerce API
        $requirements['woocommerce_api'] = array(
            'status' => get_option('woocommerce_api_enabled') === 'yes',
            'message' => get_option('woocommerce_api_enabled') === 'yes' ? 'WooCommerce API is enabled' : 'WooCommerce API is disabled'
        );
        
        // Check SSL (recommended for production)
        $requirements['ssl'] = array(
            'status' => is_ssl(),
            'message' => is_ssl() ? 'SSL is enabled' : 'SSL is not enabled (recommended for production)',
            'required' => false
        );
        
        // Check if we can reach chatbot service
        $service_test = $this->api_manager->test_chatbot_service_connection();
        $requirements['chatbot_service'] = array(
            'status' => $service_test['success'],
            'message' => $service_test['message']
        );
        
        return $requirements;
    }
    
    /**
     * Get integration status for a chatbot (simple local check)
     * 
     * @param string $chatbot_id The chatbot ID
     * @return array Result array with integration status
     */
    public function get_integration_status($chatbot_id) {
        scwc_debug_log(' Getting integration status for chatbot: ' . $chatbot_id);
        
        // Get local integration data
        $integration_data = get_option('scwc_integration_' . $chatbot_id);
        
        if (!$integration_data) {
            scwc_debug_log(' No local integration data found - not integrated');
            return array(
                'is_integrated' => false,
                'local_exists' => false,
                'integration_data' => null
            );
        }
        
        // Check if we have a valid MCP server ID
        $mcp_server_id = $integration_data['mcp_server_id'] ?? null;
        if (!$mcp_server_id) {
            scwc_debug_log(' No MCP server ID in integration data - invalid integration');
            return array(
                'is_integrated' => false,
                'local_exists' => true,
                'integration_data' => $integration_data,
                'issue' => 'missing_mcp_server_id'
            );
        }
        
        scwc_debug_log(' Integration found with MCP server ID: ' . $mcp_server_id);
        
        // Get bubble enabled status
        $bubble_enabled = get_option('scwc_bubble_enabled_' . $chatbot_id, false);
        
        return array(
            'is_integrated' => true,
            'local_exists' => true,
            'integration_data' => $integration_data,
            'mcp_server_id' => $mcp_server_id,
            'bubble_enabled' => $bubble_enabled
        );
    }
    
    /**
     * Sync integration status by cleaning up orphaned local data
     * 
     * @param string $chatbot_id The chatbot ID
     * @return array Result array
     */
    public function cleanup_orphaned_integration($chatbot_id) {
        scwc_debug_log(' Cleaning up orphaned integration for chatbot: ' . $chatbot_id);
        
        // Remove local integration data
        delete_option('scwc_integration_' . $chatbot_id);
        delete_option('scwc_bubble_enabled_' . $chatbot_id);
        
        // Clean up any orphaned API keys
        $wc_api = new SCWC_WooCommerce_API();
        $wc_api->cleanup_orphaned_keys($chatbot_id);
        
        scwc_debug_log(' Orphaned integration cleaned up');
        return array(
            'success' => true,
            'message' => 'Orphaned integration cleaned up'
        );
    }
    
    /**
     * Clean up orphaned MCP servers for a chatbot
     * 
     * @param string $chatbot_id The chatbot ID
     * @return array Result array
     */
    public function cleanup_orphaned_servers($chatbot_id) {
        scwc_debug_log(' Cleaning up orphaned MCP servers for chatbot: ' . $chatbot_id);
        
        try {
            // Get all MCP servers for this chatbot from the service
            $user_token = get_option('scwc_user_token');
            if (!$user_token) {
                scwc_debug_log(' No user token found for orphaned server cleanup');
                return array(
                    'success' => false,
                    'message' => 'User not authenticated'
                );
            }
            
            $chatbot_service_url = defined('SCWC_CHATBOT_SERVICE_URL') ? SCWC_CHATBOT_SERVICE_URL : 'https://chatbot.supa-chat.com/api/v1';
            $response = wp_remote_get($chatbot_service_url . '/chatbots/' . $chatbot_id . '/mcp-servers', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $user_token,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                scwc_debug_log(' Failed to get MCP servers: ' . $response->get_error_message());
                return array(
                    'success' => false,
                    'message' => 'Failed to connect to chatbot service'
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['success']) || !$data['success']) {
                scwc_debug_log(' No MCP servers found or API error');
                return array(
                    'success' => true,
                    'message' => 'No orphaned servers found'
                );
            }
            
            // Delete each MCP server
            $cleanup_count = 0;
            if (isset($data['data']['servers']) && is_array($data['data']['servers'])) {
                foreach ($data['data']['servers'] as $server) {
                    if (isset($server['id'])) {
                        scwc_debug_log(' Deleting orphaned MCP server: ' . $server['id']);
                        $delete_result = $this->delete_mcp_server($server['id'], $chatbot_id);
                        scwc_debug_print($delete_result, 'Delete result:');
                        if ($delete_result['success']) {
                            $cleanup_count++;
                        }
                    }
                }
            }
            
            scwc_debug_log(" Cleaned up {$cleanup_count} orphaned MCP servers");
            return array(
                'success' => true,
                'message' => "Cleaned up {$cleanup_count} orphaned MCP servers",
                'cleanup_count' => $cleanup_count
            );
            
        } catch (Exception $e) {
            scwc_debug_log(' Orphaned server cleanup failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            );
        }
    }
}
