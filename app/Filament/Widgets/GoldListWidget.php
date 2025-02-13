<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StudentResource;
use App\Models\Person;
use App\Models\Proposal;
use App\Models\Subscriber;
use Carbon\Carbon;
use DB;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\TableWidget as BaseWidget;
use IbrahimBougaoua\FilaProgress\Tables\Columns\ProgressBar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoldListWidget extends BaseWidget
{
    protected static ?int $sort = -3;

    protected static ?string $heading = 'רשימת הזהב שלך';
    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('אין לך תלמידים ברשימת הזהב')
            ->emptyStateIcon('heroicon-o-list-bullet')
            ->emptyStateDescription('המנהל עדיין לא ייחד לך תלמידים')
            ->defaultSort('work_day')
            ->query(
                Subscriber::query()
                    ->with(['student' => function (BelongsTo $query) {
                        $query
                            ->withMax(['proposals as last_proposal' => function (Builder $q) {
                                $q->where('created_by', auth()->user()->id);
                            }], 'created_at')
                            ->addSelect([
                                'last_call' => DB::table('proposals')
                                    ->join('person_proposal', 'proposals.id', '=', 'person_proposal.proposal_id')
                                    ->leftJoin('diaries', function ($join) {
                                        $join->on('proposals.id', '=', 'diaries.proposal_id')
                                            ->where('diaries.type', 'call');
                                    })
                                    ->whereColumn('people.id', 'person_proposal.person_id')
                                    ->where('diaries.created_by', auth()->user()->id)
                                    ->whereNull('hidden_at')
                                    ->where('status', '!=', 'סגור')
                                    ->selectRaw('MAX(diaries.created_at)')
                            ]);
                    }])
                    ->where('user_id', auth()->user()->id)
                    ->whereIn('status', ['active'])
            )
            ->recordUrl(fn (Subscriber $record) => StudentResource::getUrl('proposals', [
                'record' => $record->person_id,
            ]))
            ->recordClasses(fn (Subscriber $record) => $record->work_day === (now()->weekday() + 1) ? 'bg-green-50' : '')
            ->columns([
                Columns\TextColumn::make('work_day')
                    ->label('יום')
                    ->badge()
                    ->color(fn (Subscriber $record) => $record->work_day === (now()->weekday() + 1) ? 'success' : Color::Sky)
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            1 => 'ראשון',
                            2 => 'שני',
                            3 => 'שלישי',
                            4 => 'רביעי',
                            5 => 'חמישי',
                            6 => 'שישי',
                            7 => 'מוצ"ש',
                            default => $state,
                        };
                    })
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('student.full_name')
                    ->label('שם מלא')
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),
                Columns\TextColumn::make('student.last_proposal')
                    ->dateTimeTooltip()
                    ->icon('heroicon-s-clock')
                    ->iconColor(function ($state) {
                        return Carbon::make($state)->isBetween(Carbon::now()->subDays(7), now()) ? 'gray' : 'danger';
                    })
                    ->formatStateUsing(function (?string $state = null) {
                        return $state ? Carbon::make($state)->diffForHumans(): '---';
                    })
                    ->label('יצירת הצעה אחרונה'),
                Columns\TextColumn::make('student.last_call')
                    ->dateTimeTooltip()
                    ->icon('heroicon-s-clock')
                    ->iconColor(function ($state) {
                        return Carbon::make($state)->isBetween(Carbon::now()->subDays(7), now()) ? 'gray' : 'danger';
                    })
                    ->formatStateUsing(function (?string $state = null) {
                        return $state ? Carbon::make($state)->diffForHumans(): '---';
                    })
                    ->label('שיחה אחרונה'),
                ProgressBar::make('balance_payments')
                    ->label('מצב התקופה')
                    ->tooltip(function (Subscriber $record) {
                        //תחזיר בטקסט בעברית כמה שבועות ימים או חודשים נותרו לפי הend_date
                        $text = '';

                        $diff = now()->diffInDays($record->end_date);
                        if ($diff < 0) {
                            $text = 'התקופה נגמרה';
                        } elseif ($diff < 7) {
                            $text = 'נשארו ' . $diff . ' ימים';
                        } else {
                            $text = 'נשארו ' . ceil($diff / 7) . ' שבועות';
                        }

                        $text .= ' עד לסיום התקופה (' . $record->end_date->format('d/m/Y') . ')';

                        return $text;
                    })
                    ->getStateUsing(function (Subscriber $record) {
                        return [
                            'total' => $record->payments,
                            'progress' => $record->balance_payments,
                        ];
                    })
            ]);
    }
}
