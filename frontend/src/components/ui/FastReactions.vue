<template>
    <div class="fast-reactions-container">
        <!-- Floating Emojis Overlay -->
        <div class="floating-emojis-layer">
            <div v-for="particle in particles" :key="particle.id"
                class="floating-emoji-item"
                :style="{ left: particle.x + '%', bottom: particle.bottom + 'px', opacity: particle.opacity, transform: `scale(${particle.scale}) rotate(${particle.rotation}deg)` }">
                <span class="particle-emoji">{{ particle.emoji }}</span>
                <span class="particle-author" v-if="particle.name">{{ particle.name }}</span>
            </div>
        </div>

        <!-- Reaction Button Trigger Bar -->
        <div class="reactions-dock" :class="{ 'dock-expanded': isHovered }">
            <div class="dock-toggle" @mouseenter="isHovered = true">
                <i class="fas fa-smile-beam"></i>
                <span class="dock-label">React</span>
            </div>
            <div class="dock-emojis">
                <button v-for="emoji in emojis" :key="emoji"
                    class="btn-emoji"
                    @click="sendReaction(emoji)">
                    {{ emoji }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';
import { useAppStore } from '../../stores/app';
import bus from '../../lib/bus';

export default {
    props: {
        channel: { type: String, default: 'live' }
    },
    setup() {
        return { store: useAppStore() };
    },
    data() {
        return {
            emojis: ['🔥', '🚀', '💎', '🤑', '👏', '💀', '🤯', '❤️'],
            particles: [],
            isHovered: false,
            animFrame: null,
            reactionCooldown: false
        };
    },
    methods: {
        sendReaction(emoji) {
            if (this.reactionCooldown) return;
            if (!this.store.user) return;
            this.reactionCooldown = true;
            setTimeout(() => this.reactionCooldown = false, 300);

            // Local spawn immediately for zero latency feel
            this.spawnParticle(emoji, this.store.user ? this.store.user.name : 'You');

            // Whisper broadcast to other players
            whisper('Friend', {
                action: 'react',
                emoji,
                channel: this.channel
            }).catch(() => {});
        },
        spawnParticle(emoji, name = null) {
            const id = Date.now() + Math.random();
            const x = 30 + Math.random() * 40; // centered drift between 30% and 70% width
            const particle = {
                id,
                emoji,
                name: name ? name.substring(0, 10) : null,
                x,
                bottom: 20,
                opacity: 1,
                scale: 0.7,
                rotation: (Math.random() - 0.5) * 20,
                speedY: 2.2 + Math.random() * 1.8,
                speedX: (Math.random() - 0.5) * 0.4
            };

            this.particles.push(particle);
            if (this.particles.length > 25) this.particles.shift();
        },
        animate() {
            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];
                p.bottom += p.speedY;
                p.x += p.speedX;
                if (p.scale < 1.2) p.scale += 0.02;

                if (p.bottom > 180) {
                    p.opacity -= 0.035;
                }

                if (p.opacity <= 0 || p.bottom > 380) {
                    this.particles.splice(i, 1);
                }
            }
            this.animFrame = requestAnimationFrame(this.animate);
        },
        onRemoteReaction(e) {
            if (!e || !e.emoji) return;
            if (e.channel && this.channel !== 'live' && e.channel !== this.channel) return;
            const author = e.user ? e.user.name : null;
            this.spawnParticle(e.emoji, author);
        }
    },
    mounted() {
        this.animate();
        bus.on('event:fastReaction', this.onRemoteReaction);
    },
    beforeUnmount() {
        if (this.animFrame) cancelAnimationFrame(this.animFrame);
        bus.off('event:fastReaction', this.onRemoteReaction);
    }
};
</script>

<style lang="scss">
.fast-reactions-container {
    position: relative;
    pointer-events: none;
    z-index: 1200;
}

.floating-emojis-layer {
    position: absolute;
    bottom: 50px;
    left: 0;
    right: 0;
    height: 350px;
    overflow: visible;
    pointer-events: none;
}

.floating-emoji-item {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
    transition: transform 0.05s linear;
    filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.4));

    .particle-emoji {
        font-size: 2.2em;
        line-height: 1;
    }

    .particle-author {
        font-size: 0.68em;
        font-weight: 700;
        background: rgba(18, 19, 25, 0.85);
        color: #fff;
        padding: 2px 6px;
        border-radius: 6px;
        margin-top: 2px;
        white-space: nowrap;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
}

.reactions-dock {
    pointer-events: auto;
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    align-items: center;
    background: rgba(22, 23, 30, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 30px;
    padding: 4px 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 1px rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(14px);
    transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
    z-index: 1800;

    .dock-toggle {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        color: #e51f49;
        font-size: 0.9em;
        font-weight: 700;
        cursor: pointer;
        user-select: none;

        i { font-size: 1.25em; }
        .dock-label { color: #fff; font-size: 0.85em; }
    }

    .dock-emojis {
        display: flex;
        align-items: center;
        gap: 4px;
        padding-left: 4px;
        border-left: 1px solid rgba(255, 255, 255, 0.1);

        .btn-emoji {
            background: transparent;
            border: none;
            font-size: 1.25em;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.15s ease;

            &:hover {
                transform: scale(1.3);
                background: rgba(255, 255, 255, 0.12);
            }

            &:active {
                transform: scale(0.95);
            }
        }
    }
}

@media(max-width: 768px) {
    .reactions-dock {
        right: 12px;
        bottom: 80px;
        padding: 3px 6px;

        .dock-toggle .dock-label { display: none; }
        .dock-emojis .btn-emoji { width: 28px; height: 28px; font-size: 1.1em; }
    }
}
</style>
