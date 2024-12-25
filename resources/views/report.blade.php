<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
</head>
<body class="antialiased">

<div class="max-w-6xl mx-auto mt-40 flex flex-col gap-20">

    <div class="text-center font-bold text-xl">
        {{ $user->name }}
    </div>
    @foreach($user->subscribers as $subscriber)
        @php
            /**
             * @var \App\Models\Person $subscriber
             */
        @endphp
        <div class="border rounded-xl overflow-hidden shadow">
            <div class="bg-purple-100 p-2">
                <div class="text-lg font-bold">{{ $subscriber->full_name }} <span class="text-sm text-gray-400">{{ $subscriber->external_code_students }}</span></div>
                <div class="text-gray-700">
                    {{ $subscriber->parentsFamily->husband->first_name }} | {{ $subscriber->address }}, {{ $subscriber->city->name }}
                </div>
            </div>
            <div class="grid grid-cols-4 gap-4 p-4">
                <div class="border rounded-lg p-4">
                    <div class="text-sm text-gray-700">מספר הצעות</div>
                    <div class="text-lg font-bold">{{ $subscriber->proposals->count() }}</div>
                </div>
                <div class="border rounded-lg p-4">
                    <div class="text-sm text-gray-700">מס' שיחות להורים</div>
                    <div class="text-lg font-bold">{{ $subscriber->proposals->sum(fn(\App\Models\Proposal $proposal) => $proposal->countSideCalls($subscriber->gender === 'G' ? 'girl' : 'guy')) }}</div>
                </div>
                <div class="border rounded-lg p-4">
                    <div class="text-sm text-gray-700">מס' שיחות להורי צד שני</div>
                    <div class="text-lg font-bold">{{ $subscriber->proposals->sum(fn(\App\Models\Proposal $proposal) => $proposal->countSideCalls($subscriber->gender === 'G' ? 'guy' : 'girl')) }}</div>
                </div>
            </div>
            <div class="p-4">
                <table class="table-auto w-full">
                    <thead>
                    <tr class="[&>th]:text-start [&>th]:px-3 [&>th]:py-1 text-sm [&>th]:bg-gray-100">
                        <th>שם המדובר</th>
                        <th>תאריך</th>
                        <th>תיעודים</th>
                        <th>שיחות</th>
                        <th>סטטוסים</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($subscriber->proposals as $proposal)
                        @php
                            /**
                             * @var \App\Models\Proposal $proposal
                             */
                        @endphp
                        <tr class="border-b [&>td]:px-3 [&>td]:py-1 text-sm text-gray-700">
                            <td>{{ $proposal->{$subscriber->gender === 'G' ? 'guy' : 'girl'}->full_name }}</td>
                            <td>{{ $proposal->created_at->format('d/m/Y') }}</td>
                            <td>{{ $proposal->diaries->count() }}</td>
                            <td>{{ $proposal->diaries->where('type', 'call')->count() }}</td>
                            <td>{{ $proposal->status }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

</body>
</html>
