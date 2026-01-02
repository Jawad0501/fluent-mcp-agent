<?php

if (!defined('ABSPATH')) exit;

function fluent_mcp_agent_abilty_categories() {
    wp_register_ability_category(
        'site-management',
        [
            'label' => __('Site Management', 'fluent-abilities'),
            'description' => __('Abilities related to managing and retrieving information about the site, plugins, and environment.', 'fluent-abilities')
        ]
    );
}

function fluent_mcp_register_abilities() {

    

    wp_register_ability(
        'fluent-mcp-agent/get-plugins-information',
        [
            'label'               => __('Get Plugins', 'fluent-abilities'),
            'description'         => __('Retrieve a list of all installed plugins, including their activation status, version, and update availability.', 'fluent-abilities'),
            'category'            => 'site-management',
            'input_schema'        => [
                'type'       => 'object',
                'properties' => [
                    'only_active' => [
                        'type'        => 'boolean',
                        'description' => __('OPTIONAL: If true, only return currently active plugins.', 'fluent-abilities'),
                        'default'     => false,
                    ],
                    'only_inactive' => [
                        'type'        => 'boolean',
                        'description' => __('OPTIONAL: If true, only return currently inactive plugins.', 'fluent-abilities'),
                        'default'     => false,
                    ],
                    'has_update' => [
                        'type'        => 'boolean',
                        'description' => __('OPTIONAL: If true, only return plugins with an available update.', 'fluent-abilities'),
                        'default'     => false,
                    ],
                    'search' => [
                        'type'        => 'string',
                        'description' => __('OPTIONAL: Search by plugin name, slug, or description.', 'fluent-abilities'),
                    ],
                ],
            ],
            'output_schema'       => [
                'type'        => 'object',
                'description' => __('A list of plugins with detailed metadata.', 'fluent-abilities'),
                'properties'  => [
                    'plugins' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'description' => __('The name of the plugin', 'fluent-abilities'),
                                ],
                                'plugin_file' => [
                                    'type' => 'string',
                                    'description' => __('The plugin file (slug)', 'fluent-abilities'),
                                ],
                                'version' => [
                                    'type' => 'string',
                                    'description' => __('Current installed version', 'fluent-abilities'),
                                ],
                                'active' => [
                                    'type' => 'boolean',
                                    'description' => __('Whether the plugin is currently active', 'fluent-abilities'),
                                ],
                                'has_update' => [
                                    'type' => 'boolean',
                                    'description' => __('Whether an update is available for the plugin', 'fluent-abilities'),
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => __('The plugin description', 'fluent-abilities'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'permission_callback' => 'fluent_mcp_agent_can_activate_plugin',
            'execute_callback'    => 'fluent_mcp_agent_get_plugins_information'
        ]
    );

    wp_register_ability(
        'fluent-mcp-agent/get-theme-information',
        [
            'label'               => __('Get Theme Information', 'fluent-abilities'),
            'description'         => __('Retrieve a list of all installed themes on the site with detailed metadata, including current theme and update info.', 'fluent-abilities'),
            'category'            => 'site-management',
            'input_schema'        => [
                'type'        => 'object',
                'properties'  => [
                    'search' => [
                        'type'        => 'string',
                        'description' => __('OPTIONAL: Search by theme name, slug, or description.', 'fluent-abilities'),
                    ],
                ],
            ],
            'output_schema'       => [
                'type'        => 'object',
                'description' => __('A list of themes with metadata and update status.', 'fluent-abilities'),
                'properties'  => [
                    'themes' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'description' => __('The name of the theme', 'fluent-abilities'),
                                ],
                                'slug' => [
                                    'type' => 'string',
                                    'description' => __('Theme slug/directory', 'fluent-abilities'),
                                ],
                                'version' => [
                                    'type' => 'string',
                                    'description' => __('Current installed version', 'fluent-abilities'),
                                ],
                                'is_active' => [
                                    'type' => 'boolean',
                                    'description' => __('Whether the theme is currently active', 'fluent-abilities'),
                                ],
                                'has_update' => [
                                    'type' => 'boolean',
                                    'description' => __('Whether an update is available for the theme', 'fluent-abilities'),
                                ],
                                'new_version' => [
                                    'type' => 'string',
                                    'description' => __('The available new version if has_update is true', 'fluent-abilities'),
                                ],
                                'author' => [
                                    'type' => 'string',
                                    'description' => __('The theme author', 'fluent-abilities'),
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => __('Theme description', 'fluent-abilities'),
                                ],
                                'theme_uri' => [
                                    'type' => 'string',
                                    'description' => __('Theme URI', 'fluent-abilities'),
                                ],
                            ]
                        ],
                    ],
                    'active_theme' => [
                        'type' => 'string',
                        'description' => __('The stylesheet slug of the currently active theme.', 'fluent-abilities'),
                    ],
                    'total' => [
                        'type' => 'integer',
                        'description' => __('Total number of themes.', 'fluent-abilities'),
                    ],
                ],
            ],
            'permission_callback' => 'fluent_mcp_agent_can_switch_themes',
            'execute_callback'    => 'fluent_mcp_agent_get_theme_information'
        ]
    );


}
// Initialize custom ability categories for Fluent MCP Agent.
add_action('wp_abilities_api_categories_init', 'fluent_mcp_agent_abilty_categories');

