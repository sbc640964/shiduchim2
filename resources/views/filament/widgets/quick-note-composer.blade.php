<x-filament::section>
    <x-slot name="heading">
        הערה חדשה
    </x-slot>

    <x-slot name="description">
        ברירת המחדל היא תיעוד אישי. אחרי השמירה אפשר להפוך לציבורי או לשתף משתמשים.
    </x-slot>

    <form wire:submit.prevent="create" class="flex flex-col gap-4">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                שמירה
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
