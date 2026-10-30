<?php
// Run through WP CLI to test the sync path without contacting Mailchimp.
defined('ABSPATH') || exit;
if (defined('AERA_MAILCHIMP_API_KEY') || defined('AERA_MAILCHIMP_AUDIENCE_ID')) {
    WP_CLI::error('Run this check without live Mailchimp credentials.');
}
define('AERA_MAILCHIMP_API_KEY', 'test-us1');
define('AERA_MAILCHIMP_AUDIENCE_ID', 'test-audience');
global $wpdb;
$table = aera_table();
$email = 'mailchimp-check-' . bin2hex(random_bytes(8)) . '@example.test';
$wpdb->insert($table, ['email' => $email, 'code' => bin2hex(random_bytes(10)), 'created_at' => current_time('mysql', true)], ['%s', '%s', '%s']);
$id = $wpdb->insert_id;
if (!$id) {
    WP_CLI::error('Could not insert test signup.');
}
try {
    $response_code = 500;
    add_filter('pre_http_request', function ($result, $args, $url) use ($email, &$response_code) {
        if ($args['method'] !== 'PUT' || strpos($url, 'https://us1.api.mailchimp.com/3.0/lists/test-audience/members/') !== 0 || json_decode($args['body'], true)['email_address'] !== $email) {
            throw new RuntimeException('Mailchimp request was malformed.');
        }
        return ['response' => ['code' => $response_code, 'message' => 'Mock'], 'headers' => [], 'body' => '{}'];
    }, 10, 3);
    aera_mailchimp_sync($id);
    if ((int) $wpdb->get_var($wpdb->prepare("SELECT synced FROM $table WHERE id = %d", $id)) !== 0) {
        WP_CLI::error('Failed Mailchimp response marked signup synced.');
    }
    $response_code = 200;
    aera_mailchimp_sync($id);
    if ((int) $wpdb->get_var($wpdb->prepare("SELECT synced FROM $table WHERE id = %d", $id)) !== 1) {
        WP_CLI::error('Successful Mailchimp response did not mark signup synced.');
    }
    WP_CLI::success('Mailchimp failure and retry paths passed with local HTTP mock.');
} finally {
    $wpdb->delete($table, ['id' => $id], ['%d']);
}
