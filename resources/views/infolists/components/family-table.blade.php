<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div>
        @livewire($getViewData()['view'] ?? 'family-table', $getViewData())
    </div>
</x-dynamic-component>
