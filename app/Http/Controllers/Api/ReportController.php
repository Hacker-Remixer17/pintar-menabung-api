<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * GET /api/reports/summary-by-category/{type}
     * type = 'income' atau 'expense'
     */
    public function summaryByCategory(Request $request, $type)
    {
        // Validasi type harus income atau expense
        if (!in_array($type, ['income', 'expense'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Type must be income or expense'
            ], 400);
        }
        
        // Ambil semua transaksi user dengan filter bulan & tahun
        $query = Auth::user()->transactions()->with('category');
        
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }
        
        $transactions = $query->get();
        
        // Filter berdasarkan tipe dan group by category
        $summary = [];
        
        foreach ($transactions as $transaction) {
            $categoryType = strtolower($transaction->category->type ?? '');
            
            if ($categoryType === $type) {
                $categoryName = $transaction->category->name;
                
                if (!isset($summary[$categoryName])) {
                    $summary[$categoryName] = [
                        'category_id' => $transaction->category_id,
                        'category_name' => $categoryName,
                        'total_amount' => 0,
                        'transaction_count' => 0
                    ];
                }
                
                $summary[$categoryName]['total_amount'] += $transaction->amount;
                $summary[$categoryName]['transaction_count']++;
            }
        }
        
        // Hitung total untuk persentase
        $total = array_sum(array_column($summary, 'total_amount'));
        
        // Format hasil
        $result = [];
        foreach ($summary as $item) {
            $percentage = $total > 0 ? ($item['total_amount'] / $total) * 100 : 0;
            
            $result[] = [
                'category_id' => $item['category_id'],
                'category_name' => $item['category_name'],
                'total_amount' => $item['total_amount'],
                'formatted_amount' => 'Rp ' . number_format($item['total_amount'], 0, ',', '.'),
                'transaction_count' => $item['transaction_count'],
                'percentage' => round($percentage, 2)
            ];
        }
        
        // Urutkan dari yang terbesar
        usort($result, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        
        return response()->json([
            'status' => 'success',
            'type' => $type,
            'period' => [
                'month' => $request->month,
                'year' => $request->year
            ],
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
            'data' => $result
        ], 200);
    }
}