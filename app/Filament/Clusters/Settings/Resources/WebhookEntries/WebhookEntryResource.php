<?php

namespace App\Filament\Clusters\Settings\Resources\WebhookEntries;

use App\Filament\Clusters\Settings\Resources\WebhookEntries\Pages\ListWebhookEntries;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Call;
use App\Models\WebhookEntry;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Phiki\Grammar\Grammar;
use Phiki\Theme\Theme;

class WebhookEntryResource extends Resource
{
    protected static ?string $model = WebhookEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $label = 'Webhooks';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_webhook_entries');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
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
                    ->columns(1)
                    ->schema([
                        CodeEntry::make('headers_stack')
                            ->copyable()
                            ->copyMessage('התוכן הועתק בהצלחה!')
                            ->lightTheme(Theme::GithubLight)
                            ->label('כותרות')
                            ->grammar(Grammar::Json),
                        CodeEntry::make('body')
                            ->copyable()
                            ->copyMessage('התוכן הועתק בהצלחה!')
                            ->lightTheme(Theme::GithubLight)
                            ->label('גוף')
                            ->grammar(Grammar::Json),
                    ]),
                Fieldset::make('שגיאה')
                    ->columns(1)
                    ->schema([
                    CodeEntry::make('error')
                        ->copyable()
                        ->copyMessage('התוכן הועתק בהצלחה!')
                        ->lightTheme(Theme::GithubLight)
                        ->label('שגיאה')
                        ->grammar(Grammar::Json),
                ])->hidden(fn($record) => $record->is_completed),

                Section::make('מודל משוייך')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('model_type')
                            ->label('סוג מודל'),
                        TextEntry::make('model_id')
                            ->label('מזהה מודל'),
                        CodeEntry::make('model')
                            ->copyable()
                            ->copyMessage('התוכן הועתק בהצלחה!')
                            ->label('מודל')
                            ->lightTheme(Theme::GithubLight)
                            ->grammar(Grammar::Json)
                            ->getStateUsing(function (Call $state, $record) {
                                match ($state::class) {
                                    Call::class => $state->load('user:id,name', 'phoneModel.model'),
                                };

                                return $state->toArray();
                            })
                    ])->hidden(fn($record) => !$record->model_type || !$record->model_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll(fn() => session('webhook_entries_poll_interval') ? "10s" : null)
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
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('error')),
                Filter::make('filters')
                    ->schema([
                        KeyValue::make('body')
                            ->label('גוף הבקשה')
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $data['body'] ? $query->where(function ($q) use ($data) {
                        foreach ($data['body'] as $item) {
                            if (filled($item['value']) && filled($item['key']))
                                $q->where('body->' . $item['key'], 'like', '%' . $item['value'] . '%');
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
                    ->action(fn() => session(['webhook_entries_poll_interval' => !session('webhook_entries_poll_interval')]))
                    ->color(fn() => session('webhook_entries_poll_interval') ? 'success' : 'secondary'),

                ActionGroup::make([

                ]),
            ])->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalHeading(fn($record) => "וובהוק {$record->id}")
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
