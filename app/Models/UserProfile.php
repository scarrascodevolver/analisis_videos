<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'position',
        'secondary_position',
        'player_number',
        'weight',
        'height',
        'date_of_birth',
        'goals',
        'coaching_experience',
        'certifications',
        'specializations',
        'club_team_organization',
        'division_category',
        'user_category_id',
        'can_receive_assignments',
        'avatar',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'specializations' => 'array',
        'can_receive_assignments' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'user_category_id');
    }
}
