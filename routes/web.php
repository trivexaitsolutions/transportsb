<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::get('/day', [VoucherController::class, 'day'])->name('day');
        Route::post('/save', [VoucherController::class, 'save'])->name('save');
        Route::get('/options', [VoucherController::class, 'options'])->name('options');
        Route::get('/{voucher}/payments/{type}', [VoucherController::class, 'payments'])->name('payments');
        Route::post('/{voucher}/payments/{type}', [VoucherController::class, 'storePayment'])->name('payments.store');
        Route::delete('/{voucher}/payments/{type}/{payment}', [VoucherController::class, 'deletePayment'])->name('payments.delete');
    });

    Route::prefix('masters')->name('masters.')->group(function () {
        Route::get('/{type}', [MasterController::class, 'index'])->name('index');
        Route::post('/{type}', [MasterController::class, 'store'])->name('store');
        Route::put('/{type}/{id}', [MasterController::class, 'update'])->name('update');
        Route::delete('/{type}/{id}', [MasterController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/vouchers', [ReportController::class, 'voucherRegister'])->name('vouchers');
        Route::get('/customer-ledger', [ReportController::class, 'customerLedger'])->name('customer-ledger');
        Route::get('/supplier-ledger', [ReportController::class, 'supplierLedger'])->name('supplier-ledger');
        Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
        Route::get('/profit', [ReportController::class, 'profit'])->name('profit');
        Route::get('/customer-bill/{voucher}', [ReportController::class, 'customerBill'])->name('customer-bill');
        Route::get('/customer-statement/{customer}', [ReportController::class, 'customerStatement'])->name('customer-statement');
        Route::get('/supplier-statement/{supplier}', [ReportController::class, 'supplierStatement'])->name('supplier-statement');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
