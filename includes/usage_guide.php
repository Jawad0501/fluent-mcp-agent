<?php
/**
 * Renders the Fluent MCP Agent admin usage guide page.
 */

defined('ABSPATH') || exit;

function fluent_mcp_agent_usage_guide_page() {
    ?>
    <div class="wrap fluent-mcp-guide">

        <style>
            .fluent-mcp-guide {
                max-width: 900px;
            }

            .fluent-mcp-guide h1 {
                margin-bottom: 0.5rem;
            }

            .fluent-mcp-guide p {
                font-size: 14px;
                line-height: 1.7;
                color: #374151;
            }

            .fluent-mcp-guide h2 {
                margin-top: 2.5rem;
                margin-bottom: 1rem;
                padding-bottom: 0.4rem;
                border-bottom: 1px solid #e5e7eb;
            }

            .fluent-mcp-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 24px;
                margin-top: 16px;
            }

            .fluent-mcp-card ul,
            .fluent-mcp-card ol {
                margin-left: 1.25rem;
            }

            .fluent-mcp-card li {
                margin-bottom: 0.6rem;
            }

            .fluent-mcp-note {
                background: #f0f9ff;
                border-left: 4px solid #0ea5e9;
                color: #075985;
            }

            .fluent-mcp-footer {
                opacity: 0.6;
                margin-top: 40px;
            }
        </style>

        <h1><?php esc_html_e('Fluent MCP Agent – Usage Guide', 'fluent-mcp-agent'); ?></h1>
        <p><?php esc_html_e('Welcome to the Fluent MCP Agent! This page will help you get started using the MCP Agent to add advanced AI capabilities and integrate with your available abilities.', 'fluent-mcp-agent'); ?></p>

        <h2><?php esc_html_e('What is the MCP Agent?', 'fluent-mcp-agent'); ?></h2>
        <div class="fluent-mcp-card">
            <p>
                <?php esc_html_e('The Fluent MCP Agent connects your WordPress site to local LLMs and provides a chat interface for natural language operations, including running custom "abilities" (WordPress PHP functions) securely.', 'fluent-mcp-agent'); ?>
            </p>
        </div>

        <h2><?php esc_html_e('Getting Started', 'fluent-mcp-agent'); ?></h2>
        <div class="fluent-mcp-card">
            <ol>
                <li><strong><?php esc_html_e('Install and Activate:', 'fluent-mcp-agent'); ?></strong> <?php esc_html_e('Ensure the Fluent MCP Agent and Fluent Abilities plugins are active.', 'fluent-mcp-agent'); ?></li>
                <li><strong><?php esc_html_e('Configure LLM Connection:', 'fluent-mcp-agent'); ?></strong> <?php esc_html_e('Connect to your local Ollama (or other supported backend) instance.', 'fluent-mcp-agent'); ?></li>
                <li><strong><?php esc_html_e('Register/Enable Abilities:', 'fluent-mcp-agent'); ?></strong> <?php esc_html_e('Enable abilities from the Fluent Abilities screen.', 'fluent-mcp-agent'); ?></li>
                <li><strong><?php esc_html_e('Open the Agent Chat UI:', 'fluent-mcp-agent'); ?></strong> <?php esc_html_e('Use the admin bar to access the MCP Agent chat interface.', 'fluent-mcp-agent'); ?></li>
            </ol>
        </div>

        <h2><?php esc_html_e('Using the Chat Agent', 'fluent-mcp-agent'); ?></h2>
        <div class="fluent-mcp-card">
            <ul>
                <li><?php esc_html_e('Ask general questions or request explanations.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('Run admin tasks by describing your goal in natural language.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('Approve suggested tools before execution.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('Review tool output directly in chat.', 'fluent-mcp-agent'); ?></li>
            </ul>
        </div>

        <h2><?php esc_html_e('Security', 'fluent-mcp-agent'); ?></h2>
        <div class="fluent-mcp-card fluent-mcp-note">
            <ul>
                <li><?php esc_html_e('Only authenticated users with sufficient permissions can execute abilities.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('All executions use WordPress nonces and capability checks.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('Abilities are validated against their input schema before execution.', 'fluent-mcp-agent'); ?></li>
            </ul>
        </div>

        <h2><?php esc_html_e('Troubleshooting', 'fluent-mcp-agent'); ?></h2>
        <div class="fluent-mcp-card">
            <ul>
                <li><?php esc_html_e('If an ability is not found, verify it is registered and enabled.', 'fluent-mcp-agent'); ?></li>
                <li><?php esc_html_e('Ensure your local Ollama server is running.', 'fluent-mcp-agent'); ?></li>
            </ul>
        </div>

        <hr>

        <p class="fluent-mcp-footer">
            <?php esc_html_e('For support or feedback, visit the project repository or contact the developer.', 'fluent-mcp-agent'); ?>
        </p>
    </div>
    <?php
}

add_action('admin_menu', function () {
    add_submenu_page(
        'fluent-mcp-agent',
        __('Usage Guide', 'fluent-mcp-agent'),
        __('Usage Guide', 'fluent-mcp-agent'),
        'manage_options',
        'fluent-mcp-agent-usage-guide',
        'fluent_mcp_agent_usage_guide_page'
    );
});
