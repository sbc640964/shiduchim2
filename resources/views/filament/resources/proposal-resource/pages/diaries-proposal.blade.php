<x-filament-panels::page class="fi-resource-proposals diary-manage">
    <div class="content-diaries">
        <div class="-mx-4 grid  xl:grid-cols-3 grid-cols-1">
            <x-card-section :title="$record->guy->full_name" class="w-full px-4">
                <x-slot:headerActions>
                    <x-filament::icon-button
                            tooltip="הוסף תיעוד"
                            icon="heroicon-o-plus"
                            wire:click="mountAction('create-new-diary', {model_id: {{ $record->guy->id }}})"
                            wire:ignore.self
                    />
                </x-slot:headerActions>

                <x-slot:bootomHeader>
                    <div class="flex-1 px-4" data-record-id="{{ $record->guy->id }}">
                        {{ $this->simple }}
                    </div>
                </x-slot:bootomHeader>

                @foreach($record->diaries->where('model_id', $record->guy->id) as $diary)
                    <livewire:diary-item :diary="$diary" wire:key="{{$diary->id}}"/>
                @endforeach
            </x-card-section>

            <x-card-section :title="$record->girl->full_name" class="w-full px-4">
                <x-slot name="headerActions">
                    <x-filament::icon-button
                            tooltip="הוסף תיעוד"
                            icon="heroicon-o-plus"
                            wire:click="mountAction('create-new-diary', {model_id: {{ $record->girl->id }}})"
                            wire:ignore.self
                    />
                </x-slot>
                <x-slot name="bootomHeader">
                    <div class="flex-1 px-4" data-record-id="{{ $record->girl->id }}">
                        {{ $this->simple }}
                    </div>
                </x-slot>

                @foreach($record->diaries->where('model_id', $record->girl->id) as $diary)
                    <livewire:diary-item :diary="$diary" wire:key="{{$diary->id}}"/>
                @endforeach

            </x-card-section>

            <x-card-section title="תיעוד כללי" class="w-full px-4">
                <x-slot name="headerActions">
                    <x-filament::icon-button
                            tooltip="הוסף תיעוד"
                            icon="heroicon-o-plus"
                            wire:click="mountAction('create-new-diary')"
                            wire:ignore.self
                    />
                </x-slot>
                <x-slot name="bootomHeader">
                    <div class="flex-1 px-4">
                        {{ $this->simple }}
                    </div>
                </x-slot>

                @foreach($record->diaries->where('model_type', \App\Models\Old\Proposal::class) as $diary)
                    <livewire:diary-item :diary="$diary" wire:key="{{$diary->id}}"/>
                @endforeach

            </x-card-section>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('newDiaryDescription', {
                value: '',

                set(value) {
                    this.value = value
                }
            })
        })
    </script>
    <x-filament-actions::modals/>
</x-filament-panels::page>
