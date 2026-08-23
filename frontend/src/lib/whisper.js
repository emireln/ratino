import axios from 'axios';

const client = axios.create({
    baseURL: '/',
    withCredentials: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
});

export function setToken(token) {
    if (token) client.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    else delete client.defaults.headers.common['Authorization'];
}

/**
 * Whisper transport — POST /api/whisper {event, data}. Rejects with the
 * server `code` when the whisper returns an error payload.
 */
export function whisper(event, request = {}) {
    return new Promise((resolve, reject) => {
        client.post('/api/whisper', { event, data: request })
            .then(({ data }) => {
                if (data && data.message != null && data.errors != null) return reject(0);
                if (data && data.code != null) return reject(data.code);
                resolve(data);
            })
            .catch(reject);
    });
}

export default client;
