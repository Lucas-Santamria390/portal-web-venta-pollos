<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'panel'])->name('dashboard.panel');

    Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/nueva', [OrderController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos/vender', [OrderController::class, 'sell'])->name('pedidos.sell');
    Route::post('/pedidos/vender', [OrderController::class, 'storeSell'])->name('pedidos.sell.store');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{order}/editar', [OrderController::class, 'edit'])->name('pedidos.edit');
    Route::patch('/pedidos/{order}', [OrderController::class, 'update'])->name('pedidos.update');
    Route::get('/pedidos/{order}/entregar', [OrderController::class, 'deliverForm'])->name('pedidos.deliver.edit');
    Route::post('/pedidos/{order}/entregar', [OrderController::class, 'deliver'])->name('pedidos.deliver');
    Route::post('/pedidos/{order}/pagos', [OrderController::class, 'registerPayment'])->name('pedidos.payments.store');

    Route::get('/por-cobrar', [ReceivableController::class, 'index'])->name('por-cobrar.index');

    Route::get('/clientes', [CustomerController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/nuevo', [CustomerController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [CustomerController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{customer}', [CustomerController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{customer}/editar', [CustomerController::class, 'edit'])->name('clientes.edit');
    Route::patch('/clientes/{customer}', [CustomerController::class, 'update'])->name('clientes.update');

    Route::middleware('admin')->group(function () {
        Route::get('/configuracion', [SettingController::class, 'edit'])->name('configuracion.edit');
        Route::patch('/configuracion', [SettingController::class, 'update'])->name('configuracion.update');

        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/nuevo', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleActive'])->name('usuarios.estado');

        Route::post('/pedidos/{order}/anular', [OrderController::class, 'cancel'])->name('pedidos.cancel');
        Route::delete('/pedidos/{order}/pagos/{payment}', [OrderController::class, 'deletePayment'])->name('pedidos.payments.destroy');
        Route::delete('/pedidos/{order}', [OrderController::class, 'destroy'])->name('pedidos.destroy');
        Route::delete('/clientes/{customer}', [CustomerController::class, 'destroy'])->name('clientes.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
