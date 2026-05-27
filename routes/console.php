<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\LockSyncServices;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planification du nettoyage automatique tous les jours à minuit
Schedule::call(function () {
    $service = app(LockSyncServices::class);
    
    $report = ['cleaned' => 0]; 
    
    $result = $service->cleanExpiredPasscodes($report);
    
    \Log::info("Nettoyage automatique des codes expiré effectué.", $result);
})->daily();