<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function view(User $user, Video $video): bool
    {
        return $video->organization_id === $user->currentOrganization()?->id;
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
