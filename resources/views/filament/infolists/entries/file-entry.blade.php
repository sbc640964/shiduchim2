<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if (filled($fileUrl()) && $fileExists())
        <div
            class="group/card-file rounded-xl flex  gap-2 ring-1 p-2 cursor-pointer hover:shadow-lg shadow-gray-50/10 transition shadow-sm relative ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10"
        >
            <div class="flex gap-2 flex-grow group-hover/card-file:flex-col">
                @switch($type())
                    @case("audio")
                        <div class="group-hover/card-file:h-5 group-hover/card-file:w-5 z-10 group-hover/card-file:absolute top-2 start-2 origin-top-right transition-all bg-pink-400 rounded-lg w-14 h-14 group-hover/card-file:rounded-sm flex justify-center items-center">
                            <x-bi-filetype-mp3 class="w-8 h-8 group-hover/card-file:scale-75 text-white"/>
                        </div>
                        <div class="w-0 group-hover/card-file:m0 -m-1 transition-all group-hover/card-file:w-full">
                            <audio controls class="w-full">
                                <source src="{{ $fileUrl() }}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        @break
                    @case("video")
                        <div>
                            <video controls class="w-full">
                                <source src="{{ $fileUrl() }}" type="video/mp4">
                                Your browser does not support the video element.
                            </video>
                        </div>
                        @break
                    @case("image")
                        <div
                            class="w-14 h-14 rounded-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10 bg-cover"
                            style="background-image: url({{ $fileUrl() }})">
                        </div>
                        @break
                    @case("file")
                        <div>
                            @switch($mimeType())
                                @case("application/pdf")
                                    <div class="bg-red-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-filetype-pdf class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @case("application/vnd.openxmlformats-officedocument.wordprocessingml.document")
                                    <div class="bg-blue-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-filetype-doc class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @case("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
                                    <div class="bg-green-700 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-filetype-xml class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @case("application/vnd.openxmlformats-officedocument.presentationml.presentation")
                                    <div class="bg-yellow-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-filetype-ppt class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @case("application/zip")
                                @case("application/x-rar-compressed")
                                @case("application/x-rar")
                                    <div class="bg-purple-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-file-zip class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @case('application/json')
                                    <div class="bg-yellow-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-bi-filetype-json class="w-8 h-8 text-white"/>
                                    </div>
                                    @break
                                @default
                                    <div class="bg-gray-400 rounded-lg w-14 h-14 flex justify-center items-center">
                                        <x-heroicon-o-document class="w-8 h-8 text-white"/>
                                    </div>
                            @endswitch
                        </div>
                        @break
                @endswitch

                @if(!$isExternalAudio())
                    <div class="text-sm">
                        {{ $getState('name', true) }}
                    </div>
                @else
                    <div class="text-sm">
                        הקלטה מהמערכת הטלפונית
                    </div>
                @endif
            </div>

            <div class="flex-1 flex justify-end gap-1">
                @if(in_array($type(), ['video', 'image']))
                    <x-filament::modal id="show-file-{{ $getState($getFileAttribute()) }}" :close-button="true"
                                       width="screen">
                        <x-slot name="trigger">
                            <x-filament::icon-button
                                icon="heroicon-o-eye"
                                tooltip="הצג קובץ"
                                color="gray"
                            />
                        </x-slot>

                        <x-slot name="heading">
                            <div class="text-lg font-bold">
                                {{ $getState('name', true) ?? 'קובץ' }}
                            </div>
                        </x-slot>

                        <div class="w-full h-full">
                            @switch($type())
                                @case("audio")
                                    <div>
                                        <audio controls class="w-full">
                                            <source src="{{ $fileUrl() }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                    @break
                                @case("video")
                                    <div>
                                        <video controls class="w-full">
                                            <source src="{{ $fileUrl() }}" type="video/mp4">
                                            Your browser does not support the video element.
                                        </video>
                                    </div>
                                    @break
                                @case("image")
                                    <img src="{{ $fileUrl() }}"
                                         class="w-full relative ring-gray-950/5 dark:bg-gray-900 dark:ring-withe/10 rounded-xl shadow-sm ring-1"/>
                                    @break
                            @endswitch
                        </div>
                    </x-filament::modal>
                @endif
                <x-filament::icon-button
                    icon="heroicon-o-arrow-down-tray"
                    tooltip="הורד קובץ"
                    href="{{ $fileUrl() }}"
                    download="{{ $getState('name', true) }}"
                    tag="a"
                    color="gray"
                    target="_blank"
                />

{{--                {{ $getAction('delete') }}--}}
            </div>
        </div>
    @else
        <div class="text-gray-500">
            @if($fileExists())
                {{ $getPlaceholder() }}
            @else
                {{ $getNotFoundMessage() }}
            @endif

        </div>
    @endif
</x-dynamic-component>
