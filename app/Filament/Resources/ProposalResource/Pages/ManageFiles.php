<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use App\Models\Old\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ManageFiles extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'files';

    protected static ?string $title = 'קבצים';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return 'קבצים';
    }

    public function getRelationship(): Relation|Builder
    {
        return match ($this->activeTab) {
            'girl' => $this->getOwnerRecord()->girl->files(),
            'guy' => $this->getOwnerRecord()->guy->files(),
            default => parent::getRelationship(),
        };
    }

    public function getTabs(): array
    {
        return [
            'guy' => Tab::make('guy')->label('בחור'),
            'girl' => Tab::make('girl')->label('בחורה'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('path')
                    ->imageEditor()
                    ->openable()
                    ->previewable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->getStateUsing(function ($record) {
                        $state = $record->path;

                        if (! $state) {
                            return null;
                        }
                        $fileExtension = pathinfo($state, PATHINFO_EXTENSION);

                        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                            return $state;
                        }

                        return null;
                    })
                    ->checkFileExistence()
                    ->defaultImageUrl('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>')
                    ->label('קובץ'),

                Tables\Columns\TextColumn::make('file_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'תמונה' => 'success',
                        'סרטון' => 'warning',
                        'שיר' => 'primary',
                        'קובץ אחר' => 'gray',
                        'מסמך' => 'danger',
                        default => 'default',
                    })
                    ->label('סוג'),

                Tables\Columns\TextColumn::make('name')
                    ->label('שם'),

                Tables\Columns\TextColumn::make('description')
                    ->label('תיאור'),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('lg')
                    ->label('הוסף קובץ'),
            ])
            ->actions([
                //                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('lg')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('email')
                    ->label('שלח מייל')
                    ->icon('heroicon-o-envelope')
                    ->form(fn ($form) => $this->senEmailForm($form))
                    ->action(function ($records, self $livewire, array $data) {
                        $livewire->sendEmail($records, $data);
                    }),

                //                Tables\Actions\BulkActionGroup::make([
                //                    Tables\Actions\DeleteBulkAction::make(),
                //                    Tables\Actions\BulkAction::make('email')
                //                        ->label('שלח מייל')
                //                        ->icon('heroicon-o-envelope')
                //                        ->action(fn () => null),
                //                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query);
    }

    public function senEmailForm($form)
    {
        return $form
            ->schema([
                Forms\Components\Select::make('contact')
                    ->autofocus()
                    ->label('איש קשר')
                    ->options(ManageContacts::queryAllContacts($this->getOwnerRecord())
                        ->get()->pluck('name', 'id'))
                    ->searchable()
                    ->helperText('')
                    ->live()
                    ->required(),
                Forms\Components\Split::make([
                    Forms\Components\TextInput::make('email')
                        ->label('אימייל')
                        ->email()
                        ->hidden(function (Forms\Get $get, Forms\Set $set, Forms\Components\TextInput $component) {
                            if (! $get('contact')) {
                                return true;
                            }

                            $contact = Contact::find($get('contact'));

                            if ($contact->email) {
                                $component->disabled(fn ($get) => $contact?->email);
                                $set('email', $contact?->email ?? null);

                                return false;
                            }

                            $component->helperText('לא נמצא אימייל לאיש קשר זה');

                            return false;
                        })
                        ->required(),
                ]),
                Forms\Components\TextInput::make('subject')
                    ->default($this->activeTab === 'girl'
                        ? $this->getOwnerRecord()->girl->full_name
                        : $this->getOwnerRecord()->guy->full_name)
                    ->label('נושא')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('body')
                    ->label('גוף המייל')
                    ->default('בהמשך לשיחתנו מצורפים הקבצים/תמונות')
                    ->required(),

            ]);
    }

    public function sendEmail($records, array $data)
    {
        $contact = Contact::find($data['contact']);

        $email = $data['email'] ?? $contact->email;

        $subject = $data['subject'];

        $body = $data['body'];

        $files = $records->map(fn ($record) => $record->path)->toArray();

        $contact->sendEmail($email, $subject, $body, $files);

        $this->notify('success', 'המייל נשלח בהצלחה');

        $this->emit('refresh');
    }
}
