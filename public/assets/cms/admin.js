// Init Simple JS Editor on all textareas with data-editor="simple-js-editor"
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('textarea[data-editor="simple-js-editor"]').forEach(function (textarea) {
    var wrapper = document.createElement('div');
    wrapper.style.cssText = 'margin-bottom:.5rem';
    textarea.parentNode.insertBefore(wrapper, textarea);
    textarea.style.display = 'none';

    var editor = window.SimpleJsEditor
      ? window.SimpleJsEditor.createEditor({
          root: wrapper,
          value: textarea.value || '<p></p>',
          toolbar: 'full',
          onChange: function (payload) {
            textarea.value = payload.html;
          },
        })
      : null;

    if (!editor) {
      // Fallback: show textarea as-is
      textarea.style.display = '';
      wrapper.remove();
    }
  });
});
