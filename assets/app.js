document.addEventListener('click', function (event) {
  const trigger = event.target.closest('[data-confirm]');
  if (trigger && !confirm(trigger.getAttribute('data-confirm') || 'Are you sure?')) {
    event.preventDefault();
  }
});

document.addEventListener('click', function (event) {
  const btn = event.target.closest('[data-toggle-password]');
  if (!btn) return;
  const input = document.getElementById(btn.getAttribute('data-toggle-password'));
  if (!input) return;
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  const icon = btn.querySelector('i');
  if (icon) { icon.classList.toggle('bi-eye', !show); icon.classList.toggle('bi-eye-slash', show); }
  btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  btn.setAttribute('aria-pressed', show ? 'true' : 'false');
});
