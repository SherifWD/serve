(() => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const root = document.documentElement;

  if (!reduceMotion) {
    root.classList.add('motion-ready');
  }

  const revealItems = document.querySelectorAll('[data-reveal]');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px 120px' }
    );

    revealItems.forEach((item) => observer.observe(item));
  }

  document.querySelectorAll('[data-pricing-region]').forEach((section) => {
    const tabs = section.querySelectorAll('[data-region-target]');
    const prices = section.querySelectorAll('[data-region-price]');

    const setRegion = (region) => {
      section.setAttribute('data-pricing-region', region);
      tabs.forEach((tab) => {
        const active = tab.dataset.regionTarget === region;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      prices.forEach((price) => {
        price.hidden = price.dataset.regionPrice !== region;
      });
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => setRegion(tab.dataset.regionTarget));
    });

    setRegion(section.getAttribute('data-pricing-region') || 'egypt');
  });

  if (reduceMotion) {
    return;
  }

  document.querySelectorAll('[data-tilt]').forEach((item) => {
    item.addEventListener('pointermove', (event) => {
      const rect = item.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      item.style.setProperty('--tilt-x', `${(-y * 5).toFixed(2)}deg`);
      item.style.setProperty('--tilt-y', `${(x * 6).toFixed(2)}deg`);
    });

    item.addEventListener('pointerleave', () => {
      item.style.removeProperty('--tilt-x');
      item.style.removeProperty('--tilt-y');
    });
  });
})();
