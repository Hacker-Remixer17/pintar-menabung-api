<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;

// Public routes (tanpa authentication)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/verify-email', [AuthController::class, 'verifyEmail']); // <-- TAMBAHKAN INI

// Protected routes (memerlukan authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Currency & Category
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    
    // Wallet CRUD
    Route::get('/wallets', [WalletController::class, 'index']);
    Route::post('/wallets', [WalletController::class, 'store']);
    Route::get('/wallets/{walletId}', [WalletController::class, 'show']);
    Route::put('/wallets/{walletId}', [WalletController::class, 'update']);
    Route::delete('/wallets/{walletId}', [WalletController::class, 'destroy']);
    
    // Transaction CRUD
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{transactionId}', [TransactionController::class, 'destroy']);
    
    // Transfer antar wallet
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    
    // Reports
    Route::get('/reports/summary-by-category/{type}', [ReportController::class, 'summaryByCategory']);
});