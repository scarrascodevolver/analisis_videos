<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'season'];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
