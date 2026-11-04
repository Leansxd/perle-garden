<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/** Site ayarları: metinler (TR/EN/RU), görseller, video, iletişim bilgileri. */
class SettingController extends Controller
{
    use ManagesContent;

    /**
     * Grup => [anahtar => [etiket, tür, çevrilebilir?, ipucu]]
     * tür: text | textarea | image | video | url
     */
    private function schema(): array
    {
        return [
            'Genel' => [
                'company_name' => ['Proje adı', 'text', false, null],
                'company_tagline' => ['Alt başlık', 'text', false, 'Logodaki/menüdeki küçük yazı (ör. Residence)'],
            ],
            'Giriş (Hero)' => [
                'hero_eyebrow' => ['Üst etiket', 'text', true, 'Ör. Kargıcak · Alanya · Antalya'],
                'hero_title' => ['Ana başlık', 'text', true, '*yıldız arası* kelimeler altın italik olur'],
                'hero_subtitle' => ['Alt metin', 'textarea', true, null],
                'hero_video' => ['Arka plan videosu', 'video', false, 'MP4 · sessiz döngüde oynar · önerilen < 15 MB'],
                'hero_poster' => ['Arka plan görseli', 'image', false, 'Video yokken / yüklenirken gösterilir'],
            ],
            'Tanıtım' => [
                'intro_title' => ['Tanıtım başlığı', 'text', true, '*yıldız arası* kelimeler altın italik olur'],
                'intro_text' => ['Tanıtım metni', 'textarea', true, null],
                'intro_image' => ['Tanıtım görseli (büyük)', 'image', false, null],
                'intro_image_2' => ['Tanıtım görseli (küçük)', 'image', false, null],
                'expand_image' => ['Tam ekran görsel', 'image', false, 'Kaydırınca genişleyen bölüm'],
                'life_image' => ['Sosyal yaşam görseli', 'image', false, null],
            ],
            'Proje bilgileri' => [
                'location_name' => ['Konum', 'text', true, null],
                'delivery_date' => ['Tahmini teslim', 'text', true, 'Ör. Haziran 2027'],
                'floors' => ['Kat sayısı', 'text', false, null],
                'unit_size_range' => ['Daire büyüklükleri', 'text', false, 'Ör. 58 – 165 m²'],
            ],
            'İletişim' => [
                'phone' => ['Telefon', 'text', false, null],
                'whatsapp' => ['WhatsApp numarası', 'text', false, 'Ülke koduyla, boşluksuz: 905xxxxxxxxx'],
                'email' => ['E-posta', 'text', false, null],
                'address' => ['Satış ofisi adresi', 'text', true, null],
                'working_hours' => ['Çalışma saatleri', 'text', true, null],
                'map_embed' => ['Google Maps yerleştirme adresi', 'url', false, 'Google Maps → Paylaş → Haritayı yerleştir → iframe içindeki src adresi'],
                'instagram' => ['Instagram', 'url', false, null],
                'youtube' => ['YouTube', 'url', false, null],
            ],
        ];
    }

    private function flat(): array
    {
        return array_merge(...array_values($this->schema()));
    }

    public function edit(): Response
    {
        $all = Setting::query()->pluck('value', 'key');
        $values = [];
        foreach ($this->flat() as $key => [, $type, $trans]) {
            $values[$key] = $trans
                ? ['tr' => $all[$key] ?? null, 'en' => $all["$key@en"] ?? null, 'ru' => $all["$key@ru"] ?? null]
                : $all[$key] ?? null;
            if (in_array($type, ['image', 'video'], true)) {
                $values[$key] = ['path' => $all[$key] ?? null, 'url' => media_url($all[$key] ?? null)];
            }
        }

        return Inertia::render('Admin/Settings', [
            'schema' => collect($this->schema())->map(fn ($fields, $group) => [
                'group' => $group,
                'fields' => collect($fields)->map(fn ($f, $k) => ['key' => $k, 'label' => $f[0], 'type' => $f[1], 'translatable' => $f[2], 'hint' => $f[3]])->values(),
            ])->values(),
            'values' => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach ($this->flat() as $key => [$label, $type, $trans]) {
            match (true) {
                $trans => $rules += ["values.$key.tr" => ['nullable', 'string', 'max:2000'], "values.$key.en" => ['nullable', 'string', 'max:2000'], "values.$key.ru" => ['nullable', 'string', 'max:2000']],
                $type === 'image' => $rules += ["files.$key" => ['nullable', 'image', 'max:20480'], "remove.$key" => ['nullable', 'boolean']],
                $type === 'video' => $rules += ["files.$key" => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:102400'], "remove.$key" => ['nullable', 'boolean']],
                $type === 'url' => $rules += ["values.$key" => ['nullable', 'url', 'max:2000']],
                default => $rules += ["values.$key" => ['nullable', 'string', 'max:500']],
            };
        }
        $data = $request->validate($rules, [], ['files.*' => 'dosya', 'values.*' => 'değer']);

        $set = fn (string $k, $v) => Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        foreach ($this->flat() as $key => [, $type, $trans]) {
            if ($trans) {
                foreach (['tr' => $key, 'en' => "$key@en", 'ru' => "$key@ru"] as $l => $k) {
                    $set($k, Arr::get($data, "values.$key.$l"));
                }
            } elseif (in_array($type, ['image', 'video'], true)) {
                $current = Setting::where('key', $key)->value('value');
                if ($request->hasFile("files.$key")) {
                    $this->deleteUpload($current);
                    $set($key, $this->storeUpload($request->file("files.$key"), 'site'));
                } elseif (Arr::get($data, "remove.$key")) {
                    $this->deleteUpload($current);
                    $set($key, null);
                }
            } elseif (array_key_exists($key, $data['values'] ?? [])) {
                $set($key, $data['values'][$key]);
            }
        }
        Setting::flush();

        return back()->with('success', 'Ayarlar kaydedildi.');
    }
}
