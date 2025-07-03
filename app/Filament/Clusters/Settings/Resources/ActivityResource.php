<?php

namespace App\Filament\Clusters\Settings\Resources;

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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('תאריך ושעה')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn ($state) => $state->hebcal()->hebrewDate() . ' | ' . $state->diffForHumans())
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->state(fn (Activity $record) => $record->user?->name ?? 'מערכת')
                    ->formatStateUsing(fn ($state): string => $state ?? 'מערכת')
                    ->label('משתמש')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->formatStateUsing(fn ($state, Activity $record) => $record->getSubjectTypeLabel() . ($record->subject ? " ({$record->subject->id})" : ''))
                    ->description(fn (Activity $record) => $record->getSubjectLabel())
                    ->label('פעילות')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('סוג פעילות')
                    ->formatStateUsing(fn ($state, Activity $record) => app($record->subject_type)::getDefaultActivityDescription($state))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('data')
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
                Tables\Filters\Filter::make('filter')
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
                            $indicators[] = Tables\Filters\Indicator::make('משתמש: ' . User::find($data['user'])?->name ?? 'לא נמצא');
                        }

                        if ($data['subject_type']) {
                            $indicators[] = Tables\Filters\Indicator::make('מודל: ' . static::$model::mapSubjectTypeLabel($data['subject_type']));
                        }

                        if ($data['type']) {
                            $indicators[] = Tables\Filters\Indicator::make('סוג פעילות: ' . ($data['subject_type']
                                ? app($data['subject_type'])::getDefaultsActivityDescription()[$data['type']] ?? $data['type'] : $data['type']));
                        }

                        return $indicators;
                    })
                    ->form([
                        Forms\Components\Select::make('user')
                            ->relationship('user', 'name')
                            ->label('משתמש'),
                        Forms\Components\Select::make('subject_type')
                            ->live()
                            ->placeholder('כל המודלים')
                            ->options([
                                'App\Models\Proposal' => 'הצעה',
                                'App\Models\Person' => 'אדם',
                                'App\Models\Diary' => 'תיעוד',
                                'App\Models\Subscriber' => 'מנוי',
                                'App\Models\User' => 'משתמש',
                            ])
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('type', null);
                            })
                            ->label('מודל'),
                        Forms\Components\Select::make('type')
                            ->placeholder('כל סוגי הפעילויות')
                            ->helperText('סוג הפעילות תלוי במודל שנבחר')
                            ->options(function (Forms\Get $get) {
                                $subjectType = $get('subject_type') ?? null;
                                if ($subjectType) {
                                    return app($subjectType)::getDefaultsActivityDescription();
                                }

                                return [];
                            })
                            ->label('סוג פעילות'),
                    ])

            ])
            ->actions([

            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([

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
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
