<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource\RelationManagers;
use App\Models\CustomNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomNotificationResource extends Resource
{
    protected static ?string $model = CustomNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'הודעה';

    protected static ?string $pluralLabel = 'הודעות';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->columnSpanFull()
                    ->label('כותרת')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('סוג')
                    ->options([
                        'info' => 'מידע',
                        'warning' => 'אזהרה',
                        'error' => 'שגיאה',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('סטטוס')
                    ->options([
                        'draft' => 'טיוטה',
                        'scheduled' => 'מתוזמן',
                        'sent' => 'נשלח',
                    ])
                    ->required(),
                Forms\Components\RichEditor::make('body')
                    ->columnSpanFull()
                    ->label('תוכן ההודעה')
                    ->required(),
                Forms\Components\Select::make('recipients')
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCustomNotifications::route('/'),
//            'create' => Pages\CreateCustomNotification::route('/create'),
            'edit' => Pages\EditCustomNotification::route('/{record}/edit'),
        ];
    }
}
