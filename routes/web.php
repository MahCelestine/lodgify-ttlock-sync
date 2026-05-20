<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    
    Route::get('/dashboard', function () {
        return view('pages.index');
    })->name('dashboard');

});