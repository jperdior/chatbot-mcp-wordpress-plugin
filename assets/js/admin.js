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
            
            // Connect chatbot button
            $(document).on('click', '.scwc-integrate-chatbot', this.handleConnectChatbot.bind(this));
            
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
            
            
            // Integration actions
            $(document).on('click', '.scwc-delete-integration', this.handleDeleteIntegration.bind(this));
            $(document).on('click', '.scwc-test-integration', this.handleTestIntegration.bind(this));
            $(document).on('click', '.scwc-refresh-integration', this.handleRefreshIntegration.bind(this));
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
            
            // Load integrations content
            
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
                        // Store chatbots data for bubble status checking
                        this.currentChatbots = response.data;
                        this.renderChatbots(response.data);
                    } else {
                        console.log('SCWC: Response failed:', response.data);
                        $container.html(`<div class="scwc-error-box"><p>Failed to load chatbots: ${response.data}</p></div>`);
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('SCWC: AJAX request failed:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status,
                        readyState: xhr.readyState,
                        url: scwc_ajax.ajax_url
                    });
                    $loading.hide();
                    let errorMsg = 'Network error. Please try again.';
                    if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.data) {
                                errorMsg = 'Error: ' + errorData.data;
                            }
                        } catch (e) {
                            errorMsg = 'Server error: ' + xhr.status + ' ' + error;
                        }
                    }
                    $container.html(`<div class="scwc-error-box"><p>${errorMsg}</p></div>`);
                });
        },

        renderChatbots: function(chatbots) {
            console.log('SCWC: renderChatbots called with:', chatbots);
            console.log('SCWC: Chatbots type:', typeof chatbots);
            console.log('SCWC: Chatbots is array:', Array.isArray(chatbots));
            console.log('SCWC: Chatbots length:', chatbots ? chatbots.length : 'N/A');
            
            const $container = $('#scwc-chatbots-container');
            console.log('SCWC: Container found for rendering:', $container.length);
            
            if (!chatbots || chatbots.length === 0) {
                console.log('SCWC: No chatbots to render');
                $container.html(`
                    <div class="scwc-info-box">
                        <h3>No Chatbots Found</h3>
                        <p>You don't have any chatbots yet. Create your first chatbot in your SupaChat dashboard to get started with AI-powered customer support.</p>
                        <a href="https://chatbot.supa-chat.com" target="_blank" class="button button-primary">
                            Create Your First Chatbot
                        </a>
                    </div>
                `);
                return;
            }
            
            console.log('SCWC: Rendering', chatbots.length, 'chatbots');

            // Always show chatbot details, even for single chatbot
            let html = `
                <div class="scwc-chatbots-header">
                    <h3>Your Chatbots (${chatbots.length})</h3>
                    <p>Select a chatbot to connect with your site. Your chatbot will have access to your content and data.</p>
                </div>
                <div class="scwc-chatbots-grid">
            `;
            
            chatbots.forEach(chatbot => {
                const isIntegrated = this.checkIfIntegrated(chatbot);
                const createdDate = new Date(chatbot.created_at).toLocaleDateString();
                const updatedDate = new Date(chatbot.updated_at).toLocaleDateString();
                
                html += `
                    <div class="scwc-chatbot-card ${isIntegrated ? 'integrated' : ''}" data-chatbot-id="${chatbot.id}">
                        <div class="scwc-chatbot-header">
                            <h4>${this.escapeHtml(chatbot.name)}</h4>
                            ${isIntegrated ? '<span class="scwc-integrated-badge">✓ Integrated</span>' : '<span class="scwc-available-badge">Available</span>'}
                        </div>
                        <div class="scwc-chatbot-description">
                            <p><strong>Description:</strong> ${this.escapeHtml(chatbot.description || 'No description provided')}</p>
                        </div>
                        <div class="scwc-chatbot-meta">
                            <div class="scwc-meta-row">
                                <span class="scwc-meta-label">Status:</span>
                                <span class="scwc-chatbot-status ${chatbot.status?.toLowerCase() || 'active'}">${chatbot.status || 'Active'}</span>
                            </div>
                            <div class="scwc-meta-row">
                                <span class="scwc-meta-label">Created:</span>
                                <span>${createdDate}</span>
                            </div>
                            <div class="scwc-meta-row">
                                <span class="scwc-meta-label">Last Updated:</span>
                                <span>${updatedDate}</span>
                            </div>
                        </div>
                        <div class="scwc-chatbot-actions">
                            ${isIntegrated ? 
                                `<button type="button" class="button button-secondary scwc-remove-integration-direct" data-chatbot-id="${chatbot.id}" data-chatbot-name="${this.escapeHtml(chatbot.name)}">
                                    <span class="dashicons dashicons-no"></span> Remove Connection
                                </button>
                                <button type="button" class="button scwc-website-integration" data-chatbot-id="${chatbot.id}">
                                    <span class="dashicons dashicons-admin-appearance"></span> Add to Website
                                </button>` :
                                `<button type="button" class="button button-primary scwc-integrate-chatbot">
                                    Connect Chatbot
                                </button>`
                            }
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $container.html(html);

            // Don't auto-select single chatbot - let user choose explicitly for consistency
        },

        checkIfIntegrated: function(chatbot) {
            // Use integration status from server (simple and reliable)
            if (chatbot.integration_status) {
                const isIntegrated = chatbot.integration_status.is_integrated;
                console.log(`SCWC: Integration status for ${chatbot.id}:`, {
                    is_integrated: isIntegrated,
                    local_exists: chatbot.integration_status.local_exists,
                    mcp_server_id: chatbot.integration_status.mcp_server_id,
                    issue: chatbot.integration_status.issue
                });
                return isIntegrated;
            }
            
            // Fallback to localStorage check (shouldn't be needed now)
            const storedMcpServerId = localStorage.getItem(`scwc_mcp_server_${chatbot.id}`);
            console.log(`SCWC: No integration status from server, using localStorage for ${chatbot.id}:`, !!storedMcpServerId);
            return !!storedMcpServerId;
        },

        selectChatbot: function(e) {
            console.log('SCWC: selectChatbot called', e);
            const $card = $(e.currentTarget);
            const chatbotId = $card.data('chatbot-id');
            const isIntegrated = $card.hasClass('integrated');

            console.log('SCWC: Chatbot selected:', {
                chatbotId: chatbotId,
                isIntegrated: isIntegrated,
                cardElement: $card[0]
            });

            if (isIntegrated) {
                // Show integration status
                this.showIntegrationStatus(chatbotId);
            } else {
                // Show integration form
                this.showIntegrationForm(chatbotId, $card.find('h4').text());
            }
        },

        handleConnectChatbot: function(e) {
            console.log('SCWC: handleConnectChatbot called', e);
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const $card = $button.closest('.scwc-chatbot-card');
            const chatbotId = $card.data('chatbot-id');
            const chatbotName = $card.find('h4').text();
            
            console.log('SCWC: Connect chatbot button clicked:', {
                chatbotId: chatbotId,
                chatbotName: chatbotName,
                button: $button[0],
                card: $card[0]
            });
            
            // Show integration form
            this.showIntegrationForm(chatbotId, chatbotName);
        },

        showIntegrationForm: function(chatbotId, chatbotName) {
            // Find the chatbot data from the rendered list
            const $chatbotCard = $(`.scwc-chatbot-card[data-chatbot-id="${chatbotId}"]`);
            const chatbotDescription = $chatbotCard.find('.scwc-chatbot-description p').text().replace('Description: ', '');
            const chatbotStatus = $chatbotCard.find('.scwc-chatbot-status').text() || 'Active';
            const chatbotCreated = $chatbotCard.find('.scwc-meta-row:contains("Created:") span:last').text();
            
            // Generate integration name using WordPress site title
            const siteTitle = document.title.replace(' ‹ ', ' - ').split(' - ')[0] || 'WordPress Site';
            const integrationName = `${chatbotName} - ${siteTitle}`;
            
            // Populate the integration form with chatbot details
            $('#selected-chatbot-id').val(chatbotId);
            $('#integration-name').val(integrationName);
            
            // Update the chatbot preview section
            $('#selected-chatbot-name').text(chatbotName);
            $('#selected-chatbot-description').text(chatbotDescription || 'No description provided');
            $('#selected-chatbot-status').text(chatbotStatus).removeClass().addClass('scwc-meta-badge').addClass(chatbotStatus.toLowerCase());
            $('#selected-chatbot-created').text(chatbotCreated ? `Created: ${chatbotCreated}` : '');
            
            console.log('SCWC: Showing integration form for:', {
                id: chatbotId,
                name: chatbotName,
                description: chatbotDescription,
                status: chatbotStatus,
                created: chatbotCreated,
                integrationName: integrationName
            });
            
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
            console.log('SCWC: handleCreateIntegration called', e);
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            const data = {
                action: 'scwc_setup_integration',
                nonce: scwc_ajax.nonce,
                chatbot_id: $('#selected-chatbot-id').val(),
                integration_name: $('#integration-name').val()
            };

            console.log('SCWC: Creating integration with data:', data);
            console.log('SCWC: AJAX URL:', scwc_ajax.ajax_url);
            
            $submitBtn.prop('disabled', true).text('Creating...');

            $.post(scwc_ajax.ajax_url, data)
                .done((response) => {
                    console.log('SCWC: Integration creation response:', response);
                    if (response.success) {
                        console.log('SCWC: Integration created successfully');
                        // Store MCP server ID
                        if (response.data && response.data.mcp_server_id) {
                            localStorage.setItem(`scwc_mcp_server_${data.chatbot_id}`, response.data.mcp_server_id);
                        }
                        
                        this.showMessage('Integration created successfully!', 'success');
                        this.cancelIntegration();
                        
                        // Reload immediately to show updated status
                        console.log('SCWC: Reloading chatbots after integration creation');
                        this.loadChatbots();
                    } else {
                        console.log('SCWC: Integration creation failed:', response);
                        this.showMessage(`Failed to create integration: ${response.data}`, 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.log('SCWC: Integration creation AJAX failed:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
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
            apiUrl: 'https://chatbot.supa-chat.com/api/v1',
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
            // First check if we have chatbot data with server-side bubble status
            if (this.currentChatbots) {
                const chatbot = this.currentChatbots.find(c => c.id === chatbotId);
                if (chatbot && chatbot.integration_status && chatbot.integration_status.bubble_enabled !== undefined) {
                    return chatbot.integration_status.bubble_enabled;
                }
            }
            
            // Fallback to localStorage for newly changed values or non-integrated chatbots
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
            
            // Get the button element (could be the span or button)
            const $button = $(e.target).closest('button');
            const chatbotId = $button.data('chatbot-id');
            const chatbotName = $button.data('chatbot-name');
            
            console.log('SCWC: Remove integration - chatbot ID:', chatbotId, 'name:', chatbotName);
            
            if (!chatbotId) {
                this.showMessage('No chatbot ID found', 'error');
                return;
            }

            const confirmMessage = `Are you sure you want to remove the integration for "${chatbotName}"?\n\nThis will:\n• Delete the MCP server connection\n• Remove API keys\n• Disable the chatbot integration\n\nThis action cannot be undone.`;
            
            this.showConfirmModal(
                'Remove Integration',
                confirmMessage,
                () => {
                    // Show loading state
                    const originalText = $button.text();
                    $button.prop('disabled', true).text('Removing...');

                    // Make AJAX call to remove integration
                    const data = {
                        action: 'scwc_delete_integration',
                        nonce: scwc_ajax.nonce,
                        chatbot_id: chatbotId
                    };

                    console.log('SCWC: Sending delete integration request:', {
                        url: scwc_ajax.ajax_url,
                        data: data,
                        chatbotId: chatbotId,
                        chatbotName: chatbotName
                    });

                    $.post(scwc_ajax.ajax_url, data)
                        .done((response) => {
                            console.log('SCWC: Delete integration response received:', response);
                            if (response.success) {
                                console.log('SCWC: Delete integration successful');
                                this.showMessage(`Integration for "${chatbotName}" removed successfully!`, 'success');
                                
                                // Remove stored MCP server ID
                                localStorage.removeItem(`scwc_mcp_server_${chatbotId}`);
                                
                                // Reload chatbots to update UI
                                this.loadChatbots();
                            } else {
                                console.error('SCWC: Delete integration failed:', response);
                                this.showMessage(`Failed to remove integration: ${response.data}`, 'error');
                                $button.prop('disabled', false).text(originalText);
                            }
                        })
                        .fail((xhr, status, error) => {
                            console.error('SCWC: Delete integration AJAX failed:', {
                                xhr: xhr,
                                status: status,
                                error: error,
                                responseText: xhr.responseText,
                                statusCode: xhr.status
                            });
                            let errorMsg = 'Network error. Please try again.';
                            if (xhr.responseText) {
                                try {
                                    const errorData = JSON.parse(xhr.responseText);
                                    if (errorData.data) {
                                        errorMsg = 'Server error: ' + errorData.data;
                                    }
                                } catch (e) {
                                    errorMsg = `Server error: ${xhr.status} ${error}`;
                                }
                            }
                            this.showMessage(errorMsg, 'error');
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
            
            // Clear any existing login state to prevent caching issues
            this.clearLoginState();
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite; margin-right: 8px;"></span>Connecting...');
            
            const data = {
                action: 'scwc_google_login',
                nonce: scwc_ajax.nonce
            };
            
            $.post(scwc_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success && response.data.url) {
                        console.log('SCWC: Redirecting to Google OAuth with account selection');
                        // Add a timestamp to prevent caching and force fresh authentication
                        const urlWithTimestamp = response.data.url + '&t=' + Date.now();
                        window.location.href = urlWithTimestamp;
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
        
        clearLoginState: function() {
            // Clear any cached authentication state
            console.log('SCWC: Clearing cached login state');
            
            // Clear localStorage items that might cache login state
            if (typeof(Storage) !== "undefined") {
                localStorage.removeItem('scwc_user_data');
                localStorage.removeItem('scwc_auth_state');
                localStorage.removeItem('google_oauth_state');
            }
            
            // Clear sessionStorage as well
            if (typeof(Storage) !== "undefined") {
                sessionStorage.removeItem('scwc_user_data');
                sessionStorage.removeItem('scwc_auth_state');
                sessionStorage.removeItem('google_oauth_state');
            }
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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('SCWC: Document ready, initializing...');
        SCWC.init();
    });

})(jQuery);
