<div>
    <div class="flex divide-x gap-y-4 rtl:divide-x-reverse rounded-lg flex-wrap border p-4 whitespace-nowrap">
        <div class="flex flex-col items-center text-sm px-4">
            <span class="inline-block text-sm text-gray-500">גיל:</span>
            <span class="inline-block text-sm">{{ $sideRecord->born_at ? $sideRecord->age :  'חסר מידע' }}</span>
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-gray-500">תאריך לידה:</span>
            @if($sideRecord->born_at)
                <span x-tooltip.raw="{{ $sideRecord->born_at->format('d/m/Y') ?? null }}" class="text-sm">{{ $sideRecord->born_at->hebcal()->hebrewDate(false, true)}}</span>
            @else
                <span class="text-sm">לא עודכן</span>
            @endif
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-sm text-gray-500">כתובת:</span>
            <span>{{ collect([$sideRecord->city?->name ?? null, $sideRecord->address])->filter()->join(', ') }}</span>
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-gray-500">מוסד נוכחי:</span>
            <span>{{ $sideRecord->schools->last()?->name ?? 'לא ידוע' }}</span>
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-gray-500">מוסד קודם:</span>
            <span>{{ $sideRecord->schools->reverse()->skip(1)->first()?->name ?? 'לא ידוע' }}</span>
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-gray-500">אחים מעל:</span>
            <span>{{ $sideRecord->born_at ? $sideRecord->olderSiblings()->count(): 'חסר מידע' }}</span>
        </div>
        <div class="flex flex-col items-center text-sm px-4">
            <span class="text-gray-500">שטיבל:</span>
            <span>{{ $sideRecord->father?->school->first() ? $sideRecord->father?->school->first()?->name : 'חסר מידע' }}</span>
        </div>
    </div>
    <div class="mt-4">
        @foreach(['father' => 'אבא', 'mother' => 'אמא'] as $relation => $label)
            <div class="border-b last:border-b-0">
                <div class="flex gap-2 py-1">
                    <div>{{ $label }}:</div>
                    <div class="font-bold flex-grow">{{ $sideRecord->{$relation}?->full_name }}</div>
                    <div>{{ $getAction('call-'.$sideRecord->{$relation}?->id)?->color('gray') }}</div>
                </div>
                <div class="grid grid-cols-1 bg-gray-100 rounded-md mb-2 lg:grid-cols-2 divide-x rtl:divide-x-reverse">
                    @php($currentRecord = $sideRecord->{$relation})
                    @if($currentRecord)
                        @foreach(['father' => 'סבא', 'mother' => 'סבתא'] as $relation => $label)
                            <div class="px-2">
                                <div class="flex gap-2 py-1">
                                    <div>{{ $label }}:</div>
                                    <div class="font-bold flex-grow">{{ $currentRecord->{$relation}?->full_name }}</div>
                                    <div>{{ $getAction('call-'.$currentRecord->{$relation}?->id)?->color('gray') }}</div>
                                </div>
                                <div class="grid grid-cols-2 divide-x rtl:divide-x-reverse">

                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach

    </div>
</div>
