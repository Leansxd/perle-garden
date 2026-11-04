<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\ConstructionPhase;
use App\Models\Highlight;
use App\Models\LocationPoint;
use App\Models\Stat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Basit liste içerikleri için tek bir genel düzenleyici:
 * sosyal olanaklar, öne çıkanlar, inşaat aşamaları, konum mesafeleri, rakamlar.
 * Liste bir bütün olarak kaydedilir (ekle / düzenle / sil / sırala).
 */
class ContentController extends Controller
{
    use ManagesContent;

    /**
     * type => [model, başlık, açıklama, alanlar]
     * alan: [ad, etiket, tür (text|textarea|number), çevrilebilir?]
     */
    private function types(): array
    {
        return [
            'olanaklar' => [Amenity::class, 'Sosyal olanaklar', 'Yaşam bölümünde ve kayan yazı bantlarında görünür.', [
                ['name', 'Olanak', 'text', true],
            ]],
            'one-cikanlar' => [Highlight::class, 'Öne çıkan özellikler', 'Proje tanıtım bölümündeki 4 kutu.', [
                ['title', 'Başlık', 'text', true],
                ['description', 'Açıklama', 'text', true],
            ]],
            'insaat' => [ConstructionPhase::class, 'İnşaat aşamaları', 'İlerleme çubukları. Genel yüzde, aşamaların ortalamasıdır.', [
                ['title', 'Aşama', 'text', true],
                ['note', 'Durum notu', 'text', true],
                ['percent', 'Yüzde (0-100)', 'number', false],
            ]],
            'konum' => [LocationPoint::class, 'Konum mesafeleri', 'Konum bölümündeki "yer — mesafe" satırları.', [
                ['name', 'Yer', 'text', true],
                ['distance', 'Mesafe', 'text', false],
            ]],
            'rakamlar' => [Stat::class, 'Rakamlar', 'Hero altındaki 4 rakam (sayarak artar).', [
                ['value', 'Sayı', 'number', false],
                ['suffix', 'Birim (m, m² …)', 'text', false],
                ['label', 'Açıklama', 'text', true],
            ]],
        ];
    }

    private function resolve(string $type): array
    {
        abort_unless(isset($this->types()[$type]), 404);

        return $this->types()[$type];
    }

    public function edit(string $type): Response
    {
        [$model, $title, $help, $fields] = $this->resolve($type);
        $trans = collect($fields)->where(3, true)->pluck(0)->all();
        $plain = collect($fields)->where(3, false)->pluck(0)->all();

        return Inertia::render('Admin/Content', [
            'type' => $type,
            'title' => $title,
            'help' => $help,
            'fields' => collect($fields)->map(fn ($f) => ['name' => $f[0], 'label' => $f[1], 'input' => $f[2], 'translatable' => $f[3]]),
            'items' => $model::orderBy('sort_order')->orderBy('id')->get()->map(fn ($m) => $this->toForm($m, $trans, $plain)),
            'types' => collect($this->types())->map(fn ($t, $k) => ['key' => $k, 'title' => $t[1]])->values(),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        [$model, $title, , $fields] = $this->resolve($type);
        $trans = collect($fields)->where(3, true)->pluck(0)->all();

        $rules = ['items' => ['present', 'array', 'max:100'], 'items.*.id' => ['nullable', 'integer']];
        foreach ($fields as [$name, $label, $input, $isTrans]) {
            if ($isTrans) {
                $rules["items.*.$name"] = ['array'];
                $rules["items.*.$name.tr"] = ['required', 'string', 'max:300'];
                $rules["items.*.$name.en"] = ['nullable', 'string', 'max:300'];
                $rules["items.*.$name.ru"] = ['nullable', 'string', 'max:300'];
            } else {
                $rules["items.*.$name"] = $input === 'number'
                    ? ['required', 'integer', 'min:0', $name === 'percent' ? 'max:100' : 'max:99999999']
                    : ['nullable', 'string', 'max:60'];
            }
        }
        $items = $request->validate($rules, [], ['items.*.*.tr' => 'Türkçe metin'])['items'];

        DB::transaction(function () use ($items, $model, $trans) {
            $keep = [];
            foreach ($items as $i => $item) {
                $existing = isset($item['id']) ? $model::find($item['id']) : null;
                $data = $this->fromForm(collect($item)->except('id')->all(), $trans, [], $existing?->translations);
                $data['sort_order'] = $i;
                $row = $existing ? tap($existing)->update($data) : $model::create($data);
                $keep[] = $row->getKey();
            }
            $model::whereNotIn('id', $keep)->delete();
        });

        return back()->with('success', "$title kaydedildi.");
    }
}
