<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\Pages;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\RelationManagers;
use App\Models\WebhookEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Novadaemon\FilamentPrettyJson\Infolist\PrettyJsonEntry;

class WebhookEntryResource extends Resource
{
    protected static ?string $model = WebhookEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'Webhooks';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_webhook_entries');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('created_at')
                ->label('נוצר בתאריך')
                ->dateTime('d/m/Y H:i:s'),
            Infolists\Components\Fieldset::make('כללי')->schema([
                TextEntry::make('url')->label('כתובת'),
                Infolists\Components\IconEntry::make('is_completed')
                    ->label('הושלם')
                    ->boolean(),
            ]),
            Infolists\Components\Fieldset::make('נתונים')
                ->schema([
                PrettyJsonEntry::make('headers_stack')
                    ->label('כותרות'),
                PrettyJsonEntry::make('body')
                    ->label('גוף'),
            ]),
            Infolists\Components\Fieldset::make('שגיאה')->schema([
                PrettyJsonEntry::make('error')
                    ->label('שגיאה'),
            ])->hidden(fn ($record) => $record->is_completed),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll(fn () => session('webhook_entries_poll_interval') ? "10s" : null)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('מזהה'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->label('נוצר בתאריך')
                    ->dateTime('d/m/Y H:i:s'),
                Tables\Columns\TextColumn::make('url')
                    ->sortable()
                    ->label('כתובת'),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('הושלם')
                    ->boolean(),
                Tables\Columns\TextColumn::make('error->message')
                    ->label('שגיאה')
                    ->toggledHiddenByDefault()
                    ->toggleable(),
            ])->filters([
                Tables\Filters\Filter::make('is_completed')
                    ->label('יש שגיאה')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('error')),
                Tables\Filters\Filter::make('filters')
                ->form([
                    Forms\Components\KeyValue::make('body')
                        ->label('גוף הבקשה')
                ])
                    ->query(fn (Builder $query, array $data): Builder => $data['body'] ? $query->where(function ($q) use ($data) {
                        foreach ($data['body'] as $key => $value) {
                            if(filled($value) && filled($key))
                                $q->where('body->' . $key, 'like', '%' . $value . '%');
                        }
                    }) : $query),
            ])
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('ריענון אוטמטי')
                    ->iconButton()
                    ->outlined(false)
                    ->tooltip('ריענון אוטמטי')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => session(['webhook_entries_poll_interval' => !session('webhook_entries_poll_interval')]))
                    ->color(fn () => session('webhook_entries_poll_interval') ? 'success' : 'secondary'),

                Tables\Actions\ActionGroup::make([

                ]),
            ])->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalHeading(fn ($record) => "וובהוק {$record->id}")
                    ->icon('heroicon-o-eye')
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
