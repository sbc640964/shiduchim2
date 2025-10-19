@props(['heading' => 'מאחזר נתונים', 'description' => 'אוספים מידע... כבר איתך.'])

<div>
    <x-filament::section class="text-center justify-center">
        <x-slot name="heading">
            <div class="text-center flex flex-col items-center gap-1">
                <x-filament::loading-indicator/>
                {{ $heading }}
            </div>
        </x-slot>
        <x-slot name="description">
            {{ $description }}
        </x-slot>
    </x-filament::section>
</div>
