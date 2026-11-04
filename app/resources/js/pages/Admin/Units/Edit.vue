<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import TransField from '@/components/admin/TransField.vue';
import FileDrop from '@/components/admin/FileDrop.vue';
import MediaGrid, { type AdminMedia } from '@/components/admin/MediaGrid.vue';
import UploadQueue from '@/components/admin/UploadQueue.vue';
import { useUploadQueue } from '@/components/admin/useUploadQueue';
import type { Trans } from '@/types';

interface UnitForm {
    id: number;
    name: string;
    slug: string;
    unit_count: number;
    sizes: string;
    tour_url: string | null;
    video_url: string | null;
    is_published: boolean;
    label: Trans;
    description: Trans;
    rooms: Trans[];
    cover_image: string | null;
    model_3d: string | null;
}
type Kind = 'render' | 'photo' | 'plan';

const props = defineProps<{
    unit: UnitForm | null;
    media: (AdminMedia & { kind: Kind })[];
    publicUrl?: string;
}>();

const empty = (): Trans => ({ tr: '', en: '', ru: '' });
const isNew = computed(() => !props.unit);

const form = useForm({
    name: props.unit?.name ?? '',
    slug: props.unit?.slug ?? '',
    unit_count: props.unit?.unit_count ?? 0,
    sizes: props.unit?.sizes ?? '',
    tour_url: props.unit?.tour_url ?? '',
    video_url: props.unit?.video_url ?? '',
    is_published: props.unit?.is_published ?? true,
    label: props.unit?.label ?? empty(),
    description: props.unit?.description ?? empty(),
    rooms: props.unit?.rooms?.length ? props.unit.rooms : [empty()],
    cover_file: null as File | null,
    model_file: null as File | null,
    remove_cover: false,
    remove_model: false,
});

const coverPreview = ref<string | null>(props.unit?.cover_image ?? null);
function pickCover(files: File[]) {
    form.cover_file = files[0];
    form.remove_cover = false;
    coverPreview.value = URL.createObjectURL(files[0]);
}
function pickModel(files: File[]) {
    form.model_file = files[0];
    form.remove_model = false;
}

function submit() {
    const opts = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.cover_file = null;
            form.model_file = null;
        },
    };
    if (isNew.value) form.post('/admin/daireler', opts);
    else form.transform((d) => ({ ...d, _method: 'put' })).post(`/admin/daireler/${props.unit!.slug}`, opts);
}

/* Medya grupları */
const groups: { kind: Kind; title: string; text: string }[] = [
    { kind: 'render', title: '3D görseller', text: 'İç mekân render görselleri. İlki daire sayfasının kapak görseli olur (ayrı kapak seçilmediyse).' },
    { kind: 'photo', title: 'Fotoğraflar', text: 'Gerçek çekim iç mekân / örnek daire fotoğrafları.' },
    { kind: 'plan', title: 'Kat planları', text: 'Birden fazla plan eklerseniz sayfada sekme olarak görünür (ör. alt kat / üst kat). Açıklamaya plan adını yazın.' },
];
const byKind = (k: Kind) => props.media.filter((m) => m.kind === k);
const { queue, busy, upload } = useUploadQueue();
const uploadKind = (kind: Kind, files: File[]) => upload(`/admin/daireler/${props.unit!.slug}/medya`, files, { kind });
</script>

