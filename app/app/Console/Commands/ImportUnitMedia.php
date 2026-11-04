<?php

namespace App\Console\Commands;

use App\Models\UnitMedia;
use App\Models\UnitType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bir klasördeki görselleri daire tipine toplu ekler; isteğe bağlı 3D model ve kapak.
 *
 *   php artisan units:import 1-1 "C:\Downloads\1+1\RENDER" --kind=render
 *   php artisan units:import 1-1 --model="C:\Downloads\model.glb"
 *   php artisan units:import 2-1 "C:\Foto" --kind=photo --replace
 *
 * Açıklamalar dosya adından üretilir ("Yatak odası2.png" → "Yatak odası 2") ve
 * bilinen oda adları İngilizce / Rusçaya çevrilir. Admin panelinden düzenlenebilir.
 */
class ImportUnitMedia extends Command
{
    protected $signature = 'units:import
        {slug : Daire tipinin sayfa adresi (ör. 1-1, 2-1-dubleks)}
        {folder? : Görsellerin bulunduğu klasör}
        {--kind=render : render | photo | plan}
        {--model= : .glb / .gltf 3D model dosyası}
        {--cover= : Kapak görseli olarak kullanılacak dosya}
        {--replace : Bu türdeki mevcut görselleri önce sil}';

    protected $description = 'Daire tipine klasörden toplu görsel / 3D model ekler';

    /** Sık kullanılan oda adları: tr => [en, ru] (uzun ifadeler önce) */
    private array $rooms = [
        'ebeveyn banyosu' => ['En-suite bathroom', 'Ванная при спальне'],
        'ebeveyn yatak odası' => ['Master bedroom', 'Главная спальня'],
        'çocuk odası' => ['Children’s room', 'Детская'],
        'yatak odası' => ['Bedroom', 'Спальня'],
        'oturma odası' => ['Sitting room', 'Гостиная'],
        'genel banyo' => ['Bathroom', 'Ванная'],
        'genel wc' => ['Guest WC', 'Гостевой санузел'],
        'misafir wc' => ['Guest WC', 'Гостевой санузел'],
        'çalışma odası' => ['Study', 'Кабинет'],
        'giyinme odası' => ['Walk-in closet', 'Гардеробная'],
        'salon' => ['Living room', 'Гостиная'],
        'mutfak' => ['Kitchen', 'Кухня'],
        'giriş' => ['Entrance hall', 'Прихожая'],
        'antre' => ['Entrance hall', 'Прихожая'],
        'koridor' => ['Hallway', 'Коридор'],
        'banyo' => ['Bathroom', 'Ванная'],
        'wc' => ['WC', 'Санузел'],
        'balkon' => ['Balcony', 'Балкон'],
        'teras' => ['Terrace', 'Терраса'],
        'bahçe' => ['Garden', 'Сад'],
        'yemek' => ['Dining area', 'Столовая'],
        'plan' => ['Floor plan', 'Планировка'],
        'alt kat' => ['Lower floor', 'Нижний этаж'],
        'üst kat' => ['Upper floor', 'Верхний этаж'],
    ];

    /** Klasör içi sıralama önceliği (girişten yatak odasına doğru bir gezinti) */
    private array $order = ['giriş', 'antre', 'koridor', 'salon', 'oturma', 'mutfak', 'yemek', 'yatak', 'çocuk', 'çalışma', 'giyinme', 'banyo', 'wc', 'balkon', 'teras', 'bahçe'];

    public function handle(): int
    {
        $unit = UnitType::where('slug', $this->argument('slug'))->first();
        if (! $unit) {
            $this->error('Daire tipi bulunamadı: '.$this->argument('slug').' — mevcutlar: '.UnitType::pluck('slug')->join(', '));

            return self::FAILURE;
        }
        $kind = $this->option('kind');
        if (! in_array($kind, UnitMedia::KINDS, true)) {
            $this->error('--kind şunlardan biri olmalı: '.implode(', ', UnitMedia::KINDS));

            return self::FAILURE;
        }

        if ($folder = $this->argument('folder')) {
            if (! is_dir($folder)) {
                $this->error("Klasör bulunamadı: $folder");

                return self::FAILURE;
            }
            if ($this->option('replace')) {
                foreach ($unit->media()->where('kind', $kind)->get() as $m) {
                    $this->deleteIfUploaded($m->path);
                    $m->delete();
                }
            }
            $this->importFolder($unit, $folder, $kind);
        }

        if ($model = $this->option('model')) {
            if (! is_file($model) || ! in_array(strtolower(pathinfo($model, PATHINFO_EXTENSION)), ['glb', 'gltf'], true)) {
                $this->error("3D model .glb / .gltf olmalı: $model");

                return self::FAILURE;
            }
            $this->deleteIfUploaded($unit->model_3d);
            $unit->model_3d = $this->copy($model, $unit, Str::slug($unit->title()).'-3d');
            $unit->save();
            $this->info('3D model eklendi ('.round(filesize($model) / 1048576, 1).' MB)');
        }

        if ($cover = $this->option('cover')) {
            $this->deleteIfUploaded($unit->cover_image);
            $unit->cover_image = $this->copy($cover, $unit, 'kapak');
            $unit->save();
            $this->info('Kapak görseli eklendi');
        }

        $this->info("Tamam → /daire/{$unit->slug}");

        return self::SUCCESS;
    }

