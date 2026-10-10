<?php

use App\Console\Commands\Rain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Random daily rain: 5-15 random hours at a random minute, recomputed every day.
try {
    $expression = Cache::get('schedule:expressions:rain');
} catch (\Throwable) {
    $expression = null;
}

if (!$expression) {
    $randomMinute = mt_rand(0, 59);

    $hourRange = range(1, 23);
    shuffle($hourRange);
    $randomHours = array_slice($hourRange, 0, mt_rand(5, 15));

    $expression = $randomMinute.' '.implode(',', $randomHours).' * * *';
    try {
        Cache::put('schedule:expressions:rain', $expression, Carbon::now()->endOfDay());
    } catch (\Throwable) {}
}
Schedule::command(Rain::class)->cron($expression);
