<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * POST /api/transactions - Add a new transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1',
            'date' => 'required|date_format:Y-m-d',
            'note' => 'nullable|string',
        ]);

        // Cek apakah wallet milik user yang login
        $wallet = Auth::user()->wallets()->find($request->wallet_id);
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wallet not found or unauthorized'
            ], 403);
        }

        // Dapatkan category
        $category = Category::find($request->category_id);
        
        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found'
            ], 404);
        }

        // Buat transaksi
        $transaction = Transaction::create([
            'wallet_id' => $request->wallet_id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'note' => $request->note,
            'date' => $request->date,
        ]);

        // Update balance wallet
        $wallet->updateBalance($request->amount, $category->type);

        // Load relasi
        $transaction->load(['wallet', 'category']);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction added successfully',
            'data' => $transaction
        ], 201);
    }

    /**
     * GET /api/transactions - Get all transactions of logged in user
     */
    public function index(Request $request)
    {
        // Gunakan relasi dari User melalui wallets (HasManyThrough)
        $query = Auth::user()->transactions()->with(['wallet', 'category']);
        
        // Filter by month and year
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }
        
        // Filter by specific date (optional)
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        
        // Filter by wallet_id (optional)
        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        
        // Filter by category_id (optional)
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter by transaction type (income/expense)
        if ($request->has('type')) {
            $type = strtoupper($request->type);
            $query->whereHas('category', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // Order by date descending
        $query->orderBy('date', 'desc');
        
        // Pagination
        $perPage = $request->get('per_page', 25);
        $transactions = $query->paginate($perPage);
        
        // Format amount for each transaction
        $transactions->getCollection()->transform(function ($transaction) {
            $transaction->formatted_amount = number_format($transaction->amount, 0, ',', '.');
            $transaction->rupiah_amount = 'Rp ' . number_format($transaction->amount, 0, ',', '.');
            return $transaction;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Get transactions successful',
            'data' => $transactions
        ], 200);
    }

    /**
     * DELETE /api/transactions/{transactionId} - Delete a transaction
     */
    public function destroy($transactionId)
    {
        $transaction = Transaction::whereHas('wallet', function ($q) {
            $q->where('user_id', Auth::id());
        })->find($transactionId);

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found'
            ], 404);
        }

        $wallet = $transaction->wallet;
        $category = $transaction->category;

        // Reverse the balance update
        if ($category->type === 'INCOME') {
            $wallet->balance -= $transaction->amount;
        } else {
            $wallet->balance += $transaction->amount;
        }
        $wallet->save();

        $transaction->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction deleted successfully'
        ], 200);
    }
    
    /**
     * GET /api/transactions/summary - Get summary of transactions (income, expense, balance)
     */
    public function summary(Request $request)
    {
        $query = Auth::user()->transactions()->with('category');
        
        // Filter by month and year
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }
        
        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        
        $transactions = $query->get();
        
        $totalIncome = 0;
        $totalExpense = 0;
        
        foreach ($transactions as $transaction) {
            if ($transaction->category && $transaction->category->type === 'INCOME') {
                $totalIncome += $transaction->amount;
            } elseif ($transaction->category && $transaction->category->type === 'EXPENSE') {
                $totalExpense += $transaction->amount;
            }
        }
        
        $balance = $totalIncome - $totalExpense;
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'formatted_total_income' => 'Rp ' . number_format($totalIncome, 0, ',', '.'),
                'formatted_total_expense' => 'Rp ' . number_format($totalExpense, 0, ',', '.'),
                'formatted_balance' => 'Rp ' . number_format($balance, 0, ',', '.'),
            ]
        ], 200);
    }

    /**
     * POST /api/transactions/transfer - Transfer money between wallets
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'required|exists:wallets,id|different:from_wallet_id',
            'amount' => 'required|integer|min:1',
            'date' => 'required|date_format:Y-m-d',
            'note' => 'nullable|string',
        ]);

        // Cek apakah from_wallet milik user yang login
        $fromWallet = Auth::user()->wallets()->find($request->from_wallet_id);
        if (!$fromWallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Source wallet not found or unauthorized'
            ], 403);
        }

        // Cek apakah to_wallet milik user yang login
        $toWallet = Auth::user()->wallets()->find($request->to_wallet_id);
        if (!$toWallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Destination wallet not found or unauthorized'
            ], 403);
        }

        // Cek apakah saldo cukup
        if ($fromWallet->balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance'
            ], 400);
        }

        // Cari category untuk transfer (EXPENSE untuk pengirim, INCOME untuk penerima)
        // Gunakan category_id = 1 untuk EXPENSE (Food & Drinks) sebagai default
        // Gunakan category_id = 8 untuk INCOME (Salary) sebagai default
        $expenseCategoryId = 1;  // ID untuk EXPENSE
        $incomeCategoryId = 8;    // ID untuk INCOME

        // Buat transaksi PENGURANGAN (EXPENSE) untuk from_wallet
        $expenseTransaction = Transaction::create([
            'wallet_id' => $request->from_wallet_id,
            'category_id' => $expenseCategoryId,
            'amount' => $request->amount,
            'note' => $request->note ?: 'Transfer to wallet ' . $request->to_wallet_id,
            'date' => $request->date,
        ]);

        // Buat transaksi PENAMBAHAN (INCOME) untuk to_wallet
        $incomeTransaction = Transaction::create([
            'wallet_id' => $request->to_wallet_id,
            'category_id' => $incomeCategoryId,
            'amount' => $request->amount,
            'note' => $request->note ?: 'Transfer from wallet ' . $request->from_wallet_id,
            'date' => $request->date,
        ]);

        // Update balance from_wallet (kurangi)
        $fromWallet->balance -= $request->amount;
        $fromWallet->save();

        // Update balance to_wallet (tambah)
        $toWallet->balance += $request->amount;
        $toWallet->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer successful',
            'data' => [
                'from_wallet' => [
                    'id' => $fromWallet->id,
                    'name' => $fromWallet->name,
                    'new_balance' => $fromWallet->balance,
                    'formatted_balance' => 'Rp ' . number_format($fromWallet->balance, 0, ',', '.')
                ],
                'to_wallet' => [
                    'id' => $toWallet->id,
                    'name' => $toWallet->name,
                    'new_balance' => $toWallet->balance,
                    'formatted_balance' => 'Rp ' . number_format($toWallet->balance, 0, ',', '.')
                ],
                'amount' => $request->amount,
                'formatted_amount' => 'Rp ' . number_format($request->amount, 0, ',', '.'),
                'date' => $request->date,
                'note' => $request->note,
                'expense_transaction_id' => $expenseTransaction->id,
                'income_transaction_id' => $incomeTransaction->id
            ]
        ], 200);
    }
}