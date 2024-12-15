<div class="flex-grow min-h-0 max-h-full flex flex-col">
    <div class="bg-white border-b">
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
        class="relative p-4 flex-grow min-h-0 max-h-full overflow-auto transition-opacity duration-300"
        :class="{'opacity-0': !showX, 'opacity-100': showX}"
        x-data="{
            lastReadMessageId: @entangle('lastReadMessageId').live,
            messages: @entangle('messages').defer,
            showX: false,
            scroll: (behavior) => {
                $el.scrollTo({
                    top: $el.scrollHeight,
                    behavior: behavior || 'auto'
                });
            }
        }"
        x-init="
              setTimeout(() => {
                scroll();
                showX = true;
              }, 100)
        "
        x-on:win-message-created.window="setTimeout(() => scroll('smooth'), 100)"

    >
            @if($this->messages->isEmpty())
                <div class="flex items-center justify-center h-full">
                    <p class="text-gray-500">אין הודעות</p>
                </div>
            @endif

            @foreach($this->messages as $message)
                <div wire:key="{{$message->id}}">
                    <div
                        @if(! $message->read_at)
                            x-intersect.full.once="$wire.markAsRead({{ $message->id }})"
                        @endif
                        @class([
                            "flex gap-3 p-3",
                            "flex-row-reverse" => $message->user_id === auth()->id(),
                        ])>
                        <x-filament::avatar
                            class="border mt-10"
                            :src="$message->user->avater_uri"
                            size="lg"
                        />
                        <div>
                            <p class="text-sm font-semibold text-gray-700 ps-1">{{ $message->user->name }}</p>
                            <p class="ps-1 pb-1 text-xs text-gray-500">{{ $message->created_at->diffForHumans() }}</p>
                            <span>
                                {{ $message->read_at ? 'נקרא' : 'לא נקרא' }}
                            </span>
                            <div @class([
                                "p-3 rounded-xl",
                                "bg-blue-100" => $message->user_id === auth()->id(),
                                "bg-gray-100" => $message->user_id !== auth()->id(),
                                ])>
                                <h3 class="font-semibold">
                                    {!! $message->content !!}
                                </h3>
                            </div>
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
    </div>
</div>

