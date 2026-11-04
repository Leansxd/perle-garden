<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\UnitMedia;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnitTypeController extends Controller
{
    use ManagesContent;

    private array $translatable = ['label', 'description', 'rooms'];

    public function index(): Response
    {
        return Inertia::render('Admin/Units/Index', [
            'units' => UnitType::ordered()->withCount('media')->get()->map(fn (UnitType $u) => [
                'id' => $u->id,
                'title' => $u->title(),
                'slug' => $u->slug,
                'unit_count' => $u->unit_count,
                'sizes' => $u->sizes,
                'is_published' => $u->is_published,
                'media_count' => $u->media_count,
                'cover' => $u->toCard()['cover_image'],
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Units/Edit', ['unit' => null, 'media' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = UnitType::create($this->validated($request) + ['sort_order' => (UnitType::max('sort_order') ?? 0) + 1]);
        $this->handleFiles($request, $unit);

        return redirect()->route('admin.units.edit', $unit)->with('success', 'Daire tipi oluşturuldu. Şimdi fotoğraf ve 3D görselleri ekleyebilirsiniz.');
    }

    public function edit(UnitType $unit): Response
    {
        $form = $this->toForm($unit, ['label', 'description'], [
            'name', 'slug', 'unit_count', 'sizes', 'tour_url', 'video_url', 'is_published', 'sort_order',
        ]);
        // Oda listesi: [{tr,en,ru}, ...]
        $form['rooms'] = collect($unit->rooms ?? [])->map(fn ($r, $i) => [
            'tr' => $r,
            'en' => $unit->translations['en']['rooms'][$i] ?? null,
            'ru' => $unit->translations['ru']['rooms'][$i] ?? null,
        ])->values();
        $form['cover_image'] = media_url($unit->cover_image);
        $form['model_3d'] = media_url($unit->model_3d);

        return Inertia::render('Admin/Units/Edit', [
            'unit' => $form,
            'media' => $unit->media->map(fn (UnitMedia $m) => $this->toForm($m, ['caption'], ['kind', 'sort_order']) + ['url' => $m->url()]),
            'publicUrl' => url('/daire/'.$unit->slug),
        ]);
    }

    public function update(Request $request, UnitType $unit): RedirectResponse
    {
        $unit->update($this->validated($request, $unit));
        $this->handleFiles($request, $unit);

        return back()->with('success', 'Değişiklikler kaydedildi.');
    }

    public function destroy(UnitType $unit): RedirectResponse
    {
        foreach ($unit->media as $m) {
            $this->deleteUpload($m->path);
        }
        $this->deleteUpload($unit->cover_image);
        $this->deleteUpload($unit->model_3d);
        $unit->delete();

        return redirect()->route('admin.units.index')->with('success', 'Daire tipi silindi.');
    }

    /** Sıralama: ids dizisindeki sıraya göre */
    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            UnitType::whereKey($id)->update(['sort_order' => $i]);
        }

        return back()->with('success', 'Sıralama kaydedildi.');
    }

    private function validated(Request $request, ?UnitType $unit = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', Rule::unique('unit_types', 'slug')->ignore($unit?->id)],
            'unit_count' => ['required', 'integer', 'min:0', 'max:9999'],
            'sizes' => ['required', 'string', 'max:120'],
            'tour_url' => ['nullable', 'url', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'is_published' => ['boolean'],
            ...$this->transRules('label', false, 60),
            ...$this->transRules('description', false, 1000),
            'rooms' => ['nullable', 'array', 'max:20'],
            'rooms.*.tr' => ['nullable', 'string', 'max:120'],
            'rooms.*.en' => ['nullable', 'string', 'max:120'],
            'rooms.*.ru' => ['nullable', 'string', 'max:120'],
            'cover_file' => ['nullable', 'image', 'max:20480'],
            'model_file' => ['nullable', 'file', 'max:102400', function ($attr, $file, $fail) {
                if (! in_array(strtolower($file->getClientOriginalExtension()), ['glb', 'gltf'], true)) {
                    $fail('3D model .glb ya da .gltf formatında olmalıdır.');
                }
            }],
            'remove_cover' => ['boolean'],
            'remove_model' => ['boolean'],
        ], [], [
            'name' => 'oda düzeni', 'unit_count' => 'daire adedi', 'sizes' => 'metrekareler',
            'label.tr' => 'etiket', 'description.tr' => 'açıklama', 'cover_file' => 'kapak görseli', 'model_file' => '3D model',
            'tour_url' => 'sanal tur bağlantısı', 'video_url' => 'video bağlantısı',
        ]);

        $data = $this->fromForm($data, $this->translatable, ['rooms'], $unit?->translations);
        $data['slug'] = $data['slug'] ?: null;

        return collect($data)->except(['cover_file', 'model_file', 'remove_cover', 'remove_model'])->all();
    }

    private function handleFiles(Request $request, UnitType $unit): void
    {
        if ($request->boolean('remove_cover')) {
            $this->deleteUpload($unit->cover_image);
            $unit->cover_image = null;
        }
        if ($request->boolean('remove_model')) {
            $this->deleteUpload($unit->model_3d);
            $unit->model_3d = null;
        }
        if ($request->hasFile('cover_file')) {
            $this->deleteUpload($unit->cover_image);
            $unit->cover_image = $this->storeUpload($request->file('cover_file'), "units/{$unit->id}");
        }
        if ($request->hasFile('model_file')) {
            $this->deleteUpload($unit->model_3d);
            $unit->model_3d = $this->storeUpload($request->file('model_file'), "units/{$unit->id}");
        }
        $unit->save();
    }
}
