<x-layouts.internal :title="'Internal · tekomata'">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">Internal dashboard</h1>
        <p class="mt-0.5 text-sm text-gray-500">Signed in as {{ $staffName }}.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-600">
            This is the staff-only area (<code class="rounded bg-gray-100 px-1 py-0.5 text-xs">/internal</code>),
            separate from the tenant control panel. Ops and daily-job tooling will live here.
        </p>
    </div>
</x-layouts.internal>
