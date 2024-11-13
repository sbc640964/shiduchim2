<x-filament-panels::page class="fi-resource-proposals">
<div class="flex">
    <div class="flex flex-grow -mx-8">
        <div class="w-full px-8" x-data="{
            isCollapsed: false
        }" :class="isCollapsed && 'fi-collapsed' ">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10">
                <header class="cursor-pointer overflow-hidden" x-on:click="isCollapsed = ! isCollapsed">
                    <div class="px-6 py-4">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Documents') }}
                        </h2>
                    </div>
                </header>
            </div>
        </div>

        <div class="w-full px-8">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10">
                <header>
                    <div class="px-6 py-4">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Documents') }}
                        </h2>
                    </div>
                </header>
            </div>
        </div>
    </div>

</div>
</x-filament-panels::page>
