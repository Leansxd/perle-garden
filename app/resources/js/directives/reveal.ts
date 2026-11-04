import type { Directive } from 'vue';

/**
 * v-reveal — öğe görünür alana girdiğinde yumuşak "fade-up" animasyonu.
 * v-reveal="120" ile milisaniye cinsinden gecikme verilebilir.
 */
const observer =
    typeof window !== 'undefined' && 'IntersectionObserver' in window
        ? new IntersectionObserver(
              (entries) => {
                  for (const entry of entries) {
                      if (entry.isIntersecting) {
                          entry.target.classList.add('is-visible');
                          observer?.unobserve(entry.target);
                      }
                  }
              },
              { rootMargin: '0px 0px -12% 0px', threshold: 0.05 },
          )
        : null;

export const reveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        el.classList.add('reveal');
        if (binding.value) el.style.transitionDelay = `${binding.value}ms`;
        if (observer) observer.observe(el);
        else el.classList.add('is-visible');
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
