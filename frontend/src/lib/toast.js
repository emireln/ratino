let container = null;

function ensureContainer() {
    if (container) return container;
    container = document.createElement('div');
    container.id = 'ratino-toasts';
    container.style.cssText = 'position:fixed;top:15px;right:15px;z-index:100000;display:flex;flex-direction:column;gap:8px;';
    document.body.appendChild(container);
    return container;
}

function show(message, type = 'success') {
    const el = document.createElement('div');
    const colors = { success: '#2fd672', error: '#e51f49', info: '#3f7bff' };
    el.style.cssText = `background:#21232a;color:${colors[type]};border-left:4px solid ${colors[type]};padding:12px 18px;border-radius:4px;font-size:0.9em;min-width:220px;max-width:340px;box-shadow:0 6px 24px rgba(0,0,0,.45);transition:opacity .3s ease;`;
    el.textContent = message;
    ensureContainer().appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 350);
    }, 3500);
}

export const toast = {
    success: (m) => show(m, 'success'),
    error: (m) => show(m, 'error'),
    info: (m) => show(m, 'info')
};
