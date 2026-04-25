document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-close-modal]');
  if (!trigger) return;
  const id = trigger.getAttribute('data-close-modal');
  const modal = document.getElementById(id);
  if (modal) modal.hidden = true;
});
