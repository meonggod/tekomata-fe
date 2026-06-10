<?php

use App\Http\Controllers\CatalogImportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\TeamChatController;
use App\Http\Controllers\VerifyController;
use App\Http\Controllers\WarehouseController;
use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
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

// Forgot password: render the email form and ask the Go
// /auth/password/forgot to send a reset link. A successful submit always
// shows the same generic "if that email exists, we sent a link" state (no
// account enumeration).
Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

// Reset password: the set-a-new-password screen reached from the email link
// (/reset-password?token=...). Posts the token + new password to the Go
// /auth/password/reset; on success the old session is dead, so it routes to login.
Route::get('/reset-password', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

Route::post('/logout', function (Request $request, TokenStore $tokens, AuthApi $auth) {
    // Best-effort server-side revocation of the refresh token before we drop
    // the local session — never block logout on an API hiccup.
    $access = $tokens->accessToken();
    $refresh = $tokens->refreshToken();

    if ($access !== null && $refresh !== null) {
        try {
            $auth->logout($access, $refresh);
        } catch (TekomataApiException) {
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

    // Onboarding flow — authenticated but NOT gated by EnsureOnboarded (would loop).
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::get('/onboarding/kyc', [OnboardingController::class, 'showKyc'])->name('onboarding.kyc');
    Route::post('/onboarding/kyc', [OnboardingController::class, 'storeKyc'])->name('onboarding.kyc.store');
    Route::get('/onboarding/kyb', [OnboardingController::class, 'showKyb'])->name('onboarding.kyb');
    Route::post('/onboarding/kyb', [OnboardingController::class, 'storeKyb'])->name('onboarding.kyb.store');

    // Core dashboard + product routes — require completed onboarding.
    Route::middleware('ensure.onboarded')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/companies/switch', [CompanyController::class, 'switch'])->name('companies.switch');

        // Currency settings: browse the catalog and configure the active company's
        // enabled set + single default. The catalog GET is public; the mutations
        // below are JWT-scoped to the active company (carried in the token).
        // Products: list, CRUD, stock adjustment, movement history.
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');
        Route::get('/products/{id}/movements', [ProductController::class, 'movements'])->name('products.movements');
        Route::put('/products/{id}/categories', [ProductController::class, 'updateCategories'])->name('products.categories');

        // Categories: list, CRUD, product grouping.
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{id}/products', [CategoryController::class, 'addProducts'])->name('categories.products.add');
        Route::delete('/categories/{id}/products/{productId}', [CategoryController::class, 'removeProduct'])->name('categories.products.remove');

        // Warehouses: list, CRUD.
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('/warehouses/{id}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

        // Catalog: bulk import + browse.
        Route::get('/catalog/import', [CatalogImportController::class, 'index'])->name('catalog.import');
        Route::post('/catalog/import', [CatalogImportController::class, 'store'])->name('catalog.import.store');

        Route::get('/settings/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::post('/settings/currencies', [CurrencyController::class, 'enable'])->name('currencies.enable');
        Route::put('/settings/currencies/{code}/default', [CurrencyController::class, 'setDefault'])->name('currencies.default');
        Route::delete('/settings/currencies/{code}', [CurrencyController::class, 'disable'])->name('currencies.disable');

        // Inbox: omnichannel agent inbox — list, thread, reply, assign, status, notes.
        // SSE stream + read/typing MUST be registered before the {id} wildcard.
        Route::get('/inbox/stream', [InboxController::class, 'stream'])->name('inbox.stream');
        Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
        Route::get('/inbox/{id}', [InboxController::class, 'show'])->name('inbox.show');
        Route::get('/inbox/{id}/thread', [InboxController::class, 'threadJson'])->name('inbox.thread');
        Route::post('/inbox/{id}/reply', [InboxController::class, 'reply'])->name('inbox.reply');
        Route::post('/inbox/{id}/assign', [InboxController::class, 'assign'])->name('inbox.assign');
        Route::patch('/inbox/{id}/status', [InboxController::class, 'status'])->name('inbox.status');
        Route::post('/inbox/{id}/notes', [InboxController::class, 'addNote'])->name('inbox.notes');
        Route::post('/inbox/{id}/read', [InboxController::class, 'markRead'])->name('inbox.read');
        Route::post('/inbox/{id}/typing', [InboxController::class, 'typing'])->name('inbox.typing');
        Route::post('/inbox/{id}/takeover', [InboxController::class, 'takeover'])->name('inbox.takeover');
        Route::post('/inbox/{id}/handback', [InboxController::class, 'handback'])->name('inbox.handback');

        // Team chat: internal 1:1 and group conversations between company users.
        Route::get('/team', [TeamChatController::class, 'index'])->name('team.index');
        Route::get('/team/{id}', [TeamChatController::class, 'show'])->name('team.show');
        Route::get('/team/{id}/thread', [TeamChatController::class, 'threadJson'])->name('team.thread');
        Route::post('/team/conversations', [TeamChatController::class, 'createConversation'])->name('team.conversations.create');
        Route::post('/team/{id}/messages', [TeamChatController::class, 'sendMessage'])->name('team.messages.send');
        Route::post('/team/{id}/members', [TeamChatController::class, 'addMembers'])->name('team.members.add');

        // Company settings: identity, assistant behavior, notification emails, WhatsApp numbers.
        Route::get('/settings', [CompanySettingsController::class, 'show'])->name('settings.show');
        Route::post('/settings/company', [CompanySettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::post('/settings/assistant', [CompanySettingsController::class, 'updateAssistant'])->name('settings.assistant.update');
        Route::post('/settings/emails', [CompanySettingsController::class, 'addEmail'])->name('settings.emails.add');
        Route::post('/settings/emails/{id}/promote', [CompanySettingsController::class, 'promoteEmail'])->name('settings.emails.promote');
        Route::delete('/settings/emails/{id}', [CompanySettingsController::class, 'deleteEmail'])->name('settings.emails.delete');
        Route::post('/settings/whatsapp', [CompanySettingsController::class, 'addWhatsapp'])->name('settings.whatsapp.add');
        Route::post('/settings/whatsapp/{id}/promote', [CompanySettingsController::class, 'promoteWhatsapp'])->name('settings.whatsapp.promote');
        Route::delete('/settings/whatsapp/{id}', [CompanySettingsController::class, 'deleteWhatsapp'])->name('settings.whatsapp.delete');

    }); // end ensure.onboarded
}); // end auth.api
