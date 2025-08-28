<div
    class="test-ttt h-16 group/card"
    wire:refresh-calls-box.window="$refresh"
    x-data="{ hasCall: {{$this->hasCall() ? 'true' : 'false'}}, toggle: () => $wire.isOpen = !$wire.isOpen }"
>
    @if($this->hasCall())
        <button x-on:click="toggle" class="hover:bg-slate-50 px-8 text-start grid grid-cols-1 gap-2 h-16 bg-white transition-all">
            <div wire:show="isOpen" class="items-center flex">
                <x-filament::button tag="a" :outlined="true" color="gray" class="whitespace-nowrap !block">
                    סגור חלון שיחה
                </x-filament::button>
            </div>

            <div wire:show="!isOpen" class="items-center flex gap-2">
                <div class="flex-shrink flex items-center">
                    <div class="rounded-full flex justify-center p-1 bg-success-100 items-center">
                        <x-iconsax-bul-call class="w-8 h-8 text-success-600"/>
                    </div>
                </div>
                <div class="flex-col items-center">
                    <h3 class="leading-tight font-semibold">
                        {{ $this->getStatusLabel() }}
                    </h3>
                    <p class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $this->getDialName() }}
                    </p>
                </div>
            </div>
        </button>
    @endif

        <x-filament-actions::modals />

        <div
            x-data="{ hasCall: {{$this->hasCall() ? 'true' : 'false'}}, toggle: () => $wire.isOpen = !$wire.isOpen }"
            wire:show="isOpen"
            class="drawer-opened fixed end-0 top-0 w-[420px] h-screen z-50 overflow-auto bg-white shadow-lg"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-full"
        >
            <livewire:active-call-drawer :current-call="$this->getCall()" :wire:key="auth()->id()"/>
        </div>
</div>

