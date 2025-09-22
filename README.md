=== SupaChat ===
Contributors: supachat
Tags: chatbot, ai, customer-support, automation, chat
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically integrate your site with SupaChat AI chatbots for enhanced customer support.

== Description ==

A plugin that automatically integrates your site with SupaChat AI chatbots. This plugin provides seamless integration for enhanced customer support and automated assistance.

== Features ==

- **🔐 Automatic API Integration**: Automatically generates and manages API connections with appropriate permissions
- **🤖 SupaChat Integration**: Seamless login and integration with your SupaChat account
- **🔧 MCP Server Management**: Automatically creates and manages MCP servers in your chatbot service
- **📊 Real-time Status Monitoring**: Monitor the health of your integrations and services
- **🛡️ Security First**: Secure storage of API keys and proper authentication handling
- **📱 Responsive Admin Interface**: Modern, user-friendly admin interface that works on all devices
- **📝 Comprehensive Logging**: Detailed logs for troubleshooting and monitoring

== Requirements ==

- WordPress 6.8 or higher
- PHP 7.4 or higher
- SupaChat account
- Access to SupaChat user and chatbot services

== Installation ==

1. **Download the Plugin**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone <repository-url> supa-chat-woocommerce
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "SupaChat"
   - Click "Activate"

3. **Configure Services** (if needed)
   - The plugin uses default service URLs for development
   - For production, update the service URLs in the plugin settings

== Usage ==

### Initial Setup

1. **Access the Plugin**
   - Go to WordPress Admin → SupaChat
   - You'll see the SupaChat dashboard

2. **Login to SupaChat**
   - Enter your SupaChat account credentials
   - The plugin will verify connectivity to SupaChat services
   - Upon successful login, you'll be redirected to the dashboard

3. **Create an Integration**
   - Click "New Integration" button
   - Select the chatbot you want to integrate with your store
   - Provide a descriptive name for the integration
   - Click "Create Integration"

### What Happens Automatically

When you create an integration, the plugin automatically:

1. **Generates WooCommerce API Keys**
   - Creates a new REST API key pair (Consumer Key/Secret)
   - Sets appropriate read permissions for product access
   - Stores the keys securely

2. **Creates MCP Server**
   - Calls your chatbot service API
   - Creates a new WordPress MCP server entry
   - Configures it with your store's URL and API credentials
   - Validates the connection and available tools

3. **Stores Integration Data**
   - Saves integration metadata for future management
   - Links the chatbot, API keys, and MCP server

### Managing Integrations

- **View Integrations**: See all your active integrations on the dashboard
- **Test Integration**: Verify that the MCP server is working correctly
- **Refresh Integration**: Re-validate and update the MCP server configuration
- **Delete Integration**: Remove an integration (cleans up API keys and MCP server)

## Admin Interface

### Dashboard Tabs

1. **Integrations Tab**
   - View and manage all chatbot integrations
   - Create new integrations
   - Monitor integration status and health

2. **Settings Tab**
   - Configure service URLs
   - Set auto-cleanup preferences
   - Access dangerous operations (reset, cleanup)

3. **Logs Tab**
   - View integration logs
   - Filter by log level and date
   - Clear logs when needed

### Status Monitoring

The plugin provides real-time status monitoring for:

- **Service Connectivity**: User service and chatbot service availability
- **WooCommerce Status**: Plugin activation and API enablement
- **Environment Health**: SSL status, WordPress version, etc.
- **Integration Status**: Individual integration health and validation

## Configuration

### Service URLs

By default, the plugin uses these service URLs:

- **User Service**: `http://localhost:9091/api/v1`
- **Chatbot Service**: `http://localhost:9092/api/v1`

For production environments, update these in the Settings tab or by defining constants in your `wp-config.php`:

```php
define('SCWC_USER_SERVICE_URL', 'https://your-user-service.com/api/v1');
define('SCWC_CHATBOT_SERVICE_URL', 'https://your-chatbot-service.com/api/v1');
```

### Auto-Cleanup

The plugin includes an auto-cleanup feature that:
- Removes API keys when integrations are deleted
- Cleans up MCP servers when integrations are removed
- Can be disabled in the Settings tab

## API Endpoints

The plugin integrates with these SupaChat service endpoints:

### User Service (`/api/v1`)
- `POST /login` - Authenticate user
- `GET /users/{id}` - Get user details
- `GET /status` - Service health check

### Chatbot Service (`/api/v1`)
- `GET /chatbots` - List user's chatbots
- `POST /chatbots/{id}/wordpress-mcp-servers` - Create WordPress MCP server
- `DELETE /mcp-servers/{id}` - Delete MCP server
- `POST /mcp-servers/{id}/refresh` - Refresh MCP server
- `GET /status` - Service health check

## Security

### Data Protection
- API keys are stored securely in WordPress options
- User tokens are handled with WordPress best practices
- All AJAX requests include nonce verification
- User capabilities are checked for all admin operations

### Access Control
- Only users with `manage_options` capability can access the plugin
- API communications use proper authentication headers
- Sensitive data is never logged or exposed

### Best Practices
- Use HTTPS in production environments
- Regularly rotate API keys if needed
- Monitor integration logs for unusual activity
- Keep the plugin updated

## Troubleshooting

### Common Issues

1. **Service Connection Errors**
   - Check that SupaChat services are running and accessible
   - Verify service URLs in settings
   - Check firewall and network connectivity

2. **WooCommerce API Issues**
   - Ensure WooCommerce is installed and activated
   - Verify WooCommerce REST API is enabled
   - Check WordPress permalinks are configured

3. **Authentication Failures**
   - Verify SupaChat account credentials
   - Check user service connectivity
   - Clear browser cache and try again

4. **Integration Creation Failures**
   - Check chatbot service connectivity
   - Verify you have chatbots in your SupaChat account
   - Review integration logs for specific errors

### Debug Mode

Enable WordPress debug mode to see detailed error information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the WordPress debug log at `/wp-content/debug.log` for plugin-specific errors.

### Log Files

The plugin logs important events and errors. Access logs through:
- WordPress Admin → SupaChat → Logs tab
- WordPress debug log (if WP_DEBUG is enabled)
- Server error logs

## Development

### File Structure

```
supa-chat-woocommerce/
├── supa-chat-woocommerce.php    # Main plugin file
├── includes/                     # PHP classes
│   ├── class-admin.php          # Admin interface
│   ├── class-api-manager.php    # API communication
│   ├── class-mcp-manager.php    # MCP server management
│   └── class-woocommerce-api.php # WooCommerce API handling
├── assets/                      # Frontend assets
│   ├── css/
│   │   └── admin.css           # Admin styles
│   └── js/
│       └── admin.js            # Admin JavaScript
└── README.md                   # This file
```

### Extending the Plugin

The plugin is designed with extensibility in mind:

1. **Hooks and Filters**: Standard WordPress hooks are available
2. **Class Structure**: Object-oriented design for easy extension
3. **API Abstraction**: Service communication is abstracted for easy modification

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For support and questions:

1. Check the troubleshooting section above
2. Review the integration logs
3. Contact SupaChat support with log details
4. Create an issue in the project repository

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Automatic WooCommerce API key generation
- SupaChat service integration
- WordPress MCP server management
- Responsive admin interface
- Comprehensive logging and monitoring

---

**Made with ❤️ for the SupaChat community**
