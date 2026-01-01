@php
    /** @var \App\Models\Note $record */

    $visibilityLabel = $record->visibility === \App\Enums\NoteVisibility::Public ? 'ציבורי' : 'אישי';
    $visibilityColor = $record->visibility === \App\Enums\NoteVisibility::Public ? 'success' : 'gray';

    $categoryLabel = $record->category?->getLabel() ?? 'הערה';
    $categoryColor = $record->category?->getColor() ?? 'gray';
    $categoryIcon = $record->category?->getIcon();

    $sharedWithNames = $record->sharedWithUsers?->pluck('name')->filter()->values()->all() ?? [];

    $excerpt = str($record->content ?? '')
        ->stripTags()
        ->squish()
        ->limit(180);
@endphp

<x-filament::card class="h-full w-full">
    <div class="flex h-full flex-col gap-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::badge :color="$categoryColor" class="flex items-center gap-1">
                        @if ($categoryIcon)
                            <x-filament::icon :icon="$categoryIcon" class="h-4 w-4" />
                        @endif

                        <span>{{ $categoryLabel }}</span>
                    </x-filament::badge>

                    <x-filament::badge :color="$visibilityColor">
                        {{ $visibilityLabel }}
                    </x-filament::badge>
                </div>

                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <span class="font-medium">{{ $record->owner?->name ?? '—' }}</span>
                </div>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                {{ $record->created_at?->diffForHumans() }}
            </div>
        </div>

        <div class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">
            {{ filled($excerpt) ? $excerpt : '—' }}
        </div>

        <div class="mt-auto flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            @if ($record->call_id)
                <div class="flex items-center gap-1">
                    <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                    <span>{{ $record->call?->phone ?? '—' }}</span>
                </div>
            @endif

            @if ($record->visibility === \App\Enums\NoteVisibility::Private)
                <div class="flex items-center gap-1">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4" />
                    <span>
                        @php
                            $names = array_slice($sharedWithNames, 0, 3);
                            $moreCount = max(count($sharedWithNames) - count($names), 0);
                        @endphp

                        {{ count($names) ? implode(', ', $names) : 'לא שותף' }}
                        @if ($moreCount)
                            +{{ $moreCount }}
                        @endif
                    </span>
                </div>
            @endif

            <div class="flex items-center gap-1">
                <x-filament::icon icon="heroicon-o-chat-bubble-bottom-center-text" class="h-4 w-4" />
                <span>{{ $record->comments_count ?? 0 }}</span>
            </div>

            <div class="flex items-center gap-1">
                <x-filament::icon icon="heroicon-o-paper-clip" class="h-4 w-4" />
                <span>{{ $record->files_count ?? 0 }}</span>
            </div>
        </div>
    </div>
</x-filament::card>
