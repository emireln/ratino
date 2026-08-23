import { buildSystemPrompt, sanitizePlayerMessage, fencePlayerMessage } from './aiContext';

/**
 * BYOK AI — provider calls run entirely from the browser with the user's
 * own key. Nothing is stored server-side.
 */

export const PROVIDERS = {
    gemini: {
        name: 'Google Gemini',
        model: 'gemini-2.5-flash',
        endpoint: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
    },
    openai: {
        name: 'OpenAI',
        model: 'gpt-4o',
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

const SYSTEM_PROMPT = buildSystemPrompt();

/**
 * History entries are untrusted data: sanitize, then fence each player
 * message so the model treats it as content, never as instructions.
 */
function toGeminiMessages(history) {
    const contents = [];
    let lastRole = null;
    for (const m of history) {
        if (!m.content || m.role === 'system') continue;
        const text = m.role === 'user' ? fencePlayerMessage(sanitizePlayerMessage(m.content)) : sanitizePlayerMessage(m.content);
        const role = m.role === 'assistant' ? 'model' : 'user';
        if (role === lastRole && contents.length > 0) {
            contents[contents.length - 1].parts[0].text += '\n' + text;
        } else {
            contents.push({ role, parts: [{ text }] });
            lastRole = role;
        }
    }
    if (contents.length === 0) {
        contents.push({ role: 'user', parts: [{ text: fencePlayerMessage('Hello') }] });
    }
    return contents;
}

function toOpenAIMessages(history) {
    return [{
        role: 'system',
        content: SYSTEM_PROMPT
    }, ...history.filter((m) => m.content).map((m) => ({
        role: m.role,
        content: m.role === 'user' ? fencePlayerMessage(sanitizePlayerMessage(m.content)) : sanitizePlayerMessage(m.content)
    }))];
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
            systemInstruction: {
                parts: [{ text: SYSTEM_PROMPT }]
            },
            contents: toGeminiMessages(history),
            generationConfig: { maxOutputTokens: maxTokens, temperature: 0.5 }
        };
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            const msg = (err && err.error && err.error.message) ? err.error.message : ('Gemini error ' + response.status);
            throw new Error(msg);
        }
        const json = await response.json();
        content = (json.candidates && json.candidates[0] && json.candidates[0].content && json.candidates[0].content.parts || [])
            .map((p) => p.text || '').join('');
    } else {
        const body = {
            model: PROVIDERS.openai.model,
            messages: toOpenAIMessages(history),
            max_tokens: maxTokens,
            temperature: 0.5
        };
        const response = await fetch(PROVIDERS.openai.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + key
            },
            body: JSON.stringify(body)
        });
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            const msg = (err && err.error && err.error.message) ? err.error.message : ('OpenAI error ' + response.status);
            throw new Error(msg);
        }
        const json = await response.json();
        content = (json.choices && json.choices[0] && json.choices[0].message.content) || '';
    }

    if (!content) throw new Error('Empty response');
    return { content, ms: Math.round(performance.now() - started) };
}
