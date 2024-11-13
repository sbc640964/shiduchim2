<div
    class="h-16 group/card"
    wire:refresh-calls-box.window="$refresh"
>
    @if($this->hasCall())
        <div class="px-8 grid grid-cols-1 gap-2 h-16 bg-white transition-all group-hover/card:h-auto group-hover/card:rounded-b-xl group-hover/card:py-8 group-hover/card:shadow-2xl">
            <div class="items-center group-hover/card:flex-col flex gap-2">
                <div class="flex-shrink flex items-center">
                    <div class="rounded-full flex justify-center p-1 bg-success-100 items-center">
                        <x-iconsax-bul-call class="w-8 h-8 text-success-600"/>
                    </div>
                </div>
                <div class="group-hover/card:flex flex-col items-center">
                    <h3 class="leading-tight font-semibold">
                        {{ $this->getStatusLabel() }}
                    </h3>
                    <p class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $this->getDialName() }}
                    </p>
                </div>
            </div>

            <div class="hidden group-hover/card:flex items-center justify-center gap-2 text-center text-gray-600 text-sm">
                <div>
                    {{ $this->getCall()->phone }}
                </div>
                <div>
                    {{ $this->forceEndTheCall }}
                </div>
            </div>

            @foreach($this->getPersons() as $person)
                @if(($countProposals = $person->getProposalsCount()) > 0)
                    <div class="hidden group-hover/card:block">
                        <x-filament::button
                            size="sm"
                            icon="iconsax-bul-lamp-charge"
                            tag="a"
                            :outlined="true"
                            badge="{{ $countProposals }}"
                            href="{{ $person->getProposalsUrl() }}"
                        >
                            עבור להצעות הקשורות
                        </x-filament::button>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

        <x-filament-actions::modals />
</div>

