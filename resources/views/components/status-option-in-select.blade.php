@props([
    'status',
    'isColumn' => false,
    'target' => null,
    'removeBackground' => false,
    'rawStatus' => null
])

<div @class([
    'px-2' => $isColumn && !$removeBackground,
])>
    <div @class([
        "inline-flex  items-center relative",
        "h-5 gap-2" => !$removeBackground,
        "gap-1" => $removeBackground,
    ])>
        <span
            class="inline {{ $isColumn ? 'h-3 w-3 rounded-full' : 'h-3 w-3 rounded-md ' }}"
            style="background-color: {{ $status['color'] ?? '#5d5d5d' }}"
            @if($target)
                wire:loading.remove.delay.default="1"
                wire:target="{{ $target }}"
            @endif
        >

        </span>

        @if($target)
            <x-filament::loading-indicator
                style="color: {{ $status['color'] ?? '#5d5d5d' }}"
                wire:loading.delay.default=""
                wire:target="{{ $target }}"
                class="h-4 w-4"
            />
        @endif

        <span @class([
            'text-xs font-semibold' => $isColumn,
            'text-sm' => !$isColumn,
        ]) @style([
            "color: " . ($status['color'] ?? '000') => $isColumn
        ])>
            @if(!$status && $rawStatus)
                 {{ $rawStatus }}
            @endif
            {{ $status['name'] ?? '' }}
        </span>
        @if($isColumn && !$removeBackground)
            <div
                class="rounded-full border absolute -right-2 -top-1 block bottom-0 w-[calc(100%+1.5rem)] h-[calc(100%+0.5rem)]"
                style="background-color: {{ ($status['color'] ?? '#ffffff') . '14'}}; border-color: {{ ($status['color'] ?? '#ffffff') . '80'}}"
            >
            </div>
        @endif
    </div>
</div>
