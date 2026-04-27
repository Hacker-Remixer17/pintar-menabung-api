<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    /**
     * GET /api/wallets - Get all wallets of logged in user
     */
    public function index()
    {
        $wallets = Auth::user()->wallets;
        
        // PERBAIKAN: Cek apakah $wallets tidak null sebelum melakukan foreach
        if (!is_null($wallets)) {
            foreach ($wallets as $wallet) {
                $wallet->balance = $this->calculateBalance($wallet);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Get all wallets successful',
            'data' => [
                'wallets' => $wallets ?? [] // Jika null, kembalikan array kosong []
            ]
        ], 200);
    }

    /**
     * POST /api/wallets - Create a new wallet
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'currency_code' => 'required|string|size:3',
        ]);

        $wallet = Auth::user()->wallets()->create([
            'name' => $request->name,
            'currency_code' => $request->currency_code,
            'balance' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet added successful',
            'data' => $wallet
        ], 201);
    }

    /**
     * GET /api/wallets/{walletId} - Get detail of specific wallet
     */
    public function show($walletId)
    {
        $wallet = Auth::user()->wallets()->withTrashed()->find($walletId);
        
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not found'
            ], 404);
        }
        
        // Hitung balance dari transaksi
        $wallet->balance = $this->calculateBalance($wallet);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Get detail wallet successful',
            'data' => $wallet
        ], 200);
    }

    /**
     * PUT /api/wallets/{walletId} - Update wallet
     */
    public function update(Request $request, $walletId)
    {
        $wallet = Auth::user()->wallets()->find($walletId);
        
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not found'
            ], 404);
        }
        
        // Validasi - name WAJIB, balance OPTIONAL
        $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'sometimes|numeric|min:0',
            'currency_code' => 'sometimes|string|size:3',
        ]);
        
        // Data yang akan diupdate
        $dataToUpdate = [
            'name' => $request->name
        ];
        
        // Jika currency_code dikirim dalam request, update juga
        if ($request->has('currency_code')) {
            $dataToUpdate['currency_code'] = $request->currency_code;
        }
        
        // Update data dasar
        $wallet->update($dataToUpdate);
        
        // Jika balance dikirim dalam request, update balance
        if ($request->has('balance')) {
            $wallet->balance = $request->balance;
            $wallet->save();
        }
        
        // Refresh wallet data
        $wallet->refresh();
        
        // Hitung ulang balance dari transaksi (tetap menggunakan calculateBalance)
        $wallet->balance = $this->calculateBalance($wallet);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Wallet updated successfully',
            'data' => $wallet
        ], 200);
    }

    /**
     * DELETE /api/wallets/{walletId} - Delete wallet (soft delete)
     */
    public function destroy($walletId)
    {
        $wallet = Auth::user()->wallets()->find($walletId);
        
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not found'
            ], 404);
        }
        
        $wallet->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Wallet deleted successful'
        ], 200);
    }
    
    /**
     * Calculate wallet balance from transactions
     * Jika sudah ada transaksi, hitung dari jumlah income - expense
     * Jika belum, return balance dari database
     */
    private function calculateBalance($wallet)
    {
        // Jika wallet memiliki relasi transactions dan transactions sudah di-load
        if ($wallet->relationLoaded('transactions') && $wallet->transactions->count() > 0) {
            $income = $wallet->transactions->filter(function($transaction) {
                return $transaction->category && $transaction->category->type === 'INCOME';
            })->sum('amount');
            
            $expense = $wallet->transactions->filter(function($transaction) {
                return $transaction->category && $transaction->category->type === 'EXPENSE';
            })->sum('amount');
            
            return $income - $expense;
        }
        
        // Sementara return balance dari database
        // Nanti akan diupdate setelah transaksi dibuat
        return $wallet->balance ?? 0;
    }
}