    private function importFolder(UnitType $unit, string $folder, string $kind): void
    {
        $files = collect(scandir($folder))
            ->filter(fn ($f) => preg_match('/\.(jpe?g|png|webp)$/i', $f))
            ->sortBy(fn ($f) => [$this->rank($f), Str::lower($f)])
            ->values();

        // Aynı oda adının kaç dosyada geçtiği (tekse numara gösterilmez)
        $this->baseCounts = $files->map(fn ($f) => $this->normalize($this->stripNumber(pathinfo($f, PATHINFO_FILENAME))))->countBy()->all();

        $start = ($unit->media()->where('kind', $kind)->max('sort_order') ?? -1) + 1;
        foreach ($files as $i => $file) {
            [$tr, $en, $ru] = $this->caption($file);
            $unit->media()->create([
                'kind' => $kind,
                'path' => $this->copy($folder.DIRECTORY_SEPARATOR.$file, $unit, Str::slug(pathinfo($file, PATHINFO_FILENAME))),
                'caption' => $tr,
                'translations' => ['en' => ['caption' => $en], 'ru' => ['caption' => $ru]],
                'sort_order' => $start + $i,
            ]);
            $this->line("  + $file  →  $tr / $en / $ru");
        }
        $this->info($files->count()." görsel eklendi ($kind)");
    }

    private function rank(string $file): int
    {
        $name = $this->normalize(pathinfo($file, PATHINFO_FILENAME));
        foreach ($this->order as $i => $key) {
            if (str_contains($name, $key)) {
                return $i;
            }
        }

        return 99;
    }

    /** "Yatak odası2.png" → ["Yatak odası 2", "Bedroom 2", "Спальня 2"] */
    private function caption(string $file): array
    {
        $raw = pathinfo($file, PATHINFO_FILENAME);
        $raw = trim(preg_replace('/[_-]+/', ' ', $raw));
        preg_match('/\s*(\d+)$/', $raw, $m);
        $num = $m[1] ?? null;
        $base = trim(preg_replace('/\s*\d+$/', '', $raw));
        $lower = $this->normalize($base);

        $tr = mb_convert_case(mb_substr($lower, 0, 1), MB_CASE_UPPER, 'UTF-8').mb_substr($lower, 1);
        $tr = preg_replace('/\bwc\b/iu', 'WC', $tr);
        $en = $ru = $tr;
        foreach ($this->rooms as $key => [$e, $r]) {
            if ($lower === $key || str_starts_with($lower, $key)) {
                [$en, $ru] = [$e, $r];
                break;
            }
        }
        // Aynı odadan tek dosya varsa numarayı gösterme ("wc2" → "WC")
        $suffix = $num && ($this->baseCounts[$lower] ?? 1) > 1 ? " $num" : '';

        return [$tr.$suffix, $en.$suffix, $ru.$suffix];
    }

    /** Türkçe uyumlu küçük harf: "GİRİŞ" → "giriş" */
    private function normalize(string $text): string
    {
        $text = trim(preg_replace('/[_-]+/', ' ', $text));

        return mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $text), 'UTF-8');
    }

    /** Sondaki numarayı kaldırır: "Yatak odası2" → "Yatak odası" */
    private function stripNumber(string $text): string
    {
        return trim(preg_replace('/\s*\d+$/', '', $text));
    }

    private array $baseCounts = [];

    private function copy(string $source, UnitType $unit, string $name): string
    {
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = "uploads/units/{$unit->id}/".($name ?: 'dosya').'-'.Str::lower(Str::random(6)).".$ext";
        Storage::disk('public')->put($path, fopen($source, 'r'));

        return $path;
    }

    private function deleteIfUploaded(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
