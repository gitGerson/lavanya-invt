<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Invitation;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_invitation');
    }

    public function view(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('view_invitation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invitation');
    }

    public function update(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('update_invitation');
    }

    public function delete(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('delete_invitation');
    }

    public function restore(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('restore_invitation');
    }

    public function forceDelete(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('force_delete_invitation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_invitation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_invitation');
    }

    public function replicate(AuthUser $authUser, Invitation $invitation): bool
    {
        return $authUser->can('replicate_invitation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_invitation');
    }

}