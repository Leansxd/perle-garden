import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export interface QueueItem {
    name: string;
    progress: number;
    error: string | null;
    done: boolean;
}

/**
 * Dosyaları tek tek (sırayla) yükler — sunucunun istek boyutu sınırına takılmamak için.
 * Her dosya için ilerleme ve hata bilgisi tutar.
 */
export function useUploadQueue() {
    const queue = ref<QueueItem[]>([]);
    const busy = ref(false);

    async function upload(url: string, files: File[], extra: Record<string, string> = {}) {
        const items = files.map((f) => ({ name: f.name, progress: 0, error: null, done: false }) as QueueItem);
        queue.value.push(...items);
        busy.value = true;

        for (const [i, file] of files.entries()) {
            const item = queue.value[queue.value.length - files.length + i];
            await new Promise<void>((resolve) => {
                router.post(
                    url,
                    { ...extra, file },
                    {
                        forceFormData: true,
                        preserveScroll: true,
                        preserveState: true,
                        onProgress: (e) => (item.progress = Math.round(e?.percentage ?? 0)),
                        onError: (errors) => (item.error = Object.values(errors)[0] ?? 'Yükleme başarısız'),
                        onSuccess: () => (item.done = true),
                        onFinish: () => resolve(),
                    },
                );
            });
        }

        busy.value = false;
        // Başarılı olanları birkaç saniye sonra listeden kaldır
        setTimeout(() => (queue.value = queue.value.filter((q) => !q.done)), 2500);
    }

    return { queue, busy, upload };
}
