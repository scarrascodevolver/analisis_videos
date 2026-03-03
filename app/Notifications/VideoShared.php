<?php

namespace App\Notifications;

use App\Models\Video;
use App\Models\Organization;
use Illuminate\Notifications\Notification;

class VideoShared extends Notification
{
    public function __construct(
        public Video $video,
        public Organization $sourceOrg,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'video_shared',
            'video_id'        => $this->video->id,
            'video_title'     => $this->video->title,
            'source_org_id'   => $this->sourceOrg->id,
            'source_org_name' => $this->sourceOrg->name,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
