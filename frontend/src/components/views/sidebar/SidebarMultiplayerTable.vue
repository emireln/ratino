<template>
    <div class="multiplayer-table">
        <div v-for="(player, i) in players" :key="i" class="mp-player">
            <img :src="player.user.avatar" alt>
            <span class="mp-name">{{ player.user.name }}</span>
            <span class="mp-bet">{{ player.game.wager }}</span>
        </div>
        <div class="mp-empty" v-if="!players.length">No players yet</div>
    </div>
</template>

<script>
import bus from '../../../lib/bus';

export default {
    data() {
        return { players: [] };
    },
    mounted() {
        this.add = bus.on('sidebar:multiplayer:add', (e) => {
            if (!this.players.find((p) => p.user._id === e.user._id)) this.players.push(e);
        });
        this.clear = bus.on('sidebar:multiplayer:clear', () => this.players = []);
    },
    beforeUnmount() {
        if (typeof this.add === 'function') this.add();
        if (typeof this.clear === 'function') this.clear();
    }
};
</script>

<style lang="scss" scoped>
.multiplayer-table {
    display: flex;
    flex-direction: column;
    gap: 6px;

    .mp-player {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: .85em;
        background: rgba(255, 255, 255, .04);

        img { width: 22px; height: 22px; border-radius: 50%; }

        .mp-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mp-bet { font-weight: 600; }
    }

    .mp-empty { text-align: center; opacity: .4; font-size: .85em; padding: 10px; }
}
</style>
