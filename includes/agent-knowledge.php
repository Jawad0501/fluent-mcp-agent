<?php

if (!defined('ABSPATH')) exit;

// ============================================================================
// MAIN ORCHESTRATOR FUNCTIONS
// ============================================================================

// function generate_agent_knowledge(): string {
//     if (!function_exists('get_bloginfo')) {
//         return '';
//     }

//     require_once ABSPATH . 'wp-admin/includes/plugin.php';
//     require_once ABSPATH . 'wp-admin/includes/update.php';
//     require_once ABSPATH . 'wp-admin/includes/theme.php';

//     $sections = [
//         get_knowledge_header(),
//         get_agent_identity(),
//         get_site_information(),
//         get_environment_debug_info(),
//         get_plugins_knowledge(),
//         get_theme_knowledge(),
//         get_mcp_status(),
//         get_answering_rules(),
//     ];

//     return implode("\n", array_filter($sections));
// }

// function generate_tools_knowledge(): string {
//     $sections = [
//         get_tools_header(),
//         get_meta_abilities_knowledge(),
//         get_registered_abilities_knowledge(),
//         get_tool_usage_rules(),
//     ];

//     return implode("\n", array_filter($sections));
// }

// ============================================================================
// AGENT KNOWLEDGE COMPONENTS
// ============================================================================



function get_agent_identity(): string {
    $lines = [];
    $lines[] = "=== AGENT IDENTITY ===";
    $lines[] = "You are Fluent MCP Agent, an AI assistant running inside this specific WordPress site.";
    $lines[] = "You have direct access to this site's data, plugins, themes, and configuration.";
    $lines[] = "Answer questions using precise and up-to-date knowledge about THIS site.";
    $lines[] = "If site-specific information is available in the snapshot, always answer directly based on it.";
    $lines[] = "";
    $lines[] = "BEHAVIOR RULES:";
    $lines[] = "- Do NOT provide generic or boilerplate WordPress advice";
    $lines[] = "- Do NOT recommend manual steps, dashboards, or external tools unless explicitly instructed";
    $lines[] = "- Never hallucinate features, capabilities, or plugins that are not present in this site";
    $lines[] = "- If you don't have information, say so clearly rather than guessing";
    $lines[] = "- Your goal is to be as accurate and site-specific as possible";
    $lines[] = "";
    return implode("\n", $lines);
}

function get_knowledge_header(): string {
    $lines = [];
    $lines[] = "=== AUTHORITATIVE SITE SNAPSHOT ===";
    $lines[] = "This is a COMPLETE, CURRENT snapshot of this WordPress site.";
    $lines[] = "This data is GROUND TRUTH.";
    $lines[] = "Do NOT provide generic WordPress guidance.";
    $lines[] = "Do NOT suggest dashboards, plugins, WP-CLI, or manual steps.";
    $lines[] = "";
    return implode("\n", $lines);
}

function get_site_information(): string {
    $lines = [];
    $lines[] = "=== SITE INFORMATION ===";
    $lines[] = "- Name: " . get_bloginfo('name');
    $lines[] = "- URL: " . home_url();
    $lines[] = "- Description: " . get_bloginfo('description');
    $lines[] = "- Admin Email: " . get_bloginfo('admin_email');
    $lines[] = "- WordPress Version: " . get_bloginfo('version');
    $lines[] = "- Locale: " . get_locale();
    $lines[] = "- Multisite: " . (is_multisite() ? 'Yes' : 'No');
    $lines[] = "";
    return implode("\n", $lines);
}

