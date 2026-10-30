(() => {
  const root = document.documentElement;
  root.classList.add('has-js');
  const themeButton = document.querySelector('.theme-toggle');
  const updateThemeLabel = () => themeButton?.setAttribute('aria-label', `Switch to ${root.dataset.theme === 'dark' ? 'light' : 'dark'} mode`);
  updateThemeLabel();
  themeButton?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('aera-theme', root.dataset.theme); } catch (_) {}
    updateThemeLabel();
  });

  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduced && 'IntersectionObserver' in window) {
    const reveal = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('in-view'); reveal.unobserve(entry.target); }
    }), { threshold: .12 });
    document.querySelectorAll('.reveal').forEach(el => reveal.observe(el));
    const steps = new IntersectionObserver(entries => entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      document.querySelectorAll('.feature-step').forEach(step => step.classList.toggle('is-active', step === entry.target));
      const visual = document.querySelector('.feature-visual');
      visual.dataset.feature = entry.target.dataset.feature;
      document.querySelector('#feature-label').textContent = entry.target.dataset.label;
    }), { rootMargin: '-30% 0px -30% 0px' });
    document.querySelectorAll('.feature-step').forEach(el => steps.observe(el));
  } else document.querySelectorAll('.reveal').forEach(el => el.classList.add('in-view'));

  document.querySelectorAll('[data-billing]').forEach(button => button.addEventListener('click', () => {
    const yearly = button.dataset.billing === 'yearly';
    document.querySelectorAll('[data-billing]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    const price = document.querySelector('#price-value');
    price.textContent = yearly ? '$9.60' : '$12';
    if (!reduced && price.animate) price.animate([{ opacity: .3, transform: 'translateY(8px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 280, easing: 'ease-out' });
    document.querySelector('#price-period').textContent = ' / month';
    document.querySelector('#price-note').textContent = yearly ? 'Billed $115.20 yearly. Bottle sold separately; its price will be confirmed before orders open.' : 'Billed monthly. Bottle sold separately; its price will be confirmed before orders open.';
  }));

  const countdown = document.querySelector('[data-launch]');
  if (countdown) {
    const launch = new Date(countdown.dataset.launch).getTime();
    const tick = () => {
      const remaining = Math.max(0, launch - Date.now());
      const parts = [Math.floor(remaining / 86400000), Math.floor(remaining / 3600000) % 24, Math.floor(remaining / 60000) % 60, Math.floor(remaining / 1000) % 60];
      ['days', 'hours', 'minutes', 'seconds'].forEach((part, i) => countdown.querySelector(`[data-${part}]`).textContent = String(parts[i]).padStart(2, '0'));
      if (!remaining) { clearInterval(timer); countdown.setAttribute('aria-label', 'Early access is open'); document.querySelector('#countdown-intro').textContent = 'Early access is open'; }
    };
    const timer = setInterval(tick, 1000);
    tick();
  }
})();
