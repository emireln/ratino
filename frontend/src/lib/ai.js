import { buildSystemPrompt, sanitizePlayerMessage, fencePlayerMessage } from './aiContext.js';

/**
 * BYOK assistant. Provider calls run from the player's browser with the player's own key;
 * RATINO's servers never see the key or the messages.
 */

// Model IDs checked against the providers' model pages (October 2026).
export const PROVIDERS = {
    gemini: { name: 'Google Gemini', model: 'gemini-3.8-flash' },
    openai: { name: 'OpenAI', model: 'gpt-6-luna' }
};

const GEMINI_URL = (model) => `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent`;
const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';

// Thinking tokens count toward these caps, so they leave room for the visible answer.
const CHAT_MAX_TOKENS = 4096;
const TEST_MAX_TOKENS = 1024;
const CHAT_TIMEOUT_MS = 60000;
const TEST_TIMEOUT_MS = 20000;
const MAX_CONTEXT_TURNS = 12;

/** Failure with a stable code; the UI maps codes to translated text (see errorKey). */
export class AssistantError extends Error {
    constructor(code, detail = '') {
        super(code);
        this.code = code;
        this.detail = detail;
    }
}

export function errorKey(error) {
    return 'ai.err.' + (error instanceof AssistantError ? error.code : 'failed');
}

export function getProvider() {
    return localStorage.getItem('ai_provider') === 'openai' ? 'openai' : 'gemini';
}

export function getKey(provider = null) {
    return localStorage.getItem('ai_' + (provider || getProvider()) + '_key') || '';
}

export function hasKey(provider = null) {
    return getKey(provider).length > 0;
}

export function setKey(provider, key) {
    localStorage.setItem('ai_' + provider + '_key', key.trim());
}

export function removeKey(provider) {
    localStorage.removeItem('ai_' + provider + '_key');
}

export function setProvider(provider) {
    localStorage.setItem('ai_provider', provider);
}

/** Chat history is kept per account, so another player on the same browser never sees it. */
export function chatHistoryKey(userId) {
    return 'ai_chat_history:' + (userId || 'guest');
}

/** Logging out removes the key, the provider choice and every saved chat from this browser. */
export function clearAssistantData() {
    for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('ai_')) localStorage.removeItem(key);
    }
}

const SYSTEM_PROMPT = buildSystemPrompt();

/**
 * Only real questions and answers reach the provider. Error notices are not part of the
 * conversation, and the context must open with a player turn (both providers require it).
 */
export function contextFrom(messages) {
    let turns = messages
        .filter((m) => m.content && !m.error && (m.role === 'user' || m.role === 'assistant'))
        .slice(-MAX_CONTEXT_TURNS);
    while (turns.length && turns[0].role !== 'user') turns = turns.slice(1);
    return turns;
}

function toGeminiContents(turns) {
    const contents = [];
    for (const m of turns) {
        const role = m.role === 'assistant' ? 'model' : 'user';
        const text = m.role === 'user' ? fencePlayerMessage(sanitizePlayerMessage(m.content)) : sanitizePlayerMessage(m.content);
        const last = contents[contents.length - 1];
        if (last && last.role === role) last.parts[0].text += '\n' + text;
        else contents.push({ role, parts: [{ text }] });
    }
    if (contents.length === 0) contents.push({ role: 'user', parts: [{ text: fencePlayerMessage('Hello') }] });
    return contents;
}

function toOpenAIMessages(turns) {
    return [
        { role: 'system', content: SYSTEM_PROMPT },
        ...turns.map((m) => ({
            role: m.role,
            content: m.role === 'user' ? fencePlayerMessage(sanitizePlayerMessage(m.content)) : sanitizePlayerMessage(m.content)
        }))
    ];
}

function httpError(status, detail = '') {
    if (status === 401 || status === 403 || (status === 400 && /api key/i.test(detail))) return new AssistantError('auth', detail);
    if (status === 400) return new AssistantError('bad_request', detail);
    if (status === 404) return new AssistantError('model', detail);
    if (status === 429) return new AssistantError('quota', detail);
    if (status >= 500) return new AssistantError('unavailable', detail);
    return new AssistantError('http', detail);
}

