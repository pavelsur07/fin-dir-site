// Formula driven preview for the technical /ui-kit catalog.
(() => {
  const root = document.querySelector('[data-vf-logo-generator]');
  if (!root) return;

  const preview = root.querySelector('[data-vf-logo-preview]');
  const sign = preview.querySelector('.vf-logo-sign');
  const word = preview.querySelector('.vf-logo-word');
  const guide = root.querySelector('[data-vf-logo-guide]');
  const stage = root.querySelector('[data-vf-logo-stage]');
  let size = 64;
  let theme = 'light';
  const output = (key, value) => { root.querySelector(`[data-vf-logo-output="${key}"]`).textContent = value; };

  function render() {
    const radius = Math.round(size * 8 / 38);
    const letters = Math.round(size * 14 / 38);
    const wordSize = Math.round(size * 18 / 38);
    const gap = Math.round(size * 12 / 38);
    const margin = Math.round(letters * 0.72) + Math.round(size * 0.06);
    sign.style.width = sign.style.height = `${size}px`;
    sign.style.borderRadius = `${radius}px`;
    sign.style.fontSize = `${letters}px`;
    word.style.fontSize = `${wordSize}px`;
    preview.style.gap = `${gap}px`;
    guide.style.padding = `${margin}px`;
    if (theme === 'dark') stage.dataset.theme = 'dark';
    else delete stage.dataset.theme;
    output('radius', `${radius} px`);
    output('letters', `${letters} px`);
    output('word', `${wordSize} px`);
    output('gap', `${gap} px`);
    output('margin', `≈ ${margin} px`);
    root.querySelectorAll('[data-vf-logo-size]').forEach(button => button.setAttribute('aria-pressed', String(Number(button.dataset.vfLogoSize) === size)));
    root.querySelectorAll('[data-vf-logo-theme]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.vfLogoTheme === theme)));
  }

  root.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || !root.contains(button)) return;
    if (button.dataset.vfLogoSize) size = Number(button.dataset.vfLogoSize);
    if (button.dataset.vfLogoTheme) theme = button.dataset.vfLogoTheme;
    render();
  });
  render();
})();
