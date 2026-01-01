@php
    /** @var \App\Models\Note $record */

    $isPublic = $record->visibility === \App\Enums\NoteVisibility::Public;

    $sharedNames = $record->sharedWithUsers
        ?->pluck('name')
        ->filter()
        ->values()
        ->all() ?? [];
@endphp

<x-filament::card>
    <div class="flex flex-col gap-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <x-filament::badge :color="$isPublic ? 'success' : 'gray'">
                        {{ $isPublic ? 'ציבורי' : 'אישי' }}
                    </x-filament::badge>

                    @if(filled($record->category))
                        <x-filament::badge color="primary">
                            {{ $record->category }}
                        </x-filament::badge>
                    @endif
                </div>

                <div class="text-xs text-gray-500">
                    <span>{{ $record->owner?->name ?? '—' }}</span>
                    <span class="px-1">•</span>
                    <span>{{ $record->created_at?->format('d/m/Y H:i') }}</span>

                    @if($record->comments_count !== null)
                        <span class="px-1">•</span>
                        <span>{{ $record->comments_count }} תגובות</span>
                    @endif

                    @if($record->files_count !== null)
                        <span class="px-1">•</span>
                        <span>{{ $record->files_count }} קבצים</span>
                    @endif
                </div>
            </div>

            @if($record->call)
                <div class="text-xs text-gray-500 whitespace-nowrap">
                    שיחה: {{ $record->call->phone ?? '—' }}
                </div>
            @endif
        </div>

        <div class="prose max-w-none dark:prose-invert max-h-48 overflow-hidden">
            {!! $record->content !!}
        </div>

        @if(! $isPublic)
            <div class="text-xs text-gray-500">
                <span class="font-medium">משותף עם:</span>
                <span>{{ count($sharedNames) ? implode(', ', $sharedNames) : 'לא שותף' }}</span>
            </div>
        @endif
    </div>
</x-filament::card>
