<?php
if (!defined('ABSPATH')) exit;


require_once FLUENT_MCP_AGENT_PATH . 'includes/helpers.php';




add_action('admin_menu', function () {
    add_menu_page('Fluent MCP Agent', 'Fluent MCP Agent', 'manage_options', 'fluent-mcp-agent', 'fluent_mcp_agent_chat_page');
    add_submenu_page('fluent-mcp-agent', 'Chat', 'Chat', 'manage_options', 'fluent-mcp-agent', 'fluent_mcp_agent_chat_page');
    add_submenu_page('fluent-mcp-agent', 'Settings', 'Settings', 'manage_options', 'fluent-mcp-agent-settings', 'fluent_mcp_agent_settings_page');
    // add_submenu_page('fluent-mcp-agent', 'MCP Servers', 'MCP Servers', 'manage_options', 'fluent-mcp-agent-mcp-servers', 'fluent_mcp_agent_mcp_servers_page');
});




add_action('admin_enqueue_scripts', 'fluent_mcp_agent_enqueue_assets', 999);
function fluent_mcp_agent_enqueue_assets($hook) {

    $enabled_providers = fluent_mcp_agent_get_enabled_providers();

    if (empty($enabled_providers) || count($enabled_providers) <= 0) {
        return;
    }

    // Only load on your plugin’s admin page
    if ($hook !== 'toplevel_page_fluent-mcp-agent') {
        return;
    }

    $asset_url  = FLUENT_MCP_AGENT_URL . 'assets/';
    $asset_path = FLUENT_MCP_AGENT_PATH . 'assets/';

    // Use Vite dev server if available, fallback to built assets
    $vite_dev_url = 'http://localhost:5174/';
    $use_vite = false;

    // Try to detect if Vite server is running (useful for local dev)
    $ch = @curl_init($vite_dev_url . 'index.js');
    if ($ch) {
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = ($response !== false) ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
        if ($http_code === 200) {
            $use_vite = true;
        }
        curl_close($ch);
    }

    if ($use_vite) {
        // Vite development server
        wp_enqueue_style(
            'fluent-mcp-agent-app',
            $vite_dev_url . 'index.css',
            [], // No dependencies - load after everything
            '1.0.0'
        );
        // Add inline style to ensure our overrides take precedence
        wp_add_inline_style('fluent-mcp-agent-app', '
            .toplevel_page_fluent-mcp-agent #assistant-chat-root textarea { border: none !important; }
            .toplevel_page_fluent-mcp-agent #assistant-chat-root button { pointer-events: auto !important; z-index: 100 !important; }
        ');

        wp_enqueue_script(
            'fluent-mcp-agent-app',
            $vite_dev_url . 'main.jsx',
            [],
            '1.0.0',
            true
        );
    } else {
        // Built/production files
        $asset_url  = FLUENT_MCP_AGENT_URL . 'my-assistant-app/dist/assets/';
        $asset_path = FLUENT_MCP_AGENT_PATH . 'my-assistant-app/dist/assets/';
        $css_file = $asset_path . 'index.css';
        $js_file  = $asset_path . 'index.js';

        wp_enqueue_style(
            'fluent-mcp-agent-app',
            $asset_url . 'index.css',
            [], // No dependencies - load after everything
            file_exists($css_file) ? filemtime($css_file) : '1.0.0'
        );
        // Add inline style to ensure our overrides take precedence
        wp_add_inline_style('fluent-mcp-agent-app', '
            .toplevel_page_fluent-mcp-agent #assistant-chat-root textarea { border: none !important; }
            .toplevel_page_fluent-mcp-agent #assistant-chat-root button { pointer-events: auto !important; z-index: 100 !important; }
        ');

        wp_enqueue_script(
            'fluent-mcp-agent-app',
            $asset_url . 'index.js',
            [],
            file_exists($js_file) ? filemtime($js_file) : '1.0.0',
            true
        );

        wp_enqueue_script(
            'ask-fluent',
            FLUENT_MCP_AGENT_URL . 'assets/global.js',
            [],
            '1.0.0',
            true
        );
    }

    // Get the default provider
    $default_provider = fluent_mcp_agent_get_default_provider();

    $available_models = [];
    if ($enabled_providers) {
        foreach($enabled_providers as $provider) {
            $available_models[] = [$provider => fluent_mcp_agent_get_available_models($provider)];
        }
    }

    $abilities = wp_get_abilities();
    $tools = [];
    foreach($abilities as $ability) {
        $tools[] = ability_to_tool($ability);
    }

    ds($tools);


    // Localize script with enabled providers
    wp_localize_script(
        'fluent-mcp-agent-app',
        'fluentMcpAgent',
        [
            'enabledProviders' => $enabled_providers,
            'defaultProvider' => $default_provider,
            'availableModels' => $available_models,
            'openaiUrl' => get_option('fluent_mcp_agent_openai_url'),
            'claudeUrl' => get_option('fluent_mcp_agent_anthropic_url'),
            'ollamaUrl' => get_option('fluent_mcp_agent_ollama_url'),
            'nonce' => wp_create_nonce('wp_rest'),
            'abilities' => $tools
        ]
    );
}

// Get available models for the selected/default provider and make available to JS

/**
 * Get available models for a given provider.
 *
 * @param string $provider Provider slug (e.g. 'ollama', 'openai', 'anthropic')
 * @return array Array of model slugs/names, or empty array if not supported/fails
 */
function fluent_mcp_agent_get_available_models($provider) {
    $models = [];
    switch ($provider) {
        case 'ollama':
            // Try to fetch from Ollama local API
            $ollama_url = 'http://localhost:11434/';
            $endpoint = trailingslashit($ollama_url) . 'api/tags';

            $response = wp_remote_get($endpoint, [
                'timeout' => 4,
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (is_array($data) && !empty($data['models']) && is_array($data['models'])) {
                    foreach ($data['models'] as $model) {
                        if (!empty($model['model'])) {
                            $models[] = $model['model'];
                        }
                    }
                }
            }
            break;
        case 'openai':
            // Fetch dynamic models for OpenAI using API key if available
            $models = [];
            $openai_api_key = get_option('fluent_mcp_agent_openai_api_key', '');

            if (!empty($openai_api_key)) {
                $response = wp_remote_get(
                    'https://api.openai.com/v1/models',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $openai_api_key,
                            'Content-Type'  => 'application/json',
                        ],
                        'timeout' => 10,
                    ]
                );

                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);

                    if (is_array($data) && !empty($data['data']) && is_array($data['data'])) {
                        foreach ($data['data'] as $model) {
                            // Only include chat/completion models commonly used
                            if (!empty($model['id']) && preg_match('/^gpt-/', $model['id'])) {
                                $models[] = $model['id'];
                            }
                        }
                    }
                }
            }

            // Fallback to static models if nothing found
            if (empty($models)) {
                $models = [
                    'gpt-3.5-turbo',
                    'gpt-4o',
                    'gpt-4-turbo',
                    'gpt-4',
                ];
            }
            break;
        case 'anthropic':
            // Fetch dynamic models for Anthropic (Claude) using API key if available
            $models = [];
            $anthropic_api_key = get_option('fluent_mcp_agent_anthropic_api_key', '');

            if (!empty($anthropic_api_key)) {
                $response = wp_remote_post(
                    'https://api.anthropic.com/v1/models',
                    [
                        'headers' => [
                            'x-api-key'    => $anthropic_api_key,
                            'anthropic-version' => '2023-06-01',
                            'Content-Type' => 'application/json',
                        ],
                        'timeout' => 10,
                    ]
                );

                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);

                    if (is_array($data) && !empty($data['models']) && is_array($data['models'])) {
                        foreach ($data['models'] as $model) {
                            if (is_array($model) && !empty($model['name']) && preg_match('/^claude-/', $model['name'])) {
                                $models[] = $model['name'];
                            } elseif (is_string($model) && preg_match('/^claude-/', $model)) {
                                $models[] = $model;
                            }
                        }
                    }
                }
            }

            // Fallback to static models if nothing found
            if (empty($models)) {
                $models = [
                    'claude-3-opus-20240229',
                    'claude-3-sonnet-20240229',
                    'claude-3-haiku-20240307',
                ];
            }
            break;
        default:
            // not supported
            $models = [];
    }

    return $models;
}

