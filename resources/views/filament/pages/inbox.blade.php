<x-filament-panels::page>
    <div class="relative grid rounded-lg shadow overflow-hidden border bg-white grid-cols-3 h-[calc(100vh-200px)]">
        <div class="bg-white h-full border-e">
            <ul>
                @foreach($this->getDiscussions() as $discussionItem)
                    <li
                        wire:click="selectDiscussion({{ $discussionItem->id }})"
                        @class([
                            "cursor-pointer border-b last:border-b-0 border-gray-200",
                            'bg-blue-100/80' => $this->currentDiscussion && $this->currentDiscussion->id === $discussionItem->id,
                            'hover:bg-gray-100' => $this->currentDiscussion && $this->currentDiscussion->id !== $discussionItem->id,
                        ])
                    >
                        <div class="p-4">
                            <div class="flex justify-between">
                                <div>
                                    <h3 class="font-semibold">
                                        {{ $discussionItem->title }}
                                    </h3>
                                    <div class="-mx-2 flex divide-x divide-x-reverse">
                                        <span class="px-2 text-xs text-gray-500">נפתח: {{ $discussionItem->created_at->diffForHumans() }}</span>
                                        <span class="px-2 text-xs text-gray-500">על ידי: {{ $discussionItem->user->name }}</span>
                                        <span class="px-2 text-xs text-gray-500">תגובות: {{ $discussionItem->children->count() }}</span>
                                        @if($discussionItem->lastChildren)
                                            <span class="px-2 text-xs text-gray-500">תגובה אחרונה: {{ $discussionItem->lastChildren->user->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    @if(! $discussionItem->read_at || ($discussionItem->lastChildren && ! $discussionItem->lastChildren->read_at))
                                        <x-filament::badge color="danger">
                                            חדש
                                        </x-filament::badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-span-2 max-h-[calc(100vh-200px)] flex flex-col">
            @if($this->currentDiscussion)
                <livewire:discussion-messages
                    :discussion="$this->currentDiscussion"
                />
            @endif
            @if($this->currentDiscussion)
                <div
                    class="bg-white border-t p-8"
                    x-data="{
            shift: false,
            typingTimeout: null,
            submitMessage() {
                this.handleTypingFinished();
                $wire.sendMessage();
            },
            handleTypingFinished() {
                Echo.private('chat.room.' + {{ $this->discussion }})
                    .whisper('not-typing', {
                        id: {{ auth()->id() }}
                    });
            }
        }"
                    x-on:keydown="
                clearTimeout(typingTimeout);

                if(! typingTimeout) {
                    Echo.private('chat.room.' + {{ $this->discussion }})
                        .whisper('typing', {
                            id: {{ auth()->id() }}
                        });
               }

                typingTimeout = setTimeout(handleTypingFinished, 3000);
            "
                >
                    <form x-on:submit.prevent="submitMessage()">
                        {{ $this->answerForm }}

                        <div class="pt-4 flex justify-end">
                            <x-filament::button type="submit">
                                שלח
                            </x-filament::button>
                        </div>
                    </form>
                </div>
        </div>
            @endif

{{--        <div class="absolute top-0 left-0 w-full h-full"  style="--}}
{{--            background-color: #ffffff;--}}
{{--            opacity: 0.1;--}}
{{--            background-image:  linear-gradient(135deg, #d0d0d0 25%, transparent 25%), linear-gradient(225deg, #d0d0d0 25%, transparent 25%), linear-gradient(45deg, #d0d0d0 25%, transparent 25%), linear-gradient(315deg, #d0d0d0 25%, #ffffff 25%);--}}
{{--            background-position:  5px 0, 5px 0, 0 0, 0 0;--}}
{{--            background-size: 5px 5px;--}}
{{--            background-repeat: repeat;"--}}
{{--        >--}}
{{--        </div>--}}
    </div>
</x-filament-panels::page>
