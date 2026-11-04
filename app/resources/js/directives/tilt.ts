import type { Directive } from 'vue';

/**
 * v-tilt — fareyle 3B eğilme + imleci takip eden ışık (CSS değişkenleri --mx / --my).
 * Dokunmatik cihazlarda ve azaltılmış hareket tercihinde devre dışıdır.
 */
type El = HTMLElement & { __tiltOff?: () => void };

export const tilt: Directive<El, number | undefined> = {
    mounted(el, binding) {
        if (!window.matchMedia('(pointer: fine)').matches || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const max = binding.value ?? 8;
        el.style.transformStyle = 'preserve-3d';
        el.style.transition = 'transform .5s cubic-bezier(.2,.7,.2,1)';

        const move = (e: PointerEvent) => {
            const r = el.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width;
            const py = (e.clientY - r.top) / r.height;
            el.style.setProperty('--mx', `${px * 100}%`);
            el.style.setProperty('--my', `${py * 100}%`);
            el.style.transition = 'transform .12s linear';
            el.style.transform = `perspective(900px) rotateX(${(0.5 - py) * max}deg) rotateY(${(px - 0.5) * max}deg) translateZ(0)`;
        };
        const leave = () => {
            el.style.transition = 'transform .6s cubic-bezier(.2,.7,.2,1)';
            el.style.transform = '';
        };
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerleave', leave);
        el.__tiltOff = () => {
            el.removeEventListener('pointermove', move);
            el.removeEventListener('pointerleave', leave);
        };
    },
    unmounted(el) {
        el.__tiltOff?.();
    },
};
