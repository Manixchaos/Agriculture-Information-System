document.addEventListener('click', function (event) {
  const trigger = event.target.closest('[data-confirm]');
  if (trigger && !confirm(trigger.getAttribute('data-confirm') || 'Are you sure?')) {
    event.preventDefault();
  }
});
