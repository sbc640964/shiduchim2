<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div>
        @php($state = is_string($getState()) ? $getState() : ($getState()['url'] ?? null))

        @if (filled($state))
            <audio @if($showControls()) controls @endif class="w-full mt-2" @if($getAutoplay()) autoplay @endif>
                <source src="{{ $state }}" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        @else
            <div class="text-gray-500">
                {{ $getPlaceholder() }}
            </div>
        @endif
    </div>
</x-dynamic-component>
