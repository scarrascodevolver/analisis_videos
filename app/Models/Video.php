<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'file_name',
        'file_size',
        'mime_type',
        'duration',
        'uploaded_by',
        'analyzed_team_id',
        'rival_team_id',
        'category_id',
        'rugby_situation_id',
        'match_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function analyzedTeam()
    {
        return $this->belongsTo(Team::class, 'analyzed_team_id');
    }

    public function rivalTeam()
    {
        return $this->belongsTo(Team::class, 'rival_team_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(VideoComment::class);
    }

    public function assignments()
    {
        return $this->hasMany(VideoAssignment::class);
    }

    public function rugbySituation()
    {
        return $this->belongsTo(RugbySituation::class);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByRugbySituation($query, $situationId)
    {
        return $query->where('rugby_situation_id', $situationId);
    }

    public function scopeByRugbyCategory($query, $rugbyCategory)
    {
        return $query->whereHas('rugbySituation', function($q) use ($rugbyCategory) {
            $q->where('category', $rugbyCategory);
        });
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('analyzed_team_id', $teamId);
    }
}
