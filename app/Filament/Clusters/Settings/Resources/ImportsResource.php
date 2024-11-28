<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\ImportsResource\Pages;
use App\Filament\Clusters\Settings\Resources\ImportsResource\RelationManagers;
use App\Filament\Clusters\Settings\Resources\ImportsResource\RelationManagers\RowsRelationManager;
use App\Models\ImportBatch;
use App\Services\Imports\Students\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ImportsResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'העלאת נתונים';

    protected static ?string $pluralLabel = 'העלאת נתונים';

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->can('import_manager');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('סוג')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        if(! $get('file')) {
                            return;
                        }

                        HeadingRowFormatter::default('none');

                        $headers = (new HeadingRowImport)->toArray(\Arr::first($get('file')))[0][0];

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
                Forms\Components\FileUpload::make('file')
                    ->label('קובץ')
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ])
                    ->required(),

                Forms\Components\Fieldset::make('mapping')
                    ->label('מיפוי עמודות')
                    ->schema(function (Forms\Get $get, $livewire) {

                        if(! $get('file')) {
                            return [];
                        }

                        HeadingRowFormatter::default('none');

                        $headers = (new HeadingRowImport)->toArray(\Arr::first($get('file')))[0][0];

                        $selectOptions = array_combine($headers, $headers);

                        $fields = collect(match ($get('type')) {
                            'students' => Importer::fields(),
                            default => [],
                        });

                        return $fields->map(function ($field) use ($selectOptions, $fields) {
                            return Forms\Components\Select::make($field['name'])
                                ->label($field['label'])
                                ->options($selectOptions)
                                ->required($field['required'] ?? false);
                        })->toArray();
                    })
                    ->hidden(fn (Forms\Get $get) => ! $get('file'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('success')
                    ->color('success')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed')
                    ->color('danger')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('name'),
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
            'index' => Pages\ListImports::route('/'),
//            'create' => Pages\CreateImports::route('/create'),
            'edit' => Pages\EditImports::route('/{record}/edit'),
            'view' => Pages\ViewImports::route('/{record}'),
        ];
    }
}
