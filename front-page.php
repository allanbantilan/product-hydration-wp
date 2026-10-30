<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?> data-theme="light"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Meet a smart bottle designed to make hydration feel effortless. Join the early access waitlist."><script>try{document.documentElement.dataset.theme=localStorage.getItem('aera-theme')||'light'}catch(e){}</script><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header"><nav class="container nav" aria-label="Main navigation">
 <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Hydration home"><span class="brand-mark" aria-hidden="true">H</span>HYDRATION</a>
 <div class="nav-links"><a href="#story">The idea</a><a href="#features">Features</a><a href="#how">How it works</a><a href="#pricing">Pricing</a></div>
 <div class="nav-actions"><button class="icon-button theme-toggle" type="button" aria-label="Switch to dark mode" title="Toggle color mode"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8 8.5 8.5 0 1 0 20.2 15.6Z"/></svg></button><a class="button" href="#waitlist" aria-label="Join the waitlist">Join <span aria-hidden="true">↗</span></a></div>
</nav></header>
<main id="main"><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></main><footer class="site-footer"><div class="container footer-inner"><div><a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark" aria-hidden="true">H</span>HYDRATION</a><br><small>Designed for a little more life.</small></div><div class="footer-links"><a href="#story">Our story</a><a href="#features">Features</a><a href="#faq">FAQ</a><a href="#waitlist">Early access</a></div><small>© <?php echo esc_html(gmdate('Y')); ?> Hydration. All rights reserved.</small></div></footer>
<?php wp_footer(); ?></body></html>
