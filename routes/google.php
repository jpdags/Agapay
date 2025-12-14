<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\GoogleCalendarController;

// Google OAuth Routes
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/google/select-role', [GoogleLoginController::class, 'showRoleSelection'])->name('google.role.select');
Route::post('/auth/google/select-role', [GoogleLoginController::class, 'handleRoleSelection'])->name('google.role.submit');

// Google Calendar Routes
Route::get('/auth/google/calendar/redirect', [GoogleCalendarController::class, 'redirect'])->name('google.calendar.redirect');
Route::get('/auth/google/calendar/callback', [GoogleCalendarController::class, 'callback'])->name('google.calendar.callback');
Route::post('/auth/google/calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.calendar.disconnect');
Route::post('/calendar/sync/{orderId}', [GoogleCalendarController::class, 'syncBooking'])->name('calendar.sync');

