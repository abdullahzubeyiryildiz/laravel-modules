<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
    Route::post('/datatable', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'datatable'])->name('datatable');
    Route::post('/', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
    Route::get('/{user}', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
    Route::put('/{user}', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
    Route::post('/{user}/toggle-status', [\Modules\AuthModule\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('toggle-status');
});
