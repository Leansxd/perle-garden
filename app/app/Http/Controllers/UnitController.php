<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Inertia\Inertia;
use Inertia\Response;

/** Daire tipi detay sayfası: /daire/{slug}, /en/daire/{slug}, /ru/daire/{slug} */
class UnitController extends Controller
{
    public function __invoke(?string $locale = null, ?string $slug = null): Response
    {
        // Türkçe rotada {locale} parametresi yok: tek parametre slug'dır
        $slug ??= $locale;
        $unit = UnitType::published()->with('media')->where('slug', $slug)->firstOrFail();

        $others = UnitType::published()->with('media')->ordered()->whereKeyNot($unit->id)->get()->map->toCard();

        return Inertia::render('Unit', [
            'unit' => $unit->toDetail(),
            'others' => $others,
            'allUnits' => UnitType::published()->ordered()->get()->map(fn ($u) => $u->title()),
        ]);
    }
}