function get_environment_debug_info(): string {
    $lines = [];
    $lines[] = "=== ENVIRONMENT & DEBUG ===";
    $lines[] = "- PHP Version: " . PHP_VERSION;
    $lines[] = "- WP_DEBUG: " . ((defined('WP_DEBUG') && WP_DEBUG) ? 'ENABLED' : 'DISABLED');
    $lines[] = "- WP_DEBUG_LOG: " . ((defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) ? 'ENABLED' : 'DISABLED');
    $lines[] = "- WP_DEBUG_DISPLAY: " . ((defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? 'ENABLED' : 'DISABLED');

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $lines[] = "- Debug Log Path: " . (is_string(WP_DEBUG_LOG) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log');
    }

    $lines[] = "- Environment Type: " . (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'Not defined');
    $lines[] = "";
    return implode("\n", $lines);
}

function get_plugins_knowledge(): string {
    $lines = [];
    $lines[] = "=== INSTALLED PLUGINS (FINAL AUTHORITY) ===";

    $plugins = get_plugins();
    $active  = get_option('active_plugins', []);
    $updates = get_site_transient('update_plugins');

    foreach ($plugins as $file => $plugin) {
        $line = "- {$plugin['Name']} (v{$plugin['Version']})";
        $line .= in_array($file, $active, true) ? " [ACTIVE]" : " [INACTIVE]";

        if (isset($updates->response[$file])) {
            $line .= " [UPDATE → {$updates->response[$file]->new_version}]";
        }

        $lines[] = $line;
    }

    $lines[] = "";
    return implode("\n", $lines);
}



function get_mcp_status(): string {
    $lines = [];
    $lines[] = "=== MCP STATUS ===";
    
    if (class_exists('\WP\MCP\Core\McpAdapter')) {
        $lines[] = "- MCP Adapter: ACTIVE";
        if (defined('\WP\MCP\Core\McpAdapter::VERSION')) {
            $lines[] = "- MCP Version: " . constant('\WP\MCP\Core\McpAdapter::VERSION');
        }
    } else {
        $lines[] = "- MCP Adapter: NOT ACTIVE";
    }

    $lines[] = "";
    return implode("\n", $lines);
}

function get_answering_rules(): string {
    $lines = [];
    $lines[] = "=== ANSWERING RULES ===";
    $lines[] = "- If information exists above, answer directly.";
    $lines[] = "- Never suggest manual steps.";
    $lines[] = "- Never hallucinate plugins, tools, or data.";
    $lines[] = "- Final responses MUST be plain text.";
    $lines[] = "";
    return implode("\n", $lines);
}

function get_tool_calling_knowledge(): string {
    $lines = [];
    $lines[] = "=== TOOL CALLING RULES ===";
    $lines[] = "- You must ONLY call tools and abilities that are explicitly listed in the authorized tools section.";
    $lines[] = "- NEVER invent or assume the existence of a tool or ability not present in the authoritative list.";
    $lines[] = "- ALWAYS read and follow the documented parameters and capabilities for each tool or ability.";
    $lines[] = "- If a tool or ability needs to be invoked, you must respond with a pure JSON object containing ONLY the tool name and any required arguments. Do NOT provide any extra text, commentary, or explanation.";
    $lines[] = '- Example: {"tool":"tool_name","args":{...}}';
    $lines[] = "- If no arguments are required, provide an empty object for args.";
    $lines[] = "- If you don't recognize an ability, use 'mcp-adapter/get-ability-info' to retrieve its schema before calling.";
    $lines[] = "- NEVER use 'mcp-adapter/execute-ability' unless direct invocation is unavailable for a given tool.";
    $lines[] = "- DO NOT chain, combine, or nest tool calls unless explicitly instructed by the user or documentation.";
    $lines[] = "- VERIFY parameter requirements and types before constructing a tool call payload.";
    $lines[] = "- ONLY call tools responsively (after the user's instruction), not speculatively.";
    $lines[] = "- Return only the tool call result in the final output; do not include meta-discussion or guesses.";
    $lines[] = "";

    return implode("\n", $lines);
}


// ============================================================================
// TOOLS KNOWLEDGE COMPONENTS
// ============================================================================

function get_tools_header(): string {
    $lines = [];
    $lines[] = "=== MCP TOOLS & ABILITIES (AUTHORITATIVE) ===";
    $lines[] = "This section defines the ONLY tools you are allowed to use.";
    $lines[] = "If a tool is not listed here, it DOES NOT EXIST.";
    $lines[] = "You must NEVER guess, invent, or assume tools.";
    $lines[] = "";
    return implode("\n", $lines);
}

function get_meta_abilities_knowledge(): string {
    $lines = [];
    $lines[] = "=== META ABILITIES (SYSTEM LEVEL) ===";
    $lines[] = "";

    $lines[] = "mcp-adapter/discover-abilities";
    $lines[] = "- Purpose: List all registered abilities";
    $lines[] = "- Parameters: none";
    $lines[] = "- Use ONLY when the user explicitly asks what tools exist";
    $lines[] = "";

    $lines[] = "mcp-adapter/get-ability-info";
    $lines[] = "- Purpose: Inspect schema of a known ability";
    $lines[] = "- Parameters: ability_name (string, required)";
    $lines[] = "- Use BEFORE calling an unfamiliar ability";
    $lines[] = "";

    $lines[] = "mcp-adapter/execute-ability";
    $lines[] = "- Purpose: Execute an ability indirectly";
    $lines[] = "- Use ONLY if direct invocation is unavailable";
    $lines[] = "- Never use by default";
    $lines[] = "";

    return implode("\n", $lines);
}

function get_registered_abilities_knowledge(): string {
    $lines = [];
    $lines[] = "=== REGISTERED ABILITIES (LIVE SNAPSHOT) ===";
    $lines[] = "The following is the COMPLETE list of currently registered abilities.";
    $lines[] = "";

    $abilities = wp_get_abilities();

    if (empty($abilities)) {
        $lines[] = "No abilities are currently registered.";
        return implode("\n", $lines);
    }

    $grouped = group_abilities_by_category($abilities);

    foreach ($grouped as $category => $items) {
        $lines[] = "CATEGORY: " . strtoupper($category);
        $lines[] = "";

        foreach ($items as $ability) {
            $lines = array_merge($lines, format_ability_details($ability));
        }
    }

    return implode("\n", $lines);
}

function get_tool_usage_rules(): string {
    $lines = [];
    $lines[] = "=== TOOL USAGE RULES (MANDATORY) ===";
    $lines[] = "- Never call tools that do not exist";
    $lines[] = "- Never re-label tools under different plugins";
    $lines[] = "- Never retry failed tools automatically";
    $lines[] = "- Never apologize more than once";
    $lines[] = "- If no tool exists, say so clearly and stop";
    $lines[] = "";
    return implode("\n", $lines);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function group_abilities_by_category(array $abilities): array {
    $grouped = [];
    foreach ($abilities as $ability) {
        $grouped[$ability->get_category()][] = $ability;
    }
    return $grouped;
}

function format_ability_details($ability): array {
    $lines = [];
    $lines[] = "Tool Name: " . $ability->get_name();
    $lines[] = "Description: " . $ability->get_description();

    $schema = $ability->get_input_schema();
    if (!empty($schema['properties'])) {
        $lines[] = "Parameters:";
        foreach ($schema['properties'] as $key => $info) {
            $required = in_array($key, $schema['required'] ?? [], true);
            $lines[] = "- {$key}" . ($required ? " (REQUIRED)" : " (optional)");
        }
    } else {
        $lines[] = "Parameters: none";
    }

    $lines[] = "";
    return $lines;
}