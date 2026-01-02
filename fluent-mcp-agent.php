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
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));
});


// Enqueue the global.js script to be loaded in the frontend
// add_action('wp_enqueue_scripts', function () {
//     // Register and enqueue the script
//     wp_enqueue_script(
//         'fluent-mcp-agent-global',
//         plugins_url('assets/global.js', __FILE__),
//         array(),
//         filemtime(plugin_dir_path(__FILE__) . 'assets/global.js'),
//         true // load in footer
//     );
// });


require_once FLUENT_MCP_AGENT_PATH . 'includes/agent-knowledge.php';

require_once FLUENT_MCP_AGENT_PATH . 'includes/mcp-abilities.php';






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


// function generate_tools_knowledge(): string {

//     $knowledge = [];

//     $knowledge[] = "=== MCP TOOLS & ABILITIES (AUTHORITATIVE) ===";
//     $knowledge[] = "This section defines the ONLY tools you are allowed to use.";
//     $knowledge[] = "If a tool is not listed here, it DOES NOT EXIST.";
//     $knowledge[] = "You must NEVER guess, invent, or assume tools.";
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * META ABILITIES
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== META ABILITIES (SYSTEM LEVEL) ===";
//     $knowledge[] = "";

//     $knowledge[] = "mcp-adapter/discover-abilities";
//     $knowledge[] = "- Purpose: List all registered abilities";
//     $knowledge[] = "- Parameters: none";
//     $knowledge[] = "- Use ONLY when the user explicitly asks what tools exist";
//     $knowledge[] = "";

//     $knowledge[] = "mcp-adapter/get-ability-info";
//     $knowledge[] = "- Purpose: Inspect schema of a known ability";
//     $knowledge[] = "- Parameters: ability_name (string, required)";
//     $knowledge[] = "- Use BEFORE calling an unfamiliar ability";
//     $knowledge[] = "";

//     $knowledge[] = "mcp-adapter/execute-ability";
//     $knowledge[] = "- Purpose: Execute an ability indirectly";
//     $knowledge[] = "- Use ONLY if direct invocation is unavailable";
//     $knowledge[] = "- Never use by default";
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * REGISTERED ABILITIES (LIVE SNAPSHOT)
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== REGISTERED ABILITIES (LIVE SNAPSHOT) ===";
//     $knowledge[] = "The following is the COMPLETE list of currently registered abilities.";
//     $knowledge[] = "";

//     $abilities = wp_get_abilities();

//     if (empty($abilities)) {
//         $knowledge[] = "No abilities are currently registered.";
//         return implode("\n", $knowledge);
//     }

//     $grouped = [];

//     foreach ($abilities as $ability) {
//         $grouped[$ability->get_category()][] = $ability;
//     }

//     foreach ($grouped as $category => $items) {

//         $knowledge[] = "CATEGORY: " . strtoupper($category);
//         $knowledge[] = "";

//         foreach ($items as $ability) {

//             $knowledge[] = "Tool Name: " . $ability->get_name();
//             $knowledge[] = "Description: " . $ability->get_description();

//             $schema = $ability->get_input_schema();
//             if (!empty($schema['properties'])) {
//                 $knowledge[] = "Parameters:";
//                 foreach ($schema['properties'] as $key => $info) {
//                     $required = in_array($key, $schema['required'] ?? [], true);
//                     $knowledge[] = "- {$key}" . ($required ? " (REQUIRED)" : " (optional)");
//                 }
//             } else {
//                 $knowledge[] = "Parameters: none";
//             }

//             $knowledge[] = "";
//         }
//     }

//     /*
//      * --------------------------------------------------
//      * ABSOLUTE TOOL RULES
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== TOOL USAGE RULES (MANDATORY) ===";
//     $knowledge[] = "- Never call tools that do not exist";
//     $knowledge[] = "- Never re-label tools under different plugins";
//     $knowledge[] = "- Never retry failed tools automatically";
//     $knowledge[] = "- Never apologize more than once";
//     $knowledge[] = "- If no tool exists, say so clearly and stop";
//     $knowledge[] = "";

