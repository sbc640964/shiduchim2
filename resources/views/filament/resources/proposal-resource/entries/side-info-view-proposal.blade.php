<div>
    @foreach(['father' => 'אבא', 'mother' => 'אמא'] as $relation => $label)
        <div class="border-b last:border-b-0">
            <div class="flex gap-2 py-1">
                <div>{{ $label }}:</div>
                @php
                    Log::debug('test', [
                        'sideRecord' => $sideRecord,
                         'relation' => $relation,
                         'sideRecord->{$relation}' => $sideRecord->{$relation}
                    ]);
                @endphp
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
