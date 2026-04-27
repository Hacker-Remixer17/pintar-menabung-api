<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ========== TAMBAHKAN KODE INI UNTUK API (JSON RESPONSE) ==========
        // Memberitahu Fortify bahwa kita menggunakan API (bukan view Blade)
        // karena frontend kita adalah React yang terpisah
        
        Fortify::loginView(function () {
            return response()->json(['message' => 'Please use API endpoint for login'], 401);
        });

        Fortify::registerView(function () {
            return response()->json(['message' => 'Please use API endpoint for register'], 401);
        });

        Fortify::requestPasswordResetLinkView(function () {
            return response()->json(['message' => 'Please use API endpoint for forgot password'], 401);
        });

        Fortify::resetPasswordView(function ($request) {
            return response()->json(['message' => 'Please use API endpoint for reset password'], 401);
        });
        // ========== SAMPAI SINI ==========

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}