// Add `type="module"` to the script tag for this handle
add_filter('script_loader_tag', 'fluent_mcp_agent_module_script', 10, 3);
function fluent_mcp_agent_module_script($tag, $handle, $src) {
    if ($handle === 'fluent-mcp-agent-app') {
        // Replace normal script with module script
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    }
    return $tag;
}

function fluent_mcp_agent_chat_page() {

    $enabled_providers = fluent_mcp_agent_get_enabled_providers();

    if (empty($enabled_providers) || count($enabled_providers) <= 0) {
    ?>
    <div class="notice notice-warning" style="margin:2em 0;">
        <p>
            <?php _e( 'No AI provider is enabled. Please <a href="' . esc_url( admin_url( 'admin.php?page=fluent-mcp-agent-settings' ) ) . '">configure your AI providers in Settings</a> to start using Fluent MCP Agent.', 'fluent-mcp-agent' ); ?>
        </p>
    </div>
    <?php
    }
    else {
        echo '<div id="fluent-assistant-chat-root"></div> <div id="fluent-assistant-modal" style="width: 100%; height: 100%"></div>';
    }
}


function fluent_mcp_agent_settings_page() {
    ?>
    <div class="wrap">
        <h1>Fluent MCP Agent Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fluent_mcp_agent_settings');
            do_settings_sections('fluent-mcp-agent-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function fluent_mcp_server_is_mcp_adapter_available() {
    return class_exists( '\WP\MCP\Core\McpAdapter' );
}


require_once  FLUENT_MCP_AGENT_PATH . 'vendor/autoload_packages.php';

use WP\MCP\Core\McpAdapter;
use WP\MCP\Cli\StdioServerBridge;

function fluent_mcp_agent_mcp_servers_page() {

    // 1. Check if MCP Adapter is available
    if ( ! class_exists( McpAdapter::class ) ) {
        // Handle missing dependency (show admin notice, etc.)
        return;
    }

    $adapter = McpAdapter::instance();
    $adapter->init();

    $servers_list = $adapter->get_servers();
    ?>
    <h2>MCP Servers</h2>
    <?php if ( empty( $servers_list ) ) : ?>
        <p>No MCP servers registered.</p>
    <?php else : ?>
        <table class="widefat striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th>Server ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Route</th>
                    <th>Version</th>
                    <th>Available Tools</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $servers_list as $id => $server ) : ?>
                    <tr>
                        <td><?php echo esc_html( $server->get_server_id() ); ?></td>
                        <td><?php echo esc_html( $server->get_server_name() ); ?></td>
                        <td><?php echo esc_html( $server->get_server_description() ); ?></td>
                        <td>
                            <?php
                                $ns   = method_exists( $server, 'get_server_route_namespace' ) ? $server->get_server_route_namespace() : '';
                                $route = method_exists( $server, 'get_server_route' ) ? $server->get_server_route() : '';
                                echo esc_html( trim( $ns . '/' . ltrim($route, '/') , '/' ) );
                            ?>
                        </td>
                        <td><?php echo esc_html( $server->get_server_version() ); ?></td>
                        <td>
                            <?php
                            // Attempt to fetch available tools for this server
                            $tools = array();
                            if ( method_exists( $server, 'get_tools' ) ) {
                                $tools = $server->get_tools();
                            } elseif ( property_exists( $server, 'tools' ) ) {
                                $tools = $server->tools;
                            }
                            if ( !empty($tools) && is_array($tools) ) {
                                echo '<ul style="margin:0; padding-left: 1.2em;">';
                                foreach ( $tools as $key => $tool ) {
                                   
                                    echo '<li>' . esc_html( (string) $key ) . '</li>';
                                    
                                }
                                echo '</ul>';
                            } else {
                                echo '<span style="color:#999;">—</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}






