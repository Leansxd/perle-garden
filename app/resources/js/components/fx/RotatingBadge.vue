<script setup lang="ts">
/** Dairesel yol üzerinde dönen yazı + ortada vurgu (örn. "100 m"). */
withDefaults(defineProps<{ text: string; center: string; sub?: string }>(), { sub: '' });
const id = `circ-${Math.random().toString(36).slice(2, 8)}`;
</script>

<template>
    <div class="relative grid size-36 place-items-center rounded-full bg-night text-pearl shadow-[0_20px_50px_rgba(0,0,0,.25)] sm:size-40">
        <svg viewBox="0 0 200 200" class="animate-spin-slow absolute inset-0 size-full" aria-hidden="true">
            <defs><path :id="id" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0" /></defs>
            <text class="fill-gold-light text-[13px] font-medium tracking-[0.42em] uppercase">
                <textPath :href="`#${id}`">{{ text }}</textPath>
            </text>
        </svg>
        <div class="text-center">
            <p class="font-serif text-4xl leading-none font-medium text-gold-light">{{ center }}</p>
            <p v-if="sub" class="mt-1 text-[10px] tracking-[0.25em] text-pearl/60 uppercase">{{ sub }}</p>
        </div>
    </div>
</template>
