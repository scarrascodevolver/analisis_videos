<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Notifications\Notification;

class TournamentJoinRequest extends Notification
{
    public function __construct(
        public TournamentRegistration $registration,
        public Tournament $tournament,
        public Organization $clubOrg,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'tournament_join_request',
            'registration_id' => $this->registration->id,
            'tournament_id'   => $this->tournament->id,
            'tournament_name' => $this->tournament->name,
            'club_org_id'     => $this->clubOrg->id,
            'club_org_name'   => $this->clubOrg->name,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
