<?php
/**
 * Plugin Name: Fluent MCP Agent
 * Plugin URI:  https://example.com/fluent-mcp-agent
 * Description: Fluent MCP Agent is an AI agent for WordPress with chat, settings, and MCP configuration. Offline-first using Ollama.
 * Version:     0.1.0
 * Author:      Fluent MCP Agent
 * Author URI:  https://example.com
 * License:     GPL v2 or later
 * Text Domain: fluent-mcp-agent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * --------------------------------------------------
 * Plugin Constants
 * --------------------------------------------------
 */
define( 'FLUENT_MCP_AGENT_VERSION', '0.1.0' );
define( 'FLUENT_MCP_AGENT_FILE', __FILE__ );
define( 'FLUENT_MCP_AGENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLUENT_MCP_AGENT_URL', plugin_dir_url( __FILE__ ) );

/**
 * --------------------------------------------------
 * Plugin Activation / Deactivation
 * --------------------------------------------------
 */
function fluent_mcp_agent_activate() {

    // setup initial options with null value

    // ollama
    add_option( 'fluent_mcp_agent_ai_provider', '' );
    add_option( 'fluent_mcp_agent_ollama_model', '');
    add_option( 'fluent_mcp_agent_ollama_url', '' );

    //openai
    add_option('fluent_mcp_agent_openai_api_key', '');

    //claude
    add_option('fluent_mcp_agent_anthropic_api_key', '');


    add_option( 'fluent_mcp_agent_default_provider', '' );


    add_option( 'fluent_mcp_agent_enable_ollama', 0 );
    add_option( 'fluent_mcp_agent_enable_openai', 0 );
    add_option( 'fluent_mcp_agent_enable_anthropic', 0 );


    // openai (default: https://api.openai.com/v1/chat/completions)
    add_option('fluent_mcp_agent_openai_url', 'https://api.openai.com/v1/chat/completions');

    // claude (default: https://api.anthropic.com/v1/messages)
    add_option('fluent_mcp_agent_anthropic_url', 'https://api.anthropic.com/v1/messages');

    add_rewrite_rule(
        '^api/chat/?$',
        'index.php?rest_route=/fluent-mcp-agent/v1/api/chat',
        'top'
    );

    flush_rewrite_rules();

}

register_activation_hook( __FILE__, 'fluent_mcp_agent_activate' );

function fluent_mcp_agent_deactivate() {
    // Reserved for cleanup tasks later
}
register_deactivation_hook( __FILE__, 'fluent_mcp_agent_deactivate' );


// Register the REST route for proxying chat requests to the selected provider API
add_action('rest_api_init', function () {
    register_rest_route('fluent-mcp-agent/v1', '/api/chat', array(
        'methods'             => ['POST', 'OPTIONS'],
        'callback'            => 'fluent_mcp_agent_determine_provider_proxy_chat',
        'permission_callback' => '__return_true',
    ));
});


