<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages\ListCustomNotifications;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages\EditCustomNotification;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\RelationManagers;
use App\Models\CustomNotification;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomNotificationResource extends Resource
{
    protected static ?string $model = CustomNotification::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'הודעה';

    protected static ?string $pluralLabel = 'הודעות';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->label('כותרת')
                    ->required(),
                Select::make('type')
                    ->label('סוג')
                    ->options([
                        'info' => 'מידע',
                        'warning' => 'אזהרה',
                        'error' => 'שגיאה',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('סטטוס')
                    ->options([
                        'draft' => 'טיוטה',
                        'scheduled' => 'מתוזמן',
                        'sent' => 'נשלח',
                    ])
                    ->required(),
                RichEditor::make('body')
                    ->columnSpanFull()
                    ->label('תוכן ההודעה')
                    ->required(),
                Select::make('recipients')
                    ->relationship('recipientsUsers', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(100)
                    ->multiple()
                    ->placeholder('השאר ריק כדי לשלוח לכולם')
                    ->label('נמענים'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListCustomNotifications::route('/'),
//            'create' => Pages\CreateCustomNotification::route('/create'),
            'edit' => EditCustomNotification::route('/{record}/edit'),
        ];
    }
}
