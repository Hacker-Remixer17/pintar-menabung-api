<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register user baru
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // KIRIM EMAIL VERIFIKASI
        $user->sendEmailVerificationNotification();

        // TIDAK MEMBERIKAN TOKEN, SUPAYA TIDAK LANGSUNG MASUK DASHBOARD
        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi akun.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username or password incorrect'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // TAMBAHKAN PENGECEKAN INI: CEK APAKAH EMAIL SUDAH VERIFIKASI SAAT LOGIN
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum diverifikasi. Silakan cek email untuk verifikasi terlebih dahulu.',
                'data' => [
                    'email' => $user->email
                ]
            ], 403); // 403 Forbidden
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'token' => $token,
            ]
        ], 200);
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Forgot Password - Mengirim link reset password ke email
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => __($status)
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset Password - Mengganti password dengan token dari email
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => __($status)
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Verify Email - Memverifikasi email pengguna
     * GET /api/auth/verify-email
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'hash' => 'required|string',
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->firstOrFail();

        // Cocokkan hash dari URL dengan hash asli Laravel
        if (! hash_equals(sha1($user->getEmailForVerification()), $request->hash)) {
            throw ValidationException::withMessages([
                'hash' => ['Link verifikasi tidak valid.'],
            ]);
        }

        // Jika sudah terverifikasi sebelumnya
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email sudah diverifikasi sebelumnya.'
            ], 200);
        }

        // Tandai email sebagai sudah terverifikasi di database
        $user->markEmailAsVerified();

        return response()->json([
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi! Silakan login.'
        ], 200);
    }
}