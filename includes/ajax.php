<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_fluent_mcp_agent_chat', 'fluent_host_handle_chat' );


function fluent_host_handle_chat() {

    // Capability check (admin only for now)
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'error' => 'Unauthorized' ], 403 );
    }

    // Basic validation
    $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : '';
    if ( empty( $prompt ) ) {
        wp_send_json_error( [ 'error' => 'Empty prompt' ] );
    }

    $url   = get_option( 'fluent_host_ollama_url' );
    $model = get_option( 'fluent_host_ollama_model', 'qwen2.5-coder:latest' );

    if ( empty( $url ) ) {
        wp_send_json_error( [ 'error' => 'Ollama URL not set' ] );
    }

    /**
     * Ollama Chat API payload
     * Endpoint: /api/chat
     */
    $body = wp_json_encode( [
        'model'    => $model,
        'messages' => [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ],
        'stream' => true,
    ] );

    $response = wp_remote_post( 'http://localhost:11434/api/chat', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body'    => $body,
        'timeout' => 60,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [
            'error' => $response->get_error_message(),
        ] );
    }


    $raw_body = wp_remote_retrieve_body( $response );

    // Ollama returns new-line delimited JSON (one object per line: JSONL)
    $lines = explode("\n", trim($raw_body));
    $message_content = '';

    foreach ($lines as $line) {
        if ( ! $line ) continue;
        $item = json_decode($line, true);
        if ( isset($item['message']['content']) ) {
            $message_content .= $item['message']['content'];
        }
    }

    $data = [
        'message' => [
            'content' => $message_content
        ]
    ];


    if ( ! isset( $data['message']['content'] ) ) {
        wp_send_json_error( [
            'error' => 'Invalid Ollama response',
            'raw'   => $data,
        ] );
    }

    wp_send_json_success( [
        'response' => $data['message']['content'],
    ] );
}
