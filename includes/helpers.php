<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * --------------------------------------------------
 * Get enabled AI providers
 * --------------------------------------------------
 *
 * @return array List of enabled provider slugs
 */
function fluent_mcp_agent_get_enabled_providers() {

    $providers = [];

    if ( get_option( 'fluent_mcp_agent_enable_ollama', 1 ) ) {
        $providers[] = 'ollama';
    }

    if ( get_option( 'fluent_mcp_agent_enable_openai', 0 ) ) {
        $providers[] = 'openai';
    }

    if ( get_option( 'fluent_mcp_agent_enable_anthropic', 0 ) ) {
        $providers[] = 'anthropic';
    }

    return $providers;
}

/**
 * --------------------------------------------------
 * Get default / active AI provider
 * --------------------------------------------------
 *
 * Guarantees a valid enabled provider or returns null
 *
 * @return string|null Provider slug
 */
function fluent_mcp_agent_get_default_provider() {

    $default = get_option(
        'fluent_mcp_agent_default_provider',
        ''
    );

    $enabled = fluent_mcp_agent_get_enabled_providers();

    // If default provider is enabled, use it
    if ( in_array( $default, $enabled, true ) ) {
        return $default;
    }

    // Fallback to first enabled provider
    return $enabled[0] ?? null;
}
