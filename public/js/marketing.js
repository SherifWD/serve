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
      window.dispatchEvent(new CustomEvent('pricing-region-change', { detail: { region } }));
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => setRegion(tab.dataset.regionTarget));
    });

    setRegion(section.getAttribute('data-pricing-region') || 'egypt');
  });

  const checkout = document.querySelector('[data-plan-checkout]');
  if (checkout) {
    const form = checkout.querySelector('[data-checkout-form]');
    const nextButton = checkout.querySelector('[data-checkout-next]');
    const backButton = checkout.querySelector('[data-checkout-back]');
    const payButton = checkout.querySelector('[data-checkout-pay]');
    const status = checkout.querySelector('[data-checkout-status]');
    const planName = checkout.querySelector('[data-checkout-plan-name]');
    const planPrice = checkout.querySelector('[data-checkout-plan-price]');
    const planInput = checkout.querySelector('[data-checkout-plan-input]');
    const priceInput = checkout.querySelector('[data-checkout-price-input]');
    const regionInput = checkout.querySelector('[data-checkout-region-input]');
    const panes = checkout.querySelectorAll('[data-checkout-pane]');
    const stepButtons = checkout.querySelectorAll('[data-checkout-step-button]');
    let currentStep = 1;
    let selectedPlanButton = null;

    const regionLabel = {
      egypt: 'Egypt pricing',
      mena: 'MENA pricing',
      international: 'International pricing',
    };

    const priceKeyFor = (region) => {
      const suffix = region.replace(/(^|-)([a-z])/g, (_, __, char) => char.toUpperCase());
      return `planPrice${suffix}`;
    };

    const currentRegion = () => {
      const pricing = document.querySelector('[data-pricing-region]');
      return pricing?.getAttribute('data-pricing-region') || 'egypt';
    };

    const setStatus = (message = '', isError = false) => {
      if (!status) return;
      status.textContent = message;
      status.classList.toggle('is-error', isError);
    };

    const selectedPrice = () => {
      if (!selectedPlanButton) return '';
      return selectedPlanButton.dataset[priceKeyFor(currentRegion())] || '';
    };

    const syncSelectedPlan = () => {
      if (!selectedPlanButton) return;
      const region = currentRegion();
      const price = selectedPrice();
      planName.textContent = selectedPlanButton.dataset.planName || 'Selected plan';
      planPrice.textContent = price ? `${price} - ${regionLabel[region] || region}` : 'Pricing appears after selection.';
      planInput.value = selectedPlanButton.dataset.planName || '';
      priceInput.value = price;
      if (regionInput) regionInput.value = region;
    };

    const setStep = (step) => {
      currentStep = Math.min(Math.max(step, 1), 3);
      panes.forEach((pane) => {
        pane.classList.toggle('is-active', Number(pane.dataset.checkoutPane) === currentStep);
      });
      stepButtons.forEach((button) => {
        button.classList.toggle('is-active', Number(button.dataset.checkoutStepButton) === currentStep);
      });
      if (backButton) backButton.hidden = currentStep === 1;
      if (nextButton) nextButton.hidden = currentStep === 3;
      if (payButton) payButton.hidden = currentStep !== 3;
      setStatus();
    };

    const validateStep = (step) => {
      if (step === 1 && !selectedPlanButton) {
        setStatus('Choose a standard pricing plan first.', true);
        document.getElementById('pricing')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
        return false;
      }

      const pane = checkout.querySelector(`[data-checkout-pane="${step}"]`);
      const fields = pane ? Array.from(pane.querySelectorAll('input, select, textarea')) : [];
      const invalid = fields.find((field) => !field.checkValidity());
      if (invalid) {
        invalid.reportValidity();
        return false;
      }
      return true;
    };

    document.querySelectorAll('[data-plan-select]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        selectedPlanButton = button;
        syncSelectedPlan();
        setStep(1);
        setStatus('Plan selected. Complete your details to continue.');
        document.getElementById('checkout')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      });
    });

    stepButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetStep = Number(button.dataset.checkoutStepButton);
        if (targetStep <= currentStep) {
          setStep(targetStep);
          return;
        }

        if (validateStep(currentStep)) {
          setStep(Math.min(targetStep, currentStep + 1));
        }
      });
    });

    nextButton?.addEventListener('click', () => {
      if (validateStep(currentStep)) {
        setStep(currentStep + 1);
      }
    });

    backButton?.addEventListener('click', () => setStep(currentStep - 1));

    window.addEventListener('pricing-region-change', syncSelectedPlan);

    form?.addEventListener('submit', (event) => {
      event.preventDefault();
      for (let step = 1; step <= 3; step += 1) {
        setStep(step);
        if (!validateStep(step)) {
          return;
        }
      }
      setStep(3);

      syncSelectedPlan();
      setStatus('Saving checkout and opening Paymob...');
      form.submit();
    });

    setStep(1);
  }

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
