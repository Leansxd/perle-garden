# Perle Garden — medya klasörü

Dosyaları aşağıdaki adlarla koyun; site otomatik kullanır. Dosya yoksa şık bir yer tutucu görünür.

| Dosya | Nerede görünür |
| --- | --- |
| `logo.png` | Üst menü + açılış perdesi (eklendi ✓) |
| `hero.mp4` | Ana sayfa arka plan videosu (sessiz, döngülü · 1920×1080 · H.264 · < 10 MB) |

## Galeri — `galeri/` klasörü

Gönderilen render'ları şu adlarla kaydedin (JPG):

| Dosya | Görsel | Kullanım |
| --- | --- | --- |
| `01.jpg` | Kapalı otopark + tenis kortu yolu | Galeri |
| `02.jpg` | Site girişi ve önündeki meydan | Galeri |
| `03.jpg` | Kuşbakışı (havuzlar, tenis kortu) | Galeri |
| `04.jpg` | Ticari cephe — iki blok, önde meydan | Galeri |
| `05.jpg` | Havuz + çimenlik bahçe | **Proje tanıtım görseli** + galeri |
| `06.jpg` | Havuz + kapalı havuz + tenis | Galeri |
| `07.jpg` | Köşe blok, cam mağazalar | Galeri |
| `08.jpg` | Cadde cephesi, iki blok | Galeri |
| `09.jpg` | Ormanla çevrili kuşbakışı | Galeri |
| `10.jpg` | Gün batımı panoramik görünüm | **Hero arka planı (video yokken)** + galeri |

Sıra ve açıklamalar `gallery_images` tablosundan, hero/tanıtım görselleri `settings`
tablosundaki `hero_poster` / `intro_image` anahtarlarından değiştirilebilir.

## Daire planları

`planlar/1-1.jpg` gibi kaydedip `unit_types.plan_image` alanına `/media/planlar/1-1.jpg` yazın.
