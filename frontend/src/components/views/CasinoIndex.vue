<template>
    <div class="casino-index">
        <hero-carousel v-if="!filterFavorites"></hero-carousel>
        <games-grid :categories="categoryGames" :only-favorites="filterFavorites"></games-grid>
        <live-feed></live-feed>
    </div>
</template>

<script>
import GamesGrid from '../GamesGrid.vue';
import LiveFeed from '../LiveFeed.vue';
import HeroCarousel from '../HeroCarousel.vue';
import { useAppStore } from '../../stores/app';

export default {
    components: { GamesGrid, LiveFeed, HeroCarousel },
    props: {
        filterFavorites: { type: Boolean, default: false }
    },
    computed: {
        store() {
            return useAppStore();
        },
        categoryGames() {
            const cats = {};
            const duplicates = [];
            const games = this.store.games || [];

            games.forEach((game) => {
                if (!game || !game.category) return;
                game.category.forEach((category) => {
                    if (category === 'evo-play') return;
                    if (duplicates.includes(game.id)) return;
                    duplicates.push(game.id);
                    if (!cats[category]) cats[category] = [game];
                    else cats[category].push(game);
                });
            });

            return cats;
        }
    },
    mounted() {
        if (!this.store.games || !this.store.games.length) {
            this.store.updateData();
        }
    }
};
</script>

<style lang="scss">
.casino-index {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}
</style>
