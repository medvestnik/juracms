const THEME_KEY = 'jura-theme';
const THEMES = ['light', 'dark', 'auto'];

function initTheme() {
  const saved = localStorage.getItem(THEME_KEY);
  if (saved && THEMES.includes(saved)) {
    document.documentElement.setAttribute('data-theme', saved);
  }

  document.querySelectorAll('[data-jura-theme="toggle"]').forEach((button) => {
    button.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') ?? 'auto';
      const next = current === 'light' ? 'dark' : current === 'dark' ? 'auto' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem(THEME_KEY, next);
    });
  });
}
function initDropdowns() {
  document.querySelectorAll('[data-jura-toggle="dropdown"]').forEach((trigger) => {
    const targetSelector = trigger.getAttribute('data-jura-target');
    if (!targetSelector) return;
    const menu = document.querySelector(targetSelector);
    if (!menu) return;

    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      menu.hidden = !menu.hidden;
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.jura-dropdown-menu').forEach((menu) => {
      menu.hidden = true;
    });
  });
}
function openModal(modal) {
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
  modal.hidden = true;
  document.body.style.overflow = '';
}

function initModals() {
  document.querySelectorAll('[data-jura-toggle="modal"]').forEach((trigger) => {
    const targetSelector = trigger.getAttribute('data-jura-target');
    if (!targetSelector) return;
    const modal = document.querySelector(targetSelector);
    if (!modal) return;

    trigger.addEventListener('click', () => openModal(modal));

    modal.querySelectorAll('[data-jura-close]').forEach((closeNode) => {
      closeNode.addEventListener('click', () => closeModal(modal));
    });
  });
}
function initTabs() {
  document.querySelectorAll('[data-jura-tabs]').forEach((tabsRoot) => {
    const tabButtons = tabsRoot.querySelectorAll('[data-jura-tab]');

    tabButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const target = button.getAttribute('data-jura-tab');
        if (!target) return;

        tabButtons.forEach((b) => b.classList.remove('is-active'));
        button.classList.add('is-active');

        const panels = tabsRoot.parentElement?.querySelectorAll('.jura-tab-panel') ?? [];
        panels.forEach((panel) => panel.classList.remove('is-active'));
        const activePanel = document.getElementById(target);
        if (activePanel) activePanel.classList.add('is-active');
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', function() {
  if (typeof initTheme === 'function') initTheme();
  if (typeof initDropdowns === 'function') initDropdowns();
  if (typeof initModals === 'function') initModals();
  if (typeof initTabs === 'function') initTabs();
});
