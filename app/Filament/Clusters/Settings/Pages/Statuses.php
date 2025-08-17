<?php

namespace App\Filament\Clusters\Settings\Pages;

use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Flex;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Actions\Action;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Support\Enums\Size;
use DB;
use App\Filament\Clusters\Settings;
use App\Models\Proposal;
use App\Models\SettingOld as Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Infolists;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Components\Component as FilamentComponent;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Collection;

class Statuses extends Page implements HasInfolists
{
    use HasPageShield;

    protected string $view = 'filament.pages.statuses';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-setting-4';

    protected static ?string $title = 'סטטוסים';

    protected static ?string $cluster = Settings::class;

//    public static function canAccess(): bool
//    {
//        return static::canView();
//    }

    public function statusForm(Schema $schema, ?Setting $statuses = null): Schema
    {
        $statuses = $statuses ?? Setting::firstOrNew(['key' => 'statuses_proposal_person'], ['value' => []]);

        return $schema->components([
            Flex::make([
                ColorPicker::make('color')->grow(false),
                TextInput::make('name')
                    ->label('שם')
                    ->required(),
            ]),
            Checkbox::make('update_existing_proposals')
                ->label('עדכן הצעות קיימות')
                ->default(true)
                ->helperText('במידה ושם הסטטוס ישתנה ישתנה גם בהצעות קיימות'),

            Textarea::make('description')
                ->label('תיאור'),
            Section::make('הגדרת שינוי סטטוס')
                ->schema([
                    ToggleButtons::make('is_not_include')
                        ->label('סוג רשימה')
                        ->boolean('לבנה', 'שחורה')
                        ->inline()
                        ->grouped()
                        ->default(false),
                    Select::make('list')
                        ->options(collect($statuses->value)->mapWithKeys(fn ($value) => [$value['name'] => $value['name']])->toArray())
                        ->label('רשימה')
                        ->multiple()
                        ->searchable(),
                ])
                ->collapsed()
                ->description('הגדר לאיזה סטטוסים ניתן להעביר במקרה שזה הסטטוס הנוכחי'),

            Section::make('הגדרת תאריך טיפול הבא')
                ->columns(2)
                ->schema([
                    Select::make('next_date_delta')
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
                    TextInput::make('next_date_delta_value')
                        ->visible(fn (Get $get) => $get('next_date_delta') !== 'none')
                        ->numeric()
                        ->extraInputAttributes(['min' => 1])
                        ->minValue(1)
                        ->label('ערך')
                        ->default(1),

                    Toggle::make('change_other_side_too')
                        ->columnSpanFull()
                        ->label('שנה גם לצד השני')
                        ->helperText('עדכון תאריך ישנה את התאריך לטיפול הבא בשני הצדדים')
                        ->default(false),
                ])
                ->collapsed()
                ->description('הגדר את ברירת המחדל עבור תאריך טיפול הבא'),

            Toggle::make('is_default')
                ->label('סטטוס ברירת מחדל')
                ->helperText('סטטוס ברירת מחדל יוגדר אוטומטית במקרה שלא נבחר סטטוס אחר'),

            Toggle::make('is_closed_status')
                ->label('סטטוס סגור')
                ->helperText('סטטוס סגור יבחר באופן אוטומטי עבור הצעות שנסגרות, לא ניתן לבחור סטטוס זה באופן ידני.')
                ->default(false),

            Toggle::make('hidden_by_default')
                ->label('הסתר')
                ->helperText('בברירת מחדל סטטוס זה יהיה מוסתר ברשימת ההצעות')
                ->default(false),
        ]);
    }

    public function guyGirlStatusesInfolist(Schema $schema): Schema
    {
        $guyGirlStatuses = Setting::firstOrNew(['key' => 'statuses_proposal_person'], ['value' => []]);
        //        $proposalStatuses = Setting::firstOrNew(['key' => 'statuses_proposal'], ['value' => []]);

        return $schema
            ->record($guyGirlStatuses)
            ->components([
                $this->getStatusesList($guyGirlStatuses, 'סטטוסים עבור בחור/בחורה בהצעה'),
            ]);
    }

    public function proposalStatuses(Schema $schema): Schema
    {
        $proposalStatuses = Setting::firstOrNew(['key' => 'statuses_proposal'], ['value' => []]);

        return $schema
            ->record($proposalStatuses)
            ->components([
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
        if ($parent instanceof RepeatableEntry) {
            return $parent->getState();
        }

        return $this->getRepeaterEntryState($parent);
    }

    private function getStatusesList(Setting $statuses, string $heading)
    {
        return Section::make($heading)
            ->key(base64_encode($heading))
            ->headerActions([
                Action::make('add')
                    ->label('הוסף סטטוס')
                    ->iconButton()
                    ->schema(fn ($form) => $this->statusForm($form, $statuses))
                    ->action(fn ($data, $action) => $this->addOrUpdateStatus($data, $statuses) && $action->success())
                    ->icon('heroicon-o-plus'),
            ])
            ->schema([
                //                Infolists\Components\View::make('empty-statuses'),
                RepeatableEntry::make('value')
                    ->hiddenLabel()
                    ->contained(fn () => false)
                    ->extraAttributes(['class' => 'lite-repeater-container'])
                    ->hidden(count($statuses['value']) === 0)
                    ->schema(function () use ($statuses) {
                        return [
                            Flex::make([
                                ColorEntry::make('color')
                                    ->hiddenLabel()
                                    ->grow(false)
                                    ->default(Color::Gray[600])
                                    ->label('צבע'),
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'leading-none-text'])
                                    ->grow()
                                    ->helperText(fn ($component) => $this->getIndex($component, true)['description'] ?? null)
                                    ->label('שם'),
                                Actions::make([
                                    Action::make('edit')
                                        ->icon('heroicon-o-pencil')
                                        ->iconButton()
                                        ->tooltip('ערוך')
                                        ->color(Color::Gray)
                                        ->size(Size::Small)
                                        ->iconSize(IconSize::Small)
                                        ->fillForm(fn ($component) => $this->getIndex($component, true))
                                        ->schema(fn ($form) => $this->statusForm($form, $statuses))
                                        ->successNotificationTitle('עודכן בהצלחה')
                                        ->modalHeading('ערוך סטטוס')
                                        ->modalWidth('lg')
                                        ->modalSubmitActionLabel('עדכן')
                                        ->action(function ($component, $data, Setting $record, Action $action) {
                                            if (DB::transaction(fn ()
                                                => $this->addOrUpdateStatus($data, $record, $this->getIndex($component)))
                                            ) {
                                                $action->success();
                                            }
                                        }),

                                    Action::make('trash')
                                        ->icon('heroicon-o-trash')
                                        ->iconButton()
                                        ->tooltip('מחק')
                                        ->color('danger')
                                        ->size(Size::Small)
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

                Actions::make([
                    Action::make('add')
                        ->label('הוסף סטטוס')
                        ->schema(fn ($form) => $this->statusForm($form, $statuses))
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
