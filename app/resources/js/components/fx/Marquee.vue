<script setup lang="ts">
/** Sonsuz kayan yazı bandı. İçerik iki kez basılır, %50 kaydırılarak kesintisiz döngü sağlanır. */
withDefaults(defineProps<{ items: string[]; speed?: number; reverse?: boolean; separator?: string }>(), {
    speed: 40,
    reverse: false,
    separator: '✦',
});
</script>

<template>
    <div class="marquee overflow-hidden" :class="reverse && 'marquee-reverse'" :style="{ '--marquee-speed': `${speed}s` }">
        <div class="marquee-track flex w-max">
            <div v-for="n in 2" :key="n" class="flex shrink-0 items-center" :aria-hidden="n === 2">
                <template v-for="(item, i) in items" :key="`${n}-${i}`">
                    <span class="px-6 whitespace-nowrap sm:px-10"><slot :item="item">{{ item }}</slot></span>
                    <span class="text-gold" aria-hidden="true">{{ separator }}</span>
                </template>
            </div>
        </div>
    </div>
</template>