/** Runs one request with a deadline and an optional caller signal (the Stop button). */
async function withTimeout(signal, timeoutMs, run) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    const onCancel = () => controller.abort();
    if (signal) {
        if (signal.aborted) controller.abort();
        signal.addEventListener('abort', onCancel, { once: true });
    }
    try {
        return await run(controller.signal);
    } catch (error) {
        if (error instanceof AssistantError) throw error;
        if (controller.signal.aborted) throw new AssistantError(signal && signal.aborted ? 'cancelled' : 'timeout');
        throw new AssistantError('network', error && error.message ? error.message : '');
    } finally {
        clearTimeout(timer);
        if (signal) signal.removeEventListener('abort', onCancel);
    }
}

async function send(provider, key, messages, maxTokens, signal) {
    const turns = contextFrom(messages);
    const isOpenAI = provider === 'openai';
    const url = isOpenAI ? OPENAI_URL : GEMINI_URL(PROVIDERS.gemini.model);
    // The key travels in a header, never in the URL, so it stays out of logs and history.
    const headers = isOpenAI
        ? { 'Content-Type': 'application/json', Authorization: 'Bearer ' + key }
        : { 'Content-Type': 'application/json', 'x-goog-api-key': key };
    const body = isOpenAI
        ? { model: PROVIDERS.openai.model, messages: toOpenAIMessages(turns), max_completion_tokens: maxTokens, reasoning_effort: 'low' }
        : { systemInstruction: { parts: [{ text: SYSTEM_PROMPT }] }, contents: toGeminiContents(turns), generationConfig: { maxOutputTokens: maxTokens } };

    const response = await fetch(url, { method: 'POST', headers, body: JSON.stringify(body), signal });
    const json = await response.json().catch((error) => {
        if (error && error.name === 'AbortError') throw error;
        return {};
    });
    if (!response.ok) throw httpError(response.status, (json.error && json.error.message) || '');
    return json;
}

function readReply(provider, json) {
    if (provider === 'openai') {
        const choice = (json.choices || [])[0] || {};
        return { text: String((choice.message && choice.message.content) || '').trim(), finish: choice.finish_reason };
    }
    const candidate = (json.candidates || [])[0] || {};
    const text = (candidate.content && candidate.content.parts || []).map((p) => p.text || '').join('').trim();
    const finish = candidate.finishReason || (json.promptFeedback && json.promptFeedback.blockReason ? 'SAFETY' : '');
    return { text, finish };
}

/**
 * Asks the model. `messages` is the whole chat (oldest first, last entry is the new question).
 * Resolves { content, truncated, ms }; rejects with AssistantError.
 */
export async function ask(messages, options = {}) {
    const provider = options.provider || getProvider();
    const key = options.key || getKey(provider);
    if (!key) throw new AssistantError('no_key');

    const started = performance.now();
    const json = await withTimeout(options.signal, options.timeoutMs || CHAT_TIMEOUT_MS,
        (signal) => send(provider, key, messages, options.maxTokens || CHAT_MAX_TOKENS, signal));
    const { text, finish } = readReply(provider, json);
    const truncated = finish === 'length' || finish === 'MAX_TOKENS';

    if (!text) {
        if (truncated) throw new AssistantError('truncated');
        if (['content_filter', 'SAFETY', 'BLOCKLIST', 'PROHIBITED_CONTENT', 'SPII', 'RECITATION'].includes(finish)) throw new AssistantError('blocked');
        throw new AssistantError('empty');
    }
    return { content: text, truncated, ms: Math.round(performance.now() - started) };
}

/** Connection test: a successful HTTP answer proves the key and model work, whatever the text. */
export async function testKey(provider, key) {
    const started = performance.now();
    await withTimeout(null, TEST_TIMEOUT_MS,
        (signal) => send(provider, key, [{ role: 'user', content: 'Reply with OK.' }], TEST_MAX_TOKENS, signal));
    return { ms: Math.round(performance.now() - started) };
}
