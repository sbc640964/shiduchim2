<div
    x-data="{
        playSound: async function (event) {
            console.log(event.detail)
            if (event.detail.userId !== {{ auth()->id() }} && event.detail.eventType === 'new') {
                const audio = await new Audio('{{ asset('audio/new-notification-7-210334.mp3') }}')
                audio.play();
            }
        },
    }"
    x-init="Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            console.log('Notifications enabled');
        } else {
            console.warn('Notifications not enabled');
        }
    })"
    x-on:win-message-created.window="playSound"
>
    <x-filament::dropdown width="sm" teleport="true" placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-envelope"
                badge="{{ $this->getCountUnreadMessages() ?? null }}"
                icon-size="lg"
                color="gray"
                :tag="$this->getCountUnreadMessages() ? 'button' : 'a'"
                href="{{ $this->getCountUnreadMessages() ? null : \App\Filament\Pages\Inbox::getUrl() }}"
            />
        </x-slot>

        <x-filament::dropdown.list class="max-h-96 overflow-auto">
            @foreach($this->getUnreadMessages as $message)
                <x-filament::dropdown.list.item
                    tag="a"
                    class="cursor-pointer"
                    wire:key="{{ $message->id }}"
                    wire:click="openViewRoom({{ $message->parent->id }})"
                >
                    <div class="flex gap-2">
                        <div class="flex items-center flex-shrink-0">
                            <x-filament::avatar
                                class="border"
                                :src="$message->user->avater_uri"
                                size="lg"
                            />
                        </div>
                        <div class="overflow-hidden flex-grow">
                            <div class="flex mt-1 text-center gap-2 text-xs">
                                <p class="text-sm leading-tight font-semibold text-gray-700">{{ $message->user->name }}</p>
                                <p class="text-xs leading-normal text-gray-500">{{ $message->created_at->diffForHumans() }}</p>
                            </div>

                            <div>
                                <p class="font-semibold text-gray-600 text-xs text-ellipsis overflow-hidden">
                                    <span class="font-bold text-gray-800">{{ $message->parent->title }}</span>
                                    <br>
                                    {{ $message->content }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center pe-2 relative">
                            <x-filament::button
                                color="gray"
                                size="xs"
                                wire:click.prevent.stop="markAsRead({{ $message->id }})"
                            >
                                סמן כנקרא
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach

            <div
                 @class([
                 "sticky bottom-0 bg-white shadow-lg rounded-lg border",
                 'grid-cols-2 grid gap-2 divide-x rtl:divide-x-reverse' => $this->getUnreadMessages->isNotEmpty(),
                 ])
            >
                <x-filament::dropdown.list.item
                    tag="a"
                    href="{{ \App\Filament\Pages\Inbox::getUrl() }}"
                >
                    לכל ההודעות
                </x-filament::dropdown.list.item>

                @if($this->getUnreadMessages->isNotEmpty())
                    <x-filament::dropdown.list.item
                        wire:click="markAllAsRead"
                        class="cursor-pointer"
                    >
                        סמן הכל כנקרא
                    </x-filament::dropdown.list.item>
                @endif
            </div>
        </x-filament::dropdown.list>
    </x-filament::dropdown>

</div>
