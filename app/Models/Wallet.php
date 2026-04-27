<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'currency_code',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',  // ✅ Diubah dari decimal:2 menjadi integer
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Transaction
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Update wallet balance based on transaction
     * 
     * @param float $amount Jumlah transaksi
     * @param string $type Tipe kategori (INCOME atau EXPENSE)
     */
    public function updateBalance($amount, $type)
    {
        if ($type === 'INCOME') {
            $this->balance += $amount;
        } else {
            $this->balance -= $amount;
        }
        $this->save();
    }

    /**
     * Recalculate balance from all transactions
     * Digunakan untuk sinkronisasi ulang saldo jika perlu
     * 
     * @return int Saldo terbaru
     */
    public function recalculateBalance()
    {
        $balance = 0;
        
        foreach ($this->transactions as $transaction) {
            if ($transaction->category && $transaction->category->type === 'INCOME') {
                $balance += $transaction->amount;
            } elseif ($transaction->category && $transaction->category->type === 'EXPENSE') {
                $balance -= $transaction->amount;
            }
        }
        
        $this->balance = $balance;
        $this->save();
        
        return $this->balance;
    }

    /**
     * Get formatted balance with thousands separator
     * Untuk tampilan yang lebih mudah dibaca (contoh: 100.000)
     * 
     * @return string
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', '.');
    }

    /**
     * Get balance in Indonesian Rupiah format
     * Contoh: Rp 100.000
     * 
     * @return string
     */
    public function getRupiahBalanceAttribute()
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }
}