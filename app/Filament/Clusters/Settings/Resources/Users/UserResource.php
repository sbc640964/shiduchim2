<?php

namespace App\Filament\Clusters\Settings\Resources\Users;

use App\Filament\Clusters\Settings\Resources\Users\Pages\ListUsers;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Arr;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\ToggleButtons;
use App\Models\Role;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use App\Services\PhoneCallGis\CallPhone;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use STS\FilamentImpersonate\Actions\Impersonate;

//use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $label = 'משתמש';

    protected static ?string $pluralLabel = 'משתמשים';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-user';

    protected static string | \UnitEnum | null $navigationGroup = 'ניהול משתמשים';

    public static function form(Schema $schema): Schema
    {
        $extensions = (new CallPhone())->getExtensions()->mapWithKeys(function ($ext) {
            $id = $ext->get('ex_number');
            $name = $ext->get('ex_name').' - '.$id;

            return [$id => $name];
        })->toArray();

        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('שם')
                    ->required(),

                TextInput::make('email')
                    ->label('אימייל')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),

                Select::make('ext')
                    ->options($extensions)
                    ->native(false)
                    ->label('שלוחה GIS'),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Fieldset::make('סיסמה')
                    ->columns(1)
                    ->schema([
                        TextInput::make('password')
                            ->helperText('השאר ריק כדי לשמור את הסיסמה הנוכחית')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->rule('confirmed')
                            ->autocomplete('new-password'),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->rule('exclude')
                            ->autocomplete('new-password')
                            ->label('אימות סיסמה'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('שם')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('אימייל')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('תפקידים')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->iconButton()
                    ->tooltip('עריכה')
                    ->icon('iconsax-bul-edit-2')
                    ->modalHeading('עריכת משתמש')
                    ->using(function (User $record, array $data) {

                        if (blank($data['password'])) {
                            unset($data['password']);
                        }

                        $data = Arr::except($data, ['roles', 'password_confirmation']);

                        $record->update($data);

                        return $record;
                    })
                    ->modalWidth('sm'),
                ActionGroup::make([
                    Impersonate::make()
                        ->icon('iconsax-bul-brush-4')
                        ->requiresConfirmation()
                        ->label('התחברות כמשתמש')
                        ->redirectTo('/admin'),
                    DeleteAction::make()
                        ->icon('iconsax-bul-trash')
                        ->hidden(fn (User $record) => $record->id === auth()->id())
                        ->color('danger'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('roles')
                        ->icon('iconsax-bul-user')
                        ->label('הוסף/הסר תפקידים')
                        ->schema([
                            ToggleButtons::make('action')
                                ->label('פעולה')
                                ->grouped()
                                ->default('1')
                                ->boolean(
                                    'הוסף',
                                    'הסר',
                                ),
                            Select::make('roles')
                                ->label('תפקידים')
                                ->options(fn () => Role::pluck('name', 'name')->toArray())
                                ->multiple()
                                ->preload()
                                ->required()
                                ->searchable(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data, BulkAction $action) {
                            $action = $data['action'] === '1';

                            if($action) {
                                $records->each(function (User $record) use ($data, $action) {
                                    $record->assignRole(...$data['roles']);
                                });
                            } else {
                                $records->each(function (User $record) use ($data, $action) {
                                    foreach ($data['roles'] as $role) {
                                        $record->removeRole($role);
                                    }
                                });
                            }
                        }),
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
            'index' => ListUsers::route('/'),
            //            'create' => Pages\CreateUser::route('/create'),
            //            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
