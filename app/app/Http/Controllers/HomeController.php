<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\ConstructionPhase;
use App\Models\GalleryImage;
use App\Models\Highlight;
use App\Models\LocationPoint;
use App\Models\Stat;
use App\Models\UnitType;
use Inertia\Inertia;
use Inertia\Response;

/** Perle Garden tek sayfalık proje sitesi (TR / EN / RU). */
class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $phases = ConstructionPhase::ordered()->get()->map->localized(['title', 'note'], ['percent']);

        $units = UnitType::published()->with('media')->ordered()->get();

        return Inertia::render('Home', [
            'stats' => Stat::orderBy('sort_order')->get()->map->localized(['label'], ['prefix', 'value', 'suffix']),
            'highlights' => Highlight::ordered()->get()->map->localized(['title', 'description']),
            'unitTypes' => $units->map->toCard(),
            'showcaseUnits' => $units->map->toDetail(),
            'totalUnits' => (int) UnitType::sum('unit_count'), // proje toplamı: yayında olmayan tipler de dahil
            'gallery' => GalleryImage::ordered()->get()->map(fn (GalleryImage $g) => [
                'id' => $g->id,
                'path' => media_url($g->path),
                'caption' => $g->t('caption'),
            ]),
            'amenities' => Amenity::ordered()->get()->map->t('name'),
            'phases' => $phases,
            'progress' => $phases->isEmpty() ? 0 : (int) round($phases->avg('percent')),
            'locationPoints' => LocationPoint::ordered()->get()->map->localized(['name'], ['distance']),
        ]);
    }
}
