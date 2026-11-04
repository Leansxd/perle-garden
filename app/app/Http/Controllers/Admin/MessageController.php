<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Sitedeki bilgi talep formundan gelen mesajlar. */
class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->query('filtre');

        return Inertia::render('Admin/Messages', [
            'messages' => ContactMessage::query()
                ->when($filter === 'okunmamis', fn ($q) => $q->whereNull('read_at'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filter' => $filter,
            'unread' => ContactMessage::whereNull('read_at')->count(),
        ]);
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back();
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Mesaj silindi.');
    }
}
