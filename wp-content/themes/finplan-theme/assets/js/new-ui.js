document.addEventListener('DOMContentLoaded', () => {
  // year in footer
  const year = document.getElementById('year');
  if (year) {
    year.textContent = String(new Date().getFullYear());
  }

  // audit form logic
  const form = document.getElementById('auditForm');
  const ok = document.getElementById('formSuccess');

  if (form && ok) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      ok.classList.remove('d-none');
      form.reset();
      form.classList.remove('was-validated');
      setTimeout(() => ok.classList.add('d-none'), 4500);
    });
  }
});
