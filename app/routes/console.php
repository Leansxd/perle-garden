<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/*
 * Admin kullanıcısı oluşturur ya da şifresini değiştirir:
 *   php artisan admin:user ornek@firma.com
 * Şifre güvenli biçimde (ekranda görünmeden) sorulur.
 */
Artisan::command('admin:user {email} {--name=Yönetici}', function (string $email) {
    $password = $this->secret('Yeni şifre (en az 8 karakter)');
    if (strlen((string) $password) < 8) {
        $this->error('Şifre en az 8 karakter olmalıdır.');

        return 1;
    }

    $user = User::updateOrCreate(['email' => $email], [
        'name' => $this->option('name'),
        'password' => Hash::make($password),
    ]);

    $this->info(($user->wasRecentlyCreated ? 'Admin oluşturuldu: ' : 'Şifre güncellendi: ').$email);
})->purpose('Admin paneli kullanıcısı oluştur / şifre değiştir');
