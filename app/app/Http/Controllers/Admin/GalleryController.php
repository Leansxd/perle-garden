<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Ana sayfa galerisi. */
class GalleryController extends Controller
{
    use ManagesContent;

    public function index(): Response
    {
        return Inertia::render('Admin/Gallery', [
            'images' => GalleryImage::ordered()->get()->map(fn (GalleryImage $g) => $this->toForm($g, ['caption']) + ['url' => media_url($g->path)]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:20480']], [], ['file' => 'görsel']);
        $name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

        GalleryImage::create([
            'path' => $this->storeUpload($request->file('file'), 'gallery'),
            'caption' => $name,
            'sort_order' => (GalleryImage::max('sort_order') ?? 0) + 1,
        ]);

        return back();
    }

    public function update(Request $request, GalleryImage $image): RedirectResponse
    {
        $data = $request->validate($this->transRules('caption', true, 160), [], ['caption.tr' => 'açıklama']);
        $image->update($this->fromForm($data, ['caption'], [], $image->translations));

        return back()->with('success', 'Görsel bilgisi kaydedildi.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            GalleryImage::whereKey($id)->update(['sort_order' => $i]);
        }

        return back();
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        $this->deleteUpload($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }
}
