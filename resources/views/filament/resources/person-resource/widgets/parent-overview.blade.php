<x-filament-widgets::widget>
    <div>
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="font-bold text-gray-500 flex justify-between">
                    <div>
                        הורים
                    </div>
                </div>
            </x-slot>

            <x-slot name="headerEnd">
                @if($countChildren = $record->parentsFamily?->children()?->count() ?? null)
                    <x-filament::button
                        :badge="$countChildren"
                        size="xs"
                        :outlined="true"
                        wire:click.stop="$parent.setCurrentTab('father')"
                    >
                        הצג ילדים
                    </x-filament::button>
                @endif
            </x-slot>

            <div class="flex gap-4 mt-4 items-stretch">
                <div class="flex-1 p-3 rounded-xl hover:bg-gray-50">
                    <div class="text-sm text-gray-500">
                        אבא
                    </div>
                    <div class="text-lg font-semibold">
                        {{ $record->father?->reverse_full_name ?? '' }}
                    </div>
                    <div class="text-sm font-semibold">
                        ב"ר {{ $record->father?->father?->first_name ?? '' }}
                    </div>
                </div>
                <div class="my-4">
                    <div class="w-0 border-s border-gray-200 min-h-full"></div>
                </div>
                <div class="flex-1 p-3 rounded-xl hover:bg-gray-50">
                    <div class="text-sm text-gray-500">
                        אמא
                    </div>
                    <div class="text-lg font-semibold">
                        {{ $record->mother?->reverse_full_name ?? '' }}
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
