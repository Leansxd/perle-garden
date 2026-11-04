<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin paneli — /admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('giris', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('giris', [Admin\AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::post('cikis', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // Daire tipleri + medya
        Route::post('daireler/sirala', [Admin\UnitTypeController::class, 'reorder'])->name('units.reorder');
        Route::resource('daireler', Admin\UnitTypeController::class)
            ->parameters(['daireler' => 'unit'])
            ->except('show')
            ->names('units');
        Route::post('daireler/{unit}/medya', [Admin\UnitMediaController::class, 'store'])->name('units.media.store');
        Route::post('daireler/{unit}/medya/sirala', [Admin\UnitMediaController::class, 'reorder'])->name('units.media.reorder');
        Route::put('medya/{media}', [Admin\UnitMediaController::class, 'update'])->name('media.update');
        Route::delete('medya/{media}', [Admin\UnitMediaController::class, 'destroy'])->name('media.destroy');

        // Galeri
        Route::get('galeri', [Admin\GalleryController::class, 'index'])->name('gallery.index');
        Route::post('galeri', [Admin\GalleryController::class, 'store'])->name('gallery.store');
        Route::post('galeri/sirala', [Admin\GalleryController::class, 'reorder'])->name('gallery.reorder');
        Route::put('galeri/{image}', [Admin\GalleryController::class, 'update'])->name('gallery.update');
        Route::delete('galeri/{image}', [Admin\GalleryController::class, 'destroy'])->name('gallery.destroy');

        // Liste içerikleri (olanaklar, inşaat aşamaları, konum, rakamlar, öne çıkanlar)
        Route::get('icerik/{type}', [Admin\ContentController::class, 'edit'])->name('content.edit');
        Route::put('icerik/{type}', [Admin\ContentController::class, 'update'])->name('content.update');

        // Ayarlar
        Route::get('ayarlar', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::post('ayarlar', [Admin\SettingController::class, 'update'])->name('settings.update');

        // Mesajlar
        Route::get('mesajlar', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::patch('mesajlar/{message}/okundu', [Admin\MessageController::class, 'toggleRead'])->name('messages.read');
        Route::delete('mesajlar/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Site — Türkçe: /   ·   İngilizce: /en   ·   Rusça: /ru
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/daire/{slug}', UnitController::class)->name('unit');

Route::prefix('{locale}')->whereIn('locale', ['en', 'ru'])->group(function () {
    Route::get('/', HomeController::class)->name('home.locale');
    Route::get('/daire/{slug}', UnitController::class)->name('unit.locale');
});

Route::post('/bilgi-talebi', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');
