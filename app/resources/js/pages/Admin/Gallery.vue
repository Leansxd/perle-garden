<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FileDrop from '@/components/admin/FileDrop.vue';
import MediaGrid, { type AdminMedia } from '@/components/admin/MediaGrid.vue';
import UploadQueue from '@/components/admin/UploadQueue.vue';
import { useUploadQueue } from '@/components/admin/useUploadQueue';

defineProps<{ images: AdminMedia[] }>();
const { queue, upload } = useUploadQueue();
</script>

<template>
    <Head title="Galeri" />
    <h1 class="font-serif text-4xl">Galeri</h1>
    <p class="mt-1 text-muted">Ana sayfadaki yatay kayan galeri ve ışık kutusu. Sıralamayı oklarla değiştirebilirsiniz; ilk görsel en başta görünür.</p>

    <section class="adm-card mt-8">
        <FileDrop @files="(f) => upload('/admin/galeri', f)" />
        <UploadQueue :items="queue" />
        <div class="mt-6">
            <MediaGrid
                :items="images"
                :update-url="(id) => `/admin/galeri/${id}`"
                :delete-url="(id) => `/admin/galeri/${id}`"
                reorder-url="/admin/galeri/sirala"
            />
        </div>
    </section>
</template>
