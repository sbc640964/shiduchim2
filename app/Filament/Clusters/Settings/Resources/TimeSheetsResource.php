<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource\Pages\ListTimeSheets;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource\Pages;
use App\Models\TimeDiary;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimeSheetsResource extends Resource
{
    protected static ?string $model = TimeDiary::class;

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-clock-1';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'יומן שעות';

    protected static ?string $pluralLabel = 'יומני שעות';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('start_at')
                    ->label('שעת התחלה')
                    ->required(),
                DateTimePicker::make('end_at')
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
                IconColumn::make('status')
                    ->icon(fn ($record) => ! $record->end_at ? 'iconsax-bul-clock-1' : null)
                    ->color('success')
                    ->alignCenter()
                    ->width('40px')
                    ->label('סטטוס'),
                TextColumn::make('user.name')
                    ->label('שם המשתמש')
                    ->visible(auth()->user()->can('manage_time_sheets_for_all_user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->width('250px')
                    ->formatStateUsing(fn ($record) => $record->start_at->translatedFormat('d/m/Y'))
                    ->description(fn ($record) => $record->start_at->translatedFormat('l').' | '.
                        $record->start_at->hebcal()->hebrewDate(false, true)
                    )
                    ->label('תאריך'),

                TextColumn::make('start_at')
                    ->label('שעת התחלה')
                    ->time(),
                TextColumn::make('end_at')
                    ->label('שעת סיום')
                    ->time(),
                TextColumn::make('sum_hours')
                    ->weight('bold')
                    ->label('סה"כ שעות')
                    ->alignEnd(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon('iconsax-bul-edit-2')
                    ->label('עריכה'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListTimeSheets::route('/'),
        ];
    }
}