//     return implode("\n", $knowledge);
// }


/**
 * --------------------------------------------------
 * AGENT / SITE KNOWLEDGE
 * --------------------------------------------------
 */
// function generate_agent_knowledge(): string {

//     if (!function_exists('get_bloginfo')) {
//         return '';
//     }

//     require_once ABSPATH . 'wp-admin/includes/plugin.php';
//     require_once ABSPATH . 'wp-admin/includes/update.php';
//     require_once ABSPATH . 'wp-admin/includes/theme.php';

//     $knowledge = [];

//     /*
//      * --------------------------------------------------
//      * AUTHORITATIVE SNAPSHOT
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== AUTHORITATIVE SITE SNAPSHOT ===";
//     $knowledge[] = "This is a COMPLETE, CURRENT snapshot of this WordPress site.";
//     $knowledge[] = "This data is GROUND TRUTH.";
//     $knowledge[] = "Do NOT provide generic WordPress guidance.";
//     $knowledge[] = "Do NOT suggest dashboards, plugins, WP-CLI, or manual steps.";
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * AGENT IDENTITY
//      * --------------------------------------------------
//      */
//     $knowledge[] = "You are an MCP AI agent embedded inside this WordPress site.";
//     $knowledge[] = "Answer questions directly using this snapshot whenever possible.";
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * SITE INFO
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== SITE INFORMATION ===";
//     $knowledge[] = "- Name: " . get_bloginfo('name');
//     $knowledge[] = "- URL: " . home_url();
//     $knowledge[] = "- Description: " . get_bloginfo('description');
//     $knowledge[] = "- Admin Email: " . get_bloginfo('admin_email');
//     $knowledge[] = "- WordPress Version: " . get_bloginfo('version');
//     $knowledge[] = "- Locale: " . get_locale();
//     $knowledge[] = "- Multisite: " . (is_multisite() ? 'Yes' : 'No');
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * ENVIRONMENT & DEBUG
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== ENVIRONMENT & DEBUG ===";
//     $knowledge[] = "- PHP Version: " . PHP_VERSION;
//     $knowledge[] = "- WP_DEBUG: " . ((defined('WP_DEBUG') && WP_DEBUG) ? 'ENABLED' : 'DISABLED');
//     $knowledge[] = "- WP_DEBUG_LOG: " . ((defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) ? 'ENABLED' : 'DISABLED');
//     $knowledge[] = "- WP_DEBUG_DISPLAY: " . ((defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? 'ENABLED' : 'DISABLED');

//     if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
//         $knowledge[] = "- Debug Log Path: " . (is_string(WP_DEBUG_LOG) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log');
//     }

//     $knowledge[] = "- Environment Type: " . (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'Not defined');
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * PLUGINS (FINAL AUTHORITY)
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== INSTALLED PLUGINS (FINAL AUTHORITY) ===";

//     $plugins        = get_plugins();
//     $active         = get_option('active_plugins', []);
//     $updates        = get_site_transient('update_plugins');

//     foreach ($plugins as $file => $plugin) {

//         $line = "- {$plugin['Name']} (v{$plugin['Version']})";
//         $line .= in_array($file, $active, true) ? " [ACTIVE]" : " [INACTIVE]";

//         if (isset($updates->response[$file])) {
//             $line .= " [UPDATE → {$updates->response[$file]->new_version}]";
//         }

//         $knowledge[] = $line;
//     }

//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * THEME
//      * --------------------------------------------------
//      */
//     $theme = wp_get_theme();

//     $knowledge[] = "=== ACTIVE THEME ===";
//     $knowledge[] = "- Name: " . $theme->get('Name');
//     $knowledge[] = "- Version: " . $theme->get('Version');
//     $knowledge[] = "- Slug: " . $theme->get_stylesheet();
//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * MCP CONTEXT
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== MCP STATUS ===";
//     if (class_exists('\WP\MCP\Core\McpAdapter')) {
//         $knowledge[] = "- MCP Adapter: ACTIVE";
//         if (defined('\WP\MCP\Core\McpAdapter::VERSION')) {
//             $knowledge[] = "- MCP Version: " . constant('\WP\MCP\Core\McpAdapter::VERSION');
//         }
//     } else {
//         $knowledge[] = "- MCP Adapter: NOT ACTIVE";
//     }

