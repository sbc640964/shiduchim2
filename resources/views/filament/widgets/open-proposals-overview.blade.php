<x-filament-widgets::widget>
    <div>
        <div class="flex gap-8">
            <x-filament::section
                heading="השידוכים הפתוחים שלך"
                class="[&_.fi-section-content]:flex-col [&_.fi-section-content]:flex flex flex-col [&_.fi-section-content]:h-full [&_.fi-section-content-ctn]:flex-grow"
            >
                <div
                    @if(auth()->user()->can('open_proposals_manager'))
                        wire:click="setCurrentUser({{ 0 }})"
                    @endif
                    class="flex-grow flex justify-center items-center"
                >
                    <p class="text-6xl pb-4 font-bold text-gray-400">{{ $this->currentUserProposals(true)->count() }}</p>
                </div>
                <div class="flex justify-center items-center flex-col mt-auto border-t pt-1">
                    <p class="text-sm text-gray-500">סה"כ שידוכים פתוחים</p>
                    <p class="text-xl font-bold text-gray-500 mt-2">
                        <span class="text-xl font-bold text-gray-500">{{ $this->otherUsersProposals()->sum('open_proposals') }}</span>
                    </p>
                </div>
            </x-filament::section>
            <div class="flex gap-6 flex-wrap">
                @foreach($this->otherUsersProposals() as $user)
                    <div
                        @if(auth()->user()->can('open_proposals_manager'))
                            wire:click="setCurrentUser({{ $user->getKey() }})"
                        @endif
                        class="bg-white flex items-center relative justify-center shadow-xl shadow-gray-300/25 rounded-full border border-gray-200/50 w-20 h-20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-xl font-bold text-gray-500">{{ $user->open_proposals }}</span>
                            </div>
                        </div>
                        @if(auth()->user()->can('open_proposals_manager'))
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs text-center whitespace-nowrap rounded-full px-2">
                                {{ $user->name }}
                            </div>
                        @endif
                        <span
                            wire:target="setCurrentUser({{ $user->getKey() }})"
                            wire:loading.flex
                            class="absolute hidden bg-white border rounded-full w-6 h-6 justify-center items-center -bottom-2 right-0">
                           <x-filament::loading-indicator class="w-4 h-4">
                            </x-filament::loading-indicator>
                        </span>

                        <x-filament::avatar
                            wire:target="setCurrentUser({{ $user->getKey() }})"
                            wire:loading.class="hidden"
                            src="{{ $user->avater_uri }}"
                            :circular="true"
                            class="absolute bg-white border -bottom-2 right-0"
                        />
                    </div>
                @endforeach
            </div>
        </div>
        @if($this->currentUserProposals()->count())
        <div class="mt-8 flex flex-col gap-3">
            <h4>
                <span class="font-bold text-gray-800">
                    @if(auth()->user()->can('open_proposals_manager') && $this->currentUserId)
                        ההצעות הפתוחות של {{ $this->currentUser()->name }}
                    @else
                        ההצעות הפתוחות שלך
                    @endif
                </span>
                <span class="text-gray-500">({{ $this->currentUserProposals()->count() }})</span>
            </h4>
            @foreach($this->currentUserProposals() as $proposal)
                <a
                    href="{{ \App\Filament\Resources\ProposalResource\Pages\ViewProposal::getUrl(['record' => $proposal->getKey()]) }}"
                    wire:navigate
                    class="flex justify-between text-gray-800 items-center bg-white z-0 shadow-sm hover:shadow-xl transition-shadow hover:z-10 duration-300 ease-in-out shadow-gray-300/25 rounded-lg p-4"
                >
                    <div>
                        <div class="font-bold">{{ $proposal->guy->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $proposal->guy->parents_info }}</div>
                    </div>

                    <div class="text-left">
                        <div class="font-bold">{{ $proposal->girl->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $proposal->girl->parents_info }}</div>
                    </div>
                </a>
            @endforeach
        </div>
        @endif
    </div>
</x-filament-widgets::widget>
