import { reactive } from 'vue';
import bus from '../lib/bus';

const state = reactive({ modals: [] });

export function useModal() {
    return {
        state,
        open(name, props = {}, title = null) {
            state.modals.push({ name, props, title });
            document.body.classList.add('show');
        },
        close() {
            state.modals.pop();
            if (!state.modals.length) document.body.classList.remove('show');
        },
        closeAll() {
            state.modals = [];
            document.body.classList.remove('show');
        }
    };
}

export { bus };
