<div class="h-16 flex items-center relative">
    @if($this->getActiveTime())
        <button
            wire:click="toggle"
            class="rounded-full p-2 bg-danger-100 ring-1 ring-danger-300 hover:opacity-80"
        >
            <x-heroicon-s-pause class="w-5 h-5 text-danger-500"/>
        </button>

        <div class="absolute left-1/2 bg-white -translate-x-1/2 -bottom-7 p-2 rounded-md ring-1 ring-gray-100 shadow-xl">
            <p
                x-data="{
                time: @entangle('current_seconds'),
                interval: null,
                resetInterval() {
                    clearInterval(this.interval)
                    this.interval = setInterval(() => { this.time = this.time + 1 }, 1000)
                }
                }"
                x-init="resetInterval()"
                class="text-sm font-bold text-gray-800"
                x-text="new Date(time * 1000).toISOString().substr(11, 8)"
            >

            </p>
        </div>
    @else
        <button
            wire:click="toggle"
            class="rounded-full p-2 bg-success-100 ring-1 ring-success-300 hover:opacity-80"
        >
            <x-heroicon-s-play class="w-5 h-5 text-success-500"/>
        </button>
    @endif
</div>
