{{--
  Product media manager — photos (view-tagged) + videos, one thumbnail, drag to
  reorder. Driven by initProductMedia() in resources/js/app.js. All routes and
  strings ride in the embedded JSON config (nothing hard-coded in JS), and every
  call goes same-origin to ProductMediaController so the JWT stays server-side.
--}}
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-product-media>
    <script type="application/json" data-product-media-config>
        @json([
            'routes' => [
                'list' => route('products.media.index', $product['id']),
                'store' => route('products.media.store', $product['id']),
                'reorder' => route('products.media.reorder', $product['id']),
                'thumbnail' => route('products.media.thumbnail', ['id' => $product['id'], 'mediaId' => '__ID__']),
                'delete' => route('products.media.destroy', ['id' => $product['id'], 'mediaId' => '__ID__']),
            ],
            'csrf' => csrf_token(),
            'videoPlaceholder' => asset('img/video-placeholder.svg'),
            'thumbSize' => 300,
            'i18n' => [
                'view_front' => __('messages.products.media.view_front'),
                'view_back' => __('messages.products.media.view_back'),
                'view_left' => __('messages.products.media.view_left'),
                'view_right' => __('messages.products.media.view_right'),
                'view_top' => __('messages.products.media.view_top'),
                'view_bottom' => __('messages.products.media.view_bottom'),
                'view_detail' => __('messages.products.media.view_detail'),
                'thumbnail_badge' => __('messages.products.media.thumbnail_badge'),
                'video_badge' => __('messages.products.media.video_badge'),
                'make_thumbnail' => __('messages.products.media.make_thumbnail'),
                'delete' => __('messages.products.media.delete'),
                'confirm_delete' => __('messages.products.media.confirm_delete'),
                'empty' => __('messages.products.media.empty'),
                'uploading' => __('messages.products.media.uploading'),
                'upload' => __('messages.products.media.upload'),
                'view_required' => __('messages.products.media.view_required'),
                'photo_rules' => __('messages.products.media.photo_rules'),
                'video_rules' => __('messages.products.media.video_rules'),
                'load_error' => __('messages.products.media.load_error'),
                'generic_error' => __('messages.products.media.generic_error'),
            ],
        ])
    </script>

    <div class="border-b border-gray-100 px-5 py-3">
        <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.products.media.title') }}</h2>
        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.products.media.subtitle') }}</p>
    </div>

    {{-- Error banner --}}
    <div data-pm-error hidden class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800"></div>

    {{-- Uploader --}}
    <div class="space-y-4 border-b border-gray-100 px-5 py-4">
        {{-- Kind toggle --}}
        <div class="inline-flex rounded-lg border border-gray-200 p-0.5 text-sm">
            <button type="button" data-pm-kind="photo"
                    class="rounded-md px-3 py-1 font-medium text-gray-700">
                {{ __('messages.products.media.add_photo') }}
            </button>
            <button type="button" data-pm-kind="video"
                    class="rounded-md px-3 py-1 font-medium text-gray-700">
                {{ __('messages.products.media.add_video') }}
            </button>
        </div>

        <form data-pm-form class="space-y-3">
            <input type="file" data-pm-file
                   class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">

            {{-- View angle (photos only) --}}
            <div data-pm-view-wrap>
                <label for="pm-view" class="block text-sm font-medium text-gray-700">
                    {{ __('messages.products.media.view_label') }} <span class="text-red-500">*</span>
                </label>
                <select id="pm-view" data-pm-view
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:w-56">
                    <option value="front">{{ __('messages.products.media.view_front') }}</option>
                    <option value="back">{{ __('messages.products.media.view_back') }}</option>
                    <option value="left">{{ __('messages.products.media.view_left') }}</option>
                    <option value="right">{{ __('messages.products.media.view_right') }}</option>
                    <option value="top">{{ __('messages.products.media.view_top') }}</option>
                    <option value="bottom">{{ __('messages.products.media.view_bottom') }}</option>
                    <option value="detail">{{ __('messages.products.media.view_detail') }}</option>
                </select>

                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" data-pm-thumbnail
                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __('messages.products.media.set_thumbnail') }}
                </label>
            </div>

            <p data-pm-rules class="text-xs text-gray-400"></p>

            <button type="submit" data-pm-submit
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                {{ __('messages.products.media.upload') }}
            </button>
        </form>
    </div>

    {{-- Gallery --}}
    <div class="px-5 py-4">
        <p data-pm-empty class="text-sm text-gray-500">{{ __('messages.products.media.empty') }}</p>
        <p data-pm-drag-hint hidden class="mb-2 text-xs text-gray-400">{{ __('messages.products.media.drag_hint') }}</p>
        <div data-pm-grid class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
    </div>
</div>
