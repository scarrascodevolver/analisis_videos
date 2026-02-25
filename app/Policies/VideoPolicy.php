<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    /**
     * Any authenticated user can reach video listing routes.
     * The BelongsToOrganization global scope enforces org isolation at query level.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user can view a video (e.g. its lineups, stats, annotations) when they
     * belong to the same organization AND have a staff-level role.
     * Players access videos through assignment/visibility scopes, not this policy.
     */
    public function view(User $user, Video $video): bool
    {
        return $video->organization_id === $user->currentOrganization()?->id
            && in_array($user->role, [
                'analista', 'entrenador', 'staff',
                'director_tecnico', 'director_club', 'super_admin',
            ]);
    }

    public function update(User $user, Video $video): bool
    {
        return $video->organization_id === $user->currentOrganization()?->id
            && (in_array($user->role, ['analista', 'entrenador']) || $video->uploaded_by === $user->id);
    }

    public function delete(User $user, Video $video): bool
    {
        return $video->organization_id === $user->currentOrganization()?->id
            && (in_array($user->role, ['analista', 'entrenador']) || $video->uploaded_by === $user->id);
    }
}
