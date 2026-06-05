<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VerifyController;
use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes — tekomata control panel
|--------------------------------------------------------------------------
| Thin route → controller → app/Services/Tekomata → Go API. Most product
| screens (catalog, usage, billing, full auth) arrive as ClickUp stories in
| tasks/. The routes below are the runnable scaffold + auth wiring.
*/

// Public landing — funnels signups into the panel.
Route::view('/', 'landing')->name('home');

// Switch language (EN/ID). Stores the choice in the session; SetLocale applies
// it. Supported codes live in config/locales.php.
Route::get('/locale/{locale}', function (string $locale, Request $request) {
    if (array_key_exists($locale, config('locales.supported', []))) {
        $request->session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('locale.switch');

// Sign in: render the form and exchange email + password at the Go /auth/login
// for an access/refresh pair, kept server-side in the session (TokenStore).
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

// Signup: render the form, submit credentials to the Go /auth/register. A
// successful submit shows a generic "check your email" state (no account is
// created until the emailed link is verified).
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

// Verification landing — the click-through target of the email link
// (/verify?token=...). Confirms the token, then routes to login → dashboard.
Route::get('/verify', [VerifyController::class, 'verify'])->name('verify');

Route::post('/logout', function (Request $request, TokenStore $tokens, AuthApi $auth) {
    // Best-effort server-side revocation of the refresh token before we drop
    // the local session — never block logout on an API hiccup.
    $access = $tokens->accessToken();
    $refresh = $tokens->refreshToken();

    if ($access !== null && $refresh !== null) {
        try {
            $auth->logout($access, $refresh);
        } catch (\App\Services\Tekomata\Exceptions\TekomataApiException) {
            // ignore — local sign-out still proceeds
        }
    }

    $tokens->forget();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

// Authenticated area — gated by the Go API session (see EnsureAuthenticated).
Route::middleware('auth.api')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/companies/switch', [CompanyController::class, 'switch'])->name('companies.switch');
});
