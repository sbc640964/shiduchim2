import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './app/Filament/Clusters/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/filament/**/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/filament/*.blade.php',
        './vendor/kenepa/banner/resources/**/*.php',
    ],
    plugins: [
        require('tailwind-scrollbar'),
    ]
}
