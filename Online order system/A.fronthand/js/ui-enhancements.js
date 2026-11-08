document.addEventListener('DOMContentLoaded', () => {
  injectAppleEffectStyles();
  addAuthBrandLogo();
  initReveal();
  initRipple();
  initSoftCardHover();
  initRowHover();
});

function injectAppleEffectStyles() {
  if (document.getElementById('apple-ui-effects')) return;

  const style = document.createElement('style');
  style.id = 'apple-ui-effects';
  style.textContent = `
    @keyframes appleRipple {
      to {
        transform: scale(2.6);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);
}

function addAuthBrandLogo() {
  const authBrand = document.querySelector('.auth-brand');
  if (!authBrand || authBrand.querySelector('.auth-brand-logo')) return;

  const img = document.createElement('img');
  img.src = 'images/logo.png';
  img.alt = 'Douglas Logo';
  img.className = 'auth-brand-logo';
  img.onerror = function () {
    this.style.display = 'none';
  };

  const badge = authBrand.querySelector('.brand-badge');
  if (badge) {
    badge.insertAdjacentElement('afterend', img);
  } else {
    authBrand.prepend(img);
  }
}

function initReveal() {
  const elements = document.querySelectorAll(
    '.topbar-left, .topbar-right-card, .id-box, .tab-content, .product-card, .profile-card, .auth-card, .auth-brand, .media-preview-item'
  );

  if (!('IntersectionObserver' in window)) return;

  elements.forEach((el) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(10px)';
    el.style.transition = 'opacity 0.36s ease, transform 0.36s ease';
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.08 });

  elements.forEach((el) => observer.observe(el));
}

function initRipple() {
  const buttons = document.querySelectorAll('button');

  buttons.forEach((button) => {
    if (button.dataset.appleRippleBound === '1') return;
    button.dataset.appleRippleBound = '1';

    if (!button.style.position) button.style.position = 'relative';
    button.style.overflow = 'hidden';

    button.addEventListener('click', function (e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.position = 'absolute';
      ripple.style.width = `${size}px`;
      ripple.style.height = `${size}px`;
      ripple.style.left = `${x}px`;
      ripple.style.top = `${y}px`;
      ripple.style.borderRadius = '50%';
      ripple.style.background = 'rgba(255,255,255,0.30)';
      ripple.style.transform = 'scale(0)';
      ripple.style.animation = 'appleRipple 0.52s ease-out';
      ripple.style.pointerEvents = 'none';

      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 540);
    });
  });
}

function initSoftCardHover() {
  const cards = document.querySelectorAll('.product-card');

  cards.forEach((card) => {
    let frame = null;

    card.addEventListener('mousemove', (e) => {
      if (frame) cancelAnimationFrame(frame);

      frame = requestAnimationFrame(() => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const rotateY = ((x / rect.width) - 0.5) * 2;
        const rotateX = ((y / rect.height) - 0.5) * -2;

        card.style.transform = `translateY(-3px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      });
    });

    card.addEventListener('mouseleave', () => {
      if (frame) cancelAnimationFrame(frame);
      card.style.transform = '';
    });
  });
}

function initRowHover() {
  const rows = document.querySelectorAll('tbody tr');

  rows.forEach((row) => {
    row.addEventListener('mouseenter', () => {
      row.style.transition = 'transform 0.16s ease';
      row.style.transform = 'scale(1.001)';
    });

    row.addEventListener('mouseleave', () => {
      row.style.transform = '';
    });
  });
}