@use(\App\Models\Call)
@php
    /** @var Call $call */
    $call = $getRecord();

    $type = $call->direction;

    if($type === 'incoming' && !$call->started_at) {
        $type = 'missed';
    }

    switch ($type) {
        case 'incoming':
            $icon = 'phone-incoming';
            $color = 'text-green-600';
            $bgColor = 'bg-green-100';
            $label = 'Incoming call';
            break;
        case 'outgoing':
            $icon = 'phone-outgoing';
            $color = 'text-blue-600';
            $bgColor = 'bg-blue-100';
            $label = 'Outgoing call';
            break;
        case 'missed':
            $icon = 'phone-missed';
            $color = 'text-red-600';
            $bgColor = 'bg-red-100';
            $label = 'Missed call';
            break;
        default:
            $icon = 'phone';
            $color = 'text-gray-600';
            $bgColor = 'bg-gray-100';
            $label = 'Call';
    }

    $diaries = $call->getDiaries();
@endphp

<div x-data="{isExpended: false, toggle(){this.isExpended = !this.isExpended}}" class="w-full border-gray-200 transition-all duration-200 hover:bg-gray-50 {{ $call['isExpanded'] ? 'bg-gray-50' : '' }}" aria-expanded="{{ $call['isExpanded'] ? 'true' : 'false' }}">
    <button type="button"
            @click="toggle()"
            class="w-full text-start px-4 py-3 focus:outline-none focus:bg-gray-50"
            data-call-id="{{ $call->id }}"
            aria-label="{{ $label }} from {{ $call->phoneModel?->model?->full_name ?? 'אין שם' }}">
        <div class="flex items-start">
            <!-- Icon -->
            <div class="flex-shrink-0 p-2 rounded-full {{ $bgColor }} me-3">
                <x-icon name="{{ 'lucide-'.$icon }}" class="{{ $color }} size-5" />
            </div>

            <!-- Call info -->
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">
                        {{ $call->getDialName() }}
                    </h3>
                    <span class="text-xs text-gray-500 ms-2">
{{--                        created_at to ex. Today, 10:20--}}
                        {{ $call->created_at->diffForHumans() }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5">{{ $call->phone }}</p>
                <div class="flex items-center mt-1">
                    @if($call->duration)
                        <span class="text-xs 'text-gray-500">
                        {{ gmdate('H:i:s', $call->duration) }}
                    </span>
                    @endif

                    @if ($diaries->isNotEmpty())
                        <span class="ms-2 text-xs text-gray-500 truncate max-w-[200px]">
                            @if($diaries->count() === 1)
                                • {{ strlen($diaries->first()->first()['description']) > 25 ? \Illuminate\Support\Str::limit($diaries->first()['description'], 25) : $diaries->first()['description'] }}
                            @else
                                • {{ $diaries->count() }} תיעודים
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            <!-- Chevron icon -->
            @if($diaries->isNotEmpty())
                <div class="ms-2 flex-shrink-0">
                    <x-icon x-show="isExpended" name="lucide-chevron-up" class="text-gray-400 size-4" />
                    <x-icon x-show="!isExpended" name="lucide-chevron-down" class="text-gray-400 size-4" />
                </div>
            @endif
        </div>
    </button>

    <!-- Expanded content -->
    @if($diaries->isNotEmpty())
        <div x-show="isExpended" class="px-4 pb-3 pt-1 pl-14 animate-fadeIn">
            @foreach($diaries as $proposal)
                <div class="mb-2" x-data="{tooltip: `<div class='text-xs'>
                            <div class='font-semibold'>הבחור</div>
                            <p>{{$proposal->first()['guy_info']}}</p>
                            <div class='font-semibold mt-2'>הבחורה</div>
                            <p>{{$proposal->first()['girl_info']}}</p>
                        </div>`}">
                    <h4 class="text-xs font-medium text-gray-500 uppercase mb-1"
                        x-tooltip.html.max-width.350="tooltip">
                        {{ $proposal->first()['proposal_name'] }}
                    </h4>
                    <p class="text-xs text-gray-700 pb-1">
                        {{$proposal->pluck('description')->join(', ')}}
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</div>
