@props(['activities', 'people'])

<div>
    <table class="table-auto w-full">
        <thead>
        <tr>
            <th class="text-sm text-start px-3 py-1 bg-gray-100 rounded-s-md">תאריך</th>
            <th class="text-sm text-start px-3 py-1 bg-gray-100">משתמש</th>
            <th class="text-sm text-start px-3 py-1 bg-gray-100">פעולה</th>
            <th class="text-sm text-start px-3 py-1 bg-gray-100 rounded-e-md">נתונים</th>
        </tr>
        </thead>
        <tbody>
        @foreach($activities as $activity)
            <tr class="last:border-b">
                <td class="text-gray-800 text-sm px-3 py-2 border-t">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                <td class="text-gray-800 text-sm px-3 py-2 border-t">{{ $activity->user?->name ?? 'פעולה אוטומטית' }}</td>
                <td class="text-gray-800 text-sm px-3 py-2 border-t">{{ $activity->description }}</td>
                <td class="text-gray-800 text-sm px-3 py-2 border-t">
                    @switch($activity->type)
                        @case('married')
                            <div class="flex flex-col">
                                @php($spouse = $people->find($activity->data['spouse_id']))
                                <span class="text-gray-500">עם: {{ $spouse?->full_name }} ({{ $spouse?->id }})</span>
                            </div>
                            @break
                    @endswitch
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
