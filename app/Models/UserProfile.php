<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'experience_level',
        'position',
        'club_team_organization',
        'division_category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
