<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Dil SetLocale ara katmanında `locale` alanından ayarlanır
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:160'],
            'unit_type' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:3000'],
        ], [], [
            'name' => __('site.form.name'),
            'phone' => __('site.form.phone'),
            'email' => __('site.form.email'),
            'unit_type' => __('site.form.unit'),
            'message' => __('site.form.message'),
        ]);

        ContactMessage::create($data + ['ip' => $request->ip(), 'locale' => app()->getLocale()]);

        return back()->with('success', __('site.form.success'));
    }
}
