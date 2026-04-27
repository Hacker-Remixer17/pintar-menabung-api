<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomResetPassword;
use App\Notifications\CustomVerifyEmail;
use App\Models\Wallet;       // DITAMBAHKAN
use App\Models\Transaction;   // DITAMBAHKAN

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==========================================
    // RELASI DATABASE
    // ==========================================

    /**
     * Relasi ke tabel Wallets (1 User punya banyak Wallet)
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Relasi ke tabel Transactions melalui tabel Wallets
     * (Karena transaksi terhubung ke wallet, bukan langsung ke user)
     */
    public function transactions()
    {
        return $this->hasManyThrough(
            Transaction::class, // Model tujuan
            Wallet::class       // Model perantara
        );
    }

    // ==========================================
    // OVERRIDE NOTIFIKASI
    // ==========================================

    /**
     * Mengarahkan link verifikasi ke Frontend React
     */
    public function sendEmailVerificationNotification()
    {
        $url = 'http://localhost:5173/verify-email?email=' . urlencode($this->email) . '&hash=' . sha1($this->getEmailForVerification());

        $this->notify(new CustomVerifyEmail($url));
    }

    /**
     * Mengarahkan link reset password ke Frontend React
     */
    public function sendPasswordResetNotification($token)
    {
        $url = 'http://localhost:5173/reset-password?token=' . $token . '&email=' . urlencode($this->email);

        $this->notify(new CustomResetPassword($url));
    }
}