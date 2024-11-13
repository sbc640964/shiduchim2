<div>
    <div class="font-semibold">
        {{ $state }}
    </div>
    @if($withFatherName ?? null)
        <div class="text-xs">
            בן {{ $record->father_name }} @if($withFatherName && $record->mother_name)  ו{{ $record->mother_name }} @endif
        </div>
    @endif
</div>
