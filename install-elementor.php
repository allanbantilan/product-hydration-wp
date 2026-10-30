<?php
// Run with: wp eval-file wp-content/themes/hydration-launch/install-elementor.php
defined('ABSPATH') || exit;
if (!is_plugin_active('elementor/elementor.php')) {
    WP_CLI::error('Activate Elementor before importing the page.');
}
$existing = get_page_by_path('product-launch', OBJECT, 'page');
if ($existing && get_post_meta($existing->ID, '_elementor_edit_mode', true) !== 'builder') {
    WP_CLI::error('A non-Elementor page already uses the product-launch slug; rename it before importing.');
}
if ($existing) {
    WP_CLI::log('Elementor page already exists; leaving edits intact.');
    $id = $existing->ID;
} else {
    $json = file_get_contents(get_theme_file_path('/elementor-page.json'));
    if (!is_array(json_decode($json, true)) || json_last_error() !== JSON_ERROR_NONE) {
        WP_CLI::error('Invalid Elementor page data.');
    }
    $id = wp_insert_post([
        'post_title' => 'Hydration Launch Page',
        'post_name' => 'product-launch',
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => 'Product launch page built with Elementor.',
    ], true);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }
    update_post_meta($id, '_elementor_edit_mode', 'builder');
    update_post_meta($id, '_elementor_template_type', 'wp-page');
    update_post_meta($id, '_elementor_version', ELEMENTOR_VERSION);
    update_post_meta($id, '_elementor_data', wp_slash($json));
}
update_option('show_on_front', 'page');
update_option('page_on_front', $id);
WP_CLI::success('Elementor homepage ready: ' . admin_url('post.php?post=' . $id . '&action=elementor'));
