<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية
Route::get('/', function () {
    return view('welcome');
})->name('home');

// لوحة التحكم
Route::get('/dashboard', function () {
    return view('admin.dashboard');
})->name('dashboard');

// الإعلانات
Route::get('/ads', function () {
    return view('admin.ads');
})->name('ads');

// الوكلاء
Route::get('/agents', function () {
    return view('admin.agents');
})->name('agents');

// التصنيفات
Route::get('/categories', function () {
    return view('admin.categories');
})->name('categories');

// الموزعين
Route::get('/distributors', function () {
    return view('admin.distributors');
})->name('distributors');

// المنتجات
Route::get('/products', function () {
    return view('admin.products');
})->name('products');

// الشاشات
Route::get('/screens', function () {
    return view('admin.screens');
})->name('screens');

// الإعدادات
Route::get('/settings', function () {
    return view('admin.settings');
})->name('settings');

// المحلات
Route::get('/shops', function () {
    return view('admin.shops');
})->name('shops');

// المستخدمين
Route::get('/users', function () {
    return view('admin.users');
})->name('users');
