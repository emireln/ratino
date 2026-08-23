/**
 * BYOK AI — provider calls run entirely from the browser with the user's
 * own key. Nothing is stored server-side.
 */

export const PROVIDERS = {
    gemini: {
        name: 'Google Gemini',
        model: 'gemini-2.0-flash',
        endpoint: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'
    },
    openai: {
        name: 'OpenAI',
        model: 'gpt-4o-mini',
        endpoint: 'https://api.openai.com/v1/chat/completions'
    }
};

export function getProvider() {
    return localStorage.getItem('ai_provider') === 'openai' ? 'openai' : 'gemini';
}

export function getKey(provider = null) {
    const p = provider || getProvider();
    return localStorage.getItem('ai_' + p + '_key') || '';
}

export function hasKey(provider = null) {
    return getKey(provider).length > 0;
}

export function setKey(provider, key) {
    localStorage.setItem('ai_' + provider + '_key', key.trim());
}

export function setProvider(provider) {
    localStorage.setItem('ai_provider', provider);
}

const SYSTEM_PROMPT = [
    'You are the RATINO casino assistant, a friendly guide for a casual social casino app.',
    'You explain game mechanics (Mines, Dice, Crash, Plinko, Blackjack, Roulette, Baccarat, Keno, Slots and more),',
    'you analyze odds and house edge, and you give sensible bankroll management advice.',
    'You never encourage chasing losses or gambling money someone cannot afford to lose.',
    'Answer briefly and clearly, in the same language the player uses.'
].join(' ');

function toGeminiMessages(history) {
    const parts = [];
    for (const m of history) {
        if (m.role === 'system') continue;
        parts.push({ role: m.role === 'assistant' ? 'model' : 'user', parts: [{ text: m.content }] });
    }
    return [{ role: 'user', parts: [{ text: SYSTEM_PROMPT }] }, ...parts];
}

function toOpenAIMessages(history) {
    return [{ role: 'system', content: SYSTEM_PROMPT }, ...history.map((m) => ({ role: m.role, content: m.content }))];
}

export async function ask(history, maxTokens = 1024) {
    const provider = getProvider();
    const key = getKey(provider);
    if (!key) throw new Error('NO_KEY');

    const started = performance.now();
    let content;

    if (provider === 'gemini') {
        const url = PROVIDERS.gemini.endpoint + '?key=' + encodeURIComponent(key);
        const body = {
            contents: toGeminiMessages(history),
            generationConfig: { maxOutputTokens: maxTokens, temperature: 0.7 }
        };
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!response.ok) throw new Error('Gemini error ' + response.status);
        const json = await response.json();
        content = (json.candidates && json.candidates[0] && json.candidates[0].content.parts || [])
            .map((p) => p.text || '').join('');
    } else {
        const body = {
            model: PROVIDERS.openai.model,
            messages: toOpenAIMessages(history),
            max_tokens: maxTokens,
            temperature: 0.7
        };
        const response = await fetch(PROVIDERS.openai.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + key
            },
            body: JSON.stringify(body)
        });
        if (!response.ok) throw new Error('OpenAI error ' + response.status);
        const json = await response.json();
        content = (json.choices && json.choices[0] && json.choices[0].message.content) || '';
    }

    if (!content) throw new Error('Empty response');
    return { content, ms: Math.round(performance.now() - started) };
}
