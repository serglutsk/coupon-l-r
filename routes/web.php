<?php

declare(strict_types=1);

use App\Http\Controllers\Coupon\CouponController;
use App\Http\Controllers\Coupon\ValidateRuleController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::redirect('coupons/rule-builder', '/coupons/create')->name('coupons.rule-builder');
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::post('validate-rule', ValidateRuleController::class)->name('validate-rule');
});
require __DIR__.'/settings.php';