<template>
    <Head :title="isNew ? 'Yeni daire tipi' : `${unit?.name} ${unit?.label.tr ?? ''}`" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <Link href="/admin/daireler" class="text-[13px] text-muted hover:text-gold-dark">← Daire tipleri</Link>
            <h1 class="mt-1 font-serif text-4xl">{{ isNew ? 'Yeni daire tipi' : `${unit?.name} ${unit?.label.tr ?? ''}` }}</h1>
        </div>
        <a v-if="publicUrl" :href="publicUrl" target="_blank" class="adm-btn adm-btn-ghost"><Icon name="arrow-up-right" :size="14" /> Sayfayı görüntüle</a>
    </div>

    <form class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]" @submit.prevent="submit">
        <div class="space-y-6">
            <section class="adm-card space-y-5">
                <h2 class="font-medium">Temel bilgiler</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block">
                        <span class="adm-label">Oda düzeni *</span>
                        <input v-model="form.name" class="adm-input" placeholder="2+1" required />
                        <span v-if="form.errors.name" class="adm-error">{{ form.errors.name }}</span>
                    </label>
                    <label class="block">
                        <span class="adm-label">Daire adedi *</span>
                        <input v-model.number="form.unit_count" type="number" min="0" class="adm-input" required />
                        <span v-if="form.errors.unit_count" class="adm-error">{{ form.errors.unit_count }}</span>
                    </label>
                    <label class="block">
                        <span class="adm-label">Sayfa adresi</span>
                        <input v-model="form.slug" class="adm-input" placeholder="otomatik" />
                        <span class="adm-hint">/daire/{{ form.slug || '…' }}</span>
                        <span v-if="form.errors.slug" class="adm-error">{{ form.errors.slug }}</span>
                    </label>
                </div>
                <label class="block">
                    <span class="adm-label">Metrekareler *</span>
                    <input v-model="form.sizes" class="adm-input" placeholder="86 / 91 / 93 / 96 m²" required />
                    <span v-if="form.errors.sizes" class="adm-error">{{ form.errors.sizes }}</span>
                </label>
                <TransField v-model="form.label" label="Etiket" hint="Ör. Dubleks, Bahçe Dubleks — boş bırakılabilir" :error="form.errors['label.tr']" />
                <TransField v-model="form.description" label="Açıklama" textarea :rows="4" :error="form.errors['description.tr']" />
            </section>

            <section class="adm-card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-medium">Oda listesi</h2>
                    <button type="button" class="adm-btn adm-btn-ghost px-3 py-1.5 text-[12px]" @click="form.rooms.push(empty())">+ Satır ekle</button>
                </div>
                <div class="space-y-4">
                    <div v-for="(r, i) in form.rooms" :key="i" class="flex items-start gap-2">
                        <TransField v-model="form.rooms[i]" class="flex-1" :label="`${i + 1}. satır`" />
                        <button type="button" class="adm-icon-btn mt-7" aria-label="Satırı sil" @click="form.rooms.splice(i, 1)"><Icon name="x" :size="14" /></button>
                    </div>
                </div>
            </section>

            <section class="adm-card space-y-4">
                <h2 class="font-medium">Sanal tur ve video</h2>
                <label class="block">
                    <span class="adm-label">360° sanal tur bağlantısı</span>
                    <input v-model="form.tour_url" type="url" class="adm-input" placeholder="https://my.matterport.com/show/?m=…" />
                    <span class="adm-hint">Matterport, Kuula, 3DVista vb. "embed / paylaş" bağlantısı</span>
                    <span v-if="form.errors.tour_url" class="adm-error">{{ form.errors.tour_url }}</span>
                </label>
                <label class="block">
                    <span class="adm-label">Video bağlantısı</span>
                    <input v-model="form.video_url" type="url" class="adm-input" placeholder="https://www.youtube.com/watch?v=…" />
                    <span v-if="form.errors.video_url" class="adm-error">{{ form.errors.video_url }}</span>
                </label>
            </section>
        </div>

        <div class="space-y-6">
            <section class="adm-card">
                <label class="flex items-center justify-between gap-3">
                    <span>
                        <span class="block font-medium">Sitede yayınla</span>
                        <span class="text-[13px] text-muted">Kapalıysa sitede görünmez</span>
                    </span>
                    <input v-model="form.is_published" type="checkbox" class="size-5 accent-gold" />
                </label>
                <button type="submit" class="adm-btn adm-btn-gold mt-5 w-full py-3" :disabled="form.processing">
                    {{ form.processing ? `Kaydediliyor… ${form.progress ? '%' + form.progress.percentage : ''}` : isNew ? 'Oluştur' : 'Kaydet' }}
                </button>
                <p v-if="Object.keys(form.errors).length" class="adm-error mt-2">Lütfen işaretli alanları kontrol edin.</p>
            </section>

            <section class="adm-card">
                <h2 class="mb-1 font-medium">Kapak görseli</h2>
                <p class="mb-4 text-[13px] text-muted">Daire kartında ve sayfanın üstünde görünür. Boşsa ilk 3D görsel kullanılır.</p>
                <div v-if="coverPreview && !form.remove_cover" class="relative mb-3 overflow-hidden rounded-xl">
                    <img :src="coverPreview" alt="" class="aspect-[4/3] w-full object-cover" />
                    <button type="button" class="adm-btn adm-btn-danger absolute top-2 right-2 px-2 py-1 text-[12px]" @click="form.remove_cover = true; form.cover_file = null; coverPreview = null">Kaldır</button>
                </div>
                <FileDrop compact :multiple="false" label="Kapak seç" @files="pickCover" />
                <span v-if="form.errors.cover_file" class="adm-error">{{ form.errors.cover_file }}</span>
            </section>

            <section class="adm-card">
                <h2 class="mb-1 font-medium">Etkileşimli 3D model</h2>
                <p class="mb-4 text-[13px] text-muted">.glb ya da .gltf dosyası — ziyaretçiler döndürüp yakınlaştırabilir, telefonda AR ile görebilir.</p>
                <div v-if="(unit?.model_3d && !form.remove_model) || form.model_file" class="mb-3 flex items-center justify-between rounded-lg bg-cream px-3 py-2 text-[13px]">
                    <span class="truncate">{{ form.model_file?.name ?? unit?.model_3d?.split('/').pop() }}</span>
                    <button type="button" class="text-red-600 hover:underline" @click="form.remove_model = true; form.model_file = null">Kaldır</button>
                </div>
                <FileDrop compact :multiple="false" accept=".glb,.gltf,model/gltf-binary" label="3D model seç" hint=".glb / .gltf · en fazla 100 MB" @files="pickModel" />
                <span v-if="form.errors.model_file" class="adm-error">{{ form.errors.model_file }}</span>
            </section>
        </div>
    </form>

    <!-- Medya: kayıttan sonra -->
    <div v-if="!isNew" class="mt-10 space-y-6">
        <section v-for="g in groups" :key="g.kind" class="adm-card">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="font-medium">{{ g.title }} <span class="text-muted">({{ byKind(g.kind).length }})</span></h2>
                    <p class="text-[13px] text-muted">{{ g.text }}</p>
                </div>
            </div>
            <FileDrop compact :label="`${g.title} yükle`" @files="(f) => uploadKind(g.kind, f)" />
            <UploadQueue :items="queue" />
            <div class="mt-5">
                <MediaGrid
                    :items="byKind(g.kind)"
                    :update-url="(id) => `/admin/medya/${id}`"
                    :delete-url="(id) => `/admin/medya/${id}`"
                    :reorder-url="`/admin/daireler/${unit!.slug}/medya/sirala`"
                    :empty-text="busy ? 'Yükleniyor…' : 'Henüz görsel yok.'"
                />
            </div>
        </section>
    </div>
    <p v-else class="mt-8 rounded-xl bg-gold/10 px-4 py-3 text-[14px] text-gold-deep">Daire tipini oluşturduktan sonra fotoğraf, 3D görsel ve kat planı yükleyebilirsiniz.</p>
</template>
