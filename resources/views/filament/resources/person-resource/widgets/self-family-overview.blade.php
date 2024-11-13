<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6">
        <x-filament::section collapsible collapsed >
            <x-slot name="heading">
                <div class="font-bold text-gray-500 flex justify-between">
                    <div>
                        {{ $record->reverse_full_name }} {{ $record->spouse_info }}
                    </div>
                </div>
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::button
                    :badge="$record->family->children()->count()"
                    size="xs"
                    :outlined="true"
                    wire:click.stop="$parent.setCurrentTab('self')"
                >
                    הצג ילדים
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
