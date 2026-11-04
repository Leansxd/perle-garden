<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\GalleryImage;
use App\Models\UnitMedia;
use App\Models\UnitType;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'counts' => [
                'units' => UnitType::count(),
                'media' => UnitMedia::count(),
                'gallery' => GalleryImage::count(),
                'messages' => ContactMessage::count(),
                'unread' => ContactMessage::whereNull('read_at')->count(),
            ],
            'latest' => ContactMessage::latest()->take(6)->get(['id', 'name', 'phone', 'unit_type', 'locale', 'read_at', 'created_at']),
        ]);
    }
}
