<?php

namespace App\Http\Middleware;

use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'site' => fn () => Setting::map(),
            'locale' => fn () => app()->getLocale(),
            // Dil bağlantıları aynı sayfanın diğer dildeki adresini gösterir (/daire/x → /en/daire/x)
            'locales' => function () use ($request) {
                $rest = preg_replace('#^/(en|ru)(?=/|$)#', '', '/'.ltrim($request->path(), '/'));
                $rest = $rest === '/' ? '' : rtrim($rest, '/');

                return collect(config('site.locales'))
                    ->map(fn ($l, $code) => $l + [
                        'code' => $code,
                        'url' => ($code === config('site.default_locale') ? '' : "/$code").$rest ?: '/',
                    ])
                    ->values();
            },
            'auth' => fn () => $request->user() ? ['name' => $request->user()->name, 'email' => $request->user()->email] : null,
            // Admin menüsündeki okunmamış mesaj rozeti
            'adminUnread' => fn () => $request->user() && $request->is('admin*') ? ContactMessage::whereNull('read_at')->count() : null,
            // Arayüz metinleri: lang/{tr,en,ru}/site.php
            'ui' => fn () => trans('site'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
