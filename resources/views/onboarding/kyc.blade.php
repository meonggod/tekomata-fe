<x-layouts.app :title="__('messages.onboarding.kyc_title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.onboarding.breadcrumb')],
            ['label' => __('messages.onboarding.kyc_step')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-xl">

        {{-- Step progress --}}
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">1</div>
            <span class="text-xs font-medium text-indigo-600">{{ __('messages.onboarding.kyc_step') }}</span>
            <div class="h-px flex-1 bg-gray-200"></div>
            <span class="text-xs text-gray-400">{{ __('messages.onboarding.kyb_step') }}</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-400">2</div>
        </div>

        <div class="mb-4">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.onboarding.kyc_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.onboarding.kyc_subtitle') }}</p>
        </div>

        @error('kyc')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('onboarding.kyc.store') }}" class="divide-y divide-gray-100">
                @csrf

                <div class="space-y-4 px-5 py-5">

                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.full_name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required autocomplete="name"
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('full_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.date_of_birth_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('date_of_birth') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="id_type" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.id_type_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="id_type" name="id_type" required
                                class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                       {{ $errors->has('id_type') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                            <option value="">{{ __('messages.onboarding.id_type_placeholder') }}</option>
                            <option value="ktp"      @selected(old('id_type') === 'ktp')>{{ __('messages.onboarding.id_type_ktp') }}</option>
                            <option value="passport" @selected(old('id_type') === 'passport')>{{ __('messages.onboarding.id_type_passport') }}</option>
                            <option value="sim"      @selected(old('id_type') === 'sim')>{{ __('messages.onboarding.id_type_sim') }}</option>
                        </select>
                        @error('id_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="id_number" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.onboarding.id_number_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required
                               class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                      {{ $errors->has('id_number') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.onboarding.id_number_hint') }}</p>
                        @error('id_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                        {{ __('messages.onboarding.kyc_submit') }}
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-layouts.app>
