import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import livewire from '@defstudio/vite-livewire-plugin';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/filament/families/theme.css',
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: false,
        }),
        livewire({  // <-- add livewire plugin
            refresh: ['resources/css/filament/families/theme.css'],  // <-- will refresh css (tailwind ) as well
        }),
    ],
});
