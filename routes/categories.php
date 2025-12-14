<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

// Categories Page (Public)
Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
Route::get('/products', [CategoryController::class, 'products'])->name('categories.products');
Route::get('/services', [CategoryController::class, 'services'])->name('categories.services');

