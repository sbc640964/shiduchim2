<x-filament-widgets::widget>
    <x-filament::section>
        <div class="[&>div]:px-8 flex divide-x divide-x-reverse">
            <div class="flex items-center">
                <div class="flex gap-2 items-center">
                    @switch($this->record->billing_status)
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
                        @switch($this->record->billing_status)
                            @case('active')
                                פעיל
                            @break
                            @case('hold')
                            מושהה
                            @break
                                @case('pending')
                                <div>
                                    <div>ממתין</div>
                                    <span class="text-xs border-b border-dashed opacity-80 hover:opacity-100 cursor-pointer">
                                        <span
                                            x-data="{}"
                                            x-tooltip="{
                                                content: '{{ $record->billing_start_date->format('d/m/Y') }}',
                                                team: $store.team,
                                            }"
                                        >{{ $record->billing_start_date->diffForHumans() }}</span>
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
                <div><span class="text-gray-950 font-bold">משלם:</span> {{ $this->record->billingCard?->person->full_name }}</div>
                <div><span class="text-gray-950 font-bold">תאריך חיוב הבא:</span> {{ $this->record->billing_next_date?->format('d/m/Y') ?? "אין חיוב"}}</div>
            </div>

            <div class="text-gray-600">
                <div><span class="text-gray-950 font-bold">שדכן מטפל:</span> {{ $this->record->billingMatchmaker?->name ?? 'לא הותאם שדכן' }}</div>
                <div><span class="text-gray-950 font-bold">יום:</span> {{ $this->record->billing_matchmaker_day }}</div>
            </div>
            <div class="flex items-center ms-auto">
                @if($record->billing_notes)
                    <button class="p-4" x-data="{}">
                        <x-iconsax-lin-message
                            x-tooltip="{
                            content: '{{ $record->billing_notes }}',
                            team: $store.team,
                        }"
                            class="text-gray-600 w-6 h-6"></x-iconsax-lin-message>
                    </button>
                @endif
                {{ $this->editBilling() }}
            </div>
        </div>{{-- Widget content --}}
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
