<x-layouts.app :title="__('messages.onboarding.kyb_title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.onboarding.breadcrumb')],
            ['label' => __('messages.onboarding.kyb_step')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-xl">

        {{-- Step progress --}}
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600">✓</div>
            <span class="text-xs text-indigo-400">{{ __('messages.onboarding.kyc_step') }}</span>
            <div class="h-px flex-1 bg-indigo-600"></div>
            <span class="text-xs font-medium text-indigo-600">{{ __('messages.onboarding.kyb_step') }}</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">2</div>
        </div>

        <div class="mb-4">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.onboarding.kyb_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.onboarding.kyb_subtitle') }}</p>
        </div>

        @error('kyb')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('onboarding.kyb.store') }}" class="divide-y divide-gray-100">
                @csrf

                <div class="space-y-4 px-5 py-5">

                    <div>
                        <label for="legal_name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.legal_name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}" required
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('legal_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.onboarding.legal_name_hint') }}</p>
                        @error('legal_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="business_type" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.business_type_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="business_type" name="business_type" required
                                class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                       {{ $errors->has('business_type') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                            <option value="">{{ __('messages.onboarding.business_type_placeholder') }}</option>
                            <option value="pt"         @selected(old('business_type') === 'pt')>{{ __('messages.onboarding.business_type_pt') }}</option>
                            <option value="cv"         @selected(old('business_type') === 'cv')>{{ __('messages.onboarding.business_type_cv') }}</option>
                            <option value="ud"         @selected(old('business_type') === 'ud')>{{ __('messages.onboarding.business_type_ud') }}</option>
                            <option value="perorangan" @selected(old('business_type') === 'perorangan')>{{ __('messages.onboarding.business_type_perorangan') }}</option>
                            <option value="other"      @selected(old('business_type') === 'other')>{{ __('messages.onboarding.business_type_other') }}</option>
                        </select>
                        @error('business_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="registration_number" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.registration_number_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" required
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('registration_number') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.onboarding.registration_number_hint') }}</p>
                        @error('registration_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tax_id" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.tax_id_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}" required
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('tax_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.onboarding.tax_id_hint') }}</p>
                        @error('tax_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.address_label') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea id="address" name="address" rows="3" required
                                  class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                         {{ $errors->has('address') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">{{ old('address') }}</textarea>
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>

                <div class="flex items-center justify-end px-5 py-4">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.onboarding.kyb_submit') }}
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-layouts.app>
