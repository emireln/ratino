import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import i18n from './i18n';
import { pinia } from './stores/pinia';
import './sass/app.scss';

import jquery from 'jquery';
window.$ = window.jQuery = jquery;

import lodash from 'lodash';
window._ = lodash;

import 'overlayscrollbars/js/OverlayScrollbars';
import { OverlayScrollbars } from 'overlayscrollbars';

import bus from './lib/bus';
import { toast } from './lib/toast';
import { installEcho } from './lib/echo';
import { useCasino } from './composables/useCasino';

import Icon from './components/ui/Icon.vue';
import Unit from './components/ui/Unit.vue';
import Loader from './components/ui/Loader.vue';

document.documentElement.className = 'theme--dark';

const app = createApp(App);

app.use(pinia);
app.use(router);
app.use(i18n);

app.component('icon', Icon);
app.component('unit', Unit);
app.component('loader', Loader);

app.config.globalProperties.$bus = bus;
app.config.globalProperties.$toast = toast;
app.config.globalProperties.$OverlayScrollbars = OverlayScrollbars;

// global game mixin (Options API parity with the old game.js mixin)
app.mixin({
    computed: {
        ...useCasino().computed
    },
    methods: {
        ...useCasino().methods
    }
});

installEcho();

app.mount('#app');
