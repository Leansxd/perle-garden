<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\UnitMedia;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Daire tipine fotoğraf / 3D render / kat planı yükleme, düzenleme, sıralama. */
class UnitMediaController extends Controller
{
    use ManagesContent;

    /** Tek dosya yükler (panel birden çok dosyayı sırayla gönderir). */
    public function store(Request $request, UnitType $unit): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(UnitMedia::KINDS)],
            'file' => ['required', 'image', 'max:20480'],
        ], [], ['file' => 'görsel']);

        $unit->media()->create([
            'kind' => $data['kind'],
            'path' => $this->storeUpload($request->file('file'), "units/{$unit->id}"),
            'sort_order' => ($unit->media()->where('kind', $data['kind'])->max('sort_order') ?? 0) + 1,
        ]);

        return back();
    }

    public function update(Request $request, UnitMedia $media): RedirectResponse
    {
        $data = $request->validate([
            ...$this->transRules('caption', false, 160),
            'kind' => ['sometimes', Rule::in(UnitMedia::KINDS)],
        ]);
        $media->update($this->fromForm($data, ['caption'], [], $media->translations));

        return back()->with('success', 'Görsel bilgisi kaydedildi.');
    }

    public function reorder(Request $request, UnitType $unit): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            $unit->media()->whereKey($id)->update(['sort_order' => $i]);
        }

        return back();
    }

    public function destroy(UnitMedia $media): RedirectResponse
    {
        $this->deleteUpload($media->path);
        $media->delete();

        return back()->with('success', 'Görsel silindi.');
    }
}
