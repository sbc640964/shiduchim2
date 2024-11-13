<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource\Pages;
use App\Models\TimeDiary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimeSheetsResource extends Resource
{
    protected static ?string $model = TimeDiary::class;

    protected static ?string $navigationIcon = 'iconsax-bul-clock-1';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'יומן שעות';

    protected static ?string $pluralLabel = 'יומני שעות';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_at')
                    ->label('שעת התחלה')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_at')
                    ->label('שעת סיום')
                    ->required(),
            ]);
    }

    public static function getWidgets(): array
    {
        return [

        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                if (auth()->user()->canAccessAllTimeSheets()) {
                    return $query;
                }

                return $query->where('user_id', auth()->id());
            })
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn ($record) => ! $record->end_at ? 'iconsax-bul-clock-1' : null)
                    ->color('success')
                    ->alignCenter()
                    ->width('40px')
                    ->label('סטטוס'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('שם המשתמש')
                    ->visible(auth()->user()->can('manage_time_sheets_for_all_user'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->width('250px')
                    ->formatStateUsing(fn ($record) => $record->start_at->translatedFormat('d/m/Y'))
                    ->description(fn ($record) => $record->start_at->translatedFormat('l').' | '.
                        $record->start_at->hebcal()->hebrewDate(false, true)
                    )
                    ->label('תאריך'),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('שעת התחלה')
                    ->time(),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('שעת סיום')
                    ->time(),
                Tables\Columns\TextColumn::make('sum_hours')
                    ->weight('bold')
                    ->label('סה"כ שעות')
                    ->alignEnd(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('iconsax-bul-edit-2')
                    ->label('עריכה'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListTimeSheets::route('/'),
        ];
    }
}
