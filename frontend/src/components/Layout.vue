<template>
    <div class="d-flex">
        <Modal />
        <div class="wrapper">
            <Sidebar />
            <div class="pageWrapper">
                <Header />
                <router-view :key="$route.fullPath" class="pageContent"></router-view>
            </div>
        </div>
    </div>
</template>

<script>
import Modal from './modals/Modal.vue';
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';
import { useAppStore } from '../stores/app';
import { connectRealtime } from '../lib/echo';
import bus from '../lib/bus';

export default {
    components: { Modal, Sidebar, Header },
    created() {
        const store = useAppStore();
        store.changeLocale(store.locale);
        store.updateData();
        if (store.token) store.update();
        if (store.user && store.user._id) connectRealtime(store.user._id);

        bus.on('event:balanceModification', (e) => {
            if (!e) return;
            const currId = e.currency || 'credits';
            if (store.currencies && store.currencies[currId]) {
                if (e.demo) {
                    store.currencies[currId].balance.demo = e.demo_balance ?? e.balance;
                } else {
                    store.currencies[currId].balance.real = e.balance;
                }
            }
            if (store.user) {
                if (e.demo) {
                    store.user.demo_credits = e.demo_balance ?? e.balance;
                } else {
                    store.user.credits = e.balance;
                }
            }
        });
    }
};
</script>

<style lang="scss">
@import '../sass/variables';

.wrapper {
    width: 100%;
    display: flex;
    min-height: 100vh;
}

.pageWrapper {
    width: 100%;
    margin-left: $sidebar-width;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.pageContent {
    padding: 20px 24px 40px;
    flex: 1;
    width: 100%;
    max-width: 1320px;
    margin: 0 auto;
}

@media(max-width: 991px) {
    .pageWrapper { margin-left: 0; }
    .pageContent { padding: 15px 12px 30px; }
}
</style>
