<div class="group/status flex flex-col px-3 py-4 w-full">

    @php
        use App\Filament\Resources\Students\StudentResource;
        /** @var \App\Models\Person $student */
        $student = $getRecord()->getSpoken($side);
        $status = $getRecord()->allowedBeDefinedStatuses($side)
                ->firstWhere('name', $getRecord()->{'status_'.$side});
    @endphp

    <div class="mb-1">
        <a
            @class(['!text-red-600' => $student->current_family_id])
            class="hover:underline hover:text-gray-700 transition decoration-dotted decoration-1 decoration-gray-400"
            href="{{ StudentResource::getUrl('proposals', ['record' => $student->id]) }}"
        >
            <div class="font-bold">
                {{ $student->full_name }}
                @if($student->current_subscription_matchmaker)
                    <span
                        class="inline-block ms-1 bg-yellow-100 ring-1 ring-yellow-600 rounded-full p-0.5 text-yellow-600">
                        <x-icon
                            class="w-3 h-3 focus:outline-none"
                            name="heroicon-o-user"
                            x-tooltip.raw="שדכן מטפל: {{ $student->current_subscription_matchmaker }}"
                        /></span>
                @endif
            </div>
        </a>
        <div class="text-sm">{{ $student->parents_info }}</div>
    </div>

    @if($getRecord()->userCanAccess())
        @php
            $action = "mountTableAction('create-diary-$side', '{$getRecord()->id}')"
        @endphp

        <div class="hover:bg-gray-100 hover:ring-1 p-0.5 pe-2 ring-gray-400 rounded-full flex items-center">
            <button class="w-full flex gap-1 items-center justify-between" wire:click.stop.prevent="{{ $action }}">
                <div class="leading-none">
                    <x-status-option-in-select
                        :status="$status ?? ['name' => 'אין סטטוס', 'color' => '#5d5d5d' ]"
                        :isColumn="true"
                        :target="$action"
                        :remove-background="true"
                    />
                </div>
                <span>
                    <x-icon name="iconsax-bul-edit-2" class="w-4 h-4 opacity-0 group-hover/status:opacity-100"/>
                </span>
            </button>
        </div>
    @endif
</div>
