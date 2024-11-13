<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div>
        @livewire('family-table', $getViewData())
    </div>
</x-dynamic-component>
