
@php

$wrapperIcon = match ($getRecord()->type) {
    'call' => 'bg-blue-100 ring-1 ring-blue-400/50',
    'email' => 'bg-green-100 ring-1 ring-green-400/50',
    'meeting' => 'bg-yellow-100 ring-1 ring-yellow-400/50',
    'document' => 'bg-red-100 ring-1 ring-red-400/50',
    default => 'bg-gray-100 ring-1 ring-gray-400/50',
};

$lineColor = match ($getRecord()->type) {
    'call' => 'bg-blue-400',
    'email' => 'bg-green-400',
    'meeting' => 'bg-yellow-400',
    'document' => 'bg-red-400',
    default => 'bg-gray-400',
};

@endphp

<div class="flex items-center">
    <div @class([$wrapperIcon, "rounded-full p-2 shadow z-10"])>
        @switch($getRecord()->type)
            @case('call')
                <x-heroicon-o-phone class="w-6 h-6 text-blue-400" />
                @break
            @case('email')
                <x-heroicon-o-envelope class="w-6 h-6 text-green-400" />
                @break
            @case('meeting')
                <x-heroicon-o-calendar class="w-6 h-6 text-yellow-400" />
                @break
            @case('document')
                <x-heroicon-o-document-text class="w-6 h-6 text-red-400" />
                @break
        @endswitch
    </div>
    <div @class([$lineColor, 'h-[1px] flex-grow relative mt-4 -ms-1'])>
        <div class="text-xs font-bold text-gray-500 ps-2 absolute bottom-full">
            {{ $getRecord()->created_at->format('H:i') }}
        </div>
    </div>
</div>
