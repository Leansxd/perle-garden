<script setup lang="ts">
import type { QueueItem } from './useUploadQueue';

defineProps<{ items: QueueItem[] }>();
</script>

<template>
    <ul v-if="items.length" class="mt-3 space-y-2">
        <li v-for="(q, i) in items" :key="i" class="rounded-lg border border-line bg-white px-3 py-2 text-[13px]">
            <div class="flex items-center justify-between gap-3">
                <span class="truncate">{{ q.name }}</span>
                <span :class="q.error ? 'text-red-600' : q.done ? 'text-green-700' : 'text-muted'">
                    {{ q.error ? 'Hata' : q.done ? 'Yüklendi' : `%${q.progress}` }}
                </span>
            </div>
            <div class="mt-1.5 h-1 overflow-hidden rounded bg-cream">
                <div class="h-full transition-[width]" :class="q.error ? 'bg-red-400' : 'bg-gold'" :style="{ width: `${q.error ? 100 : q.progress}%` }"></div>
            </div>
            <p v-if="q.error" class="mt-1 text-[12px] text-red-600">{{ q.error }}</p>
        </li>
    </ul>
</template>
