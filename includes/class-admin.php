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
            <h1><?php esc_html_e('SupaChat', 'supa-chat-for-woocommerce'); ?></h1>
            
            <div id="scwc-admin-container">
                <?php if (!$is_logged_in): ?>
                    <?php $this->render_login_form(); ?>
                <?php else: ?>
                    <?php $this->render_dashboard($user_data); ?>
                <?php endif; ?>
            </div>
            
            <div id="scwc-loading" style="display: none;">
                <div class="scwc-loading-spinner"></div>
                <p><?php esc_html_e('Loading...', 'supa-chat-for-woocommerce'); ?></p>
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
            <h2><?php esc_html_e('Connect to SupaChat', 'supa-chat-for-woocommerce'); ?></h2>
            <p><?php esc_html_e('Login to your SupaChat account to start integrating your site with AI chatbots.', 'supa-chat-for-woocommerce'); ?></p>
            
            <div class="scwc-login-options">
                <div class="scwc-google-login" style="margin-bottom: 20px; text-align: center;">
                    <button type="button" id="scwc-google-login" class="button button-primary button-large" style="padding: 10px 20px; font-size: 16px;">
                        <span class="dashicons dashicons-google" style="margin-right: 8px; vertical-align: middle;"></span>
                        <?php esc_html_e('Sign in with Google', 'supa-chat-for-woocommerce'); ?>
                    </button>
                    <p class="description" style="margin-top: 8px; font-size: 12px; color: #666;">
                        <?php esc_html_e('You will be able to choose which Google account to use', 'supa-chat-for-woocommerce'); ?>
                    </p>
                </div>
                
                <div class="scwc-divider" style="text-align: center; margin: 20px 0; position: relative;">
                    <hr style="border: none; border-top: 1px solid #ddd;">
                    <span style="background: white; padding: 0 15px; color: #666; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);">
                        <?php esc_html_e('or', 'supa-chat-for-woocommerce'); ?>
                    </span>
                </div>
                
                <div class="scwc-email-login">
                    <h3 style="margin-bottom: 15px;"><?php esc_html_e('Sign in with Email & Password', 'supa-chat-for-woocommerce'); ?></h3>
                    <form id="scwc-login-form" method="post">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="scwc-email"><?php esc_html_e('Email', 'supa-chat-for-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <input type="email" id="scwc-email" name="email" class="regular-text" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="scwc-password"><?php esc_html_e('Password', 'supa-chat-for-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" id="scwc-password" name="password" class="regular-text" required />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-secondary">
                                <?php esc_html_e('Connect to SupaChat', 'supa-chat-for-woocommerce'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="scwc-help">
                <h3><?php esc_html_e('Need a SupaChat Account?', 'supa-chat-for-woocommerce'); ?></h3>
                <p><?php esc_html_e('Sign up for a free SupaChat account to get started with AI-powered customer support.', 'supa-chat-for-woocommerce'); ?></p>
                <a href="https://supa-chat-for-woocommerce.com/signup" target="_blank" class="button">
                    <?php esc_html_e('Sign Up for SupaChat', 'supa-chat-for-woocommerce'); ?>
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
                    <h2><?php 
                        // translators: %s is the user's name or email
                        printf(esc_html__('Welcome, %s!', 'supa-chat-for-woocommerce'), esc_html($user_data['name'] ?? $user_data['email'] ?? 'User')); ?></h2>
                    <p><?php esc_html_e('Manage your chatbot integrations', 'supa-chat-for-woocommerce'); ?></p>
                </div>
                <div class="scwc-actions">
                    <span class="scwc-user-email">
                        <?php 
                        // translators: %s is the user's email address
                        printf(esc_html__('Logged in as: %s', 'supa-chat-for-woocommerce'), '<strong>' . esc_html($user_data['email'] ?? 'Unknown') . '</strong>'); ?>
                    </span>
                    <button id="scwc-logout" class="button" style="margin-left: 15px;">
                        <?php esc_html_e('Logout', 'supa-chat-for-woocommerce'); ?>
                    </button>
                </div>
            </div>
            
            <div class="scwc-main-content">
                <?php $this->render_integrations_content(); ?>
            </div>
        </div>
        <?php
    }
    
    
    
    /**
     * Render integrations content
     */
    private function render_integrations_content() {
        ?>
        <div class="scwc-integrations">

            <div class="scwc-chatbot-selection">
                <h4><?php esc_html_e('Select Chatbot to Connect', 'supa-chat-for-woocommerce'); ?></h4>
                <div id="scwc-chatbots-container">
                    <div class="scwc-loading-chatbots">
                        <div class="scwc-loading-spinner"></div>
                        <p><?php esc_html_e('Loading your chatbots...', 'supa-chat-for-woocommerce'); ?></p>
                    </div>
                </div>
            </div>

            <div id="scwc-integration-form-container" style="display: none;">
                <div class="scwc-integration-header">
                    <h3><?php esc_html_e('Connect Your Chatbot', 'supa-chat-for-woocommerce'); ?></h3>
                    <p><?php esc_html_e('Enable AI-powered customer support with access to your site information.', 'supa-chat-for-woocommerce'); ?></p>
                </div>

                <div class="scwc-selected-chatbot-info">
                    <div class="scwc-chatbot-preview">
                        <div class="scwc-chatbot-icon">🤖</div>
                        <div class="scwc-chatbot-details">
                            <h4 id="selected-chatbot-name"><?php esc_html_e('Loading chatbot...', 'supa-chat-for-woocommerce'); ?></h4>
                            <p id="selected-chatbot-description" class="description"><?php esc_html_e('Loading description...', 'supa-chat-for-woocommerce'); ?></p>
                            <div class="scwc-chatbot-meta">
                                <span class="scwc-meta-badge" id="selected-chatbot-status">Active</span>
                                <span class="scwc-meta-date" id="selected-chatbot-created"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="scwc-integration-benefits">
                    <h4><?php esc_html_e('What this connection will enable:', 'supa-chat-for-woocommerce'); ?></h4>
                    <div class="scwc-benefits-grid">
                        <div class="scwc-benefit-item">
                            <span class="scwc-benefit-icon">🛍️</span>
                            <div>
                                <strong><?php esc_html_e('Product Knowledge', 'supa-chat-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Your chatbot will know all about your products, prices, and inventory', 'supa-chat-for-woocommerce'); ?></p>
                            </div>
                        </div>
                        <div class="scwc-benefit-item">
                            <span class="scwc-benefit-icon">🔒</span>
                            <div>
                                <strong><?php esc_html_e('Secure & Safe', 'supa-chat-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Read-only access keeps your data secure and unchanged', 'supa-chat-for-woocommerce'); ?></p>
                            </div>
                        </div>
                        <div class="scwc-benefit-item">
                            <span class="scwc-benefit-icon">⚡</span>
                            <div>
                                <strong><?php esc_html_e('Instant Setup', 'supa-chat-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Automatic API key generation - no technical setup required', 'supa-chat-for-woocommerce'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="scwc-integration-form">
                    <input type="hidden" id="selected-chatbot-id" name="chatbot_id" />
                    <input type="hidden" id="integration-name" name="integration_name" />
                    
                    <div class="scwc-form-actions">
                        <button type="submit" class="button button-primary button-hero">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e('Connect Chatbot', 'supa-chat-for-woocommerce'); ?>
                        </button>
                        <button type="button" id="scwc-cancel-integration" class="button button-large">
                            <?php esc_html_e('← Back to Chatbots', 'supa-chat-for-woocommerce'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div id="scwc-integration-status" style="display: none;">
                <div class="scwc-success-box">
                    <h4><?php esc_html_e('Integration Active', 'supa-chat-for-woocommerce'); ?></h4>
                    <p id="scwc-integration-details"></p>
                    <div class="scwc-integration-actions">
                        <button type="button" id="scwc-remove-integration" class="button button-secondary">
                            <?php esc_html_e('Remove Integration', 'supa-chat-for-woocommerce'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
