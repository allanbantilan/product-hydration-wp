<?php
defined('ABSPATH') || exit;
global $wpdb;
$table = aera_table();
$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
?>
<section class="section" id="voices"><div class="container"><div class="section-head reveal"><span class="eyebrow">A little more, every day</span><h2>A better routine<br>starts with one sip.</h2><p><?php if ($count) : ?><?php echo esc_html(number_format_i18n($count)); ?> <?php echo $count === 1 ? 'person is' : 'people are'; ?> on the early access list.<?php else : ?>Early access is open. Be among the first to join.<?php endif; ?></p></div><div class="proof-grid"><div class="proof-card reveal"><span class="proof-icon" aria-hidden="true">01 / REMIND</span><h3>A quieter kind of reminder.</h3><p>A soft glow makes it easier to pause for water without another alert on your phone.</p></div><div class="proof-card reveal"><span class="proof-icon" aria-hidden="true">02 / REPEAT</span><h3>Designed to stay with you.</h3><p>Thoughtful insulation and an easy daily carry help a small habit become part of your rhythm.</p></div></div></div></section>