// Enqueue the global.js script to be loaded in the frontend
add_action('wp_enqueue_scripts', function () {
    // Register and enqueue the script
    wp_enqueue_script(
        'fluent-mcp-agent-global',
        plugins_url('assets/global.js', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/global.js'),
        true // load in footer
    );
});




function fluent_mcp_agent_determine_provider_proxy_chat(\WP_REST_Request $request) {

    remove_all_actions('rest_api_init');
    remove_all_filters('rest_pre_serve_request');

    ignore_user_abort(true);
    set_time_limit(0);


    // get provider and model
    $body = $request->get_body();
    $data = json_decode($body, true);

    $provider = isset($data['provider']) ? $data['provider'] : null;
    $model = isset($data['model']) ? $data['model'] : null;

    if($provider == 'ollama') {
        ollama_proxy_chat($request, $model);
    }
    elseif($provider == 'openai')  {
        openai_proxy_chat($request, $model);
    }
    elseif($provider == 'claude')  {
        claude_proxy_chat($request);
    }

    exit;
}

use WP\MCP\Core\McpAdapter;
use WP\MCP\Abilities\ExecuteAbilityAbility;

function generate_agent_knowledge(): string {

    $site_name   = get_bloginfo( 'name' );
    $site_desc   = get_bloginfo( 'description' );
    $admin_email = get_bloginfo( 'admin_email' );
    $site_url    = home_url();

    $knowledge = [];

    /*
     * --------------------------------------------------
     * AGENT IDENTITY
     * --------------------------------------------------
     */
    $knowledge[] = "You are an intelligent MCP AI agent embedded inside a WordPress website.";
    $knowledge[] = "You can perform actions using WordPress abilities exposed via MCP tools.";
    $knowledge[] = "";
    $knowledge[] = "This site’s info:";
    $knowledge[] = "- Site Name: {$site_name}";
    $knowledge[] = "- Site URL: {$site_url}";
    if ( $site_desc ) {
        $knowledge[] = "- Site Description: {$site_desc}";
    }
    $knowledge[] = "- Admin Contact: {$admin_email}";
    $knowledge[] = "";

    return implode("\n", $knowledge);

}

function ability_to_tool( $ability ) {
    $name = $ability->get_name();

    $input_schema = $ability->get_input_schema();

    $parameters = [
        'type' => 'object',
        'properties' => new stdClass(),
    ];

    if (
        isset($input_schema['properties']) &&
        is_array($input_schema['properties']) &&
        !empty($input_schema['properties'])
    ) {
        $parameters['properties'] = $input_schema['properties'];
    }

    if (
        !empty($input_schema['required']) &&
        is_array($input_schema['required'])
    ) {
        $parameters['required'] = array_values($input_schema['required']);
    }

    return [
        'type' => 'function',
        'function' => [
            'name'        => $name,
            'description' => $ability->get_description(),
            'parameters'  => $parameters,
        ],
    ];
}
/**
 * Streaming proxy for Ollama
 */
function ollama_proxy_chat(\WP_REST_Request $request, $model) {

    // Ensure current user context is set for REST API
    if (!get_current_user_id()) {
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            wp_set_current_user($current_user->ID);
        }
    }

    $body = $request->get_json_params();
    
    $is_tool_available = $body['functionCallingEnabled'];

    if($is_tool_available) {

        $tools = [];
    
        // First, add tools from request body if provided
        if (!empty($body['tools']) && is_array($body['tools'])) {
            $tools = array_merge($tools, $body['tools']);
        }
        
        // Then, add WordPress abilities as tools
        $wp_abilities = wp_get_abilities() ?? [];
        if (!empty($wp_abilities) && is_array($wp_abilities)) {
            foreach($wp_abilities as $ability) {
                $tools[] = ability_to_tool($ability);
            }
        }

        ds($tools);

    }
    

    $messages = array_merge(
        [
            [
                'role' => 'system',
                'content' => generate_agent_knowledge()
            ],
        ],
        array_map(function ($m) {
            return [
                'role' => $m['role'],
                'content' => is_array($m['content'])
                    ? implode('', array_column($m['content'], 'text'))
                    : $m['content']
            ];
        }, $body['messages'] ?? [])
    );

    // Set headers for streaming
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    if (ob_get_level()) ob_end_clean();

    // Increase PHP execution time for long-running tool calls
    set_time_limit(300); // 5 minutes

    // Main loop to handle tool calls
    $maxIterations = 5;
    $iteration = 0;

    while ($iteration < $maxIterations) {
        $iteration++;
        
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ];

        if(isset($tools)) {
            $payload['tools'] = $tools;
        }

        $buffer = '';
        $streamingAllowed = true;
        $toolCallDetected = false;
        $toolCallData = null;

        $ch = curl_init('http://localhost:11434/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, &$streamingAllowed, &$toolCallDetected, &$toolCallData) {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if (!$line) continue;

                    $json = json_decode($line, true);
                    if (!$json) continue;

                    if($json['error']) {
                        echo "3:" . json_encode($json['error']) . "\n";
                        flush();
                    }

                    $content = $json['message']['content'] ?? '';
                    $done = !empty($json['done']);

                    if ($content !== '') {
                        $buffer .= $content;
                    }

                    // Stop streaming if JSON detected
                    if ($streamingAllowed && preg_match('/^\s*\{/', $buffer)) {
                        $streamingAllowed = false;
                    }

                    // Stream natural language only
                    if ($streamingAllowed && $content !== '') {
                        echo "0:" . json_encode($content) . "\n";
                        flush();
                    }

                    if ($done) {
                        $final = trim($buffer);

                        // Check for tool call
                        if ($final !== '' && $final[0] === '{') {
                            $parsed = json_decode($final, true);

                            if (json_last_error() === JSON_ERROR_NONE && isset($parsed['name'])) {
                                $toolCallDetected = true;
                                $toolCallData = $parsed;
                            }
                        }
                    }
                }

                return strlen($data);
            }
        ]);

        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo "0:" . json_encode("Error: " . $curlError) . "\n";
            flush();
            exit;
        }



        // If no tool call, we're done
        if (!$toolCallDetected || !$toolCallData) {
            break;
        }

        // Process tool call
        $toolName = $toolCallData['name'];
        $toolArgs = $toolCallData['arguments'] ?? [];
        $toolId = uniqid("tool_");

        // Execute the ability
        $toolResult = null;
        
        try {
            if (function_exists('wp_get_ability')) {
                $ability = wp_get_ability($toolName);
                ds('ability');
                ds($ability);
                // Temporarily log in as admin for this request
                $admin_user = get_users([
                    'role'    => 'administrator',
                    'number'  => 1,
                    'orderby' => 'ID',
                    'order'   => 'ASC'
                ]);
                if (!empty($admin_user) && isset($admin_user[0])) {
                    wp_set_current_user($admin_user[0]->ID);
                }

                ds('admin user');
                ds($admin_user);

                
                if ($ability) {
                    ds('if ability found');
                    $toolResult = $ability->execute($toolArgs);
                } else {
                    $toolResult = [
                        'error' => "Ability '$toolName' not found.",
                        'toolName' => $toolName
                    ];
                }
            } else {
                $toolResult = [
                    'error' => 'wp_get_ability function not found.',
                    'toolName' => $toolName
                ];
            }
        } catch (\Throwable $e) {
            $toolResult = [
                'error' => $e->getMessage(),
                'toolName' => $toolName
            ];
        }

        // Send tool result back to client
        echo "3:" . json_encode([
            "toolCallId" => $toolId,
            "result" => $toolResult
        ]) . "\n";
        flush();

        // Add tool call and result to messages for next iteration
        $messages[] = [
            'role' => 'assistant',
            'content' => json_encode($toolCallData)
        ];

        $messages[] = [
            'role' => 'tool',
            'content' => json_encode($toolResult)
        ];

        // Continue loop to get final response from AI
    }

    exit;
}


