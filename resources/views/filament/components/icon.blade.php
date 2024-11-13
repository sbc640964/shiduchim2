@props(['icon', 'size' => 'md', 'color' => 'gray'])

@php
    $sizes = [
        'xs' => 'w-4 h-4',
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
        'xl' => 'w-10 h-10',
    ];

    $colors = [
        'gray' => 'text-gray-400',
        'blue' => 'text-blue-400',
        'green' => 'text-green-400',
        'red' => 'text-red-400',
        'yellow' => 'text-yellow-400',
    ];

    $bgColors = [
        'gray' => 'bg-gray-100',
        'blue' => 'bg-blue-100',
        'green' => 'bg-green-100',
        'red' => 'bg-red-100',
        'yellow' => 'bg-yellow-100',
    ];
@endphp

<div class="rounded-full" @class([$size, $bgColors])>

</div>
