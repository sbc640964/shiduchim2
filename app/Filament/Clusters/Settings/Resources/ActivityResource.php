<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Clusters\Settings\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\ActivityResource\Pages;
use App\Filament\Clusters\Settings\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'לוג פעילות';

    protected static ?string $pluralLabel = 'לוג פעילויות';

    public static function canAccess(): bool
    {
        return auth()->user()->can('activity_log', Activity::class);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'subject' => function (MorphTo $morphTo) {
            $morphTo->constrain([
                'App\Models\Proposal' => fn ($query) => $query
                    ->with('girl', 'guy')
                    ->withoutGlobalScopes(['withoutHidden','withoutClosed']),
                'App\Models\Subscriber' => fn ($query) => $query->with('student', 'matchmaker'),
            ]);
        }]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('תאריך ושעה')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn ($state) => $state->hebcal()->hebrewDate() . ' | ' . $state->diffForHumans())
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->state(fn (Activity $record) => $record->user?->name ?? 'מערכת')
                    ->formatStateUsing(fn ($state): string => $state ?? 'מערכת')
                    ->label('משתמש')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->formatStateUsing(fn ($state, Activity $record) => $record->getSubjectTypeLabel() . ($record->subject ? " ({$record->subject->id})" : ''))
                    ->description(fn (Activity $record) => $record->getSubjectLabel())
                    ->label('פעילות')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('סוג פעילות')
                    ->formatStateUsing(fn ($state, Activity $record) => app($record->subject_type)::getDefaultActivityDescription($state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('data')
                    ->label('נתונים')
                    ->formatStateUsing(function (Activity $record) {
                        return str($record->renderDataToTable())->toHtmlString();
                    })
                    ->html()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->withIndicator()
                    ->label('טווח תאריכים'),
                Filter::make('filter')
                    ->modifyQueryUsing(fn (Builder $query, array $data): Builder => $query
                        ->when($data['subject_type'] ?? null, function (Builder $query, $value) {
                            $query->where('subject_type', $value);
                        })
                        ->when($data['type'] ?? null, function (Builder $query, $value) {
                            $query->where('type', $value);
                        })
                        ->when($data['user'] ?? null, function (Builder $query, $value) {
                            $query->where('user_id', $value);
                        })
                    )
                    ->indicateUsing(function (array $data, $livewire): array {
                        $indicators = [];

                        if ($data['user']) {
                            $indicators[] = Indicator::make('משתמש: ' . User::find($data['user'])?->name ?? 'לא נמצא');
                        }

                        if ($data['subject_type']) {
                            $indicators[] = Indicator::make('מודל: ' . static::$model::mapSubjectTypeLabel($data['subject_type']));
                        }

                        if ($data['type']) {
                            $indicators[] = Indicator::make('סוג פעילות: ' . ($data['subject_type']
                                ? app($data['subject_type'])::getDefaultsActivityDescription()[$data['type']] ?? $data['type'] : $data['type']));
                        }

                        return $indicators;
                    })
                    ->schema([
                        Select::make('user')
                            ->relationship('user', 'name')
                            ->label('משתמש'),
                        Select::make('subject_type')
                            ->live()
                            ->placeholder('כל המודלים')
                            ->options([
                                'App\Models\Proposal' => 'הצעה',
                                'App\Models\Person' => 'אדם',
                                'App\Models\Diary' => 'תיעוד',
                                'App\Models\Subscriber' => 'מנוי',
                                'App\Models\User' => 'משתמש',
                            ])
                            ->afterStateUpdated(function (Set $set) {
                                $set('type', null);
                            })
                            ->label('מודל'),
                        Select::make('type')
                            ->placeholder('כל סוגי הפעילויות')
                            ->helperText('סוג הפעילות תלוי במודל שנבחר')
                            ->options(function (Get $get) {
                                $subjectType = $get('subject_type') ?? null;
                                if ($subjectType) {
                                    return app($subjectType)::getDefaultsActivityDescription();
                                }

                                return [];
                            })
                            ->label('סוג פעילות'),
                    ])

            ])
            ->recordActions([

            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
