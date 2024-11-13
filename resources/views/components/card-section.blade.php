<div {{ $attributes->merge(['class' => '']) }}>
    <div class="bg-white rounded-xl overflow-hidden shadow-sm ring-1 relative ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10">
        <div class="scrollbar-thumb-gray-200/50 scrollbar-thin overflow-y-auto max-h-[65vh]">
            <header class="cursor-pointer sticky top-0 bg-white z-10 pb-4 w-full">
                <div class="px-6 py-4 flex justify-between items-center ">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $title }}
                    </h2>
                    {{ $headerActions }}
                </div>

                {{ $bootomHeader }}

            </header>
            <section class="p-8">
                {{ $slot }}
            </section>
        </div>
    </div>
</div>
