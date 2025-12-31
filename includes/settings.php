<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * --------------------------------------------------
 * Register Settings
 * --------------------------------------------------
 */
add_action( 'admin_init', 'fluent_mcp_agent_register_settings' );

function fluent_mcp_agent_register_settings() {

    /**
     * Default provider
     */
    register_setting(
        'fluent_mcp_agent_settings',
        'fluent_mcp_agent_default_provider',
        [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]
    );

    /**
     * Provider enable flags
     */
    register_setting( 'fluent_mcp_agent_settings', 'fluent_mcp_agent_enable_ollama', [ 'default' => 0 ] );
    register_setting( 'fluent_mcp_agent_settings', 'fluent_mcp_agent_enable_openai', [ 'default' => 0 ] );
    register_setting( 'fluent_mcp_agent_settings', 'fluent_mcp_agent_enable_anthropic', [ 'default' => 0 ] );

    /**
     * Ollama
     */
    register_setting(
        'fluent_mcp_agent_settings',
        'fluent_mcp_agent_ollama_url',
        [
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'http://localhost:11434/api/chat',
        ]
    );

    /**
     * OpenAI
     */
    register_setting(
        'fluent_mcp_agent_settings',
        'fluent_mcp_agent_openai_api_key',
        [
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    /**
     * Anthropic
     */
    register_setting(
        'fluent_mcp_agent_settings',
        'fluent_mcp_agent_anthropic_api_key',
        [
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    /**
     * Section
     */
    add_settings_section(
        'fluent_mcp_agent_section_providers',
        __( 'AI Providers', 'fluent-mcp-agent' ),
        'fluent_mcp_agent_providers_section_cb',
        'fluent-mcp-agent-settings'
    );

    /**
     * Fields
     */
    add_settings_field(
        'fluent_mcp_agent_default_provider',
        __( 'Default Provider', 'fluent-mcp-agent' ),
        'fluent_mcp_agent_default_provider_field_cb',
        'fluent-mcp-agent-settings',
        'fluent_mcp_agent_section_providers'
    );

    add_settings_field(
        'fluent_mcp_agent_ollama',
        __( 'Ollama (Local)', 'fluent-mcp-agent' ),
        'fluent_mcp_agent_ollama_field_cb',
        'fluent-mcp-agent-settings',
        'fluent_mcp_agent_section_providers'
    );

    add_settings_field(
        'fluent_mcp_agent_openai',
        __( 'ChatGPT (OpenAI)', 'fluent-mcp-agent' ),
        'fluent_mcp_agent_openai_field_cb',
        'fluent-mcp-agent-settings',
        'fluent_mcp_agent_section_providers'
    );

    add_settings_field(
        'fluent_mcp_agent_anthropic',
        __( 'Claude (Anthropic)', 'fluent-mcp-agent' ),
        'fluent_mcp_agent_anthropic_field_cb',
        'fluent-mcp-agent-settings',
        'fluent_mcp_agent_section_providers'
    );
}

/**
 * --------------------------------------------------
 * Section Callback
 * --------------------------------------------------
 */
function fluent_mcp_agent_providers_section_cb() {
    ?>
    <div style="background: #f8f9fa; padding: 16px 20px; border-radius: 8px; border-left: 4px solid #2271b1; margin-bottom: 24px;">
        <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #3c434a;">
            <strong style="color: #1d2327;">💡 Getting Started:</strong> Enable and configure one or more AI providers below. Select a default provider for seamless integration.
        </p>
    </div>
    <?php
}

/**
 * --------------------------------------------------
 * Default Provider Selector
 * --------------------------------------------------
 */
function fluent_mcp_agent_default_provider_field_cb() {

    $default = get_option( 'fluent_mcp_agent_default_provider', '' );

    $providers = [
        'ollama'    => 'Ollama',
        'openai'    => 'OpenAI',
        'anthropic' => 'Anthropic',
    ];
    ?>
    <div style="background: #fff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px;">
            Select Default Provider
        </label>
        <select name="fluent_mcp_agent_default_provider" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #8c8f94; border-radius: 6px; font-size: 14px; background: #fff; cursor: pointer;">
            <option value="">
                — Choose a provider —
            </option>

            <?php foreach ( $providers as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p style="margin: 12px 0 0; font-size: 13px; color: #646970; line-height: 1.5;">
            The default provider will be used for new conversations and API requests.
        </p>
    </div>
    <?php
}

/**
 * --------------------------------------------------
 * Ollama
 * --------------------------------------------------
 */
function fluent_mcp_agent_ollama_field_cb() {
    $enabled = get_option( 'fluent_mcp_agent_enable_ollama', 0 );
    ?>
    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 4px; font-size: 16px; color: #1d2327; font-weight: 600;">Ollama (Local)</h3>
                <p style="margin: 0; font-size: 13px; color: #646970;">Run AI models locally on your machine</p>
            </div>
            <label style="position: relative; display: inline-block; width: 50px; height: 26px; cursor: pointer;">
                <input type="checkbox"
                       name="fluent_mcp_agent_enable_ollama"
                       value="1"
                       <?php checked( $enabled, 1 ); ?>
                       style="opacity: 0; width: 0; height: 0;">
                <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $enabled ? '#2271b1' : '#8c8f94'; ?>; transition: .3s; border-radius: 26px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);"></span>
                <span style="position: absolute; content: ''; height: 20px; width: 20px; left: <?php echo $enabled ? '27px' : '3px'; ?>; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
            </label>
        </div>

        <div style="<?php echo !$enabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px;">
                API Endpoint URL
            </label>
            <input type="text"
                   name="fluent_mcp_agent_ollama_url"
                   value="<?php echo esc_attr( get_option( 'fluent_mcp_agent_ollama_url' ) ); ?>"
                   placeholder="http://localhost:11434/api/chat"
                   style="width: 100%; padding: 10px 12px; border: 1px solid #8c8f94; border-radius: 6px; font-size: 14px; font-family: 'Courier New', monospace; background: #fafafa;">
            <p style="margin: 8px 0 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Default: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px;">http://localhost:11434/api/chat</code>
            </p>
        </div>
    </div>
    <?php
}

/**
 * --------------------------------------------------
 * OpenAI
 * --------------------------------------------------
 */
function fluent_mcp_agent_openai_field_cb() {
    $enabled = get_option( 'fluent_mcp_agent_enable_openai', 0 );
    ?>
    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 4px; font-size: 16px; color: #1d2327; font-weight: 600;">ChatGPT (OpenAI)</h3>
                <p style="margin: 0; font-size: 13px; color: #646970;">Access GPT-4, GPT-3.5 and other OpenAI models</p>
            </div>
            <label style="position: relative; display: inline-block; width: 50px; height: 26px; cursor: pointer;">
                <input type="checkbox"
                       name="fluent_mcp_agent_enable_openai"
                       value="1"
                       <?php checked( $enabled, 1 ); ?>
                       style="opacity: 0; width: 0; height: 0;">
                <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $enabled ? '#2271b1' : '#8c8f94'; ?>; transition: .3s; border-radius: 26px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);"></span>
                <span style="position: absolute; content: ''; height: 20px; width: 20px; left: <?php echo $enabled ? '27px' : '3px'; ?>; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
            </label>
        </div>

        <div style="<?php echo !$enabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px;">
                API Key
            </label>
            <input type="password"
                   name="fluent_mcp_agent_openai_api_key"
                   value="<?php echo esc_attr( get_option( 'fluent_mcp_agent_openai_api_key' ) ); ?>"
                   placeholder="sk-..."
                   style="width: 100%; padding: 10px 12px; border: 1px solid #8c8f94; border-radius: 6px; font-size: 14px; font-family: 'Courier New', monospace; background: #fafafa;">
            <p style="margin: 8px 0 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" style="color: #2271b1; text-decoration: none; font-weight: 500;">OpenAI Dashboard →</a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * --------------------------------------------------
 * Anthropic
 * --------------------------------------------------
 */
function fluent_mcp_agent_anthropic_field_cb() {
    $enabled = get_option( 'fluent_mcp_agent_enable_anthropic', 0 );
    ?>
    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 4px; font-size: 16px; color: #1d2327; font-weight: 600;">Claude (Anthropic)</h3>
                <p style="margin: 0; font-size: 13px; color: #646970;">Access Claude 3 Opus, Sonnet, and Haiku models</p>
            </div>
            <label style="position: relative; display: inline-block; width: 50px; height: 26px; cursor: pointer;">
                <input type="checkbox"
                       name="fluent_mcp_agent_enable_anthropic"
                       value="1"
                       <?php checked( $enabled, 1 ); ?>
                       style="opacity: 0; width: 0; height: 0;">
                <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $enabled ? '#2271b1' : '#8c8f94'; ?>; transition: .3s; border-radius: 26px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);"></span>
                <span style="position: absolute; content: ''; height: 20px; width: 20px; left: <?php echo $enabled ? '27px' : '3px'; ?>; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
            </label>
        </div>

        <div style="<?php echo !$enabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px;">
                API Key
            </label>
            <input type="password"
                   name="fluent_mcp_agent_anthropic_api_key"
                   value="<?php echo esc_attr( get_option( 'fluent_mcp_agent_anthropic_api_key' ) ); ?>"
                   placeholder="sk-ant-..."
                   style="width: 100%; padding: 10px 12px; border: 1px solid #8c8f94; border-radius: 6px; font-size: 14px; font-family: 'Courier New', monospace; background: #fafafa;">
            <p style="margin: 8px 0 0; font-size: 13px; color: #646970; line-height: 1.5;">
                Get your API key from <a href="https://console.anthropic.com/settings/keys" target="_blank" style="color: #2271b1; text-decoration: none; font-weight: 500;">Anthropic Console →</a>
            </p>
        </div>
    </div>
    <?php
}