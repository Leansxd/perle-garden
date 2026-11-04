import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger, SplitText);

export const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let lenis: Lenis | null = null;

/** Lenis yumuşak kaydırma — GSAP ticker ile senkron. */
export function startSmoothScroll(): Lenis | null {
    if (prefersReducedMotion() || lenis) return lenis;
    lenis = new Lenis({ lerp: 0.09, anchors: { offset: -70 }, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis?.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
    return lenis;
}

export const getLenis = () => lenis;

export function lockScroll(lock: boolean) {
    if (lock) lenis?.stop();
    else lenis?.start();
    document.documentElement.classList.toggle('overflow-hidden', lock);
}

export function scrollToId(id: string) {
    const el = document.getElementById(id);
    if (!el) return;
    if (lenis) lenis.scrollTo(el, { offset: -70, duration: 1.4 });
    else el.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Sayfadaki data-* işaretli öğelere scroll animasyonlarını bağlar.
 *  data-split        → başlık satır satır maskeli yükselir
 *  data-clip         → görsel alttan yukarı perde gibi açılır (içteki görsel küçülerek oturur)
 *  data-parallax="n" → kaydırmayla n oranında dikey parallax
 *  data-stagger      → çocuk öğeler sırayla belirir
 *  data-line         → çizgi soldan sağa çizilir
 * Temizleme fonksiyonu döndürür.
 */
export function bindScrollAnimations(root: HTMLElement): () => void {
    if (prefersReducedMotion()) return () => {};

    const ctx = gsap.context(() => {
        root.querySelectorAll<HTMLElement>('[data-split]').forEach((el) => {
            const split = SplitText.create(el, { type: 'lines', mask: 'lines', linesClass: 'split-line' });
            gsap.from(split.lines, {
                yPercent: 110,
                duration: 1.15,
                ease: 'expo.out',
                stagger: 0.09,
                scrollTrigger: { trigger: el, start: 'top 88%' },
            });
        });

        root.querySelectorAll<HTMLElement>('[data-clip]').forEach((el) => {
            const inner = el.firstElementChild as HTMLElement | null;
            const tl = gsap.timeline({ scrollTrigger: { trigger: el, start: 'top 85%' } });
            tl.fromTo(el, { clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease: 'expo.inOut' });
            if (inner) tl.fromTo(inner, { scale: 1.3 }, { scale: 1, duration: 1.8, ease: 'expo.out' }, 0.1);
        });

        root.querySelectorAll<HTMLElement>('[data-parallax]').forEach((el) => {
            const speed = parseFloat(el.dataset.parallax || '0.15');
            gsap.fromTo(
                el,
                { yPercent: -speed * 50 },
                {
                    yPercent: speed * 50,
                    ease: 'none',
                    scrollTrigger: { trigger: el.parentElement ?? el, start: 'top bottom', end: 'bottom top', scrub: true },
                },
            );
        });

        root.querySelectorAll<HTMLElement>('[data-stagger]').forEach((el) => {
            gsap.from(el.children, {
                y: 36,
                autoAlpha: 0,
                duration: 0.9,
                ease: 'power3.out',
                stagger: 0.06,
                scrollTrigger: { trigger: el, start: 'top 85%' },
            });
        });

        // Kelime kelime aydınlanan büyük metin (kaydırmaya bağlı)
        root.querySelectorAll<HTMLElement>('[data-scrub-words]').forEach((el) => {
            const split = SplitText.create(el, { type: 'words' });
            gsap.fromTo(
                split.words,
                { opacity: 0.12 },
                {
                    opacity: 1,
                    ease: 'none',
                    stagger: 0.1,
                    scrollTrigger: { trigger: el, start: 'top 82%', end: 'bottom 45%', scrub: 0.6 },
                },
            );
        });

        // Kaydırdıkça kenarlardan açılıp tam ekrana yayılan görsel
        root.querySelectorAll<HTMLElement>('[data-expand]').forEach((el) => {
            const media = el.querySelector<HTMLElement>('[data-expand-media]');
            const text = el.querySelector<HTMLElement>('[data-expand-text]');
            const tl = gsap.timeline({
                scrollTrigger: { trigger: el, start: 'top 85%', end: 'center center', scrub: 0.8 },
            });
            tl.fromTo(
                el.querySelector('[data-expand-frame]'),
                { clipPath: 'inset(14% 12% 14% 12% round 28px)' },
                { clipPath: 'inset(0% 0% 0% 0% round 0px)', ease: 'none' },
                0,
            );
            if (media) tl.fromTo(media, { scale: 1.25 }, { scale: 1, ease: 'none' }, 0);
            if (text) {
                gsap.from(text.children, {
                    y: 50,
                    autoAlpha: 0,
                    duration: 1.2,
                    ease: 'expo.out',
                    stagger: 0.12,
                    scrollTrigger: { trigger: el, start: 'center 70%' },
                });
            }
        });

        // Yatay kayan büyük yazı (kaydırma yönüne bağlı)
        root.querySelectorAll<HTMLElement>('[data-scroll-x]').forEach((el) => {
            const amount = parseFloat(el.dataset.scrollX || '-20');
            gsap.fromTo(
                el,
                { xPercent: 0 },
                { xPercent: amount, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true } },
            );
        });

        // Ölçek + saydamlık ile beliren kartlar
        root.querySelectorAll<HTMLElement>('[data-pop]').forEach((el) => {
            gsap.from(el.children, {
                y: 60,
                scale: 0.94,
                autoAlpha: 0,
                duration: 1.1,
                ease: 'expo.out',
                stagger: 0.08,
                scrollTrigger: { trigger: el, start: 'top 85%' },
            });
        });

        root.querySelectorAll<HTMLElement>('[data-line]').forEach((el) => {
            gsap.from(el, { scaleX: 0, transformOrigin: 'left center', duration: 1.2, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 90%' } });
        });

        const bar = document.getElementById('scroll-progress');
        if (bar) {
            gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: 0.3 } });
        }
    }, root);

    // Fontlar/görseller yüklenince ölçüleri yenile
    const refresh = () => ScrollTrigger.refresh();
    window.addEventListener('load', refresh);

    return () => {
        window.removeEventListener('load', refresh);
        ctx.revert();
    };
}

/** Masaüstünde fareye doğru hafifçe çekilen "mıknatıslı" öğeler: [data-magnetic] */
export function bindMagnetic(root: HTMLElement): () => void {
    if (prefersReducedMotion() || !window.matchMedia('(pointer: fine)').matches) return () => {};
    const els = [...root.querySelectorAll<HTMLElement>('[data-magnetic]')];
    const handlers = els.map((el) => {
        const xTo = gsap.quickTo(el, 'x', { duration: 0.6, ease: 'power3' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.6, ease: 'power3' });
        const move = (e: PointerEvent) => {
            const r = el.getBoundingClientRect();
            xTo((e.clientX - (r.left + r.width / 2)) * 0.3);
            yTo((e.clientY - (r.top + r.height / 2)) * 0.4);
        };
        const leave = () => {
            xTo(0);
            yTo(0);
        };
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerleave', leave);
        return () => {
            el.removeEventListener('pointermove', move);
            el.removeEventListener('pointerleave', leave);
        };
    });
    return () => handlers.forEach((off) => off());
}

export { gsap, ScrollTrigger, SplitText };
