<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\KeyValue;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\Pages\ListWebhookEntries;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\Pages;
use App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\RelationManagers;
use App\Models\Call;
use App\Models\WebhookEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'Webhooks';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_webhook_entries');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')
                ->label('נוצר בתאריך')
                ->dateTime('d/m/Y H:i:s'),
            Fieldset::make('כללי')->schema([
                TextEntry::make('url')->label('כתובת'),
                IconEntry::make('is_completed')
                    ->label('הושלם')
                    ->boolean(),
            ]),
            Fieldset::make('נתונים')
                ->schema([
                PrettyJsonEntry::make('headers_stack')
                    ->label('כותרות'),
                PrettyJsonEntry::make('body')
                    ->label('גוף'),
            ]),
            Fieldset::make('שגיאה')->schema([
                PrettyJsonEntry::make('error')
                    ->label('שגיאה'),
            ])->hidden(fn ($record) => $record->is_completed),

            Section::make('מודל משוייך')
                ->columns(2)
                ->schema([
                    TextEntry::make('model_type')
                        ->label('סוג מודל'),
                    TextEntry::make('model_id')
                        ->label('מזהה מודל'),
                    PrettyJsonEntry::make('model')->label('מודל')
                        ->getStateUsing(function (Call $state, $record) {
                            match ($state::class) {
                                 Call::class => $state->load('user:id,name', 'phoneModel.model'),
                            };

                            return $state->toArray();
                        })
                ])->hidden(fn ($record) => !$record->model_type || !$record->model_id),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll(fn () => session('webhook_entries_poll_interval') ? "10s" : null)
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('מזהה'),
                TextColumn::make('created_at')
                    ->sortable()
                    ->label('נוצר בתאריך')
                    ->dateTime('d/m/Y H:i:s'),
                TextColumn::make('url')
                    ->sortable()
                    ->label('כתובת'),
                IconColumn::make('is_completed')
                    ->label('הושלם')
                    ->boolean(),
                TextColumn::make('error->message')
                    ->label('שגיאה')
                    ->toggledHiddenByDefault()
                    ->toggleable(),
            ])->filters([
                Filter::make('is_completed')
                    ->label('יש שגיאה')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('error')),
                Filter::make('filters')
                ->schema([
                    KeyValue::make('body')
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
                Action::make('refresh')
                    ->label('ריענון אוטמטי')
                    ->iconButton()
                    ->outlined(false)
                    ->tooltip('ריענון אוטמטי')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => session(['webhook_entries_poll_interval' => !session('webhook_entries_poll_interval')]))
                    ->color(fn () => session('webhook_entries_poll_interval') ? 'success' : 'secondary'),

                ActionGroup::make([

                ]),
            ])->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalHeading(fn ($record) => "וובהוק {$record->id}")
                    ->icon('heroicon-o-eye')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
            'index' => ListWebhookEntries::route('/'),
        ];
    }
}
