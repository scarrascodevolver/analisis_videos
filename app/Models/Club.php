<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Club extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['name', 'slug'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Club $club) {
            if (empty($club->slug)) {
                $club->slug = self::makeUniqueSlug($club->name, $club->organization_id);
            }
        });
    }

    public static function makeUniqueSlug(string $name, int $orgId): string
    {
        $base  = Str::slug($name);
        $slug  = $base;
        $count = 2;

        while (self::withoutGlobalScopes()->where('organization_id', $orgId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
