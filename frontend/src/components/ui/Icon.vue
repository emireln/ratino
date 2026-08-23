<template>
    <i :class="resolvedClass" v-if="resolvedClass"></i>
    <span v-else-if="icon" v-html="icon"></span>
</template>

<script>
const iconMap = {
    'wheel': 'fas fa-dharmachakra',
    'keno': 'fas fa-th',
    'stairs': 'fas fa-stream',
    'blackjack': 'fas fa-club',
    'roulette': 'fas fa-circle-notch',
    'crash': 'fas fa-chart-line',
    'hilo': 'fas fa-sort-amount-up',
    'baccarat': 'fas fa-heart',
    'dice': 'fad fa-dice-d20',
    'mines': 'fas fa-bomb',
    'plinko': 'fas fa-bullseye',
    'coinflip': 'fas fa-coins',
    'tower': 'fad fa-gopuram',
    'diamonds': 'fad fa-gem',
    'limbo': 'fas fa-rocket',
    'slide': 'fas fa-sliders-h',
    'videopoker': 'fas fa-spade',
    'slots': 'fab fa-raspberry-pi'
};

export default {
    props: ['icon'],
    computed: {
        resolvedClass() {
            if (!this.icon) return '';
            if (this.icon.startsWith('fa')) return this.icon;
            if (iconMap[this.icon.toLowerCase()]) return iconMap[this.icon.toLowerCase()];
            return '';
        }
    }
};
</script>
