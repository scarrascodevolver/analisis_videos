<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RugbySituation extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'color',
        'active',
        'sort_order'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