//     $knowledge[] = "";

//     /*
//      * --------------------------------------------------
//      * ANSWERING RULES
//      * --------------------------------------------------
//      */
//     $knowledge[] = "=== ANSWERING RULES ===";
//     $knowledge[] = "- If information exists above, answer directly.";
//     $knowledge[] = "- Never suggest manual steps.";
//     $knowledge[] = "- Never hallucinate plugins, tools, or data.";
//     $knowledge[] = "- Final responses MUST be plain text.";
//     $knowledge[] = "";

//     return implode("\n", $knowledge);
// }


function fluent_is_tool_failure($toolResult): bool {
    if ($toolResult === null) {
        return true;
    }

    if (is_array($toolResult) && isset($toolResult['error'])) {
        return true;
    }

    return false;
}

function fluent_stringify_tool_error($toolResult): string {
    if (is_array($toolResult) && isset($toolResult['error'])) {
        return (string) $toolResult['error'];
    }

    return 'Unknown error occurred while executing the tool.';
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
        // Ensure properties is an associative array (object when JSON encoded)
        // Convert any numeric keys to string keys if needed
        $properties = [];
        foreach ($input_schema['properties'] as $key => $value) {
            $properties[(string)$key] = $value;
        }
        $parameters['properties'] = $properties;
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
// function ollama_proxy_chat(\WP_REST_Request $request, $model) {

//     // Ensure current user context is set for REST API
//     if (!get_current_user_id()) {
//         $current_user = wp_get_current_user();
//         if ($current_user->ID) {
//             wp_set_current_user($current_user->ID);
//         }
//     }

//     $body = $request->get_json_params();
    
//     $is_tool_available = $body['functionCallingEnabled'];

//     if($is_tool_available) {

//         $tools = [];
//         // $abilities = wp_get_abilities();



//         // foreach($abilities as $ability) {
//         //     $tools[] = ability_to_tool($ability);
//         // }

//         // FIX: Remove debug statement, properly handle tools array

//         // $plugin_information_ability = wp_get_ability('fluent-mcp-agent/get-plugins-information');


//         // ability_to_tool($plugin_information_ability);
//         // if ($plugin_information_ability && is_array($plugin_information_ability)) {
//         //     $tools[] = ability_to_tool($plugin_information_ability);
//         // }

//         // ds($tools);

//         // ds($body['tools']);
//         // return;



//         // If body contains tools, only keep those tools that match (by name) the tools present in the body
//         // if (!empty($body['tools']) && is_array($body['tools'])) {
//         //     $body_tool_names = array_map(function($t) {
//         //         // The tool name might be in 'function' or directly declared
//         //         if (isset($t['function']['name'])) {
//         //             return $t['function']['name'];
//         //         } elseif (isset($t['name'])) {
//         //             return $t['name'];
//         //         }
//         //         return null;
//         //     }, $body['tools']);
//         //     $body_tool_names = array_filter($body_tool_names);

//         //     // Only keep tools whose function name matches those specified in the body
//         //     $tools = array_filter($tools, function($tool) use ($body_tool_names) {
//         //         if (isset($tool['function']['name'])) {
//         //             return in_array($tool['function']['name'], $body_tool_names, true);
//         //         }
//         //         return false;
//         //     });
//         //     // Re-index the array to maintain a plain array
//         //     $tools = array_values($tools);
//         // }

//         $tools = $body['tools'];
        
        
//         // First, add tools from request body if provided
//         // if (!empty($body['tools']) && is_array($body['tools'])) {
//         //     $tools = $body['tools'];
//         // }
        
//     }

//     $content = get_agent_identity();

//     $content .=  "\n" . get_site_information();

//     $content .= "\n" .  get_environment_debug_info();

//     $content .= "\n" . get_tool_calling_knowledge();



//     // if($tools) {
//     //     $content .= generate_tools_knowledge();
//     // }


//     $messages = array_merge(
//         [
//             [
//                 'role' => 'system',
//                 'content' => $content
//             ],
//         ],
//         array_map(function ($m) {
//             return [
//                 'role' => $m['role'],
//                 'content' => is_array($m['content'])
//                     ? implode('', array_column($m['content'], 'text'))
//                     : $m['content']
//             ];
//         }, $body['messages'] ?? [])
//     );


//     // Set headers for streaming
//     header('Content-Type: text/plain; charset=utf-8');
//     header('Cache-Control: no-cache');
//     header('X-Accel-Buffering: no');
//     header('Connection: keep-alive');

//     if (ob_get_level()) ob_end_clean();

//     // Increase PHP execution time for long-running tool calls
//     set_time_limit(300); 

//     // Main loop to handle tool calls
//     $maxIterations = 5;
//     $iteration = 0;

//     while ($iteration < $maxIterations) {
//         $iteration++;
        
//         $payload = [
//             'model' => $model,
//             'messages' => $messages,
//             'stream' => true,
//         ];

//         if(isset($tools)) {
//             $payload['tools'] = $tools;
//         }

//         $buffer = '';
//         $streamingAllowed = true;
//         $toolCallDetected = false;
//         $toolCallData = null;
//         $nonce = wp_create_nonce('wp_rest');

//         $ch = curl_init('http://localhost:11434/api/chat');
//         curl_setopt_array($ch, [
//             CURLOPT_POST => true,
//             CURLOPT_POSTFIELDS => json_encode($payload),
//             CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
//             CURLOPT_RETURNTRANSFER => false,
//             CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, &$streamingAllowed, &$toolCallDetected, &$toolCallData, $nonce) {
//                 foreach (explode("\n", $data) as $line) {
//                     $line = trim($line);
//                     if (!$line) continue;

//                     $json = json_decode($line, true);
//                     if (!$json) continue;

//                     if($json['error']) {
//                         echo "3:" . json_encode($json['error']) . "\n";
//                         flush();
//                     }

//                     $content = $json['message']['content'] ?? '';
//                     $done = !empty($json['done']);

//                     if ($content !== '') {
//                         $buffer .= $content;
//                     }

//                     // Stop streaming if JSON detected
//                     if ($streamingAllowed && preg_match('/^\s*\{/', $buffer)) {
//                         $streamingAllowed = false;  
//                     }


//                     // Stream natural language only
//                     if ($streamingAllowed && $content !== '') {
//                         echo "0:" . json_encode($content) . "\n";
//                         flush();
//                     }

//                     if ($done) {
//                         $final = trim($buffer);
                        
//                         ds('raw final');
//                         ds($final);
                    
//                         // ✅ EXTRACT JSON FROM MARKDOWN CODE BLOCKS OR MIXED CONTENT
//                         // Pattern 1: ```json ... ``` or ``` ... ```
//                         if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $final, $matches)) {
//                             $final = $matches[1];
//                             ds('extracted from markdown block');
//                             ds($final);
//                         }
//                         // Pattern 2: Text followed by JSON (take only the JSON part)
//                         elseif (preg_match('/\{[^{]*"name"\s*:\s*"[^"]+"/s', $final, $matches)) {
//                             // Find the last occurrence of { followed by "name":
//                             if (preg_match_all('/(\{(?:[^{}]|(?1))*\})/s', $final, $all_matches)) {
//                                 // Take the last JSON object
//                                 $json_candidates = $all_matches[0];
//                                 foreach (array_reverse($json_candidates) as $candidate) {
//                                     $test = json_decode($candidate, true);
//                                     if (json_last_error() === JSON_ERROR_NONE && isset($test['name'])) {
//                                         $final = $candidate;
//                                         ds('extracted JSON from mixed content');
//                                         break;
//                                     }
//                                 }
//                             }
//                         }
                        
//                         $final = trim($final);
                        
//                         ds('cleaned final');
//                         ds($final);
                    
//                         // Check for tool call
//                         if ($final !== '' && $final[0] === '{') {
//                             $parsed = json_decode($final, true);
                    
//                             if (json_last_error() === JSON_ERROR_NONE && isset($parsed['name'])) {
//                                 $toolCallDetected = true;
//                                 $toolCallData = $parsed;
//                                 ds('tool call detected successfully');
//                             } 
//                         }
//                     }
//                 }

//                 return strlen($data);
//             }
//         ]);

//         curl_exec($ch);
//         $curlError = curl_error($ch);
//         curl_close($ch);

//         if ($curlError) {
//             echo "0:" . json_encode("Error: " . $curlError) . "\n";
//             flush();
//             exit;
//         }



//         // If no tool call, we're done
//         if (!$toolCallDetected || !$toolCallData) {

//             break;
//         }


//         if ($iteration >= $maxIterations) {
//             echo "0:" . json_encode(
//                 "Sorry — I couldn't complete this request due to repeated errors. " .
//                 "Please try again or rephrase your request."
//             ) . "\n";
//             flush();
//             exit;
//         }

//         // Process tool call
//         $toolName = $toolCallData['name'];
//         $toolArgs = $toolCallData['arguments'] ?? [];
//         $toolId = uniqid("tool_");

//         if ($toolCallDetected && $toolCallData) {
//             $toolName = $toolCallData['name'];
            
//             echo "2:" . json_encode(['name'=> $toolName]) . "\n";
//             flush();
//             // Otherwise, execute as WordPress ability
//             $ability = wp_get_ability($toolName);
//             // ... rest of your execution logic
//         }
        

//         // Execute the ability
//         $toolResult = null;
        
//         try {
//             if (function_exists('wp_get_ability')) {

//                 if($toolName == 'WaitUntilToolExecuted') {
//                     echo "2:" . json_encode(
//                         $toolName
//                     ) . "\n";
//                     flush();
//                 }
                
//                 $ability = wp_get_ability($toolName);

//                 ds('ability');
//                 ds($ability);
    
//                 if ($ability) {
//                     if (is_array($toolArgs) && empty($toolArgs)) {
//                         $toolArgs = null;
//                     }
    
//                     $toolResult = $ability->execute($toolArgs);
                    
//                     if (is_wp_error($toolResult)) {
//                         $toolResult = [
//                             'error' => $toolResult->get_error_message(),
//                             'error_code' => $toolResult->get_error_code(),
//                             'toolName' => $toolName
//                         ];
//                     }
//                 } else {
//                     $toolResult = [
//                         'error' => "Ability '$toolName' not found.",
//                         'toolName' => $toolName
//                     ];
//                 }
//             }
//         } catch (\Throwable $e) {
//             $toolResult = [
//                 'error' => $e->getMessage(),
//                 'toolName' => $toolName
//             ];
//         }

//         ds('tool result');
//         ds($toolResult);

//         // Ensure tool result is always a valid array
//         if (is_wp_error($toolResult)) {
//             $toolResult = [
//                 'error' => $toolResult->get_error_message(),
//                 'error_code' => $toolResult->get_error_code(),
//                 'toolName' => $toolName
//             ];
//         }

//         if (is_array($toolResult)) {
//             if (isset($toolResult['error'])) {
//                 $errorText = $toolResult['error'];
//             } else {
//                 $errorText = json_encode($toolResult, JSON_PRETTY_PRINT);
//             }
//         } else {
//             $errorText = (string) $toolResult;
//         }

//         echo "3:" . json_encode($errorText) . "\n";
//         flush();

//         // Add tool call and result to messages for next iteration
//         $messages[] = [
//             'role' => 'assistant',
//             'content' => json_encode($toolCallData)
//         ];

//         $messages[] = [
//             'role' => 'tool',
//             'content' => json_encode($toolResult)
//         ];

//         if (fluent_is_tool_failure($toolResult)) {

//             $errorMessage = fluent_stringify_tool_error($toolResult);
        
//             $messages[] = [
//                 'role' => 'system',
//                 'content' =>
//                     "The previous tool call FAILED.\n" .
//                     "Reason: {$errorMessage}\n\n" .
//                     "INSTRUCTIONS:\n" .
//                     "- Apologize briefly to the user\n" .
//                     "- Do NOT repeat the same tool call\n" .
//                     "- Try a DIFFERENT ability if possible\n" .
//                     "- If no other ability is suitable, explain the limitation clearly\n" .
//                     "- The FINAL response MUST be plain text\n" .
//                     "- Do NOT output JSON"
//             ];
        
//         } else {
        
//             $messages[] = [
//                 'role' => 'system',
//                 'content' =>
//                     "The previous tool call SUCCEEDED.\n" .
//                     "Use the tool result to answer the user.\n" .
//                     "Respond with a clear, helpful explanation in TEXT ONLY.\n" .
//                     "Do NOT call any more tools.\n" .
//                     "Do NOT output JSON."
//             ];
//         }

//     }

//     exit;
// }


function ollama_proxy_chat(\WP_REST_Request $request, $model) {
    // Ensure current user context is set for REST API
    if (!get_current_user_id()) {
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            wp_set_current_user($current_user->ID);
        }
    }

    $body = $request->get_json_params();
    
    // Get the current state from the request
    $state = $body['state'] ?? [];
    $commands = $body['commands'] ?? [];
    
    // Initialize state if empty
    if (empty($state)) {
        $state = [
            'messages' => [],
            'status' => 'idle'
        ];
    }
    
    // Ensure messages array exists
    if (!isset($state['messages'])) {
        $state['messages'] = [];
    }
    
    // List of frontend-only tools that shouldn't be executed on the backend
    $frontendOnlyTools = ['WaitUntilToolExecuted', 'ConfirmToolExecution'];
    
    // Process commands to update state
    foreach ($commands as $command) {
        switch ($command['type']) {
            case 'add-message':
                // Add the message to our state
                $state['messages'][] = $command['message'];
                break;
            case 'add-tool-result':
                // Handle tool result
                $toolName = $command['toolName'] ?? '';
                $result = $command['result'] ?? '';
                
                $state['messages'][] = [
                    'role' => 'tool',
                    'content' => is_array($result) ? json_encode($result) : $result,
                    'tool_call_id' => $command['toolCallId'] ?? ''
                ];
                break;
        }
    }
    
    // Get tools if function calling is enabled
    $tools = [];
    if (!empty($body['functionCallingEnabled'])) {
        $tools = $body['tools'] ?? [];
    }
    
    // Build system message
    $content = get_agent_identity();
    $content .= "\n" . get_site_information();
    $content .= "\n" . get_environment_debug_info();
    // $content .= "\n" . get_tool_calling_knowledge();
    
    // Prepare messages for the API
    $api_messages = array_merge(
        [
            [
                'role' => 'system',
                'content' => $content
            ],
        ],
        array_map(function ($m) {
            // Handle both formats: parts[] and content[]
            $text = '';
            if (isset($m['parts']) && is_array($m['parts'])) {
                foreach ($m['parts'] as $part) {
                    if ($part['type'] === 'text' && isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            } elseif (isset($m['content']) && is_array($m['content'])) {
                foreach ($m['content'] as $part) {
                    if ($part['type'] === 'text' && isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            } elseif (is_string($m['content'])) {
                $text = $m['content'];
            }
            
            return [
                'role' => $m['role'],
                'content' => $text
            ];
        }, $state['messages'] ?? [])
    );

    // Set headers for streaming
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    if (ob_get_level()) ob_end_clean();

    // Increase PHP execution time for long-running tool calls
    set_time_limit(300); 

    // Update status to processing
    $state['status'] = 'processing';
    echo "aui-state:" . json_encode([[
        "type" => "set",
        "path" => ["status"],
        "value" => "processing"
    ]]) . "\n";
    flush();

    // Add a new assistant message placeholder using the same format as user messages
    $newMessage = [
        'role' => 'assistant',
        'parts' => [['type' => 'text', 'text' => '']]
    ];
    $state['messages'][] = $newMessage;
    $currentMessageIndex = count($state['messages']) - 1;

    // Update the entire messages array with the new message
    echo "aui-state:" . json_encode([[
        "type" => "set",
        "path" => ["messages"],
        "value" => $state['messages']
    ]]) . "\n";
    flush();

    // Main loop to handle tool calls
    $maxIterations = 5;
    $iteration = 0;
    $currentMessage = '';

    while ($iteration < $maxIterations) {
        $iteration++;
        
        $payload = [
            'model' => $model,
            'messages' => $api_messages,
            'stream' => true,
        ];

        if(!empty($tools)) {
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
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, &$streamingAllowed, &$toolCallDetected, &$toolCallData, &$currentMessage, &$state, $currentMessageIndex) {
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
                        $currentMessage .= $content;
                        
                        // Update the current message in state using parts format
                        $state['messages'][$currentMessageIndex]['parts'][0]['text'] .= $content;
                        
                        // Stream the content update using parts format
                        echo "aui-state:" . json_encode([[
                            "type" => "append-text",
                            "path" => ["messages", $currentMessageIndex, "parts", 0, "text"],
                            "value" => $content
                        ]]) . "\n";
                        flush();
                    }

                    if ($done) {
                        $final = trim($buffer);
                        
                        // EXTRACT JSON FROM MARKDOWN CODE BLOCKS OR MIXED CONTENT
                        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $final, $matches)) {
                            $final = $matches[1];
                        }
                        // Pattern 2: Text followed by JSON
                        elseif (preg_match('/\{[^{]*"name"\s*:\s*"[^"]+"/s', $final, $matches)) {
                            if (preg_match_all('/(\{(?:[^{}]|(?1))*\})/s', $final, $all_matches)) {
                                $json_candidates = $all_matches[0];
                                foreach (array_reverse($json_candidates) as $candidate) {
                                    $test = json_decode($candidate, true);
                                    if (json_last_error() === JSON_ERROR_NONE && isset($test['name'])) {
                                        $final = $candidate;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $final = trim($final);
                        
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
            echo "3:" . json_encode("Error: " . $curlError) . "\n";
            flush();
            exit;
        }

        // If no tool call, we're done
        if (!$toolCallDetected || !$toolCallData) {
            $state['status'] = 'idle';
            echo "aui-state:" . json_encode([[
                "type" => "set",
                "path" => ["status"],
                "value" => "idle"
            ]]) . "\n";
            flush();
            break;
        }

        if ($iteration >= $maxIterations) {
            // Append error message to current message
            $state['messages'][$currentMessageIndex]['parts'][0]['text'] .= 
                " Sorry — I couldn't complete this request due to repeated errors. Please try again or rephrase your request.";
            
            echo "aui-state:" . json_encode([[
                "type" => "set",
                "path" => ["messages"],
                "value" => $state['messages']
            ]]) . "\n";
            flush();
            exit;
        }

        // Process tool call
        $toolName = $toolCallData['name'];
        $toolArgs = $toolCallData['arguments'] ?? [];
        $toolId = uniqid("tool_");

        // Update state to show tool execution
        echo "aui-state:" . json_encode([[
            "type" => "set",
            "path" => ["currentTool"],
            "value" => $toolName
        ]]) . "\n";
        flush();

        // Check if this is a frontend-only tool
        if (in_array($toolName, $frontendOnlyTools)) {
            // For frontend-only tools, just acknowledge and continue
            $toolResult = [
                'status' => 'success',
                'message' => "UI component '$toolName' displayed"
            ];
            
            // Add a special message to trigger the UI component
            echo "aui-state:" . json_encode([[
                "type" => "set",
                "path" => ["uiComponent"],
                "value" => [
                    'type' => $toolName,
                    'status' => 'running',
                    'args' => $toolArgs
                ]
            ]]) . "\n";
            flush();
            
            // Simulate some processing time
            sleep(1);
            
            // Update the UI component status to completed
            echo "aui-state:" . json_encode([[
                "type" => "set",
                "path" => ["uiComponent"],
                "value" => [
                    'type' => $toolName,
                    'status' => 'completed',
                    'args' => $toolArgs,
                    'result' => ['message' => 'Tool execution completed']
                ]
            ]]) . "\n";
            flush();
        } else {
            // Execute the ability for backend tools
            $toolResult = null;
            
            try {
                if (function_exists('wp_get_ability')) {
                    $ability = wp_get_ability($toolName);
        
                    if ($ability) {
                        if (is_array($toolArgs) && empty($toolArgs)) {
                            $toolArgs = null;
                        }
        
                        $toolResult = $ability->execute($toolArgs);
                        
                        if (is_wp_error($toolResult)) {
                            $toolResult = [
                                'error' => $toolResult->get_error_message(),
                                'error_code' => $toolResult->get_error_code(),
                                'toolName' => $toolName
                            ];
                        }
                    } else {
                        $toolResult = [
                            'error' => "Ability '$toolName' not found.",
                            'toolName' => $toolName
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $toolResult = [
                    'error' => $e->getMessage(),
                    'toolName' => $toolName
                ];
            }
        }

        // Ensure tool result is always a valid array
        if (is_wp_error($toolResult)) {
            $toolResult = [
                'error' => $toolResult->get_error_message(),
                'error_code' => $toolResult->get_error_code(),
                'toolName' => $toolName
            ];
        }

        // Add tool call and result to messages for next iteration
        $api_messages[] = [
            'role' => 'assistant',
            'content' => json_encode($toolCallData)
        ];

        $api_messages[] = [
            'role' => 'tool',
            'content' => json_encode($toolResult)
        ];

        // Add tool result to state
        $toolMessage = [
            'role' => 'tool',
            'content' => is_array($toolResult) ? json_encode($toolResult) : (string) $toolResult,
            'tool_call_id' => $toolId
        ];
        $state['messages'][] = $toolMessage;
        
        // Update state with tool result
        echo "aui-state:" . json_encode([[
            "type" => "set",
            "path" => ["messages"],
            "value" => $state['messages']
        ]]) . "\n";
        flush();

        // Clear current tool
        echo "aui-state:" . json_encode([[
            "type" => "set",
            "path" => ["currentTool"],
            "value" => null
        ]]) . "\n";
        flush();

        // Add a new assistant message for the next response using parts format
        $newMessage = [
            'role' => 'assistant',
            'parts' => [['type' => 'text', 'text' => '']]
        ];
        $state['messages'][] = $newMessage;
        $currentMessageIndex = count($state['messages']) - 1;

        // Update the entire messages array with the new message
        echo "aui-state:" . json_encode([[
            "type" => "set",
            "path" => ["messages"],
            "value" => $state['messages']
        ]]) . "\n";
        flush();

        if (fluent_is_tool_failure($toolResult)) {
            $errorMessage = fluent_stringify_tool_error($toolResult);
        
            $api_messages[] = [
                'role' => 'system',
                'content' =>
                    "The previous tool call FAILED.\n" .
                    "Reason: {$errorMessage}\n\n" .
                    "INSTRUCTIONS:\n" .
                    "- Apologize briefly to the user\n" .
                    "- Do NOT repeat the same tool call\n" .
                    "- Try a DIFFERENT ability if possible\n" .
                    "- If no other ability is suitable, explain the limitation clearly\n" .
                    "- The FINAL response MUST be plain text\n" .
                    "- Do NOT output JSON"
            ];
        
        } else {
        
            $api_messages[] = [
                'role' => 'system',
                'content' =>
                    "The previous tool call SUCCEEDED.\n" .
                    "Use the tool result to answer the user.\n" .
                    "Respond with a clear, helpful explanation in TEXT ONLY.\n" .
                    "Do NOT call any more tools.\n" .
                    "- Do NOT output JSON"
            ];
        }

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


/**
 * --------------------------------------------------
 * Load Plugin Files
 * --------------------------------------------------
 */

// Admin-only functionality
if ( is_admin() ) {
    require_once FLUENT_MCP_AGENT_PATH . 'includes/admin-menu.php';
    require_once FLUENT_MCP_AGENT_PATH . 'includes/settings.php';
    require_once FLUENT_MCP_AGENT_PATH . 'includes/usage_guide.php';
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
