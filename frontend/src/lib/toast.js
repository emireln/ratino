let container = null;
const MAX_TOASTS = 5;

function ensureContainer() {
    if (container) return container;
    container = document.createElement('div');
    container.id = 'ratino-toasts';
    container.style.cssText = 'position:fixed;top:15px;right:15px;z-index:100000;display:flex;flex-direction:column;gap:8px;pointer-events:none;';
    document.body.appendChild(container);
    return container;
}

function dismissToast(el) {
    if (!el || el.dataset.dismissing === 'true') return;
    el.dataset.dismissing = 'true';
    el.style.opacity = '0';
    el.style.transform = 'translateX(20px)';
    setTimeout(() => {
        if (el.parentNode) el.remove();
    }, 300);
}

function show(message, type = 'success') {
    const parent = ensureContainer();

    // If limit of 5 is reached, fade out the oldest
    const active = Array.from(parent.children).filter(c => c.dataset.dismissing !== 'true');
    while (active.length >= MAX_TOASTS) {
        const oldest = active.shift();
        dismissToast(oldest);
    }

    const el = document.createElement('div');
    const colors = { success: '#2fd672', error: '#e51f49', info: '#3f7bff' };
    el.style.cssText = `background:#21232a;color:${colors[type] || '#fff'};border-left:4px solid ${colors[type] || '#fff'};padding:12px 18px;border-radius:6px;font-size:0.9em;font-weight:600;min-width:240px;max-width:min(360px,calc(100vw - 30px));box-shadow:0 8px 24px rgba(0,0,0,.5);transition:opacity .25s ease, transform .25s ease;pointer-events:auto;cursor:pointer;`;
    el.textContent = message;
    parent.appendChild(el);

    const timer = setTimeout(() => {
        dismissToast(el);
    }, 3500);

    el.onclick = () => {
        clearTimeout(timer);
        dismissToast(el);
    };
}

export const toast = {
    success: (m) => show(m, 'success'),
    error: (m) => show(m, 'error'),
    info: (m) => show(m, 'info')
};
