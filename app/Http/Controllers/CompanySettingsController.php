<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CatalogApi;
use App\Services\Tekomata\CompanySettingsApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(
        private readonly CompanySettingsApi $settingsApi,
        private readonly CatalogApi $catalog,
        private readonly TokenStore $tokens,
    ) {}

    public function show(): View
    {
        $token = $this->tokens->accessToken();

        $settings = [];
        try {
            $settings = $this->settingsApi->getSettings($token);
        } catch (TekomataApiException) {
            // Degrade gracefully — page renders with empty defaults
        }

        $countries = [];
        try {
            $countries = $this->catalog->countries();
        } catch (TekomataApiException) {
            // Selector renders nothing, not a blocking failure
        }

        return view('settings.index', [
            'settings' => $settings,
            'countries' => $countries,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'companyId' => $this->tokens->activeCompany()['id'] ?? '',
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        $token = $this->tokens->accessToken();

        if (! empty($payload)) {
            try {
                $this->settingsApi->patchSettings($token, $payload);
            } catch (ValidationException $e) {
                return back()
                    ->withErrors($e->errors() ?: ['display_name' => __('errors.validation_failed')])
                    ->withInput()
                    ->with('error_section', 'company');
            } catch (ApiUnavailableException $e) {
                return $this->apiErrorModal($e, $request);
            } catch (TekomataApiException $e) {
                return back()
                    ->withErrors(['display_name' => $e->localizedMessage()])
                    ->withInput()
                    ->with('error_section', 'company');
            }
        }

        return redirect(route('settings.show').'?tab=company#company')
            ->with('status', __('messages.settings.company_saved'))
            ->with('status_section', 'company');
    }

    public function updateAssistant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assistant_persona_name' => ['nullable', 'string', 'max:100'],
            'reply_language' => ['nullable', 'string', 'in:id,en,auto'],
            'business_hours_mode' => ['required', 'string', 'in:always_active,scheduled'],
            'out_of_hours_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [];

        if ($validated['assistant_persona_name'] !== null && $validated['assistant_persona_name'] !== '') {
            $payload['assistant_persona_name'] = $validated['assistant_persona_name'];
        }
        if ($validated['reply_language'] !== null) {
            $payload['reply_language'] = $validated['reply_language'];
        }

        if ($validated['business_hours_mode'] === 'always_active') {
            $payload['business_hours'] = null;
            $payload['out_of_hours_message'] = null;
        } else {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $hours = [];
            foreach ($days as $day) {
                if ($request->boolean("bh_enabled.$day")) {
                    $slots = $request->input("bh.$day", []);
                    $hours[$day] = collect($slots)
                        ->map(fn ($s) => ['open' => $s['open'] ?? '', 'close' => $s['close'] ?? ''])
                        ->filter(fn ($s) => $s['open'] !== '' && $s['close'] !== '')
                        ->values()
                        ->toArray();
                } else {
                    $hours[$day] = [];
                }
            }
            $payload['business_hours'] = $hours;
            $payload['out_of_hours_message'] = $validated['out_of_hours_message'] ?? '';
        }

        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->patchSettings($token, $payload);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors() ?: ['assistant_persona_name' => __('errors.validation_failed')])
                ->withInput()
                ->with('error_section', 'assistant');
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()
                ->withErrors(['assistant_persona_name' => $e->localizedMessage()])
                ->withInput()
                ->with('error_section', 'assistant');
        }

        return redirect(route('settings.show').'?tab=company#assistant')
            ->with('status', __('messages.settings.assistant_saved'))
            ->with('status_section', 'assistant');
    }

    public function addEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => __('errors.validation.required'),
            'email.email' => __('errors.validation.email'),
        ]);

        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->addEmail($token, ['email' => $validated['email']]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors() ?: ['new_email' => __('errors.validation_failed')]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['new_email' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#billing')
            ->with('status', __('messages.settings.email_added'))
            ->with('status_section', 'billing');
    }

    public function deleteEmail(Request $request, string $id): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->deleteEmail($token, $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['email_action' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#billing')
            ->with('status', __('messages.settings.email_deleted'))
            ->with('status_section', 'billing');
    }

    public function promoteEmail(Request $request, string $id): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->updateEmail($token, $id, ['priority' => 0]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['email_action' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#billing')
            ->with('status', __('messages.settings.email_promoted'))
            ->with('status_section', 'billing');
    }

    public function addWhatsapp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
        ], [
            'number.required' => __('errors.validation.required'),
            'number.regex' => __('errors.validation.invalid_phone'),
        ]);

        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->addWhatsapp($token, ['number' => $validated['number']]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors() ?: ['new_number' => __('errors.validation_failed')]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['new_number' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#whatsapp')
            ->with('status', __('messages.settings.whatsapp_added'))
            ->with('status_section', 'whatsapp');
    }

    public function deleteWhatsapp(Request $request, string $id): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->deleteWhatsapp($token, $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['whatsapp_action' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#whatsapp')
            ->with('status', __('messages.settings.whatsapp_deleted'))
            ->with('status_section', 'whatsapp');
    }

    public function promoteWhatsapp(Request $request, string $id): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $this->settingsApi->updateWhatsapp($token, $id, ['priority' => 0]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['whatsapp_action' => $e->localizedMessage()]);
        }

        return redirect(route('settings.show').'?tab=user#whatsapp')
            ->with('status', __('messages.settings.whatsapp_promoted'))
            ->with('status_section', 'whatsapp');
    }
}
