import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './app/Filament/Clusters/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/filament/**/**/*.blade.php',
        './resources/views/filament/tables/columns/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/filament/*.blade.php',
        './vendor/kenepa/banner/resources/**/*.php',
        './app/models/**/*.php',
        './app/livewire/**/*.php',
        './vendor/guava/calendar/resources/**/*.blade.php',
    ],
    plugins: [
        require('tailwind-scrollbar'),
    ]
}
