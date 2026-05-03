<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\FeeGroup;
use App\Models\Tenant\FeeType;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Fee Catalog — a single, user-friendly page that merges Fee Group and
 * Fee Type management. Schools think of fees as a hierarchy ("Tuition
 * has Monthly, Annual, Re-test fees"), not as two independent lists.
 *
 * Header actions add new groups / types. Each row inline-edits or
 * deletes. Quick add: when adding a Fee Type, you can create the parent
 * group on the fly from the same modal.
 */
class FeeCatalog extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_fee_structures';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Fee Catalog';

    protected string $view = 'filament.school-admin.pages.fee-catalog';

    public function getTitle(): string
    {
        return 'Fee Catalog';
    }

    public function getSubheading(): ?string
    {
        return 'All fee categories and the specific fees inside them, in one place. Click "+ Add Fee Type" to create a chargeable fee — choose its group, name and whether it\'s monthly or one-time.';
    }

    /**
     * @return array<int,array{group:FeeGroup, types:\Illuminate\Support\Collection<int,FeeType>}>
     */
    public function catalog(): array
    {
        $groups = FeeGroup::query()->orderBy('name')->get();
        $allTypes = FeeType::query()
            ->withCount('feeMasters')
            ->orderBy('name')
            ->get()
            ->groupBy('fee_group_id');

        $rows = $groups->map(fn (FeeGroup $g) => [
            'group' => $g,
            'types' => $allTypes->get($g->id, collect()),
        ])->all();

        // Orphan types (no group) — shouldn't happen but show them anyway.
        $orphans = $allTypes->get(null, collect());
        if ($orphans->isNotEmpty()) {
            $rows[] = [
                'group' => (object) ['id' => null, 'name' => 'Ungrouped', 'description' => null],
                'types' => $orphans,
            ];
        }

        return $rows;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addType')
                ->label('+ Add Fee Type')
                ->icon('heroicon-o-tag')
                ->color('primary')
                ->modalHeading('Add a fee type')
                ->modalDescription('A fee type is a specific charge — e.g. "Monthly Tuition" or "Admission Fee".')
                ->form([
                    Components\Select::make('fee_group_id')
                        ->label('Group')
                        ->options(fn () => FeeGroup::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Components\TextInput::make('name')->required()->maxLength(100)
                                ->placeholder('e.g. Tuition · Transport · Boarding'),
                            Components\Textarea::make('description')->maxLength(255)->rows(2),
                        ])
                        ->createOptionUsing(fn (array $data): string => FeeGroup::create($data)->id),

                    Components\TextInput::make('name')
                        ->required()->maxLength(100)
                        ->placeholder('e.g. Monthly Tuition · Admission Fee · Re-test Fee'),

                    Components\Toggle::make('is_recurring')
                        ->label('Recurring (monthly)?')
                        ->default(false)
                        ->helperText('If on, this fee is included in monthly roll-outs. Off = one-time charge.'),
                ])
                ->action(function (array $data) {
                    FeeType::create([
                        'fee_group_id' => $data['fee_group_id'],
                        'name'         => $data['name'],
                        'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                    ]);
                    Notification::make()->title('Fee type added')->success()->send();
                }),

            Action::make('addGroup')
                ->label('+ New Group')
                ->icon('heroicon-o-folder-plus')
                ->color('gray')
                ->modalHeading('Add a fee group (category)')
                ->form([
                    Components\TextInput::make('name')->required()->maxLength(100)
                        ->placeholder('e.g. Tuition · Transport · Boarding'),
                    Components\Textarea::make('description')->maxLength(255)->rows(2),
                ])
                ->action(function (array $data) {
                    FeeGroup::create($data);
                    Notification::make()->title('Group added')->success()->send();
                }),
        ];
    }

    public function deleteType(string $id): void
    {
        $type = FeeType::query()->withCount('feeMasters', 'studentFees')->find($id);
        if (! $type) { return; }
        if ($type->fee_masters_count > 0 || $type->student_fees_count > 0) {
            Notification::make()
                ->title('Cannot delete')
                ->body('This fee type is used in the Fee Structure or has invoices. Remove those references first.')
                ->danger()->send();
            return;
        }
        $type->delete();
        Notification::make()->title('Fee type removed')->success()->send();
    }

    public function deleteGroup(string $id): void
    {
        $group = FeeGroup::query()->withCount('feeTypes')->find($id);
        if (! $group) { return; }
        if ($group->fee_types_count > 0) {
            Notification::make()->title('Cannot delete')
                ->body('Move or delete the fee types inside this group first.')
                ->danger()->send();
            return;
        }
        $group->delete();
        Notification::make()->title('Group removed')->success()->send();
    }

    public function toggleRecurring(string $typeId): void
    {
        $type = FeeType::find($typeId);
        if ($type) {
            $type->update(['is_recurring' => ! $type->is_recurring]);
            Notification::make()->title('Updated')->success()->send();
        }
    }
}
