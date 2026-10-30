<?php
defined('ABSPATH') || exit;
global $wpdb;
$table = aera_table();
$joined = isset($_GET['joined']) ? sanitize_text_field(wp_unslash($_GET['joined'])) : '';
$member = preg_match('/^[a-f0-9]{20}$/', $joined) ? $wpdb->get_row($wpdb->prepare("SELECT id, code FROM $table WHERE code = %s", $joined)) : null;
$referrals = $member ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE referred_by = %d", $member->id)) : 0;
$rank = $member ? 1 + (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM (SELECT w.id, COUNT(r.id) AS referrals FROM $table w LEFT JOIN $table r ON r.referred_by = w.id GROUP BY w.id) ranked WHERE referrals > %d OR (referrals = %d AND id < %d)", $referrals, $referrals, $member->id)) : 0;
$ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : '';
$ref = preg_match('/^[a-f0-9]{20}$/', $ref) ? $ref : '';
$signup = isset($_GET['signup']) ? sanitize_key(wp_unslash($_GET['signup'])) : '';

?>
<section class="final-cta" id="waitlist"><div class="container final-grid"><div class="reveal"><span class="eyebrow">Your better rhythm starts here</span><h2>Be first to feel the flow.</h2><p id="countdown-intro">Early access opens in</p><div class="countdown" data-launch="2027-03-15T09:00:00+08:00" role="timer" aria-label="Time until early access"><div><strong data-days>--</strong><span>Days</span></div><div><strong data-hours>--</strong><span>Hours</span></div><div><strong data-minutes>--</strong><span>Minutes</span></div><div><strong data-seconds>--</strong><span>Seconds</span></div></div></div><div class="form-card reveal"><h3>Join the early access list.</h3><p>One email when it's ready. Your own link to bring friends along.</p>
<?php if ($member) : ?><p class="form-feedback" role="status">You're on the list! Share your link to move up.</p><div class="share-box"><strong>Your referral link</strong><a href="<?php echo esc_url(add_query_arg('ref', $member->code, home_url('/'))); ?>"><?php echo esc_html(add_query_arg('ref', $member->code, home_url('/'))); ?></a><p>You're #<?php echo esc_html($rank); ?> on the list. <?php echo esc_html($referrals); ?> friend<?php echo $referrals === 1 ? '' : 's'; ?> joined through your link.</p></div><?php else : ?>
<?php if ($signup) : ?><p class="form-feedback error" role="alert"><?php echo esc_html($signup === 'invalid' ? 'Please enter a valid email address.' : ($signup === 'slow' ? 'Please wait a minute before trying again.' : 'Something went wrong. Please try again.')); ?></p><?php endif; ?>
<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="aera_signup"><input type="hidden" name="ref" value="<?php echo esc_attr($ref); ?>"><?php wp_nonce_field('aera_signup', 'aera_nonce'); ?><div class="honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required maxlength="190"><button class="button" type="submit">Save my spot <span aria-hidden="true">↗</span></button><small>By joining, you agree to receive launch emails. Unsubscribe anytime.</small></form><?php endif; ?></div></div></section>
