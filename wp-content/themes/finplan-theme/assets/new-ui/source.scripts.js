    document.getElementById('year').textContent = new Date().getFullYear();

    const form = document.getElementById('auditForm');
    const ok  = document.getElementById('formSuccess');

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
  
