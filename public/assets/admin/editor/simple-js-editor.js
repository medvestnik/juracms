(function () {
  function initSimpleEditor(textarea) {
    if (!textarea || textarea.dataset.editorInitialized === '1') return;
    textarea.dataset.editorInitialized = '1';
    textarea.classList.add('simple-js-editor');
  }

  function boot() {
    document.querySelectorAll('textarea[data-editor="simple-js-editor"]').forEach(initSimpleEditor);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
