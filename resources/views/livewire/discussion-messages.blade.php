<div class="relative bg-chat flex-grow min-h-0 max-h-full flex flex-col">
    <div class="z-[2] bg-white border-b flex justify-between items-center">
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

        <div class="pe-10">
            {{ $this->editRoomAction }}
        </div>
    </div>
    <div
        class="relative z-[2] p-4 flex-grow min-h-0 max-h-full overflow-auto transition-opacity duration-300"
        :class="{'opacity-0': !showX, 'opacity-100': showX}"
        x-data="{
            discussion: @entangle('discussionId').live,
            showX: false,
            windowFocus: true,
            editItems: {},
            editItemIds: [],
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
            },
            placeCaretAtEnd(el) {
                el.focus();
                if (typeof window.getSelection != 'undefined'
                    && typeof document.createRange != 'undefined') {
                    var range = document.createRange();
                    range.selectNodeContents(el);
                    range.collapse(false);
                    var sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                } else if (typeof document.body.createTextRange != 'undefined') {
                    var textRange = document.body.createTextRange();
                    textRange.moveToElementText(el);
                    textRange.collapse(false);
                    textRange.select();
                }
            },
            setMarksToMessagesThatInViewport() {
                const msgsElms = $el.querySelectorAll('&>div');
                msgsElms.forEach((msgElm) => {
                    const rect = msgElm.getBoundingClientRect();
                    if(rect.top >= 0 && rect.bottom <= window.innerHeight) {
                        const id = msgElm.getAttribute('wire:key');
                        $wire.markAsRead(id);
                    }
                });
            },
            selectedItemToEdit(id) {
                const el = $el.querySelector('#msg-' + id);

                if(this.editItemIds.includes(id)) {
                    el.innerHTML = this.editItems[id];
                    delete this.editItems[id];
                } else {
                    this.editItems = {...this.editItems, [id]: el.innerHTML};
                    $nextTick(() => {
                        this.placeCaretAtEnd(el);
                    });
                }
            }
        }"
        x-effect="editItemIds = Object.keys(editItems).map(Number)"
        x-init="initComponent()"
        x-on:focus.window="windowFocus = true; setMarksToMessagesThatInViewport()"
        x-on:blur.window="windowFocus = false"
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
            console.log($event.detail)
            if(($event.detail.room === discussion && $el.scrollTop >= ($el.scrollHeight - $el.offsetHeight - lastMessageHeight - 200)) || $event.detail.userId === {{ auth()->id() }}) {
                scroll('smooth');
            } else if($event.detail.room === discussion) {
                showAlertHasNewMessages = true;
            }
        }, 100)"

    >
            @if($this->discussionMessages->isEmpty())
                <div class="flex items-center justify-center h-full">
                    <p class="text-gray-500">אין הודעות</p>
                </div>
            @endif

            @foreach($this->discussionMessages as $message)
                @php
                    /** @var \App\Models\Discussion $message */

                    $isMyMessage = $message->user_id === auth()->id();
                @endphp

                <div wire:key="{{$message->id}}">
                    <div
                        @if(! $message->read_at)
                            x-intersect.full.once="windowFocus && $wire.markAsRead({{ $message->id }})"
                        @endif
                        @class([
                            "flex gap-3 p-3",
                            "flex-row-reverse" => $isMyMessage,
                        ])>
                        @if(! $isMyMessage)
                        <x-filament::avatar
                            class="border mt-10"
                            :src="$message->user->avater_uri"
                            size="lg"
                        />
                        @endif
                        <div class="flex flex-col">

                            @if( ! $isMyMessage)
                                <span class="text-sm font-semibold text-gray-700 ps-1">{{ $message->user->name }}</span>
                            @endif
                            <span
                                @class(["ps-1 pb-1 text-xs text-gray-500"])
                            >
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                                <div @class([
                                "p-3 rounded-xl relative group/item-message",
                                "bg-blue-100" => $isMyMessage,
                                "bg-gray-100" => ! $isMyMessage,

                                ])
                                x-bind:class="{
                                    'w-96 bg-white border border-blue-600 rounded-bl-sm': editItemIds.includes( {{ $message->id }} ),
                                }"
                            >
                                    @if($isMyMessage)
                                        <div x-bind:class="{ 'opacity-0': ! editItemIds.includes( {{ $message->id }} )}" class="absolute bg-white rounded-lg p-2 shadow -top-10 end-1 group-hover/item-message:opacity-100">
                                            <x-filament::icon-button
                                                :color="\Filament\Support\Colors\Color::Blue"
                                                x-show="!editItemIds.includes({{ $message->id }})"
                                                @click="selectedItemToEdit({{ $message->id }})"
                                                class="text-gray-400"
                                                size="sm"
                                                icon="heroicon-o-pencil"
                                            />
                                        </div>
                                    @endif

                                <div id="{{ 'msg-'.$message->id }}" class="whitespace-pre-line outline-none" x-bind:contenteditable="editItemIds.includes( {{ $message->id }})">{!! trim($message->content) !!}</div>
                            </div>
                                @if($isMyMessage)
                                    <template  x-if="editItemIds.includes( {{ $message->id }} )">
                                        <div
                                            class="flex justify-between w-full mt-2"
                                        >
                                            <div></div>
                                            <div class="flex gap-2">
                                                <x-filament::icon-button
                                                    :color="\Filament\Support\Colors\Color::Blue"
                                                    @click="selectedItemToEdit({{ $message->id }})"
                                                    size="sm"
                                                    icon="heroicon-o-x-mark"
                                                    loading-indicator
                                                >
                                                    ביטול
                                                </x-filament::icon-button>
                                                <x-filament::icon-button
                                                    wire:target="updateMessage"
                                                    @click="$wire.updateMessage({{ $message->id }}, $root.querySelector('#msg-{{ $message->id }}').innerHTML); selectedItemToEdit({{ $message->id }})"
                                                    :color="\Filament\Support\Colors\Color::Blue"
                                                    size="sm"
                                                    icon="heroicon-o-check"
                                                    target="updateMessage"
                                                >
                                                    שמירה
                                                </x-filament::icon-button>
                                            </div>
                                        </div>
                                    </template>
                                @endif

                            @if($isMyMessage)
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
                    @if($this->lastReadMessageId === $message->id && $message->id !== $this->discussionMessages->last()->id)
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

    <x-filament-actions::modals />
</div>

