<x-filament-widgets::widget>
    <x-filament::section>
        <div class="[&>div]:px-8 flex divide-x divide-x-reverse">
            <div class="flex items-center">
                <div class="flex gap-2 items-center">
                    @switch($this->getSubscription()->status)
                        @case('active')
                            <span class="bg-success-400 block w-5 h-5 rounded-full"></span>
                            @break
                        @case('hold')
                            <span class="bg-warning-400 block w-5 h-5 rounded-full"></span>
                            @break
                        @default
                            <span class="bg-danger-400 block w-5 h-5 rounded-full"></span>
                    @endswitch

                    <span class="text-sm text-gray-500">
                        @switch(trim($this->getSubscription()->status))
                            @case('active')
                                פעיל
                                @break
                            @case('hold')
                                מושהה
                                @break
                            @case('pending')
                                <div>
                                    <div>ממתין</div>
                                    <span
                                        class="text-xs border-b border-dashed opacity-80 hover:opacity-100 cursor-pointer">
                                        <span
                                            x-data="{}"
                                            x-tooltip="{
                                                content: '{{ $this->getSubscription()->start_date?->format('d/m/Y') ?? '' }}',
                                                team: $store.team,
                                            }"
                                        >{{ $this->getSubscription()->start_date?->diffForHumans() ?? '' }}</span>
                                    </span>
                                </div>
                                @break
                            @default
                                לא פעיל
                        @endswitch
                    </span>
                </div>
            </div>

            <div class="text-gray-600">
                <div><span
                        class="text-gray-950 font-bold">משלם:</span> {{ $this->getSubscription()->creditCard?->person->full_name }}
                </div>
                <div><span
                        class="text-gray-950 font-bold">תאריך חיוב הבא:</span> {{ $this->getSubscription()->next_payment_date?->format('d/m/Y') ?? "אין חיוב"}}
                </div>
            </div>

            <div class="text-gray-600">
                <div><span
                        class="text-gray-950 font-bold">שדכן מטפל:</span> {{ $this->getSubscription()->matchmaker?->name ?? 'לא הותאם שדכן' }}
                </div>
                <div><span class="text-gray-950 font-bold">יום:</span> {{ $this->getSubscription()->work_day_he }}</div>
            </div>
            <div class="flex items-center ms-auto">
                @if($this->getSubscription()->notes)
                    <button class="p-4" x-data="{}">
                        <x-iconsax-lin-message
                            x-tooltip="{
                            content: '{{ $this->getSubscription()->notes }}',
                            team: $store.team,
                        }"
                            class="text-gray-600 w-6 h-6"></x-iconsax-lin-message>
                    </button>
                @endif

                <div class="flex gap-2">
                    {{ $this->editBilling() }}
                    @if($this->toggleSubscription()->isVisible())
                        {{ $this->toggleSubscription() }}
                    @endif

                    @if($this->togglePublished()->isVisible())
                         {{ $this->togglePublished() }}
                    @endif
                </div>
            </div>
        </div>{{-- Widget content --}}
    </x-filament::section>

    <x-filament-actions::modals/>
</x-filament-widgets::widget>
