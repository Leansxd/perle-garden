<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\ConstructionPhase;
use App\Models\GalleryImage;
use App\Models\Highlight;
use App\Models\LocationPoint;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Perle Garden — proje içeriği (TR / EN / RU).
 *
 * ÖNEMLİ: Rakamlar, daire tipleri, mesafeler ve iletişim bilgileri ÖRNEKTİR.
 * Gerçek bilgilerle güncellenmelidir. Görseller: public/media/README.md
 *
 * Çeviri yapısı:
 *  - Ayarlar: "anahtar" (TR) + "anahtar@en" / "anahtar@ru"
 *  - Diğer tablolar: TR sütunlar + translations JSON {"en": {...}, "ru": {...}}
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->admin();
        $this->settings();
        $this->stats();
        $this->highlights();
        $this->unitTypes();
        $this->gallery();
        $this->amenities();
        $this->construction();
        $this->location();
    }

    /** Admin paneli kullanıcısı — bilgiler .env dosyasındaki ADMIN_EMAIL / ADMIN_PASSWORD */
    private function admin(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD tanımlı değil — admin oluşturulmadı. "php artisan admin:user e-posta" ile oluşturun.');

            return;
        }
        User::updateOrCreate(['email' => $email], ['name' => 'Yönetici', 'password' => Hash::make($password)]);
    }

    /** [tr, en, ru] üçlüsünden translations dizisi üretir. */
    private function tr(array $fields): array
    {
        $out = ['en' => [], 'ru' => []];
        foreach ($fields as $name => [, $en, $ru]) {
            $out['en'][$name] = $en;
            $out['ru'][$name] = $ru;
        }

        return $out;
    }

    private function settings(): void
    {
        // Dilden bağımsız ayarlar
        $plain = [
            'company_name' => 'Perle Garden',
            'company_tagline' => 'Residence',
            'hero_video' => '/media/hero.mp4',
            'hero_poster' => '/media/galeri/10.jpg',
            'intro_image' => '/media/galeri/05.jpg',
            'intro_image_2' => '/media/galeri/06.jpg',
            'expand_image' => '/media/galeri/09.jpg',
            'life_image' => '/media/galeri/01.jpg',
            'floors' => '5',
            'unit_size_range' => '58 – 165 m²',
            'map_embed' => null, // Google Maps "Haritayı yerleştir" iframe src adresi
            'phone' => '+90 (500) 000 00 00',
            'whatsapp' => '905000000000', // ülke koduyla, boşluksuz
            'email' => 'sales@perlegarden.com',
            'instagram' => null,
            'youtube' => null,
        ];

        // Çok dilli ayarlar: [tr, en, ru]
        $localized = [
            'hero_eyebrow' => ['Kargıcak · Alanya · Antalya', 'Kargıcak · Alanya · Antalya', 'Каргыджак · Алания · Анталья'],
            'hero_title' => ['Denize yakın, *bahçe içinde* yaşam', 'Garden living, *close to the sea*', 'Жизнь в саду, *у самого моря*'],
            'hero_subtitle' => [
                'Perle Garden; geniş peyzaj alanları, otel konforunda sosyal olanakları ve modern mimarisiyle Akdeniz kıyısında yükselen yeni yaşam projesi.',
                'Perle Garden is a new residential project on the Mediterranean coast, with generous landscaped gardens, hotel-style amenities and contemporary architecture.',
                'Perle Garden — новый жилой проект на побережье Средиземного моря с просторным ландшафтным парком, инфраструктурой уровня отеля и современной архитектурой.',
            ],
            'intro_title' => ['Şehre yakın, *doğanın* içinde.', 'Close to the city, *within nature*.', 'Рядом с городом, *среди природы*.'],
            'intro_text' => [
                'Deniz, dağ manzarası ve yeşil peyzajı tek bir yerleşimde buluşturan Perle Garden; günlük hayatın tüm ihtiyaçlarını sitenin içinde karşılayan bütüncül bir yaşam alanı olarak tasarlandı.',
                'Bringing together the sea, mountain views and lush landscaping in one community, Perle Garden is designed as a complete living environment that meets every daily need within the complex.',
                'Perle Garden объединяет море, вид на горы и зелёный ландшафт в одном комплексе и задуман как целостная среда, где все повседневные потребности решаются на территории.',
            ],
            'delivery_date' => ['Haziran 2027', 'June 2027', 'Июнь 2027'],
            'location_name' => ['Kargıcak / Alanya', 'Kargıcak / Alanya', 'Каргыджак / Алания'],
            'address' => ['Satış Ofisi: Kargıcak Mah., Alanya / Antalya', 'Sales Office: Kargıcak, Alanya / Antalya', 'Офис продаж: Каргыджак, Алания / Анталья'],
            'working_hours' => ['Her gün 09:00 – 19:00', 'Daily 09:00 – 19:00', 'Ежедневно 09:00 – 19:00'],
        ];

        Setting::query()->delete();
        foreach ($plain as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
        foreach ($localized as $key => [$tr, $en, $ru]) {
            Setting::create(['key' => $key, 'value' => $tr]);
            Setting::create(['key' => "$key@en", 'value' => $en]);
            Setting::create(['key' => "$key@ru", 'value' => $ru]);
        }
    }

    private function stats(): void
    {
        Stat::query()->delete();
        $rows = [
            [100, ' m', ['Denize mesafe', 'To the sea', 'До моря']],
            [9534, ' m²', ['Toplam proje alanı', 'Total site area', 'Площадь проекта']],
            [139, null, ['Bağımsız bölüm', 'Residences', 'Квартир']],
            [5, null, ['Katlı bloklar', 'Storey blocks', 'Этажей в корпусах']],
        ];
        foreach ($rows as $i => [$value, $suffix, $label]) {
            Stat::create([
                'value' => $value, 'suffix' => $suffix, 'label' => $label[0], 'sort_order' => $i,
                'translations' => $this->tr(['label' => $label]),
            ]);
        }
    }

    private function highlights(): void
    {
        Highlight::query()->delete();
        $rows = [
            [['Otel konforu', 'Hotel comfort', 'Комфорт отеля'], ['Spa, havuz ve fitness ile tatil hissi her gün', 'Spa, pools and fitness — a holiday feeling every day', 'Спа, бассейны и фитнес — ощущение отпуска каждый день']],
            [['Doğayla iç içe', 'Close to nature', 'В гармонии с природой'], ['Geniş peyzaj, yürüyüş yolları ve meyve bahçeleri', 'Generous landscaping, walking paths and orchards', 'Просторный ландшафт, прогулочные дорожки и сады']],
            [['Güvenli yaşam', 'Secure living', 'Безопасность'], ['7/24 güvenlik, kamera ve kartlı giriş sistemi', '24/7 security, CCTV and key-card access', 'Охрана 24/7, видеонаблюдение и доступ по картам']],
            [['Ticari cadde', 'Retail street', 'Торговая улица'], ['Kafe ve mağazalarla canlı bir zemin kat', 'A lively ground floor of cafés and shops', 'Оживлённый первый этаж с кафе и магазинами']],
        ];
        foreach ($rows as $i => [$title, $desc]) {
            Highlight::create([
                'title' => $title[0], 'description' => $desc[0], 'sort_order' => $i,
                'translations' => $this->tr(['title' => $title, 'description' => $desc]),
            ]);
        }
    }

    private function unitTypes(): void
    {
        UnitType::query()->delete();
        $rows = [
            ['1+1', [null, null, null], 83, '58 / 63 / 67 / 71 / 76 m²',
                ['Yatırım ve tatil kullanımı için ideal, balkonlu ve aydınlık planlar.', 'Bright layouts with balconies, ideal for investment or holiday use.', 'Светлые планировки с балконом — идеально для инвестиций и отдыха.'],
                [['Salon + Amerikan mutfak', '1 yatak odası', '1 banyo', 'Balkon'], ['Living room + open kitchen', '1 bedroom', '1 bathroom', 'Balcony'], ['Гостиная + кухня-студия', '1 спальня', '1 санузел', 'Балкон']]],
            ['2+1', [null, null, null], 16, '86 / 91 / 93 / 96 m²',
                ['Aileler için ferah salon ve iki ayrı yatak odasıyla dengeli yaşam.', 'A spacious living room and two separate bedrooms — balanced family living.', 'Просторная гостиная и две отдельные спальни — для комфортной семейной жизни.'],
                [['Salon + Amerikan mutfak', '2 yatak odası', 'Ebeveyn banyosu', 'Geniş balkon'], ['Living room + open kitchen', '2 bedrooms', 'En-suite bathroom', 'Large balcony'], ['Гостиная + кухня-студия', '2 спальни', 'Мастер-санузел', 'Большой балкон']]],
            ['2+1', ['Dubleks', 'Duplex', 'Дуплекс'], 29, '111 – 127 m²',
                ['Çatı katında teraslı, iki katlı yaşam alanı.', 'Two-level living on the top floor with a private terrace.', 'Двухуровневая квартира на верхнем этаже с террасой.'],
                [['Alt kat salon ve mutfak', 'Üst kat 2 yatak odası', '2 banyo', 'Çatı terası'], ['Lower level living & kitchen', 'Upper level 2 bedrooms', '2 bathrooms', 'Roof terrace'], ['Внизу гостиная и кухня', 'Наверху 2 спальни', '2 санузла', 'Терраса на крыше']]],
            ['3+1', ['Dubleks', 'Duplex', 'Дуплекс'], 4, '165 m²',
                ['Projenin en geniş planları; panoramik manzaralı büyük teras.', 'The largest homes in the project, with a large panoramic terrace.', 'Самые просторные квартиры проекта с большой панорамной террасой.'],
                [['Geniş salon', '3 yatak odası', '2 banyo + WC', 'Panoramik teras'], ['Spacious living room', '3 bedrooms', '2 bathrooms + WC', 'Panoramic terrace'], ['Просторная гостиная', '3 спальни', '2 санузла + WC', 'Панорамная терраса']]],
            ['2+1', ['Bahçe Dubleks', 'Garden Duplex', 'Дуплекс с садом'], 7, '117 / 119 / 121 m²',
                ['Zemin katta özel bahçeli, bahçe kullanımına açık dubleks yaşam.', 'Ground-floor duplex living with a private garden.', 'Дуплекс на первом этаже с собственным садом.'],
                [['Özel bahçe', '2 yatak odası', '2 banyo', 'Bahçe katı + alt kat'], ['Private garden', '2 bedrooms', '2 bathrooms', 'Garden level + lower floor'], ['Собственный сад', '2 спальни', '2 санузла', 'Садовый + нижний уровень']]],
        ];
        foreach ($rows as $i => [$name, $label, $count, $sizes, $desc, $rooms]) {
            $unit = UnitType::create([
                'name' => $name, 'label' => $label[0], 'unit_count' => $count, 'sizes' => $sizes,
                'description' => $desc[0], 'rooms' => $rooms[0], 'sort_order' => $i,
                'is_published' => $i < 2, // dubleks tipleri şimdilik yayında değil; admin panelinden açılabilir
                'translations' => $this->tr(['label' => $label, 'description' => $desc, 'rooms' => $rooms]),
            ]);
            $this->unitMedia($unit, $i + 1);
        }
    }

    /** Daireye özel render'lar; listede olmayan daireler ortak diziyi kullanır. */
    private const UNIT_RENDERS = [
        '1-1' => [
            'salon' => ['Salon', 'Living room', 'Гостиная'],
            'mutfak' => ['Amerikan mutfak', 'Open kitchen', 'Кухня'],
            'yatak-odasi' => ['Yatak odası', 'Bedroom', 'Спальня'],
            'yatak-odasi-2' => ['Yatak odası', 'Bedroom', 'Спальня'],
            'giris' => ['Giriş', 'Entrance', 'Прихожая'],
            'banyo' => ['Banyo', 'Bathroom', 'Ванная'],
            'wc' => ['Duş ve WC', 'Shower and WC', 'Душ и санузел'],
        ],
        '2-1' => [
            'salon' => ['Salon', 'Living room', 'Гостиная'],
            'salon-2' => ['Salon ve Amerikan mutfak', 'Living room and open kitchen', 'Гостиная и кухня-студия'],
            'ebeveyn-yatak-odasi' => ['Ebeveyn yatak odası', 'Master bedroom', 'Главная спальня'],
            'ebeveyn-yatak-odasi-2' => ['Ebeveyn yatak odası', 'Master bedroom', 'Главная спальня'],
            'ebeveyn-banyo' => ['Ebeveyn banyosu', 'En-suite bathroom', 'Мастер-санузел'],
            'genel-banyo' => ['Genel banyo', 'Main bathroom', 'Общая ванная'],
            'genc-odasi' => ['Genç odası', 'Youth room', 'Молодёжная комната'],
        ],
    ];

    /** Repodaki 3D model ve render'ları bağlar: storage/app/public/uploads/units/{klasör}/ */
    private function unitMedia(UnitType $unit, int $folder): void
    {
        $prefix = "uploads/units/$folder/{$unit->slug}";
        $unit->update(['model_3d' => "$prefix-model.glb"]);

        $renders = self::UNIT_RENDERS[$unit->slug] ?? [
            'salon' => ['Salon', 'Living room', 'Гостиная'],
            'mutfak' => ['Amerikan mutfak', 'Open kitchen', 'Кухня'],
            'giris' => ['Giriş', 'Entrance', 'Прихожая'],
            'banyo' => ['Banyo', 'Bathroom', 'Ванная'],
            'wc' => ['WC', 'Guest WC', 'Санузел'],
            'yatak-odasi' => ['Yatak odası', 'Bedroom', 'Спальня'],
            'yatak-odasi-2' => ['Yatak odası', 'Bedroom', 'Спальня'],
        ];
        foreach (array_keys($renders) as $order => $file) {
            $unit->media()->create([
                'kind' => 'render', 'path' => "$prefix-$file.png", 'caption' => $renders[$file][0], 'sort_order' => $order,
                'translations' => $this->tr(['caption' => $renders[$file]]),
            ]);
        }
    }

    private function gallery(): void
    {
        // Dosyalar: public/media/galeri/01.jpg … 10.jpg
        GalleryImage::query()->delete();
        $rows = [
            ['10', ['Panoramik görünüm', 'Panoramic view', 'Панорама комплекса']],
            ['05', ['Havuz ve peyzaj alanı', 'Pool and gardens', 'Бассейн и сад']],
            ['06', ['Açık & kapalı havuzlar', 'Outdoor & indoor pools', 'Открытый и крытый бассейны']],
            ['03', ['Kuşbakışı yerleşim', 'Aerial site plan', 'Вид с высоты']],
            ['02', ['Site girişi ve meydan', 'Entrance and plaza', 'Въезд и площадь']],
            ['04', ['Ticari cephe', 'Retail frontage', 'Торговый фасад']],
            ['07', ['Köşe blok ve mağazalar', 'Corner block and shops', 'Угловой корпус и магазины']],
            ['08', ['Cadde cephesi', 'Street frontage', 'Фасад вдоль улицы']],
            ['09', ['Ormanla çevrili yerleşim', 'Surrounded by forest', 'В окружении леса']],
            ['01', ['Kapalı otopark ve tenis kortu', 'Covered parking and tennis court', 'Крытый паркинг и теннисный корт']],
        ];
        foreach ($rows as $i => [$file, $caption]) {
            GalleryImage::create([
                'path' => "/media/galeri/$file.jpg", 'caption' => $caption[0], 'sort_order' => $i,
                'translations' => $this->tr(['caption' => $caption]),
            ]);
        }
    }

    private function amenities(): void
    {
        Amenity::query()->delete();
        $rows = [
            ['Açık yüzme havuzu', 'Outdoor pool', 'Открытый бассейн'],
            ['Çocuk havuzu', 'Children’s pool', 'Детский бассейн'],
            ['Kaydıraklı havuz', 'Pool with water slide', 'Бассейн с горкой'],
            ['Kapalı yüzme havuzu', 'Indoor pool', 'Крытый бассейн'],
            ['Sauna & hamam', 'Sauna & hammam', 'Сауна и хаммам'],
            ['Masaj ve dinlenme odaları', 'Massage & relaxation rooms', 'Массаж и зона отдыха'],
            ['Fitness salonu', 'Fitness centre', 'Фитнес-зал'],
            ['Çocuk oyun odası', 'Children’s playroom', 'Детская игровая'],
            ['Çocuk oyun parkı', 'Playground', 'Детская площадка'],
            ['Tenis kortu', 'Tennis court', 'Теннисный корт'],
            ['Basketbol sahası', 'Basketball court', 'Баскетбольная площадка'],
            ['Peyzajlı bahçeler', 'Landscaped gardens', 'Ландшафтный сад'],
            ['Açık & kapalı otopark', 'Outdoor & covered parking', 'Открытый и крытый паркинг'],
            ['7/24 güvenlik', '24/7 security', 'Охрана 24/7'],
            ['Jeneratör', 'Backup generator', 'Генератор'],
            ['Plaj servisi', 'Beach shuttle', 'Трансфер на пляж'],
            ['Kafe & lounge', 'Café & lounge', 'Кафе и лаунж'],
            ['Profesyonel site yönetimi', 'Professional management', 'Управляющая компания'],
        ];
        foreach ($rows as $i => $name) {
            Amenity::create(['name' => $name[0], 'sort_order' => $i, 'translations' => $this->tr(['name' => $name])]);
        }
    }

    private function construction(): void
    {
        ConstructionPhase::query()->delete();
        $done = ['Tamamlandı', 'Completed', 'Завершено'];
        $going = ['Devam ediyor', 'In progress', 'В процессе'];
        $rows = [
            [['Ruhsat & zemin etüdü', 'Permits & ground survey', 'Разрешения и геология'], 100, $done],
            [['Kazı & temel', 'Excavation & foundations', 'Котлован и фундамент'], 100, $done],
            [['Kaba inşaat', 'Structural frame', 'Каркас здания'], 85, $going],
            [['Cephe & çatı', 'Façade & roof', 'Фасад и кровля'], 40, $going],
            [['İnce işler', 'Interior finishing', 'Внутренняя отделка'], 15, ['Başladı', 'Started', 'Начато']],
            [['Peyzaj & sosyal alanlar', 'Landscaping & amenities', 'Благоустройство и инфраструктура'], 0, ['Planlandı', 'Planned', 'Запланировано']],
        ];
        foreach ($rows as $i => [$title, $percent, $note]) {
            ConstructionPhase::create([
                'title' => $title[0], 'percent' => $percent, 'note' => $note[0], 'sort_order' => $i,
                'translations' => $this->tr(['title' => $title, 'note' => $note]),
            ]);
        }
    }

    private function location(): void
    {
        LocationPoint::query()->delete();
        $rows = [
            [['Deniz', 'Sea', 'Море'], '100 m'],
            [['Mahmutlar merkezi', 'Mahmutlar centre', 'Центр Махмутлара'], '2,5 km'],
            [['Alanya merkezi', 'Alanya centre', 'Центр Алании'], '15 km'],
            [['Gazipaşa Havalimanı', 'Gazipaşa Airport', 'Аэропорт Газипаша'], '25 km'],
            [['Antalya Havalimanı', 'Antalya Airport', 'Аэропорт Анталии'], '140 km'],
        ];
        foreach ($rows as $i => [$name, $distance]) {
            LocationPoint::create([
                'name' => $name[0], 'distance' => $distance, 'sort_order' => $i,
                'translations' => $this->tr(['name' => $name]),
            ]);
        }
    }
}
