<x-filament-widgets::widget>
    <x-filament::tabs label="Content tabs">
        <x-filament::tabs.item
            wire:click="$set('activeTab', 'info')"
            :active="$this->activeTab === 'info'"
        >
            כללי
        </x-filament::tabs.item>

        <x-filament::tabs.item
            wire:click="$set('activeTab', 'diaries')"
            :active="$this->activeTab === 'diaries'"
        >
            תיעודים
        </x-filament::tabs.item>

        <x-filament::tabs.item
            wire:click="$set('activeTab', 'calls')"
            :active="$this->activeTab === 'calls'"
        >
            שיחות
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if($this->activeTab === 'info')
        <div>
            מידע
        </div>
    @endif

    @if($this->activeTab === 'diaries')
        <x-filament-widgets::widgets
            class="mt-10"
            :columns="1"
            :widgets="[App\Filament\Resources\ProposalResource\Widgets\DiaryListWidget::make([
                'record' => $this->proposal,
                'datesRange' => $this->getFilter('dates_range'),
            ])]"
        />
    @endif

    @if($this->activeTab === 'calls')
        <x-filament-widgets::widgets
            class="mt-10"
            :columns="1"
            :widgets="[\App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets\CallsTableWidget::make([
                'proposal' => $this->proposal,
                'datesRange' => $this->getFilter('dates_range'),
            ])]"
        />
    @endif
</x-filament-widgets::widget>
