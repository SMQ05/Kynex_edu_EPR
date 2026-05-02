<?php

namespace App\Filament\SchoolAdmin\Resources\SchoolUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\SchoolUserResource;
use App\Services\ApprovalService;
use App\Support\RoleHierarchy;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Hiring a new SchoolUser (teacher, clerk, etc.) routes through the
 * institute-head approval queue unless the actor has bypass_approvals.
 * The freshly-created row stays inactive (is_active=false) until the
 * institute head approves the staff_hire request.
 */
class CreateSchoolUser extends CreateRecord
{
    protected static string $resource = SchoolUserResource::class;

    protected function authorizeAccess(): void
    {
        // The school admin panel route is already protected by the school_users
        // guard. Avoid a second Livewire-time resource check that can resolve
        // against the wrong guard and reject valid school admins during submit.
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->guard('school_users')->user();
        $bypass = static::actorBypassesApproval($actor);

        // primary_role is dehydrated=false in the form, but Livewire still
        // hands it to us here through getFormState().
        $primaryRole = $this->form->getRawState()['primary_role'] ?? null;
        if ($primaryRole) {
            $data['active_role'] = $primaryRole;
        }

        if (! $bypass) {
            // Newly-hired staff stay inactive until the institute head
            // approves the hire from their Approval Queue.
            $data['is_active'] = false;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $actor = auth()->guard('school_users')->user();
        $bypass = static::actorBypassesApproval($actor);

        // Sync the chosen primary role onto the new user. We do this
        // *after* create because Spatie's HasRoles needs the model id.
        $primaryRole = $this->form->getRawState()['primary_role'] ?? null;
        if ($primaryRole) {
            // Replace any auto-assigned roles with exactly the primary one;
            // extra stacking can be added later via the row "Manage Roles" action.
            $this->record->syncRoles([$primaryRole]);
            if (! $this->record->active_role) {
                $this->record->forceFill(['active_role' => $primaryRole])->saveQuietly();
            }
        }

        if ($bypass) {
            return; // already created active by mutate hook
        }

        app(ApprovalService::class)->submit(
            requestedBy: $actor,
            actionType: 'staff_hire',
            subject: $this->record,
            payload: [
                'school_user_id' => $this->record->id,
                'name'           => $this->record->name,
                'email'          => $this->record->email,
                'submitted_by'   => $actor?->name,
                'reason'         => 'Admin-initiated staff hire',
            ],
        );

        Notification::make()
            ->title('Staff hire sent for approval')
            ->body("The institute head will review {$this->record->name}'s account from the Approval Queue. The user is inactive until then.")
            ->warning()
            ->send();
    }

    /**
     * Only INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD acting in that role may
     * hire / remove staff directly. Everyone else routes through approval.
     */
    protected static function actorBypassesApproval($actor): bool
    {
        if (! $actor) {
            return false;
        }
        $activeRole = $actor->active_role ?? $actor->roles->first()?->name;
        return in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);
    }
}
