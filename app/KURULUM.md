# Perle Garden Residence — Proje Sitesi

Tek sayfalık, animasyonlu konut projesi tanıtım sitesi.

**Teknolojiler:** Laravel 13 (PHP 8.5) · Inertia.js v3 · Vue 3 + TypeScript · Tailwind CSS v4 · GSAP (ScrollTrigger, SplitText) · Lenis · MySQL/MariaDB

## Çalıştırma

```bash
cd app
php artisan migrate:fresh --seed   # tabloları kurar + örnek içerik (XAMPP MySQL, port 3307)
npm run build                      # ya da geliştirirken: npm run dev
php artisan serve                  # http://localhost:8000
```

## Bölümler (tek sayfa)

`#top` video hero · istatistikler · `#proje` tanıtım · `#daireler` daire tipleri (tıklayınca plan penceresi)
· `#galeri` yatay kayan galeri + ışık kutusu · `#yasam` sosyal olanaklar · `#insaat` inşaat ilerlemesi
· `#konum` mesafeler + harita · `#iletisim` bilgi talep formu (veritabanına kaydedilir)

## Efektler

Açılış perdesi (logo), Lenis yumuşak kaydırma, harf harf hero başlığı, inci parçacıkları (canvas),
satır satır başlık açılımı, perde efektiyle açılan görseller, parallax, sabitlenen yatay galeri,
kayan yazı bantları, 3B eğilen daire kartları, mıknatıslı butonlar, özel imleç, kaydırma ilerleme çubuğu.
"Hareketi azalt" tercihi açık cihazlarda tüm efektler otomatik kapanır.

## İçerik nerede?

| Tablo | İçerik |
| --- | --- |
| `settings` | Proje adı, hero metinleri, iletişim, WhatsApp no, teslim tarihi, harita linki |
| `stats` | Hero altındaki 4 rakam |
| `highlights` | Tanıtım bölümündeki 4 özellik |
| `unit_types` | Daire tipleri, adetleri, m², oda listesi, plan görseli |
| `gallery_images` | Galeri görselleri ve açıklamaları |
| `amenities` | Sosyal olanaklar |
| `construction_phases` | İnşaat aşamaları ve yüzdeleri |
| `location_points` | Konum mesafeleri |
| `contact_messages` | Formdan gelen talepler |

Başlangıç değerleri: `database/seeders/DatabaseSeeder.php`. Metinlerde `*yıldız arası*` kelimeler altın italik vurgu olur.

> ⚠️ Rakamlar, mesafeler, telefon ve WhatsApp numarası şu an **örnek** değerlerdir — gerçek bilgilerle güncelleyin.

Görseller için: `public/media/README.md`

## Diller (TR / EN / RU)

| Adres | Dil |
| --- | --- |
| `/` | Türkçe (varsayılan) |
| `/en` | English |
| `/ru` | Русский |

- **Arayüz metinleri:** `lang/tr/site.php`, `lang/en/site.php`, `lang/ru/site.php`
- **Ayarlar (settings):** anahtarın sonuna `@en` / `@ru` ekleyin — ör. `hero_title@en`. Çevirisi yoksa Türkçe gösterilir.
- **Diğer içerikler** (daireler, olanaklar, galeri, aşamalar, konumlar, rakamlar): Türkçe sütunlar + `translations` JSON sütunu: `{"en": {"title": "..."}, "ru": {"title": "..."}}`
- Formdan gelen taleplerde hangi dilden geldiği `contact_messages.locale` sütununda tutulur.

## Fontlar

Forum (başlıklar, logodaki klasik Roma harflerine yakın) · Cormorant Garamond Italic (altın vurgular) · Jost (gövde). Üçü de Latin + Türkçe + Kiril karakterleri destekler.

## Admin paneli — `/admin`

```bash
composer serve        # büyük dosya yükleme limitleriyle sunucu (100 MB) → http://localhost:8000
```

- **Giriş:** `http://localhost:8000/admin` — ilk kullanıcı `.env` dosyasındaki `ADMIN_EMAIL` / `ADMIN_PASSWORD` ile oluşturulur.
- **Şifre değiştirme / yeni kullanıcı:** `php artisan admin:user eposta@adres.com` (şifre ekranda görünmeden sorulur)
- `php artisan serve` yükleme boyutunu 2 MB ile sınırlar; büyük fotoğraf ve 3D model için `composer serve` kullanın.

| Bölüm | Ne yapılır |
| --- | --- |
| Daireler | Daire tipi ekle/düzenle/sırala/gizle; **3D görseller, fotoğraflar, kat planları** yükle; kapak görseli; **.glb 3D model**; 360° sanal tur ve video bağlantısı; tüm metinler TR/EN/RU |
| Galeri | Ana sayfa galerisine görsel yükle, açıklama (3 dil), sırala, sil |
| Sosyal olanaklar · İnşaat aşamaları · Konum · Rakamlar · Öne çıkanlar | Liste düzenleyici: satır ekle/sil/sırala, 3 dilde metin |
| Site ayarları | Hero metinleri, video ve görseller, tanıtım görselleri, iletişim, WhatsApp, harita |
| Mesajlar | Formdan gelen talepler; okundu işareti, ara / WhatsApp / e-posta kısayolları |

Yüklenen dosyalar `storage/app/public/uploads/` altına kaydedilir (`public/storage` bağlantısıyla yayınlanır — yeni kurulumda bir kez `php artisan storage:link`).

## Daire sayfaları

`/daire/{adres}` · `/en/daire/{adres}` · `/ru/daire/{adres}` — ör. `/daire/2-1-dubleks`

Sayfada sırasıyla: kapak + bilgiler, açıklama ve oda listesi, **3D görseller**, **fotoğraflar**, **kat planları** (birden fazlaysa sekmeli),
**etkileşimli 3D model** (döndür/yakınlaştır, telefonda AR), **360° tur**, video, diğer daire tipleri ve bilgi formu (daire önceden seçili).
Görseli olmayan bölümler otomatik gizlenir.