/**
 * Streaming proxy for OpenAI
 */
function openai_proxy_chat(\WP_REST_Request $request, $model) {

    $body_arr = $request->get_json_params();


    $openai_url = get_option(
        'fluent_mcp_agent_openai_url',
        'https://api.openai.com/v1/chat/completions'
    );
    $api_key = get_option('fluent_mcp_agent_openai_api_key', '');

    if (empty($api_key)) {
        echo "2:" . json_encode(['error' => 'OpenAI API key missing']) . "\n";
        flush();
        exit;
    }

    // Merge tools from request body with WordPress abilities
    $tools = [];
    
    // First, add tools from request body if provided
    if (!empty($body_arr['tools']) && is_array($body_arr['tools'])) {
        $tools = array_merge($tools, $body_arr['tools']);
    }
    
    // Then, add WordPress abilities as tools
    $wp_abilities = wp_get_abilities() ?? [];
    if (!empty($wp_abilities) && is_array($wp_abilities)) {
        foreach($wp_abilities as $ability) {
            $tools[] = ability_to_tool($ability);
        }
    }
    
    // Set tools in body if we have any
    if (!empty($tools)) {
        $body_arr['tools'] = $tools;
    }

    // Force streaming
    $body_arr['model']  = $model;
    $body_arr['stream'] = true;

    // assistant-ui stream headers (NOT SSE)
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    if (ob_get_level()) {
        ob_end_clean();
    }

    $ch = curl_init($openai_url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body_arr),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function ($ch, $data) {

            $trim = trim($data);

            if ($trim !== '' && $trim[0] === '{') {
                $json = json_decode($trim, true);
        
                if (isset($json['error'])) {
                    $msg = is_array($json['error'])
                        ? ($json['error']['message'] ?? 'Unknown OpenAI error')
                        : $json['error'];

                    echo "0:" . json_encode($msg) . "\n";
                    flush();
                    return strlen($data);
                }
            }

            $lines = explode("\n", $data);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));

                if ($payload === '[DONE]') {
                    // end of stream
                    continue;
                }

                $json = json_decode($payload, true);
                if (!$json) continue;

                $delta = $json['choices'][0]['delta']['content'] ?? '';

                if ($delta !== '') {
                    // assistant-ui text chunk
                    echo "0:" . json_encode($delta) . "\n";
                    flush();
                }
            }

            return strlen($data);
        }
    ]);

    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
        $len = strlen($header);
        return $len;
    });

    curl_exec($ch);

    if ($err = curl_error($ch)) {
        echo "2:" . json_encode(['error' => $err]) . "\n";
        flush();
    }

    curl_close($ch);
    exit;
}

/**
 * Streaming proxy for Claude (Anthropic)
 */
