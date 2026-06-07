<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Surface an unexpected backend failure (5xx / unreachable, after retries)
     * as a dismissible "something went wrong" modal on the page the user was on
     * — never an inline field error (it isn't the user's fault) nor a dead-end
     * page (the action just didn't go through). Carries the request id so the
     * user can quote it to the help desk; the backend already alerted on it.
     * Sensitive inputs are dropped from the reflashed old input.
     *
     * @param  array<int,string>  $except  Input keys to drop when reflashing.
     */
    protected function apiErrorModal(ApiUnavailableException $e, Request $request, array $except = ['password']): RedirectResponse
    {
        return back()
            ->withInput($request->except($except))
            ->with('api_error', [
                'request_id' => $e->requestId(),
            ]);
    }
}
