<template>
    <div class="games-grid">
        <!-- Favorites Section if any exist and not filtering only favorites -->
        <div class="favorites-section" v-if="favoriteGamesList.length && !onlyFavorites">
            <div class="index_cat favorites-heading">
                <i class="fas fa-star text-warning"></i>
                <span>Favorites</span>
                <span class="badge-count">{{ favoriteGamesList.length }}</span>
            </div>
            <div class="games">
                <div v-for="game in favoriteGamesList" :key="'fav-' + game.id" class="game_poster"
                    @click="game.isDisabled ? null : $router.push(`/casino/game/${game.id}`)">
                    <div class="game_poster-image" :class="`game-${game.id}-image`">
                        <button class="btn-favorite active" @click.stop="toggleFavorite(game.id)" title="Remove from favorites">
                            <i class="fas fa-star"></i>
                        </button>
                        <div class="unavailable" v-if="game.isDisabled">
                            <div class="content">{{ $t(game.isPlaceholder ? 'general.coming_soon' : 'general.not_available') }}</div>
                        </div>
                    </div>
                    <div class="game_poster-name">
                        <icon :icon="game.icon" class="game_poster-icon"></icon>
                        <span>{{ game.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Only Favorites view -->
        <div v-if="onlyFavorites">
            <div class="index_cat favorites-heading">
                <i class="fas fa-star text-warning"></i>
                <span>Your Favorite Games</span>
            </div>
            <div class="games" v-if="favoriteGamesList.length">
                <div v-for="game in favoriteGamesList" :key="'onlyfav-' + game.id" class="game_poster"
                    @click="game.isDisabled ? null : $router.push(`/casino/game/${game.id}`)">
                    <div class="game_poster-image" :class="`game-${game.id}-image`">
                        <button class="btn-favorite active" @click.stop="toggleFavorite(game.id)" title="Remove from favorites">
                            <i class="fas fa-star"></i>
                        </button>
                        <div class="unavailable" v-if="game.isDisabled">
                            <div class="content">{{ $t(game.isPlaceholder ? 'general.coming_soon' : 'general.not_available') }}</div>
                        </div>
                    </div>
                    <div class="game_poster-name">
                        <icon :icon="game.icon" class="game_poster-icon"></icon>
                        <span>{{ game.name }}</span>
                    </div>
                </div>
            </div>
            <div class="games-empty-favorites" v-else>
                <i class="far fa-star"></i>
                <div class="mt-2">No favorite games added yet.</div>
                <small class="text-muted">Click the star icon on any game to add it to your favorites!</small>
            </div>
        </div>

        <!-- Standard Categories View -->
        <template v-else v-for="(category, key) in categories" :key="key">
            <div class="index_cat">
                <i class="fab fa-gripfire"></i>
                <span>{{ $t('general.sidebar.' + key) }}</span>
            </div>
            <div class="games">
                <div v-for="game in category" :key="game.id" class="game_poster"
                    @click="game.isDisabled ? null : $router.push(`/casino/game/${game.id}`)">
                    <div class="game_poster-image" :class="`game-${game.id}-image`">
                        <button :class="['btn-favorite', isFavorite(game.id) ? 'active' : '']"
                            @click.stop="toggleFavorite(game.id)"
                            :title="isFavorite(game.id) ? 'Remove from favorites' : 'Add to favorites'">
                            <i :class="isFavorite(game.id) ? 'fas fa-star' : 'far fa-star'"></i>
                        </button>
                        <div class="unavailable" v-if="game.isDisabled">
                            <div class="content">{{ $t(game.isPlaceholder ? 'general.coming_soon' : 'general.not_available') }}</div>
                        </div>
                    </div>
                    <div class="game_poster-name">
                        <icon :icon="game.icon" class="game_poster-icon"></icon>
                        <span>{{ game.name }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="games-empty" v-if="!hasGames && !onlyFavorites">
            <loader></loader>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../stores/app';
import { useModal } from '../composables/useModal';
import { whisper } from '../lib/whisper';

export default {
    props: {
        categories: { type: Object, default: () => ({}) },
        onlyFavorites: { type: Boolean, default: false }
    },
    data() {
        return {
            favorites: JSON.parse(localStorage.getItem('ratino_favorites') || '[]')
        };
    },
    computed: {
        store() {
            return useAppStore();
        },
        hasGames() {
            return this.categories && Object.keys(this.categories).length > 0;
        },
        favoriteGamesList() {
            const allGames = this.store.games || [];
            return allGames.filter((g) => g && this.favorites.includes(g.id));
        }
    },
    methods: {
        isFavorite(id) {
            return this.favorites.includes(id);
        },
        toggleFavorite(id) {
            if (!this.store.user) {
                useModal().open('Auth', { type: 'login' });
                this.$toast.info('Please sign in to favorite games.');
                return;
            }

            if (this.favorites.includes(id)) {
                this.favorites = this.favorites.filter((item) => item !== id);
                this.$toast.info('Removed from favorites');
            } else {
                this.favorites.push(id);
                this.$toast.success('Added to favorites!');
            }
            localStorage.setItem('ratino_favorites', JSON.stringify(this.favorites));

            whisper('Friend', { action: 'toggle_favorite', game: id }).then((res) => {
                if (res && res.favorites) {
                    this.favorites = res.favorites;
                    localStorage.setItem('ratino_favorites', JSON.stringify(this.favorites));
                }
            }).catch(() => {});
        },
        loadFavorites() {
            if (this.store.user) {
                if (this.store.user.favoriteGames && Array.isArray(this.store.user.favoriteGames)) {
                    this.favorites = this.store.user.favoriteGames;
                    localStorage.setItem('ratino_favorites', JSON.stringify(this.favorites));
                }
                whisper('Friend', { action: 'get_favorites' }).then((res) => {
                    if (res && res.favorites) {
                        this.favorites = res.favorites;
                        localStorage.setItem('ratino_favorites', JSON.stringify(this.favorites));
                    }
                }).catch(() => {});
            }
        }
    },
    mounted() {
        this.loadFavorites();
    },
    watch: {
        'store.user'(newUser) {
            if (newUser) this.loadFavorites();
            else this.favorites = [];
        }
    }
};
</script>

<style lang="scss">
@import '../sass/variables';

.games-grid {
    width: 100%;
    margin-bottom: 30px;
}

.index_cat {
    font-size: 1.3em;
    font-weight: 700;
    margin: 24px 0 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    letter-spacing: 0.02em;

    i {
        color: #e51f49;
        font-size: 1.15em;
    }

    &.favorites-heading i {
        color: #ffb703;
    }

    .badge-count {
        background: rgba(255, 183, 3, 0.2);
        color: #ffb703;
        font-size: 0.65em;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
    }
}

.games-empty-favorites {
    text-align: center;
    padding: 60px 20px;
    color: rgba(255, 255, 255, 0.5);

    i {
        font-size: 3em;
        color: rgba(255, 183, 3, 0.4);
    }
}

.games-empty {
    padding: 60px 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.games {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 18px;
    width: 100%;
}

.game_poster {
    width: 195px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: #1d1e24;
    border: 1px solid rgba(255, 255, 255, 0.07);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
    transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.25s ease, border-color 0.25s ease;
    user-select: none;
    position: relative;

    &:hover {
        transform: translateY(-4px) scale(1.02);
        border-color: rgba(229, 31, 73, 0.45);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5);

        .game_poster-name {
            color: #fff;
            .game_poster-icon { color: #e51f49; }
        }

        .btn-favorite {
            opacity: 1;
        }
    }

    .btn-favorite {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 10;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(4px);
        color: rgba(255, 255, 255, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85em;
        cursor: pointer;
        opacity: 0;
        transition: all 0.2s ease;

        &:hover {
            transform: scale(1.15);
            background: rgba(0, 0, 0, 0.8);
            color: #ffb703;
        }

        &.active {
            opacity: 1;
            color: #ffb703;
            background: rgba(0, 0, 0, 0.7);
            border-color: rgba(255, 183, 3, 0.4);
        }
    }

    .game_poster-image {
        height: 126px;
        background-size: cover;
        background-position: center;
        position: relative;
        background-color: #17181d;

        &.game-limbo-image { background-image: url('/img/game/limbo.svg'); }
        &.game-mines-image { background-image: url('/img/game/mines.svg'); }
        &.game-tower-image { background-image: url('/img/game/tower.svg'); }
        &.game-plinko-image { background-image: url('/img/game/plinko.svg'); }
        &.game-dice-image { background-image: url('/img/game/dice.svg'); }
        &.game-stairs-image { background-image: url('/img/game/stairs.svg'); }
        &.game-keno-image { background-image: url('/img/game/keno.svg'); }
        &.game-baccarat-image { background-image: url('/img/game/baccarat.png'); }
        &.game-crash-image { background-image: url('/img/game/crash.svg'); }
        &.game-videopoker-image { background-image: url('/img/game/videopoker.svg'); }
        &.game-slide-image { background-image: url('/img/game/slide.svg'); }
        &.game-slots-image { background-image: url('/img/game/slots.svg'); }
        &.game-wheel-image { background-image: url('/img/game/wheel.svg'); }
        &.game-hilo-image { background-image: url('/img/game/hilo.svg'); }
        &.game-blackjack-image { background-image: url('/img/game/blackjack.svg'); }
        &.game-roulette-image { background-image: url('/img/game/roulette.svg'); }
        &.game-coinflip-image { background-image: url('/img/game/coinflip.png'); }
        &.game-diamonds-image { background-image: url('/img/game/diamonds.svg'); }

        .unavailable {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .7);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85em;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.75);
        }
    }

    .game_poster-name {
        padding: 12px 14px;
        font-size: 0.95em;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.88);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.2s ease;

        .game_poster-icon {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.45);
            transition: color 0.2s ease;
        }
    }
}

@media(max-width: 576px) {
    .games { gap: 12px; }
    .game_poster { width: calc(50% - 10px); min-width: 140px; }
    .game_poster .btn-favorite { opacity: 1; }
}
</style>
