<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('plugin-hello-world::index');
})->name('plugin.hello-world.index');
