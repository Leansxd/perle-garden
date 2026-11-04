<script setup lang="ts">
import { onMounted, ref } from 'vue';
import BrandLogo from '../BrandLogo.vue';
import { gsap, prefersReducedMotion } from '@/motion/motion';

/** Açılış perdesi: logo maskeyle belirir + altın çizgi dolar, ardından perde yukarı kayar. */
defineProps<{ name: string }>();
const emit = defineEmits<{ done: [] }>();

const root = ref<HTMLElement | null>(null);
const count = ref(0);
const visible = ref(true);

onMounted(() => {
    document.documentElement.classList.add('overflow-hidden');
    const finish = () => {
        document.documentElement.classList.remove('overflow-hidden');
        visible.value = false;
        emit('done');
    };
    if (prefersReducedMotion() || !root.value) return finish();

    // Aynı oturumda (ör. dil değişimi) ikinci açılışta perde çok daha hızlı geçer
    let seen = false;
    try {
        seen = sessionStorage.getItem('pg-intro') === '1';
        sessionStorage.setItem('pg-intro', '1');
    } catch {
        /* gizli mod */
    }

    const counter = { v: 0 };
    const tl = gsap.timeline({ onComplete: finish });
    if (seen) tl.timeScale(3);
    tl
        .fromTo('.pl-logo', { clipPath: 'inset(0 100% 0 0)', y: 10 }, { clipPath: 'inset(0 0% 0 0)', y: 0, duration: 1.4, ease: 'expo.inOut' }, 0)
        .to(counter, { v: 100, duration: 1.6, ease: 'power2.inOut', onUpdate: () => (count.value = Math.round(counter.v)) }, 0)
        .fromTo('.pl-bar', { scaleX: 0 }, { scaleX: 1, duration: 1.6, ease: 'power2.inOut' }, 0)
        .to('.pl-inner', { autoAlpha: 0, y: -30, duration: 0.5, ease: 'power2.in' }, '+=0.2')
        .to(root.value, { yPercent: -100, duration: 1.1, ease: 'expo.inOut' }, '-=0.1');
});
</script>

<template>
    <div v-if="visible" ref="root" class="fixed inset-0 z-[200] grid place-items-center bg-night" aria-hidden="true">
        <div class="pl-inner flex w-[min(380px,72vw)] flex-col items-center">
            <BrandLogo class="pl-logo w-full" :alt="name" />
            <div class="mt-10 h-px w-full bg-pearl/10">
                <div class="pl-bar h-full origin-left bg-linear-to-r from-gold-dark to-gold-light"></div>
            </div>
            <p class="mt-4 self-end font-serif text-sm text-pearl/50 tabular-nums">{{ count }}%</p>
        </div>
    </div>
</template>
