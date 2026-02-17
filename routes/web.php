<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

Route::get('/', [WhatsAppController::class, 'index'])->name('dashboard');
Route::post('/messages', [WhatsAppController::class, 'store'])->name('messages.store');
