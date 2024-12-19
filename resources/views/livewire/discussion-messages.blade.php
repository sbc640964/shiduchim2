<div class="relative bg-chat flex-grow min-h-0 max-h-full flex flex-col">
    <div class="z-[2] bg-white border-b">
        <div class="p-4 flex flex-col gap-2">
            <h3 class="font-semibold text-xl">
                {{ $this->discussion->title }}
            </h3>
            <div>
                <div class="flex gap-1 items-center">
                    <span class="text-xs text-gray-500">משפתתפים: </span>
                    @foreach($this->discussion->usersAssigned as $user)
                        <x-filament::badge size="sm" :color="\Filament\Support\Colors\Color::Blue">
                            {{ $user->name }}
                        </x-filament::badge>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div
        class="relative z-[2] p-4 flex-grow min-h-0 max-h-full overflow-auto transition-opacity duration-300"
        :class="{'opacity-0': !showX, 'opacity-100': showX}"
        x-data="{
            discussion: @entangle('discussionId').live,
            showX: false,
            showAlertHasNewMessages: false,
            scroll: (behavior) => {
                $el.scrollTo({
                    top: $el.scrollHeight,
                    behavior: behavior || 'auto'
                });
            },
            selectedChanged(time = 0) {
                if(time > 0) {setTimeout(() => this.scroll(), time)} else {this.scroll()}
                setTimeout(() => {
                    this.showX = true;
                }, time + 100)
            },
            initComponent() {
               this.selectedChanged();
            }
        }"
        x-init="initComponent()"
        x-on:scroll="
            if($el.scrollTop >= ($el.scrollHeight - $el.offsetHeight - 10)) {
                showAlertHasNewMessages = false;
            }
        "

        x-on:discussion-selected.window="selectedChanged(100)"
        x-on:prepare-discussion-selected.window="showX = false; showAlertHasNewMessages = false;"

        x-on:win-message-created.window="setTimeout(() => {
            const msgsElms = $el.querySelectorAll('&>div');
            const lastMessageHeight = msgsElms[msgsElms.length - 1].clientHeight;
            if($event.detail.room === discussion && $el.scrollTop >= ($el.scrollHeight - $el.offsetHeight - lastMessageHeight - 200)) {
                scroll('smooth');
            } else {
                showAlertHasNewMessages = true;
            }
        }, 100)"

    >
            @if($this->messages->isEmpty())
                <div class="flex items-center justify-center h-full">
                    <p class="text-gray-500">אין הודעות</p>
                </div>
            @endif

            @foreach($this->messages as $message)
                @php
                    /** @var \App\Models\Discussion $message */
                @endphp

                <div wire:key="{{$message->id}}">
                    <div
                        @if(! $message->read_at)
                            x-intersect.full.once="$wire.markAsRead({{ $message->id }})"
                        @endif
                        @class([
                            "flex gap-3 p-3",
                            "flex-row-reverse" => $message->user_id === auth()->id(),
                        ])>
                        @if($message->user_id !== auth()->id())
                        <x-filament::avatar
                            class="border mt-10"
                            :src="$message->user->avater_uri"
                            size="lg"
                        />
                        @endif
                        <div class="flex flex-col">

                            @if($message->user_id !== auth()->id()) <span class="text-sm font-semibold text-gray-700 ps-1">{{ $message->user->name }}</span> @endif
                            <span
                                @class(["ps-1 pb-1 text-xs text-gray-500"])
                            >
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                            <div @class([
                                "p-3 rounded-xl",
                                "bg-blue-100" => $message->user_id === auth()->id(),
                                "bg-gray-100" => $message->user_id !== auth()->id(),
                                ])>
                                <div class="font-semibold whitespace-pre-line">{!! trim($message->content) !!}</div>
                            </div>
                            @if($message->user_id === auth()->id())
                                <div
                                    x-tooltip="tooltip"
                                    x-data="{ tooltip: '{{$message->otherUsersAsRead->pluck('name')->join(', ')}}' }"
                                    class="text-xs ps-2 text-gray-400 flex items-center gap-1 pt-1">
                                    @if($message->otherUsersAsRead->isEmpty())
                                        לא נקרא
                                    @elseif($message->otherUsersAsRead->count() === ($message->parent ?? $message)->usersAssigned->count() - 1)
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M2 12L7.25 17C7.25 17 8.66939 15.3778 9.875 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M8 12L13.25 17L22 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 7L12.5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        כולם
                                    @else
                                        {{ $message->otherUsersAsRead->count() }} / {{ ($message->parent ?? $message)->usersAssigned->count() - 1 }} נמענים
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @if($this->lastReadMessageId === $message->id && $message->id !== $this->messages->last()->id)
                        <div class="flex justify-center items-center">
                            <span class="h-px bg-success-500 w-full"></span>
                            <x-filament::badge class="flex-shrink-0 mx-2" size="sm" :color="\Filament\Support\Colors\Color::Green">
                                חדש
                            </x-filament::badge>
                            <span class="h-px bg-success-500 w-full"></span>
                        </div>
                    @endif
                </div>

            @endforeach
                <div x-show="showAlertHasNewMessages" class="w-full sticky bottom-4 left-0 z-[3] flex justify-center items-center">
                    <button
                        @click="scroll('smooth')"
                        class="flex items-center justify-center gap-2 p-2 bg-blue-500 text-white rounded-lg shadow-lg"
                    >
                        <x-heroicon-c-chevron-double-down class="w-4 h-4" />
                        <span>הודעות חדשות</span>
                        <x-heroicon-c-chevron-double-down class="w-4 h-4" />
                    </button>
                </div>
    </div>
</div>

