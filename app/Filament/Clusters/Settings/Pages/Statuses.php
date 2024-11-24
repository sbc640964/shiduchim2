<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Proposal;
use App\Models\SettingOld as Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Components\Component as FilamentComponent;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Collection;

class Statuses extends Page implements Infolists\Contracts\HasInfolists
{
    use HasPageShield;

    protected static string $view = 'filament.pages.statuses';

    protected static ?string $navigationIcon = 'iconsax-bul-setting-4';

    protected static ?string $title = 'סטטוסים';

    protected static ?string $cluster = Settings::class;

//    public static function canAccess(): bool
//    {
//        return static::canView();
//    }

    public function statusForm(Form $form, ?Setting $statuses = null): Form
    {
        $statuses = $statuses ?? Setting::firstOrNew(['key' => 'statuses_proposal_person'], ['value' => []]);

        return $form->schema([
            Forms\Components\Split::make([
                Forms\Components\ColorPicker::make('color')->grow(false),
                Forms\Components\TextInput::make('name')
                    ->label('שם')
                    ->required(),
            ]),
            Forms\Components\Checkbox::make('update_existing_proposals')
                ->label('עדכן הצעות קיימות')
                ->default(true)
                ->helperText('במידה ושם הסטטוס ישתנה ישתנה גם בהצעות קיימות'),

            Forms\Components\Textarea::make('description')
                ->label('תיאור'),
            Forms\Components\Section::make('הגדרת שינוי סטטוס')
                ->schema([
                    Forms\Components\ToggleButtons::make('is_not_include')
                        ->label('סוג רשימה')
                        ->boolean('לבנה', 'שחורה')
                        ->inline()
                        ->grouped()
                        ->default(false),
                    Forms\Components\Select::make('list')
                        ->options(collect($statuses->value)->mapWithKeys(fn ($value) => [$value['name'] => $value['name']])->toArray())
                        ->label('רשימה')
                        ->multiple()
                        ->searchable(),
                ])
                ->collapsed()
                ->description('הגדר לאיזה סטטוסים ניתן להעביר במקרה שזה הסטטוס הנוכחי'),

            Forms\Components\Section::make('הגדרת תאריך טיפול הבא')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('next_date_delta')
                        ->native(false)
                        ->options([
                            'none' => 'ללא',
                            'day' => 'יום',
                            'week' => 'שבוע',
                            'month' => 'חודש',
                            'year' => 'שנה',
                        ])
                        ->selectablePlaceholder(false)
                        ->live()
                        ->label('יחידת זמן')
                        ->default('day'),
                    Forms\Components\TextInput::make('next_date_delta_value')
                        ->visible(fn (Forms\Get $get) => $get('next_date_delta') !== 'none')
                        ->numeric()
                        ->extraInputAttributes(['min' => 1])
                        ->minValue(1)
                        ->label('ערך')
                        ->default(1),

                    Forms\Components\Toggle::make('change_other_side_too')
                        ->columnSpanFull()
                        ->label('שנה גם לצד השני')
                        ->helperText('עדכון תאריך ישנה את התאריך לטיפול הבא בשני הצדדים')
                        ->default(false),
                ])
                ->collapsed()
                ->description('הגדר את ברירת המחדל עבור תאריך טיפול הבא'),

            Forms\Components\Toggle::make('is_default')
                ->label('סטטוס ברירת מחדל')
                ->helperText('סטטוס ברירת מחדל יוגדר אוטומטית במקרה שלא נבחר סטטוס אחר'),

            Forms\Components\Toggle::make('is_closed_status')
                ->label('סטטוס סגור')
                ->helperText('סטטוס סגור יבחר באופן אוטומטי עבור הצעות שנסגרות, לא ניתן לבחור סטטוס זה באופן ידני.')
                ->default(false),

            Forms\Components\Toggle::make('hidden_by_default')
                ->label('הסתר')
                ->helperText('בברירת מחדל סטטוס זה יהיה מוסתר ברשימת ההצעות')
                ->default(false),
        ]);
    }

    public function guyGirlStatusesInfolist(Infolist $infolist): Infolist
    {
        $guyGirlStatuses = Setting::firstOrNew(['key' => 'statuses_proposal_person'], ['value' => []]);
        //        $proposalStatuses = Setting::firstOrNew(['key' => 'statuses_proposal'], ['value' => []]);

        return $infolist
            ->record($guyGirlStatuses)
            ->schema([
                $this->getStatusesList($guyGirlStatuses, 'סטטוסים עבור בחור/בחורה בהצעה'),
            ]);
    }

    public function proposalStatuses(Infolist $infolist): Infolist
    {
        $proposalStatuses = Setting::firstOrNew(['key' => 'statuses_proposal'], ['value' => []]);

        return $infolist
            ->record($proposalStatuses)
            ->schema([
                $this->getStatusesList($proposalStatuses, 'סטטוסים עבור הצעה'),
            ]);
    }

    public function getIndex(FilamentComponent $component, bool $returnData = false): string|array
    {
        $index = str($component->getContainer()->getStatePath())
            ->afterLast('.')
            ->value();

        if ($returnData) {

            $data = $this->getRepeaterEntryState($component);

            if (isset($data[$index])) {
                return $data[$index];
            }
        }

        return $index;
    }

    public function getRepeaterEntryState($component)
    {
        $parent = $component->getContainer()?->getParentComponent();
        if ($parent instanceof Infolists\Components\RepeatableEntry) {
            return $parent->getState();
        }

        return $this->getRepeaterEntryState($parent);
    }

    private function getStatusesList(Setting $statuses, string $heading)
    {
        return Infolists\Components\Section::make($heading)
            ->key(base64_encode($heading))
            ->headerActions([
                Infolists\Components\Actions\Action::make('add')
                    ->label('הוסף סטטוס')
                    ->iconButton()
                    ->form(fn ($form) => $this->statusForm($form, $statuses))
                    ->action(fn ($data, $action) => $this->addOrUpdateStatus($data, $statuses) && $action->success())
                    ->icon('heroicon-o-plus'),
            ])
            ->schema([
                //                Infolists\Components\View::make('empty-statuses'),
                Infolists\Components\RepeatableEntry::make('value')
                    ->hiddenLabel()
                    ->contained(fn () => false)
                    ->extraAttributes(['class' => 'lite-repeater-container'])
                    ->hidden(count($statuses['value']) === 0)
                    ->schema(function () use ($statuses) {
                        return [
                            Infolists\Components\Split::make([
                                Infolists\Components\ColorEntry::make('color')
                                    ->hiddenLabel()
                                    ->grow(false)
                                    ->default(Color::Gray[600])
                                    ->label('צבע'),
                                Infolists\Components\TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'leading-none-text'])
                                    ->grow()
                                    ->helperText(fn ($component) => $this->getIndex($component, true)['description'] ?? null)
                                    ->label('שם'),
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('edit')
                                        ->icon('heroicon-o-pencil')
                                        ->iconButton()
                                        ->tooltip('ערוך')
                                        ->color(Color::Gray)
                                        ->size(ActionSize::Small)
                                        ->iconSize(IconSize::Small)
                                        ->fillForm(fn ($component) => $this->getIndex($component, true))
                                        ->form(fn ($form) => $this->statusForm($form, $statuses))
                                        ->successNotificationTitle('עודכן בהצלחה')
                                        ->modalHeading('ערוך סטטוס')
                                        ->modalWidth('lg')
                                        ->modalSubmitActionLabel('עדכן')
                                        ->action(function ($component, $data, Setting $record, Infolists\Components\Actions\Action $action) {
                                            if (\DB::transaction(fn ()
                                                => $this->addOrUpdateStatus($data, $record, $this->getIndex($component)))
                                            ) {
                                                $action->success();
                                            }
                                        }),

                                    Infolists\Components\Actions\Action::make('trash')
                                        ->icon('heroicon-o-trash')
                                        ->iconButton()
                                        ->tooltip('מחק')
                                        ->color('danger')
                                        ->size(ActionSize::Small)
                                        ->iconSize(IconSize::Small)
                                        ->requiresConfirmation()
                                        ->modalHeading('מחק סטטוס')
                                        ->successNotificationTitle('נמחק בהצלחה')
                                        ->modalDescription(fn ($component) => "האם אתה בטוח שברצונך למחוק את הסטטוס \"{$this->getIndex($component, true)['name']}\"?")
                                        ->action(function ($component, $data, $record, $action) {
                                            $newData = $record->value;
                                            unset($newData[$this->getIndex($component)]);
                                            $record->value = $newData;
                                            $record->save();

                                            $action->success();
                                        }),

                                ])
                                    ->grow(false),
                            ])
                                ->verticalAlignment('center')
                                ->extraAttributes(['class' => '!gap-4']),
                        ];
                    }),

                Infolists\Components\Actions::make([
                    Infolists\Components\Actions\Action::make('add')
                        ->label('הוסף סטטוס')
                        ->form(fn ($form) => $this->statusForm($form, $statuses))
                        ->action(fn ($data, $action) => $this->addOrUpdateStatus($data, $statuses) && $action->success())
                        ->icon('heroicon-o-plus-circle'),
                ]),
            ]);
    }

    private function addOrUpdateStatus($data, Setting $statuses, ?int $index = null): bool
    {
        $newValue = $statuses->value ?? [];

        if ($data['is_default'] ?? false) {
            foreach ($newValue as $key => $value) {
                $newValue[$key]['is_default'] = false;
            }
        }

        if($data['is_closed_status'] ?? false) {
            foreach ($newValue as $key => $value) {
                $newValue[$key]['is_closed_status'] = false;
            }
        }

        $index !== null
            ? $newValue[$index] = $data
            : $newValue[] = $data;


        if(
            $index !== null
            && $statuses->value[$index]['name'] !== $data['name']
            && $data['update_existing_proposals'] ?? false
        ) {
            $statusKeyInProposals = match ($statuses->getAttributes()['key']) {
                'statuses_proposal' => ['status'],
                'statuses_proposal_person' => ['status_guy', 'status_girl'],
                default => [],
            };

            foreach ($statusKeyInProposals as $key) {
                Proposal::where($key, $statuses->value[$index]['name'])
                    ->update([$key => $data['name']]);
            }
        }

        $statuses->value = $newValue;

        return $statuses->save();
    }

    public static function getProposalStatuses(): Collection
    {
        return collect(Setting::rememberCache('statuses_proposal',[])->value);
    }

    public static function getGuyGirlStatuses(): Collection
    {
        return collect(Setting::rememberCache('statuses_proposal_person',[])->value);
    }

    public static function getDefaultProposalStatus(): ?string
    {
        return static::getProposalStatuses()
            ->firstWhere('is_default', true)
            ['name']  ?? null;
    }

    public static function getDefaultGuyGirlStatus(): ?string
    {
        return static::getGuyGirlStatuses()
            ->firstWhere('is_default', true)
            ['name'] ?? null;
    }

    public static function getClosedProposalStatus(): string
    {
        return static::getProposalStatuses()
            ->firstWhere('is_closed_status', true)
            ['name'] ?? 'closed';
    }
}
