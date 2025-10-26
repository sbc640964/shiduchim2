@php
    use App\Filament\Resources\Proposals\ProposalResource;
    use App\Filament\Resources\Students\StudentResource;
@endphp
<div>
    @if(!$this->hiddenHeader)
        <header class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex-shrink flex items-center">
                    <div
                        @class(["rounded-full flex justify-center p-1 items-center", "bg-success-100" => !$this->call?->finished_at,  "bg-red-100" => $this->call?->finished_at])
                    >
                        <x-iconsax-bul-call @class([ "w-8 h-8", "text-success-600" => !$this->call?->finished_at,  "text-red-600" => $this->call?->finished_at])/>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold">
                        {{ $this->call?->getStatusLabel() }}
                    </h3>
                    <p class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $this->call?->getDialName() }}
                    </p>
                </div>
            </div>
            <x-filament::icon-button
                icon="heroicon-o-x-mark"
                color="gray"
                x-on:click="toggle(); !hasCall && $dispatch('reset-drawer-call')"
            />
        </header>
    @endif
    @if($this->isFamilyPhone())
        <div class="px-4 py-2 border-b border-gray-200">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="sideFamilyParent">
                    <option value="B">אבא: {{$this->call->phoneModel?->model->husband->full_name}}</option>
                    <option value="G">אמא: {{$this->call->phoneModel?->model->wife->full_name}}</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    @endif

    <div xmlns:x-filament="http://www.w3.org/1999/html">
        <x-filament::tabs contained="true">
            <x-filament::tabs.item
                wire:click="setActiveTab('proposals')"
                :active="$activeTab === 'proposals'"
            >הצעות
            </x-filament::tabs.item>
            <x-filament::tabs.item
                wire:click="setActiveTab('family')"
                :active="$activeTab === 'family'"
            >משפחה
            </x-filament::tabs.item>
            <x-filament::tabs.item
                wire:click="setActiveTab('calls')"
                :active="$activeTab === 'calls'"
            >שיחות אחרונות
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="py-4 px-6 text-gray-950">

            @if($activeTab === 'proposals')
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                            הצעות
                        </h2>
                        <div>
                            <x-filament::badge
                                wire:click="toggleHiddenProposals"
                                :spa-mode="true"
                            >
                                {{ $this->showHiddenProposals ? 'הסתר הצעות מוסתרות' : 'הצג הצעות מוסתרות' }}
                            </x-filament::badge>
                        </div>
                    </div>

                    <p class="text-gray-500 text-sm mt-2">
                        כאן תוכלו לתעד את השיחות שלכם עבור כל אחת מהצעות הקיימות עבור צאצאי נשוא השיחה
                    </p>
                </div>

                @foreach($this->getRelevantChildren() as $child)
                    <div class="mt-6 space-y-2">
                        <div class="flex justify-between items-center">
                            <div class="ps-1 text-xs font-semibold text-gray-500 flex items-center gap-1">
                                עבור {{ $child->first_name }}
                                <x-filament::badge size="sm">
                                    {{ $child->age }}
                                </x-filament::badge>
                            </div>
                            <div>
                                <x-filament::icon-button
                                    size="sm"
                                    icon="heroicon-o-plus"
                                    tag="a"
                                    :href="StudentResource::getUrl('add_proposal', ['record' => $child->getKey()])"
                                    tooltip="הוסף הצעה"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            @foreach($this->getProposalsFor($child) as $proposal)
                                <div x-on:click="toggle"
                                     class="cursor-pointer hover:bg-gray-50 transition flex justify-between border rounded-lg p-2.5 w-full"
                                     x-data="{
                            open: false,
                            key: {{ $proposal->getKey() }},
                            toggle(event) {
                                //check if target is any child of [x-show=open] element OR is it a link element
                                if(event.target.closest('[x-show=open]') || event.target.closest(['a[href]']) || event.target.tagName === 'A') {
                                    return
                                }

                                if(($wire.data?.[this.key]?.description ?? '').trim().length === 0) {
                                    this.open = !this.open
                                }
                                setTimeout(() => $refs.description.focus(), 10)
                            }
                        }">
                                    @php($skopen = $proposal->{$child->gender === 'B' ? 'girl' : 'guy'})
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-1">
                                            <div class="font-bold text-sm">
                                                {{ $skopen->full_name }}
                                            </div>
                                            <x-filament::badge size="xs" class="[&_span]:text-[10px] !px-1">
                                                {{ $skopen->age }}
                                            </x-filament::badge>
                                        </div>
                                        <div class="text-gray-700 text-xs">
                                            {{ $skopen->parents_info }}
                                            (בת {{  $skopen->mother->father?->full_name ?? 'לא ידוע' }})
                                        </div>

                                        <div x-show="open" class="-me-7 mt-2 flex flex-col gap-2">
                                            <div>
                                                {{ $this->getProposalForm($proposal) }}

                                                <div class="mt-8 flex justify-end">
                                                    <x-filament::button
                                                        size="xs"
                                                        wire:click="saveProposalDiary({{$proposal->getKey()}})"
                                                        class="w-full"
                                                        type="button">
                                                        שמור
                                                    </x-filament::button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ps-2 flex flex-col h-full">
                                        <x-filament::icon-button
                                            size="sm"
                                            icon="heroicon-o-eye"
                                            tag="a"
                                            color="gray"
                                            :href="ProposalResource::getUrl('view', ['record' => $proposal->getKey()])"
                                            tooltip="הצג להצעה"
                                        />
                                    </div>
                                </div>
                            @endforeach
                            @empty($this->proposals)
                                <div class="text-gray-500 text-sm">
                                    אין הצעות
                                </div>
                                <div>
                                    <x-filament::button
                                        :href="StudentResource::getUrl('add_proposal', ['record' => $child->getKey()])"
                                    >
                                        צור הצעה חדשה
                                    </x-filament::button>
                                </div>
                            @endempty
                        </div>
                    </div>
                @endforeach
            @endif


            @if($activeTab === 'family')
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">משפחה</h2>
                </div>

                <p class="text-gray-500 text-sm mt-4 text-center">
                    בקרוב תוכלו לראות כאן פרטים נוספים על המשפחה של המשוחח...
                </p>
            @endif


            @if($activeTab === 'calls')
                <div>
                    <h2 class="mb-4 text-xl font-bold tracking-tight text-gray-950 dark:text-white">שיחות</h2>
                </div>

                <livewire:active-call-last-calls :current-call="$this->call"/>

            @endif
        </div>
    </div>
</div>
