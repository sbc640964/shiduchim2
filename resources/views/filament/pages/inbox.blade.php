<x-filament-panels::page>
    <div class="relative grid rounded-lg shadow overflow-hidden border bg-white grid-cols-3 h-[calc(100vh-200px)]">
        <div class="bg-white h-full border-e overflow-auto">
            <ul>
                @foreach($this->list as $discussionItem)
                    <li
                        wire:key="{{ $discussionItem->id }}"
                        wire:click="selectDiscussion({{ $discussionItem->id }})"
                        @class([
                            "cursor-pointer border-b last:border-b-0 border-gray-200",
                            'bg-blue-100/80' => $this->discussion === $discussionItem->id,
                            'hover:bg-gray-100' => $this->discussion !== $discussionItem->id,
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
                                        <span class="px-2 text-xs text-gray-500">תגובות: {{ $discussionItem->children_count }}</span>
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

            @if($this->list->hasPages())
                <div x-intersect.full="$wire.loadMore()" class="p-4">
                    <div wire:loading wire:target="loadMore" class="text-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-span-2 max-h-[calc(100vh-200px)] flex flex-col relative">
            @if($this->discussion)
                <livewire:discussion-messages
                    :discussion-id="$this->discussion"
                />
            @endif
            @if($this->discussion)
                <div
                    x-init="initListeners()"
                    class="bg-white border-t p-8"
                    x-data="{
            usersTyping: [],
            shift: false,
            typingTimeout: null,
            pause: false,
            submitMessage(event) {
                if(event && event.type === 'keydown' && event.key === 'Enter' && event.shiftKey) {
                    return;
                }
                this.handleTypingFinished();
                this.typingTimeout = null;
                if (this.pause) return;
                this.pause = true;
                $wire.sendMessage();
                setTimeout(() => {
                    this.pause = false;
                }, 1000);
            },
            initListeners() {
                Echo.private('chat.room.' + {{ $this->discussion }})
                    .listenForWhisper('typing', (e) => {
                        if (e.id !== {{ auth()->id() }}) {
                            if (! this.usersTyping.includes(e.id+'|'+e.name)) {
                                this.usersTyping.push(e.id+'|'+e.name);
                            }
                        }
                    })
                    .listenForWhisper('not-typing', (e) => {
                        if (e.id !== {{ auth()->id() }}) {
                            this.usersTyping = this.usersTyping.filter((name) => ! name.startsWith(e.id+'|'));
                        }
                    });
                },
                handleTypingFinished() {
                    Echo.private('chat.room.' + {{ $this->discussion }})
                        .whisper('not-typing', {
                            id: {{ auth()->id() }}
                    });
                }
           }"
                    x-on:keydown="

                        if(event.key === 'Enter') {
                            if(event.shiftKey) {
                            } else {
                                event.preventDefault();
                                submitMessage();
                                return;
                            }
                        }

                        typingTimeout && clearTimeout(typingTimeout);

                        if(! typingTimeout) {
                            Echo.private('chat.room.' + {{ $this->discussion }})
                                .whisper('typing', {
                                    id: {{ auth()->id() }},
                                    name: '{{ auth()->user()->name }}'
                                });
                        }
                        typingTimeout = setTimeout(() => {handleTypingFinished(); typingTimeout = null}, 3000);
                    "
                >
                    <form x-on:submit.prevent="submitMessage()">
                        {{ $this->answerForm }}

                        <div
                            class="text-xs text-gray-500 mt-1.5 ms-1 absolute"
                            x-show="usersTyping.length > 0"
                            x-text="usersTyping.map(name => name.split('|')[1]).join(' ו') + (usersTyping.length > 1 ? ' כותבים' : ' כותב') + '...'">
                        </div>

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
