<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Admin\StaffAdminApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff management + the config-change audit log (foundation reads). Any staff
 * can see the roster and the audit trail; inviting a new staff member is
 * superadmin-only (route middleware). The new member sets their own password
 * from an emailed link — no password is entered here.
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly StaffAdminApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $staff = [];
        $audit = [];

        try {
            $staff = $this->api->staff($token);
            $audit = $this->api->auditLog($token);
        } catch (TekomataApiException) {
            // Degrade gracefully.
        }

        return view('internal.staff', [
            'staff' => $staff,
            'audit' => $audit,
            'currentEmail' => $this->tokens->email(),
            'isSuperadmin' => $this->tokens->isSuperadmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:superadmin,ops'],
        ]);

        $token = (string) $this->tokens->accessToken();

        try {
            $this->api->createStaff($token, $data);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['email' => $e->localizedMessage()]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            // e.g. staff.email_taken
            return back()->withInput()->withErrors(['email' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.staff.flash.invited'));
    }
}
