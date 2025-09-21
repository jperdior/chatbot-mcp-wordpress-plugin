<?php
/**
 * API Manager Class
 * 
 * Handles communication with SupaChat user service and chatbot service
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCWC_API_Manager {
    
    private $user_service_url;
    private $chatbot_service_url;
    
    public function __construct() {
        $this->user_service_url = defined('SCWC_USER_SERVICE_URL') ? SCWC_USER_SERVICE_URL : 'http://localhost:9091/api/v1';
        $this->chatbot_service_url = defined('SCWC_CHATBOT_SERVICE_URL') ? SCWC_CHATBOT_SERVICE_URL : 'http://localhost:9092/api/v1';
    }
    
    /**
     * Login to SupaChat user service
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Result array with success status and data
     */
    public function login($email, $password) {
        $url = $this->user_service_url . '/login';
        
        $body = json_encode(array(
            'email' => $email,
            'password' => $password
        ));
        
        $args = array(
            'body' => $body,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'method' => 'POST'
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($data['token'])) {
            // Store token and refresh token
            update_option('scwc_user_token', $data['token']);
            if (isset($data['refresh_token'])) {
                update_option('scwc_refresh_token', $data['refresh_token']);
            }
            
            // Get user details
            $user_details = $this->get_user_details($data['token']);
            if ($user_details['success']) {
                update_option('scwc_user_data', $user_details['data']);
            }
            
            return array(
                'success' => true,
                'data' => array(
                    'token' => $data['token'],
                    'refresh_token' => isset($data['refresh_token']) ? $data['refresh_token'] : null,
                    'user' => $user_details['success'] ? $user_details['data'] : null
                )
            );
        } else {
            $error_message = isset($data['error']) ? $data['error'] : 'Login failed';
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Initiate Google OAuth login
     * 
     * @return array Result array with Google OAuth URL
     */
    public function get_google_login_url() {
        // Create a special callback URL that will be used by the frontend to redirect back to WordPress
        $wordpress_callback = admin_url('admin.php?page=supa-chat-woocommerce&google_callback=1');
        
        // The service will redirect to frontend with tokens, and frontend should redirect to WordPress
        // We'll encode the WordPress callback in the redirect parameter
        $callback_url = 'wordpress:' . base64_encode($wordpress_callback);
        
        // For Google OAuth, we need to use the host-accessible URL since Google redirects the browser
        // The browser runs on the host, not in the container network
        // Note: Google OAuth routes are at root level (/auth/google/login), not under /api/v1
        $google_oauth_base_url = 'http://local.user.com:8000';
        $url = $google_oauth_base_url . '/auth/google/login?redirect=' . urlencode($callback_url);
        
        error_log('SCWC: Google login URL: ' . $url);
        error_log('SCWC: WordPress callback: ' . $wordpress_callback);
        error_log('SCWC: Encoded callback: ' . $callback_url);
        
        return array(
            'success' => true,
            'url' => $url
        );
    }
    
    /**
     * Handle Google OAuth callback
     * 
     * @param string $code OAuth authorization code
     * @return array Result array with tokens
     */
    public function handle_google_callback($code) {
        error_log('SCWC: Handling Google callback with code: ' . substr($code, 0, 10) . '...');
        
        // The Google callback from your service will redirect with tokens in URL
        // We'll handle this differently - the service redirects with tokens as URL params
        // So we don't need to make an API call here, just validate and store the tokens
        
        return array(
            'success' => true,
            'message' => 'Google OAuth callback received'
        );
    }
    
    /**
     * Store tokens from Google OAuth callback
     * 
     * @param string $token Access token
     * @param string $refresh_token Refresh token
     * @return array Result array
     */
    public function store_google_tokens($token, $refresh_token) {
        error_log('SCWC: Storing Google OAuth tokens');
        
        // Store tokens
        update_option('scwc_user_token', $token);
        if ($refresh_token) {
            update_option('scwc_refresh_token', $refresh_token);
        }
        
        // Get user details with the new token
        $user_details = $this->get_user_details($token);
        if ($user_details['success']) {
            update_option('scwc_user_data', $user_details['data']);
        }
        
        return array(
            'success' => true,
            'data' => array(
                'token' => $token,
                'refresh_token' => $refresh_token,
                'user' => $user_details['success'] ? $user_details['data'] : null
            )
        );
    }
    
    /**
     * Refresh the access token using the refresh token
     * 
     * @return array Result array with new tokens
     */
    public function refresh_token() {
        $refresh_token = get_option('scwc_refresh_token');
        if (!$refresh_token) {
            return array(
                'success' => false,
                'message' => 'No refresh token available'
            );
        }
        
        $url = $this->user_service_url . '/refresh-token';
        error_log('SCWC: Refreshing token at: ' . $url);
        
        $body = json_encode(array(
            'refresh_token' => $refresh_token
        ));
        
        $args = array(
            'body' => $body,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'method' => 'POST'
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $error_msg = 'Token refresh failed: ' . $response->get_error_message();
            error_log('SCWC: ' . $error_msg);
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        error_log('SCWC: Token refresh response code: ' . $response_code);
        
        if ($response_code === 200 && isset($data['token'])) {
            // Store new tokens
            update_option('scwc_user_token', $data['token']);
            if (isset($data['refresh_token'])) {
                update_option('scwc_refresh_token', $data['refresh_token']);
            }
            
            error_log('SCWC: Token refreshed successfully');
            return array(
                'success' => true,
                'data' => array(
                    'token' => $data['token'],
                    'refresh_token' => isset($data['refresh_token']) ? $data['refresh_token'] : null
                )
            );
        } else {
            $error_message = isset($data['error']) ? $data['error'] : 'Token refresh failed';
            error_log('SCWC: Token refresh failed: ' . $error_message);
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Get user details from the token
     * 
     * @param string $token JWT token
     * @return array Result array with user data
     */
    public function get_user_details($token) {
        // For now, we'll decode the JWT to get user ID, then fetch details
        // In a production environment, you might want to use a proper JWT library
        $token_parts = explode('.', $token);
        if (count($token_parts) !== 3) {
            return array(
                'success' => false,
                'message' => 'Invalid token format'
            );
        }
        
        $payload = json_decode(base64_decode($token_parts[1]), true);
        if (!isset($payload['user_id'])) {
            return array(
                'success' => false,
                'message' => 'User ID not found in token'
            );
        }
        
        $user_id = $payload['user_id'];
        $url = $this->user_service_url . '/users/' . $user_id;
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to fetch user details: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to fetch user details'
            );
        }
    }
    
    /**
     * Get user's chatbots from the chatbot service
     * 
     * @return array Result array with chatbots data
     */
    public function get_chatbots() {
        $token = get_option('scwc_user_token');
        if (!$token) {
            error_log('SCWC: No token found for get_chatbots');
            return array(
                'success' => false,
                'message' => 'Not logged in'
            );
        }
        
        // First attempt
        $result = $this->fetch_chatbots_with_token($token);
        
        // If we get a 401, try to refresh the token and retry
        if (!$result['success'] && isset($result['status_code']) && $result['status_code'] === 401) {
            error_log('SCWC: Got 401, attempting token refresh');
            $refresh_result = $this->refresh_token();
            
            if ($refresh_result['success']) {
                $new_token = $refresh_result['data']['token'];
                error_log('SCWC: Token refreshed, retrying chatbots fetch');
                $result = $this->fetch_chatbots_with_token($new_token);
            } else {
                error_log('SCWC: Token refresh failed: ' . $refresh_result['message']);
                return array(
                    'success' => false,
                    'message' => 'Authentication failed: ' . $refresh_result['message']
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Helper method to fetch chatbots with a given token
     * 
     * @param string $token JWT token
     * @return array Result array
     */
    private function fetch_chatbots_with_token($token) {
        $url = $this->chatbot_service_url . '/chatbots';
        error_log('SCWC: Fetching chatbots from: ' . $url);
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            $error_msg = 'Failed to fetch chatbots: ' . $response->get_error_message();
            error_log('SCWC: Chatbots fetch error: ' . $error_msg);
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('SCWC: Chatbots response code: ' . $response_code);
        error_log('SCWC: Chatbots response body: ' . $response_body);
        
        $data = json_decode($response_body, true);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            $error_message = isset($data['error']) ? $data['error'] : 'Failed to fetch chatbots';
            return array(
                'success' => false,
                'message' => $error_message,
                'status_code' => $response_code
            );
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function is_logged_in() {
        $token = get_option('scwc_user_token');
        return !empty($token);
    }
    
    /**
     * Get stored user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function get_user_data() {
        if (!$this->is_logged_in()) {
            return null;
        }
        
        return get_option('scwc_user_data');
    }
    
    /**
     * Logout user (clear stored data)
     */
    public function logout() {
        delete_option('scwc_user_token');
        delete_option('scwc_refresh_token');
        delete_option('scwc_user_data');
    }
    
    /**
     * Test connection to user service
     * 
     * @return array Result array
     */
    public function test_user_service_connection() {
        $url = $this->user_service_url . '/status';
        
        // Debug logging
        error_log('SCWC: Testing user service connection to: ' . $url);
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            $error_msg = 'Cannot connect to user service: ' . $response->get_error_message();
            error_log('SCWC: User service error: ' . $error_msg);
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('SCWC: User service response code: ' . $response_code);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => 'User service is reachable'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'User service returned HTTP ' . $response_code
            );
        }
    }
    
    /**
     * Test connection to chatbot service
     * 
     * @return array Result array
     */
    public function test_chatbot_service_connection() {
        $url = $this->chatbot_service_url . '/status';
        
        // Debug logging
        error_log('SCWC: Testing chatbot service connection to: ' . $url);
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            $error_msg = 'Cannot connect to chatbot service: ' . $response->get_error_message();
            error_log('SCWC: Chatbot service error: ' . $error_msg);
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('SCWC: Chatbot service response code: ' . $response_code);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => 'Chatbot service is reachable'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Chatbot service returned HTTP ' . $response_code
            );
        }
    }
    
    /**
     * Make authenticated request to chatbot service
     * 
     * @param string $endpoint API endpoint (without base URL)
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Result array
     */
    public function make_chatbot_service_request($endpoint, $method = 'GET', $data = null) {
        $token = get_option('scwc_user_token');
        if (!$token) {
            error_log('SCWC: Chatbot service request failed - No token available');
            return array(
                'success' => false,
                'message' => 'Not authenticated'
            );
        }
        
        $url = $this->chatbot_service_url . '/' . ltrim($endpoint, '/');
        error_log('SCWC: Making chatbot service request - URL: ' . $url . ', Method: ' . $method);
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30
        );
        
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
            error_log('SCWC: Request body: ' . json_encode($data));
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $error_msg = 'Request failed: ' . $response->get_error_message();
            error_log('SCWC: Chatbot service request error: ' . $error_msg);
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('SCWC: Chatbot service response - Code: ' . $response_code . ', Body: ' . $response_body);
        
        $response_data = json_decode($response_body, true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data' => $response_data,
                'status_code' => $response_code
            );
        } else {
            // Check if we got a 401 and try to refresh token
            if ($response_code === 401) {
                error_log('SCWC: Got 401 on chatbot service request, attempting token refresh');
                $refresh_result = $this->refresh_token();
                
                if ($refresh_result['success']) {
                    error_log('SCWC: Token refreshed, retrying chatbot service request');
                    // Update token and retry
                    $token = $refresh_result['data']['token'];
                    $args['headers']['Authorization'] = 'Bearer ' . $token;
                    
                    $response = wp_remote_request($url, $args);
                    if (!is_wp_error($response)) {
                        $response_code = wp_remote_retrieve_response_code($response);
                        $response_body = wp_remote_retrieve_body($response);
                        $response_data = json_decode($response_body, true);
                        
                        error_log('SCWC: Retry response - Code: ' . $response_code . ', Body: ' . $response_body);
                        
                        if ($response_code >= 200 && $response_code < 300) {
                            return array(
                                'success' => true,
                                'data' => $response_data,
                                'status_code' => $response_code
                            );
                        }
                    }
                }
            }
            
            $error_message = isset($response_data['error']) ? $response_data['error'] : 'Request failed';
            error_log('SCWC: Chatbot service request failed - ' . $error_message . ' (HTTP ' . $response_code . ')');
            return array(
                'success' => false,
                'message' => $error_message,
                'status_code' => $response_code
            );
        }
    }

    /**
     * Get MCP server details by ID
     */
    public function get_mcp_server($chatbot_id, $mcp_server_id) {
        $token = get_option('scwc_user_token');
        if (!$token) {
            return array('success' => false, 'message' => 'Not authenticated');
        }

        $url = $this->chatbot_service_url . '/chatbots/' . $chatbot_id . '/mcp-servers/' . $mcp_server_id;
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('SCWC: Get MCP server request failed - ' . $response->get_error_message());
            return array('success' => false, 'message' => 'Request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('SCWC: Get MCP server response - Code: ' . $response_code . ', Body: ' . $body);

        if ($response_code === 401) {
            // Try to refresh token and retry
            $refresh_result = $this->refresh_token();
            if ($refresh_result['success']) {
                return $this->get_mcp_server($chatbot_id, $mcp_server_id); // Retry with new token
            }
            return array('success' => false, 'message' => 'Authentication failed');
        }

        if ($response_code !== 200) {
            return array('success' => false, 'message' => 'Request failed (HTTP ' . $response_code . ')');
        }

        $data = json_decode($body, true);
        if (!$data) {
            return array('success' => false, 'message' => 'Invalid response format');
        }

        return array('success' => true, 'data' => $data);
    }
}
