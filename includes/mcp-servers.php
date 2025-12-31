<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the MCP Config admin page
 */
function fluent_host_mcp_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Fluent Host – MCP Configuration', 'fluent-host' ); ?></h1>

        <?php if ( ! fluent_host_is_mcp_adapter_available() ) : ?>
            <p style="color: #c00;">
                <?php esc_html_e( 'MCP Adapter is not installed or active. Please install/activate it to use MCP.', 'fluent-host' ); ?>
            </p>
        <?php else : ?>

            <?php
            // Get running servers
            $servers = fluent_host_get_all_mcp_servers();
            $enabled = get_option( 'fluent_host_mcp_enabled_servers', [] );
            ?>

            <?php if ( empty( $servers ) ) : ?>
                <p><?php esc_html_e( 'No MCP servers are currently registered on this site.', 'fluent-host' ); ?></p>
            <?php else : ?>

                <form method="post">
                    <?php wp_nonce_field( 'fluent_host_mcp_save', 'fluent_host_mcp_nonce' ); ?>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Enabled', 'fluent-host' ); ?></th>
                                <th><?php esc_html_e( 'Server ID', 'fluent-host' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'fluent-host' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'fluent-host' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $servers as $id => $info ) : ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="fluent_host_mcp_servers[]"
                                            value="<?php echo esc_attr( $id ); ?>"
                                            <?php checked( in_array( $id, $enabled, true ) ); ?>
                                        />
                                    </td>
                                    <td><?php echo esc_html( $id ); ?></td>
                                    <td><?php echo esc_html( $info['name'] ); ?></td>
                                    <td><?php echo esc_html( $info['description'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top: 16px;">
                        <button class="button button-primary">
                            <?php esc_html_e( 'Save MCP Configuration', 'fluent-host' ); ?>
                        </button>
                    </p>
                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>
    <?php
}

/**
 * Save enabled MCP servers
 */
add_action( 'admin_init', 'fluent_host_handle_mcp_save' );

function fluent_host_handle_mcp_save() {
    if ( empty( $_POST['fluent_host_mcp_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['fluent_host_mcp_nonce'], 'fluent_host_mcp_save' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $enabled = array_map( 'sanitize_text_field', $_POST['fluent_host_mcp_servers'] ?? [] );
    update_option( 'fluent_host_mcp_enabled_servers', $enabled );
}

/**
 * Helper: Check if MCP Adapter is available
 */
function fluent_host_is_mcp_adapter_available() {
    return class_exists( '\WP\MCP\Core\McpAdapter' );
}

/**
 * Retrieve all registered MCP servers
 *
 * Uses the mcp_adapter_init hook to ensure servers are registered before reading.
 *
 * @return array<string, array{name:string,description:string}>
 */
function fluent_host_get_all_mcp_servers() {

    $servers_list = [];

    if ( ! fluent_host_is_mcp_adapter_available() ) {
        return $servers_list;
    }

    dd('here');
    // Trigger initialization
    $adapter = \WP\MCP\Core\McpAdapter::instance();

    dd($adapter);


    return $servers_list;
}
