<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\SoNumberSeriesController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('sale.orders.index') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::prefix('sale')->name('sale.')->group(function () {
        Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/options', [SalesOrderController::class, 'options'])->name('orders.options');
        Route::post('/orders', [SalesOrderController::class, 'store'])->name('orders.store');
        Route::put('/orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers/save-all', [VoucherController::class, 'saveAll'])->name('vouchers.save-all');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/{voucher}/attachments/{attachment}', [BillingController::class, 'voucherAttachment'])->name('vouchers.attachments.show');
        Route::get('/vouchers/{voucher}/supplier-payments', [VoucherController::class, 'payments'])->name('vouchers.payments');
        Route::post('/vouchers/{voucher}/supplier-payments', [VoucherController::class, 'storePayment'])->name('vouchers.payments.store');
        Route::delete('/vouchers/{voucher}/supplier-payments/{payment}', [VoucherController::class, 'destroyPayment'])->name('vouchers.payments.destroy');


        Route::get('/bills', [BillingController::class, 'index'])->name('billing.index');
        Route::get('/bills/data', [BillingController::class, 'data'])->name('billing.data');
        Route::get('/bills/so-options', [BillingController::class, 'soOptions'])->name('billing.so-options');
        Route::post('/bills', [BillingController::class, 'store'])->name('billing.store');
        Route::put('/bills/{invoice}', [BillingController::class, 'update'])->name('billing.update');
        Route::get('/bills/{invoice}/print', [BillingController::class, 'print'])->name('billing.print');
        Route::get('/bills/{invoice}/attachments/{attachment}', [BillingController::class, 'attachment'])->name('billing.attachments.show');
        Route::get('/bills/{invoice}/payments', [BillingController::class, 'payments'])->name('billing.payments');
        Route::post('/bills/{invoice}/payments', [BillingController::class, 'storePayment'])->name('billing.payments.store');
        Route::delete('/bills/{invoice}/payments/{payment}', [BillingController::class, 'destroyPayment'])->name('billing.payments.destroy');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/suppliers', [PaymentController::class, 'supplierIndex'])->name('suppliers.index');
        Route::post('/suppliers', [PaymentController::class, 'supplierStore'])->name('suppliers.store');
        Route::get('/suppliers/{payment}/attachment', [PaymentController::class, 'supplierAttachment'])->name('suppliers.attachment');
        Route::patch('/suppliers/{payment}/cheque-status', [PaymentController::class, 'supplierChequeStatus'])->name('suppliers.cheque-status');
        Route::delete('/suppliers/{payment}', [PaymentController::class, 'supplierDestroy'])->name('suppliers.destroy');

        Route::get('/customers', [PaymentController::class, 'customerIndex'])->name('customers.index');
        Route::post('/customers', [PaymentController::class, 'customerStore'])->name('customers.store');
        Route::get('/customers/{payment}/attachment', [PaymentController::class, 'customerAttachment'])->name('customers.attachment');
        Route::patch('/customers/{payment}/cheque-status', [PaymentController::class, 'customerChequeStatus'])->name('customers.cheque-status');
        Route::delete('/customers/{payment}', [PaymentController::class, 'customerDestroy'])->name('customers.destroy');

        Route::get('/bank', [BankTransactionController::class, 'index'])->name('bank.index');
        Route::post('/bank', [BankTransactionController::class, 'store'])->name('bank.store');
        Route::delete('/bank/{transaction}', [BankTransactionController::class, 'destroy'])->name('bank.destroy');
    });

    Route::prefix('ledgers')->name('ledgers.')->group(function () {
        Route::get('/customers', [LedgerController::class, 'customerIndex'])->name('customers.index');
        Route::get('/suppliers', [LedgerController::class, 'supplierIndex'])->name('suppliers.index');
    });

    Route::prefix('masters')->name('masters.')->group(function () {
        Route::get('/so-number-series', [SoNumberSeriesController::class, 'index'])->name('so-series.index');
        Route::post('/so-number-series', [SoNumberSeriesController::class, 'store'])->name('so-series.store');
        Route::put('/so-number-series/{series}', [SoNumberSeriesController::class, 'update'])->name('so-series.update');
        Route::delete('/so-number-series/{series}', [SoNumberSeriesController::class, 'destroy'])->name('so-series.destroy');

        Route::get('/settings', [MasterController::class, 'settings'])->name('settings');
        Route::put('/settings', [MasterController::class, 'updateSettings'])->name('settings.update');
        Route::get('/options/{type}', [MasterController::class, 'options'])->name('options');
        Route::get('/form/{type}', [MasterController::class, 'createForm'])->name('form');
        Route::get('/{type}', [MasterController::class, 'index'])->name('index');
        Route::post('/{type}', [MasterController::class, 'store'])->name('store');
        Route::put('/{type}/{id}', [MasterController::class, 'update'])->name('update');
        Route::delete('/{type}/{id}', [MasterController::class, 'destroy'])->name('destroy');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
