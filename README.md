# Perle Garden

Alanya Kargıcak'taki Perle Garden konut projesinin tanıtım ve satış sitesi.

- Ana sayfa: proje tanıtımı, galeri, olanaklar, inşaat aşamaları, konum, iletişim
- Vitrin: binadan kat seçimi → 3D kat planı → oda görselleri
- Daire sayfaları: render görselleri, etkileşimli 3D model, telefonda AR
- Diller: Türkçe, İngilizce, Rusça
- Admin paneli: daireler, galeri, olanaklar, site ayarları, formdan gelen mesajlar

**Teknolojiler:** Laravel 13, Inertia, Vue 3 + TypeScript, Tailwind CSS 4, GSAP, model-viewer

## Hızlı kurulum

Görseller ve 3D modeller repoda olduğu için ayrıca dosya indirmeniz gerekmez. Yerel kurulum SQLite kullanır, veritabanı sunucusu gerekmez.

### 1. Gerekenler

| Araç | Sürüm |
| --- | --- |
| [Git](https://git-scm.com/) | güncel |
| [PHP](https://www.php.net/downloads) | 8.3 veya üstü |
| [Composer](https://getcomposer.org/) | 2 |
| [Node.js](https://nodejs.org/) | 20.19+ veya 22.12+ |

PHP eklentileri: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`.

Windows'ta `php.ini` içinde bu eklentilerin başındaki `;` işaretini kaldırın. Yüklü eklentileri şu komutla görebilirsiniz:

```bash
php -m
```

### 2. İndirin ve kurun

```bash
git clone https://github.com/Leansxd/perle-garden.git
```

```bash
cd perle-garden/app
```

```bash
composer setup
```

`composer setup` tek seferde şunları yapar: PHP ve Node paketlerini kurar, `.env` dosyasını oluşturur, uygulama anahtarını üretir, SQLite veritabanını kurup örnek içerikle doldurur, `public/storage` bağlantısını açar ve arayüzü derler.

> Bu komutu yalnızca ilk kurulumda çalıştırın. Tekrar çalıştırmak, içeriği seeder'daki örnek verilerle değiştirir ve admin panelindeki düzenlemeleri siler.

### 3. Çalıştırın

```bash
composer serve
```

Site adresi: <http://127.0.0.1:8000>

| Adres | Sayfa |
| --- | --- |
| `/` | Türkçe ana sayfa |
| `/en`, `/ru` | İngilizce ve Rusça ana sayfa |
| `/daire/1-1`, `/daire/2-1` | Daire sayfaları |
| `/admin` | Admin paneli |

`php artisan serve` dosya yüklemeyi 2 MB ile sınırlar. Büyük görsel ve 3D model yüklemek için `composer serve` kullanın.

### 4. Admin kullanıcısı oluşturun

Örnek veride şifre bulunmaz, kullanıcıyı kendiniz oluşturursunuz. Şifre ekranda görünmeden sorulur:

```bash
php artisan admin:user eposta@adres.com
```

## Geliştirme

İki ayrı terminalde `app` klasöründeyken çalıştırın:

```bash
npm run dev
```

```bash
composer serve
```

Kod kontrolü ve testler:

```bash
npx vue-tsc --noEmit
```

```bash
php artisan test
```

## İçerik nerede?

| İçerik | Yer |
| --- | --- |
| Metin, rakam, daire, galeri, ayarlar | Admin paneli (`/admin`) |
| Arayüz çevirileri | `app/lang/{tr,en,ru}/site.php` |
| İlk kurulum verisi | `app/database/seeders/DatabaseSeeder.php` |
| Daire render'ları ve 3D modeller | `app/storage/app/public/uploads/units/` |
| Proje görselleri | `app/public/media/galeri/` |
| Vitrin: bina görseli, katlar, oda noktaları, fiyat | `app/resources/js/data/showcase.json` |

Ayrıntılı içerik ve admin notları: [app/KURULUM.md](app/KURULUM.md)

### Vitrin verisi

`showcase.json` içinde:

- `building.facades`: görsel üzerindeki her cephe. `corners` sol üst, sağ üst, sağ alt, sol alt köşenin piksel koordinatıdır; `floors` kat sayısı, `unit` o cephedeki daire tipidir.
- `units[].price`: `null` ise "Fiyat için bilgi alın" yazar.
- `units[].roomPoints`: 3D modeldeki oda noktaları. `render`, render dosya adının son kısmıdır (`1-1-salon.png` için `salon`).

## Yayına almadan önce

- Seeder'daki rakamlar, daire bilgileri ve iletişim bilgileri örnektir; gerçekleriyle değiştirin.
- `showcase.json` içindeki cephe, kat ve daire tipi eşleşmeleri temsilîdir; gerçek dağılımı girin.
- Sunucuda MySQL kullanacaksanız `.env` içinde `DB_CONNECTION=mysql` ve bağlantı bilgilerini ayarlayın.
- `.env` içinde `APP_ENV=production`, `APP_DEBUG=false` yapın ve `APP_URL` değerini gerçek adresle değiştirin.

Sunucuda:

```bash
composer install --no-dev --optimize-autoloader
```

```bash
npm ci
```

```bash
npm run build
```

```bash
php artisan migrate --force
```

```bash
php artisan storage:link
```

```bash
php artisan optimize
```

Web sunucusunun kök klasörü `app/public` olmalıdır. Sunucudaki PHP'de `upload_max_filesize` ve `post_max_size` değerlerini 3D model boyutuna (yaklaşık 25 MB) göre artırın.

## Sorun giderme

| Belirti | Çözüm |
| --- | --- |
| `could not find driver` | `php.ini` içinde `pdo_sqlite` ve `sqlite3` eklentilerini açın |
| `composer install` zip veya fileinfo hatası veriyor | `php.ini` içinde `zip` ve `fileinfo` eklentilerini açın |
| Sayfa stilsiz veya boş açılıyor | `npm run dev` kapalıysa `app/public/hot` dosyasını silin, ardından `npm run build` çalıştırın |
| Görseller veya 3D model 404 veriyor | `php artisan storage:link` çalıştırın |
| 8000 portu dolu | Portu kullanan programı kapatın ya da `php artisan serve --port=8001` ile açın |
| 3D model ilk açılışta geç geliyor | Normaldir, modeller yaklaşık 20 MB'tır |
