<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'category_id',
        'amount',
        'note',
        'date',
    ];

    protected $casts = [
        'amount' => 'integer',  // ✅ Diubah dari decimal:2 menjadi integer
        'date' => 'date',
    ];

    // Relasi ke Wallet
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    // Relasi ke Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get formatted amount with thousands separator
     * Contoh: 100000 → "100.000"
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get amount in Indonesian Rupiah format
     * Contoh: 100000 → "Rp 100.000"
     */
    public function getRupiahAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Check if transaction is income (based on category type)
     */
    public function isIncome()
    {
        return $this->category && $this->category->type === 'INCOME';
    }

    /**
     * Check if transaction is expense (based on category type)
     */
    public function isExpense()
    {
        return $this->category && $this->category->type === 'EXPENSE';
    }
}