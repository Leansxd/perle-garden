<script setup lang="ts">
import { computed, nextTick, reactive, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import Model3D, { type ModelHotspot } from '@/components/Model3D.vue';
import { useI18n } from '@/composables/useI18n';
import config from '@/data/showcase.json';
import { gsap, prefersReducedMotion, scrollToId } from '@/motion/motion';
import type { MediaItem, UnitDetail } from '@/types';

/**
 * Üç aşamalı vitrin: binadan kat seçimi → 3D kat planı → oda fotoğrafı.
 * Katlar görsel üzerinde cephe köşelerinden hesaplanır (`data/showcase.json` → building.facades).
 * 3D model imleç bir kata gelince arkada hazırlanır: geçişte yükleme takılması olmaz, geri dönüşte yeniden indirilmez.
 */
type View = 'building' | 'floorplan' | 'room';
type Entry = (typeof config.units)[number];
type ShowcaseUnit = UnitDetail & Entry;
type Point = [number, number];
interface Floor {
    key: string;
    number: number;
    unit: ShowcaseUnit;
    corners: Point[];
    points: string;
    center: Point;
    focusable: boolean;
}

const props = defineProps<{ units: UnitDetail[] }>();

const { t, localePath } = useI18n();

const { width, height } = config.building;
const zoomScale = 5;
const leader = 56;
const cardWidth = 300;

const units = computed(() =>
    (config.units as Entry[]).flatMap((entry) => {
        const unit = props.units.find((u) => u.slug === entry.slug);
        return unit ? [{ ...unit, ...entry } as ShowcaseUnit] : [];
    }),
);

/* Cephe dört köşesinden kat şeritlerine bölünür; 1. kat en altta */
const floors = computed<Floor[]>(() =>
    config.building.facades.flatMap((facade) => {
        const unit = units.value.find((u) => u.slug === facade.unit);
        if (!unit) return [];
        const [tl, tr, br, bl] = facade.corners as Point[];
        const lerp = (a: Point, b: Point, k: number): Point => [a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k];
        return Array.from({ length: facade.floors }, (_, i) => {
            const top = i / facade.floors;
            const bottom = (i + 1) / facade.floors;
            const corners = [lerp(tl, bl, top), lerp(tr, br, top), lerp(tr, br, bottom), lerp(tl, bl, bottom)];
            return {
                key: `${facade.id}-${i}`,
                number: facade.floors - i,
                unit,
                corners,
                points: corners.map((c) => c.join(',')).join(' '),
                center: [corners.reduce((n, c) => n + c[0], 0) / 4, corners.reduce((n, c) => n + c[1], 0) / 4] as Point,
                focusable: i === 0,
            };
        });
    }),
);

const state = reactive<{
    currentView: View;
    selectedUnit: ShowcaseUnit | null;
    selectedFloor: Floor | null;
    selectedRoom: MediaItem | null;
}>({
    currentView: 'building',
    selectedUnit: null,
    selectedFloor: null,
    selectedRoom: null,
});

const stage = ref<HTMLElement | null>(null);
const frame = ref<HTMLElement | null>(null);
const model = ref<InstanceType<typeof Model3D> | null>(null);
const hovered = ref<Floor | null>(null);
const planUnit = ref<ShowcaseUnit | null>(null);
const anchor = ref<{ x: number; y: number; toLeft: boolean } | null>(null);
const fits = ref<Record<number, 'cover' | 'contain'>>({});
const animating = ref(false);
const roomOrigin = ref('50% 50%');
const roomDirection = ref(0);
const lookLayer = ref<HTMLElement | null>(null);
let lookAround: gsap.core.Tween | undefined;

const unitTitle = (u: UnitDetail) => (u.label ? `${u.name} ${u.label}` : u.name);
const floorOrigin = (f: Floor) => `${(f.center[0] / width) * 100}% ${(f.center[1] / height) * 100}%`;
const duration = (seconds: number) => (prefersReducedMotion() ? 0 : seconds);
const renderOf = (u: UnitDetail, key: string) => u.renders.find((r) => r.url.endsWith(`-${key}.png`));

/* Kartı katın yanına çiz: sağda yer yoksa sola geçer; dar ekranda sayfanın altına oturur */
function showFloor(floor: Floor) {
    hovered.value = floor;
    planUnit.value = floor.unit;
    const box = stage.value?.getBoundingClientRect();
    const f = frame.value?.getBoundingClientRect();
    if (!box || !f || !window.matchMedia('(min-width: 1024px)').matches) {
        anchor.value = null;
        return;
    }
    const scale = f.width / width;
    const xs = floor.corners.map((c) => f.left - box.left + c[0] * scale);
    const toLeft = Math.max(...xs) + leader + cardWidth + 24 > box.width;
    anchor.value = {
        x: toLeft ? Math.min(...xs) : Math.max(...xs),
        y: f.top - box.top + floor.center[1] * scale,
        toLeft,
    };
}

function onFloorEnter(floor: Floor, e: PointerEvent) {
    if (e.pointerType === 'mouse') showFloor(floor);
}

/* Fare: tıklayınca açılır. Dokunmatik: ilk dokunuş kartı gösterir, karttaki düğme açar. */
function onFloorClick(floor: Floor, e: MouseEvent) {
    if ((e as PointerEvent).pointerType === 'touch') showFloor(floor);
    else selectFloor(floor);
}

function selectFloor(floor: Floor) {
    if (animating.value) return;
    planUnit.value = floor.unit;
    state.selectedUnit = floor.unit;
    state.selectedFloor = floor;
    hovered.value = null;
    animating.value = true;
    gsap.to(frame.value, {
        scale: zoomScale,
        filter: 'blur(4px)',
        transformOrigin: floorOrigin(floor),
        duration: duration(1),
        ease: 'power3.in',
        onComplete: () => {
            planIntro();
            state.currentView = 'floorplan';
            animating.value = false;
        },
    });
}

/* Plana geçiş: model aşağıdan gelir, kart sağdan kayar */
function planIntro() {
    const el = stage.value;
    gsap.fromTo(
        el?.querySelector('[data-plan-model]') ?? null,
        { y: 120, scale: 0.86 },
        {
            y: 0,
            scale: 1,
            duration: duration(1.2),
            ease: 'power3.out',
            clearProps: 'transform',
        },
    );
    gsap.fromTo(
        el?.querySelector('[data-plan-card]') ?? null,
        { x: 70, autoAlpha: 0 },
        {
            x: 0,
            autoAlpha: 1,
            delay: duration(0.5),
            duration: duration(0.8),
            ease: 'power3.out',
            clearProps: 'transform',
        },
    );
}

/* Oda noktaları: JSON'daki konum + o odanın render'ı; render'ı olmayan nokta çizilmez */
const hotspots = computed<ModelHotspot[]>(() => {
    const unit = planUnit.value;
    if (!unit) return [];
    return unit.roomPoints.flatMap((room) => {
        const render = renderOf(unit, room.render);
        return render
            ? [
                  {
                      id: room.render,
                      label: render.caption ?? unitTitle(unit),
                      position: room.position as ModelHotspot['position'],
                  },
              ]
            : [];
    });
});

async function enterRoom(id: string, e?: MouseEvent) {
    const unit = planUnit.value;
    const hotspot = hotspots.value.find((h) => h.id === id);
    const render = unit && renderOf(unit, id);
    if (!render || !hotspot || animating.value) return;

    const box = stage.value?.getBoundingClientRect();
    roomOrigin.value = e && box ? `${e.clientX - box.left}px ${e.clientY - box.top}px` : '50% 50%';
    animating.value = true;
    await model.value?.flyTo(hotspot.position);
    roomDirection.value = 0;
    state.selectedRoom = render;
    state.currentView = 'room';
    animating.value = false;
}

function backToPlan() {
    model.value?.resetCamera();
    state.currentView = 'floorplan';
}

/* Geniş ekrana yatay, dar ekrana dikey görsel tam oturur; ters orandaki görsel kırpılmaz */
function setFit(room: MediaItem, e: Event) {
    const img = e.target as HTMLImageElement;
    const sameOrientation = img.naturalWidth >= img.naturalHeight === window.innerWidth >= window.innerHeight;
    fits.value[room.id] = sameOrientation ? 'cover' : 'contain';
}

function fadeOut(el: Element, done: () => void) {
    gsap.to(el, {
        autoAlpha: 0,
        duration: duration(0.5),
        ease: 'power2.in',
        onComplete: done,
    });
}

/* Binaya dönüş yakınlaşmanın tersi */
function buildingIn(el: Element, done: () => void) {
    gsap.fromTo(
        el,
        { autoAlpha: 0 },
        {
            autoAlpha: 1,
            duration: duration(0.6),
            ease: 'power2.out',
            onComplete: done,
        },
    );
    const floor = state.selectedFloor;
    if (!floor) return;
    nextTick(() => {
        gsap.fromTo(
            frame.value,
            {
                scale: zoomScale,
                filter: 'blur(4px)',
                transformOrigin: floorOrigin(floor),
            },
            {
                scale: 1,
                filter: 'blur(0px)',
                duration: duration(1.1),
                ease: 'power3.out',
                clearProps: 'filter',
                onComplete: () => {
                    state.selectedFloor = null;
                    state.selectedUnit = null;
                },
            },
        );
    });
}

/* Oda, tıklanan noktadan açılıyormuş gibi belirir; kapanışta aynı noktaya çekilir */
function roomIn(el: Element, done: () => void) {
    gsap.fromTo(
        el,
        { autoAlpha: 0, scale: 1.3, transformOrigin: roomOrigin.value },
        {
            autoAlpha: 1,
            scale: 1,
            duration: duration(0.9),
            ease: 'power3.out',
            clearProps: 'transform',
            onComplete: done,
        },
    );
}

function roomOut(el: Element, done: () => void) {
    lookAround?.kill();
    gsap.to(el, {
        autoAlpha: 0,
        scale: 1.15,
        transformOrigin: roomOrigin.value,
        duration: duration(0.6),
        ease: 'power2.in',
        onComplete: done,
    });
}

/* Odalar arası: seçilen yöne doğru yürüyormuş gibi geçer (sağdaki oda sağdan gelir) */
function selectRoom(room: MediaItem) {
    const renders = planUnit.value?.renders ?? [];
    const from = renders.findIndex((r) => r.id === state.selectedRoom?.id);
    roomDirection.value = Math.sign(renders.findIndex((r) => r.id === room.id) - from);
    state.selectedRoom = room;
}

/* Görsel hafif yakın durur ve sağa-sola bakar; bu sayede kenarlar görünmez */
function photoIn(el: Element, done: () => void) {
    if (prefersReducedMotion()) {
        gsap.fromTo(el, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.01, onComplete: done });
        return;
    }
    const dir = roomDirection.value;
    gsap.fromTo(
        el,
        { autoAlpha: 0, xPercent: dir * 10, rotationY: -dir * 8, scale: 1.3, transformPerspective: 1400 },
        {
            autoAlpha: 1,
            xPercent: -2.6,
            rotationY: 2.4,
            scale: 1.18,
            duration: 1.1,
            ease: 'power3.out',
            onComplete: () => {
                done();
                lookAround = gsap.to(el, { xPercent: 2.6, rotationY: -2.4, duration: 7, ease: 'sine.inOut', repeat: -1, yoyo: true });
            },
        },
    );
}

