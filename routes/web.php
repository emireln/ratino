<?php

use Illuminate\Support\Facades\Route;

Route::get('/avatar/{hash}', function ($hash) {
    $size = 100;
    $icon = new \Jdenticon\Identicon();
    $icon->setValue($hash);
    $icon->setSize($size);

    $style = new \Jdenticon\IdenticonStyle();
    $style->setBackgroundColor('#21232a');
    $icon->setStyle($style);

    $icon->displayImage('png');
    return response('')->header('Content-Type', 'image/png');
});

Route::get('/{any?}', function () {
    // SPA shell built by the frontend/ Vite app (public/index.html)
    return response(file_get_contents(public_path('index.html')))->header('Content-Type', 'text/html');
})->where('any', '.*');