function claude_proxy_chat(\WP_REST_Request $request) {
    $body = $request->get_body();
    $body_arr = json_decode($body, true);

    $anthropic_url = get_option('fluent_mcp_agent_anthropic_url', 'https://api.anthropic.com/v1/messages');
    $api_key = get_option('fluent_mcp_agent_anthropic_api_key', '');

    if (empty($api_key)) {
        return new WP_Error('claude_api_key_missing', 'Anthropic (Claude) API Key not configured', ['status' => 500]);
    }

    // Convert OpenAI format to Anthropic format
    $system_message = '';
    $messages = [];
    
    if (isset($body_arr['messages']) && is_array($body_arr['messages'])) {
        foreach ($body_arr['messages'] as $msg) {
            if ($msg['role'] === 'system') {
                $system_message = $msg['content'];
            } else {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
    }

    $claude_body = [
        'messages' => $messages,
        'max_tokens' => $body_arr['max_tokens'] ?? 4096,
        'stream' => true, // Enable streaming
    ];

    if (!empty($system_message)) {
        $claude_body['system'] = $system_message;
    }

    // Set model
    $model = $request->get_param('model');
    if ($model) {
        $claude_body['model'] = $model;
    } else {
        $claude_body['model'] = $body_arr['model'] ?? 'claude-3-5-sonnet-20241022';
    }

    // Set headers for SSE streaming
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    // Disable PHP output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Initialize cURL for streaming
    $ch = curl_init($anthropic_url);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($claude_body),
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function($ch, $data) use ($claude_body) {
            // Claude sends SSE format with different event types
            $lines = explode("\n", $data);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip empty lines and non-data lines
                if (empty($line) || !str_starts_with($line, 'data: ')) {
                    continue;
                }
                
                // Remove "data: " prefix
                $json_str = substr($line, 6);
                
                // Check for [DONE]
                if ($json_str === '[DONE]') {
                    echo "data: [DONE]\n\n";
                    flush();
                    continue;
                }
                
                $json = json_decode($json_str, true);
                if (!$json) continue;
                
                // Handle different Claude event types
                if ($json['type'] === 'content_block_delta') {
                    $content = $json['delta']['text'] ?? '';
                    
                    // Convert to OpenAI streaming format
                    $openai_chunk = [
                        'id' => 'chatcmpl-' . uniqid(),
                        'object' => 'chat.completion.chunk',
                        'created' => time(),
                        'model' => $claude_body['model'],
                        'choices' => [
                            [
                                'index' => 0,
                                'delta' => [
                                    'content' => $content
                                ],
                                'finish_reason' => null
                            ]
                        ]
                    ];
                    
                    echo "data: " . json_encode($openai_chunk) . "\n\n";
                    flush();
                }
                else if ($json['type'] === 'message_stop') {
                    echo "data: [DONE]\n\n";
                    flush();
                }
            }
            
            return strlen($data);
        }
    ]);

    curl_exec($ch);
    curl_close($ch);
    exit;
}





add_action('admin_init', function () {
    $disablePages = [
        'fluent-mcp-agent'
    ];

    if (isset($_GET['page']) && in_array($_GET['page'], $disablePages)) {
        remove_all_actions('admin_notices');
    }


    $adapter = McpAdapter::instance();
    $adapter->init();

    // Cache the adapter server information for later use
    $servers = $adapter->get_servers();
    set_transient('fluent_mcp_agent_cached_servers', $servers, MINUTE_IN_SECONDS * 10);

}, 10, 1);

















// add_action('init', function() {

//     dd($adapter);
//     // dd($adapter);
// });










/**
 * --------------------------------------------------
 * Load Plugin Files
 * --------------------------------------------------
 */

// Admin-only functionality
if ( is_admin() ) {
    require_once FLUENT_MCP_AGENT_PATH . 'includes/admin-menu.php';
    require_once FLUENT_MCP_AGENT_PATH . 'includes/settings.php';
    require_once FLUENT_MCP_AGENT_PATH . 'includes/mcp-servers.php';
}

// Shared / backend logic
require_once FLUENT_MCP_AGENT_PATH . 'includes/ajax.php';
// require_once FLUENT_HOST_PATH . 'includes/ollama-client.php';


/**
 * --------------------------------------------------
 * Future-ready: Providers & Tools
 * --------------------------------------------------
 * These files will later contain:
 * - Provider abstraction (ChatGPT, Claude, Ollama)
 * - Tool / capability registration for WordPress
 * - MCP server discovery
 */
// require_once FLUENT_HOST_PATH . 'includes/providers/provider-manager.php';
// require_once FLUENT_HOST_PATH . 'includes/tools/wp-tools.php';
