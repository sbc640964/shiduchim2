<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10">
    <header class="cursor-pointer overflow-hidden">
        <div class="px-6 py-4 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $student->full_name }}
            </h2>
            {{ ($this->addAction)(['model_id' => $student->id]) }}
        </div>
    </header>
    <section class="p-8">
        <div class="flex gap-4 mb-4">
            <div class="w-9"></div>
            <div class="flex-1">
            </div>
        </div>
        <div>
            @foreach($proposal->diaries->where('model_id', $student->id) as $diary)
                <livewire:diary-item :diary="$diary" :key="$diary->id" />
            @endforeach
        </div>
    </section>
    <x-filament-actions::modals/>
</div>
