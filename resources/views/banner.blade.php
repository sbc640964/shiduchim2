@props(['banner', 'location'])

@php

/** @var $banner \App\Models\Banner */

    $isHeightFull = in_array($location, [
        \Filament\View\PanelsRenderHook::LAYOUT_START,
        \Filament\View\PanelsRenderHook::LAYOUT_END,
    ]);

@endphp

<div @class([
    'p-2' => $location !== \Filament\View\PanelsRenderHook::BODY_START,
])>
    <div
        @class([
            "p-4",
            'rounded-lg' => $location !== \Filament\View\PanelsRenderHook::BODY_START,
            "h-full" => $isHeightFull,
            data_get($banner->config, 'style.width', 'w-[280px]') => $isHeightFull,
        ])
        style="{{ $banner->getStyle() }}"
    >
        <div @class([
            'flex',
            'flex-col gap-2' => $isHeightFull,
            'gap-4' => ! $isHeightFull,
        ])>
            @if($banner->config['style']['image'] ?? false)
                <div>
                    <img
                        src="{{ Storage::disk('s3')->temporaryUrl($banner->config['style']['image'], now()->addMinutes(30)->endOfHour()) }}"
                        alt="{{ $banner->heading }}"
                        @class(["w-full object-cover rounded-lg", $isHeightFull ? 'max-h-32' : 'max-h-20'])
                    />
                </div>
            @elseif($banner->config['style']['icon'] ?? false)
                <div class="flex items-center">
                    <x-filament::icon :icon="$banner->getIconCase()" style="color: {{ $banner->getIconColor() }};" />
                </div>
            @endif
            <div>
                <h4 class="font-semibold">{{ $banner->heading }}</h4>
                <div class="text-sm">
                    {!! $banner->body !!}
                </div>
            </div>
        </div>
    </div>
</div>

