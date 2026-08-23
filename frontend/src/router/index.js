import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        component: () => import('../components/views/CasinoIndex.vue')
    },
    {
        path: '/casino/game/:id',
        component: () => import('../components/views/GameView.vue')
    },
    {
        path: '/favorites',
        component: () => import('../components/views/CasinoIndex.vue'),
        props: { filterFavorites: true }
    },
    {
        path: '/fairness',
        component: () => import('../components/views/Fairness.vue')
    },
    {
        path: '/:pathMatch(.*)*',
        component: () => import('../components/views/NotFound.vue')
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
