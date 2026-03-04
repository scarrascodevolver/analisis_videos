<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function view(User $user, Video $video): bool
    {
        // Video propio de la org
        if ($video->organization_id === $user->currentOrganization()?->id) {
            return true;
        }

        // Video compartido cross-org (VideoOrgShare activo)
        $orgId = $user->currentOrganization()?->id;
        if (! $orgId) {
            return false;
        }

        return \App\Models\VideoOrgShare::where('video_id', $video->id)
            ->where('target_organization_id', $orgId)
            ->where('status', 'active')
            ->exists();
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
