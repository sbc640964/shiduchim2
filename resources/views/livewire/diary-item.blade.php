@php

    $wrapperIcon = match ($diary->type) {
        'call' => 'bg-blue-100 ring-1 ring-blue-400/50',
        'email' => 'bg-green-100 ring-1 ring-green-400/50',
        'meeting' => 'bg-yellow-100 ring-1 ring-yellow-400/50',
        'document' => 'bg-red-100 ring-1 ring-red-400/50',
        default => 'bg-gray-100 ring-1 ring-gray-400/50',
    };

    $lineColor = match ($diary->type) {
        'call' => 'bg-blue-400',
        'email' => 'bg-green-400',
        'meeting' => 'bg-yellow-400',
        'document' => 'bg-red-400',
        default => 'bg-gray-400',
    };

@endphp

<div>
    <div class="flex gap-4 items-stretch">
        <div class="flex flex-col gap-1">
            <div @class([$wrapperIcon, "rounded-full p-1.5 shadow-lg sticky top-0"])>
                @switch($diary->type)
                    @case('call')
                        <x-heroicon-o-phone class="w-6 h-6 text-blue-400"/>
                        @break
                    @case('email')
                        <x-heroicon-o-envelope class="w-6 h-6 text-green-400"/>
                        @break
                    @case('meeting')
                        <x-heroicon-o-calendar class="w-6 h-6 text-yellow-400"/>
                        @break
                    @case('document')
                        <x-heroicon-o-document-text class="w-6 h-6 text-red-400"/>
                        @break
                    @case('note')
                        <x-heroicon-o-chat-bubble-left class="w-6 h-6 text-gray-400"/>
                        @break
                    @case('message')
                        <x-heroicon-o-envelope class="w-6 h-6 text-gray-400"/>
                        @break
                    @case('other')
                        <x-heroicon-o-exclamation-circle class="w-6 h-6 text-gray-400"/>
                        @break
                @endswitch
            </div>
            <div class="flex-1 w-full h-full relative mb-1.5">
                <i class="bg-gray-400 h-full w-px absolute top-0 left-1/2 -translate-x-1/2">
                </i>
            </div>
        </div>

        <div class="mb-8 flex-1">
            <div class="font-bold text-gray-500">
                {{ $diary->label_type_with_description }}
            </div>
            <div class="text-sm text-gray-500">
                {{ $diary->created_at->diffForHumans() }}
            </div>

            <div class="border rounded-xl p-4 mt-2 infolist-diary flex flex-col gap-4">
                @if($diary->type === 'call')
                    <div>
                        {{ $this->diaryInfolist }}
                    </div>
                @endif
                <div class="relative">
                    {{ $this->descriptionForm }}
                    <x-filament::loading-indicator
                        wire:loading.class="opacity-100"
                        class="opacity-0 absolute top-0 end-0 h-5 w-5"
                    />
                </div>

                {{ $this->diaryBottomInfolist }}
            </div>
        </div>
    </div>
    <x-filament-actions::modals/>
</div>
