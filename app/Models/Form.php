<?php

namespace App\Models;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Actions\Action as Action;
use Filament\Actions\ActionGroup as ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'name', 'resource', 'fields', 'edit_pleases', 'view_pleases',
    ];

    protected $casts = [
        'fields' => 'array',
        'edit_pleases' => 'array',
        'view_pleases' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FormEntry::class);
    }

    public static function getInfolistFields(Form $form): array
    {
        return collect($form->fields)->map(function ($field) {
            return static::generateEntryInfolist($field);
        })->filter()->toArray();
    }

    public static function generateEntryInfolist(array $field): ?Entry
    {
        if (empty($field['type']) || empty($field['label'])) {
            return null;
        }

        $fieldInfolist = match ($field['type']) {
            default => TextEntry::class,
        };

        $fieldInfolist = $fieldInfolist::make($field['label'])
            ->label($field['label'])
            ->when($field['help'] ?? null, fn ($component) => $component->helpText($field['help']));

        return $fieldInfolist;
    }

    public static function getFormFields(Form $form): array
    {
        return [
            Group::make([
                ...collect($form->fields)->map(function ($field) {
                    return static::generateField($field);
                })->filter()->toArray(),
            ])->inlineLabel(),
        ];

    }

    public static function generateField(array $field): ?Field
    {
        if (empty($field['type']) || empty($field['label'])) {
            return null;
        }

        $fieldForm = match ($field['type']) {
            'select' => Select::class,
            'checkbox' => Checkbox::class,
            'radio' => Radio::class,
            'textarea' => Textarea::class,
            'date', 'datetime' => DateTimePicker::class,
            default => TextInput::class,
        };

        return $fieldForm::make($field['label'])
            ->label($field['label'])
            ->when($field['required'] ?? null, fn ($component) => $component->required())
            ->when($field['placeholder'] ?? null, fn ($component) => $component->placeholder($field['placeholder']))
            ->when($field['help'] ?? null, fn ($component) => $component->helperText($field['help']))
            ->when(in_array($field['type'], ['date', 'datetime']), fn (DateTimePicker $component) => $component
                ->format($field['type'] === 'datetime' ? 'd/m/Y H:i:s' : 'd/m/Y')
                ->native(false)
            )
            ->when($field['type'] === 'number', fn (TextInput $component) => $component->numeric())
            ->when($field['type'] === 'email', fn (TextInput $component) => $component->email())
            ->when(in_array($field['type'], ['checkbox', 'radio', 'select']), fn ($component) => $component
                ->options($field['options'])
                ->native(false)
            );
    }

    /**
     * @return array<ActionGroup|Action>
     */
    public static function getActions(string $resource, ?string $type = null, ?bool $onGroup = false): array
    {
        $type = $type ?? Action::class;

        $groupType = match ($type) {
            Action::class => ActionGroup::class,
            default => ActionGroup::class,
        };

        $forms = Form::where('resource', $resource)
            ->where(function (Builder $builder) {
                $builder->whereJsonContains('edit_pleases', ['place' => 'list'])
                    ->orWhereJsonContains('view_pleases', ['place' => 'list']);

            })->get();

        $actions = [];
        $groupedActions = [];

        foreach ($forms as $formModel) {
            $actionsConfig = [];

            $actionsConfig[] = array_merge(collect($formModel->edit_pleases)->firstWhere('place', 'list') ?? [], ['_type' => 'edit']);
            $actionsConfig[] = array_merge(collect($formModel->view_pleases)->firstWhere('place', 'list') ?? [], ['_type' => 'view']);

            collect($actionsConfig)
                ->filter(fn ($actionConfig) => $actionConfig['place'] ?? false)
                ->each(function ($actionConfig) use ($formModel, &$actions, &$groupedActions, $onGroup, $type) {

                    $isButtonIcon = $actionConfig['type_label'] === 'tooltip' && ! ($actionConfig['is_grouped'] ?? null);

                    $prepareAction = $type::make("action-$$formModel->id-$actionConfig[_type]")
                        ->when($isButtonIcon, function ($action) use ($actionConfig) {
                            $action->iconButton();
                            $action->tooltip(filled($actionConfig['label']) ? $actionConfig['label'] : null);
                        })
                        ->when($actionConfig['_type'] === 'edit', function ($action) use ($formModel) {
                            $action
                                ->form(fn (Schema $schema) => $schema->components(static::getFormFields($formModel)))
                                ->action(function ($data, $record, $action) use ($formModel) {
                                    if (method_exists($record, 'entry')) {
                                        $record->entry()->create([
                                            'form_id' => $formModel->id,
                                            'data' => $data,
                                            'model' => $record::class,
                                            'model_id' => $record->id,
                                        ]);

                                        $action->success();
                                    } else {
                                        $action->failure();
                                    }
                                })
                                ->fillForm(function ($record) {
                                    if (method_exists($record, 'entry')) {
                                        return $record->entry?->data ?? [];
                                    }

                                    return [];
                                });
                        })
                        ->when($actionConfig['_type'] === 'view', function (Action $action) {
                            $action
                                ->schema(fn (Schema $schema, $record) => $schema
//                                    ->schema(static::getInfolistFields($formModel))
                                    ->record($record->entry ?? new FormEntry())
                                    ->components([
                                        KeyValueEntry::make('data')
                                            ->hiddenLabel()
                                            ->keyLabel('מפתח')
                                            ->valueLabel('ערך'),
                                    ])
                                );
                        })
                        ->modalWidth('lg')
                        ->slideOver()
                        ->icon($actionConfig['icon'] ?? null)
                        ->label($actionConfig['label'] ?? $formModel->name);

                    if ($onGroup || $actionConfig['is_grouped']) {
                        $groupedActions[] = $prepareAction;
                    } else {
                        $actions[] = $prepareAction;
                    }
                });

        }

        return $onGroup
            ? [$groupType::make($groupedActions)]
            : [...$actions, $groupType::make($groupedActions)];
    }
}
