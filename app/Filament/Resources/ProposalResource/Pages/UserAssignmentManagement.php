<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class UserAssignmentManagement extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'users';

    protected static ?string $navigationIcon = 'iconsax-bul-security-safe';

    protected static ?string $title = 'הקצאת משתמשים';

    public static function getNavigationLabel(): string
    {
        return 'הקצאת משתמשים';
    }

    public static function canAccess(array $parameters = []): bool
    {
        /** @var Proposal $record */
        $record = $parameters['record'] ?? null;

        return $record && $record->userCanAccess();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('pivot.timeout')
                    ->state(fn ($record) => $record->pivot->timeout ? Carbon::parse($record->pivot->timeout)->format('d/m/Y בשעה H:i') : 'לא מוגבל')
                    ->default('לא מוגבל')
                    ->label('סיום הרשאה'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('הוסף משתמש')
                    ->modalHeading('הוספת משתמשים'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit-timeout')
                    ->icon('iconsax-bul-timer-1')
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('הגבל זמן')
                    ->fillForm(function ($record) {
                        return [
                            'timeout' => $record->pivot->timeout,
                        ];
                    })
                    ->modalWidth('sm')
                    ->modalHeading('הגבל זמן')
                    ->form(function (Form $form) {
                        return $form->schema([
                            Forms\Components\DateTimePicker::make('timeout')
                                ->label('סיום הרשאה')
                                ->live()
                                ->displayFormat('d/m/Y בשעה H:i')
                                ->placeholder('ללא הגבלת זמן')
                                ->hintAction(Forms\Components\Actions\Action::make('clear-timeout')
                                    ->label('ללא הגבלת זמן')
                                    ->visible(fn (Forms\Get $get) => $get('timeout') !== null)
                                    ->action(fn (Forms\Set $set) => $set('timeout', null))
                                    ->color('gray')
                                )
                                ->minDate(now()->addMinutes(1))
                                ->native(false),
                        ]);
                    })
                    ->action(fn ($record, $data) => $record->pivot->update($data)),
                Tables\Actions\DetachAction::make()
                    ->tooltip('הסר משתמש')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
