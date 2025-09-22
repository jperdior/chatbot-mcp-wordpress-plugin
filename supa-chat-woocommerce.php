<?php
/*
Plugin Name: SupaChat
Plugin URI: https://supa-chat-woocommerce.com
Description: Automatically integrate your site with SupaChat AI chatbots. Provides seamless integration for enhanced customer support.
Version: 1.0.0
Author: SupaChat
Author URI: https://supa-chat-woocommerce.com
Text Domain: supa-chat-woocommerce
Requires at least: 5.6
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCWC_PLUGIN_VERSION', '1.0.0');
define('SCWC_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
define('SCWC_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

/**
 * Debug logging function - only logs when WP_DEBUG is enabled
 */
function scwc_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
        error_log('SCWC: ' . $message);
    }
}

/**
 * Debug print function - only logs arrays when WP_DEBUG is enabled
 */
function scwc_debug_print($data, $label = '') {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging only when WP_DEBUG is enabled
        error_log('SCWC: ' . $label . ' ' . print_r($data, true));
    }
}

// Default service URLs
define('SCWC_USER_SERVICE_URL', 'https://user.supa-chat.com/api/v1');
define('SCWC_CHATBOT_SERVICE_URL', 'https://chatbot.supa-chat.com/api/v1');

/**
 * Main plugin class
 */
class SupaChatWooCommercePlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin components
        $this->load_includes();
        $this->init_hooks();
    }
    
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('SupaChat requires WooCommerce to be installed and active.', 'supa-chat-woocommerce'); ?></p>
        </div>
        <?php
    }
    
    private function load_includes() {
        require_once SCWC_PLUGIN_DIR_PATH . 'includes/class-admin.php';
        require_once SCWC_PLUGIN_DIR_PATH . 'includes/class-api-manager.php';
        require_once SCWC_PLUGIN_DIR_PATH . 'includes/class-woocommerce-api.php';
        require_once SCWC_PLUGIN_DIR_PATH . 'includes/class-mcp-manager.php';
    }
    
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_scwc_login', array($this, 'handle_login'));
        add_action('wp_ajax_scwc_google_login', array($this, 'handle_google_login'));
        add_action('wp_ajax_scwc_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_scwc_setup_integration', array($this, 'handle_setup_integration'));
        add_action('wp_ajax_scwc_get_chatbots', array($this, 'handle_get_chatbots'));
        add_action('wp_ajax_scwc_delete_integration', array($this, 'handle_delete_integration'));
        add_action('wp_ajax_scwc_check_integration', array($this, 'handle_check_integration'));
        add_action('wp_ajax_scwc_get_mcp_server', array($this, 'handle_get_mcp_server'));
        add_action('wp_ajax_scwc_save_website_integration', array($this, 'handle_save_website_integration'));
        
        // Handle Google OAuth callback
        add_action('admin_init', array($this, 'handle_google_oauth_callback'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register shortcode
        add_shortcode('supa-chat-woocommerce', array($this, 'render_chatbot_shortcode'));
        
        // Add bubble chat to frontend if enabled
        add_action('wp_footer', array($this, 'add_bubble_chat'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SupaChat', 'supa-chat-woocommerce'),
            __('SupaChat', 'supa-chat-woocommerce'),
            'manage_options',
            'supa-chat-woocommerce',
            array($this, 'admin_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>'),
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        scwc_debug_log(' enqueue_admin_scripts called with hook: ' . $hook);
        
        if ('toplevel_page_supa-chat-woocommerce' !== $hook) {
            scwc_debug_log(' Hook mismatch, expected toplevel_page_supa-chat-woocommerce, got: ' . $hook);
            return;
        }
        
        scwc_debug_log(' Enqueuing admin scripts and styles');
        
        wp_enqueue_style('scwc-admin-css', SCWC_PLUGIN_DIR_URL . 'assets/css/admin.css', array(), SCWC_PLUGIN_VERSION);
        wp_enqueue_script('scwc-admin-js', SCWC_PLUGIN_DIR_URL . 'assets/js/admin.js', array('jquery'), SCWC_PLUGIN_VERSION, true);
        
        // Localize script
        wp_localize_script('scwc-admin-js', 'scwc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scwc_nonce'),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'supa-chat-woocommerce'),
                'success' => __('Operation completed successfully.', 'supa-chat-woocommerce'),
                'loading' => __('Loading...', 'supa-chat-woocommerce'),
                'confirm_delete' => __('Are you sure you want to delete this integration?', 'supa-chat-woocommerce'),
            )
        ));
    }
    
    public function admin_page() {
        $admin = new SCWC_Admin();
        $admin->render_page();
    }
    
    public function handle_login() {
        scwc_debug_log(' handle_login called');
        
        check_ajax_referer('scwc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            scwc_debug_log(' Login failed - insufficient permissions');
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = sanitize_text_field(wp_unslash($_POST['password'] ?? ''));
        
        scwc_debug_log(' Login attempt for email: ' . $email);
        
        if (empty($email) || empty($password)) {
            scwc_debug_log(' Login failed - missing email or password');
            wp_send_json_error('Email and password are required');
            return;
        }
        
        $api_manager = new SCWC_API_Manager();
        scwc_debug_log(' Calling API manager login');
        $result = $api_manager->login($email, $password);
        scwc_debug_print($result, "");
        
        if ($result['success']) {
            scwc_debug_log(' Login successful for: ' . $email);
            wp_send_json_success($result['data']);
        } else {
            scwc_debug_log(' Login failed for: ' . $email . ' - ' . $result['message']);
            wp_send_json_error($result['message']);
        }
    }
    
    public function handle_logout() {
        check_ajax_referer('scwc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        delete_option('scwc_user_token');
        delete_option('scwc_user_data');
        
        wp_send_json_success('Logged out successfully');
    }
    
    public function handle_setup_integration() {
        scwc_debug_log(' handle_setup_integration called');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Debug logging only, nonce verified below
        scwc_debug_print($_POST, "");
        
        check_ajax_referer('scwc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            scwc_debug_log(' Integration setup failed - Insufficient permissions');
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $chatbot_id = sanitize_text_field(wp_unslash($_POST['chatbot_id'] ?? ''));
        $integration_name = sanitize_text_field(wp_unslash($_POST['integration_name'] ?? ''));
        
        scwc_debug_log(' Starting integration setup - Chatbot ID: ' . $chatbot_id . ', Name: ' . $integration_name);
        
        if (empty($chatbot_id) || empty($integration_name)) {
            scwc_debug_log(' Integration setup failed - Missing required fields');
            wp_send_json_error('Chatbot ID and integration name are required');
            return;
        }
        
        try {
            // Generate WooCommerce API keys
            scwc_debug_log(' Generating WooCommerce API keys');
            $wc_api = new SCWC_WooCommerce_API();
            $api_keys = $wc_api->generate_api_keys($integration_name);
            
            if (!$api_keys['success']) {
                scwc_debug_log(' API key generation failed: ' . $api_keys['message']);
                wp_send_json_error($api_keys['message']);
                return;
            }
            
            scwc_debug_log(' API keys generated successfully - Key ID: ' . $api_keys['key_id']);
            
            // Create MCP server
            scwc_debug_log(' Creating MCP server for chatbot: ' . $chatbot_id);
            $mcp_manager = new SCWC_MCP_Manager();
            $mcp_result = $mcp_manager->create_wordpress_mcp_server(
                $chatbot_id,
                $integration_name,
                get_site_url(),
                $api_keys['consumer_key'],
                $api_keys['consumer_secret']
            );
            
            if (!$mcp_result['success']) {
                scwc_debug_log(' MCP server creation failed: ' . $mcp_result['message']);
                // If MCP creation fails, clean up the API keys
                $wc_api->delete_api_key($api_keys['key_id']);
                wp_send_json_error($mcp_result['message']);
                return;
            }
            
            scwc_debug_log(' MCP server created successfully - Server ID: ' . $mcp_result['server_id']);
            
            // Store integration data
            $integration_data = array(
                'chatbot_id' => $chatbot_id,
                'name' => $integration_name,
                'mcp_server_id' => $mcp_result['server_id'],
                'api_key_id' => $api_keys['key_id'],
                'consumer_key' => $api_keys['consumer_key'],
                'created_at' => current_time('mysql'),
                'status' => 'active'
            );
            
            update_option('scwc_integration_' . $chatbot_id, $integration_data);
            
            wp_send_json_success(array(
                'message' => 'Integration setup successfully',
                'integration' => $integration_data,
                'mcp_server_id' => $mcp_result['server_id'],
                'mcp_server_url' => $mcp_result['mcp_server_url']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Setup failed: ' . $e->getMessage());
        }
    }
    
    public function handle_google_login() {
        check_ajax_referer('scwc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $api_manager = new SCWC_API_Manager();
        $result = $api_manager->get_google_login_url();
        
        if ($result['success']) {
            wp_send_json_success(array('url' => $result['url']));
        } else {
            wp_send_json_error('Failed to get Google login URL');
        }
    }
    
    public function handle_google_oauth_callback() {
        // Check if this is a Google OAuth callback
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable
        if (!isset($_GET['google_callback'])) {
            return;
        }
        
        // Verify we're on the right page
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable
        if (!isset($_GET['page']) || $_GET['page'] !== 'supa-chat-woocommerce') {
            return;
        }
        
        // Check if we have tokens from the frontend redirect
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable
        if (!isset($_GET['token'])) {
            // Redirect to admin page with error message
            $redirect_url = admin_url('admin.php?page=supa-chat-woocommerce&google_login=error&message=' . urlencode('No authentication tokens received'));
            wp_redirect($redirect_url);
            exit;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable
        $token = sanitize_text_field(wp_unslash($_GET['token']));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable
        $refresh_token = isset($_GET['refresh_token']) ? sanitize_text_field(wp_unslash($_GET['refresh_token'])) : '';
        
        scwc_debug_log(' Google OAuth callback - Token received: ' . substr($token, 0, 20) . '...');
        scwc_debug_log(' Google OAuth callback - Refresh token: ' . ($refresh_token ? 'Yes' : 'No'));
        
        $api_manager = new SCWC_API_Manager();
        $result = $api_manager->store_google_tokens($token, $refresh_token);
        
        if ($result['success']) {
            // Redirect to admin page with success message
            $redirect_url = admin_url('admin.php?page=supa-chat-woocommerce&google_login=success');
            wp_redirect($redirect_url);
            exit;
        } else {
            // Redirect to admin page with error message
            $redirect_url = admin_url('admin.php?page=supa-chat-woocommerce&google_login=error&message=' . urlencode($result['message']));
        }
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Check if a chatbot is integrated
     */
    public function handle_check_integration() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'scwc_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $chatbot_id = isset($_POST['chatbot_id']) ? sanitize_text_field(wp_unslash($_POST['chatbot_id'])) : '';
        if (empty($chatbot_id)) {
            wp_send_json_error('Chatbot ID is required');
            return;
        }

        $integration_data = get_option('scwc_integration_' . $chatbot_id);
        
        if ($integration_data && $integration_data['status'] === 'active') {
            wp_send_json_success(array(
                'integrated' => true,
                'integration' => $integration_data
            ));
        } else {
            wp_send_json_success(array(
                'integrated' => false
            ));
        }
    }

    /**
     * Get MCP server details
     */
    public function handle_get_mcp_server() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'scwc_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $chatbot_id = isset($_POST['chatbot_id']) ? sanitize_text_field(wp_unslash($_POST['chatbot_id'])) : '';
        $mcp_server_id = isset($_POST['mcp_server_id']) ? sanitize_text_field(wp_unslash($_POST['mcp_server_id'])) : '';
        
        if (empty($chatbot_id) || empty($mcp_server_id)) {
            wp_send_json_error('Chatbot ID and MCP Server ID are required');
            return;
        }

        $api_manager = new SCWC_API_Manager();
        $result = $api_manager->get_mcp_server($chatbot_id, $mcp_server_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function handle_get_chatbots() {
        scwc_debug_log(' handle_get_chatbots called');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Debug logging only, nonce verified below
        scwc_debug_print($_POST, "");
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'scwc_nonce')) {
            scwc_debug_log(' Invalid nonce in get_chatbots');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            scwc_debug_log(' Insufficient permissions in get_chatbots');
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        scwc_debug_log(' Getting chatbots from API');
        
        $api_manager = new SCWC_API_Manager();
        scwc_debug_log(' API Manager created, calling get_chatbots()');
        
        $result = $api_manager->get_chatbots();
        scwc_debug_print($result, "");
        
        if ($result['success']) {
            // Extract the chatbots array from the API response
            $chatbots = array();
            if (isset($result['data']['Chatbots']) && is_array($result['data']['Chatbots'])) {
                $chatbots = $result['data']['Chatbots'];
                scwc_debug_log(' Found ' . count($chatbots) . ' chatbots');
                
                // Get integration status for each chatbot (simple local check)
                scwc_debug_log(' === CHECKING INTEGRATION STATUS ===');
                $mcp_manager = new SCWC_MCP_Manager();
                
                foreach ($chatbots as &$chatbot) {
                    $chatbot_id = $chatbot['id'] ?? null;
                    if ($chatbot_id) {
                        scwc_debug_log(' Checking integration status for chatbot: ' . $chatbot_id);
                        $integration_status = $mcp_manager->get_integration_status($chatbot_id);
                        
                        // Add integration info to chatbot data for frontend
                        $chatbot['integration_status'] = $integration_status;
                        scwc_debug_log(' Chatbot ' . $chatbot_id . ' integration status: ' . ($integration_status['is_integrated'] ? 'INTEGRATED' : 'AVAILABLE'));
                    }
                }
                unset($chatbot); // Break reference
                
                scwc_debug_log(' Integration status check completed');
            } else {
                scwc_debug_log(' No chatbots found in response data structure');
                scwc_debug_print(array_keys($result['data'] ?? []), "");
            }
            wp_send_json_success($chatbots);
        } else {
            scwc_debug_log(' Failed to get chatbots: ' . $result['message']);
            wp_send_json_error($result['message']);
        }
    }
    
    public function handle_delete_integration() {
        scwc_debug_log('=== DELETE INTEGRATION STARTED ===');
        scwc_debug_print($_POST, 'POST data received:');
        
        check_ajax_referer('scwc_nonce', 'nonce');
        scwc_debug_log(' Nonce verification passed');
        
        if (!current_user_can('manage_options')) {
            scwc_debug_log(' Permission check failed - user cannot manage options');
            wp_send_json_error('Insufficient permissions');
            return;
        }
        scwc_debug_log(' Permission check passed');
        
        $chatbot_id = isset($_POST['chatbot_id']) ? sanitize_text_field(wp_unslash($_POST['chatbot_id'])) : '';
        scwc_debug_log(' Chatbot ID extracted: ' . $chatbot_id);
        
        if (empty($chatbot_id)) {
            scwc_debug_log(' ERROR: Chatbot ID is empty');
            wp_send_json_error('Chatbot ID is required');
            return;
        }
        
        $integration_data = get_option('scwc_integration_' . $chatbot_id);
        $is_orphaned = !$integration_data;
        
        scwc_debug_log(' Integration data check - Found: ' . ($integration_data ? 'YES' : 'NO'));
        scwc_debug_log(' Is orphaned: ' . ($is_orphaned ? 'YES' : 'NO'));
        
        if ($integration_data) {
            scwc_debug_print($integration_data, 'Integration data:');
        }
        
        try {
            if ($integration_data) {
                // Normal case: we have local integration data
                scwc_debug_log(' === NORMAL CLEANUP PATH ===');
                scwc_debug_log(' Found local integration data, performing normal cleanup');
                
                // Delete MCP server
                $mcp_manager = new SCWC_MCP_Manager();
                $delete_result = $mcp_manager->delete_mcp_server($integration_data['mcp_server_id'], $chatbot_id);
                scwc_debug_print($delete_result, 'MCP server deletion result:');
                
                // Delete WooCommerce API key
                $wc_api = new SCWC_WooCommerce_API();
                $wc_api->delete_api_key($integration_data['api_key_id']);
                
                // Remove integration data
                delete_option('scwc_integration_' . $chatbot_id);
                
                scwc_debug_log(' Integration cleanup completed successfully');
                wp_send_json_success('Integration deleted successfully');
                
            } else {
                // Orphaned case: no local data but chatbot shows as integrated
                scwc_debug_log(' === ORPHANED CLEANUP PATH ===');
                scwc_debug_log(' No local integration data found (orphaned integration), attempting cleanup');
                
                // Try to delete any orphaned MCP servers for this chatbot
                scwc_debug_log(' Creating MCP Manager for orphaned cleanup');
                $mcp_manager = new SCWC_MCP_Manager();
                scwc_debug_log(' Calling cleanup_orphaned_servers for chatbot: ' . $chatbot_id);
                $cleanup_result = $mcp_manager->cleanup_orphaned_servers($chatbot_id);
                scwc_debug_print($cleanup_result, 'MCP cleanup result:');
                
                // Clean up any orphaned API keys
                scwc_debug_log(' Creating WooCommerce API for orphaned key cleanup');
                $wc_api = new SCWC_WooCommerce_API();
                scwc_debug_log(' Calling cleanup_orphaned_keys for chatbot: ' . $chatbot_id);
                $key_cleanup_result = $wc_api->cleanup_orphaned_keys($chatbot_id);
                scwc_debug_print($key_cleanup_result, 'API key cleanup result:');
                
                // Remove any orphaned settings
                scwc_debug_log(' Removing orphaned WordPress options');
                delete_option('scwc_integration_' . $chatbot_id);
                delete_option('scwc_bubble_enabled_' . $chatbot_id);
                
                scwc_debug_log(' Orphaned integration cleanup completed successfully');
                wp_send_json_success('Orphaned integration cleaned up successfully');
            }
            
        } catch (Exception $e) {
            scwc_debug_log(' Integration deletion failed: ' . $e->getMessage());
            wp_send_json_error('Delete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle saving website integration settings
     */
    public function handle_save_website_integration() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'scwc_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $chatbot_id = isset($_POST['chatbot_id']) ? sanitize_text_field(wp_unslash($_POST['chatbot_id'])) : '';
        $bubble_enabled = isset($_POST['bubble_enabled']) && $_POST['bubble_enabled'] === 'true';

        if (empty($chatbot_id)) {
            wp_send_json_error('Chatbot ID is required');
            return;
        }

        // Save bubble setting
        update_option('scwc_bubble_enabled_' . $chatbot_id, $bubble_enabled);

        wp_send_json_success('Website integration settings saved');
    }
    
    /**
     * Render chatbot shortcode
     */
    public function render_chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'chatbot' => '',
            'width' => '100%',
            'height' => '600px',
            'title' => 'SupaChat Assistant'
        ), $atts, 'supa-chat-woocommerce');

        if (empty($atts['chatbot'])) {
            return '<p><strong>SupaChat Error:</strong> Chatbot ID is required. Usage: [supa-chat-woocommerce chatbot="your-chatbot-id"]</p>';
        }

        $chatbot_id = esc_attr($atts['chatbot']);
        $width = esc_attr($atts['width']);
        $height = esc_attr($atts['height']);
        $title = esc_attr($atts['title']);

        // Generate iframe URL for the chatbot
        $iframe_url = "https://chatbot.supa-chat.com/chat/{$chatbot_id}";

        return sprintf(
            '<div class="supa-chat-woocommerce-iframe-container" style="width: %s; height: %s; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                <iframe src="%s" width="100%%" height="100%%" frameborder="0" title="%s" style="border: none;"></iframe>
            </div>',
            $width,
            $height,
            esc_url($iframe_url),
            $title
        );
    }
    
    /**
     * Add bubble chat to frontend if enabled
     */
    public function add_bubble_chat() {
        // Get all enabled bubble chats
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for finding enabled bubble chats
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'scwc_bubble_enabled_%' AND option_value = '1'"
        );

        foreach ($options as $option) {
            if ($option->option_value === '1') {
                // Extract chatbot ID from option name
                $chatbot_id = str_replace('scwc_bubble_enabled_', '', $option->option_name);
                
                // Output bubble chat script
                echo wp_kses($this->generate_bubble_script($chatbot_id), array(
                    'script' => array('type' => array()),
                    'div' => array(),
                )) . "\n";
                break; // Only show one bubble at a time
            }
        }
    }
    
    /**
     * Generate bubble chat script
     */
    private function generate_bubble_script($chatbot_id) {
        return "
        <!-- SupaChat Bubble Integration -->
        <script type=\"text/javascript\">
        (function(){
            window.SupaChatConfig={chatbotId:\"" . esc_js($chatbot_id) . "\"};
            var d=document,s=d.createElement(\"script\");
            s.src=\"https://widget.supa-chat.com/widget.js\";
            s.async=1;
            d.head.appendChild(s);
        })();
        </script>";
    }
}

// Plugin activation
register_activation_hook(__FILE__, 'scwc_activate_plugin');
function scwc_activate_plugin() {
    // Check requirements
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('SupaChat requires WooCommerce to be installed and active.', 'supa-chat-woocommerce'));
    }
    
    // Create database tables or options if needed
    // For now, we'll use WordPress options
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'scwc_deactivate_plugin');
function scwc_deactivate_plugin() {
    // Clean up if needed
    // Note: We don't delete integrations on deactivation, only on uninstall
}

// Plugin uninstall
register_uninstall_hook(__FILE__, 'scwc_uninstall_plugin');
function scwc_uninstall_plugin() {
    // Clean up all plugin data
    delete_option('scwc_user_token');
    delete_option('scwc_user_data');
    
    // Delete all integration data
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for plugin cleanup on uninstall
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'scwc_integration_%'");
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    SupaChatWooCommercePlugin::get_instance();
});
