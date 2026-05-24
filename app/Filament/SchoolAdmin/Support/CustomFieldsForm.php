<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Support;

use App\Models\Tenant\CustomField;
use App\Models\Tenant\CustomFieldValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Renders admin-defined CustomField definitions as Filament form
 * components, and loads/saves their values. Components live under the
 * `custom_fields` statePath (an array keyed by the field's machine key)
 * so they don't collide with the host model's own attributes.
 *
 * Surface on a resource form (e.g. StudentResource / StaffResource):
 *
 *   public static function form(Schema $schema): Schema
 *   {
 *       return $schema->schema([
 *           // ...native fields...
 *           ...CustomFieldsForm::section('student'),
 *       ]);
 *   }
 *
 *   // EditX page:
 *   protected function mutateFormDataBeforeFill(array $data): array
 *   {
 *       $data['custom_fields'] = CustomFieldsForm::load('student', $this->record->id);
 *       return $data;
 *   }
 *
 *   // CreateX + EditX pages, after save:
 *   protected function afterSave(): void  // or afterCreate()
 *   {
 *       CustomFieldsForm::save('student', $this->record->id, $this->data['custom_fields'] ?? []);
 *   }
 */
class CustomFieldsForm
{
    /**
     * The set of components (no wrapping section). Returns [] when no
     * active definitions exist, so callers can spread safely.
     *
     * @return array<int,mixed>
     */
    public static function components(string $entity): array
    {
        $fields = CustomField::activeFor($entity);

        $components = [];
        foreach ($fields as $field) {
            $components[] = static::componentFor($field);
        }

        return $components;
    }

    /**
     * The components wrapped in a collapsible "Additional Information"
     * Section, hidden entirely when there are no custom fields.
     *
     * @return array<int,mixed>
     */
    public static function section(string $entity, string $heading = 'Additional Information'): array
    {
        $components = static::components($entity);

        if ($components === []) {
            return [];
        }

        return [
            Section::make($heading)
                ->description('Custom fields defined under Settings → Custom Fields.')
                ->columns(2)
                ->collapsible()
                ->schema($components),
        ];
    }

    /** Build the right Filament component for a definition. */
    protected static function componentFor(CustomField $field)
    {
        $name = 'custom_fields.' . $field->key;
        $required = $field->required;

        $component = match ($field->type) {
            'number'   => TextInput::make($name)->numeric(),
            'date'     => DatePicker::make($name),
            'textarea' => Textarea::make($name)->rows(3)->columnSpanFull(),
            'toggle'   => Toggle::make($name),
            'select'   => Select::make($name)
                ->options(static::selectOptions($field))
                ->native(false)
                ->searchable(),
            default    => TextInput::make($name)->maxLength(255),
        };

        $component = $component
            ->label($field->label)
            ->required($required && $field->type !== 'toggle');

        if ($field->help_text) {
            $component = $component->helperText($field->help_text);
        }

        return $component;
    }

    /** @return array<string,string> */
    protected static function selectOptions(CustomField $field): array
    {
        $options = $field->options ?? [];
        $out = [];
        foreach ($options as $opt) {
            if (is_array($opt)) {
                $value = $opt['value'] ?? ($opt['label'] ?? null);
                $label = $opt['label'] ?? $value;
            } else {
                $value = $label = (string) $opt;
            }
            if ($value !== null && $value !== '') {
                $out[(string) $value] = (string) $label;
            }
        }

        return $out;
    }

    /**
     * Load saved values for an entity record, keyed by field machine key.
     *
     * @return array<string,mixed>
     */
    public static function load(string $entity, string $entityId): array
    {
        $fields = CustomField::activeFor($entity)->keyBy('id');

        $values = CustomFieldValue::query()
            ->where('entity', $entity)
            ->where('entity_id', $entityId)
            ->get();

        $out = [];
        foreach ($values as $value) {
            $field = $fields->get($value->custom_field_id);
            if (! $field) {
                continue;
            }
            $out[$field->key] = static::castOut($field->type, $value->value);
        }

        return $out;
    }

    /**
     * Persist submitted custom-field values for an entity record.
     *
     * @param  array<string,mixed>  $data  key => raw value (from statePath custom_fields)
     */
    public static function save(string $entity, string $entityId, array $data): void
    {
        $fields = CustomField::activeFor($entity);

        foreach ($fields as $field) {
            $raw = $data[$field->key] ?? null;

            CustomFieldValue::query()->updateOrCreate(
                [
                    'custom_field_id' => $field->id,
                    'entity_id'       => $entityId,
                ],
                [
                    'entity' => $entity,
                    'value'  => static::castIn($field->type, $raw),
                ],
            );
        }
    }

    /** Normalise a value for storage (everything is stored as a string). */
    protected static function castIn(string $type, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($type === 'toggle') {
            return $raw ? '1' : '0';
        }

        return is_scalar($raw) ? (string) $raw : json_encode($raw);
    }

    /** Convert a stored string back to a form-friendly value. */
    protected static function castOut(string $type, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'toggle' => (bool) $value,
            'number' => is_numeric($value) ? $value + 0 : $value,
            default  => $value,
        };
    }
}
