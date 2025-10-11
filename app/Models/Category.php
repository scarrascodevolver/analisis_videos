<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function userProfiles()
    {
        return $this->hasMany(UserProfile::class, 'user_category_id');
    }
}
