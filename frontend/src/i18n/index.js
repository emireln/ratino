import { createI18n } from 'vue-i18n';

function loadLocales() {
    const modules = import.meta.glob('../lang/*.json', { eager: true });
    const messages = {};
    for (const path in modules) {
        const match = path.match(/([A-Za-z0-9-_]+)\.json$/);
        if (match) messages[match[1]] = modules[path].default ?? modules[path];
    }
    return messages;
}

export const selectedLocale = navigator.language.split('-')[0] || 'en';

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: localStorage.getItem('ratino_locale') || selectedLocale,
    fallbackLocale: 'en',
    messages: loadLocales()
});

export default i18n;
