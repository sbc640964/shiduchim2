<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class UserAssignmentManagement extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'users';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-security-safe';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('pivot.timeout')
                    ->state(fn ($record) => $record->pivot->timeout ? Carbon::parse($record->pivot->timeout)->format('d/m/Y בשעה H:i') : 'לא מוגבל')
                    ->default('לא מוגבל')
                    ->label('סיום הרשאה'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('הוסף משתמש')
                    ->modalHeading('הוספת משתמשים'),
            ])
            ->recordActions([
                Action::make('edit-timeout')
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
                    ->schema(function (Schema $schema) {
                        return $schema->components([
                            DateTimePicker::make('timeout')
                                ->label('סיום הרשאה')
                                ->live()
                                ->displayFormat('d/m/Y בשעה H:i')
                                ->placeholder('ללא הגבלת זמן')
                                ->hintAction(Action::make('clear-timeout')
                                    ->label('ללא הגבלת זמן')
                                    ->visible(fn (Get $get) => $get('timeout') !== null)
                                    ->action(fn (Set $set) => $set('timeout', null))
                                    ->color('gray')
                                )
                                ->minDate(now()->addMinutes(1))
                                ->native(false),
                        ]);
                    })
                    ->action(fn ($record, $data) => $record->pivot->update($data)),
                DetachAction::make()
                    ->tooltip('הסר משתמש')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
