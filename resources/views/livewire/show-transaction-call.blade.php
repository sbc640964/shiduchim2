<div>
    <label class="font-bold text-sm mb-2">תמלול שיחה</label>

    @if($this->record->transcription)
        <div class="overflow-auto max-h-[calc(100vh-15rem)]">
            @php($spoken = '')
            @foreach($this->record->text_call as $item)
                @if(!$item)
                    <div class="mb-4 flex items-center justify-between p-2 bg-red-100 text-yellow-600 rounded-lg">
                        <span>לא נמצא טקסט לשיחה, יכול להיות שעוד לא פיענחנו, אולי עוד כמה דקות תנסה שוב, יש מצב?</span>
                    </div>
                @elseif($item['text'] === null)
                    <div class="mb-4 flex items-center justify-between p-2 bg-red-100 text-red-600 rounded-lg">
                        @if(isset($item['error']))
                            <span>{{ $item['error'] }}: </span> <span>{{ $item['status_message'] ?? '' }}</span>
                            <span>
                                @if(($this->reTranscriptionChunk)(['chunk_index' => $item['index']])->isVisible())
                                    {{ ($this->reTranscriptionChunk)(['chunk_index' => $item['index']]) }}
                                @endif
                        </span>
                        @else
                            <span>נראה שעוד לא פיענחנו את החלק הזה, כנס לכאן יותר מאוחר...</span>
                        @endif
                    </div>
                @else
                    <div @class([
                    "item-transcription-call",
                    'matchmaker-spoken-call' => ($item['spoken'] ?? '') === 'שדכן',
                    'parent-spoken-call' => ($item['spoken'] ?? '') === 'הורה',
                ])>
                        <div class="flex items-center mb-1">
                            @if($spoken !== ($item['spoken'] ?? ''))
                                <span class="font-bold text-sm">{{ $item['spoken'] ?? 'jhdgbg' }}</span>
                            @endif
                            <span class="text-xs text-gray-500 ml-2"></span>
                        </div>
                        <div class="text-xs">{{ $item['text'] }}</div>
                    </div>
                    @php($spoken = $item['spoken'] ?? '')
                @endif
            @endforeach
        </div>
    @else
        <div class="flex justify-center flex-col items-center gap-8 h-full w-full p-8">
            <p class="text-center text-sm text-gray-600">
                השיחה עדיין לא תומללה, ממש אומללה... <br>
                אתה מוזמן לתת לא צ'אנס.
            </p>
            {{ $this->parseTranscription }}
        </div>
    @endif

    <x-filament-actions::modals/>
</div>
