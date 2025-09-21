<?php
/**
 * Admin Interface Class
 * 
 * Handles the WordPress admin interface for SupaChat WooCommerce integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCWC_Admin {
    
    private $api_manager;
    private $mcp_manager;
    
    public function __construct() {
        $this->api_manager = new SCWC_API_Manager();
        $this->mcp_manager = new SCWC_MCP_Manager();
    }
    
    /**
     * Render the main admin page
     */
    public function render_page() {
        $is_logged_in = $this->api_manager->is_logged_in();
        $user_data = $this->api_manager->get_user_data();
        
        ?>
        <div class="wrap">
            <h1><?php _e('SupaChat WooCommerce Integration', 'supa-chat-woocommerce'); ?></h1>
            
            <div id="scwc-admin-container">
                <?php if (!$is_logged_in): ?>
                    <?php $this->render_login_form(); ?>
                <?php else: ?>
                    <?php $this->render_dashboard($user_data); ?>
                <?php endif; ?>
            </div>
            
            <div id="scwc-loading" style="display: none;">
                <div class="scwc-loading-spinner"></div>
                <p><?php _e('Loading...', 'supa-chat-woocommerce'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render login form
     */
    private function render_login_form() {
        ?>
        <div class="scwc-card">
            <h2><?php _e('Connect to SupaChat', 'supa-chat-woocommerce'); ?></h2>
            <p><?php _e('Login to your SupaChat account to start integrating your WooCommerce store with AI chatbots.', 'supa-chat-woocommerce'); ?></p>
            
            <?php $this->render_service_status(); ?>
            
            <div class="scwc-login-options">
                <div class="scwc-google-login" style="margin-bottom: 20px; text-align: center;">
                    <button type="button" id="scwc-google-login" class="button button-primary button-large" style="padding: 10px 20px; font-size: 16px;">
                        <span class="dashicons dashicons-google" style="margin-right: 8px; vertical-align: middle;"></span>
                        <?php _e('Sign in with Google', 'supa-chat-woocommerce'); ?>
                    </button>
                </div>
                
                <div class="scwc-divider" style="text-align: center; margin: 20px 0; position: relative;">
                    <hr style="border: none; border-top: 1px solid #ddd;">
                    <span style="background: white; padding: 0 15px; color: #666; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);">
                        <?php _e('or', 'supa-chat-woocommerce'); ?>
                    </span>
                </div>
                
                <div class="scwc-email-login">
                    <h3 style="margin-bottom: 15px;"><?php _e('Sign in with Email & Password', 'supa-chat-woocommerce'); ?></h3>
                    <form id="scwc-login-form" method="post">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="scwc-email"><?php _e('Email', 'supa-chat-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <input type="email" id="scwc-email" name="email" class="regular-text" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="scwc-password"><?php _e('Password', 'supa-chat-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" id="scwc-password" name="password" class="regular-text" required />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-secondary">
                                <?php _e('Connect to SupaChat', 'supa-chat-woocommerce'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="scwc-help">
                <h3><?php _e('Need a SupaChat Account?', 'supa-chat-woocommerce'); ?></h3>
                <p><?php _e('Sign up for a free SupaChat account to get started with AI-powered customer support.', 'supa-chat-woocommerce'); ?></p>
                <a href="https://supachat.com/signup" target="_blank" class="button">
                    <?php _e('Sign Up for SupaChat', 'supa-chat-woocommerce'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard for logged-in users
     */
    private function render_dashboard($user_data) {
        ?>
        <div class="scwc-dashboard">
            <div class="scwc-header">
                <div class="scwc-user-info">
                    <h2><?php printf(__('Welcome, %s!', 'supa-chat-woocommerce'), esc_html($user_data['name'] ?? $user_data['email'] ?? 'User')); ?></h2>
                    <p><?php _e('Manage your WooCommerce chatbot integrations', 'supa-chat-woocommerce'); ?></p>
                </div>
                <div class="scwc-actions">
                    <button id="scwc-logout" class="button">
                        <?php _e('Logout', 'supa-chat-woocommerce'); ?>
                    </button>
                </div>
            </div>
            
            <?php $this->render_environment_status(); ?>
            
            <div class="scwc-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#" class="nav-tab nav-tab-active" data-tab="integrations">
                        <?php _e('Integrations', 'supa-chat-woocommerce'); ?>
                    </a>
                    <a href="#" class="nav-tab" data-tab="settings">
                        <?php _e('Settings', 'supa-chat-woocommerce'); ?>
                    </a>
                    <a href="#" class="nav-tab" data-tab="logs">
                        <?php _e('Logs', 'supa-chat-woocommerce'); ?>
                    </a>
                </nav>
                
                <div id="tab-integrations" class="tab-content active">
                    <?php $this->render_integrations_tab(); ?>
                </div>
                
                <div id="tab-settings" class="tab-content">
                    <?php $this->render_settings_tab(); ?>
                </div>
                
                <div id="tab-logs" class="tab-content">
                    <?php $this->render_logs_tab(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render service status indicators
     */
    private function render_service_status() {
        $user_service_status = $this->api_manager->test_user_service_connection();
        $chatbot_service_status = $this->api_manager->test_chatbot_service_connection();
        
        ?>
        <div class="scwc-service-status">
            <h3><?php _e('Service Status', 'supa-chat-woocommerce'); ?></h3>
            <div class="scwc-status-grid">
                <div class="scwc-status-item <?php echo $user_service_status['success'] ? 'success' : 'error'; ?>">
                    <span class="dashicons <?php echo $user_service_status['success'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <div>
                        <strong><?php _e('User Service', 'supa-chat-woocommerce'); ?></strong>
                        <p><?php echo esc_html($user_service_status['message']); ?></p>
                    </div>
                </div>
                <div class="scwc-status-item <?php echo $chatbot_service_status['success'] ? 'success' : 'error'; ?>">
                    <span class="dashicons <?php echo $chatbot_service_status['success'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <div>
                        <strong><?php _e('Chatbot Service', 'supa-chat-woocommerce'); ?></strong>
                        <p><?php echo esc_html($chatbot_service_status['message']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render environment status
     */
    private function render_environment_status() {
        $requirements = $this->mcp_manager->validate_mcp_requirements();
        $env_info = $this->mcp_manager->get_wordpress_environment_info();
        
        ?>
        <div class="scwc-environment-status">
            <h3><?php _e('Environment Status', 'supa-chat-woocommerce'); ?></h3>
            <div class="scwc-status-grid">
                <?php foreach ($requirements as $key => $requirement): ?>
                    <div class="scwc-status-item <?php echo $requirement['status'] ? 'success' : (isset($requirement['required']) && !$requirement['required'] ? 'warning' : 'error'); ?>">
                        <span class="dashicons <?php echo $requirement['status'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                        <div>
                            <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></strong>
                            <p><?php echo esc_html($requirement['message']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <details class="scwc-env-details">
                <summary><?php _e('Environment Details', 'supa-chat-woocommerce'); ?></summary>
                <dl class="scwc-env-list">
                    <?php foreach ($env_info as $key => $value): ?>
                        <dt><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></dt>
                        <dd><?php echo esc_html($value); ?></dd>
                    <?php endforeach; ?>
                </dl>
            </details>
        </div>
        <?php
    }
    
    /**
     * Render integrations tab
     */
    /**
     * Render integrations tab
     */
    private function render_integrations_tab() {
        ?>
        <div class="scwc-integrations">
            <div class="scwc-integration-header">
                <h3><?php _e('WordPress Integration', 'supa-chat-woocommerce'); ?></h3>
                <p class="description">
                    <?php _e('Connect your chatbot to WordPress to enable AI-powered customer support with access to your products, posts, and store information.', 'supa-chat-woocommerce'); ?>
                </p>
            </div>

            <div class="scwc-integration-info">
                <div class="scwc-info-box">
                    <h4><?php _e('How it works:', 'supa-chat-woocommerce'); ?></h4>
                    <ul>
                        <li><?php _e('Creates a secure connection between your WordPress site and your chatbot', 'supa-chat-woocommerce'); ?></li>
                        <li><?php _e('Enables your chatbot to answer questions about products, posts, and store information', 'supa-chat-woocommerce'); ?></li>
                        <li><?php _e('Only performs READ operations - your data remains secure and unchanged', 'supa-chat-woocommerce'); ?></li>
                        <li><?php _e('Automatically generates WooCommerce API credentials for the current admin user', 'supa-chat-woocommerce'); ?></li>
                    </ul>
                </div>
                
                <div class="scwc-warning-box">
                    <h4><?php _e('Important:', 'supa-chat-woocommerce'); ?></h4>
                    <p><?php _e('This integration should be set up by a WordPress administrator as it will create WooCommerce API keys for the current user account.', 'supa-chat-woocommerce'); ?></p>
                </div>
            </div>

            <div class="scwc-chatbot-selection">
                <h4><?php _e('Select Chatbot to Integrate', 'supa-chat-woocommerce'); ?></h4>
                <div id="scwc-chatbots-container">
                    <div class="scwc-loading-chatbots">
                        <div class="scwc-loading-spinner"></div>
                        <p><?php _e('Loading your chatbots...', 'supa-chat-woocommerce'); ?></p>
                    </div>
                </div>
            </div>

            <div id="scwc-integration-form-container" style="display: none;">
                <form id="scwc-integration-form">
                    <input type="hidden" id="selected-chatbot-id" name="chatbot_id" />
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="integration-name"><?php _e('Integration Name', 'supa-chat-woocommerce'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="integration-name" name="integration_name" class="regular-text" 
                                           placeholder="<?php _e('e.g., My Store Integration', 'supa-chat-woocommerce'); ?>" required />
                                    <p class="description"><?php _e('A descriptive name for this integration', 'supa-chat-woocommerce'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="scwc-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <?php _e('Create Integration', 'supa-chat-woocommerce'); ?>
                        </button>
                        <button type="button" id="scwc-cancel-integration" class="button">
                            <?php _e('Cancel', 'supa-chat-woocommerce'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div id="scwc-integration-status" style="display: none;">
                <div class="scwc-success-box">
                    <h4><?php _e('Integration Active', 'supa-chat-woocommerce'); ?></h4>
                    <p id="scwc-integration-details"></p>
                    <div class="scwc-integration-actions">
                        <button type="button" id="scwc-remove-integration" class="button button-secondary">
                            <?php _e('Remove Integration', 'supa-chat-woocommerce'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings tab
     */
    private function render_settings_tab() {
        ?>
        <div class="scwc-settings">
            <h3><?php _e('Plugin Settings', 'supa-chat-woocommerce'); ?></h3>
            
            <form id="scwc-settings-form" method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="user-service-url"><?php _e('User Service URL', 'supa-chat-woocommerce'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="user-service-url" name="user_service_url" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('scwc_user_service_url', SCWC_USER_SERVICE_URL)); ?>" />
                                <p class="description"><?php _e('URL of the SupaChat user service API', 'supa-chat-woocommerce'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="chatbot-service-url"><?php _e('Chatbot Service URL', 'supa-chat-woocommerce'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="chatbot-service-url" name="chatbot_service_url" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('scwc_chatbot_service_url', SCWC_CHATBOT_SERVICE_URL)); ?>" />
                                <p class="description"><?php _e('URL of the SupaChat chatbot service API', 'supa-chat-woocommerce'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto-cleanup"><?php _e('Auto Cleanup', 'supa-chat-woocommerce'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto-cleanup" name="auto_cleanup" value="1" 
                                           <?php checked(get_option('scwc_auto_cleanup', 1)); ?> />
                                    <?php _e('Automatically clean up API keys and MCP servers when integrations are deleted', 'supa-chat-woocommerce'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Settings', 'supa-chat-woocommerce'); ?>
                    </button>
                </p>
            </form>
            
            <div class="scwc-danger-zone">
                <h3><?php _e('Danger Zone', 'supa-chat-woocommerce'); ?></h3>
                <p><?php _e('These actions are irreversible. Use with caution.', 'supa-chat-woocommerce'); ?></p>
                
                <button id="scwc-cleanup-all" class="button button-secondary">
                    <?php _e('Clean Up All Integrations', 'supa-chat-woocommerce'); ?>
                </button>
                
                <button id="scwc-reset-plugin" class="button button-secondary">
                    <?php _e('Reset Plugin Data', 'supa-chat-woocommerce'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render logs tab
     */
    private function render_logs_tab() {
        ?>
        <div class="scwc-logs">
            <div class="scwc-section-header">
                <h3><?php _e('Integration Logs', 'supa-chat-woocommerce'); ?></h3>
                <button id="scwc-refresh-logs" class="button">
                    <?php _e('Refresh', 'supa-chat-woocommerce'); ?>
                </button>
            </div>
            
            <div class="scwc-log-filters">
                <select id="scwc-log-level">
                    <option value=""><?php _e('All Levels', 'supa-chat-woocommerce'); ?></option>
                    <option value="info"><?php _e('Info', 'supa-chat-woocommerce'); ?></option>
                    <option value="warning"><?php _e('Warning', 'supa-chat-woocommerce'); ?></option>
                    <option value="error"><?php _e('Error', 'supa-chat-woocommerce'); ?></option>
                </select>
                
                <input type="date" id="scwc-log-date" />
                
                <button id="scwc-clear-logs" class="button button-secondary">
                    <?php _e('Clear Logs', 'supa-chat-woocommerce'); ?>
                </button>
            </div>
            
            <div id="scwc-logs-container">
                <p><?php _e('Loading logs...', 'supa-chat-woocommerce'); ?></p>
            </div>
        </div>
        <?php
    }
}
