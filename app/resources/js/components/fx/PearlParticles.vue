<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { prefersReducedMotion } from '@/motion/motion';

/**
 * Hero üzerinde süzülen inci/altın ışık parçacıkları (Canvas 2D).
 * Fareye hafif parallax ile tepki verir; sekme gizliyken durur.
 */
const props = withDefaults(defineProps<{ count?: number }>(), { count: 55 });
const canvas = ref<HTMLCanvasElement | null>(null);
let raf = 0;
let cleanup: (() => void) | null = null;

interface P { x: number; y: number; r: number; vy: number; vx: number; a: number; tw: number; depth: number; gold: boolean }

onMounted(() => {
    const c = canvas.value;
    if (!c || prefersReducedMotion()) return;
    const ctx = c.getContext('2d');
    if (!ctx) return;

    let w = 0, h = 0, dpr = 1;
    let mx = 0, my = 0, tx = 0, ty = 0;
    const count = window.innerWidth < 640 ? Math.round(props.count * 0.5) : props.count;
    let parts: P[] = [];

    const resize = () => {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = c.clientWidth;
        h = c.clientHeight;
        c.width = w * dpr;
        c.height = h * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    const spawn = (initial: boolean): P => {
        const depth = Math.random();
        return {
            x: Math.random() * w,
            y: initial ? Math.random() * h : h + 20,
            r: 0.6 + depth * 2.6,
            vy: 0.12 + depth * 0.45,
            vx: (Math.random() - 0.5) * 0.15,
            a: 0.15 + depth * 0.55,
            tw: Math.random() * Math.PI * 2,
            depth,
            gold: Math.random() > 0.35,
        };
    };

    resize();

    const draw = () => {
        // Yerleşim henüz hesaplanmadıysa (genişlik 0) parçacıkları sonra üret
        if (!parts.length) {
            if (!w) resize();
            if (w) parts = Array.from({ length: count }, () => spawn(true));
        }
        tx += (mx - tx) * 0.04;
        ty += (my - ty) * 0.04;
        ctx.clearRect(0, 0, w, h);
        for (const p of parts) {
            p.y -= p.vy;
            p.x += p.vx;
            p.tw += 0.02;
            if (p.y < -20) Object.assign(p, spawn(false));
            const ox = tx * p.depth * 24;
            const oy = ty * p.depth * 16;
            const alpha = p.a * (0.65 + Math.sin(p.tw) * 0.35);
            const g = ctx.createRadialGradient(p.x + ox, p.y + oy, 0, p.x + ox, p.y + oy, p.r * 4);
            const col = p.gold ? '232,200,150' : '255,250,240';
            g.addColorStop(0, `rgba(${col},${alpha})`);
            g.addColorStop(1, `rgba(${col},0)`);
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(p.x + ox, p.y + oy, p.r * 4, 0, Math.PI * 2);
            ctx.fill();
        }
        raf = requestAnimationFrame(draw);
    };

    const onMove = (e: PointerEvent) => {
        mx = e.clientX / window.innerWidth - 0.5;
        my = e.clientY / window.innerHeight - 0.5;
    };
    const onVis = () => {
        cancelAnimationFrame(raf);
        if (!document.hidden) raf = requestAnimationFrame(draw);
    };

    window.addEventListener('resize', resize);
    window.addEventListener('pointermove', onMove);
    document.addEventListener('visibilitychange', onVis);
    raf = requestAnimationFrame(draw);

    cleanup = () => {
        cancelAnimationFrame(raf);
        window.removeEventListener('resize', resize);
        window.removeEventListener('pointermove', onMove);
        document.removeEventListener('visibilitychange', onVis);
    };
});
onBeforeUnmount(() => cleanup?.());
</script>

<template>
    <canvas ref="canvas" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true"></canvas>
</template>