function photoOut(el: Element, done: () => void) {
    lookAround?.kill();
    const dir = roomDirection.value;
    gsap.to(el, { autoAlpha: 0, xPercent: -dir * 10, rotationY: dir * 8, scale: 1.3, duration: duration(0.7), ease: 'power2.in', onComplete: done });
}

/* Fare odada gezerken bakış yönü de hafifçe imlece döner */
function onLook(e: PointerEvent) {
    if (e.pointerType !== 'mouse' || prefersReducedMotion()) return;
    const box = stage.value?.getBoundingClientRect();
    if (!box) return;
    const x = (e.clientX - box.left) / box.width - 0.5;
    const y = (e.clientY - box.top) / box.height - 0.5;
    gsap.to(lookLayer.value, { rotationY: x * 5, rotationX: -y * 3, transformPerspective: 1400, duration: 0.9, ease: 'power2.out', overwrite: 'auto' });
}
</script>

<template>
    <section ref="stage" id="vitrin" class="relative h-svh min-h-[640px] overflow-hidden bg-[#0a0a0a] text-pearl [container-type:size]">
        <Transition :css="false" @enter="buildingIn" @leave="fadeOut">
            <!-- 1. Aşama: binadan kat seçimi -->
            <div v-if="state.currentView === 'building'" class="absolute inset-0 z-10 bg-[#0a0a0a]" @pointerleave="hovered = null">
                <div
                    ref="frame"
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
                    :style="{
                        width: `max(100%, calc(100cqh * ${width / height}))`,
                        aspectRatio: width / height,
                    }"
                >
                    <img :src="config.building.image" :alt="t('showcase.building_alt')" class="size-full object-cover" />
                    <svg :viewBox="`0 0 ${width} ${height}`" class="absolute inset-0 size-full" role="group" :aria-label="t('showcase.hint')">
                        <polygon
                            v-for="f in floors"
                            :key="f.key"
                            :points="f.points"
                            class="cursor-pointer outline-none transition-[fill,stroke] duration-200"
                            :class="hovered?.key === f.key ? 'fill-gold-light/55 stroke-white' : 'fill-transparent stroke-transparent'"
                            stroke-width="1.5"
                            role="button"
                            :tabindex="f.focusable ? 0 : -1"
                            :aria-label="`${t('showcase.floor')} ${f.number} · ${unitTitle(f.unit)} · ${f.unit.sizes}`"
                            @pointerenter="onFloorEnter(f, $event)"
                            @focus="showFloor(f)"
                            @click="onFloorClick(f, $event)"
                            @keydown.enter="selectFloor(f)"
                        />
                    </svg>
                </div>

                <div class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-linear-to-b from-[#0a0a0a]/70 to-transparent"></div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-linear-to-t from-[#0a0a0a]/70 to-transparent"></div>

                <!-- Bilgi kartı ve kata bağlanan çizgi -->
                <template v-if="hovered">
                    <span
                        v-if="anchor"
                        class="pointer-events-none absolute h-px bg-white/80"
                        :style="{
                            left: `${anchor.toLeft ? anchor.x - leader : anchor.x}px`,
                            top: `${anchor.y}px`,
                            width: `${leader}px`,
                        }"
                    ></span>
                    <div
                        class="absolute z-10 flex items-center gap-4 border border-white/10 bg-[#0a0a0a]/90 p-4 backdrop-blur-md max-lg:inset-x-4 max-lg:bottom-14 lg:pointer-events-none lg:-translate-y-1/2"
                        :style="
                            anchor
                                ? {
                                      left: `${anchor.toLeft ? anchor.x - leader - cardWidth : anchor.x + leader}px`,
                                      top: `${anchor.y}px`,
                                      width: `${cardWidth}px`,
                                  }
                                : undefined
                        "
                    >
                        <div class="shrink-0 text-center">
                            <p class="text-[10px] tracking-[0.2em] text-pearl/70 uppercase">
                                {{ t('showcase.floor') }}
                            </p>
                            <p class="font-serif text-3xl">
                                {{ hovered.number }}
                            </p>
                        </div>
                        <div class="min-w-0 flex-1 border-l border-white/10 pl-4">
                            <p class="font-serif text-xl">
                                {{ unitTitle(hovered.unit) }}
                            </p>
                            <p class="mt-1 text-[12px] text-pearl/75">
                                {{ hovered.unit.sizes }}
                            </p>
                            <p class="mt-2 inline-block border border-gold-light/50 px-2 py-0.5 text-[11px] tracking-[0.04em] text-gold-light">
                                {{ hovered.unit.price ?? t('showcase.price_ask') }}
                            </p>
                        </div>
                        <button type="button" class="btn shrink-0 self-center px-4 py-3 lg:hidden" @click="selectFloor(hovered)">
                            {{ t('showcase.view_plan') }}
                        </button>
                    </div>
                </template>

                <p v-if="!hovered" class="pointer-events-none absolute inset-x-0 bottom-8 px-20 text-center text-sm tracking-[0.08em] text-white">
                    {{ t('showcase.hint') }}
                </p>
            </div>
        </Transition>

        <!-- 2. Aşama: 3D kat planı (binanın arkasında hazır bekler) -->
        <div
            v-if="planUnit"
            :inert="state.currentView === 'building'"
            class="absolute inset-0 grid grid-rows-[minmax(0,1fr)_auto] lg:grid-cols-[minmax(0,1fr)_24rem] lg:grid-rows-1"
        >
            <div class="relative min-h-0 overflow-hidden">
                <div data-plan-model class="size-full">
                    <Model3D v-if="planUnit.model_3d" ref="model" fill :src="planUnit.model_3d" :alt="unitTitle(planUnit)" :hotspots="hotspots" @hotspot="enterRoom" />
                </div>
                <button
                    type="button"
                    class="absolute top-24 left-4 inline-flex min-h-11 items-center gap-2 rounded-full border border-white/10 bg-black/45 px-4 text-[13px] tracking-[0.08em] backdrop-blur-md transition hover:border-gold sm:left-8"
                    @click="state.currentView = 'building'"
                >
                    <Icon name="arrow-left" :size="16" />
                    {{ t('showcase.back_building') }}
                </button>
            </div>

            <aside
                data-plan-card
                class="max-h-[40svh] overflow-y-auto border-t border-white/10 bg-[#0a0a0a] p-5 sm:p-8 lg:flex lg:max-h-none lg:flex-col lg:justify-center lg:border-t-0 lg:border-l"
                data-lenis-prevent
            >
                <p v-if="state.selectedFloor" class="text-[12px] tracking-[0.2em] text-gold-light uppercase">{{ t('showcase.floor') }} {{ state.selectedFloor.number }}</p>
                <h3 class="mt-2 font-serif text-4xl">
                    {{ unitTitle(planUnit) }}
                </h3>
                <p v-if="planUnit.description" class="mt-3 text-sm leading-relaxed text-pearl/70">
                    {{ planUnit.description }}
                </p>
                <p v-if="hotspots.length" class="mt-3 text-sm text-gold-light">
                    {{ t('showcase.plan_hint') }}
                </p>

                <dl class="mt-5 border-t border-white/10 text-sm">
                    <div class="flex justify-between gap-6 border-b border-white/10 py-3">
                        <dt class="text-pearl/70">
                            {{ t('showcase.bathrooms') }}
                        </dt>
                        <dd>{{ planUnit.bathrooms }}</dd>
                    </div>
                    <div class="flex justify-between gap-6 border-b border-white/10 py-3">
                        <dt class="shrink-0 text-pearl/70">
                            {{ t('unit.sizes') }}
                        </dt>
                        <dd class="text-right">{{ planUnit.sizes }}</dd>
                    </div>
                    <div class="flex justify-between gap-6 border-b border-white/10 py-3">
                        <dt class="text-pearl/70">{{ t('unit.count') }}</dt>
                        <dd>
                            {{ t('units.count', { count: planUnit.unit_count }) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-6 border-b border-white/10 py-3">
                        <dt class="text-pearl/70">{{ t('showcase.price') }}</dt>
                        <dd class="text-right text-gold-light">
                            {{ planUnit.price ?? t('showcase.price_ask') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 grid gap-2">
                    <a v-if="planUnit.tour_url" :href="planUnit.tour_url" target="_blank" rel="noopener" class="btn justify-center">{{ t('showcase.tour') }}</a>
                    <button v-if="hotspots.length" type="button" class="btn justify-center" @click="enterRoom(hotspots[0].id)">
                        {{ t('showcase.rooms') }}
                        <Icon name="arrow-right" :size="16" />
                    </button>
                    <button type="button" class="btn btn-outline-light justify-center" @click="scrollToId('iletisim')">
                        {{ t('unit.enquire') }}
                    </button>
                    <a :href="localePath(`/daire/${planUnit.slug}`)" class="mt-1 text-center text-[13px] text-pearl/70 underline-offset-4 hover:text-pearl hover:underline">{{
                        t('showcase.unit_page')
                    }}</a>
                </div>
            </aside>

            <!-- 3. Aşama: oda fotoğrafı -->
            <Transition :css="false" @enter="roomIn" @leave="roomOut">
                <div v-if="state.currentView === 'room' && state.selectedRoom" class="absolute inset-0 z-10 overflow-hidden bg-[#0a0a0a]" @pointermove="onLook">
                    <div ref="lookLayer" class="absolute inset-0">
                        <Transition appear :css="false" @enter="photoIn" @leave="photoOut">
                            <img
                                :key="state.selectedRoom.id"
                                :src="state.selectedRoom.url"
                                :alt="state.selectedRoom.caption ?? unitTitle(planUnit)"
                                class="absolute inset-0 size-full"
                                :class="fits[state.selectedRoom.id] === 'contain' ? 'object-contain' : 'object-cover'"
                                @load="setFit(state.selectedRoom!, $event)"
                            />
                        </Transition>
                    </div>
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-linear-to-b from-[#0a0a0a]/75 to-transparent"></div>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-56 bg-linear-to-t from-[#0a0a0a]/80 to-transparent"></div>

                    <button
                        type="button"
                        class="absolute top-24 left-4 inline-flex min-h-11 items-center gap-2 rounded-full border border-white/10 bg-black/45 px-4 text-[13px] tracking-[0.08em] backdrop-blur-md transition hover:border-gold sm:left-8"
                        @click="backToPlan"
                    >
                        <Icon name="arrow-left" :size="16" />
                        {{ t('showcase.back_plan') }}
                    </button>

                    <div class="absolute inset-x-4 bottom-24 flex flex-col gap-3 sm:inset-x-8 sm:bottom-6 lg:flex-row lg:items-end lg:justify-between">
                        <Transition
                            mode="out-in"
                            enter-active-class="transition duration-500 ease-out"
                            enter-from-class="translate-y-3 opacity-0"
                            leave-active-class="transition duration-200"
                            leave-to-class="opacity-0"
                        >
                            <p :key="state.selectedRoom.id" class="font-serif text-3xl sm:text-4xl">
                                {{ state.selectedRoom.caption }}
                            </p>
                        </Transition>
                        <div
                            class="flex max-w-full gap-1 overflow-x-auto rounded-full border border-white/10 bg-black/45 p-1.5 [scrollbar-width:none] backdrop-blur-xl lg:mr-20"
                            role="tablist"
                            data-lenis-prevent
                        >
                            <button
                                v-for="room in planUnit.renders"
                                :key="room.id"
                                type="button"
                                role="tab"
                                class="min-h-11 shrink-0 rounded-full px-4 text-[13px] tracking-[0.04em] transition sm:px-5"
                                :class="room.id === state.selectedRoom.id ? 'bg-pearl text-night' : 'text-pearl/85 hover:text-pearl'"
                                :aria-selected="room.id === state.selectedRoom.id"
                                @click="selectRoom(room)"
                            >
                                {{ room.caption }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </section>
</template>
