<div>
    <x-filament::icon-button
        icon="heroicon-o-envelope"
        badge="{{ $this->countUnreadMessages }}"
        icon-size="lg"
        color="gray"
        href="{{ \App\Filament\Pages\Inbox::getUrl() }}"
        tag="a"
    />
</div>
