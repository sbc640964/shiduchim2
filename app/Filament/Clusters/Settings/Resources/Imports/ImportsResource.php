<?php

namespace App\Filament\Clusters\Settings\Resources\Imports;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Arr;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Fieldset;
use App\Filament\Clusters\Settings\Resources\Imports\Pages\ViewImports;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Clusters\Settings\Resources\Imports\Pages\ListImports;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Clusters\Settings\Resources\Imports\RelationManagers\RowsRelationManager;
use App\Models\ImportBatch;
use App\Services\Imports\Students\Importer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ImportsResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $label = 'העלאת נתונים';

    protected static ?string $pluralLabel = 'העלאת נתונים';

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->can('import_manager');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('type')
                    ->label('סוג')
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        if(! $get('file')) {
                            return;
                        }

                        HeadingRowFormatter::default('none');

                        $headers = (new HeadingRowImport)->toArray(Arr::first($get('file')))[0][0];

                        $selectOptions = array_combine($headers, $headers);

                        $fields = collect(match ($get('type')) {
                            'students' => Importer::fields(),
                            default => [],
                        });

                        $fields->map(fn ($field) =>
                            blank($get($field['name'])) && $set($field['name'], collect($selectOptions)->firstWhere(fn ($option) => in_array($option, $field['guesses'] ?? [])))
                        );
                    })
                    ->options([
                        'students' => 'תלמידים',
                        'teachers' => 'עדכון גור',
                    ]),
                FileUpload::make('file')
                    ->label('קובץ')
                    ->hiddenOn('edit')
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ])
                    ->required(),

                Fieldset::make('mapping')
                    ->label('מיפוי עמודות')
                    ->schema(function (Get $get, $livewire) {

                        if($livewire instanceof ViewImports) {
                            $headers = $livewire->getRecord()->headers ?? [];
                        } else {
                            $file = $get('file');

                            if(! $file || (is_array($file) && count($file) === 0)) {
                                return [];
                            }

                            HeadingRowFormatter::default('none');
                            $headers = (new HeadingRowImport)->toArray(Arr::first($file))[0][0];
                        }

                        if(count($headers) === 0) {
                            return [];
                        }

                        $selectOptions = array_combine($headers, $headers);

                        $fields = collect(match ($get('type')) {
                            'students' => Importer::fields(),
                            default => [],
                        });

                        return $fields->map(function ($field) use ($livewire, $selectOptions, $fields) {
                            return Select::make($field['name'])
                                ->label($field['label'])
                                ->when($livewire instanceof ViewImports, fn (Select $component) =>
                                    $component->statePath(
                                    'options.mapping.' . $field['name']
                                    ))
                                ->options($selectOptions)
                                ->required($field['required'] ?? false);
                        })->toArray();
                    })
                    ->hidden(fn (Get $get, $livewire) => ! $get('file') && !($livewire instanceof ViewImports))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('total')
                    ->badge()
                    ->sortable(),
                TextColumn::make('success')
                    ->color('success')
                    ->badge()
                    ->sortable(),
                TextColumn::make('failed')
                    ->color('danger')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImports::route('/'),
//            'create' => Pages\CreateImports::route('/create'),
//            'edit' => Pages\EditImports::route('/{record}/edit'),
            'view' => ViewImports::route('/{record}'),
        ];
    }
}
