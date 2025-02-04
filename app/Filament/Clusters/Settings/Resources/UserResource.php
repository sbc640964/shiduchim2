<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Services\PhoneCallGis\CallPhone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'משתמש';

    protected static ?string $pluralLabel = 'משתמשים';

    protected static ?string $navigationIcon = 'iconsax-bul-user';

    protected static ?string $navigationGroup = 'ניהול משתמשים';

    public static function form(Form $form): Form
    {
        $extensions = (new CallPhone())->getExtensions()->mapWithKeys(function ($ext) {
            $id = $ext->get('ex_number');
            $name = $ext->get('ex_name').' - '.$id;

            return [$id => $name];
        })->toArray();

        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('שם')
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->label('אימייל')
                    ->email()
                    ->required(),

                Forms\Components\Select::make('ext')
                    ->options($extensions)
                    ->native(false)
                    ->label('שלוחה GIS'),

                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Forms\Components\Fieldset::make('סיסמה')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->helperText('השאר ריק כדי לשמור את הסיסמה הנוכחית')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->rule('confirmed')
                            ->autocomplete('new-password'),

                        Forms\Components\TextInput::make('password_confirmation')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('שם')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('אימייל')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('תפקידים')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->iconButton()
                    ->tooltip('עריכה')
                    ->icon('iconsax-bul-edit-2')
                    ->modalHeading('עריכת משתמש')
                    ->using(function (User $record, array $data) {

                        if (blank($data['password'])) {
                            unset($data['password']);
                        }

                        $data = \Arr::except($data, ['roles', 'password_confirmation']);

                        $record->update($data);

                        return $record;
                    })
                    ->modalWidth('sm'),
                Tables\Actions\ActionGroup::make([
                    Impersonate::make()
                        ->view('filament-actions::grouped-action')
                        ->icon('iconsax-bul-brush-4')
                        ->requiresConfirmation()
                        ->label('התחברות כמשתמש')
                        ->redirectTo('/admin'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('iconsax-bul-trash')
                        ->hidden(fn (User $record) => $record->id === auth()->id())
                        ->color('danger'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('roles')
                        ->icon('iconsax-bul-user')
                        ->label('הוסף/הסר תפקידים')
                        ->form([
                            Forms\Components\ToggleButtons::make('action')
                                ->label('פעולה')
                                ->grouped()
                                ->default('1')
                                ->boolean(
                                    'הוסף',
                                    'הסר',
                                ),
                            Forms\Components\Select::make('roles')
                                ->label('תפקידים')
                                ->options(fn () => \App\Models\Role::pluck('name', 'name')->toArray())
                                ->multiple()
                                ->preload()
                                ->required()
                                ->searchable(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data, Tables\Actions\BulkAction $action) {
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