add_action('wp_abilities_api_init', 'fluent_mcp_register_abilities');

function fluent_mcp_agent_get_plugins_information() {
    // Get all plugins
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (!function_exists('get_site_transient')) {
        require_once ABSPATH . 'wp-includes/option.php';
    }

    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', []);
    $updates = get_site_transient('update_plugins');

    $plugins_info = [];
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        $has_update = isset($updates->response[$plugin_file]);
        $plugins_info[] = [
            'file'         => $plugin_file,
            'name'         => $plugin_data['Name'] ?? '',
            'version'      => $plugin_data['Version'] ?? '',
            'active'       => in_array($plugin_file, $active_plugins, true),
            'has_update'   => $has_update,
            'new_version'  => $has_update ? $updates->response[$plugin_file]->new_version : '',
            'description'  => $plugin_data['Description'] ?? '',
            'author'       => $plugin_data['Author'] ?? '',
            'plugin_uri'   => $plugin_data['PluginURI'] ?? '',
        ];
    }

    return [
        'plugins' => $plugins_info,
        'total'   => count($plugins_info),
    ];
}

function fluent_mcp_agent_can_activate_plugin() {
    return current_user_can('activate_plugins');
}


function fluent_mcp_agent_get_theme_information() {
    if (!function_exists('wp_get_themes')) {
        require_once ABSPATH . 'wp-includes/theme.php';
    }

    $themes = wp_get_themes();
    $current_theme = wp_get_theme();
    $update_data = get_site_transient('update_themes');

    $themes_info = [];
    foreach ($themes as $slug => $theme) {
        $info = [
            'slug'        => $slug,
            'name'        => $theme->get('Name'),
            'version'     => $theme->get('Version'),
            'active'      => ($theme->get_stylesheet() === $current_theme->get_stylesheet()),
            'parent'      => null,
            'is_child'    => false,
            'description' => $theme->get('Description'),
            'author'      => $theme->get('Author'),
            'theme_uri'   => $theme->get('ThemeURI'),
            'has_update'  => false,
            'new_version' => '',
        ];

        $parent = $theme->get('Template');
        if ($parent && $parent !== $theme->get_stylesheet()) {
            $parent_theme = wp_get_theme($parent);
            if ($parent_theme->exists()) {
                $info['parent'] = $parent_theme->get('Name');
                $info['is_child'] = true;
            }
        }

        if (
            $update_data &&
            isset($update_data->response[$slug]) &&
            isset($update_data->response[$slug]['new_version'])
        ) {
            $info['has_update'] = true;
            $info['new_version'] = $update_data->response[$slug]['new_version'];
        }

        $themes_info[] = $info;
    }

    return [
        'themes' => $themes_info,
        'total'  => count($themes_info),
        'active' => $current_theme->get_stylesheet(),
    ];
}

function fluent_mcp_agent_can_switch_themes() {
    // Only allow if user can manage themes.
    return current_user_can('switch_themes');
}

// dd(fluent_mcp_agent_get_theme_information());

