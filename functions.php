<?php
defined('ABSPATH') || exit;

function aera_table() {
    global $wpdb;
    return $wpdb->prefix . 'aera_waitlist';
}

add_action('after_switch_theme', function () {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = aera_table();
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL,
        code varchar(24) NOT NULL,
        referred_by bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL,
        synced tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email),
        UNIQUE KEY code (code),
        KEY referred_by (referred_by)
    ) $charset;");
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('aera-style', get_stylesheet_uri(), [], '1.0.7');
    wp_enqueue_script('aera-site', get_theme_file_uri('/assets/site.js'), [], '1.0.7', true);
});

add_theme_support('title-tag');
add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
add_filter('elementor/frontend/print_google_fonts', '__return_false');
add_shortcode('aera_voices', function () {
    ob_start();
    require get_theme_file_path('/template-parts/landing-voices.php');
    return ob_get_clean();
});
add_shortcode('aera_waitlist', function () {
    ob_start();
    require get_theme_file_path('/template-parts/landing-waitlist.php');
    return ob_get_clean();
});
// The landing page uses SVG art, so WordPress's emoji loader has no work here.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
remove_action('wp_print_styles', 'print_emoji_styles');

function aera_mailchimp_sync($id) {
    if (!defined('AERA_MAILCHIMP_API_KEY') || !defined('AERA_MAILCHIMP_AUDIENCE_ID') || !AERA_MAILCHIMP_API_KEY || !AERA_MAILCHIMP_AUDIENCE_ID) {
        return;
    }
    global $wpdb;
    $table = aera_table();
    $row = $wpdb->get_row($wpdb->prepare("SELECT email, code, synced FROM $table WHERE id = %d", $id));
    if (!$row || $row->synced) {
        return;
    }
    $parts = explode('-', AERA_MAILCHIMP_API_KEY);
    $dc = end($parts);
    if (!preg_match('/^[a-z0-9]+$/', $dc)) {
        return;
    }
    $url = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . rawurlencode(AERA_MAILCHIMP_AUDIENCE_ID) . '/members/' . md5(strtolower($row->email));
    $response = wp_remote_request($url, [
        'method' => 'PUT',
        'timeout' => 8,
        'headers' => ['Authorization' => 'Basic ' . base64_encode('aera:' . AERA_MAILCHIMP_API_KEY), 'Content-Type' => 'application/json'],
        'body' => wp_json_encode(['email_address' => $row->email, 'status_if_new' => 'pending']),
    ]);
    if (!is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), [200, 201], true)) {
        $wpdb->update($table, ['synced' => 1], ['id' => $id], ['%d'], ['%d']);
    }
}
add_action('aera_sync_signup', 'aera_mailchimp_sync');
add_action('admin_post_aera_retry_sync', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed.', 'Forbidden', ['response' => 403]);
    }
    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    check_admin_referer('aera_retry_' . $id);
    aera_mailchimp_sync($id);
    wp_safe_redirect(admin_url('admin.php?page=aera-waitlist'));
    exit;
});

function aera_signup() {
    if (!isset($_POST['aera_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aera_nonce'])), 'aera_signup')) {
        wp_die('Your session expired. Please go back and try again.', 'Signup failed', ['response' => 403]);
    }
    $return = home_url('/#waitlist');
    if (!empty($_POST['website'])) {
        wp_safe_redirect($return);
        exit;
    }
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!is_email($email)) {
        wp_safe_redirect(add_query_arg('signup', 'invalid', home_url('/')) . '#waitlist');
        exit;
    }
    // shortcut: one signup per IP per minute, upgrade to a dedicated abuse service if traffic grows.
    $rate_key = 'aera_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (get_transient($rate_key)) {
        wp_safe_redirect(add_query_arg('signup', 'slow', home_url('/')) . '#waitlist');
        exit;
    }
    set_transient($rate_key, 1, MINUTE_IN_SECONDS);
    global $wpdb;
    $table = aera_table();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT id, code FROM $table WHERE email = %s", $email));
    if ($existing) {
        $code = $existing->code;
    } else {
        $ref = isset($_POST['ref']) ? sanitize_text_field(wp_unslash($_POST['ref'])) : '';
        $referrer = preg_match('/^[a-f0-9]{20}$/', $ref) ? $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE code = %s", $ref)) : null;
        $code = bin2hex(random_bytes(10));
        $ok = $wpdb->insert($table, ['email' => $email, 'code' => $code, 'referred_by' => $referrer ?: null, 'created_at' => current_time('mysql', true)], ['%s', '%s', '%d', '%s']);
        if (!$ok) {
            wp_safe_redirect(add_query_arg('signup', 'error', home_url('/')) . '#waitlist');
            exit;
        }
        wp_schedule_single_event(time() + 10, 'aera_sync_signup', [$wpdb->insert_id]);
    }
    wp_safe_redirect(add_query_arg('joined', $code, home_url('/')) . '#waitlist');
    exit;
}
add_action('admin_post_nopriv_aera_signup', 'aera_signup');
add_action('admin_post_aera_signup', 'aera_signup');

add_action('admin_menu', function () {
    add_menu_page('Launch Waitlist', 'Launch Waitlist', 'manage_options', 'aera-waitlist', 'aera_admin_page', 'dashicons-email-alt', 25);
});
function aera_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    global $wpdb;
    $table = aera_table();
    $rows = $wpdb->get_results("SELECT w.id, w.email, w.code, w.created_at, w.synced, COUNT(r.id) AS referrals FROM $table w LEFT JOIN $table r ON r.referred_by = w.id GROUP BY w.id ORDER BY w.id DESC LIMIT 100");
    echo '<div class="wrap"><h1>Launch Waitlist</h1><p>Latest 100 signups. Mailchimp is ' . (defined('AERA_MAILCHIMP_API_KEY') && defined('AERA_MAILCHIMP_AUDIENCE_ID') ? 'configured' : 'not configured') . '.</p><table class="widefat striped"><thead><tr><th>Email</th><th>Referral code</th><th>Friends referred</th><th>Mailchimp synced</th><th>Joined (UTC)</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr><td>' . esc_html($row->email) . '</td><td>' . esc_html($row->code) . '</td><td>' . esc_html($row->referrals) . '</td><td>';
        if ($row->synced) {
            echo 'Yes';
        } else {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="aera_retry_sync"><input type="hidden" name="id" value="' . esc_attr($row->id) . '">';
            wp_nonce_field('aera_retry_' . $row->id);
            submit_button('Retry sync', 'small', 'submit', false);
            echo '</form>';
        }
        echo '</td><td>' . esc_html($row->created_at) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
