<?php

/*
 * Geliştirme sunucusu yönlendiricisi (php -S için).
 * `php artisan serve` yüklemeyi 2 MB ile sınırlar; büyük fotoğraf / 3D model
 * yükleyebilmek için sunucuyu şöyle başlatın:
 *
 *   composer serve
 *   (php -d upload_max_filesize=100M -d post_max_size=210M -S 127.0.0.1:8000 -t public server.php)
 */
$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Var olan statik dosyaları (css, js, görsel) doğrudan sun
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
