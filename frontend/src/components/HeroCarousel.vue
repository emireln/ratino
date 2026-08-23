<template>
    <div class="hero-carousel" v-if="visibleSlides.length">
        <div class="hero-track" :style="{ transform: `translateX(-${index * 100}%)` }">
            <div v-for="(slide, i) in visibleSlides" :key="slide" class="hero-slide">
                <img :src="slide" :alt="'slide ' + (i + 1)" @error="onError(slide)">
            </div>
        </div>

        <div class="hero-arrow hero-arrow-left" @click="prev" v-if="visibleSlides.length > 1" aria-label="Previous Slide">
            <i class="fas fa-chevron-left"></i>
        </div>
        <div class="hero-arrow hero-arrow-right" @click="next" v-if="visibleSlides.length > 1" aria-label="Next Slide">
            <i class="fas fa-chevron-right"></i>
        </div>

        <div class="hero-dots" v-if="visibleSlides.length > 1">
            <div v-for="(slide, i) in visibleSlides" :key="slide"
                :class="['hero-dot', i === index ? 'active' : '']"
                @click="index = i"></div>
        </div>
    </div>
</template>

<script>
const SLIDES = [
    '/img/misc/slider/slider1.png',
    '/img/misc/slider/slider2.png'
];

export default {
    data() {
        return {
            index: 0,
            visibleSlides: SLIDES.slice(),
            timer: null
        };
    },
    methods: {
        next() {
            if (!this.visibleSlides.length) return;
            this.index = (this.index + 1) % this.visibleSlides.length;
        },
        prev() {
            if (!this.visibleSlides.length) return;
            this.index = (this.index - 1 + this.visibleSlides.length) % this.visibleSlides.length;
        },
        onError(slide) {
            this.visibleSlides = this.visibleSlides.filter((s) => s !== slide);
            if (this.index >= this.visibleSlides.length && this.visibleSlides.length) this.index = 0;
        },
        restart() {
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (this.visibleSlides.length > 1) this.next();
            }, 5500);
        }
    },
    mounted() {
        this.restart();
    },
    beforeUnmount() {
        clearInterval(this.timer);
    }
};
</script>

<style lang="scss">
.hero-carousel {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    margin: 0 auto 30px;
    width: 100%;
    max-width: 1200px;
    aspect-ratio: 1640 / 401;
    min-height: 180px;
    max-height: 290px;
    background: rgba(255, 255, 255, .03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    user-select: none;

    .hero-track {
        display: flex;
        height: 100%;
        width: 100%;
        transition: transform .6s cubic-bezier(.25, .8, .25, 1);
    }

    .hero-slide {
        min-width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    .hero-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(18, 19, 24, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        color: #fff;
        cursor: pointer;
        opacity: 0;
        transition: all .25s ease;
        z-index: 5;

        &:hover {
            background: rgba(229, 31, 73, 0.9);
            border-color: #e51f49;
            transform: translateY(-50%) scale(1.08);
        }

        &.hero-arrow-left { left: 16px; }
        &.hero-arrow-right { right: 16px; }
    }

    &:hover .hero-arrow { opacity: 1; }

    .hero-dots {
        position: absolute;
        bottom: 14px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(6px);
        z-index: 5;

        .hero-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .35);
            cursor: pointer;
            transition: all .3s ease;

            &.active {
                background: #e51f49;
                width: 20px;
                border-radius: 4px;
            }
        }
    }
}

@media(max-width: 768px) {
    .hero-carousel {
        border-radius: 10px;
        margin-bottom: 20px;
        min-height: 120px;
    }
}
</style>