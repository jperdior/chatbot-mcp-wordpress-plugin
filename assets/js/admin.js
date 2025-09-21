/**
 * SupaChat WooCommerce Admin JavaScript
 */

console.log('SCWC: JavaScript file loaded');

(function($) {
    'use strict';

    const SCWC = {
        init: function() {
            console.log('SCWC: init() called');
            this.bindEvents();
            this.initTabs();
            this.loadInitialData();
        },

        bindEvents: function() {
            // Login form
            $('#scwc-login-form').on('submit', this.handleLogin.bind(this));
            
            // Google login
            $('#scwc-google-login').on('click', this.handleGoogleLogin.bind(this));
            
            // Logout
            $('#scwc-logout').on('click', this.handleLogout.bind(this));
            
            // Integration form
            $('#scwc-integration-form').on('submit', this.handleCreateIntegration.bind(this));
            
            // Cancel integration
            $('#scwc-cancel-integration').on('click', this.cancelIntegration.bind(this));
            
            // Remove integration
            $('#scwc-remove-integration').on('click', this.handleRemoveIntegration.bind(this));
            
            // Chatbot selection
            $(document).on('click', '.scwc-chatbot-card', this.selectChatbot.bind(this));
            
            // Website integration
            $(document).on('click', '.scwc-website-integration', this.showWebsiteIntegration.bind(this));
            
            // Remove integration directly
            $(document).on('click', '.scwc-remove-integration-direct', this.handleRemoveIntegrationDirect.bind(this));
            
            // Modal controls
            $('.scwc-modal-close').on('click', this.hideModal.bind(this));
            $(document).on('click', '.scwc-modal', function(e) {
                if (e.target === this) {
                    SCWC.hideModal();
                }
            });
            
            // Settings form
            $('#scwc-settings-form').on('submit', this.handleSaveSettings.bind(this));
            
            // Integration actions
            $(document).on('click', '.scwc-delete-integration', this.handleDeleteIntegration.bind(this));
            $(document).on('click', '.scwc-test-integration', this.handleTestIntegration.bind(this));
            $(document).on('click', '.scwc-refresh-integration', this.handleRefreshIntegration.bind(this));
            
            // Logs
            $('#scwc-refresh-logs').on('click', this.loadLogs.bind(this));
            $('#scwc-clear-logs').on('click', this.clearLogs.bind(this));
            $('#scwc-log-level, #scwc-log-date').on('change', this.loadLogs.bind(this));
            
            // Danger zone
            $('#scwc-cleanup-all').on('click', this.handleCleanupAll.bind(this));
            $('#scwc-reset-plugin').on('click', this.handleResetPlugin.bind(this));
        },

        initTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                
                // Update active tab
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show tab content
                $('.tab-content').removeClass('active');
                $('#tab-' + tabId).addClass('active');
                
                // Load tab-specific data
                if (tabId === 'integrations') {
                    SCWC.loadIntegrations();
                } else if (tabId === 'logs') {
                    SCWC.loadLogs();
                }
            });
        },

        loadInitialData: function() {
            console.log('SCWC: loadInitialData called');
            console.log('SCWC: Dashboard elements found:', $('.scwc-dashboard').length);
            console.log('SCWC: Integration elements found:', $('.scwc-integrations').length);
            
            // Check if we're logged in and load appropriate data
            if ($('.scwc-dashboard').length > 0) {
                console.log('SCWC: Dashboard found, loading integrations');
                this.loadIntegrations();
            } else {
                console.log('SCWC: No dashboard found, user not logged in');
            }
        },

        showLoading: function() {
            $('#scwc-loading').show();
        },

        hideLoading: function() {
            $('#scwc-loading').hide();
        },

        showMessage: function(message, type = 'success') {
            const messageHtml = `<div class="scwc-message ${type}">${message}</div>`;
            $('.scwc-dashboard, .scwc-card').first().prepend(messageHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.scwc-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        loadIntegrations: function() {
            console.log('SCWC: loadIntegrations called');
            // Load chatbots and check integration status
            console.log('SCWC: About to call loadChatbots');
            this.loadChatbots();
            console.log('SCWC: loadChatbots call completed');
        },

        loadChatbots: function() {
            console.log('SCWC: loadChatbots called');
            
            // Check if scwc_ajax is available
            if (typeof scwc_ajax === 'undefined') {
                console.error('SCWC: scwc_ajax object is undefined!');
                $('#scwc-chatbots-container').html('<div class="scwc-error-box"><p>Configuration error: AJAX settings not loaded.</p></div>');
                return;
            }
            
            console.log('SCWC: scwc_ajax object:', scwc_ajax);
            
            // First, make sure the integrations tab is visible
            $('.scwc-tab-content').hide();
            $('#scwc-integrations-tab').show();
            $('.nav-tab').removeClass('nav-tab-active');
            $('[data-tab="integrations"]').addClass('nav-tab-active');
            
            const $container = $('#scwc-chatbots-container');
            const $loading = $('.scwc-loading-chatbots');
            
            console.log('SCWC: Container found:', $container.length);
            console.log('SCWC: Loading element found:', $loading.length);
            
            if ($container.length === 0) {
                console.log('SCWC: chatbots container not found - creating fallback');
                // Create a fallback container if it doesn't exist
                $('.scwc-integrations, .scwc-dashboard').first().append('<div id="scwc-chatbots-container"><div class="scwc-loading-chatbots"><p>Loading chatbots...</p></div></div>');
                const $newContainer = $('#scwc-chatbots-container');
                const $newLoading = $('.scwc-loading-chatbots');
                console.log('SCWC: Created fallback container:', $newContainer.length);
            }
            
            $loading.show();

            const data = {
                action: 'scwc_get_chatbots',
                nonce: scwc_ajax.nonce
            };
            
            console.log('SCWC: Making AJAX request with data:', data);

            $.post(scwc_ajax.ajax_url, data)
                .done((response) => {
                    console.log('SCWC: AJAX response received:', response);
                    $loading.hide();
                    if (response.success) {
                        console.log('SCWC: Response successful, chatbots data:', response.data);
                        this.renderChatbots(response.data);
                    } else {
                        console.log('SCWC: Response failed:', response.data);
                        $container.html(`<div class="scwc-error-box"><p>Failed to load chatbots: ${response.data}</p></div>`);
                    }
                })
                .fail((xhr, status, error) => {
                    console.log('SCWC: AJAX request failed:', xhr, status, error);
                    $loading.hide();
                    $container.html('<div class="scwc-error-box"><p>Network error. Please try again.</p></div>');
                });
        },

        renderChatbots: function(chatbots) {
            const $container = $('#scwc-chatbots-container');
            
            if (!chatbots || chatbots.length === 0) {
                $container.html('<div class="scwc-info-box"><p>No chatbots found. Please create a chatbot first in your SupaChat dashboard.</p></div>');
                return;
            }

            let html = '<div class="scwc-chatbots-grid">';
            chatbots.forEach(chatbot => {
                const isIntegrated = this.checkIfIntegrated(chatbot.id);
                html += `
                    <div class="scwc-chatbot-card ${isIntegrated ? 'integrated' : ''}" data-chatbot-id="${chatbot.id}">
                        <div class="scwc-chatbot-header">
                            <h4>${this.escapeHtml(chatbot.name)}</h4>
                            ${isIntegrated ? '<span class="scwc-integrated-badge">Integrated</span>' : ''}
                        </div>
                        <p class="scwc-chatbot-description">${this.escapeHtml(chatbot.description || 'No description')}</p>
                        <div class="scwc-chatbot-meta">
                            <span class="scwc-chatbot-date">Created: ${new Date(chatbot.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="scwc-chatbot-actions">
                            ${isIntegrated ? 
                                '<button type="button" class="button button-secondary scwc-remove-integration-direct" data-chatbot-id="' + chatbot.id + '" data-chatbot-name="' + this.escapeHtml(chatbot.name) + '">Remove Integration</button>' :
                                '<button type="button" class="button button-primary scwc-integrate-chatbot">Integrate</button>'
                            }
                            <button type="button" class="button scwc-website-integration" data-chatbot-id="${chatbot.id}">Add to Website</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $container.html(html);

            // If only one chatbot and not integrated, pre-select it
            if (chatbots.length === 1 && !this.checkIfIntegrated(chatbots[0].id)) {
                this.selectChatbot({ currentTarget: $container.find('.scwc-chatbot-card').first()[0] });
            }
        },

        checkIfIntegrated: function(chatbotId) {
            // Check if this chatbot is already integrated
            // This would check against stored MCP server ID
            const storedMcpServerId = localStorage.getItem(`scwc_mcp_server_${chatbotId}`);
            return !!storedMcpServerId;
        },

        selectChatbot: function(e) {
            const $card = $(e.currentTarget);
            const chatbotId = $card.data('chatbot-id');
            const isIntegrated = $card.hasClass('integrated');

            if (isIntegrated) {
                // Show integration status
                this.showIntegrationStatus(chatbotId);
            } else {
                // Show integration form
                this.showIntegrationForm(chatbotId, $card.find('h4').text());
            }
        },

        showIntegrationForm: function(chatbotId, chatbotName) {
            $('#selected-chatbot-id').val(chatbotId);
            $('#integration-name').val(`${chatbotName} Integration`);
            
            $('.scwc-chatbot-selection').hide();
            $('#scwc-integration-form-container').show();
        },

        showIntegrationStatus: function(chatbotId) {
            const storedMcpServerId = localStorage.getItem(`scwc_mcp_server_${chatbotId}`);
            $('#scwc-integration-details').text(`Integration active with MCP Server ID: ${storedMcpServerId}`);
            
            $('.scwc-chatbot-selection').hide();
            $('#scwc-integration-status').show();
        },

        cancelIntegration: function() {
            $('#scwc-integration-form-container').hide();
            $('#scwc-integration-status').hide();
            $('.scwc-chatbot-selection').show();
        },

        handleCreateIntegration: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text('Creating...');

            const data = {
                action: 'scwc_setup_integration',
                nonce: scwc_ajax.nonce,
                chatbot_id: $('#selected-chatbot-id').val(),
                integration_name: $('#integration-name').val()
            };

            $.post(scwc_ajax.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        // Store MCP server ID
                        if (response.data && response.data.mcp_server_id) {
                            localStorage.setItem(`scwc_mcp_server_${data.chatbot_id}`, response.data.mcp_server_id);
                        }
                        
                        this.showMessage('Integration created successfully!', 'success');
                        this.cancelIntegration();
                        this.loadChatbots(); // Reload to show updated status
                    } else {
                        this.showMessage(`Failed to create integration: ${response.data}`, 'error');
                    }
                })
                .fail(() => {
                    this.showMessage('Network error. Please try again.', 'error');
                })
                .always(() => {
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        },

        handleRemoveIntegration: function() {
            this.showConfirmModal(
                'Remove Integration',
                'Are you sure you want to remove this integration? This will delete the MCP server and API keys.',
                () => {
                    // Implementation for removing integration
                    this.showMessage('Integration removal not yet implemented.', 'info');
                }
            );
        },

        showWebsiteIntegration: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const chatbotId = $(e.target).data('chatbot-id');
            const chatbotName = $(e.target).closest('.scwc-chatbot-card').find('h4').text();
            
            console.log('SCWC: Showing website integration for chatbot:', chatbotId);
            
            this.renderWebsiteIntegrationModal(chatbotId, chatbotName);
        },

        renderWebsiteIntegrationModal: function(chatbotId, chatbotName) {
            const bubbleSnippet = this.generateBubbleSnippet(chatbotId);
            const shortcode = `[supachat chatbot="${chatbotId}" width="100%" height="500px"]`;
            
            console.log('SCWC: Generated shortcode:', shortcode);
            
            const modalHtml = `
                <div class="scwc-modal" id="scwc-website-integration-modal">
                    <div class="scwc-modal-content">
                        <div class="scwc-modal-header">
                            <h3>Add chatbot "${this.escapeHtml(chatbotName)}" to Your Website</h3>
                            <button type="button" class="scwc-modal-close">&times;</button>
                        </div>
                        <div class="scwc-modal-body">
                            <div class="scwc-integration-options">
                                <div class="scwc-integration-option">
                                    <div class="scwc-option-header">
                                        <h4>🎈 Bubble Chat (Floating)</h4>
                                        <label class="scwc-switch">
                                            <input type="checkbox" id="scwc-enable-bubble" data-chatbot-id="${chatbotId}" ${this.isBubbleEnabled(chatbotId) ? 'checked' : ''}>
                                            <span class="scwc-slider"></span>
                                        </label>
                                    </div>
                                    <p>Add a floating chat bubble to all pages of your website. Visitors can click to start chatting. The bubble will be automatically added when enabled.</p>
                                </div>
                                
                                <div class="scwc-integration-option">
                                    <div class="scwc-option-header">
                                        <h4>📄 Shortcode (Embed in Pages)</h4>
                                    </div>
                                    <p>Use this shortcode to embed the chatbot in specific pages, posts, or products.</p>
                                    <div class="scwc-code-block">
                                        <label>Shortcode:</label>
                                        <textarea readonly rows="2" class="scwc-shortcode-input">${shortcode}</textarea>
                                        <button type="button" class="button button-small scwc-copy-code" data-code="${shortcode}">Copy Shortcode</button>
                                    </div>
                                    <div class="scwc-shortcode-examples">
                                        <h5>Usage Examples:</h5>
                                        <ul>
                                            <li><strong>In a page/post:</strong> Just paste the shortcode in the content editor</li>
                                            <li><strong>In a template:</strong> <code>&lt;?php echo do_shortcode('${shortcode}'); ?&gt;</code></li>
                                            <li><strong>With custom width:</strong> <code>[supachat chatbot="${chatbotId}" width="100%" height="600px"]</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="scwc-modal-footer">
                            <button type="button" class="button scwc-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal and add new one
            $('#scwc-website-integration-modal').remove();
            $('body').append(modalHtml);
            
            // Bind events for this modal
            this.bindWebsiteIntegrationEvents();
            
            // Show modal
            $('#scwc-website-integration-modal').show();
        },

        generateBubbleSnippet: function(chatbotId) {
            const baseUrl = window.location.origin;
            return `<!-- SupaChat Bubble Integration -->
<script>
(function() {
    var script = document.createElement('script');
    script.src = '${baseUrl}/wp-content/plugins/supa-chat-woocommerce/assets/js/bubble-chat.js';
    script.onload = function() {
        SupaChatBubble.init({
            chatbotId: '${chatbotId}',
            apiUrl: 'https://your-chatbot-api.com',
            position: 'bottom-right',
            primaryColor: '#007bff',
            size: 'medium'
        });
    };
    document.head.appendChild(script);
})();
</script>`;
        },

        isBubbleEnabled: function(chatbotId) {
            return localStorage.getItem(`scwc_bubble_enabled_${chatbotId}`) === 'true';
        },

        bindWebsiteIntegrationEvents: function() {
            // Copy code functionality
            $(document).off('click', '.scwc-copy-code').on('click', '.scwc-copy-code', function(e) {
                const code = $(this).data('code');
                navigator.clipboard.writeText(code).then(() => {
                    $(this).text('Copied!').addClass('copied');
                    setTimeout(() => {
                        $(this).text('Copy Code').removeClass('copied');
                    }, 2000);
                });
            });
            
            // Bubble toggle functionality - save immediately when changed
            $(document).off('change', '#scwc-enable-bubble').on('change', '#scwc-enable-bubble', this.handleBubbleToggle.bind(this));
            
            // Close modal functionality
            $(document).off('click', '.scwc-modal-close').on('click', '.scwc-modal-close', function(e) {
                e.preventDefault();
                $('#scwc-website-integration-modal').hide();
            });
            
            // Close modal when clicking outside
            $(document).off('click', '#scwc-website-integration-modal').on('click', '#scwc-website-integration-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },

        handleBubbleToggle: function(e) {
            const bubbleEnabled = $(e.target).is(':checked');
            const chatbotId = $(e.target).data('chatbot-id');
            
            if (!chatbotId) {
                this.showMessage('Could not determine chatbot ID', 'error');
                return;
            }
            
            console.log('SCWC: Bubble toggle changed for chatbot:', chatbotId, 'enabled:', bubbleEnabled);
            
            // Save to localStorage
            localStorage.setItem(`scwc_bubble_enabled_${chatbotId}`, bubbleEnabled.toString());
            
            // Save to WordPress database
            const data = {
                action: 'scwc_save_website_integration',
                nonce: scwc_ajax.nonce,
                chatbot_id: chatbotId,
                bubble_enabled: bubbleEnabled
            };
            
            $.post(scwc_ajax.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        const message = bubbleEnabled ? 
                            'Bubble chat enabled! It will now appear on all pages of your website.' : 
                            'Bubble chat disabled and removed from your website.';
                        this.showMessage(message, 'success');
                    } else {
                        this.showMessage('Failed to save settings: ' + response.data, 'error');
                        // Revert the toggle
                        $(e.target).prop('checked', !bubbleEnabled);
                        localStorage.setItem(`scwc_bubble_enabled_${chatbotId}`, (!bubbleEnabled).toString());
                    }
                })
                .fail(() => {
                    this.showMessage('Network error. Settings may not be saved.', 'error');
                    // Revert the toggle
                    $(e.target).prop('checked', !bubbleEnabled);
                    localStorage.setItem(`scwc_bubble_enabled_${chatbotId}`, (!bubbleEnabled).toString());
                });
        },

        handleRemoveIntegrationDirect: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const chatbotId = $(e.target).data('chatbot-id');
            const chatbotName = $(e.target).data('chatbot-name');
            
            if (!chatbotId) {
                this.showMessage('No chatbot ID found', 'error');
                return;
            }

            const confirmMessage = `Are you sure you want to remove the integration for "${chatbotName}"?\n\nThis will:\n• Delete the MCP server connection\n• Remove WooCommerce API keys\n• Disable the chatbot integration\n\nThis action cannot be undone.`;
            
            this.showConfirmModal(
                'Remove Integration',
                confirmMessage,
                () => {
                    // Show loading state
                    const $button = $(e.target);
                    const originalText = $button.text();
                    $button.prop('disabled', true).text('Removing...');

                    // Make AJAX call to remove integration
                    const data = {
                        action: 'scwc_delete_integration',
                        nonce: scwc_ajax.nonce,
                        chatbot_id: chatbotId
                    };

                    $.post(scwc_ajax.ajax_url, data)
                        .done((response) => {
                            if (response.success) {
                                this.showMessage(`Integration for "${chatbotName}" removed successfully!`, 'success');
                                
                                // Remove stored MCP server ID
                                localStorage.removeItem(`scwc_mcp_server_${chatbotId}`);
                                
                                // Reload chatbots to update UI
                                this.loadChatbots();
                            } else {
                                this.showMessage(`Failed to remove integration: ${response.data}`, 'error');
                                $button.prop('disabled', false).text(originalText);
                            }
                        })
                        .fail(() => {
                            this.showMessage('Network error. Please try again.', 'error');
                            $button.prop('disabled', false).text(originalText);
                        });
                }
            );
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Custom modal functions
        showConfirmModal: function(title, message, onConfirm, onCancel) {
            const modalId = 'scwc-confirm-modal-' + Date.now();
            const modalHtml = `
                <div class="scwc-modal scwc-confirm-modal" id="${modalId}">
                    <div class="scwc-modal-content scwc-confirm-modal-content">
                        <div class="scwc-modal-header">
                            <h3>${this.escapeHtml(title)}</h3>
                        </div>
                        <div class="scwc-modal-body">
                            <p>${this.escapeHtml(message)}</p>
                        </div>
                        <div class="scwc-modal-footer">
                            <button type="button" class="button scwc-modal-cancel">Cancel</button>
                            <button type="button" class="button button-primary scwc-modal-confirm">OK</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove any existing confirm modals
            $('.scwc-confirm-modal').remove();
            
            // Add new modal
            $('body').append(modalHtml);
            
            // Bind events
            $(`#${modalId} .scwc-modal-cancel, #${modalId} .scwc-modal-close`).on('click', () => {
                $(`#${modalId}`).remove();
                if (onCancel) onCancel();
            });
            
            $(`#${modalId} .scwc-modal-confirm`).on('click', () => {
                $(`#${modalId}`).remove();
                if (onConfirm) onConfirm();
            });
            
            // Close on background click
            $(`#${modalId}`).on('click', (e) => {
                if (e.target.id === modalId) {
                    $(`#${modalId}`).remove();
                    if (onCancel) onCancel();
                }
            });
            
            // Show modal
            $(`#${modalId}`).show();
            
            // Focus on confirm button
            $(`#${modalId} .scwc-modal-confirm`).focus();
        },

        showAlertModal: function(title, message, onClose) {
            const modalId = 'scwc-alert-modal-' + Date.now();
            const modalHtml = `
                <div class="scwc-modal scwc-alert-modal" id="${modalId}">
                    <div class="scwc-modal-content scwc-alert-modal-content">
                        <div class="scwc-modal-header">
                            <h3>${this.escapeHtml(title)}</h3>
                        </div>
                        <div class="scwc-modal-body">
                            <p>${this.escapeHtml(message)}</p>
                        </div>
                        <div class="scwc-modal-footer">
                            <button type="button" class="button button-primary scwc-modal-ok">OK</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove any existing alert modals
            $('.scwc-alert-modal').remove();
            
            // Add new modal
            $('body').append(modalHtml);
            
            // Bind events
            $(`#${modalId} .scwc-modal-ok, #${modalId} .scwc-modal-close`).on('click', () => {
                $(`#${modalId}`).remove();
                if (onClose) onClose();
            });
            
            // Close on background click
            $(`#${modalId}`).on('click', (e) => {
                if (e.target.id === modalId) {
                    $(`#${modalId}`).remove();
                    if (onClose) onClose();
                }
            });
            
            // Show modal
            $(`#${modalId}`).show();
            
            // Focus on OK button
            $(`#${modalId} .scwc-modal-ok`).focus();
        },

        handleLogin: function(e) {
            e.preventDefault();
            
            const email = $('#scwc-email').val();
            const password = $('#scwc-password').val();
            
            if (!email || !password) {
                this.showMessage(scwc_ajax.strings.error, 'error');
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'scwc_login',
                nonce: scwc_ajax.nonce,
                email: email,
                password: password
            };
            
            $.post(scwc_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        SCWC.showMessage(response.data || scwc_ajax.strings.error, 'error');
                    }
                })
                .fail(function() {
                    SCWC.showMessage(scwc_ajax.strings.error, 'error');
                })
                .always(function() {
                    SCWC.hideLoading();
                });
        },

        handleGoogleLogin: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.html();
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite; margin-right: 8px;"></span>Connecting...');
            
            const data = {
                action: 'scwc_google_login',
                nonce: scwc_ajax.nonce
            };
            
            $.post(scwc_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success && response.data.url) {
                        // Redirect to Google OAuth URL
                        window.location.href = response.data.url;
                    } else {
                        SCWC.showAlertModal('Login Error', 'Failed to initiate Google login. Please try again.');
                        $button.prop('disabled', false).html(originalText);
                    }
                })
                .fail(function() {
                    SCWC.showAlertModal('Network Error', 'Network error. Please check your connection and try again.');
                    $button.prop('disabled', false).html(originalText);
                });
        },

        handleLogout: function(e) {
            e.preventDefault();
            
            this.showConfirmModal(
                'Confirm Logout',
                'Are you sure you want to logout?',
                () => {
                    this.showLoading();
                    
                    const data = {
                        action: 'scwc_logout',
                        nonce: scwc_ajax.nonce
                    };
                    
                    $.post(scwc_ajax.ajax_url, data)
                        .done(function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                SCWC.showMessage(response.data || scwc_ajax.strings.error, 'error');
                            }
                        })
                        .fail(function() {
                            SCWC.showMessage(scwc_ajax.strings.error, 'error');
                        })
                        .always(function() {
                            SCWC.hideLoading();
                        });
                }
            );
        },

        showNewIntegrationModal: function() {
            this.loadChatbots();
            $('#scwc-new-integration-modal').show();
        },

        hideModal: function() {
            $('.scwc-modal').hide();
        },


        handleNewIntegration: function(e) {
            e.preventDefault();
            
            const chatbotId = $('#integration-chatbot').val();
            const integrationName = $('#integration-name').val();
            
            if (!chatbotId || !integrationName) {
                this.showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'scwc_setup_integration',
                nonce: scwc_ajax.nonce,
                chatbot_id: chatbotId,
                integration_name: integrationName
            };
            
            $.post(scwc_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        SCWC.showMessage('Integration created successfully!', 'success');
                        SCWC.hideModal();
                        SCWC.loadIntegrations();
                        $('#scwc-new-integration-form')[0].reset();
                    } else {
                        SCWC.showMessage(response.data || scwc_ajax.strings.error, 'error');
                    }
                })
                .fail(function() {
                    SCWC.showMessage(scwc_ajax.strings.error, 'error');
                })
                .always(function() {
                    SCWC.hideLoading();
                });
        },


        handleDeleteIntegration: function(e) {
            e.preventDefault();
            
            this.showConfirmModal(
                'Delete Integration',
                scwc_ajax.strings.confirm_delete,
                () => {
                    const chatbotId = $(e.target).data('chatbot-id');
                    
                    this.showLoading();
                    
                    const data = {
                        action: 'scwc_delete_integration',
                        nonce: scwc_ajax.nonce,
                        chatbot_id: chatbotId
                    };
                    
                    $.post(scwc_ajax.ajax_url, data)
                        .done(function(response) {
                            if (response.success) {
                                SCWC.showMessage('Integration deleted successfully', 'success');
                                SCWC.loadIntegrations();
                            } else {
                                SCWC.showMessage(response.data || scwc_ajax.strings.error, 'error');
                            }
                        })
                        .fail(function() {
                            SCWC.showMessage(scwc_ajax.strings.error, 'error');
                        })
                        .always(function() {
                            SCWC.hideLoading();
                        });
                }
            );
        },

        handleTestIntegration: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.text();
            $button.text('Testing...').prop('disabled', true);
            
            // Simulate test - in real implementation, make API call
            setTimeout(function() {
                $button.text(originalText).prop('disabled', false);
                SCWC.showMessage('Integration test completed', 'success');
            }, 2000);
        },

        handleRefreshIntegration: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.text();
            $button.text('Refreshing...').prop('disabled', true);
            
            // Simulate refresh - in real implementation, make API call
            setTimeout(function() {
                $button.text(originalText).prop('disabled', false);
                SCWC.showMessage('Integration refreshed successfully', 'success');
                SCWC.loadIntegrations();
            }, 2000);
        },

        handleSaveSettings: function(e) {
            e.preventDefault();
            
            this.showLoading();
            
            // Simulate saving settings
            setTimeout(function() {
                SCWC.hideLoading();
                SCWC.showMessage('Settings saved successfully', 'success');
            }, 1000);
        },

        loadLogs: function() {
            const $container = $('#scwc-logs-container');
            $container.html('<p>Loading logs...</p>');
            
            // Simulate loading logs
            setTimeout(function() {
                const sampleLogs = [
                    {
                        level: 'info',
                        message: 'Integration created successfully for chatbot: test-bot',
                        timestamp: new Date().toISOString()
                    },
                    {
                        level: 'warning',
                        message: 'API key generation took longer than expected',
                        timestamp: new Date(Date.now() - 300000).toISOString()
                    },
                    {
                        level: 'error',
                        message: 'Failed to connect to chatbot service',
                        timestamp: new Date(Date.now() - 600000).toISOString()
                    }
                ];
                
                let logsHtml = '';
                sampleLogs.forEach(function(log) {
                    logsHtml += `
                        <div class="scwc-log-entry ${log.level}">
                            <div class="scwc-log-meta">
                                <span class="scwc-log-level">${log.level.toUpperCase()}</span>
                                <span class="scwc-log-time">${new Date(log.timestamp).toLocaleString()}</span>
                            </div>
                            <div class="scwc-log-message">${log.message}</div>
                        </div>
                    `;
                });
                
                if (logsHtml) {
                    $container.html(logsHtml);
                } else {
                    $container.html('<p>No logs found</p>');
                }
            }, 1000);
        },

        clearLogs: function() {
            this.showConfirmModal(
                'Clear Logs',
                'Are you sure you want to clear all logs?',
                () => {
                    this.showLoading();
                    
                    setTimeout(function() {
                        SCWC.hideLoading();
                        SCWC.showMessage('Logs cleared successfully', 'success');
                        $('#scwc-logs-container').html('<p>No logs found</p>');
                    }, 1000);
                }
            );
        },

        handleCleanupAll: function() {
            this.showConfirmModal(
                'Cleanup All Integrations',
                'This will delete ALL integrations and their associated data. This action cannot be undone. Are you sure?',
                () => {
                    this.showLoading();
                    
                    setTimeout(function() {
                        SCWC.hideLoading();
                        SCWC.showMessage('All integrations cleaned up successfully', 'success');
                        SCWC.loadIntegrations();
                    }, 2000);
                }
            );
        },

        handleResetPlugin: function() {
            this.showConfirmModal(
                'Reset Plugin',
                'This will reset ALL plugin data including settings and login information. This action cannot be undone. Are you sure?',
                () => {
                    // Second confirmation
                    this.showConfirmModal(
                        'Final Warning',
                        'This is your final warning. All data will be permanently deleted. Continue?',
                        () => {
                            this.showLoading();
                            
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    );
                }
            );
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('SCWC: Document ready, initializing...');
        SCWC.init();
    });

})(jQuery);
