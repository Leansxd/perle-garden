export type SiteSettings = Record<string, string | null>;

export interface StatItem {
    prefix: string | null;
    value: number;
    suffix: string | null;
    label: string;
}

export interface Highlight {
    title: string;
    description: string;
}

export interface UnitType {
    id: number;
    slug: string;
    name: string;
    label: string | null;
    unit_count: number;
    sizes: string;
    description: string | null;
    rooms: string[] | null;
    plan_image: string | null;
    cover_image: string | null;
}

export interface MediaItem {
    id: number;
    url: string;
    caption: string | null;
}

export interface UnitDetail extends UnitType {
    photos: MediaItem[];
    renders: MediaItem[];
    plans: MediaItem[];
    model_3d: string | null;
    tour_url: string | null;
    video_url: string | null;
}

export interface GalleryImage {
    id: number;
    path: string | null;
    caption: string;
}

export interface ConstructionPhase {
    title: string;
    percent: number;
    note: string | null;
}

export interface LocationPoint {
    name: string;
    distance: string;
}

export interface LocaleOption {
    code: string;
    label: string;
    name: string;
    hreflang: string;
    url: string;
}

/** Admin formlarında çok dilli alan */
export interface Trans {
    tr: string | null;
    en: string | null;
    ru: string | null;
}

export interface SharedProps {
    site: SiteSettings;
    locale: string;
    locales: LocaleOption[];
    ui: Record<string, unknown>;
    auth: { name: string; email: string } | null;
    flash: { success: string | null };
    [key: string]: unknown;
}
