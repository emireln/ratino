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

export default {
    components: { Modal, Sidebar, Header },
    created() {
        const store = useAppStore();
        store.changeLocale(store.locale);
        store.updateData();
        if (store.token) store.update();
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
