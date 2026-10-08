<template></template>

<script>
import { useAppStore } from '../../../stores/app';
import { useModal } from '../../../composables/useModal';

export default {
    props: ['data'],
    computed: {
        sound() { return useAppStore().sound; },
        quick() { return useAppStore().quick; }
    },
    watch: {
        sound() { this.renderButtons(); },
        quick() { this.renderButtons(); }
    },
    methods: {
        renderButtons() {
            const footer = document.querySelector('.game-footer');
            if (!footer) return;
            footer.innerHTML = '';

            const createButton = (icon, title, callback, active = false) => {
                const e = document.createElement('button');
                e.className = `action ${active ? 'active' : ''}`;
                e.type = 'button';
                e.title = title;
                e.setAttribute('aria-label', title);
                e.innerHTML = `<i class="${icon}"></i>`;
                footer.appendChild(e);
                e.onclick = (evt) => {
                    evt.preventDefault();
                    callback(e);
                };
                return e;
            };

            const buttons = (this.data && this.data.buttons) ? this.data.buttons : ['help', 'sound', 'stats'];

            buttons.forEach((name) => {
                switch (name) {
                    case 'help':
                        createButton('fas fa-question-circle', 'Help & Fairness Center', () => {
                            useModal().open('Help', {}, 'help.title', 'wide');
                        });
                        break;
                    case 'sound': {
                        const store = useAppStore();
                        const soundIcon = store.sound ? 'fas fa-volume-up' : 'fas fa-volume-mute';
                        createButton(soundIcon, store.sound ? 'Mute Sound' : 'Unmute Sound', () => {
                            store.setSoundState(!store.sound);
                            this.renderButtons();
                        }, store.sound);
                        break;
                    }
                    case 'stats':
                        createButton('fas fa-chart-area', 'Live Session Stats', () => {
                            useModal().open('SessionStats', {}, 'session.title');
                        });
                        break;
                    case 'quick': {
                        const store = useAppStore();
                        createButton('fas fa-bolt', 'Quick Bet Mode', () => {
                            store.setQuickState(!store.quick);
                            this.renderButtons();
                        }, store.quick);
                        break;
                    }
                }
            });
        }
    },
    mounted() {
        this.renderButtons();
    },
    beforeUnmount() {
        const footer = document.querySelector('.game-footer');
        if (footer) footer.innerHTML = '';
    }
};
</script>
