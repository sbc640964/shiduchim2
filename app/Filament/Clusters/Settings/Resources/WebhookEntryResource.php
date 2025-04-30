<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\Pages;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\RelationManagers;
use App\Models\WebhookEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebhookEntryResource extends Resource
{
    protected static ?string $model = WebhookEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'Webhooks';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('מזהה'),
                Tables\Columns\TextColumn::make('url')
                    ->sortable()
                    ->label('כתובת'),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('הושלם')
                    ->boolean(),
            ])->filters([
                //
            ])->headerActions([

            ])->actions([
                //
            ])->bulkActions([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListWebhookEntries::route('/'),
        ];
    }
}
